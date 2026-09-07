@extends('layouts.storefront')

@section('title', $page->meta_title)

{{-- The layout yields 'meta', not 'meta_description'. This page defined the
     latter, so the description never reached the document at all. --}}
@section('meta')
    <meta name="description" content="{{ $page->seo_description ?? "Read the {$page->title} for {$company->name}." }}">
@endsection

@push('styles')
    <style>
        /*
                         * Applies only to sanitised rich-text blocks and legacy HTML. Plain
                         * text fields are printed as escaped strings and styled inline.
                         */
        .page-prose {
            color: #4b5563;
            line-height: 1.8;
            font-size: 1.05rem;
        }

        .page-prose h2,
        .page-prose h3,
        .page-prose h4 {
            color: #111827;
            font-weight: 800;
            margin-top: 2em;
            margin-bottom: 1em;
            line-height: 1.3;
        }

        .page-prose h2 {
            font-size: 1.75rem;
            border-bottom: 1px solid #f3f4f6;
            padding-bottom: .3em;
        }

        .page-prose h3 {
            font-size: 1.35rem;
        }

        .page-prose p {
            margin-bottom: 1.5em;
        }

        .page-prose a {
            color: var(--brand-600);
            text-decoration: underline;
            font-weight: 600;
        }

        .page-prose ul {
            list-style-type: disc;
            padding-left: 1.5em;
            margin-bottom: 1.5em;
        }

        .page-prose ol {
            list-style-type: decimal;
            padding-left: 1.5em;
            margin-bottom: 1.5em;
        }

        .page-prose li {
            margin-bottom: .5em;
        }

        .page-prose img {
            max-width: 100%;
            height: auto;
            border-radius: .75rem;
            margin: 2em 0;
        }

        .page-prose table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2em;
        }

        .page-prose th,
        .page-prose td {
            border: 1px solid #e5e7eb;
            padding: .75rem;
            text-align: left;
        }

        .page-prose th {
            background-color: #f9fafb;
            font-weight: 700;
        }

        .page-prose blockquote {
            border-left: 4px solid var(--brand-500);
            background: #f9fafb;
            padding: 1em 1.5em;
            margin: 1.5em 0;
            border-radius: 0 8px 8px 0;
            font-style: italic;
        }
    </style>
@endpush

@section('content')
    <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
        {{-- Each template renders its own heading, so there is no shared one
             here. The dispatcher only supplies the shell. --}}
        @include($page->template->view())

        <p
            class="mt-16 border-t border-gray-100 pt-6 text-center text-xs font-medium tracking-widest text-gray-400 uppercase">
            Last updated on {{ $page->updated_at->format('F j, Y') }}
        </p>
    </div>
@endsection
