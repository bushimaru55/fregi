<?php

namespace App\Console\Commands;

use App\Models\FregiConfig;
use App\Services\EncryptionService;
use App\Services\FregiConfigService;
use Illuminate\Console\Command;

class RegisterFregiTestConfig extends Command
{
    protected $signature = 'fregi:register-test-config';
    protected $description = 'F-REGIテスト環境設定を登録';

    public function handle(EncryptionService $encryptionService, FregiConfigService $configService)
    {
        $companyId = 1;
        $environment = 'test';
        
        // 既存のアクティブな設定を確認
        $existing = FregiConfig::where('company_id', $companyId)
            ->where('environment', $environment)
            ->where('is_active', true)
            ->first();
            
        if ($existing) {
            if (!$this->confirm('既存のアクティブな設定が見つかりました。上書きしますか？', true)) {
                $this->info('キャンセルしました。');
                return 0;
            }
            $existing->update(['is_active' => false]);
        }
        
        // 接続パスワードを暗号化
        $connectPassword = 'iGkwdy2a';
        $connectPasswordEnc = $encryptionService->encryptSecret($connectPassword);
        
        // 設定データ
        $data = [
            'company_id' => $companyId,
            'environment' => $environment,
            'shopid' => '23034',
            'connect_password_enc' => $connectPasswordEnc,
            'notify_url' => 'http://localhost:8080/billing/api/fregi/notify',
            'return_url_success' => 'http://localhost:8080/billing/contracts/complete',
            'return_url_cancel' => 'http://localhost:8080/billing/contracts/cancel',
            'is_active' => true,
            'updated_by' => 'system',
        ];
        
        try {
            $config = $configService->createConfig($data);
            $this->info('F-REGIテスト環境設定を登録しました。');
            $this->info("ID: {$config->id}");
            $this->info("SHOPID: {$config->shopid}");
            $this->info("環境: {$config->environment}");
            return 0;
        } catch (\Exception $e) {
            $this->error('登録に失敗しました: ' . $e->getMessage());
            return 1;
        }
    }
}