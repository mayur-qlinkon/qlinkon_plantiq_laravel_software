<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Platform\PlantLibrary;
use App\Services\Platform\PlantLibraryImportService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PlantLibraryImportController extends Controller
{
    public function __construct(private PlantLibraryImportService $importService)
    {
    }

    /**
     * Company picker — step 1 of the import flow.
     */
    public function index(Request $request)
    {
        $companies = Company::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('name', 'like', '%'.$request->string('search').'%');
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('platform.plant-library.import.index', compact('companies'));
    }

    /**
     * Plant multi-select + per-plant type/price form — step 2, for one company.
     */
    public function select(Company $company)
    {
        $plants = PlantLibrary::with('media')->active()->ordered()->get();

        return view('platform.plant-library.import.select', compact('company', 'plants'));
    }

    /**
     * Runs the batch import for the selected plants into the chosen company.
     */
    public function store(Request $request, Company $company)
    {
        $validated = $request->validate([
            'plant_ids'                    => ['required', 'array', 'min:1'],
            'plant_ids.*'                  => ['integer', 'exists:plant_library,id'],
            'product_type'                 => ['required', 'array'],
            'product_type.*'               => [Rule::in(['catalog', 'sellable'])],
            'price'                        => ['nullable', 'array'],
            'price.*'                      => ['nullable', 'numeric', 'min:0'],
            'cost'                         => ['nullable', 'array'],
            'cost.*'                       => ['nullable', 'numeric', 'min:0'],
        ]);

        $overridesByPlantId = [];

        foreach ($validated['plant_ids'] as $plantId) {
            $type = $validated['product_type'][$plantId] ?? 'catalog';

            if ($type === 'sellable') {
                if (! isset($validated['price'][$plantId]) || ! isset($validated['cost'][$plantId])) {
                    return back()->withErrors([
                        'price' => "Price and cost are required for plant #{$plantId} because it's set to Sellable.",
                    ])->withInput();
                }
            }

            $overridesByPlantId[$plantId] = [
                'product_type' => $type,
                'price'        => $validated['price'][$plantId] ?? null,
                'cost'         => $validated['cost'][$plantId] ?? null,
            ];
        }

        $plants = PlantLibrary::with('media')
            ->whereIn('id', $validated['plant_ids'])
            ->get();

        $imported = $this->importService->importManyToCompany($plants, $company->id, $overridesByPlantId);

        return redirect()->route('platform.plant-library-import.select', $company)
            ->with('success', $imported->count().' plant(s) imported into '.$company->name.' successfully.');
    }
}