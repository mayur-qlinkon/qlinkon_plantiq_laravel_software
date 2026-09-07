<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreExpenseRequest;
use App\Http\Requests\Admin\UpdateExpenseRequest;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\PaymentMethod;

use App\Services\ExpenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use InvalidArgumentException;

use Throwable;

class ExpenseController extends Controller
{
    public function __construct(protected ExpenseService $expenseService) {}

    // ════════════════════════════════════════════════════
    //  INDEX (Data Table)
    // ════════════════════════════════════════════════════
    public function index(Request $request): View
    {
        $storeIds = active_store_ids();

        $query = Expense::with(['category', 'user', 'approver', 'media'])
            // 'amount' is the value applied to the document. 'amount_received'
            // is POS cash tendered and includes change handed back.
            ->withSum(['payments as total_paid' => function ($q) {
                $q->where('status', 'completed');
            }], 'amount')
            ->when($storeIds, fn ($q) => $q->whereIn('store_id', $storeIds))
            ->latest('expense_date')
            ->latest('id');

        // Basic Filtering
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('category_id')) {
            $query->where('expense_category_id', $request->category_id);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('merchant_name', 'like', "%{$search}%")
                    ->orWhere('expense_number', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%");
            });
        }

        $expenses = $query->paginate(50)->withQueryString();

        // Fetch root categories with their children for a clean filter dropdown
        $categories = ExpenseCategory::active()->root()->with('children')->ordered()->get();

        return view('admin.expenses.index', compact('expenses', 'categories'));
    }

    // ════════════════════════════════════════════════════
    //  CREATE
    // ════════════════════════════════════════════════════
    public function create(): View
    {
        // Using the scope from your Model to build a grouped dropdown (Parent -> Child)
        $categories = ExpenseCategory::active()->root()->with('children')->ordered()->get();

        return view('admin.expenses.create', compact('categories'));
    }

    // ════════════════════════════════════════════════════
    //  STORE
    // ════════════════════════════════════════════════════
    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        try {
            $expense = $this->expenseService->store(
                data: $request->validated(),
                receipt: $request->file('receipt') // Spatie will handle this natively
            );

            return redirect()->route('admin.expenses.show', $expense->id)
                ->with('success', "Expense {$expense->expense_number} logged successfully.");

        } catch (Throwable $e) {
            return back()->withInput()->with('error', 'Failed to log expense. Please try again.');
        }
    }

    // ════════════════════════════════════════════════════
    //  SHOW (Detail View & Audit Trail)
    // ════════════════════════════════════════════════════
    public function show(Expense $expense): View
    {
        $expense->load([
            'category',
            'user',
            'approver',
            'media',
            'payments.paymentMethod',
        ])->loadSum([
            'payments as total_paid' => function ($q) {
                $q->where('status', 'completed');
            }
        ], 'amount');

        $paymentMethods = PaymentMethod::getForSelector();
        
        return view('admin.expenses.show', compact('expense', 'paymentMethods'));
    }

    // ════════════════════════════════════════════════════
    //  EDIT
    // ════════════════════════════════════════════════════
    public function edit(Expense $expense): View|RedirectResponse
    {
        // 🌟 GUARDRAIL: Do not allow editing of finalized financial records
        if (in_array($expense->status, ['approved', 'reimbursed'])) {
            return back()->with('error', 'Approved or Reimbursed expenses cannot be edited. Please reverse the status first.');
        }

        $categories = ExpenseCategory::active()->root()->with('children')->ordered()->get();

        return view('admin.expenses.edit', compact('expense', 'categories'));
    }

    // ════════════════════════════════════════════════════
    //  UPDATE
    // ════════════════════════════════════════════════════
    public function update(UpdateExpenseRequest $request, Expense $expense): RedirectResponse
    {
        // 🌟 GUARDRAIL: Double-check on POST to prevent API tampering
        if (in_array($expense->status, ['approved', 'reimbursed'])) {
            return back()->with('error', 'Financial lockdown: This expense is finalized and cannot be modified.');
        }

        try {
            $this->expenseService->update(
                expense: $expense,
                data: $request->validated(),
                receipt: $request->file('receipt')
            );

            return redirect()->route('admin.expenses.show', $expense->id)
                ->with('success', 'Expense updated successfully.');

        } catch (Throwable $e) {
            return back()->withInput()->with('error', 'Failed to update expense. Please try again.');
        }
    }

    // ════════════════════════════════════════════════════
    //  DESTROY
    // ════════════════════════════════════════════════════
    public function destroy(Expense $expense): RedirectResponse
    {
        if (in_array($expense->status, ['approved', 'reimbursed'])) {
            return back()->with('error', 'Cannot delete finalized expenses. Please cancel or reverse them instead.');
        }

        $expense->delete(); // Triggers SoftDeletes

        return redirect()->route('admin.expenses.index')
            ->with('success', 'Expense deleted successfully.');
    }

   // ════════════════════════════════════════════════════
    //  RECORD PAYMENT (AJAX)
    // ════════════════════════════════════════════════════
   public function addPayment(Request $request, $id): JsonResponse
    {
        // 🌟 FIX: Manually fetch the expense using the ID from the route
        $expense = Expense::findOrFail($id);

        // 🌟 GUARDRAIL: Block payments on unapproved records
        if (!in_array($expense->status, ['approved', 'reimbursed'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot record payments on unapproved or draft expenses.', 
            ], 422);
        }

        // Shape validation only. The due-balance ceiling cannot be enforced
        // here — the balance has to be read and the payment written under one
        // lock, or two concurrent posts both clear the same ceiling.
        $request->validate([
            'amount'            => ['required', 'numeric', 'min:0.01'],
            'payment_method_id' => [
                'required',
                'integer',
                // Scoped to this tenant. An unscoped integer let another
                // company's method id through, and a bad id surfaced as a raw
                // foreign-key error.
                Rule::exists('payment_methods', 'id')
                    ->where('company_id', $expense->company_id)
                    ->whereNull('deleted_at'),
            ],
            'payment_date'      => ['nullable', 'date'],
            'reference'         => ['nullable', 'string', 'max:255'],
            'notes'             => ['nullable', 'string', 'max:500'],
        ]);

        $lock = Cache::lock("expense_payment_{$expense->id}", 10);

        try {
            $result = $lock->block(5, function () use ($request, $expense) {
                // Read the balance fresh inside the lock.
                $paid = (float) $expense->payments()
                    ->where('status', 'completed')
                    ->sum('amount');

                $dueAmount = round(max(0, (float) $expense->total_amount - $paid), 2);

                if ($dueAmount <= 0) {
                    return ['error' => 'This expense is already fully paid.'];
                }

                if (round((float) $request->amount, 2) > $dueAmount) {
                    return ['error' => 'Amount exceeds the outstanding balance of ₹' . number_format($dueAmount, 2) . '.'];
                }

                $this->expenseService->recordPayment($expense, [
                    'amount'            => $request->amount,
                    'payment_method_id' => $request->payment_method_id,
                    'payment_date'      => $request->payment_date,
                    'reference'         => $request->reference,
                    'notes'             => $request->notes,
                    'store_id'          => $expense->store_id,
                ]);

                return ['error' => null];
            });

        } catch (LockTimeoutException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Another payment is being recorded against this expense. Please try again in a moment.',
            ], 429);

        } catch (Throwable $e) {
            Log::error('Expense Payment Error: ' . $e->getMessage(), [
                'expense_id'   => $expense->id,
                'request_data' => $request->except(['_token']),
                'trace'        => $e->getTraceAsString()
            ]);

            // The raw exception text stays in the log. It used to be returned
            // to the browser, exposing SQL and schema details.
            return response()->json([
                'success' => false,
                'message' => 'Could not record this payment. Please try again.',
            ], 500);
        }

        if ($result['error']) {
            return response()->json(['success' => false, 'message' => $result['error']], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment of ₹' . number_format($request->amount, 2) . ' recorded successfully.',
            'payment_status' => $expense->fresh()->payment_status,
        ]);
    }

    // ════════════════════════════════════════════════════
    //  SERVE RECEIPT  (GET /expenses/{expense}/receipt)
    // ════════════════════════════════════════════════════

    /**
     * Stream the receipt from the private disk after the tenant check that
     * route model binding already applied.
     */
    public function receipt(Expense $expense): StreamedResponse
    {
        $receipt = $expense->getFirstMedia('receipts');

        abort_if(! $receipt, 404);

        return $receipt->toInlineResponse(request());
    }
    // ════════════════════════════════════════════════════
    //  UPDATE STATUS (Workflow Engine via AJAX)
    // ════════════════════════════════════════════════════
    public function updateStatus(Request $request, Expense $expense): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:draft,pending_approval,approved,rejected,reimbursed',
        ]);

        try {
            $this->expenseService->updateStatus($expense, $request->status);

            return response()->json([
                'success' => true,
                'message' => 'Expense status updated to '.ucfirst(str_replace('_', ' ', $request->status)),
                'status' => $request->status,
            ]);

        } catch (InvalidArgumentException $e) {
            // Rejected transitions are user-facing rules, not failures.
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);

        } catch (Throwable $e) {
            Log::error('Expense status update failed', [
                'expense_id' => $expense->id,
                'new_status' => $request->status,
                'exception'  => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update status.',
            ], 500);
        }
    }
}
