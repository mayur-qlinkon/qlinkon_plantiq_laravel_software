@extends ('layouts.platform')

@section('title', 'Contact Inquiries')
@section('header', 'Contact Inquiries')

@section('content')
    <div class="pb-10">
        {{-- Header --}}
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-800">Contact Inquiries</h1>
                <p class="mt-1 text-sm text-gray-500">Messages submitted via the public landing page.</p>
            </div>
            <span class="rounded-full bg-gray-100 px-3 py-1.5 text-xs font-semibold text-gray-500">
                {{ $inquiries->total() }} total
            </span>
        </div>

        @if (session('success'))
            <div
                class="mb-4 flex items-center gap-3 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                <i class="fa-solid fa-circle-check shrink-0 text-sm"></i>
                {{ session('success') }}
            </div>
        @endif

        {{-- Table --}}
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            @if ($inquiries->isEmpty())
                <div class="flex flex-col items-center justify-center py-16 text-gray-400">
                    <i class="fa-solid fa-inbox mb-3 text-3xl"></i>
                    <p class="text-sm font-medium">No inquiries yet</p>
                </div>
            @else
                <table class="w-full text-sm">
                    <thead class="border-b border-gray-200 bg-gray-50">
                        <tr>
                            <th class="px-5 py-3.5 text-left text-xs font-semibold tracking-wider text-gray-500 uppercase">
                                Submitted By
                            </th>
                            <th
                                class="hidden px-5 py-3.5 text-left text-xs font-semibold tracking-wider text-gray-500 uppercase md:table-cell">
                                Message
                            </th>
                            <th
                                class="hidden px-5 py-3.5 text-left text-xs font-semibold tracking-wider text-gray-500 uppercase lg:table-cell">
                                Date
                            </th>
                            <th class="px-5 py-3.5"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($inquiries as $inquiry)
                            <tr class="hover:bg-gray-50 transition-colors {{ $inquiry->is_read ? '' : 'bg-blue-50/40' }}">
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        @if (!$inquiry->is_read)
                                            <span class="bg-brand-600 h-2 w-2 shrink-0 rounded-full"></span>
                                        @else
                                            <span class="h-2 w-2 shrink-0"></span>
                                        @endif
                                        <div class="min-w-0">
                                            <div class="truncate font-semibold text-gray-800">{{ $inquiry->name }}</div>
                                            <div class="truncate text-xs text-gray-400">{{ $inquiry->email }}</div>
                                            @if ($inquiry->user && $inquiry->user->company)
                                                <a href="{{ route('platform.tenants.show', $inquiry->user->company_id) }}"
                                                    class="text-brand-600 hover:text-brand-700 mt-0.5 inline-flex items-center gap-1 text-[11px] font-semibold hover:underline">
                                                    <i class="fa-solid fa-building text-[10px]"></i>
                                                    {{ $inquiry->user->company->name }}
                                                </a>
                                            @else
                                                <span
                                                    class="mt-0.5 inline-flex items-center gap-1 text-[11px] font-medium text-gray-400">
                                                    <i class="fa-solid fa-globe text-[10px]"></i>
                                                    Public inquiry
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="hidden max-w-xs truncate px-5 py-4 text-gray-500 md:table-cell">
                                    {{ Str::limit($inquiry->message, 80) }}
                                </td>
                                <td class="hidden px-5 py-4 text-xs whitespace-nowrap text-gray-400 lg:table-cell">
                                    {{ $inquiry->created_at->format('d M Y, h:i A') }}
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <a href="{{ route('platform.inquiries.show', $inquiry) }}"
                                        class="text-brand-600 hover:text-brand-700 text-xs font-semibold">
                                        View →
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                @if ($inquiries->hasPages())
                    <div class="border-t border-gray-100 px-5 py-4">{{ $inquiries->links() }}</div>
                @endif
            @endif
        </div>
    </div>
@endsection
