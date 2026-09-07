<?php

namespace App\Http\Controllers\Admin\Project;

use App\Enums\Project\BillingCycle;
use App\Enums\Project\ChargeStatus;
use App\Enums\Project\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Rules\TenantExists;
use App\Models\Client;
use App\Models\Payment;
use App\Models\Project\Project;
use App\Models\Project\ProjectService;
use App\Services\Project\ChargeAllocationService;
use App\Services\Project\ProjectChargeService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Projects are units of WORK. Nothing in this controller changes money, and
 * nothing about money changes a project's status — the two lifecycles are kept
 * apart deliberately. Charges and payments have their own controllers.
 */
class ProjectController extends Controller
{
    public function __construct(
        private readonly ChargeAllocationService $allocations,
        private readonly ProjectChargeService $charges,
    ) {}

    public function index(Request $request)
    {
        $query = Project::query()
            ->with('client:id,name,phone', 'owner:id,name')
            // Charge totals arrive as one aggregate per column rather than
            // loading every charge row for every project.
            ->withChargeTotals()
            ->forActiveStore()
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->filled('owner_id')) {
            $query->where('owner_id', $request->owner_id);
        }

        if ($request->filled('search')) {
            $term = trim($request->search);

            $query->where(function (Builder $q) use ($term) {
                $q->where('title', 'like', "%{$term}%")
                    ->orWhereHas('client', fn (Builder $c) => $c
                        ->where('name', 'like', "%{$term}%")
                        ->orWhere('phone', 'like', "%{$term}%"));
            });
        }

        // Payment status is derived from charges, never stored on the project.
        if ($request->filled('payment_status')) {
            $this->applyPaymentStatusFilter($query, $request->payment_status);
        }

        $projects = $query->paginate(50)->withQueryString();

        // gst_number is shown as a secondary line in the searchable dropdown,
        // matching the invoice create screen.
        $clients = Client::select('id', 'name', 'phone', 'gst_number')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Clients this store worked with most recently. Shown as suggestions
        // before the user types anything, because a new project is almost
        // always for someone already on the list.
        $recentClientIds = Project::query()
            ->forActiveStore()
            ->whereNotNull('client_id')
            ->orderByDesc('id')
            ->limit(60)
            ->pluck('client_id')
            ->unique()
            ->take(6)
            ->values();

        $statuses = ProjectStatus::options();

