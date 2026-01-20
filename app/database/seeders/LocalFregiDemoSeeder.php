<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LocalFregiDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * ローカル環境でのF-REGI疎通確認用のサンプルデータを投入
     * Eloquentモデルに依存せず、DBファサードで直接操作
     */
    public function run(): void
    {
        // APP_ENV=local のときのみ実行（安全のため）
        if (env('APP_ENV') !== 'local') {
            $this->command->warn('このSeederはローカル環境でのみ実行可能です。APP_ENV=' . env('APP_ENV'));
            return;
        }

        $this->command->info('ローカルF-REGI疎通確認用のサンプルデータを投入します...');

        // 1. contract_plan_masters を作成（nameで検索してupsert）
        $masterName = 'デモプランマスター';
        $master = DB::table('contract_plan_masters')
            ->where('name', $masterName)
            ->first();
        
        $masterData = [
            'name' => $masterName,
            'description' => 'ローカルF-REGI疎通確認用のプランマスター',
            'is_active' => true,
            'display_order' => 1,
            'updated_at' => now(),
        ];
        
        if ($master) {
            $masterId = $master->id;
            DB::table('contract_plan_masters')
                ->where('id', $masterId)
                ->update($masterData);
            $this->command->info("✓ contract_plan_masters 更新完了: ID={$masterId}, name={$masterName}");
        } else {
            $masterData['created_at'] = now();
            $masterId = DB::table('contract_plan_masters')->insertGetId($masterData);
            $this->command->info("✓ contract_plan_masters 作成完了: ID={$masterId}, name={$masterName}");
        }

        // 2. contract_plans を作成（2件：一回限り・月額）
        // 一回限り決済プラン
        $planOneTimeItem = 'DEMO_ONCE_10000';
        $planOneTime = DB::table('contract_plans')
            ->where('item', $planOneTimeItem)
            ->where('contract_plan_master_id', $masterId)
            ->first();
        
        $planOneTimeData = [
            'contract_plan_master_id' => $masterId,
            'item' => $planOneTimeItem,
            'name' => 'デモプラン（一回限り）',
            'price' => 10000,
            'billing_type' => 'one_time',
            'description' => 'ローカルF-REGI疎通確認用：一回限り決済プラン（10,000円）',
            'is_active' => true,
            'display_order' => 1,
            'updated_at' => now(),
        ];
        
        if ($planOneTime) {
            $planOneTimeId = $planOneTime->id;
            DB::table('contract_plans')
                ->where('id', $planOneTimeId)
                ->update($planOneTimeData);
            $this->command->info("✓ contract_plans 更新完了（一回限り）: ID={$planOneTimeId}, item={$planOneTimeItem}, price=10000");
        } else {
            $planOneTimeData['created_at'] = now();
            $planOneTimeId = DB::table('contract_plans')->insertGetId($planOneTimeData);
            $this->command->info("✓ contract_plans 作成完了（一回限り）: ID={$planOneTimeId}, item={$planOneTimeItem}, price=10000");
        }

        // 月額課金プラン
        $planMonthlyItem = 'DEMO_MONTHLY_5000';
        $planMonthly = DB::table('contract_plans')
            ->where('item', $planMonthlyItem)
            ->where('contract_plan_master_id', $masterId)
            ->first();
        
        $planMonthlyData = [
            'contract_plan_master_id' => $masterId,
            'item' => $planMonthlyItem,
            'name' => 'デモプラン（月額）',
            'price' => 5000,
            'billing_type' => 'monthly',
            'description' => 'ローカルF-REGI疎通確認用：月額課金プラン（5,000円/月）',
            'is_active' => true,
            'display_order' => 2,
            'updated_at' => now(),
        ];
        
        if ($planMonthly) {
            $planMonthlyId = $planMonthly->id;
            DB::table('contract_plans')
                ->where('id', $planMonthlyId)
                ->update($planMonthlyData);
            $this->command->info("✓ contract_plans 更新完了（月額）: ID={$planMonthlyId}, item={$planMonthlyItem}, price=5000");
        } else {
            $planMonthlyData['created_at'] = now();
            $planMonthlyId = DB::table('contract_plans')->insertGetId($planMonthlyData);
            $this->command->info("✓ contract_plans 作成完了（月額）: ID={$planMonthlyId}, item={$planMonthlyItem}, price=5000");
        }

        // 3. products を作成（codeで検索してupsert、codeはユニーク）
        $productCode = 'PROD_DEMO_001';
        $product = DB::table('products')
            ->where('code', $productCode)
            ->first();
        
        $productData = [
            'code' => $productCode,
            'name' => 'デモ商品',
            'unit_price' => 10000,
            'updated_at' => now(),
        ];
        
        if ($product) {
            $productId = $product->id;
            DB::table('products')
                ->where('id', $productId)
                ->update($productData);
            $this->command->info("✓ products 更新完了: ID={$productId}, code={$productCode}, name=デモ商品");
        } else {
            $productData['created_at'] = now();
            $productId = DB::table('products')->insertGetId($productData);
            $this->command->info("✓ products 作成完了: ID={$productId}, code={$productCode}, name=デモ商品");
        }

        // 4. contract_form_urls を作成（申込フォームURL）
        $planIds = [$planOneTimeId, $planMonthlyId];
        $planIdsString = implode(',', $planIds);
        $baseUrl = env('APP_URL', 'http://localhost:8080/billing');
        $formUrl = $baseUrl . '/contract/create?plans=' . $planIdsString;

        $formUrlRecord = DB::table('contract_form_urls')
            ->where('url', $formUrl)
            ->first();
        
        $formUrlData = [
            'token' => null, // 申込フォームURLはtokenなし
            'url' => $formUrl,
            'plan_ids' => json_encode($planIds), // JSON配列として保存
            'name' => 'ローカルF-REGI疎通確認用申込フォーム',
            'expires_at' => now()->addYears(10), // 長期間有効
            'is_active' => true,
            'updated_at' => now(),
        ];
        
        if ($formUrlRecord) {
            $formUrlId = $formUrlRecord->id;
            DB::table('contract_form_urls')
                ->where('id', $formUrlId)
                ->update($formUrlData);
            $this->command->info("✓ contract_form_urls 更新完了: ID={$formUrlId}");
        } else {
            $formUrlData['created_at'] = now();
            $formUrlId = DB::table('contract_form_urls')->insertGetId($formUrlData);
            $this->command->info("✓ contract_form_urls 作成完了: ID={$formUrlId}");
        }
        $this->command->info("  URL: {$formUrl}");

        $this->command->newLine();
        $this->command->info('=== サンプルデータ投入完了 ===');
        $this->command->info('');
        $this->command->info('作成されたデータ:');
        $this->command->info("  - contract_plan_masters: ID={$masterId}, name={$masterName}");
        $this->command->info("  - contract_plans（一回限り）: ID={$planOneTimeId}, item={$planOneTimeItem}, price=10000円");
        $this->command->info("  - contract_plans（月額）: ID={$planMonthlyId}, item={$planMonthlyItem}, price=5000円/月");
        $this->command->info("  - products: ID={$productId}, code={$productCode}");
        $this->command->info("  - contract_form_urls: ID={$formUrlId}");
        $this->command->info('');
        $this->command->info('申込フォームURL（ブラウザでアクセス）:');
        $this->command->info("  {$formUrl}");
        $this->command->info('');
        $this->command->info('DB確認SQL:');
        $this->command->info("  SELECT id, name FROM contract_plan_masters WHERE name = '{$masterName}';");
        $this->command->info("  SELECT id, item, name, price, billing_type FROM contract_plans WHERE item IN ('{$planOneTimeItem}', '{$planMonthlyItem}');");
        $this->command->info("  SELECT id, code, name FROM products WHERE code = '{$productCode}';");
        $this->command->info("  SELECT id, url, plan_ids FROM contract_form_urls WHERE id = {$formUrlId};");
    }
}
