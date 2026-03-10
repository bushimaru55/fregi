<?php

namespace App\Http\Controllers;

use App\Services\RobotPayment\RobotPaymentNotifyService;
use App\Services\RobotPayment\RobotPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class RobotPaymentController extends Controller
{
    /**
     * 決済実行（トークン + セッションの申込データで契約作成 → gateway_token.aspx へサーバ間 POST）
     */
    public function execute(Request $request): RedirectResponse
    {
        $token = $request->input('tkn');
        $tokenCreatedMs = (int) $request->input('token_created_ms', 0);
        $sessionData = $request->session()->get('contract_confirm_data');
        $nowMs = (int) floor(microtime(true) * 1000);
        $tokenAgeMs = $tokenCreatedMs > 0 ? max(0, $nowMs - $tokenCreatedMs) : null;
        $tokenHash = is_string($token) && $token !== '' ? hash('sha256', $token) : '';
        $tokenHashPrefix = $tokenHash !== '' ? substr($tokenHash, 0, 12) : '';
        $dupCacheKey = $tokenHash !== '' ? 'rp_tkn_' . $tokenHash : '';
        $duplicateDetected = $dupCacheKey !== '' ? !Cache::add($dupCacheKey, 1, now()->addMinutes(15)) : false;

        $correlationId = 'pay_' . uniqid('', true);
        $debugContext = [
            'correlation_id' => $correlationId,
            'token_created_ms' => $tokenCreatedMs > 0 ? $tokenCreatedMs : null,
            'token_age_ms' => $tokenAgeMs,
            'token_hash_prefix' => $tokenHashPrefix,
            'duplicate_detected' => $duplicateDetected,
            'request_ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'received_at_ms' => $nowMs,
            'frontend_am' => $request->input('er584_am') !== null ? (int) $request->input('er584_am') : null,
            'frontend_tx' => $request->input('er584_tx') !== null ? (int) $request->input('er584_tx') : null,
            'frontend_sf' => $request->input('er584_sf') !== null ? (int) $request->input('er584_sf') : null,
            'frontend_use_zero_amount' => $request->input('er584_use_zero') !== null && $request->input('er584_use_zero') !== '' ? ($request->input('er584_use_zero') === '1') : null,
        ];

        Log::channel('contract_payment')->info('決済実行リクエスト受付', [
            'correlation_id' => $correlationId,
            'has_tkn' => !empty($token),
            'tkn_length' => is_string($token) ? strlen($token) : 0,
            'has_session_data' => !empty($sessionData),
            'contract_plan_id' => $sessionData['contract_plan_id'] ?? null,
        ]);
        // #region agent log
        Log::channel('contract_payment')->info('[DIAG][H7_H8] トークン再利用・期限チェック', [
            'token_hash_prefix' => $tokenHashPrefix,
            'duplicate_detected' => $duplicateDetected,
            'token_created_ms' => $tokenCreatedMs > 0 ? $tokenCreatedMs : null,
            'token_age_ms' => $tokenAgeMs,
        ]);
        // #endregion

        if (!$token || !$sessionData) {
            if (!$sessionData) {
                return redirect()->route('contract.create')->with('error', '申込内容が見つかりません。最初から入力し直してください。');
            }
            return redirect()->route('contract.payment')->with('payment_error', 'トークンが取得できませんでした。もう一度お試しください。');
        }

        try {
            $service = app(RobotPaymentService::class);
            $result = $service->executePayment($sessionData, $token, $debugContext);

            if ($result['success'] && $result['contract']) {
                Log::channel('contract_payment')->info('決済実行成功', [
                    'contract_id' => $result['contract']->id,
                ]);
                $request->session()->forget('contract_confirm_data');
                $request->session()->forget('payment_error');
                return redirect()->away(
                    URL::temporarySignedRoute('contract.complete', now()->addMinutes(60), ['contract' => $result['contract']->id])
                );
            }

            Log::channel('contract_payment')->warning('決済実行失敗', [
                'error' => $result['error'] ?? '決済処理に失敗しました。',
            ]);
            return redirect()->route('contract.payment')
                ->with('payment_error', $result['error'] ?? '決済処理に失敗しました。');
        } catch (\Throwable $e) {
            Log::channel('contract_payment')->error('ROBOT PAYMENT 決済実行エラー', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->route('contract.payment')
                ->with('payment_error', '決済処理中にエラーが発生しました。しばらくしてから再度お試しください。');
        }
    }

    /**
     * API 2 直接テスト（ER584 切り分け用。API 1 をスキップして API 2 だけ呼ぶ）
     * 本番では削除すること。
     */
    public function api2DirectTest(Request $request): RedirectResponse
    {
        $token = $request->input('tkn');
        $billingCode = $request->input('billing_code');
        $paymentMethodNumber = (int) $request->input('payment_method_number', 1);

        Log::channel('contract_payment')->info('[API2_DIRECT_TEST] リクエスト受付', [
            'billing_code' => $billingCode,
            'payment_method_number' => $paymentMethodNumber,
            'has_tkn' => !empty($token),
            'tkn_length' => is_string($token) ? strlen($token) : 0,
        ]);

        if (!$token || !$billingCode) {
            return redirect()->back()->with('payment_error', 'トークンまたは請求先コードが不足しています。');
        }

        $client = app(\App\Services\BillingRobo\BillingRoboApiClient::class);
        $path = 'api/v1.0/billing_payment_method/credit_card_token';
        $body = [
            'billing_code' => $billingCode,
            'billing_payment_method_number' => $paymentMethodNumber,
            'token' => $token,
        ];

        Log::channel('contract_payment')->info('[API2_DIRECT_TEST] API 2 送信', [
            'billing_code' => $billingCode,
            'payment_method_number' => $paymentMethodNumber,
            'token_len' => strlen($token),
            'token_preview' => strlen($token) > 8 ? substr($token, 0, 4) . '...' . substr($token, -4) : '(short)',
            'api_url' => rtrim(config('billing_robo.base_url', ''), '/') . '/' . $path,
        ]);

        try {
            $result = $client->post($path, $body, true);
        } catch (\Throwable $e) {
            Log::channel('contract_payment')->error('[API2_DIRECT_TEST] 接続失敗', ['message' => $e->getMessage()]);
            return redirect()->back()->with('api2_result', json_encode([
                'success' => false,
                'error' => '接続失敗: ' . $e->getMessage(),
            ]));
        }

        Log::channel('contract_payment')->info('[API2_DIRECT_TEST] API 2 レスポンス', [
            'http_status' => $result['status'],
            'body' => $result['body'],
            'error' => $result['error'],
        ]);

        $success = $result['error'] === null && $result['status'] < 400;
        return redirect()->back()->with('api2_result', json_encode([
            'success' => $success,
            'http_status' => $result['status'],
            'error' => $result['error'],
            'body' => $result['body'],
        ]));
    }

    /**
     * カード入力検証用ログ（桁数・下4桁・有効期限のみ。フル番号は受け取らない）
     */
    public function logCardHint(Request $request): Response
    {
        $validated = $request->validate([
            'card_length' => 'required|integer|min:12|max:19',
            'last4' => 'required|string|size:4|regex:/^\d{4}$/',
            'expiry_mm' => 'required|string|size:2|regex:/^\d{2}$/',
            'expiry_yy' => 'required|string|size:2|regex:/^\d{2}$/',
        ]);

        Log::channel('contract_payment')->info('カード入力情報（検証用）', [
            'card_length' => $validated['card_length'],
            'last4' => $validated['last4'],
            'expiry_mm' => $validated['expiry_mm'],
            'expiry_yy' => $validated['expiry_yy'],
        ]);

        return response('', 204);
    }

    /**
     * トークン作成失敗時のクライアント報告（ログ用）
     */
    public function logTokenCreateFailed(Request $request): Response
    {
        $errMsg = $request->input('err_msg', '');
        $pageOrigin = $request->input('page_origin', '');
        Log::channel('contract_payment')->warning('トークン作成失敗（クライアント）', [
            'err_msg' => mb_substr($errMsg, 0, 500),
            'page_origin' => $pageOrigin,
        ]);
        return response('', 204);
    }

    /**
     * 初回決済結果通知（決済結果通知URL）。GET キックバック。冪等。ContentLength 0 以上を返す。
     */
    public function notifyInitial(Request $request): Response
    {
        $rawQuery = $request->getQueryString() ?? '';
        $service = app(RobotPaymentNotifyService::class);
        $service->handleInitialNotify($rawQuery);
        return response('OK', 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    /**
     * 自動課金結果通知（自動課金結果通知URL）。GET キックバック。冪等。ContentLength 0 以上を返す。
     */
    public function notifyRecurring(Request $request): Response
    {
        $rawQuery = $request->getQueryString() ?? '';
        $service = app(RobotPaymentNotifyService::class);
        $service->handleRecurringNotify($rawQuery);
        return response('OK', 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
