<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AttributeValueController extends Controller
{
    public function store(Request $request, Attribute $attribute)
    {
        $validated = $request->validate([
            'value' => [
                'required', 'string', 'max:100',
                // Prevent duplicate values inside the SAME attribute (e.g., two "Red"s in "Color")
                Rule::unique('attribute_values', 'value')->where(fn ($query) => $query->where('attribute_id', $attribute->id)),
            ],
            'color_code' => ['nullable', 'string', 'max:20'],
        ]);

        // Create the value attached to this specific attribute
        $attribute->values()->create($validated);

        return redirect()->route('admin.attributes.index', ['active_id' => $attribute->id])
            ->with('success', 'Option added successfully.');
    }

    public function update(Request $request, AttributeValue $attributeValue)
    {
        $validated = $request->validate([
            'value' => [
                'required', 'string', 'max:100',
                Rule::unique('attribute_values', 'value')
                    ->where(fn ($query) => $query->where('attribute_id', $attributeValue->attribute_id))
                    ->ignore($attributeValue->id),
            ],
            'color_code' => ['nullable', 'string', 'max:20'],
        ]);

        $attributeValue->update($validated);

        return redirect()->route('admin.attributes.index', ['active_id' => $attributeValue->attribute_id])
            ->with('success', 'Option updated successfully.');
    }

    public function destroy(AttributeValue $attributeValue)
    {
        $attributeId = $attributeValue->attribute_id;
        $attributeValue->delete();

        return redirect()->route('admin.attributes.index', ['active_id' => $attributeId])
            ->with('success', 'Option removed.');
    }
}
