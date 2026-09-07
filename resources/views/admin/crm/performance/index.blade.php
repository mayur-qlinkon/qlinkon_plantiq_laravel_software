@extends ('layouts.admin')

@section('title', 'Team Performance - ' . config('app.name'))

@section('header-title')
    <h1 class="text-sm font-bold tracking-widest text-gray-500 uppercase">CRM Team Performance</h1>
@endsection

@push('styles')
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
@endpush

@php
    $presets = [
        'today' => 'Today',
        'week' => 'This Week',
        'month' => 'This Month',
        'quarter' => 'This Quarter',
        'custom' => 'Custom',
    ];
@endphp

@section('content')
    <div class="pb-10" x-data="crmPerformance(@js($rows))">
        {{-- PERIOD --}}
        <div class="mb-4 rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
            <form id="performance-filter-form" action="{{ route('admin.crm.performance.index') }}" method="GET"
                class="flex flex-wrap items-end gap-2">
                <div class="flex flex-wrap gap-1.5">
                    @foreach ($presets as $key => $label)
                        <a href="{{ route('admin.crm.performance.index', ['preset' => $key]) }}"
                            class="rounded-lg px-3 py-2 text-xs font-bold transition-colors
                        {{ $preset === $key ? 'bg-[#108c2a] text-white' : 'bg-gray-50 text-gray-600 hover:bg-gray-100' }}">{{ $label }}</a>
                    @endforeach
                </div>

                @if ($preset === 'custom')
                    <input type="hidden" name="preset" value="custom" />
                    <div>
                        <label class="text-[10px] font-bold text-gray-500 uppercase">From</label>
                        <input type="date" name="from" value="{{ $from->toDateString() }}"
                            class="mt-1 block rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-[#108c2a] focus:ring-1 focus:ring-[#108c2a]" />
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-gray-500 uppercase">To</label>
                        <input type="date" name="to" value="{{ $to->toDateString() }}"
                            class="mt-1 block rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-[#108c2a] focus:ring-1 focus:ring-[#108c2a]" />
                    </div>
                    <button type="submit"
                        class="rounded-lg bg-[#108c2a] px-4 py-2 text-xs font-bold text-white hover:bg-[#0d7522]">
                        Apply
                    </button>
                @endif

                <div class="ml-auto text-[11px] font-semibold text-gray-400">
                    {{ $from->format('d M Y') }} &rarr; {{ $to->format('d M Y') }}
                </div>
            </form>
        </div>

        {{-- TOTALS --}}
        <div class="mb-4 grid grid-cols-2 gap-2 sm:grid-cols-4 xl:grid-cols-7">
            @php
                $cards = [
                    ['label' => 'Logged Contacts', 'value' => $totals['effort'], 'tone' => 'text-gray-800'],
                    ['label' => 'Calls Logged', 'value' => $totals['calls'], 'tone' => 'text-gray-800'],
                    ['label' => 'Stage Moves', 'value' => $totals['stage_moves'], 'tone' => 'text-indigo-600'],
                    ['label' => 'Conversions', 'value' => $totals['conversions'], 'tone' => 'text-green-600'],
                    ['label' => 'Tasks Done', 'value' => $totals['tasks_completed'], 'tone' => 'text-gray-800'],
                    ['label' => 'Tasks Overdue', 'value' => $totals['tasks_overdue'], 'tone' => 'text-red-600'],
                    ['label' => 'Stale Leads', 'value' => $totals['stale_leads'], 'tone' => 'text-red-600'],
                ];
            @endphp

            @foreach ($cards as $card)
                <div class="rounded-xl border border-gray-100 bg-white px-3 py-2.5 shadow-sm">
                    <div class="text-[10px] font-extrabold tracking-wider text-gray-400 uppercase">
                        {{ $card['label'] }}
                    </div>
                    <div class="mt-1 text-lg leading-none font-black {{ $card['tone'] }}">{{ $card['value'] }}</div>
                </div>
            @endforeach
        </div>

        {{-- Effort is self-reported and outcome is not. Saying so once here stops
             the table being read as a single ranking. --}}
        <div class="mb-3 flex items-start gap-2 rounded-lg border border-amber-100 bg-amber-50/60 px-3 py-2">
            <i data-lucide="info" class="mt-0.5 h-3.5 w-3.5 shrink-0 text-amber-600"></i>
            <p class="text-[11px] leading-relaxed text-amber-800">
                <strong>Logged contacts are self-reported</strong> — they count what the employee entered, not what
                actually happened. Stage moves, conversions and task punctuality are recorded by the system. Read the
                two together: high activity with no stage movement is worth a conversation.
            </p>
        </div>

        {{-- TABLE --}}
        <div class="overflow-x-auto rounded-xl border border-gray-100 bg-white shadow-sm">
            <table class="w-full min-w-[1100px] text-left">
                <thead class="border-b border-gray-100 bg-gray-50/60">
                    <tr class="text-[10px] font-extrabold tracking-wider text-gray-500 uppercase">
                        <th class="px-4 py-3">
                            <button @click="sortBy('user_name')" class="hover:text-gray-800">Employee</button>
                        </th>
                        <th class="px-3 py-3">
                            <button @click="sortBy('leads_held')" class="hover:text-gray-800">Leads Held</button>
                        </th>
                        <th class="px-3 py-3">
                            <button @click="sortBy('effort')" class="hover:text-gray-800">Logged Contacts</button>
                        </th>
                        <th class="px-3 py-3">
                            <button @click="sortBy('stage_moves')" class="hover:text-gray-800">Stage Moves</button>
                        </th>
                        <th class="px-3 py-3">
                            <button @click="sortBy('conversions')" class="hover:text-gray-800">Won</button>
                        </th>
                        <th class="px-3 py-3">
                            <button @click="sortBy('tasks_completed')" class="hover:text-gray-800">Tasks</button>
                        </th>
                        <th class="px-3 py-3">
                            <button @click="sortBy('tasks_overdue')" class="hover:text-gray-800">Overdue</button>
                        </th>
                        <th class="px-3 py-3">
                            <button @click="sortBy('stale_leads')" class="hover:text-gray-800">Stale</button>
                        </th>
                        <th class="px-3 py-3">
                            <button @click="sortBy('active_days')" class="hover:text-gray-800">Active Days</button>
                        </th>
                        <th class="px-3 py-3">
                            <button @click="sortBy('avg_first_touch_hours')" class="hover:text-gray-800">
                                1st Touch
                            </button>
                        </th>
                        <th class="px-3 py-3">Signal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <template x-for="r in sorted" :key="r.user_id">
                        <tr :class="isDormant(r) ? 'bg-gray-50/60' : ''" class="transition-colors hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <div :class="isDormant(r) ? 'text-gray-400' : 'text-gray-800'" class="text-sm font-bold"
                                    x-text="r.user_name"></div>
                                <div class="text-[10px] text-gray-400">
                                    <span x-text="r.leads_received"></span> received ·
                                    <span x-text="r.leads_handed"></span> handed off
                                </div>
                            </td>

                            <td class="px-3 py-3 text-sm font-semibold text-gray-700" x-text="r.leads_held"></td>

                            <td class="px-3 py-3">
                                <div class="text-sm font-bold text-gray-800" x-text="r.effort"></div>
                                <div class="text-[10px] text-gray-400">
                                    <span title="Calls"><span x-text="r.calls"></span>c</span> ·
                                    <span title="WhatsApp"><span x-text="r.whatsapp"></span>w</span> ·
                                    <span title="Emails"><span x-text="r.emails"></span>e</span> ·
                                    <span title="Meetings"><span x-text="r.meetings"></span>m</span> ·
                                    <span title="Notes"><span x-text="r.notes"></span>n</span>
                                </div>
                                <div class="text-[10px] text-gray-400">
                                    <span x-text="r.leads_touched"></span> leads touched
                                </div>
                            </td>

                            <td class="px-3 py-3">
                                <span :class="r.stage_moves > 0 ? 'text-indigo-600' : 'text-gray-300'"
                                    class="text-sm font-bold" x-text="r.stage_moves"></span>
                            </td>

                            <td class="px-3 py-3">
                                <span :class="r.conversions > 0 ? 'text-green-600' : 'text-gray-300'"
                                    class="text-sm font-bold" x-text="r.conversions"></span>
                            </td>

                            <td class="px-3 py-3">
                                <div class="text-sm font-semibold text-gray-700" x-text="r.tasks_completed"></div>
                                <div class="text-[10px] font-bold" :class="onTimeTone(r.on_time_rate)"
                                    x-text="r.on_time_rate === null ? '—' : r.on_time_rate + '% on time'"></div>
                            </td>

                            <td class="px-3 py-3">
                                <span :class="r.tasks_overdue > 0 ? 'text-red-600' : 'text-gray-300'"
                                    class="text-sm font-bold" x-text="r.tasks_overdue"></span>
                            </td>

                            <td class="px-3 py-3">
                                <span :class="r.stale_leads > 0 ? 'text-red-600' : 'text-gray-300'"
                                    class="text-sm font-bold" x-text="r.stale_leads"></span>
                            </td>

                            <td class="px-3 py-3 text-sm font-semibold text-gray-600" x-text="r.active_days"></td>

                            {{-- Null means nothing was assigned in this window, which is
                                 not the same as an instant response. Never render it as 0. --}}
                            <td class="px-3 py-3 text-xs font-semibold text-gray-600"
                                x-text="r.avg_first_touch_hours === null ? '—' : r.avg_first_touch_hours + 'h'"></td>

                            <td class="px-3 py-3">
                                <span x-show="signal(r)" x-cloak :class="signal(r) ? signal(r).class : ''"
                                    class="rounded-md border px-2 py-0.5 text-[10px] font-extrabold uppercase"
                                    x-text="signal(r) ? signal(r).label : ''"></span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>

            <div x-show="sorted.length === 0" x-cloak class="px-4 py-16 text-center">
                <div class="mb-3 inline-flex h-12 w-12 items-center justify-center rounded-full bg-gray-50">
                    <i data-lucide="users" class="h-6 w-6 text-gray-400"></i>
                </div>
                <p class="text-sm font-semibold text-gray-500">No CRM activity in this period.</p>
            </div>
        </div>

        <p class="mt-3 text-[11px] text-gray-400">Overdue tasks and stale leads are counted as of right now, not for the
            selected period — they describe what needs attention today. A lead is stale after {{ $staleDays }} days with
            no activity.</p>
    </div>
