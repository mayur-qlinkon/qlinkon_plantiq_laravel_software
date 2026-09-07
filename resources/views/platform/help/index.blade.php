@extends('layouts.platform')

@section('title', 'Help Articles - Platform')
@section('header', 'Help Articles')

@section('content')
<div x-data="helpArticles()" class="space-y-6">

    {{-- ── ALERTS ── --}}
    @if(session('success'))
        <div class="bg-teal-50 border border-teal-200 text-teal-800 px-4 py-3 rounded-xl flex items-center gap-3">
            <i class="fa-solid fa-circle-check text-teal-600 text-base"></i>
            <span class="text-sm font-medium">{{ session('success') }}</span>
        </div>
    @endif
    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl flex items-center gap-3">
            <i class="fa-solid fa-triangle-exclamation text-red-600 text-base"></i>
            <span class="text-sm font-medium">Please check the form for errors and try again.</span>
        </div>
    @endif

    {{-- ── TOOLBAR ── --}}
    <div class="flex flex-col md:flex-row gap-4 justify-between md:items-center bg-white p-4 rounded-2xl border border-gray-100 shadow-sm">
        
        {{-- Filters & Search --}}
        <form method="GET" action="{{ route('platform.help.index') }}" class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
            
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fa-solid fa-magnifying-glass text-gray-400 text-sm"></i>
                </div>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search articles..." 
                    class="w-full sm:w-64 pl-9 pr-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all outline-none">
            </div>

            <div class="relative">
                <select name="category_id" class="w-full sm:w-48 pl-4 pr-10 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all outline-none appearance-none bg-white">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                            {{ $cat->title }}
                        </option>
                    @endforeach
                </select>
                <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none text-gray-500">
                    <i class="fa-solid fa-chevron-down text-sm"></i>
                </div>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="bg-gray-900 hover:bg-gray-800 text-white px-4 py-2.5 rounded-xl text-sm font-bold transition-colors">
                    Filter
                </button>
                @if(request('search') || request('category_id'))
                    <a href="{{ route('platform.help.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-600 px-4 py-2.5 rounded-xl text-sm font-bold transition-colors flex items-center">
                        Clear
                    </a>
                @endif
            </div>
        </form>

        {{-- Add Action --}}
        <button @click="openCreate()" class="w-full md:w-auto bg-brand-600 hover:bg-brand-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold transition-colors shadow-sm flex items-center justify-center gap-2 shrink-0">
            <i class="fa-solid fa-plus text-sm"></i>
            New Article
        </button>

    </div>

    {{-- ── DATA GRID (Cards on Mobile / Table on Desktop) ── --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        
        <div class="hidden md:grid grid-cols-12 gap-4 p-4 bg-gray-50/50 border-b border-gray-100 text-xs font-bold text-gray-500 uppercase tracking-wider">
            <div class="col-span-5">Article details</div>
            <div class="col-span-3">Category</div>
            <div class="col-span-2 text-center">Status</div>
            <div class="col-span-2 text-right">Actions</div>
        </div>

        <div class="divide-y divide-gray-100">
            @forelse($articles as $article)
                <div class="flex flex-col md:grid md:grid-cols-12 gap-4 p-4 hover:bg-gray-50 transition-colors items-start md:items-center">
                    
                    {{-- Article Info --}}
                    <div class="col-span-5 flex flex-col">
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-bold text-gray-900">{{ $article->title }}</h3>
                            @if($article->hasVideo())
                                <span class="bg-red-50 text-red-600 p-1 rounded-md" title="Contains Video">
                                    <i class="fa-brands fa-youtube text-xs"></i>
                                </span>
                            @endif
                        </div>
                        <p class="text-xs text-gray-400 font-medium mt-0.5">/help/{{ $article->slug }}</p>
                        <p class="text-[11px] text-gray-400 mt-1 flex items-center gap-1">
                            <i class="fa-solid fa-clock text-xs"></i> ~{{ $article->reading_time }} min read
                        </p>
                    </div>

                    {{-- Category --}}
                    <div class="col-span-3 flex items-center gap-2 mt-2 md:mt-0">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-gray-100 text-gray-600">
                            @if($article->category->icon)
                                <i class="fa-solid fa-{{ $article->category->icon }} text-xs"></i>
                            @endif
                            {{ $article->category->title }}
                        </span>
                    </div>

                    {{-- Status Toggle --}}
                    <div class="col-span-2 flex items-center md:justify-center mt-3 md:mt-0">
                        <form method="POST" action="{{ route('platform.help.toggle', $article) }}">
                            @csrf
                            <button type="submit" class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer items-center justify-center rounded-full focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
                                <span class="sr-only">Toggle publish status</span>
                                <span aria-hidden="true" class="pointer-events-none absolute h-full w-full rounded-md bg-white"></span>
                                <span aria-hidden="true" class="pointer-events-none absolute mx-auto h-4 w-8 rounded-full transition-colors duration-200 ease-in-out {{ $article->is_published ? 'bg-brand-500' : 'bg-gray-200' }}"></span>
                                <span aria-hidden="true" class="pointer-events-none absolute left-0 inline-block h-5 w-5 transform rounded-full border border-gray-200 bg-white shadow ring-0 transition-transform duration-200 ease-in-out {{ $article->is_published ? 'translate-x-4' : 'translate-x-0' }}"></span>
                            </button>
                        </form>
                        <span class="ml-2 text-xs font-bold {{ $article->is_published ? 'text-brand-600' : 'text-gray-400' }} md:hidden">
                            {{ $article->is_published ? 'Published' : 'Draft' }}
                        </span>
                    </div>

                    {{-- Actions --}}
                    <div class="col-span-2 flex items-center md:justify-end gap-2 mt-4 md:mt-0 w-full md:w-auto">
                        <button @click="openEdit({{ $article->toJson() }})" class="flex-1 md:flex-none text-center px-3 py-1.5 text-xs font-bold bg-white border border-gray-200 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-brand-600 transition-colors">
                            Edit
                        </button>
                        <button @click="deleteArticle({{ $article->id }})" class="flex-1 md:flex-none text-center px-3 py-1.5 text-xs font-bold bg-white border border-gray-200 text-red-500 rounded-lg hover:bg-red-50 transition-colors">
                            Delete
                        </button>
                        <form id="delete-form-{{ $article->id }}" action="{{ route('platform.help.destroy', $article) }}" method="POST" class="hidden">
                            @csrf @method('DELETE')
                        </form>
                    </div>

                </div>
            @empty
                <div class="p-12 text-center flex flex-col items-center">
                    <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center text-gray-400 mb-3">
                        <i class="fa-solid fa-circle-question text-2xl"></i>
                    </div>
                    <h3 class="text-base font-bold text-gray-800">No articles found</h3>
                    <p class="text-sm text-gray-500 mt-1">Get started by creating your first help center article.</p>
                    <button @click="openCreate()" class="mt-4 text-brand-600 font-bold text-sm hover:underline">
                        + Create Article
                    </button>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Pagination --}}
    @if($articles->hasPages())
        <div class="mt-4">
            {{ $articles->links() }}
        </div>
    @endif

    {{-- ── CREATE / EDIT MODAL ── --}}
    <div x-show="showModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            
            {{-- Backdrop --}}
            <div x-show="showModal" 
                 x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="fixed inset-0 transition-opacity bg-gray-900/60 backdrop-blur-sm" @click="showModal = false"></div>

            {{-- Modal Panel --}}
            <div x-show="showModal"
                 x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="relative inline-block w-full max-w-3xl text-left align-bottom transition-all transform bg-white rounded-2xl shadow-xl sm:my-8 sm:align-middle">
                
                <form :action="formAction" method="POST">
                    @csrf
                    <input type="hidden" name="_method" :value="isEditing ? 'PUT' : 'POST'">
                    
                    {{-- Header --}}
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                        <h3 class="text-lg font-bold text-gray-900" x-text="isEditing ? 'Edit Article' : 'Create New Article'"></h3>
                        <button type="button" @click="showModal = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                            <i class="fa-solid fa-xmark text-base"></i>
                        </button>
                    </div>

                    {{-- Body --}}
                    <div class="px-6 py-4 space-y-5 max-h-[70vh] overflow-y-auto custom-scrollbar">
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            {{-- Title --}}
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-bold text-gray-700 mb-1">Article Title <span class="text-red-500">*</span></label>
                                <input type="text" name="title" x-model="form.title" required
                                    class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all">
                            </div>

                            {{-- Category --}}
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Category <span class="text-red-500">*</span></label>
                                <select name="help_category_id" x-model="form.help_category_id" required
                                    class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all bg-white">
                                    <option value="">Select a category...</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->title }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Sort Order --}}
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Sort Order</label>
                                <input type="number" name="sort_order" x-model="form.sort_order" min="0"
                                    class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all">
                                <p class="text-[11px] text-gray-400 mt-1">Lower numbers appear first.</p>
                            </div>

                            {{-- Video URL --}}
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-bold text-gray-700 mb-1">YouTube Video URL <span class="text-gray-400 font-normal">(Optional)</span></label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fa-brands fa-youtube text-gray-400 text-sm"></i>
                                    </div>
                                    <input type="url" name="video_url" x-model="form.video_url" placeholder="e.g. https://youtube.com/watch?v=..."
                                        class="w-full pl-9 pr-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all">
                                </div>
                            </div>

                            {{-- Content (Markdown) --}}
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-bold text-gray-700 mb-1">Content (Markdown) <span class="text-red-500">*</span></label>
                                <textarea name="content" x-model="form.content" required rows="10"
                                    class="w-full px-4 py-3 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all font-mono"></textarea>
                            </div>

                            {{-- Publish Toggle --}}
                            <div class="sm:col-span-2 flex items-center gap-3 bg-gray-50 p-4 rounded-xl border border-gray-100">
                                <input type="hidden" name="is_published" value="0">
                                <input type="checkbox" name="is_published" id="is_published" value="1" x-model="form.is_published"
                                    class="w-5 h-5 text-brand-600 border-gray-300 rounded focus:ring-brand-500 focus:ring-offset-0">
                                <label for="is_published" class="text-sm font-bold text-gray-800 cursor-pointer">
                                    Publish this article immediately
                                    <span class="block text-xs text-gray-500 font-medium">Draft articles are hidden from tenants.</span>
                                </label>
                            </div>
                        </div>

                    </div>

                    {{-- Footer --}}
                    <div class="px-6 py-4 bg-gray-50 rounded-b-2xl border-t border-gray-100 flex items-center justify-end gap-3">
                        <button type="button" @click="showModal = false" class="px-5 py-2.5 text-sm font-bold text-gray-600 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors shadow-sm">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2.5 text-sm font-bold text-white bg-brand-600 rounded-xl hover:bg-brand-700 transition-colors shadow-sm">
                            Save Article
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>

</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('helpArticles', () => ({
            showModal: false,
            isEditing: false,
            formAction: '',
            form: {
                id: '',
                help_category_id: '',
                title: '',
                video_url: '',
                sort_order: 0,
                is_published: true,
                content: ''
            },
            
            openCreate() {
                this.isEditing = false;
                this.formAction = "{{ route('platform.help.store') }}";
                this.form = {
                    id: '',
                    help_category_id: '',
                    title: '',
                    video_url: '',
                    sort_order: 0,
                    is_published: true,
                    content: ''
                };
                this.showModal = true;
            },
            
            openEdit(article) {
                this.isEditing = true;
                this.formAction = `/platform/help/${article.id}`;
                this.form = {
                    id: article.id,
                    help_category_id: article.help_category_id,
                    title: article.title,
                    video_url: article.video_url || '',
                    sort_order: article.sort_order || 0,
                    is_published: !!article.is_published,
                    content: article.content || ''
                };
                this.showModal = true;
            },

            deleteArticle(id) {
                BizAlert.confirm(
                    'Delete Article?', 
                    'Are you sure you want to permanently delete this help article?', 
                    'Yes, delete it'
                ).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('delete-form-' + id).submit();
                    }
                });
            }
        }));
    });
</script>
@endsection