<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'unit_price',
        'type',
        'billing_type',
        'is_active',
        'display_order',
    ];

    protected $attributes = [
        'billing_type' => 'one_time',
    ];

    protected $casts = [
        'unit_price' => 'integer',
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    /**
     * 契約明細
     */
    public function contractItems(): HasMany
    {
        return $this->hasMany(ContractItem::class, 'product_id');
    }

    /**
     * このオプション商品が紐づくベース商品（契約プラン）
     */
    public function contractPlans(): BelongsToMany
    {
        return $this->belongsToMany(ContractPlan::class, 'contract_plan_option_products', 'product_id', 'contract_plan_id')
            ->withTimestamps();
    }

    /**
     * オプション商品のみを取得するスコープ
     */
    public function scopeOptions($query)
    {
        return $query->where('type', 'option')
            ->where('is_active', true)
            ->orderBy('display_order');
    }

    /**
     * 有効な商品のみを取得するスコープ
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * 価格を税込表示用にフォーマット
     * オプション製品で月額課金の場合は「/月額」を付与
     */
    public function getFormattedPriceAttribute(): string
    {
        $price = number_format($this->unit_price) . '円';
        if ($this->type === 'option' && ($this->billing_type ?? 'one_time') === 'monthly') {
            $price .= '/月額';
        }
        return $price;
    }

    /**
     * 商品種別のラベルを取得
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'plan' => 'プラン',
            'option' => 'オプション',
            'addon' => '追加商品',
            default => '不明',
        };
    }

    /**
     * 決済タイプのラベルを取得（オプション製品の1回限り・月額課金表示用）
     */
    public function getBillingTypeLabelAttribute(): string
    {
        return match($this->billing_type ?? 'one_time') {
            'monthly' => '月額課金',
            'one_time' => '一回限り',
            default => '一回限り',
        };
    }

    /**
     * オプション製品かどうか
     */
    public function isOption(): bool
    {
        return $this->type === 'option';
    }
}