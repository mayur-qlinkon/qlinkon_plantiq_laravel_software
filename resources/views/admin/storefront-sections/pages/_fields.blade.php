{{--
    Renders one template's fields.

    Driven entirely by PageTemplate::fields(), so adding a field to a template
    needs no change here. Names are data[<key>], which lands straight in the
    JSON column.

    $template  PageTemplate
    $values    array<string, ?string>  current values (old() already merged in)
--}}
@foreach ($template->fields() as $key => $field)
    @php
        $value = $values[$key] ?? null;
        $hasError = $errors->has("data.{$key}");
    @endphp

    <div class="mb-5">
        <label class="mb-1.5 block text-[11px] font-bold tracking-wider text-gray-500 uppercase">
            {{ $field['label'] }}
            @if ($field['required'] ?? false)
                <span class="text-red-500">*</span>
            @endif
        </label>

        @if ($field['type'] === App\Enums\PageFieldType::RichText)
            {{-- Trix keeps its value in the hidden input; the editor element
                 is only the chrome around it. --}}
            <input id="field_{{ $key }}" type="hidden" name="data[{{ $key }}]"
                value="{{ $value }}">
            <trix-editor input="field_{{ $key }}"
                class="trix-content min-h-[180px] rounded-lg border {{ $hasError ? 'border-red-400' : 'border-gray-200' }} bg-white px-3 py-2 text-[13px]"></trix-editor>
        @elseif ($field['type'] === App\Enums\PageFieldType::Textarea)
            <textarea name="data[{{ $key }}]" rows="4"
                class="w-full rounded-lg border {{ $hasError ? 'border-red-400' : 'border-gray-200' }} px-3 py-2 text-[13px] focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none">{{ $value }}</textarea>
        @elseif ($field['type'] === App\Enums\PageFieldType::Url)
            <input type="url" name="data[{{ $key }}]" value="{{ $value }}" placeholder="https://…"
                class="w-full rounded-lg border {{ $hasError ? 'border-red-400' : 'border-gray-200' }} px-3 py-2 text-[13px] focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none">
        @else
            <input type="text" name="data[{{ $key }}]" value="{{ $value }}"
                class="w-full rounded-lg border {{ $hasError ? 'border-red-400' : 'border-gray-200' }} px-3 py-2 text-[13px] focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none">
        @endif

        @if (!empty($field['help']))
            <p class="mt-1 text-[11px] text-gray-400">{{ $field['help'] }}</p>
        @endif

        @error("data.{$key}")
            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
        @enderror
    </div>
@endforeach
