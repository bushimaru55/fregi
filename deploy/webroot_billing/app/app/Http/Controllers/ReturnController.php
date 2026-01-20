<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Payment;
use App\Services\EncryptionService;
use App\Services\FregiConfigService;
use App\Services\FregiPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReturnController extends Controller
{
    protected FregiConfigService $configService;
    protected FregiPaymentService $paymentService;
    protected EncryptionService $encryptionService;

    public function __construct(
        FregiConfigService $configService,
        FregiPaymentService $paymentService,
        EncryptionService $encryptionService
    ) {
        $this->configService = $configService;
        $this->paymentService = $paymentService;
        $this->encryptionService = $encryptionService;
    }

    /**
     * カード決済完了時の戻りURL
     * F-REGI仕様: STATUS, SETTLENO, ID, AUTHCODE, SEQNO, FREE, UA, CHECKSUM
     */
    public function handle(Request $request)
    {
        try {
            // パラメータを取得（F-REGI仕様: 大文字）
            $status = $request->input('STATUS'); // OK/NG/CANCEL
            $settleno = $request->input('SETTLENO'); // 発行番号
            $id = $request->input('ID'); // 伝票番号（orderid）
            $authcode = $request->input('AUTHCODE', ''); // 承認番号
            $seqno = $request->input('SEQNO', ''); // 取引番号
            $free = $request->input('FREE', ''); // 自由項目
            $ua = $request->input('UA', ''); // 使用端末識別フラグ
            $checksum = $request->input('CHECKSUM'); // チェックサム

            // 必須パラメータの確認
            if (!$status || !$settleno || !$id || !$checksum) {
                Log::warning('F-REGI戻りURL: 必須パラメータが不足', $request->all());
                return view('return.error', ['message' => '必須パラメータが不足しています。']);
            }

            // 決済を取得（company_idは仮で1、実際の実装では適切に取得）
            $companyId = 1;
            $payment = Payment::where('company_id', $companyId)
                ->where(function($query) use ($id, $settleno) {
                    $query->where('orderid', $id)
                          ->orWhere('settleno', $settleno);
                })
                ->first();

            if (!$payment) {
                Log::warning('F-REGI戻りURL: 決済情報が見つからない', [
                    'id' => $id,
                    'settleno' => $settleno,
                ]);
                return view('return.error', ['message' => '決済情報が見つかりません。']);
            }

            // F-REGI設定を取得
            // FREGI_ENVを使用して設定を検索（APP_ENVとは独立）
            $targetEnv = config('fregi.environment', 'test');
            $config = $this->configService->getActiveConfig($companyId, $targetEnv);
            $connectPassword = $this->encryptionService->decryptSecret($config->connect_password_enc);

            // チェックサム検証
            $calculatedChecksum = $this->paymentService->generateReturnUrlChecksum(
                $status,
                $settleno,
                $id,
                $authcode,
                $seqno,
                $free ?: null,
                $ua,
                $connectPassword
            );

            if (!hash_equals($checksum, $calculatedChecksum)) {
                Log::warning('F-REGI戻りURL: チェックサム検証失敗', [
                    'payment_id' => $payment->id,
                    'received' => $checksum,
                    'calculated' => $calculatedChecksum,
                ]);
                return view('return.error', ['message' => 'チェックサム検証に失敗しました。']);
            }

            // 決済情報を更新
            $payment->receiptno = $authcode ?: $payment->receiptno;
            $payment->slipno = $seqno ?: $payment->slipno;
            
            // STATUSに応じてステータスを更新
            if ($status === 'OK') {
                $payment->status = 'paid';
                $payment->completed_at = now();
            } elseif ($status === 'CANCEL') {
                $payment->status = 'canceled';
            } else {
                $payment->status = 'failed';
            }
            $payment->save();

            // Contractステータスを更新（決済ステータスに応じて）
            if ($payment->contract) {
                if ($status === 'OK' && $payment->contract->status !== 'active') {
                    // 決済完了時: Contractをactiveに
                    $payment->contract->update([
                        'status' => 'active',
                        'actual_start_date' => $payment->contract->actual_start_date ?? now()->toDateString(),
                    ]);
                } elseif (in_array($status, ['CANCEL', 'NG']) && $payment->contract->status === 'pending_payment') {
                    // 決済失敗/キャンセル時: Contractをpending_paymentのまま（再決済可能）
                    // 既にpending_paymentの場合は更新不要（冪等性）
                } elseif (in_array($status, ['CANCEL', 'NG']) && $payment->contract->status !== 'pending_payment') {
                    // 他のステータスから失敗/キャンセルになった場合のみ更新
                    $payment->contract->update(['status' => 'pending_payment']);
                }
            }

            // STATUSに応じてビューを返す
            if ($status === 'OK') {
                return redirect()->route('contract.complete', ['orderid' => $id]);
            } elseif ($status === 'CANCEL') {
                return view('return.cancel', compact('payment'));
            } else {
                return view('return.failure', compact('payment'));
            }
        } catch (\Exception $e) {
            Log::error('F-REGI戻りURL処理エラー', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return view('return.error', ['message' => 'エラーが発生しました: ' . $e->getMessage()]);
        }
    }
}
