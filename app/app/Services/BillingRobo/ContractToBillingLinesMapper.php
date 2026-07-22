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

    /** 繰返し周期: 月額（毎月） */
    public const REPETITION_PERIOD_MONTHLY = 1;

    /** 繰返し周期: 年額（12ヶ月ごと。請求管理ロボは周期単位が「月」のみのため月数で表現） */
    public const REPETITION_PERIOD_YEARLY = 12;

    /**
     * 契約の明細から請求用の行配列を返す。
     * demand_type が定期定額(1)の場合、repetition_period_number に繰返し周期（月数）を格納する。
     * 単発(0)の場合は repetition_period_number は null。
     *
     * @return array<int, array{goods_name: string, price: int, quantity: int, tax_category: int, tax: int, demand_type: int, repetition_period_number: int|null}>
     */
    public function map(Contract $contract): array
    {
        $items = $contract->contractItems()->with('product')->orderBy('id')->get();
        if ($items->isEmpty()) {
            return [];
        }

        $lines = [];
        foreach ($items as $item) {
            $billingType = strtolower($item->billing_type ?? 'one_time');
            $demandType = self::DEMAND_TYPE_ONE_TIME;
            $repetitionPeriodNumber = null;
            if ($billingType === 'monthly') {
                $demandType = self::DEMAND_TYPE_RECURRING;
                $repetitionPeriodNumber = self::REPETITION_PERIOD_MONTHLY;
            } elseif ($billingType === 'yearly') {
                $demandType = self::DEMAND_TYPE_RECURRING;
                $repetitionPeriodNumber = self::REPETITION_PERIOD_YEARLY;
            }
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
                'repetition_period_number' => $repetitionPeriodNumber,
            ];
        }
        return $lines;
    }
}
