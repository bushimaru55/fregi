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
        'customer_id',
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
        'usage_url_domain',
        'import_from_trial',
        'desired_start_date',
        'actual_start_date',
        'end_date',
        'notes',
        'card_last4',
    ];

    protected $casts = [
        'desired_start_date' => 'date',
        'actual_start_date' => 'date',
        'end_date' => 'date',
        'import_from_trial' => 'boolean',
    ];

    /**
     * 契約プラン
     */
    public function contractPlan(): BelongsTo
    {
        return $this->belongsTo(ContractPlan::class, 'contract_plan_id');
    }

    /**
     * 決済情報（主決済 - 後方互換性のため維持）
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }

    /**
     * 決済情報（複数決済に対応）
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'contract_id');
    }

    /**
     * 契約明細
     */
    public function contractItems(): HasMany
    {
        return $this->hasMany(ContractItem::class, 'contract_id');
    }

    /**
     * 契約ステータス（マスター。contracts.status は code を保持）
     */
    public function contractStatus(): BelongsTo
    {
        return $this->belongsTo(ContractStatus::class, 'status', 'code');
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
     * CUSTOMERIDを生成
     * 形式: CUST + 契約ID（パディング）+ タイムスタンプ
     * 最大20文字以内
     */
    public function generateCustomerId(): string
    {
        $contractId = str_pad((string)$this->id, 6, '0', STR_PAD_LEFT); // 6桁
        $timestamp = now()->format('YmdHis'); // 14文字
        $customerId = 'CUST' . $contractId . $timestamp; // 4 + 6 + 14 = 24文字
        
        // 20文字を超える場合は末尾を切り詰める
        if (strlen($customerId) > 20) {
            $customerId = substr($customerId, 0, 20);
        }
        
        return $customerId;
    }

    /**
     * ステータスラベルを取得（マスター参照。マスターにない code は「不明」）
     */
    public function getStatusLabelAttribute(): string
    {
        return $this->contractStatus?->name ?? '不明';
    }
}
