<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Mail\ContactInquiryReceivedMail;
use App\Mail\NewContactInquiryMail;

use App\Models\Platform\ContactInquiry;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ContactInquiryController extends Controller
{
    /**
     * List all contact inquiries, newest first.
     */
    public function index(): View
    {
        $inquiries = ContactInquiry::with('user.company')
            ->latest('created_at')
            ->paginate(25);

        return view('platform.inquiries.index', compact('inquiries'));
    }

    public function store(Request $request): JsonResponse
    {
        // This endpoint is only reachable from the authenticated tenant admin
        // Help Center (see routes/admin.php — wrapped in 'auth' middleware).
        // Trust the logged-in user's identity instead of client-submitted fields.
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

       $user = $request->user();

        $inquiry = ContactInquiry::create([
            'user_id' => $user?->id,
            'name'    => $user?->name,
            'email'   => $user?->email,
            'phone'   => $user?->phone,
            'message' => $validated['message'],
        ]);

        // Fire both notification emails synchronously — never let email
        // failure break the inquiry submission itself.
        try {
            if (! empty($inquiry->email)) {
                Mail::to($inquiry->email, $inquiry->name)->send(new ContactInquiryReceivedMail($inquiry));
            }

            $superAdminEmail = config('app.super_admin_email');

            if (! empty($superAdminEmail)) {
                Mail::to($superAdminEmail)->send(new NewContactInquiryMail($inquiry));
            }
        } catch (\Throwable $e) {
            Log::error('[ContactInquiryController] Failed to send inquiry emails', [
                'inquiry_id' => $inquiry->id,
                'error' => $e->getMessage(),
            ]);
        }
        return response()->json(['success' => true, 'message' => 'Your message has been sent!']);
    }

    /**
     * Show a single inquiry and mark it as read.
     */
    public function show(ContactInquiry $contactInquiry): View
    {
        $contactInquiry->load('user.company');

        if (! $contactInquiry->is_read) {
            $contactInquiry->markRead();
        }

        return view('platform.inquiries.show', compact('contactInquiry'));
    }

    /**
     * Delete an inquiry permanently.
     */
    public function destroy(ContactInquiry $contactInquiry): RedirectResponse
    {
        $contactInquiry->delete();

        return redirect()->route('platform.inquiries.index')
            ->with('success', 'Inquiry deleted.');
    }
}
