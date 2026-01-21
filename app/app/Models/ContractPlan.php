<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContractPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_plan_master_id',
        'item', // F-REGI標準: ITEM（商品コード）
        'name',
        'price',
        'billing_type', // 決済タイプ（one_time: 一回限り, monthly: 月額課金）
        'description',
        'is_active',
        'display_order',
    ];

    protected $casts = [
        'price' => 'integer',
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    /**
     * このプランに紐づく契約プランマスター
     */
    public function contractPlanMaster(): BelongsTo
    {
        return $this->belongsTo(ContractPlanMaster::class, 'contract_plan_master_id');
    }

    /**
     * このプランに紐づく契約
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
            default => '不明',
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
