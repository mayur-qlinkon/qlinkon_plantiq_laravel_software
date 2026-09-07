@extends ('layouts.admin')

@section ('title', 'My Dashboard')

@section ('header-title')
    <div>
        <h1 class="text-sm font-bold tracking-widest text-gray-500 uppercase">My Dashboard</h1>
        <p class="mt-0.5 text-xs font-medium text-gray-400">{{ $employee->employee_code }} · {{ $employee->department?->name }}</p>
    </div>
@endsection

@push ('styles')
    <style>
        [x-cloak] {
            display: none !important;
        }

        /* Minimal App Grid Card (Replaces Stat & Quick Action Cards) */
        .app-grid-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 16px 8px;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            text-decoration: none;
            height: 120px; /* Fixed height replaces aspect-ratio to prevent giant cards */
            position: relative;
            transition:
                transform 100ms ease,
                background 150ms ease;
            flex-shrink: 0; /* Prevents cards from squishing when scrolling horizontally */
        }
        .app-grid-card:active {
            background: #f9fafb;
            transform: scale(0.97);
        }
        .app-grid-icon {
            color: #4b5563; /* Slate outline icon color like screenshot */
            margin-bottom: 12px;
            stroke-width: 1.5px;
        }
        /* Horizontal Text Scrolling Animation */
        .marquee-wrapper {
            width: 100%;
            overflow: hidden;
            padding: 0 4px;
        }
        .marquee-text {
            display: inline-block;
            white-space: nowrap;
            font-size: 13px;
            font-weight: 500;
            color: #374151;
        }
        /* QR Scanner Modal */
        .scan-modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(10, 15, 30, 0.6);
            backdrop-filter: blur(4px);
            z-index: 1050; /* 🌟 FIX: Reduced from 9999 so SweetAlert (1060) appears above it */
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }
        .scan-modal-box {
            background: #fff;
            border-radius: 24px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 24px 80px rgba(0, 0, 0, 0.22);
            overflow: hidden;
        }
        #qr-reader {
            width: 100%;
        }
        #qr-reader video {
            border-radius: 0;
        }
        #qr-reader__scan_region {
            background: transparent !important;
        }
        #qr-reader__dashboard_section_swaplink {
            display: none !important;
        }

        .section-card {
            background: #fff;
            border: 1.5px solid #f1f5f9;
            border-radius: 16px;
            overflow: hidden;
        }
        .section-header {
            padding: 14px 18px;
            border-bottom: 1px solid #f8fafc;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .section-icon {
            width: 30px;
            height: 30px;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
    </style>
@endpush

@section ('content')
    @php
    $empName   = $employee->user?->name ?? 'Employee';
    $firstName = explode(' ', $empName)[0];
    $checkedIn  = $todayAttendance?->check_in_time;
    $checkedOut = $todayAttendance?->check_out_time;

    if ($checkedOut)     { $todayStatus = 'Checked Out'; $todayTime = $todayAttendance->check_out_time->format('h:i A'); $statusColor = 'text-gray-500'; }
    elseif ($checkedIn)  { $todayStatus = 'Checked In';  $todayTime = $todayAttendance->check_in_time->format('h:i A'); $statusColor = 'text-green-600'; }
    else                 { $todayStatus = 'Not Checked In'; $todayTime = null; $statusColor = 'text-gray-400'; }
@endphp

    <div x-data="empDashboard()" class="w-full space-y-6 pb-10">
        {{-- ── Top: Quick Actions (Horizontal scroll on mobile, Grid on desktop) ── --}}
        <div>
            <div
                class="flex scrollbar-none flex-nowrap gap-3 overflow-x-auto pb-3 sm:grid sm:grid-cols-2 sm:pb-0 lg:grid-cols-4"
            >
                {{-- Scan QR --}}
                <button
                    @click="openScanner()"
                    class="app-grid-card relative w-[140px] snap-center sm:w-full"
                    style="
                        background: color-mix(in srgb, var(--brand-600) 5%, #fff);
                        border-color: color-mix(in srgb, var(--brand-600) 18%, #fff);
                    "
                >
                    <span
                        class="absolute top-2 right-2 h-2 w-2 animate-pulse rounded-full"
                        style="background: var(--brand-600)"
                    ></span>
                    <i data-lucide="qr-code" class="app-grid-icon h-7 w-7" style="color: var(--brand-600)"></i>
                    <div class="marquee-wrapper mt-1">
                        <span class="marquee-text" style="color: var(--brand-700)">Scan QR</span>
                    </div>
                </button>
                {{-- Apply Leave --}}
                <a
                    href="{{ route('admin.hrm.my-leaves.index') }}"
                    class="app-grid-card relative w-[140px] snap-center sm:w-full"
                >
                    <i data-lucide="calendar-off" class="app-grid-icon h-7 w-7"></i>
                    <div class="marquee-wrapper mt-1">
                        <span class="marquee-text">Apply Leave</span>
                    </div>
                </a>
                {{-- Payslips --}}
                <a
                    href="{{ route('admin.hrm.my-salary-slips.index') }}"
                    class="app-grid-card relative w-[140px] snap-center sm:w-full"
                >
                    <i data-lucide="banknote" class="app-grid-icon h-7 w-7"></i>
                    <div class="marquee-wrapper mt-1">
                        <span class="marquee-text">Payslips</span>
                    </div>
                </a>
                {{-- My Tasks --}}
                <a
                    @if (has_module('production'))
                        href="{{ route('admin.production.my-tasks.index') }}"
                    @else
                        href="{{ route('admin.hrm.my-tasks.index') }}"
                    @endif
                    class="app-grid-card relative w-[140px] snap-center sm:w-full"
                >
                    <i data-lucide="check-square" class="app-grid-icon h-7 w-7"></i>
                    <div class="marquee-wrapper mt-1">
                        <span class="marquee-text">My Tasks</span>
                    </div>
                </a>
            </div>
        </div>

        {{-- ── Greeting ── --}}
        <div class="flex items-end justify-between pt-2">
            <div>
                <p class="text-[22px] font-black text-gray-900">Welcome back, {{ $firstName }}!</p>
                <p class="mt-0.5 text-[13px] text-gray-400">
                    {{ $employee->designation?->name }}
                    @if ($employee->department) ·{{ $employee->department->name }} @endif
                </p>
            </div>
            <p class="hidden text-[13px] font-semibold text-gray-400 sm:block">{{ now()->format('l, d M Y') }}</p>
        </div>

        {{-- ══ Main: Simple Todo Widget Only ══ --}}
        <div class="grid grid-cols-1 items-start gap-6">
            {{-- ── Left: Simple Todo Widget ── --}}
            <div
                x-data="todoWidget()"
               
                class="flex flex-col overflow-hidden rounded-2xl border border-gray-100 bg-white"
                style="min-height: 380px"
            >
                {{-- Header --}}
                <div class="flex items-center gap-2.5 border-b border-gray-100 px-5 py-4">
                    <p class="text-[15px] font-black text-gray-900">My Todo</p>
                    <span
                        class="flex h-5 min-w-[20px] items-center justify-center rounded-full bg-blue-500 px-1.5 text-[11px] font-black text-white"
                        x-text="todos.filter((t) => t.status === 'pending').length"
                    ></span>
                </div>

                {{-- Add Row --}}
                <div class="flex gap-2.5 border-b border-gray-100 px-5 py-4">
                    <input
                        x-model="newTitle"
                        @keydown.enter="addTodo()"
                        type="text"
                        placeholder="What needs to be done?"
                        class="min-w-0 flex-1 rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-[13px] focus:border-blue-300 focus:outline-none"
                    />
                    <button
                        @click="addTodo()"
                        :disabled="saving || !newTitle.trim()"
                        class="flex-shrink-0 cursor-pointer rounded-xl border-none px-4 py-2.5 text-[13px] font-black whitespace-nowrap text-white disabled:opacity-40"
                        style="background: #22c55e"
                    >
                        + Add
                    </button>
                </div>

                {{-- Task List --}}
                <div class="flex-1 divide-y divide-gray-50 overflow-y-auto" style="max-height: 340px">
                    <template x-for="todo in todos" :key="todo.id">
                        <div class="group flex items-center gap-3 px-5 py-3 transition-colors hover:bg-gray-50">
                            {{-- Checkbox --}}
                            <button
                                @click="toggleComplete(todo)"
                                class="flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full border-2 transition-all"
                                :class="todo.status === 'completed'
                                    ? 'bg-green-500 border-green-500'
                                    : 'border-gray-300 hover:border-blue-400'"
                            >
                                <i
                                    x-show="todo.status === 'completed'"
                                    data-lucide="check"
                                    class="h-3 w-3 text-white"
                                ></i>
                            </button>

                            {{-- View Mode --}}
                            <div
                                x-show="editingId !== todo.id"
                                class="min-w-0 flex-1 cursor-pointer"
                                @click="startEdit(todo)"
                            >
                                <span
                                    class="block truncate text-[13px]"
                                    :class="todo.status === 'completed'
                                        ? 'line-through text-gray-400'
                                        : 'font-medium text-gray-800'"
                                    x-text="todo.title"
                                ></span>
                            </div>

                            {{-- Edit Mode --}}
                            <div x-show="editingId === todo.id" class="flex min-w-0 flex-1 gap-2" @click.stop>
                                <input
                                    x-model="editTitle"
                                    @keydown.enter="saveEdit(todo)"
                                    @keydown.escape="cancelEdit()"
                                    type="text"
                                    maxlength="255"
                                    class="min-w-0 flex-1 rounded-lg border border-blue-300 bg-white px-2.5 py-1.5 text-[13px] focus:outline-none"
                                />
                                <button
                                    @click="saveEdit(todo)"
                                    class="flex-shrink-0 cursor-pointer rounded-lg border-none px-2.5 py-1.5 text-[12px] font-black text-white"
                                    style="background: var(--brand-600)"
                                >
                                    ✓
                                </button>
                                <button
                                    @click="cancelEdit()"
                                    class="flex-shrink-0 cursor-pointer rounded-lg border-none bg-gray-100 px-2.5 py-1.5 text-[12px] text-gray-500"
                                >
                                    ✕
                                </button>
                            </div>

                            {{-- Delete (show on row hover) --}}
                            <button
                                x-show="editingId !== todo.id"
                                @click.stop="deleteTodo(todo)"
                                class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-lg opacity-0 transition-all group-hover:opacity-100 hover:bg-red-50"
                            >
                                <i data-lucide="trash-2" class="h-3.5 w-3.5 text-gray-300 hover:text-red-400"></i>
                            </button>
                        </div>
                    </template>

                    {{-- Empty State --}}
                    <div x-show="todos.length === 0" class="py-12 text-center">
                        <i data-lucide="check-circle-2" class="mx-auto mb-2 h-10 w-10 text-gray-200"></i>
                        <p class="text-[12px] font-semibold text-gray-400">No tasks yet. Add one above!</p>
                    </div>
                </div>
            </div>
        </div>
        {{-- ── 4 Stat Cards ── --}}
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            {{-- Today's Status --}}
            <div class="rounded-2xl border border-gray-100 bg-white p-5 text-center">
                <div class="mx-auto mb-3 flex h-11 w-11 items-center justify-center rounded-full bg-green-50">
                    <i data-lucide="clock" class="h-5 w-5 text-green-500"></i>
                </div>
                <p class="mb-2 text-[11px] font-semibold tracking-wide text-gray-400 uppercase">Today's Status</p>
                <p class="text-[14px] font-black text-gray-900 truncate {{ $statusColor }}">{{ $todayStatus }}</p>
                <p class="mt-1 text-[11px] text-gray-400">{{ $todayTime ? 'at '.$todayTime : '—' }}</p>
            </div>

            {{-- Assigned Tasks --}}
            <a
                href="{{ route('admin.hrm.my-tasks.index') }}"
                class="group block rounded-2xl border border-gray-100 bg-white p-5 text-center transition-colors hover:border-blue-200"
            >
                <div
                    class="mx-auto mb-3 flex h-11 w-11 items-center justify-center rounded-full bg-blue-50 transition-colors group-hover:bg-blue-100"
                >
                    <i data-lucide="clipboard-list" class="h-5 w-5 text-blue-500"></i>
                </div>
                <p class="mb-2 text-[11px] font-semibold tracking-wide text-gray-400 uppercase">Assigned Tasks</p>
                <p class="mb-1 text-[26px] leading-none font-black text-gray-900">{{ $assignedTaskCount }}</p>
                <p class="text-[11px] font-semibold text-blue-500">View all →</p>
            </a>

            {{-- Pending Leaves --}}
            <a
                href="{{ route('admin.hrm.my-leaves.index') }}"
                class="group block rounded-2xl border border-gray-100 bg-white p-5 text-center transition-colors hover:border-amber-200"
            >
                <div
                    class="mx-auto mb-3 flex h-11 w-11 items-center justify-center rounded-full bg-amber-50 transition-colors group-hover:bg-amber-100"
                >
                    <i data-lucide="hourglass" class="h-5 w-5 text-amber-500"></i>
                </div>
                <p class="mb-2 text-[11px] font-semibold tracking-wide text-gray-400 uppercase">Pending Leaves</p>
                <p class="mb-1 text-[26px] leading-none font-black text-gray-900">{{ $pendingLeaveCount }}</p>
                <p class="text-[11px] font-semibold {{ $pendingLeaveCount > 0 ? 'text-amber-500' : 'text-gray-400' }}">
                    {{ $pendingLeaveCount > 0 ? 'Awaiting approval' : 'All clear' }}
                </p>
            </a>

            {{-- Days Present --}}
            <div class="rounded-2xl border border-gray-100 bg-white p-5 text-center">
                <div class="mx-auto mb-3 flex h-11 w-11 items-center justify-center rounded-full bg-purple-50">
                    <i data-lucide="trending-up" class="h-5 w-5 text-purple-500"></i>
                </div>
                <p class="mb-2 text-[11px] font-semibold tracking-wide text-gray-400 uppercase">Days Present</p>
                <p class="mb-1 text-[26px] leading-none font-black text-gray-900">{{ $presentThisMonth }}</p>
                <p class="text-[11px] font-semibold text-purple-500">This month</p>
            </div>
        </div>

        {{-- ── Recent Attendance + Recent Leaves ── --}}
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            {{-- Recent Attendance --}}
            <div class="section-card">
                <div class="section-header">
                    <div class="section-icon bg-blue-50">
                        <i data-lucide="history" class="h-4 w-4 text-blue-500"></i>
                    </div>
                    <p class="text-[13px] font-black text-gray-800">Recent Attendance</p>
                    <a
                        href="{{ route('admin.hrm.my-attendance.index') }}"
                        class="ml-auto text-[11px] font-bold text-blue-600 hover:text-blue-800"
                    >
                        View Full History
                    </a>
                </div>
                <div class="divide-y divide-gray-50">
                    @forelse ($recentAttendance as $att)
                        @php $sc = \App\Models\Hrm\Attendance::STATUS_COLORS[$att->status] ?? ['bg'=>'#f3f4f6','text'=>'#374151','dot'=>'#9ca3af']; @endphp
                        <div class="flex items-center justify-between px-4 py-3">
                            <div>
                                <p class="text-[13px] font-bold text-gray-700">{{ $att->date->format('M d, Y') }}</p>
                                <p class="text-[11px] text-gray-400">{{ $att->date->format('l') }}</p>
                            </div>
                            <div class="flex items-center gap-3 text-right">
                                @if ($att->check_in_time)
                                    <p class="text-[11px] text-gray-400">{{ $att->check_in_time->format('h:i A') }}</p>
                                @endif
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-[11px] font-extrabold tracking-wider uppercase"
                                    style="background: {{ $sc['bg'] }}; color: {{ $sc['text'] }}"
                                >
                                    {{ \App\Models\Hrm\Attendance::STATUS_LABELS[$att->status] ?? $att->status }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <p class="px-4 py-8 text-center text-[13px] text-gray-400">No attendance records yet.</p>
                    @endforelse
                </div>
            </div>

            {{-- Recent Leaves --}}
            <div class="section-card">
                <div class="section-header">
                    <div class="section-icon bg-green-50">
                        <i data-lucide="plane-takeoff" class="h-4 w-4 text-green-500"></i>
                    </div>
                    <p class="text-[13px] font-black text-gray-800">Recent Leaves</p>
                </div>
                <div class="divide-y divide-gray-50">
                    @forelse ($recentLeaves as $leave)
                        @php $sc = \App\Models\Hrm\Leave::STATUS_COLORS[$leave->status]; @endphp
                        <div class="flex items-center justify-between px-4 py-3">
                            <div>
                                <p class="text-[13px] font-bold text-gray-800">{{ $leave->leaveType?->name }}</p>
                                <p class="text-[11px] text-gray-400">
                                    {{ $leave->from_date->format('M d') }} – {{ $leave->to_date->format('M d') }}
                                </p>
                            </div>
                            <span
                                class="rounded-lg px-2.5 py-1 text-[11px] font-extrabold tracking-wider uppercase"
                                style="background: {{ $sc['bg'] }}; color: {{ $sc['text'] }}"
                            >
                                {{ \App\Models\Hrm\Leave::STATUS_LABELS[$leave->status] }}
                            </span>
                        </div>
                    @empty
                        <p class="px-4 py-8 text-center text-[13px] text-gray-400">No leave requests yet.</p>
                    @endforelse
                </div>
                <div class="border-t border-gray-50 px-4 py-3">
                    <a
                        href="{{ route('admin.hrm.my-leaves.index') }}"
                        class="text-[12px] font-black"
                        style="color: var(--brand-600)"
                    >
                        Manage Leaves →
                    </a>
                </div>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════
         QR SCANNER MODAL — must stay INSIDE x-data wrapper
    ══════════════════════════════════════════════════════ --}}
        <template x-teleport="body">
            <div x-show="showScanner" x-cloak class="scan-modal-backdrop" @click.self="closeScanner()">
                <div class="scan-modal-box" @click.stop>
                    {{-- Header --}}
                    <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                        <div>
                            <p class="text-[15px] font-black text-gray-900">Mark Attendance</p>
                            <p class="mt-0.5 text-[12px] text-gray-400" x-text="scanStatus"></p>
                        </div>
                        <button
                            @click="closeScanner()"
                            class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600"
                        >
                            <i data-lucide="x" class="h-4 w-4"></i>
                        </button>
                    </div>

                    {{-- Scanner area --}}
                    <div class="p-4">
                        {{-- Camera / QR Scanner --}}
                        <div x-show="scanStep === 'scan'">
                            <p class="mb-3 text-center text-[12px] text-gray-400">Point your camera at the attendance QR code</p>
                            <div id="qr-reader" class="overflow-hidden rounded-xl" style="min-height: 280px"></div>
                        </div>

                        {{-- Processing --}}
                        <div x-show="scanStep === 'processing'" class="py-8 text-center">
                            <div
                                class="mx-auto mb-4 flex h-14 w-14 animate-pulse items-center justify-center rounded-2xl bg-blue-50"
                            >
                                <i data-lucide="loader" class="h-7 w-7 text-blue-500"></i>
                            </div>
                            <p class="text-[14px] font-black text-gray-800">Processing...</p>
                            <p class="mt-1 text-[12px] text-gray-400">Marking your attendance</p>
                        </div>

                        {{-- Success --}}
                        <div x-show="scanStep === 'success'" class="py-6 text-center">
                            <div
                                class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-green-50"
                            >
                                <i data-lucide="check-circle-2" class="h-8 w-8 text-green-500"></i>
                            </div>
                            <p class="mb-1 text-[16px] font-black text-gray-900" x-text="successMessage"></p>
                            <p class="text-[12px] text-gray-400">Attendance has been recorded successfully.</p>
                            <button
                                @click="closeScanner()"
                                class="mt-5 cursor-pointer rounded-xl border-none px-6 py-2.5 text-[13px] font-black text-white"
                                style="background: var(--brand-600)"
                            >
                                Done
                            </button>
                        </div>

                        {{-- Error --}}
                        <div x-show="scanStep === 'error'" class="py-6 text-center">
                            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-red-50">
                                <i data-lucide="alert-circle" class="h-7 w-7 text-red-500"></i>
                            </div>
                            <p class="mb-1 text-[14px] font-black text-gray-800" x-text="errorTitle()"></p>
                            <p class="mb-2 text-[12px] text-gray-400" x-text="errorMessage"></p>
                            <div
                                x-show="errorCode === 'shift_ended' && errorContext.shift_start"
                                class="mx-auto mb-4 inline-flex items-center gap-1.5 rounded-full bg-gray-50 px-3 py-1 text-[11px] font-bold text-gray-500"
                            >
                                <i data-lucide="clock" class="h-3 w-3"></i>
                                <span
                                    x-text="'Shift: ' + errorContext.shift_start + ' – ' + errorContext.shift_end"
                                ></span>
                            </div>
                            <button
                                @click="retryScanner()"
                                class="cursor-pointer rounded-xl border-none px-6 py-2.5 text-[13px] font-black text-white"
                                style="background: var(--brand-600)"
                            >
                                Try Again
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>

@endsection

@push ('scripts')
    {{-- html5-qrcode — production-grade QR scanner library --}}
    {{-- <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script> --}}
    <script src="{{ asset('assets/js/html5-qrcode.min.js') }}"></script>

    <script>
        function empDashboard() {
            // 🌟 CRITICAL FIX: Store the scanner instance OUTSIDE the reactive Alpine object.
            // This prevents Alpine's Proxy system from breaking the camera's internal feed.
            let scannerInstance = null;

            return {
                showScanner: false,
                scanStep: "location", // location | scan | processing | success | error
                scanStatus: "Allow location access to continue",
                successMessage: "",
                errorMessage: "",
                errorCode: "",
                errorContext: {},
                latitude: null,
                longitude: null,
                accuracy: null,
                isProcessingScan: false,
                forceCheckout: false,
                forceCheckoutGps: false,
                forceCheckinGps: false,
                pendingStoreId: null,
                isLocating: false,
                gpsFallbackContext: null,

                init() {
                    if (window.lucide) lucide.createIcons();
                },

                // Central place to move into the error step so title/message/badge
                // always stay in sync — avoids "Scan Failed" showing for things
                // that aren't actually a bad scan (e.g. shift already ended).
                showError(message, code = "", context = {}) {
                    this.scanStep = "error";
                    this.errorMessage = message || "Something went wrong.";
                    this.errorCode = code;
                    this.errorContext = context || {};
                    this.scanStatus = this.errorTitle();
                    this.isProcessingScan = false;
                    this.$nextTick(() => {
                        if (window.lucide) lucide.createIcons();
                    });
                },

                errorTitle() {
                    return (
                        {
                            shift_ended: "Shift Ended",
                        }[this.errorCode] || "Scan Failed"
                    );
                },

                async openScanner() {
                    // Check for mandatory announcements before allowing scan
                    try {
                        const res = await fetch("{{ route("admin.announcements-popup.pending") }}", {
                            headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
                        });
                        if (res.ok) {
                            const data = await res.json();
                            if (data.mandatory_count > 0) {
                                window.dispatchEvent(new CustomEvent("announcements:recheck"));
                                if (typeof BizAlert !== "undefined") {
                                    BizAlert.toast(
                                        "Please acknowledge all mandatory announcements before marking attendance.",
                                        "error",
                                    );
                                }
                                return;
                            }
                        }
                    } catch (e) {
                        console.warn("Announcement check failed, proceeding with scanner:", e);
                    }

                    // Reset states and go directly to location → camera
                    this.showScanner = true;
                    this.errorMessage = "";
                    this.successMessage = "";
                    this.forceCheckout = false;
                    this.forceCheckoutGps = false;
                    this.gpsFallbackContext = null;
                    this.isLocating = false;
                    this.scanStep = "processing";
                    this.scanStatus = "Getting your location...";
                    this.$nextTick(() => {
                        if (window.lucide) lucide.createIcons();
                    });
                    this.requestLocation();
                },

                closeScanner() {
                    this.stopCamera();
                    this.showScanner = false;
                },

                stopCamera() {
                    if (scannerInstance) {
                        try {
                            // Stop the hardware feed and clear the canvas safely
                            scannerInstance.stop().catch(() => {});
                            scannerInstance.clear();
                        } catch (e) {}
                        scannerInstance = null;
                    }
                },

                // A fix at/below this accuracy (meters) is accepted immediately.
                // Above it, we try to get a better fix before finalizing.
                GPS_GOOD_ACCURACY_METERS: 50,

                requestLocation() {
                    // Guard: only one location request should run at a time
                    if (this.isLocating) return;
                    this.isLocating = true;
                    this.accuracy = null;
                    if (!navigator.geolocation) {
                        this.isLocating = false;
                        this.showError("Location not supported. Try opening this page in Chrome.");
                        return;
                    }
                    let settled = false;
                    let bestFix = null; // { lat, lng, accuracy }

                    const captureIfBetter = (pos) => {
                        const acc = pos.coords.accuracy;
                        if (!bestFix || acc < bestFix.accuracy) {
                            bestFix = { lat: pos.coords.latitude, lng: pos.coords.longitude, accuracy: acc };
                        }
                        return acc;
                    };

                    const finalize = () => {
                        if (settled || !bestFix) return;
                        settled = true;
                        this.isLocating = false;
                        this.latitude = bestFix.lat;
                        this.longitude = bestFix.lng;
                        this.accuracy = bestFix.accuracy;
                        this.startCamera();
                    };

                    const onFinalError = (err) => {
                        if (settled) return;
                        // We already have some fix from an earlier attempt — use it rather
                        // than blocking the employee entirely; backend will judge accuracy.
                        if (bestFix) {
                            finalize();
                            return;
                        }
                        settled = true;
                        this.isLocating = false;
                        if (err.code === err.PERMISSION_DENIED) {
                            this.showError("Location permission denied. Please allow location access in your browser.");
                        } else if (err.code === err.POSITION_UNAVAILABLE) {
                            this.showError(
                                "Could not get location. Try opening this page in Chrome/Safari directly (not in app browser).",
                            );
                        } else {
                            this.showError(
                                "GPS Timeout. Please check if Location is ON in phone settings and try outside or near a window.",
                            );
                        }
                    };
                    // Step 2: GPS hardware fallback — longer timeout so older/slower devices can get a lock
                    const tryHighAccuracy = () => {
                        if (settled) return;
                        this.scanStatus = "Getting a precise GPS lock, please wait...";
                        navigator.geolocation.getCurrentPosition(
                            (pos) => {
                                captureIfBetter(pos);
                                finalize();
                            },
                            onFinalError,
                            { enableHighAccuracy: true, timeout: 28000, maximumAge: 300000 },
                        );
                    };
                    // Step 1: Network/WiFi — fast fallback, 7 sec timeout
                    navigator.geolocation.getCurrentPosition(
                        (pos) => {
                            const acc = captureIfBetter(pos);
                            if (acc <= this.GPS_GOOD_ACCURACY_METERS) {
                                finalize();
                            } else {
                                // Fast fix isn't precise enough — don't accept it blindly,
                                // try for a better one instead.
                                tryHighAccuracy();
                            }
                        },
                        (err) => {
                            if (settled) return;
                            if (err.code === err.PERMISSION_DENIED) {
                                onFinalError(err);
                                return;
                            }
                            tryHighAccuracy();
                        },
                        { enableHighAccuracy: false, timeout: 7000, maximumAge: 120000 },
                    );
                },

                startCamera() {
                    this.stopCamera();
                    this.scanStep = "scan";
                    this.scanStatus = "Scan the QR code shown at reception";
                    // 🌟 RESET the lock when camera starts
                    this.isProcessingScan = false;

                    this.$nextTick(() => {
                        if (window.lucide) lucide.createIcons();
                        if (typeof Html5Qrcode === "undefined") {
                            /* error handling */ return;
                        }

                        scannerInstance = new Html5Qrcode("qr-reader");
                        const config = {
                            fps: 10,
                            qrbox: (vw, vh) => {
                                const minEdge = Math.min(vw, vh);
                                return { width: minEdge * 0.7, height: minEdge * 0.7 };
                            },
                        };

                        scannerInstance
                            .start(
                                { facingMode: "environment" },
                                config,
                                (decodedText) => {
                                    // 🌟 THE FIX: If we are already processing a scan, ignore everything else!
                                    if (this.isProcessingScan) return;

                                    this.isProcessingScan = true; // Lock the door
                                    this.stopCamera();
                                    this.submitScan(decodedText);
                                },
                                (errorMessage) => {},
                            )
                            .catch((err) => {
                                /* error handling */
                            });
                    });
                },

                /**
                 * Extract store_id from QR content.
                 * Only supports the standard format: /attend/{id}
                 */
                parseStoreId(qrText) {
                    const attendMatch = qrText.match(/\/attend\/(\d+)/);
                    if (attendMatch) return parseInt(attendMatch[1]);
                    return null;
                },

                async submitScan(qrData) {
                    const storeId = this.parseStoreId(qrData);
                    this.pendingStoreId = storeId;
                    if (!storeId) {
                        this.showError("Invalid QR code. Please scan the attendance QR at your office.");
                        return;
                    }

                    this.scanStep = "processing";
                    this.scanStatus = "Recording attendance...";
                    this.$nextTick(() => {
                        if (window.lucide) lucide.createIcons();
                    });

                    try {
                        const res = await fetch("{{ route("admin.hrm.attendance.scan") }}", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": document.querySelector("meta[name=csrf-token]").content,
                                Accept: "application/json",
                                "X-Device-Info": navigator.userAgent.substring(0, 200),
                            },
                            body: JSON.stringify({
                                store_id: storeId,
                                latitude: this.latitude,
                                longitude: this.longitude,
                                accuracy: this.accuracy,
                                force_checkout: this.forceCheckout, // 🌟 Pass the flag to Laravel
                                force_checkout_gps: this.forceCheckoutGps,
                                force_checkin_gps: this.forceCheckinGps,
                            }),
                        });

                        if (res.status === 429) {
                            throw new Error("Too many attempts. Please wait a minute and try again.");
                        }

                        const data = await res.json();

                        // Backend couldn't verify location for check-in/check-out — offer a
                        // manager-reviewed fallback instead of a hard block.
                        if (data.requires_gps_fallback) {
                            const isCheckIn = data.action === "check_in";

                            this.isProcessingScan = false;
                            this.scanStep = "processing";
                            this.scanStatus = "Waiting for confirmation...";
                            this.gpsFallbackContext = data.context || null;

                            Swal.fire({
                                title: "Location Not Verified",
                                text: data.message,
                                icon: "warning",
                                showCancelButton: true,
                                confirmButtonColor: "#f59e0b",
                                cancelButtonColor: "#6c757d",
                                confirmButtonText: isCheckIn ? "Request Check-in Anyway" : "Request Checkout Anyway",
                                cancelButtonText: "Try Again",
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    if (isCheckIn) {
                                        this.forceCheckinGps = true;
                                    } else {
                                        this.forceCheckoutGps = true;
                                    }
                                    this.submitScan(qrData);
                                } else {
                                    this.showError(
                                        (isCheckIn ? "Check-in" : "Checkout") +
                                            " not completed. Please try again with a stronger GPS signal, or ask your manager for help.",
                                    );
                                }
                            });
                            return;
                        }

                        // Backend says "leaving early?" — ask for confirmation
                        if (data.requires_confirmation) {
                            this.isProcessingScan = false;
                            // 🌟 FIX: Keep modal open in processing state, DO NOT close scanner
                            this.scanStep = "processing";
                            this.scanStatus = "Waiting for confirmation...";

                            Swal.fire({
                                title: "Leave Early?",
                                text: data.message,
                                icon: "warning",
                                showCancelButton: true,
                                confirmButtonColor: "#ef4444",
                                cancelButtonColor: "#6c757d",
                                confirmButtonText: "Yes, Checkout Now",
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    // 🌟 FIX: Directly re-submit — no camera restart, no second QR scan needed!
                                    this.isProcessingScan = true;
                                    this.scanStep = "processing";
                                    this.scanStatus = "Recording attendance...";
                                    this.$nextTick(() => {
                                        if (window.lucide) lucide.createIcons();
                                    });

                                    fetch("{{ route("admin.hrm.attendance.scan") }}", {
                                        method: "POST",
                                        headers: {
                                            "Content-Type": "application/json",
                                            "X-CSRF-TOKEN": document.querySelector("meta[name=csrf-token]").content,
                                            Accept: "application/json",
                                            "X-Device-Info": navigator.userAgent.substring(0, 200),
                                        },
                                        body: JSON.stringify({
                                            store_id: this.pendingStoreId,
                                            latitude: this.latitude,
                                            longitude: this.longitude,
                                            accuracy: this.accuracy,
                                            force_checkout: true,
                                            force_checkout_gps: this.forceCheckoutGps,
                                        }),
                                    })
                                        .then((r) => r.json())
                                        .then((d) => {
                                            if (d.success) {
                                                this.scanStep = "success";
                                                this.successMessage = d.message;
                                                this.scanStatus = "Attendance recorded!";
                                                this.$nextTick(() => {
                                                    if (window.lucide) lucide.createIcons();
                                                });
                                                setTimeout(() => window.location.reload(), 3500);
                                            } else {
                                                this.showError(d.message, d.error_code, d.context);
                                            }
                                        })
                                        .catch(() => {
                                            this.showError("Network error. Please check your connection.");
                                        });
                                } else {
                                    // User cancelled — let them retry from the error step
                                    this.showError("Check-out cancelled.");
                                }
                            });
                            return;
                        }

                        if (data.success) {
                            this.scanStep = "success";
                            this.successMessage = data.message;
                            this.scanStatus = "Attendance recorded!";
                            this.$nextTick(() => {
                                if (window.lucide) lucide.createIcons();
                            });
                            // 🌟 FIX: Removed redundant Swal, extended timer to 3.5s so UI is fully readable
                            setTimeout(() => window.location.reload(), 3500);
                        } else {
                            this.showError(data.message, data.error_code, data.context);
                        }
                    } catch (e) {
                        this.showError(
                            e.message === "Too many attempts. Please wait a minute and try again."
                                ? e.message
                                : "Network error. Please check your connection.",
                        );
                    }
                    this.$nextTick(() => {
                        if (window.lucide) lucide.createIcons();
                    });
                },

                retryScanner() {
                    this.stopCamera();
                    this.latitude = null;
                    this.longitude = null;
                    this.isProcessingScan = false; // 🌟 Ensure unlock when retrying
                    this.openScanner();
                },
            };
        }

        // ── Todo Widget ──────────────────────────────────────────────────────
        function todoWidget() {
            const BASE = "{{ url("admin/hrm/employee/todos") }}";
            const hdrs = () => ({
                "Content-Type": "application/json",
                Accept: "application/json",
                "X-CSRF-TOKEN": document.querySelector("meta[name=csrf-token]").content,
            });

            return {
                todos: [],
                newTitle: "",
                editingId: null,
                editTitle: "",
                saving: false,

                init() {
                    const d = @json ($todoData);
                    const all = [...(d.pending || []), ...(d.completed || []), ...(d.overdue || [])];
                    const seen = new Set();
                    this.todos = all.filter((t) => {
                        if (seen.has(t.id)) return false;
                        seen.add(t.id);
                        return true;
                    });
                    this.$nextTick(() => {
                        if (window.lucide) lucide.createIcons();
                    });
                },

                async addTodo() {
                    if (!this.newTitle.trim() || this.saving) return;
                    this.saving = true;
                    try {
                        const res = await fetch(BASE, {
                            method: "POST",
                            headers: hdrs(),
                            body: JSON.stringify({ title: this.newTitle }),
                        });
                        const data = await res.json();
                        if (data.success) {
                            this.todos.unshift(data.todo);
                            this.newTitle = "";
                            this.$nextTick(() => {
                                if (window.lucide) lucide.createIcons();
                            });
                        }
                    } catch (e) {
                        console.error("addTodo:", e);
                    }
                    this.saving = false;
                },

                startEdit(todo) {
                    if (todo.status === "completed") return;
                    this.editingId = todo.id;
                    this.editTitle = todo.title;
                },
                cancelEdit() {
                    this.editingId = null;
                    this.editTitle = "";
                },

                async saveEdit(todo) {
                    if (!this.editTitle.trim() || this.saving) return;
                    this.saving = true;
                    try {
                        const res = await fetch(`${BASE}/${todo.id}`, {
                            method: "PUT",
                            headers: hdrs(),
                            body: JSON.stringify({ title: this.editTitle }),
                        });
                        const data = await res.json();
                        if (data.success) {
                            const i = this.todos.findIndex((t) => t.id === todo.id);
                            if (i !== -1) this.todos[i] = data.todo;
                            this.editingId = null;
                        }
                    } catch (e) {
                        console.error("saveEdit:", e);
                    }
                    this.saving = false;
                },

                async toggleComplete(todo) {
                    try {
                        const res = await fetch(`${BASE}/${todo.id}/complete`, { method: "PATCH", headers: hdrs() });
                        const data = await res.json();
                        if (data.success) {
                            const i = this.todos.findIndex((t) => t.id === todo.id);
                            if (i !== -1) this.todos[i] = data.todo;
                            this.$nextTick(() => {
                                if (window.lucide) lucide.createIcons();
                            });
                        }
                    } catch (e) {
                        console.error("toggleComplete:", e);
                    }
                },

                async deleteTodo(todo) {
                    const result = await BizAlert.confirm(
                        "Delete Task?",
                        "This task will be permanently removed.",
                        "Yes, Delete",
                        "warning",
                    );
                    if (!result.isConfirmed) return;
                    try {
                        const res = await fetch(`${BASE}/${todo.id}`, { method: "DELETE", headers: hdrs() });
                        const data = await res.json();
                        if (data.success) {
                            this.todos = this.todos.filter((t) => t.id !== todo.id);
                            BizAlert.toast("Task deleted.", "success");
                        }
                    } catch (e) {
                        console.error("deleteTodo:", e);
                        BizAlert.toast("Failed to delete task.", "error");
                    }
                },
            };
        }
    </script>

@endpush
