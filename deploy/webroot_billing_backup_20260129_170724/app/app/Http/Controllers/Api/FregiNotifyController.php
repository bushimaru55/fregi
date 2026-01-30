<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FregiPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FregiNotifyController extends Controller
{
    private FregiPaymentService $paymentService;

    public function __construct(FregiPaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * F-REGI通知受領（受付CGI）
     */
    public function handle(Request $request)
    {
        try {
            $params = $request->all();

            // 決済を処理（冪等）
            $payment = $this->paymentService->processNotify($params);

            // 成功レスポンスを返す（F-REGIの仕様に合わせる）
            return response('OK', 200);
        } catch (\Exception $e) {
            Log::error('F-REGI通知処理エラー', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // エラーレスポンス（F-REGIの仕様に合わせる）
            return response('ERROR', 500);
        }
    }
}
