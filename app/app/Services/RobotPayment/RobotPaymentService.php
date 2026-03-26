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
use Illuminate\Support\Facades\Log;

/**
 * 申込処理: 契約・明細・Payment を作成し、請求管理ロボへ API1→API2→API3 で登録する。
 * RP gateway への直接決済は行わない（課金は請求管理ロボのスケジュールに委ねる）。
 */
class RobotPaymentService
{
    public function __construct(
        private PurchasePatternService $patternService
    ) {}

    /**
     * セッションの申込データとトークンで契約・明細・Payment を作成し、
     * 請求管理ロボへ API1（請求先登録）→ API2（カードトークン登録）→ API3（請求情報登録）を実行する。
     *
     * @param  array  $debugContext  ER584 原因特定用（correlation_id, token_age_ms 等）
     * @return array{success: bool, contract: Contract|null, error: string|null}
     */
    public function executePayment(array $sessionData, string $token, array $debugContext = []): array
    {
        $correlationId = $debugContext['correlation_id'] ?? null;
        PaymentStageLogger::info(PaymentStageLogger::STAGE_SVC_ENTRY, '申込サービス開始', [
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

        return DB::transaction(function () use ($sessionData, $token, $debugContext, $plans, $representativePlanId, $optionProductIds, $amounts, $pattern, $correlationId) {
            $createData = $sessionData;
            unset($createData['option_product_ids'], $createData['base_plan_ids'], $createData['terms_agreed']);
            if (empty($createData['desired_start_date'])) {
                $createData['desired_start_date'] = now()->format('Y-m-d');
            }
            $createData['contract_plan_id'] = $representativePlanId;

            $contract = Contract::create([
                ...$createData,
                'status' => 'applied',
                'billing_robo_mode' => Contract::BILLING_ROBO_MODE_API3_STANDARD,
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

            $cod = $this->buildUnifiedOrderCode($contract->id, $contract->created_at);
            if (empty($contract->billing_code)) {
                $contract->billing_code = $cod;
                $contract->save();
            }
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

            if (!$billingRoboEnabled) {
                $payment->update(['status' => 'failed', 'failure_reason' => '請求管理ロボが未設定です。']);
                return ['success' => false, 'contract' => $contract, 'error' => '請求管理ロボが未設定のため、カード登録を実行できません。'];
            }

            PaymentStageLogger::info(PaymentStageLogger::STAGE_SVC_BILLING_ROBO, '請求ロボ連携 API1→API2→API3', [
                'contract_id' => $contract->id,
                'pattern' => $pattern,
            ], $correlationId);

            try {
                $executionService = app(BillingRoboExecutionService::class);
                $result = $executionService->executeForContract($contract, $payment, $token, $debugContext);
                if ($result['success']) {
                    $contract->refresh();
                    $payment->update(['status' => 'paid']);
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
        });
    }

    private function sendContractMailsOnce(Contract $contract): void
    {
        try {
            app(ContractMailService::class)->sendOnce($contract);
        } catch (\Throwable $e) {
            Log::channel('contract_payment')->error('申込完了メール送信エラー', [
                'contract_id' => $contract->id,
                'message' => $e->getMessage(),
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }
    }

    private function buildUnifiedOrderCode(?int $contractId, $createdAt = null): string
    {
        $id = $contractId ?? 0;
        $datePart = ($createdAt ?? now())->format('ymdHi');
        $idBase36 = strtoupper(base_convert((string) $id, 10, 36));
        $idPart = str_pad($idBase36, 6, '0', STR_PAD_LEFT);

        return 'DSC' . $datePart . $idPart;
    }
}
