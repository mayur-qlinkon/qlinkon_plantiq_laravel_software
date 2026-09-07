<x-mail.layout>
    <p style="margin:0 0 14px; font-size:14px; color:#111827;">Hi {{ $ownerName }},</p>

    <p style="margin:0 0 16px; font-size:14px; line-height:1.6; color:#374151;">
        A new <strong>{{ $scanType }}</strong> document was scanned on {{ $scanDate }}.
    </p>

    <p style="margin:0 0 8px; font-size:11px; font-weight:700; color:#9ca3af; text-transform:uppercase;">Extracted data
    </p>
    {{-- Unescaped deliberately: this table is built by buildOcrEmailTable() in
         our own controller, never from user input. --}}
    {!! $extractedHtml !!}

    @if ($rawText)
        <p style="margin:16px 0 8px; font-size:11px; font-weight:700; color:#9ca3af; text-transform:uppercase;">Raw text
        </p>
        <div
            style="padding:14px; background-color:#f3f4f6; border-radius:8px; font-family:monospace; font-size:12px; line-height:1.5; color:#374151; white-space:pre-wrap;">
            {{ $rawText }}</div>
    @endif

    <p style="margin:20px 0 0;">
        <a href="{{ $viewUrl }}"
            style="display:inline-block; padding:10px 20px; background-color:#108c2a; color:#ffffff; font-size:13px; font-weight:700; text-decoration:none; border-radius:8px;">
            View scan details
        </a>
    </p>
</x-mail.layout>
