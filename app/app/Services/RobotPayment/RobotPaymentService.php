<?php

namespace App\Services\RobotPayment;

use App\Mail\ContractNotificationMail;
use App\Mail\ContractReplyMail;
use App\Models\Contract;
use App\Services\BillingRobo\BillingRoboBillingService;
use App\Services\BillingRobo\BillingRoboCreditCardService;
use App\Models\ContractItem;
use App\Models\ContractPlan;
use App\Models\Payment;
use App\Models\Product;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * ROBOT PAYMENT: 契約・明細・決済レコード作成と gateway_token.aspx へのサーバ間 POST
 * 固定IP要件のため必ずサーバから送信する（03_sequence_and_callbacks）
 */
class RobotPaymentService
{
    public function __construct(
        private PurchasePatternService $patternService
    ) {}

    /**
     * セッションの申込データとトークンで契約・明細・Payment を作成し、gateway へ POST
     * 戻り値: ['success' => bool, 'contract' => Contract|null, 'error' => string|null]
     */
    public function executePayment(array $sessionData, string $token): array
    {
        $plan = ContractPlan::findOrFail($sessionData['contract_plan_id']);
        $optionProductIds = $sessionData['option_product_ids'] ?? [];
        $desiredStartDate = $sessionData['desired_start_date'] ?? now()->format('Y-m-d');

        $amounts = $this->patternService->getAmountsFromPlanAndOptions($plan, $optionProductIds, $desiredStartDate);
        $pattern = $amounts['pattern'];

        return DB::transaction(function () use ($sessionData, $token, $plan, $optionProductIds, $desiredStartDate, $amounts, $pattern) {
            $createData = $sessionData;
            unset($createData['option_product_ids'], $createData['terms_agreed']);
            if (empty($createData['desired_start_date'])) {
                $createData['desired_start_date'] = now()->format('Y-m-d');
            }

            $contract = Contract::create([
                ...$createData,
                'status' => 'applied',
            ]);

            ContractItem::create([
                'contract_id' => $contract->id,
                'contract_plan_id' => $plan->id,
                'product_id' => null,
                'product_name' => $plan->name,
                'product_code' => $plan->item,
                'quantity' => 1,
                'unit_price' => $plan->price,
                'subtotal' => $plan->price,
                'billing_type' => $plan->billing_type ?? 'one_time',
            ]);

            foreach ($optionProductIds as $productId) {
                $product = Product::where('id', $productId)
                    ->where('type', 'option')
                    ->where('is_active', true)
                    ->firstOrFail();
                ContractItem::create([
                    'contract_id' => $contract->id,
                    'contract_plan_id' => null,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_code' => $product->code,
                    'quantity' => 1,
                    'unit_price' => $product->unit_price,
                    'subtotal' => $product->unit_price,
                    'billing_type' => $product->billing_type ?? 'one_time',
                ]);
            }

            $cod = (string) $contract->id;
            $paymentKind = $this->patternService->isAutoBillingInitial($pattern) ? 'auto_initial' : 'normal';
            $companyId = config('robotpayment.company_id', 1);

            $payment = Payment::create([
                'company_id' => $companyId,
                'contract_id' => $contract->id,
                'provider' => 'robotpayment',
                'payment_kind' => $paymentKind,
                'merchant_order_no' => $cod,
                'orderid' => $cod,
                'amount' => $amounts['ta'],
                'amount_initial' => $amounts['amount_initial'],
                'amount_recurring' => $amounts['amount_recurring'],
                'currency' => 'JPY',
                'payment_method' => 'card',
                'status' => 'waiting_notify',
                'requested_at' => now(),
            ]);

            $api1Success = null;
            if (config('billing_robo.base_url') && config('billing_robo.user_id')) {
                try {
                    $billingService = app(BillingRoboBillingService::class);
                    $api1Result = $billingService->upsertBillingFromContract($contract);
                    $api1Success = $api1Result['success'];
                    if ($api1Result['success']) {
                        $payment->refresh();
                        $cod = $payment->merchant_order_no ?? $cod;
                        Log::channel('contract_payment')->info('請求管理ロボ API 1 請求先登録完了', [
                            'contract_id' => $contract->id,
                            'billing_code' => $api1Result['billing_code'],
                            'cod' => $api1Result['cod'],
                        ]);
                    } else {
                        Log::channel('contract_payment')->warning('請求管理ロボ API 1 失敗（決済は cod=契約ID で継続）', [
                            'contract_id' => $contract->id,
                            'error' => $api1Result['error'] ?? '',
                        ]);
                    }
                } catch (\Throwable $e) {
                    $api1Success = false;
                    Log::channel('contract_payment')->warning('請求管理ロボ API 1 例外（決済は cod=契約ID で継続）', [
                        'contract_id' => $contract->id,
                        'message' => $e->getMessage(),
                    ]);
                }
            }

            $params = $this->buildGatewayParams($contract, $amounts, $pattern, $token, $payment);

            $storeId = config('robotpayment.store_id', '');
            $accessKey = config('robotpayment.access_key', '');
            $gatewayUrl = config('robotpayment.gateway_url');
            Log::channel('contract_payment')->info('[DIAG] gateway 送信前パラメータ診断', [
                'contract_id' => $contract->id,
                'gateway_url' => $gatewayUrl,
                'aid_set' => $storeId !== '',
                'aid_length' => strlen($storeId),
                'access_key_set' => $accessKey !== '',
                'param_keys' => array_keys($params),
                'cod' => $params['cod'] ?? null,
                'am' => $params['am'] ?? null,
                'tx' => $params['tx'] ?? null,
                'sf' => $params['sf'] ?? null,
                'tkn_set' => !empty($params['tkn']),
                'pattern' => $pattern,
                'api1_success' => $api1Success,
            ]);

            if ($this->patternService->isAutoBillingInitial($pattern)) {
                Log::channel('contract_payment')->info('自動課金初回決済（商品登録なし）: actp, acam, ac1, ac4 を送信', [
                    'contract_id' => $contract->id,
                    'actp' => $amounts['actp'] ?? null,
                    'acam' => $amounts['acam'] ?? null,
                    'ac1' => $amounts['ac1'] ?? null,
                    'ac4' => $amounts['ac4'] ?? null,
                ]);
            }
            $response = Http::asForm()->timeout(30)->post($gatewayUrl, $params);
            $body = $response->body();

            Log::channel('contract_payment')->info('[DIAG] gateway 応答', [
                'contract_id' => $contract->id,
                'cod' => $cod,
                'pattern' => $pattern,
                'http_status' => $response->status(),
                'response_body' => $body,
                'response_headers' => $response->headers(),
                'body_contains_ER003' => str_contains((string)$body, 'ER003'),
            ]);

            if ($response->successful() && $this->isGatewaySuccess($body)) {
                $contract->update(['payment_id' => $payment->id]);

                if (config('billing_robo.base_url') && config('billing_robo.user_id')
                    && $contract->billing_code && ($payment->billing_payment_method_number !== null || $payment->billing_payment_method_code)) {
                    try {
                        $creditCardService = app(BillingRoboCreditCardService::class);
                        $api2Result = $creditCardService->registerToken($contract, $payment, $token);
                        if ($api2Result['success']) {
                            Log::channel('contract_payment')->info('請求管理ロボ API 2 クレジットカード登録完了', ['contract_id' => $contract->id]);
                        } else {
                            Log::channel('contract_payment')->warning('請求管理ロボ API 2 クレジットカード登録失敗', [
                                'contract_id' => $contract->id,
                                'error' => $api2Result['error'] ?? '',
                            ]);
                        }
                    } catch (\Throwable $e) {
                        Log::channel('contract_payment')->warning('請求管理ロボ API 2 例外', [
                            'contract_id' => $contract->id,
                            'message' => $e->getMessage(),
                        ]);
                    }
                }

                return ['success' => true, 'contract' => $contract, 'error' => null];
            }

            $errorMessage = $this->parseGatewayError($body) ?: '決済処理に失敗しました。';
            Log::channel('contract_payment')->warning('[DIAG] gateway エラー詳細', [
                'contract_id' => $contract->id,
                'raw_body' => $body,
                'parsed_error' => $errorMessage,
                'gateway_url' => $gatewayUrl ?? config('robotpayment.gateway_url'),
                'aid_sent' => $params['aid'] ?? '(empty)',
                'access_key_sent' => !empty($params['access_key']) ? '(set)' : '(empty)',
            ]);
            $payment->update([
                'status' => 'failed',
                'failure_reason' => $errorMessage,
            ]);
            return ['success' => false, 'contract' => $contract, 'error' => $errorMessage];
        });
    }

