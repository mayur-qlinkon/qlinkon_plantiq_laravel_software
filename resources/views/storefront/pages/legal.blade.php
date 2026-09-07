<div class="mb-12 border-b border-gray-100 pb-8">
    <h1 class="mb-3 text-3xl font-black tracking-tight text-gray-900 sm:text-4xl">
        {{ $page->field('heading') ?: $page->title }}
    </h1>

    @if ($page->field('effective_date'))
        <p class="text-sm font-medium text-gray-500">
            Effective from {{ $page->field('effective_date') }}
        </p>
    @endif
</div>

<div class="page-prose">{!! $page->field('body') !!}</div>
