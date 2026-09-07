<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Services\CrmPerformanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class CrmPerformanceController extends Controller
{
    public function __construct(
        private readonly CrmPerformanceService $performance,
    ) {}

    /** Named windows the UI offers. Custom falls back to the from/to inputs. */
    private const PRESETS = ['today', 'week', 'month', 'quarter', 'custom'];

    public function index(Request $request)
    {
        $companyId = Auth::user()->company_id;

        $preset = in_array($request->input('preset'), self::PRESETS, true)
            ? $request->input('preset')
            : 'month';

        [$from, $to] = $this->resolveWindow($preset, $request);

        $rows = $this->performance->teamSummary($companyId, $from, $to);

        // Column totals, so the manager can see team output without adding up
        // by eye. Averages are deliberately absent — one strong performer makes
        // a team average meaningless.
        $totals = [
            'effort'          => $rows->sum('effort'),
            'calls'           => $rows->sum('calls'),
            'stage_moves'     => $rows->sum('stage_moves'),
            'conversions'     => $rows->sum('conversions'),
            'tasks_completed' => $rows->sum('tasks_completed'),
            'tasks_overdue'   => $rows->sum('tasks_overdue'),
            'stale_leads'     => $rows->sum('stale_leads'),
        ];

        $staleDays = CrmPerformanceService::STALE_DAYS;

        return view(
            'admin.crm.performance.index',
            compact('rows', 'totals', 'preset', 'from', 'to', 'staleDays')
        );
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolveWindow(string $preset, Request $request): array
    {
        $today = Carbon::today();

        if ($preset === 'custom') {
            $from = $request->filled('from')
                ? Carbon::parse($request->input('from'))->startOfDay()
                : $today->copy()->startOfMonth();

            $to = $request->filled('to')
                ? Carbon::parse($request->input('to'))->endOfDay()
                : $today->copy()->endOfDay();

            // A backwards range silently returns nothing, which reads as "this
            // person did no work" rather than "you typed the dates the wrong way".
            return $from->lte($to) ? [$from, $to] : [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return match ($preset) {
            'today'   => [$today->copy()->startOfDay(), $today->copy()->endOfDay()],
            'week'    => [$today->copy()->startOfWeek(), $today->copy()->endOfDay()],
            'quarter' => [$today->copy()->startOfQuarter(), $today->copy()->endOfDay()],
            default   => [$today->copy()->startOfMonth(), $today->copy()->endOfDay()],
        };
    }
}