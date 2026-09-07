@extends('layouts.admin')

@section('title', 'Import Leads')

@section('header-title')
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.crm.leads.index') }}"
            class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
        </a>
        <div>
            <h1 class="text-[17px] font-bold text-gray-800 leading-none">Import Leads</h1>
            <p class="text-xs text-gray-400 font-medium mt-0.5">Upload CSV or Excel file</p>
        </div>
    </div>
@endsection

@push('styles')
<style>
    [x-cloak] { display: none !important; }

    .upload-zone {
        border: 2px dashed #e2e8f0;
        border-radius: 16px;
        padding: 48px 24px;
        text-align: center;
        transition: border-color 150ms, background 150ms;
        cursor: pointer;
        background: #fff;
    }

    .upload-zone.dragover {
        border-color: var(--brand-600);
        background: var(--brand-50);
    }

    .upload-zone.has-file {
        border-color: var(--brand-600);
        background: #f0fdf4;
    }

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
        transition: border-color 150ms;
    }

    .field-input:focus { border-color: var(--brand-600); }

    select.field-input {
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2.5' stroke-linecap='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 12px center;
        padding-right: 36px;
        cursor: pointer;
    }

    .result-row {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        padding: 8px 0;
        border-bottom: 1px solid #f8fafc;
        font-size: 12px;
    }

    .result-row:last-child { border-bottom: none; }
</style>
@endpush

@section('content')

@php
    $allStagesJson = $pipelines->map(fn($p) => [
        'pipeline_id' => $p->id,
        'stages'      => $p->stages->map(fn($s) => ['id' => $s->id, 'name' => $s->name])->values(),
    ])->values()->toJson();
@endphp

