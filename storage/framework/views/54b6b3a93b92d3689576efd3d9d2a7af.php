

<?php
    $helpColor = $helpColor ?? 'emerald';
    $title = $type->title();
    $columns = $type->columnHint();
    $importType = $type->key();
    $sampleType = $type->key();
    $extraLinks = $type->extraLinks();
    $hasLimit = $type->isPlanLimited();
    $depNotice = $type->dependencyNotice();
    $helpText = $type->helpText();

    $helpClasses = match ($helpColor) {
        'blue' => 'bg-blue-50 border-blue-200 text-blue-800',
        'amber' => 'bg-amber-50 border-amber-200 text-amber-800',
        default => 'bg-emerald-50 border-emerald-200 text-emerald-800',
    };
    $helpIconColor = match ($helpColor) {
        'blue' => 'text-blue-500',
        'amber' => 'text-amber-500',
        default => 'text-emerald-500',
    };

    $colorMap = [
        'blue' => 'text-blue-700 bg-blue-50 border-blue-200 hover:bg-blue-100',
        'green' => 'text-green-700 bg-green-50 border-green-200 hover:bg-green-100',
        'purple' => 'text-purple-700 bg-purple-50 border-purple-200 hover:bg-purple-100',
        'gray' => 'text-gray-700 bg-white border-gray-200 hover:bg-gray-50',
    ];
