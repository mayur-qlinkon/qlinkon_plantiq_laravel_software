@extends('layouts.platform')
@section('title', 'Core Features')
@section('header', 'Core Features')

@section('content')
    <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Core Features</h2>
            <p class="text-sm text-gray-500">Features shown in the "What's included" section of the checkout summary.</p>
        </div>
        <a href="{{ route('platform.core-features.create') }}" class="bg-brand-600 hover:bg-brand-700 text-white px-4 py-2.5 rounded-lg text-sm font-medium transition flex items-center gap-2 shadow-sm">
            <i class="fa-solid fa-plus"></i> Add Feature
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
                        <th class="px-6 py-4">Feature</th>
                        <th class="px-6 py-4">Value</th>
                        <th class="px-6 py-4">Order</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($features as $feature)
                        <tr class="hover:bg-gray-50/50 transition">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-gray-100 text-gray-500 flex items-center justify-center shrink-0 text-sm">
                                        <i class="{{ $feature->icon ?: 'fa-solid fa-check' }}"></i>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-800 text-sm">{{ $feature->name }}</p>
                                        @if($feature->description)
                                            <p class="text-xs text-gray-400">{{ Str::limit($feature->description, 60) }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm font-medium text-gray-700">{{ $feature->value ?: '—' }}</span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $feature->sort_order }}</td>
                            <td class="px-6 py-4">
                                @if($feature->is_active)
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
                                    <a href="{{ route('platform.core-features.edit', $feature) }}" class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-md transition">
                                        <i class="fa-solid fa-pen-to-square text-sm"></i>
                                    </a>
                                    <form id="delete-form-{{ $feature->id }}" action="{{ route('platform.core-features.destroy', $feature) }}" method="POST" class="inline-block m-0">
                                        @csrf @method('DELETE')
                                        <button type="button" onclick="confirmDelete({{ $feature->id }})" class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-md transition">
                                            <i class="fa-solid fa-trash-can text-sm"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <div class="w-16 h-16 mx-auto bg-gray-50 rounded-full flex items-center justify-center mb-4 text-gray-400 border border-gray-100">
                                    <i class="fa-solid fa-star text-2xl"></i>
                                </div>
                                <h3 class="text-base font-semibold text-gray-900 mb-1">No Features Yet</h3>
                                <p class="text-sm text-gray-500 mb-4">Add features shown in the checkout summary card.</p>
                                <a href="{{ route('platform.core-features.create') }}" class="inline-flex items-center gap-2 text-sm text-brand-600 hover:text-brand-700 font-semibold">
                                    <i class="fa-solid fa-plus"></i> Add First Feature
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($features->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50/50">{{ $features->links() }}</div>
        @endif
    </div>
@endsection
@push('scripts')
    <script>
        function confirmDelete(id) {
            BizAlert.confirm(
                "Delete Feature?",
                "Are you sure you want to delete this feature? This action cannot be undone.",
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