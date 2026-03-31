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

    /** 月末5営業日以内: 翌月末日で発行・送付、翌々月1日決済（1=翌月, 2=翌々月, 99=末日） */
    private const WITHIN_ISSUE_MONTH = 1;
    private const WITHIN_ISSUE_DAY = 99;
    private const WITHIN_SENDING_MONTH = 1;
    private const WITHIN_SENDING_DAY = 99;
    private const WITHIN_DEADLINE_MONTH = 2;
    private const WITHIN_DEADLINE_DAY = 1;

    /** 月末5営業日以前: 当月末日で発行・送付、翌月1日決済（0=当月, 1=翌月, 99=末日） */
    private const AFTER_ISSUE_MONTH = 0;
    private const AFTER_ISSUE_DAY = 99;
    private const AFTER_SENDING_MONTH = 0;
    private const AFTER_SENDING_DAY = 99;
    private const AFTER_DEADLINE_MONTH = 1;
    private const AFTER_DEADLINE_DAY = 1;

    /**
     * 指定日が当該月の「月末5営業日」に含まれるか。
     * 営業日 = 土日を除く。月末から数えて営業日5日分のいずれかであれば true。
     */
    public function isWithinLast5BusinessDaysOfMonth(DateTimeInterface $date): bool
    {
        $carbon = Carbon::instance($date)->timezone(self::TIMEZONE);
        $year = (int) $carbon->format('Y');
        $month = (int) $carbon->format('n');
        $last5 = $this->getLast5BusinessDaysOfMonth($year, $month);
        $day = (int) $carbon->format('j');
        return in_array($day, $last5, true);
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
        return $this->getScheduleForDate($date);
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
