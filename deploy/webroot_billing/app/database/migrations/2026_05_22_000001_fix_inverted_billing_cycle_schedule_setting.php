<?php

use App\Models\SiteSetting;
use Illuminate\Database\Migrations\Migration;

/**
 * fcfcc3b で BillingScheduleService の within/after 定数を反転させた際、
 * site_settings.billing_cycle_schedule に旧スキーマで保存されていた既存レコードは
 * そのまま残り、コードの新しい意味と取り違えが発生していた。
 * （例: 2026/05/22 申込時の Billing Robo 売上計上日が 5/1 になっていた事象）
 *
 * 既存値が旧スキーマと完全一致する場合のみ、反転（新スキーマ）に置き換える。
 * 管理者が手動編集している場合は触らない。
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

        $oldExpected = [
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

        if (! $this->matchesSchedule($schedule, $oldExpected)) {
            return;
        }

        $fixed = [
            'within' => $oldExpected['after'],
            'after' => $oldExpected['within'],
        ];

        SiteSetting::setTextValue(
            'billing_cycle_schedule',
            json_encode($fixed, JSON_UNESCAPED_UNICODE),
            '請求サイクル既定値（月末5営業日ルール）'
        );
    }

    public function down(): void
    {
        // 反転前の値に戻したいケースは想定しないため何もしない
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
