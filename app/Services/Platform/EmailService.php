<?php

namespace App\Services\Platform;

use App\Mail\DynamicMail;
use App\Models\Appointment\Appointment;
use App\Models\Company;
use App\Models\Order;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * EmailService — Single entry point for all application email sending.
 *
 * Responsibilities:
 *   1. Apply runtime SMTP config from system_settings (Super Admin controlled).
 *   2. Enforce sender identity from system_settings — tenants cannot override this.
 *   3. Send via Laravel Mail and log success / failure, never throwing.
 *
 * Content is NOT this class's concern. Every email is a Blade view under
 * resources/views/emails, rendered through DynamicMail: version controlled,
 * reviewable in a diff, and escaped by default.
 *
 * This replaced a database-backed template table that offered tenants a raw
 * HTML body field none of them ever used, resolved through a three-layer
 * fallback ending in hardcoded PHP strings, and interpolated variables with
 * str_replace before printing them unescaped — which let anyone submitting a
 * public storefront form inject markup into the store owner's inbox.
 *
 * Usage:
 *   app(EmailService::class)->sendMailable(
 *       new DynamicMail('Subject line', 'emails.some-view', $data),
 *       $toEmail,
 *       $toName,
 *   );
 */
class EmailService
{
    /**
     * Name of the runtime mailer registered per-request in config.
     * Using a dedicated name avoids touching the global 'smtp' mailer config.
     */
    private const MAILER_NAME = 'system_smtp';

    // ════════════════════════════════════════════════════
    //  PUBLIC API
    // ════════════════════════════════════════════════════

