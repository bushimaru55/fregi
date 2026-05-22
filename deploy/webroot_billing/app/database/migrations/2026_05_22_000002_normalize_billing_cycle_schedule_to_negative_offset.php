<?php

use App\Models\SiteSetting;
use Illuminate\Database\Migrations\Migration;

/**
 * 利用規約 第4条 4-5 項に整合する最終形へ site_settings.billing_cycle_schedule を訂正する。
 *
 *   仕様: 月末5営業日以前申込 = 請求書発行(申込月末) / 決済(翌月1日)
 *         月末5営業日以内申込 = 請求書発行(申込翌月末) / 決済(翌々月1日)
 *
 * 上記は start_date を「課金開始月の1日」で送る前提のもと、
 *   issue=前月末日(-1/99), sending=前月末日(-1/99), deadline=当月1日(0/1)
 * を within/after の両方に適用すれば実現できる。
 *
 * 旧スキーマ（fcfcc3b 反転前）または 549b8a1 で書き込んだ中間スキーマと完全一致する場合のみ
 * 正規スキーマで上書きする。管理者が手動編集している場合は触らない。
 */
return new class extends Migration
{
    public function up(): void
    {
        $raw = SiteSetting::getTextValue('billing_cycle_schedule', '');
        if ($raw === '' || $raw === null) {
            return;
        }

        $schedule = json_decode((string) $raw, true);
        if (! is_array($schedule)) {
            return;
        }

        $oldInverted = [
            'within' => [
                'issue_month' => 0, 'issue_day' => 99,
                'sending_month' => 0, 'sending_day' => 99,
                'deadline_month' => 1, 'deadline_day' => 1,
            ],
            'after' => [
                'issue_month' => 1, 'issue_day' => 99,
                'sending_month' => 1, 'sending_day' => 99,
                'deadline_month' => 2, 'deadline_day' => 1,
            ],
        ];

        $halfwayPostFcfcc3b = [
            'within' => [
                'issue_month' => 1, 'issue_day' => 99,
                'sending_month' => 1, 'sending_day' => 99,
                'deadline_month' => 2, 'deadline_day' => 1,
            ],
            'after' => [
                'issue_month' => 0, 'issue_day' => 99,
                'sending_month' => 0, 'sending_day' => 99,
                'deadline_month' => 1, 'deadline_day' => 1,
            ],
        ];

        if (! $this->matchesSchedule($schedule, $oldInverted)
            && ! $this->matchesSchedule($schedule, $halfwayPostFcfcc3b)) {
            return;
        }

        $block = [
            'issue_month' => -1, 'issue_day' => 99,
            'sending_month' => -1, 'sending_day' => 99,
            'deadline_month' => 0, 'deadline_day' => 1,
        ];
        $fixed = ['within' => $block, 'after' => $block];

        SiteSetting::setTextValue(
            'billing_cycle_schedule',
            json_encode($fixed, JSON_UNESCAPED_UNICODE),
            '請求サイクル（月末5営業日ルール）発行日・送付日・決済期限の相対月/日'
        );
    }

    public function down(): void
    {
        // 反転前に戻す運用は想定しないため何もしない
    }

    private function matchesSchedule(array $actual, array $expected): bool
    {
        foreach (['within', 'after'] as $block) {
            if (! isset($actual[$block]) || ! is_array($actual[$block])) {
                return false;
            }
            foreach ($expected[$block] as $key => $value) {
                if (! array_key_exists($key, $actual[$block])) {
                    return false;
                }
                if ((int) $actual[$block][$key] !== (int) $value) {
                    return false;
                }
            }
        }
        return true;
    }
};
