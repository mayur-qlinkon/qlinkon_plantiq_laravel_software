<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Hrm\Holiday;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class HolidayController extends Controller
{
    public function index(Request $request)
    {
        $selectedYear  = (int) $request->input('year', now()->year);
        $selectedMonth = (int) $request->input('month', 0);
        $selectedType  = $request->input('type', '');

        // ── Paginated list (for List View) ──
        $holidays = Holiday::query()
            ->where(function ($q) use ($selectedYear) {
                $q->whereYear('date', $selectedYear)
                ->orWhere(function ($q2) use ($selectedYear) {
                    $q2->where('is_recurring', true)
                        ->whereYear('date', '<=', $selectedYear);
                });
            })
            ->when($selectedMonth > 0, fn ($q) => $q->whereMonth('date', $selectedMonth))
            ->when($selectedType, fn ($q) => $q->where('type', $selectedType))
            ->orderBy('date')
            ->paginate(25)
            ->withQueryString();
        $holidays->getCollection()->transform(function (Holiday $h) use ($selectedYear) {
            if ($h->is_recurring) {
                $h->date = $h->date->copy()->setYear($selectedYear);

                if ($h->end_date) {
                    $h->end_date = $h->end_date->copy()->setYear($selectedYear);
                }
            }

            return $h;
        });

        // ── Full collection for Calendar View (no pagination) ──
        $calendarHolidays = Holiday::query()
            ->where(function ($q) use ($selectedYear) {
                $q->whereYear('date', $selectedYear)
                ->orWhere('is_recurring', true);
            })
            ->when($selectedType, fn ($q) => $q->where('type', $selectedType))
            ->orderBy('date')
            ->get()
            ->map(function (Holiday $h) use ($selectedYear) {
                $date = $h->is_recurring 
                    ? $h->date->copy()->setYear($selectedYear) 
                    : $h->date->copy();
                $endDate = ($h->is_recurring && $h->end_date)
                    ? $h->end_date->copy()->setYear($selectedYear)
                    : $h->end_date?->copy();

                return [
                    'id'           => $h->id,
                    'name'         => $h->name,
                    'date'         => $date->toDateString(),
                    'end_date'     => $endDate?->toDateString(),
                    'type'         => $h->type,
                    'type_label'   => $h->type_label,
                    'type_color'   => $h->type_color,
                    'description'  => $h->description,
                    'is_paid'      => $h->is_paid,
                    'is_recurring' => $h->is_recurring,
                    'is_active'    => $h->is_active,
                    'total_days'   => $h->total_days,
                ];
            });
        // dd($calendarHolidays);
        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $calendarHolidays]);
        }

        $yearQuery = fn () => Holiday::where(function ($q) use ($selectedYear) {
            $q->whereYear('date', $selectedYear)->orWhere('is_recurring', true);
        });

        $stats = [
            'total'    => $yearQuery()->active()->count(),
            'upcoming' => Holiday::upcoming()->active()->count(),
            'national' => $yearQuery()->where('type', 'national')->count(),
            'company'  => $yearQuery()->where('type', 'company')->count(),
        ];

        return view('admin.hrm.holidays.index', compact(
            'holidays',
            'calendarHolidays',
            'stats',
            'selectedYear',
            'selectedMonth',
            'selectedType'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'                    => ['required', 'string', 'max:150'],
            'date'                    => ['required', 'date'],
            'end_date'                => ['nullable', 'date', 'after_or_equal:date'],
            'type'                    => ['required', Rule::in(['national', 'state', 'company', 'restricted', 'optional'])],
            'description'             => ['nullable', 'string'],
            'is_paid'                 => ['boolean'],
            'is_recurring'            => ['boolean'],
            'is_active'               => ['boolean'],
            'applicable_departments'  => ['nullable', 'array'],
            'applicable_stores'       => ['nullable', 'array'],
        ]);

        $validated['created_by'] = Auth::id();

        $holiday = Holiday::create($validated);

        return response()->json(['success' => true, 'message' => 'Holiday created.', 'data' => $holiday]);
    }

    public function update(Request $request, Holiday $holiday)
    {
        $validated = $request->validate([
            'name'                    => ['required', 'string', 'max:150'],
            'date'                    => ['required', 'date'],
            'end_date'                => ['nullable', 'date', 'after_or_equal:date'],
            'type'                    => ['required', Rule::in(['national', 'state', 'company', 'restricted', 'optional'])],
            'description'             => ['nullable', 'string'],
            'is_paid'                 => ['boolean'],
            'is_recurring'            => ['boolean'],
            'is_active'               => ['boolean'],
            'applicable_departments'  => ['nullable', 'array'],
            'applicable_stores'       => ['nullable', 'array'],
        ]);

        $holiday->update($validated);

        return response()->json(['success' => true, 'message' => 'Holiday updated.', 'data' => $holiday]);
    }

    public function destroy(Holiday $holiday)
    {
        $holiday->delete();

        return response()->json(['success' => true, 'message' => 'Holiday deleted.']);
    }
}