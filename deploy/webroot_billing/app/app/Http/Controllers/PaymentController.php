<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\FregiPaymentService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    private FregiPaymentService $paymentService;

    public function __construct(FregiPaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * 決済開始
     */
    public function initiate(Request $request, Payment $payment)
    {
        try {
            // F-REGIへのPOSTパラメータを生成
            $params = $this->paymentService->initiatePayment($payment);

            // ステータスを更新
            $payment->status = 'redirect_issued';
            $payment->save();

            // F-REGIのエンドポイントURL（実際のURLに置き換える）
            $fregiEndpoint = env('FREGI_ENDPOINT_URL', 'https://example.f-regi.jp/payment');

            return view('payment.redirect', [
                'endpoint' => $fregiEndpoint,
                'params' => $params,
            ]);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => '決済開始に失敗しました: ' . $e->getMessage()]);
        }
    }
}
