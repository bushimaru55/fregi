<?php

namespace Database\Seeders;

use App\Models\ContractPlan;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ContractPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'item' => 'PLAN-050',
                'name' => '学習ページ数 50',
                'price' => 5500,
                'description' => '小規模サイト向けのエントリープラン',
                'is_active' => true,
                'display_order' => 1,
            ],
            [
                'item' => 'PLAN-100',
                'name' => '学習ページ数 100',
                'price' => 10450,
                'description' => '中小規模サイト向けのスタンダードプラン',
                'is_active' => true,
                'display_order' => 2,
            ],
            [
                'item' => 'PLAN-150',
                'name' => '学習ページ数 150',
                'price' => 15675,
                'description' => '中規模サイト向けのビジネスプラン',
                'is_active' => true,
                'display_order' => 3,
            ],
            [
                'item' => 'PLAN-200',
                'name' => '学習ページ数 200',
                'price' => 20900,
                'description' => '大規模サイト向けのプロフェッショナルプラン',
                'is_active' => true,
                'display_order' => 4,
            ],
            [
                'item' => 'PLAN-250',
                'name' => '学習ページ数 250',
                'price' => 24750,
                'description' => '大規模サイト向けのエンタープライズプラン',
                'is_active' => true,
                'display_order' => 5,
            ],
            [
                'item' => 'PLAN-300',
                'name' => '学習ページ数 300',
                'price' => 28050,
                'description' => '超大規模サイト向けのプレミアムプラン',
                'is_active' => true,
                'display_order' => 6,
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
