<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingRoboDemand extends Model
{
    use HasFactory;

    protected $table = 'billing_robo_demands';

    protected $fillable = [
        'contract_id',
        'demand_number',
        'demand_code',
        'demand_type',
    ];

    protected $casts = [
        'demand_number' => 'integer',
    ];

    /**
     * 契約
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }
}
