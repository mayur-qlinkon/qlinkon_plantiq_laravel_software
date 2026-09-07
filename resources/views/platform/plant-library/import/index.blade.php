@extends ('layouts.platform')

@section ('title', 'Import to Tenant')
@section ('header', 'Plant Library → Tenant Import')

@section ('content')
    <div class="mx-auto max-w-5xl pb-12">
        <div class="mb-8">
            <a
                href="{{ route('platform.plant-library.index') }}"
                class="text-brand-600 hover:text-brand-700 mb-2 flex items-center gap-2 text-sm font-bold transition-colors"
            >
                <i class="fa-solid fa-arrow-left-long"></i> Back to Library
            </a>
            <h1 class="text-2xl font-extrabold tracking-tight text-gray-900">Import to Tenant</h1>
            <p class="mt-1 text-sm text-gray-500">Pick a company, then choose which plants to add to their product catalog. Photos are reused — nothing is re-uploaded.</p>
        </div>

        @if (session('success'))
            <div
                class="mb-6 flex items-center gap-3 rounded-xl border border-green-200 bg-green-50 px-5 py-4 text-sm text-green-800 shadow-sm"
            >
                <i class="fa-solid fa-circle-check shrink-0 text-xl text-green-600"></i>
                <span class="font-medium">{{ session('success') }}</span>
            </div>
        @endif

        {{-- Search --}}
        <form method="GET" action="{{ route('platform.plant-library-import.index') }}" class="mb-6">
            <div class="relative max-w-md">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                    <i class="fa-solid fa-magnifying-glass text-sm text-gray-400"></i>
                </div>
                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Search company by name…"
                    class="focus:ring-brand-500/20 focus:border-brand-500 w-full rounded-lg border border-gray-300 py-2.5 pr-4 pl-10 text-sm transition-all outline-none focus:ring-2"
                />
            </div>
        </form>

        {{-- Company List --}}
        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            @forelse ($companies as $company)
                <a
                    href="{{ route('platform.plant-library-import.select', $company) }}"
                    class="flex items-center justify-between gap-4 px-6 py-4 hover:bg-gray-50 transition-colors {{ !$loop->last ? 'border-b border-gray-100' : '' }}"
                >
                    <div class="flex min-w-0 items-center gap-4">
                        <div
                            class="bg-brand-50 border-brand-100 flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-xl border"
                        >
                            @if ($company->logo)
                                <img
                                    src="{{ asset('storage/'.$company->logo) }}"
                                    alt="{{ $company->name }}"
                                    class="h-full w-full object-cover"
                                />
                            @else
                                <i class="fa-solid fa-building text-brand-500"></i>
                            @endif
                        </div>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-bold text-gray-900">{{ $company->name }}</p>
                            <p class="truncate text-xs text-gray-400">{{ $company->email ?? $company->slug }}</p>
                        </div>
                    </div>
                    <span class="text-brand-600 flex shrink-0 items-center gap-2 text-sm font-semibold">
                        Select Plants <i class="fa-solid fa-chevron-right text-xs"></i>
                    </span>
                </a>
            @empty
                <div class="p-12 text-center text-gray-400">
                    <i class="fa-solid fa-building mb-3 text-3xl"></i>
                    <p class="text-sm">No companies found.</p>
                </div>
            @endforelse
        </div>

        @if ($companies->hasPages())
            <div class="mt-8">{{ $companies->links() }}</div>
        @endif
    </div>
@endsection