<div class="pb-10" x-data="importPage()">

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 items-start">

        {{-- ═══════ LEFT — Upload Form ═══════ --}}
        <div class="lg:col-span-2 space-y-4">

            {{-- Step 1 — Download template ── --}}
            <div class="bg-white border border-gray-100 rounded-2xl p-5">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-7 h-7 rounded-full flex items-center justify-center text-[11px] font-black text-white flex-shrink-0"
                        style="background: var(--brand-600)">1</div>
                    <p class="text-[13px] font-bold text-gray-800">Download the sample template</p>
                </div>
                <p class="text-[12px] text-gray-500 mb-3 ml-10">
                    Use our template to ensure correct column format. Do not rename or remove columns.
                </p>
                <a href="{{ route('admin.crm.leads.import.template') }}" target="_blank"
                    class="ml-10 inline-flex items-center gap-2 px-4 py-2 rounded-xl text-[12px] font-bold border border-gray-200 text-gray-700 hover:bg-gray-50 transition-colors">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Download Template (.xlsx)
                </a>
            </div>

            {{-- Step 2 — Upload file ── --}}
            <div class="bg-white border border-gray-100 rounded-2xl p-5">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-7 h-7 rounded-full flex items-center justify-center text-[11px] font-black text-white flex-shrink-0"
                        style="background: var(--brand-600)">2</div>
                    <p class="text-[13px] font-bold text-gray-800">Upload your file</p>
                </div>

                {{-- Drop zone ── --}}
                <div class="upload-zone ml-10"
                    :class="file ? 'has-file' : (dragover ? 'dragover' : '')"
                    @dragover.prevent="dragover = true"
                    @dragleave.prevent="dragover = false"
                    @drop.prevent="handleDrop($event)"
                    @click="$refs.fileInput.click()">

                    <input type="file"
                        x-ref="fileInput"
                        accept=".xlsx,.xls,.csv"
                        class="hidden"
                        @change="handleFile($event)">

                    <template x-if="!file">
                        <div>
                            <svg class="w-10 h-10 mx-auto mb-3 text-gray-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                            <p class="text-[13px] font-bold text-gray-600 mb-1">Drop your file here or click to browse</p>
                            <p class="text-[11px] text-gray-400">Supports: .xlsx, .xls, .csv — Max 10MB</p>
                        </div>
                    </template>

                    <template x-if="file">
                        <div class="flex flex-col items-center">
                            <svg class="w-8 h-8 mb-2 text-green-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                            <p class="text-[13px] font-bold text-green-700" x-text="file.name"></p>
                            <p class="text-[11px] text-green-600" x-text="formatSize(file.size)"></p>
                            <button @click.stop="file = null"
                                class="mt-2 text-[11px] font-bold text-red-500 hover:text-red-700 transition-colors">
                                Remove file
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Step 3 — Pipeline / Stage ── --}}
            <div class="bg-white border border-gray-100 rounded-2xl p-5">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-7 h-7 rounded-full flex items-center justify-center text-[11px] font-black text-white flex-shrink-0"
                        style="background: var(--brand-600)">3</div>
                    <p class="text-[13px] font-bold text-gray-800">Choose Pipeline & Stage</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 ml-10">
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">
                            Pipeline
                        </label>
                        <select x-model="pipelineId" @change="loadStages()" class="field-input">
                            <option value="">Default Pipeline</option>
                            @foreach($pipelines as $pipeline)
                                <option value="{{ $pipeline->id }}">
                                    {{ $pipeline->name }}
                                    {{ $pipeline->is_default ? '(Default)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">
                            Starting Stage
                        </label>
                        <select x-model="stageId" class="field-input"
                            :disabled="availableStages.length === 0">
                            <option value="">First Stage (Auto)</option>
                            <template x-for="stage in availableStages" :key="stage.id">
                                <option :value="stage.id" x-text="stage.name"></option>
                            </template>
                        </select>
                    </div>

                    {{-- 🌟 Bulk Assign To --}}
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">
                            Assign To User
                        </label>
                        <select x-model="assignedTo" class="field-input">
                            <option value="">Leave Unassigned</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- Error display ── --}}
            <template x-if="importError">
                <div class="bg-red-50 border border-red-200 rounded-xl px-4 py-3 flex items-start gap-3">
                    <svg class="w-4 h-4 text-red-500 flex-shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <p class="text-sm font-semibold text-red-700" x-text="importError"></p>
                </div>
            </template>

            {{-- ── RESULT ── --}}
            <template x-if="result">
                <div class="bg-white border border-gray-100 rounded-2xl overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-50 flex items-center gap-3">
                        <svg class="w-5 h-5 text-green-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
                        <p class="text-[13px] font-bold text-gray-800">Import Complete</p>
                    </div>
                    <div class="p-5">

                        {{-- Stats ── --}}
                        <div class="grid grid-cols-3 gap-3 mb-5">
                            <div class="bg-green-50 rounded-xl p-3 text-center">
                                <p class="text-2xl font-black text-green-600" x-text="result.imported"></p>
                                <p class="text-[11px] font-bold text-green-700 mt-0.5">Imported</p>
                            </div>
                            <div class="bg-blue-50 rounded-xl p-3 text-center">
                                <p class="text-2xl font-black text-blue-600" x-text="result.duplicates"></p>
                                <p class="text-[11px] font-bold text-blue-700 mt-0.5">Duplicates</p>
                            </div>
                            <div class="bg-orange-50 rounded-xl p-3 text-center">
                                <p class="text-2xl font-black text-orange-600" x-text="result.skipped"></p>
                                <p class="text-[11px] font-bold text-orange-700 mt-0.5">Skipped</p>
                            </div>
                        </div>

                        {{-- Skipped rows detail ── --}}
                        <template x-if="result.skipped_rows && result.skipped_rows.length > 0">
                            <div>
                                <p class="text-[11px] font-black text-gray-400 uppercase tracking-wider mb-2">Skipped Rows</p>
                                <div class="bg-gray-50 rounded-xl px-4 py-2 max-h-40 overflow-y-auto">
                                    <template x-for="row in result.skipped_rows" :key="row">
                                        <div class="result-row">
                                            <svg class="w-3 h-3 text-orange-400 flex-shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>
                                            <span class="text-gray-600" x-text="row"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>

                        {{-- Errors ── --}}
                        <template x-if="result.errors && result.errors.length > 0">
                            <div class="mt-3">
                                <p class="text-[11px] font-black text-gray-400 uppercase tracking-wider mb-2">Errors</p>
                                <div class="bg-red-50 rounded-xl px-4 py-2 max-h-40 overflow-y-auto">
                                    <template x-for="err in result.errors" :key="err">
                                        <div class="result-row">
                                            <svg class="w-3 h-3 text-red-400 flex-shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                                            <span class="text-red-600" x-text="err"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <a href="{{ route('admin.crm.leads.index') }}"
                            class="mt-4 inline-flex items-center gap-2 px-4 py-2 rounded-xl text-[12px] font-bold text-white hover:opacity-90 transition-opacity"
                            style="background: var(--brand-600)">
                            View Imported Leads →
                        </a>
                    </div>
                </div>
            </template>

        </div>

        {{-- ═══════ RIGHT — Info + Action ═══════ --}}
        <div class="lg:col-span-1 space-y-4 lg:sticky lg:top-5">

            {{-- Import button ── --}}
            <div class="bg-white border border-gray-100 rounded-2xl p-5 space-y-3">
                <button @click="startImport()"
                    :disabled="importing || !file"
                    class="w-full flex items-center justify-center gap-2 py-3 rounded-xl text-[14px] font-bold text-white transition-opacity"
                    style="background: var(--brand-600)"
                    :class="(importing || !file) ? 'opacity-50 cursor-not-allowed' : 'hover:opacity-90'">
                    <svg x-show="importing" class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                    <svg x-show="!importing" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                    <span x-text="importing ? 'Importing...' : 'Start Import'"></span>
                </button>
                <a href="{{ route('admin.crm.leads.index') }}"
                    class="w-full flex items-center justify-center py-2.5 rounded-xl text-[13px] font-bold text-gray-600 border border-gray-200 hover:bg-gray-50 transition-colors">
                    Cancel
                </a>
            </div>

            {{-- Column reference ── --}}
            <div class="bg-white border border-gray-100 rounded-2xl overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-50">
                    <p class="text-[11px] font-black text-gray-400 uppercase tracking-widest">Column Reference</p>
                </div>
                <div class="p-4">
                    @foreach([
                        'name'      => ['Required', 'text-red-600'],
                        'phone'     => ['Duplicate check key', 'text-blue-600'],
                        'email'     => ['Optional', 'text-gray-400'],
                        'company'   => ['Optional', 'text-gray-400'],
                        'source'    => ['Must match exactly', 'text-orange-600'],
                        'priority'  => ['low/medium/high/hot', 'text-gray-400'],
                        'value'     => ['Numeric only', 'text-gray-400'],
                        'tags'      => ['Comma-separated', 'text-gray-400'],
                        'city/state/pin' => ['Optional', 'text-gray-400'],
                        'notes'     => ['Optional', 'text-gray-400'],
                    ] as $col => [$hint, $class])
                        <div class="flex items-center justify-between py-1.5 border-b border-gray-50 last:border-none">
                            <span class="text-[11px] font-mono font-bold text-gray-700">{{ $col }}</span>
                            <span class="text-[10px] font-semibold {{ $class }}">{{ $hint }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Tips ── --}}
            <div class="bg-blue-50 border border-blue-100 rounded-xl p-4">
                <p class="text-[11px] font-bold text-blue-800 mb-2 flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                    Import Tips
                </p>
                <ul class="text-[11px] text-blue-700 space-y-1 font-medium">
                    <li>• Duplicate phone numbers are automatically skipped</li>
                    <li>• Tags are created if they don't exist</li>
                    <li>• Source must match your configured sources exactly</li>
                    <li>• Failed rows are skipped — import never aborts entirely</li>
                    <li>• Max 10MB file size</li>
                </ul>
            </div>

        </div>

    </div>

