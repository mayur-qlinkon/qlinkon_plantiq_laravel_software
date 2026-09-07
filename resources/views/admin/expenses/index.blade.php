@extends ('layouts.admin')

@section('title', 'Expenses')

@section('header-title')
    <div>
        <h1 class="text-sm font-bold tracking-widest text-gray-500 uppercase">Expenses</h1>
    </div>
@endsection

@push('styles')
    <style>
        [x-cloak] {
            display: none !important;
        }

        .filter-input {
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            padding: 8px 12px;
            font-size: 13px;
            color: #1f2937;
            outline: none;
            background: #fff;
            transition: border-color 150ms ease;
            height: 38px;
        }

        .filter-input:focus {
            border-color: var(--brand-600);
        }

        select.filter-input {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2.5' stroke-linecap='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 32px;
        }

        .table-row {
            transition: background 150ms ease;
        }

        .table-row:hover {
            background: #f8fafc;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }
    </style>
@endpush

@section('content')
    @php
        // Status color mapping
        $statusColors = [
            'draft' => ['bg' => '#f3f4f6', 'text' => '#4b5563', 'dot' => '#9ca3af'],
            'pending_approval' => ['bg' => '#fef3c7', 'text' => '#b45309', 'dot' => '#f59e0b'],
            'approved' => ['bg' => '#e0f2fe', 'text' => '#0369a1', 'dot' => '#0ea5e9'],
            'reimbursed' => ['bg' => '#dcfce3', 'text' => '#166534', 'dot' => '#22c55e'],
            'rejected' => ['bg' => '#fee2e2', 'text' => '#b91c1c', 'dot' => '#ef4444'],
        ];

        $paymentColors = [
            'unpaid' => ['bg' => '#fee2e2', 'text' => '#b91c1c'],
            'partial' => ['bg' => '#fef3c7', 'text' => '#b45309'],
            'paid' => ['bg' => '#dcfce3', 'text' => '#166534'],
        ];
    @endphp

    <div class="w-full pb-10" x-data="expensesIndex()">
        {{-- ════════ HEADER & ACTIONS ════════ --}}
        <div class="mb-5 flex flex-col items-start justify-between gap-4 lg:flex-row lg:items-center">
            @php
                // Map the hierarchical categories into a flat array structure for the custom select component
                $categoryOptions = [];
                foreach ($categories as $cat) {
                    $categoryOptions[$cat->id] = $cat->name;
                    foreach ($cat->children as $child) {
                        $categoryOptions[$child->id] = '— ' . $child->name;
                    }
                }
            @endphp

            {{-- Filters Form ── --}}
            <form id="expense-filter-form" method="GET" action="{{ route('admin.expenses.index') }}"
                class="flex w-full flex-1 flex-wrap items-center gap-3" @submit.prevent="submitForm" @change="submitForm">
                {{-- 1. Search Group (Input + Clear) --}}
                <div class="flex w-full max-w-md min-w-[250px] flex-1 flex-row items-center gap-2">
                    <div class="relative flex-1">
                        {{-- Stable flex inner container locking the search icon visually --}}
                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="Search merchant, invoice..." @input.debounce.400ms="submitForm"
                            class="filter-input w-full" />
                    </div>

                    <button type="button" id="expense-clear-btn" @click="clearFilters" x-show="hasActiveFilters" x-cloak
                        class="flex h-[38px] shrink-0 items-center justify-center gap-1.5 rounded-lg bg-red-50 px-3 text-sm font-bold text-red-500 transition-colors hover:bg-red-100"
                        title="Clear Filters">
                        <i data-lucide="x" class="h-3.5 w-3.5"></i> Clear
                    </button>
                </div>

                {{-- Status Custom Select --}}
                <div class="w-full shrink-0 sm:w-[160px]">
                    <x-custom-select name="status" placeholder="All Statuses" :options="[
                        'pending_approval' => 'Pending Approval',
                        'approved' => 'Approved',
                        'reimbursed' => 'Reimbursed',
                        'rejected' => 'Rejected',
                        'draft' => 'Draft',
                    ]"
                        selected="{{ request('status') }}" />
                </div>

                {{-- Category Custom Select --}}
                <div class="w-full shrink-0 sm:w-[190px]">
                    <x-custom-select name="category_id" placeholder="All Categories" :options="$categoryOptions"
                        selected="{{ request('category_id') }}" />
                </div>
            </form>

            {{-- Action Buttons ── --}}
            <div class="flex w-full flex-wrap items-center gap-2 sm:w-auto">
                {{-- Quick Access Links --}}
                <div class="flex shrink-0 items-center gap-1.5 rounded-lg border border-gray-200 bg-white p-1">
                    @if (has_permission('expense_categories.view'))
                        <a href="{{ route('admin.expense-categories.index') }}"
                            class="bg-brand-500 hover:bg-brand-600 inline-flex w-full items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-bold text-white transition-opacity sm:w-auto"
                            title="Manage Expense Categories">
                            <i data-lucide="layers" class="h-3.5 w-3.5"></i> Categories
                        </a>
                    @endif

                    {{-- Primary CTA --}}
                    @if (has_permission('expenses.create'))
                        <a href="{{ route('admin.expenses.create') }}"
                            class="bg-brand-500 hover:bg-brand-600 inline-flex w-full items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-bold text-white transition-opacity sm:w-auto">
                            <i data-lucide="plus" class="h-4 w-4"></i>
                            Log Expense
                        </a>
                    @endif
                </div>
            </div>
        </div>

        {{-- ════════ FLASH MESSAGES ════════ --}}
        @if (session('success'))
            <div class="mb-4 flex items-center gap-3 rounded-xl border border-green-200 bg-green-50 px-4 py-3">
                <i data-lucide="check-circle" class="h-4 w-4 flex-shrink-0 text-green-600"></i>
                <p class="text-sm font-semibold text-green-800">{{ session('success') }}</p>
            </div>
        @endif
        @if (session('error'))
            <div class="mb-4 flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3">
                <i data-lucide="alert-circle" class="h-4 w-4 flex-shrink-0 text-red-500"></i>
                <p class="text-sm font-semibold text-red-700">{{ session('error') }}</p>
            </div>
        @endif

        {{-- ════════ TABLE ════════ --}}
        <div id="expenses-list-container" class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm"
            @click="handlePaginationClick($event)">
            @if ($expenses->isEmpty())
                <div class="flex flex-col items-center justify-center py-20 text-center">
                    <div
                        class="mb-3 flex h-14 w-14 items-center justify-center rounded-2xl border border-gray-100 bg-gray-50">
                        <i data-lucide="indian-rupee" class="h-6 w-6 text-gray-300"></i>
                    </div>
                    <p class="mb-1 font-semibold text-gray-600">No expenses found</p>
                    <p class="mb-4 text-sm text-gray-400">
                        @if (request()->hasAny(['search', 'status', 'category_id']))
                            Try adjusting your filters to find what you're looking for.
                        @else
                            Start tracking your company expenditures by logging an expense.
                        @endif
                    </p>
                    @if (!request()->hasAny(['search', 'status', 'category_id']))
                        <a href="{{ route('admin.expenses.create') }}"
                            class="rounded-xl px-4 py-2 text-sm font-bold text-white shadow-sm hover:opacity-90"
                            style="background: var(--brand-600)">
                            Log First Expense
                        </a>
                    @endif
                </div>
            @else
                {{-- 🖥️ DESKTOP VIEW (TABLE) --}}
                <div class="hidden overflow-x-auto md:block">
                    <table class="w-full min-w-[950px] border-collapse text-left">
                        <thead>
                            <tr class="border-b border-gray-100 bg-gray-50/50">
                                <th
                                    class="w-[50px] px-5 py-3 text-[10px] font-black tracking-wider text-gray-400 uppercase">
                                    #
                                </th>
                                <th class="px-4 py-3 text-[10px] font-black tracking-wider text-gray-400 uppercase">
                                    Expense Details
                                </th>
                                <th class="px-4 py-3 text-[10px] font-black tracking-wider text-gray-400 uppercase">
                                    Category
                                </th>
                                <th
                                    class="px-4 py-3 text-right text-[10px] font-black tracking-wider text-gray-400 uppercase">
                                    Amount
                                </th>
                                <th
                                    class="px-4 py-3 text-center text-[10px] font-black tracking-wider text-gray-400 uppercase">
                                    Status
                                </th>
                                <th
                                    class="px-4 py-3 text-center text-[10px] font-black tracking-wider text-gray-400 uppercase">
                                    Payment Status
                                </th>
                                <th
                                    class="px-4 py-3 text-right text-[10px] font-black tracking-wider text-gray-400 uppercase">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach ($expenses as $expense)
                                @php
                                    $statusConf = $statusColors[$expense->status] ?? $statusColors['draft'];
                                    $payConf = $paymentColors[$expense->payment_status] ?? $paymentColors['unpaid'];
                                    $isFinalized = in_array($expense->status, ['approved', 'reimbursed']);
                                @endphp
                                <tr class="group table-row">
                                    {{-- # Numbering --}}
                                    <td class="px-5 py-3.5 text-[12px] font-bold text-gray-400">
                                        {{ $expenses->firstItem() + $loop->index }}
                                    </td>

                                    {{-- Details --}}
                                    <td class="px-3 py-3 sm:px-4 sm:py-3.5">
                                        <div class="flex items-start gap-3">
                                            <div class="mt-0.5 flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg"
                                                style="background: var(--brand-50)">
                                                <i data-lucide="indian-rupee" class="h-4 w-4"></i>
                                            </div>
                                            <div class="min-w-0">
                                                <a href="{{ route('admin.expenses.show', $expense->id) }}"
                                                    class="block text-[13px] font-bold break-words text-gray-900 hover:text-blue-600 hover:underline sm:truncate">
                                                    {{ $expense->merchant_name }}
                                                </a>
                                                <div
                                                    class="mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-[11px] font-medium text-gray-500">
                                                    <span>{{ $expense->expense_number }}</span>
                                                    <span class="h-1 w-1 rounded-full bg-gray-300"></span>
                                                    <span>{{ $expense->expense_date->format('d M Y') }}</span>
                                                </div>
                                                @if ($expense->merchant_gstin)
                                                    <p class="mt-0.5 text-[10px] text-gray-400">GSTIN:
                                                        {{ $expense->merchant_gstin }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Category --}}
                                    <td class="px-3 py-3 sm:px-4 sm:py-3.5">
                                        <span
                                            class="inline-flex items-center gap-1.5 rounded-md bg-gray-100 px-2 py-1 text-[11px] font-semibold text-gray-600">
                                            {{ $expense->category->name ?? 'Uncategorized' }}
                                        </span>
                                        @if ($expense->is_reimbursable)
                                            <p class="mt-1 text-[10px] font-bold text-[#b45309]">Reimbursable</p>
                                        @endif
                                    </td>

                                    {{-- Amount --}}
                                    <td class="px-3 py-3 text-right sm:px-4 sm:py-3.5">
                                        <p class="text-[14px] font-black whitespace-nowrap text-gray-900">
                                            &#8377;{{ number_format($expense->total_amount, 2) }}
                                        </p>
                                        {{-- @if ($expense->tax_amount > 0)
                                            <p class="text-[10px] font-semibold text-gray-400 mt-0.5"
                                                title="Base: {{ number_format($expense->base_amount, 2) }} + Taxes">
                                                Includes Tax
                                            </p>
                                        @else
                                            <p class="text-[10px] font-semibold text-gray-400 mt-0.5">No Tax</p>
                                        @endif --}}
                                    </td>

                                    {{-- Status --}}
                                    <td class="px-3 py-3 text-center sm:px-4 sm:py-3.5">
                                        <span class="status-badge whitespace-nowrap"
                                            style="background: {{ $statusConf['bg'] }}; color: {{ $statusConf['text'] }}">
                                            <span class="h-1.5 w-1.5 rounded-full"
                                                style="background: {{ $statusConf['dot'] }}"></span>
                                            {{ str_replace('_', ' ', $expense->status) }}
                                        </span>
                                    </td>

                                    {{-- Payment Status --}}
                                    <td class="px-3 py-3 text-center sm:px-4 sm:py-3.5">
                                        @if ($expense->payment_status === 'paid')
                                            <span class="badge bg-success">Paid</span>
                                        @elseif ($expense->payment_status === 'partial')
                                            <span class="badge bg-warning">Partial</span>
                                        @else
                                            <span class="badge bg-danger">Unpaid</span>
                                        @endif
                                    </td>

                                    {{-- Actions --}}
                                    <td class="px-3 py-3 text-right sm:px-4 sm:py-3.5">
                                        <div class="flex items-center justify-end gap-1">
                                            @if (has_permission('expenses.view'))
                                                <a href="{{ route('admin.expenses.show', $expense->id) }}"
                                                    class="flex h-7 w-7 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-blue-50 hover:text-blue-600"
                                                    title="View Details">
                                                    <i data-lucide="eye" class="h-4 w-4"></i>
                                                </a>
                                            @endif

                                            @if (!$isFinalized)
                                                @if (has_permission('expenses.update'))
                                                    <a href="{{ route('admin.expenses.edit', $expense->id) }}"
                                                        class="flex h-7 w-7 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-amber-50 hover:text-amber-600"
                                                        title="Edit">
                                                        <i data-lucide="edit" class="h-4 w-4"></i>
                                                    </a>
                                                @endif

                                                @if (has_permission('expenses.delete'))
                                                    <button type="button"
                                                        @click="deleteExpense({{ $expense->id }}, '{{ $expense->expense_number }}')"
                                                        class="flex h-7 w-7 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-red-50 hover:text-red-600"
                                                        title="Delete">
                                                        <i data-lucide="trash-2" class="h-4 w-4"></i>
                                                    </button>
                                                @endif
                                            @else
                                                {{-- Visual indicator that edit/delete is locked --}}
                                                <div class="flex h-7 w-7 cursor-not-allowed items-center justify-center rounded-lg text-gray-200"
                                                    title="Locked (Finalized)">
                                                    <i data-lucide="lock" class="h-3.5 w-3.5"></i>
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- 📱 MOBILE VIEW (CARDS) --}}
                <div class="divide-y divide-gray-50 border-t border-gray-50 md:hidden">
                    @foreach ($expenses as $expense)
                        @php
                            $statusConf = $statusColors[$expense->status] ?? $statusColors['draft'];
                            $payConf = $paymentColors[$expense->payment_status] ?? $paymentColors['unpaid'];
                            $isFinalized = in_array($expense->status, ['approved', 'reimbursed']);
                        @endphp
                        <div class="flex flex-col gap-3 p-4 transition-colors hover:bg-gray-50/50">
                            {{-- Header: Merchant & Amount --}}
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex min-w-0 items-start gap-3">
                                    <div class="mt-0.5 flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg"
                                        style="background: var(--brand-50)">
                                        <i data-lucide="indian-rupee" class="h-4 w-4"
                                            style="color: var(--brand-600)"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <a href="{{ route('admin.expenses.show', $expense->id) }}"
                                            class="block truncate text-[13px] font-bold text-gray-900 hover:text-blue-600 hover:underline">
                                            {{ $expense->merchant_name }}
                                        </a>
                                        <div class="mt-0.5 text-[11px] font-medium text-gray-500">
                                            {{ $expense->expense_number }} • {{ $expense->expense_date->format('d M Y') }}
                                        </div>
                                    </div>
                                </div>
                                <div class="shrink-0 text-right">
                                    <p class="text-[15px] font-black text-gray-900">{{ $expense->currency_code }}
                                        {{ number_format($expense->total_amount, 2) }}</p>
                                    @if ($expense->tax_amount > 0)
                                        <p class="mt-0.5 text-[9px] font-bold tracking-wider text-gray-400 uppercase">Incl.
                                            Tax</p>
                                    @endif
                                </div>
                            </div>

                            {{-- Badges: Category, Reimbursable, Status, Payment --}}
                            <div class="mt-1 flex flex-wrap items-center gap-2 border-t border-gray-100/50 pt-1">
                                <span
                                    class="inline-flex items-center rounded bg-gray-100 px-1.5 py-0.5 text-[10px] font-semibold text-gray-600">
                                    {{ $expense->category->name ?? 'Uncategorized' }}
                                </span>
                                @if ($expense->is_reimbursable)
                                    <span
                                        class="rounded border border-amber-100 bg-amber-50 px-1.5 py-0.5 text-[9px] font-bold tracking-wide text-[#b45309]">
                                        Reimbursable
                                    </span>
                                @endif
                                <span
                                    class="ml-auto inline-flex items-center gap-1 rounded border px-1.5 py-0.5 text-[9px] font-extrabold tracking-wider uppercase"
                                    style="background: {{ $statusConf['bg'] }}; color: {{ $statusConf['text'] }}; border-color: {{ $statusConf['bg'] }}">
                                    <span class="h-1.5 w-1.5 rounded-full"
                                        style="background: {{ $statusConf['dot'] }}"></span>
                                    {{ str_replace('_', ' ', $expense->status) }}
                                </span>
                                <span
                                    class="inline-flex items-center rounded px-1.5 py-0.5 text-[9px] font-extrabold tracking-wider uppercase"
                                    style="background: {{ $payConf['bg'] }}; color: {{ $payConf['text'] }}">
                                    {{ $expense->payment_status }}
                                </span>
                            </div>

                            {{-- Actions --}}
                            <div class="flex items-center justify-end gap-2 pt-1">
                                @if (has_permission('expenses.view'))
                                    <a href="{{ route('admin.expenses.show', $expense->id) }}"
                                        class="flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-gray-600 transition-colors hover:bg-blue-50 hover:text-blue-600"
                                        title="View Details">
                                        <i data-lucide="eye" class="h-4 w-4"></i>
                                    </a>
                                @endif

                                @if (!$isFinalized)
                                    @if (has_permission('expenses.update'))
                                        <a href="{{ route('admin.expenses.edit', $expense->id) }}"
                                            class="flex h-8 w-8 items-center justify-center rounded-lg border border-amber-200 text-amber-600 transition-colors hover:bg-amber-50"
                                            title="Edit">
                                            <i data-lucide="edit" class="h-4 w-4"></i>
                                        </a>
                                    @endif

                                    @if (has_permission('expenses.delete'))
                                        <button type="button"
                                            @click="deleteExpense({{ $expense->id }}, '{{ $expense->expense_number }}')"
                                            class="flex h-8 w-8 items-center justify-center rounded-lg border border-red-200 text-red-500 transition-colors hover:bg-red-50"
                                            title="Delete">
                                            <i data-lucide="trash-2" class="h-4 w-4"></i>
                                        </button>
                                    @endif
                                @else
                                    <div class="flex h-8 w-8 cursor-not-allowed items-center justify-center rounded-lg border border-gray-100 text-gray-300"
                                        title="Locked (Finalized)">
                                        <i data-lucide="lock" class="h-3.5 w-3.5"></i>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Pagination --}}
                @if ($expenses->hasPages())
                    <div
                        class="flex flex-col items-center justify-center gap-4 border-t border-gray-100 bg-white px-5 py-4 text-center sm:flex-row sm:justify-between sm:text-left">
                        <p class="text-[12px] font-medium text-gray-400">Showing <span
                                class="font-bold text-gray-600">{{ $expenses->firstItem() }}</span> to <span
                                class="font-bold text-gray-600">{{ $expenses->lastItem() }}</span> of <span
                                class="font-bold text-gray-600">{{ $expenses->total() }}</span> expenses</p>
                        <div>{{ $expenses->links('pagination::tailwind') }}</div>
                    </div>
                @endif
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function expensesIndex() {
            return {
                // SPA-Safe Search & Filter Logic
                hasActiveFilters: false,

                init() {
                    this.checkActiveFilters();
                    window.submitExpenseForm = () => this.submitForm();
                },

                handlePaginationClick(e) {
                    const pageLink = e.target.closest('a[href*="?page="]');
                    if (pageLink) {
                        e.preventDefault();
                        this.fetchResults(pageLink.href);
                    }
                },

                checkActiveFilters() {
                    const form = document.getElementById("expense-filter-form");
                    if (!form) return;
                    const formData = new FormData(form);
                    this.hasActiveFilters = [...formData.entries()].some(([, v]) => v && String(v).trim() !== "");
                },

                submitForm() {
                    const form = document.getElementById("expense-filter-form");
                    if (!form) return;
                    const url = new URL(form.action);
                    new FormData(form).forEach((v, k) => {
                        if (v) url.searchParams.set(k, v);
                    });

                    this.fetchResults(url.toString());
                },

                clearFilters() {
                    const form = document.getElementById("expense-filter-form");
                    if (form) {
                        form.querySelectorAll('input[type="text"], input[type="search"], select').forEach((el) => {
                            el.value = "";
                            // Dispatch change events so the custom select x-model resets back to placeholder labels
                            el.dispatchEvent(
                                new Event("change", {
                                    bubbles: true,
                                }),
                            );
                        });
                        this.fetchResults(form.action);
                    }
                },

                fetchResults(url) {
                    const targetContainer = document.getElementById("expenses-list-container");
                    if (!targetContainer) return;

                    targetContainer.style.opacity = "0.5";
                    targetContainer.style.pointerEvents = "none";

                    fetch(url, {
                            headers: {
                                "X-Requested-With": "XMLHttpRequest",
                            },
                        })
                        .then((res) => res.text())
                        .then((html) => {
                            const doc = new DOMParser().parseFromString(html, "text/html");
                            const newContainer = doc.getElementById("expenses-list-container");

                            if (newContainer) {
                                targetContainer.innerHTML = newContainer.innerHTML;
                            }

                            targetContainer.style.opacity = "1";
                            targetContainer.style.pointerEvents = "auto";
                            window.history.pushState({}, "", url);

                            this.checkActiveFilters();

                            if (typeof lucide !== "undefined") lucide.createIcons();
                        })
                        .catch(() => {
                            targetContainer.style.opacity = "1";
                            targetContainer.style.pointerEvents = "auto";
                        });
                },

                async deleteExpense(id, number) {
                    const confirmed = await Swal.fire({
                        title: "Delete Expense?",
                        text: `Are you sure you want to delete ${number}? This action cannot be undone.`,
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonText: "Yes, delete it",
                        cancelButtonText: "Cancel",
                        confirmButtonColor: "#ef4444",
                    });

                    if (confirmed.isConfirmed) {
                        // Create a dynamic form to submit the DELETE request
                        const form = document.createElement("form");
                        form.method = "POST";
                        form.action = `/admin/expenses/${id}`;

                        const csrfInput = document.createElement("input");
                        csrfInput.type = "hidden";
                        csrfInput.name = "_token";
                        csrfInput.value = document.querySelector('meta[name="csrf-token"]').content;

                        const methodInput = document.createElement("input");
                        methodInput.type = "hidden";
                        methodInput.name = "_method";
                        methodInput.value = "DELETE";

                        form.appendChild(csrfInput);
                        form.appendChild(methodInput);
                        document.body.appendChild(form);
                        form.submit();
                    }
                },
            };
        }
    </script>
@endpush
