<?php

namespace App\Console\Commands;

use App\Models\ContractPlan;
use App\Models\ContractPlanMaster;
use Illuminate\Console\Command;

class CreateTestPlans extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:create-plans';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'テスト用の契約プランを作成（一回限りと月額課金の各1つずつ）';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // テスト用マスターを作成（存在しない場合）
        $testMaster = ContractPlanMaster::firstOrCreate(
            ['name' => 'テスト用プラン'],
            [
                'description' => '動作テスト用の契約プランマスター',
                'is_active' => true,
                'display_order' => 999,
            ]
        );

        // 一回限りのプラン
        $oneTimePlan = ContractPlan::updateOrCreate(
            ['item' => 'TEST-ONE-TIME'],
            [
                'contract_plan_master_id' => $testMaster->id,
                'name' => 'テスト用 一回限りプラン',
                'price' => 10000,
                'billing_type' => 'one_time',
                'description' => '動作テスト用：一回限りの決済プラン',
                'is_active' => true,
                'display_order' => 1,
            ]
        );

        // 月額課金のプラン
        $monthlyPlan = ContractPlan::updateOrCreate(
            ['item' => 'TEST-MONTHLY'],
            [
                'contract_plan_master_id' => $testMaster->id,
                'name' => 'テスト用 月額課金プラン',
                'price' => 5000,
                'billing_type' => 'monthly',
                'description' => '動作テスト用：月額課金プラン',
                'is_active' => true,
                'display_order' => 2,
            ]
        );

        $this->info('テスト用プランを作成しました:');
        $this->line("  - 一回限り: ID={$oneTimePlan->id}, プランコード={$oneTimePlan->item}, プラン名={$oneTimePlan->name}, 料金={$oneTimePlan->formatted_price}");
        $this->line("  - 月額課金: ID={$monthlyPlan->id}, プランコード={$monthlyPlan->item}, プラン名={$monthlyPlan->name}, 料金={$monthlyPlan->formatted_price}");

        return Command::SUCCESS;
    }
}
