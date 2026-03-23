<?php

namespace App\Console\Commands;

use App\Models\ContractPlan;
use App\Services\RobotPayment\RobotPaymentService;
use Illuminate\Console\Command;

/**
 * 店舗ID 133732 と検証用トークンで API 1 → API 2 の送信テストを実行する。
 * トークンはブラウザの CPToken でしか取得できないため、API 2 にはダミートークンで送信し、
 * 接続・店舗ID・レスポンス内容を確認する。
 */
class RobotPaymentApi2TestCommand extends Command
{
    protected $signature = 'robotpayment:api2-test
                            {--token= : 送信するトークン（省略時は検証用ダミー）}';

    protected $description = '店舗IDと検証用トークンで API 1 → API 2 送信テストを実行し、状況を確認する';

    public function handle(): int
    {
        $storeId = config('robotpayment.store_id', '');
        $baseUrl = config('billing_robo.base_url', '');
        $userId = config('billing_robo.user_id', '');

        $this->info('--- 決済 API 送信テスト（店舗ID・API 1 → API 2）---');
        $this->line('店舗ID: ' . ($storeId !== '' ? $storeId : '(未設定)'));
        $this->line('請求管理ロボ base_url: ' . ($baseUrl ?: '(未設定)'));
        $this->newLine();

        if ($storeId === '' || $baseUrl === '' || $userId === '') {
            $this->error('ROBOTPAYMENT_STORE_ID / BILLING_ROBO_BASE_URL / BILLING_ROBO_USER_ID を .env に設定し、php artisan config:clear を実行してください。');
            return Command::FAILURE;
        }

        $plan = ContractPlan::first();
        if (!$plan) {
            $this->error('製品が1件も登録されていません。contract_plans にデータを入れてから実行してください。');
            return Command::FAILURE;
        }

        $sessionData = $this->buildTestSessionData($plan->id);
        $token = $this->option('token') ?: 'TEST_TOKEN_4444333322221111_' . time();

        $this->line('製品ID: ' . $plan->id . ' (' . $plan->name . ')');
        $this->line('テスト用トークン: ' . (strlen($token) > 24 ? substr($token, 0, 20) . '...' : $token));
        $this->newLine();

        $service = app(RobotPaymentService::class);
        $debugContext = [
            'correlation_id' => 'cli_test_' . uniqid('', true),
            'token_created_ms' => (int) floor(microtime(true) * 1000) - 5000,
            'token_age_ms' => 5000,
            'token_hash_prefix' => substr(hash('sha256', $token), 0, 12),
            'duplicate_detected' => false,
            'request_ip' => '127.0.0.1',
            'user_agent' => 'RobotPaymentApi2TestCommand',
            'received_at_ms' => (int) floor(microtime(true) * 1000),
            'frontend_am' => 0,
            'frontend_tx' => 0,
            'frontend_sf' => 0,
            'frontend_use_zero_amount' => true,
        ];

        $this->info('決済実行フローを実行中（API 1 → API 2）...');
        $result = $service->executePayment($sessionData, $token, $debugContext);

        $this->newLine();
        if ($result['success']) {
            $this->info('結果: 成功');
            $this->line('契約ID: ' . ($result['contract']->id ?? '-'));
        } else {
            $this->warn('結果: 失敗（想定どおり。ダミートークンのため API 2 はエラーになることがあります）');
            $this->line('エラー: ' . ($result['error'] ?? ''));
        }

        $this->newLine();
        $this->line('詳細は contract_payment ログを確認してください:');
        $this->line('  php artisan robotpayment:show-test-log --lines=80');
        $this->line('  grep \'[ER584_DEBUG]\' storage/logs/contract_payment.log');
        $this->newLine();

        return Command::SUCCESS;
    }

    private function buildTestSessionData(int $contractPlanId): array
    {
        $today = now()->format('Y-m-d');

        return [
            'contract_plan_id' => $contractPlanId,
            'option_product_ids' => [],
            'company_name' => 'API2テスト株式会社',
            'company_name_kana' => null,
            'department' => null,
            'position' => null,
            'contact_name' => '検証太郎',
            'contact_name_kana' => null,
            'email' => 'test@example.com',
            'phone' => '0300000000',
            'postal_code' => null,
            'prefecture' => null,
            'city' => null,
            'address_line1' => null,
            'address_line2' => null,
            'usage_url_domain' => 'localhost',
            'import_from_trial' => false,
            'desired_start_date' => $today,
            'terms_agreed' => true,
        ];
    }
}
