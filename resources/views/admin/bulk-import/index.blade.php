@extends('layouts.admin')

@section('title', 'Recent Imports')

@section('header-title')
    <h1 class="text-sm font-bold text-gray-500 uppercase tracking-widest">Bulk Import</h1>
@endsection

@push('styles')
    <style>
        [x-cloak] {
            display: none !important;
        }

        .step-tab-active {
            border-bottom: 2.5px solid var(--brand-600);
            color: #1f2937;
            font-weight: 900;
        }

        .step-tab-inactive {
            color: #6b7280;
            font-weight: 700;
        }

        .step-tab-inactive:hover {
            color: #374151;
        }

        .step-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            font-size: 10px;
            font-weight: 900;
            flex-shrink: 0;
        }

        .dep-pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 7px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 700;
        }
    </style>
@endpush

@section('content')

    <div class="w-full">

        {{-- ══ Recent Import History ══ --}}
        @if ($imports->isNotEmpty())
            <div class="mt-8">
                <h3 class="text-[12px] font-black text-gray-400 uppercase tracking-widest mb-3">Recent Imports</h3>

                {{-- Desktop --}}
                <div class="hidden md:block bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <table class="w-full text-left">
                        <thead>
                            <tr
                                class="bg-gray-50 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">
                                <th class="px-4 py-3">Type</th>
                                <th class="px-4 py-3">Date</th>
                                <th class="px-4 py-3">Total</th>
                                <th class="px-4 py-3">Created</th>
                                <th class="px-4 py-3">Updated</th>
                                <th class="px-4 py-3">Skipped</th>
                                <th class="px-4 py-3">Failed</th>
                                <th class="px-4 py-3">Mode</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach ($imports as $imp)
                                <tr class="text-[12px] hover:bg-gray-50/50 transition-colors">
                                    <td class="px-4 py-2.5 font-bold text-gray-800">
                                        {{ match ($imp->type) {
                                            'product_with_skus' => 'Products + SKUs',
                                            'product_images' => 'Product Images',
                                            default => ucfirst($imp->type),
                                        } }}
                                    </td>
                                    <td class="px-4 py-2.5 text-gray-500">{{ $imp->created_at->format('d M Y, h:i A') }}
                                    </td>
                                    <td class="px-4 py-2.5 font-bold text-gray-600">{{ $imp->total_rows }}</td>
                                    <td class="px-4 py-2.5 font-bold text-sky-600">{{ $imp->created_rows }}</td>
                                    <td class="px-4 py-2.5 font-bold text-emerald-600">{{ $imp->updated_rows }}</td>
                                    <td class="px-4 py-2.5 font-bold text-amber-600">{{ $imp->skipped_rows }}</td>
                                    <td class="px-4 py-2.5 font-bold text-red-500">{{ $imp->failed_rows }}</td>
                                    <td class="px-4 py-2.5 text-gray-500 capitalize">
                                        {{ str_replace('_', ' ', $imp->import_mode ?? '—') }}</td>
                                    <td class="px-4 py-2.5">
                                        @php $sc = ['pending'=>'bg-gray-100 text-gray-500','processing'=>'bg-blue-50 text-blue-600','completed'=>'bg-emerald-50 text-emerald-600','failed'=>'bg-red-50 text-red-500']; @endphp
                                        <span
                                            class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider {{ $sc[$imp->status] ?? '' }}">{{ $imp->status }}</span>
                                    </td>
                                    <td class="px-4 py-2.5">
                                        @if ($imp->failed_rows > 0 || $imp->skipped_rows > 0)
                                            <a href="{{ route('admin.bulk-import.errors', $imp) }}" target="_blank"
                                                class="inline-flex items-center gap-1 text-[11px] font-bold text-red-500 hover:text-red-700 transition-colors">
                                                <i data-lucide="download" class="w-3 h-3"></i> Errors
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Mobile --}}
                <div class="md:hidden divide-y divide-gray-50 border border-gray-100 rounded-xl bg-white shadow-sm">
                    @foreach ($imports as $imp)
                        @php $sc = ['pending'=>'bg-gray-100 text-gray-500 border-gray-200','processing'=>'bg-blue-50 text-blue-600 border-blue-200','completed'=>'bg-emerald-50 text-emerald-600 border-emerald-200','failed'=>'bg-red-50 text-red-500 border-red-200']; @endphp
                        <div class="p-4">
                            <div class="flex justify-between items-start gap-2 mb-3">
                                <div>
                                    <p class="font-bold text-[13px] text-gray-900">
                                        {{ match ($imp->type) {
                                            'product_with_skus' => 'Products + SKUs',
                                            'product_images' => 'Product Images',
                                            default => ucfirst($imp->type),
                                        } }}
                                    </p>
                                    <p class="text-[11px] text-gray-400 mt-0.5">
                                        {{ $imp->created_at->format('d M Y, h:i A') }}</p>
                                </div>
                                <div class="flex flex-col items-end gap-1.5 shrink-0">
                                    <span
                                        class="inline-flex px-2 py-0.5 rounded text-[9px] font-black uppercase border {{ $sc[$imp->status] ?? '' }}">{{ $imp->status }}</span>
                                    @if ($imp->failed_rows > 0)
                                        <a href="{{ route('admin.bulk-import.errors', $imp) }}" target="_blank"
                                            class="text-[10px] font-bold text-red-500 flex items-center gap-1">
                                            <i data-lucide="download" class="w-3 h-3"></i> Errors
                                        </a>
                                    @endif
                                </div>
                            </div>
                            <div
                                class="grid grid-cols-4 gap-1 text-center bg-gray-50 rounded-lg p-2.5 border border-gray-100">
                                <div>
                                    <p class="text-[9px] text-gray-400 font-bold uppercase">Total</p>
                                    <p class="text-[12px] font-bold text-gray-600">{{ $imp->total_rows }}</p>
                                </div>
                                <div>
                                    <p class="text-[9px] text-sky-500 font-bold uppercase">Created</p>
                                    <p class="text-[12px] font-bold text-sky-600">{{ $imp->created_rows }}</p>
                                </div>
                                <div>
                                    <p class="text-[9px] text-amber-500 font-bold uppercase">Skip</p>
                                    <p class="text-[12px] font-bold text-amber-600">{{ $imp->skipped_rows }}</p>
                                </div>
                                <div>
                                    <p class="text-[9px] text-red-400 font-bold uppercase">Fail</p>
                                    <p class="text-[12px] font-bold text-red-500">{{ $imp->failed_rows }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

    </div>
@endsection
