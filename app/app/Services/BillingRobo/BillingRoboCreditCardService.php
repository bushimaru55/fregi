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
     * @return array{success: bool, error?: string}
     */
    public function registerToken(Contract $contract, Payment $payment, string $token): array
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
        $error = $result['error'];

        if ($status >= 400) {
            $msg = $error['message'] ?? "HTTP {$status}";
            Log::warning('請求管理ロボ API 2 エラー', [
                'contract_id' => $contract->id,
                'status' => $status,
                'error' => $error,
            ]);
            return ['success' => false, 'error' => $msg];
        }

        return ['success' => true];
    }
}
