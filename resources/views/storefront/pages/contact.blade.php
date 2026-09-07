@php
    // Only a genuine Google Maps embed is framed. The field is a plain URL
    // input, so without this check a tenant could point an iframe anywhere.
    $mapUrl = $page->field('map_embed_url');
    $mapHost = $mapUrl ? parse_url($mapUrl, PHP_URL_HOST) : null;
    $mapAllowed = $mapHost && str_ends_with(strtolower($mapHost), 'google.com');
@endphp

<div class="mb-12 border-b border-gray-100 pb-8 text-center">
    <h1 class="mb-4 text-3xl font-black tracking-tight text-gray-900 sm:text-5xl">
        {{ $page->field('heading') ?: $page->title }}
    </h1>

    @if ($page->field('intro'))
        <p class="mx-auto max-w-2xl text-lg whitespace-pre-line text-gray-500">
            {{ $page->field('intro') }}
        </p>
    @endif
</div>

<div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
    @if ($page->field('address'))
        <div class="rounded-2xl border border-gray-100 bg-gray-50/50 p-6">
            <p class="mb-2 text-[11px] font-bold tracking-widest text-gray-400 uppercase">Address</p>
            <p class="text-[15px] leading-relaxed whitespace-pre-line text-gray-700">{{ $page->field('address') }}</p>
        </div>
    @endif

    @if ($page->field('hours'))
        <div class="rounded-2xl border border-gray-100 bg-gray-50/50 p-6">
            <p class="mb-2 text-[11px] font-bold tracking-widest text-gray-400 uppercase">Opening hours</p>
            <p class="text-[15px] leading-relaxed whitespace-pre-line text-gray-700">{{ $page->field('hours') }}</p>
        </div>
    @endif

    @if ($page->field('phone'))
        <div class="rounded-2xl border border-gray-100 bg-gray-50/50 p-6">
            <p class="mb-2 text-[11px] font-bold tracking-widest text-gray-400 uppercase">Phone</p>
            <a href="tel:{{ $page->field('phone') }}" class="text-brand-600 text-[15px] font-semibold hover:underline">
                {{ $page->field('phone') }}
            </a>
        </div>
    @endif

    @if ($page->field('email'))
        <div class="rounded-2xl border border-gray-100 bg-gray-50/50 p-6">
            <p class="mb-2 text-[11px] font-bold tracking-widest text-gray-400 uppercase">Email</p>
            <a href="mailto:{{ $page->field('email') }}"
                class="text-brand-600 text-[15px] font-semibold hover:underline">
                {{ $page->field('email') }}
            </a>
        </div>
    @endif
</div>

@if ($mapAllowed)
    <div class="mt-8 overflow-hidden rounded-2xl border border-gray-100">
        <iframe src="{{ $mapUrl }}" width="100%" height="360" style="border:0;" allowfullscreen=""
            loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Map"></iframe>
    </div>
@endif
