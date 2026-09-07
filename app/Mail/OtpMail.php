<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * OTP email for password reset.
 */
class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        private readonly string $otp,
        private readonly string $appName,
        private readonly int $expiryMinutes,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your Verification Code — '.$this->appName);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.otp',
            with: [
                'otp' => $this->otp,
                'appName' => $this->appName,
                'expiryMinutes' => $this->expiryMinutes,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
