<?php

namespace App\Services\BillingRobo;

use App\Exceptions\BillingRoboApiException;
use App\Models\Contract;
use Illuminate\Support\Facades\Log;

/**
 * 請求管理ロボ API 5: 即時決済（請求書合算）
 * 参照: 07_api_05_bulk_register.md
 * 請求情報登録・請求書発行・クレジット決済を一括で実行する。API 1・API 2 完了後のみ利用可。
 */
class BillingRoboBulkRegisterService
{
    /** 請求方法: 自動メール */
    private const BILLING_METHOD_AUTO_MAIL = 1;

    /** 請求書テンプレート: シンプル */
    private const BILL_TEMPLATE_SIMPLE = 10010;

    /** 対象期間形式: ○年○月分 */
    private const PERIOD_FORMAT_MONTH = 0;

    /** 請求タイプ: 単発 */
    private const DEMAND_TYPE_ONE_TIME = 0;

    /** 請求タイプ: 定期定額 */
    private const DEMAND_TYPE_RECURRING = 1;

    /**
     * API5（/api/demand/bulk_register）の jb は「仮実同時売上: CAPTURE」のみ有効。
     * AUTH を送ると error 232 Invalid 'jb'. となる（公式: 07_api_05_bulk_register.md）。
     * 仮売上は ROBOT PAYMENT 直送信経路（請求ロボ未使用時の gateway）では ROBOTPAYMENT_JOB_TYPE=AUTH で可能。
     */
    public function __construct(
        private BillingRoboApiClient $client,
        private ContractToBillingLinesMapper $linesMapper
    ) {}

    /**
     * 契約に基づき API 5 即時決済を実行する。
     * 請求書は1件のみ有効（242）。本日を発行日・送付日・決済期限とする。
     *
     * @return array{success: bool, error?: string, ec?: string}
     */
    public function executeForContract(Contract $contract): array
    {
        $billingCode = $contract->billing_code;
        if ($billingCode === null || $billingCode === '') {
            return ['success' => false, 'error' => '請求先コードが未登録です。先に API 1 を実行してください。'];
        }

        $hasIndividual = $contract->billing_individual_number !== null
            || ($contract->billing_individual_code !== null && $contract->billing_individual_code !== '');
        if (!$hasIndividual) {
            Log::channel('contract_payment')->warning('請求管理ロボ API 5: 請求先部署が未設定', ['contract_id' => $contract->id]);
            return ['success' => false, 'error' => '請求先部署が未登録です。しばらくしてから再度お試しください。'];
        }

        $bill = $this->buildBill($contract);
        if ($bill === null) {
            return ['success' => false, 'error' => '請求内容を組み立てられませんでした。'];
        }

        $path = 'api/demand/bulk_register';
        $body = ['bill' => [$bill]];

        try {
            $result = $this->client->post($path, $body, false);
        } catch (BillingRoboApiException $e) {
            Log::channel('contract_payment')->warning('請求管理ロボ API 5 接続失敗', [
                'contract_id' => $contract->id,
                'message' => $e->getMessage(),
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }

        $status = $result['status'];
        $resBody = $result['body'];
        $error = $result['error'];

        if ($status >= 400) {
            $msg = $error['message'] ?? "HTTP {$status}";
            Log::channel('contract_payment')->warning('請求管理ロボ API 5 エラー', [
                'contract_id' => $contract->id,
                'status' => $status,
                'error' => $error,
            ]);
            return ['success' => false, 'error' => $msg];
        }

        // エラー時は user.bill で返却（各要素に error_code, error_message, ec）
        if (isset($resBody['user']['bill']) && is_array($resBody['user']['bill'])) {
            foreach ($resBody['user']['bill'] as $b) {
                $ec = $b['error_code'] ?? null;
                $em = $b['error_message'] ?? null;
                if ($ec !== null || $em !== null) {
                    Log::channel('contract_payment')->warning('請求管理ロボ API 5 請求書エラー', [
                        'contract_id' => $contract->id,
                        'error_code' => $ec,
                        'error_message' => $em,
                        'ec' => $b['ec'] ?? null,
                    ]);
                    $userMessage = $em ?? "エラーコード {$ec}";
                    if (($b['ec'] ?? null) === 'ER018') {
                        $userMessage = '決済可能上限額を超えています。製品・オプションの合計金額をご確認いただくか、決済代行会社にお問い合わせください。';
                    }
                    return [
                        'success' => false,
                        'error' => $userMessage,
                        'ec' => $b['ec'] ?? null,
                    ];
                }
            }
        }

        Log::channel('contract_payment')->info('請求管理ロボ API 5 即時決済完了', [
            'contract_id' => $contract->id,
        ]);
        return ['success' => true];
    }

    /**
     * 契約から API 5 用の bill 1件を組み立てる。発行日・送付日・決済期限は本日固定（API5 制約）。
     */
    private function buildBill(Contract $contract): ?array
    {
        $lines = $this->linesMapper->map($contract);
        if ($lines === []) {
            return null;
        }

        $today = now()->format('Y/m/d');
        $individual = $this->buildIndividualSpec($contract);
        if ($individual === []) {
            return null;
        }

        $startDate = $this->formatStartDate($contract);
        $billDetails = [];
        foreach ($lines as $line) {
            $detail = [
                'demand_type' => $line['demand_type'],
                'goods_name' => $line['goods_name'],
                'price' => $line['price'],
                'quantity' => $line['quantity'],
                'unit' => '円',
                'tax_category' => $line['tax_category'],
                'tax' => $line['tax'],
                'start_date' => $startDate,
                'period_format' => self::PERIOD_FORMAT_MONTH,
                'sales_recorded_date' => $today,
            ];
            if ($line['demand_type'] === self::DEMAND_TYPE_RECURRING) {
                $detail['repetition_period_number'] = 1;
                $detail['repetition_period_unit'] = 1;
                $detail['repeat_count'] = 0;
            }
            $billDetails[] = $detail;
        }

        return [
            'billing_code' => $contract->billing_code,
            ...$individual,
            'billing_method' => self::BILLING_METHOD_AUTO_MAIL,
            'bill_template_code' => self::BILL_TEMPLATE_SIMPLE,
            'tax' => 10,
            'issue_date' => $today,
            'sending_date' => $today,
            'deadline_date' => $today,
            'jb' => 'CAPTURE',
            'bill_detail' => $billDetails,
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
}
