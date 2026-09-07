@extends ('layouts.admin')

@section('title', 'My Leaves')

@section('header-title')
    <div>
        <h1 class="text-sm font-bold tracking-widest text-gray-500 uppercase">My Leaves</h1>
        <p class="mt-0.5 text-xs font-medium text-gray-400">Apply for leave and track your requests</p>
    </div>
@endsection

@push('styles')
    <style>
        [x-cloak] {
            display: none !important;
        }

        .field-label {
            font-size: 11px;
            font-weight: 700;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 6px;
        }

        .field-input {
            width: 100%;
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            padding: 10px 30px;
            font-size: 13.5px;
            outline: none;
            transition:
                border-color 150ms ease,
                box-shadow 150ms ease;
            font-family: inherit;
            background: #fff;
        }

        .field-input:focus {
            border-color: var(--brand-600);
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--brand-600) 10%, transparent);
        }

        .field-error {
            font-size: 11px;
            color: #dc2626;
            margin-top: 4px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.45);
            backdrop-filter: blur(3px);
            z-index: 50;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }

        .modal-box {
            background: #fff;
            border-radius: 18px;
            width: 100%;
            max-width: 520px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.18);
            overflow: hidden;
        }

        .modal-header {
            padding: 18px 22px 14px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .btn-primary {
            background: var(--brand-600);
            color: #fff;
            font-weight: 700;
            font-size: 13px;
            padding: 9px 20px;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            transition: opacity 150ms;
            font-family: inherit;
        }

        .btn-primary:hover {
            opacity: 0.88;
        }

        .btn-primary:disabled {
            opacity: 0.55;
            cursor: not-allowed;
        }

        .btn-ghost {
            background: transparent;
            color: #6b7280;
            font-weight: 600;
            font-size: 13px;
            padding: 9px 16px;
            border-radius: 10px;
            border: 1.5px solid #e5e7eb;
            cursor: pointer;
            transition: background 150ms;
            font-family: inherit;
        }

        .btn-ghost:hover {
            background: #f9fafb;
        }

        /* Balance Strip */
        .balance-strip {
            display: flex;
            gap: 0;
            overflow-x: auto;
            scrollbar-width: none;
        }

        .balance-strip::-webkit-scrollbar {
            display: none;
        }

        .balance-pill {
            flex: 1;
            min-width: 140px;
            padding: 14px 18px;
            border-right: 1px solid #f1f5f9;
            transition: background 150ms;
        }

        .balance-pill:last-child {
            border-right: none;
        }

        .balance-pill:hover {
            background: #fafafa;
        }
    </style>
@endpush

@section('content')
    <div x-data="myLeaves()" class="space-y-4 pb-10">
        {{-- Hidden trigger button for header Apply Leave --}}
        <button id="apply-leave-trigger" @click="openApply()" class="hidden"></button>

        {{-- ── Leave Requests Table ── --}}
        <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white">
            {{-- Premium Mobile-Friendly Toolbar --}}
            <div
                class="flex flex-col justify-between gap-3.5 border-b border-gray-100 px-4 py-3.5 sm:flex-row sm:items-center sm:px-5 sm:py-4">
                {{-- Row 1 on Mobile: Header & Quick Apply --}}
                <div class="flex w-full items-center justify-between sm:w-auto">
                    <p class="text-[14px] font-extrabold text-gray-800">My Leave Requests</p>

                    {{-- Mobile Apply Button --}}
                    <button @click="openApply()"
                        class="bg-brand-50 text-brand-600 hover:bg-brand-100 border-brand-100 inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-[11.5px] font-bold shadow-sm transition-all active:scale-95 sm:hidden">
                        <i data-lucide="plus" class="h-3.5 w-3.5"></i> Apply
                    </button>
                </div>

                {{-- Row 2 on Mobile: 50/50 Grid Filters & Desktop Action --}}
                <div class="flex w-full flex-col items-center gap-2.5 sm:w-auto sm:flex-row">
                    <div class="grid w-full grid-cols-2 items-center gap-2.5 sm:flex sm:w-auto">
                        <select x-model="filterStatus" @change="filterLeaves()"
                            class="focus:border-brand-500 focus:ring-brand-500/20 w-full cursor-pointer truncate rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-[12px] font-bold text-gray-600 shadow-sm transition-all outline-none focus:bg-white focus:ring-2 sm:w-auto">
                            <option value="">All Status</option>
                            @foreach (\App\Models\Hrm\Leave::STATUS_LABELS as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>

                        <select x-model="filterType" @change="filterLeaves()"
                            class="focus:border-brand-500 focus:ring-brand-500/20 w-full cursor-pointer truncate rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-[12px] font-bold text-gray-600 shadow-sm transition-all outline-none focus:bg-white focus:ring-2 sm:w-auto">
                            <option value="">All Types</option>
                            @foreach ($leaveTypes as $lt)
                                <option value="{{ $lt->id }}">{{ $lt->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Desktop Apply Button --}}
                    <button @click="openApply()"
                        class="bg-brand-50 text-brand-600 hover:bg-brand-100 border-brand-100 hidden items-center gap-2 rounded-xl border px-3.5 py-2 text-[12px] font-bold shadow-sm transition-all active:scale-95 sm:inline-flex">
                        <i data-lucide="plus" class="h-3.5 w-3.5"></i> Apply
                    </button>
                </div>
            </div>

            {{-- 🖥️ DESKTOP VIEW (TABLE) --}}
            <div class="hidden overflow-x-auto md:block">
                <table class="w-full text-left whitespace-nowrap">
                    <thead>
                        <tr class="border-b border-gray-50">
                            <th class="px-5 py-3 text-[11px] font-bold tracking-wider text-gray-400 uppercase">
                                Leave Type
                            </th>
                            <th class="px-5 py-3 text-[11px] font-bold tracking-wider text-gray-400 uppercase">
                                Dates
                            </th>
                            <th class="px-5 py-3 text-[11px] font-bold tracking-wider text-gray-400 uppercase">Days</th>
                            <th class="px-5 py-3 text-[11px] font-bold tracking-wider text-gray-400 uppercase">
                                Status
                            </th>
                            <th class="px-5 py-3 text-[11px] font-bold tracking-wider text-gray-400 uppercase">
                                Applied On
                            </th>
                            <th class="w-20 px-5 py-3 text-[11px] font-bold tracking-wider text-gray-400 uppercase">
                                Action
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse ($leaves as $leave)
                            @php $sc = \App\Models\Hrm\Leave::STATUS_COLORS[$leave->status]; @endphp
                            <tr class="transition-colors hover:bg-gray-50/50">
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-2">
                                        <p class="text-[13px] font-bold text-gray-800">{{ $leave->leaveType?->name }}</p>
                                        @if (!empty($leave->document))
                                            <a href="{{ route('admin.hrm.my-leaves.document', $leave->id) }}"
                                                target="_blank" class="text-blue-500 transition-colors hover:text-blue-700"
                                                title="View Document">
                                                <i data-lucide="paperclip" class="h-3.5 w-3.5"></i>
                                            </a>
                                        @endif
                                    </div>
                                    <p class="mt-0.5 text-[11px] text-gray-400">
                                        {{ str_replace('_', ' ', $leave->day_type) }}</p>
                                </td>
                                <td class="px-5 py-3.5">
                                    <div
                                        class="inline-flex items-center gap-2 rounded-lg border border-gray-100 bg-gray-50/80 px-2.5 py-1.5 text-[13px] font-medium text-gray-700">
                                        <span>{{ $leave->from_date->format('d M Y') }}</span>
                                        @if ($leave->from_date != $leave->to_date)
                                            <i data-lucide="arrow-right" class="h-3 w-3 text-gray-400"></i>
                                            <span>{{ $leave->to_date->format('d M Y') }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-5 py-3.5 text-[13px] font-bold text-gray-800">
                                    {{ $leave->total_days }}
                                </td>
                                <td class="px-5 py-3.5">
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-[11px] font-extrabold tracking-wider uppercase"
                                        style="background: {{ $sc['bg'] }}; color: {{ $sc['text'] }}">
                                        <span class="h-1.5 w-1.5 flex-shrink-0 rounded-full"
                                            style="background: {{ $sc['dot'] }}"></span>
                                        {{ \App\Models\Hrm\Leave::STATUS_LABELS[$leave->status] }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-[12px] text-gray-500">
                                    {{ $leave->created_at->format('d M Y') }}
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-1">
                                        @if ($leave->status === 'pending')
                                            <button
                                                @click="openEdit({{ $leave->id }}, {{ $leave->leave_type_id }}, '{{ $leave->from_date->format('Y-m-d') }}', '{{ $leave->to_date->format('Y-m-d') }}', {{ $leave->total_days }}, '{{ $leave->day_type }}', '{{ addslashes($leave->reason) }}', '{{ $leave->document ? route('admin.hrm.my-leaves.document', $leave->id) : '' }}')"
                                                class="flex h-7 w-7 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-blue-50 hover:text-blue-600">
                                                <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                                            </button>
                                            <button @click="confirmDelete({{ $leave->id }})"
                                                class="flex h-7 w-7 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-red-50 hover:text-red-500">
                                                <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                                            </button>
                                        @else
                                            <span class="text-[11px] text-gray-300">—</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-12 text-center text-sm text-gray-400">
                                    No leave requests found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- 📱 MOBILE VIEW (CARDS) --}}
            <div class="divide-y divide-gray-50 border-t border-gray-50 md:hidden">
                @forelse ($leaves as $leave)
                    @php $sc = \App\Models\Hrm\Leave::STATUS_COLORS[$leave->status]; @endphp
                    <div class="p-4 transition-colors hover:bg-gray-50/50">
                        <div class="mb-3 flex items-start justify-between">
                            <div>
                                <div class="flex items-center gap-2">
                                    <p class="text-[13px] font-bold text-gray-800">{{ $leave->leaveType?->name }}</p>
                                    @if (!empty($leave->document))
                                        <a href="{{ route('admin.hrm.my-leaves.document', $leave->id) }}" target="_blank"
                                            class="text-blue-500 transition-colors hover:text-blue-700"
                                            title="View Document">
                                            <i data-lucide="paperclip" class="h-3.5 w-3.5"></i>
                                        </a>
                                    @endif
                                </div>
                                <p class="mt-0.5 text-[11px] text-gray-400 capitalize">
                                    {{ str_replace('_', ' ', $leave->day_type) }} • <span
                                        class="font-bold text-gray-600">{{ $leave->total_days }} Day(s)</span></p>
                            </div>
                            <span
                                class="inline-flex items-center gap-1.5 rounded-md px-2 py-0.5 text-[10px] font-extrabold tracking-wider uppercase"
                                style="background: {{ $sc['bg'] }}; color: {{ $sc['text'] }}">
                                <span class="h-1.5 w-1.5 flex-shrink-0 rounded-full"
                                    style="background: {{ $sc['dot'] }}"></span>
                                {{ \App\Models\Hrm\Leave::STATUS_LABELS[$leave->status] }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between">
                            <div
                                class="inline-flex items-center gap-2 rounded-lg border border-gray-100 bg-gray-50/80 px-2.5 py-1.5 text-[12px] font-medium text-gray-700">
                                <span>{{ $leave->from_date->format('d M Y') }}</span>
                                @if ($leave->from_date != $leave->to_date)
                                    <i data-lucide="arrow-right" class="h-3 w-3 text-gray-400"></i>
                                    <span>{{ $leave->to_date->format('d M Y') }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="mt-3 flex items-center justify-between border-t border-gray-50 pt-3">
                            <span class="text-[11px] font-medium text-gray-400">Applied:
                                {{ $leave->created_at->format('d M Y') }}</span>
                            <div class="flex items-center gap-1">
                                @if ($leave->status === 'pending')
                                    <button
                                        @click="openEdit({{ $leave->id }}, {{ $leave->leave_type_id }}, '{{ $leave->from_date->format('Y-m-d') }}', '{{ $leave->to_date->format('Y-m-d') }}', {{ $leave->total_days }}, '{{ $leave->day_type }}', '{{ addslashes($leave->reason) }}', '{{ $leave->document ? route('admin.hrm.my-leaves.document', $leave->id) : '' }}')"
                                        class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-blue-50 hover:text-blue-600">
                                        <i data-lucide="pencil" class="h-4 w-4"></i>
                                    </button>
                                    <button @click="confirmDelete({{ $leave->id }})"
                                        class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-red-50 hover:text-red-500">
                                        <i data-lucide="trash-2" class="h-4 w-4"></i>
                                    </button>
                                @else
                                    <span class="px-2 text-[11px] text-gray-300 italic">Locked</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="bg-white p-8 text-center text-sm text-gray-400">
                        <p class="mb-1 font-semibold text-gray-500">No leave requests found</p>
                        <p class="text-xs text-gray-400">Use the button above to apply for leave.</p>
                    </div>
                @endforelse
            </div>

            @if ($leaves->hasPages())
                <div class="border-t border-gray-50 px-5 py-3">{{ $leaves->links() }}</div>
            @endif
        </div>

        {{-- ── Leave Balance Strip ── --}}
        <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white">
            <div class="flex items-center justify-between border-b border-gray-50 px-5 py-3">
                <p class="text-[11px] font-bold tracking-wider text-gray-400 uppercase">Leave Balance —
                    {{ $year }}</p>
                <span class="text-[11px] text-gray-400">{{ now()->format('F Y') }}</span>
            </div>
            @if ($leaveBalances->isEmpty())
                <div class="px-5 py-4 text-center text-sm text-gray-400">No leave types configured. Contact HR.</div>
            @else
                <div class="balance-strip">
                    @foreach ($leaveBalances as $lb)
                        @php
                            $pct = $lb['allocated'] > 0 ? min(100, ($lb['used'] / $lb['allocated']) * 100) : 0;
                            $availPct = 100 - $pct;
                        @endphp
                        <div class="balance-pill">
                            <div class="mb-2 flex items-center gap-1.5">
                                <span class="h-2 w-2 flex-shrink-0 rounded-full"
                                    style="background: {{ $lb['color'] }}"></span>
                                <p class="truncate text-[10.5px] font-bold tracking-wider text-gray-400 uppercase">
                                    {{ $lb['name'] }}</p>
                            </div>
                            <div class="mb-1.5 flex items-baseline gap-1">
                                <span
                                    class="text-[22px] leading-none font-black text-gray-900">{{ $lb['available'] }}</span>
                                <span class="text-[11px] font-medium text-gray-400">/ {{ $lb['allocated'] }}</span>
                            </div>
                            <div class="mb-1 h-1 overflow-hidden rounded-full bg-gray-100">
                                <div class="h-full rounded-full"
                                    style="width: {{ $availPct }}%; background: {{ $lb['color'] }}; opacity: 0.85;">
                                </div>
                            </div>
                            <p class="text-[10px] text-gray-400">{{ $lb['used'] }} used</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ── Apply / Edit Modal ── --}}
        <template x-teleport="body">
            <div x-show="showModal" x-cloak class="modal-backdrop" @click.self="showModal = false">
                <div class="modal-box" @click.stop>
                    <div class="modal-header">
                        <div>
                            <p class="text-[15px] font-black text-gray-900"
                                x-text="
                                    editId ? 'Edit Leave Request' : 'Apply for Leave'
                                ">
                            </p>
                            <p class="mt-0.5 text-[12px] text-gray-400">Submit your leave application</p>
                        </div>
                        <button @click="showModal = false"
                            class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600">
                            <i data-lucide="x" class="h-4 w-4"></i>
                        </button>
                    </div>

                    <div class="space-y-4 p-5">
                        <div>
                            <p class="field-label">Leave Type</p>
                            <select x-model="form.leave_type_id" @change="checkDocumentRequirement($event)"
                                class="field-input">
                                <option value="" data-requires-document="0">Select leave type</option>
                                @foreach ($leaveTypes as $lt)
                                    <option value="{{ $lt->id }}"
                                        data-requires-document="{{ $lt->requires_document ? '1' : '0' }}">
                                        {{ $lt->name }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="field-error" x-show="errors.leave_type_id" x-text="errors.leave_type_id"></p>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <p class="field-label">From Date</p>
                                <input type="date" x-model="form.from_date"
                                    :min="editId ? '' : '{{ now()->format('Y-m-d') }}'" @change="calcDays()"
                                    class="field-input" />
                                <p class="field-error" x-show="errors.from_date" x-text="errors.from_date"></p>
                            </div>
                            <div>
                                <p class="field-label">To Date</p>
                                <input type="date" x-model="form.to_date"
                                    :min="form.from_date || (editId ? '' : '{{ now()->format('Y-m-d') }}')"
                                    @change="calcDays()" class="field-input" />
                                <p class="field-error" x-show="errors.to_date" x-text="errors.to_date"></p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <p class="field-label">Day Type</p>
                                <select x-model="form.day_type" @change="calcDays()" class="field-input">
                                    <option value="full_day">Full Day</option>
                                    <option value="first_half">First Half</option>
                                    <option value="second_half">Second Half</option>
                                </select>
                            </div>
                            <div>
                                <p class="field-label">Total Days</p>
                                <input type="number" x-model="form.total_days" step="0.5" min="0.5"
                                    class="field-input" readonly />
                            </div>
                        </div>

                        <div>
                            <p class="field-label">Reason</p>
                            <textarea x-model="form.reason" rows="3" class="field-input" style="resize: none"
                                placeholder="Briefly describe the reason..."></textarea>
                            <p class="field-error" x-show="errors.reason" x-text="errors.reason"></p>
                        </div>

                        <div x-show="requiresDocument || existingDocumentUrl" x-transition x-cloak
                            class="mt-4 rounded-xl border border-gray-100 bg-gray-50 p-4">
                            <p class="field-label mb-3 flex items-center gap-1.5">
                                <i data-lucide="paperclip" class="h-3.5 w-3.5"></i>
                                Supporting Document <span class="text-red-500" x-show="!existingDocumentUrl">*</span>
                            </p>
                            <div x-show="existingDocumentUrl"
                                class="mb-2 flex items-center justify-between rounded-lg border border-gray-200 bg-gray-50 p-2.5">
                                <a :href="existingDocumentUrl" target="_blank"
                                    class="flex items-center gap-1.5 text-[12px] font-bold text-blue-600 hover:underline">
                                    <i data-lucide="external-link" class="h-3.5 w-3.5"></i> View Uploaded Document
                                </a>
                                <span class="text-[10px] text-gray-400">Upload below to replace</span>
                            </div>
                            <input type="file" x-ref="fileInput" @change="handleFileUpload($event)"
                                accept=".pdf,.jpg,.jpeg,.png"
                                class="w-full rounded-xl border border-gray-200 p-1 text-sm text-gray-500 file:mr-4 file:rounded-lg file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:text-xs file:font-bold file:text-blue-700 hover:file:bg-blue-100"
                                :required="requiresDocument && !existingDocumentUrl" />
                            <p class="mt-1.5 text-[10px] text-gray-400">Medical certificate or proof required for this
                                leave type (PDF, JPG, PNG).</p>
                            <p class="field-error" x-show="errors.document" x-text="errors.document"></p>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 px-5 pb-5">
                        <button @click="showModal = false" class="btn-ghost">Cancel</button>
                        <button @click="submitLeave()" :disabled="saving" class="btn-primary">
                            <span x-text="saving ? 'Saving...' : editId ? 'Update Request' : 'Submit Request'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>

@endsection

@push('scripts')
    <script>
        function myLeaves() {
            return {
                showModal: false,
                editId: null,
                saving: false,
                requiresDocument: false,
                documentFile: null,
                existingDocumentUrl: "",
                filterStatus: "{{ request('status', '') }}",
                filterType: "{{ request('leave_type_id', '') }}",
                form: {
                    leave_type_id: "",
                    from_date: "",
                    to_date: "",
                    day_type: "full_day",
                    total_days: 1,
                    reason: ""
                },
                errors: {},

                init() {
                    if (window.lucide) lucide.createIcons();
                },

                openApply() {
                    this.editId = null;
                    this.form = {
                        leave_type_id: "",
                        from_date: "",
                        to_date: "",
                        day_type: "full_day",
                        total_days: 1,
                        reason: "",
                    };
                    this.requiresDocument = false;
                    this.documentFile = null;
                    this.existingDocumentUrl = "";
                    if (this.$refs.fileInput) this.$refs.fileInput.value = "";
                    this.errors = {};
                    this.showModal = true;
                    this.$nextTick(() => {
                        if (window.lucide) lucide.createIcons();
                    });
                },

                openEdit(id, typeId, from, to, days, dayType, reason, documentUrl = "") {
                    this.editId = id;
                    this.form = {
                        leave_type_id: String(typeId),
                        from_date: from,
                        to_date: to,
                        total_days: days,
                        day_type: dayType,
                        reason: reason,
                    };
                    this.requiresDocument = false;
                    this.documentFile = null;
                    this.existingDocumentUrl = documentUrl || "";
                    if (this.$refs.fileInput) this.$refs.fileInput.value = "";
                    this.errors = {};
                    this.showModal = true;
                    this.$nextTick(() => {
                        if (window.lucide) lucide.createIcons();
                        const selectEl = document.querySelector('select[x-model="form.leave_type_id"]');
                        if (selectEl) {
                            selectEl.value = typeId;
                            selectEl.dispatchEvent(new Event("change"));
                        }
                    });
                },

                checkDocumentRequirement(event) {
                    const selectedOption = event.target.options[event.target.selectedIndex];
                    this.requiresDocument = selectedOption.getAttribute("data-requires-document") === "1";
                    if (!this.requiresDocument) {
                        this.documentFile = null;
                        if (this.$refs.fileInput) this.$refs.fileInput.value = "";
                    }
                },

                handleFileUpload(event) {
                    this.documentFile = event.target.files[0];
                },

                calcDays() {
                    if (!this.form.from_date || !this.form.to_date) return;
                    const from = new Date(this.form.from_date);
                    const to = new Date(this.form.to_date);
                    if (to < from) {
                        this.form.to_date = this.form.from_date;
                        return;
                    }
                    const diff = Math.ceil((to - from) / (1000 * 60 * 60 * 24)) + 1;
                    this.form.total_days = this.form.day_type === "full_day" ? diff : 0.5;
                },

                filterLeaves() {
                    const params = new URLSearchParams(window.location.search);
                    if (this.filterStatus) params.set("status", this.filterStatus);
                    else params.delete("status");
                    if (this.filterType) params.set("leave_type_id", this.filterType);
                    else params.delete("leave_type_id");
                    window.location.search = params.toString();
                },

                async submitLeave() {
                    this.saving = true;
                    this.errors = {};

                    const url = this.editId ? `/admin/hrm/my-leaves/${this.editId}` : "/admin/hrm/my-leaves";

                    const formData = new FormData();
                    Object.keys(this.form).forEach((key) => formData.append(key, this.form[key]));

                    if (this.editId) {
                        formData.append("_method", "PUT");
                    }

                    if (this.requiresDocument && this.documentFile) {
                        formData.append("document", this.documentFile);
                    }

                    try {
                        const res = await fetch(url, {
                            method: "POST",
                            headers: {
                                "X-CSRF-TOKEN": document.querySelector("meta[name=csrf-token]").content,
                                Accept: "application/json",
                            },
                            body: formData,
                        });
                        const data = await res.json();
                        if (data.success) {
                            BizAlert.toast(data.message, "success");
                            this.showModal = false;
                            setTimeout(() => window.location.reload(), 800);
                        } else {
                            if (data.errors) this.errors = data.errors;
                            else BizAlert.toast(data.message || "Something went wrong.", "error");
                        }
                    } catch (e) {
                        BizAlert.toast("Network error. Please try again.", "error");
                    } finally {
                        this.saving = false;
                    }
                },

                confirmDelete(id) {
                    BizAlert.confirm("Delete this leave request?", "This cannot be undone.", "Delete").then(async (
                        result) => {
                        if (!result.isConfirmed) return;
                        try {
                            const res = await fetch(`/admin/hrm/my-leaves/${id}`, {
                                method: "DELETE",
                                headers: {
                                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')
                                        .content,
                                    Accept: "application/json",
                                },
                            });
                            const data = await res.json();
                            if (data.success) {
                                BizAlert.toast(data.message, "success");
                                setTimeout(() => window.location.reload(), 800);
                            } else {
                                BizAlert.toast(data.message, "error");
                            }
                        } catch (error) {
                            BizAlert.toast("Network error. Please try again.", "error");
                        }
                    });
                },
            };
        }
    </script>
@endpush
