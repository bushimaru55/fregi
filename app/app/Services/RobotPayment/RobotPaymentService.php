<?php

namespace App\Services\RobotPayment;

use App\Mail\ContractNotificationMail;
use App\Mail\ContractReplyMail;
use App\Models\Contract;
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

            $params = $this->buildGatewayParams($contract, $amounts, $pattern, $token);
            if ($this->patternService->isAutoBillingInitial($pattern)) {
                Log::channel('contract_payment')->info('自動課金初回決済（商品登録なし）: actp, acam, ac1, ac4 を送信', [
                    'contract_id' => $contract->id,
                    'actp' => $amounts['actp'] ?? null,
                    'acam' => $amounts['acam'] ?? null,
                    'ac1' => $amounts['ac1'] ?? null,
                    'ac4' => $amounts['ac4'] ?? null,
                ]);
            }
            $response = Http::asForm()->timeout(30)->post(config('robotpayment.gateway_url'), $params);
            $body = $response->body();

            Log::channel('contract_payment')->info('ROBOT PAYMENT gateway_token 送信', [
                'contract_id' => $contract->id,
                'cod' => $cod,
                'pattern' => $pattern,
                'http_status' => $response->status(),
                'response_body' => $body,
            ]);

            if ($response->successful() && $this->isGatewaySuccess($body)) {
                $contract->update(['payment_id' => $payment->id]);
                return ['success' => true, 'contract' => $contract, 'error' => null];
            }

            $errorMessage = $this->parseGatewayError($body) ?: '決済処理に失敗しました。';
            $payment->update([
                'status' => 'failed',
                'failure_reason' => $errorMessage,
            ]);
            return ['success' => false, 'contract' => $contract, 'error' => $errorMessage];
        });
    }

    private function buildGatewayParams(Contract $contract, array $amounts, string $pattern, string $token): array
    {
        $params = [
            'aid' => config('robotpayment.store_id'),
            'jb' => config('robotpayment.job_type', 'CAPTURE'),
            'rt' => config('robotpayment.reply_type', '0'),
            'cod' => (string) $contract->id,
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
