<?php

namespace App\Http\Controllers\Admin\Production;

use App\Enums\Production\BatchSourceType;
use App\Enums\Production\BatchStatus;
use App\Enums\Production\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Production\StorePlantBatchRequest;

use App\Http\Requests\Admin\Production\StoreBatchAdjustmentRequest;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Production\PlantBatch;
use App\Services\Production\PlantBatchService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class PlantBatchController extends Controller
{
    public function __construct(
        protected PlantBatchService $batchService
    ) {}

    // ════════════════════════════════════════════════════
    //  INDEX
    // ════════════════════════════════════════════════════

    public function index(Request $request)
    {
        $batches = PlantBatch::with(['product', 'createdBy'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = '%' . $request->search . '%';
                $q->where(function ($query) use ($search) {
                    $query->where('batch_code', 'like', $search)
                          ->orWhereHas('product', fn($p) => $p->where('name', 'like', $search));
                });
            })
            ->when($request->filled('status'),      fn($q) => $q->byStatus($request->status))
            ->when($request->filled('source_type'), fn($q) => $q->bySource($request->source_type))
            ->when($request->filled('product_id'),  fn($q) => $q->byProduct((int) $request->product_id))
            ->latest('batch_start_datetime')
            ->paginate(20)
            ->withQueryString();

        $products    = Product::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $statusTypes = BatchStatus::cases();
        $sourceTypes = BatchSourceType::cases();

        return view('admin.production.plant-batches.index', compact(
            'batches', 'products', 'statusTypes', 'sourceTypes'
        ));
    }

    // ════════════════════════════════════════════════════
    //  CREATE
    // ════════════════════════════════════════════════════

    public function create(Request $request)
    {
        $prefill = [];

        // Source-assisted creation: ?source_type=production_plan&source_reference_id=5
        if ($request->filled('source_type') && $request->filled('source_reference_id')) {
            $sourceType = BatchSourceType::tryFrom($request->source_type);

            if ($sourceType?->isPrefilled()) {
                try {
                    $prefill = $this->batchService->prefillFromSource(
                        $sourceType,
                        (int) $request->source_reference_id,
                        Auth::user()->company_id
                    );
                } catch (InvalidArgumentException $e) {
                    // Service's own guard — safe to show ("A batch can only be
                    // created from a confirmed Production Plan.").
                    session()->flash('warning', 'Could not prefill from source: '.$e->getMessage());

                } catch (Throwable $e) {
                    // A bad id reaches findOrFail() and the raw message names
                    // the model class. Open a blank form instead.
                    Log::error('[PlantBatch] Prefill failed', ['error' => $e->getMessage()]);
                    session()->flash('warning', 'Could not prefill from the selected source. Please enter the details manually.');
                }
            }
        }

         $products    = Product::where('is_active', true)
            ->where('product_type', 'sellable')
            ->with(['skus' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        // SKU stock map — used client-side to auto-fill Initial Quantity
        // as soon as a plant/SKU is manually selected (editable, not locked).
        $skuIds = $products->flatMap(fn ($p) => $p->skus->pluck('id'));
        $stockBySku = ProductStock::whereIn('product_sku_id', $skuIds)
            ->selectRaw('product_sku_id, SUM(qty) as total_qty')
            ->groupBy('product_sku_id')
            ->pluck('total_qty', 'product_sku_id');

        // UI-only restriction: only Production Plan and Opening Stock are
        // offered on the create form for now. Purchase, Branch Transfer,
        // and Other remain fully valid in the enum/backend for existing
        // batches and future use — this filter does not touch them.
        $sourceTypes = array_filter(
            BatchSourceType::cases(),
            fn (BatchSourceType $type) => in_array($type, [BatchSourceType::ProductionPlan, BatchSourceType::OpeningStock], true)
        );

        return view('admin.production.plant-batches.create', compact('prefill', 'products', 'sourceTypes', 'stockBySku'));
    }

    // ════════════════════════════════════════════════════
    //  STORE
    // ════════════════════════════════════════════════════

    public function store(StorePlantBatchRequest $request)
    {
        try {
            $batch = $this->batchService->create(
                $request->validated(),
                Auth::user()->company_id,
                Auth::id()
            );

            Log::info('[PlantBatch] Created', [
                'batch_id'   => $batch->id,
                'batch_code' => $batch->batch_code,
                'by'         => Auth::id(),
            ]);

            return redirect()
                ->route('admin.production.plant-batches.show', $batch)
                ->with('success', "Batch {$batch->batch_code} created successfully.");

        } catch (InvalidArgumentException|RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());

        } catch (Throwable $e) {
            // This is where SQLSTATE[22003] "Numeric value out of range ...
            // SQL: insert into ..." was being printed to the user.
            Log::error('[PlantBatch] Create failed', ['error' => $e->getMessage()]);

            return back()->withInput()->with('error', 'Could not create this batch. Please check the values and try again.');
        }
    }

    // ════════════════════════════════════════════════════
    //  SHOW
    // ════════════════════════════════════════════════════

    public function show(PlantBatch $plantBatch)
    {
        // Base relations that are always needed
        $relationsToLoad = ['product', 'createdBy'];

        // Conditionally eager-load the nested source document
        if ($plantBatch->source_type === 'production_plan') {
            $relationsToLoad[] = 'productionPlanItem.plan.createdBy'; 
        } elseif ($plantBatch->source_type === 'purchase') {
            $relationsToLoad[] = 'purchaseItem.purchase.supplier'; 
        }

        $plantBatch->load($relationsToLoad);

        $allowedTransitions = array_map(
            fn(BatchStatus $s) => ['value' => $s->value, 'label' => $s->label()],
            $plantBatch->statusEnum()->transitions()
        );

        // ── Placement & Occupancy: full move history, latest first ──
        $placements = $plantBatch->placements()
            ->with(['growingSpace.site', 'growingSpace.zone', 'placedBy'])
            ->orderByDesc('placed_at')
            ->paginate(8, ['*'], 'placements_page');

        // ── Daily Activities: watering/fertilizer/pruning/inspection log ──
        $activities = $plantBatch->activities()
            ->with('performedBy')
            ->orderByDesc('performed_on')
            ->orderByDesc('id')
            ->paginate(10, ['*'], 'activities_page');

        // ── Loss & Harvest: two separate read-only ledgers ──
        $losses = $plantBatch->losses()
            ->with('recordedBy')
            ->orderByDesc('loss_date')
            ->paginate(10, ['*'], 'losses_page');

       $harvests = $plantBatch->harvests()
            ->with(['harvestedBy', 'warehouse'])
            ->orderByDesc('harvested_on')
            ->paginate(10, ['*'], 'harvests_page');

        $totalLost = (int) $plantBatch->losses()->sum('quantity_lost');
        $totalHarvested = (int) $plantBatch->harvests()->sum('quantity_harvested');

        // Current active placement (ended_at null) — feeds the Lifecycle Stage
        // sidebar's "Zone → Space" sub-label, and the Placement & Occupancy
        // card's "Currently Placed" block (incl. employees assigned to that zone).
        // Separate from the paginated $placements history above since that
        // can be on any page/order.
        $currentPlacement = $plantBatch->currentPlacement()
            ->with([
                'growingSpace.site',
                'growingSpace.zone.assignments' => fn ($q) => $q->active()->with('employee.user', 'employee.designation'),
            ])
            ->first();

        // ── Task Completion: today's stats + missed-task rollup ──
        // "Missed" is derived, not a stored status: a Pending task whose
        // due_date has already passed. No cron/backend job flips it.
        $today = now()->toDateString();

        $todayTaskCounts = $plantBatch->dailyTasks()
            ->whereDate('due_date', $today)
            ->selectRaw("count(*) as total, sum(case when status = ? then 1 else 0 end) as completed, sum(case when status = ? then 1 else 0 end) as pending", [
                TaskStatus::Done->value,
                TaskStatus::Pending->value,
            ])
            ->first();

        $missedCount = $plantBatch->dailyTasks()
            ->where('status', TaskStatus::Pending->value)
            ->whereDate('due_date', '<', $today)
            ->count();

        $taskStats = [
            'total' => (int) ($todayTaskCounts->total ?? 0),
            'completed' => (int) ($todayTaskCounts->completed ?? 0),
            'pending' => (int) ($todayTaskCounts->pending ?? 0),
            'missed' => $missedCount,
        ];

        // ── Last 7 days trend strip (oldest → today) ──
        $weekStart = now()->subDays(6)->startOfDay();
        $weekTasks = $plantBatch->dailyTasks()
            ->whereDate('due_date', '>=', $weekStart->toDateString())
            ->whereDate('due_date', '<=', $today)
            ->get(['due_date', 'status']);

        $taskWeek = collect(range(0, 6))->map(function (int $offset) use ($weekStart, $weekTasks, $today) {
            $date = $weekStart->copy()->addDays($offset);
            $dateString = $date->toDateString();
            $dayTasks = $weekTasks->filter(fn ($t) => $t->due_date->toDateString() === $dateString);

            $total = $dayTasks->count();
            $done = $dayTasks->where('status', TaskStatus::Pending)->count() === 0 && $total > 0
                ? $dayTasks->where('status', TaskStatus::Done)->count()
                : $dayTasks->where('status', TaskStatus::Done)->count();
            $isToday = $dateString === $today;
            $hasMissed = $dayTasks->contains(fn ($t) => $t->status === TaskStatus::Pending && !$isToday);
            $hasPending = $dayTasks->contains(fn ($t) => $t->status === TaskStatus::Pending && $isToday);

            $state = match (true) {
                $total === 0 => 'empty',
                $hasMissed => 'missed',
                $hasPending => 'pending',
                $done === $total => 'done',
                default => 'pending',
            };

            return [
                'label' => $date->format('D'),
                'state' => $state,
                'isToday' => $isToday,
            ];
        });

        // ── Open tasks list: due today + any still-pending overdue tasks ──
        $openTasks = $plantBatch->dailyTasks()
            ->with(['template', 'completedBy.user'])
            ->where(function ($q) use ($today) {
                $q->whereDate('due_date', $today)
                    ->orWhere(function ($q2) use ($today) {
                        $q2->where('status', TaskStatus::Pending->value)
                            ->whereDate('due_date', '<', $today);
                    });
            })
            ->orderBy('due_date')
            ->orderBy('id')
            ->get();

        return view('admin.production.plant-batches.show', compact(
            'plantBatch',
            'allowedTransitions',
            'placements',
            'activities',
            'losses',
            'harvests',
            'totalLost',
            'totalHarvested',
            'currentPlacement',
            'taskStats',
            'taskWeek',
            'openTasks'
        ));
    }

    // ════════════════════════════════════════════════════
    //  DOWNLOAD PDF — server-rendered plant batch summary
    //  GET /admin/production/plant-batches/{plantBatch}/pdf
    // ════════════════════════════════════════════════════

    public function downloadPdf(PlantBatch $plantBatch)
    {
        $relationsToLoad = ['product', 'sku', 'createdBy'];

        if ($plantBatch->source_type === 'production_plan') {
            $relationsToLoad[] = 'productionPlanItem.plan.createdBy';
        } elseif ($plantBatch->source_type === 'purchase') {
            $relationsToLoad[] = 'purchaseItem.purchase.supplier';
        }

        $plantBatch->load($relationsToLoad);

        // Fresh un-paginated queries: the show() page paginates these, a PDF must not.
        // Capped so a long-running batch does not produce a 40-page document.
        $placements = $plantBatch->placements()
            ->with(['growingSpace.site', 'growingSpace.zone', 'placedBy'])
            ->orderByDesc('placed_at')
            ->limit(25)
            ->get();

        $losses = $plantBatch->losses()
            ->with('recordedBy')
            ->orderByDesc('loss_date')
            ->limit(25)
            ->get();

        $harvests = $plantBatch->harvests()
            ->with(['harvestedBy', 'warehouse'])
            ->orderByDesc('harvested_on')
            ->limit(25)
            ->get();

        $totalLost = (int) $plantBatch->losses()->sum('quantity_lost');
        $totalHarvested = (int) $plantBatch->harvests()->sum('quantity_harvested');

        $currentPlacement = $plantBatch->currentPlacement()
            ->with(['growingSpace.site', 'growingSpace.zone'])
            ->first();

        $generatedAt = now()->format('d-M-Y h:i A');

        $pdf = Pdf::loadView('admin.production.plant-batches.pdf', compact(
            'plantBatch',
            'placements',
            'losses',
            'harvests',
            'totalLost',
            'totalHarvested',
            'currentPlacement',
            'generatedAt'
        ));

        $pdf->setPaper('A4', 'portrait');

        return $pdf->download('Plant-Batch-'.$plantBatch->batch_code.'.pdf');
    }

    // ════════════════════════════════════════════════════
    //  EDIT
    // ════════════════════════════════════════════════════

    public function edit(PlantBatch $plantBatch)
    {
        // product_id and initial_quantity are locked once any reduction has happened.
        $isLocked = !$plantBatch->isQuantityIntact();

        // Direct quantity correction is only offered for Opening Stock batches —
        // mirrors the same restriction adjustQuantity() enforces below.
        $isOpeningStockBatch = $plantBatch->source_type === BatchSourceType::OpeningStock->value;

        // Quantity adjustment ledger, paginated — full correction history for this batch.
        $adjustments = $plantBatch->adjustments()
            ->with('adjustedBy')
            ->latestFirst()
            ->paginate(10)
            ->withQueryString();

        return view('admin.production.plant-batches.edit', compact('plantBatch', 'isLocked', 'isOpeningStockBatch', 'adjustments'));
    }    

    // ════════════════════════════════════════════════════
    //  ADJUST QUANTITY — Opening Stock batches only. These skip the
    //  harvest/loss lifecycle entirely (sold directly), so instead of
    //  Report Harvest, the admin edits current_quantity directly here.
    //  Every change is recorded as a permanent, timestamped BatchAdjustment
    //  row — old/new quantity, note, and who made the change.
    // ════════════════════════════════════════════════════

    public function adjustQuantity(StoreBatchAdjustmentRequest $request, PlantBatch $plantBatch)
    {
        if ($plantBatch->source_type !== BatchSourceType::OpeningStock->value) {
            return back()->with('error', 'Direct quantity adjustment is only available for Opening Stock batches.');
        }

        try {
            $this->batchService->adjustQuantityWithLedger(
                $plantBatch,
                (int) $request->current_quantity,
                $request->notes,
                Auth::id()
            );

            Log::info('[PlantBatch] Quantity adjusted', [
                'batch_id' => $plantBatch->id,
                'by'       => Auth::id(),
            ]);

            return redirect()
                ->route('admin.production.plant-batches.show', $plantBatch)
                ->with('success', "Batch {$plantBatch->batch_code} quantity adjusted.");

        } catch (InvalidArgumentException|RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());

        } catch (Throwable $e) {
            Log::error('[PlantBatch] Quantity adjustment failed', [
                'batch_id' => $plantBatch->id,
                'error'    => $e->getMessage(),
            ]);

            return back()->withInput()->with('error', 'Could not adjust the quantity. Please try again.');
        }
    }

    // ════════════════════════════════════════════════════
    //  UPDATE STATUS — AJAX
    // ════════════════════════════════════════════════════

    public function updateStatus(Request $request, PlantBatch $plantBatch): JsonResponse
    {
        $request->validate([
            'status' => ['required', 'string', Rule::in(BatchStatus::values())],
        ]);

        try {
            $updated = $this->batchService->updateStatus(
                $plantBatch,
                BatchStatus::from($request->status)
            );

            Log::info('[PlantBatch] Status updated', [
                'batch_id'   => $plantBatch->id,
                'new_status' => $request->status,
                'by'         => Auth::id(),
            ]);

            return response()->json([
                'success'      => true,
                'message'      => "Batch {$updated->batch_code} is now {$updated->status_label}.",
                'status'       => $updated->status,
                'status_label' => $updated->status_label,
                'status_color' => $updated->status_color,
            ]);

        } catch (InvalidArgumentException|RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);

        } catch (Throwable $e) {
            Log::error('[PlantBatch] Status update failed', [
                'batch_id' => $plantBatch->id,
                'error'    => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Could not change the batch status. Please try again.',
            ], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  DESTROY — cancel + soft-delete
    // ════════════════════════════════════════════════════

    public function destroy(PlantBatch $plantBatch): JsonResponse
    {
        try {
            // Cancellation guard is inside the service
            $this->batchService->updateStatus($plantBatch, BatchStatus::Cancelled);
            $plantBatch->delete();

            Log::info('[PlantBatch] Cancelled and deleted', [
                'batch_id'   => $plantBatch->id,
                'batch_code' => $plantBatch->batch_code,
                'by'         => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => "Batch {$plantBatch->batch_code} has been cancelled.",
            ]);

        } catch (InvalidArgumentException|RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);

        } catch (Throwable $e) {
            Log::error('[PlantBatch] Cancel failed', [
                'batch_id' => $plantBatch->id,
                'error'    => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Could not cancel this batch. Please try again.',
            ], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  ACTIVE BATCHES — AJAX picker for the Layout canvas
    //  GET admin/production/plant-batches/active
    //  Returns all Active batches (with current placement if any)
    //  so the Layout canvas can render the "Place Batch" modal.
    // ════════════════════════════════════════════════════

    public function activeBatches(Request $request): JsonResponse
    {
        $companyId = Auth::user()->company_id;

        $batches = PlantBatch::with(['product', 'currentPlacement.growingSpace'])
            ->where('company_id', $companyId)
            ->active()
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = $request->q;
                $q->where(function ($sub) use ($term) {
                    $sub->where('batch_code', 'like', "%{$term}%")
                        ->orWhereHas('product', fn($p) => $p->where('name', 'like', "%{$term}%"));
                });
            })
            ->orderBy('batch_start_datetime', 'desc')
            ->orderBy('id', 'desc')
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'batches' => $batches->map(fn ($batch) => [
                'id'               => $batch->id,
                'batch_code'       => $batch->batch_code,
                'product_name'     => $batch->product?->name,
                'current_quantity' => $batch->current_quantity,
                // current_space: name of the space this batch is currently in (null if unplaced)
                'current_space'    => $batch->currentPlacement->first()?->growingSpace?->name,
            ])->values(),
        ]);
    }

   // ════════════════════════════════════════════════════
    //  SOURCE OPTIONS — AJAX search for the searchable source picker
    //  GET admin/production/plant-batches/source-options?source_type=production_plan&q=jade
    // ════════════════════════════════════════════════════

    // ════════════════════════════════════════════════════
    //  PLANT OPTIONS — AJAX search for the searchable plant picker
    //  GET admin/production/plant-batches/plant-options?q=jade
    // ════════════════════════════════════════════════════

    public function plantOptions(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $options = $this->batchService->searchPlantOptions(
            Auth::user()->company_id,
            $request->q
        );

        return response()->json(['options' => $options]);
    }

    public function sourceOptions(Request $request): JsonResponse
    {
        $request->validate([
            'source_type' => ['required', 'string', Rule::in(BatchSourceType::values())],
            'q'           => ['nullable', 'string', 'max:100'],
        ]);

        $sourceType = BatchSourceType::from($request->source_type);

        if (! $sourceType->isPrefilled()) {
            return response()->json(['options' => []]);
        }

        $options = $this->batchService->searchSourceOptions(
            $sourceType,
            Auth::user()->company_id,
            $request->q,
            5
        );

        return response()->json(['options' => $options]);
    }

    // ════════════════════════════════════════════════════
    //  PREFILL — AJAX endpoint for dynamic form prefill
    // ════════════════════════════════════════════════════

    public function prefill(Request $request): JsonResponse
    {
        $request->validate([
            'source_type'         => ['required', 'string', Rule::in(BatchSourceType::values())],
            'source_reference_id' => ['required', 'integer', 'min:1'],
        ]);

        $sourceType = BatchSourceType::from($request->source_type);

        if (!$sourceType->isPrefilled()) {
            return response()->json(['prefill' => []]);
        }

        try {
            $prefill = $this->batchService->prefillFromSource(
                $sourceType,
                (int) $request->source_reference_id,
                Auth::user()->company_id
            );

            return response()->json(['prefill' => $prefill]);

        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);

        } catch (Throwable $e) {
            // A bogus source_reference_id lands on findOrFail(), whose message
            // is "No query results for model [App\Models\PurchaseItem] 999999".
            Log::error('[PlantBatch] Prefill lookup failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Could not load details for the selected source.',
            ], 422);
        }
    }
}