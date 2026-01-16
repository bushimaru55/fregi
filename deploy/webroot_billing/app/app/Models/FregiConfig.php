<?php

namespace App\Models;

use App\Services\EncryptionService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FregiConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'environment',
        'shopid', // F-REGI標準: SHOPID
        'connect_password_enc',
        'notify_url',
        'return_url_success',
        'return_url_cancel',
        'is_active',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * 接続パスワード（アクセサ：自動復号）
     * 注意: このアクセサは平文を返すため、ログ出力時などは使用を避けること
     */
    protected function connectPassword(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                if (empty($attributes['connect_password_enc'])) {
                    return null;
                }
                
                try {
                    $encryptionService = app(EncryptionService::class);
                    return $encryptionService->decryptSecret($attributes['connect_password_enc']);
                } catch (\Exception $e) {
                    // ログに秘密情報を含めないよう注意
                    \Log::error('Failed to decrypt connect_password', [
                        'config_id' => $attributes['id'] ?? null,
                        'error' => $e->getMessage(),
                    ]);
                    throw $e;
                }
            },
            set: function ($value) {
                if (empty($value)) {
                    return ['connect_password_enc' => null];
                }
                
                try {
                    $encryptionService = app(EncryptionService::class);
                    return ['connect_password_enc' => $encryptionService->encryptSecret($value)];
                } catch (\Exception $e) {
                    // ログに秘密情報を含めないよう注意
                    \Log::error('Failed to encrypt connect_password', [
                        'error' => $e->getMessage(),
                    ]);
                    throw $e;
                }
            },
        );
    }

    /**
     * 変更履歴
     */
    public function versions(): HasMany
    {
        return $this->hasMany(FregiConfigVersion::class, 'config_id');
    }
}
