<?php $__env->startSection('title', 'Create Challan - Qlinkon BIZNESS'); ?>

<?php $__env->startSection('header-title'); ?>
    <h1 class="text-sm font-bold text-gray-500 uppercase tracking-widest">CREATE / Challan</h1>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('styles'); ?>
    <style>
        [x-cloak] {
            display: none !important;
        }

        body.item-modal-open {
            overflow: hidden;
        }

        .custom-scrollbar::-webkit-scrollbar {
            width: 5px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #e2e8f0;
            border-radius: 10px;
        }
    </style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
    <?php
        // Custom component ke liye select options mapping
        $storeOptions = [];
        foreach ($stores ?? [] as $store) {
            $storeOptions[$store->id] = $store->name;
        }

        $warehouseOptions = [];
        foreach ($warehouses ?? [] as $warehouse) {
            $warehouseOptions[$warehouse->id] = $warehouse->name;
        }

        $toWarehouseOptions = [];
        foreach ($warehouses ?? [] as $warehouse) {
            $toWarehouseOptions[$warehouse->id] = $warehouse->name . ' (' . ($warehouse->store?->name ?? '') . ')';
        }

        $stateOptions = [];
        foreach ($states ?? [] as $state) {
            $stateOptions[$state->id] = $state->name;
        }
    ?>

    <div class="pb-20" x-data="challanForm(<?php echo \Illuminate\Support\Js::from($units ?? [])->toHtml() ?>, <?php echo \Illuminate\Support\Js::from($clients ?? [])->toHtml() ?>, <?php echo \Illuminate\Support\Js::from($suppliers ?? [])->toHtml() ?>, <?php echo \Illuminate\Support\Js::from($stores ?? [])->toHtml() ?>, <?php echo \Illuminate\Support\Js::from($warehouses ?? [])->toHtml() ?>)">

        
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-[1.5rem] font-bold text-[#212538] tracking-tight mb-1">Create Challan</h1>
            </div>
        </div>

        
        <?php if($errors->any()): ?>
            <div class="bg-red-50 text-red-700 p-4 rounded-lg mb-6 border border-red-200 shadow-sm">
                <div class="font-bold mb-2 flex items-center gap-2">
                    <i data-lucide="alert-triangle" class="w-5 h-5"></i>
                    Please fix the following mistakes:
                </div>
                <ul class="list-disc list-inside text-sm space-y-1">
                    <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li><?php echo e($error); ?></li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            </div>
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    if (typeof Swal !== 'undefined') Swal.close();
                });
            </script>
        <?php endif; ?>

        
        <?php if(session('error')): ?>
            <div class="bg-amber-50 text-amber-800 p-4 rounded-lg mb-6 border border-amber-300 shadow-sm">
                <div class="font-bold mb-1 flex items-center gap-2">
                    <i data-lucide="package-x" class="w-5 h-5 text-amber-600"></i>
                    Insufficient Stock
                </div>
                <p class="text-sm"><?php echo e(session('error')); ?></p>
            </div>
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    if (typeof Swal !== 'undefined') Swal.close();
                });
            </script>
        <?php endif; ?>

        <form id="mainChallanForm" action="<?php echo e(route('admin.challans.store')); ?>" method="POST"
            @submit="handleFormSubmit($event)">
            <?php echo csrf_field(); ?>

            
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                    <h2 class="text-sm font-bold text-gray-800 uppercase tracking-wider">Document Details</h2>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-4 gap-6">

                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Challan Type
                            <span class="text-red-500">*</span></label>
                        <div @change="formData.challan_type = $event.target.value; handleTypeChange()">
                            <?php if (isset($component)) { $__componentOriginalc933793160c9c2655e76e4334d02687c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc933793160c9c2655e76e4334d02687c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.custom-select','data' => ['name' => 'challan_type','placeholder' => 'Select Type','options' => $typeLabels,'selected' => old('challan_type', ''),'required' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('custom-select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'challan_type','placeholder' => 'Select Type','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($typeLabels),'selected' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('challan_type', '')),'required' => true]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc933793160c9c2655e76e4334d02687c)): ?>
<?php $attributes = $__attributesOriginalc933793160c9c2655e76e4334d02687c; ?>
<?php unset($__attributesOriginalc933793160c9c2655e76e4334d02687c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc933793160c9c2655e76e4334d02687c)): ?>
<?php $component = $__componentOriginalc933793160c9c2655e76e4334d02687c; ?>
<?php unset($__componentOriginalc933793160c9c2655e76e4334d02687c); ?>
<?php endif; ?>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Direction <span
                                class="text-red-500">*</span></label>
                        <div @change="formData.direction = $event.target.value">
                            <?php if (isset($component)) { $__componentOriginalc933793160c9c2655e76e4334d02687c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc933793160c9c2655e76e4334d02687c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.custom-select','data' => ['name' => 'direction','options' => ['outward' => 'Outward (Dispatch)', 'inward' => 'Inward (Receipt)'],'selected' => old('direction', 'outward'),'required' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('custom-select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'direction','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(['outward' => 'Outward (Dispatch)', 'inward' => 'Inward (Receipt)']),'selected' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('direction', 'outward')),'required' => true]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc933793160c9c2655e76e4334d02687c)): ?>
<?php $attributes = $__attributesOriginalc933793160c9c2655e76e4334d02687c; ?>
<?php unset($__attributesOriginalc933793160c9c2655e76e4334d02687c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc933793160c9c2655e76e4334d02687c)): ?>
<?php $component = $__componentOriginalc933793160c9c2655e76e4334d02687c; ?>
<?php unset($__componentOriginalc933793160c9c2655e76e4334d02687c); ?>
<?php endif; ?>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Date <span
                                class="text-red-500">*</span></label>
                        <input type="date" name="challan_date" x-model="formData.challan_date" required
                            class="w-full border border-gray-300 rounded px-3 py-2.5 text-sm focus:border-brand-500 outline-none font-medium">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Origin Store
                            <span class="text-red-500">*</span></label>
                        <div @change="formData.store_id = $event.target.value; autoSelectWarehouse()">
                            <?php if (isset($component)) { $__componentOriginalc933793160c9c2655e76e4334d02687c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc933793160c9c2655e76e4334d02687c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.custom-select','data' => ['name' => 'store_id','placeholder' => 'Select Store','options' => $storeOptions,'selected' => old('store_id', session('store_id', auth()->user()->store_id ?? '')),'required' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('custom-select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'store_id','placeholder' => 'Select Store','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($storeOptions),'selected' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('store_id', session('store_id', auth()->user()->store_id ?? ''))),'required' => true]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc933793160c9c2655e76e4334d02687c)): ?>
<?php $attributes = $__attributesOriginalc933793160c9c2655e76e4334d02687c; ?>
<?php unset($__attributesOriginalc933793160c9c2655e76e4334d02687c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc933793160c9c2655e76e4334d02687c)): ?>
<?php $component = $__componentOriginalc933793160c9c2655e76e4334d02687c; ?>
<?php unset($__componentOriginalc933793160c9c2655e76e4334d02687c); ?>
<?php endif; ?>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">
                            Initial Status <span class="text-red-500">*</span>
                        </label>
                        <div @change="formData.status = $event.target.value">
                            
                            <?php if (isset($component)) { $__componentOriginalc933793160c9c2655e76e4334d02687c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc933793160c9c2655e76e4334d02687c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.custom-select','data' => ['name' => 'status','options' => [
                                    'draft' => 'Draft (Saved only)',
                                    'dispatched' => old('challan_type') === 'branch_transfer'
                                        ? 'Dispatched (Moves Stock Between Warehouses)'
                                        : 'Dispatched (Document only — stock unchanged)',
                                ],'selected' => old('status', 'draft'),'required' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('custom-select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'status','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([
                                    'draft' => 'Draft (Saved only)',
                                    'dispatched' => old('challan_type') === 'branch_transfer'
                                        ? 'Dispatched (Moves Stock Between Warehouses)'
                                        : 'Dispatched (Document only — stock unchanged)',
                                ]),'selected' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('status', 'draft')),'required' => true]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc933793160c9c2655e76e4334d02687c)): ?>
