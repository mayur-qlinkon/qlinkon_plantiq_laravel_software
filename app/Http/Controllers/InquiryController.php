<?php

namespace App\Http\Controllers;

use App\Models\Platform\ContactInquiry;
use App\Services\Platform\EmailService;
use App\Mail\DynamicMail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InquiryController extends Controller
{
    /**
     * Show the public landing page.
     */
    public function index(): View
    {
        return view('welcome');
    }

    /**
     * Handle the contact form submission.
     */
    public function inquire(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:20'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $inquiry = ContactInquiry::create($validated);

        // Notify super admin via email if template exists.
        $adminEmail = get_system_setting('support_email');
        if ($adminEmail) {
            app(EmailService::class)->sendMailable(
                new DynamicMail("New contact inquiry from {$inquiry->name}", 'emails.inquiry-received', [
                    'name' => $inquiry->name,
                    'email' => $inquiry->email,
                    'phone' => $inquiry->phone ?? 'N/A',
                    'message' => $inquiry->message,
                ]),
                $adminEmail,
                get_system_setting('app_name', 'Platform Admin'),
            );
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Thank you! We will get back to you soon.',
            ], 200);
        }

        return redirect()
            ->route('landing')
            ->with('success', 'Thank you! We will get back to you soon.');
    }
}