    /**
     * Send a Mailable using the platform's SMTP settings.
     *
     * Never throws: email is always secondary to the action that triggered it,
     * and an SMTP outage must not roll back an order or a leave request.
     */
    public function sendMailable(Mailable $mailable, string $toEmail, string $toName = ''): void
    {
        $name = class_basename($mailable);

        try {
            $mailConfig = $this->loadMailConfig();

            if (! $this->isConfigComplete($mailConfig)) {
                Log::error('[EmailService] Aborted — incomplete SMTP configuration in system_settings', [
                    'mailable' => $name,
                    'to' => $toEmail,
                    'missing' => $this->missingConfigKeys($mailConfig),
                ]);

                return;
            }

            $this->applyRuntimeMailer($mailConfig);

            Mail::mailer(self::MAILER_NAME)->to($toEmail, $toName)->send($mailable);

            Log::info('[EmailService] Email sent', ['mailable' => $name, 'to' => $toEmail]);

        } catch (\Throwable $e) {
            Log::error('[EmailService] Failed to send email', [
                'mailable' => $name,
                'to' => $toEmail,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send the customer their inquiry confirmation.
     *
     * The store's own copy is not sent from here — HandleNewOrderNotification
     * routes it through NotificationDispatcher, so Settings > Notifications
     * stays the only place that decides which staff hear about an inquiry.
     */
    public function sendCustomerInquiryConfirmation(Order $order, Company $company, string $productName = ''): void
    {
        $data = [
            'customerName' => $order->customer_name ?? '',
            'customerEmail' => $order->customer_email ?? '',
            'customerPhone' => $order->customer_phone ?? '',
            'productName' => $productName,
            'message' => $order->customer_notes ?? '',
            'storeName' => $company->name,
            'orderNumber' => $order->order_number,
            'inquiryDate' => $order->created_at?->format('d M Y, h:i A') ?? now()->format('d M Y, h:i A'),
        ];

        // Confirmation to the customer — only when they left an address.
        if (! empty($order->customer_email)) {
            $this->sendMailable(
                new DynamicMail(
                    "We received your inquiry, {$data['customerName']}",
                    'emails.order-inquiry-customer',
                    $data,
                ),
                $order->customer_email,
                $order->customer_name ?? 'Customer',
            );
        }

        // The owner's copy is NOT sent here. It goes through
        // NotificationDispatcher from HandleNewOrderNotification, so that
        // Settings > Notifications is the single place deciding who on the
        // tenant's staff hears about an inquiry.
    }

    /**
     * Send the customer their appointment booking confirmation.
     *
     * Sits here rather than behind Settings > Notifications because it is not
     * a tenant decision: the customer typed their address into the booking
     * form and is expecting a receipt for it. That screen governs which staff
     * hear about a booking, which is a different question.
     *
     * The email deliberately does not promise a confirmed slot — bookings land
     * as pending and a human still has to accept them.
     */
    public function sendCustomerAppointmentConfirmation(Appointment $appointment): void
    {
        // Nothing to send to — the phone number is the required field on the
        // booking form, email is optional.
        if (empty($appointment->customer_email)) {
            return;
        }

        $storeName = $appointment->company?->name ?? config('app.name');

        $this->sendMailable(
            new DynamicMail(
                "Your appointment request — {$appointment->appointment_no}",
                'emails.appointment-booked-customer',
                [
                    'customerName'    => $appointment->customer_name,
                    'storeName'       => $storeName,
                    'appointmentNo'   => $appointment->appointment_no,
                    'serviceName'     => $appointment->service?->name ?? 'Appointment',
                    'slotLabel'       => $appointment->slot?->display_label ?? '—',
                    'appointmentDate' => $appointment->appointment_date?->format('d M Y') ?? '—',
                    'address'         => $appointment->address,
                    'notes'           => $appointment->notes,
                ],
            ),
            $appointment->customer_email,
            $appointment->customer_name ?: 'Customer',
        );
    }

    /**
     * Tell the customer their appointment was confirmed or cancelled.
     *
     * Not routed through NotificationDispatcher on purpose. That screen decides
     * which staff hear about an event; here the recipient follows from the
     * booking itself and there is no decision for a tenant to make.
     *
     * Statuses other than confirmed and cancelled are ignored: marking an
     * appointment complete is an internal bookkeeping step, and the customer
     * was there — they do not need an email about it.
     */
    public function sendCustomerAppointmentStatusUpdate(Appointment $appointment): void
    {
        if (empty($appointment->customer_email)) {
            return;
        }

        [$subject, $view] = match ($appointment->status) {
            Appointment::STATUS_CONFIRMED => [
                "Your appointment is confirmed — {$appointment->appointment_no}",
                'emails.appointment-confirmed-customer',
            ],
            Appointment::STATUS_CANCELLED => [
                "Your appointment was cancelled — {$appointment->appointment_no}",
                'emails.appointment-cancelled-customer',
            ],
            default => [null, null],
        };

        if ($view === null) {
            return;
        }

        $this->sendMailable(
            new DynamicMail($subject, $view, [
                'customerName'    => $appointment->customer_name,
                'storeName'       => $appointment->company?->name ?? config('app.name'),
                'appointmentNo'   => $appointment->appointment_no,
                'serviceName'     => $appointment->service?->name ?? 'Appointment',
                'slotLabel'       => $appointment->slot?->display_label ?? '—',
                'appointmentDate' => $appointment->appointment_date?->format('d M Y') ?? '—',
                'address'         => $appointment->address,
                // Shown to the customer as the reason on a cancellation, so
                // whatever staff type here is customer-facing.
                'adminNotes'      => $appointment->admin_notes,
            ]),
            $appointment->customer_email,
            $appointment->customer_name ?: 'Customer',
        );
    }

    // ════════════════════════════════════════════════════
    //  PRIVATE — MAIL CONFIGURATION
    // ════════════════════════════════════════════════════

    /**
     * Fetch SMTP settings from system_settings (Super Admin controlled).
     * SystemSetting::getSetting() uses rememberForever caching, so repeated
     * calls within a request incur only one cache lookup.
     *
     * @return array<string,mixed>
     */
    private function loadMailConfig(): array
    {
        // ?: rather than ?? on every line, and rather than passing a default to
        // get_system_setting(). The settings rows are created up front and sit
        // there blank until a Super Admin fills them in, so the helper finds a
        // row and returns '' — a default argument would never be reached and
        // ?? would not fire either. Empty must mean "not configured".
        //
        // config() and not env(): after config:cache, env() returns null
        // outside the config files, and config/mail.php already reads .env.
        return [
            'driver'     => get_system_setting('mail_driver') ?: config('mail.default') ?: 'smtp',
            'scheme'     => get_system_setting('mail_scheme') ?: config('mail.mailers.smtp.scheme'),
            'host'       => get_system_setting('mail_host') ?: config('mail.mailers.smtp.host'),
            'port'       => (int) (get_system_setting('mail_port') ?: config('mail.mailers.smtp.port') ?: 587),
            'username'   => get_system_setting('mail_username') ?: config('mail.mailers.smtp.username'),
            'password'   => get_system_setting('mail_password') ?: config('mail.mailers.smtp.password'),
            'encryption' => get_system_setting('mail_encryption') ?: config('mail.mailers.smtp.encryption'),
            'from_email' => get_system_setting('mail_from_email') ?: config('mail.from.address'),
            'from_name'  => get_system_setting('mail_from_name') ?: config('mail.from.name') ?: config('app.name'),
        ];
    }

    /**
     * Minimum required keys that must be non-empty before attempting to send.
     *
     * @param  array<string,mixed>  $config
     */
    private function isConfigComplete(array $config): bool
    {
        // log and array transports write nowhere and need no credentials.
        // Requiring a host from them would block local development, where
        // MAIL_MAILER=log is the normal setup.
        if (in_array($config['driver'], ['log', 'array'], true)) {
            return ! empty($config['from_email']);
        }

        return ! empty($config['host'])
            && ! empty($config['username'])
            && ! empty($config['from_email']);
    }

    /**
     * Return the list of missing required keys, used for structured error logging.
     *
     * @param  array<string,mixed>  $config
     * @return string[]
     */
    private function missingConfigKeys(array $config): array
    {
        $required = in_array($config['driver'], ['log', 'array'], true)
            ? ['from_email']
            : ['host', 'username', 'from_email'];

        return array_values(
            array_filter($required, fn ($k) => empty($config[$k]))
        );
    }

    /**
     * Register a named runtime mailer in Laravel's config so we can call
     * Mail::mailer('system_smtp'). Using a dedicated name leaves the global
     * 'smtp' / 'log' mailers from .env completely untouched.
     *
     * Sender identity (from address & name) is always set from system_settings
     * here — tenant code never has access to override it.
     *
     * @param  array<string,mixed>  $mailConfig
     */
    private function applyRuntimeMailer(array $mailConfig): void
    {
        Config::set('mail.mailers.'.self::MAILER_NAME, [
            'transport' => $mailConfig['driver'] ?: 'smtp',
            // Shared hosting commonly needs smtps on port 465; without the
            // scheme Symfony negotiates STARTTLS on 465 and the connection
            // hangs until it times out.
            'scheme' => $mailConfig['scheme'] ?: null,
            'host' => $mailConfig['host'],
            'port' => $mailConfig['port'],
            'username' => $mailConfig['username'],
            'password' => $mailConfig['password'],
            'encryption' => $mailConfig['encryption'] ?: null,
        ]);

        // Sender identity — Super Admin controlled, enforced here on every send.
        Config::set('mail.from.address', $mailConfig['from_email']);
        Config::set('mail.from.name', $mailConfig['from_name']);
    }
}