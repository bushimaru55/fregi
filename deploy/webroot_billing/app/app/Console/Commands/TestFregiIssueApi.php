<?php

namespace App\Console\Commands;

use App\Models\FregiConfig;
use App\Services\FregiApiService;
use Illuminate\Console\Command;

class TestFregiIssueApi extends Command
{
    protected $signature = 'fregi:test-issue-api 
                            {--shopid=23034 : SHOPID}
                            {--id= : 伝票番号（省略時は自動生成）}
                            {--pay=1000 : 金額}';
    
    protected $description = 'F-REGI発行受付APIをテスト実行';

    public function handle(FregiApiService $apiService)
    {
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
            $this->info('先に fregi:register-test-config を実行してください');
            return 1;
        }
        
        // テストパラメータを構築
        $shopid = $this->option('shopid');
        // 伝票番号は最大20文字（仕様書より）
        $id = $this->option('id') ?: 'TEST' . now()->format('YmdHis') . rand(100, 999);
        // 20文字を超える場合は切り詰める
        if (strlen($id) > 20) {
            $id = substr($id, 0, 20);
        }
        $pay = $this->option('pay');
        
        $this->info('=== F-REGI発行受付API テスト ===');
        $this->line("SHOPID: {$shopid}");
        $this->line("ID（伝票番号）: {$id}");
        $this->line("PAY（金額）: {$pay}");
        $this->line("環境: {$environment}");
        $this->line("API URL: https://ssl.f-regi.com/connecttest/compsettleapply.cgi");
        $this->line('');
        
        $params = [
            'SHOPID' => $shopid,
            'ID' => $id,
            'PAY' => (string)$pay,
            // AUTOREGISTERは省略（省略時は0: 選択登録が設定される）
        ];
        
        $this->line('送信パラメータ:');
        foreach ($params as $key => $value) {
            $this->line("  {$key} = {$value}");
        }
        $this->line('');
        
        try {
            $this->info('APIリクエスト送信中...');
            $result = $apiService->issuePayment($params, $config);
            
            if ($result['result'] === 'OK') {
                $this->info('✓ 成功！');
                $this->line('');
                $this->line("発行番号（SETTLENO）: {$result['settleno']}");
                $this->line('');
                $this->info('次のステップ:');
                $this->line('1. 発行番号をメモしてください');
                $this->line('2. お支払い方法選択画面URLを生成してブラウザで確認できます');
            } else {
                $this->error('✗ 失敗');
                $this->line('');
                $this->error("エラーコード: {$result['error_code']}");
                $this->error("エラーメッセージ: {$result['error_message']}");
                return 1;
            }
        } catch (\Exception $e) {
            $this->error('✗ エラーが発生しました');
            $this->error($e->getMessage());
            $this->line('');
            $this->error('スタックトレース:');
            $this->error($e->getTraceAsString());
            return 1;
        }
        
        return 0;
    }
}