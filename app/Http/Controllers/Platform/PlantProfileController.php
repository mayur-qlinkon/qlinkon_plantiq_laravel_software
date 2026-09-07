<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;

use App\Models\Platform\PlantProfile;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PlantProfileController extends Controller
{
    /**
     * Display a listing of the plant profiles.
     */
    public function index()
    {
        $profiles = PlantProfile::ordered()->paginate(15);
        return view('platform.plant-profiles.index', compact('profiles'));
    }

    /**
     * Show the form for creating a new plant profile.
     */
    public function create()
    {
        return view('platform.plant-profiles.create');
    }

    /**
     * Store a newly created plant profile in storage.
     */
    public function store(Request $request)
    {
        $validated = $this->validateProfile($request);
        $validated['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('plant-profiles', 'public');
        }

        PlantProfile::create($validated);

        return redirect()->route('platform.plant-profiles.index')
            ->with('success', 'Plant Profile created successfully.');
    }

    /**
     * Show the form for editing the specified plant profile.
     */
    public function edit(PlantProfile $plantProfile)
    {
        return view('platform.plant-profiles.edit', compact('plantProfile'));
    }

    /**
     * Update the specified plant profile in storage.
     */
    public function update(Request $request, PlantProfile $plantProfile)
    {
        $validated = $this->validateProfile($request);
        $validated['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('image')) {
            if ($plantProfile->image) {
                Storage::disk('public')->delete($plantProfile->image);
            }
            $validated['image'] = $request->file('image')->store('plant-profiles', 'public');
        }

        $plantProfile->update($validated);

        return redirect()->route('platform.plant-profiles.index')
            ->with('success', 'Plant Profile updated successfully.');
    }

    /**
     * Remove the specified plant profile from storage.
     */
    public function destroy(PlantProfile $plantProfile)
    {
        // Safe deletion check can be added here if this profile is tied to existing active subscriptions
        $plantProfile->delete();

        return redirect()->route('platform.plant-profiles.index')
            ->with('success', 'Plant Profile deleted successfully.');
    }

    /**
     * Centralized validation rules.
     */
    private function validateProfile(Request $request): array
    {
        return $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'image'       => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'plant_limit' => ['required', 'integer', 'min:0'],
            'price'       => ['required', 'numeric', 'min:0'],
            'is_active'   => ['boolean'],
            'sort_order'  => ['nullable', 'integer'],
        ]);
    }
}