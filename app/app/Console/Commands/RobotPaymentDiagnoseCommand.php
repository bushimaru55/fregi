<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RobotPaymentDiagnoseCommand extends Command
{
    protected $signature = 'robotpayment:diagnose';

    protected $description = 'ER003 等のゲートウェイエラー原因を特定するための診断を実行する';

    public function handle(): int
    {
        $this->info('=== ROBOT PAYMENT ゲートウェイ診断 ===');
        $this->newLine();

        $storeId = Config::get('robotpayment.store_id', '');
        $accessKey = Config::get('robotpayment.access_key', '');
        $gatewayUrl = Config::get('robotpayment.gateway_url', '');
        $enabled = Config::get('robotpayment.enabled', false);

        $this->info('[1] 設定値チェック');
        $configData = [
            ['ROBOTPAYMENT_ENABLED', $enabled ? 'true' : 'false', $enabled ? 'OK' : 'NG - false'],
            ['STORE_ID (aid)', $storeId !== '' ? (strlen($storeId) . '文字: ' . substr($storeId, 0, 3) . '***') : '(空)', $storeId !== '' ? 'OK' : 'NG - 空'],
            ['ACCESS_KEY', $accessKey !== '' ? (strlen($accessKey) . '文字: ***設定済***') : '(空)', $accessKey !== '' ? 'OK' : '未設定'],
            ['GATEWAY_URL', $gatewayUrl, $gatewayUrl !== '' ? 'OK' : 'NG - 空'],
        ];
        $this->table(['項目', '値', '状態'], $configData);

        if ($storeId === '') {
            $this->error('ROBOTPAYMENT_STORE_ID が空です。これが ER003 の原因の可能性が高いです。');
            $this->line('.env に ROBOTPAYMENT_STORE_ID=（6桁の店舗ID）を設定してください。');
        }

        $this->newLine();
        $this->info('[2] サーバー送信元IP確認');
        $outboundIp = $this->getOutboundIp();
        if ($outboundIp) {
            $this->line("  送信元IP: {$outboundIp}");
            $this->line('  → この IP が決済ゲートウェイ側で許可されている必要があります');
        } else {
            $this->warn('  送信元IP取得失敗（外部接続不可の可能性）');
        }

        $this->newLine();
        $this->info('[3] ゲートウェイ接続テスト（空リクエスト）');
        $this->line("  URL: {$gatewayUrl}");
        try {
            $response = Http::asForm()->timeout(15)->post($gatewayUrl, [
                'aid' => $storeId,
            ]);
            $body = trim($response->body());
            $this->line("  HTTP Status: {$response->status()}");
            $this->line("  Response Body: {$body}");

            if (str_contains($body, 'ER003')) {
                $this->error('  → ER003: 送信元IP認証エラー');
                $this->line("  → 送信元IP ({$outboundIp}) がゲートウェイ側で未許可です");
                $this->line('  → 決済会社に送信元IPの登録を依頼してください');
            } elseif (str_contains($body, 'ER')) {
                $this->warn("  → エラー応答あり（ER003 以外）: {$body}");
                $this->line('  → IP認証は通過している可能性があります');
            } else {
                $this->line('  → ゲートウェイへの接続成功');
            }
        } catch (\Throwable $e) {
            $this->error("  接続失敗: {$e->getMessage()}");
        }

        if ($storeId !== '') {
            $this->newLine();
            $this->info('[4] store_id + access_key でのテスト送信');
            try {
                $testParams = ['aid' => $storeId];
                if ($accessKey !== '') {
                    $testParams['access_key'] = $accessKey;
                }
                $testParams['jb'] = config('robotpayment.job_type', 'CAPTURE');
                $testParams['cod'] = 'DIAG_TEST_' . time();

                $response = Http::asForm()->timeout(15)->post($gatewayUrl, $testParams);
                $body = trim($response->body());
                $this->line("  HTTP Status: {$response->status()}");
                $this->line("  Response Body: {$body}");

                if (str_contains($body, 'ER003')) {
                    $this->error('  → ER003: aid=' . substr($storeId, 0, 3) . '*** でも IP 認証エラー');
                    $this->line("  → IP ({$outboundIp}) の許可登録が必要です");
                } elseif (str_contains($body, 'ER')) {
                    $this->warn("  → IP認証は通過、別のエラー: {$body}");
                    $this->line('  → ER003 以外のエラーは正常（テスト送信にカード情報なし）');
                } else {
                    $this->line('  → 正常応答');
                }
            } catch (\Throwable $e) {
                $this->error("  テスト送信失敗: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info('[5] 診断サマリー');
        $issues = [];
        if (!$enabled) {
            $issues[] = 'ROBOTPAYMENT_ENABLED=false（決済ページ非表示）';
        }
        if ($storeId === '') {
            $issues[] = 'ROBOTPAYMENT_STORE_ID が空（ER003の主要原因）';
        }
        if ($accessKey === '') {
            $issues[] = 'ROBOTPAYMENT_ACCESS_KEY が空（必要な場合あり）';
        }

        if (empty($issues)) {
            $this->info('  設定に明らかな問題はありません。ER003 が出る場合はIPホワイトリスト登録を確認してください。');
        } else {
            foreach ($issues as $issue) {
                $this->warn("  - {$issue}");
            }
        }

        $logEntry = [
            'command' => 'robotpayment:diagnose',
            'outbound_ip' => $outboundIp,
            'store_id_set' => $storeId !== '',
            'store_id_length' => strlen($storeId),
            'access_key_set' => $accessKey !== '',
            'gateway_url' => $gatewayUrl,
            'enabled' => $enabled,
            'issues' => $issues,
        ];
        Log::channel('contract_payment')->info('[DIAG] ゲートウェイ診断実行', $logEntry);

        return Command::SUCCESS;
    }

    private function getOutboundIp(): ?string
    {
        $services = [
            'https://ifconfig.me/ip',
            'https://api.ipify.org',
            'https://checkip.amazonaws.com',
        ];
        foreach ($services as $url) {
            try {
                $response = Http::timeout(5)->get($url);
                if ($response->successful()) {
                    return trim($response->body());
                }
            } catch (\Throwable $e) {
                continue;
            }
        }
        return null;
    }
}
