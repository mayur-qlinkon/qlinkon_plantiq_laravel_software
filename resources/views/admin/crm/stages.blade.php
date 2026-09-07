@extends('layouts.admin')

@section('title', 'Stages — ' . $pipeline->name)

@section('header-title')
    <div>
        <h1 class="text-sm font-bold text-gray-500 uppercase tracking-widest">{{ $pipeline->name }}</h1>        
    </div>
@endsection

@push('styles')
<style>
    [x-cloak] { display: none !important; }

    .field-input {
        width: 100%;
        border: 1.5px solid #e5e7eb;
        border-radius: 10px;
        padding: 8px 12px;
        font-size: 13px;
        color: #1f2937;
        outline: none;
        font-family: inherit;
        background: #fff;
        transition: border-color 150ms ease;
    }

    .field-input:focus { border-color: var(--brand-600); }

    .stage-row {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 16px;
        background: #fff;
        border: 1.5px solid #f1f5f9;
        border-radius: 12px;
        transition: box-shadow 150ms, border-color 150ms;
        cursor: default;
    }

    .stage-row:hover {
        border-color: #e2e8f0;
        box-shadow: 0 2px 12px rgba(0,0,0,0.05);
    }

    .stage-row.sortable-ghost {
        opacity: 0.4;
        background: #f8fafc;
    }

    .stage-row.sortable-drag {
        box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        border-color: var(--brand-600);
    }

    .drag-handle {
        color: #d1d5db;
        cursor: grab;
        flex-shrink: 0;
        padding: 2px;
    }

    .drag-handle:active { cursor: grabbing; }

    .color-dot {
        width: 18px; height: 18px;
        border-radius: 50%;
        flex-shrink: 0;
        border: 2px solid rgba(0,0,0,0.1);
    }

    .badge {
        display: inline-flex;
        align-items: center;
        padding: 2px 8px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 700;
        white-space: nowrap;
    }

    .badge-won  { background: #f0fdf4; color: #15803d; }
    .badge-lost { background: #fef2f2; color: #dc2626; }

    /* ── Inline edit row ── */
    .edit-row {
        padding: 14px 16px;
        background: #f8fafc;
        border: 1.5px solid var(--brand-600);
        border-radius: 12px;
    }

    /* ── Color picker ── */
    .color-swatch {
        width: 26px; height: 26px;
        border-radius: 50%;
        cursor: pointer;
        border: 2px solid transparent;
        transition: transform 150ms, border-color 150ms;
        flex-shrink: 0;
    }

    .color-swatch:hover  { transform: scale(1.15); }
    .color-swatch.active { border-color: #1f2937; transform: scale(1.1); }

    /* ── Toggle ── */
    .toggle-track {
        position: relative;
        width: 36px; height: 20px;
        background: #d1d5db;
        border-radius: 10px;
        cursor: pointer;
        transition: background 150ms;
        flex-shrink: 0;
    }

    .toggle-track.on { background: #22c55e; }

    .toggle-thumb {
        position: absolute;
        top: 2px; left: 2px;
        width: 16px; height: 16px;
        border-radius: 50%;
        background: #fff;
        transition: transform 150ms;
        box-shadow: 0 1px 3px rgba(0,0,0,0.2);
    }

    .toggle-track.on .toggle-thumb { transform: translateX(16px); }
</style>
@endpush

@section('content')

<div class="pb-10 w-full" x-data="stagesPage()">

    {{-- ── Breadcrumb ── --}}
    <div class="flex items-center gap-2 text-sm text-gray-400 font-medium mb-5">
        <a href="{{ route('admin.crm.pipelines.index') }}"
            class="hover:text-gray-700 transition-colors">Pipelines</a>
        <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
        <span class="text-gray-700 font-semibold">{{ $pipeline->name }}</span>
        <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
        <span class="text-gray-700 font-semibold">Stages</span>
    </div>

    {{-- ── Page header ── --}}
    <div class="flex items-center justify-between mb-5 flex-wrap gap-3">
        <div>
            <p class="text-[13px] text-gray-500 font-medium">
                Drag to reorder · Click <i data-lucide="edit" class="w-3 h-3 inline"></i> to edit inline
            </p>
        </div>
        @if($pipeline->is_default)
            <span class="text-[11px] font-bold px-3 py-1 rounded-full bg-blue-50 text-blue-600">
                Default Pipeline
            </span>
        @endif
    </div>

    {{-- ── Feedback banners ── --}}
    <template x-if="pageSuccess">
        <div class="mb-4 bg-green-50 border border-green-200 rounded-xl px-4 py-3 flex items-center gap-3">
            <i data-lucide="check-circle" class="w-4 h-4 text-green-600 flex-shrink-0"></i>
            <p class="text-sm font-semibold text-green-800" x-text="pageSuccess"></p>
        </div>
    </template>

    <template x-if="pageError">
        <div class="mb-4 bg-red-50 border border-red-200 rounded-xl px-4 py-3 flex items-center gap-3">
            <i data-lucide="alert-circle" class="w-4 h-4 text-red-500 flex-shrink-0"></i>
            <p class="text-sm font-semibold text-red-700" x-text="pageError"></p>
        </div>
    </template>

    {{-- ════════ STAGE LIST ════════ --}}
    <div id="stages-list" class="space-y-2 mb-4">
        <template x-for="(stage, index) in stages" :key="stage.id">
            <div :data-id="stage.id">

                {{-- ── View row ── --}}
                <div x-show="editingId !== stage.id" class="stage-row">

                    {{-- Drag handle ── --}}
                    <div class="drag-handle" title="Drag to reorder">
                        <i data-lucide="grip-vertical" class="w-4 h-4"></i>
                    </div>

                    {{-- Sort order ── --}}
                    <span class="text-[11px] font-mono text-gray-400 w-5 text-center flex-shrink-0"
                        x-text="index + 1"></span>

                    {{-- Color dot ── --}}
                    <div class="color-dot" :style="`background: ${stage.color}`"></div>

                    {{-- Name + badges ── --}}
                    <div class="flex-1 min-w-0 flex items-center gap-2 flex-wrap">
                        <span class="text-[14px] font-bold text-gray-800" x-text="stage.name"></span>
                        <span x-show="stage.is_won"  class="badge badge-won">🏆 Won</span>
                        <span x-show="stage.is_lost" class="badge badge-lost">✕ Lost</span>
                        <span x-show="!stage.is_active"
                            class="badge" style="background:#f3f4f6; color:#6b7280">Inactive</span>
                    </div>

                    {{-- Lead count ── --}}
                    <span class="text-[12px] text-gray-400 font-medium flex-shrink-0"
                        x-text="(stage.leads_count ?? 0) + ' lead' + ((stage.leads_count ?? 0) !== 1 ? 's' : '')">
                    </span>

                    {{-- Actions ── --}}
                    <div class="flex items-center gap-1 flex-shrink-0">
                        <button @click="startEdit(stage)"
                            class="w-7 h-7 flex items-center justify-center rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition-colors">
                            <i data-lucide="edit" class="w-3.5 h-3.5"></i>
                        </button>
                        <button @click="deleteStage(stage.id, stage.name)"
                            class="w-7 h-7 flex items-center justify-center rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors">
                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                        </button>
                    </div>
                </div>

                {{-- ── Inline edit row ── --}}
                <div x-show="editingId === stage.id" x-cloak class="edit-row space-y-3">

                    {{-- Error ── --}}
                    <template x-if="formError">
                        <div class="bg-red-50 border border-red-200 rounded-lg px-3 py-2">
                            <p class="text-[12px] font-semibold text-red-600" x-text="formError"></p>
                        </div>
                    </template>

                    {{-- Row 1: Name + Color ── --}}
                    <div class="flex items-center gap-3 flex-wrap">
                        <input type="text"
                            x-model="editForm.name"
                            placeholder="Stage name"
                            class="field-input flex-1 min-w-[140px]"
                            @keydown.enter.prevent="saveEdit(stage.id)"
                            @keydown.escape="cancelEdit()">

                        {{-- Color swatches ── --}}
                        <div class="flex items-center gap-1.5 flex-wrap">
                            @php
                                $colors = ['#6b7280','#3b82f6','#8b5cf6','#f59e0b','#f97316','#10b981','#ef4444','#ec4899','#06b6d4'];
                            @endphp
                            @foreach($colors as $color)
                                <button type="button"
                                    class="color-swatch"
                                    style="background: {{ $color }}"
                                    :class="editForm.color === '{{ $color }}' ? 'active' : ''"
                                    @click="editForm.color = '{{ $color }}'">
                                </button>
                            @endforeach
                            {{-- Custom hex ── --}}
                            <input type="color"
                                x-model="editForm.color"
                                class="w-7 h-7 rounded-full cursor-pointer border-0 p-0"
                                title="Custom color"
                                style="background: transparent">
                        </div>
                    </div>

                    {{-- Row 2: Won / Lost / Active toggles ── --}}
                    <div class="flex items-center gap-4 flex-wrap">

                        <label class="flex items-center gap-2 cursor-pointer select-none">
                            <div class="toggle-track" :class="editForm.is_won ? 'on' : ''"
                                @click="editForm.is_won = !editForm.is_won; if(editForm.is_won) editForm.is_lost = false">
                                <div class="toggle-thumb"></div>
                            </div>
                            <span class="text-[12px] font-semibold text-gray-600">Won stage 🏆</span>
                        </label>

                        <label class="flex items-center gap-2 cursor-pointer select-none">
                            <div class="toggle-track" :class="editForm.is_lost ? 'on' : ''"
                                @click="editForm.is_lost = !editForm.is_lost; if(editForm.is_lost) editForm.is_won = false">
                                <div class="toggle-thumb"></div>
                            </div>
                            <span class="text-[12px] font-semibold text-gray-600">Lost stage ✕</span>
                        </label>

                        <label class="flex items-center gap-2 cursor-pointer select-none">
                            <div class="toggle-track" :class="editForm.is_active ? 'on' : ''"
                                @click="editForm.is_active = !editForm.is_active">
                                <div class="toggle-thumb"></div>
                            </div>
                            <span class="text-[12px] font-semibold text-gray-600">Active</span>
                        </label>

                    </div>

                    {{-- Row 3: Save / Cancel ── --}}
                    <div class="flex items-center gap-2">
                        <button @click="saveEdit(stage.id)"
                            :disabled="saving || !editForm.name.trim()"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-[12px] font-bold text-white transition-opacity"
                            style="background: var(--brand-600)"
                            :class="saving ? 'opacity-60 cursor-not-allowed' : 'hover:opacity-90'">
                            <i data-lucide="loader-2" x-show="saving" class="w-3.5 h-3.5 animate-spin"></i>
                            <i data-lucide="save" x-show="!saving" class="w-3.5 h-3.5"></i>
                            <span x-text="saving ? 'Saving...' : 'Save'"></span>
                        </button>
                        <button @click="cancelEdit()"
                            class="px-4 py-2 rounded-lg text-[12px] font-bold text-gray-600 border border-gray-200 hover:bg-gray-50 transition-colors">
                            Cancel
                        </button>
                    </div>

                </div>

            </div>
        </template>
    </div>

    {{-- ════════ ADD STAGE FORM ════════ --}}
    <div class="bg-white border border-gray-100 rounded-2xl overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-50">
            <p class="text-[11px] font-black text-gray-400 uppercase tracking-widest">Add New Stage</p>
        </div>
        <div class="px-5 py-4 space-y-3">

            <template x-if="addError">
                <div class="bg-red-50 border border-red-200 rounded-lg px-3 py-2">
                    <p class="text-[12px] font-semibold text-red-600" x-text="addError"></p>
                </div>
            </template>

            {{-- Name + color ── --}}
            <div class="flex items-center gap-3 flex-wrap">
                <input type="text"
                    x-model="addForm.name"
                    placeholder="Stage name (e.g. Contacted, Proposal, Negotiation)"
                    class="field-input flex-1 min-w-[200px]"
                    @keydown.enter.prevent="addStage()">

                {{-- Color swatches ── --}}
                <div class="flex items-center gap-1.5 flex-wrap">
                    @foreach($colors as $color)
                        <button type="button"
                            class="color-swatch"
                            style="background: {{ $color }}"
                            :class="addForm.color === '{{ $color }}' ? 'active' : ''"
                            @click="addForm.color = '{{ $color }}'">
                        </button>
                    @endforeach
                    <input type="color"
                        x-model="addForm.color"
                        class="w-7 h-7 rounded-full cursor-pointer border-0 p-0"
                        title="Custom color"
                        style="background: transparent">
                </div>
            </div>

            {{-- Won / Lost toggles ── --}}
            <div class="flex items-center gap-4 flex-wrap">
                <label class="flex items-center gap-2 cursor-pointer select-none">
                    <div class="toggle-track" :class="addForm.is_won ? 'on' : ''"
                        @click="addForm.is_won = !addForm.is_won; if(addForm.is_won) addForm.is_lost = false">
                        <div class="toggle-thumb"></div>
                    </div>
                    <span class="text-[12px] font-semibold text-gray-600">Won stage 🏆</span>
                </label>

                <label class="flex items-center gap-2 cursor-pointer select-none">
                    <div class="toggle-track" :class="addForm.is_lost ? 'on' : ''"
                        @click="addForm.is_lost = !addForm.is_lost; if(addForm.is_lost) addForm.is_won = false">
                        <div class="toggle-thumb"></div>
                    </div>
                    <span class="text-[12px] font-semibold text-gray-600">Lost stage ✕</span>
                </label>

                <p class="text-[11px] text-gray-400 ml-auto">
                    Won & Lost are mutually exclusive
                </p>
            </div>

            {{-- Submit ── --}}
            <div class="pt-1">
                <button @click="addStage()"
                    :disabled="adding || !addForm.name.trim()"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold text-white transition-opacity"
                    style="background: var(--brand-600)"
                    :class="(adding || !addForm.name.trim()) ? 'opacity-50 cursor-not-allowed' : 'hover:opacity-90'">
                    <i data-lucide="loader-2" x-show="adding" class="w-4 h-4 animate-spin"></i>
                    <i data-lucide="plus" x-show="!adding" class="w-4 h-4"></i>
                    <span x-text="adding ? 'Adding...' : 'Add Stage'"></span>
                </button>
            </div>

        </div>
    </div>

    {{-- ── Back link ── --}}
    <div class="mt-5">
        <a href="{{ route('admin.crm.pipelines.index') }}"
            class="inline-flex items-center gap-1.5 text-sm font-semibold text-gray-500 hover:text-gray-800 transition-colors">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            Back to Pipelines
        </a>
    </div>

</div>
@endsection

@push('scripts')
{{-- SortableJS from CDN --}}
<script src="{{ asset('assets/js/sortable.min.js') }}"></script>
<script>
function stagesPage() {
    return {
        stages:      @json($stages),
        pageSuccess: null,
        pageError:   null,

        // ── Inline edit ──
        editingId:  null,
        saving:     false,
        formError:  null,
        editForm: { name: '', color: '#6b7280', is_won: false, is_lost: false, is_active: true },

        // ── Add form ──
        adding:   false,
        addError: null,
        addForm: { name: '', color: '#3b82f6', is_won: false, is_lost: false },

        init() {
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
                this.initSortable();
            });
        },

        // ── SortableJS drag-drop ──
        initSortable() {
            const el = document.getElementById('stages-list');
            if (!el || typeof Sortable === 'undefined') return;

            Sortable.create(el, {
                handle:          '.drag-handle',
                animation:       150,
                ghostClass:      'sortable-ghost',
                dragClass:       'sortable-drag',
                onEnd: async () => {
                    // Collect new order from DOM
                    const order = [...el.querySelectorAll('[data-id]')]
                        .map(el => parseInt(el.dataset.id));

                    // Update local index display immediately
                    order.forEach((id, i) => {
                        const stage = this.stages.find(s => s.id === id);
                        if (stage) stage.sort_order = i + 1;
                    });
                    this.stages.sort((a, b) => a.sort_order - b.sort_order);

                    await this.saveOrder(order);
                }
            });
        },

        async saveOrder(order) {
            try {
                const res  = await fetch(`/admin/crm/pipelines/{{ $pipeline->id }}/stages/reorder`, {
                    method:  'POST',
                    headers: {
                        'Content-Type':     'application/json',
                        'X-CSRF-TOKEN':     document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ order }),
                });
                const data = await res.json();
                if (!data.success) {
                    this.pageError = data.message;
                }
            } catch(e) {
                console.error('[Stages] Reorder error:', e);
            }
        },

        // ── Start inline edit ──
        startEdit(stage) {
            this.editingId = stage.id;
            this.formError = null;
            this.editForm  = {
                name:      stage.name,
                color:     stage.color ?? '#6b7280',
                is_won:    !!stage.is_won,
                is_lost:   !!stage.is_lost,
                is_active: stage.is_active !== false,
            };
            this.$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); });
        },

        cancelEdit() {
            this.editingId = null;
            this.formError = null;
            this.$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); });
        },

        // ── Save inline edit ──
        async saveEdit(stageId) {
            if (!this.editForm.name.trim() || this.saving) return;
            this.saving    = true;
            this.formError = null;

            try {
                const res  = await fetch(`/admin/crm/pipelines/{{ $pipeline->id }}/stages/${stageId}`, {
                    method:  'PUT',
                    headers: {
                        'Content-Type':     'application/json',
                        'X-CSRF-TOKEN':     document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(this.editForm),
                });

                const data = await res.json();

                if (res.status === 422) {
                    this.formError = data.errors?.name?.[0] ?? data.message ?? 'Validation error.';
                    return;
                }

                if (!data.success) {
                    this.formError = data.message;
                    return;
                }

                // Update in local array
                const idx = this.stages.findIndex(s => s.id === stageId);
                if (idx !== -1) this.stages[idx] = { ...this.stages[idx], ...data.stage };

                this.editingId   = null;
                this.pageSuccess = data.message;
                setTimeout(() => this.pageSuccess = null, 3000);

                this.$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); });

            } catch(e) {
                console.error('[Stages] Edit error:', e);
                this.formError = 'Network error. Please try again.';
            } finally {
                this.saving = false;
            }
        },

        // ── Add new stage ──
        async addStage() {
            if (!this.addForm.name.trim() || this.adding) return;
            this.adding   = true;
            this.addError = null;

            try {
                const res  = await fetch(`/admin/crm/pipelines/{{ $pipeline->id }}/stages`, {
                    method:  'POST',
                    headers: {
                        'Content-Type':     'application/json',
                        'X-CSRF-TOKEN':     document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(this.addForm),
                });

                const data = await res.json();

                if (res.status === 422) {
                    this.addError = data.errors?.name?.[0] ?? data.message ?? 'Validation error.';
                    return;
                }

                if (!data.success) {
                    this.addError = data.message;
                    return;
                }

                // Push to list
                this.stages.push(data.stage);

                // Reset form — keep color, reset rest
                this.addForm = { name: '', color: this.addForm.color, is_won: false, is_lost: false };

                this.pageSuccess = data.message;
                setTimeout(() => this.pageSuccess = null, 3000);

                this.$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); });

            } catch(e) {
                console.error('[Stages] Add error:', e);
                this.addError = 'Network error. Please try again.';
            } finally {
                this.adding = false;
            }
        },

        // ── Delete stage ──
        async deleteStage(id, name) {
            const confirmed = await Swal.fire({
                title:             'Delete Stage?',
                text:              `"${name}" will be deleted. Leads in this stage must be moved first.`,
                icon:              'warning',
                showCancelButton:  true,
                confirmButtonText: 'Yes, delete',
                cancelButtonText:  'Cancel',
                confirmButtonColor:'#ef4444',
            });

            if (!confirmed.isConfirmed) return;

            try {
                const res  = await fetch(`/admin/crm/pipelines/{{ $pipeline->id }}/stages/${id}`, {
                    method:  'DELETE',
                    headers: {
                        'X-CSRF-TOKEN':     document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const data = await res.json();
                if (data.success) {
                    this.stages      = this.stages.filter(s => s.id !== id);
                    this.pageSuccess = data.message;
                    setTimeout(() => this.pageSuccess = null, 3000);
                    this.$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); });
                } else {
                    Swal.fire('Cannot Delete', data.message, 'error');
                }
            } catch(e) {
                Swal.fire('Error', 'Network error. Please try again.', 'error');
            }
        },
    }
}
</script>
@endpush