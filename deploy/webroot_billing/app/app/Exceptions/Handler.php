<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Log;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            // グローバルなエラーハンドリング：契約・管理契約関連のルートの場合は詳細ログを記録
            if (request()->is('contract/*') || request()->is('admin/contracts/*')) {
                $this->logDetailedError($e);
            }
        });
    }

    /**
     * 詳細なエラーログを記録
     */
    protected function logDetailedError(Throwable $e): void
    {
        try {
            $request = request();
            
            Log::channel('contract_payment')->error('グローバル例外ハンドラ：エラー詳細', [
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'error_class' => get_class($e),
                'stack_trace' => $e->getTraceAsString(),
                'previous_exception' => $e->getPrevious() ? [
                    'message' => $e->getPrevious()->getMessage(),
                    'file' => $e->getPrevious()->getFile(),
                    'line' => $e->getPrevious()->getLine(),
                    'class' => get_class($e->getPrevious()),
                ] : null,
                'request_method' => $request->method(),
                'request_url' => $request->fullUrl(),
                'request_path' => $request->path(),
                'request_route' => $request->route() ? $request->route()->getName() : null,
                'request_headers' => [
                    'user-agent' => $request->userAgent(),
                    'referer' => $request->header('referer'),
                    'accept' => $request->header('accept'),
                    'content-type' => $request->header('content-type'),
                ],
                'request_query' => $request->query(),
                'session_id' => $request->hasSession() ? $request->session()->getId() : null,
                'session_keys' => $request->hasSession() ? array_keys($request->session()->all()) : [],
                'ip' => $request->ip(),
                'app_env' => config('app.env', 'unknown'),
                'app_debug' => config('app.debug', false),
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'memory_usage' => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true),
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Throwable $logError) {
            // ログ記録自体が失敗した場合は、最小限の情報のみ記録
            Log::error('エラーログ記録に失敗', [
                'original_error' => $e->getMessage(),
                'log_error' => $logError->getMessage(),
            ]);
        }
    }
}
