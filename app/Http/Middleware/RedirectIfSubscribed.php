<?php

namespace App\Http\Middleware;

use App\Models\CompanySubscription;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectIfSubscribed
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        // Agar user guest hai ya super admin hai (no company_id), toh normal chalne do
        if (! $user || ! $user->company_id) {
            return $next($request);
        }

        // Check karein ki kya company ka subscription abhi active hai
        // Same resolver as CheckSubscription and has_module(), so the three
        // never disagree about whether a subscription is live.
        $hasActiveSubscription = CompanySubscription::activeFor($user->company_id) !== null;

        // 🛑 AGAR SUBSCRIPTION ALREADY ACTIVE HAI TOH DASHBOARD PAR BHEJ DO
        if ($hasActiveSubscription) {
            return redirect()->route('admin.dashboard') // Apne dashboard ke exact route name se replace karein
                ->with('info', 'Your subscription is already active.');
        }

        return $next($request);
    }
}