<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

class RobotPaymentVerifyConfigCommand extends Command
{
    protected $signature = 'robotpayment:verify-config';

    protected $description = 'ROBOT PAYMENT の設定を確認し、テストモード接続検証の準備ができているかチェックする';

    public function handle(): int
    {
        $this->info('ROBOT PAYMENT 設定確認');
        $this->newLine();

        $enabled = Config::get('robotpayment.enabled');
        $storeId = Config::get('robotpayment.store_id');
        $gatewayUrl = Config::get('robotpayment.gateway_url');
        $companyId = Config::get('robotpayment.company_id');
        $notifyInitial = Config::get('robotpayment.notify_initial_url');
        $notifyRecurring = Config::get('robotpayment.notify_recurring_url');
        $accessKeySet = Config::get('robotpayment.access_key') !== '';

        $this->table(
            ['項目', '値'],
            [
                ['ROBOTPAYMENT_ENABLED', $enabled ? 'true' : 'false'],
                ['ROBOTPAYMENT_STORE_ID (aid)', $storeId !== '' ? $storeId : '(未設定)'],
                ['ROBOTPAYMENT_GATEWAY_URL', $gatewayUrl],
                ['ROBOTPAYMENT_COMPANY_ID', (string) $companyId],
                ['ROBOTPAYMENT_ACCESS_KEY', $accessKeySet ? '***設定済み***' : '(未設定)'],
                ['ROBOTPAYMENT_NOTIFY_INITIAL_URL', $notifyInitial !== '' ? $notifyInitial : '(未設定)'],
                ['ROBOTPAYMENT_NOTIFY_RECURRING_URL', $notifyRecurring !== '' ? $notifyRecurring : '(未設定)'],
            ]
        );

        $hasWarnings = false;
        if (!$enabled) {
            $this->warn('ROBOTPAYMENT_ENABLED が false です。テストモード接続検証では true にしてください。');
            $hasWarnings = true;
        }
        if ($storeId === '') {
            $this->warn('ROBOTPAYMENT_STORE_ID が未設定です。決済システムCPの店舗IDを設定してください。');
            $hasWarnings = true;
        }
        if ($notifyInitial === '' || $notifyRecurring === '') {
            $this->line('通知URLが未設定の場合、Step 8（通知受信）は検証できません。請求管理ロボに登録後、.env に設定してください。');
        }

        if (!$hasWarnings) {
            $this->info('設定は接続検証の準備ができています。');
            $this->line('手順: AIdocs/payment_integration_robotpayment/テストモード接続検証手順.md');
        } else {
            $this->newLine();
            $this->line('上記を修正し、php artisan config:clear のあと再度本コマンドを実行してください。');
        }

        return Command::SUCCESS;
    }
}
