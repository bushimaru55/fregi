<?php

namespace App\Console\Commands;

use App\Models\ContractPlan;
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
    protected $description = 'テスト用の製品を作成（一回限りと月額課金の各1つずつ）';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // 一回限りの製品（買い切り単品・テスト用1円）
        $oneTimePlan = ContractPlan::updateOrCreate(
            ['item' => 'TEST-ONE-TIME'],
            [
                'name' => 'テスト用 一回限りプラン',
                'price' => 1,
                'billing_type' => 'one_time',
                'description' => '動作テスト用：一回限りの決済プラン',
                'is_active' => true,
                'display_order' => 1,
            ]
        );

        // 月額課金の製品（テスト用2円/月）
        $monthlyPlan = ContractPlan::updateOrCreate(
            ['item' => 'TEST-MONTHLY'],
            [
                'name' => 'テスト用 月額課金プラン',
                'price' => 2,
                'billing_type' => 'monthly',
                'description' => '動作テスト用：月額課金プラン',
                'is_active' => true,
                'display_order' => 2,
            ]
        );

        $this->info('テスト用製品を作成しました:');
        $this->line("  - 一回限り: ID={$oneTimePlan->id}, 製品コード={$oneTimePlan->item}, 製品名={$oneTimePlan->name}, 料金={$oneTimePlan->formatted_price}");
        $this->line("  - 月額課金: ID={$monthlyPlan->id}, 製品コード={$monthlyPlan->item}, 製品名={$monthlyPlan->name}, 料金={$monthlyPlan->formatted_price}");

        return Command::SUCCESS;
    }
}