?>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">

    
    <div class="p-6" x-show="!importId">

        
        <div class="flex items-start justify-between gap-4 mb-3">
            <h2 class="text-[16px] font-black text-gray-800 leading-tight"><?php echo e($title); ?></h2>

            <a href="<?php echo e(route('admin.bulk-import.sample', $sampleType)); ?>" target="_blank"
                class="shrink-0 inline-flex items-center gap-1.5 text-[11px] font-bold px-3 py-1.5 rounded-lg border border-gray-200 text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                <i data-lucide="download" class="w-3 h-3"></i>
                Sample CSV
            </a>
        </div>

        
        <div class="mb-4">
            <p class="text-[11px] text-gray-500 mb-1.5">Your file must contain these columns:</p>
            <div class="flex flex-wrap gap-1.5">
                <?php $__currentLoopData = $type->requiredHeaders(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $header): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <code
                        class="bg-gray-100 text-gray-700 px-2 py-0.5 rounded text-[10px] font-mono"><?php echo e($header); ?></code>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
            <?php if(count($type->optionalHeaders()) > 0): ?>
                <p class="text-[11px] text-gray-400 mt-1.5">
                    <?php echo e(count($type->optionalHeaders())); ?> more optional column<?php echo e(count($type->optionalHeaders()) === 1 ? '' : 's'); ?>

                    are supported — download the sample CSV to see them all.
                </p>
            <?php endif; ?>
        </div>

        
        <?php if($extraLinks): ?>
            <div class="flex flex-wrap items-center gap-2 mb-5">
                <?php $__currentLoopData = $extraLinks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $link): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php $cls = $colorMap[$link['color']] ?? $colorMap['gray']; ?>
                    <a href="<?php echo $link['href']; ?>" target="_blank"
                        class="inline-flex items-center gap-1.5 text-[11px] font-bold px-3 py-1.5 rounded-lg border transition-colors <?php echo e($cls); ?>">
                        <i data-lucide="<?php echo e($link['icon']); ?>" class="w-3 h-3"></i>
                        <?php echo e($link['label']); ?>

                    </a>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php endif; ?>

        
        <?php if($depNotice): ?>
            <div class="flex items-start gap-2.5 bg-amber-50 border border-amber-200 rounded-lg px-4 py-3 mb-4">
                <i data-lucide="alert-triangle" class="w-4 h-4 text-amber-500 shrink-0 mt-0.5"></i>
                <p class="text-[12px] text-amber-800 leading-relaxed"><?php echo $depNotice; ?></p>
            </div>
        <?php endif; ?>

        
        <?php if($helpText): ?>
            <div class="flex items-start gap-2.5 border rounded-lg px-4 py-3 mb-5 <?php echo e($helpClasses); ?>">
                <i data-lucide="info" class="w-4 h-4 shrink-0 mt-0.5 <?php echo e($helpIconColor); ?>"></i>
                <p class="text-[12px] leading-relaxed"><?php echo $helpText; ?></p>
            </div>
        <?php endif; ?>

        

        
        <?php if($hasLimit): ?>
            <div x-show="limitExceeded && !limitExceededDetails" x-transition
                class="mb-5 bg-orange-50 border border-orange-200 rounded-xl px-4 py-3.5 flex items-start gap-3">
                <i data-lucide="shield-alert" class="w-5 h-5 text-orange-500 shrink-0 mt-0.5"></i>
                <div>
                    <p class="text-[13px] font-black text-orange-800 mb-0.5">Product limit reached</p>
                    <p class="text-[12px] text-orange-700 leading-snug">
                        Your plan allows <strong x-text="productLimit"></strong> products and
                        you currently have <strong x-text="productCount"></strong>.
                        Upgrade your plan to continue importing.
                    </p>
                </div>
            </div>

            
            <div x-show="limitExceeded && limitExceededDetails" x-transition
                class="mb-5 bg-orange-50 border border-orange-200 rounded-xl p-4">
                <div class="flex items-start gap-3">
                    <i data-lucide="shield-alert" class="w-5 h-5 text-orange-500 shrink-0 mt-0.5"></i>
                    <div class="flex-1">
                        <p class="text-[13px] font-black text-orange-800 mb-1">Product Limit Exceeded</p>
                        <p class="text-[12px] text-orange-700 leading-snug" x-text="limitExceededDetails?.error"></p>
                        <div class="flex flex-wrap gap-x-5 gap-y-1 mt-2.5 text-[11px] text-orange-700">
                            <span>Plan limit: <strong x-text="limitExceededDetails?.product_limit"></strong></span>
                            <span>Existing: <strong x-text="limitExceededDetails?.existing_count"></strong></span>
                            <span>File would add: <strong
                                    x-text="limitExceededDetails?.incoming_new_count"></strong></span>
                            <span>Available slots: <strong
                                    x-text="limitExceededDetails?.available_slots"></strong></span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        
        <div class="border-2 border-dashed rounded-xl p-8 text-center transition-colors"
            :class="<?php if($hasLimit): ?> limitExceeded
                    ? 'border-gray-100 bg-gray-50 opacity-40 pointer-events-none'
                    : (dragOver ? 'border-blue-400 bg-blue-50' : 'border-gray-200 hover:border-gray-300')
                <?php else: ?>
                    dragOver ? 'border-blue-400 bg-blue-50' : 'border-gray-200 hover:border-gray-300' <?php endif; ?>"
            @dragover.prevent="<?php if($hasLimit): ?> if(!limitExceeded) <?php endif; ?> dragOver = true"
            @dragleave.prevent="dragOver = false"
            @drop.prevent="dragOver = false; <?php if($hasLimit): ?> if(!limitExceeded) <?php endif; ?> handleFileDrop($event)">

            <i data-lucide="upload-cloud" class="w-10 h-10 text-gray-200 mx-auto mb-3"></i>
            <p class="text-[13px] font-bold text-gray-600 mb-1">Drop your CSV file here</p>
            <p class="text-[11px] text-gray-400 mb-4">or click to browse</p>

            <label
                class="inline-flex items-center gap-2 px-4 py-2 text-[12px] font-bold text-white rounded-lg cursor-pointer hover:opacity-90 transition-opacity"
                style="background: var(--brand-600)">
                <i data-lucide="file-up" class="w-3.5 h-3.5"></i>
                Choose CSV
                <input type="file" accept=".csv,text/csv" class="hidden" @change="handleFileSelect($event)">
            </label>
        </div>

        
        <?php echo $__env->make('admin.bulk-import._selected-file-bar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php echo $__env->make('admin.bulk-import._upload-error', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        <div class="mt-4 flex justify-end" x-show="selectedFile && !uploading">
            <?php if($hasLimit): ?>
                <template x-if="limitExceeded">
                    <button disabled
                        class="flex items-center gap-2 px-5 py-2.5 text-[13px] font-bold text-white rounded-lg opacity-60 cursor-not-allowed bg-orange-400">
                        <i data-lucide="shield-alert" class="w-4 h-4"></i>
                        Product Limit Reached
                    </button>
                </template>
                <template x-if="!limitExceeded">
                    <button @click="startUpload('<?php echo e($importType); ?>')"
                        class="flex items-center gap-2 px-5 py-2.5 text-[13px] font-bold text-white rounded-lg hover:opacity-90 transition-opacity"
                        style="background: var(--brand-600)">
                        <i data-lucide="play" class="w-4 h-4"></i>
                        Start Import
                    </button>
                </template>
            <?php else: ?>
                <button @click="startUpload('<?php echo e($importType); ?>')"
                    class="flex items-center gap-2 px-5 py-2.5 text-[13px] font-bold text-white rounded-lg hover:opacity-90 transition-opacity"
                    style="background: var(--brand-600)">
                    <i data-lucide="play" class="w-4 h-4"></i>
                    Start Import
                </button>
            <?php endif; ?>
        </div>

        <?php echo $__env->make('admin.bulk-import._uploading-spinner', ['label' => 'Uploading…'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </div>

    
    <div class="p-6" x-show="importId" x-transition>
        <?php echo $__env->make('admin.bulk-import._progress-section', ['heading' => $title . '…'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php echo $__env->make('admin.bulk-import._stats-cards', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php echo $__env->make('admin.bulk-import._chunk-error-resume', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php echo $__env->make('admin.bulk-import._complete-panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </div>

</div>
<?php /**PATH C:\Users\qlinkongraphics\Desktop\MyLab\plantiq-local\resources\views/admin/bulk-import/_panel.blade.php ENDPATH**/ ?>