    private function buildGatewayParams(Contract $contract, array $amounts, string $pattern, string $token, ?Payment $payment = null): array
    {
        $cod = $payment && $payment->merchant_order_no
            ? $payment->merchant_order_no
            : (string) $contract->id;
        $params = [
            'aid' => config('robotpayment.store_id'),
            'jb' => config('robotpayment.job_type', 'CAPTURE'),
            'rt' => config('robotpayment.reply_type', '0'),
            'cod' => $cod,
        ];
        $accessKey = config('robotpayment.access_key', '');
        if ($accessKey !== '') {
            $params['access_key'] = $accessKey;
        }
        $params = array_merge($params, [
            'tkn' => $token,
            'em' => $contract->email ?: '',
            'pn' => preg_replace('/\D/', '', $contract->phone ?? ''),
            'am' => $amounts['am'],
            'tx' => $amounts['tx'],
            'sf' => $amounts['sf'],
        ]);

        if ($this->patternService->isAutoBillingInitial($pattern)) {
            $params['actp'] = $amounts['actp'];
            $params['acam'] = $amounts['acam'];
            $params['actx'] = $amounts['actx'];
            $params['acsf'] = $amounts['acsf'];
            $params['ac1'] = $amounts['ac1'];
            if (!empty($amounts['ac4'])) {
                $params['ac4'] = $amounts['ac4'];
            }
        }

        return $params;
    }

    /**
     * レスポンス形式: カンマ区切りで2番目が決済結果（1:成功 / 2:失敗）、または "OK" の場合は成功
     */
    private function isGatewaySuccess(string $body): bool
    {
        $body = trim($body);
        if ($body === 'OK') {
            return true;
        }
        $parts = explode(',', $body);
        return isset($parts[1]) && $parts[1] === '1';
    }

    private function parseGatewayError(string $body): ?string
    {
        $body = trim($body);
        if (preg_match('/^ER:(.+)$/m', $body, $m)) {
            $code = trim($m[1]);
            if ($code === 'ER003') {
                return '送信元IPの認証に失敗しました。';
            }
            return 'エラーコード: ' . $code;
        }
        $parts = explode(',', $body);
        if (isset($parts[3]) && $parts[3] !== '') {
            return 'エラーコード: ' . $parts[3];
        }
        if (str_contains($body, 'ER003')) {
            return '送信元IPの認証に失敗しました。';
        }
        return null;
    }
}
