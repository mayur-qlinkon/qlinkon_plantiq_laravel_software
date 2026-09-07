<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Company;
use App\Models\Order;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CustomerPortalController extends Controller
{
    /**
     * Dashboard Overview (Recent Orders & Stats)
     */
    public function index()
    {
        $company = tenant() ?? abort(404);
        $user = Auth::user();

        // Securely fetch orders belonging ONLY to this store AND this customer
        // We match by phone or email to catch orders they made before registering!
        $recentOrders = Order::where('company_id', $company->id)
            ->where(function ($query) use ($user) {
                if ($user->phone) {
                    $query->orWhere('customer_phone', $user->phone);
                }
                if ($user->email) {
                    $query->orWhere('customer_email', $user->email);
                }
            })
            ->with('items') // Assuming you have an items() relationship on Order
            ->latest()
            ->take(5)
            ->get();

        $totalOrdersCount = Order::where('company_id', $company->id)
            ->where(function ($query) use ($user) {
                if ($user->phone) {
                    $query->orWhere('customer_phone', $user->phone);
                }
                if ($user->email) {
                    $query->orWhere('customer_email', $user->email);
                }
            })->count();

        return view('customer.dashboard', compact('company', 'user', 'recentOrders', 'totalOrdersCount'));
    }

    /**
     * Full Order History Pagination
     */
    public function orders()
    {
        $company = tenant() ?? abort(404);
        $user = Auth::user();

        $orders = Order::where('company_id', $company->id)
            ->where(function ($query) use ($user) {
                if ($user->phone) {
                    $query->orWhere('customer_phone', $user->phone);
                }
                if ($user->email) {
                    $query->orWhere('customer_email', $user->email);
                }
            })
            ->with('items')
            ->latest()
            ->paginate(10);

        return view('customer.orders', compact('company', 'user', 'orders'));
    }

    /**
     * Show Address Form
     */
    public function addresses()
    {
        $company = tenant() ?? abort(404);
        $user = Auth::user();

        // Fetch their CRM profile
        $client = Client::where('company_id', $company->id)
            ->where('user_id', $user->id)
            ->first();

        // Fetch states for the dropdown (assuming you have a State model)
        $states = State::orderBy('name')->get();

        return view('customer.addresses', compact('company', 'user', 'client', 'states'));
    }

    /**
     * Store/Update Address (AJAX)
     */
    public function storeAddress(Request $request)
    {
        $company = tenant() ?? abort(404);
        $user = Auth::user();

        // 1. Validate the exact columns in your DB
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'address' => ['required', 'string', 'max:1000'],
            'city' => ['required', 'string', 'max:100'],
            'state_id' => ['required', 'integer', 'exists:states,id'],
            'zip_code' => ['required', 'string', 'max:20'],
            'country' => ['required', 'string', 'max:100'],
        ]);

        try {
            // 2. Update the CRM Client Profile
            $client = Client::where('company_id', $company->id)
                ->where('user_id', $user->id)
                ->first();

            if ($client) {
                $client->update($validated);
            }

            // 3. Keep the core User auth profile in sync
            $user->update([
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'address' => $validated['address'],
                'zip_code' => $validated['zip_code'],
                'country' => $validated['country'],
            ]);

            Log::info('[Storefront] Customer updated address', ['user_id' => $user->id]);

            return response()->json([
                'success' => true,
                'message' => 'Address saved successfully!',
            ]);

        } catch (\Throwable $e) {
            Log::error('[Storefront] Address update failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while saving your address.',
            ], 500);
        }
    }

    /**
     * Show Profile Form
     */
    public function profile()
    {
        $company = tenant() ?? abort(404);
        $user = Auth::user();

        return view('customer.profile', compact('company', 'user'));
    }

    /**
     * Update Profile (AJAX with Image Upload)
     */
    public function updateProfile(Request $request)
    {
        $company = tenant() ?? abort(404);
        $user = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'], // Max 2MB
        ]);

        try {
            DB::beginTransaction();

            // 1. Handle Avatar Upload
            if ($request->hasFile('avatar')) {
                // Delete old image if it exists to save space
                if ($user->image) {
                    Storage::disk('public')->delete($user->image);
                }
                $path = $request->file('avatar')->store('avatars/customers', 'public');
                $user->image = $path;
            }

            // 2. Update User Auth Data
            $user->name = $validated['name'];
            $user->phone = $validated['phone'];
            $user->save();

            // 3. Sync with CRM Client Profile
            $client = Client::where('company_id', $company->id)
                ->where('user_id', $user->id)
                ->first();
            if ($client) {
                $client->update([
                    'name' => $validated['name'],
                    'phone' => $validated['phone'],
                ]);
            }

            DB::commit();

            Log::info('[Storefront] Customer updated profile', ['user_id' => $user->id]);

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully!',
                'avatar_url' => $user->image ? asset('storage/'.$user->image) : null,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[Storefront] Profile update failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile. Please try again.',
            ], 500);
        }
    }
}
