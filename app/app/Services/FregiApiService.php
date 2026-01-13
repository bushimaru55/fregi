<?php

namespace App\Services;

use App\Models\FregiConfig;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FregiApiService
{
    /**
     * APIエンドポイントURLを取得
     *
     * @param string $environment 環境（test/prod）
     * @param string $apiType APIタイプ（issue/cancel/change/info）
     * @return string
     */
    private function getApiUrl(string $environment, string $apiType): string
    {
        $baseUrl = 'https://ssl.f-regi.com';
        
        if ($environment === 'test') {
            $basePath = '/connecttest';
        } else {
            $basePath = '/connect';
        }
        
        $apiPaths = [
            'issue' => $basePath . '/compsettleapply.cgi',
            'cancel' => $basePath . '/compsettlecancel.cgi',
            'change' => $basePath . '/compsettlechange.cgi',
            'info' => $basePath . '/compsettleinfo.cgi',
        ];
        
        return $baseUrl . ($apiPaths[$apiType] ?? $apiPaths['issue']);
    }

    /**
     * お支払い方法選択画面のURLを取得
     *
     * @param string $environment 環境（test/prod）
     * @return string
     */
    private function getPaymentPageUrl(string $environment): string
    {
        if ($environment === 'test') {
            return 'https://pay.f-regi.com/usertest/';
        } else {
            return 'https://pay.f-regi.com/user/';
        }
    }

    /**
     * 発行受付APIに接続して発行番号を取得
     *
     * @param array $params 発行受付APIのパラメータ
     * @param FregiConfig $config F-REGI設定
     * @return array ['result' => 'OK', 'settleno' => '...'] または ['result' => 'NG', 'error_code' => '...', 'error_message' => '...']
     * @throws \Exception
     */
    public function issuePayment(array $params, FregiConfig $config): array
    {
        $url = $this->getApiUrl($config->environment, 'issue');
        
        // 必須パラメータの確認
        if (empty($params['SHOPID']) || empty($params['ID']) || empty($params['PAY'])) {
            throw new \Exception('必須パラメータが不足しています（SHOPID, ID, PAY）');
        }
        
        // AUTOREGISTERは省略可能（省略時はF-REGI側で「0: 選択登録」が設定される）
        // パラメータが指定されている場合のみ送信
        // 注意: テスト環境によってはAUTOREGISTERを送信しない方が良い場合がある
        
        // CHARCODEが指定されていない場合はeucを設定（仕様書のデフォルトは自動判別だが、明示的に指定）
        if (!isset($params['CHARCODE'])) {
            $params['CHARCODE'] = 'euc';
        }
        
        try {
            // POSTリクエストを送信（application/x-www-form-urlencoded）
            $response = Http::asForm()->post($url, $params);
            
            if (!$response->successful()) {
                throw new \Exception('HTTPリクエストが失敗しました: ' . $response->status());
            }
            
            // レスポンスボディを取得（EUC-JPからUTF-8に変換）
            $body = $response->body();
            // EUC-JPでエンコードされている可能性があるため、UTF-8に変換を試みる
            if (!mb_check_encoding($body, 'UTF-8')) {
                $body = mb_convert_encoding($body, 'UTF-8', 'EUC-JP');
            }
            
            // レスポンスを改行区切りで分割
            $lines = explode("\n", $body);
            
            // 空行を除去
            $lines = array_filter($lines, function($line) {
                return trim($line) !== '';
            });
            $lines = array_values($lines);
            
            if (empty($lines)) {
                throw new \Exception('レスポンスが空です');
            }
            
            // 1行目を確認（OK/NG）
            $firstLine = trim($lines[0]);
            
            if (substr($firstLine, 0, 2) === 'OK') {
                // 成功時: 2行目に発行番号
                if (empty($lines[1])) {
                    throw new \Exception('発行番号が取得できませんでした');
                }
                
                $settleno = trim($lines[1]);
                
                Log::info('F-REGI発行受付API成功', [
                    'settleno' => $settleno,
                    'shopid' => $params['SHOPID'],
                    'id' => $params['ID'],
                ]);
                
                return [
                    'result' => 'OK',
                    'settleno' => $settleno,
                ];
            } else if (substr($firstLine, 0, 2) === 'NG') {
                // 失敗時: 2行目にエラーコード、3行目にエラーメッセージ
                $errorCode = !empty($lines[1]) ? trim($lines[1]) : 'UNKNOWN';
                $errorMessage = !empty($lines[2]) ? trim($lines[2]) : 'エラーが発生しました';
                
                Log::error('F-REGI発行受付API失敗', [
                    'error_code' => $errorCode,
                    'error_message' => $errorMessage,
                    'shopid' => $params['SHOPID'],
                    'id' => $params['ID'],
                ]);
                
                return [
                    'result' => 'NG',
                    'error_code' => $errorCode,
                    'error_message' => $errorMessage,
                ];
            } else {
                throw new \Exception('予期しないレスポンス形式: ' . $firstLine);
            }
        } catch (\Exception $e) {
            Log::error('F-REGI発行受付API例外', [
                'error' => $e->getMessage(),
                'url' => $url,
                'shopid' => $params['SHOPID'] ?? null,
            ]);
            throw $e;
        }
    }

    /**
     * お支払い方法選択画面のURLを生成
     *
     * @param string $settleno 発行番号
     * @param string $checksum チェックサム
     * @param FregiConfig $config F-REGI設定
     * @return string
     */
    public function getPaymentPageUrlWithParams(string $settleno, string $checksum, FregiConfig $config): string
    {
        $baseUrl = $this->getPaymentPageUrl($config->environment);
        return $baseUrl . '?SETTLENO=' . urlencode($settleno) . '&CHECKSUM=' . urlencode($checksum);
    }
}