        return view('admin.projects.index', compact('projects', 'clients', 'recentClientIds', 'statuses'));
    }

    /**
     * A project has money outstanding when any of its charges do. Expressed as
     * an EXISTS subquery so it stays index-friendly as charges grow.
     */
    private function applyPaymentStatusFilter(Builder $query, string $status): void
    {
        match ($status) {
            'outstanding' => $query->whereHas(
                'charges',
                fn (Builder $q) => $q->whereIn('status', ChargeStatus::outstandingValues())
            ),
            'settled' => $query
                ->whereHas('charges')
                ->whereDoesntHave(
                    'charges',
                    fn (Builder $q) => $q->whereIn('status', ChargeStatus::outstandingValues())
                ),
            'unbilled' => $query->whereDoesntHave('charges'),
            default => null,
        };
    }

    public function show(Request $request, Project $project)
    {
        abort_if($project->company_id !== Auth::user()->company_id, 403);

        $project->load([
            'client:id,name,phone,email',
            'owner:id,name',
            'clientServices' => fn ($q) => $q->latest(),

            // Allocations are eager loaded so the screen can answer "which
            // payment settled this charge" without a query per card. Reversed
            // rows are kept: an audit trail is only useful if the undone
            // entries stay visible.
            'charges' => fn ($q) => $q->with([
                'clientService:id,name',
                'allocations' => fn ($a) => $a
                    ->with('payment:id,payment_number,payment_date,reference')
                    ->latest(),
            ])->latest('charge_date'),
        ]);

        // Cancelled charges stay in the list for the audit trail but are money
        // that was never owed, so they are excluded from every figure here.
        $countableCharges = $project->charges->whereIn(
            'status',
            ChargeStatus::countableValues()
        );

        $summary = [
            'charged'     => (float) $countableCharges->sum('total_amount'),
            'paid'        => (float) $countableCharges->sum('paid_amount'),
            'written_off' => (float) $countableCharges->sum('written_off_amount'),
        ];

        $summary['outstanding'] = round(
            $summary['charged'] - $summary['paid'] - $summary['written_off'],
            2
        );

        // Shown so an advance can be applied from this screen without hunting
        // for it on the client's ledger.
        $clientCredit = $this->allocations->clientCreditBalance($project->client_id);

        // Every project payment this client has made, with how much of each is
        // still unapplied. Client level rather than project level on purpose:
        // one payment can settle charges across several projects, so filtering
        // by project would hide money the user is looking for.
        $payments = Payment::query()
            ->where('party_type', 'customer')
            ->where('party_id', $project->client_id)
            ->where('type', 'received')
            ->where('status', 'completed')
            ->where('payment_for', 'project')
            ->with([
                'paymentMethod:id,label',
                'projectAllocations' => fn ($q) => $q->with('charge:id,title')->latest(),
            ])
            ->withSum(
                ['projectAllocations as allocated_amount' => fn ($q) => $q->where('is_reversed', false)],
                'amount'
            )
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->get()
            ->map(function (Payment $payment) {
                $allocated = round((float) ($payment->allocated_amount ?? 0), 2);

                return [
                    'id'             => $payment->id,
                    'payment_number' => $payment->payment_number,
                    'payment_date'   => optional($payment->payment_date)->toDateString(),
                    'reference'      => $payment->reference,
                    'method'         => $payment->paymentMethod?->label,
                    'amount'         => (float) $payment->amount,
                    'allocated'      => $allocated,
                    'available'      => round((float) $payment->amount - $allocated, 2),
                    'allocations'    => $payment->projectAllocations->map(fn ($a) => [
                        'id'          => $a->id,
                        'charge_id'   => $a->charge_id,
                        'charge'      => $a->charge?->title,
                        'amount'      => (float) $a->amount,
                        'kind'        => $a->kind->value,
                        'source'      => $a->source,
                        'is_reversed' => (bool) $a->is_reversed,
                    ])->values(),
                ];
            });

        // The service catalog drives the Assign Service picker. Without it the
        // screen could only create ad-hoc services, which left service_id null
        // on every row and made the catalog pointless.
        $catalog = ProjectService::active()
            ->orderBy('name')
            ->get(['id', 'name', 'service_type', 'billing_cycle', 'duration_days', 'price', 'tax_rate']);

        $cycles = BillingCycle::options();

        return view('admin.projects.show', compact(
            'project',
            'summary',
            'clientCredit',
            'catalog',
            'cycles',
            'payments'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id'         => ['required', TenantExists::make('clients', softDeletes: true)],
            'title'             => 'required|string|max:255',
            'description'       => 'nullable|string',
            'status'            => 'nullable|string|in:'.implode(',', array_column(ProjectStatus::cases(), 'value')),
            'start_date'        => 'nullable|date',
            'expected_end_date' => 'nullable|date|after_or_equal:start_date',
            'owner_id'          => ['nullable', TenantExists::make('users', softDeletes: true)],
            'quotation_id'      => 'nullable|integer',
            'invoice_id'        => 'nullable|integer',
            'notes'             => 'nullable|string',

            // Optional opening amount. Filling it raises the project's first
            // charge in the same step — the agreed price is what the user has
            // in mind when creating a project, and making them open a second
            // form to record it is the kind of friction that kills adoption.
            'amount'   => 'nullable|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'due_date' => 'nullable|date',
        ]);

        $validated['status'] ??= ProjectStatus::Draft->value;

        $amount   = (float) ($validated['amount'] ?? 0);
        $taxRate  = $validated['tax_rate'] ?? null;
        $dueDate  = $validated['due_date'] ?? null;

        unset($validated['amount'], $validated['tax_rate'], $validated['due_date']);

        // One transaction: a project created without the charge the user typed
        // an amount for would leave them thinking the money was recorded.
        $project = DB::transaction(function () use ($validated, $amount, $taxRate, $dueDate) {
            $project = Project::create($validated);

            if ($amount > 0) {
                $this->charges->create([
                    'company_id' => $project->company_id,
                    'store_id'   => $project->store_id,
                    'client_id'  => $project->client_id,
                    'project_id' => $project->id,
                    'title'      => $project->title,
                    'subtotal'   => $amount,
                    'tax_rate'   => $taxRate ?? 0,
                    'due_date'   => $dueDate,
                ]);
            }

            return $project;
        });

        return response()->json([
            'success'  => true,
            'message'  => $amount > 0
                ? 'Project created and the first charge has been raised.'
                : 'Project created successfully.',
            'project'  => $project->load('client:id,name'),
            'redirect' => route('admin.projects.show', $project),
        ]);
    }

    public function update(Request $request, Project $project)
    {
        abort_if($project->company_id !== Auth::user()->company_id, 403);

        $validated = $request->validate([
            'client_id'         => ['sometimes', 'required', TenantExists::make('clients', softDeletes: true)],
            'title'             => 'sometimes|required|string|max:255',
            'description'       => 'nullable|string',
            'start_date'        => 'nullable|date',
            'expected_end_date' => 'nullable|date|after_or_equal:start_date',
            'owner_id'          => ['nullable', TenantExists::make('users', softDeletes: true)],
            'quotation_id'      => 'nullable|integer',
            'invoice_id'        => 'nullable|integer',
            'notes'             => 'nullable|string',
        ]);

        $project->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Project updated successfully.',
            'project' => $project->fresh('client:id,name'),
        ]);
    }

    /**
     * Status is a human decision about delivery. It is exposed as its own
     * endpoint rather than folded into update() so it reads clearly in the
     * audit log — and so nothing else can change it as a side effect.
     */
    public function updateStatus(Request $request, Project $project)
    {
        abort_if($project->company_id !== Auth::user()->company_id, 403);

        $validated = $request->validate([
            'status' => 'required|string|in:'.implode(',', array_column(ProjectStatus::cases(), 'value')),
        ]);

        $status = ProjectStatus::from($validated['status']);

        $project->update([
            'status' => $status->value,
            // Stamped from the delivery decision only. A fully paid project
            // that is still in progress must not be marked complete.
            'completed_at' => $status === ProjectStatus::Completed
                ? ($project->completed_at ?? now()->toDateString())
                : null,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Project marked as {$status->label()}.",
            'status'  => $status->value,
        ]);
    }

    /**
     * Soft delete. Charges are left untouched — money already billed does not
     * disappear because someone tidied up the project list. The nullOnDelete
     * foreign key only detaches them if the row is ever purged.
     */
    public function destroy(Project $project)
    {
        abort_if($project->company_id !== Auth::user()->company_id, 403);

        $outstanding = $project->charges()
            ->whereIn('status', ChargeStatus::outstandingValues())
            ->exists();

        if ($outstanding) {
            return response()->json([
                'success' => false,
                'message' => 'This project has unpaid charges. Settle, write off or cancel them before deleting it.',
            ], 422);
        }

        try {
            $project->delete();

            return response()->json(['success' => true, 'message' => 'Project deleted.']);
        } catch (Exception $e) {
            Log::error('Project delete failed', ['project_id' => $project->id, 'error' => $e->getMessage()]);

            // The internal message goes to the log, not the browser. The delete
            // guard raises ProjectBillingException with a message written for
            // the user; anything reaching here is a genuine fault whose text
            // can name tables, IDs or file paths.
            return response()->json([
                'success' => false,
                'message' => config('app.debug')
                    ? 'Could not delete the project: '.$e->getMessage()
                    : 'Could not delete the project.',
            ], 422);
        }
    }
}