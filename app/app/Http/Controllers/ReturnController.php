<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;

class ReturnController extends Controller
{
    /**
     * 成功時の戻りURL
     */
    public function success(Request $request)
    {
        $orderId = $request->input('ORDERID');
        
        if (!$orderId) {
            return view('return.error', ['message' => 'オーダー番号が指定されていません。']);
        }

        // 決済を取得（company_idの特定が必要。実際の実装では適切に取得）
        $companyId = $request->input('COMPANY_ID', 1); // 実際の実装では適切に取得
        $payment = Payment::where('company_id', $companyId)
            ->where('orderid', $orderId)
            ->first();

        if (!$payment) {
            return view('return.error', ['message' => '決済情報が見つかりません。']);
        }

        return view('return.success', compact('payment'));
    }

    /**
     * キャンセル時の戻りURL
     */
    public function cancel(Request $request)
    {
        $orderId = $request->input('ORDERID');
        
        if (!$orderId) {
            return view('return.error', ['message' => 'オーダー番号が指定されていません。']);
        }

        $companyId = $request->input('COMPANY_ID', 1);
        $payment = Payment::where('company_id', $companyId)
            ->where('orderid', $orderId)
            ->first();

        if (!$payment) {
            return view('return.error', ['message' => '決済情報が見つかりません。']);
        }

        return view('return.cancel', compact('payment'));
    }

    /**
     * 失敗時の戻りURL
     */
    public function failure(Request $request)
    {
        $orderId = $request->input('ORDERID');
        
        if (!$orderId) {
            return view('return.error', ['message' => 'オーダー番号が指定されていません。']);
        }

        $companyId = $request->input('COMPANY_ID', 1);
        $payment = Payment::where('company_id', $companyId)
            ->where('orderid', $orderId)
            ->first();

        if (!$payment) {
            return view('return.error', ['message' => '決済情報が見つかりません。']);
        }

        return view('return.failure', compact('payment'));
    }
}
