<?php

namespace App\Http\Controllers\Admin\Production;

use App\Http\Controllers\Controller;
use App\Models\Production\BatchPlacement;
use App\Models\Production\GrowingSpace;
use App\Models\Production\GrowingSpaceType;
use App\Models\Production\ProductionSite;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Read-only spatial view of the whole facility.
 *
 * The layout screens answer "what does my facility contain"; this one answers
 * "where is everything right now", which previously took three levels of
 * drilling to work out. Everything is loaded in one pass and filtered client
 * side — the tree is small enough that a round trip per filter would feel
 * slower than it is.
 */
class ProductionMapController extends Controller
{
    /** A batch sitting this long without moving is worth flagging. */
    private const STALE_DAYS = 180;

    public function index(): View
    {
        $companyId = Auth::user()->company_id;
        $activeStore = active_store();

        // store_id on a production site is an optional reporting link, not a
        // structural one — the schema states the spatial hierarchy is
        // deliberately independent of Store. Sites left unassigned (store_id
        // null, which is every site until a store is explicitly picked) must
        // stay visible under any active store, otherwise this screen renders
        // empty while Layout shows the exact same tree.
        $sites = ProductionSite::active()
            ->ordered()
            ->when($activeStore, fn ($q, $s) => $q->where(
                fn ($w) => $w->where('store_id', $s->id)->orWhereNull('store_id')
            ))
            ->with([
                'zones' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order'),
                'growingSpaces' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order'),
                'growingSpaces.type:id,name,capacity_unit,custom_unit_label',
            ])
            ->get();

        $spaces = GrowingSpace::active()
            ->whereIn('production_site_id', $sites->pluck('id'))
            ->with('type:id,name,capacity_unit,custom_unit_label')
            ->orderBy('sort_order')
            ->get();

        $spaceIds = $spaces->pluck('id')->all();

        $occupancy = $this->buildOccupancy($spaceIds, $companyId);
        $vacantSince = $this->buildVacantSince($spaceIds, $companyId);

        // Flattened cells keyed by space id — the Blade partial reads from this
        // rather than re-querying inside a recursive render.
        $cells = $spaces->mapWithKeys(fn (GrowingSpace $space) => [
            $space->id => $this->buildCell($space, $occupancy, $vacantSince),
        ]);

        return view('admin.production.map.index', [
            'sites' => $sites,
            'cells' => $cells,
            'stats' => $this->buildStats($cells),
            'species' => $cells->flatMap(fn ($c) => collect($c['batches'])->pluck('species'))
                ->unique()->sort()->values(),
        ]);
    }

    /**
     * Active batches per space, with everything a bin needs to render.
     *
     * Every batch is returned, not just the first — the point of this screen
     * is that nothing is hidden behind a "+2 more".
     */
    private function buildOccupancy(array $spaceIds, int $companyId): Collection
    {
        if (empty($spaceIds)) {
            return collect();
        }

        return BatchPlacement::query()
            ->whereIn('growing_space_id', $spaceIds)
            ->whereNull('ended_at')
            ->where('company_id', $companyId)
            ->with(['batch:id,batch_code,product_id,current_quantity,status,batch_start_datetime', 'batch.product:id,name'])
            ->orderBy('placed_at')
            ->get()
            ->filter(fn ($p) => $p->batch !== null)
            ->groupBy('growing_space_id');
    }

    /**
     * When each empty space was last vacated.
     *
     * Not stored anywhere — derived from the most recent ended placement, so a
     * space that has never held anything simply has no entry.
     */
    private function buildVacantSince(array $spaceIds, int $companyId): Collection
    {
        if (empty($spaceIds)) {
            return collect();
        }

        return BatchPlacement::query()
            ->whereIn('growing_space_id', $spaceIds)
            ->whereNotNull('ended_at')
            ->where('company_id', $companyId)
            ->selectRaw('growing_space_id, MAX(ended_at) as last_ended')
            ->groupBy('growing_space_id')
            ->pluck('last_ended', 'growing_space_id');
    }

    private function buildCell(GrowingSpace $space, Collection $occupancy, Collection $vacantSince): array
    {
        $placements = $occupancy->get($space->id, collect());
        $unit = $space->type?->capacity_unit;

        // Percentage and over-capacity only mean something when capacity and
        // quantity share a unit. An area bed measured in sqm holding 200 pots
        // cannot be expressed as a fill ratio.
        $isCountBased = in_array($unit, GrowingSpaceType::COUNT_BASED_UNITS, true);

        $occupied = (int) $placements->sum(fn ($p) => $p->batch->current_quantity ?? 0);
        $capacity = (float) $space->capacity;

        $batches = $placements->map(function ($p) {
            $days = $p->batch->batch_start_datetime
                ? (int) $p->batch->batch_start_datetime->diffInDays(now())
                : null;

            return [
                'id' => $p->batch->id,
                'code' => $p->batch->batch_code,
                'species' => $p->batch->product->name ?? 'Unknown',
                'qty' => (int) $p->batch->current_quantity,
                'age_days' => $days,
                'is_stale' => $days !== null && $days >= self::STALE_DAYS,
                'url' => route('admin.production.plant-batches.show', $p->batch->id),
            ];
        })->values()->all();

        $isEmpty = empty($batches);
        $pct = $isCountBased && $capacity > 0 ? (int) round(($occupied / $capacity) * 100) : null;

        $vacated = $vacantSince->get($space->id);

        return [
            'id' => $space->id,
            'zone_id' => $space->zone_id,
            'site_id' => $space->production_site_id,
            'code' => $space->name,
            'type' => $space->type?->name ?? 'Space',
            // Accessor, not a method — resolves the custom label when the type
            // uses a tenant-defined unit.
            'unit' => $space->type?->capacity_unit_label ?? $unit,
            'is_count_based' => $isCountBased,
            'capacity' => $capacity,
            'occupied' => $occupied,
            'pct' => $pct,
            'status' => match (true) {
                $isEmpty => 'empty',
                $isCountBased && $pct > 100 => 'over',
                $isCountBased && $pct >= 100 => 'full',
                $isCountBased => 'partial',
                default => 'full',
            },
            'batch_count' => count($batches),
            'batches' => $batches,
            'has_stale' => collect($batches)->contains('is_stale', true),
            'empty_days' => $isEmpty && $vacated
                ? (int) \Carbon\Carbon::parse($vacated)->diffInDays(now())
                : null,
            // Prebuilt so the client-side filter does no string work per keystroke.
            'search' => mb_strtolower(implode(' ', array_merge(
                [$space->name, $space->type?->name],
                collect($batches)->pluck('code')->all(),
                collect($batches)->pluck('species')->all(),
            ))),
        ];
    }

    private function buildStats(Collection $cells): array
    {
        $countBased = $cells->where('is_count_based', true);

        return [
            'total_spaces' => $cells->count(),
            'empty' => $cells->where('status', 'empty')->count(),
            'over' => $cells->where('status', 'over')->count(),
            'stale' => $cells->where('has_stale', true)->count(),
            'batches' => $cells->sum('batch_count'),
            'plants' => $cells->sum('occupied'),
            'capacity' => (int) $countBased->sum('capacity'),
            'util_pct' => $countBased->sum('capacity') > 0
                ? (int) round(($countBased->sum('occupied') / $countBased->sum('capacity')) * 100)
                : 0,
        ];
    }
}