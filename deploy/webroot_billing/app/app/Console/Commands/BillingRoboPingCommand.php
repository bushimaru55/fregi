<?php

namespace App\Console\Commands;

use App\Exceptions\BillingRoboApiException;
use App\Services\BillingRobo\BillingRoboApiClient;
use App\Services\BillingRobo\BillingRoboErrorMap;
use Illuminate\Console\Command;

class BillingRoboPingCommand extends Command
{
    protected $signature = 'billing-robo:ping';

    protected $description = '請求管理ロボ API の疎通テスト（認証・接続確認）';

    public function handle(): int
    {
        $baseUrl = config('billing_robo.base_url');
        $userId = config('billing_robo.user_id');
        $accessKey = config('billing_robo.access_key');

        if ($baseUrl === '' || $userId === '' || $accessKey === '') {
            $this->error('BILLING_ROBO_BASE_URL / USER_ID / ACCESS_KEY が未設定です。.env を確認してください。');
            $this->line('例: deploy/webroot_billing/app/.env または app/.env');
            return Command::FAILURE;
        }

        $this->info('請求管理ロボ API 疎通テスト（共通クライアント経由）');
        $this->line("Base URL: {$baseUrl}");
        $this->line('user_id: ' . $userId);
        $this->line('access_key: ***');
        $this->newLine();

        $client = BillingRoboApiClient::fromConfig();
        $path = 'api/v1.0/billing_individual/search';
        $this->line('POST ' . $baseUrl . '/' . $path);

        try {
            $result = $client->post($path, [], true);
        } catch (BillingRoboApiException $e) {
            $this->error('接続失敗: ' . $e->getMessage());
            return Command::FAILURE;
        }

        $status = $result['status'];
        $body = $result['body'];
        $error = $result['error'];

        if ($status === 200) {
            $this->info('疎通成功（HTTP 200）');
            if (is_array($body)) {
                $this->line(json_encode($body, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            }
            return Command::SUCCESS;
        }

        if ($status === 401) {
            $this->error('認証失敗（HTTP 401）');
            if ($error) {
                $this->line(BillingRoboErrorMap::getMessage($error['code']) . ': ' . ($error['message'] ?? ''));
            }
            $this->line('user_id または access_key が不正です。接続元IPが請求管理ロボ側で許可されているかも確認してください。');
            return Command::FAILURE;
        }

        if ($status === 400) {
            $this->warn('リクエスト内容のエラー（HTTP 400）');
            $this->line('接続・認証は通っている可能性が高いです。API の必須パラメータ不足などのエラーです。');
            if ($error) {
                $this->line(BillingRoboErrorMap::getMessage($error['code']) . ': ' . ($error['message'] ?? ''));
            }
            if (is_array($body)) {
                $this->line(json_encode($body, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            }
            return Command::SUCCESS;
        }

        $this->error("HTTP {$status}");
        if ($error) {
            $this->line(BillingRoboErrorMap::getMessage($error['code']) . ': ' . ($error['message'] ?? ''));
        }
        if (is_array($body)) {
            $this->line(json_encode($body, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        }
        return Command::FAILURE;
    }
}
