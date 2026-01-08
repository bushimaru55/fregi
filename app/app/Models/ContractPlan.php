<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContractPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_plan_master_id',
        'item', // F-REGI標準: ITEM（商品コード）
        'name',
        'price',
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
        return number_format($this->price) . '円';
    }
}
