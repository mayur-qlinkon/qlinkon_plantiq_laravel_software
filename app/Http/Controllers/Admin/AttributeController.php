<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AttributeController extends Controller
{
    public function index(Request $request)
    {
        // 1. Fetch all attributes with the count of their values
        $attributes = Attribute::withCount('values')->latest()->get();
        $activeAttribute = null;

        if ($attributes->isNotEmpty()) {
            // 2. Get the active ID from the URL (?active_id=1), or default to the first one
            $activeId = $request->get('active_id', $attributes->first()->id);

            // 3. Load the active attribute and its values
            $activeAttribute = Attribute::with(['values' => function ($query) {
                $query->orderBy('id', 'asc'); // or position, if you add sorting later
            }])->find($activeId);
        }

        return view('admin.products.attributes', compact('attributes', 'activeAttribute'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required', 'string', 'max:50',
                Rule::unique('attributes')->where(fn ($query) => $query->where('company_id', Auth::user()->company_id)),
            ],
            'type' => ['required', 'in:text,color,button'],
        ]);

        $attribute = Attribute::create($validated);

        // Redirect back, automatically selecting the newly created attribute!
        return redirect()->route('admin.attributes.index', ['active_id' => $attribute->id])
            ->with('success', 'Attribute created successfully.');
    }

    public function update(Request $request, Attribute $attribute)
    {
        $validated = $request->validate([
            'name' => [
                'required', 'string', 'max:50',
                Rule::unique('attributes')
                    ->where(fn ($query) => $query->where('company_id', Auth::user()->company_id))
                    ->ignore($attribute->id),
            ],
            'type' => ['required', 'in:text,color,button'],
        ]);

        $attribute->update($validated);

        return back()->with('success', 'Attribute updated successfully.');
    }

    public function destroy(Attribute $attribute)
    {
        $attribute->delete();

        // Since it's deleted, strip the active_id from the URL so it loads the next available one
        return redirect()->route('admin.attributes.index')
            ->with('success', 'Attribute deleted successfully.');
    }
}
