@extends('layouts.admin')

@section('title', 'Storefront Pages')

@section('header-title')
        <h1 class="text-sm font-bold text-gray-500 uppercase tracking-widest">Storefront Pages</h1>
@endsection

@push('styles')
<style>
    .page-row { border-bottom: 1px solid #f8fafc; transition: background 150ms; }
    .page-row:hover { background: #fafbfc; }
    .page-row:last-child { border-bottom: none; }
    .filter-input { border: 1.5px solid #e5e7eb; border-radius: 9px; padding: 8px 12px; font-size: 13px; color: #374151; outline: none; background: #fff; transition: border-color 150ms; }
    .filter-input:focus { border-color: var(--brand-600); }
</style>
@endpush

@section('content')
<div class="pb-10" x-data="pagesList()">

    {{-- ════════ TOOLBAR ════════ --}}
    <div class="bg-white border border-gray-100 rounded-2xl px-4 py-3 mb-5 shadow-sm">
        <form method="GET" action="{{ route('admin.pages.index') }}" class="flex flex-col sm:flex-row sm:items-center flex-wrap gap-3">
            
            <div class="relative flex-1 min-w-[240px]">                
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search pages..." class="filter-input pl-4 w-full">
            </div>

            <select name="type" class="filter-input min-w-[150px]" onchange="this.form.submit()">
                <option value="">All Types</option>
                @foreach($types as $key => $label)
                    <option value="{{ $key }}" {{ request('type') == $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>

            <select name="is_published" class="filter-input min-w-[130px]" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <option value="1" {{ request('is_published') === '1' ? 'selected' : '' }}>Published</option>
                <option value="0" {{ request('is_published') === '0' ? 'selected' : '' }}>Draft</option>
            </select>

            <button type="submit" class="px-6 py-2.5 rounded-xl text-[13px] font-bold text-white transition-all hover:opacity-90" style="background: #212538;">
                Search
            </button>

            @if(has_permission('pages.create'))
            <a href="{{ route('admin.pages.create') }}" class="w-full sm:w-auto justify-center inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-[13px] font-bold text-white transition-all hover:opacity-95" style="background: var(--brand-600);">
                <i data-lucide="plus" class="w-4 h-4"></i> Create Page
            </a>
            @endif
        </form>
    </div>

    {{-- ════════ TABLE ════════ --}}
    <div class="bg-white border border-gray-100 rounded-2xl overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="hidden md:table-header-group">
                    <tr class="bg-gray-50/50 border-b border-gray-100">
                        <th class="px-6 py-4 text-[11px] font-black text-gray-400 uppercase tracking-widest w-[300px]">Page Details</th>
                        <th class="px-6 py-4 text-[11px] font-black text-gray-400 uppercase tracking-widest">Type</th>
                        <th class="px-6 py-4 text-[11px] font-black text-gray-400 uppercase tracking-widest w-[140px]">Status</th>
                        <th class="px-6 py-4 text-[11px] font-black text-gray-400 uppercase tracking-widest w-[160px]">Last Updated</th>
                        <th class="px-6 py-4 text-[11px] font-black text-gray-400 uppercase tracking-widest text-right w-[120px]">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pages as $page)
                        <tr class="page-row flex flex-col md:table-row p-4 md:p-0">
    
                            {{-- Title & Slug --}}
                            <td class="px-0 md:px-6 py-2 md:py-4 border-none md:border-b md:border-gray-100">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-gray-50 flex items-center justify-center flex-shrink-0">
                                        <i data-lucide="file-text" class="w-4 h-4 text-gray-400"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-[14px] font-bold text-gray-800 mb-0.5 truncate">{{ $page->title }}</p>
                                        <a href="{{ route('storefront.page.show', ['slug' => $companySlug, 'pageSlug' => $page->slug]) }}" target="_blank" class="text-[11px] text-brand-600 hover:underline flex items-center gap-1 font-medium truncate">
                                            /{{ $page->slug }} <i data-lucide="external-link" class="w-3 h-3"></i>
                                        </a>
                                    </div>
                                </div>
                            </td>

                            {{-- Type --}}
                            <td class="px-0 md:px-6 py-2 md:py-4 flex md:table-cell items-center justify-between md:justify-start">
                                <span class="md:hidden text-[11px] font-bold text-gray-400 uppercase tracking-widest">Type</span>
                                <span class="text-[12px] font-semibold text-gray-600">{{ $page->type_label }}</span>
                            </td>

                            {{-- Status Toggle --}}
                            <td class="px-0 md:px-6 py-2 md:py-4 flex md:table-cell items-center justify-between md:justify-start">
                                <span class="md:hidden text-[11px] font-bold text-gray-400 uppercase tracking-widest">Status</span>
                                @if(has_permission('pages.toggle_publish'))
                                <button @click="toggleStatus({{ $page->id }}, '{{ addslashes($page->title) }}')"
                                        class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors focus:outline-none"
                                        :class="pageStatuses[{{ $page->id }}] ? 'bg-green-500' : 'bg-gray-200'">
                                    <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white transition-transform"
                                          :class="pageStatuses[{{ $page->id }}] ? 'translate-x-4' : 'translate-x-1'"></span>
                                </button>
                                @endif
                                <span class="ml-2 text-[11px] font-bold uppercase tracking-wider" 
                                      :class="pageStatuses[{{ $page->id }}] ? 'text-green-600' : 'text-gray-400'"
                                      x-text="pageStatuses[{{ $page->id }}] ? 'Published' : 'Draft'">
                                </span>
                            </td>

                            {{-- Last Updated --}}
                            <td class="px-0 md:px-6 py-2 md:py-4 flex md:table-cell items-center justify-between md:justify-start border-b border-gray-50 md:border-none pb-4 md:pb-4">
                                    <span class="md:hidden text-[11px] font-bold text-gray-400 uppercase tracking-widest">Updated</span>
                                    <div class="text-right md:text-left">
                                    <p class="text-[13px] font-bold text-gray-700">{{ $page->updated_at->format('d M, Y') }}</p>
                                    <p class="text-[11px] text-gray-400 font-medium">{{ $page->updater?->name ?? 'System' }}</p>
                                </div>
                            </td>

                            {{-- Actions --}}
                            <td class="px-0 md:px-6 py-3 md:py-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    @if(has_permission('pages.update'))
                                    <a href="{{ route('admin.pages.edit', $page->id) }}" class="w-8 h-8 inline-flex items-center justify-center rounded-lg text-gray-400 hover:text-brand-600 hover:bg-brand-50 transition-colors" title="Edit">
                                        <i data-lucide="edit-3" class="w-4 h-4"></i>
                                    </a>
                                    @endif
                                    @if(has_permission('pages.delete'))
                                    <button @click="deletePage({{ $page->id }}, '{{ addslashes($page->title) }}')" class="w-8 h-8 inline-flex items-center justify-center rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors" title="Delete">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr class="md:table-row flex">
                            <td colspan="5" class="px-6 py-20 text-center">
                                <div class="w-16 h-16 bg-gray-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
                                    <i data-lucide="file-x" class="w-8 h-8 text-gray-300"></i>
                                </div>
                                <p class="text-gray-500 font-bold text-sm">No pages found</p>
                                <p class="text-gray-400 text-xs mt-1">Click "Create Page" to get started.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($pages->hasPages())
            <div class="px-6 py-4 border-t border-gray-50 bg-gray-50/30">
                {{ $pages->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
function pagesList() {
    return {
        // Initialize an object holding the boolean status for every page on screen
        pageStatuses: {
            @foreach($pages as $page)
                {{ $page->id }}: {{ $page->is_published ? 'true' : 'false' }},
            @endforeach
        },

        async toggleStatus(id, title) {
            // Optimistic UI update
            this.pageStatuses[id] = !this.pageStatuses[id];

            try {
                const res = await fetch(`/admin/pages/${id}/toggle`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' }
                });
                
                const data = await res.json();
                if(!data.success) throw new Error();
                
                Swal.fire({ toast: true, position: 'top-end', showConfirmButton: false, timer: 2000, icon: 'success', title: data.message });
            } catch (e) {
                // Revert on failure
                this.pageStatuses[id] = !this.pageStatuses[id];
                Swal.fire('Error', 'Could not update status.', 'error');
            }
        },

        async deletePage(id, title) {
            const confirmed = await Swal.fire({
                title: 'Delete Page?',
                text: `Are you sure you want to delete "${title}"?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete',
                confirmButtonColor: '#ef4444'
            });

            if (!confirmed.isConfirmed) return;

            try {
                const res = await fetch(`/admin/pages/${id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                
                if (data.success) {
                    location.reload(); 
                } else {
                    throw new Error(data.message);
                }
            } catch (e) {
                Swal.fire('Error', e.message || 'Action failed', 'error');
            }
        }
    }
}
</script>
@endpush