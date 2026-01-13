<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'contract_id',
        'orderid', // F-REGI標準: ORDERID（伝票番号）
        'settleno', // F-REGI標準: SETTLENO（発行番号）
        'receiptno', // F-REGI標準: RECEIPTNO（承認番号）
        'slipno', // F-REGI標準: SLIPNO（取引番号）
        'amount',
        'currency',
        'payment_method',
        'status',
        'requested_at',
        'notified_at',
        'completed_at',
        'failure_reason',
        'raw_notify_payload',
    ];

    protected $casts = [
        'amount' => 'integer',
        'requested_at' => 'datetime',
        'notified_at' => 'datetime',
        'completed_at' => 'datetime',
        'raw_notify_payload' => 'array',
    ];

    /**
     * 決済イベント
     */
    public function events(): HasMany
    {
        return $this->hasMany(PaymentEvent::class, 'payment_id');
    }

    /**
     * 契約情報
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }
}
