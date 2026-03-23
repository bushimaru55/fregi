<?php

namespace App\Exceptions;

use Throwable;

/**
 * 請求管理ロボ API 呼び出し時の例外（接続失敗・タイムアウト、または呼び出し元で 4xx/5xx を例外扱いにする場合に使用）
 */
class BillingRoboApiException extends \Exception
{
    public function __construct(
        string $message = '',
        int $statusCode = 0,
        private ?array $errorBody = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode, $previous);
    }

    /** HTTP ステータスコード（接続失敗時は 0） */
    public function getStatusCode(): int
    {
        return (int) $this->code;
    }

    /** レスポンスの error 部分（code / message）。ない場合は null */
    public function getErrorBody(): ?array
    {
        return $this->errorBody;
    }

    /** エラーコード（共通: 11=ログインID不正, 12=アクセスキー不正, 13=接続IP不正 等） */
    public function getErrorCode(): int|string|null
    {
        return $this->errorBody['code'] ?? null;
    }

    /** エラーメッセージ */
    public function getErrorMessage(): ?string
    {
        return $this->errorBody['message'] ?? null;
    }
}
