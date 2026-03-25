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
     * @param  array{issue_month?: int, issue_day?: int, sending_month?: int, sending_day?: int, deadline_month?: int, deadline_day?: int}|null  $schedule  API3 標準運用時は BillingScheduleService::getScheduleForApplication の戻り値を渡す。null の場合はスケジュールを送らない（API5 即時決済用）。
     * @return array{success: bool, billing_code: string|null, individual_number: int|null, individual_code: string|null, payment_number: int|null, payment_code: string|null, cod: string|null, error?: string}
     */
    public function upsertBillingFromContract(Contract $contract, ?array $schedule = null): array
    {
        $body = $this->buildBillingBody($contract, $schedule);
        $path = 'api/v1.0/billing/bulk_upsert';

        Log::channel('contract_payment')->info('請求管理ロボ API 1 リクエストボディ', [
            'contract_id' => $contract->id,
            'billing_code' => $body['billing'][0]['code'] ?? null,
            'individual_code' => $body['billing'][0]['individual'][0]['code'] ?? '(未設定)',
            'individual_number' => $body['billing'][0]['individual'][0]['number'] ?? '(未設定)',
            'payment_code' => $body['billing'][0]['payment'][0]['code'] ?? null,
        ]);

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

        $paymentResponse = $resBody['billing'][0]['payment'] ?? null;
        Log::channel('contract_payment')->info('請求管理ロボ API 1 フルレスポンス', [
            'contract_id' => $contract->id,
            'http_status' => $status,
            'billing_error_code' => $resBody['billing'][0]['error_code'] ?? null,
            'billing_error_message' => $resBody['billing'][0]['error_message'] ?? null,
            'billing_code_resp' => $resBody['billing'][0]['code'] ?? null,
            'individual_resp' => $resBody['billing'][0]['individual'] ?? null,
            'payment_raw' => $paymentResponse,
            'top_level_error' => $error,
        ]);

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

        // 請求先部署にデフォルト決済手段を紐付け（API 3 の 1340 対策）
        $pmCode = $parsed['payment_code'] ?? '';
        if ($pmCode === '' && ($parsed['payment_number'] ?? null) !== null) {
            // code が返らない場合、リクエスト時に設定した code で再試行
            $pmCode = 'PMT-' . $contract->id;
        }
        if ($pmCode !== '' && ($parsed['individual_number'] !== null || ($parsed['individual_code'] ?? '') !== '')) {
            $updateIndividual = ['payment_method_code' => $pmCode];
            if ($parsed['individual_number'] !== null) {
                $updateIndividual['number'] = $parsed['individual_number'];
            } else {
                $updateIndividual['code'] = $parsed['individual_code'];
            }
            $updateBody = [
                'billing' => [
                    [
                        'code' => $parsed['billing_code'],
                        'individual' => [$updateIndividual],
                    ],
                ],
            ];
            try {
                $updateResult = $this->client->post('billing/bulk_upsert', $updateBody, true);
                if ($updateResult['status'] >= 400) {
                    Log::channel('contract_payment')->warning('請求先部署への決済手段紐付け失敗', [
                        'contract_id' => $contract->id,
                        'status' => $updateResult['status'],
                        'payment_method_code' => $pmCode,
                        'error' => $updateResult['error'] ?? null,
                    ]);
                } else {
                    Log::channel('contract_payment')->info('請求先部署にデフォルト決済手段を紐付け完了', [
                        'contract_id' => $contract->id,
                        'payment_method_code' => $pmCode,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::channel('contract_payment')->warning('請求先部署への決済手段紐付け例外', [
                    'contract_id' => $contract->id,
                    'message' => $e->getMessage(),
                ]);
            }
        }

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
     * Contract から API 1 の billing 配列を組み立てる。
     *
     * @param  array{issue_month?: int, issue_day?: int, sending_month?: int, sending_day?: int, deadline_month?: int, deadline_day?: int}|null  $schedule  請求先部署にスケジュールを載せる場合（API3 標準運用時）。null ならスケジュールなし。
     * @return array{billing: array<int, array>}
     */
    public function buildBillingBody(Contract $contract, ?array $schedule = null): array
    {
        $billingCode = $contract->billing_code ?? $this->generateBillingCode($contract);
        $zipCode = $contract->postal_code ? preg_replace('/\D/', '', $contract->postal_code) : '1000001';
        $individual = [
            'name' => $contract->department ?: $contract->company_name,
            'address1' => $this->buildAddress1($contract),
            'zip_code' => $zipCode,
            'pref' => $contract->prefecture ?? 'その他',
            'city_address' => trim(($contract->city ?? '') . ($contract->address_line1 ?? '') . ($contract->address_line2 ?? '')) ?: '未入力',
            'email' => $contract->email ?? '',
            'tel' => $contract->phone ? preg_replace('/\D/', '', $contract->phone) : '',
        ];

        if ($contract->billing_individual_number !== null) {
            $individual['number'] = $contract->billing_individual_number;
        } elseif ($contract->billing_individual_code !== null) {
            $individual['code'] = $contract->billing_individual_code;
        } else {
            $individual['code'] = 'IND-' . ($contract->id ?? '0');
        }

        if ($schedule !== null) {
            $individual['billing_method'] = 1;
            if (isset($schedule['issue_month'])) {
                $individual['issue_month'] = (int) $schedule['issue_month'];
            }
            if (isset($schedule['issue_day'])) {
                $individual['issue_day'] = (int) $schedule['issue_day'];
            }
            if (isset($schedule['sending_month'])) {
                $individual['sending_month'] = (int) $schedule['sending_month'];
            }
            if (isset($schedule['sending_day'])) {
                $individual['sending_day'] = (int) $schedule['sending_day'];
            }
            if (isset($schedule['deadline_month'])) {
                $individual['deadline_month'] = (int) $schedule['deadline_month'];
            }
            if (isset($schedule['deadline_day'])) {
                $individual['deadline_day'] = (int) $schedule['deadline_day'];
            }
        }

        $paymentCode = 'PMT-' . ($contract->id ?? '0');
        $payment = [
            'code' => $paymentCode,
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
        $timing = strtoupper((string) config('robotpayment.job_type', 'CAPTURE')) === 'AUTH' ? 'A' : 'C';
        $datePart = ($contract->created_at ?? now())->format('ymdHi');
        $idBase36 = strtoupper(base_convert((string) $id, 10, 36));
        $idPart = str_pad($idBase36, 6, '0', STR_PAD_LEFT);

        return 'DS' . $timing . $datePart . $idPart;
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
