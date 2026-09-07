@extends ('layouts.platform')

@section ('title', 'Article Feedback - Qlinkon')

@section ('header', 'Article Feedback')

@section ('content')
    <div class="space-y-6">
        {{-- Stats Cards --}}
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
            <!-- Total -->
            <div class="flex items-center rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="mr-4 flex h-12 w-12 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                    <i class="fa-solid fa-comment text-xl"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Total Feedback</p>
                    <p class="text-2xl font-bold text-gray-800">{{ number_format($totalFeedback) }}</p>
                </div>
            </div>

            <!-- Helpful -->
            <div class="flex items-center rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="mr-4 flex h-12 w-12 items-center justify-center rounded-lg bg-green-50 text-green-600">
                    <i class="fa-solid fa-thumbs-up text-xl"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Helpful</p>
                    <p class="text-2xl font-bold text-gray-800">{{ number_format($helpfulCount) }}</p>
                </div>
            </div>

            <!-- Not Helpful -->
            <div class="flex items-center rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="mr-4 flex h-12 w-12 items-center justify-center rounded-lg bg-red-50 text-red-600">
                    <i class="fa-solid fa-thumbs-down text-xl"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Not Helpful</p>
                    <p class="text-2xl font-bold text-gray-800">{{ number_format($notHelpfulCount) }}</p>
                </div>
            </div>
        </div>

        {{-- Top Section: Filters --}}
        <div class="flex flex-col justify-between gap-4 sm:flex-row">
            <form action="{{ route('platform.help.feedback') }}" method="GET" class="flex items-center gap-3">
                <div class="relative">
                    <select
                        name="is_helpful"
                        onchange="this.form.submit()"
                        class="focus:ring-brand-500 focus:border-brand-500 appearance-none rounded-lg border border-gray-200 bg-white py-2 pr-10 pl-4 text-sm text-gray-700 focus:ring-2 focus:outline-none"
                    >
                        <option value="">All Feedback</option>
                        <option value="1" {{ request('is_helpful') === '1' ? 'selected' : '' }}>👍 Helpful</option>
                        <option value="0" {{ request('is_helpful') === '0' ? 'selected' : '' }}>👎 Not Helpful</option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-500">
                        <i class="fa-solid fa-chevron-down"></i>
                    </div>
                </div>

                @if (request()->has('is_helpful') && request('is_helpful') !== null)
                    <a
                        href="{{ route('platform.help.feedback') }}"
                        class="hover:text-brand-600 text-sm text-gray-500 transition-colors"
                    >
                        Clear Filter
                    </a>
                @endif
            </form>
        </div>

        {{-- Feedback Table --}}
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead
                        class="border-b border-gray-200 bg-gray-50/80 text-xs font-semibold tracking-wider text-gray-600 uppercase"
                    >
                        <tr>
                            <th class="px-6 py-4">Article</th>
                            <th class="px-6 py-4">User / Company</th>
                            <th class="px-6 py-4">Feedback</th>
                            <th class="px-6 py-4">IP Address</th>
                            <th class="px-6 py-4">Date & Time</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-gray-700">
                        @forelse ($feedbacks as $feedback)
                            <tr class="transition-colors hover:bg-gray-50/50">
                                {{-- Article Column --}}
                                <td class="min-w-[250px] px-6 py-4 whitespace-normal">
                                    @if ($feedback->article)
                                        <div class="font-medium text-gray-800">{{ $feedback->article->title }}</div>
                                        <div class="mt-0.5 text-xs text-gray-400">
                                            Category: {{ $feedback->article->category->title ?? 'N/A' }}
                                        </div>
                                    @else
                                        <span class="flex items-center gap-1.5 text-gray-400 italic">
                                            <i class="fa-solid fa-circle-exclamation"></i>
                                            Deleted Article
                                        </span>
                                    @endif
                                </td>

                                {{-- User / Company Column --}}
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">                                        
                                        <div class="min-w-0">
                                            @if ($feedback->user)
                                                <div class="truncate font-semibold text-gray-800">
                                                    {{ $feedback->user->name }}
                                                </div>

                                                @if ($feedback->user->company)
                                                    <a
                                                        href="{{ route('platform.clients.show', $feedback->user->company_id) }}"
                                                        class="text-brand-600 hover:text-brand-700 mt-0.5 inline-flex items-center gap-1 text-[11px] font-semibold hover:underline"
                                                    >
                                                        <i class="fa-solid fa-building text-[10px]"></i>
                                                        {{ $feedback->user->company->name }}
                                                    </a>
                                                @else
                                                    <span
                                                        class="mt-0.5 inline-flex items-center gap-1 text-[11px] font-medium text-gray-400"
                                                    >
                                                        <i class="fa-solid fa-building-slash text-[10px]"></i>
                                                        No Company
                                                    </span>
                                                @endif
                                            @else
                                                <div class="font-semibold text-gray-500">Guest / Unknown</div>
                                                <span
                                                    class="mt-0.5 inline-flex items-center gap-1 text-[11px] font-medium text-gray-400"
                                                >
                                                    <i class="fa-solid fa-user-secret text-[10px]"></i>
                                                    Anonymous Feedback
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                {{-- Feedback Status Column --}}
                                <td class="px-6 py-4">
                                    @if ($feedback->is_helpful)
                                        <span
                                            class="inline-flex items-center gap-1.5 rounded-full border border-green-200 bg-green-50 px-2.5 py-1 text-xs font-medium text-green-700"
                                        >
                                            <i class="fa-solid fa-thumbs-up"></i>
                                            Helpful
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center gap-1.5 rounded-full border border-red-200 bg-red-50 px-2.5 py-1 text-xs font-medium text-red-700"
                                        >
                                            <i class="fa-solid fa-thumbs-down"></i>
                                            Not Helpful
                                        </span>
                                    @endif
                                </td>

                                {{-- IP Address Column --}}
                                <td class="px-6 py-4">
                                    <span class="rounded bg-gray-100 px-2 py-1 font-mono text-xs text-gray-500">
                                        {{ $feedback->ip_address ?? 'Unknown' }}
                                    </span>
                                </td>

                                {{-- Date Column --}}
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    <div>{{ $feedback->created_at->format('M d, Y') }}</div>
                                    <div class="text-xs text-gray-400">
                                        {{ $feedback->created_at->format('h:i A') }}
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                                    <div class="flex flex-col items-center justify-center">
                                        <div
                                            class="mb-3 flex h-12 w-12 items-center justify-center rounded-full border border-gray-100 bg-gray-50"
                                        >
                                            <i class="fa-solid fa-comment-dots text-xl text-gray-400"></i>
                                        </div>
                                        <p class="font-medium text-gray-600">No feedback found</p>
                                        <p class="mt-1 text-xs text-gray-400">There are no article ratings recorded yet.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if ($feedbacks->hasPages())
                <div class="border-t border-gray-100 bg-white px-6 py-4">{{ $feedbacks->links() }}</div>
            @endif
        </div>
    </div>
@endsection
