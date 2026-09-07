<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Renders any Blade view under resources/views/emails as an email.
 *
 * One Mailable for every email in the app rather than a class per message:
 * the only thing that varies between them is the subject line and which view
 * to render, and a dedicated class for each would be thirty files of identical
 * boilerplate.
 *
 * Queueable is deliberately absent. Every send is synchronous — the app runs
 * on shared hosting with no queue worker, so a queued mail would sit in the
 * jobs table forever.
 */
class DynamicMail extends Mailable
{
    use SerializesModels;

    /**
     * @param  string  $subjectLine   Subject of the email.
     * @param  string  $viewTemplate  Blade view to render (e.g. 'emails.otp').
     * @param  array<string,mixed>  $templateData  Data passed to the view.
     */
    public function __construct(
        public string $subjectLine,
        public string $viewTemplate,
        public array $templateData = [],
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine);
    }

    public function content(): Content
    {
        return new Content(view: $this->viewTemplate, with: $this->templateData);
    }
}