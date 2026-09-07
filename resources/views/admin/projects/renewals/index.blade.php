@extends ('layouts.admin')

@section ('title', 'Renewals - ' . config('app.name'))

@section ('header-title')
    <h1 class="text-sm font-bold tracking-widest text-gray-500 uppercase">Renewals</h1>
@endsection

@push ('styles')
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
@endpush

@php
    // Tab definitions live here rather than in the controller: they are purely
    // presentational, and the counts they display are already computed.
    $tabs = [
        'overdue'   => ['label' => 'Overdue',    'icon' => 'alert-triangle', 'tone' => 'red'],
        'today'     => ['label' => 'Today',      'icon' => 'calendar-clock', 'tone' => 'orange'],
        'week'      => ['label' => 'This Week',  'icon' => 'calendar-days',  'tone' => 'amber'],
        'month'     => ['label' => 'This Month', 'icon' => 'calendar-range', 'tone' => 'indigo'],
        'active'    => ['label' => 'Running',    'icon' => 'shield-check',   'tone' => 'green'],
        'expired'   => ['label' => 'Expired',    'icon' => 'clock-alert',    'tone' => 'orange'],
        'cancelled' => ['label' => 'Cancelled',  'icon' => 'x-circle',       'tone' => 'gray'],
        'all'       => ['label' => 'All',        'icon' => 'layers',         'tone' => 'gray'],
    ];

    $toneClasses = [
        'red'    => 'border-red-200 bg-red-50 text-red-700',
        'orange' => 'border-orange-200 bg-orange-50 text-orange-700',
        'amber'  => 'border-amber-200 bg-amber-50 text-amber-700',
        'indigo' => 'border-indigo-200 bg-indigo-50 text-indigo-700',
        'green'  => 'border-green-200 bg-green-50 text-green-700',
        'gray'   => 'border-gray-200 bg-gray-50 text-gray-600',
    ];
@endphp

