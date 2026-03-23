<?php

namespace App\Services\BillingRobo;

use App\Exceptions\BillingRoboApiException;
use App\Models\Contract;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;

/**
 * 請求管理ロボ API 2: クレジットカード登録（トークン方式）の呼び出し
 * 参照: 04_api_02_credit_card_token.md
 */
class BillingRoboCreditCardService
{
    public function __construct(
        private BillingRoboApiClient $client
    ) {}

    /**
     * トークンを請求管理ロボに送り、決済情報としてクレジットカードを登録する。
     *
     * @param  array  $debugContext  ER584 原因特定用。API2 失敗時に [ER584_DEBUG] で 1 本出力する
     * @return array{success: bool, error?: string}
     */
    public function registerToken(Contract $contract, Payment $payment, string $token, array $debugContext = []): array
    {
        $billingCode = $contract->billing_code;
        if ($billingCode === null || $billingCode === '') {
            return ['success' => false, 'error' => '請求先コードが未登録です。先に API 1 を実行してください。'];
        }

        $body = [
            'billing_code' => $billingCode,
            'token' => $token,
            'email' => $contract->email ?? '',
            'tel' => $contract->phone ? preg_replace('/\D/', '', $contract->phone) : '',
        ];

        if ($payment->billing_payment_method_number !== null) {
            $body['billing_payment_method_number'] = $payment->billing_payment_method_number;
        } elseif ($payment->billing_payment_method_code !== null && $payment->billing_payment_method_code !== '') {
            $body['billing_payment_method_code'] = $payment->billing_payment_method_code;
        } else {
            return ['success' => false, 'error' => '決済情報番号または決済情報コードが未登録です。'];
        }

        $path = 'api/v1.0/billing_payment_method/credit_card_token';

        $storeId = config('robotpayment.store_id', '');
        $maskedToken = strlen($token) > 8
            ? substr($token, 0, 4) . '...' . substr($token, -4)
            : '(short)';

        Log::channel('contract_payment')->info('請求管理ロボ API 2 リクエスト', [
            'contract_id' => $contract->id,
            'billing_code' => $body['billing_code'],
            'has_number' => isset($body['billing_payment_method_number']),
            'payment_method_number' => $body['billing_payment_method_number'] ?? null,
            'has_code' => isset($body['billing_payment_method_code']),
            'payment_method_code' => $body['billing_payment_method_code'] ?? null,
            'email' => $body['email'] ?? '',
            'tel_len' => strlen($body['tel'] ?? ''),
            'token_len' => strlen($token),
            'token_preview' => $maskedToken,
            'cptoken_aid' => $storeId,
            'api_url' => rtrim(config('billing_robo.base_url', ''), '/') . '/' . $path,
        ]);

        try {
            $result = $this->client->post($path, $body, true);
        } catch (BillingRoboApiException $e) {
            Log::warning('請求管理ロボ API 2 接続失敗', [
                'contract_id' => $contract->id,
                'message' => $e->getMessage(),
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }

        $status = $result['status'];
        $resBody = $result['body'];
        $error = $result['error'];

        Log::channel('contract_payment')->info('請求管理ロボ API 2 レスポンス', [
            'contract_id' => $contract->id,
            'http_status' => $status,
            'response_body' => $resBody,
            'error' => $error,
        ]);

        if ($status >= 400 || $error !== null) {
            $msg = $error['message'] ?? "HTTP {$status}";
            Log::channel('contract_payment')->warning('請求管理ロボ API 2 エラー', [
                'contract_id' => $contract->id,
                'status' => $status,
                'error' => $error,
            ]);
            if (!empty($debugContext)) {
                $this->logEr584Debug($contract, $payment, $token, $body, $status, $resBody, $error, $debugContext);
            }
            return ['success' => false, 'error' => $msg];
        }

        return ['success' => true];
    }

    private function logPaymentMethodSearch(Contract $contract, Payment $payment, string $billingCode): void
    {
        $path = 'api/v1.0/billing_payment_method/search';

        if ($payment->billing_payment_method_number !== null) {
            try {
                $byNumber = $this->client->post($path, [
                    'billing_payment_method' => [
                        'billing_code' => $billingCode,
                        'number' => (int) $payment->billing_payment_method_number,
                    ],
                ], true);
                Log::channel('contract_payment')->info('[DIAG][H4] API2失敗後 決済情報参照(number)', [
                    'contract_id' => $contract->id,
                    'status' => $byNumber['status'],
                    'error' => $byNumber['error'],
                    'body' => $byNumber['body'],
                ]);
            } catch (BillingRoboApiException $e) {
                Log::channel('contract_payment')->warning('[DIAG][H4] API2失敗後 決済情報参照(number) 例外', [
                    'contract_id' => $contract->id,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        if ($payment->billing_payment_method_code !== null && $payment->billing_payment_method_code !== '') {
            try {
                $byCode = $this->client->post($path, [
                    'billing_payment_method' => [
                        'billing_code' => $billingCode,
                        'code' => $payment->billing_payment_method_code,
                    ],
                ], true);
                Log::channel('contract_payment')->info('[DIAG][H5] API2失敗後 決済情報参照(code)', [
                    'contract_id' => $contract->id,
                    'status' => $byCode['status'],
                    'error' => $byCode['error'],
                    'body' => $byCode['body'],
                ]);
            } catch (BillingRoboApiException $e) {
                Log::channel('contract_payment')->warning('[DIAG][H5] API2失敗後 決済情報参照(code) 例外', [
                    'contract_id' => $contract->id,
                    'message' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * API2 失敗時に 1 本だけ [ER584_DEBUG] を出力（5名協議スキーマ）
     * 参照: AIdocs/api_documents/ER584_debug_log_design.md
     */
    private function logEr584Debug(
        Contract $contract,
        Payment $payment,
        string $token,
        array $api2Body,
        int $httpStatus,
        $resBody,
        ?array $apiError,
        array $debugContext
    ): void {
        $tokenLen = strlen($token);
        $tokenTrimmed = $token === trim($token);
        $looksBase64 = $tokenLen > 0 && (bool) preg_match('/^[A-Za-z0-9+\/=]+$/', $token);

        $email = $contract->email ?? '';
        $emailPreview = $email !== '' ? mb_substr($email, 0, 3) . '***' : '';
        $tel = $contract->phone ? preg_replace('/\D/', '', $contract->phone) : '';
        $baseUrl = config('billing_robo.base_url', '');
        $parsed = $baseUrl !== '' ? parse_url($baseUrl) : [];
        $billingRoboBaseUrl = isset($parsed['scheme'], $parsed['host'])
            ? ($parsed['scheme'] . '://' . $parsed['host'])
            : $baseUrl;

        $context = [
            'correlation_id' => $debugContext['correlation_id'] ?? null,
            'contract_id' => $contract->id,
            'billing_code' => $contract->billing_code,
            'token' => [
                'length' => $tokenLen,
                'age_ms' => $debugContext['token_age_ms'] ?? null,
                'hash_prefix_12' => $debugContext['token_hash_prefix'] ?? null,
                'duplicate_detected' => $debugContext['duplicate_detected'] ?? null,
                'looks_base64' => $looksBase64,
                'trimmed' => $tokenTrimmed,
            ],
            'frontend' => [
                'aid' => config('robotpayment.store_id', ''),
                'am' => $debugContext['frontend_am'] ?? null,
                'tx' => $debugContext['frontend_tx'] ?? null,
                'sf' => $debugContext['frontend_sf'] ?? null,
                'use_zero_amount' => $debugContext['frontend_use_zero_amount'] ?? null,
                'em_len' => strlen($email),
                'pn_len' => strlen($tel),
            ],
            'api2_request' => [
                'billing_code' => $api2Body['billing_code'] ?? null,
                'payment_method_number' => $api2Body['billing_payment_method_number'] ?? null,
                'payment_method_code' => $api2Body['billing_payment_method_code'] ?? null,
                'email_preview' => $emailPreview,
                'tel_len' => strlen($tel),
            ],
            'api2_response' => [
                'http_status' => $httpStatus,
                'error_code' => $apiError['code'] ?? null,
                'error_message' => $apiError['message'] ?? null,
            ],
            'env' => [
                'store_id' => config('robotpayment.store_id', ''),
                'billing_robo_base_url' => $billingRoboBaseUrl,
            ],
            'request' => [
                'ip' => $debugContext['request_ip'] ?? null,
                'user_agent' => $debugContext['user_agent'] ?? null,
            ],
            'received_at_ms' => $debugContext['received_at_ms'] ?? null,
        ];

        Log::channel('contract_payment')->info('[ER584_DEBUG]', $context);
    }
}
