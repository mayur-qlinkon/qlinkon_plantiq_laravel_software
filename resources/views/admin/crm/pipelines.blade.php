@extends('layouts.admin')

@section('title', 'CRM Pipelines')

@section('header-title')
    <div>
        <h1 class="text-sm font-bold text-gray-500 uppercase tracking-widest">CRM Pipelines</h1>        
    </div>
@endsection

@push('styles')
<style>
    [x-cloak] { display: none !important; }

    .pipeline-card {
        background: #fff;
        border: 1.5px solid #f1f5f9;
        border-radius: 16px;
        overflow: hidden;
        transition: box-shadow 150ms ease, border-color 150ms ease;
    }

    .pipeline-card:hover {
        border-color: #e2e8f0;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
    }

    .stage-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        white-space: nowrap;
    }

    .stage-dot { width: 6px; height: 6px; border-radius: 50%; }

    .field-input {
        width: 100%;
        border: 1.5px solid #e5e7eb;
        border-radius: 10px;
        padding: 9px 13px;
        font-size: 13px;
        color: #1f2937;
        outline: none;
        font-family: inherit;
        background: #fff;
        transition: border-color 150ms ease;
    }

    .field-input:focus { border-color: var(--brand-600); }

    .toggle-wrap {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 14px;
        background: #f8fafc;
        border: 1.5px solid #f1f5f9;
        border-radius: 10px;
    }

    /* ── Toggle switch ── */
    .toggle-track {
        position: relative;
        width: 40px; height: 22px;
        background: #d1d5db;
        border-radius: 11px;
        cursor: pointer;
        transition: background 150ms ease;
        flex-shrink: 0;
    }

    .toggle-track.on { background: #22c55e; }

    .toggle-thumb {
        position: absolute;
        top: 3px; left: 3px;
        width: 16px; height: 16px;
        border-radius: 50%;
        background: #fff;
        transition: transform 150ms ease;
        box-shadow: 0 1px 3px rgba(0,0,0,0.2);
    }

    .toggle-track.on .toggle-thumb { transform: translateX(18px); }
</style>
@endpush

@section('content')

<div class="pb-10" x-data="pipelinesPage()">

    {{-- ════════ HEADER BAR ════════ --}}
    <div class="flex items-center justify-between mb-5 flex-wrap gap-3">
        <div class="flex items-center gap-3 flex-wrap">
            @if(has_permission('crm_sources.view'))
            <a href="{{ route('admin.crm.sources.index') }}"
                class="inline-flex items-center gap-2.5 bg-white border border-gray-200 shadow-sm text-sm font-bold text-gray-700 hover:text-gray-900 px-4 py-2.5 rounded-xl hover:bg-gray-50 hover:border-gray-300 transition-all duration-200 group">
                <div class="flex items-center justify-center bg-blue-50 rounded-lg p-1.5 group-hover:bg-blue-100 transition-colors">
                    <i data-lucide="radio-tower" class="w-4 h-4 text-blue-600"></i>
                </div>
                Lead Sources
            </a>
            @endif
            @if(has_permission('crm_tags.view'))
            <a href="{{ route('admin.crm.tags.index') }}"
                class="inline-flex items-center gap-2.5 bg-white border border-gray-200 shadow-sm text-sm font-bold text-gray-700 hover:text-gray-900 px-4 py-2.5 rounded-xl hover:bg-gray-50 hover:border-gray-300 transition-all duration-200 group">
                <div class="flex items-center justify-center bg-purple-50 rounded-lg p-1.5 group-hover:bg-purple-100 transition-colors">
                    <i data-lucide="tag" class="w-4 h-4 text-purple-600"></i>
                </div>
                Tags
            </a>
            @endif
        </div>
        @if(has_permission('crm_pipelines.create'))
        <button @click="openCreate()"
            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-bold text-white hover:opacity-90 transition-opacity"
            style="background: var(--brand-600)">
            <i data-lucide="plus" class="w-4 h-4"></i>
            New Pipeline
        </button>
        @endif
    </div>

    {{-- ════════ ERROR BANNER ════════ --}}
    <template x-if="pageError">
        <div class="mb-4 bg-red-50 border border-red-200 rounded-xl px-4 py-3 flex items-center gap-3">
            <i data-lucide="alert-circle" class="w-4 h-4 text-red-500 flex-shrink-0"></i>
            <p class="text-sm font-semibold text-red-700" x-text="pageError"></p>
        </div>
    </template>

    {{-- ════════ SUCCESS BANNER ════════ --}}
    <template x-if="pageSuccess">
        <div class="mb-4 bg-green-50 border border-green-200 rounded-xl px-4 py-3 flex items-center gap-3">
            <i data-lucide="check-circle" class="w-4 h-4 text-green-600 flex-shrink-0"></i>
            <p class="text-sm font-semibold text-green-800" x-text="pageSuccess"></p>
        </div>
    </template>

    {{-- ════════ EMPTY STATE ════════ --}}
    <template x-if="pipelines.length === 0 && !loading">
        <div class="bg-white border border-gray-100 rounded-2xl flex flex-col items-center justify-center py-20 text-center">
            <div class="w-14 h-14 bg-gray-100 rounded-2xl flex items-center justify-center mb-3">
                <i data-lucide="git-branch" class="w-7 h-7 text-gray-300"></i>
            </div>
            <p class="font-semibold text-gray-500 mb-1">No pipelines yet</p>
            <p class="text-sm text-gray-400 mb-4 max-w-xs">
                Create your first pipeline to start tracking leads through stages
            </p>
            <button @click="openCreate()"
                class="text-sm font-bold px-4 py-2 rounded-xl text-white"
                style="background: var(--brand-600)">
                Create Pipeline
            </button>
        </div>
    </template>

    {{-- ════════ LOADING ════════ --}}
    <template x-if="loading">
        <div class="space-y-4">
            <template x-for="i in 2">
                <div class="pipeline-card animate-pulse">
                    <div class="px-5 py-4 flex items-center gap-3 border-b border-gray-50">
                        <div class="w-9 h-9 rounded-xl bg-gray-100"></div>
                        <div class="flex-1">
                            <div class="h-4 bg-gray-100 rounded w-40 mb-1.5"></div>
                            <div class="h-3 bg-gray-50 rounded w-24"></div>
                        </div>
                    </div>
                    <div class="px-5 py-3 flex gap-2">
                        <div class="h-6 bg-gray-100 rounded-full w-20"></div>
                        <div class="h-6 bg-gray-100 rounded-full w-24"></div>
                        <div class="h-6 bg-gray-100 rounded-full w-16"></div>
                    </div>
                </div>
            </template>
        </div>
    </template>

    {{-- ════════ PIPELINE CARDS ════════ --}}
    <div x-show="!loading && pipelines.length > 0" class="space-y-4">
        <template x-for="pipeline in pipelines" :key="pipeline.id">
            <div class="pipeline-card">

                {{-- Card header ── --}}
                <div class="px-5 py-4 flex items-center justify-between gap-4 flex-wrap border-b border-gray-50">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0"
                            style="background: var(--brand-50)">
                            <i data-lucide="git-branch" class="w-4 h-4" style="color: var(--brand-600)"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-[15px] font-bold text-gray-900" x-text="pipeline.name"></span>
                                <span x-show="pipeline.is_default"
                                    class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-blue-50 text-blue-600 uppercase tracking-wide">
                                    Default
                                </span>
                                <span x-show="!pipeline.is_active"
                                    class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-gray-100 text-gray-500 uppercase tracking-wide">
                                    Inactive
                                </span>
                            </div>
                            <p x-show="pipeline.description"
                                x-text="pipeline.description"
                                class="text-[12px] text-gray-400 mt-0.5 truncate max-w-xs">
                            </p>
                        </div>
                    </div>

                    {{-- Actions ── --}}
                    <div class="flex items-center gap-2 flex-wrap flex-shrink-0">
                        <span class="text-[12px] text-gray-400 font-medium"
                            x-text="(pipeline.leads_count ?? 0) + ' lead' + ((pipeline.leads_count ?? 0) !== 1 ? 's' : '')">
                        </span>

                        <button x-show="!pipeline.is_default"
                            @click="setDefault(pipeline.id)"
                            class="text-[12px] font-semibold text-gray-500 hover:text-blue-600 px-3 py-1.5 rounded-lg hover:bg-blue-50 transition-colors">
                            Set Default
                        </button>

                        @if(has_permission('crm_stages.view'))
                        <a :href="`/admin/crm/pipelines/${pipeline.id}/stages`"
                            class="inline-flex items-center gap-1.5 text-[12px] font-bold px-3 py-1.5 rounded-lg transition-opacity hover:opacity-90 text-white"
                            style="background: var(--brand-600)">
                            <i data-lucide="layers" class="w-3.5 h-3.5"></i>
                            Manage Stages
                        </a>
                        @endif

                        @if(has_permission('crm_pipelines.update'))
                        <button @click="openEdit(pipeline)"
                            class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition-colors"
                            title="Edit">
                            <i data-lucide="edit" class="w-4 h-4"></i>
                        </button>
                        @endif

                        @if(has_permission('crm_pipelines.delete'))
                        <button @click="deletePipeline(pipeline.id, pipeline.name)"
                            class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors"
                            title="Delete">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                        @endif
                    </div>
                </div>

                {{-- Stages preview ── --}}
                <div class="px-5 py-3 flex items-center gap-2 flex-wrap">
                    <template x-if="!pipeline.stages || pipeline.stages.length === 0">
                        <div class="text-[12px] text-gray-400 italic">
                            No stages yet —
                            <a :href="`/admin/crm/pipelines/${pipeline.id}/stages`"
                                class="font-semibold hover:underline"
                                style="color: var(--brand-600)">add stages</a>
                        </div>
                    </template>
                    <template x-if="pipeline.stages && pipeline.stages.length > 0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <template x-for="(stage, i) in pipeline.stages" :key="stage.id">
                                <div class="flex items-center gap-2">
                                    <span class="stage-pill"
                                        :style="`background: ${stage.color}18; color: ${stage.color}`">
                                        <span class="stage-dot" :style="`background: ${stage.color}`"></span>
                                        <span x-text="stage.name"></span>
                                        <span x-show="stage.is_won" class="text-xs">🏆</span>
                                        <span x-show="stage.is_lost" class="text-xs">✕</span>
                                    </span>
                                    <i x-show="i < pipeline.stages.length - 1"
                                        data-lucide="chevron-right"
                                        class="w-3 h-3 text-gray-300 flex-shrink-0"></i>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>

            </div>
        </template>
    </div>

    {{-- ════════════════════════════════════════
         CREATE / EDIT MODAL
    ════════════════════════════════════════ --}}
    <div x-show="modal" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        style="background: rgba(0,0,0,0.45)">

        <div @click.away="closeModal()"
            class="bg-white rounded-2xl shadow-2xl w-full max-w-lg"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95">

            {{-- Modal header ── --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <div>
                    <h3 class="text-base font-bold text-gray-900"
                        x-text="editingId ? 'Edit Pipeline' : 'New Pipeline'"></h3>
                    <p class="text-xs text-gray-400 mt-0.5"
                        x-text="editingId ? 'Update pipeline details' : 'Create a new CRM pipeline'"></p>
                </div>
                <button @click="closeModal()"
                    class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            {{-- Modal body ── --}}
            <div class="px-6 py-5 space-y-4">

                {{-- Server error ── --}}
                <template x-if="formError">
                    <div class="bg-red-50 border border-red-200 rounded-xl px-4 py-3 flex items-start gap-2">
                        <i data-lucide="alert-circle" class="w-4 h-4 text-red-500 flex-shrink-0 mt-0.5"></i>
                        <p class="text-[13px] font-semibold text-red-700" x-text="formError"></p>
                    </div>
                </template>

                {{-- Name ── --}}
                <div>
                    <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">
                        Pipeline Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                        x-model="form.name"
                        placeholder="e.g. Sales Pipeline, Support Pipeline"
                        class="field-input"
                        :class="formErrors.name ? 'border-red-400' : ''"
                        @keydown.enter.prevent="save()">
                    <template x-if="formErrors.name">
                        <p class="text-[11px] text-red-500 font-semibold mt-1" x-text="formErrors.name[0]"></p>
                    </template>
                </div>

                {{-- Description ── --}}
                <div>
                    <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">
                        Description
                        <span class="text-gray-400 font-normal normal-case">(optional)</span>
                    </label>
                    <textarea x-model="form.description" rows="2"
                        placeholder="What is this pipeline used for?"
                        class="field-input resize-none"></textarea>
                </div>

                {{-- Toggles ── --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">

                    <div class="toggle-wrap">
                        <div>
                            <p class="text-sm font-bold text-gray-700">Default Pipeline</p>
                            <p class="text-[11px] text-gray-400">Auto-assigned to new leads</p>
                        </div>
                        <div class="toggle-track"
                            :class="form.is_default ? 'on' : ''"
                            @click="form.is_default = !form.is_default">
                            <div class="toggle-thumb"></div>
                        </div>
                    </div>

                    <div class="toggle-wrap">
                        <div>
                            <p class="text-sm font-bold text-gray-700">Active</p>
                            <p class="text-[11px] text-gray-400">Show in lead assignment</p>
                        </div>
                        <div class="toggle-track"
                            :class="form.is_active ? 'on' : ''"
                            @click="form.is_active = !form.is_active">
                            <div class="toggle-thumb"></div>
                        </div>
                    </div>

                </div>

                {{-- Info note for new pipeline ── --}}
                <template x-if="!editingId">
                    <div class="bg-blue-50 border border-blue-100 rounded-xl px-4 py-3 flex items-start gap-2">
                        <i data-lucide="info" class="w-4 h-4 text-blue-500 flex-shrink-0 mt-0.5"></i>
                        <p class="text-[12px] text-blue-700 leading-relaxed">
                            After creating, you'll be taken to the stage manager to add stages like
                            <strong>New Lead → Contacted → Won → Lost</strong>.
                        </p>
                    </div>
                </template>

            </div>

            {{-- Modal footer ── --}}
            <div class="px-6 py-4 border-t border-gray-100 flex items-center gap-3">
                <button @click="save()"
                    :disabled="saving || !form.name.trim()"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold text-white transition-opacity"
                    style="background: var(--brand-600)"
                    :class="(saving || !form.name.trim()) ? 'opacity-50 cursor-not-allowed' : 'hover:opacity-90'">
                    <i data-lucide="loader-2" x-show="saving" class="w-4 h-4 animate-spin"></i>
                    <i data-lucide="save" x-show="!saving" class="w-4 h-4"></i>
                    <span x-text="saving ? 'Saving...' : (editingId ? 'Save Changes' : 'Create Pipeline')"></span>
                </button>
                <button @click="closeModal()"
                    class="px-5 py-2.5 rounded-xl text-sm font-bold text-gray-600 border border-gray-200 hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
            </div>

        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
function pipelinesPage() {
    return {
        // ── State ──
        pipelines:   @json($pipelines),
        loading:     false,
        pageError:   null,
        pageSuccess: null,

        // ── Modal ──
        modal:      false,
        editingId:  null,
        saving:     false,
        formError:  null,
        formErrors: {},

        form: {
            name:        '',
            description: '',
            is_default:  false,
            is_active:   true,
        },

        // ── Init ──
        init() {
            // Re-run Lucide after Alpine renders dynamic icons in x-for
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        // ── Open create modal ──
        openCreate() {
            this.editingId  = null;
            this.formError  = null;
            this.formErrors = {};
            this.form = { name: '', description: '', is_default: false, is_active: true };
            this.modal = true;
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        // ── Open edit modal ──
        openEdit(pipeline) {
            this.editingId  = pipeline.id;
            this.formError  = null;
            this.formErrors = {};
            this.form = {
                name:        pipeline.name,
                description: pipeline.description ?? '',
                is_default:  !!pipeline.is_default,
                is_active:   !!pipeline.is_active,
            };
            this.modal = true;
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        closeModal() {
            this.modal    = false;
            this.saving   = false;
            this.formError  = null;
            this.formErrors = {};
        },

        // ── Save (create or update) ──
        async save() {
            if (!this.form.name.trim() || this.saving) return;

            this.saving     = true;
            this.formError  = null;
            this.formErrors = {};

            const url    = this.editingId
                ? `/admin/crm/pipelines/${this.editingId}`
                : '/admin/crm/pipelines';

            const method = this.editingId ? 'PUT' : 'POST';

            try {
                const res  = await fetch(url, {
                    method,
                    headers: {
                        'Content-Type':     'application/json',
                        'X-CSRF-TOKEN':     document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(this.form),
                });

                const data = await res.json();

                if (res.status === 422) {
                    // Laravel validation errors
                    this.formErrors = data.errors ?? {};
                    this.formError  = data.message ?? 'Please fix the errors below.';
                    return;
                }

                if (!data.success) {
                    this.formError = data.message || 'Something went wrong.';
                    return;
                }

                // ── Success ──
                this.closeModal();

                if (!this.editingId && data.redirect_stages) {
                    // New pipeline → go to stages manager immediately
                    window.location.href = data.redirect_stages;
                    return;
                }

                // Update or insert in local array
                if (this.editingId) {
                    const idx = this.pipelines.findIndex(p => p.id === this.editingId);
                    if (idx !== -1) this.pipelines[idx] = data.pipeline;
                } else {
                    this.pipelines.push(data.pipeline);
                }

                this.pageSuccess = data.message;
                setTimeout(() => this.pageSuccess = null, 3000);

                this.$nextTick(() => {
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                });

            } catch (e) {
                console.error('[Pipeline] Save error:', e);
                this.formError = 'Network error. Please try again.';
            } finally {
                this.saving = false;
            }
        },

        // ── Set default ──
        async setDefault(pipelineId) {
            try {
                const res  = await fetch(`/admin/crm/pipelines/${pipelineId}/default`, {
                    method:  'POST',
                    headers: {
                        'X-CSRF-TOKEN':     document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const data = await res.json();
                if (data.success) {
                    // Update local state
                    this.pipelines = this.pipelines.map(p => ({
                        ...p,
                        is_default: p.id === pipelineId,
                    }));
                    this.pageSuccess = data.message;
                    setTimeout(() => this.pageSuccess = null, 3000);
                    this.$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); });
                } else {
                    this.pageError = data.message;
                }
            } catch(e) {
                this.pageError = 'Network error. Please try again.';
            }
        },

        // ── Delete pipeline ──
        async deletePipeline(id, name) {
            const confirmed = await Swal.fire({
                title:             'Delete Pipeline?',
                text:              `"${name}" and all its stages will be deleted permanently.`,
                icon:              'warning',
                showCancelButton:  true,
                confirmButtonText: 'Yes, delete',
                cancelButtonText:  'Cancel',
                confirmButtonColor:'#ef4444',
            });

            if (!confirmed.isConfirmed) return;

            try {
                const res  = await fetch(`/admin/crm/pipelines/${id}`, {
                    method:  'DELETE',
                    headers: {
                        'X-CSRF-TOKEN':     document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const data = await res.json();
                if (data.success) {
                    this.pipelines    = this.pipelines.filter(p => p.id !== id);
                    this.pageSuccess  = data.message;
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