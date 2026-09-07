@extends ('layouts.admin')

@section('title', 'Plant Batches - PlantIQ')

@section('header-title')
    <h1 class="text-lg font-bold tracking-tight text-gray-900">Plant Batches</h1>
@endsection

@section('content')
    <div class="mx-auto w-full">
        {{-- Toolbar: Filters + New Batch --}}
        <div class="mb-5">
            <form method="GET" action="{{ route('admin.production.plant-batches.index') }}"
                class="flex flex-col gap-3 md:flex-row md:flex-wrap md:items-center">
                {{-- Search Input --}}
                <div class="relative w-full md:max-w-xs">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                        <i data-lucide="search" class="h-4 w-4 text-gray-400"></i>
                    </div>
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="Search batch or plant..."
                        class="focus:border-brand-500 focus:ring-brand-500/10 w-full rounded-xl border border-gray-200 bg-white py-2.5 pr-4 pl-10 text-sm font-medium text-gray-800 shadow-sm transition-all outline-none placeholder:text-gray-400 focus:ring-4" />
                </div>

                {{-- Plant Filter --}}
                <div class="relative min-w-[220px] flex-1 sm:flex-none">
                    <x-custom-select name="product_id" placeholder="All Plants" :options="$products->pluck('name', 'id')->toArray()"
                        selected="{{ request('product_id') }}" />
                </div>

                {{-- Source Type Filter --}}
                <div class="relative min-w-[180px] flex-1 sm:flex-none">
                    <x-custom-select name="source_type" placeholder="All Sources" :options="collect($sourceTypes)->mapWithKeys(fn($s) => [$s->value => $s->label()])->toArray()"
                        selected="{{ request('source_type') }}" />
                </div>

                {{-- Status Filter --}}
                <div class="relative min-w-[160px] flex-1 sm:flex-none">
                    <x-custom-select name="status" placeholder="All Statuses" :options="collect($statusTypes)->mapWithKeys(fn($s) => [$s->value => $s->label()])->toArray()"
                        selected="{{ request('status') }}" />
                </div>

                <button type="submit"
                    class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-gray-900 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition-colors hover:bg-gray-800">
                    Filter
                </button>

                @if (request()->anyFilled(['search', 'product_id', 'source_type', 'status']))
                    <a href="{{ route('admin.production.plant-batches.index') }}"
                        class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-sm font-bold text-gray-600 shadow-sm transition-colors hover:bg-gray-50 hover:text-gray-900">
                        Clear
                    </a>
                @endif

                {{-- New Batch button — pushed to right end of filter row --}}
                <a href="{{ route('admin.production.plant-batches.create') }}"
                    class="bg-brand-600 hover:bg-brand-700 inline-flex w-full shrink-0 items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-bold text-white shadow-sm transition-all hover:shadow active:scale-95 md:ml-auto md:w-auto">
                    <i data-lucide="plus" class="h-4 w-4"></i>
                    New Batch
                </a>
            </form>
        </div>

        {{-- Main Data Presentation --}}
        <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
            @if ($batches->isEmpty())
                {{-- Empty State --}}
                <div class="flex flex-col items-center justify-center px-6 py-20 text-center">
                    <div
                        class="mb-5 flex h-20 w-20 items-center justify-center rounded-full border-8 border-white bg-indigo-50 text-indigo-300 shadow-sm">
                        <i data-lucide="package-open" class="h-8 w-8 text-indigo-400"></i>
                    </div>
                    <h3 class="mb-1 text-lg font-bold text-gray-900">No Plant Batches Found</h3>
                    <p class="mb-6 max-w-sm text-sm text-gray-500">Track and manage groups of plants by creating your first
                        batch. You can originate a batch from a production plan, purchase, or transfer.</p>
                    <a href="{{ route('admin.production.plant-batches.create') }}"
                        class="bg-brand-50 text-brand-700 hover:bg-brand-100 inline-flex items-center gap-2 rounded-xl px-5 py-2.5 text-sm font-bold transition-colors">
                        <i data-lucide="plus" class="h-4 w-4"></i> Create First Batch
                    </a>
                </div>
            @else
                {{-- 🌟 DESKTOP TABLE VIEW --}}
                <div class="hidden overflow-x-auto md:block">
                    <table class="min-w-full divide-y divide-gray-100 text-left align-middle">
                        <thead class="bg-gray-50/80">
                            <tr>
                                <th scope="col"
                                    class="px-6 py-3.5 text-[11px] font-black tracking-wider text-gray-500 uppercase">
                                    No.
                                </th>
                                <th scope="col"
                                    class="px-6 py-3.5 text-[11px] font-black tracking-wider text-gray-500 uppercase">
                                    Batch Reference
                                </th>
                                <th scope="col"
                                    class="px-6 py-3.5 text-[11px] font-black tracking-wider text-gray-500 uppercase">
                                    Plant
                                </th>
                                <th scope="col"
                                    class="px-6 py-3.5 text-[11px] font-black tracking-wider text-gray-500 uppercase">
                                    Source
                                </th>
                                <th scope="col"
                                    class="px-6 py-3.5 text-right text-[11px] font-black tracking-wider text-gray-500 uppercase">
                                    Quantity (Live)
                                </th>
                                <th scope="col"
                                    class="px-6 py-3.5 text-[11px] font-black tracking-wider text-gray-500 uppercase">
                                    Status
                                </th>
                                <th scope="col"
                                    class="px-6 py-3.5 text-right text-[11px] font-black tracking-wider text-gray-500 uppercase">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 bg-white">
                            @foreach ($batches as $batch)
                                <tr class="group transition-colors hover:bg-gray-50/50">
                                    <td class="px-6 py-4 text-[13px] font-bold whitespace-nowrap text-gray-400">
                                        #{{ $batches->firstItem() + $loop->index }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-3.5">
                                            <div
                                                class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl border border-gray-100 bg-brand-50/50 text-brand-500 transition-all group-hover:bg-white group-hover:shadow-sm">
                                                <i data-lucide="box" class="h-5 w-5"></i>
                                            </div>
                                            <div>
                                                <a href="{{ route('admin.production.plant-batches.show', $batch) }}"
                                                    class="hover:text-brand-600 text-[14px] font-bold text-gray-900 transition-colors">
                                                    {{ $batch->batch_code }}
                                                </a>
                                                <div
                                                    class="mt-0.5 flex items-center gap-1.5 text-[12px] font-medium text-gray-500">
                                                    <i data-lucide="calendar" class="h-3 w-3"></i>
                                                    {{ $batch->batch_start_datetime ? $batch->batch_start_datetime->format('d M, Y') : '—' }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-[13px] font-bold text-gray-900">
                                            {{ $batch->product->name ?? 'Unknown Product' }}
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div
                                            class="inline-flex items-center gap-1.5 rounded-lg bg-gray-100 px-2.5 py-1 text-[12px] font-semibold text-gray-700">
                                            <i data-lucide="git-merge" class="h-3.5 w-3.5 text-gray-400"></i>
                                            {{ $batch->source_type_label }}
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 text-right whitespace-nowrap">
                                        <div
                                            class="text-[14px] font-black {{ $batch->current_quantity === 0 ? 'text-gray-400 line-through' : 'text-gray-800' }}">
                                            {{ $batch->current_quantity }}
                                        </div>
                                        <div class="mt-0.5 text-[11px] font-medium text-gray-400">
                                            of {{ $batch->initial_quantity }} initial
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-bold"
                                            style="background-color: {{ $batch->status_color['bg'] }}; color: {{ $batch->status_color['text'] }}">
                                            <span class="h-1.5 w-1.5 rounded-full"
                                                style="background-color: {{ $batch->status_color['dot'] }}"></span>
                                            {{ $batch->status_label }}
                                        </span>
                                    </td>

                                    <td class="px-6 py-4 text-right whitespace-nowrap">
                                        <a href="{{ route('admin.production.plant-batches.show', $batch) }}"
                                            class="inline-flex items-center justify-center rounded-lg px-3 py-1.5 text-[13px] font-bold text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-900">
                                            Manage Batch
                                            <i data-lucide="chevron-right" class="ml-1.5 h-4 w-4"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- 🌟 MOBILE CARDS VIEW (< md) --}}
                <div class="block divide-y divide-gray-100 bg-gray-50/30 md:hidden">
                    @foreach ($batches as $batch)
                        <div class="bg-white p-4">
                            {{-- Top row: Batch ID & Status --}}
                            <div class="flex items-start justify-between border-b border-gray-50 pb-3">
                                <div class="flex min-w-0 items-center gap-3">
                                    <div
                                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-gray-100 bg-indigo-50/50 text-indigo-500">
                                        <i data-lucide="box" class="h-5 w-5"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <a href="{{ route('admin.production.plant-batches.show', $batch) }}"
                                            class="hover:text-brand-600 truncate text-sm font-bold text-gray-900 transition-colors">
                                            {{ $batch->batch_code }}
                                        </a>
                                        <div class="flex items-center gap-1.5 text-[11px] font-medium text-gray-500">
                                            <i data-lucide="calendar" class="h-3 w-3"></i>
                                            {{ $batch->batch_start_datetime ? $batch->batch_start_datetime->format('d M, Y') : '—' }}
                                        </div>
                                    </div>
                                </div>
                                <div class="ml-3 shrink-0">
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-bold"
                                        style="background-color: {{ $batch->status_color['bg'] }}; color: {{ $batch->status_color['text'] }}">
                                        <span class="h-1 w-1 rounded-full"
                                            style="background-color: {{ $batch->status_color['dot'] }}"></span>
                                        {{ $batch->status_label }}
                                    </span>
                                </div>
                            </div>

                            {{-- Middle Details Grid --}}
                            <div class="grid grid-cols-2 gap-3 py-3">
                                <div>
                                    <p class="text-[10px] font-black tracking-wider text-gray-400 uppercase">Product</p>
                                    <p class="mt-1 truncate text-xs font-bold text-gray-900">
                                        {{ $batch->product->name ?? 'Unknown' }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-black tracking-wider text-gray-400 uppercase">Source</p>
                                    <div
                                        class="mt-1 inline-flex items-center gap-1 rounded-lg bg-gray-100 px-2 py-0.5 text-[11px] font-semibold text-gray-700">
                                        <i data-lucide="git-merge" class="h-3 w-3 text-gray-400"></i>
                                        {{ $batch->source_type_label }}
                                    </div>
                                </div>
                            </div>

                            {{-- Bottom Qty & Actions --}}
                            <div class="flex items-center justify-between border-t border-gray-50 pt-3">
                                <div>
                                    <div
                                        class="text-sm font-black {{ $batch->current_quantity === 0 ? 'text-gray-400 line-through' : 'text-gray-800' }}">
                                        {{ $batch->current_quantity }}
                                        <span class="text-[10px] font-bold text-gray-400 no-underline">LIVE</span>
                                    </div>
                                    <div class="text-[10px] font-medium text-gray-400">
                                        {{ $batch->initial_quantity }} initial
                                    </div>
                                </div>
                                <a href="{{ route('admin.production.plant-batches.show', $batch) }}"
                                    class="inline-flex h-8 items-center justify-center rounded-lg bg-gray-100 px-3 text-[11px] font-bold text-gray-700 transition-colors hover:bg-gray-200">
                                    Manage <i data-lucide="chevron-right" class="ml-1 h-3.5 w-3.5"></i>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if ($batches->hasPages())
                    <div class="border-t border-gray-100 bg-white px-6 py-4">{{ $batches->links() }}</div>
                @endif
            @endif
        </div>
    </div>
@endsection
