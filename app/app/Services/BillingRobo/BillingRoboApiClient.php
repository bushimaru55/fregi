<?php

namespace App\Services\BillingRobo;

use App\Exceptions\BillingRoboApiException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * 請求管理ロボ API 共通クライアント（POST・JSON・認証付与・タイムアウト・エラーパース）
 * 参照: AIdocs/api_documents/00_billing_robo_api_overview.md
 */
class BillingRoboApiClient
{
    private const DEFAULT_TIMEOUT = 15;

    public function __construct(
        private string $baseUrl,
        private string $userId,
        private string $accessKey,
        private int $timeout = self::DEFAULT_TIMEOUT
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    /**
     * POST で API を呼び出す。認証パラメータは body に自動付与する。
     *
     * @param  string  $path  ベースURL相対のパス（例: /api/v1.0/billing_individual/search）。v1.0 なしの場合は $useV1 = false
     * @param  array<string, mixed>  $body  リクエスト body（user_id / access_key は上書き付与）
     * @param  bool  $useV1  true のとき path が /api/ 始まりでなければ /api/v1.0 を付与しない（path をそのまま使用）
     * @return array{status: int, body: array|null, error: array{code: int|string, message: string}|null}
     * @throws BillingRoboApiException 接続失敗・タイムアウト時
     */
    public function post(string $path, array $body, bool $useV1 = true): array
    {
        $body['user_id'] = $this->userId;
        $body['access_key'] = $this->accessKey;

        $path = ltrim($path, '/');
        if ($useV1 && !str_starts_with($path, 'api/')) {
            $path = 'api/v1.0/' . $path;
        }
        $url = $this->baseUrl . '/' . $path;

        try {
            $response = Http::timeout($this->timeout)
                ->acceptJson()
                ->asJson()
                ->post($url, $body);
        } catch (\Throwable $e) {
            Log::warning('BillingRobo API 接続失敗', [
                'url' => $url,
                'message' => $e->getMessage(),
            ]);
            throw new BillingRoboApiException(
                '請求管理ロボ API への接続に失敗しました: ' . $e->getMessage(),
                0,
                null,
                $e
            );
        }

        $status = $response->status();
        $decoded = $response->json();
        $error = $this->parseError($decoded);

        return [
            'status' => $status,
            'body' => is_array($decoded) ? $decoded : null,
            'error' => $error,
        ];
    }

    /**
     * レスポンス body から error オブジェクトをパースする。
     *
     * @param  mixed  $body
     * @return array{code: int|string, message: string}|null
     */
    private function parseError(mixed $body): ?array
    {
        if (!is_array($body) || !isset($body['error']) || !is_array($body['error'])) {
            return null;
        }
        $err = $body['error'];
        $code = $err['code'] ?? 0;
        $message = $err['message'] ?? '';

        return [
            'code' => $code,
            'message' => (string) $message,
        ];
    }

    /**
     * config からクライアントを生成する。
     */
    public static function fromConfig(): self
    {
        $baseUrl = config('billing_robo.base_url', '');
        $userId = config('billing_robo.user_id', '');
        $accessKey = config('billing_robo.access_key', '');

        return new self($baseUrl, $userId, $accessKey);
    }
}
