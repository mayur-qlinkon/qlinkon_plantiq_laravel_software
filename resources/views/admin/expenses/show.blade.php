@extends('layouts.admin')

@section('title', $expense->merchant_name . ' - Expense Details')
@push('styles')
<style>[x-cloak] { display: none !important; }</style>
@endpush
@section('content')
    <div class="space-y-6 pb-10" x-data="expenseShow(@js($expense), @js(auth()->user()->can('expenses.approve')), @js(auth()->user()->can('expenses.reimburse')))">

        {{-- Header --}}
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <div class="flex items-center gap-3 flex-wrap">
                    <h1 class="text-2xl font-bold text-[#212538] tracking-tight">{{ $expense->merchant_name }}</h1>
                    <span class="badge" :class="{
                        'bg-amber-100 text-amber-800': status === 'pending_approval',
                        'bg-green-100 text-green-800': status === 'approved',
                        'bg-blue-100 text-blue-800': status === 'reimbursed',
                        'bg-red-100 text-red-800': status === 'rejected',
                        'bg-gray-100 text-gray-600': status === 'draft'
                    }" x-text="statusLabel"></span>
                </div>
                <p class="text-sm text-gray-500 mt-1 font-medium">
                    Logged on {{ $expense->created_at->format('M d, Y') }}
                    @if($expense->approved_at)
                        &nbsp;• Approved on {{ \Carbon\Carbon::parse($expense->approved_at)->format('M d, Y') }}
                    @endif
                </p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.expenses.index') }}"
                    class="bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 px-4 py-2 rounded-lg text-sm font-bold transition-colors shadow-sm flex items-center gap-2">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i> Back
                </a>
                @if(in_array($expense->status, ['draft', 'pending_approval']) && auth()->user()->can('expenses.edit'))
                    <a href="{{ route('admin.expenses.edit', $expense->id) }}"
                        class="bg-[#108c2a] hover:bg-[#0c6b1f] text-white px-4 py-2 rounded-lg text-sm font-bold shadow-sm flex items-center gap-2 transition-all">
                        <i data-lucide="edit" class="w-4 h-4"></i> Edit Expense
                    </a>
                @endif
                @if(in_array($expense->status, ['approved', 'reimbursed']) && $expense->due_amount > 0)
                    <button type="button" @click="$dispatch('open-quick-payment')"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-bold shadow-sm flex items-center gap-2 transition-all">
                        <i data-lucide="banknote" class="w-4 h-4"></i> Record Payment
                    </button>
                @endif
            </div>
        </div>

        {{-- ── Payment history table (example) ──────────────── --}}
        @if($expense->payments->count())
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm mb-6 overflow-hidden">
            <div class="px-5 py-3.5 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-gray-700">Payment History</h2>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-[11px] text-gray-400 uppercase tracking-wide">
                    <tr>
                        <th class="px-5 py-2.5 text-left font-medium">Date</th>
                        <th class="px-5 py-2.5 text-left font-medium">Method</th>
                        <th class="px-5 py-2.5 text-left font-medium">Reference</th>
                        <th class="px-5 py-2.5 text-right font-medium">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($expense->payments->where('status','completed') as $payment)
                    <tr class="hover:bg-gray-50/50 transition-colors">
                        <td class="px-5 py-3 text-gray-600">{{ $payment->payment_date?->format('d M Y') }}</td>
                        <td class="px-5 py-3 text-gray-600">{{ $payment->paymentMethod?->label ?? '—' }}</td>
                        <td class="px-5 py-3 text-gray-400 font-mono text-xs">{{ $payment->reference ?? '—' }}</td>
                        <td class="px-5 py-3 text-right font-semibold text-emerald-700 tabular-nums">₹{{ number_format($payment->amount, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        {{-- ══════════════════════════════════════════
            QUICK PAYMENT MODAL — Drop in once per page
            The trigger above dispatches 'open-quick-payment'
        ══════════════════════════════════════════ --}}
        <x-modals.quick-payment
            :action="route('admin.expenses.add.payment', $expense)"
            :due-amount="$expense->due_amount"
            :total-amount="$expense->total_amount"
            :paid-amount="$expense->total_paid"
            :payment-methods="$paymentMethods"
            currency="₹"
            title="Record Expense Payment"
            subtitle="This payment will be logged against {{ $expense->expense_number }}"
        />

        <div class="grid grid-cols-1 lg:grid-cols-5 xl:grid-cols-3 gap-6">

            {{-- Left Column: General Info & Receipt --}}
            <div class="lg:col-span-3 xl:col-span-2 space-y-6">

                {{-- General Information Card --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                        <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                            <i data-lucide="file-text" class="w-5 h-5 text-brand-500"></i> General Information
                        </h2>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-4 gap-x-8">
                            <div>
                                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Merchant / Vendor</p>
                                <p class="text-sm font-bold text-gray-800 mt-0.5">{{ $expense->merchant_name }}</p>
                            </div>
                            <div>
                                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Date of Expense</p>
                                <p class="text-sm font-bold text-gray-800 mt-0.5">{{ \Carbon\Carbon::parse($expense->expense_date)->format('d M, Y') }}</p>
                            </div>
                            <div>
                                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Reference / Invoice No.</p>
                                <p class="text-sm font-bold text-gray-800 mt-0.5 font-mono">{{ $expense->reference_number ?: '—' }}</p>
                            </div>
                            <div>
                                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Expense Category</p>
                                <p class="text-sm font-bold text-gray-800 mt-0.5">
                                    {{ $expense->category->name ?? 'N/A' }}
                                    @if($expense->category && $expense->category->parent)
                                        <span class="text-gray-400 text-xs">({{ $expense->category->parent->name }})</span>
                                    @endif
                                </p>
                            </div>
                            <div class="sm:col-span-2">
                                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Description / Notes</p>
                                <p class="text-sm text-gray-600 mt-0.5 leading-relaxed whitespace-pre-line">{{ $expense->notes ?: '—' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Receipt Card --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                        <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                            <i data-lucide="paperclip" class="w-5 h-5 text-brand-500"></i> Attached Receipt
                        </h2>
                    </div>
                    <div class="p-6">
                        @php $receipt = $expense->getFirstMedia('receipts'); @endphp
                        @if($receipt)
                            <div class="flex flex-col md:flex-row gap-6">
                                @if(str_starts_with($receipt->mime_type, 'image/'))
                                    <div class="flex-shrink-0">
                                        <img src="{{ $expense->receipt_url }}" alt="Receipt" class="max-h-64 rounded-lg border border-gray-200 shadow-sm object-contain bg-gray-50 p-2">
                                    </div>
                                @endif
                                <div class="flex-1">
                                    <div class="flex items-start justify-between gap-4 flex-wrap">
                                        <div>
                                            <p class="text-sm font-bold text-gray-800">{{ $receipt->file_name }}</p>
                                            <p class="text-xs text-gray-500">{{ number_format($receipt->size / 1024, 2) }} KB</p>
                                        </div>
                                        <a href="{{ $expense->receipt_url }}" target="_blank"
                                            class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-xs font-bold transition-colors flex items-center gap-2">
                                            <i data-lucide="external-link" class="w-3.5 h-3.5"></i> Open
                                        </a>
                                    </div>
                                    @if(!str_starts_with($receipt->mime_type, 'image/'))
                                        <div class="mt-4 p-4 bg-gray-50 rounded-lg border border-gray-200 text-center text-gray-500">
                                            <i data-lucide="file-text" class="w-8 h-8 mx-auto mb-1"></i>
                                            <p class="text-xs">PDF document attached</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @else
                            <div class="text-center py-8 border-2 border-dashed border-gray-100 rounded-lg">
                                <i data-lucide="image-off" class="w-8 h-8 text-gray-300 mx-auto mb-2"></i>
                                <p class="text-sm text-gray-400 italic">No receipt attached</p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- System Information Card --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                        <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                            <i data-lucide="clock" class="w-5 h-5 text-brand-500"></i> Audit Trail
                        </h2>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                            <div>
                                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Created By</p>
                                <p class="text-sm font-medium text-gray-800">{{ $expense->user?->name ?? 'System' }}</p>
                                <p class="text-xs text-gray-400">{{ $expense->created_at->format('d M, Y h:i A') }}</p>
                            </div>
                            <div>
                                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Last Updated</p>
                                <p class="text-sm font-medium text-gray-800">{{ $expense->updated_at->format('d M, Y h:i A') }}</p>
                            </div>
                            @if($expense->approved_by)
                                <div>
                                    <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Approved By</p>
                                    <p class="text-sm font-medium text-gray-800">{{ $expense->approver?->name ?? '—' }}</p>
                                    <p class="text-xs text-gray-400">{{ $expense->approved_at ? \Carbon\Carbon::parse($expense->approved_at)->format('d M, Y h:i A') : '' }}</p>
                                </div>
                            @endif
                        </div>                       
                    </div>
                </div>

            </div>

            {{-- Right Column: Financial Summary & Actions --}}
            <div class="lg:col-span-2 xl:col-span-1 space-y-6">

                {{-- Financial Summary Card --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                        <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                            <i data-lucide="calculator" class="w-5 h-5 text-brand-500"></i> Financial Summary
                        </h2>
                    </div>
                    <div class="p-4 sm:p-6 lg:p-4 xl:p-6 space-y-3">
                        <div class="flex justify-between items-center text-sm border-b border-gray-100 pb-2">
                            <span class="font-semibold text-gray-600">Base Amount</span>
                            <span class="font-bold text-gray-800">₹ {{ number_format($expense->base_amount, 2) }}</span>
                        </div>

                        @if($expense->tax_type === 'cgst_sgst' && $expense->cgst_amount)
                            <div class="flex justify-between items-center text-sm border-b border-gray-100 pb-2">
                                <span class="font-medium text-gray-500">CGST ({{ number_format($expense->tax_percent/2, 2) }}%)</span>
                                <span class="font-medium text-gray-700">+ ₹ {{ number_format($expense->cgst_amount, 2) }}</span>
                            </div>
                            <div class="flex justify-between items-center text-sm border-b border-gray-100 pb-2">
                                <span class="font-medium text-gray-500">SGST ({{ number_format($expense->tax_percent/2, 2) }}%)</span>
                                <span class="font-medium text-gray-700">+ ₹ {{ number_format($expense->sgst_amount, 2) }}</span>
                            </div>
                        @elseif($expense->tax_type === 'igst' && $expense->igst_amount)
                            <div class="flex justify-between items-center text-sm border-b border-gray-100 pb-2">
                                <span class="font-medium text-gray-500">IGST ({{ number_format($expense->tax_percent, 2) }}%)</span>
                                <span class="font-medium text-gray-700">+ ₹ {{ number_format($expense->igst_amount, 2) }}</span>
                            </div>
                        @endif

                        <div class="flex justify-between items-center text-sm border-b border-gray-100 pb-2">
                            <span class="font-medium text-gray-500">Total Tax</span>
                            <span class="font-medium text-gray-700">₹ {{ number_format($expense->tax_amount, 2) }}</span>
                        </div>

                        <div class="flex justify-between items-center text-sm border-b border-gray-100 pb-2">
                            <span class="font-medium text-gray-500">Round Off</span>
                            <span class="font-medium text-gray-700">{{ $expense->round_off >= 0 ? '+' : '' }} ₹ {{ number_format($expense->round_off, 2) }}</span>
                        </div>

                        <div class="flex justify-between items-end pt-2">
                            <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Total Amount</span>
                            <span class="text-2xl font-black text-brand-600">₹ {{ number_format($expense->total_amount, 2) }}</span>
                        </div>
                        <div class="flex justify-between items-end pt-4 border-t border-gray-100 mt-2">
                            <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Total Paid</span>
                            <span class="text-lg font-bold text-green-600">₹ {{ number_format($expense->total_paid, 2) }}</span>
                        </div>
                        <div class="flex justify-between items-end pt-2">
                            <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Amount Due</span>
                            <span class="text-lg font-bold text-red-500">₹ {{ number_format($expense->due_amount, 2) }}</span>
                        </div>
                    </div>
                </div>

                {{-- Actions Card (conditional) --}}
                @php
                    $canApprove = has_permission('expenses.approve');
                    $canReimburse = has_permission('expenses.reimburse');
                @endphp

                @if(($expense->status === 'pending_approval' && $canApprove) || ($expense->status === 'approved' && $canReimburse))
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                            <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                                <i data-lucide="settings" class="w-5 h-5 text-brand-500"></i> Workflow Actions
                            </h2>
                        </div>
                        <div class="p-4 sm:p-6 lg:p-4 xl:p-6 space-y-3">
                            @if($expense->status === 'pending_approval' && $canApprove)
                                <div class="flex flex-col xl:flex-row gap-3">                                 
                                    <button @click="updateStatus('approved')"
                                        class="flex-1 bg-green-600 hover:bg-green-700 text-white px-3 py-2.5 rounded-lg text-sm font-bold shadow-sm transition-all flex items-center justify-center gap-2 whitespace-nowrap">
                                        <i data-lucide="check-circle" class="w-4 h-4"></i> Approve
                                    </button>                                    

                                    @if(has_permission('expenses.reject'))
                                    <button @click="updateStatus('rejected')"
                                        class="flex-1 bg-red-600 hover:bg-red-700 text-white px-3 py-2.5 rounded-lg text-sm font-bold shadow-sm transition-all flex items-center justify-center gap-2 whitespace-nowrap">
                                        <i data-lucide="x-circle" class="w-4 h-4"></i> Reject
                                    </button>
                                    @endif
                                </div>
                            @endif

                            @if($expense->status === 'approved' && $canReimburse)
                                <button @click="updateStatus('reimbursed')"
                                    class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-lg text-sm font-bold shadow-sm transition-all flex items-center justify-center gap-2">
                                    <i data-lucide="banknote" class="w-4 h-4"></i> Mark as Reimbursed
                                </button>
                            @endif
                        </div>
                    </div>
                @endif

            </div>
        </div>



        {{-- 🌟 AUDIT TRAIL DETAILS MODAL --}}
        <div x-show="isAuditModalOpen" style="display: none;" class="relative z-[100]" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div x-show="isAuditModalOpen" x-transition.opacity class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity"></div>
            
            <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                    
                    <div x-show="isAuditModalOpen" x-transition.scale.95 @click.away="isAuditModalOpen = false" 
                        class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-gray-100 flex flex-col max-h-[85vh]">
                        
                        {{-- Modal Header --}}
                        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center sticky top-0">
                            <h3 class="text-lg font-bold text-gray-800 capitalize" x-text="currentAudit.title + ' Details'"></h3>
                            <button type="button" @click="isAuditModalOpen = false" class="text-gray-400 hover:text-red-500 bg-gray-100 hover:bg-red-50 rounded-lg p-1.5 transition-colors">
                                <i data-lucide="x" class="w-4 h-4"></i>
                            </button>
                        </div>
                        
                        {{-- Modal Body (Scrollable) --}}
                        <div class="p-6 overflow-y-auto flex-1 custom-scrollbar">
                            <div class="space-y-4">
                                <template x-for="change in currentAudit.changes" :key="change.field">
                                    <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm">
                                        <p class="text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-2" x-text="change.field"></p>
                                        
                                        <div class="flex items-center gap-3 text-sm">
                                            {{-- Old Value --}}
                                            <div class="flex-1 bg-red-50 border border-red-100 rounded-lg p-2.5 text-red-700 line-through break-all" x-text="change.old"></div>
                                            
                                            {{-- Arrow --}}
                                            <div class="shrink-0 text-gray-300">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                            </div>
                                            
                                            {{-- New Value --}}
                                            <div class="flex-1 bg-green-50 border border-green-100 rounded-lg p-2.5 text-green-800 font-bold break-all" x-text="change.new"></div>
                                        </div>
                                    </div>
                                </template>

                                <template x-if="currentAudit.changes.length === 0">
                                    <div class="text-center py-8">
                                        <p class="text-sm text-gray-500 font-medium">No specific field changes recorded for this event.</p>
                                    </div>
                                </template>
                            </div>
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>        

    </div>
    
@endsection

@push('scripts')
    <script>
        function expenseShow(expense, canApprove, canReimburse) {
            return {
                status: expense.status,
                statusLabel: '',
                // --- NEW AUDIT MODAL STATE ---                
                isAuditModalOpen: false,
                currentAudit: { title: '', changes: [] },                

                openAuditModal(description, properties) {
                    this.currentAudit.title = description;
                    this.currentAudit.changes = [];

                    // Spatie formats updates with 'attributes' (new) and 'old'
                    const newValues = properties.attributes || properties;
                    const oldValues = properties.old || {};

                    for (const key in newValues) {
                        if (key === 'updated_at') continue; // Skip boring timestamps

                        this.currentAudit.changes.push({
                            // Format snake_case to Title Case (e.g., 'base_amount' -> 'Base Amount')
                            field: key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()), 
                            old: oldValues[key] !== undefined && oldValues[key] !== null ? oldValues[key] : '—',
                            new: newValues[key] !== undefined && newValues[key] !== null ? newValues[key] : '—'
                        });
                    }

                    this.isAuditModalOpen = true;
                },
                // --- END NEW STATE ---
                init() {
                    this.updateStatusLabel();
                },
                updateStatusLabel() {
                    const statusMap = {
                        draft: 'Draft',
                        pending_approval: 'Pending Approval',
                        approved: 'Approved',
                        rejected: 'Rejected',
                        reimbursed: 'Reimbursed'
                    };
                    this.statusLabel = statusMap[this.status] || this.status;
                },
                async updateStatus(newStatus) {
                    let confirmMessage = '';
                    let actionLabel = '';
                    switch (newStatus) {
                        case 'approved':
                            confirmMessage = 'Approve this expense? It will be locked for editing.';
                            actionLabel = 'Approve';
                            break;
                        case 'rejected':
                            confirmMessage = 'Reject this expense? This action can be reversed later.';
                            actionLabel = 'Reject';
                            break;
                        case 'reimbursed':
                            confirmMessage = 'Mark as reimbursed? This action is final.';
                            actionLabel = 'Reimburse';
                            break;
                        default:
                            return;
                    }

                    const result = await BizAlert.confirm(confirmMessage, actionLabel);
                    if (!result.isConfirmed) return;

                    BizAlert.loading('Updating status...');

                    try {
                        const response = await fetch(`{{ route('admin.expenses.status.update', $expense->id) }}`, {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ status: newStatus })
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.status = data.status;
                            this.updateStatusLabel();
                            BizAlert.toast(data.message, 'success');
                            // Refresh page after 1 second to reflect changes
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            BizAlert.toast(data.message || 'Failed to update status.', 'error');
                        }
                    } catch (error) {
                        console.error(error);
                        BizAlert.toast('Network error. Please try again.', 'error');
                    }
                },                               
                
            };
            
        }
    </script>
@endpush