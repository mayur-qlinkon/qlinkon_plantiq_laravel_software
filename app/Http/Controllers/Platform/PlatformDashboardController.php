<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Plan;

class PlatformDashboardController extends Controller
{
    public function index()
    {
        // 1. Total Companies
        $totalCompanies = Company::count();

        // 2. Active Subscriptions
       $activeSubscriptions = CompanySubscription::where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->count();

        // 3. Active Plans
        $activePlans = Plan::public()->where('is_active', true)->count();

        // 4. Monthly Revenue (Sum of Plan prices for currently active subscriptions)
        $monthlyRevenue = CompanySubscription::where('company_subscriptions.is_active', true)
            ->where('company_subscriptions.expires_at', '>', now())
            ->join('plans', 'company_subscriptions.plan_id', '=', 'plans.id')
            ->sum('plans.price');

        // 5. Recent Subscriptions (Used to populate the Recent Payments table)
        $recentSubscriptions = CompanySubscription::with(['company', 'plan'])
            ->latest()
            ->take(5)
            ->get();

        return view('platform.dashboard', compact(
            'totalCompanies',
            'activeSubscriptions',
            'activePlans',
            'monthlyRevenue',
            'recentSubscriptions'
        ));
    }
}
