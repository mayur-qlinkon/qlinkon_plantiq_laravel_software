@extends ('layouts.admin')

@section ('title', "Today's Attendance")

@section ('header-title')
    <div>
        {{-- <h1 class="text-sm font-bold text-gray-500 uppercase tracking-widest">Attendance Report</h1> --}}
        <p class="mt-0.5 text-xs font-medium text-gray-400">Attendance Live Report for {{ \Carbon\Carbon::parse($todayDate)->format('l, d M Y') }}</p>
    </div>
@endsection

@push ('styles')
    <style>
        [x-cloak] {
            display: none !important;
        }
        .table-container {
            background: #fff;
            border: 1.5px solid #f1f5f9;
            border-radius: 16px;
            overflow: hidden;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        .data-table th {
            background: #f8fafc;
            padding: 14px 20px;
            text-align: left;
            font-size: 11px;
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 1px solid #f1f5f9;
            white-space: nowrap;
        }
        .data-table td {
            padding: 16px 20px;
            font-size: 13px;
            color: #334155;
            border-bottom: 1px solid #f8fafc;
            vertical-align: middle;
            white-space: nowrap;
        }
        .data-table tr:hover td {
            background: #f8fafc;
        }
        .data-table tr:last-child td {
            border-bottom: none;
        }

        .status-badge {
            display: inline-flex;
            items-align: center;
            padding: 4px 10px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .status-present {
            background: #ecfdf5;
            color: #10b981;
        }
        .status-late {
            background: #fffbeb;
            color: #f59e0b;
        }
        .status-half_day {
            background: #fff7ed;
            color: #f97316;
        }
        .status-absent {
            background: #fef2f2;
            color: #ef4444;
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
            font-size: 13px;
            color: #1f2937;
            outline: none;
        }
        .form-input:focus {
            border-color: var(--brand-500);
        }
    </style>
@endpush

@section ('content')
    @php
    // Calculate quick stats for HR
    $totalPresent = $attendances->whereIn('status', ['present', 'late', 'half_day'])->count();
    $totalLate = $attendances->where('status', 'late')->count();
    $totalAbsent = $attendances->where('status', 'absent')->count();
@endphp

    <div x-data="todayAttendance()" class="w-full space-y-6 pb-10">
        {{-- ── Stats Row ── --}}
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div class="border-1.5 flex items-center gap-4 rounded-2xl border-gray-100 bg-white p-5 shadow-sm">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-green-50 text-green-500">
                    <i data-lucide="users" class="h-6 w-6"></i>
                </div>
                <div>
                    <p class="text-[12px] font-bold tracking-wider text-gray-400 uppercase">Total Present</p>
                    <p class="text-2xl font-black text-gray-900">{{ $totalPresent }}</p>
                </div>
            </div>
            <div class="border-1.5 flex items-center gap-4 rounded-2xl border-gray-100 bg-white p-5 shadow-sm">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-50 text-amber-500">
                    <i data-lucide="clock-3" class="h-6 w-6"></i>
                </div>
                <div>
                    <p class="text-[12px] font-bold tracking-wider text-gray-400 uppercase">Late Arrivals</p>
                    <p class="text-2xl font-black text-gray-900">{{ $totalLate }}</p>
                </div>
            </div>
            <div class="border-1.5 flex items-center gap-4 rounded-2xl border-gray-100 bg-white p-5 shadow-sm">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-red-50 text-red-500">
                    <i data-lucide="user-minus" class="h-6 w-6"></i>
                </div>
                <div>
                    <p class="text-[12px] font-bold tracking-wider text-gray-400 uppercase">Absent / Missing</p>
                    <p class="text-2xl font-black text-gray-900">{{ $totalAbsent }}</p>
                </div>
            </div>
        </div>

        {{-- ── Main Data Table ── --}}
        <div class="table-container">
            <div class="flex items-center justify-between border-b border-gray-100 p-5">
                <h2 class="text-[15px] font-black text-gray-800">Employee Logs</h2>
                <button
                    onclick="window.location.reload()"
                    class="hover:text-brand-600 flex items-center gap-2 text-[12px] font-bold text-gray-500 transition-colors"
                >
                    <i data-lucide="refresh-cw" class="h-4 w-4"></i> Refresh
                </button>
            </div>

            {{-- 🖥️ DESKTOP VIEW (TABLE) --}}
            <div class="hidden overflow-x-auto md:block">
                <table class="data-table w-full min-w-[800px]">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Department</th>
                            <th>In Time</th>
                            <th>Out Time</th>
                            <th>Location / Store</th>
                            <th>Status</th>
                            <th class="text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($attendances as $att)
                            <tr>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex h-9 w-9 items-center justify-center rounded-full bg-gray-100 text-[12px] font-bold text-gray-500"
                                        >
                                            {{ substr($att->employee->user->name ?? '?', 0, 2) }}
                                        </div>
                                        <div>
                                            <p class="font-bold text-gray-900">{{ $att->employee->user->name ?? 'Unknown' }}</p>
                                            <p class="text-[11px] text-gray-400">{{ $att->employee?->employee_code ?? '—' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span
                                        class="rounded-md border border-gray-200 bg-gray-50 px-2.5 py-1 text-[12px] font-semibold text-gray-600"
                                    >
                                        {{ $att->employee->department->name ?? 'N/A' }}
                                    </span>
                                </td>
                                <td>
                                    @if ($att->check_in_time)
                                        <p class="font-bold text-gray-700">{{ $att->check_in_time->format('h:i A') }}</p>
                                        @if ($att->is_overridden)
                                            <span class="text-[10px] font-bold text-red-500">*Overridden</span>
                                        @endif
                                    @else
                                        <span class="text-gray-300">--:--</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($att->check_out_time)
                                        <p class="font-bold text-gray-700">{{ $att->check_out_time->format('h:i A') }}</p>
                                        <p class="text-[10px] text-gray-400">{{ $att->worked_hours }} hrs worked</p>
                                    @else
                                        <span class="text-[11px] font-semibold text-blue-500 italic">Working...</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="flex items-center gap-1.5 text-gray-500">
                                        <i data-lucide="map-pin" class="h-3.5 w-3.5"></i>
                                        <span
                                            class="text-[12px] font-medium"
                                            >{{ $att->store->name ?? 'Head Office' }}</span
                                        >
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge status-{{ $att->status }}">
                                        {{ str_replace('_', ' ', $att->status) }}
                                    </span>
                                    @if ($att->check_in_review_status === 'pending' || $att->check_out_review_status === 'pending')
                                        <span class="mt-1 block text-[10px] font-bold text-orange-600"
                                            >📍 Pending Review</span
                                        >
                                    @endif
                                </td>
                                <td class="text-right">
                                    @php $pendingField = $att->check_in_review_status === 'pending' ? 'check_in' : ($att->check_out_review_status === 'pending' ? 'check_out' : null); @endphp
                                    @if ($pendingField)
                                        <button
                                            @click="reviewLocation({{ $att->id }}, '{{ $pendingField }}', 'approve')"
                                            class="inline-flex h-11 w-11 items-center justify-center rounded-lg text-green-500 transition-all hover:bg-green-50 md:h-8 md:w-8"
                                            title="Approve Location"
                                        >
                                            <i data-lucide="check" class="h-5 w-5 md:h-4 md:w-4"></i>
                                        </button>
                                        <button
                                            @click="reviewLocation({{ $att->id }}, '{{ $pendingField }}', 'reject')"
                                            class="inline-flex h-11 w-11 items-center justify-center rounded-lg text-red-500 transition-all hover:bg-red-50 md:h-8 md:w-8"
                                            title="Reject Location"
                                        >
                                            <i data-lucide="x" class="h-5 w-5 md:h-4 md:w-4"></i>
                                        </button>
                                    @endif
                                    <button
                                        @click="openOverrideModal({{ json_encode($att) }})"
                                        class="hover:text-brand-600 inline-flex h-11 w-11 items-center justify-center rounded-lg text-gray-400 transition-all hover:bg-gray-100 md:h-8 md:w-8"
                                        title="Override Record"
                                    >
                                        <i data-lucide="edit-3" class="h-5 w-5 md:h-4 md:w-4"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-10 text-center">
                                    <div class="flex flex-col items-center justify-center text-gray-400">
                                        <i data-lucide="inbox" class="mb-2 h-10 w-10 opacity-50"></i>
                                        <p class="text-[13px] font-bold">No attendance records found for today.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- 📱 MOBILE VIEW (CARDS) --}}
            <div class="divide-y divide-gray-50 border-t border-gray-50 md:hidden">
                @forelse ($attendances as $att)
                    <div class="p-4 transition-colors hover:bg-gray-50/50">
                        {{-- Header: Employee & Action --}}
                        <div class="mb-3 flex items-start justify-between">
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gray-100 text-[10px] font-bold text-gray-500"
                                >
                                    {{ substr($att->employee->user->name ?? '?', 0, 2) }}
                                </div>
                                <div>
                                    <p class="text-[13px] leading-tight font-bold text-gray-900">{{ $att->employee->user->name ?? 'Unknown' }}</p>
                                    <p class="mt-0.5 text-[11px] text-gray-400">
                                        {{ $att->employee?->employee_code ?? '—' }} •
                                        <span
                                            class="font-semibold text-gray-500"
                                            >{{ $att->employee->department->name ?? 'N/A' }}</span
                                        >
                                    </p>
                                </div>
                            </div>
                            <button
                                @click="openOverrideModal({{ json_encode($att) }})"
                                class="hover:text-brand-600 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-gray-100"
                                title="Override Record"
                            >
                                <i data-lucide="edit-3" class="h-4 w-4"></i>
                            </button>
                        </div>

                        {{-- Context: Check-in / Check-out --}}
                        <div
                            class="mb-3 flex items-center justify-between rounded-lg border border-gray-100 bg-gray-50/80 px-3 py-2.5"
                        >
                            <div>
                                <p class="mb-0.5 text-[9px] font-bold tracking-wider text-gray-400 uppercase">Check In</p>
                                @if ($att->check_in_time)
                                    <p class="text-[12px] font-bold text-gray-700">{{ $att->check_in_time->format('h:i A') }}</p>
                                    @if ($att->is_overridden)
                                        <p class="mt-0.5 text-[9px] font-bold text-red-500">*Overridden</p>
                                    @endif
                                @else
                                    <p class="text-[12px] text-gray-300">--:--</p>
                                @endif
                            </div>
                            <i data-lucide="arrow-right" class="h-4 w-4 text-gray-300"></i>
                            <div class="text-right">
                                <p class="mb-0.5 text-[9px] font-bold tracking-wider text-gray-400 uppercase">Check Out</p>
                                @if ($att->check_out_time)
                                    <p class="text-[12px] font-bold text-gray-700">{{ $att->check_out_time->format('h:i A') }}</p>
                                    <p class="mt-0.5 text-[9px] font-medium text-gray-400">{{ $att->worked_hours }} hrs worked</p>
                                @else
                                    <p class="text-[11px] font-semibold text-blue-500 italic">Working...</p>
                                @endif
                            </div>
                        </div>

                        {{-- Footer: Location & Status --}}
                        <div class="flex items-center justify-between pt-1 text-[11px]">
                            <div class="flex items-center gap-1.5 text-gray-500">
                                <i data-lucide="map-pin" class="h-3.5 w-3.5"></i>
                                <span class="font-medium">{{ $att->store->name ?? 'Head Office' }}</span>
                            </div>
                            <span class="status-badge status-{{ $att->status }} px-2 py-0.5 text-[10px]">
                                {{ str_replace('_', ' ', $att->status) }}
                            </span>
                        </div>
                        @php $pendingFieldMobile = $att->check_in_review_status === 'pending' ? 'check_in' : ($att->check_out_review_status === 'pending' ? 'check_out' : null); @endphp
                        @if ($pendingFieldMobile)
                            <div class="mt-3 flex items-center justify-between gap-2 border-t border-gray-100 pt-3">
                                <span class="text-[10px] font-bold text-orange-600">📍 Pending Review</span>
                                <div class="flex items-center gap-2">
                                    <button
                                        @click="reviewLocation({{ $att->id }}, '{{ $pendingFieldMobile }}', 'approve')"
                                        class="rounded-lg bg-green-50 px-3 py-1.5 text-[11px] font-bold text-green-600"
                                    >
                                        Approve
                                    </button>
                                    <button
                                        @click="reviewLocation({{ $att->id }}, '{{ $pendingFieldMobile }}', 'reject')"
                                        class="rounded-lg bg-red-50 px-3 py-1.5 text-[11px] font-bold text-red-600"
                                    >
                                        Reject
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="bg-white p-8 text-center text-sm text-gray-400">
                        <div class="flex flex-col items-center justify-center">
                            <i data-lucide="inbox" class="mb-2 h-8 w-8 opacity-50"></i>
                            <p class="mb-1 font-bold text-gray-900">No attendance records found for today.</p>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- ── OVERRIDE MODAL ── --}}
        <template x-teleport="body">
            <div
                x-show="showModal"
                x-cloak
                class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 p-4 backdrop-blur-sm"
            >
                <div
                    class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-xl"
                    @click.outside="closeModal()"
                >
                    <div class="flex items-center justify-between border-b border-gray-100 bg-gray-50/50 px-6 py-4">
                        <div>
                            <h3 class="text-[15px] font-black text-gray-900">Override Attendance</h3>
                            <p class="mt-0.5 text-[12px] text-gray-500" x-text="recordData?.employee?.user?.name"></p>
                        </div>
                        <button @click="closeModal()" class="text-gray-400 hover:text-gray-600">
                            <i data-lucide="x" class="h-5 w-5"></i>
                        </button>
                    </div>

                    <div class="space-y-4 p-6">
                        <div>
                            <label class="form-label">Status</label>
                            <select x-model="formData.status" class="form-input">
                                <option value="present">Present</option>
                                <option value="late">Late</option>
                                <option value="half_day">Half Day</option>
                                <option value="absent">Absent</option>
                            </select>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="form-label">Check In Time</label>
                                <input type="datetime-local" x-model="formData.check_in_time" class="form-input" />
                            </div>
                            <div>
                                <label class="form-label">Check Out Time</label>
                                <input type="datetime-local" x-model="formData.check_out_time" class="form-input" />
                            </div>
                        </div>

                        <div>
                            <label class="form-label">Reason for Override <span class="text-red-500">*</span></label>
                            <textarea
                                x-model="formData.reason"
                                class="form-input"
                                rows="3"
                                placeholder="e.g., Forgot to scan QR, System error..."
                            ></textarea>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-gray-100 bg-gray-50/50 px-6 py-4">
                        <button
                            @click="closeModal()"
                            class="rounded-xl px-4 py-2 text-[13px] font-bold text-gray-600 transition-colors hover:bg-gray-100"
                        >
                            Cancel
                        </button>
                        <button
                            @click="submitOverride()"
                            :disabled="isSubmitting"
                            class="flex items-center gap-2 rounded-xl px-5 py-2 text-[13px] font-bold text-white transition-colors disabled:opacity-50"
                            style="background: var(--brand-600)"
                        >
                            <span x-show="!isSubmitting">Save Override</span>
                            <span x-show="isSubmitting">Saving...</span>
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>

