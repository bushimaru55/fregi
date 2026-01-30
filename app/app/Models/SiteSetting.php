<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SiteSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'value_text',
        'description',
    ];

    /**
     * キーで設定値を取得（HTML版）
     */
    public static function getValue(string $key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * キーで設定値を取得（プレーンテキスト版）
     */
    public static function getTextValue(string $key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        return $setting ? ($setting->value_text ?? $setting->value) : $default;
    }

    /**
     * 申込通知メール送信先を配列で取得（複数対応）
     * 改行・カンマ区切りで保存された文字列をパースし、有効なメールアドレスのみ返す
     *
     * @return array<int, string>
     */
    public static function getNotificationEmailsArray(): array
    {
        $raw = self::getTextValue('notification_email', '');
        if ($raw === '' || $raw === null) {
            return [];
        }
        $parts = preg_split('/[\r\n,]+/', (string) $raw) ?: [];
        $emails = array_values(array_filter(array_map('trim', $parts)));
        return array_values(array_filter($emails, fn (string $e) => filter_var($e, FILTER_VALIDATE_EMAIL) !== false));
    }

    /**
     * キーで設定値を設定（HTML＋テキスト両方保存）
     * 
     * @param string $key 設定キー
     * @param string $html サニタイズ済みHTML
     * @param string|null $description 設定の説明
     * @return SiteSetting
     */
    public static function setValue(string $key, string $html, string $description = null)
    {
        // HTMLからプレーンテキストを生成
        $text = Str::squish(strip_tags($html));

        return self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $html,
                'value_text' => $text,
                'description' => $description,
            ]
        );
    }

    /**
     * キーで設定値を設定（旧互換用：テキストのみ）
     */
    public static function setTextValue(string $key, string $text, string $description = null)
    {
        return self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $text,
                'value_text' => $text,
                'description' => $description,
            ]
        );
    }
}
