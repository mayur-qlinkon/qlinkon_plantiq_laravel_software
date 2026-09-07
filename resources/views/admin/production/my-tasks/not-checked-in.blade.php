@extends ('layouts.admin')

@section ('title', 'Check-in Required - PlantIQ')

@section ('content')
    <div class="mx-auto flex max-w-md flex-col items-center justify-center px-6 py-24 text-center">
        <div
            class="mb-6 flex h-20 w-20 items-center justify-center rounded-full border-8 border-white bg-amber-50 text-amber-500 shadow-sm"
        >
            <i data-lucide="qr-code" class="h-8 w-8"></i>
        </div>
        <h3 class="mb-2 text-lg font-bold text-gray-900">Check in first</h3>
        <p class="mb-6 text-sm text-gray-500">To Access Production tasks You have to Scan QR and Check in</p>

        <a
            href="{{ route('admin.employee.dashboard') }}"
            class="bg-brand-600 hover:bg-brand-700 inline-flex items-center gap-2 rounded-xl px-5 py-2.5 text-sm font-bold text-white shadow-sm transition-all"
        >
            <i data-lucide="scan-line" class="h-4 w-4"></i>
            Check-in Now
        </a>
    </div>
@endsection
