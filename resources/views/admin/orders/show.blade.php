@extends('layouts.admin')

@section('title', 'Order #' . $order->order_number)

@section('header-title')
    <div>
        <h1 class="text-sm font-bold text-gray-500 uppercase tracking-widest">Order #{{ $order->order_number }}</h1>
        <p class="text-xs text-gray-400 font-medium mt-0.5">{{ $order->created_at->format('d M Y, h:i A') }}</p>
    </div>
@endsection

@push('styles')
    <style>
        [x-cloak] {
            display: none !important;
        }

        .detail-card {
            background: #fff;
            border: 1.5px solid #f1f5f9;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 16px;
        }

        .card-title {
            font-size: 11px;
            font-weight: 800;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            padding-bottom: 12px;
            margin-bottom: 16px;
            border-bottom: 1.5px solid #f3f4f6;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card-title i {
            color: var(--brand-600);
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            padding: 6px 0;
            border-bottom: 1px solid #f9fafb;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-size: 11px;
            font-weight: 700;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .info-value {
            font-size: 13px;
            font-weight: 600;
            color: #1f2937;
            text-align: right;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }

        .status-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            transition: opacity 150ms ease, transform 80ms ease;
        }

        .action-btn:active {
            transform: scale(0.97);
        }

        .action-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .action-btn-primary {
            background: var(--brand-600);
            color: #fff;
        }

        .action-btn-primary:hover:not(:disabled) {
            opacity: 0.9;
        }

        .action-btn-outline {
            background: #fff;
            color: #374151;
            border: 1.5px solid #e5e7eb;
        }

        .action-btn-outline:hover {
            background: #f9fafb;
        }

        .action-btn-danger {
            background: #fef2f2;
            color: #dc2626;
        }

        .action-btn-danger:hover {
            background: #fee2e2;
        }

        .field-input {
            width: 100%;
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            padding: 9px 13px;
            font-size: 13px;
            color: #1f2937;
            outline: none;
            font-family: inherit;
            background: #fff;
            transition: border-color 150ms ease;
        }

        .field-input:focus {
            border-color: var(--brand-600);
        }

        .timeline-item {
            display: flex;
            gap: 12px;
            position: relative;
            padding-bottom: 16px;
        }

        .timeline-item:not(:last-child)::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 32px;
            width: 2px;
            bottom: 0;
            background: #f1f5f9;
        }

        .timeline-dot {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 12px;
            font-weight: 700;
            border: 2px solid transparent;
        }

        /* Mobile responsive table */
        @media (max-width: 640px) {
            .items-table-header {
                display: none;
            }

            .items-table-row {
                display: flex;
                flex-direction: column;
                gap: 4px;
                padding: 12px;
                border-bottom: 1px solid #f3f4f6;
            }

            .items-table-row .col-product {
                font-weight: 600;
            }

            .items-table-row .col-price::before {
                content: 'Price: ';
                color: #9ca3af;
                font-size: 11px;
            }

            .items-table-row .col-qty::before {
                content: 'Qty: ';
                color: #9ca3af;
                font-size: 11px;
            }

            .items-table-row .col-total::before {
                content: 'Total: ';
                color: #9ca3af;
                font-size: 11px;
                font-weight: 700;
            }
        }

        @media (min-width: 641px) {

            .items-table-header,
            .items-table-row {
                display: grid;
                grid-template-columns: 2fr 1fr 60px 1fr;
                gap: 16px;
                align-items: center;
                padding: 10px 16px;
            }

            .items-table-header {
                background: #f8fafc;
                border-bottom: 1px solid #f1f5f9;
            }

            .items-table-row .col-qty {
                text-align: center;
            }

            .items-table-row .col-total,
            .items-table-header span:last-child {
                text-align: right;
            }

            .items-table-header span:nth-child(3) {
                text-align: center;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $sc = $order->status_color;
    @endphp

    <div class="pb-10" x-data="orderShow()">

        {{-- ── Breadcrumb ── --}}
        <div class="mb-5 flex items-center justify-between flex-wrap gap-3">
            <div class="flex items-center gap-2 text-sm text-gray-400 font-medium">
                <a href="{{ route('admin.orders.index') }}" class="hover:text-brand-600 transition-colors">Orders</a>
                <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                <span class="text-gray-700 font-semibold font-mono">{{ $order->order_number }}</span>
            </div>
            <a href="{{ route('admin.orders.index') }}"
                class="inline-flex items-center gap-1.5 text-sm font-semibold text-gray-500 hover:text-gray-800 transition-colors">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Orders
            </a>
        </div>

        {{-- ── Flash messages ── --}}
        @if (session('success'))
            <div class="mb-4 bg-green-50 border border-green-200 rounded-xl px-4 py-3 flex items-center gap-3">
                <i data-lucide="check-circle" class="w-4 h-4 text-green-600 flex-shrink-0"></i>
                <p class="text-sm font-semibold text-green-800">{{ session('success') }}</p>
            </div>
        @endif
        @if (session('error'))
            <div class="mb-4 bg-red-50 border border-red-200 rounded-xl px-4 py-3 flex items-center gap-3">
                <i data-lucide="alert-circle" class="w-4 h-4 text-red-600 flex-shrink-0"></i>
                <p class="text-sm font-semibold text-red-800">{{ session('error') }}</p>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

            {{-- ════════ LEFT — Main details ════════ --}}
            <div class="lg:col-span-2 space-y-4">

                {{-- ── Order Summary ── --}}
                <div class="detail-card">
                    <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
                        <div>
                            <p class="font-mono text-lg font-bold text-gray-900">{{ $order->order_number }}</p>
                            <p class="text-xs text-gray-400 font-medium mt-0.5">
                                Placed {{ $order->created_at->diffForHumans() }}
                                · via {{ ucfirst($order->source) }}
                            </p>
                        </div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="status-badge" id="status-badge-display"
                                style="background: {{ $sc['bg'] }}; color: {{ $sc['text'] }}">
                                <span class="status-dot" style="background: {{ $sc['dot'] }}"></span>
                                <span>{{ $order->status_label }}</span>
                            </span>
                            {{-- Invoice status reference (read-only — payment managed in Invoice module) --}}
                            @if ($order->invoice)
                                <a href="{{ route('admin.invoices.show', $order->invoice->id) }}"
                                    class="status-badge text-[11px] font-bold"
                                    style="background: #f0fdf4; color: #15803d; text-decoration: none;">
                                    <i data-lucide="file-text" class="w-3 h-3"></i>
                                    {{ $order->invoice->invoice_number }}
                                </a>
                            @endif
                        </div>
                    </div>

                    {{-- Quick actions ── --}}
                    <div class="flex flex-wrap gap-2 pt-3 border-t border-gray-100">

                        {{-- Confirm ── --}}
                        @if (in_array($order->status, ['inquiry']) && has_permission('orders.change_status'))
                            <button @click="updateStatus('confirmed')" :disabled="loading"
                                class="action-btn action-btn-primary">
                                <i data-lucide="check-circle" class="w-4 h-4"></i>
                                Confirm Order
                            </button>
                        @endif

                        {{-- Mark Processing ── --}}
                        @if (in_array($order->status, ['confirmed']) && has_permission('orders.change_status'))
                            <button @click="updateStatus('processing')" :disabled="loading"
                                class="action-btn action-btn-outline">
                                <i data-lucide="loader-2" class="w-4 h-4"></i>
                                Mark Processing
                            </button>
                        @endif

                        {{-- Mark Shipped ── --}}
                        @if (in_array($order->status, ['confirmed', 'processing']) && has_permission('orders.change_status'))
                            <button @click="updateStatus('shipped')" :disabled="loading"
                                class="action-btn action-btn-outline">
                                <i data-lucide="truck" class="w-4 h-4"></i>
                                Mark Shipped
                            </button>
                        @endif

                        {{-- Mark Delivered ── --}}
                        @if (in_array($order->status, ['shipped', 'out_for_delivery']) && has_permission('orders.change_status'))
                            <button @click="updateStatus('delivered')" :disabled="loading"
                                class="action-btn action-btn-outline">
                                <i data-lucide="package-check" class="w-4 h-4"></i>
                                Mark Delivered
                            </button>
                        @endif

                        {{-- Create Invoice — primary billing action ── --}}
                        @if (!$order->invoice_id && !in_array($order->status, ['cancelled', 'failed']) && has_permission('invoices.create'))
                            <a href="{{ route('admin.invoices.create', ['order_id' => $order->id]) }}"
                                class="action-btn action-btn-primary" style="background: #7c3aed;">
                                <i data-lucide="file-plus" class="w-4 h-4"></i>
                                Create Invoice
                            </a>
                        @endif

                        {{-- Cancel ── --}}
                        @if ($order->is_cancellable && has_permission('orders.cancel'))
                            <button @click="cancelModal = true" class="action-btn action-btn-danger ml-auto">
                                <i data-lucide="x-circle" class="w-4 h-4"></i>
                                Cancel
                            </button>
                        @endif
                    </div>
                </div>

                {{-- ── Order Items ── --}}
                <div class="detail-card !p-0 overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100">
                        <p class="card-title !border-0 !pb-0 !mb-0">
                            <i data-lucide="shopping-bag" class="w-4 h-4"></i>
                            Order Items
                            <span class="text-gray-400 font-normal normal-case ml-1">({{ $order->items_count }} items ·
                                {{ $order->items_qty }} qty)</span>
                        </p>
                    </div>

                    {{-- Table header ── --}}
                    <div class="items-table-header text-[10px] font-black text-gray-400 uppercase tracking-widest"
                        style="{{ $order->order_type === 'inquiry' ? 'grid-template-columns: 3fr 1fr;' : '' }}">
                        <span>Product</span>
                        @if ($order->order_type !== 'inquiry')
                            <span>Unit Price</span>
                        @endif
                        <span class="text-center">Qty</span>
                        @if ($order->order_type !== 'inquiry')
                            <span class="text-right">Total</span>
                        @endif
                    </div>

                    {{-- Items ── --}}
                    @foreach ($order->items as $item)
                        <div class="items-table-row border-b border-gray-50"
                            style="{{ $order->order_type === 'inquiry' ? 'grid-template-columns: 3fr 1fr;' : '' }}">
                            <div class="col-product min-w-0">
                                <p class="text-[13px] font-semibold text-gray-800 truncate">{{ $item->product_name }}</p>
                                @if ($item->sku_code)
                                    <p class="text-[10px] text-gray-300 font-mono">{{ $item->sku_code }}</p>
                                @endif
                            </div>

                            @if ($order->order_type !== 'inquiry')
                                <div class="col-price text-[13px] text-gray-600 font-medium">
                                    ₹{{ number_format($item->unit_price, 2) }}
                                </div>
                            @endif

                            <div class="col-qty text-[13px] font-bold text-gray-900 sm:text-center">
                                {{ $item->qty }}
                            </div>

                            @if ($order->order_type !== 'inquiry')
                                <div class="col-total text-[13px] font-bold text-gray-900 sm:text-right">
                                    ₹{{ number_format($item->line_total, 2) }}
                                </div>
                            @endif
                        </div>
                    @endforeach

                    {{-- Totals ── --}}
                    @if ($order->order_type !== 'inquiry')
                        <div class="px-5 py-4 bg-gray-50/50 space-y-2">
                            <div class="flex justify-between text-[13px] text-gray-500">
                                <span>Subtotal</span>
                                <span
                                    class="font-semibold text-gray-700 font-mono">₹{{ number_format($order->subtotal, 2) }}</span>
                            </div>
                            @if ($order->discount_amount > 0)
                                <div class="flex justify-between text-[13px] text-green-600">
                                    <span>Discount</span>
                                    <span
                                        class="font-semibold font-mono">−₹{{ number_format($order->discount_amount, 2) }}</span>
                                </div>
                            @endif
                            @if ($order->tax_amount > 0)
                                <div class="flex justify-between text-[12px] text-gray-400">
                                    <span>
                                        Tax
                                        @if ($order->cgst_amount > 0)
                                            (CGST ₹{{ number_format($order->cgst_amount, 2) }} + SGST
                                            ₹{{ number_format($order->sgst_amount, 2) }})
                                        @elseif($order->igst_amount > 0)
                                            (IGST)
                                        @endif
                                    </span>
                                    <span class="font-mono">₹{{ number_format($order->tax_amount, 2) }}</span>
                                </div>
                            @endif
                            @if ($order->shipping_amount > 0)
                                <div class="flex justify-between text-[13px] text-gray-500">
                                    <span>Shipping</span>
                                    <span
                                        class="font-semibold font-mono">₹{{ number_format($order->shipping_amount, 2) }}</span>
                                </div>
                            @endif
                            @if ($order->round_off != 0)
                                <div class="flex justify-between text-[12px] text-gray-400">
                                    <span>Round Off</span>
                                    <span
                                        class="font-mono">{{ $order->round_off > 0 ? '+' : '' }}₹{{ number_format($order->round_off, 2) }}</span>
                                </div>
                            @endif
                            <div
                                class="flex justify-between text-[15px] font-bold text-gray-900 border-t border-gray-200 pt-2 mt-2">
                                <span>Total</span>
                                <span class="font-mono"
                                    style="color: var(--brand-600)">₹{{ number_format($order->total_amount, 2) }}</span>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- ── Customer Notes ── --}}
                @if ($order->customer_notes)
                    <div class="detail-card">
                        <p class="card-title">
                            <i data-lucide="message-square" class="w-4 h-4"></i>
                            Customer Notes
                        </p>
                        <p
                            class="text-[13px] text-gray-600 leading-relaxed bg-amber-50 border border-amber-100 rounded-xl px-4 py-3">
                            {{ $order->customer_notes }}
                        </p>
                    </div>
                @endif

                {{-- ── Admin Notes ── --}}
                @if (has_permission('orders.add_note'))
                    {{-- No local x-data: editing, note, saving and saveNote() all live in
                         orderShow() above. The inline object that stood here declared the
                         first three but not saveNote(), so the Save button referenced a
                         function that was never in scope. --}}
                    <div class="detail-card">
                        <p class="card-title">
                            <i data-lucide="file-text" class="w-4 h-4"></i>
                            Admin Notes
                            <button @click="editing = !editing"
                                class="ml-auto text-[11px] font-bold px-2.5 py-1 rounded-lg transition-colors"
                                :class="editing ? 'bg-gray-100 text-gray-600' : 'text-brand-600 hover:bg-brand-50'"
                                style="color: var(--brand-600)">
                                <span x-text="editing ? 'Cancel' : 'Edit'"></span>
                            </button>
                        </p>

                        <template x-if="!editing">
                            <p class="text-[13px] text-gray-600 leading-relaxed" x-text="note || 'No notes added yet.'">
                            </p>
                        </template>

                        <template x-if="editing">
                            <div class="space-y-3">
                                <textarea x-model="note" rows="3" class="field-input resize-none"
                                    placeholder="Add internal notes about this order..."></textarea>
                                <button @click="saveNote()" :disabled="saving" class="action-btn action-btn-primary">
                                    <i data-lucide="loader-2" x-show="saving" class="w-3.5 h-3.5 animate-spin"></i>
                                    <i data-lucide="save" x-show="!saving" class="w-3.5 h-3.5"></i>
                                    <span x-text="saving ? 'Saving...' : 'Save Note'"></span>
                                </button>
                            </div>
                        </template>
                    </div>
                @endif

                {{-- ── Status History Timeline ── --}}
                @if ($order->statusHistory->isNotEmpty())
                    <div class="detail-card">
                        <p class="card-title">
                            <i data-lucide="clock" class="w-4 h-4"></i>
                            Status History
                        </p>
                        <div class="space-y-0">
                            @foreach ($order->statusHistory as $history)
                                @php
                                    $toColor = \App\Models\Order::STATUS_COLORS[$history->to_status] ?? [
                                        'bg' => '#f8fafc',
                                        'text' => '#6b7280',
                                        'dot' => '#94a3b8',
                                    ];
                                @endphp
                                <div class="timeline-item">
                                    <div class="timeline-dot"
                                        style="background: {{ $toColor['bg'] }}; border-color: {{ $toColor['dot'] }}">
                                        <span style="color: {{ $toColor['dot'] }}; font-size: 9px;">●</span>
                                    </div>
                                    <div class="flex-1 min-w-0 pt-1">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="text-[12px] font-bold text-gray-900">
                                                {{ ucfirst(str_replace('_', ' ', $history->to_status)) }}
                                            </span>
                                            @if ($history->from_status)
                                                <span class="text-[11px] text-gray-400">
                                                    from {{ ucfirst(str_replace('_', ' ', $history->from_status)) }}
                                                </span>
                                            @endif
                                            <span class="text-[10px] text-gray-400 ml-auto font-mono">
                                                {{ $history->created_at->format('d M Y, h:i A') }}
                                            </span>
                                        </div>
                                        @if ($history->notes)
                                            <p class="text-[12px] text-gray-500 mt-0.5">{{ $history->notes }}</p>
                                        @endif
                                        @php
                                            // changed_by_type is an enum column, so the "owner:John Doe"
                                            // packing this used to parse could never have been written to
                                            // it — the name lives in the changed_by relation instead.
                                            // Automated actors (system, razorpay) have no user, so the
                                            // type alone is the whole answer for them.
                                            $byRole = $history->changed_by_type ?? 'system';
                                            $byName = $history->changedBy?->name;
                                        @endphp
                                        <p class="text-[10px] text-gray-400 mt-0.5 flex items-center gap-1">
                                            <span class="capitalize">{{ $byRole }}</span>
                                            @if ($byName)
                                                <span class="text-gray-300">·</span>
                                                <span class="font-semibold text-gray-500">{{ $byName }}</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

            </div>

            {{-- ════════ RIGHT — Sidebar ════════ --}}
            <div class="space-y-4">

                {{-- ── Update Status ── --}}
                @if (has_permission('orders.change_status'))
                    <div class="detail-card">
                        <p class="card-title">
                            <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                            Update Status
                        </p>

                        <div class="space-y-3">
                            <div>
                                <label
                                    class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">New
                                    Status</label>
                                <select x-model="newStatus" class="field-input">
                                    @foreach (\App\Models\Order::STATUS_COLORS as $status => $colors)
                                        <option value="{{ $status }}"
                                            {{ $order->status === $status ? 'selected' : '' }}>
                                            {{ ucfirst(str_replace('_', ' ', $status)) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label
                                    class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">Notes
                                    (optional)</label>
                                <textarea x-model="statusNotes" rows="2" class="field-input resize-none"
                                    placeholder="Reason for status change..."></textarea>
                            </div>
                            <button @click="updateStatus(newStatus)"
                                :disabled="loading || newStatus === '{{ $order->status }}'"
                                class="action-btn action-btn-primary w-full justify-center">
                                <i data-lucide="loader-2" x-show="loading" class="w-4 h-4 animate-spin"></i>
                                <i data-lucide="check" x-show="!loading" class="w-4 h-4"></i>
                                <span x-text="loading ? 'Updating...' : 'Update Status'"></span>
                            </button>
                        </div>
                    </div>
                @endif

                {{-- ── Customer Info ── --}}
                <div class="detail-card">
                    <p class="card-title">
                        <i data-lucide="user" class="w-4 h-4"></i>
                        Customer
                    </p>
                    <div class="space-y-0">
                        <div class="info-row">
                            <span class="info-label">Name</span>
                            <span class="info-value">{{ $order->customer_name }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Phone</span>
                            <a href="tel:{{ $order->customer_phone }}" class="info-value font-mono"
                                style="color: var(--brand-600)">{{ $order->customer_phone }}</a>
                        </div>
                        @if ($order->customer_email)
                            <div class="info-row">
                                <span class="info-label">Email</span>
                                <span class="info-value text-xs">{{ $order->customer_email }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- ── Delivery Address ── --}}
                @if ($order->delivery_address)
                    <div class="detail-card">
                        <p class="card-title">
                            <i data-lucide="map-pin" class="w-4 h-4"></i>
                            Delivery Address
                        </p>
                        <address class="not-italic text-[13px] text-gray-600 leading-relaxed">
                            {{ $order->delivery_address }}
                            @if ($order->delivery_city)
                                <br>{{ $order->delivery_city }}
                            @endif
                            @if ($order->delivery_state)
                                , {{ $order->delivery_state }}
                            @endif
                            @if ($order->delivery_pincode)
                                — {{ $order->delivery_pincode }}
                            @endif
                            <br>{{ $order->delivery_country ?? 'India' }}
                        </address>
                        @if ($order->supply_state)
                            <p class="text-[11px] text-gray-400 font-medium mt-2">
                                GST: {{ $order->cgst_amount > 0 ? 'Intra-state (CGST + SGST)' : 'Inter-state (IGST)' }}
                            </p>
                        @endif
                    </div>
                @endif

                {{-- ── Invoice Status (reference — billing managed in Invoice module) ── --}}
                <div class="detail-card">
                    <p class="card-title">
                        <i data-lucide="file-text" class="w-4 h-4"></i>
                        Invoice
                    </p>
                    @if ($order->invoice)
                        <div class="space-y-0">
                            <div class="info-row">
                                <span class="info-label">Invoice #</span>
                                <a href="{{ route('admin.invoices.show', $order->invoice->id) }}"
                                    class="info-value font-mono text-xs" style="color: var(--brand-600)">
                                    {{ $order->invoice->invoice_number }}
                                </a>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Status</span>
                                <span class="info-value">
                                    @php
                                        $invStatusColors = [
                                            'draft' => ['bg' => '#fefce8', 'text' => '#a16207'],
                                            'confirmed' => ['bg' => '#f0fdf4', 'text' => '#15803d'],
                                            'cancelled' => ['bg' => '#fef2f2', 'text' => '#dc2626'],
                                        ];
                                        $isc = $invStatusColors[$order->invoice->status] ?? [
                                            'bg' => '#f8fafc',
                                            'text' => '#64748b',
                                        ];
                                    @endphp
                                    <span class="status-badge text-[11px] px-2 py-0.5"
                                        style="background: {{ $isc['bg'] }}; color: {{ $isc['text'] }}">
                                        {{ ucfirst($order->invoice->status) }}
                                    </span>
                                </span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Payment</span>
                                <span class="info-value capitalize text-xs text-gray-500">
                                    {{ ucfirst($order->invoice->payment_status) }}
                                </span>
                            </div>
                            @if ($order->invoice->grand_total > 0)
                                <div class="info-row">
                                    <span class="info-label">Amount</span>
                                    <span
                                        class="info-value font-mono">₹{{ number_format($order->invoice->grand_total, 2) }}</span>
                                </div>
                            @endif
                        </div>
                        <a href="{{ route('admin.invoices.show', $order->invoice->id) }}"
                            class="mt-3 flex items-center justify-center gap-1.5 w-full py-2 rounded-xl text-[12px] font-bold transition-colors"
                            style="background: #f5f3ff; color: #7c3aed;">
                            <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                            View Full Invoice
                        </a>
                    @else
                        <div class="flex flex-col items-center gap-3 py-3">
                            <div class="w-10 h-10 rounded-xl bg-gray-50 flex items-center justify-center">
                                <i data-lucide="file-x" class="w-5 h-5 text-gray-300"></i>
                            </div>
                            <p class="text-[12px] text-gray-400 text-center">No invoice created yet.</p>
                            @if (!in_array($order->status, ['cancelled', 'failed']) && has_permission('invoices.create'))
                                <a href="{{ route('admin.invoices.create', ['order_id' => $order->id]) }}"
                                    class="action-btn w-full justify-center text-[12px]"
                                    style="background: #7c3aed; color: #fff;">
                                    <i data-lucide="file-plus" class="w-3.5 h-3.5"></i>
                                    Create Invoice
                                </a>
                            @endif
                        </div>
                    @endif
                </div>

                {{-- ── Shipping / Tracking ── --}}
                <div class="detail-card" x-data="{ editTracking: false }">
                    <div class="card-title">
                        <i data-lucide="truck" class="w-4 h-4"></i>
                        Shipping
                        <button @click="editTracking = !editTracking"
                            class="ml-auto text-[11px] font-bold px-2.5 py-1 rounded-lg hover:bg-gray-100 transition-colors text-gray-500">
                            <span x-text="editTracking ? 'Cancel' : 'Edit'"></span>
                        </button>
                    </div>

                    <template x-if="!editTracking">
                        <div class="space-y-0">
                            <div class="info-row">
                                <span class="info-label">Type</span>
                                <span class="info-value capitalize">{{ $order->delivery_type ?? 'Delivery' }}</span>
                            </div>
                            @if ($order->courier_name)
                                <div class="info-row">
                                    <span class="info-label">Courier</span>
                                    <span class="info-value">{{ $order->courier_name }}</span>
                                </div>
                            @endif
                            @if ($order->tracking_number)
                                <div class="info-row">
                                    <span class="info-label">Tracking</span>
                                    <span class="info-value font-mono text-xs">{{ $order->tracking_number }}</span>
                                </div>
                            @endif
                            @if ($order->expected_delivery_date)
                                <div class="info-row">
                                    <span class="info-label">Expected</span>
                                    <span class="info-value">{{ $order->expected_delivery_date->format('d M Y') }}</span>
                                </div>
                            @endif
                            @if (!$order->courier_name && !$order->tracking_number)
                                <p class="text-[12px] text-gray-400">No tracking info yet.</p>
                            @endif
                        </div>
                    </template>

                    <template x-if="editTracking">
                        <form method="POST" action="{{ route('admin.orders.logistics', $order->id) }}"
                            class="space-y-3">
                            @csrf
                            @method('PATCH')
                            <div>
                                <label
                                    class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Courier</label>
                                <input type="text" name="courier_name" value="{{ $order->courier_name }}"
                                    placeholder="e.g. Delhivery, BlueDart" class="field-input">
                            </div>
                            <div>
                                <label
                                    class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Tracking
                                    #</label>
                                <input type="text" name="tracking_number" value="{{ $order->tracking_number }}"
                                    placeholder="Tracking number" class="field-input">
                            </div>
                            <div>
                                <label
                                    class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Expected
                                    Delivery</label>
                                <input type="date" name="expected_delivery_date"
                                    value="{{ $order->expected_delivery_date?->format('Y-m-d') }}" class="field-input">
                            </div>
                            <button type="submit" class="action-btn action-btn-primary w-full justify-center">
                                <i data-lucide="save" class="w-3.5 h-3.5"></i>
                                Save Tracking
                            </button>
                        </form>
                    </template>
                </div>

                {{-- ── Order Meta ── --}}
                <div class="detail-card">
                    <p class="card-title">
                        <i data-lucide="info" class="w-4 h-4"></i>
                        Order Info
                    </p>
                    <div class="space-y-0">
                        <div class="info-row">
                            <span class="info-label">Order ID</span>
                            <span class="info-value font-mono">#{{ $order->id }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Type</span>
                            <span class="info-value capitalize">{{ $order->order_type }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Source</span>
                            <span class="info-value capitalize">{{ $order->source }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Created</span>
                            <span class="info-value text-xs">{{ $order->created_at->format('d M Y, h:i A') }}</span>
                        </div>
                        @if ($order->creator)
                            <div class="info-row">
                                <span class="info-label">Created by</span>
                                <span class="info-value">{{ $order->creator->name }}</span>
                            </div>
                        @endif
                        @if ($order->confirmed_at)
                            <div class="info-row">
                                <span class="info-label">Confirmed</span>
                                <span class="info-value text-xs">{{ $order->confirmed_at->format('d M Y') }}</span>
                            </div>
                        @endif
                        @if ($order->cancelled_at)
                            <div class="info-row">
                                <span class="info-label">Cancelled</span>
                                <span
                                    class="info-value text-xs text-red-500">{{ $order->cancelled_at->format('d M Y') }}</span>
                            </div>
                        @endif
                        @if ($order->cancellation_reason)
                            <div class="info-row">
                                <span class="info-label">Reason</span>
                                <span class="info-value text-xs text-red-400">{{ $order->cancellation_reason }}</span>
                            </div>
                        @endif
                    </div>
                </div>

            </div>
        </div>

        {{-- ════════ Cancel Modal ════════ --}}
        <div x-show="cancelModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4"
            style="background: rgba(0,0,0,0.45)">
            <div @click.away="cancelModal = false" class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6"
                x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100">

                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 bg-red-100 rounded-xl flex items-center justify-center flex-shrink-0">
                        <i data-lucide="x-circle" class="w-5 h-5 text-red-600"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900">Cancel Order</h3>
                        <p class="text-xs text-gray-400">Order #{{ $order->order_number }}</p>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">
                        Cancellation Reason <span class="text-red-500">*</span>
                    </label>
                    <textarea x-model="cancelReason" rows="3" class="field-input resize-none"
                        placeholder="Why is this order being cancelled?"></textarea>
                </div>

                <div class="flex gap-3">
                    <button @click="cancelModal = false" class="action-btn action-btn-outline flex-1 justify-center">
                        Keep Order
                    </button>
                    <button @click="cancelOrder()" :disabled="!cancelReason.trim() || loading"
                        class="action-btn action-btn-danger flex-1 justify-center">
                        <i data-lucide="loader-2" x-show="loading" class="w-4 h-4 animate-spin"></i>
                        <span x-text="loading ? 'Cancelling...' : 'Confirm Cancel'"></span>
                    </button>
                </div>
            </div>
        </div>


    </div>
@endsection

@push('scripts')
    <script>
        function orderShow() {
            return {
                loading: false,
                newStatus: @js($order->status),
                statusNotes: '',
                cancelModal: false,
                cancelReason: '',

                // Admin notes state. Encoded by Blade's JS helper rather than
                // addslashes(): addslashes escapes quotes and backslashes but
                // leaves newlines intact, and a textarea value with a line break
                // produces a literal newline inside a JS string literal, which
                // is a syntax error.
                editing: false,
                saving: false,
                note: @js($order->admin_notes ?? ''),

                async saveNote() {
                    this.saving = true;
                    try {
                        const res = await fetch(@js(route('admin.orders.note', $order->id)), {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify({
                                admin_notes: this.note
                            }),
                        });
                        const data = await res.json();
                        if (data.success) {
                            this.editing = false;
                            if (typeof BizAlert !== 'undefined') BizAlert.toast('Note saved.', 'success');
                        }
                    } catch (e) {
                        console.error('[OrderShow] Note save error:', e);
                    } finally {
                        this.saving = false;
                    }
                },

                // ── Update status via AJAX ──
                async updateStatus(status) {
                    if (this.loading) return;
                    if (status === '{{ $order->status }}' && !this.statusNotes) {
                        return;
                    }

                    this.loading = true;

                    try {
                        const res = await fetch('{{ route('admin.orders.status', $order->id) }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify({
                                status: status,
                                notes: this.statusNotes,
                            }),
                        });

                        const data = await res.json();

                        if (data.success) {
                            // Update badge in DOM
                            const badge = document.getElementById('status-badge-display');
                            if (badge && data.status_color) {
                                badge.style.background = data.status_color.bg;
                                badge.style.color = data.status_color.text;
                                badge.querySelector('span:last-child').textContent = data.status_label;
                                badge.querySelector('.status-dot').style.background = data.status_color.dot;
                            }

                            this.newStatus = status;
                            this.statusNotes = '';

                            if (typeof BizAlert !== 'undefined') {
                                BizAlert.toast(data.message, 'success');
                            }

                            // Reload page after short delay to refresh history
                            setTimeout(() => location.reload(), 1200);
                        } else {
                            alert(data.message || 'Failed to update status.');
                        }

                    } catch (e) {
                        console.error('[OrderShow] Status update error:', e);
                        alert('Network error. Please try again.');
                    } finally {
                        this.loading = false;
                    }
                },

                // ── Cancel order ──
                async cancelOrder() {
                    if (!this.cancelReason.trim() || this.loading) return;
                    this.loading = true;

                    try {
                        const res = await fetch('{{ route('admin.orders.cancel', $order->id) }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify({
                                reason: this.cancelReason
                            }),
                        });

                        const data = await res.json();

                        if (data.success) {
                            this.cancelModal = false;
                            if (typeof BizAlert !== 'undefined') {
                                BizAlert.toast(data.message, 'success');
                            }
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            alert(data.message || 'Failed to cancel order.');
                        }

                    } catch (e) {
                        console.error('[OrderShow] Cancel error:', e);
                        alert('Network error. Please try again.');
                    } finally {
                        this.loading = false;
                    }
                },
            }
        }

        // The alpine:init block that stood here never ran on SPA navigation:
        // pushed scripts are re-executed by rerunScripts() long after Alpine has
        // started, so the event has already fired. Its component is now part of
        // orderShow() above, which is a plain global function and works on both
        // a full page load and an in-place navigation.
    </script>
@endpush
