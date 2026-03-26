<?php

namespace App\Services\BillingRobo;

use App\Exceptions\BillingRoboApiException;
use App\Models\Contract;
use App\Models\BillingRoboDemand;
use Illuminate\Support\Facades\Log;

/**
 * 請求管理ロボ API 3: 請求情報登録更新の呼び出しとレスポンスの DB 反映
 * 参照: 05_api_03_demand_bulk_upsert.md
 * item_code は使わず goods_name, price, quantity, tax 等で送信（08 方針）
 */
class BillingRoboDemandService
{
    /** 請求タイプ: 単発 */
    private const TYPE_ONE_TIME = 0;

    /** 請求タイプ: 定期定額 */
    private const TYPE_RECURRING = 1;

    /** 請求方法: 自動メール */
    private const BILLING_METHOD_AUTO_MAIL = 1;

    /** 対象期間形式: ○年○月分 */
    private const PERIOD_FORMAT_MONTH = 0;

    /** 請求書テンプレート: シンプル（API5 と共通） */
    private const BILL_TEMPLATE_SIMPLE = 10010;

    public function __construct(
        private BillingRoboApiClient $client,
        private ContractToBillingLinesMapper $linesMapper
    ) {}

    /**
     * 契約から請求情報登録（API 3）を実行し、成功した請求情報を billing_robo_demands に保存する。
     *
     * @param  array{issue_month?: int, issue_day?: int, sending_month?: int, sending_day?: int, deadline_month?: int, deadline_day?: int}|null  $schedule  請求先側スケジュールと合わせる場合。BillingScheduleService::getScheduleForApplication の戻り値。
     * @return array{success: bool, demands: array<int, array{number: int|null, code: string|null}>, error?: string}
     */
    public function upsertDemandFromContract(Contract $contract, ?array $schedule = null): array
    {
        $billingCode = $contract->billing_code;
        if ($billingCode === null || $billingCode === '') {
            return [
                'success' => false,
                'demands' => [],
                'error' => '請求先コードが未登録です。先に API 1 を実行してください。',
            ];
        }

        $demandArray = $this->buildDemandArray($contract, $schedule);
        if (empty($demandArray)) {
            return [
                'success' => false,
                'demands' => [],
                'error' => '請求情報が1件もありません（契約明細を確認してください）。',
            ];
        }

        $path = 'demand/bulk_upsert';
        $body = ['demand' => $demandArray];
        $maxAttempts = 2;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $result = $this->client->post($path, $body, true);
            } catch (BillingRoboApiException $e) {
                Log::channel('contract_payment')->warning('請求管理ロボ API 3 接続失敗', [
                    'contract_id' => $contract->id,
                    'attempt' => $attempt,
                    'message' => $e->getMessage(),
                ]);
                return [
                    'success' => false,
                    'demands' => [],
                    'error' => $e->getMessage(),
                ];
            }

            $status = $result['status'];
            $resBody = $result['body'];
            $error = $result['error'];

            if ($status >= 400) {
                $msg = $error['message'] ?? "HTTP {$status}";
                Log::channel('contract_payment')->warning('請求管理ロボ API 3 エラー', [
                    'contract_id' => $contract->id,
                    'attempt' => $attempt,
                    'status' => $status,
                    'error' => $error,
                ]);
                return [
                    'success' => false,
                    'demands' => [],
                    'error' => $msg,
                ];
            }

            $parsed = $this->parseAndSaveDemandResponse($contract, $resBody, $demandArray);

            // 1341（cooperation_status 不正）は API 2 の反映遅延の可能性がある。リトライ
            if (!$parsed['success'] && $attempt < $maxAttempts && $this->hasRetryableError($resBody, [1341])) {
                Log::channel('contract_payment')->info('請求管理ロボ API 3 リトライ（1341 cooperation_status 待ち）', [
                    'contract_id' => $contract->id,
                    'attempt' => $attempt,
                ]);
                sleep(2);
                continue;
            }

