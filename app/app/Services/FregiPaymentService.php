<?php

namespace App\Services;

use App\Models\Contract;
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
     * 通知の署名を検証（現時点では実装せず、後で実装する）
     * 注意: F-REGI仕様書では通知のチェックサム検証の詳細が記載されていないため、現時点ではスキップ
     *
     * @param array $params 通知パラメータ
     * @return bool
     */
    public function verifyNotifySignature(array $params): bool
    {
        // TODO: 通知のチェックサム検証を実装（仕様書に詳細が記載されたら実装）
        // 現時点では常にtrueを返す（実装待ち）
        return true;
    }

    /**
     * 通知を処理（冪等）
     * F-REGI仕様: 通知パラメータ名は小文字（settleno, seqno, paymenttype, code, authcode）
     *
     * @param array $params 通知パラメータ
     * @return Payment
     * @throws \Exception
     */
    public function processNotify(array $params): Payment
    {
        // 署名検証（現時点では実装せず、後で実装する）
        if (!$this->verifyNotifySignature($params)) {
            throw new \Exception('Invalid signature');
        }

        // 決済を特定（code（伝票番号）またはsettleno（発行番号）から）
        // パラメータ名は小文字（F-REGI仕様）
        $orderId = $params['code'] ?? $params['CODE'] ?? null; // 伝票番号
        $settleno = $params['settleno'] ?? $params['SETTLENO'] ?? null; // 発行番号
        
        if (!$orderId && !$settleno) {
            throw new \Exception('code（伝票番号）またはsettleno（発行番号）が必要です');
        }

        // 決済を取得
        // まずcompany_idを特定（実際の実装では適切に取得する必要がある）
        $companyId = 1; // 仮のcompany_id（マルチテナント対応時に変更）
        
        $query = Payment::where('company_id', $companyId);
        if ($orderId) {
            $query->where('orderid', $orderId);
        }
        if ($settleno) {
            $query->where('settleno', $settleno);
        }
        $payment = $query->firstOrFail();

        // 冪等性チェック：既に確定済みの場合は更新しない
        if (in_array($payment->status, ['paid', 'failed', 'canceled'])) {
            return $payment;
        }

        // ステータスを更新
        $status = $this->mapNotifyStatus($params);
        $payment->status = $status;
        
        // パラメータ名は小文字（F-REGI仕様）
        $payment->receiptno = $params['authcode'] ?? $params['AUTHCODE'] ?? null; // 承認番号
        $payment->slipno = $params['seqno'] ?? $params['SEQNO'] ?? null; // 取引番号
        $payment->notified_at = now();
        
        if ($status === 'paid') {
            $payment->completed_at = now();
        } elseif (in_array($status, ['failed', 'canceled'])) {
            $payment->failure_reason = $params['error_code'] ?? $params['ERROR_CODE'] ?? $params['message'] ?? $params['MESSAGE'] ?? null;
        }

        // 通知ペイロードを保存（マスク処理を推奨）
        $payload = $params;
        // 秘密情報をマスク（必要に応じて）
        $payment->raw_notify_payload = $payload;

        $payment->save();

        // Contractステータスを更新（決済ステータスに応じて）
        if ($payment->contract) {
            if ($status === 'paid' && $payment->contract->status !== 'active') {
                // 決済完了時: Contractをactiveに
                $payment->contract->update([
                    'status' => 'active',
                    'actual_start_date' => $payment->contract->actual_start_date ?? now()->toDateString(),
                ]);
            } elseif (in_array($status, ['failed', 'canceled']) && $payment->contract->status === 'pending_payment') {
                // 決済失敗/キャンセル時: Contractをpending_paymentのまま（再決済可能）
                // 既にpending_paymentの場合は更新不要（冪等性）
            } elseif (in_array($status, ['failed', 'canceled']) && $payment->contract->status !== 'pending_payment') {
                // 他のステータスから失敗/キャンセルになった場合のみ更新
                $payment->contract->update(['status' => 'pending_payment']);
            }
        }

        return $payment;
    }

    /**
     * お支払い方法選択画面URL用のチェックサムを生成
     * F-REGI仕様: SHOPID<タブ>接続パスワード<タブ>発行番号<タブ>伝票番号 をMD5でハッシュ化
     *
     * @param string $shopid SHOPID
     * @param string $connectPassword 接続パスワード
     * @param string $settleno 発行番号（SETTLENO）
     * @param string $id 伝票番号（ID）
     * @return string MD5ハッシュ値
     */
    public function generatePaymentPageChecksum(string $shopid, string $connectPassword, string $settleno, string $id): string
    {
        // タブ区切りで連結（サンプルプログラム79行目を参考）
        $md5_seed = $shopid . "\t" . $connectPassword . "\t" . $settleno . "\t" . $id;
        
        // MD5ハッシュを生成
        return md5($md5_seed);
    }

    /**
     * 戻りURL用のチェックサムを生成
     * F-REGI仕様: 処理成否文字列<タブ>発行番号<タブ>伝票番号<タブ>承認番号<タブ>取引番号<タブ>自由項目<タブ>使用端末識別フラグ<タブ>加盟店パスワード をMD5でハッシュ化
     * 自由項目が設定されていない場合は自由項目を除外
     *
     * @param string $status 処理成否文字列（OK/NG/CANCEL）
     * @param string $settleno 発行番号
     * @param string $id 伝票番号
     * @param string $authcode 承認番号
     * @param string $seqno 取引番号
     * @param string|null $free 自由項目（設定されていない場合はnull）
     * @param string $ua 使用端末識別フラグ
     * @param string $connectPassword 接続パスワード
     * @return string MD5ハッシュ値
     */
    public function generateReturnUrlChecksum(
        string $status,
        string $settleno,
        string $id,
        string $authcode,
        string $seqno,
        ?string $free,
        string $ua,
        string $connectPassword
    ): string {
        // 自由項目が設定されている場合
        if ($free !== null && $free !== '') {
            $md5_seed = $status . "\t" . $settleno . "\t" . $id . "\t" . $authcode . "\t" . $seqno . "\t" . $free . "\t" . $ua . "\t" . $connectPassword;
        } else {
            // 自由項目が設定されていない場合
            $md5_seed = $status . "\t" . $settleno . "\t" . $id . "\t" . $authcode . "\t" . $seqno . "\t" . $ua . "\t" . $connectPassword;
        }
        
        // MD5ハッシュを生成
        return md5($md5_seed);
    }

    /**
     * チェックサムを生成（後方互換性のため残すが、非推奨）
     * 注意: 実際のF-REGI仕様に合わせて実装を調整すること
     *
     * @param array $params
     * @param string $connectPassword
     * @return string
     * @deprecated 代わりに generatePaymentPageChecksum または generateReturnUrlChecksum を使用してください
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
     * F-REGI仕様: 決済完了通知にはステータスが含まれないため、paymenttypeから推測
     * または、決済完了通知が来た時点で'paid'と判断
     *
     * @param array $params
     * @return string
     */
    private function mapNotifyStatus(array $params): string
    {
        // F-REGIの決済完了通知は、通知が来た時点で決済が完了していると判断
        // 通知が来た = 決済完了
        // ただし、エラーが発生している可能性もあるため、paymenttypeを確認
        
        // paymenttype（お支払い方法）が存在する場合、決済が完了していると判断
        $paymentType = $params['paymenttype'] ?? $params['PAYMENTTYPE'] ?? null;
        
        if ($paymentType) {
            // お支払い方法が指定されている = 決済が完了
            return 'paid';
        }
        
        // デフォルトはpaid（通知が来た時点で決済完了と判断）
        return 'paid';
    }
}

