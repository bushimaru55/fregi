<?php

namespace App\Console\Commands;

use App\Models\FregiConfig;
use App\Services\EncryptionService;
use App\Services\FregiApiService;
use App\Services\FregiPaymentService;
use Illuminate\Console\Command;

class TestFregiPaymentPageUrl extends Command
{
    protected $signature = 'fregi:test-payment-page-url 
                            {--settleno= : 発行番号（省略時は発行受付APIを呼び出して取得）}
                            {--id= : 伝票番号（省略時は自動生成）}
                            {--pay=1000 : 金額}';
    
    protected $description = 'F-REGIお支払い方法選択画面URLを生成してテスト';

    public function handle(
        FregiApiService $apiService,
        FregiPaymentService $paymentService,
        EncryptionService $encryptionService
    ) {
        $companyId = 1;
        $environment = 'test';
        
        // F-REGI設定を取得
        try {
            $config = FregiConfig::where('company_id', $companyId)
                ->where('environment', $environment)
                ->where('is_active', true)
                ->firstOrFail();
        } catch (\Exception $e) {
            $this->error("F-REGI設定が見つかりません（company_id: {$companyId}, environment: {$environment}）");
            return 1;
        }
        
        // 接続パスワードを復号
        $connectPassword = $encryptionService->decryptSecret($config->connect_password_enc);
        
        $settleno = $this->option('settleno');
        $id = $this->option('id');
        
        // 発行番号が指定されていない場合は発行受付APIを呼び出す
        if (!$settleno) {
            $this->info('発行番号が指定されていないため、発行受付APIを呼び出します...');
            
            // 伝票番号を生成（20文字以内）
            if (!$id) {
                $id = 'TEST' . now()->format('YmdHis') . rand(100, 999);
                if (strlen($id) > 20) {
                    $id = substr($id, 0, 20);
                }
            }
            
            $pay = $this->option('pay');
            $params = [
                'SHOPID' => $config->shopid,
                'ID' => $id,
                'PAY' => (string)$pay,
            ];
            
            $this->line("伝票番号（ID）: {$id}");
            $this->line("金額（PAY）: {$pay}");
            $this->line('');
            
            try {
                $result = $apiService->issuePayment($params, $config);
                
                if ($result['result'] !== 'OK') {
                    $this->error('発行受付APIが失敗しました');
                    $this->error("エラーコード: {$result['error_code']}");
                    $this->error("エラーメッセージ: {$result['error_message']}");
                    return 1;
                }
                
                $settleno = $result['settleno'];
                $this->info("✓ 発行番号を取得: {$settleno}");
                $this->line('');
            } catch (\Exception $e) {
                $this->error('発行受付APIでエラーが発生しました: ' . $e->getMessage());
                return 1;
            }
        } else {
            // 伝票番号が指定されていない場合は必須
            if (!$id) {
                $this->error('発行番号が指定されている場合、伝票番号（--id）も指定してください');
                return 1;
            }
        }
        
        $this->info('=== お支払い方法選択画面URL生成 ===');
        $this->line("SHOPID: {$config->shopid}");
        $this->line("発行番号（SETTLENO）: {$settleno}");
        $this->line("伝票番号（ID）: {$id}");
        $this->line('');
        
        // チェックサムを生成
        $checksum = $paymentService->generatePaymentPageChecksum(
            $config->shopid,
            $connectPassword,
            $settleno,
            $id
        );
        
        $this->info("チェックサム: {$checksum}");
        $this->line('');
        
        // URLを生成
        $paymentPageUrl = $apiService->getPaymentPageUrlWithParams(
            $settleno,
            $checksum,
            $config
        );
        
        $this->info('=== 生成されたURL ===');
        $this->line($paymentPageUrl);
        $this->line('');
        
        // 仕様書の例で検証（オプション）
        $this->info('=== 仕様書の例で検証 ===');
        $exampleChecksum = $paymentService->generatePaymentPageChecksum(
            '00001',
            'abcdefg',
            '00000000000000000001',
            '123456789'
        );
        $expectedChecksum = '91c8328dc2f0d47c9d020ba077b0176a';
        $this->line("期待値: {$expectedChecksum}");
        $this->line("計算値: {$exampleChecksum}");
        if ($exampleChecksum === $expectedChecksum) {
            $this->info('✓ 仕様書の例と一致しました');
        } else {
            $this->error('✗ 仕様書の例と一致しません');
        }
        $this->line('');
        
        $this->info('次のステップ:');
        $this->line('1. 生成されたURLをブラウザで開いて、お支払い方法選択画面が表示されるか確認してください');
        $this->line('2. テスト決済を実行して、戻りURLの動作を確認してください');
        
        return 0;
    }
}