            return $parsed;
        }

        return ['success' => false, 'demands' => [], 'error' => 'リトライ上限到達'];
    }

    /**
     * レスポンスの demand 配列内に指定エラーコードが含まれるか判定
     */
    private function hasRetryableError(mixed $resBody, array $codes): bool
    {
        $demands = $resBody['demand'] ?? $resBody['demands'] ?? [];
        foreach ($demands as $d) {
            $ec = $d['error_code'] ?? null;
            if ($ec !== null && in_array((int)$ec, $codes, true)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 契約・契約明細から API 3 用の demand 配列を組み立てる（item_code は使わない）。
     *
     * @param  array{issue_month?: int, issue_day?: int, sending_month?: int, sending_day?: int, deadline_month?: int, deadline_day?: int}|null  $schedule  請求先側スケジュールと合わせる場合。null のときは 0/1 固定。
     * @return array<int, array<string, mixed>>
     */
    public function buildDemandArray(Contract $contract, ?array $schedule = null): array
    {
        $lines = $this->linesMapper->map($contract);
        if ($lines === []) {
            return [];
        }

        $startDate = $this->formatStartDate($contract);
        $individual = $this->buildIndividualSpec($contract);

        $issueMonth = $schedule['issue_month'] ?? 0;
        $issueDay = $schedule['issue_day'] ?? 1;
        $sendingMonth = $schedule['sending_month'] ?? 0;
        $sendingDay = $schedule['sending_day'] ?? 1;
        $deadlineMonth = $schedule['deadline_month'] ?? 0;
        $deadlineDay = $schedule['deadline_day'] ?? 1;

        $demands = [];
        foreach ($lines as $line) {
            $type = $line['demand_type'];
            $row = [
                'billing_code' => $contract->billing_code,
                ...$individual,
                'type' => $type,
                'goods_name' => $line['goods_name'],
                'price' => $line['price'],
                'quantity' => $line['quantity'],
                'tax_category' => $line['tax_category'],
                'tax' => $line['tax'],
                'billing_method' => self::BILLING_METHOD_AUTO_MAIL,
                'bill_template_code' => self::BILL_TEMPLATE_SIMPLE,
                'start_date' => $startDate,
                'period_format' => self::PERIOD_FORMAT_MONTH,
                'issue_month' => $issueMonth,
                'issue_day' => $issueDay,
                'sending_month' => $sendingMonth,
                'sending_day' => $sendingDay,
                'deadline_month' => $deadlineMonth,
                'deadline_day' => $deadlineDay,
            ];

            if ($type === self::TYPE_RECURRING) {
                $row['repetition_period_number'] = 1;
                $row['repetition_period_unit'] = 1;
                $row['repeat_count'] = 0;
            }

            $demands[] = $row;
        }

        return $demands;
    }

    /**
     * API 3 レスポンスをパースし、成功した請求情報を billing_robo_demands に保存する
     *
     * @param  array<int, array<string, mixed>>  $requestDemands  送信した demand の並び（type 判定用）
     */
    private function parseAndSaveDemandResponse(Contract $contract, ?array $resBody, array $requestDemands): array
    {
        if (!is_array($resBody) || empty($resBody['demand']) || !is_array($resBody['demand'])) {
            return [
                'success' => false,
                'demands' => [],
                'error' => 'レスポンスの demand を解析できませんでした',
            ];
        }

        $saved = [];
        $hasError = false;
        foreach ($resBody['demand'] as $index => $d) {
            if (!is_array($d)) {
                continue;
            }
            $errorCode = $d['error_code'] ?? null;
            $errorMessage = $d['error_message'] ?? null;
            if ($errorCode !== null || $errorMessage !== null) {
                $hasError = true;
                Log::channel('contract_payment')->warning('請求管理ロボ API 3 請求情報エラー', [
                    'contract_id' => $contract->id,
                    'index' => $index,
                    'error_code' => $errorCode,
                    'error_message' => $errorMessage,
                ]);
                continue;
            }

            $number = isset($d['number']) ? (int) $d['number'] : null;
            $code = isset($d['code']) ? (string) $d['code'] : null;
            if ($number === null && ($code === null || $code === '')) {
                continue;
            }

            $demandType = 'initial';
            if (isset($requestDemands[$index]['type']) && $requestDemands[$index]['type'] === self::TYPE_RECURRING) {
                $demandType = 'recurring';
            }

            BillingRoboDemand::create([
                'contract_id' => $contract->id,
                'demand_number' => $number,
                'demand_code' => $code,
                'demand_type' => $demandType,
            ]);

            $saved[] = ['number' => $number, 'code' => $code];
        }

        if (!empty($saved)) {
            Log::channel('contract_payment')->info('請求管理ロボ API 3 請求情報登録完了', [
                'contract_id' => $contract->id,
                'count' => count($saved),
                'demands' => $saved,
            ]);
        }

        return [
            'success' => !$hasError && !empty($saved),
            'demands' => $saved,
            'error' => $hasError ? '一部の請求情報でエラーが発生しました' : null,
        ];
    }

    private function formatStartDate(Contract $contract): string
    {
        $date = $contract->desired_start_date ?? $contract->actual_start_date ?? now();
        if (is_string($date)) {
            $date = \Carbon\Carbon::parse($date);
        }
        return $date->format('Y/m/d');
    }

    /** @return array{billing_individual_number?: int, billing_individual_code?: string} */
    private function buildIndividualSpec(Contract $contract): array
    {
        if ($contract->billing_individual_number !== null) {
            return ['billing_individual_number' => (int) $contract->billing_individual_number];
        }
        if ($contract->billing_individual_code !== null && $contract->billing_individual_code !== '') {
            return ['billing_individual_code' => $contract->billing_individual_code];
        }
        return [];
    }

    private function mapBillingTypeToDemandType(string $billingType): int
    {
        return strtolower($billingType) === 'monthly' ? self::TYPE_RECURRING : self::TYPE_ONE_TIME;
    }
}
