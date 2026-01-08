<?php

namespace App\Services;

use App\Models\FregiConfig;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;

class FregiPaymentService
{
    private EncryptionService $encryptionService;
    private FregiConfigService $configService;

    public function __construct(
        EncryptionService $encryptionService,
        FregiConfigService $configService
    ) {
        $this->encryptionService = $encryptionService;
        $this->configService = $configService;
    }

    /**
     * 決済開始パラメータを生成（F-REGIへのPOST用）
     *
     * @param Payment $payment
     * @return array F-REGIへのPOSTパラメータ
     * @throws \Exception
     */
    public function initiatePayment(Payment $payment): array
    {
        // アクティブな設定を取得
        $config = $this->configService->getActiveConfig(
            $payment->company_id,
            env('APP_ENV') === 'production' ? 'prod' : 'test'
        );

        // パスワードを復号
        $connectPassword = $this->encryptionService->decryptSecret($config->connect_password_enc);

        // F-REGIへのパラメータを構築
        $params = [
            'SHOPID' => $config->shopid,
            'ORDERID' => $payment->orderid,
            'AMOUNT' => (string)$payment->amount,
            'CURRENCY' => $payment->currency,
            // その他の必要なパラメータはF-REGI仕様に応じて追加
        ];

        // チェックサムを生成
        $checksum = $this->generateChecksum($params, $connectPassword);
        $params['CHECKSUM'] = $checksum;

        return $params;
    }

    /**
     * 通知の署名を検証
     *
     * @param array $params 通知パラメータ
     * @return bool
     */
    public function verifyNotifySignature(array $params): bool
    {
        if (empty($params['SHOPID']) || empty($params['CHECKSUM'])) {
            return false;
        }

        try {
            // 設定を取得（SHOPIDから環境を特定する必要がある場合がある）
            // ここでは簡易実装。実際の仕様に合わせて調整が必要
            $companyId = $params['COMPANY_ID'] ?? 1; // 実際の実装では適切に取得
            $environment = env('APP_ENV') === 'production' ? 'prod' : 'test';
            
            $config = $this->configService->getActiveConfig($companyId, $environment);
            $connectPassword = $this->encryptionService->decryptSecret($config->connect_password_enc);

            $receivedChecksum = $params['CHECKSUM'];
            unset($params['CHECKSUM']);

            $calculatedChecksum = $this->generateChecksum($params, $connectPassword);

            return hash_equals($receivedChecksum, $calculatedChecksum);
        } catch (\Exception $e) {
            Log::error('Failed to verify notify signature', [
                'error' => $e->getMessage(),
                // パラメータはログに含めない（秘密情報の可能性があるため）
            ]);
            return false;
        }
    }

    /**
     * 通知を処理（冪等）
     *
     * @param array $params 通知パラメータ
     * @return Payment
     * @throws \Exception
     */
    public function processNotify(array $params): Payment
    {
        // 署名検証
        if (!$this->verifyNotifySignature($params)) {
            throw new \Exception('Invalid signature');
        }

        // 決済を特定（ORDERIDまたはF-REGIの識別子から）
        $orderId = $params['ORDERID'] ?? null;
        if (!$orderId) {
            throw new \Exception('ORDERID is required');
        }

        // 決済を取得（company_idの特定が必要。実際の実装では適切に取得）
        $companyId = $params['COMPANY_ID'] ?? 1; // 実際の実装では適切に取得
        $payment = Payment::where('company_id', $companyId)
            ->where('orderid', $orderId)
            ->firstOrFail();

        // 冪等性チェック：既に確定済みの場合は更新しない
        if (in_array($payment->status, ['paid', 'failed', 'canceled'])) {
            return $payment;
        }

        // ステータスを更新
        $status = $this->mapNotifyStatus($params);
        $payment->status = $status;
        $payment->receiptno = $params['RECEIPTNO'] ?? null;
        $payment->slipno = $params['SLIPNO'] ?? null;
        $payment->notified_at = now();
        
        if ($status === 'paid') {
            $payment->completed_at = now();
        } elseif (in_array($status, ['failed', 'canceled'])) {
            $payment->failure_reason = $params['ERRCODE'] ?? $params['MESSAGE'] ?? null;
        }

        // 通知ペイロードを保存（マスク処理を推奨）
        $payload = $params;
        // 秘密情報をマスク（必要に応じて）
        $payment->raw_notify_payload = $payload;

        $payment->save();

        return $payment;
    }

    /**
     * チェックサムを生成
     * 注意: 実際のF-REGI仕様に合わせて実装を調整すること
     *
     * @param array $params
     * @param string $connectPassword
     * @return string
     */
    private function generateChecksum(array $params, string $connectPassword): string
    {
        // パラメータをソート
        ksort($params);

        // パラメータを文字列に結合（KEY=VALUE形式）
        $string = '';
        foreach ($params as $key => $value) {
            if ($key !== 'CHECKSUM') { // CHECKSUM自体は除外
                $string .= $key . '=' . $value . '&';
            }
        }

        // 接続パスワードを追加
        $string .= $connectPassword;

        // SHA256ハッシュを生成（実際のF-REGI仕様に合わせて調整）
        return hash('sha256', $string);
    }

    /**
     * 通知パラメータからステータスをマッピング
     *
     * @param array $params
     * @return string
     */
    private function mapNotifyStatus(array $params): string
    {
        // 実際のF-REGIのレスポンスコードに合わせて調整
        $result = $params['RESULT'] ?? $params['STATUS'] ?? '';

        if ($result === 'OK' || $result === 'SUCCESS') {
            return 'paid';
        } elseif ($result === 'CANCEL') {
            return 'canceled';
        } else {
            return 'failed';
        }
    }
}

