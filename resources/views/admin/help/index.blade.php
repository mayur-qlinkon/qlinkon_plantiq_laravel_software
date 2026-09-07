@extends ('layouts.admin')
@section ('title', 'Help Center | Plantiq')

@section ('header-title')
    <h1 class="text-sm font-bold tracking-widest text-gray-500 uppercase">Help Center</h1>
@endsection

@section ('content')
    <div class="mx-auto w-full space-y-10 px-4 py-8 sm:px-6 lg:px-8">
        {{-- ═══════════════════════════════════════════
             AI CHATBOT POPUP
        ═══════════════════════════════════════════ --}}
        <x-modals.ai-chatbot-popup />

        {{-- 1. HERO & SEARCH SECTION --}}
        <div
            class="from-brand-50 relative overflow-hidden rounded-3xl border border-gray-100 bg-gradient-to-b to-white px-6 py-12 text-center shadow-sm sm:px-12 sm:py-16"
        >
            {{-- Decorative background elements (Optional but makes UI mast) --}}
            <div class="bg-brand-100/50 absolute -top-10 -left-10 h-40 w-40 rounded-full blur-3xl"></div>
            <div class="absolute -right-10 -bottom-10 h-40 w-40 rounded-full bg-blue-100/50 blur-3xl"></div>

            <div class="relative z-10">
                <h1 class="mb-4 text-3xl font-extrabold tracking-tight text-gray-900 sm:text-4xl md:text-5xl">
                    Hi, how can we help you?
                </h1>
                <p class="mx-auto mb-10 max-w-2xl text-base text-gray-500 sm:text-lg">Type your question below or search for keywords to find solutions instantly.</p>

                {{-- Modern Search Bar --}}
                <form action="{{ route('admin.help.index') }}" method="GET" class="relative mx-auto max-w-3xl">
                    <div
                        class="focus-within:border-brand-500 focus-within:shadow-brand-500/10 relative flex h-16 w-full items-center overflow-hidden rounded-2xl border-2 border-gray-200 bg-white transition-all duration-300 focus-within:shadow-lg"
                    >
                        <div class="grid h-full w-14 place-items-center text-gray-400">
                            <i class="fa-solid fa-magnifying-glass text-lg"></i>
                        </div>
                        <input
                            type="text"
                            name="search"
                            class="h-full w-full border-none bg-transparent pr-32 text-base text-gray-700 placeholder-gray-400 outline-none focus:ring-0"
                            placeholder="E.g., How to reset my password?"
                            value="{{ request('search') }}"
                            autocomplete="off"
                        />
                        <div class="absolute top-2 right-2 bottom-2">
                            <button
                                type="submit"
                                class="bg-brand-600 hover:bg-brand-700 flex h-full items-center justify-center rounded-xl px-6 font-semibold text-white transition-colors active:scale-95"
                            >
                                Search
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- CONDITIONAL RENDERING: Search Results vs Landing Page --}}
        @if (request()->filled('search'))
            {{-- SEARCH RESULTS --}}
            <div class="space-y-6">
                <div
                    class="flex flex-col justify-between gap-4 border-b border-gray-200 pb-4 sm:flex-row sm:items-center"
                >
                    <h2 class="text-2xl font-bold text-gray-900">
                        Search Results for <span class="text-brand-600">"{{ request('search') }}"</span>
                    </h2>
                    <a
                        href="{{ route('admin.help.index') }}"
                        class="hover:text-brand-600 inline-flex items-center gap-2 text-sm font-semibold text-gray-500 transition-colors"
                    >
                        <i class="fa-solid fa-xmark"></i> Clear Search
                    </a>
                </div>

                <div class="grid grid-cols-1 gap-4">
                    @forelse ($articles as $article)
                        <a
                            href="{{ route('admin.help.show', $article->slug) }}"
                            class="group hover:border-brand-200 block rounded-2xl border border-gray-100 bg-white p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-md"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h3
                                        class="group-hover:text-brand-600 mb-2 text-lg font-bold text-gray-900 transition-colors"
                                    >
                                        {{ $article->title }}
                                    </h3>
                                    <p class="mb-4 line-clamp-2 text-sm text-gray-500">
                                        {{ Str::limit(strip_tags(Str::markdown($article->content ?? '')), 200) }}
                                    </p>
                                    <div class="flex flex-wrap items-center gap-4 text-xs font-semibold text-gray-400">
                                        <span
                                            class="flex items-center gap-1.5 rounded-full bg-gray-50 px-3 py-1 text-gray-600"
                                        >
                                            <i class="fa-regular fa-folder text-brand-500"></i>
                                            {{ $article->category->title }}
                                        </span>
                                    </div>
                                </div>
                                <div
                                    class="group-hover:bg-brand-50 group-hover:text-brand-600 hidden h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gray-50 text-gray-300 transition-colors sm:flex"
                                >
                                    <i class="fa-solid fa-arrow-right"></i>
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="rounded-3xl border border-gray-100 bg-white p-16 text-center shadow-sm">
                            <div
                                class="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-full bg-gray-50"
                            >
                                <i class="fa-solid fa-magnifying-glass-minus text-3xl text-gray-300"></i>
                            </div>
                            <h3 class="mb-2 text-lg font-bold text-gray-900">No solutions found</h3>
                            <p class="text-gray-500">We couldn't find any articles matching your search. Try different keywords or contact support.</p>
                        </div>
                    @endforelse
                </div>

                {{-- Pagination --}}
                @if ($articles->hasPages())
                    <div class="mt-8">{{ $articles->links() }}</div>
                @endif
            </div>

        @else
            {{-- 2. POPULAR / QUICK ANSWERS --}}
            @php
                $popularArticles = \App\Models\Platform\HelpArticle::published()->ordered()->take(3)->get();
            @endphp

            @if ($popularArticles->isNotEmpty())
                <div class="space-y-4">
                    <h2 class="flex items-center gap-2 text-lg font-bold text-gray-900">
                        <i class="fa-solid fa-fire text-orange-500"></i> Quick Answers
                    </h2>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        @foreach ($popularArticles as $article)
                            <a
                                href="{{ route('admin.help.show', $article->slug) }}"
                                class="group hover:border-brand-300 flex flex-col justify-between rounded-2xl border border-gray-100 bg-white p-5 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-md"
                            >
                                <div>
                                    <div
                                        class="group-hover:bg-brand-50 group-hover:text-brand-600 mb-3 inline-flex h-10 w-10 items-center justify-center rounded-xl bg-orange-50 text-orange-500 transition-colors"
                                    >
                                        <i class="fa-regular fa-file-lines text-lg"></i>
                                    </div>
                                    <h3 class="group-hover:text-brand-600 mb-1 leading-tight font-bold text-gray-900">
                                        {{ $article->title }}
                                    </h3>
                                </div>
                                <div
                                    class="text-brand-600 mt-4 flex items-center justify-end text-sm font-semibold opacity-0 transition-opacity group-hover:opacity-100"
                                >
                                    Read more <i class="fa-solid fa-arrow-right ml-1"></i>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- 3. BROWSE BY CATEGORY --}}
            <div class="space-y-6 pt-6">
                <h2 class="flex items-center gap-2 text-lg font-bold text-gray-900">
                    <i class="fa-solid fa-layer-group text-brand-500"></i> Browse by Topic
                </h2>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($categories as $index => $category)
                        <div
                            class="flex h-full flex-col overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm transition-all duration-300 hover:shadow-lg"
                        >
                            {{-- Category Header --}}
                            <div class="border-b border-gray-50 bg-gray-50/50 p-6">
                                <div class="flex items-center gap-4">
                                    <div
                                        class="bg-brand-100 text-brand-600 flex h-12 w-12 shrink-0 items-center justify-center rounded-xl"
                                    >
                                        {{-- Using FontAwesome. If DB has old lucide tags, use a generic fallback or update DB --}}
                                        <i class="fa-solid fa-{{ $category->icon ?? 'folder-open' }} text-xl"></i>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-extrabold text-gray-900">{{ $category->title }}</h3>
                                    </div>
                                </div>
                            </div>

                            {{-- Article Links --}}
                            <div class="flex-grow p-6">
                                <ul class="space-y-4">
                                    @foreach ($category->publishedArticles->take(4) as $article)
                                        <li>
                                            <a
                                                href="{{ route('admin.help.show', $article->slug) }}"
                                                class="group hover:text-brand-600 flex items-start gap-3 text-sm font-medium text-gray-600 transition-colors"
                                            >
                                                <i
                                                    class="fa-solid fa-chevron-right group-hover:text-brand-500 mt-1 text-[10px] text-gray-300 transition-colors"
                                                ></i>
                                                <span class="line-clamp-2 leading-relaxed">{{ $article->title }}</span>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>

                            {{-- View All Link (Optional, if category has more than 4 articles) --}}
                            @if ($category->publishedArticles->count() > 4)
                                <div class="bg-gray-50 px-6 py-3">
                                    <a
                                        href="{{ route('admin.help.index', ['category' => $category->slug]) }}"
                                        class="text-brand-600 hover:text-brand-700 text-sm font-bold"
                                    >
                                        View all {{ $category->publishedArticles->count() }} articles
                                        <i class="fa-solid fa-arrow-right-long ml-1"></i>
                                    </a>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- 4. CONTACT SUPPORT FOOTER (Sleek UI) --}}
            <div
                class="mt-8 flex flex-col items-center justify-between gap-6 rounded-3xl bg-gray-900 p-8 shadow-xl md:flex-row md:px-12 md:py-10"
            >
                <div class="flex items-center gap-6 text-center md:text-left">
                    <div class="hidden h-16 w-16 shrink-0 items-center justify-center rounded-full bg-gray-800 md:flex">
                        <i class="fa-solid fa-headset text-2xl text-white"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-white">Can't find what you're looking for?</h3>
                        <p class="mt-1 text-sm text-gray-400">Our support team is ready to assist you right away.</p>
                    </div>
                </div>
                <button
                    onclick="window.openContactModal()"
                    type="button"
                    class="group bg-brand-500 hover:bg-brand-400 flex w-full items-center justify-center gap-2 rounded-xl px-8 py-3.5 text-sm font-bold text-white transition-all active:scale-95 md:w-auto"
                >
                    <i
                        class="fa-solid fa-paper-plane transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5"
                    ></i>
                    Submit a Request
                </button>
            </div>
        @endif
    </div>

    {{-- Contact Modal Component --}}
    <x-modals.contact-inquiry-modal />

@endsection
