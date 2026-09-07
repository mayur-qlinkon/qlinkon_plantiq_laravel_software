@extends('layouts.admin')

@section('title', $article->title . ' | Help Center | Qlinkon')

@section('header-title')
    <h1 class="text-sm font-bold text-gray-500 uppercase tracking-widest">Help Center</h1>
@endsection

@push('styles')
<style>
    /* 1. Custom Typography & Styling for Rendered Markdown */
    .article-content { color: #374151; font-size: 1rem; line-height: 1.75; }
    
    /* Headings */
    .article-content h2 { font-size: 1.5rem; font-weight: 700; color: #111827; margin-top: 2.5rem; margin-bottom: 1.25rem; border-bottom: 1px solid #f3f4f6; padding-bottom: 0.5rem; scroll-margin-top: 6rem; }
    .article-content h3 { font-size: 1.25rem; font-weight: 600; color: #1f2937; margin-top: 2rem; margin-bottom: 1rem; scroll-margin-top: 6rem; }
    
    /* Paragraphs & Inline Code */
    .article-content p { margin-bottom: 1.25rem; }
    .article-content code { background-color: #f3f4f6; color: #ef4444; padding: 0.125rem 0.375rem; border-radius: 0.375rem; font-size: 0.875em; font-family: ui-monospace, monospace; }
    
    /* Callout / Note Box (Rendered via Blockquotes in Markdown) */
    .article-content blockquote {
        border-left: 4px solid var(--brand-500);
        background-color: var(--color-brand-50);
        padding: 1.25rem 1.5rem;
        border-radius: 0 0.75rem 0.75rem 0;
        color: var(--brand-700);
        margin: 2rem 0;
        font-size: 0.95rem;
    }
    .article-content blockquote p:last-child { margin-bottom: 0; }
    .article-content blockquote strong { color: var(--brand-600); display: flex; align-items: center; gap: 0.375rem; margin-bottom: 0.5rem; font-size: 1.05em; }
    .article-content blockquote strong::before {
        content: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="%23{{ ltrim($primary ?? '4f46e5', '#') }}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>');
        display: inline-block;
        width: 16px; height: 16px;
    }

    /* Numbered Steps */
    .article-content ol { counter-reset: step-counter; list-style-type: none; padding-left: 0; margin-top: 1.5rem; margin-bottom: 2rem; }
    .article-content ol li { position: relative; padding-left: 3rem; margin-bottom: 1.25rem; }
    .article-content ol li::before {
        content: counter(step-counter);
        counter-increment: step-counter;
        position: absolute;
        left: 0; top: 0.125rem;
        width: 1.75rem; height: 1.75rem;
        background-color: var(--brand-600);
        color: white;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 0.875rem; font-weight: 600;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    /* Bullet Lists */
    .article-content ul { list-style-type: disc; padding-left: 1.5rem; margin-bottom: 1.5rem; }
    .article-content ul li { margin-bottom: 0.5rem; }
    
    /* Links */
    .article-content a { color: var(--brand-600); text-decoration: none; font-weight: 500; }
    .article-content a:hover { text-decoration: underline; }
</style>
@endpush

@section('content')

{{-- 2. Reading Progress Bar --}}
<div x-data="readingProgress" @scroll.window="calculate" class="fixed top-0 left-0 w-full h-1 z-50 bg-transparent">
    <div class="h-full bg-brand-500 transition-all duration-150 ease-out" :style="`width: ${percent}%`"></div>
</div>

<div class="w-full px-4 sm:px-6 lg:px-8 py-6 relative" x-data="articleSetup">
    
    {{-- 3. Robust Breadcrumb --}}
    <nav class="flex text-sm text-gray-500 font-medium mb-8 overflow-x-auto whitespace-nowrap hide-scrollbar pb-2" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-2">
            <li class="inline-flex items-center">
                <a href="{{ route('admin.help.index') }}" class="hover:text-brand-600 flex items-center gap-1.5 transition-colors">
                    <i data-lucide="life-buoy" class="w-4 h-4"></i> Help Center
                </a>
            </li>
            <li>
                <div class="flex items-center">
                    <i data-lucide="chevron-right" class="w-4 h-4 text-gray-300 mx-1"></i>
                    <a href="{{ route('admin.help.index') }}?category={{ $article->category->slug }}" class="hover:text-brand-600 transition-colors">
                        {{ $article->category->title }}
                    </a>
                </div>
            </li>
            <li aria-current="page">
                <div class="flex items-center">
                    <i data-lucide="chevron-right" class="w-4 h-4 text-gray-300 mx-1"></i>
                    <span class="text-gray-900 font-semibold truncate max-w-[200px] sm:max-w-md">{{ $article->title }}</span>
                </div>
            </li>
        </ol>
    </nav>

    <div class="flex flex-col lg:flex-row gap-8 xl:gap-12 pb-12">
        
        {{-- MAIN CONTENT AREA --}}
        <main class="flex-1 lg:max-w-[70%]">
            <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-6 sm:p-8 md:p-10">
                
                {{-- Meta Info --}}
                <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-brand-50 text-brand-700 rounded-lg text-sm font-semibold">
                            <i class="fa-solid fa-{{ $category->icon ?? 'folder-open' }} h-4 w-4"></i>
                            {{ $article->category->title }}
                        </span>
                        <span class="text-sm text-gray-400 flex items-center gap-1">
                            <i data-lucide="clock" class="w-4 h-4"></i> {{ $article->reading_time }} min read
                        </span>
                    </div>
                    <span class="text-sm text-gray-400 flex items-center gap-1">
                        <i data-lucide="calendar" class="w-4 h-4"></i> Updated {{ $article->updated_at->diffForHumans() }}
                    </span>
                </div>

                {{-- Title --}}
                <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-8 leading-tight">{{ $article->title }}</h1>

                {{-- 4. Video Embed Thumbnail --}}
                @if($article->hasVideo())
                <div class="relative w-full aspect-video bg-gray-900 rounded-2xl overflow-hidden mb-10 shadow-lg group">
                    <iframe src="{{ $article->youtube_embed_url }}" class="absolute inset-0 w-full h-full border-0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                </div>
                @endif

                {{-- 5. Rendered Markdown Content --}}
                <div class="article-content" x-ref="contentContainer">
                    {!! $renderedContent !!}
                </div>
            </div>

            {{-- 6. "Was this helpful?" Feedback Bar --}}
            <div class="mt-8 border border-gray-100 bg-white rounded-2xl p-5 sm:p-6 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4" 
                 x-data="feedbackWidget('{{ route('admin.help.feedback', $article->slug) }}')">
                <div class="flex items-center gap-3 text-gray-800">
                    <div class="w-10 h-10 bg-gray-50 rounded-full flex items-center justify-center shrink-0">
                        <i data-lucide="message-square" class="w-5 h-5 text-gray-500"></i>
                    </div>
                    <span class="font-semibold">Was this article helpful?</span>
                </div>
                
                {{-- Buttons --}}
                <div class="flex items-center gap-3" x-show="!submitted" x-cloak>
                    <button @click="submit(true)" :disabled="loading" class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 hover:border-brand-300 hover:bg-brand-50 hover:text-brand-600 text-gray-600 font-medium rounded-xl transition-all focus:ring-2 focus:ring-brand-500/20 disabled:opacity-50">
                        <i data-lucide="thumbs-up" class="w-4 h-4"></i> Yes
                    </button>
                    <button @click="submit(false)" :disabled="loading" class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 hover:border-red-300 hover:bg-red-50 hover:text-red-600 text-gray-600 font-medium rounded-xl transition-all focus:ring-2 focus:ring-red-500/20 disabled:opacity-50">
                        <i data-lucide="thumbs-down" class="w-4 h-4"></i> No
                    </button>
                </div>

                {{-- Success Message --}}
                <div x-show="submitted" x-cloak class="flex items-center gap-2 text-emerald-600 font-medium px-4 py-2 bg-emerald-50 rounded-xl">
                    <i data-lucide="check-circle-2" class="w-5 h-5"></i>
                    <span x-text="message"></span>
                </div>
            </div>

            {{-- 7. More in Category (Related) --}}
            @php
                // Fetch up to 4 other articles in the same category
                $relatedArticles = $article->category->publishedArticles
                    ->where('id', '!=', $article->id)
                    ->take(4);
            @endphp
            
            @if($relatedArticles->isNotEmpty())
            <div class="mt-8 border border-gray-100 bg-white rounded-2xl p-6 shadow-sm">
                <h3 class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-4 flex items-center gap-2">
                    <i data-lucide="folder-open" class="w-4 h-4"></i> More in {{ $article->category->title }}
                </h3>
                <div class="space-y-1">
                    @foreach($relatedArticles as $rel)
                    <a href="{{ route('admin.help.show', $rel->slug) }}" class="flex items-center justify-between group p-3 -mx-3 hover:bg-gray-50 rounded-xl transition-colors">
                        <div class="flex items-center gap-3 text-sm font-medium text-gray-700 group-hover:text-brand-600">
                            <i data-lucide="file-text" class="w-4 h-4 text-gray-400 group-hover:text-brand-500"></i>
                            {{ $rel->title }}
                        </div>
                        <i data-lucide="chevron-right" class="w-4 h-4 text-gray-300 group-hover:text-brand-500 transition-transform group-hover:translate-x-1"></i>
                    </a>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- 8. Prev / Next Navigation --}}
            <div class="mt-8 grid grid-cols-1 sm:grid-cols-2 gap-4">
                @if($prevArticle)
                <a href="{{ route('admin.help.show', $prevArticle->slug) }}" class="flex flex-col border border-gray-100 bg-white rounded-2xl p-5 hover:border-brand-200 hover:shadow-md transition-all text-left group">
                    <span class="text-xs text-gray-400 font-semibold uppercase tracking-wider mb-2 flex items-center gap-1.5">
                        <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i> Previous
                    </span>
                    <span class="font-medium text-gray-900 group-hover:text-brand-600 line-clamp-2 leading-snug">{{ $prevArticle->title }}</span>
                </a>
                @else
                <div class="hidden sm:block"></div>
                @endif

                @if($nextArticle)
                <a href="{{ route('admin.help.show', $nextArticle->slug) }}" class="flex flex-col border border-gray-100 bg-white rounded-2xl p-5 hover:border-brand-200 hover:shadow-md transition-all text-right group">
                    <span class="text-xs text-gray-400 font-semibold uppercase tracking-wider mb-2 flex items-center justify-end gap-1.5">
                        Next <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                    </span>
                    <span class="font-medium text-gray-900 group-hover:text-brand-600 line-clamp-2 leading-snug">{{ $nextArticle->title }}</span>
                </a>
                @endif
            </div>
        </main>

        {{-- 9. SIDEBAR (TOC + Contact) --}}
        <aside class="hidden lg:block w-72 shrink-0">
            <div class="sticky top-24 space-y-6">
                
                {{-- Table of Contents --}}
                <div class="bg-white border border-gray-100 rounded-3xl p-6 shadow-sm" x-show="headings.length > 0" x-cloak>
                    <h4 class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-4">On this page</h4>
                    
                    <ul class="space-y-1 relative before:absolute before:inset-y-0 before:left-[11px] before:w-px before:bg-gray-100">
                        <template x-for="h in headings" :key="h.id">
                            <li class="relative">
                                {{-- Active Indicator Dot --}}
                                <div x-show="activeId === h.id" class="absolute left-[9px] top-1/2 -translate-y-1/2 w-[5px] h-[5px] rounded-full bg-brand-500 z-10 transition-opacity"></div>
                                
                                <a @click.prevent="scrollTo(h.id)" :href="'#'+h.id"
                                   class="block text-sm py-1.5 pl-6 transition-colors cursor-pointer rounded-r-lg"
                                   :class="{
                                      'font-semibold text-brand-600 bg-brand-50/50': activeId === h.id,
                                      'text-gray-500 hover:text-gray-900': activeId !== h.id,
                                      'pl-9 text-xs': h.level === 'h3'
                                   }"
                                   x-text="h.text"></a>
                            </li>
                        </template>
                    </ul>
                </div>

                {{-- Support CTA --}}
                <div class="bg-brand-50 rounded-3xl p-6 text-center border border-brand-100">
                    <h4 class="font-bold text-brand-900 mb-2">Need more help?</h4>
                    <p class="text-sm text-brand-700 mb-4 leading-relaxed">Our support team is always ready to assist you.</p>
                    <button onclick="window.openContactModal()" type="button" class="inline-flex w-full items-center justify-center gap-2 bg-brand-600 hover:bg-brand-700 text-white font-medium text-sm px-4 py-2.5 rounded-xl transition-colors shadow-sm">
                        <i data-lucide="messages-square" class="h-4 w-4"></i> Submit a Request
                    </button>
                </div>

            </div>
        </aside>
    </div>
</div>

<x-modals.contact-inquiry-modal />

@push('scripts')
<script>
    // ─────────────────────────────────────────────────────────────────────────
    // SPA-SAFE Alpine Component Registration
    (function registerHelpPageAlpine() {

        function register() {

            // --- Widget 1: Scroll Progress ---
            Alpine.data('readingProgress', () => ({
                percent: 0,
                calculate() {
                    let winScroll = document.body.scrollTop || document.documentElement.scrollTop;
                    let height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
                    this.percent = height > 0 ? Math.round((winScroll / height) * 100) : 0;
                }
            }));

            // --- Widget 2: Auto-TOC Generation ---
            Alpine.data('articleSetup', () => ({
                headings: [],
                activeId: null,
                init() {
                    this.$nextTick(() => {
                        // Guard: $refs may not be bound if Alpine is still settling
                        const container = this.$refs.contentContainer;
                        if (!container) return;

                        // Extract H2 and H3 from the article content
                        const els = container.querySelectorAll('h2, h3');

                        els.forEach((el) => {
                            // Assign ID if it doesn't exist (required for scroll-linking)
                            if (!el.id) {
                                el.id = 'heading-' + el.innerText.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)+/g, '');
                            }
                            this.headings.push({
                                id: el.id,
                                text: el.innerText,
                                level: el.tagName.toLowerCase()
                            });
                        });

                        // Set up scroll spying to highlight active TOC item
                        if (this.headings.length > 0) {
                            const observer = new IntersectionObserver((entries) => {
                                let visible = entries.filter(e => e.isIntersecting);
                                if (visible.length > 0) {
                                    this.activeId = visible[0].target.id;
                                }
                            }, { rootMargin: '-15% 0px -80% 0px' });

                            els.forEach(el => observer.observe(el));
                            this.activeId = this.headings[0].id; // default to first
                        }
                    });
                },
                scrollTo(id) {
                    document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }));

            // --- Widget 3: AJAX Feedback Form ---
            Alpine.data('feedbackWidget', (endpointUrl) => ({
                loading: false,
                submitted: false,
                message: '',
                async submit(isHelpful) {
                    this.loading = true;
                    try {
                        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

                        const res = await fetch(endpointUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ is_helpful: isHelpful })
                        });

                        const data = await res.json();

                        if (data.success) {
                            this.message = data.message;
                            this.submitted = true;
                        }
                    } catch (e) {
                        console.error("Feedback submission failed:", e);
                    } finally {
                        this.loading = false;
                    }
                }
            }));
        }

        if (window.Alpine) {
            // SPA navigation: Alpine is already running — register immediately so
            // Alpine.initTree() (called right after this script) finds the factories.
            register();
        } else {
            // Fresh page load: Alpine hasn't booted yet — queue with { once: true }
            // so this listener auto-removes itself and never accumulates.
            document.addEventListener('alpine:init', register, { once: true });
        }

    })();
</script>
@endpush
@endsection