<?php

namespace App\Services\BillingRobo;

use App\Models\Contract;

/**
 * 契約から Billing-Robo 用の請求明細行を組み立てる共通マッパー。
 * API3 の demand 行と API5 の bill_detail の両方で利用する。
 * 商品マスタは使わず、ContractItem を正本とする。
 */
class ContractToBillingLinesMapper
{
    /** 請求タイプ: 単発 */
    public const DEMAND_TYPE_ONE_TIME = 0;

    /** 請求タイプ: 定期定額 */
    public const DEMAND_TYPE_RECURRING = 1;

    /**
     * 契約の明細から請求用の行配列を返す。
     *
     * @return array<int, array{goods_name: string, price: int, quantity: int, tax_category: int, tax: int, demand_type: int}>
     */
    public function map(Contract $contract): array
    {
        $items = $contract->contractItems()->with('product')->orderBy('id')->get();
        if ($items->isEmpty()) {
            return [];
        }

        $lines = [];
        foreach ($items as $item) {
            $demandType = strtolower($item->billing_type ?? 'one_time') === 'monthly'
                ? self::DEMAND_TYPE_RECURRING
                : self::DEMAND_TYPE_ONE_TIME;
            $taxCategory = 1;
            $tax = 10;
            if ($item->product_id && $item->product) {
                $taxCategory = (int) ($item->product->tax_category ?? 1);
                $tax = (int) ($item->product->tax ?? 10);
            }
            $lines[] = [
                'goods_name' => $item->product_name ?? '商品',
                'price' => (int) $item->unit_price,
                'quantity' => (int) max(1, $item->quantity),
                'tax_category' => $taxCategory,
                'tax' => $tax,
                'demand_type' => $demandType,
            ];
        }
        return $lines;
    }
}
