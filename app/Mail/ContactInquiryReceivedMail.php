<?php

namespace App\Mail;

use App\Models\Platform\ContactInquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent to the tenant user right after they submit a Help Center contact inquiry.
 * Plain Blade-view mailable — deliberately bypasses EmailService/EmailTemplate
 * (no DB-driven dynamic template for this one).
 */
class ContactInquiryReceivedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly ContactInquiry $inquiry,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'We\'ve received your request',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contact-inquiry-received',
            with: [
                'name' => $this->inquiry->name,
                'inquiryMessage' => $this->inquiry->message,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}