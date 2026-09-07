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

<div class="page-prose">{!! $page->field('body') !!}</div>
