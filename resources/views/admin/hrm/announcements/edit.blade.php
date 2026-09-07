@extends('layouts.admin')

@section('title', 'Edit Announcement')

@section('header-title')
    <div>
        <h1 class="text-sm font-bold text-gray-500 uppercase tracking-widest">HRM / Announcements</h1>
        <p class="text-xs text-gray-400 font-medium mt-0.5">Update an existing company announcement.</p>
    </div>
@endsection

@push('styles')
<style>
    [x-cloak] { display: none !important; }
    .section-card { background: #fff; border: 1.5px solid #f1f5f9; border-radius: 16px; padding: 24px; box-shadow: 0 1px 2px rgba(0,0,0,0.01); }
    .field-label { display: block; font-size: 11px; font-weight: 800; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px; }
    .field-input { width: 100%; border: 1.5px solid #e5e7eb; border-radius: 10px; padding: 10px 14px; font-size: 13px; color: #1f2937; outline: none; transition: all 150ms ease; background: #fff; }
    .field-input:focus { border-color: var(--brand-500); box-shadow: 0 0 0 3px color-mix(in srgb, var(--brand-500) 15%, transparent); }
    .field-input.has-error { border-color: #ef4444; }
    .field-error { font-size: 11px; font-weight: 600; color: #ef4444; margin-top: 4px; display: flex; align-items: center; gap: 4px; }
</style>
@endpush

@section('content')
    @php
        // Prepare associative collections for component strict type-matching in edit mode
        $announcementTypes = [];
        foreach($typeOptions ?? [] as $val => $lbl) {
            $announcementTypes[(string) $val] = $lbl;
        }

        $priorityLevels = [];
        foreach($priorityOptions ?? [] as $val => $lbl) {
            $priorityLevels[(string) $val] = $lbl;
        }

        $audienceLabels = [];
        foreach(\App\Models\Hrm\Announcement::TARGET_LABELS as $val => $lbl) {
            $audienceLabels[(string) $val] = $lbl;
        }
    @endphp
<div class="pb-12" x-data="announcementForm()">

    <form action="{{ route('admin.hrm.announcements.update', $announcement->id) }}" method="POST" enctype="multipart/form-data" @submit="isSubmitting = true">
        @csrf
        @method('PUT')

        {{-- Top Action Bar --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.hrm.announcements.index') }}"
                    class="w-10 h-10 rounded-xl bg-white border border-gray-200 flex items-center justify-center text-gray-500 hover:bg-gray-50 hover:text-gray-700 transition-colors shadow-sm">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                </a>
                <h2 class="text-lg font-black text-gray-800 tracking-tight">Edit Announcement</h2>
            </div>
            
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.hrm.announcements.index') }}" 
                    class="px-5 py-2.5 rounded-xl border border-gray-200 text-gray-600 text-sm font-bold hover:bg-gray-50 transition-colors bg-white shadow-sm">
                    Cancel
                </a>
                <button type="submit" :disabled="isSubmitting"
                    class="bg-brand-500 hover:bg-brand-600 text-white px-6 py-2.5 rounded-xl text-sm font-bold shadow-md transition-all active:scale-95 flex items-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed">
                    <span x-show="!isSubmitting"><i data-lucide="save" class="w-4 h-4 inline-block mr-1 pb-0.5"></i> Update Announcement</span>
                    <span x-show="isSubmitting" x-cloak class="flex items-center gap-2">
                        <i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> Saving...
                    </span>
                </button>
            </div>
        </div>

        {{-- Main Layout Grid --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 lg:gap-8">

            {{-- 🟢 LEFT PANE: Content & Media (Spans 2 columns on PC) --}}
            <div class="lg:col-span-2 space-y-6">
                
                {{-- Content Card --}}
                <div class="section-card">
                    <div class="mb-5 pb-3 border-b border-gray-100 flex items-center gap-2 text-gray-800">
                        <i data-lucide="file-text" class="w-4 h-4 text-brand-500"></i>
                        <h3 class="text-[13px] font-black uppercase tracking-wider">Message Content</h3>
                    </div>

                    <div class="space-y-5">
                        <div>
                            <label for="title" class="field-label">Announcement Title <span class="text-red-500">*</span></label>
                            <input type="text" id="title" name="title" value="{{ old('title', $announcement->title) }}" required
                                placeholder="E.g., Q3 Company Townhall Meeting"
                                class="field-input @error('title') has-error @enderror">
                            @error('title')
                                <p class="field-error"><i data-lucide="alert-circle" class="w-3 h-3"></i> {{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="content" class="field-label">Detailed Message <span class="text-red-500">*</span></label>
                            <textarea id="content" name="content" rows="10" required
                                placeholder="Write the full announcement details here..."
                                class="field-input resize-y min-h-[200px] @error('content') has-error @enderror">{{ old('content', $announcement->content) }}</textarea>
                            @error('content')
                                <p class="field-error"><i data-lucide="alert-circle" class="w-3 h-3"></i> {{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Attachment Card --}}
                <div class="section-card">
                    <div class="mb-5 pb-3 border-b border-gray-100 flex items-center gap-2 text-gray-800">
                        <i data-lucide="paperclip" class="w-4 h-4 text-blue-500"></i>
                        <h3 class="text-[13px] font-black uppercase tracking-wider">Media & Attachments</h3>
                    </div>

                    <div>
                        <label class="field-label">Update Attached File (Optional)</label>
                        
                        @if($announcement->attachment)
                            <div class="mb-4 p-3 bg-blue-50 border border-blue-100 rounded-lg flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center">
                                        <i data-lucide="file" class="w-4 h-4"></i>
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold text-gray-800">Current File</p>
                                        <p class="text-[11px] text-gray-500">{{ $announcement->attachment_name ?? 'document.pdf' }}</p>
                                    </div>
                                </div>
                                <a href="{{ $announcement->attachment_url }}" target="_blank" class="text-xs font-bold text-blue-600 hover:text-blue-800 bg-white px-3 py-1.5 rounded-md border border-blue-200">View File</a>
                            </div>
                        @endif

                        <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-xl hover:border-brand-500 hover:bg-brand-50/50 transition-colors relative group">
                            <div class="space-y-1 text-center">
                                <i data-lucide="upload-cloud" class="mx-auto h-10 w-10 text-gray-400 group-hover:text-brand-500 transition-colors"></i>
                                <div class="flex text-sm text-gray-600 justify-center">
                                    <label for="attachment" class="relative cursor-pointer rounded-md font-bold text-brand-600 hover:text-brand-500 focus-within:outline-none">
                                        <span>{{ $announcement->attachment ? 'Replace file' : 'Upload a file' }}</span>
                                        <input id="attachment" name="attachment" type="file" class="sr-only" @change="fileName = $event.target.files[0]?.name">
                                    </label>
                                    <p class="pl-1">or drag and drop</p>
                                </div>
                                <p class="text-[11px] text-gray-500 font-medium">PDF, DOCX, PNG, JPG up to 10MB</p>
                            </div>
                        </div>
                        <p x-show="fileName" x-cloak class="mt-2 text-sm font-bold text-green-600 flex items-center gap-1.5">
                            <i data-lucide="check-circle-2" class="w-4 h-4"></i> <span x-text="fileName"></span> selected.
                        </p>
                        @error('attachment')
                            <p class="field-error"><i data-lucide="alert-circle" class="w-3 h-3"></i> {{ $message }}</p>
                        @enderror
                    </div>
                </div>

            </div>

            {{-- 🟢 RIGHT PANE: Settings & Toggles (Spans 1 column on PC) --}}
            <div class="space-y-6">
                
               {{-- Classification Card --}}
                <div class="section-card">
                    <div class="mb-5 pb-3 border-b border-gray-100 flex items-center gap-2 text-gray-800">
                        <i data-lucide="settings-2" class="w-4 h-4 text-gray-500"></i>
                        <h3 class="text-[13px] font-black uppercase tracking-wider">Classification</h3>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="field-label">Type</label>
                            <x-custom-select
                                name="type"
                                :options="$announcementTypes"
                                :selected="old('type', (string) $announcement->type)"
                            />
                            @error('type')
                                <p class="field-error"><i data-lucide="alert-circle" class="w-3 h-3"></i> {{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="field-label">Priority Level</label>
                            <x-custom-select
                                name="priority"
                                :options="$priorityLevels"
                                :selected="old('priority', (string) $announcement->priority)"
                            />
                            @error('priority')
                                <p class="field-error"><i data-lucide="alert-circle" class="w-3 h-3"></i> {{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="field-label">Target Audience</label>
                            <div @change="targetAudience = $event.target.value">
                                <x-custom-select
                                    name="target_audience"
                                    :options="$audienceLabels"
                                    :selected="old('target_audience', (string) $announcement->target_audience)"
                                />
                            </div>
                            @error('target_audience')
                                <p class="field-error"><i data-lucide="alert-circle" class="w-3 h-3"></i> {{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Dynamic Multi-Select (shows when audience != 'all') --}}
                        <div x-show="targetAudience !== 'all'" x-cloak x-transition.opacity>
                            <label class="field-label">
                                Select <span x-text="audienceLabel" class="capitalize"></span> <span class="text-red-500">*</span>
                            </label>

                            <div class="relative" @click.outside="dropdownOpen = false">
                                {{-- Selected chips + search input --}}
                                <div class="field-input !p-2 flex flex-wrap gap-1.5 min-h-[42px] cursor-text" @click="dropdownOpen = true; $nextTick(() => $refs.targetSearch.focus())">
                                    <template x-for="id in selectedIds" :key="id">
                                        <span class="inline-flex items-center gap-1 bg-brand-50 text-brand-700 text-[11px] font-bold px-2 py-1 rounded-md">
                                            <span x-text="getOptionLabel(id)"></span>
                                            <button type="button" @click.stop="removeId(id)" class="hover:text-red-600 transition-colors">&times;</button>
                                        </span>
                                    </template>
                                    <input type="text" x-ref="targetSearch" x-model="searchQuery"
                                        placeholder="Type to search..." autocomplete="off"
                                        class="flex-1 min-w-[100px] border-0 outline-none text-[12px] p-1 bg-transparent"
                                        @focus="dropdownOpen = true" @keydown.backspace="searchQuery === '' && selectedIds.length && selectedIds.pop()">
                                </div>

                                {{-- Hidden inputs for form submission --}}
                                <template x-for="id in selectedIds" :key="'input-'+id">
                                    <input type="hidden" name="target_ids[]" :value="id">
                                </template>

                                {{-- Dropdown list --}}
                                <div x-show="dropdownOpen" x-transition.opacity.duration.150ms
                                     class="absolute z-50 left-0 right-0 mt-1 bg-white border border-gray-200 rounded-xl shadow-lg max-h-52 overflow-y-auto">
                                    <template x-for="opt in filteredOptions" :key="opt.id">
                                        <button type="button"
                                            @click="toggleId(opt.id)"
                                            class="w-full text-left px-3 py-2 text-[12px] flex items-center justify-between hover:bg-gray-50 transition-colors border-b border-gray-50 last:border-0"
                                            :class="selectedIds.includes(opt.id) ? 'bg-brand-50/50 font-bold text-brand-700' : 'text-gray-700'">
                                            <span>
                                                <span x-text="opt.label"></span>
                                                <span x-show="opt.sub" x-text="opt.sub" class="text-gray-400 ml-1"></span>
                                            </span>
                                            <svg x-show="selectedIds.includes(opt.id)" width="14" height="14" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="3" stroke-linecap="round" class="text-brand-600 flex-shrink-0">
                                                <polyline points="20 6 9 17 4 12"/>
                                            </svg>
                                        </button>
                                    </template>
                                    <div x-show="filteredOptions.length === 0" class="px-3 py-4 text-center text-[11px] text-gray-400 font-medium">
                                        No matches found
                                    </div>
                                </div>
                            </div>

                            <p x-show="selectedIds.length > 0" class="mt-1.5 text-[10px] text-gray-400 font-medium">
                                <span x-text="selectedIds.length"></span> selected
                            </p>
                            @error('target_ids')
                                <p class="field-error"><i data-lucide="alert-circle" class="w-3 h-3"></i> {{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Scheduling Card --}}
                <div class="section-card">
                    <div class="mb-5 pb-3 border-b border-gray-100 flex items-center gap-2 text-gray-800">
                        <i data-lucide="calendar-clock" class="w-4 h-4 text-purple-500"></i>
                        <h3 class="text-[13px] font-black uppercase tracking-wider">Scheduling</h3>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label for="publish_at" class="field-label">Publish Date & Time (Optional)</label>
                            <input type="datetime-local" id="publish_at" name="publish_at" 
                                value="{{ old('publish_at', $announcement->publish_at ? $announcement->publish_at->format('Y-m-d\TH:i') : '') }}"
                                class="field-input @error('publish_at') has-error @enderror">
                            <p class="text-[10px] text-gray-400 mt-1 font-medium">Leave blank to keep as Draft.</p>
                            @error('publish_at')
                                <p class="field-error"><i data-lucide="alert-circle" class="w-3 h-3"></i> {{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="expire_at" class="field-label">Expiry Date & Time (Optional)</label>
                            <input type="datetime-local" id="expire_at" name="expire_at" 
                                value="{{ old('expire_at', $announcement->expire_at ? $announcement->expire_at->format('Y-m-d\TH:i') : '') }}"
                                class="field-input @error('expire_at') has-error @enderror">
                            <p class="text-[10px] text-gray-400 mt-1 font-medium">When should this stop showing?</p>
                            @error('expire_at')
                                <p class="field-error"><i data-lucide="alert-circle" class="w-3 h-3"></i> {{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Preferences Card --}}
                <div class="section-card">
                    <div class="mb-5 pb-3 border-b border-gray-100 flex items-center gap-2 text-gray-800">
                        <i data-lucide="sliders" class="w-4 h-4 text-orange-500"></i>
                        <h3 class="text-[13px] font-black uppercase tracking-wider">Preferences</h3>
                    </div>

                    <div class="space-y-5">
                        {{-- Pin Toggle --}}
                        <label class="relative flex items-start cursor-pointer group">
                            <div class="flex items-center h-5">
                                <input type="checkbox" name="is_pinned" value="1" {{ old('is_pinned', $announcement->is_pinned) ? 'checked' : '' }} class="sr-only peer">
                                <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-yellow-400 group-hover:bg-gray-300 peer-checked:group-hover:bg-yellow-500 transition-colors"></div>
                            </div>
                            <div class="ml-3 text-sm">
                                <span class="font-bold text-gray-800">Pin to Dashboard</span>
                                <p class="text-[11px] text-gray-500 font-medium leading-tight mt-0.5">Keeps this announcement at the top of employee feeds.</p>
                            </div>
                        </label>

                        {{-- Acknowledgement Toggle --}}
                        <label class="relative flex items-start cursor-pointer group">
                            <div class="flex items-center h-5">
                                <input type="checkbox" name="requires_acknowledgement" value="1" {{ old('requires_acknowledgement', $announcement->requires_acknowledgement) ? 'checked' : '' }} class="sr-only peer">
                                <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-green-500 group-hover:bg-gray-300 peer-checked:group-hover:bg-green-600 transition-colors"></div>
                            </div>
                            <div class="ml-3 text-sm">
                                <span class="font-bold text-gray-800">Require Acknowledgement</span>
                                <p class="text-[11px] text-gray-500 font-medium leading-tight mt-0.5">Forces employees to click "I Accept" after reading.</p>
                            </div>
                        </label>
                    </div>
                </div>

            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
@php
    // Format the data cleanly in PHP first so Blade's regex doesn't crash
    $deptOptions = clone $targetOptions['departments']->map(fn($name, $id) => ['id' => $id, 'label' => $name, 'sub' => ''])->values();
    $storeOptions = clone $targetOptions['stores']->map(fn($name, $id) => ['id' => $id, 'label' => $name, 'sub' => ''])->values();
    $designationOptions = clone $targetOptions['designations']->map(fn($name, $id) => ['id' => $id, 'label' => $name, 'sub' => ''])->values();
    $roleOptions = clone $targetOptions['roles']->map(fn($name, $id) => ['id' => $id, 'label' => $name, 'sub' => ''])->values();
    $userOptions = clone $targetOptions['users']->map(fn($u) => ['id' => $u->id, 'label' => $u->name, 'sub' => $u->email])->values();
@endphp
<script>
window.announcementForm = function() {
     const allOptions = {
        department: @json($deptOptions),
        store:      @json($storeOptions),
        designation:@json($designationOptions),
        role:       @json($roleOptions),
        users:      @json($userOptions),
    };

    const audienceLabels = @json(\App\Models\Hrm\Announcement::TARGET_LABELS);
    const initialAudience = '{{ old('target_audience', $announcement->target_audience ?? 'all') }}';
    const initialIds = @json(old('target_ids', $announcement->target_ids ?? [])).map(Number).filter(n => !isNaN(n));

    return {
        isSubmitting: false,
        fileName: '',
        targetAudience: initialAudience,
        selectedIds: initialIds,
        searchQuery: '',
        dropdownOpen: false,

        get audienceLabel() {
            return audienceLabels[this.targetAudience] || this.targetAudience;
        },

        get currentOptions() {
            return allOptions[this.targetAudience] || [];
        },

        get filteredOptions() {
            const q = this.searchQuery.toLowerCase().trim();
            if (!q) return this.currentOptions;
            return this.currentOptions.filter(o =>
                o.label.toLowerCase().includes(q) || (o.sub && o.sub.toLowerCase().includes(q))
            );
        },

        getOptionLabel(id) {
            const opt = this.currentOptions.find(o => o.id === id);
            return opt ? opt.label : '#' + id;
        },

        toggleId(id) {
            const idx = this.selectedIds.indexOf(id);
            if (idx > -1) { this.selectedIds.splice(idx, 1); }
            else { this.selectedIds.push(id); }
        },

        removeId(id) {
            this.selectedIds = this.selectedIds.filter(i => i !== id);
        },

        init() {
            this.$watch('targetAudience', (val, oldVal) => {
                // Only clear when audience type actually changes
                if (val !== oldVal) {
                    this.selectedIds = [];
                    this.searchQuery = '';
                    this.dropdownOpen = false;
                }
            });
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        }
    };
};
</script>
@endpush