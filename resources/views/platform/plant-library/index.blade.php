@extends ('layouts.platform')

@section('title', 'Plant Library')
@section('header', 'Plant Education Master Library')

@section('content')
    <div class="mx-auto max-w-7xl pb-12">
        {{-- Top Action Bar --}}
        <div class="mb-8 flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight text-gray-900">Plant Library</h1>
                <p class="mt-1 text-sm text-gray-500">Master plant catalog — tenants import from here into their own store.
                    Write once, use everywhere.</p>
            </div>
            <div class="flex shrink-0 items-center gap-2">
                <button type="button" @click="$dispatch('open-plant-import')"
                    class="flex items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition-all hover:bg-gray-50">
                    <i class="fa-solid fa-file-csv text-brand-600"></i> Bulk Import
                </button>
                <a href="{{ route('platform.plant-library.create') }}"
                    class="bg-brand-600 hover:bg-brand-700 shadow-brand-500/20 flex shrink-0 items-center justify-center gap-2 rounded-lg px-5 py-2.5 text-sm font-semibold text-white shadow-md transition-all">
                    <i class="fa-solid fa-plus"></i> Add Plant
                </a>
            </div>
        </div>

        {{-- Flash Messages --}}
        @if (session('success'))
            <div
                class="mb-6 flex items-center gap-3 rounded-xl border border-green-200 bg-green-50 px-5 py-4 text-sm text-green-800 shadow-sm">
                <i class="fa-solid fa-circle-check shrink-0 text-xl text-green-600"></i>
                <span class="font-medium">{{ session('success') }}</span>
            </div>
        @endif
        @if (session('error'))
            <div
                class="mb-6 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-800 shadow-sm">
                <i class="fa-solid fa-triangle-exclamation mt-0.5 shrink-0 text-xl text-red-600"></i>
                <span class="font-medium">{{ session('error') }}</span>
            </div>
        @endif

        {{-- Search --}}
        {{-- Search --}}
        <form method="GET" action="{{ route('platform.plant-library.index') }}" class="mb-6">
            <div class="flex max-w-md items-center gap-2">
                <div class="relative flex-1">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                        <i class="fa-solid fa-magnifying-glass text-sm text-gray-400"></i>
                    </div>
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="Search by name or category…"
                        class="focus:ring-brand-500/20 focus:border-brand-500 w-full rounded-lg border border-gray-300 py-2.5 pr-4 pl-10 text-sm transition-all outline-none focus:ring-2" />
                </div>
                @if (request('search'))
                    <a href="{{ route('platform.plant-library.index') }}"
                        class="flex shrink-0 items-center justify-center gap-1.5 rounded-lg border border-gray-200 bg-gray-100 px-4 py-2.5 text-sm font-semibold text-gray-600 transition-all hover:bg-gray-200"
                        title="Clear Search">
                        <i class="fa-solid fa-xmark"></i> Clear
                    </a>
                @endif
            </div>
        </form>

        {{-- Grid View --}}
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @forelse ($plants as $plant)
                <div
                    class="group flex flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition-shadow hover:shadow-md">
                    {{-- Cover Media --}}
                    <div
                        class="relative flex aspect-square items-center justify-center overflow-hidden border-b border-gray-100 bg-gray-50">
                        @php $primary = $plant->primary_media; @endphp
                        @if ($primary && $primary->media_type === 'image')
                            <img src="{{ $primary->media_url }}" alt="{{ $plant->name }}"
                                class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" />
                        @else
                            <div
                                class="absolute inset-0 flex items-center justify-center bg-gradient-to-br from-green-50 to-green-100/50">
                                <i
                                    class="fa-solid fa-seedling absolute -right-4 -bottom-4 -rotate-12 transform text-[5rem] text-green-200/50 transition-transform duration-700 group-hover:scale-110"></i>
                                <div
                                    class="z-10 flex h-20 w-20 items-center justify-center rounded-full bg-white/80 shadow-sm backdrop-blur-sm transition-transform duration-500 group-hover:scale-110">
                                    <i class="fa-solid fa-seedling text-3xl text-green-500"></i>
                                </div>
                            </div>
                        @endif

                        <div class="absolute top-3 left-3 z-20">
                            @if ($plant->is_active)
                                <span
                                    class="rounded bg-green-500 px-2 py-1 text-[10px] font-bold tracking-wider text-white uppercase shadow-sm">Active</span>
                            @else
                                <span
                                    class="rounded bg-gray-500 px-2 py-1 text-[10px] font-bold tracking-wider text-white uppercase shadow-sm">Draft</span>
                            @endif
                        </div>
                        <div class="absolute top-3 right-3 z-20">
                            <span
                                class="flex items-center gap-1 rounded border border-gray-200/50 bg-white/90 px-2 py-1 text-[10px] font-bold text-gray-700 shadow-sm backdrop-blur-sm"
                                title="Media count">
                                <i class="fa-solid fa-photo-film text-brand-600"></i> {{ $plant->media_count }}
                            </span>
                        </div>
                    </div>

                    {{-- Card Content --}}
                    <div class="flex flex-1 flex-col p-5">
                        <h3 class="mb-1 line-clamp-1 text-lg font-bold text-gray-900" title="{{ $plant->name }}">
                            {{ $plant->name }}
                        </h3>
                        <p class="mb-3 text-xs text-gray-400">{{ $plant->category_name ?? '—' }}</p>

                        <div class="mb-5 flex flex-1 flex-wrap items-center gap-2">
                            <span
                                class="rounded bg-gray-100 px-2 py-1 text-[10px] font-bold tracking-wider text-gray-600 uppercase">{{ $plant->type }}</span>
                            <span
                                class="text-[10px] font-bold uppercase tracking-wider px-2 py-1 rounded {{ $plant->product_type === 'catalog' ? 'bg-amber-100 text-amber-700' : 'bg-blue-100 text-blue-700' }}">{{ $plant->product_type }}</span>
                            @if ($plant->unit_short_name)
                                <span
                                    class="rounded bg-gray-100 px-2 py-1 text-[10px] font-bold tracking-wider text-gray-600 uppercase">{{ $plant->unit_short_name }}</span>
                            @endif
                        </div>

                        {{-- Action Buttons --}}
                        <div class="grid grid-cols-5 gap-2 border-t border-gray-100 pt-4">
                            <a href="{{ route('platform.plant-library.show', $plant) }}"
                                class="col-span-2 flex items-center justify-center gap-1 rounded-xl border border-gray-200 bg-gray-50 py-2.5 text-xs font-semibold text-gray-700 transition-colors hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700">
                                <i class="fa-solid fa-eye"></i> View
                            </a>
                            <a href="{{ route('platform.plant-library.edit', $plant) }}"
                                class="hover:bg-brand-50 hover:border-brand-200 hover:text-brand-700 col-span-2 flex items-center justify-center gap-1 rounded-xl border border-gray-200 bg-gray-50 py-2.5 text-xs font-semibold text-gray-700 transition-colors">
                                <i class="fa-solid fa-pen-to-square"></i> Edit
                            </a>
                            <form action="{{ route('platform.plant-library.destroy', $plant) }}" method="POST"
                                class="col-span-1 block"
                                onsubmit="
                                    event.preventDefault();
                                    Swal.fire({
                                        title: 'Are you sure?',
                                        text: 'Delete this plant from the master library? Tenants who already imported it keep their own copy.',
                                        icon: 'warning',
                                        showCancelButton: true,
                                        confirmButtonColor: '#dc2626',
                                        cancelButtonColor: '#4b5563',
                                        confirmButtonText: 'Yes, delete it!',
                                        cancelButtonText: 'Cancel',
                                    }).then((result) => {
                                        if (result.isConfirmed) {
                                            this.submit(); // Submit the form natively without triggering the onsubmit loop
                                        }
                                    });
                                ">
                                @csrf
                                @method ('DELETE')
                                <button type="submit"
                                    class="flex h-full w-full items-center justify-center rounded-xl border border-gray-200 bg-gray-50 text-gray-500 transition-colors hover:border-red-200 hover:bg-red-50 hover:text-red-600"
                                    title="Delete">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div
                    class="col-span-1 flex min-h-[400px] flex-col items-center justify-center rounded-2xl border border-gray-200 bg-white p-12 text-center shadow-sm sm:col-span-2 lg:col-span-3 xl:col-span-4">
                    <div
                        class="mb-5 flex h-20 w-20 items-center justify-center rounded-full border border-green-100 bg-green-50 text-green-500 shadow-sm">
                        <i class="fa-solid fa-book-open text-3xl"></i>
                    </div>
                    <h3 class="mb-2 text-lg font-bold text-gray-900">Library is Empty</h3>
                    <p class="mx-auto mb-6 max-w-md text-sm text-gray-500">Add your first master plant entry — every tenant
                        will be able to import it into their own store with one click.</p>
                    <a href="{{ route('platform.plant-library.create') }}"
                        class="bg-brand-600 hover:bg-brand-700 inline-flex items-center gap-2 rounded-lg px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors">
                        <i class="fa-solid fa-plus"></i> Add First Plant
                    </a>
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if ($plants->hasPages())
            <div class="mt-8">{{ $plants->links() }}</div>
        @endif
    </div>

    @include('platform.plant-library._import-modal', [
        'maxImportRows' => 2000,
        'maxUploadBytes' => max_upload_bytes(),
    ])
@endsection
