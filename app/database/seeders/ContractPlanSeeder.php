<?php

namespace Database\Seeders;

use App\Models\ContractPlan;
use App\Models\ContractPlanMaster;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ContractPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // マスターを取得（名前で検索）
        $chatbotMaster = ContractPlanMaster::where('name', 'DSチャットボットシリーズ')->first();
        $dshrMaster = ContractPlanMaster::where('name', 'DSHRシリーズ')->first();
        $dsxrMaster = ContractPlanMaster::where('name', 'DSXRシリーズ')->first();
        $dsonlineMaster = ContractPlanMaster::where('name', 'DSオンラインシリーズ')->first();

        $plans = [
            // DSチャットボットシリーズのプラン
            [
                'contract_plan_master_id' => $chatbotMaster?->id,
                'item' => 'CHATBOT-050',
                'name' => 'チャットボット エントリープラン',
                'price' => 5500,
                'description' => 'DSチャットボット 小規模サイト向けのエントリープラン（50ページ）',
                'is_active' => true,
                'display_order' => 1,
            ],
            [
                'contract_plan_master_id' => $chatbotMaster?->id,
                'item' => 'CHATBOT-100',
                'name' => 'チャットボット スタンダードプラン',
                'price' => 10450,
                'description' => 'DSチャットボット 中小規模サイト向けのスタンダードプラン（100ページ）',
                'is_active' => true,
                'display_order' => 2,
            ],
            [
                'contract_plan_master_id' => $chatbotMaster?->id,
                'item' => 'CHATBOT-200',
                'name' => 'チャットボット ビジネスプラン',
                'price' => 20900,
                'description' => 'DSチャットボット 中規模サイト向けのビジネスプラン（200ページ）',
                'is_active' => true,
                'display_order' => 3,
            ],
            [
                'contract_plan_master_id' => $chatbotMaster?->id,
                'item' => 'CHATBOT-300',
                'name' => 'チャットボット エンタープライズプラン',
                'price' => 28050,
                'description' => 'DSチャットボット 大規模サイト向けのエンタープライズプラン（300ページ）',
                'is_active' => true,
                'display_order' => 4,
            ],

            // DSHRシリーズのプラン
            [
                'contract_plan_master_id' => $dshrMaster?->id,
                'item' => 'DSHR-BASIC',
                'name' => 'DSHR ベーシックプラン',
                'price' => 8800,
                'description' => 'DSHR 基本機能を含むベーシックプラン',
                'is_active' => true,
                'display_order' => 1,
            ],
            [
                'contract_plan_master_id' => $dshrMaster?->id,
                'item' => 'DSHR-STANDARD',
                'name' => 'DSHR スタンダードプラン',
                'price' => 16500,
                'description' => 'DSHR 標準機能を含むスタンダードプラン',
                'is_active' => true,
                'display_order' => 2,
            ],
            [
                'contract_plan_master_id' => $dshrMaster?->id,
                'item' => 'DSHR-PREMIUM',
                'name' => 'DSHR プレミアムプラン',
                'price' => 27500,
                'description' => 'DSHR 全機能を含むプレミアムプラン',
                'is_active' => true,
                'display_order' => 3,
            ],

            // DSXRシリーズのプラン
            [
                'contract_plan_master_id' => $dsxrMaster?->id,
                'item' => 'DSXR-STARTER',
                'name' => 'DSXR スタータープラン',
                'price' => 11000,
                'description' => 'DSXR スターター向けプラン',
                'is_active' => true,
                'display_order' => 1,
            ],
            [
                'contract_plan_master_id' => $dsxrMaster?->id,
                'item' => 'DSXR-PROFESSIONAL',
                'name' => 'DSXR プロフェッショナルプラン',
                'price' => 22000,
                'description' => 'DSXR プロフェッショナル向けプラン',
                'is_active' => true,
                'display_order' => 2,
            ],
            [
                'contract_plan_master_id' => $dsxrMaster?->id,
                'item' => 'DSXR-ENTERPRISE',
                'name' => 'DSXR エンタープライズプラン',
                'price' => 33000,
                'description' => 'DSXR エンタープライズ向けプラン',
                'is_active' => true,
                'display_order' => 3,
            ],

            // DSオンラインシリーズのプラン
            [
                'contract_plan_master_id' => $dsonlineMaster?->id,
                'item' => 'ONLINE-LITE',
                'name' => 'DSオンライン ライトプラン',
                'price' => 4950,
                'description' => 'DSオンライン ライト向けプラン',
                'is_active' => true,
                'display_order' => 1,
            ],
            [
                'contract_plan_master_id' => $dsonlineMaster?->id,
                'item' => 'ONLINE-BASIC',
                'name' => 'DSオンライン ベーシックプラン',
                'price' => 9900,
                'description' => 'DSオンライン ベーシック向けプラン',
                'is_active' => true,
                'display_order' => 2,
            ],
            [
                'contract_plan_master_id' => $dsonlineMaster?->id,
                'item' => 'ONLINE-ADVANCED',
                'name' => 'DSオンライン アドバンスプラン',
                'price' => 19800,
                'description' => 'DSオンライン アドバンス向けプラン',
                'is_active' => true,
                'display_order' => 3,
            ],
            [
                'contract_plan_master_id' => $dsonlineMaster?->id,
                'item' => 'ONLINE-ULTIMATE',
                'name' => 'DSオンライン アルティメットプラン',
                'price' => 29700,
                'description' => 'DSオンライン アルティメット向けプラン',
                'is_active' => true,
                'display_order' => 4,
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
