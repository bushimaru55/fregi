<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SiteSetting extends Model
{
    use HasFactory;

    /** 旧単一キー（フォールバック用） */
    public const TERMS_LEGACY_KEY = 'terms_of_service';

    /** 決済タイプ別キーのプレフィックス */
    public const TERMS_KEY_PREFIX = 'terms_of_service_';

    /**
     * 決済タイプ（billing_selection）の一覧と日本語ラベル。
     * ContractPlan の複合セレクト値と一致させる。
     *
     * @return array<string, string>
     */
    public static function billingSelectionLabels(): array
    {
        return [
            'one_time' => '一回限り',
            'monthly' => '月額課金（クレジット）',
            'yearly' => '年額課金（クレジット）',
            'monthly_invoice' => '月額課金（請求書払い）',
            'yearly_invoice' => '年額課金（請求書払い）',
        ];
    }

    /**
     * 決済タイプに対応する site_settings.key を返す。
     */
    public static function termsKeyFor(string $billingSelection): string
    {
        $labels = self::billingSelectionLabels();
        if (!array_key_exists($billingSelection, $labels)) {
            $billingSelection = 'one_time';
        }

        return self::TERMS_KEY_PREFIX . $billingSelection;
    }

    /**
     * 決済タイプ別の利用規約HTMLを取得する。
     * 該当キーが空の場合は旧 terms_of_service にフォールバックする。
     */
    public static function getTermsOfService(string $billingSelection = 'one_time'): string
    {
        $html = self::getValue(self::termsKeyFor($billingSelection), '');
        if ($html !== null && $html !== '') {
            return $html;
        }

        return (string) self::getValue(self::TERMS_LEGACY_KEY, '');
    }

    /**
     * 決済タイプ別の利用規約をすべて取得する。
     *
     * @return array<string, string>
     */
    public static function getAllTermsOfService(): array
    {
        $result = [];
        foreach (array_keys(self::billingSelectionLabels()) as $selection) {
            $result[$selection] = self::getTermsOfService($selection);
        }

        return $result;
    }

    /**
     * 決済タイプ別の利用規約を保存する。
     */
    public static function setTermsOfService(string $billingSelection, string $html, ?string $description = null): self
    {
        $labels = self::billingSelectionLabels();
        if (!array_key_exists($billingSelection, $labels)) {
            $billingSelection = 'one_time';
        }

        $label = $labels[$billingSelection];
        $description = $description ?? "利用規約の本文（{$label}）";

        return self::setValue(self::termsKeyFor($billingSelection), $html, $description);
    }

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
