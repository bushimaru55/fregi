<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contract extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_plan_id',
        'payment_id',
        'status',
        'company_name',
        'company_name_kana',
        'department',
        'position',
        'contact_name',
        'contact_name_kana',
        'email',
        'phone',
        'postal_code',
        'prefecture',
        'city',
        'address_line1',
        'address_line2',
        'desired_start_date',
        'actual_start_date',
        'end_date',
        'notes',
    ];

    protected $casts = [
        'desired_start_date' => 'date',
        'actual_start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * 契約プラン
     */
    public function contractPlan(): BelongsTo
    {
        return $this->belongsTo(ContractPlan::class, 'contract_plan_id');
    }

    /**
     * 決済情報
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }

    /**
     * 契約明細
     */
    public function contractItems(): HasMany
    {
        return $this->hasMany(ContractItem::class, 'contract_id');
    }

    /**
     * 完全な住所を取得
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->postal_code ? '〒' . $this->postal_code : null,
            $this->prefecture,
            $this->city,
            $this->address_line1,
            $this->address_line2,
        ]);
        
        return implode(' ', $parts);
    }

    /**
     * ステータスラベルを取得
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'draft' => '下書き',
            'pending_payment' => '決済待ち',
            'active' => '有効',
            'canceled' => 'キャンセル',
            'expired' => '期限切れ',
            default => '不明',
        };
    }
}
