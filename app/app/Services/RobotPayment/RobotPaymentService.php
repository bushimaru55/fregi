<?php

namespace App\Services\RobotPayment;

use App\Logging\PaymentStageLogger;
use App\Models\Contract;
use App\Services\BillingRobo\BillingRoboExecutionService;
use App\Services\ContractMailService;
use App\Models\ContractItem;
use App\Models\ContractPlan;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
     *
     * @param  array  $debugContext  ER584 原因特定用（correlation_id, token_age_ms 等）。API2 失敗時に [ER584_DEBUG] で出力
     * @return array{success: bool, contract: Contract|null, error: string|null}
     */
    public function executePayment(array $sessionData, string $token, array $debugContext = []): array
    {
        $correlationId = $debugContext['correlation_id'] ?? null;
        PaymentStageLogger::info(PaymentStageLogger::STAGE_SVC_ENTRY, '決済サービス開始', [
            'has_token' => !empty($token),
            'session_keys' => array_keys($sessionData),
        ], $correlationId);

        $basePlanIds = $sessionData['base_plan_ids'] ?? (isset($sessionData['contract_plan_id']) ? [$sessionData['contract_plan_id']] : []);
        $optionProductIds = $sessionData['option_product_ids'] ?? [];
        $desiredStartDate = $sessionData['desired_start_date'] ?? now()->format('Y-m-d');

        if (empty($basePlanIds)) {
            throw new \InvalidArgumentException('申込データにベース製品が含まれていません。');
        }

        $amounts = $this->patternService->getAmountsFromPlansAndOptions($basePlanIds, $optionProductIds, $desiredStartDate);
        $pattern = $amounts['pattern'];
        $plans = ContractPlan::whereIn('id', $basePlanIds)->get();
        $representativePlanId = $plans->isNotEmpty() ? $plans->first()->id : null;

        return DB::transaction(function () use ($sessionData, $token, $debugContext, $plans, $representativePlanId, $optionProductIds, $desiredStartDate, $amounts, $pattern, $correlationId) {
            $createData = $sessionData;
            unset($createData['option_product_ids'], $createData['base_plan_ids'], $createData['terms_agreed']);
            if (empty($createData['desired_start_date'])) {
                $createData['desired_start_date'] = now()->format('Y-m-d');
            }
            $createData['contract_plan_id'] = $representativePlanId;

            $contract = Contract::create([
                ...$createData,
                'status' => 'applied',
                'billing_robo_mode' => Contract::BILLING_ROBO_MODE_API5_IMMEDIATE,
            ]);

            foreach ($plans as $plan) {
                ContractItem::create([
                    'contract_id' => $contract->id,
                    'contract_plan_id' => $plan->id,
                    'product_id' => null,
                    'product_name' => $plan->name,
                    'product_code' => $plan->item ?? '',
                    'quantity' => 1,
                    'unit_price' => $plan->price,
                    'subtotal' => $plan->price,
                    'billing_type' => $plan->billing_type ?? 'one_time',
                ]);
            }

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
                    'product_code' => $product->code ?? '',
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

            $billingRoboEnabled = config('billing_robo.base_url') && config('billing_robo.user_id');
            PaymentStageLogger::info(PaymentStageLogger::STAGE_SVC_CONTRACT_CREATED, '契約・Payment作成済み', [
                'contract_id' => $contract->id,
                'payment_id' => $payment->id,
            ], $correlationId);
            PaymentStageLogger::info(PaymentStageLogger::STAGE_SVC_BILLING_ROBO, '請求ロボ連携分岐', [
                'enabled' => $billingRoboEnabled,
                'contract_id' => $contract->id,
                'pattern' => $pattern,
            ], $correlationId);

            if ($billingRoboEnabled) {
                try {
                    $executionService = app(BillingRoboExecutionService::class);
                    $result = $executionService->executeForContract($contract, $payment, $token, $debugContext);
                    if ($result['success']) {
                        $contract->refresh();
                        $contract->update(['payment_id' => $payment->id]);
                        $this->sendContractMailsOnce($contract);
                        return ['success' => true, 'contract' => $contract, 'error' => null];
                    }
                    $errorMessage = $result['error'] ?? '請求管理ロボ連携に失敗しました。';
                    $payment->update(['status' => 'failed', 'failure_reason' => $errorMessage]);
                    return ['success' => false, 'contract' => $contract, 'error' => $errorMessage];
                } catch (\Throwable $e) {
                    PaymentStageLogger::warning(PaymentStageLogger::STAGE_EXEC_FAIL, '請求管理ロボ連携で例外', [
                        'contract_id' => $contract->id,
                        'error' => mb_substr($e->getMessage(), 0, 200),
                    ], $correlationId);
                    Log::channel('contract_payment')->warning('請求管理ロボ連携 例外', [
                        'contract_id' => $contract->id,
                        'message' => $e->getMessage(),
                    ]);
                    $payment->update(['status' => 'failed', 'failure_reason' => $e->getMessage()]);
                    return ['success' => false, 'contract' => $contract, 'error' => $e->getMessage()];
                }
            }

            // 請求管理ロボ未使用時: gateway で決済
            $params = $this->buildGatewayParams($contract, $amounts, $pattern, $token, $payment);
            $gatewayUrl = config('robotpayment.gateway_url');
            PaymentStageLogger::info(PaymentStageLogger::STAGE_SVC_GATEWAY_SEND, 'ROBOT PAYMENT gateway 送信', [
                'contract_id' => $contract->id,
                'cod' => $params['cod'] ?? null,
                'pattern' => $pattern,
            ], $correlationId);
            Log::channel('contract_payment')->info('gateway 送信', [
                'contract_id' => $contract->id,
                'cod' => $params['cod'] ?? null,
                'am' => $params['am'] ?? null,
                'pattern' => $pattern,
            ]);
            if ($this->patternService->isAutoBillingInitial($pattern)) {
                Log::channel('contract_payment')->info('自動課金初回決済: actp, acam, ac1, ac4 を送信', [
                    'contract_id' => $contract->id,
                    'actp' => $amounts['actp'] ?? null,
                    'acam' => $amounts['acam'] ?? null,
                    'ac1' => $amounts['ac1'] ?? null,
                    'ac4' => $amounts['ac4'] ?? null,
                ]);
            }
            $response = Http::asForm()->timeout(30)->post($gatewayUrl, $params);
            $body = $response->body();
            Log::channel('contract_payment')->info('gateway 応答', [
                'contract_id' => $contract->id,
                'cod' => $cod,
                'http_status' => $response->status(),
                'response_body' => $body,
            ]);

            if ($response->successful() && $this->isGatewaySuccess($body)) {
                PaymentStageLogger::info(PaymentStageLogger::STAGE_SVC_GATEWAY_OK, 'ROBOT PAYMENT gateway 成功', [
                    'contract_id' => $contract->id,
                ], $correlationId);
                $contract->update(['payment_id' => $payment->id]);
                $this->sendContractMailsOnce($contract);
                return ['success' => true, 'contract' => $contract, 'error' => null];
            }

            $errorMessage = $this->parseGatewayError($body) ?: '決済処理に失敗しました。';
            PaymentStageLogger::warning(PaymentStageLogger::STAGE_SVC_GATEWAY_FAIL, 'ROBOT PAYMENT gateway 失敗', [
                'contract_id' => $contract->id,
                'error' => mb_substr($errorMessage, 0, 200),
                'response_preview' => mb_substr($body, 0, 200),
            ], $correlationId);
            Log::channel('contract_payment')->warning('gateway エラー', [
                'contract_id' => $contract->id,
                'raw_body' => $body,
                'parsed_error' => $errorMessage,
            ]);
            $payment->update(['status' => 'failed', 'failure_reason' => $errorMessage]);
            return ['success' => false, 'contract' => $contract, 'error' => $errorMessage];
        });
    }

    /**
     * 決済成功直後に申込者・管理者へメール送信（ContractMailService に委譲）
     */
    private function sendContractMailsOnce(Contract $contract): void
    {
        try {
            app(ContractMailService::class)->sendOnce($contract);
        } catch (\Throwable $e) {
            Log::channel('contract_payment')->error('決済成功時の申込メール送信エラー', [
                'contract_id' => $contract->id,
                'message' => $e->getMessage(),
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }
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
