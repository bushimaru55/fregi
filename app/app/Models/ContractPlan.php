<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContractPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'item', // F-REGI標準: ITEM（商品コード）
        'name',
        'price',
        'billing_type', // 決済タイプ（one_time: 一回限り, monthly: 月額課金, yearly: 年額課金）
        'payment_collection_method', // 回収方法（card: クレジット, bank_transfer: 請求書払い）
        'description',
        'is_active',
        'display_order',
    ];

    /** 回収方法: クレジットカード */
    public const PAYMENT_CARD = 'card';

    /** 回収方法: 請求書払い（銀行振込） */
    public const PAYMENT_BANK_TRANSFER = 'bank_transfer';

    protected $casts = [
        'price' => 'integer',
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    /**
     * この製品に紐づく契約
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class, 'contract_plan_id');
    }

    /**
     * 有効なプランのみを取得するスコープ
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('display_order');
    }

    /**
     * 料金を税込表示用にフォーマット
     */
    public function getFormattedPriceAttribute(): string
    {
        $price = number_format($this->price) . '円';
        if ($this->billing_type === 'monthly') {
            $price .= '/月';
        } elseif ($this->billing_type === 'yearly') {
            $price .= '/年';
        }
        return $price;
    }

    /**
     * 決済タイプのラベルを取得
     */
    public function getBillingTypeLabelAttribute(): string
    {
        return match($this->billing_type) {
            'one_time' => '一回限り',
            'monthly' => '月額課金',
            'yearly' => '年額課金',
            default => '不明',
        };
    }

    /**
     * 請求書払い（銀行振込）かどうか
     */
    public function usesBankTransfer(): bool
    {
        return ($this->payment_collection_method ?? self::PAYMENT_CARD) === self::PAYMENT_BANK_TRANSFER;
    }

    /**
     * 回収方法のラベルを取得
     */
    public function getPaymentMethodLabelAttribute(): string
    {
        return $this->usesBankTransfer() ? '請求書払い' : 'クレジット';
    }

    /**
     * 管理フォーム用の複合セレクション値（billing_type × 回収方法）。
     * 例: monthly + bank_transfer → 'monthly_invoice'
     */
    public function getBillingSelectionAttribute(): string
    {
        $billingType = $this->billing_type ?? 'one_time';
        if ($billingType === 'one_time') {
            return 'one_time';
        }
        return $this->usesBankTransfer() ? $billingType . '_invoice' : $billingType;
    }

    /**
     * 管理フォームの複合セレクション値を billing_type / payment_collection_method に分解する。
     *
     * @return array{billing_type: string, payment_collection_method: string}
     */
    public static function splitBillingSelection(string $selection): array
    {
        return match ($selection) {
            'monthly' => ['billing_type' => 'monthly', 'payment_collection_method' => self::PAYMENT_CARD],
            'yearly' => ['billing_type' => 'yearly', 'payment_collection_method' => self::PAYMENT_CARD],
            'monthly_invoice' => ['billing_type' => 'monthly', 'payment_collection_method' => self::PAYMENT_BANK_TRANSFER],
            'yearly_invoice' => ['billing_type' => 'yearly', 'payment_collection_method' => self::PAYMENT_BANK_TRANSFER],
            default => ['billing_type' => 'one_time', 'payment_collection_method' => self::PAYMENT_CARD],
        };
    }

    /**
     * 一回限りの決済プランのみを取得するスコープ
     */
    public function scopeOneTime($query)
    {
        return $query->where('billing_type', 'one_time');
    }

    /**
     * 月額課金プランのみを取得するスコープ
     */
    public function scopeMonthly($query)
    {
        return $query->where('billing_type', 'monthly');
    }

    /**
     * 年額課金プランのみを取得するスコープ
     */
    public function scopeYearly($query)
    {
        return $query->where('billing_type', 'yearly');
    }

    /**
     * このベース商品に紐づくオプション商品
     */
    public function optionProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'contract_plan_option_products', 'contract_plan_id', 'product_id')
            ->where('products.type', 'option')
            ->where('products.is_active', true)
            ->orderBy('products.display_order')
            ->withTimestamps();
    }
}
