<?php

namespace App\Services\BillingRobo;

/**
 * 請求管理ロボ API 共通エラーコードのマッピング（00_billing_robo_api_overview.md 準拠）
 */
class BillingRoboErrorMap
{
    /** 共通エラーコード → 説明（ログ・ユーザー向けメッセージ用） */
    public const COMMON_CODES = [
        1 => '内部エラー',
        10 => '不明なURI',
        11 => 'ログインIDが不正',
        12 => 'アクセスキーが不正',
        13 => '接続IPが不正',
        14 => '店舗IDが不正',
        16 => 'ログイン失敗',
        17 => '権限が不正',
        18 => '利用企業が不正',
        19 => 'メンテナンス中',
        20 => 'リクエスト数が不正',
        21 => '廃止されたAPI',
    ];

    /** HTTP ステータス別の扱い（リトライ可否の目安） */
    public const HTTP_RETRYABLE = [
        429 => false, // Too Many Requests: リトライは間隔を空ける
        503 => true,  // Service Unavailable: メンテ後リトライ可
        413 => false, // Request Entity Too Large: リクライ不可
        401 => false, // Unauthorized: 設定修正が必要
        400 => false, // Bad Request: リクエスト修正が必要
    ];

    public static function getMessage(int|string $code): string
    {
        $code = is_numeric($code) ? (int) $code : $code;
        return self::COMMON_CODES[$code] ?? "エラーコード: {$code}";
    }

    public static function isRetryableByHttpStatus(int $status): bool
    {
        return self::HTTP_RETRYABLE[$status] ?? false;
    }
}
