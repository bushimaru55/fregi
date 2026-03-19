<?php

namespace App\Services;

use App\Mail\ContractNotificationMail;
use App\Mail\ContractReplyMail;
use App\Models\Contract;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * 契約申込時のメール送信サービス（RobotPayment に依存しない）
 *
 * - 管理者通知メール: SiteSetting の notification_email に送信
 * - 申込者返信メール: SiteSetting の reply_mail_header / reply_mail_footer を使用
 * - mail_sent_at による二重送信防止
 */
class ContractMailService
{
    /**
     * 管理者通知 + 申込者返信メールを送信する。
     * 既に送信済み（mail_sent_at がセット済み）の場合はスキップ。
     *
     * @return bool true=送信実行, false=スキップ
     */
    public function sendOnce(Contract $contract): bool
    {
        if ($contract->mail_sent_at !== null) {
            Log::channel('contract_payment')->info('申込メール送信スキップ（送信済み）', [
                'contract_id' => $contract->id,
                'mail_sent_at' => $contract->mail_sent_at,
            ]);
            return false;
        }

        $contract->load('contractItems');
        $optionItems = $contract->contractItems->filter(fn ($i) => $i->product_id !== null);
        $optionTotalAmount = $optionItems->sum('subtotal');

        Log::channel('contract_payment')->info('申込メール送信開始', [
            'contract_id' => $contract->id,
            'to_customer' => self::maskEmail($contract->email),
            'mail_driver' => config('mail.default'),
            'mail_host' => config('mail.mailers.' . config('mail.default') . '.host', '(N/A)'),
        ]);

        $this->sendAdminNotification($contract, $optionItems, $optionTotalAmount);
        $this->sendCustomerReply($contract, $optionItems, $optionTotalAmount);

        $contract->update(['mail_sent_at' => now()]);

        Log::channel('contract_payment')->info('申込メール送信処理完了', [
            'contract_id' => $contract->id,
        ]);

        return true;
    }

    private function sendAdminNotification(Contract $contract, $optionItems, int $optionTotalAmount): void
    {
        try {
            $notificationEmails = SiteSetting::getNotificationEmailsArray();
            if ($notificationEmails === []) {
                Log::channel('contract_payment')->warning('管理者通知メール送信スキップ（通知先未設定）', [
                    'contract_id' => $contract->id,
                ]);
                return;
            }
            foreach ($notificationEmails as $email) {
                Mail::to($email)->send(new ContractNotificationMail($contract, $optionItems, $optionTotalAmount));
                Log::channel('contract_payment')->info('管理者通知メール送信完了', [
                    'contract_id' => $contract->id,
                    'to' => self::maskEmail($email),
                ]);
            }
        } catch (\Throwable $e) {
            Log::channel('contract_payment')->error('管理者通知メール送信エラー', [
                'contract_id' => $contract->id,
                'message' => $e->getMessage(),
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    private function sendCustomerReply(Contract $contract, $optionItems, int $optionTotalAmount): void
    {
        try {
            if (!$contract->email) {
                Log::channel('contract_payment')->warning('申込者返信メール送信スキップ（メールアドレスなし）', [
                    'contract_id' => $contract->id,
                ]);
                return;
            }
            Mail::to($contract->email)->send(new ContractReplyMail($contract, $optionItems, $optionTotalAmount));
            Log::channel('contract_payment')->info('申込者返信メール送信完了', [
                'contract_id' => $contract->id,
                'to' => self::maskEmail($contract->email),
            ]);
        } catch (\Throwable $e) {
            Log::channel('contract_payment')->error('申込者返信メール送信エラー', [
                'contract_id' => $contract->id,
                'to' => self::maskEmail($contract->email ?? ''),
                'message' => $e->getMessage(),
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    public static function maskEmail(?string $email): string
    {
        if ($email === null || $email === '') {
            return '(empty)';
        }
        $at = strpos($email, '@');
        if ($at === false) {
            return substr($email, 0, 3) . '***';
        }
        $local = substr($email, 0, $at);
        $domain = substr($email, $at);
        return (strlen($local) <= 3 ? $local : substr($local, 0, 3) . '***') . $domain;
    }
}
