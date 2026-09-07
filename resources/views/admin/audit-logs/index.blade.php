@extends('layouts.admin')

@section('title', 'System Audit Logs')

@section('header-title')
    <h1 class="text-sm font-bold text-gray-500 uppercase tracking-widest">Settings / Audit Logs</h1>
@endsection

@section('content')
    <div class="pb-10" x-data="auditLogViewer()">

        <div class="mb-6">
            <h1 class="text-2xl font-bold text-[#212538] tracking-tight">System Audit Trail</h1>
            <p class="text-sm text-gray-500 font-medium">Immutable record of all system creations, updates, and deletions.
            </p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-gray-900 text-white text-[11px] font-bold uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-4 rounded-tl-lg">Timestamp</th>
                            <th class="px-6 py-4">User</th>
                            <th class="px-6 py-4">Module</th>
                            <th class="px-6 py-4">Action</th>
                            <th class="px-6 py-4 text-right rounded-tr-lg">Changes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($logs as $log)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-3">
                                    <div class="font-bold text-gray-900">{{ $log->created_at->format('d M Y') }}</div>
                                    <div class="text-[11px] text-gray-500">{{ $log->created_at->format('h:i:s A') }}</div>
                                </td>
                                <td class="px-6 py-3 font-bold text-gray-800">
                                    {{ $log->causer->name ?? 'System / Auto' }}
                                </td>
                                <td class="px-6 py-3">
                                    <span
                                        class="bg-gray-100 text-gray-700 px-2 py-1 rounded text-xs font-bold uppercase tracking-wider">
                                        {{ class_basename($log->subject_type) }} #{{ $log->subject_id }}
                                    </span>
                                </td>
                                <td class="px-6 py-3">
                                    @php
                                        $color = match ($log->event) {
                                            'created' => 'text-green-600',
                                            'updated' => 'text-blue-600',
                                            'deleted' => 'text-red-600',
                                            default => 'text-gray-600',
                                        };
                                    @endphp
                                    <span class="font-black uppercase text-[11px] {{ $color }}">
                                        {{ $log->description }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-right">
                                    @if (isset($log->properties['old']) || isset($log->properties['attributes']))
                                        <button
                                            @click="viewChanges({{ json_encode($log->properties) }}, '{{ $log->description }}')"
                                            class="text-indigo-600 hover:text-indigo-800 bg-indigo-50 px-3 py-1.5 rounded-lg text-xs font-bold transition-colors">
                                            View Data
                                        </button>
                                    @else
                                        <span class="text-gray-400 text-xs italic">No payload</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500 font-medium">No audit logs
                                    found. Make a change in the system to see it here!</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($logs->hasPages())
                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>

        {{-- Alpine Modal for viewing JSON Differences --}}
        <div x-show="isOpen" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div class="bg-white w-full max-w-2xl rounded-xl shadow-2xl overflow-hidden" @click.away="isOpen = false">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                    <h3 class="font-black text-gray-800 uppercase tracking-widest text-sm" x-text="modalTitle"></h3>
                    <button @click="isOpen = false" class="text-gray-400 hover:text-red-500"><i data-lucide="x"
                            class="w-5 h-5"></i></button>
                </div>

                <div class="p-6 grid grid-cols-2 gap-4 max-h-[60vh] overflow-y-auto">
                    <div>
                        <h4 class="text-xs font-bold text-red-500 uppercase mb-2 border-b border-red-100 pb-1">Old Values
                        </h4>
                        <pre class="text-[11px] bg-red-50 text-red-900 p-3 rounded overflow-x-auto font-mono"
                            x-text="JSON.stringify(properties.old || {}, null, 2)"></pre>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-green-500 uppercase mb-2 border-b border-green-100 pb-1">New
                            Values</h4>
                        <pre class="text-[11px] bg-green-50 text-green-900 p-3 rounded overflow-x-auto font-mono"
                            x-text="JSON.stringify(properties.attributes || {}, null, 2)"></pre>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        // SPA-safe registration. Pushed scripts are re-executed by rerunScripts()
        // on in-place navigation, long after Alpine has booted — so alpine:init
        // has already fired and a plain listener never runs, leaving
        // x-data="auditLogViewer()" pointing at nothing.
        (function registerAuditLogAlpine() {
            function register() {
                Alpine.data('auditLogViewer', () => ({
                isOpen: false,
                properties: {},
                modalTitle: '',

                    viewChanges(props, title) {
                        this.properties = props;
                        this.modalTitle = title;
                        this.isOpen = true;
                    }
                }));
            }

            if (window.Alpine) {
                // SPA navigation: Alpine is already running — register now so the
                // Alpine.initTree() call that follows this script finds the factory.
                register();
            } else {
                // Fresh page load: queue it, { once: true } so it never accumulates.
                document.addEventListener('alpine:init', register, { once: true });
            }
        })();
    </script>
@endpush
