<?php

namespace App\Services\BillingRobo;

use App\Models\Contract;
use App\Models\SiteSetting;
use Carbon\Carbon;
use DateTimeInterface;

/**
 * 請求スケジュール判定（月末5営業日ルール）。
 * API1 individual と API3 demand に渡す発行日・送付日・決済期限の月/日を返す。
 * 営業日は土日のみ除外（祝日は未対応）。タイムゾーンは Asia/Tokyo 固定。
 */
class BillingScheduleService
{
    private const TIMEZONE = 'Asia/Tokyo';

    /*
     * 仕様（利用規約 第4条 4-5 項）
     *   月末5営業日以前申込: 請求書発行=申込月末, 決済(=売上計上)=翌月1日
     *   月末5営業日以内申込: 請求書発行=申込翌月末, 決済(=売上計上)=翌々月1日
     *
     * 実装方針
     *   - start_date は「課金開始月の1日」(以前→翌月1日 / 以内→翌々月1日) を送る。
     *     → Billing Robo の「対象期間開始日」設定により 売上計上日 が自動で start_date月の1日になる。
     *   - 発行日・送付日は start_date の前月末日（issue_month=-1, day=99）。
     *   - クレジットの決済期限は start_date 月の1日（deadline_month=0, day=1）。カード決済日。
     *   - 銀行振込の支払期限は start_date 月の末日（deadline_month=0, day=99）。
     *     通常=申込翌月末、最終5営業日以内=翌々月末。サイト設定の deadline は使わない。
     *   - 上記オフセットは within/after で同じ。差分は start_date のみ。
     *   - issue_month/deadline_month の値域: API 仕様 -60〜60 を許容。
     */
    private const WITHIN_ISSUE_MONTH = -1;
    private const WITHIN_ISSUE_DAY = 99;
    private const WITHIN_SENDING_MONTH = -1;
    private const WITHIN_SENDING_DAY = 99;
    private const WITHIN_DEADLINE_MONTH = 0;
    private const WITHIN_DEADLINE_DAY = 1;

    private const AFTER_ISSUE_MONTH = -1;
    private const AFTER_ISSUE_DAY = 99;
    private const AFTER_SENDING_MONTH = -1;
    private const AFTER_SENDING_DAY = 99;
    private const AFTER_DEADLINE_MONTH = 0;
    private const AFTER_DEADLINE_DAY = 1;

    /** 銀行振込の支払期限: start_date 月の末日（クレジットの day=1 には上書きしない） */
    private const BANK_TRANSFER_DEADLINE_MONTH = 0;
    private const BANK_TRANSFER_DEADLINE_DAY = 99;

    /**
     * 指定日が「月末最終5営業日から月末まで」の期間に含まれるか。
     * 営業日 = 土日を除く。最終5営業日のうち最も早い日(=しきい値)以降、月末日までを true とする。
     * しきい値日以降であれば、その後の土日も「以内」として扱う（利用規約 第4条 4-5 項）。
     */
    public function isWithinLast5BusinessDaysOfMonth(DateTimeInterface $date): bool
    {
        $carbon = Carbon::instance($date)->timezone(self::TIMEZONE);
        $year = (int) $carbon->format('Y');
        $month = (int) $carbon->format('n');
        $last5 = $this->getLast5BusinessDaysOfMonth($year, $month);
        if ($last5 === []) {
            return false;
        }
        $threshold = min($last5);
        $day = (int) $carbon->format('j');
        return $day >= $threshold;
    }

    /**
     * 課金開始月の1日を返す。
     * 利用規約 第4条 4-5 項のルール:
     *   申込日が月末5営業日「以前」 → 翌月1日が課金開始日
     *   申込日が月末5営業日「以内」 → 翌々月1日が課金開始日
     * 申込日の基準は desired_start_date（無ければ actual_start_date / now()）。
     */
    public function getBillingStartDateForApplication(Contract $contract): Carbon
    {
        $base = $contract->desired_start_date ?? $contract->actual_start_date ?? null;
        $date = $base instanceof DateTimeInterface
            ? Carbon::instance($base)->timezone(self::TIMEZONE)
            : ($base !== null
                ? Carbon::parse((string) $base, self::TIMEZONE)
                : Carbon::now(self::TIMEZONE));
        $offsetMonths = $this->isWithinLast5BusinessDaysOfMonth($date) ? 2 : 1;
        return $date->copy()->addMonthsNoOverflow($offsetMonths)->startOfMonth();
    }

