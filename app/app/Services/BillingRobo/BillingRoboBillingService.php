<?php

namespace App\Services\BillingRobo;

use App\Exceptions\BillingRoboApiException;
use App\Models\Contract;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;

/**
 * 請求管理ロボ API 1: 請求先登録更新の呼び出しとレスポンスの DB 反映
 * 参照: 03_api_01_billing_bulk_upsert.md
 */
class BillingRoboBillingService
{
    public function __construct(
        private BillingRoboApiClient $client
    ) {}

    /**
     * 契約から請求先登録更新（API 1）を実行し、レスポンスを Contract / Payment に保存する。
     *
     * @return array{success: bool, billing_code: string|null, individual_number: int|null, individual_code: string|null, payment_number: int|null, payment_code: string|null, cod: string|null, error?: string}
     */
    public function upsertBillingFromContract(Contract $contract): array
    {
        $body = $this->buildBillingBody($contract);
        $path = 'api/v1.0/billing/bulk_upsert';

        try {
            $result = $this->client->post($path, $body, true);
        } catch (BillingRoboApiException $e) {
            Log::warning('請求管理ロボ API 1 接続失敗', [
                'contract_id' => $contract->id,
                'message' => $e->getMessage(),
            ]);
            return [
                'success' => false,
                'billing_code' => null,
                'individual_number' => null,
                'individual_code' => null,
                'payment_number' => null,
                'payment_code' => null,
                'cod' => null,
                'error' => $e->getMessage(),
            ];
        }

        $status = $result['status'];
        $resBody = $result['body'];
        $error = $result['error'];

        if ($status >= 400) {
            $msg = $error['message'] ?? "HTTP {$status}";
            Log::warning('請求管理ロボ API 1 エラー', [
                'contract_id' => $contract->id,
                'status' => $status,
                'error' => $error,
            ]);
            return [
                'success' => false,
                'billing_code' => null,
                'individual_number' => null,
                'individual_code' => null,
                'payment_number' => null,
                'payment_code' => null,
                'cod' => null,
                'error' => $msg,
            ];
        }

        $parsed = $this->parseBillingResponse($resBody);
        if (!$parsed) {
            return [
                'success' => false,
                'billing_code' => null,
                'individual_number' => null,
                'individual_code' => null,
                'payment_number' => null,
                'payment_code' => null,
                'cod' => null,
                'error' => 'レスポンスの billing を解析できませんでした',
            ];
        }

        $contract->update([
            'billing_code' => $parsed['billing_code'],
            'billing_individual_number' => $parsed['individual_number'],
            'billing_individual_code' => $parsed['individual_code'],
        ]);

        if ($parsed['payment_number'] !== null || $parsed['payment_code'] !== null || $parsed['cod'] !== null) {
            $payment = $contract->payments()->where('provider', 'robotpayment')->first();
            if (!$payment) {
                $payment = Payment::create([
                    'company_id' => config('robotpayment.company_id', 1),
                    'contract_id' => $contract->id,
                    'provider' => 'robotpayment',
                    'payment_kind' => 'initial',
                    'orderid' => 'RP-' . $contract->id . '-' . now()->format('YmdHis'),
                    'amount' => 0,
                    'currency' => 'JPY',
                    'payment_method' => 'card',
                    'status' => 'created',
                ]);
            }
            $payment->update(array_filter([
                'billing_payment_method_number' => $parsed['payment_number'],
                'billing_payment_method_code' => $parsed['payment_code'],
                'merchant_order_no' => $parsed['cod'],
            ]));
        }

        return [
            'success' => true,
            'billing_code' => $parsed['billing_code'],
            'individual_number' => $parsed['individual_number'],
            'individual_code' => $parsed['individual_code'],
            'payment_number' => $parsed['payment_number'],
            'payment_code' => $parsed['payment_code'],
            'cod' => $parsed['cod'],
        ];
    }

    /**
     * Contract から API 1 の billing 配列を組み立てる
     *
     * @return array{billing: array<int, array>}
     */
    public function buildBillingBody(Contract $contract): array
    {
        $billingCode = $contract->billing_code ?? $this->generateBillingCode($contract);
        $individual = [
            'name' => $contract->department ?: $contract->company_name,
            'address1' => $this->buildAddress1($contract),
            'zip_code' => $contract->postal_code ? preg_replace('/\D/', '', $contract->postal_code) : '',
            'pref' => $contract->prefecture ?? 'その他',
            'city_address' => trim(($contract->city ?? '') . ($contract->address_line1 ?? '') . ($contract->address_line2 ?? '')),
            'email' => $contract->email ?? '',
            'tel' => $contract->phone ? preg_replace('/\D/', '', $contract->phone) : '',
        ];

        if ($contract->billing_individual_number !== null) {
            $individual['number'] = $contract->billing_individual_number;
        } elseif ($contract->billing_individual_code !== null) {
            $individual['code'] = $contract->billing_individual_code;
        }

        $payment = [
            'name' => 'クレジットカード',
            'payment_method' => 1,
            'credit_card_regist_kind' => 1,
        ];

        $billing = [
            'code' => $billingCode,
            'name' => $contract->company_name ?? '',
            'individual' => [$individual],
            'payment' => [$payment],
        ];

        return ['billing' => [$billing]];
    }

    private function generateBillingCode(Contract $contract): string
    {
        $id = $contract->id ?? 0;
        return 'BC' . str_pad((string) $id, 8, '0', STR_PAD_LEFT);
    }

    private function buildAddress1(Contract $contract): string
    {
        $parts = array_filter([
            $contract->contact_name,
            $contract->department ? "({$contract->department})" : null,
        ]);
        return implode(' ', $parts) ?: ($contract->company_name ?? '');
    }

    /**
     * API 1 レスポンスから billing[0] の code, individual[0], payment[0] をパース
     *
     * @return array{billing_code: string|null, individual_number: int|null, individual_code: string|null, payment_number: int|null, payment_code: string|null, cod: string|null}|null
     */
    private function parseBillingResponse(?array $body): ?array
    {
        if (!is_array($body) || empty($body['billing']) || !is_array($body['billing'])) {
            return null;
        }
        $b = $body['billing'][0];
        if (!is_array($b)) {
            return null;
        }

        $billingCode = isset($b['code']) ? (string) $b['code'] : null;
        $individualNumber = null;
        $individualCode = null;
        if (!empty($b['individual']) && is_array($b['individual'])) {
            $ind = $b['individual'][0];
            if (is_array($ind)) {
                $individualNumber = isset($ind['number']) ? (int) $ind['number'] : null;
                $individualCode = isset($ind['code']) ? (string) $ind['code'] : null;
            }
        }

        $paymentNumber = null;
        $paymentCode = null;
        $cod = null;
        if (!empty($b['payment']) && is_array($b['payment'])) {
            $pay = $b['payment'][0];
            if (is_array($pay)) {
                $paymentNumber = isset($pay['number']) ? (int) $pay['number'] : null;
                $paymentCode = isset($pay['code']) ? (string) $pay['code'] : null;
                $cod = isset($pay['cod']) ? (string) $pay['cod'] : null;
            }
        }

        return [
            'billing_code' => $billingCode,
            'individual_number' => $individualNumber,
            'individual_code' => $individualCode,
            'payment_number' => $paymentNumber,
            'payment_code' => $paymentCode,
            'cod' => $cod,
        ];
    }
}
