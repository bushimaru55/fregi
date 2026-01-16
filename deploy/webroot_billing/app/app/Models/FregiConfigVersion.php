<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FregiConfigVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'config_id',
        'version_no',
        'snapshot_json',
        'changed_at',
        'changed_by',
        'change_reason',
    ];

    protected $casts = [
        'snapshot_json' => 'array',
        'changed_at' => 'datetime',
    ];

    /**
     * F-REGI設定
     */
    public function config(): BelongsTo
    {
        return $this->belongsTo(FregiConfig::class, 'config_id');
    }
}
