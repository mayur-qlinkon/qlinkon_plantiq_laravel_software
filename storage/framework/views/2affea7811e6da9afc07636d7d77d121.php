<?php $__env->startSection('title', 'Dashboard'); ?>

<?php $__env->startSection('header-title'); ?>
    <h1 class="text-xs font-bold tracking-widest text-gray-400 uppercase sm:text-sm">Dashboard</h1>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('styles'); ?>
    <style>
        [x-cloak] {
            display: none !important;
        }

        .cat-pill {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 7px 14px;
            border-radius: 999px;
            border: 1.5px solid #e5e7eb;
            background: #fff;
            font-size: 12.5px;
            font-weight: 700;
            color: #4b5563;
            white-space: nowrap;
            cursor: pointer;
            transition:
                background 140ms ease,
                color 140ms ease,
                border-color 140ms ease;
        }

        .cat-pill:hover {
            border-color: #d1d5db;
            background: #f9fafb;
        }

        .cat-pill.active {
            background: var(--brand-600);
            border-color: var(--brand-600);
            color: #fff;
        }

        .cat-count {
            font-size: 10.5px;
            font-weight: 800;
            padding: 1px 6px;
            border-radius: 999px;
            background: #f3f4f6;
            color: #6b7280;
        }

        .cat-pill.active .cat-count {
            background: rgba(255, 255, 255, 0.18);
            color: #fff;
        }

        .action-tile {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 13px;
            border: 1.5px solid #f1f5f9;
            border-radius: 14px;
            background: #fff;
            text-decoration: none;
            transition:
                border-color 140ms ease,
                box-shadow 140ms ease,
                transform 80ms ease;
        }

        .action-tile:hover {
            border-color: var(--brand-600);
            box-shadow: 0 4px 14px color-mix(in srgb, var(--brand-600) 12%, transparent);
        }

        .action-tile:active {
            transform: scale(0.985);
        }

        .tile-icon {
            width: 34px;
            height: 34px;
            border-radius: 10px;
        }

        @media (min-width: 640px) {
            .action-tile {
                gap: 12px;
                padding: 14px 16px;
            }

            .tile-icon {
                width: 38px;
                height: 38px;
                border-radius: 11px;
            }
        }

        .tile-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            background: color-mix(in srgb, var(--brand-600) 10%, transparent);
            color: var(--brand-600);
        }

        .hide-scrollbar::-webkit-scrollbar {
            display: none;
        }

        .hide-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
    <div class="pb-10" x-data="homeLauncher()">
        
        <?php if(has_module('ai_assistant')): ?>
            <?php if (isset($component)) { $__componentOriginal9368b173434343d0bf202a3a2a541301 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9368b173434343d0bf202a3a2a541301 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.modals.ai-chatbot-popup','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('modals.ai-chatbot-popup'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9368b173434343d0bf202a3a2a541301)): ?>
