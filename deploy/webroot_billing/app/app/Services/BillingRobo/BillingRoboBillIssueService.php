<?php

namespace App\Services\BillingRobo;

use App\Exceptions\BillingRoboApiException;
use App\Models\Contract;
use App\Models\BillingRoboDemand;
use Illuminate\Support\Facades\Log;

/**
 * 請求管理ロボ API 4: 請求書発行の呼び出し
 * 参照: 06_api_04_bulk_issue_bill_select.md
 * 発行対象は API 3 で登録した請求情報の number または code
 */
class BillingRoboBillIssueService
{
    public function __construct(
        private BillingRoboApiClient $client
    ) {}

    /**
     * 指定した請求情報番号・コードで請求書発行（API 4）を実行する。
     *
     * @param  array<int, array{number?: int|null, code?: string|null}>  $demandSpecs  number または code のいずれかを持つ配列（重複不可）
     * @return array{success: bool, results: array<int, array{number: int|null, code: string|null, bill_number: string|null, issue_date: string|null, error_message: string|null}>}
     */
    public function issueBillSelect(array $demandSpecs): array
    {
        $demand = [];
        foreach ($demandSpecs as $spec) {
            $entry = [];
            if (isset($spec['number']) && $spec['number'] !== null) {
                $entry['number'] = (int) $spec['number'];
            } elseif (isset($spec['code']) && $spec['code'] !== null && $spec['code'] !== '') {
                $entry['code'] = (string) $spec['code'];
            } else {
                continue;
            }
            $demand[] = $entry;
        }

        if (empty($demand)) {
            return ['success' => false, 'results' => []];
        }

        $path = 'demand/bulk_issue_bill_select';
        $body = ['demand' => $demand];

        try {
            $result = $this->client->post($path, $body, true);
        } catch (BillingRoboApiException $e) {
            Log::channel('contract_payment')->warning('請求管理ロボ API 4 接続失敗', [
                'message' => $e->getMessage(),
            ]);
            return ['success' => false, 'results' => []];
        }

        $status = $result['status'];
        $resBody = $result['body'];
        $error = $result['error'];

        if ($status >= 400) {
            $msg = $error['message'] ?? "HTTP {$status}";
            Log::channel('contract_payment')->warning('請求管理ロボ API 4 エラー', [
                'status' => $status,
                'error' => $error,
            ]);
            return ['success' => false, 'results' => []];
        }

        return $this->parseIssueResponse($resBody, $demandSpecs);
    }

    /**
     * 契約に紐づく請求情報（billing_robo_demands）を取得して請求書発行する。
     *
     * @return array{success: bool, results: array}
     */
    public function issueBillForContract(Contract $contract): array
    {
        $demands = BillingRoboDemand::where('contract_id', $contract->id)->get();
        if ($demands->isEmpty()) {
            Log::channel('contract_payment')->info('請求書発行スキップ（請求情報未登録）', ['contract_id' => $contract->id]);
            return ['success' => false, 'results' => []];
        }

        $specs = [];
        foreach ($demands as $d) {
            $spec = [];
            if ($d->demand_number !== null) {
                $spec['number'] = $d->demand_number;
            } elseif ($d->demand_code !== null && $d->demand_code !== '') {
                $spec['code'] = $d->demand_code;
            } else {
                continue;
            }
            $specs[] = $spec;
        }

        return $this->issueBillSelect($specs);
    }

    /**
     * API 4 レスポンスをパースする
     *
     * @param  array<int, array{number?: int, code?: string}>  $requestDemand
     */
    private function parseIssueResponse(?array $resBody, array $requestDemand): array
    {
        $results = [];
        if (!is_array($resBody) || empty($resBody['demand']) || !is_array($resBody['demand'])) {
            return ['success' => false, 'results' => []];
        }

        foreach ($resBody['demand'] as $index => $d) {
            if (!is_array($d)) {
                continue;
            }
            $req = $requestDemand[$index] ?? [];
            $number = $d['number'] ?? $req['number'] ?? null;
            $code = $d['code'] ?? $req['code'] ?? null;
            $errorMessage = $d['error_message'] ?? null;
            $billNumber = null;
            $issueDate = null;

            if ($errorMessage === null && !empty($d['sales']) && is_array($d['sales'])) {
                $firstSale = $d['sales'][0] ?? null;
                if (is_array($firstSale) && !empty($firstSale['bill']) && is_array($firstSale['bill'])) {
                    $firstBill = $firstSale['bill'][0] ?? null;
                    if (is_array($firstBill)) {
                        $billNumber = $firstBill['number'] ?? null;
                        $issueDate = $firstBill['issue_date'] ?? null;
                    }
                }
            }

            $results[] = [
                'number' => $number,
                'code' => $code,
                'bill_number' => $billNumber,
                'issue_date' => $issueDate,
                'error_message' => $errorMessage,
            ];
        }

        $success = collect($results)->every(fn ($r) => $r['error_message'] === null);
        return ['success' => $success, 'results' => $results];
    }
}
