<?php

namespace App\Mail;

use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContractNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public Contract $contract;
    public $optionItems;
    public $optionTotalAmount;

    /**
     * Create a new message instance.
     */
    public function __construct(Contract $contract, $optionItems = null, $optionTotalAmount = 0)
    {
        $this->contract = $contract;
        $this->optionItems = $optionItems;
        $this->optionTotalAmount = $optionTotalAmount;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '【申込受付】新規申込のお知らせ - ' . $this->contract->company_name,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.contract-notification',
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