@section ('content')
    <div class="pb-10" x-data="renewalBoard()">
        {{-- SYNC HEALTH --}}
        {{-- Every count on this page is a snapshot of the last nightly run. If
             that run stopped happening, the board keeps looking healthy while
             quietly serving stale numbers — so it says so itself. --}}
        <div
            class="mb-3 flex items-center gap-2 rounded-lg border px-3 py-2 text-[11px] font-semibold
            {{ $syncIsStale ? 'border-red-200 bg-red-50 text-red-700' : 'border-gray-100 bg-gray-50 text-gray-500' }}"
        >
            <i data-lucide="{{ $syncIsStale ? 'alert-triangle' : 'refresh-cw' }}" class="h-3.5 w-3.5 shrink-0"></i>

            @if (!$lastSyncedAt)
                <span>Renewal sync has never run. Expiry dates and statuses on this page may be out of date.</span>
            @elseif ($syncIsStale)
                <span>
                    Last synced {{ $lastSyncedAt->diffForHumans() }}
                    ({{ $lastSyncedAt->format('d M Y, h:i A') }}). The nightly job may have stopped — these figures
                    could be stale.
                </span>
            @else
                <span>
                    Last synced {{ $lastSyncedAt->diffForHumans() }}
                    <span class="font-normal text-gray-400">({{ $lastSyncedAt->format('d M Y, h:i A') }})</span>
                </span>
            @endif
        </div>

        {{-- BUCKET TABS --}}
        <div class="mb-4 grid grid-cols-2 gap-2 sm:grid-cols-4 xl:grid-cols-8">
            @foreach ($tabs as $key => $tab)
                <a
                    href="{{ route('admin.project_renewals.index', array_merge(request()->except(['bucket', 'page']), ['bucket' => $key])) }}"
                    class="flex flex-col rounded-xl border px-3 py-2.5 transition-all
                {{ $bucket === $key
                            ? $toneClasses[$tab['tone']] . ' shadow-sm ring-2 ring-gray-900/10'
                            : 'border-gray-100 bg-white text-gray-500 hover:border-gray-200 hover:bg-gray-50' }}"
                >
                    <div class="flex items-center gap-1.5">
                        <i data-lucide="{{ $tab['icon'] }}" class="h-3.5 w-3.5"></i>
                        <span class="text-[10px] font-extrabold tracking-wider uppercase">{{ $tab['label'] }}</span>
                    </div>
                    <span class="mt-1 text-lg leading-none font-black">{{ $counts[$key] }}</span>
                </a>
            @endforeach
        </div>

        {{-- FILTERS --}}
        <div class="rounded-t-xl border border-b-0 border-gray-100 bg-white p-4 shadow-sm">
            <form
                id="renewal-filter-form"
                action="{{ route('admin.project_renewals.index') }}"
                method="GET"
                class="flex w-full flex-wrap items-center gap-3"
                @submit.prevent="submitForm"
                @change="submitForm"
            >
                {{-- Carried through so changing a filter never silently moves the user to another tab. --}}
                <input type="hidden" name="bucket" value="{{ $bucket }}" />

                <div class="relative w-full max-w-md min-w-[240px] flex-1">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                        <i data-lucide="search" class="h-4 w-4 text-gray-400"></i>
                    </div>
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search service, client or phone..."
                        @input.debounce.400ms="submitForm"
                        class="w-full rounded-lg border border-gray-200 py-2.5 pr-4 pl-10 text-sm text-gray-700 placeholder-gray-400 transition-all outline-none focus:border-[#108c2a] focus:ring-1 focus:ring-[#108c2a]"
                    />
                </div>

                <select
                    name="client_id"
                    class="rounded-lg border border-gray-200 px-3 py-2.5 text-sm text-gray-700 outline-none focus:border-[#108c2a] focus:ring-1 focus:ring-[#108c2a]"
                >
                    <option value="">All Clients</option>
                    @foreach ($clients as $client)
                        <option value="{{ $client->id }}" @selected (request('client_id') == $client->id)>
                            {{ $client->name }}{{ $client->phone ? ' — ' . $client->phone : '' }}
                        </option>
                    @endforeach
                </select>

                <select
                    name="billing_cycle"
                    class="rounded-lg border border-gray-200 px-3 py-2.5 text-sm text-gray-700 outline-none focus:border-[#108c2a] focus:ring-1 focus:ring-[#108c2a]"
                >
                    <option value="">All Cycles</option>
                    @foreach ($cycles as $cycle)
                        <option value="{{ $cycle['value'] }}" @selected (request('billing_cycle') === $cycle['value'])>
                            {{ $cycle['label'] }}
                        </option>
                    @endforeach
                </select>

                {{-- filled() not hasAny(): a native form submit posts every field,
                     so hasAny() is true even when every filter is blank. --}}
                @if (request()->filled('search') || request()->filled('client_id') || request()->filled('billing_cycle'))
                    <a
                        href="{{ route('admin.project_renewals.index') }}"
                        class="flex items-center gap-1.5 rounded-lg bg-red-50 px-3 py-2.5 text-sm font-bold text-red-500 transition-colors hover:bg-red-100"
                    >
                        <i data-lucide="x" class="h-4 w-4"></i>
                        Clear
                    </a>
                @endif
            </form>
        </div>

        {{-- TABLE --}}
        <div class="overflow-x-auto rounded-b-xl border border-gray-100 bg-white shadow-sm">
            <table class="w-full min-w-[900px] text-left">
                <thead class="border-b border-gray-100 bg-gray-50/60">
                    <tr class="text-[10px] font-extrabold tracking-wider text-gray-500 uppercase">
                        <th class="px-4 py-3">Client</th>
                        <th class="px-4 py-3">Service</th>
                        <th class="px-4 py-3">Cycle</th>
                        <th class="px-4 py-3">Current Period</th>
                        <th class="px-4 py-3">Renewal Due</th>
                        <th class="px-4 py-3">Next Price</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse ($renewals as $row)
                        @php
                            // Urgency is read off the date, not the status column.
                            // A row can still say "active" while its period ended
                            // yesterday, and that is exactly the row worth shouting about.
                            $days = $row->current_period_end
                                ? (int) $today->diffInDays($row->current_period_end, false)
                                : null;

                            $rowTone = match (true) {
                                $row->status->value === 'cancelled' => 'bg-gray-50/60 text-gray-400',
                                $days === null                      => '',
                                $days < 0                           => 'bg-red-50/50',
                                $days === 0                         => 'bg-orange-50/60',
                                $days <= 7                          => 'bg-amber-50/50',
                                $days <= 30                         => 'bg-indigo-50/30',
                                default                             => '',
                            };

                            $dueLabel = match (true) {
                                $days === null => 'No end date',
                                $days < 0      => abs($days) . ' days overdue',
                                $days === 0    => 'Due today',
                                $days === 1    => 'Due tomorrow',
                                default        => 'in ' . $days . ' days',
                            };

                            $dueTone = match (true) {
                                $days === null => 'text-gray-400',
                                $days < 0      => 'text-red-600',
                                $days === 0    => 'text-orange-600',
                                $days <= 7     => 'text-amber-600',
                                default        => 'text-gray-500',
                            };
                        @endphp

                        <tr class="{{ $rowTone }} transition-colors hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <div class="text-sm font-bold text-gray-800">{{ $row->client?->name ?? '—' }}</div>
                                <div class="text-[11px] text-gray-400">{{ $row->client?->phone }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-sm font-semibold text-gray-700">
                                    {{ $row->name ?: ($row->service?->name ?? 'Custom Service') }}
                                </div>
                                @if ($row->project)
                                    <a
                                        href="{{ route('admin.projects.show', $row->project_id) }}"
                                        class="text-[11px] font-semibold text-indigo-500 hover:underline"
                                        >{{ $row->project->title }}</a
                                    >
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="rounded-md bg-indigo-50 px-2 py-0.5 text-[10px] font-bold text-indigo-600 uppercase"
                                >
                                    {{ $row->billing_cycle->label() }}
                                </span>
                                @if ($row->auto_renew)
                                    <div class="mt-1 text-[10px] font-bold text-green-600 uppercase">Auto renew</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-500">
                                {{ $row->current_period_start?->format('d M Y') ?? '—' }}
                                <span class="mx-1">&rarr;</span>
                                {{ $row->current_period_end?->format('d M Y') ?? 'Ongoing' }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-xs font-bold {{ $dueTone }}">{{ $dueLabel }}</div>
                            </td>
                            <td class="px-4 py-3 text-xs font-bold text-gray-700">
                                ₹{{ number_format((float) $row->price, 2) }}
                                @if ((float) $row->tax_rate > 0)
                                    <span class="font-normal text-gray-400">+{{ (int) $row->tax_rate }}%</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @php ($color = $row->status->color())
                                <span
                                    class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-[10px] font-extrabold uppercase"
                                    style="background: {{ $color['bg'] }}; color: {{ $color['text'] }}"
                                >
                                    <i data-lucide="{{ $row->status->icon() }}" class="h-3 w-3"></i>
                                    {{ $row->status->label() }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if (has_permission('project_client_services.renew') && $row->isRenewable())
                                    <button
                                        type="button"
                                        @click="openRenew({{ $row->id }}, @js($row->name ?: ($row->service?->name ?? 'Service')), {{ (float) $row->price }})"
                                        class="rounded-lg bg-indigo-50 px-3 py-1.5 text-[11px] font-bold text-indigo-700 transition-colors hover:bg-indigo-100"
                                    >
                                        Renew
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-16 text-center">
                                <div
                                    class="mb-3 inline-flex h-12 w-12 items-center justify-center rounded-full bg-gray-50"
                                >
                                    <i data-lucide="calendar-check" class="h-6 w-6 text-gray-400"></i>
                                </div>
                                <p class="text-sm font-semibold text-gray-500">Nothing in this bucket.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($renewals->hasPages())
            <div class="mt-4">{{ $renewals->links() }}</div>
        @endif

        {{-- RENEW MODAL --}}
        <template x-teleport="body">
            <div
                x-show="modal"
                x-cloak
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
                @keydown.escape.window="modal = false"
                @click.self="modal = false"
            >
                <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
                    <h3 class="text-sm font-bold text-gray-800">Renew — <span x-text="form.label"></span></h3>
                    <p class="mt-1 text-[11px] text-gray-500">Moves the entitlement into the next period and raises a new charge. Leave the start date blank to continue from the current period end.</p>

                    <div class="mt-4 space-y-3">
                        <div>
                            <label class="text-[10px] font-bold text-gray-500 uppercase">Period Start</label>
                            <input
                                type="date"
                                x-model="form.period_start"
                                class="mt-1 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-[#108c2a] focus:ring-1 focus:ring-[#108c2a]"
                            />
                        </div>
                        <div>
                            <label class="text-[10px] font-bold text-gray-500 uppercase">Price</label>
                            <input
                                type="number"
                                step="0.01"
                                x-model="form.price"
                                class="mt-1 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-[#108c2a] focus:ring-1 focus:ring-[#108c2a]"
                            />
                        </div>
                    </div>

                    <div class="mt-5 flex justify-end gap-2">
                        <button
                            type="button"
                            @click="modal = false"
                            class="rounded-lg bg-gray-100 px-4 py-2 text-xs font-bold text-gray-600 hover:bg-gray-200"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            @click="submitRenew"
                            :disabled="isProcessing"
                            class="rounded-lg bg-[#108c2a] px-4 py-2 text-xs font-bold text-white hover:bg-[#0d7522] disabled:opacity-50"
                            x-text="isProcessing ? 'Renewing...' : 'Renew'"
                        ></button>
                    </div>
                </div>
            </div>
        </template>
    </div>
@endsection

@push ('scripts')
    <script>
        window.renewalBoard = function () {
            return {
                modal: false,
                isProcessing: false,
                form: { id: null, label: "", period_start: "", price: "" },

                submitForm() {
                    const form = document.getElementById("renewal-filter-form");

                    // Blank filters are dropped rather than submitted as empty
                    // strings, so the URL reflects only what is actually applied.
                    // Page is not carried either — a changed filter always means
                    // a different result set, so page 3 of the old one is wrong.
                    const params = new URLSearchParams();

                    new FormData(form).forEach((value, key) => {
                        if (String(value).trim() !== "") {
                            params.append(key, value);
                        }
                    });

                    const query = params.toString();

                    window.location = query ? `${form.action}?${query}` : form.action;
                },

                openRenew(id, label, price) {
                    this.form = { id: id, label: label, period_start: "", price: price };
                    this.modal = true;
                },

                submitRenew() {
                    this.isProcessing = true;

                    fetch(`/admin/project-client-services/${this.form.id}/renew`, {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            Accept: "application/json",
                            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({
                            period_start: this.form.period_start || null,
                            price: this.form.price || null,
                        }),
                    })
                        .then((res) => res.json())
                        .then((data) => {
                            this.isProcessing = false;

                            if (!data.success) {
                                // The skipped-period guard names the date to pick,
                                // so the server message is shown as-is.
                                BizAlert.error(data.message || "Could not renew this service.");
                                return;
                            }

                            BizAlert.success(data.message).then(() => window.location.reload());
                        })
                        .catch(() => {
                            this.isProcessing = false;
                            BizAlert.error("Something went wrong. Please try again.");
                        });
                },
            };
        };
    </script>
@endpush
