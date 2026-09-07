<?php

namespace App\Mail;

use App\Models\Platform\ContactInquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent to SUPER_ADMIN_EMAIL whenever a tenant submits a Help Center contact inquiry.
 * Plain Blade-view mailable — deliberately bypasses EmailService/EmailTemplate.
 */
class NewContactInquiryMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly ContactInquiry $inquiry,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Contact Inquiry — '.($this->inquiry->name ?? 'Unknown user'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.new-contact-inquiry',
            with: [
                'name' => $this->inquiry->name,
                'email' => $this->inquiry->email,
                'phone' => $this->inquiry->phone,
                'inquiryMessage' => $this->inquiry->message,
                'companyName' => $this->inquiry->user?->company?->name,
                'submittedAt' => $this->inquiry->created_at?->format('d M Y, h:i A'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}