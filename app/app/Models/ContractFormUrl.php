<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ContractFormUrl extends Model
{
    use HasFactory;

    protected $fillable = [
        'token',
        'url',
        'plan_ids',
        'name',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'plan_ids' => 'array',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * 有効なURLのみ取得
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('expires_at', '>', now());
    }

    /**
     * 有効期限切れかどうか
     */
    public function isExpired(): bool
    {
        return $this->expires_at < now();
    }

    /**
     * 有効かどうか（有効フラグと有効期限を確認）
     * トークンがない場合（申込フォームURL）は常に有効とみなす
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }
        
        // トークンがない場合は申込フォームURLなので常に有効
        if (!$this->token) {
            return true;
        }
        
        // トークンがある場合は有効期限をチェック
        return !$this->isExpired();
    }
}
