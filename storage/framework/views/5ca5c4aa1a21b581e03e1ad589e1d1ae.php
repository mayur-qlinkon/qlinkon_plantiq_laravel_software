<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    
    
    <div class="flex items-center gap-2 text-sm text-gray-400 font-medium">
        <?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php if($index > 0): ?>
                <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
            <?php endif; ?>

            <?php if(isset($item['url'])): ?>
                <a href="<?php echo e($item['url']); ?>" class="hover:text-brand-600 transition-colors">
                    <?php echo e($item['label']); ?>

                </a>
            <?php else: ?>
                <span class="text-gray-700 font-semibold truncate max-w-[200px]">
                    <?php echo e($item['label']); ?>

                </span>
            <?php endif; ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    
    <div class="flex items-center gap-2">
        <?php echo e($right ?? ''); ?>

    </div>

</div><?php /**PATH C:\Users\qlinkongraphics\Desktop\MyLab\plantiq-local\resources\views/components/admin/breadcrumb.blade.php ENDPATH**/ ?>