@endsection

@push('scripts')
    <script>
        window.crmPerformance = function(rows) {
            return {
                rows: rows,
                sortKey: "effort",
                sortAsc: false,

                get sorted() {
                    return [...this.rows].sort((a, b) => {
                        const x = a[this.sortKey];
                        const y = b[this.sortKey];

                        // Nulls always sink, regardless of direction. A missing
                        // first-touch time is not a good score.
                        if (x === null && y === null) return 0;
                        if (x === null) return 1;
                        if (y === null) return -1;

                        if (typeof x === "string") {
                            return this.sortAsc ? x.localeCompare(y) : y.localeCompare(x);
                        }

                        return this.sortAsc ? x - y : y - x;
                    });
                },

                sortBy(key) {
                    if (this.sortKey === key) {
                        this.sortAsc = !this.sortAsc;
                        return;
                    }

                    this.sortKey = key;
                    // Names read best A–Z; every other column is "most first".
                    this.sortAsc = key === "user_name";
                },

                /**
                 * Nobody did any work in this window. Such a row only appears
                 * because of a point-in-time metric like an overdue task, so it
                 * is dimmed to avoid reading as activity.
                 */
                isDormant(r) {
                    return r.effort === 0 && r.stage_moves === 0 && r.tasks_completed === 0;
                },

                onTimeTone(rate) {
                    if (rate === null) return "text-gray-300";
                    if (rate >= 90) return "text-green-600";
                    if (rate >= 70) return "text-amber-600";
                    return "text-red-600";
                },

                /**
                 * One flag per row, worst first. The point is to draw the eye to
                 * the handful of rows worth acting on, not to grade everyone.
                 */
                signal(r) {
                    if (r.stale_leads > 0) {
                        return {
                            label: r.stale_leads + " going cold",
                            class: "border-red-200 bg-red-50 text-red-700"
                        };
                    }

                    if (r.tasks_overdue > 0) {
                        return {
                            label: "Overdue tasks",
                            class: "border-red-200 bg-red-50 text-red-700"
                        };
                    }

                    // Plenty of logged contact, nothing moved. Either the leads are
                    // dead or the logging is optimistic — both need a look.
                    if (r.effort >= 5 && r.stage_moves === 0) {
                        return {
                            label: "No movement",
                            class: "border-amber-200 bg-amber-50 text-amber-700"
                        };
                    }

                    if (this.isDormant(r)) {
                        return {
                            label: "Inactive",
                            class: "border-gray-200 bg-gray-100 text-gray-500"
                        };
                    }

                    return null;
                },
            };
        };
    </script>
@endpush
