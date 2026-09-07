@extends ('layouts.admin')

@section('title', 'Categories')

@section('header-title')
    <h1 class="text-sm font-bold tracking-widest text-gray-500 uppercase">Product Categories</h1>
@endsection

@section('content')
    <div class="space-y-6 pb-10" x-data="categoryCrud()">


        <div class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
            @if (session('success'))
                <div
                    class="flex items-center gap-2 rounded-lg bg-[#dcfce7] px-4 py-2 text-sm font-bold text-[#16a34a] shadow-sm">
                    <i data-lucide="check-circle" class="h-4 w-4"></i> {{ session('success') }}
                </div>
            @endif
            @if ($errors->any())
                <div
                    class="flex w-full flex-col gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 shadow-sm sm:w-auto">
                    <div class="flex items-center gap-2 font-bold">
                        <i data-lucide="alert-circle" class="h-4 w-4"></i>
                        <span>Please fix the following mistakes:</span>
                    </div>
                    <ul class="list-inside list-disc text-xs font-medium text-red-600">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <div class="flex flex-col overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm">
            <div
                class="flex flex-col items-center justify-between gap-4 border-b border-gray-100 bg-white px-6 py-4 sm:flex-row">
                <h2 class="text-[1.15rem] font-bold tracking-tight text-[#212538]">All Categories</h2>

                <div class="flex w-full items-center gap-3 sm:w-auto">

                    <x-import-modal type="categories" />
                    <div class="relative w-full sm:w-64">
                        <input type="text" x-model="search" placeholder="Search categories..."
                            class="focus:ring-brand-500/20 focus:border-brand-500 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700 transition-all outline-none placeholder:text-gray-400 focus:ring-2" />
                    </div>

                    @if (has_permission('categories.create'))
                        <button @click="openCreateModal()"
                            class="bg-brand-500 hover:bg-brand-600 flex items-center gap-1.5 rounded-lg px-4 py-2 text-sm font-bold whitespace-nowrap text-white shadow-sm transition-colors">
                            <i data-lucide="plus" class="h-4 w-4"></i> Add
                        </button>
                    @endif

                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead
                        class="hidden border-b border-gray-100 bg-[#f8fafc] text-[11px] font-bold tracking-wider text-gray-400 uppercase md:table-header-group">
                        <tr>
                            <th class="w-1/3 px-6 py-4">CATEGORY NAME</th>
                            <th class="w-1/3 px-6 py-4">IMAGE</th>
                            <th class="w-1/4 px-6 py-4 text-center">STATUS</th>
                            <th class="w-1/3 px-6 py-4 text-right">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($categories as $category)
                            <tr class="relative flex flex-col border-b border-gray-100 p-4 transition-colors hover:bg-gray-50/50 md:table-row md:border-b-0 md:p-0"
                                x-show="matchesSearch('{{ strtolower($category->name) }}')">
                                <td class="block w-full px-0 py-2 md:table-cell md:w-auto md:px-6 md:py-4">
                                    <div
                                        class="mb-1 text-[10px] font-bold tracking-wider text-gray-400 uppercase md:hidden">
                                        Category Name
                                    </div>
                                    <span
                                        class="pr-20 text-[15px] font-bold text-[#475569] md:text-[13.5px]">{{ $category->name }}</span>
                                </td>

                                <td class="block w-full px-0 py-2 md:table-cell md:w-auto md:px-6 md:py-4">
                                    <div
                                        class="mb-2 text-[10px] font-bold tracking-wider text-gray-400 uppercase md:hidden">
                                        Image
                                    </div>
                                    <div
                                        class="flex h-[50px] w-[50px] items-center justify-center overflow-hidden rounded-lg border border-gray-200 bg-gray-50 shadow-sm md:h-[45px] md:w-[45px]">
                                        <img src="{{ $category->image_url }}" alt="{{ $category->name }}"
                                            class="h-full w-full object-cover" />
                                    </div>
                                </td>

                                <td
                                    class="block flex w-full items-center justify-between px-0 py-2 md:table-cell md:w-auto md:px-6 md:py-4 md:text-center">
                                    <div class="text-[10px] font-bold tracking-wider text-gray-400 uppercase md:hidden">
                                        Status
                                    </div>
                                    @if ($category->is_active)
                                        <span
                                            class="rounded-md bg-[#dcfce7] px-3 py-1 text-[11px] font-bold tracking-wider text-[#16a34a] uppercase">Active</span>
                                    @else
                                        <span
                                            class="rounded-md bg-gray-200 px-3 py-1 text-[11px] font-bold tracking-wider text-gray-500 uppercase">Inactive</span>
                                    @endif
                                </td>

                                <td
                                    class="absolute top-4 right-4 block w-auto px-0 py-0 md:relative md:top-auto md:right-auto md:table-cell md:px-6 md:py-4">
                                    <div class="flex items-center justify-end gap-2.5">
                                        @if (has_permission('categories.update'))
                                            <button
                                                @click="openEditModal({{ $category->toJson() }}, '{{ $category->image_url }}')"
                                                class="flex h-[32px] w-[32px] items-center justify-center rounded border border-[#108c2a] text-[#108c2a] transition-colors hover:bg-green-50"
                                                title="Edit">
                                                <i data-lucide="edit" class="h-4 w-4"></i>
                                            </button>
                                        @endif


                                        @if (has_permission('categories.delete'))
                                            <form action="{{ route('admin.categories.destroy', $category->id) }}"
                                                method="POST" @submit.prevent="confirmDelete($event.target)"
                                                class="inline-block">
                                                @csrf
                                                @method ('DELETE')
                                                <button type="submit"
                                                    class="flex h-[32px] w-[32px] items-center justify-center rounded border border-red-400 text-red-500 transition-colors hover:bg-red-50"
                                                    title="Delete">
                                                    <i data-lucide="trash-2" class="h-4 w-4"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-10 text-center font-medium text-gray-400">
                                    No categories found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="h-12 w-full border-t border-gray-100 bg-white"></div>
        </div>

        <div x-show="isModalOpen" style="display: none"
            class="fixed inset-0 z-50 flex items-center justify-center overflow-x-hidden overflow-y-auto bg-black/50 backdrop-blur-sm transition-opacity"
            x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <div class="relative w-full max-w-md p-4" @click.away="closeModal()">
                <div class="relative overflow-hidden rounded-xl border border-gray-100 bg-white shadow-2xl">
                    <div class="flex items-center justify-between border-b border-gray-100 bg-white p-5">
                        <h3 class="text-lg font-bold text-[#212538]"
                            x-text="modalMode === 'create' ? 'Add New Category' : 'Edit Category'"></h3>
                        <button @click="closeModal()" type="button"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-sm text-gray-400 transition-colors hover:bg-gray-100">
                            <i data-lucide="x" class="h-5 w-5"></i>
                        </button>
                    </div>

                    <form :action="formAction" method="POST" enctype="multipart/form-data" class="p-5"
                        @submit="BizAlert.loading('Uploading...')">
                        @csrf
                        <template x-if="modalMode === 'edit'">
                            <input type="hidden" name="_method" value="PUT" />
                        </template>

                        <div class="space-y-4">
                            <div class="mb-2 flex flex-col items-center justify-center">
                                <div
                                    class="group relative flex h-24 w-24 items-center justify-center overflow-hidden rounded-xl border-2 border-dashed border-gray-200 bg-gray-50">
                                    <template x-if="imagePreview">
                                        <img :src="imagePreview" class="h-full w-full object-cover" />
                                    </template>
                                    <template x-if="!imagePreview">
                                        <i data-lucide="image" class="h-8 w-8 text-gray-300"></i>
                                    </template>
                                    <label
                                        class="absolute inset-0 flex cursor-pointer items-center justify-center bg-black/40 text-[10px] font-bold text-white opacity-0 transition-opacity group-hover:opacity-100">
                                        CHANGE
                                        <input type="file" name="image_file" class="hidden"
                                            @change="previewFile($event)" accept="image/*" />
                                    </label>
                                </div>
                                <span class="mt-2 text-[10px] font-bold tracking-wider text-gray-400 uppercase">Category
                                    Thumbnail</span>
                            </div>

                            <div>
                                <label
                                    class="mb-1.5 block text-[11px] font-bold tracking-wider text-gray-500 uppercase">Category
                                    Name <span class="text-red-500">*</span></label>
                                <input type="text" name="name" x-model="formData.name" required
                                    placeholder="e.g. Indoor Plants"
                                    class="w-full rounded-lg border border-gray-200 px-4 py-2.5 text-sm text-gray-700 transition-all outline-none focus:border-[#108c2a] focus:ring-2 focus:ring-[#108c2a]/20" />
                            </div>

                            <div class="mt-4">
                                <label
                                    class="mb-1.5 block text-[11px] font-bold tracking-wider text-gray-500 uppercase">Slug
                                    (Optional)</label>
                                <input type="text" name="slug" x-model="formData.slug"
                                    placeholder="Leave blank to auto-generate"
                                    class="w-full rounded-lg border border-gray-200 px-4 py-2.5 text-sm text-gray-700 transition-all outline-none focus:border-[#108c2a] focus:ring-2 focus:ring-[#108c2a]/20" />
                                <p class="mt-1 text-[10px] font-medium text-gray-400">Determines the URL (e.g.,
                                    /category/indoor-plants)</p>
                            </div>

                            <div class="flex items-center pt-2">
                                <label class="relative inline-flex cursor-pointer items-center">
                                    <input type="checkbox" name="is_active" value="1" x-model="formData.is_active"
                                        class="peer sr-only" />
                                    <div
                                        class="peer h-6 w-11 rounded-full bg-gray-200 peer-checked:bg-[#108c2a] peer-focus:outline-none after:absolute after:start-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:after:translate-x-full peer-checked:after:border-white">
                                    </div>
                                    <span class="ms-3 text-sm font-bold text-gray-600">Active</span>
                                </label>
                            </div>
                        </div>

                        <button type="submit"
                            class="mt-6 w-full rounded-lg bg-[#108c2a] px-5 py-3 text-sm font-bold text-white shadow-sm transition-colors hover:bg-[#0c6b1f]">
                            <span x-text="modalMode === 'create' ? 'Save Category' : 'Update Category'"></span>
                        </button>

                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function categoryCrud() {
            return {
                search: "",
                isModalOpen: false,
                modalMode: "create",
                formAction: "{{ route('admin.categories.store') }}",
                imagePreview: null,
                formData: {
                    name: "",
                    slug: "",
                    is_active: true,
                },

                matchesSearch(name) {
                    return this.search === "" || name.includes(this.search.toLowerCase());
                },

                previewFile(event) {
                    const file = event.target.files[0];
                    if (file) {
                        this.imagePreview = URL.createObjectURL(file);
                    }
                },

                openCreateModal() {
                    this.modalMode = "create";
                    this.formAction = "{{ route('admin.categories.store') }}";
                    this.formData = {
                        name: "",
                        slug: "",
                        is_active: true,
                    };
                    this.imagePreview = null;
                    this.isModalOpen = true;
                },

                openEditModal(cat, imgUrl) {
                    this.modalMode = "edit";
                    this.formAction = `/admin/categories/${cat.id}`;
                    this.formData = {
                        name: cat.name,
                        slug: cat.slug || "",
                        is_active: cat.is_active,
                    };
                    this.imagePreview = imgUrl;
                    this.isModalOpen = true;
                },

                closeModal() {
                    this.isModalOpen = false;
                },

                confirmDelete(form) {
                    BizAlert.confirm(
                        "Delete Category?",
                        "This will hide the category and its associated data.",
                        "Yes, Delete",
                    ).then((result) => {
                        if (result.isConfirmed) {
                            BizAlert.loading("Removing...");
                            form.submit();
                        }
                    });
                },
            };
        }
    </script>
@endpush
