<?php

namespace App\Mail;

use App\Models\Contract;
use App\Models\SiteSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContractReplyMail extends Mailable
{
    use Queueable, SerializesModels;

    public Contract $contract;
    public $optionItems;
    public $optionTotalAmount;
    public string $headerText;
    public string $footerText;

    /**
     * Create a new message instance.
     */
    public function __construct(Contract $contract, $optionItems = null, $optionTotalAmount = 0)
    {
        $this->contract = $contract;
        $this->optionItems = $optionItems;
        $this->optionTotalAmount = $optionTotalAmount;
        
        // 返信メール設定を取得
        $this->headerText = SiteSetting::getTextValue('reply_mail_header', '');
        $this->footerText = SiteSetting::getTextValue('reply_mail_footer', '');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '【申込受付完了】DSchatbotサービスへのお申し込みありがとうございます',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.contract-reply',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
