@extends('layouts.platform')

@section('title', 'Edit Promotion - Super Admin')
@section('header', 'Edit Promotion')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <a href="{{ route('platform.promotions.index') }}" class="text-sm text-brand-600 hover:text-brand-700 font-medium flex items-center gap-2 mb-2 transition">
                <i class="fa-solid fa-arrow-left"></i> Back to Promotions
            </a>
            <h2 class="text-xl font-semibold text-gray-800">Edit Promotion: {{ $promotion->code ?? $promotion->name }}</h2>
        </div>
    </div>

    {{-- Used Promotion Warning --}}
    @if($promotion->times_used > 0)
        <div class="mb-6 bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded-lg flex items-start gap-3">
            <i class="fa-solid fa-triangle-exclamation mt-0.5 text-lg"></i>
            <div>
                <span class="text-sm font-bold">This promotion is active and has been used {{ number_format($promotion->times_used) }} times.</span>
                <p class="text-xs mt-1">Changes made here will apply immediately to all future checkouts. Past invoices and audit trails will not be affected.</p>
            </div>
        </div>
    @endif

    {{-- Error Display --}}
    @if ($errors->any())
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg flex items-start gap-3">
            <i class="fa-solid fa-circle-xmark mt-0.5 text-lg"></i>
            <div>
                <span class="text-sm font-bold">Please fix the following errors:</span>
                <ul class="list-disc list-inside text-sm mt-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <form action="{{ route('platform.promotions.update', $promotion->id) }}" method="POST" x-data="promotionForm()">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            {{-- Left Column: Main Settings --}}
            <div class="lg:col-span-2 space-y-6">
                
                {{-- 1. Basic Information --}}
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="text-base font-semibold text-gray-800 border-b pb-3 mb-4 flex items-center gap-2">
                        <i class="fa-solid fa-circle-info text-gray-400"></i> Basic Information
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Internal Name *</label>
                            <input type="text" name="name" value="{{ old('name', $promotion->name) }}" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Coupon Code</label>
                            <input type="text" name="code" value="{{ old('code', $promotion->code) }}" placeholder="Leave blank for auto-apply only" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 text-sm uppercase">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea name="description" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 text-sm" placeholder="Internal notes about this promotion...">{{ old('description', $promotion->description) }}</textarea>
                    </div>
                </div>

                {{-- 2. Reward / Discount Logic --}}
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="text-base font-semibold text-gray-800 border-b pb-3 mb-4 flex items-center gap-2">
                        <i class="fa-solid fa-gift text-gray-400"></i> Discount Details
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Discount Type *</label>
                            <select name="discount_type" x-model="type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 text-sm">
                                <option value="fixed">Fixed Amount</option>
                                <option value="percentage">Percentage (%)</option>
                                <option value="free_trial">Free Trial Extension</option>
                                <option value="free_module">Free Module</option>
                                <option value="free_limit">Free Limit Bump</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                <span x-text="type === 'free_trial' ? 'Trial Days' : 'Discount Value'">Discount Value</span>
                            </label>
                            <input type="number" step="0.01" name="discount_value" value="{{ old('discount_value', $promotion->discount_value) }}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 text-sm">
                        </div>

                        <div x-show="type === 'percentage'" x-cloak>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Max Discount Cap (₹)</label>
                            <input type="number" step="0.01" name="max_discount" value="{{ old('max_discount', $promotion->max_discount) }}" placeholder="No limit" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 text-sm">
                        </div>
                    </div>
                </div>

                {{-- 3. Conditions & Limits --}}
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="text-base font-semibold text-gray-800 border-b pb-3 mb-4 flex items-center gap-2">
                        <i class="fa-solid fa-scale-balanced text-gray-400"></i> Conditions & Limits
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Minimum Order Amount (₹)</label>
                                <input type="number" step="0.01" name="minimum_order_amount" value="{{ old('minimum_order_amount', $promotion->minimum_order_amount) }}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-brand-500 text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Maximum Order Amount (₹)</label>
                                <input type="number" step="0.01" name="maximum_order_amount" value="{{ old('maximum_order_amount', $promotion->maximum_order_amount) }}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-brand-500 text-sm">
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Total Usage Limit</label>
                                <input type="number" name="usage_limit" value="{{ old('usage_limit', $promotion->usage_limit) }}" placeholder="Leave blank for unlimited" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-brand-500 text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Uses Per Customer</label>
                                <input type="number" name="usage_per_customer" value="{{ old('usage_per_customer', $promotion->usage_per_customer) }}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-brand-500 text-sm">
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            {{-- Right Column: Status & Dates --}}
            <div class="space-y-6">
                
                {{-- Status Card --}}
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="text-base font-semibold text-gray-800 border-b pb-3 mb-4 flex items-center gap-2">
                        <i class="fa-solid fa-toggle-on text-gray-400"></i> Status & Behavior
                    </h3>
                    
                    <div class="space-y-4">
                        <label class="flex items-center gap-3 cursor-pointer p-2 hover:bg-gray-50 rounded-md transition">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $promotion->is_active) ? 'checked' : '' }} class="w-4 h-4 text-brand-600 border-gray-300 rounded focus:ring-brand-500">
                            <div>
                                <span class="block text-sm font-medium text-gray-800">Active</span>
                                <span class="block text-xs text-gray-500">Allow customers to use this promotion</span>
                            </div>
                        </label>

                        <label class="flex items-center gap-3 cursor-pointer p-2 hover:bg-gray-50 rounded-md transition border-t border-gray-100 pt-3">
                            <input type="hidden" name="auto_apply" value="0">
                            <input type="checkbox" name="auto_apply" value="1" {{ old('auto_apply', $promotion->auto_apply) ? 'checked' : '' }} class="w-4 h-4 text-brand-600 border-gray-300 rounded focus:ring-brand-500">
                            <div>
                                <span class="block text-sm font-medium text-gray-800">Auto-Apply</span>
                                <span class="block text-xs text-gray-500">Apply automatically at checkout if valid</span>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- Schedule Card --}}
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="text-base font-semibold text-gray-800 border-b pb-3 mb-4 flex items-center gap-2">
                        <i class="fa-regular fa-calendar text-gray-400"></i> Schedule
                    </h3>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Start Date & Time</label>
                            <input type="datetime-local" name="starts_at" value="{{ old('starts_at', $promotion->starts_at ? $promotion->starts_at->format('Y-m-d\TH:i') : '') }}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-brand-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Expiration Date & Time</label>
                            <input type="datetime-local" name="expires_at" value="{{ old('expires_at', $promotion->expires_at ? $promotion->expires_at->format('Y-m-d\TH:i') : '') }}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-brand-500 text-sm">
                        </div>
                    </div>
                </div>

                {{-- Form Actions --}}
                <div class="pt-2 flex gap-3">
                    <a href="{{ route('platform.promotions.index') }}" class="w-1/3 text-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                        Cancel
                    </a>
                    <button type="submit" class="w-2/3 px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white rounded-md text-sm font-medium transition shadow-sm flex items-center justify-center gap-2">
                        <i class="fa-solid fa-floppy-disk"></i> Save Changes
                    </button>
                </div>

            </div>
        </div>
    </form>
@endsection

@section('scripts')
<script>
    function promotionForm() {
        return {
            type: '{{ old('discount_type', $promotion->discount_type) }}'
        }
    }
</script>
@endsection