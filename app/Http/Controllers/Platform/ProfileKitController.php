<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Platform\ProfileKit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileKitController extends Controller
{
    /**
     * Display a listing of the profile kits.
     */
    public function index()
    {
        $kits = ProfileKit::ordered()->paginate(15);
        return view('platform.profile-kits.index', compact('kits'));
    }

    /**
     * Show the form for creating a new profile kit.
     */
    public function create()
    {
        return view('platform.profile-kits.create');
    }

    /**
     * Store a newly created profile kit in storage.
     */
    public function store(Request $request)
    {
        $validated = $this->validateKit($request);
        $validated['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('profile-kits', 'public');
        }

        ProfileKit::create($validated);

        return redirect()->route('platform.profile-kits.index')
            ->with('success', 'Profile Kit created successfully.');
    }

    /**
     * Show the form for editing the specified profile kit.
     */
    public function edit(ProfileKit $profileKit)
    {
        return view('platform.profile-kits.edit', compact('profileKit'));
    }

    /**
     * Update the specified profile kit in storage.
     */
    public function update(Request $request, ProfileKit $profileKit)
    {
        $validated = $this->validateKit($request);
        $validated['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('image')) {
            if ($profileKit->image) {
                Storage::disk('public')->delete($profileKit->image);
            }
            $validated['image'] = $request->file('image')->store('profile-kits', 'public');
        }

        $profileKit->update($validated);

        return redirect()->route('platform.profile-kits.index')
            ->with('success', 'Profile Kit updated successfully.');
    }

    /**
     * Remove the specified profile kit from storage.
     */
    public function destroy(ProfileKit $profileKit)
    {
        $profileKit->delete();

        return redirect()->route('platform.profile-kits.index')
            ->with('success', 'Profile Kit deleted successfully.');
    }

    /**
     * Centralized validation rules.
     */
    private function validateKit(Request $request): array
    {
        return $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'image'       => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'description' => ['nullable', 'string', 'max:1000'],
            'price'       => ['required', 'numeric', 'min:0'],
            'is_active'   => ['boolean'],
            'sort_order'  => ['nullable', 'integer'],
        ]);
    }
}