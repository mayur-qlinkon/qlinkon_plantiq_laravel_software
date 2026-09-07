
<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'type' => null,
    'types' => null,
    'label' => 'Bulk Import',
    'triggerClass' =>
        'inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-bold whitespace-nowrap text-gray-700 shadow-sm transition-colors hover:bg-gray-50',
]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([
    'type' => null,
    'types' => null,
    'label' => 'Bulk Import',
    'triggerClass' =>
        'inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-bold whitespace-nowrap text-gray-700 shadow-sm transition-colors hover:bg-gray-50',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $registry = app(\App\Imports\ImportTypeRegistry::class);

    // Product images is not an ImportType — that contract is built around CSV
    // rows. It is described here instead of being forced into the registry.
    $zipTypes = [
        'product_images' => [
            'key' => 'product_images',
            'segment' => 'product-images',
            'label' => 'Product Images',
            'icon' => 'image',
            'panel' => 'admin.bulk-import._panel-zip',
            'permission' => 'images_import',
            'isZip' => true,
            'planLimited' => false,
        ],
    ];

    $requested = $types ?? array_filter([$type]);
    $entries = [];

    foreach ($requested as $key) {
        if (isset($zipTypes[$key])) {
            $entry = $zipTypes[$key];
        } else {
            $importType = $registry->get($key);
            $entry = [
                'key' => $importType->key(),
                'segment' => $importType->key(),
                'label' => $importType->label(),
                'icon' => 'file-spreadsheet',
                'panel' => 'admin.bulk-import._panel',
                'permission' => $importType->permission(),
                'isZip' => false,
                'planLimited' => $importType->isPlanLimited(),
                'type' => $importType,
            ];
        }

        if ($entry['permission'] !== null && !has_permission($entry['permission'])) {
            continue;
        }

        $entries[] = $entry;
    }

    // The product cap is the only plan limit any import cares about, so it is
    // resolved once here rather than per entry.
    $productLimit = null;
    $productCount = 0;
    $limitReached = false;

    if (collect($entries)->contains('planLimited', true)) {
        $companyId = auth()->user()->company_id;
        $productCount = \App\Models\Product::withoutGlobalScopes()->where('company_id', $companyId)->count();
        $productLimit = auth()->user()->company->subscription?->plan?->product_limit;
        $limitReached = $productLimit !== null && $productCount >= $productLimit;
    }

    $maxBytes = max_upload_bytes();
?>

<?php if(has_module('bulk_import') && $entries): ?>
    <?php echo $__env->make('admin.bulk-import._engine-script', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    
    <?php if(count($entries) === 1): ?>
        <button type="button" class="<?php echo e($triggerClass); ?>" @click="$dispatch('open-import', '<?php echo e($entries[0]['key']); ?>')">
            <i data-lucide="upload-cloud" class="h-4 w-4 text-[var(--brand-600)]"></i>
            <?php echo e($label); ?>

        </button>
    <?php else: ?>
        <div class="relative" x-data="{ menuOpen: false }" @click.outside="menuOpen = false">
            <button type="button" class="<?php echo e($triggerClass); ?>" @click="menuOpen = !menuOpen">
                <i data-lucide="upload-cloud" class="h-4 w-4 text-[var(--brand-600)]"></i>
                <?php echo e($label); ?>

                <i data-lucide="chevron-down" class="h-3.5 w-3.5 opacity-50 transition-transform"
                    :class="menuOpen ? 'rotate-180' : ''"></i>
            </button>

            <div x-show="menuOpen" x-cloak x-transition
                class="absolute right-0 z-20 mt-1 w-56 overflow-hidden rounded-lg border border-gray-100 bg-white shadow-lg">
                <?php $__currentLoopData = $entries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $entry): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <button type="button" @click="menuOpen = false; $dispatch('open-import', '<?php echo e($entry['key']); ?>')"
                        class="flex w-full items-center gap-2.5 px-4 py-2.5 text-left text-[13px] font-bold text-gray-700 transition-colors hover:bg-gray-50">
                        <i data-lucide="<?php echo e($entry['icon']); ?>" class="h-4 w-4 text-gray-400"></i>
                        <?php echo e($entry['label']); ?>

                    </button>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
    <?php endif; ?>

    
    <?php $__currentLoopData = $entries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $entry): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div x-data="importModal({
            type: '<?php echo e($entry['segment']); ?>',
            isZip: <?php echo e($entry['isZip'] ? 'true' : 'false'); ?>,
            maxBytes: <?php echo e($maxBytes); ?>,
            productLimit: <?php echo e($entry['planLimited'] ? $productLimit ?? 'null' : 'null'); ?>,
            productCount: <?php echo e($entry['planLimited'] ? $productCount : 0); ?>,
            limitExceeded: <?php echo e($entry['planLimited'] && $limitReached ? 'true' : 'false'); ?>

        })" @open-import.window="if ($event.detail === '<?php echo e($entry['key']); ?>') openModal()">

            <div x-show="open" x-cloak @keydown.escape.window="requestClose()"
                class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto p-4 sm:p-6">

                <div x-show="open" x-transition.opacity @click="requestClose()" class="fixed inset-0 bg-gray-900/50">
                </div>

                <div x-show="open" x-transition class="relative my-8 w-full max-w-2xl">
                    <button type="button" @click="requestClose()"
                        class="absolute -top-3 -right-3 z-10 flex h-8 w-8 items-center justify-center rounded-full border border-gray-200 bg-white shadow-sm transition-colors hover:bg-gray-50">
                        <i data-lucide="x" class="h-4 w-4 text-gray-500"></i>
                    </button>

                    <?php echo $__env->make($entry['panel'], isset($entry['type']) ? ['type' => $entry['type']] : [], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                </div>
            </div>
        </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php endif; ?>
<?php /**PATH C:\Users\qlinkongraphics\Desktop\MyLab\plantiq-local\resources\views/components/import-modal.blade.php ENDPATH**/ ?>