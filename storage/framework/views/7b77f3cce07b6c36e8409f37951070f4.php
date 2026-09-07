

<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'name' => '',
    'options' => [],
    'selected' => null,
    'placeholder' => 'Select',
    'required' => false,
    'disabled' => false,
    'icons' => [],
    'id' => null,
    'onchange' => null,
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
    'name' => '',
    'options' => [],
    'selected' => null,
    'placeholder' => 'Select',
    'required' => false,
    'disabled' => false,
    'icons' => [],
    'id' => null,
    'onchange' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $id = $id ?? $name;
    $currentValue = old($name, $selected);
    $listboxId = $id . '-listbox-' . substr(md5($id . microtime()), 0, 6);
?>

<div
    x-data="customSelect({
        options: <?php echo \Illuminate\Support\Js::from($options)->toHtml() ?>,
        icons: <?php echo \Illuminate\Support\Js::from($icons)->toHtml() ?>,
        initial: <?php echo \Illuminate\Support\Js::from((string) ($currentValue ?? ''))->toHtml() ?>,
        placeholder: <?php echo \Illuminate\Support\Js::from($placeholder)->toHtml() ?>,
    })"
    class="relative w-full"
    @keydown.escape.window="open = false"
>
    
    <select
        name="<?php echo e($name); ?>"
        id="<?php echo e($id); ?>"
        x-ref="nativeSelect"
        x-model="value"
        <?php echo e($required ? 'required' : ''); ?>

        <?php echo e($disabled ? 'disabled' : ''); ?>

        <?php if($onchange): ?> onchange="<?php echo e($onchange); ?>" <?php endif; ?>
        class="sr-only absolute pointer-events-none"
        tabindex="-1"
        aria-hidden="true"
    >
        <option value=""><?php echo e($placeholder); ?></option>
        <?php $__currentLoopData = $options; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $val => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($val); ?>"><?php echo e($label); ?></option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select>

    
    <button
        type="button"
        id="<?php echo e($listboxId); ?>-trigger"
        @click="!<?php echo e($disabled ? 'true' : 'false'); ?> && (open = !open)"
        @click.away="open = false"
        :aria-expanded="open"
        :disabled="<?php echo e($disabled ? 'true' : 'false'); ?>"
        role="combobox"
        aria-haspopup="listbox"
        aria-controls="<?php echo e($listboxId); ?>"
        <?php echo e($attributes->merge([
            'class' => 'w-full flex items-center justify-between gap-2 border border-gray-200 rounded-lg px-3 py-2.5 text-sm text-left bg-white outline-none transition-all cursor-pointer hover:border-gray-300 focus:border-[#108c2a] focus:ring-1 focus:ring-[#108c2a] disabled:opacity-50 disabled:cursor-not-allowed disabled:bg-gray-50'
        ])); ?>

        :class="open ? 'border-[#108c2a] ring-1 ring-[#108c2a]' : ''"
    >
        <span class="flex items-center gap-2 truncate">
            <template x-if="icons[value]">
                <i :data-lucide="icons[value]" class="w-4 h-4 text-gray-500 shrink-0"></i>
            </template>
            <span :class="value === '' ? 'text-gray-400' : 'text-gray-800 font-medium'" x-text="label" class="truncate"></span>
        </span>
        <i data-lucide="chevron-down" class="w-4 h-4 text-gray-400 shrink-0 transition-transform" :class="open ? 'rotate-180' : ''"></i>
    </button>

    
    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        id="<?php echo e($listboxId); ?>"
        role="listbox"
        class="absolute z-50 mt-1.5 min-w-full w-max max-w-[280px] max-h-64 overflow-y-auto bg-white border border-gray-100 rounded-xl shadow-2xl py-1.5 left-0 origin-top-left [&::-webkit-scrollbar]:w-1.5 [&::-webkit-scrollbar-thumb]:bg-gray-200 [&::-webkit-scrollbar-thumb]:rounded-full hover:[&::-webkit-scrollbar-thumb]:bg-gray-300"
        style="display: none;"
    >
        <button
            type="button"
            role="option"
            @click="select('')"
            class="w-full flex items-center gap-2 px-4 py-2.5 text-sm text-left transition-colors whitespace-nowrap"
            :class="value === '' ? 'bg-[#108c2a]/10 text-[#108c2a] font-bold' : 'text-gray-600 hover:bg-gray-50'"
        >
            <span x-text="placeholder"></span>
        </button>

        <template x-for="opt in optionList" :key="opt.value">
            <button
                type="button"
                role="option"
                @click="select(opt.value)"
                class="w-full flex items-center gap-2 px-4 py-2.5 text-sm text-left transition-colors whitespace-nowrap"
                :class="value === opt.value ? 'bg-[#108c2a]/10 text-[#108c2a] font-bold' : 'text-gray-700 hover:bg-gray-50'"
            >
                <template x-if="icons[opt.value]">
                    <i :data-lucide="icons[opt.value]" class="w-4 h-4 shrink-0" :class="value === opt.value ? 'text-[#108c2a]' : 'text-gray-400'"></i>
                </template>
                <span x-text="opt.label"></span>
            </button>
        </template>
    </div>
</div><?php /**PATH C:\Users\qlinkongraphics\Desktop\MyLab\plantiq-local\resources\views/components/custom-select.blade.php ENDPATH**/ ?>