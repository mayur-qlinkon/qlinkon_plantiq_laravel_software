@extends('layouts.platform')
@section('title', 'Addons')
@section('header', 'Addons')

@section('content')
    <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">All Addons</h2>
            <p class="text-sm text-gray-500">Bundle multiple modules into a purchasable addon.</p>
        </div>
        <a href="{{ route('platform.addons.create') }}" class="bg-brand-600 hover:bg-brand-700 text-white px-4 py-2.5 rounded-lg text-sm font-medium transition flex items-center gap-2 shadow-sm">
            <i class="fa-solid fa-plus"></i> Create Addon
        </a>
    </div>

    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg flex items-center gap-3">
            <i class="fa-solid fa-circle-check text-lg"></i>
            <span class="text-sm font-medium">{{ session('success') }}</span>
        </div>
    @endif

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full whitespace-nowrap">
                <thead class="bg-gray-50/80 border-b border-gray-200">
                    <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        <th class="px-6 py-4">Addon</th>
                        <th class="px-6 py-4">Price</th>
                        <th class="px-6 py-4">Modules</th>
                        <th class="px-6 py-4">Order</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($addons as $addon)
                        <tr class="hover:bg-gray-50/50 transition">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center shrink-0">
                                        <i class="{{ $addon->icon ?: 'fa-solid fa-puzzle-piece' }}"></i>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-800">{{ $addon->name }}</p>
                                        <p class="text-xs text-gray-400 font-mono">{{ $addon->slug }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="font-semibold text-gray-800">₹{{ number_format($addon->price, 2) }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                                    <i class="fa-solid fa-cubes-stacked"></i> {{ $addon->modules_count }} module(s)
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $addon->sort_order }}</td>
                            <td class="px-6 py-4">
                                @if($addon->is_active)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700 border border-green-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600 border border-gray-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span> Inactive
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('platform.addons.edit', $addon) }}" class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-md transition">
                                        <i class="fa-solid fa-pen-to-square text-sm"></i>
                                    </a>
                                    <form id="delete-form-{{ $addon->id }}" action="{{ route('platform.addons.destroy', $addon) }}" method="POST" class="inline-block m-0">
                                        @csrf @method('DELETE')
                                        <button type="button" onclick="confirmDelete('{{ $addon->id }}')" class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-md transition">
                                            <i class="fa-solid fa-trash-can text-sm"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="w-16 h-16 mx-auto bg-gray-50 rounded-full flex items-center justify-center mb-4 text-gray-400 border border-gray-100">
                                    <i class="fa-solid fa-puzzle-piece text-2xl"></i>
                                </div>
                                <h3 class="text-base font-semibold text-gray-900 mb-1">No Addons Yet</h3>
                                <p class="text-sm text-gray-500 mb-4">Create your first addon bundle.</p>
                                <a href="{{ route('platform.addons.create') }}" class="inline-flex items-center gap-2 text-sm text-brand-600 hover:text-brand-700 font-semibold">
                                    <i class="fa-solid fa-plus"></i> Create Addon
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($addons->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50/50">{{ $addons->links() }}</div>
        @endif
    </div>
@endsection

@push('scripts')
    <script>
        function confirmDelete(id) {
            BizAlert.confirm(
                "Delete Addon?",
                "Are you sure you want to delete this addon? This action cannot be undone.",
                "Yes, Delete it!",
                "warning"
            ).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('delete-form-' + id).submit();
                }
            });
        }
    </script>
@endpush