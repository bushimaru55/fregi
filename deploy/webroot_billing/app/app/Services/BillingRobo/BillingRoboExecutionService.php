<?php

namespace App\Services\BillingRobo;

use App\Logging\PaymentStageLogger;
use App\Models\Contract;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;

/**
 * Billing-Robo 連携の実行をモードに応じて分岐する。
 * API5 即時決済モード: API1 → API2 → API5
 * API3 標準運用モード: API1（スケジュール付き）→ API2 → API3
 */
class BillingRoboExecutionService
{
    public function __construct(
        private BillingRoboBillingService $billingService,
        private BillingRoboCreditCardService $creditCardService,
        private BillingRoboBulkRegisterService $bulkRegisterService,
        private BillingRoboDemandService $demandService,
        private BillingScheduleService $scheduleService
    ) {}

    /**
     * 契約の billing_robo_mode に応じて API1→2→5 または API1→2→3 を実行する。
     *
     * @param  array  $debugContext  API2 失敗時の ER584 等デバッグ用
     * @return array{success: bool, error: string|null}
     */
    public function executeForContract(Contract $contract, Payment $payment, string $token, array $debugContext = []): array
    {
        $correlationId = $debugContext['correlation_id'] ?? null;

        $schedule = null;
        if (!$contract->isBillingRoboApi5Immediate()) {
            $schedule = $this->scheduleService->getScheduleForApplication($contract);
        }

        $api1Result = $this->billingService->upsertBillingFromContract($contract, $schedule);
        if (!$api1Result['success']) {
            PaymentStageLogger::warning(PaymentStageLogger::STAGE_API1_FAIL, '請求管理ロボ API1 失敗', [
                'contract_id' => $contract->id,
                'error' => mb_substr($api1Result['error'] ?? '', 0, 200),
            ], $correlationId);
            Log::channel('contract_payment')->warning('請求管理ロボ API 1 失敗', [
                'contract_id' => $contract->id,
                'error' => $api1Result['error'] ?? '',
            ]);
            return ['success' => false, 'error' => $api1Result['error'] ?? '請求先登録に失敗しました。'];
        }
        PaymentStageLogger::info(PaymentStageLogger::STAGE_API1_OK, '請求管理ロボ API1 成功', [
            'contract_id' => $contract->id,
            'billing_code' => $api1Result['billing_code'] ?? null,
        ], $correlationId);

        $payment->refresh();
        $api2Result = $this->creditCardService->registerToken($contract, $payment, $token, $debugContext);
        if (!$api2Result['success']) {
            PaymentStageLogger::warning(PaymentStageLogger::STAGE_API2_FAIL, '請求管理ロボ API2 失敗', [
                'contract_id' => $contract->id,
                'error' => mb_substr($api2Result['error'] ?? '', 0, 200),
            ], $correlationId);
            Log::channel('contract_payment')->warning('請求管理ロボ API 2 失敗', [
                'contract_id' => $contract->id,
                'error' => $api2Result['error'] ?? '',
            ]);
            return ['success' => false, 'error' => $api2Result['error'] ?? 'クレジットカード登録に失敗しました。'];
        }
        PaymentStageLogger::info(PaymentStageLogger::STAGE_API2_OK, '請求管理ロボ API2 成功', ['contract_id' => $contract->id], $correlationId);

        $contract->refresh();

        if ($contract->isBillingRoboApi5Immediate()) {
            $api5Result = $this->bulkRegisterService->executeForContract($contract);
            if (!$api5Result['success']) {
                PaymentStageLogger::warning(PaymentStageLogger::STAGE_API5_FAIL, '請求管理ロボ API5 失敗', [
                    'contract_id' => $contract->id,
                    'error' => mb_substr($api5Result['error'] ?? '', 0, 200),
                ], $correlationId);
                return ['success' => false, 'error' => $api5Result['error'] ?? '即時決済に失敗しました。'];
            }
            PaymentStageLogger::info(PaymentStageLogger::STAGE_API5_OK, '請求管理ロボ API5 成功', ['contract_id' => $contract->id], $correlationId);
            return ['success' => true, 'error' => null];
        }

        $api3Result = $this->demandService->upsertDemandFromContract($contract, $schedule);
        if (!$api3Result['success']) {
            PaymentStageLogger::warning(PaymentStageLogger::STAGE_API3_FAIL, '請求管理ロボ API3 失敗', [
                'contract_id' => $contract->id,
                'error' => mb_substr($api3Result['error'] ?? '', 0, 200),
            ], $correlationId);
            return ['success' => false, 'error' => $api3Result['error'] ?? '請求情報の登録に失敗しました。'];
        }
        PaymentStageLogger::info(PaymentStageLogger::STAGE_API3_OK, '請求管理ロボ API3 成功', ['contract_id' => $contract->id], $correlationId);
        return ['success' => true, 'error' => null];
    }

    /**
     * AUTH（仮売上）モード専用: API1 → API2 のみ実行する。
     * API5 は CAPTURE 限定のため呼ばない。
     * 呼び出し後、RP gateway に直接 jb=AUTH で送信することで仮売上を実現する。
     *
     * @return array{success: bool, error: string|null}
     */
    public function executeApi1AndApi2(Contract $contract, Payment $payment, string $token, array $debugContext = []): array
    {
        $correlationId = $debugContext['correlation_id'] ?? null;

        $api1Result = $this->billingService->upsertBillingFromContract($contract, null);
        if (!$api1Result['success']) {
            PaymentStageLogger::warning(PaymentStageLogger::STAGE_API1_FAIL, '請求管理ロボ API1 失敗 (AUTH mode)', [
                'contract_id' => $contract->id,
                'error' => mb_substr($api1Result['error'] ?? '', 0, 200),
            ], $correlationId);
            return ['success' => false, 'error' => $api1Result['error'] ?? '請求先登録に失敗しました。'];
        }
        PaymentStageLogger::info(PaymentStageLogger::STAGE_API1_OK, '請求管理ロボ API1 成功 (AUTH mode)', [
            'contract_id' => $contract->id,
            'billing_code' => $api1Result['billing_code'] ?? null,
        ], $correlationId);

        $payment->refresh();
        $api2Result = $this->creditCardService->registerToken($contract, $payment, $token, $debugContext);
        if (!$api2Result['success']) {
            PaymentStageLogger::warning(PaymentStageLogger::STAGE_API2_FAIL, '請求管理ロボ API2 失敗 (AUTH mode)', [
                'contract_id' => $contract->id,
                'error' => mb_substr($api2Result['error'] ?? '', 0, 200),
            ], $correlationId);
            return ['success' => false, 'error' => $api2Result['error'] ?? 'クレジットカード登録に失敗しました。'];
        }
        PaymentStageLogger::info(PaymentStageLogger::STAGE_API2_OK, '請求管理ロボ API2 成功 (AUTH mode)', ['contract_id' => $contract->id], $correlationId);

        return ['success' => true, 'error' => null];
    }
}
