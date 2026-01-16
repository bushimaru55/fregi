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