<?php $attributes = $__attributesOriginal9368b173434343d0bf202a3a2a541301; ?>
<?php unset($__attributesOriginal9368b173434343d0bf202a3a2a541301); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9368b173434343d0bf202a3a2a541301)): ?>
<?php $component = $__componentOriginal9368b173434343d0bf202a3a2a541301; ?>
<?php unset($__componentOriginal9368b173434343d0bf202a3a2a541301); ?>
<?php endif; ?>
        <?php endif; ?>
        
        <div class="mb-6">
            <div class="mb-3 flex flex-wrap items-center gap-3">
                <span
                    class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-[10px] font-black tracking-widest text-white uppercase"
                    style="background: var(--brand-600)">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                    Live · <?php echo e(now()->format('D d M')); ?>

                </span>
                <span class="font-mono text-xs text-gray-400"><?php echo e(now()->format('h:i A')); ?></span>
                <?php ($switchableStores = auth_stores()->get()); ?>

                <?php if(active_store()): ?>
                    
                    <span class="hidden items-center gap-1.5 text-xs font-bold text-gray-500 sm:inline-flex">
                        <i data-lucide="store" class="h-3.5 w-3.5"></i>
                        <?php echo e(active_store()->name); ?>

                    </span>

                    <div class="relative sm:hidden" x-data="{ storeOpen: false }" @click.away="storeOpen = false">
                        <button type="button" @click="storeOpen = !storeOpen"
                            class="inline-flex items-center gap-1.5 rounded-full border border-gray-200 bg-white px-3 py-1 text-xs font-bold text-gray-600">
                            <i data-lucide="store" class="h-3.5 w-3.5 text-gray-400"></i>
                            <?php echo e(active_store()->name); ?>

                            <?php if($switchableStores->count() > 1): ?>
                                <i data-lucide="chevron-down" class="h-3 w-3 text-gray-400"></i>
                            <?php endif; ?>
                        </button>

                        <?php if($switchableStores->count() > 1): ?>
                            <div x-show="storeOpen" x-cloak x-transition
                                class="absolute left-0 z-50 mt-1.5 w-56 overflow-hidden rounded-xl border border-gray-100 bg-white shadow-xl">
                                <?php $__currentLoopData = $switchableStores; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $store): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <form method="POST" action="<?php echo e(route('admin.store.switch')); ?>">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="store_id" value="<?php echo e($store->id); ?>" />
                                        <button type="submit"
                                            class="w-full border-b border-gray-50 px-4 py-2.5 text-left text-[13px] last:border-0 <?php echo e(active_store()->id == $store->id ? 'bg-brand-50/50 font-bold text-brand-700' : 'font-medium text-gray-700'); ?>">
                                            <?php echo e($store->name); ?>

                                        </button>
                                    </form>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <h2 class="text-2xl font-black tracking-tight text-gray-900 sm:text-4xl">
                <span class="text-gray-400"><?php echo e($greeting ?? ''); ?></span>
                <span x-text="greeting" class="text-gray-400"></span>, <?php echo e(explode(' ', trim($user->name))[0]); ?>

            </h2>
            <p class="mt-1.5 max-w-2xl text-sm font-medium text-gray-500">Everything is one keystroke away — search below,
                or pick a category.</p>
        </div>

        
        <div class="relative mb-4">
            <div
                class="flex items-center gap-3 rounded-2xl border border-gray-100 bg-white px-4 py-3 shadow-sm focus-within:border-gray-300">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-white"
                    style="background: var(--brand-600)">
                    <i data-lucide="search" class="h-4 w-4"></i>
                </div>
                <input type="text" x-ref="search" x-model="query" @keydown.escape="query = ''"
                    :placeholder="window.innerWidth < 640 ? 'Search...' : 'Jump to anything — invoices, batches, leads, attendance...'"
                    class="w-full border-0 bg-transparent text-[15px] font-medium text-gray-800 placeholder-gray-400 outline-none" />
                <span x-show="query" x-cloak class="shrink-0 text-xs font-bold text-gray-400"
                    x-text="visible.length + (visible.length === 1 ? ' result' : ' results')"></span>
                <kbd
                    class="hidden shrink-0 rounded-lg border border-gray-200 px-2 py-1 font-mono text-[10px] font-bold text-gray-400 sm:block">Ctrl
                    K</kbd>
            </div>
        </div>

        
        <div class="hide-scrollbar mb-6 flex gap-2 overflow-x-auto pb-1" x-show="!query" x-cloak>
            <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <button type="button" class="cat-pill" :class="{ 'active': tab === '<?php echo e($key); ?>' }"
                    @click="tab = '<?php echo e($key); ?>'">
                    <?php echo e($label); ?>

                    <span class="cat-count" x-text="countFor('<?php echo e($key); ?>')"></span>
                </button>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>

        
        <div class="grid grid-cols-2 gap-2.5 sm:gap-3 lg:grid-cols-3 xl:grid-cols-4">
            <template x-for="action in visible" :key="action.url">
                <a :href="action.url" class="action-tile">
                    <span class="tile-icon">
                        <i :data-lucide="action.icon" class="h-4 w-4"></i>
                    </span>
                    <span class="min-w-0">
                        <span class="block truncate text-[13.5px] font-bold text-gray-800" x-text="action.label"></span>
                        <span class="hidden text-[11px] font-semibold text-gray-400 capitalize sm:block"
                            x-text="action.category"></span>
                    </span>
                </a>
            </template>
        </div>

        
        <div x-show="visible.length === 0" x-cloak class="py-20 text-center">
            <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100">
                <i data-lucide="search-x" class="h-7 w-7 text-gray-300"></i>
            </div>
            <p class="font-semibold text-gray-500">Nothing matches that</p>
            <p class="mt-1 text-sm text-gray-400">Try a different word, or browse a category.</p>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
    <script>
        function homeLauncher() {
            return {
                actions: <?php echo json_encode($actions, 15, 512) ?>,
                query: "",
                tab: "quick",
                greeting: "",

                init() {
                    const hour = new Date().getHours();
                    this.greeting = hour < 12 ? "Good morning" : hour < 17 ? "Good afternoon" : "Good evening";

                    // A tenant with no quick tiles would otherwise land on an
                    // empty screen, so open the first category that has links.
                    if (!this.actions.some((a) => a.quick)) {
                        this.tab = this.actions.length ? this.actions[0].category : "quick";
                    }

                    window.addEventListener("keydown", (e) => {
                        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === "k") {
                            e.preventDefault();
                            this.$refs.search.focus();
                        }
                    });

                    this.$watch("visible", () => this.refreshIcons());
                    this.refreshIcons();
                },

                // Icons are rendered inside x-for, so Lucide has to re-scan
                // whenever the visible list changes.
                refreshIcons() {
                    this.$nextTick(() => {
                        if (window.lucide) lucide.createIcons();
                    });
                },

                get visible() {
                    const q = this.query.trim().toLowerCase();

                    if (q) {
                        return this.actions.filter((a) => a.search.includes(q));
                    }

                    if (this.tab === "quick") return this.actions.filter((a) => a.quick);

                    return this.actions.filter((a) => a.category === this.tab);
                },

                countFor(category) {
                    return category === "quick" ?
                        this.actions.filter((a) => a.quick).length :
                        this.actions.filter((a) => a.category === category).length;
                },
            };
        }
    </script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\qlinkongraphics\Desktop\MyLab\plantiq-local\resources\views/admin/home.blade.php ENDPATH**/ ?>