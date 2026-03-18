<?php

namespace App\Logging;

use Illuminate\Support\Facades\Log;

/**
 * 決済フロー用のステージ付き構造化ログ。
 * 決済エラーが「どこで」発生したかをエラーログから詳細に特定するための共通ロガー。
 *
 * @see app/AIdocs/payment_error_logging_design.md
 */
final class PaymentStageLogger
{
    public const STAGE_EXEC_ENTRY = 'PAY_EXEC_ENTRY';
    public const STAGE_NO_SESSION = 'PAY_NO_SESSION';
    public const STAGE_NO_TOKEN = 'PAY_NO_TOKEN';
    public const STAGE_EXEC_SUCCESS = 'PAY_EXEC_SUCCESS';
    public const STAGE_EXEC_FAIL = 'PAY_EXEC_FAIL';
    public const STAGE_EXEC_EXCEPTION = 'PAY_EXEC_EXCEPTION';
    public const STAGE_SVC_ENTRY = 'PAY_SVC_ENTRY';
    public const STAGE_SVC_CONTRACT_CREATED = 'PAY_SVC_CONTRACT_CREATED';
    public const STAGE_SVC_BILLING_ROBO = 'PAY_SVC_BILLING_ROBO';
    public const STAGE_SVC_GATEWAY_SEND = 'PAY_SVC_GATEWAY_SEND';
    public const STAGE_SVC_GATEWAY_OK = 'PAY_SVC_GATEWAY_OK';
    public const STAGE_SVC_GATEWAY_FAIL = 'PAY_SVC_GATEWAY_FAIL';
    public const STAGE_API1_START = 'PAY_API1_START';
    public const STAGE_API1_OK = 'PAY_API1_OK';
    public const STAGE_API1_FAIL = 'PAY_API1_FAIL';
    public const STAGE_API2_START = 'PAY_API2_START';
    public const STAGE_API2_OK = 'PAY_API2_OK';
    public const STAGE_API2_FAIL = 'PAY_API2_FAIL';
    public const STAGE_API5_START = 'PAY_API5_START';
    public const STAGE_API5_OK = 'PAY_API5_OK';
    public const STAGE_API5_FAIL = 'PAY_API5_FAIL';
    public const STAGE_API3_START = 'PAY_API3_START';
    public const STAGE_API3_OK = 'PAY_API3_OK';
    public const STAGE_API3_FAIL = 'PAY_API3_FAIL';
    public const STAGE_CLIENT_TOKEN_FAIL = 'PAY_CLIENT_TOKEN_FAIL';
    public const STAGE_CLIENT_3DS_FAIL = 'PAY_CLIENT_3DS_FAIL';

    private const CHANNEL = 'contract_payment';

    /**
     * ステージ・相関ID付きで決済ログを出力する。
     *
     * @param  string  $stage  ステージコード（PAY_*）
     * @param  string  $message  短い説明（機密情報を含まないこと）
     * @param  array<string, mixed>  $context  追加コンテキスト（token, カード番号等は含めない）
     * @param  string|null  $correlationId  相関ID。無い場合は null
     * @param  'info'|'warning'|'error'  $level
     */
    public static function log(
        string $stage,
        string $message,
        array $context = [],
        ?string $correlationId = null,
        string $level = 'info'
    ): void {
        $prefix = '[PAY][' . $stage . '][' . ($correlationId ?? '-') . '] ';
        $fullMessage = $prefix . $message;
        $context['stage'] = $stage;
        if ($correlationId !== null) {
            $context['correlation_id'] = $correlationId;
        }

        $logger = Log::channel(self::CHANNEL);
        match ($level) {
            'warning' => $logger->warning($fullMessage, $context),
            'error' => $logger->error($fullMessage, $context),
            default => $logger->info($fullMessage, $context),
        };
    }

    public static function info(string $stage, string $message, array $context = [], ?string $correlationId = null): void
    {
        self::log($stage, $message, $context, $correlationId, 'info');
    }

    public static function warning(string $stage, string $message, array $context = [], ?string $correlationId = null): void
    {
        self::log($stage, $message, $context, $correlationId, 'warning');
    }

    public static function error(string $stage, string $message, array $context = [], ?string $correlationId = null): void
    {
        self::log($stage, $message, $context, $correlationId, 'error');
    }
}
