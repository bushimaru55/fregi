<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ContractFormUrl extends Model
{
    use HasFactory;

    const JOB_TYPE_CAPTURE = 'CAPTURE';
    const JOB_TYPE_AUTH    = 'AUTH';

    protected $fillable = [
        'token',
        'url',
        'plan_ids',
        'name',
        'expires_at',
        'is_active',
        'job_type',
    ];

    protected $casts = [
        'plan_ids' => 'array',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * フォームURLに設定された job_type を返す。
     * 未設定（null）の場合はサイト設定（config）を使用する。
     */
    public function resolvedJobType(): string
    {
        if ($this->job_type) {
            return strtoupper($this->job_type);
        }
        return strtoupper(config('robotpayment.job_type', 'CAPTURE'));
    }

    /**
     * 管理画面一覧用: 現在の決済方法の主ラベル（日本語）
     */
    public function jobTypeListPrimaryLabel(): string
    {
        return match (strtoupper((string) $this->job_type)) {
            'AUTH' => '仮売上のみ',
            'CAPTURE' => '仮実同時売上',
            default => 'フォーム未指定',
        };
    }

    /**
     * 管理画面一覧用: 補足（コード・適用元・実際に効く値）
     */
    public function jobTypeListSecondaryLine(): string
    {
        $resolved = $this->resolvedJobType();
        $resolvedJa = $resolved === 'AUTH' ? '仮売上' : '仮実同時';

        if ($this->job_type) {
            return strtoupper($this->job_type) . ' · このフォームで固定';
        }

        return '実際の適用: ' . $resolvedJa . '（' . $resolved . '）· サイト全体設定';
    }

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
