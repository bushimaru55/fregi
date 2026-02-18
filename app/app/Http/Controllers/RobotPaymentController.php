<?php

namespace App\Http\Controllers;

use App\Services\RobotPayment\RobotPaymentNotifyService;
use App\Services\RobotPayment\RobotPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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
        $sessionData = $request->session()->get('contract_confirm_data');

        Log::channel('contract_payment')->info('決済実行リクエスト受付', [
            'has_tkn' => !empty($token),
            'tkn_length' => is_string($token) ? strlen($token) : 0,
            'has_session_data' => !empty($sessionData),
            'contract_plan_id' => $sessionData['contract_plan_id'] ?? null,
        ]);

        if (!$token || !$sessionData) {
            if (!$sessionData) {
                return redirect()->route('contract.create')->with('error', '申込内容が見つかりません。最初から入力し直してください。');
            }
            return redirect()->route('contract.payment')->with('payment_error', 'トークンが取得できませんでした。もう一度お試しください。');
        }

        try {
            $service = app(RobotPaymentService::class);
            $result = $service->executePayment($sessionData, $token);

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
        Log::channel('contract_payment')->warning('トークン作成失敗（クライアント）', [
            'err_msg' => mb_substr($errMsg, 0, 500),
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
