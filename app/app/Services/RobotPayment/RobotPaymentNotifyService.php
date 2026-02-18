<?php

namespace App\Services\RobotPayment;

use App\Mail\ContractNotificationMail;
use App\Mail\ContractReplyMail;
use App\Models\Contract;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * 決済結果通知・自動課金結果通知の冪等処理（03_sequence_and_callbacks / 04_data_model）
 */
class RobotPaymentNotifyService
{
    public const EVENT_INITIAL = 'rp_initial_kickback';
    public const EVENT_RECURRING = 'rp_recurring_kickback';

    /**
     * 初回決済結果通知（決済結果通知URL）
     * GET キックバック。冪等: 同一 gid はスキップ。ContentLength 0 以上を返すこと。
     */
    public function handleInitialNotify(string $rawQuery): array
    {
        $params = $this->parseQuery($rawQuery);
        $gid = $params['gid'] ?? null;
        if (!$gid) {
            Log::channel('contract_payment')->warning('RP 初回通知: gid なし', ['raw' => substr($rawQuery, 0, 200)]);
            return ['handled' => false, 'reason' => 'no_gid'];
        }

        if (PaymentEvent::where('event_type', self::EVENT_INITIAL)->where('rp_gid', $gid)->exists()) {
            return ['handled' => true, 'idempotent' => true];
        }

        $cod = $params['cod'] ?? null;
        $rst = (int) ($params['rst'] ?? 0);
        $acid = $params['acid'] ?? null;
        $ta = isset($params['ta']) ? (int) $params['ta'] : null;

        $payment = Payment::where('provider', 'robotpayment')
            ->where('merchant_order_no', $cod)
            ->first();

        if (!$payment) {
            Log::channel('contract_payment')->warning('RP 初回通知: 対応する Payment なし', ['cod' => $cod, 'gid' => $gid]);
            return ['handled' => true, 'reason' => 'no_payment'];
        }

        return DB::transaction(function () use ($payment, $rawQuery, $params, $gid, $acid, $rst, $ta) {
            PaymentEvent::create([
                'payment_id' => $payment->id,
                'event_type' => self::EVENT_INITIAL,
                'raw_query' => $rawQuery,
                'rp_gid' => $gid,
                'rp_acid' => $acid,
                'payload' => $params,
                'created_at' => now(),
            ]);

            $payment->update([
                'rp_gid' => $gid,
                'rp_acid' => $acid,
                'status' => $rst === 1 ? 'paid' : 'failed',
                'notified_at' => now(),
                'completed_at' => $rst === 1 ? now() : null,
                'paid_at' => $rst === 1 ? now() : null,
                'failure_reason' => $rst !== 1 ? ($params['ec'] ?? '決済失敗') : null,
                'raw_notify_payload' => $params,
            ]);

            $contract = $payment->contract;
            if ($contract && $rst === 1) {
                $contract->update(['payment_id' => $payment->id]);
                $this->sendContractMails($contract);
            }

            return ['handled' => true, 'idempotent' => false];
        });
    }

    /**
     * 自動課金結果通知（自動課金結果通知URL）
     * 冪等: 同一 (gid, acid) はスキップ。
     */
    public function handleRecurringNotify(string $rawQuery): array
    {
        $params = $this->parseQuery($rawQuery);
        $gid = $params['gid'] ?? null;
        $acid = $params['acid'] ?? null;
        if (!$gid) {
            Log::channel('contract_payment')->warning('RP 自動課金通知: gid なし', ['raw' => substr($rawQuery, 0, 200)]);
            return ['handled' => false, 'reason' => 'no_gid'];
        }

        if (PaymentEvent::where('event_type', self::EVENT_RECURRING)->where('rp_gid', $gid)->where('rp_acid', $acid)->exists()) {
            return ['handled' => true, 'idempotent' => true];
        }

        $cod = $params['cod'] ?? null;
        $rst = (int) ($params['rst'] ?? 0);

        if (Payment::where('provider', 'robotpayment')->where('payment_kind', 'auto_recurring')->where('rp_gid', $gid)->exists()) {
            return ['handled' => true, 'idempotent' => true];
        }

        $initialPayment = Payment::where('provider', 'robotpayment')
            ->where('merchant_order_no', $cod)
            ->where('payment_kind', 'auto_initial')
            ->first();

        if (!$initialPayment) {
            Log::channel('contract_payment')->warning('RP 自動課金通知: 対応する初回決済なし（cod に紐づく auto_initial がありません）', ['cod' => $cod, 'gid' => $gid]);
            return ['handled' => true, 'idempotent' => false];
        }

        return DB::transaction(function () use ($initialPayment, $rawQuery, $params, $gid, $acid, $rst, $cod) {
            $recurringPayment = Payment::create([
                'company_id' => $initialPayment->company_id,
                'contract_id' => $initialPayment->contract_id,
                'provider' => 'robotpayment',
                'payment_kind' => 'auto_recurring',
                'merchant_order_no' => $cod,
                'orderid' => $cod . '-' . $gid,
                'rp_gid' => $gid,
                'rp_acid' => $acid,
                'amount' => (int) ($params['ta'] ?? $params['am'] ?? 0),
                'currency' => 'JPY',
                'payment_method' => 'card',
                'status' => $rst === 1 ? 'paid' : 'failed',
                'notified_at' => now(),
                'completed_at' => $rst === 1 ? now() : null,
                'paid_at' => $rst === 1 ? now() : null,
                'raw_notify_payload' => $params,
            ]);
            PaymentEvent::create([
                'payment_id' => $recurringPayment->id,
                'event_type' => self::EVENT_RECURRING,
                'raw_query' => $rawQuery,
                'rp_gid' => $gid,
                'rp_acid' => $acid,
                'payload' => $params,
                'created_at' => now(),
            ]);
            return ['handled' => true, 'idempotent' => false];
        });
    }

    private function parseQuery(string $raw): array
    {
        $out = [];
        parse_str($raw, $out);
        return $out;
    }

    private function sendContractMails(Contract $contract): void
    {
        $contract->load('contractItems');
        $optionItems = $contract->contractItems->filter(fn ($i) => $i->product_id !== null);
        $optionTotalAmount = $optionItems->sum('subtotal');

        try {
            $notificationEmails = SiteSetting::getNotificationEmailsArray();
            if ($notificationEmails !== []) {
                foreach ($notificationEmails as $email) {
                    Mail::to($email)->send(new ContractNotificationMail($contract, $optionItems, $optionTotalAmount));
                }
            }
        } catch (\Throwable $e) {
            Log::channel('contract_payment')->error('申込通知メール送信エラー', ['contract_id' => $contract->id, 'message' => $e->getMessage()]);
        }

        try {
            if ($contract->email) {
                Mail::to($contract->email)->send(new ContractReplyMail($contract, $optionItems, $optionTotalAmount));
            }
        } catch (\Throwable $e) {
            Log::channel('contract_payment')->error('申込者返信メール送信エラー', ['contract_id' => $contract->id, 'message' => $e->getMessage()]);
        }
    }
}
