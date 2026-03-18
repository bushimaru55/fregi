<?php

namespace Database\Seeders;

use App\Models\ContractPlan;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ContractPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 製品マスターは廃止のため、マスター紐付けなしで製品のみ登録する。
     */
    public function run(): void
    {
        $plans = [
            [
                'item' => 'CHATBOT-050',
                'name' => 'チャットボット エントリープラン',
                'price' => 5500,
                'billing_type' => 'one_time',
                'description' => 'DSチャットボット 小規模サイト向けのエントリープラン（50ページ）',
                'is_active' => true,
                'display_order' => 1,
            ],
            [
                'item' => 'CHATBOT-100',
                'name' => 'チャットボット スタンダードプラン',
                'price' => 10450,
                'billing_type' => 'one_time',
                'description' => 'DSチャットボット 中小規模サイト向けのスタンダードプラン（100ページ）',
                'is_active' => true,
                'display_order' => 2,
            ],
            [
                'item' => 'CHATBOT-200',
                'name' => 'チャットボット ビジネスプラン',
                'price' => 20900,
                'billing_type' => 'one_time',
                'description' => 'DSチャットボット 中規模サイト向けのビジネスプラン（200ページ）',
                'is_active' => true,
                'display_order' => 3,
            ],
            [
                'item' => 'CHATBOT-300',
                'name' => 'チャットボット エンタープライズプラン',
                'price' => 28050,
                'billing_type' => 'one_time',
                'description' => 'DSチャットボット 大規模サイト向けのエンタープライズプラン（300ページ）',
                'is_active' => true,
                'display_order' => 4,
            ],
            [
                'item' => 'DSHR-BASIC',
                'name' => 'DSHR ベーシックプラン',
                'price' => 8800,
                'billing_type' => 'one_time',
                'description' => 'DSHR 基本機能を含むベーシックプラン',
                'is_active' => true,
                'display_order' => 5,
            ],
            [
                'item' => 'DSHR-STANDARD',
                'name' => 'DSHR スタンダードプラン',
                'price' => 16500,
                'billing_type' => 'one_time',
                'description' => 'DSHR 標準機能を含むスタンダードプラン',
                'is_active' => true,
                'display_order' => 6,
            ],
            [
                'item' => 'DSHR-PREMIUM',
                'name' => 'DSHR プレミアムプラン',
                'price' => 27500,
                'billing_type' => 'one_time',
                'description' => 'DSHR 全機能を含むプレミアムプラン',
                'is_active' => true,
                'display_order' => 7,
            ],
            [
                'item' => 'DSXR-STARTER',
                'name' => 'DSXR スタータープラン',
                'price' => 11000,
                'billing_type' => 'one_time',
                'description' => 'DSXR スターター向けプラン',
                'is_active' => true,
                'display_order' => 8,
            ],
            [
                'item' => 'DSXR-PROFESSIONAL',
                'name' => 'DSXR プロフェッショナルプラン',
                'price' => 22000,
                'billing_type' => 'one_time',
                'description' => 'DSXR プロフェッショナル向けプラン',
                'is_active' => true,
                'display_order' => 9,
            ],
            [
                'item' => 'DSXR-ENTERPRISE',
                'name' => 'DSXR エンタープライズプラン',
                'price' => 33000,
                'billing_type' => 'one_time',
                'description' => 'DSXR エンタープライズ向けプラン',
                'is_active' => true,
                'display_order' => 10,
            ],
            [
                'item' => 'ONLINE-LITE',
                'name' => 'DSオンライン ライトプラン',
                'price' => 4950,
                'billing_type' => 'one_time',
                'description' => 'DSオンライン ライト向けプラン',
                'is_active' => true,
                'display_order' => 11,
            ],
            [
                'item' => 'ONLINE-BASIC',
                'name' => 'DSオンライン ベーシックプラン',
                'price' => 9900,
                'billing_type' => 'one_time',
                'description' => 'DSオンライン ベーシック向けプラン',
                'is_active' => true,
                'display_order' => 12,
            ],
            [
                'item' => 'ONLINE-ADVANCED',
                'name' => 'DSオンライン アドバンスプラン',
                'price' => 19800,
                'billing_type' => 'one_time',
                'description' => 'DSオンライン アドバンス向けプラン',
                'is_active' => true,
                'display_order' => 13,
            ],
            [
                'item' => 'ONLINE-ULTIMATE',
                'name' => 'DSオンライン アルティメットプラン',
                'price' => 29700,
                'billing_type' => 'one_time',
                'description' => 'DSオンライン アルティメット向けプラン',
                'is_active' => true,
                'display_order' => 14,
            ],
        ];

        foreach ($plans as $plan) {
            ContractPlan::updateOrCreate(
                ['item' => $plan['item']],
                $plan
            );
        }
    }
}