<?php $attributes = $__attributesOriginalc933793160c9c2655e76e4334d02687c; ?>
<?php unset($__attributesOriginalc933793160c9c2655e76e4334d02687c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc933793160c9c2655e76e4334d02687c)): ?>
<?php $component = $__componentOriginalc933793160c9c2655e76e4334d02687c; ?>
<?php unset($__componentOriginalc933793160c9c2655e76e4334d02687c); ?>
<?php endif; ?>
                        </div>

                        
                        <p x-show="formData.status === 'dispatched' && formData.challan_type === 'branch_transfer'"
                            class="text-[10px] text-orange-600 mt-1 font-bold italic" x-cloak>
                            * Warning: Selecting Dispatched will immediately deduct stock from Origin and add to
                            Destination.
                        </p>
                    </div>
                    
                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Stock Warehouse
                            <span class="text-red-500">*</span></label>
                        <div @change="formData.warehouse_id = $event.target.value">
                            <?php if (isset($component)) { $__componentOriginalc933793160c9c2655e76e4334d02687c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc933793160c9c2655e76e4334d02687c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.custom-select','data' => ['name' => 'warehouse_id','placeholder' => 'Select Warehouse','options' => $warehouseOptions,'selected' => old('warehouse_id', ''),'required' => true,'@setWarehouse.window' => 'value = $event.detail']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('custom-select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'warehouse_id','placeholder' => 'Select Warehouse','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($warehouseOptions),'selected' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('warehouse_id', '')),'required' => true,'@set-warehouse.window' => 'value = $event.detail']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc933793160c9c2655e76e4334d02687c)): ?>
<?php $attributes = $__attributesOriginalc933793160c9c2655e76e4334d02687c; ?>
<?php unset($__attributesOriginalc933793160c9c2655e76e4334d02687c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc933793160c9c2655e76e4334d02687c)): ?>
<?php $component = $__componentOriginalc933793160c9c2655e76e4334d02687c; ?>
<?php unset($__componentOriginalc933793160c9c2655e76e4334d02687c); ?>
<?php endif; ?>
                        </div>
                    </div>

                    
                    <div x-show="formData.challan_type === 'branch_transfer'" x-transition.opacity x-cloak
                        class="md:col-span-1">
                        <label class="block text-xs font-black text-blue-600 uppercase tracking-wider mb-2">
                            Destination Warehouse <span class="text-red-500">*</span>
                        </label>
                        <div @change="formData.to_warehouse_id = $event.target.value">
                            <?php if (isset($component)) { $__componentOriginalc933793160c9c2655e76e4334d02687c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc933793160c9c2655e76e4334d02687c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.custom-select','data' => ['name' => 'to_warehouse_id','placeholder' => 'Select Destination','options' => $toWarehouseOptions,'selected' => old('to_warehouse_id', ''),'@setToWarehouse.window' => 'value = $event.detail']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('custom-select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'to_warehouse_id','placeholder' => 'Select Destination','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($toWarehouseOptions),'selected' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('to_warehouse_id', '')),'@set-to-warehouse.window' => 'value = $event.detail']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc933793160c9c2655e76e4334d02687c)): ?>
<?php $attributes = $__attributesOriginalc933793160c9c2655e76e4334d02687c; ?>
<?php unset($__attributesOriginalc933793160c9c2655e76e4334d02687c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc933793160c9c2655e76e4334d02687c)): ?>
<?php $component = $__componentOriginalc933793160c9c2655e76e4334d02687c; ?>
<?php unset($__componentOriginalc933793160c9c2655e76e4334d02687c); ?>
<?php endif; ?>
                        </div>
                    </div>

                </div>
            </div>

            
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                    <h2 class="text-sm font-bold text-gray-800 uppercase tracking-wider">Party Details</h2>
                    <button type="button" @click="isGuest = !isGuest"
                        :class="isGuest ? 'bg-gray-800 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'"
                        class="px-3 py-1.5 rounded text-[10px] font-bold uppercase tracking-widest transition-colors">
                        <span x-text="isGuest ? 'Manual Entry Active' : 'Manual Entry'"></span>
                    </button>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">

                    
                    <div class="lg:col-span-2 relative" x-show="!isGuest" @click.away="isPartyDropdownOpen = false">
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">
                            Select Party <span x-show="isPartyRequired" class="text-red-500">*</span>
                        </label>

                        
                        <div class="relative">
                            <input type="text" x-model="partySearchTerm" @focus="isPartyDropdownOpen = true"
                                @input="isPartyDropdownOpen = true; formData.party_id = ''"
                                :placeholder="partyList.length === 0 ? 'Select a Challan Type first...' : 'Search party name...'"
                                :disabled="partyList.length === 0"
                                class="w-full border border-gray-300 rounded px-3 py-2.5 text-sm focus:border-brand-500 outline-none bg-white font-bold text-gray-700 disabled:bg-gray-50 disabled:text-gray-400">
                            <i data-lucide="chevron-down"
                                class="w-4 h-4 absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                        </div>

                        
                        <input type="hidden" :name="partyFieldName" x-model="formData.party_id">

                        
                        <ul x-show="isPartyDropdownOpen" x-cloak x-transition
                            class="absolute z-[60] w-full bg-white border border-gray-200 rounded-lg shadow-2xl mt-1 max-h-60 overflow-y-auto overscroll-contain top-full left-0 custom-scrollbar">

                            <li x-show="filteredPartyList.length === 0"
                                class="px-4 py-4 text-sm text-gray-500 text-center font-medium">
                                No matching parties found.
                            </li>

                            <template x-for="party in filteredPartyList" :key="party.id">
                                <li @click="selectParty(party)"
                                    class="px-4 py-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-0 transition-colors">
                                    <div class="font-bold text-[13px] text-gray-800" x-text="party.name"></div>
                                    <div class="text-[11px] text-gray-500 mt-0.5 flex items-center gap-2"
                                        x-show="party.phone || party.gst_number || party.gstin">
                                        <span x-show="party.phone" x-text="'📞 ' + party.phone"></span>
                                        <span x-show="party.gst_number || party.gstin"
                                            class="bg-gray-100 px-1.5 py-0.5 rounded border border-gray-200 text-[9px] font-bold text-gray-600"
                                            x-text="'GST: ' + (party.gst_number || party.gstin)"></span>
                                    </div>
                                </li>
                            </template>
                        </ul>
                    </div>

                    
                    <div class="lg:col-span-2 grid grid-cols-2 gap-4" x-show="isGuest" x-cloak>
                        <div class="col-span-2">
                            <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Party
                                Name</label>
                            <input type="text" name="party_name" x-model="formData.party_name"
                                placeholder="Walk-in / Unknown"
                                class="w-full border border-gray-300 rounded px-3 py-2.5 text-sm focus:border-brand-500 outline-none">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Party
                            GSTIN</label>
                        <input type="text" name="party_gst" x-model="formData.party_gst" placeholder="Optional"
                            class="w-full border border-gray-300 rounded px-3 py-2.5 text-sm focus:border-brand-500 outline-none uppercase">
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Destination
                            State</label>
                        <div @change="formData.to_state_id = $event.target.value">
                            <?php if (isset($component)) { $__componentOriginalc933793160c9c2655e76e4334d02687c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc933793160c9c2655e76e4334d02687c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.custom-select','data' => ['name' => 'to_state_id','placeholder' => 'Select State','options' => $stateOptions,'selected' => old('to_state_id', '')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('custom-select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'to_state_id','placeholder' => 'Select State','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($stateOptions),'selected' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('to_state_id', ''))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc933793160c9c2655e76e4334d02687c)): ?>
<?php $attributes = $__attributesOriginalc933793160c9c2655e76e4334d02687c; ?>
<?php unset($__attributesOriginalc933793160c9c2655e76e4334d02687c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc933793160c9c2655e76e4334d02687c)): ?>
<?php $component = $__componentOriginalc933793160c9c2655e76e4334d02687c; ?>
<?php unset($__componentOriginalc933793160c9c2655e76e4334d02687c); ?>
<?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                    <h2 class="text-sm font-bold text-gray-800 uppercase tracking-wider">Logistics & Tracking</h2>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Transporter
                            Name</label>
                        <input type="text" name="transport_name" value="<?php echo e(old('transport_name')); ?>"
                            placeholder="e.g. BlueDart"
                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:border-brand-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Vehicle /
                            Courier No.</label>
                        <input type="text" name="vehicle_number" value="<?php echo e(old('vehicle_number')); ?>"
                            placeholder="GJ01XX1234"
                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:border-brand-500 outline-none uppercase">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">E-Way Bill
                            Number</label>
                        <input type="text" name="eway_bill_number" value="<?php echo e(old('eway_bill_number')); ?>"
                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:border-brand-500 outline-none">
                    </div>

                    
                    <div class="p-3 bg-blue-50 border border-blue-100 rounded-lg flex flex-col justify-center">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_returnable" value="1" x-model="formData.is_returnable"
                                class="w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500">
                            <span class="text-xs font-bold text-blue-800 uppercase tracking-wider">Goods are
                                Returnable</span>
                        </label>
                        <div x-show="formData.is_returnable" x-collapse class="mt-3">
                            <label class="block text-[10px] font-bold text-blue-600 uppercase tracking-wider mb-1">Return
                                Due Date</label>
                            <input type="date" name="return_due_date" value="<?php echo e(old('return_due_date')); ?>"
                                class="w-full border border-blue-200 rounded px-2 py-1.5 text-xs focus:border-blue-500 outline-none">
                        </div>
                    </div>
                </div>
            </div>

            
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6 flex flex-col">
                <div
                    class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <h2 class="text-lg font-bold text-gray-800 tracking-tight">Challan Items <span
                            class="text-red-500">*</span></h2>

                    
                    <div class="relative w-full sm:max-w-md md:max-w-lg lg:max-w-2xl" x-data="{ showResults: false }">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i data-lucide="search" class="w-4 h-4 text-gray-400"></i>
                        </div>
                        <input type="text" x-model="globalSearch" @input.debounce.300ms="fetchGlobalSkus()"
                            @focus="showResults = true" @click.away="showResults = false"
                            placeholder="Type product name or scan barcode..."
                            class="w-full border border-gray-300 rounded shadow-sm pl-9 pr-4 py-2.5 text-sm focus:border-brand-500 outline-none bg-white">

                        <ul x-show="showResults && globalSearch.length > 1" x-cloak
                            class="absolute z-[60] w-full bg-white border border-gray-200 rounded-lg shadow-2xl mt-1 max-h-60 overflow-y-auto overscroll-contain top-full left-0 custom-scrollbar">

                            <li x-show="isSearching"
                                class="px-4 py-3 text-xs text-gray-500 text-center font-medium flex items-center justify-center gap-2">
                                <svg class="animate-spin h-4 w-4 text-[#108c2a]" xmlns="http://www.w3.org/2000/svg"
                                    fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                        stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                Searching inventory...
                            </li>

                            <li x-show="!isSearching && globalSearchResults.length === 0" class="px-4 py-4 text-center">
                                <span class="block text-sm font-bold text-gray-700">No products found</span>
                            </li>

                            <template x-for="result in globalSearchResults" :key="result.product_sku_id">
                                <li @click="addSkuToTable(result); showResults = false"
                                    class="px-4 py-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-0 transition-colors">
                                    <div class="flex justify-between items-start gap-2">
                                        <div class="text-[13px] font-bold text-gray-800 leading-tight"
                                            x-text="result.display_name || result.product_name"></div>
                                        <div class="text-[12px] font-black text-[#108c2a] flex-shrink-0"
                                            x-text="result.price ? '₹' + parseFloat(result.price).toFixed(2) : '₹0.00'">
                                        </div>
                                    </div>
                                    <div
                                        class="text-[11px] text-gray-500 flex items-center flex-wrap gap-1.5 mt-1.5 font-medium">
                                        <span>Code:</span>
                                        <span
                                            class="bg-gray-100 border border-gray-200 text-gray-600 px-1 py-0.5 rounded font-mono text-[10px] font-bold tracking-wide"
                                            x-text="result.sku_code"></span>
                                        <span class="text-gray-300">&middot;</span>
                                        <span x-text="'Stock: ' + (result.stock || 0)"></span>
                                    </div>
                                </li>
                            </template>
                        </ul>
                    </div>
                </div>

                <div class="overflow-x-auto min-h-[200px]">
                    <table class="w-full text-left border-collapse">
                        <thead
                            class="bg-gray-50 border-b border-gray-200 text-[11px] font-bold text-gray-500 uppercase tracking-wider">
                            <tr>
                                <th class="px-5 py-4 min-w-[250px]">PRODUCT DETAILS</th>
                                <th class="px-4 py-4 min-w-[100px] text-center">HSN/SAC</th>
                                <?php if(batch_enabled()): ?>
                                    <th class="px-4 py-4 min-w-[130px] text-center">BATCH #</th>
                                    <th class="px-4 py-4 min-w-[130px] text-center">EXPIRY DATE</th>
                                <?php endif; ?>
                                <th class="px-4 py-4 min-w-[140px] text-right">UNIT PRICE</th>
                                <th class="px-4 py-4 min-w-[140px] text-center">QTY</th>
                                <th class="px-4 py-4 min-w-[110px] text-right">TAX %</th>
                                <th class="px-5 py-4 min-w-[140px] text-right">VALUE</th>
                                <th class="px-4 py-4 w-[60px] text-center"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template x-for="(item, index) in items" :key="item.key">
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    
                                    <td class="px-5 py-3 align-middle">
                                        <div class="text-[13px] font-bold text-gray-800 leading-tight"
                                            x-text="item.display_name || item.product_name"></div>
                                        <div class="flex items-center gap-1.5 mt-1.5">
                                            <span class="text-gray-500 text-[11px] font-medium">Code:</span>
                                            <span
                                                class="bg-[#dcfce7] text-[#16a34a] text-[10px] px-1.5 py-0.5 rounded font-mono font-bold tracking-wide border border-green-200"
                                                x-text="item.sku_code"></span>
                                        </div>

                                        
                                        <input type="hidden" :name="'items[' + index + '][product_sku_id]'"
                                            :value="item.product_sku_id">
                                        <input type="hidden" :name="'items[' + index + '][product_id]'"
                                            :value="item.product_id">
                                        <input type="hidden" :name="'items[' + index + '][unit_id]'"
                                            :value="item.unit_id">
                                        <input type="hidden" :name="'items[' + index + '][product_name]'"
                                            :value="item.product_name">
                                        <input type="hidden" :name="'items[' + index + '][display_name]'"
                                            :value="item.display_name">
                                        <input type="hidden" :name="'items[' + index + '][sku_code]'"
                                            :value="item.sku_code">
                                        <input type="hidden" :name="'items[' + index + '][hsn_code]'"
                                            :value="item.hsn_code">
                                    </td>

                                    
                                    <td class="px-4 py-3 text-center align-middle">
                                        <span class="text-[12px] font-mono text-gray-600"
                                            x-text="item.hsn_code || '-'"></span>
                                    </td>

                                    <?php if(batch_enabled()): ?>
                                        
                                        <td class="px-4 py-3 align-middle">
                                            <input type="text" :name="'items[' + index + '][batch_number]'"
                                                x-model="item.batch_number" placeholder="e.g. BT-001"
                                                class="w-full min-w-[100px] h-10 md:h-9 border border-gray-300 rounded px-2 text-[12px] font-mono focus:border-brand-500 outline-none text-gray-700 shadow-sm transition-all bg-white">
                                        </td>

                                        
                                        <td class="px-4 py-3 align-middle">
                                            <input type="date" :name="'items[' + index + '][expiry_date]'"
                                                x-model="item.expiry_date"
                                                class="w-full min-w-[120px] h-10 md:h-9 border border-gray-300 rounded px-2 text-[12px] focus:border-brand-500 outline-none text-gray-700 shadow-sm transition-all bg-white">
                                        </td>
                                    <?php endif; ?>

                                    
                                    <td class="px-4 py-3 align-middle">
                                        <div class="relative w-full min-w-[100px]">
                                            <span
                                                class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm font-bold">₹</span>
                                            <input type="text" inputmode="numeric" pattern="[0-9]*"
                                                :name="'items[' + index + '][unit_price]'" x-model="item.unit_price"
                                                @input="item.unit_price = window.sanitizeInteger($event); calculate();"
                                                @blur="item.unit_price = window.commitInteger(item.unit_price, 0); calculate();"
                                                class="w-full h-10 md:h-9 border border-gray-300 rounded px-2 pl-7 text-sm focus:border-brand-500 outline-none font-bold text-gray-700 text-right shadow-sm transition-all bg-white">
                                        </div>
                                    </td>

                                    
                                    <td class="px-4 py-3 align-middle">
                                        <div class="flex items-center justify-center min-w-[120px]">
                                            <button type="button"
                                                @click="item.qty_sent = Math.max(1, parseFloat(item.qty_sent || 0) - 1); calculate()"
                                                class="w-10 h-10 md:w-8 md:h-9 border border-gray-300 rounded-l flex items-center justify-center bg-gray-50 hover:bg-gray-100 text-gray-600 active:bg-gray-200 transition-colors">-</button>
                                            <input type="text" inputmode="numeric" pattern="[0-9]*"
                                                :name="'items[' + index + '][qty_sent]'" x-model="item.qty_sent"
                                                @input="item.qty_sent = window.sanitizeInteger($event); calculate();"
                                                @blur="item.qty_sent = window.commitInteger(item.qty_sent, 1); calculate();"
                                                class="w-16 h-10 md:h-9 border-y border-x-0 border-gray-300 text-center text-sm font-bold focus:ring-0 focus:border-brand-500 outline-none p-0 text-gray-700 shadow-inner">
                                            <button type="button"
                                                @click="item.qty_sent = parseFloat(item.qty_sent || 0) + 1; calculate()"
                                                class="w-10 h-10 md:w-8 md:h-9 border border-gray-300 rounded-r flex items-center justify-center bg-gray-50 hover:bg-gray-100 text-gray-600 active:bg-gray-200 transition-colors">+</button>
                                        </div>
                                    </td>

                                    
                                    <td class="px-4 py-3 align-middle text-right">
                                        <div class="relative inline-block w-full min-w-[80px]">
                                            
                                            <input type="text" inputmode="numeric"
                                                :name="'items[' + index + '][tax_rate]'" x-model="item.tax_rate"
                                                @input="item.tax_rate = window.sanitizeInteger($event, { max: 100 }); calculate();"
                                                @blur="item.tax_rate = window.commitInteger(item.tax_rate, 0); calculate();"
                                                class="w-full h-10 md:h-9 border border-gray-300 rounded px-2 pr-6 text-sm focus:border-brand-500 outline-none font-bold text-gray-700 text-right shadow-sm transition-all bg-white">
                                            <span
                                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm font-bold">%</span>
                                        </div>
                                    </td>

                                    
                                    <td class="px-5 py-3 text-right align-middle max-w-[140px]">
                                        <span class="font-black text-gray-800 text-[14px] block truncate"
                                            :title="formatCurrency(item.line_value)"
                                            x-text="formatCurrency(item.line_value)"></span>
                                    </td>

                                    
                                    <td class="px-4 py-3 text-center align-middle">
                                        <button type="button" @click="removeItem(index)"
                                            class="text-red-400 hover:text-red-600 hover:bg-red-50 p-1.5 rounded transition-colors"
                                            title="Remove Item">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="items.length === 0" x-cloak>
                                <td colspan="<?php echo e(batch_enabled() ? 9 : 7); ?>" class="px-6 py-12 text-center">
                                    <p class="text-sm font-medium text-gray-400">Search and add a product to begin.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-10">
                <div class="lg:col-span-2 bg-white rounded-lg shadow-sm border border-gray-200 p-6 flex flex-col gap-4">
                    <h3 class="text-xs font-bold text-gray-800 uppercase tracking-wider pb-3">Notes & Reference
                    </h3>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Purpose
                                of Challan</label>
                            <textarea name="purpose_note" rows="2" placeholder="e.g. Sent for quality testing..."
                                class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:border-brand-500 outline-none resize-none"><?php echo e(old('purpose_note')); ?></textarea>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Internal
                                Notes</label>
                            <textarea name="internal_notes" rows="2" placeholder="Private notes..."
                                class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:border-brand-500 outline-none resize-none"><?php echo e(old('internal_notes')); ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3
                        class="text-xs font-bold text-gray-800 uppercase tracking-wider border-b border-gray-200 pb-3 mb-4">
                        Summary (Indicative)</h3>
                    <div class="space-y-3 text-sm text-gray-600">
                        <div class="flex justify-between items-center">
                            <span class="font-semibold">Total Quantity:</span>
                            <span class="font-bold text-gray-800" x-text="totals.total_qty"></span>
                        </div>
                        <div class="flex justify-between items-center pt-2 border-t border-gray-100">
                            <span class="font-semibold">Total Tax Amount:</span>
                            <span class="font-bold text-gray-800" x-text="formatCurrency(totals.total_tax)"></span>
                        </div>
                        <div class="flex justify-between items-end pt-2">
                            <div class="text-right w-full">
                                <div class="text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Total
                                    Indicative Value</div>
                                <div class="text-2xl font-black text-[#108c2a]"
                                    x-text="formatCurrency(totals.total_value)"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            
            <div
                class="bg-white border border-gray-200 p-5 rounded-lg flex flex-col sm:flex-row justify-end items-stretch sm:items-center gap-4 shadow-sm">
                <a href="<?php echo e(route('admin.challans.index')); ?>"
                    class="bg-white border border-gray-300 text-gray-700 px-6 py-2.5 rounded-lg font-bold text-sm hover:bg-gray-50 transition-colors text-center">
                    CANCEL
                </a>
                <button type="submit"
                    :class="formData.status === 'dispatched' ? 'bg-orange-600 hover:bg-orange-700' :
                        'bg-[#108c2a] hover:bg-[#0c6b1f]'"
                    class="text-white px-8 py-2.5 rounded-lg text-sm font-bold transition-all shadow-md active:scale-95 flex items-center justify-center gap-2">
                    <i :data-lucide="formData.status === 'dispatched' ? 'send' : 'save'" class="w-4 h-4"></i>
                    <span x-text="formData.status === 'dispatched' ? 'Dispatch Now' : 'Save as Draft'"></span>
                </button>
            </div>
        </form>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
    <script>
        function challanForm(allUnits = [], allClients = [], allSuppliers = [], allStores = [], allWarehouses = []) {
            return {
                units: allUnits,
                clients: allClients,
                suppliers: allSuppliers,
                stores: allStores,
                warehouses: allWarehouses,
                // 🌟 Map default warehouses to stores
                storeDefaults: <?php echo json_encode(collect($warehouses ?? [])->where('is_default', 1)->pluck('id', 'store_id')) ?>,

                partyList: [], // Dynamically populated based on challan type

                items: [],
                itemCounter: 0,

                globalSearch: '',
                isSearching: false,
                globalSearchResults: [],

                isGuest: false,
                partySearchTerm: '',
                isPartyDropdownOpen: false,

                formData: {
                    challan_type: "<?php echo e(old('challan_type', '')); ?>", // 🌟 Wrap in old()
                    status: "<?php echo e(old('status', 'draft')); ?>",
                    direction: "<?php echo e(old('direction', 'outward')); ?>",
                    challan_date: "<?php echo e(old('challan_date', now()->format('Y-m-d'))); ?>",
                    store_id: "<?php echo e(old('store_id', session('store_id', auth()->user()->store_id ?? ''))); ?>",
                    warehouse_id: "<?php echo e(old('warehouse_id', '')); ?>",
                    to_warehouse_id: "<?php echo e(old('to_warehouse_id', '')); ?>", // 🌟 Bind to old value

                    party_id: "<?php echo e(old('party_id', '')); ?>",
                    party_name: "<?php echo e(old('party_name', '')); ?>",
                    party_gst: "<?php echo e(old('party_gst', '')); ?>",
                    to_state_id: "<?php echo e(old('to_state_id', '')); ?>",
                    is_returnable: <?php echo e(old('is_returnable') ? 'true' : 'false'); ?>,
                },

                totals: {
                    total_qty: 0,
                    total_tax: 0,
                    total_value: 0
                },

                init() {
                    // Auto-sync warehouse on load
                    if (!this.formData.warehouse_id) {
                        this.autoSelectWarehouse();
                    }
                    this.calculate();
                },

                autoSelectWarehouse() {
                    let sId = String(this.formData.store_id);
                    if (this.storeDefaults[sId]) {
                        this.formData.warehouse_id = String(this.storeDefaults[sId]);
                    } else {
                        this.formData.warehouse_id = '';
                    }
                    // 🌟 Custom Select UI update trigger
                    window.dispatchEvent(new CustomEvent('set-warehouse', {
                        detail: this.formData.warehouse_id
                    }));
                },

                // 🌟 Dynamically compute the correct <select name="..."> based on challan type
                get partyFieldName() {
                    if (this.formData.challan_type === 'delivery') return 'client_id';
                    if (this.formData.challan_type === 'job_work_out' || this.formData.challan_type === 'repair_out')
                        return 'supplier_id';
                    if (this.formData.challan_type === 'branch_transfer') return 'branch_store_id';
                    return 'client_id'; // fallback
                },

                get isPartyRequired() {
                    return ['delivery', 'job_work_out', 'repair_out', 'branch_transfer'].includes(this.formData
                        .challan_type);
                },
                // 🌟 NEW: Filter the list based on what the user types
                get filteredPartyList() {
                    if (this.partySearchTerm.trim() === '') {
                        return this.partyList;
                    }
                    return this.partyList.filter(party => {
                        return party.name.toLowerCase().includes(this.partySearchTerm.toLowerCase());
                    });
                },

                handleTypeChange() {
                    let type = this.formData.challan_type;
                    this.formData.to_warehouse_id = '';
                    this.formData.party_id = '';
                    this.formData.party_name = '';
                    this.formData.party_gst = '';
                    this.partySearchTerm = '';
                    this.isGuest = false;

                    // 🌟 Clear Custom Dropdown UI for Destination Warehouse
                    window.dispatchEvent(new CustomEvent('set-to-warehouse', {
                        detail: ''
                    }));

                    // Auto-set returnable for certain types
                    const returnableTypes = ['job_work_out', 'sale_on_approval', 'consignment', 'repair_out', 'exhibition',
                        'returnable'
                    ];
                    this.formData.is_returnable = returnableTypes.includes(type);

                    // Populate correct list
                    if (type === 'delivery' || type === 'sale_on_approval' || type === 'consignment') {
                        this.partyList = this.clients;
                    } else if (type === 'job_work_out' || type === 'repair_out') {
                        this.partyList = this.suppliers;
                    } else if (type === 'branch_transfer') {
                        // Filter out the currently selected store
                        this.partyList = this.stores.filter(s => s.id != this.formData.store_id);
                    } else {
                        this.partyList = [];
                        this.isGuest = true; // Force manual entry if type is unknown/custom
                    }
                },

                // 🌟 NEW: Handle clicking an item in the dropdown
                selectParty(party) {
                    this.formData.party_id = party.id;
                    this.partySearchTerm = party.name; // Put the name inside the input box

                    // Update Snapshot data
                    this.formData.party_name = party.name || '';
                    this.formData.party_gst = party.gst_number || party.gstin || '';

                    // Close the dropdown
                    this.isPartyDropdownOpen = false;
                },

                async fetchGlobalSkus() {
                    let warehouseId = this.formData.warehouse_id;
                    if (!warehouseId) {
                        BizAlert.toast('Please select a warehouse first', 'error');
                        this.globalSearch = '';
                        return;
                    }

                    if (this.globalSearch.length < 2) return;
                    this.isSearching = true;

                    try {
                        let response = await fetch(
                            `<?php echo e(route('admin.api.challans.search-skus')); ?>?term=${encodeURIComponent(this.globalSearch)}&warehouse_id=${warehouseId}`
                        );
                        if (!response.ok) throw new Error("Search failed");
                        this.globalSearchResults = await response.json();
                    } catch (error) {
                        console.error(error);
                    } finally {
                        this.isSearching = false;
                    }
                },

                addSkuToTable(result) {
                    // Prevent duplicate items in the table
                    if (this.items.some(item => item.product_sku_id === result.product_sku_id)) {
                        BizAlert.toast('This product is already added!', 'error');
                        this.globalSearch = '';
                        this.showResults = false;
                        return;
                    }

                    this.items.push({
                        key: this.itemCounter++,
                        product_id: result.product_id,
                        product_sku_id: result.product_sku_id,
                        unit_id: result.unit_id,
                        product_name: result.product_name,
                        display_name: result.display_name || result.product_name, // 🌟 Map the variant name
                        sku_code: result.sku_code,
                        hsn_code: result.hsn_code || '',
                        batch_number: '',
                        expiry_date: '',
                        qty_sent: 1,
                        unit_price: parseFloat(result.price) || 0,
                        tax_rate: parseFloat(result.tax_percent) || 0,
                        line_value: 0
                    });
                    this.globalSearch = '';
                    this.calculate();
                },

                removeItem(index) {
                    this.items.splice(index, 1);
                    this.calculate();
                },

                handleFormSubmit(event) {
                    // 1. Validate Challan Type
                    if (!this.formData.challan_type) {
                        event.preventDefault();
                        BizAlert.toast('Please select a Challan Type.', 'error');
                        if (typeof window.focusCustomSelect === 'function') {
                            window.focusCustomSelect('challan_type');
                        }
                        return false;
                    }

                    // 2. Validate Origin Store and Stock Warehouse
                    if (!this.formData.store_id) {
                        event.preventDefault();
                        BizAlert.toast('Please select an Origin Store.', 'error');
                        if (typeof window.focusCustomSelect === 'function') {
                            window.focusCustomSelect('store_id');
                        }
                        return false;
                    }

                    if (!this.formData.warehouse_id) {
                        event.preventDefault();
                        BizAlert.toast('Please select a Stock Warehouse.', 'error');
                        if (typeof window.focusCustomSelect === 'function') {
                            window.focusCustomSelect('warehouse_id');
                        }
                        return false;
                    }

                    // 3. Validate Destination Warehouse for Branch Transfers
                    if (this.formData.challan_type === 'branch_transfer') {
                        if (!this.formData.to_warehouse_id) {
                            event.preventDefault();
                            BizAlert.toast('Please select a Destination Warehouse for branch transfer.', 'error');
                            if (typeof window.focusCustomSelect === 'function') {
                                window.focusCustomSelect('to_warehouse_id');
                            }
                            return false;
                        }

                        if (String(this.formData.warehouse_id) === String(this.formData.to_warehouse_id)) {
                            event.preventDefault();
                            BizAlert.toast('Origin and Destination warehouses cannot be the same.', 'error');
                            return false;
                        }
                    }

                    // 4. Validate Party Selection / Manual Entry
                    if (this.isPartyRequired) {
                        if (!this.isGuest && !this.formData.party_id) {
                            event.preventDefault();
                            BizAlert.toast('Please select a party for this challan.', 'error');
                            const partyInput = document.querySelector('input[x-model="partySearchTerm"]');
                            if (partyInput) partyInput.focus();
                            return false;
                        }

                        if (this.isGuest && !this.formData.party_name.trim()) {
                            event.preventDefault();
                            BizAlert.toast('Please enter a party name.', 'error');
                            const manualPartyInput = document.querySelector('input[name="party_name"]');
                            if (manualPartyInput) manualPartyInput.focus();
                            return false;
                        }
                    }

                    // 5. Validate at least one item added
                    if (!this.items || this.items.length === 0) {
                        event.preventDefault();
                        BizAlert.toast('You must add at least one product to create a challan.', 'error');
                        const searchInput = document.querySelector('input[placeholder*="Type product name"]');
                        if (searchInput) searchInput.focus();
                        return false;
                    }

                    // 6. Validate Item Quantities and Prices
                    for (let i = 0; i < this.items.length; i++) {
                        const item = this.items[i];
                        const qty = parseFloat(item.qty_sent) || 0;
                        const price = parseFloat(item.unit_price) || 0;

                        if (qty <= 0) {
                            event.preventDefault();
                            BizAlert.toast(`Invalid quantity for item "${item.display_name || item.product_name}".`,
                                'error');
                            return false;
                        }

                        if (price < 0) {
                            event.preventDefault();
                            BizAlert.toast(
                                `Unit price cannot be negative for item "${item.display_name || item.product_name}".`,
                                'error');
                            return false;
                        }
                    }

                    // All checks passed -> Show loading indicator
                    BizAlert.loading(this.formData.status === 'dispatched' ? 'Dispatching Challan...' :
                        'Generating Challan...');
                },

                calculate() {
                    let totalQty = 0;
                    let totalTax = 0;
                    let totalValue = 0;

                    this.items.forEach(item => {
                        let qty = parseFloat(item.qty_sent) || 0;
                        let price = parseFloat(item.unit_price) || 0;
                        let taxRate = parseFloat(item.tax_rate) || 0;

                        let baseValue = qty * price;
                        let taxValue = baseValue * (taxRate / 100);

                        item.line_value = baseValue + taxValue;

                        totalQty += qty;
                        totalTax += taxValue;
                        totalValue += item.line_value;
                    });

                    this.totals.total_qty = totalQty;
                    this.totals.total_tax = totalTax;
                    this.totals.total_value = totalValue;
                },

                formatCurrency(val) {
                    return '₹' + parseFloat(val).toLocaleString('en-IN', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }
            }
        }
    </script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\qlinkongraphics\Desktop\MyLab\plantiq-local\resources\views/admin/challans/create.blade.php ENDPATH**/ ?>