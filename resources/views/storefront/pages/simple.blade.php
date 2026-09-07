<div class="mb-12 border-b border-gray-100 pb-8">
    <h1 class="text-3xl font-black tracking-tight text-gray-900 sm:text-4xl">
        {{ $page->field('heading') ?: $page->title }}
    </h1>
</div>

<div class="page-prose">{!! $page->field('body') !!}</div>
