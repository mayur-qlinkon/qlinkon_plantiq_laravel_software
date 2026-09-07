{{-- Pre-template page. The stored HTML was never checked when it was written,
     so it is cleaned on the way out — see Page::legacyHtml(). --}}
<div class="mb-12 border-b border-gray-100 pb-8 text-center">
    <h1 class="text-3xl font-black tracking-tight text-gray-900 sm:text-5xl">
        {{ $page->title }}
    </h1>
</div>

<div class="page-prose">{!! $page->legacyHtml() !!}</div>