@endsection

@push ('scripts')
    <script>
        function todayAttendance() {
            return {
                showModal: false,
                isSubmitting: false,
                recordData: null,
                formData: {
                    id: null,
                    status: "",
                    check_in_time: "",
                    check_out_time: "",
                    reason: "",
                },

                init() {
                    if (window.lucide) lucide.createIcons();
                },

                // Format MySQL datetime to HTML5 datetime-local format
                formatDateForInput(dateStr) {
                    if (!dateStr) return "";
                    // Remove the space and replace with T (e.g. 2026-04-04 14:30:00 -> 2026-04-04T14:30)
                    return dateStr.replace(" ", "T").substring(0, 16);
                },

                openOverrideModal(attendance) {
                    this.recordData = attendance;
                    this.formData.id = attendance.id;
                    this.formData.status = attendance.status;
                    this.formData.check_in_time = this.formatDateForInput(attendance.check_in_time);
                    this.formData.check_out_time = this.formatDateForInput(attendance.check_out_time);
                    this.formData.reason = attendance.override_reason || "";

                    this.showModal = true;
                },

                closeModal() {
                    this.showModal = false;
                    this.recordData = null;
                },

                async submitOverride() {
                    if (!this.formData.reason.trim()) {
                        if (typeof BizAlert !== "undefined") BizAlert.toast("A reason is required for audits.", "error");
                        else alert("A reason is required.");
                        return;
                    }

                    this.isSubmitting = true;

                    try {
                        // Laravel Route for override: /admin/hrm/attendance/{attendance}/override
                        const url = `{{ url('admin/hrm/attendance') }}/${this.formData.id}/override`;

                        // Format dates back for Laravel (Y-m-d H:i:s)
                        let payload = {
                            status: this.formData.status,
                            reason: this.formData.reason,
                            check_in_time: this.formData.check_in_time
                                ? this.formData.check_in_time.replace("T", " ") + ":00"
                                : null,
                            check_out_time: this.formData.check_out_time
                                ? this.formData.check_out_time.replace("T", " ") + ":00"
                                : null,
                        };

                        const res = await fetch(url, {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                                Accept: "application/json",
                            },
                            body: JSON.stringify(payload),
                        });

                        const data = await res.json();

                        if (data.success) {
                            if (typeof BizAlert !== "undefined")
                                BizAlert.toast("Attendance overridden successfully!", "success");
                            this.closeModal();
                            // Reload to update stats and table
                            setTimeout(() => window.location.reload(), 1500);
                        } else {
                            if (typeof BizAlert !== "undefined") BizAlert.toast(data.message, "error");
                            else alert(data.message);
                        }
                    } catch (e) {
                        console.error(e);
                        if (typeof BizAlert !== "undefined") BizAlert.toast("Network error occurred.", "error");
                    } finally {
                        this.isSubmitting = false;
                    }
                },

                async reviewLocation(attendanceId, field, decision) {
                    let reason = null;

                    if (decision === "reject") {
                        const { value, isConfirmed } = await Swal.fire({
                            title: "Reject Location & Mark Absent",
                            input: "text",
                            inputLabel: "Reason (required)",
                            inputPlaceholder: "e.g. Location does not match store area",
                            showCancelButton: true,
                            confirmButtonText: "Reject",
                            confirmButtonColor: "#ef4444",
                            inputValidator: (value) => (!value ? "A reason is required." : undefined),
                        });

                        if (!isConfirmed) return;
                        reason = value;
                    }

                    try {
                        const url = `{{ url('admin/hrm/attendance') }}/${attendanceId}/review-location`;

                        const res = await fetch(url, {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                                Accept: "application/json",
                            },
                            body: JSON.stringify({ field, decision, reason }),
                        });

                        const data = await res.json();

                        if (data.success) {
                            if (typeof BizAlert !== "undefined") BizAlert.toast(data.message, "success");
                            setTimeout(() => window.location.reload(), 1200);
                        } else {
                            if (typeof BizAlert !== "undefined") BizAlert.toast(data.message, "error");
                            else alert(data.message);
                        }
                    } catch (e) {
                        console.error(e);
                        if (typeof BizAlert !== "undefined") BizAlert.toast("Network error occurred.", "error");
                    }
                },
            };
        }
    </script>
@endpush
