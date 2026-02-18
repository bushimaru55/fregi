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
        'provider',
        'payment_kind',
        'merchant_order_no',
        'rp_gid',
        'rp_acid',
        'orderid',
        'settleno',
        'receiptno',
        'slipno',
        'amount',
        'amount_initial',
        'amount_recurring',
        'currency',
        'payment_method',
        'status',
        'requested_at',
        'notified_at',
        'completed_at',
        'paid_at',
        'failure_reason',
        'raw_notify_payload',
    ];

    protected $casts = [
        'amount' => 'integer',
        'amount_initial' => 'integer',
        'amount_recurring' => 'integer',
        'requested_at' => 'datetime',
        'notified_at' => 'datetime',
        'completed_at' => 'datetime',
        'paid_at' => 'datetime',
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