    /**
     * 当該月の月末5営業日（日付のリスト）を返す。
     *
     * @return array<int>
     */
    public function getLast5BusinessDaysOfMonth(int $year, int $month): array
    {
        $last = Carbon::create($year, $month, 1, 0, 0, 0, self::TIMEZONE)->endOfMonth();
        $days = [];
        $cursor = $last->copy();
        while (count($days) < 5 && $cursor->month === $month) {
            if ($this->isBusinessDay($cursor)) {
                $days[] = (int) $cursor->format('j');
            }
            $cursor->subDay();
        }
        return array_reverse($days);
    }

    /**
     * 契約の申込日（または基準日）に対するスケジュール値を返す。
     * API1 individual と API3 demand の issue_month, issue_day, sending_*, deadline_* にそのまま渡せる。
     *
     * @return array{issue_month: int, issue_day: int, sending_month: int, sending_day: int, deadline_month: int, deadline_day: int}
     */
    public function getScheduleForApplication(Contract $contract): array
    {
        $base = $contract->desired_start_date ?? $contract->actual_start_date ?? null;
        if ($base !== null) {
            $date = $base instanceof DateTimeInterface ? $base : Carbon::parse($base, self::TIMEZONE);
        } else {
            $date = Carbon::now(self::TIMEZONE);
        }
        $schedule = $this->getScheduleForDate($date);

        // 銀行振込のみ支払期限を課金開始月末日にする。発行・送付とクレジットの期限は変えない。
        if ($contract->usesBankTransfer()) {
            $schedule['deadline_month'] = self::BANK_TRANSFER_DEADLINE_MONTH;
            $schedule['deadline_day'] = self::BANK_TRANSFER_DEADLINE_DAY;
        }

        return $schedule;
    }

    /**
     * 指定日を申込日とみなしたときのスケジュール値を返す。
     *
     * @return array{issue_month: int, issue_day: int, sending_month: int, sending_day: int, deadline_month: int, deadline_day: int}
     */
    public function getScheduleForDate(DateTimeInterface $date): array
    {
        $within = $this->isWithinLast5BusinessDaysOfMonth($date);
        $schedule = $this->getBillingCycleScheduleFromSettings();
        if ($schedule !== null) {
            $block = $within ? $schedule['within'] : $schedule['after'];
            return [
                'issue_month' => (int) ($block['issue_month'] ?? self::WITHIN_ISSUE_MONTH),
                'issue_day' => (int) ($block['issue_day'] ?? self::WITHIN_ISSUE_DAY),
                'sending_month' => (int) ($block['sending_month'] ?? self::WITHIN_SENDING_MONTH),
                'sending_day' => (int) ($block['sending_day'] ?? self::WITHIN_SENDING_DAY),
                'deadline_month' => (int) ($block['deadline_month'] ?? self::WITHIN_DEADLINE_MONTH),
                'deadline_day' => (int) ($block['deadline_day'] ?? self::WITHIN_DEADLINE_DAY),
            ];
        }
        if ($within) {
            return [
                'issue_month' => self::WITHIN_ISSUE_MONTH,
                'issue_day' => self::WITHIN_ISSUE_DAY,
                'sending_month' => self::WITHIN_SENDING_MONTH,
                'sending_day' => self::WITHIN_SENDING_DAY,
                'deadline_month' => self::WITHIN_DEADLINE_MONTH,
                'deadline_day' => self::WITHIN_DEADLINE_DAY,
            ];
        }
        return [
            'issue_month' => self::AFTER_ISSUE_MONTH,
            'issue_day' => self::AFTER_ISSUE_DAY,
            'sending_month' => self::AFTER_SENDING_MONTH,
            'sending_day' => self::AFTER_SENDING_DAY,
            'deadline_month' => self::AFTER_DEADLINE_MONTH,
            'deadline_day' => self::AFTER_DEADLINE_DAY,
        ];
    }

    /**
     * サイト設定から請求サイクルスケジュールを取得。不正・未設定時は null。
     *
     * @return array{within: array, after: array}|null
     */
    private function getBillingCycleScheduleFromSettings(): ?array
    {
        $raw = SiteSetting::getTextValue('billing_cycle_schedule', '');
        if ($raw === '' || $raw === null) {
            return null;
        }
        $decoded = json_decode((string) $raw, true);
        if (!is_array($decoded) || !isset($decoded['within'], $decoded['after'])) {
            return null;
        }
        $keys = ['issue_month', 'issue_day', 'sending_month', 'sending_day', 'deadline_month', 'deadline_day'];
        foreach (['within', 'after'] as $block) {
            $b = $decoded[$block] ?? null;
            if (!is_array($b)) {
                return null;
            }
            foreach ($keys as $k) {
                if (!array_key_exists($k, $b)) {
                    return null;
                }
            }
        }
        return $decoded;
    }

    private function isBusinessDay(Carbon $date): bool
    {
        $w = (int) $date->format('w'); // 0=Sun, 6=Sat
        return $w !== 0 && $w !== 6;
    }
}
