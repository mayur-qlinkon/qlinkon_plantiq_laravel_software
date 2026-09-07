<div class="mb-12 border-b border-gray-100 pb-8 text-center">
    <h1 class="mb-4 text-3xl font-black tracking-tight text-gray-900 sm:text-5xl">
        {{ $page->field('hero_heading') ?: $page->title }}
    </h1>

    @if ($page->field('hero_text'))
        <p class="mx-auto max-w-2xl text-lg leading-relaxed whitespace-pre-line text-gray-500">
            {{ $page->field('hero_text') }}
        </p>
    @endif
</div>

@if ($page->field('story_heading'))
    <h2 class="mb-5 text-2xl font-extrabold text-gray-900">
        {{ $page->field('story_heading') }}
    </h2>
@endif

@if ($page->field('story_body'))
    {{-- Sanitised on save by PageContentSanitizer; safe to print as markup. --}}
    <div class="page-prose">{!! $page->field('story_body') !!}</div>
@endif
