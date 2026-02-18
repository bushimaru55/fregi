<?php

namespace App\Services\RobotPayment;

use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\ContractPlan;
use App\Models\Product;
use Carbon\Carbon;

/**
 * 購入パターン判定と RP 送信用金額計算（01_purchase_patterns / 02_parameter_mapping 準拠）
 */
class PurchasePatternService
{
    public const PATTERN_MONTHLY_ONLY = 'monthly_only';           // A
    public const PATTERN_INITIAL_PLUS_RECURRING = 'initial_plus_recurring'; // B
    public const PATTERN_ONE_TIME_ONLY = 'one_time_only';        // C

    /**
     * 契約に紐づく明細からパターンを判定
     */
    public function detectPatternFromItems(\Illuminate\Support\Collection $items): string
    {
        $hasMonthly = $items->contains('billing_type', 'monthly');
        $hasOneTime = $items->contains('billing_type', 'one_time');

        if ($hasMonthly && !$hasOneTime) {
            return self::PATTERN_MONTHLY_ONLY;
        }
        if ($hasMonthly && $hasOneTime) {
            return self::PATTERN_INITIAL_PLUS_RECURRING;
        }
        return self::PATTERN_ONE_TIME_ONLY;
    }

    /**
     * 契約からパターンと金額を取得（契約・明細作成後用）
     */
    public function getAmountsForContract(Contract $contract): array
    {
        $items = $contract->contractItems;
        $pattern = $this->detectPatternFromItems($items);
        $desiredDate = $contract->desired_start_date
            ? Carbon::parse($contract->desired_start_date)
            : now();

        return $this->computeAmounts($items, $pattern, $desiredDate);
    }

    /**
     * プラン＋オプション商品IDからパターンと金額を取得（契約作成前の決済ページ用）
     */
    public function getAmountsFromPlanAndOptions(
        ContractPlan $plan,
        array $optionProductIds,
        string $desiredStartDate
    ): array {
        $items = collect();
        $items->push((object)[
            'billing_type' => $plan->billing_type ?? 'one_time',
            'subtotal' => (int) $plan->price,
        ]);
        foreach ($optionProductIds as $productId) {
            $product = Product::where('id', $productId)
                ->where('type', 'option')
                ->where('is_active', true)
                ->first();
            if ($product) {
                $items->push((object)[
                    'billing_type' => $product->billing_type ?? 'one_time',
                    'subtotal' => (int) $product->unit_price,
                ]);
            }
        }
        $pattern = $this->detectPatternFromItems($items);
        $desiredDate = Carbon::parse($desiredStartDate);

        return $this->computeAmounts($items, $pattern, $desiredDate);
    }

    /**
     * 明細コレクション・パターン・希望開始日から RP 用パラメータを計算
     */
    private function computeAmounts(\Illuminate\Support\Collection $items, string $pattern, Carbon $desiredDate): array
    {
        $total = $items->sum('subtotal');
        $monthlyTotal = $items->where('billing_type', 'monthly')->sum('subtotal');

        $am = $total;
        $tx = 0;
        $sf = 0;
        $acam = $pattern !== self::PATTERN_ONE_TIME_ONLY ? $monthlyTotal : null;
        $actx = 0;
        $acsf = 0;
        $actp = $pattern !== self::PATTERN_ONE_TIME_ONLY ? 4 : null; // 4 = 毎月
        $ac1 = $pattern !== self::PATTERN_ONE_TIME_ONLY ? 1 : null;  // 毎月1日
        $ac4 = $pattern !== self::PATTERN_ONE_TIME_ONLY ? $this->computeAc4($desiredDate) : null;

        return [
            'pattern' => $pattern,
            'am' => $am,
            'tx' => $tx,
            'sf' => $sf,
            'ta' => $am + $tx + $sf,
            'acam' => $acam,
            'actx' => $actx,
            'acsf' => $acsf,
            'actp' => $actp,
            'ac1' => $ac1,
            'ac4' => $ac4,
            'amount_initial' => $am,
            'amount_recurring' => $acam,
        ];
    }

    /**
     * 課金開始日 ac4（翌月1日を標準。申込日が1日なら翌月1日）
     */
    private function computeAc4(Carbon $desiredDate): string
    {
        $day = (int) $desiredDate->format('d');
        if ($day === 1) {
            return $desiredDate->copy()->addMonth()->format('Y/m/d');
        }
        return $desiredDate->copy()->addMonth()->startOfMonth()->format('Y/m/d');
    }

    /**
     * 自動課金初回決済（A/B）かどうか
     */
    public function isAutoBillingInitial(string $pattern): bool
    {
        return in_array($pattern, [self::PATTERN_MONTHLY_ONLY, self::PATTERN_INITIAL_PLUS_RECURRING], true);
    }

    /**
     * 通常決済（C）かどうか
     */
    public function isNormalPayment(string $pattern): bool
    {
        return $pattern === self::PATTERN_ONE_TIME_ONLY;
    }
}
