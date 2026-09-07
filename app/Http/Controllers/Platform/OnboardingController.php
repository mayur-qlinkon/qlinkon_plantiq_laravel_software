<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OnboardingController extends Controller
{
    public function index()
    {

        $user = Auth::user();
        if ($user->hasRole('super_admin')) {
            return redirect()->route('platform.dashboard');
        }

        return view('admin.onboarding.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:150',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
        ]);

        DB::transaction(function () use ($request) {
            // 1. Create the store (Tenantable trait handles company_id automatically!)
            $store = Store::create([
                'name' => $request->name,
                'phone' => $request->phone,
                'address' => $request->address,
                'city' => $request->city,
                'is_active' => true,
            ]);

            // 2. Assign the currently logged-in owner to this new store via the store_user pivot table
            auth()->user()->stores()->attach($store->id);

            // 3. Set this as their active session store
            session(['store_id' => $store->id]);
        });

        return redirect()->route('admin.dashboard')
            ->with('success', 'Your primary store has been created! Welcome to your dashboard.');
    }
}
