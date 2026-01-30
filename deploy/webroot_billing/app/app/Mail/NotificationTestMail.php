<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NotificationTestMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct()
    {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '【送信テスト】申込通知メール送信先の確認',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.notification-test',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
