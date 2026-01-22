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
     * @param string $apiType APIタイプ（issue/cancel/change/info/authm/salem）
     * @param string $environment 環境（test/prod）- DBの設定レコードから取得
     * @return string
     */
    private function getApiUrl(string $apiType, string $environment): string
    {
        $baseUrl = 'https://ssl.f-regi.com';
        
        // DBの設定レコードのenvironmentを使用（設定画面で変更可能）
        $basePath = $environment === 'test' ? '/connecttest' : '/connect';
        
        $apiPaths = [
            'issue' => $basePath . '/compsettleapply.cgi',
            'cancel' => $basePath . '/compsettlecancel.cgi',
            'change' => $basePath . '/compsettlechange.cgi',
            'info' => $basePath . '/compsettleinfo.cgi',
            'authm' => $basePath . '/authm.cgi',
            'salem' => $basePath . '/salem.cgi',
        ];
        
        return $baseUrl . ($apiPaths[$apiType] ?? $apiPaths['issue']);
    }

    /**
     * お支払い方法選択画面のURLを取得
     *
     * @param string $environment 環境（test/prod）- DBの設定レコードから取得
     * @return string
     */
    private function getPaymentPageUrl(string $environment): string
    {
        // DBの設定レコードのenvironmentを使用（設定画面で変更可能）
        return $environment === 'test' ? 'https://pay.f-regi.com/usertest/' : 'https://pay.f-regi.com/user/';
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
        // DBの設定レコードのenvironmentを使用（設定画面で変更可能）
        $url = $this->getApiUrl('issue', $config->environment);
        
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
        // DBの設定レコードのenvironmentを使用（設定画面で変更可能）
        $baseUrl = $this->getPaymentPageUrl($config->environment);
        return $baseUrl . '?SETTLENO=' . urlencode($settleno) . '&CHECKSUM=' . urlencode($checksum);
    }

    /**
     * オーソリ処理（authm.cgi）を実行
     *
     * @param array $params オーソリ処理のパラメータ
     * @param FregiConfig $config F-REGI設定
     * @return array ['result' => 'OK', 'auth_code' => '...', 'seqno' => '...', 'customer_id' => '...', 'additional_info' => '...'] または ['result' => 'NG', 'error_message' => '...']
     * @throws \Exception
     */
    public function authorizePayment(array $params, FregiConfig $config): array
    {
        // DBの設定レコードのenvironmentを使用（設定画面で変更可能）
        $fregiEnv = $config->environment;
        $url = $this->getApiUrl('authm', $fregiEnv);
        
        // 必須パラメータの確認（カード情報がある場合とCUSTOMERIDのみの場合で分岐）
        $hasCardInfo = !empty($params['PAN1']) && !empty($params['PAN2']) && !empty($params['PAN3']) && !empty($params['PAN4']);
        $hasCustomerId = !empty($params['CUSTOMERID']);
        
        if (empty($params['SHOPID']) || empty($params['PAY'])) {
            throw new \Exception('必須パラメータが不足しています（SHOPID, PAY）');
        }
        
        if (!$hasCardInfo && !$hasCustomerId) {
            throw new \Exception('必須パラメータが不足しています（PAN1-4 または CUSTOMERID）');
        }
        
        if ($hasCardInfo) {
            // カード情報がある場合は、必須パラメータを確認
            if (empty($params['CARDEXPIRY1']) || empty($params['CARDEXPIRY2']) || empty($params['CARDNAME'])) {
                throw new \Exception('必須パラメータが不足しています（CARDEXPIRY1, CARDEXPIRY2, CARDNAME）');
            }
        }
        
        // CHARCODEが指定されていない場合はeucを設定
        if (!isset($params['CHARCODE'])) {
            $params['CHARCODE'] = 'euc';
        }
        
        // ログ用にマスクしたパラメータを作成（秘密情報をマスク）
        $logParams = $params;
        if (isset($logParams['PAN1'])) $logParams['PAN1'] = '****';
        if (isset($logParams['PAN2'])) $logParams['PAN2'] = '****';
        if (isset($logParams['PAN3'])) $logParams['PAN3'] = '****';
        if (isset($logParams['PAN4'])) $logParams['PAN4'] = '****';
        if (isset($logParams['SCODE'])) $logParams['SCODE'] = '****';
        
        // リクエストの先頭情報（デバッグ用、安全な情報のみ）
        $requestPreview = [
            'SHOPID' => $params['SHOPID'] ?? null,
            'ID' => $params['ID'] ?? null,
            'PAY' => $params['PAY'] ?? null,
            'MONTHLY' => $params['MONTHLY'] ?? null,
            'CUSTOMERID' => $params['CUSTOMERID'] ?? null,
            'has_card_info' => $hasCardInfo,
        ];
        
        Log::info('F-REGIオーソリ処理送信前', [
            'url' => $url,
            'fregi_env' => $fregiEnv,
            'auth_url' => $url,
            'config_environment' => $config->environment, // DB上の設定値（参考用）
            'request_preview' => $requestPreview,
            'params_count' => count($params),
        ]);
        
        try {
            // POSTリクエストを送信（application/x-www-form-urlencoded）
            $response = Http::asForm()->post($url, $params);
            
            // HTTPステータスコードを記録
            $httpStatus = $response->status();
            Log::info('F-REGIオーソリ処理HTTPレスポンス受信', [
                'url' => $url,
                'http_status' => $httpStatus,
                'response_size' => strlen($response->body()),
            ]);
            
            if (!$response->successful()) {
                $errorBody = $response->body();
                $errorPreview = mb_substr($errorBody, 0, 200);
                Log::error('F-REGIオーソリ処理HTTPエラー', [
                    'url' => $url,
                    'http_status' => $httpStatus,
                    'response_preview' => $errorPreview,
                ]);
                throw new \Exception('HTTPリクエストが失敗しました: ' . $httpStatus);
            }
            
            // レスポンスボディを取得（EUC-JPからUTF-8に変換）
            $body = $response->body();
            // EUC-JPでエンコードされている可能性があるため、UTF-8に変換を試みる
            if (!mb_check_encoding($body, 'UTF-8')) {
                $body = mb_convert_encoding($body, 'UTF-8', 'EUC-JP');
            }
            
            // レスポンスの先頭200文字を記録（デバッグ用、安全な情報のみ）
            $responsePreview = mb_substr($body, 0, 200);
            Log::info('F-REGIオーソリ処理レスポンス受信', [
                'url' => $url,
                'response_preview' => $responsePreview,
                'response_length' => strlen($body),
            ]);
            
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
                // 成功時: 2行目に承認番号、3行目に取引番号、4行目に付加情報
                $authCode = !empty($lines[1]) ? trim($lines[1]) : null;
                $seqno = !empty($lines[2]) ? trim($lines[2]) : null;
                $additionalInfo = !empty($lines[3]) ? trim($lines[3]) : null;
                
                // CUSTOMERIDがパラメータに含まれている場合は返却値に含める
                $customerId = $params['CUSTOMERID'] ?? null;
                
                Log::info('F-REGIオーソリ処理成功', [
                    'auth_code' => $authCode,
                    'seqno' => $seqno,
                    'customer_id' => $customerId,
                    'shopid' => $params['SHOPID'],
                    'id' => $params['ID'] ?? null,
                ]);
                
                $result = [
                    'result' => 'OK',
                    'auth_code' => $authCode,
                    'seqno' => $seqno,
                    'additional_info' => $additionalInfo,
                ];
                
                if ($customerId) {
                    $result['customer_id'] = $customerId;
                }
                
                return $result;
            } else if (substr($firstLine, 0, 2) === 'NG') {
                // 失敗時: 2行目に失敗理由
                $errorMessage = !empty($lines[1]) ? trim($lines[1]) : 'エラーが発生しました';
                
                Log::error('F-REGIオーソリ処理失敗', [
                    'error_message' => $errorMessage,
                    'shopid' => $params['SHOPID'],
                    'id' => $params['ID'] ?? null,
                ]);
                
                return [
                    'result' => 'NG',
                    'error_message' => $errorMessage,
                ];
            } else {
                throw new \Exception('予期しないレスポンス形式: ' . $firstLine);
            }
        } catch (\Exception $e) {
            Log::error('F-REGIオーソリ処理例外', [
                'error' => $e->getMessage(),
                'url' => $url,
                'shopid' => $params['SHOPID'] ?? null,
            ]);
            throw $e;
        }
    }

    /**
     * 月次売上処理（salem.cgi）を実行
     *
     * @param array $params 月次売上処理のパラメータ
     * @param FregiConfig $config F-REGI設定
     * @return array ['result' => 'OK', 'auth_code' => '...', 'seqno' => '...'] または ['result' => 'NG', 'error_message' => '...']
     * @throws \Exception
     */
    public function processMonthlySale(array $params, FregiConfig $config): array
    {
        // DBの設定レコードのenvironmentを使用（設定画面で変更可能）
        $url = $this->getApiUrl('salem', $config->environment);
        
        // 必須パラメータの確認
        if (empty($params['SHOPID']) || empty($params['CUSTOMERID']) || empty($params['PAY'])) {
            throw new \Exception('必須パラメータが不足しています（SHOPID, CUSTOMERID, PAY）');
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
                // 成功時: 2行目に承認番号、3行目に取引番号
                $authCode = !empty($lines[1]) ? trim($lines[1]) : null;
                $seqno = !empty($lines[2]) ? trim($lines[2]) : null;
                
                Log::info('F-REGI月次売上処理成功', [
                    'auth_code' => $authCode,
                    'seqno' => $seqno,
                    'customer_id' => $params['CUSTOMERID'],
                    'shopid' => $params['SHOPID'],
                ]);
                
                return [
                    'result' => 'OK',
                    'auth_code' => $authCode,
                    'seqno' => $seqno,
                ];
            } else if (substr($firstLine, 0, 2) === 'NG') {
                // 失敗時: 2行目に失敗理由
                $errorMessage = !empty($lines[1]) ? trim($lines[1]) : 'エラーが発生しました';
                
                Log::error('F-REGI月次売上処理失敗', [
                    'error_message' => $errorMessage,
                    'customer_id' => $params['CUSTOMERID'],
                    'shopid' => $params['SHOPID'],
                ]);
                
                return [
                    'result' => 'NG',
                    'error_message' => $errorMessage,
                ];
            } else {
                throw new \Exception('予期しないレスポンス形式: ' . $firstLine);
            }
        } catch (\Exception $e) {
            Log::error('F-REGI月次売上処理例外', [
                'error' => $e->getMessage(),
                'url' => $url,
                'customer_id' => $params['CUSTOMERID'] ?? null,
                'shopid' => $params['SHOPID'] ?? null,
            ]);
            throw $e;
        }
    }
}