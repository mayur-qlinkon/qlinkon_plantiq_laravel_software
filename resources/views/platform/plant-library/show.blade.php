@extends ('layouts.platform')

@section ('title', $plantLibrary->name)
@section ('header', 'Plant Library Details')

@section ('content')
    <div class="mx-auto max-w-7xl pb-12">
        {{-- Top Navigation & Action Header --}}
        <div class="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <a
                    href="{{ route('platform.plant-library.index') }}"
                    class="text-brand-600 hover:text-brand-700 mb-2 flex items-center gap-2 text-sm font-bold transition-colors"
                >
                    <i class="fa-solid fa-arrow-left-long"></i> Back to Master Library
                </a>
                <h1 class="text-3xl font-extrabold tracking-tight text-gray-900">{{ $plantLibrary->name }}</h1>
                <p class="mt-1 text-sm text-gray-500">System Slug Reference: <span class="rounded bg-gray-100 px-1.5 py-0.5 font-mono text-gray-700">{{ $plantLibrary->slug }}</span></p>
            </div>

            <div class="flex items-center gap-3 self-start sm:self-end">
                <a
                    href="{{ route('platform.plant-library.edit', $plantLibrary) }}"
                    class="bg-brand-600 hover:bg-brand-700 shadow-brand-500/20 flex items-center justify-center gap-2 rounded-lg px-5 py-2.5 text-sm font-semibold text-white shadow-md transition-all"
                >
                    <i class="fa-solid fa-pen-to-square"></i> Edit Entry
                </a>
            </div>
        </div>

        @php
            // Segregate the cover media from alternative gallery assets
            $primaryMedia = $plantLibrary->media->firstWhere('is_primary', true) ?? $plantLibrary->media->first();
            $galleryMedia = $plantLibrary->media->filter(fn($m) => $m->id !== ($primaryMedia->id ?? null));
        @endphp

        <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
            {{-- Left Column: Media & Descriptive Info --}}
            <div class="space-y-8 lg:col-span-2">
                {{-- Media Viewer --}}
                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 class="mb-4 flex items-center gap-2 text-base font-bold text-gray-900">
                        <i class="fa-solid fa-image text-gray-400"></i> Visual Media Assets
                    </h2>

                    @if ($primaryMedia)
                        {{-- Featured Resource Display --}}
                        <div
                            class="relative mx-auto aspect-square max-h-[500px] w-full overflow-hidden rounded-xl border border-gray-100 bg-gray-50"
                        >
                            @if ($primaryMedia->media_type === 'image')
                                <img
                                    src="{{ $primaryMedia->media_url }}"
                                    alt="{{ $plantLibrary->name }}"
                                    class="h-full w-full object-cover"
                                />
                            @else
                                @php
                                    preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|[^/]+\?v=)|youtu\.be/)([^"&?/ ]{11})%i', $primaryMedia->media_path, $match);
                                    $youtubeId = $match[1] ?? null;
                                @endphp
                                @if ($youtubeId)
                                    <iframe
                                        class="h-full w-full"
                                        src="https://www.youtube.com/embed/{{ $youtubeId }}"
                                        title="YouTube Video Feed"
                                        frameborder="0"
                                        allow="
                                            accelerometer;
                                            autoplay;
                                            clipboard-write;
                                            encrypted-media;
                                            gyroscope;
                                            picture-in-picture;
                                        "
                                        allowfullscreen
                                    ></iframe>
                                @else
                                    <div
                                        class="flex h-full w-full flex-col items-center justify-center bg-red-50 p-6 text-center"
                                    >
                                        <i class="fa-brands fa-youtube mb-3 text-5xl text-red-500"></i>
                                        <a
                                            href="{{ $primaryMedia->media_path }}"
                                            target="_blank"
                                            class="text-brand-600 flex items-center gap-1.5 text-sm font-semibold hover:underline"
                                        >
                                            Open External Video URL
                                            <i class="fa-solid fa-arrow-up-right-from-square text-xs"></i>
                                        </a>
                                    </div>
                                @endif
                            @endif
                            <span
                                class="absolute top-3 left-3 rounded bg-gray-900/80 px-2 py-1 text-[10px] font-bold tracking-wider text-white uppercase shadow-sm backdrop-blur-sm"
                            >
                                Primary Cover
                            </span>
                        </div>

                        {{-- Supporting Gallery Items Grid --}}
                        @if ($galleryMedia->isNotEmpty())
                            <div class="mt-4 grid grid-cols-4 gap-3">
                                @foreach ($galleryMedia as $media)
                                    <div
                                        class="group relative aspect-square overflow-hidden rounded-lg border border-gray-100 bg-gray-50"
                                    >
                                        @if ($media->media_type === 'image')
                                            <img
                                                src="{{ $media->media_url }}"
                                                alt="Gallery Asset"
                                                class="h-full w-full object-cover"
                                            />
                                        @else
                                            <div class="flex h-full w-full items-center justify-center bg-red-50/50">
                                                <i class="fa-brands fa-youtube text-2xl text-red-500"></i>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    @else
                        {{-- Fallback Graphic Block when no files are mapped --}}
                        <div
                            class="flex flex-col items-center justify-center rounded-xl border border-dashed border-gray-200 bg-gray-50 py-12 text-center"
                        >
                            <div
                                class="mb-3 flex h-16 w-16 items-center justify-center rounded-full border border-green-100 bg-green-50 text-green-500 shadow-sm"
                            >
                                <i class="fa-solid fa-seedling text-2xl"></i>
                            </div>
                            <p class="text-sm font-medium text-gray-500">No media uploads cataloged for this plant library entry.</p>
                        </div>
                    @endif
                </div>

                {{-- Plant Description --}}
                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
                    <h2 class="mb-4 border-b border-gray-100 pb-3 text-lg font-bold text-gray-900">
                        Description Profile
                    </h2>
                    <div class="prose max-w-none text-sm leading-relaxed whitespace-pre-line text-gray-600">
                        {{ $plantLibrary->description ?: 'No structured description profile has been documented for this entry yet.' }}
                    </div>
                </div>

                {{-- Plant Care Guides Content --}}
                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
                    <h2 class="mb-6 border-b border-gray-100 pb-3 text-lg font-bold text-gray-900">
                        Comprehensive Care Knowledge Base
                    </h2>

                    @if (!empty($plantLibrary->product_guide))
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            @foreach ($plantLibrary->product_guide as $guide)
                                <div
                                    class="rounded-xl border border-gray-100 bg-gray-50 p-5 shadow-2xs transition-all hover:bg-gray-100/60"
                                >
                                    <div
                                        class="text-brand-700 mb-2 flex items-center gap-2 text-sm font-bold tracking-wide uppercase"
                                    >
                                        @if (Str::contains(Str::lower($guide['title']), 'water'))
                                            <i class="fa-solid fa-droplet text-blue-500"></i>
                                        @elseif (Str::contains(Str::lower($guide['title']), 'sun') || Str::contains(Str::lower($guide['title']), 'light'))
                                            <i class="fa-solid fa-sun text-amber-500"></i>
                                        @elseif (Str::contains(Str::lower($guide['title']), 'about'))
                                            <i class="fa-solid fa-leaf text-green-500"></i>
                                        @elseif (Str::contains(Str::lower($guide['title']), 'planting') || Str::contains(Str::lower($guide['title']), 'guide'))
                                            <i class="fa-solid fa-seedling text-emerald-500"></i>
                                        @elseif (Str::contains(Str::lower($guide['title']), 'fertilizer'))
                                            <i class="fa-solid fa-flask text-purple-500"></i>
                                        @elseif (Str::contains(Str::lower($guide['title']), 'temperature') || Str::contains(Str::lower($guide['title']), 'ideal'))
                                            <i class="fa-solid fa-temperature-half text-red-500"></i>
                                        @elseif (Str::contains(Str::lower($guide['title']), 'origin'))
                                            <i class="fa-solid fa-globe text-teal-500"></i>
                                        @elseif (Str::contains(Str::lower($guide['title']), 'scientific') || Str::contains(Str::lower($guide['title']), 'detail'))
                                            <i class="fa-solid fa-microscope text-indigo-500"></i>
                                        @else
                                            <i class="fa-solid fa-circle-info text-gray-500"></i>
                                        @endif
                                        {{ $guide['title'] }}
                                    </div>
                                    <p class="text-sm leading-normal whitespace-pre-line text-gray-600">
                                        {{ $guide['description'] }}
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-400 italic">No structured care guides or accordion matrices configured.</p>
                    @endif
                </div>
            </div>

            {{-- Right Column: Side Quick Specs Metadata Panel --}}
            <div class="space-y-8">
                {{-- Quick Specifications Matrix --}}
                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
                    <h2 class="mb-4 border-b border-gray-100 pb-3 text-base font-bold text-gray-900">
                        Technical Specifications
                    </h2>

                    <div class="space-y-4">
                        <div>
                            <span class="block text-xs font-semibold tracking-wider text-gray-400 uppercase"
                                >Visibility Status</span
                            >
                            <div class="mt-1 flex items-center gap-2">
                                @if ($plantLibrary->is_active)
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-full border border-green-200 bg-green-50 px-2.5 py-1 text-xs font-bold text-green-700"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span> Active Catalog
                                    </span>
                                @else
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-full border border-gray-200 bg-gray-50 px-2.5 py-1 text-xs font-bold text-gray-600"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span> System Draft
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="border-t border-gray-50 pt-3">
                            <span class="block text-xs font-semibold tracking-wider text-gray-400 uppercase"
                                >Primary Category Hierarchy</span
                            >
                            <p class="mt-0.5 text-sm font-bold text-gray-900">
                                {{ $plantLibrary->category_name ?: 'Unassigned Classification' }}
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-4 border-t border-gray-50 pt-3">
                            <div>
                                <span class="block text-xs font-semibold tracking-wider text-gray-400 uppercase"
                                    >Inventory Type</span
                                >
                                <span
                                    class="mt-1 inline-block rounded border border-gray-200 bg-gray-100 px-2 py-0.5 text-[10px] font-bold text-gray-700 uppercase"
                                >
                                    {{ $plantLibrary->type }}
                                </span>
                            </div>
                            <div>
                                <span class="block text-xs font-semibold tracking-wider text-gray-400 uppercase"
                                    >Product Model</span
                                >
                                <span
                                    class="mt-1 inline-block rounded px-2 py-0.5 text-[10px] font-bold uppercase {{ $plantLibrary->product_type === 'catalog' ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-blue-50 text-blue-700 border border-blue-200' }}"
                                >
                                    {{ $plantLibrary->product_type }}
                                </span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4 border-t border-gray-50 pt-3">
                            <div>
                                <span class="block text-xs font-semibold tracking-wider text-gray-400 uppercase"
                                    >Default Unit Mapping</span
                                >
                                <p class="mt-0.5 font-mono text-sm font-semibold text-gray-900">
                                    {{ $plantLibrary->unit_short_name ?: '—' }}
                                </p>
                            </div>
                            <div>
                                <span class="block text-xs font-semibold tracking-wider text-gray-400 uppercase"
                                    >Sort Execution Weight</span
                                >
                                <p class="mt-0.5 font-mono text-sm font-semibold text-gray-900">
                                    {{ $plantLibrary->sort_order }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Tenant Informational Card --}}
                <div class="rounded-2xl border border-blue-100 bg-blue-50 p-6 text-sm text-blue-800 shadow-xs">
                    <h3 class="mb-1.5 flex items-center gap-1.5 font-bold text-blue-900">
                        <i class="fa-solid fa-circle-info"></i> Master Catalog Distribution
                    </h3>
                    <p class="text-xs leading-normal text-blue-900/80">This record represents a global entity within the system configuration directory. Stores across different multi-tenant environments dynamically pull these details when importing plants into localized catalog inventories.</p>
                </div>
            </div>
        </div>
    </div>
@endsection
