@extends('layouts.admin')

@section('title', 'Create Page')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/trix.css') }}">
    <style>
        /* Only bold, italic, lists and links survive the sanitiser, so the rest
                           of Trix's toolbar is hidden rather than left to produce markup that
                           gets stripped on save. */
        trix-toolbar .trix-button--icon-heading-1,
        trix-toolbar .trix-button--icon-quote,
        trix-toolbar .trix-button--icon-code,
        trix-toolbar .trix-button--icon-strike,
        trix-toolbar .trix-button-group--file-tools {
            display: none !important;
        }

        trix-editor {
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 14px;
            min-height: 180px;
            background: #fff;
        }

        trix-editor:focus {
            border-color: var(--brand-500);
            box-shadow: 0 0 0 4px color-mix(in srgb, var(--brand-500) 10%, transparent);
            outline: none;
        }

        .form-label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: #4b5563;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .form-input {
            width: 100%;
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 14px;
            color: #1f2937;
            background: #fff;
            transition: all 150ms;
            outline: none;
        }

        .form-input:focus {
            border-color: var(--brand-500);
            box-shadow: 0 0 0 4px color-mix(in srgb, var(--brand-500) 10%, transparent);
        }

        .input-group {
            display: flex;
            align-items: stretch;
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            overflow: hidden;
            transition: all 150ms;
            background: #fff;
        }

        .input-group:focus-within {
            border-color: var(--brand-500);
            box-shadow: 0 0 0 4px color-mix(in srgb, var(--brand-500) 10%, transparent);
        }

        .input-group-prefix {
            display: flex;
            align-items: center;
            padding: 0 12px;
            background: #f9fafb;
            border-right: 1.5px solid #e5e7eb;
            color: #9ca3af;
            font-size: 13px;
            font-weight: 500;
            user-select: none;
        }

        .input-group-field {
            flex: 1;
            width: 100%;
            padding: 10px 12px;
            font-size: 13px;
            color: #1f2937;
            background: transparent;
            outline: none;
            border: none;
        }

        .card-box {
            background: #fff;
            border: 1px solid #f1f5f9;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        }

        .tpl-card {
            border: 1.5px solid #e5e7eb;
            border-radius: 12px;
            padding: 14px;
            cursor: pointer;
            transition: all 150ms;
            background: #fff;
        }

        .tpl-card:hover {
            background: #f9fafb;
        }

        .tpl-card.is-active {
            border-color: var(--brand-500);
            background: color-mix(in srgb, var(--brand-500) 5%, #fff);
        }
    </style>
@endpush

@section('header-title')
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.pages.index') }}"
            class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-gray-200 text-gray-500 hover:bg-gray-50 transition-colors">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
        </a>
        <div>
            <h1 class="text-sm font-bold text-gray-500 uppercase tracking-widest">Create Page</h1>
        </div>
    </div>
@endsection

@section('content')
    <div class="pb-10 w-full max-w-[1600px] mx-auto" x-data="{ template: @js(old('template', '')) }">

        <div class="mb-6 flex flex-col sm:flex-row flex-wrap sm:items-center justify-between gap-4">
            <div class="w-full sm:w-auto">
                <x-admin.breadcrumb :items="[['label' => 'Pages', 'url' => route('admin.pages.index')], ['label' => 'Page Create']]" />
                <p class="text-sm text-gray-500 mt-1">Pick a layout, then fill in the text. No coding needed.</p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('admin.pages.index') }}"
                    class="bg-white hover:bg-gray-50 text-gray-700 border border-gray-200 px-5 py-2.5 rounded-lg text-sm font-bold transition-colors shadow-sm flex items-center gap-2">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Pages
                </a>
            </div>
        </div>

        @if ($errors->any())
            <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700">
                <div class="mb-2 flex items-center gap-2 font-bold">
                    <i data-lucide="triangle-alert" class="w-4 h-4"></i> Please fix the following:
                </div>
                <ul class="list-inside list-disc space-y-0.5 pl-2 text-xs font-medium">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.pages.store') }}" class="w-full">
            @csrf

            <div class="flex flex-col lg:flex-row gap-6 w-full">

                {{-- LEFT: layout choice + its fields --}}
                <div class="flex-1 min-w-0 space-y-6">

                    <div class="card-box">
                        <div class="mb-5">
                            <label class="form-label">Page Title <span class="text-red-500">*</span></label>
                            <input type="text" name="title" value="{{ old('title') }}" required
                                class="form-input text-lg font-bold" placeholder="e.g., Privacy Policy">
                            @error('title')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <label class="form-label">Choose a layout <span class="text-red-500">*</span></label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            @foreach ($templates as $tpl)
                                <label class="tpl-card" :class="template === @js($tpl->value) && 'is-active'">
                                    <div class="flex items-start gap-3">
                                        <input type="radio" name="template" value="{{ $tpl->value }}" x-model="template"
                                            class="mt-1 accent-brand-600">
                                        <div class="min-w-0">
                                            <p class="text-sm font-bold text-gray-800">{{ $tpl->label() }}</p>
                                            <p class="text-[11px] text-gray-400 leading-snug mt-0.5">
                                                {{ $tpl->description() }}
                                            </p>
                                        </div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                        @error('template')
                            <p class="text-red-500 text-xs mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    {{--
                        Every template's fields are rendered, and all but the chosen
                        one are disabled. Disabled inputs are not submitted, so the
                        other layouts' empty boxes never reach the data column — and
                        the server only validates the fields the chosen template
                        actually declares.
                    --}}
                    @foreach ($templates as $tpl)
                        <div class="card-box" x-show="template === @js($tpl->value)" x-cloak>
                            <h3
                                class="text-[12px] font-black text-gray-800 uppercase tracking-wider mb-5 pb-3 border-b border-gray-100">
                                {{ $tpl->label() }} content
                            </h3>

                            @foreach ($tpl->fields() as $key => $field)
                                @php
                                    $inputId = "f_{$tpl->value}_{$key}";
                                    $value = old("data.{$key}");
                                    $hasError = $errors->has("data.{$key}");
                                @endphp

                                <div class="mb-5">
                                    <label class="form-label">
                                        {{ $field['label'] }}
                                        @if ($field['required'] ?? false)
                                            <span class="text-red-500">*</span>
                                        @endif
                                    </label>

                                    @if ($field['type'] === App\Enums\PageFieldType::RichText)
                                        {{-- Trix keeps its value in the hidden input; the
                                             editor element is only the chrome around it. --}}
                                        <input id="{{ $inputId }}" type="hidden" name="data[{{ $key }}]"
                                            value="{{ $value }}"
                                            :disabled="template !== @js($tpl->value)">
                                        <trix-editor input="{{ $inputId }}"
                                            class="trix-content {{ $hasError ? 'border-red-400' : '' }}"></trix-editor>
                                    @elseif ($field['type'] === App\Enums\PageFieldType::Textarea)
                                        <textarea name="data[{{ $key }}]" rows="4" :disabled="template !== @js($tpl->value)"
                                            class="form-input text-[13px] {{ $hasError ? 'border-red-400' : '' }}">{{ $value }}</textarea>
                                    @elseif ($field['type'] === App\Enums\PageFieldType::Url)
                                        <input type="url" name="data[{{ $key }}]" value="{{ $value }}"
                                            placeholder="https://…" :disabled="template !== @js($tpl->value)"
                                            class="form-input {{ $hasError ? 'border-red-400' : '' }}">
                                    @else
                                        <input type="text" name="data[{{ $key }}]" value="{{ $value }}"
                                            :disabled="template !== @js($tpl->value)"
                                            class="form-input {{ $hasError ? 'border-red-400' : '' }}">
                                    @endif

                                    @if (!empty($field['help']))
                                        <p class="text-[11px] text-gray-400 mt-1.5 leading-snug">{{ $field['help'] }}</p>
                                    @endif

                                    @error("data.{$key}")
                                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            @endforeach
                        </div>
                    @endforeach

                    <div class="card-box text-center text-sm text-gray-400" x-show="!template" x-cloak>
                        Choose a layout above to start writing.
                    </div>
                </div>

                {{-- RIGHT: settings & SEO --}}
                <div class="w-full lg:w-[320px] xl:w-[360px] flex-shrink-0 space-y-6">

                    <div class="card-box bg-gray-50/50">
                        <div class="flex items-center justify-between mb-6">
                            <label class="form-label mb-0 text-gray-700">Visibility</label>
                            <div class="flex items-center gap-2">
                                <input type="hidden" name="is_published" value="0">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="is_published" value="1" class="sr-only peer"
                                        {{ old('is_published') ? 'checked' : '' }}>
                                    <div
                                        class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-500">
                                    </div>
                                </label>
                            </div>
                        </div>

                        <button type="submit" :disabled="!template"
                            class="w-full flex items-center justify-center gap-2 px-5 py-3 rounded-xl text-[13px] font-bold text-white transition-all hover:opacity-95 disabled:opacity-40 disabled:cursor-not-allowed"
                            style="background: var(--brand-600);">
                            <i data-lucide="plus" class="w-4 h-4"></i>
                            Create Page
                        </button>
                    </div>

                    <div class="card-box">
                        <h3
                            class="text-[12px] font-black text-gray-800 uppercase tracking-wider mb-5 pb-3 border-b border-gray-100">
                            Page Attributes</h3>

                        @php
                            $requiredSlugs = [
                                'return-policy' => 'Return Policy',
                                'faq' => 'FAQ',
                                'privacy-policy' => 'Privacy & Policy',
                                'terms-and-conditions' => 'Terms & Conditions',
                                'about-us' => 'About Us',
                                'contact-us' => 'Contact Us',
                            ];
                        @endphp

                        <div>
                            <label class="form-label">URL Slug</label>

                            <div class="input-group">
                                <span class="input-group-prefix">/page/</span>
                                <select name="slug" class="input-group-field font-mono">
                                    <option value="">Select page slug</option>
                                    @foreach ($requiredSlugs as $slug => $label)
                                        <option value="{{ $slug }}" @selected(old('slug') === $slug)>
                                            {{ $slug }} — {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <p class="text-[11px] text-gray-400 mt-1.5 leading-snug">
                                This slug is fixed so storefront links always work.
                            </p>

                            @error('slug')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="card-box">
                        <h3
                            class="text-[12px] font-black text-gray-800 uppercase tracking-wider mb-5 pb-3 border-b border-gray-100">
                            Search Engine (SEO)</h3>

                        <div class="mb-5">
                            <label class="form-label">Meta Title</label>
                            <input type="text" name="seo_title" value="{{ old('seo_title') }}" class="form-input"
                                placeholder="Title for Google search">
                            @error('seo_title')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="form-label">Meta Description</label>
                            <textarea name="seo_description" rows="4" class="form-input text-[13px]"
                                placeholder="A brief summary of this page...">{{ old('seo_description') }}</textarea>
                            @error('seo_description')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                </div>
            </div>
        </form>
    </div>

    <script src="{{ asset('assets/js/trix.umd.min.js') }}"></script>
    <script>
        // Images are not on the whitelist, so a dropped file would upload
        // nowhere and then be stripped anyway. Refusing it up front is clearer
        // than letting the user watch it vanish on save.
        document.addEventListener("trix-file-accept", (event) => event.preventDefault());
    </script>
@endsection
