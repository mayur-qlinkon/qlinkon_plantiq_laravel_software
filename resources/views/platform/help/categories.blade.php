@extends ('layouts.platform')

@section ('title', 'Help Categories - Platform')
@section ('header', 'Help Categories')

@section ('content')
    <div x-data="helpCategories()" class="space-y-6">
        {{-- ── ALERTS ── --}}
        @if (session('success'))
            <div class="flex items-center gap-3 rounded-xl border border-teal-200 bg-teal-50 px-4 py-3 text-teal-800">
                <i class="fa-solid fa-circle-check text-base text-teal-600"></i>
                <span class="text-sm font-medium">{{ session('success') }}</span>
            </div>
        @endif
        @if (session('error'))
            <div class="flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-800">
                <i class="fa-solid fa-triangle-exclamation text-base text-red-600"></i>
                <span class="text-sm font-medium">{{ session('error') }}</span>
            </div>
        @endif
        @if ($errors->any())
            <div class="flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-800">
                <i class="fa-solid fa-circle-exclamation text-base text-red-600"></i>
                <span class="text-sm font-medium">Please check the form for errors and try again.</span>
            </div>
        @endif

        {{-- ── TOOLBAR ── --}}
        <div
            class="flex flex-col justify-between gap-4 rounded-2xl border border-gray-100 bg-white p-4 shadow-sm md:flex-row md:items-center"
        >
            <div class="flex items-center gap-3">
                <div class="bg-brand-50 text-brand-600 flex h-10 w-10 items-center justify-center rounded-xl">
                    <i class="fa-solid fa-sitemap text-base"></i>
                </div>
                <div>
                    <h2 class="text-base font-bold text-gray-900">Manage Categories</h2>
                    <p class="text-xs font-medium text-gray-500">Organize your help center articles into sections.</p>
                </div>
            </div>

            {{-- Add Action --}}
            <button
                @click="openCreate()"
                class="bg-brand-600 hover:bg-brand-700 flex w-full shrink-0 items-center justify-center gap-2 rounded-xl px-5 py-2.5 text-sm font-bold text-white shadow-sm transition-colors md:w-auto"
            >
                <i class="fa-solid fa-plus text-sm"></i>
                New Category
            </button>
        </div>

        {{-- ── DATA GRID (Cards on Mobile / Table on Desktop) ── --}}
        <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
            <div
                class="hidden grid-cols-12 gap-4 border-b border-gray-100 bg-gray-50/50 p-4 text-xs font-bold tracking-wider text-gray-500 uppercase md:grid"
            >
                <div class="col-span-5">Category Details</div>
                <div class="col-span-3 text-center">Articles</div>
                <div class="col-span-2 text-center">Status</div>
                <div class="col-span-2 text-right">Actions</div>
            </div>

            <div class="divide-y divide-gray-100">
                @forelse ($categories as $category)
                    <div
                        class="flex flex-col items-start gap-4 p-4 transition-colors hover:bg-gray-50 md:grid md:grid-cols-12 md:items-center"
                    >
                        {{-- Category Info --}}
                        <div class="col-span-5 flex items-center gap-4">
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-500"
                            >
                                <i class="fa-solid fa-{{ $category->icon ?: 'folder' }} text-base"></i>
                            </div>
                            <div>
                                <h3 class="flex items-center gap-2 text-sm font-bold text-gray-900">
                                    {{ $category->title }}
                                </h3>
                                <p class="mt-0.5 text-xs font-medium text-gray-400">Slug: /{{ $category->slug }}</p>
                                @if ($category->sort_order > 0)
                                    <p class="mt-1 text-[10px] font-bold tracking-wider text-gray-400 uppercase">Sort: {{ $category->sort_order }}</p>
                                @endif
                            </div>
                        </div>

                        {{-- Stats --}}
                        <div class="col-span-3 mt-2 flex flex-col md:mt-0 md:items-center">
                            <span
                                class="inline-flex items-center gap-1.5 rounded-lg bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700"
                            >
                                <i class="fa-solid fa-file-lines text-xs"></i>
                                {{ $category->articles_count }} Total
                            </span>
                            @if ($category->articles_count > 0)
                                <span class="mt-1 text-[11px] font-medium text-gray-500">
                                    {{ $category->published_articles_count }} Published
                                </span>
                            @endif
                        </div>

                        {{-- Status Badge --}}
                        <div class="col-span-2 mt-2 flex items-center md:mt-0 md:justify-center">
                            @if ($category->is_active)
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-md border border-green-200 bg-green-50 px-2.5 py-1 text-xs font-bold text-green-700"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span> Active
                                </span>
                            @else
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-md border border-gray-200 bg-gray-100 px-2.5 py-1 text-xs font-bold text-gray-600"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span> Hidden
                                </span>
                            @endif
                        </div>

                        {{-- Actions --}}
                        <div class="col-span-2 mt-4 flex w-full items-center gap-2 md:mt-0 md:w-auto md:justify-end">
                            <button
                                @click="openEdit({{ $category->toJson() }})"
                                class="hover:text-brand-600 flex-1 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-center text-xs font-bold text-gray-600 transition-colors hover:bg-gray-50 md:flex-none"
                            >
                                Edit
                            </button>

                            @if ($category->articles_count > 0)
                                <button
                                    type="button"
                                    @click="BizAlert.toast('Cannot delete category because it has {{ $category->articles_count }} articles inside it. Reassign them first.', 'error')"
                                    class="flex-1 cursor-not-allowed rounded-lg border border-gray-200 bg-gray-50 px-3 py-1.5 text-center text-xs font-bold text-gray-400 md:flex-none"
                                >
                                    Delete
                                </button>
                            @else
                                <button
                                    @click="deleteCategory({{ $category->id }})"
                                    class="flex-1 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-center text-xs font-bold text-red-500 transition-colors hover:bg-red-50 md:flex-none"
                                >
                                    Delete
                                </button>
                                <form
                                    id="delete-form-{{ $category->id }}"
                                    action="{{ route('platform.help.categories.destroy', $category) }}"
                                    method="POST"
                                    class="hidden"
                                >
                                    @csrf
                                    @method ('DELETE')
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="flex flex-col items-center p-12 text-center">
                        <div
                            class="mb-3 flex h-16 w-16 items-center justify-center rounded-full bg-gray-50 text-gray-400"
                        >
                            <i class="fa-solid fa-folder-open text-2xl"></i>
                        </div>
                        <h3 class="text-base font-bold text-gray-800">No categories found</h3>
                        <p class="mt-1 text-sm text-gray-500">Organize your help center by creating your first category.</p>
                        <button @click="openCreate()" class="text-brand-600 mt-4 text-sm font-bold hover:underline">
                            + Create Category
                        </button>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- ── CREATE / EDIT MODAL ── --}}
        <div x-show="showModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
            <div class="flex min-h-screen items-center justify-center px-4 pt-4 pb-20 text-center sm:p-0">
                {{-- Backdrop --}}
                <div
                    x-show="showModal"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"
                    @click="showModal = false"
                ></div>

                {{-- Modal Panel --}}
                <div
                    x-show="showModal"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="relative inline-block w-full max-w-lg transform rounded-2xl bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:align-middle"
                >
                    <form :action="formAction" method="POST">
                        @csrf
                        <input type="hidden" name="_method" :value="isEditing ? 'PUT' : 'POST'" />

                        {{-- Header --}}
                        <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                            <h3
                                class="text-lg font-bold text-gray-900"
                                x-text="isEditing ? 'Edit Category' : 'Create New Category'"
                            ></h3>
                            <button
                                type="button"
                                @click="showModal = false"
                                class="text-gray-400 transition-colors hover:text-gray-600"
                            >
                                <i class="fa-solid fa-xmark text-base"></i>
                            </button>
                        </div>

                        {{-- Body --}}
                        <div class="space-y-5 px-6 py-5">
                            {{-- Title --}}
                            <div>
                                <label class="mb-1 block text-sm font-bold text-gray-700"
                                    >Category Title <span class="text-red-500">*</span></label
                                >
                                <input
                                    type="text"
                                    name="title"
                                    x-model="form.title"
                                    required
                                    class="focus:ring-brand-500/20 focus:border-brand-500 w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm transition-all outline-none focus:ring-2"
                                />
                            </div>

                            {{-- Icon & Sort Order Grid --}}
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="mb-1 block text-sm font-bold text-gray-700"
                                        >Font Awesome Icon Name</label
                                    >
                                    <div class="relative">
                                        <div
                                            class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3"
                                        >
                                            <i class="fa-solid fa-face-smile text-sm text-gray-400"></i>
                                        </div>
                                        <input
                                            type="text"
                                            name="icon"
                                            x-model="form.icon"
                                            placeholder="e.g. file-text"
                                            class="focus:ring-brand-500/20 focus:border-brand-500 w-full rounded-xl border border-gray-200 py-2.5 pr-4 pl-9 text-sm transition-all outline-none focus:ring-2"
                                        />
                                    </div>
                                    <p class="mt-1 text-[11px] text-gray-400"><a href="https://fontawesome.com/search?o=r&s=solid" target="_blank" class="hover:text-brand-600 hover:underline">Find icons here</a></p>
                                </div>

                                <div>
                                    <label class="mb-1 block text-sm font-bold text-gray-700">Sort Order</label>
                                    <input
                                        type="number"
                                        name="sort_order"
                                        x-model="form.sort_order"
                                        min="0"
                                        class="focus:ring-brand-500/20 focus:border-brand-500 w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm transition-all outline-none focus:ring-2"
                                    />
                                    <p class="mt-1 text-[11px] text-gray-400">Lower = higher priority.</p>
                                </div>
                            </div>

                            {{-- Active Toggle --}}
                            <div class="mt-2 flex items-center gap-3 rounded-xl border border-gray-100 bg-gray-50 p-4">
                                <input type="hidden" name="is_active" value="0" />
                                <input
                                    type="checkbox"
                                    name="is_active"
                                    id="is_active"
                                    value="1"
                                    x-model="form.is_active"
                                    class="text-brand-600 focus:ring-brand-500 h-5 w-5 rounded border-gray-300 focus:ring-offset-0"
                                />
                                <label for="is_active" class="cursor-pointer text-sm font-bold text-gray-800">
                                    Active Category
                                    <span class="block text-xs font-medium text-gray-500"
                                        >Inactive categories hide all their articles from users.</span
                                    >
                                </label>
                            </div>
                        </div>

                        {{-- Footer --}}
                        <div
                            class="flex items-center justify-end gap-3 rounded-b-2xl border-t border-gray-100 bg-gray-50 px-6 py-4"
                        >
                            <button
                                type="button"
                                @click="showModal = false"
                                class="rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-sm font-bold text-gray-600 shadow-sm transition-colors hover:bg-gray-50"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                class="bg-brand-600 hover:bg-brand-700 rounded-xl px-5 py-2.5 text-sm font-bold text-white shadow-sm transition-colors"
                            >
                                Save Category
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section ('scripts')
    <script>
        document.addEventListener("alpine:init", () => {
            Alpine.data("helpCategories", () => ({
                showModal: false,
                isEditing: false,
                formAction: "",
                form: {
                    id: "",
                    title: "",
                    icon: "",
                    sort_order: 0,
                    is_active: true,
                },

                openCreate() {
                    this.isEditing = false;
                    this.formAction = "{{ route('platform.help.categories.store') }}";
                    this.form = {
                        id: "",
                        title: "",
                        icon: "",
                        sort_order: 0,
                        is_active: true,
                    };
                    this.showModal = true;
                },

                openEdit(category) {
                    this.isEditing = true;
                    this.formAction = `/platform/help/categories/${category.id}`;
                    this.form = {
                        id: category.id,
                        title: category.title,
                        icon: category.icon || "",
                        sort_order: category.sort_order || 0,
                        is_active: !!category.is_active,
                    };
                    this.showModal = true;
                },

                deleteCategory(id) {
                    BizAlert.confirm(
                        "Delete Category?",
                        "Are you sure you want to permanently delete this category? This cannot be undone.",
                        "Yes, delete it",
                    ).then((result) => {
                        if (result.isConfirmed) {
                            document.getElementById("delete-form-" + id).submit();
                        }
                    });
                },
            }));
        });
    </script>
@endsection