</div>
@endsection

@push('scripts')
<script>
function importPage() {
    return {
        file:            null,
        dragover:        false,
        pipelineId:      '',
        stageId:         '',
        assignedTo:      '',
        availableStages: [],
        importing:       false,
        importError:     null,
        result:          null,

        allStages: {!! $allStagesJson !!},

        init() {},

        handleFile(event) {
            const f = event.target.files[0];
            if (f) this.file = f;
        },

        handleDrop(event) {
            this.dragover = false;
            const f = event.dataTransfer.files[0];
            if (f && ['xlsx','xls','csv'].some(ext => f.name.endsWith('.' + ext))) {
                this.file = f;
            } else {
                this.importError = 'Only .xlsx, .xls, or .csv files are accepted.';
            }
        },

        formatSize(bytes) {
            if (bytes < 1024)       return bytes + ' B';
            if (bytes < 1048576)    return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / 1048576).toFixed(1) + ' MB';
        },

        loadStages() {
            const pipelineId   = parseInt(this.pipelineId);
            const pipelineData = this.allStages.find(p => p.pipeline_id === pipelineId);
            this.availableStages = pipelineData?.stages ?? [];
            this.stageId         = '';
        },

        async startImport() {
            if (!this.file || this.importing) return;

            this.importing   = true;
            this.importError = null;
            this.result      = null;

            const formData = new FormData();
            formData.append('file', this.file);
            if (this.pipelineId) formData.append('pipeline_id', this.pipelineId);
            if (this.stageId)    formData.append('stage_id',    this.stageId);
            if (this.assignedTo) formData.append('assigned_to', this.assignedTo);

            try {
                const res  = await fetch('{{ route("admin.crm.leads.import.store") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN':     document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: formData,
                });

                const data = await res.json();

                if (res.status === 422) {
                    this.importError = data.message + (data.errors ? ': ' + data.errors.join('; ') : '');
                    return;
                }

                if (!data.success) {
                    this.importError = data.message;
                    return;
                }

                this.result = data.result;
                this.file   = null;

            } catch(e) {
                console.error('[Import] Error:', e);
                this.importError = 'Network error. Please try again.';
            } finally {
                this.importing = false;
            }
        },
    }
}
</script>
@endpush