<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;

use App\Models\Platform\Addon;
use App\Models\Module;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AddonController extends Controller
{
    public function index()
    {
        $addons = Addon::withCount('modules')
            ->ordered()
            ->paginate(20);

        return view('platform.addons.index', compact('addons'));
    }

    public function create()
    {
        $modules = Module::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return view('platform.addons.create', compact('modules'));
    }

    public function store(Request $request)
    {
        if ($request->filled('slug')) {
            $request->merge(['slug' => $this->normalizeSlug($request->input('slug'))]);
        }

        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:150'],
            'slug'        => ['nullable', 'string', 'max:150', 'unique:addons,slug'],
            'description' => ['nullable', 'string'],
            'icon'        => ['nullable', 'string', 'max:100'],
            'price'       => ['required', 'numeric', 'min:0'],
            'sort_order'  => ['nullable', 'integer', 'min:0'],
            'is_active'   => ['boolean'],
            'modules'     => ['nullable', 'array'],
            'modules.*'   => ['integer', 'exists:modules,id'],
        ]);

        $validated['slug']      = filled($validated['slug'] ?? null)
            ? $validated['slug']
            : $this->uniqueSlug($validated['name']);
        $validated['is_active'] = $request->boolean('is_active', true);

        $addon = Addon::create($validated);

        if (! empty($validated['modules'])) {
            $addon->modules()->sync($validated['modules']);
        }

        return redirect()->route('platform.addons.index')
            ->with('success', 'Addon created successfully.');
    }

    public function edit(Addon $addon)
    {
        $modules        = Module::where('is_active', true)->orderBy('name')->get(['id', 'name', 'slug']);
        $assignedIds    = $addon->modules()->pluck('modules.id')->toArray();

        return view('platform.addons.edit', compact('addon', 'modules', 'assignedIds'));
    }

    public function update(Request $request, Addon $addon)
    {
        if ($request->filled('slug')) {
            $request->merge(['slug' => $this->normalizeSlug($request->input('slug'))]);
        }
        
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:150'],
            'slug'        => ['nullable', 'string', 'max:150', "unique:addons,slug,{$addon->id}"],
            'description' => ['nullable', 'string'],
            'icon'        => ['nullable', 'string', 'max:100'],
            'price'       => ['required', 'numeric', 'min:0'],
            'sort_order'  => ['nullable', 'integer', 'min:0'],
            'is_active'   => ['boolean'],
            'modules'     => ['nullable', 'array'],
            'modules.*'   => ['integer', 'exists:modules,id'],
        ]);

        $validated['slug']      = filled($validated['slug'] ?? null)
            ? $validated['slug']
            : $this->uniqueSlug($validated['name'], $addon->id);
        $validated['is_active'] = $request->boolean('is_active');

        $addon->update($validated);
        $addon->modules()->sync($validated['modules'] ?? []);

        return redirect()->route('platform.addons.index')
            ->with('success', 'Addon updated successfully.');
    }

    public function destroy(Addon $addon)
    {
        $addon->modules()->detach();
        $addon->delete();

        return redirect()->route('platform.addons.index')
            ->with('success', 'Addon deleted.');
    }

    // ── Helpers ───────────────────────────────────────────

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i    = 1;

        while (
            Addon::where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }

    private function normalizeSlug(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/\s+/', '-', $value);          // spaces -> hyphen
        $value = preg_replace('/[^a-z0-9_-]/', '', $value);    // strip invalid chars, KEEP underscore
        $value = preg_replace('/-+/', '-', $value);            // collapse multiple hyphens
        return trim($value, '-_');
    }
}