<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContractStatus extends Model
{
    protected $fillable = [
        'code',
        'name',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * このステータスを持つ契約
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class, 'status', 'code');
    }
}
