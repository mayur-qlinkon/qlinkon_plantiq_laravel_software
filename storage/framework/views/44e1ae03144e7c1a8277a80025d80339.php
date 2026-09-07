<?php
    $primary = get_setting('primary_color', '#008a62');
    $hover = get_setting('primary_hover_color', '#007050');
    $currentStore = active_store(auth()->user());
    $stores = auth_stores()->orderBy('name')->get();
    $canSwitchStore = $stores->count() > 1 && has_permission('stores.switch');
    $subscription = tenant_subscription();
    $expiresAt = $subscription ? $subscription->expires_at : null;
    $isLifetime = $subscription && is_null($expiresAt);
    $daysLeft = $expiresAt ? (int) ceil(now()->floatDiffInDays(\Carbon\Carbon::parse($expiresAt))) : 0;

    $companySlug = auth()->user()->company?->slug;
    $companyStorefrontUrl = auth()->user()->company?->storefront_url;

    $notifItems = $unreadNotifications
        ->map(
            fn($n) => [
                'id' => $n->id,
                'title' => $n->data['title'] ?? 'Notification',
                'message' => $n->data['message'] ?? '',
                'icon' => $n->data['icon'] ?? 'bell',
                'color' => $n->data['color'] ?? 'blue',
                'link' => $n->data['link'] ?? '#',
                'time' => $n->created_at->diffForHumans(),
            ],
        )
        ->values()
        ->all();
    $notifLatestId = !empty($notifItems) ? $notifItems[0]['id'] : null;
?>


<?php if(request()->ajax()): ?>
    <title><?php echo $__env->yieldContent('title', 'Qlinkon'); ?></title>

    <script type="text/plain" id="ajax-styles">
        <?php echo $__env->yieldPushContent('styles'); ?>
    </script>
    <div id="ajax-header-content"><?php echo $__env->yieldContent('header-title'); ?></div>

    <div id="ajax-main-content">
        <?php echo $__env->yieldContent('content'); ?>
        <footer class="mt-auto py-6 text-center text-xs text-gray-400">
            &copy; <?php echo e(date('Y')); ?> Powered by <span class="font-semibold text-gray-600">Qlinkon</span>
        </footer>

        <?php echo $__env->yieldPushContent('scripts'); ?>
    </div>
<?php else: ?>
    
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>" />
        <title><?php echo $__env->yieldContent('title', 'Qlinkon'); ?></title>

        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
            rel="stylesheet" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
        <link rel="icon" type="image/png"
            href="<?php echo e(get_setting('favicon') ? asset('storage/' . get_setting('favicon')) : asset('assets/icons/favicon.webp')); ?>" />

        
        
        <link rel="stylesheet" href="<?php echo e(asset_v('assets/css/tailwind.min.css')); ?>" />
        
        <style>
            :root {
                /* ─────────────────────────────
                        Brand Colors
                        ───────────────────────────── */
                --brand-500: <?php echo e($primary); ?>;
                --brand-600: <?php echo e($hover); ?>;
                --brand-700: <?php echo e($hover); ?>;

                /* RGB Version (Required for glow/shadows) */
                --brand-rgb: <?php echo e(implode(' ', sscanf(ltrim($primary, '#'), '%02x%02x%02x'))); ?>;

                /* ─────────────────────────────
                        Light Brand Variations
                        ───────────────────────────── */
                --color-brand-50: color-mix(in srgb, var(--brand-500) 10%, white);
                --color-brand-100: color-mix(in srgb, var(--brand-500) 20%, white);
                --color-brand-200: color-mix(in srgb, var(--brand-500) 35%, white);

                /* ─────────────────────────────
                        Layout Variables
                        ───────────────────────────── */
                --bg-page: #f4f6f9;
                --ease: cubic-bezier(0.4, 0, 0.2, 1);
            }

            .brand-glow {
                box-shadow:
                    0 0 10px rgb(var(--brand-rgb) / 0.35),
                    0 0 20px rgb(var(--brand-rgb) / 0.20),
                    0 0 32px rgb(var(--brand-rgb) / 0.12);
            }

            /* Chrome, Safari, Edge, Opera */
            input[type="number"]::-webkit-outer-spin-button,
            input[type="number"]::-webkit-inner-spin-button {
                -webkit-appearance: none;
                margin: 0;
            }

            /* Firefox */
            input[type="number"] {
                -moz-appearance: textfield;
            }

            [x-cloak] {
                display: none !important;
            }

            .ui-select {
                -webkit-appearance: none;
                appearance: none;
                color-scheme: light;
                background-color: #fff;
                color: #111827;
            }

            .ui-select option {
                background: #fff;
                color: #111827;
            }

            .ui-select:focus {
                outline: none;
            }

            select,
            select option {
                font-family: inherit;
            }

            #page-cover {
                position: fixed;
                inset: 0;
                background: var(--bg-page);
                z-index: 9999;
                opacity: 1;
                pointer-events: none;
                transition: opacity 220ms ease;
            }

            /* ── Progress Bar ── */
            #nav-progress {
                position: fixed;
                top: 0;
                left: 0;
                height: 2px;
                width: 0%;
                background: var(--brand-600);
                z-index: 99999;
                opacity: 0;
                transition: width 200ms ease, opacity 300ms ease;
            }

            /* ── Scrollbar ── */
            .nav-scroll::-webkit-scrollbar {
                width: 3px;
            }

            .nav-scroll::-webkit-scrollbar-track {
                background: transparent;
            }

            .nav-scroll::-webkit-scrollbar-thumb {
                background: transparent;
                border-radius: 4px;
            }

            .nav-scroll:hover::-webkit-scrollbar-thumb {
                background: #e5e7eb;
            }

            /* ── Sidebar Overlay ── */
            #sidebar-overlay {
                position: fixed;
                inset: 0;
                background: rgba(0, 0, 0, 0.42);
                backdrop-filter: blur(3px);
                z-index: 30;
                opacity: 0;
                pointer-events: none;
                transition: opacity 280ms var(--ease);
            }

            #sidebar-overlay.active {
                opacity: 1;
                pointer-events: auto;
            }

            /* ── Sidebar ── */
            #main-sidebar {
                transition: transform 280ms var(--ease), box-shadow 280ms var(--ease);
            }

            @media (max-width: 1023px) {
                #main-sidebar {
                    position: fixed;
                    top: 0;
                    left: 0;
                    height: 100%;
                    transform: translateX(-100%);
                    z-index: 40;
                    box-shadow: none;
                }

                #main-sidebar.sidebar-open {
                    transform: translateX(0);
                    box-shadow: 20px 0 60px rgba(0, 0, 0, 0.13);
                }

                .sidebar-close-btn {
                    display: flex !important;
                }
            }

            .sidebar-close-btn {
                display: none;
            }

            /* ── Nav Items ── */
            .nav-item {
                display: flex;
                align-items: center;
                justify-content: space-between;
                width: 100%;
                padding: 0.55rem 0.75rem;
                border-radius: 0.625rem;
                font-size: 0.875rem;
                font-weight: 500;
                color: #6b7280;
                text-decoration: none;
                border: none;
                background: transparent;
                text-align: left;
                cursor: pointer;
                outline: none;
                transition: background 140ms var(--ease), color 140ms var(--ease), transform 80ms ease;
            }

            .nav-item:hover {
                background: #f0f7f4;
                color: var(--brand-600);
            }

            .nav-item:hover .nav-icon {
                color: var(--brand-600);
            }

            .nav-item:active {
                transform: scale(0.984);
            }

            .nav-item.active,
            .nav-item.active:hover {
                background: var(--brand-600);
                color: #fff;
            }

            .nav-item.active .nav-icon,
            .nav-item.active:hover .nav-icon {
                color: #fff;
            }

            .nav-item.active .nav-chevron,
            .nav-item.active:hover .nav-chevron {
                color: rgba(255, 255, 255, 0.6);
            }

            .nav-item.acc-open:not(.active) {
                background: #f0f7f4;
                color: var(--brand-600);
            }

            .nav-item.acc-open:not(.active) .nav-icon {
                color: var(--brand-600);
            }

            .nav-item.acc-open:not(.active) .nav-chevron {
                color: var(--brand-600);
            }

            .nav-icon {
                color: #9ca3af;
                flex-shrink: 0;
                transition: color 140ms var(--ease);
            }

            .nav-chevron {
                width: 14px;
                height: 14px;
                color: #d1d5db;
                flex-shrink: 0;
                transition: color 140ms var(--ease), transform 240ms var(--ease);
            }

            .nav-chevron.rotated {
                transform: rotate(90deg);
            }

            /* ── Accordion ── */
            .acc-wrap {
                overflow: hidden;
                max-height: 0;
                opacity: 0;
                transition: max-height 255ms var(--ease), opacity 200ms ease;
            }

            .sub-menu {
                margin: 3px 0 4px 0.9rem;
                padding: 2px 0 2px 0.85rem;
                border-left: 1.5px solid #e5e7eb;
            }

            .sub-item {
                display: flex;
                align-items: center;
                gap: 0.45rem;
                padding: 0.4rem 0.6rem;
                border-radius: 0.5rem;
                font-size: 0.8125rem;
                font-weight: 500;
                color: #6b7280;
                text-decoration: none;
                transition: background 130ms var(--ease), color 130ms var(--ease), padding-left 150ms ease;
            }

            .sub-item:hover {
                background: #f0f7f4;
                color: var(--brand-600);
                padding-left: 0.9rem;
            }

            .sub-item.active {
                background: var(--brand-50);
                color: var(--brand-600);
                font-weight: 600;
            }

            .sub-item.active::before {
                content: '';
                display: inline-block;
                width: 5px;
                height: 5px;
                border-radius: 50%;
                background: var(--brand-600);
                flex-shrink: 0;
            }

            /* ── Nav Section Labels ── */
            .nav-section-label {
                padding: 1.1rem 0.75rem 0.35rem;
                margin-top: 0.35rem;
                border-top: 1px solid #f3f4f6;
                font-size: 0.65rem;
                font-weight: 700;
                letter-spacing: 0.08em;
                text-transform: uppercase;
                color: #9ca3af;
            }

            /* The first label has nothing above it to be separated from. */
            .nav-section-label:first-child {
                border-top: none;
                margin-top: 0;
            }

            .nav-section-label:first-child {
                padding-top: 0.25rem;
            }

            /* ── Hamburger ── */
            .hb-line {
                display: block;
                width: 20px;
                height: 2px;
                background: #212538;
                border-radius: 2px;
                transform-origin: center;
                transition: transform 250ms var(--ease), opacity 200ms ease, width 200ms ease;
            }

            #hamburger-btn.is-open .hb-line:nth-child(1) {
                transform: translateY(7px) rotate(45deg);
            }

            #hamburger-btn.is-open .hb-line:nth-child(2) {
                opacity: 0;
                width: 0;
            }

            #hamburger-btn.is-open .hb-line:nth-child(3) {
                transform: translateY(-7px) rotate(-45deg);
            }

            /* 1. Hide the mini logo by default */
            .sidebar-logo-mini {
                display: none !important;
            }

            /* ── Minimized Desktop Sidebar ── */
            @media (min-width: 1024px) {
                #main-sidebar {
                    transition: width 250ms var(--ease), transform 280ms var(--ease), box-shadow 280ms var(--ease);
                }

                #main-sidebar.is-minimized {
                    width: 76px !important;
                }

                /* Hide extra elements */
                #main-sidebar.is-minimized .nav-section-label,
                #main-sidebar.is-minimized .nav-chevron,
                #main-sidebar.is-minimized .acc-wrap,
                #main-sidebar.is-minimized .subscription-box {
                    display: none !important;
                }

                /* Squeeze the nav items to only show the icon */
                #main-sidebar.is-minimized .nav-item {
                    justify-content: center;
                    padding-left: 0;
                    padding-right: 0;
                }

                #main-sidebar.is-minimized .nav-item>span {
                    width: 18px;
                    /* Exact width of the Lucide icon */
                    overflow: hidden;
                    white-space: nowrap;
                    margin: 0 auto;
                }

                /* Hide the large logo, show a tiny version if needed */
                #main-sidebar.is-minimized .sidebar-logo-img {
                    display: none;
                }

                /* 2. When minimized, HIDE the full logo */
                #main-sidebar.is-minimized .sidebar-logo-img {
                    display: none !important;
                }

                /* 3. When minimized, SHOW the mini logo */
                #main-sidebar.is-minimized .sidebar-logo-mini {
                    display: flex !important;
                    margin: 0 auto;
                    /* Keeps it perfectly centered */
                }

                /* 4. Fix the header padding so the icon centers properly */
                #main-sidebar.is-minimized .h-\[60px\] {
                    padding-left: 0;
                    padding-right: 0;
                    justify-content: center;
                }
            }
        </style>

        <?php echo $__env->yieldContent('styles'); ?>
        <?php echo $__env->yieldPushContent('styles'); ?>
    </head>

    <body class="bg-[#f4f6f9] font-sans text-gray-800">
        <div id="nav-progress"></div>
        <div id="page-cover"></div>

        <?php
            $isActive = fn(string|array $r) => request()->routeIs(...(array) $r);
            $navCls = fn(string|array $r) => $isActive($r) ? 'active' : '';
            $subCls = fn(string|array $r) => $isActive($r) ? 'active' : '';
            $accOpen = fn(string|array $r) => $isActive($r) ? 'true' : 'false';
        ?>

        <div id="sidebar-overlay" onclick="closeSidebar()"></div>

        <div class="flex h-screen w-full overflow-hidden">
            
            <aside id="main-sidebar" class="flex w-64 flex-shrink-0 flex-col border-r border-gray-100 bg-white">
                <?php

                    $defaultLogo = asset('assets/images/logo.webp');
                    $defaultFavicon = asset('assets/icons/favicon.webp');

                    $loginLogo = get_system_setting('app_logo');
                    $appFavicon = get_system_setting('app_favicon');

                    $siteName = config('app.name', 'Qlinkon');

                    // Full Logo
                    $logoSrc = $defaultLogo;

                    if (!empty($loginLogo)) {
                        $logoSrc = Str::startsWith($loginLogo, ['http://', 'https://'])
                            ? $loginLogo
                            : asset('storage/' . ltrim($loginLogo, '/'));
                    }

                    // Mini/Favicon Logo
                    $faviconSrc = $defaultFavicon;

                    if (!empty($appFavicon)) {
                        $faviconSrc = Str::startsWith($appFavicon, ['http://', 'https://'])
                            ? $appFavicon
                            : asset('storage/' . ltrim($appFavicon, '/'));
                    }
                ?>

                <div
                    class="relative flex h-[60px] flex-shrink-0 items-center justify-center border-b border-gray-100 px-4">
                    
                    <div class="flex items-center justify-center gap-2.5">
                        
                        <img src="<?php echo e($logoSrc); ?>" alt="<?php echo e($siteName); ?> logo"
                            class="sidebar-logo-img h-7 w-auto max-w-[150px] object-contain md:h-8 lg:h-9"
                            onerror="this.onerror=null;this.src='<?php echo e($defaultLogo); ?>';" />

                        
                        <div
                            class="sidebar-logo-mini flex h-10 w-10 items-center justify-center overflow-hidden rounded-lg text-white shadow-sm">
                            <img src="<?php echo e($faviconSrc); ?>" alt="<?php echo e($siteName); ?> mini logo"
                                class="h-full w-full object-contain p-1"
                                onerror="this.onerror=null;this.src='<?php echo e($defaultFavicon); ?>';" />
                        </div>
                    </div>

                    
                    <button onclick="closeSidebar()"
                        class="sidebar-close-btn absolute top-1/2 right-3 h-8 w-8 -translate-y-1/2 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600">
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                </div>

                <nav id="sidebar-nav" class="nav-scroll flex-1 space-y-0.5 overflow-y-auto px-3 py-4">
                    <div class="nav-section-label">Home</div>
                    <a href="<?php echo e(route('admin.dashboard')); ?>" class="nav-item <?php echo e($navCls('admin.dashboard')); ?>">
                        <span class="flex items-center gap-3">
                            <i data-lucide="home" class="nav-icon h-[18px] w-[18px]"></i> Dashboard
                        </span>
                    </a>
                    
                    <?php if(has_employee_profile()): ?>
                        <div class="nav-section-label">My Space</div>

                        <a href="<?php echo e(route('admin.employee.dashboard')); ?>"
                            class="nav-item <?php echo e($navCls('admin.employee.dashboard')); ?>">
                            <span class="flex items-center gap-3">
                                <i data-lucide="layout-dashboard" class="nav-icon h-[18px] w-[18px]"></i> My Work
                            </span>
                        </a>
                        <a href="<?php echo e(route('admin.hrm.my-leaves.index')); ?>"
                            class="nav-item <?php echo e($navCls('admin.hrm.my-leaves.*')); ?>">
                            <span class="flex items-center gap-3">
                                <i data-lucide="calendar-off" class="nav-icon h-[18px] w-[18px]"></i> My Leaves
                            </span>
                        </a>
                        <a href="<?php echo e(route('admin.hrm.my-attendance.index')); ?>"
                            class="nav-item <?php echo e($navCls('admin.hrm.my-attendance.*')); ?>">
                            <span class="flex items-center gap-3">
                                <i data-lucide="clock" class="nav-icon h-[18px] w-[18px]"></i> My Attendance
                            </span>
                        </a>
                        <a href="<?php echo e(route('admin.hrm.my-tasks.index')); ?>"
                            class="nav-item <?php echo e($navCls('admin.hrm.my-tasks.*')); ?>">
                            <span class="flex items-center gap-3">
                                <i data-lucide="check-square" class="nav-icon h-[18px] w-[18px]"></i> My Tasks
                            </span>
                        </a>
                        <a href="<?php echo e(route('admin.hrm.my-work-logs.index')); ?>"
                            class="nav-item <?php echo e($navCls('admin.hrm.my-work-logs.*')); ?>">
                            <span class="flex items-center gap-3">
                                <i data-lucide="clipboard-list" class="nav-icon h-[18px] w-[18px]"></i> My Work Logs
                            </span>
                        </a>
                        <a href="<?php echo e(route('admin.hrm.my-salary-slips.index')); ?>"
                            class="nav-item <?php echo e($navCls('admin.hrm.my-salary-slips.*')); ?>">
                            <span class="flex items-center gap-3">
                                <i data-lucide="banknote" class="nav-icon h-[18px] w-[18px]"></i> My Salary Slips
                            </span>
                        </a>
                        <?php if(has_module('production')): ?>
                            <div class="nav-section-label">Production Operations</div>
                            <a href="<?php echo e(route('admin.production.my-tasks.index')); ?>"
                                class="nav-item <?php echo e($navCls('admin.production.my-tasks.*')); ?>">
                                <span class="flex items-center gap-3">
                                    <i data-lucide="check-square" class="nav-icon h-[18px] w-[18px]"></i> Production
                                    Tasks
                                </span>
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if(has_module(['ocr_scanner', 'label_printing', 'bulk_import']) &&
                            has_permission(['ocr_scanner.access', 'bulk_import.view', 'labels.view'])): ?>
                        <div class="nav-section-label">Tools</div>
                        <?php if(has_module('ocr_scanner') && has_permission('ocr_scanner.access')): ?>
                            <a href="<?php echo e(route('admin.ocr-scanner.index')); ?>"
                                class="nav-item <?php echo e($navCls(['admin.ocr-scanner.*'])); ?>">
                                <span class="flex items-center gap-3">
                                    <i data-lucide="scan-line" class="nav-icon h-[18px] w-[18px]"></i> Doc Scanner
                                </span>
                            </a>
                        <?php endif; ?>
                        <?php if(has_module('label_printing') && has_permission('labels.view')): ?>
                            <a href="<?php echo e(route('admin.labels.index')); ?>"
                                class="nav-item <?php echo e($navCls(['admin.labels.*'])); ?>">
                                <span class="flex items-center gap-3">
                                    <i data-lucide="printer" class="nav-icon h-[18px] w-[18px]"></i> Label Printing
                                </span>
                            </a>
                        <?php endif; ?>
                        <?php if(has_module('bulk_import') && has_permission('bulk_import.view')): ?>
                            <a href="<?php echo e(route('admin.bulk-import.index')); ?>"
                                class="nav-item <?php echo e($navCls(['admin.bulk-import.*'])); ?>">
                                <span class="flex items-center gap-3">
                                    <i data-lucide="pickaxe" class="nav-icon h-[18px] w-[18px]"></i>
                                    Bulk Import
                                </span>
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if(has_module(['inquiry', 'appointments']) && has_permission(['inquiries.view', 'appointments.view'])): ?>
                        <div class="nav-section-label">Operations</div>
                    <?php endif; ?>
                    <?php if(has_module('inquiry') && has_permission('inquiries.view')): ?>
                        <a href="<?php echo e(route('admin.orders.index')); ?>"
                            class="nav-item <?php echo e($navCls('admin.orders.*')); ?>">
                            <span class="flex items-center gap-3">
                                <i data-lucide="list-ordered" class="nav-icon h-[18px] w-[18px]"></i> Order Process
                            </span>
                        </a>
                    <?php endif; ?>

                    
                    <?php if(has_module('appointments') && has_permission('appointments.view')): ?>
                        <a href="<?php echo e(route('admin.appointments.index')); ?>"
                            data-owns="/admin/appointment-services,/admin/appointment-slots"
                            class="nav-item <?php echo e($subCls('admin.appointments.*')); ?>">
                            <span class="flex items-center gap-3">
                                <i data-lucide="calendar-days" class="nav-icon h-[18px] w-[18px]"></i>
                                Appointments
                            </span>
                        </a>
                    <?php endif; ?>

                    <?php if(
                        (has_module('pos') && has_permission('pos.access')) ||
                            (has_module('invoicing') &&
                                has_permission([
                                    'sales_dashboard.view',
                                    'invoices.view',
                                    'invoice_ledger.view',
                                    'sales_reports.view',
                                    'quotations.view',
                                    'challans.view',
                                ]))): ?>
                        <div class="nav-section-label">Sales & Finance</div>

                        
                        <?php if(has_module('invoicing') && has_permission('sales_dashboard.view')): ?>
                            <a href="<?php echo e(route('admin.sales.dashboard')); ?>"
                                class="nav-item <?php echo e($navCls('admin.sales.dashboard')); ?>">
                                <span class="flex items-center gap-3">
                                    <i data-lucide="trending-up" class="nav-icon h-[18px] w-[18px]"></i>
                                    Sales Dashboard
                                </span>
                            </a>
                        <?php endif; ?>

                        
                        <?php if(has_module('pos') && has_permission('pos.access')): ?>
                            <a href="<?php echo e(url('/admin/pos')); ?>" class="nav-item" target="_blank" data-no-spa>
                                <span class="flex items-center gap-3">
                                    <i data-lucide="monitor" class="nav-icon h-[18px] w-[18px]"></i>
                                    POS Counter
                                </span>
                            </a>
                        <?php endif; ?>

                        
                        <?php if(has_module('invoicing')): ?>
                            <?php if(has_permission('invoices.view')): ?>
                                <a href="<?php echo e(route('admin.invoices.index')); ?>"
                                    class="nav-item <?php echo e($navCls('admin.invoices.*')); ?>">
                                    <span class="flex items-center gap-3">
                                        <i data-lucide="file-text" class="nav-icon h-[18px] w-[18px]"></i>
                                        Sales Invoices
                                    </span>
                                </a>
                            <?php endif; ?>

                            
                            <?php if(has_permission('invoices.view')): ?>
                                <a href="<?php echo e(route('admin.invoice-returns.index')); ?>"
                                    class="nav-item <?php echo e($navCls('admin.invoice-returns.*')); ?>">
                                    <span class="flex items-center gap-3">
                                        <i data-lucide="corner-up-left" class="nav-icon h-[18px] w-[18px]"></i>
                                        Sales Returns
                                    </span>
                                </a>
                            <?php endif; ?>

                            
                            <?php if(has_permission('invoice_ledger.view')): ?>
                                <a href="<?php echo e(route('admin.ledger.index')); ?>"
                                    class="nav-item <?php echo e($navCls('admin.ledger.*')); ?>">
                                    <span class="flex items-center gap-3">
                                        <i data-lucide="book-open" class="nav-icon h-[18px] w-[18px]"></i>
                                        Invoice Ledger
                                    </span>
                                </a>
                            <?php endif; ?>

                            
                            <?php if(has_permission('sales_reports.view')): ?>
                                <a href="<?php echo e(route('admin.reports.index')); ?>"
                                    class="nav-item <?php echo e($navCls('admin.reports.*')); ?>">
                                    <span class="flex items-center gap-3">
                                        <i data-lucide="pie-chart" class="nav-icon h-[18px] w-[18px]"></i>
                                        Analytics & Reports
                                    </span>
                                </a>
                            <?php endif; ?>

                            
                            <?php if(has_permission('quotations.view')): ?>
                                <a href="<?php echo e(route('admin.quotations.index')); ?>"
                                    class="nav-item <?php echo e($navCls(['admin.quotations.*'])); ?>">
                                    <span class="flex items-center gap-3">
                                        <i data-lucide="quote" class="nav-icon h-[18px] w-[18px]"></i>
                                        Quotations
                                    </span>
                                </a>
                            <?php endif; ?>

                            
                            <?php if(has_permission('challans.view')): ?>
                                <a href="<?php echo e(route('admin.challans.index')); ?>"
                                    class="nav-item <?php echo e($navCls('admin.challans.*')); ?>">
                                    <span class="flex items-center gap-3">
                                        <i data-lucide="truck" class="nav-icon h-[18px] w-[18px]"></i>
                                        Delivery Challans
                                    </span>
                                </a>
                            <?php endif; ?>

                            
                            <?php if(has_permission('challans.view')): ?>
                                <a href="<?php echo e(route('admin.challan-returns.index')); ?>"
                                    class="nav-item <?php echo e($navCls('admin.challan-returns.*')); ?>">
                                    <span class="flex items-center gap-3">
                                        <i data-lucide="rotate-ccw" class="nav-icon h-[18px] w-[18px]"></i>
                                        Challan Returns
                                    </span>
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endif; ?>

                    
                    <?php if(has_module('purchases') && has_permission(['purchases.view', 'purchase_returns.view'])): ?>
                        <?php if(has_permission('purchases.view')): ?>
                            <a href="<?php echo e(route('admin.purchases.index')); ?>"
                                class="nav-item <?php echo e($navCls('admin.purchases.*')); ?>">
                                <span class="flex items-center gap-3">
                                    <i data-lucide="shopping-bag" class="nav-icon h-[18px] w-[18px]"></i>
                                    Purchases
                                </span>
                            </a>
                        <?php endif; ?>

                        <?php if(has_permission('purchase_returns.view')): ?>
                            <a href="<?php echo e(route('admin.purchase-returns.index')); ?>"
                                class="nav-item <?php echo e($navCls('admin.purchase-returns.*')); ?>">
                                <span class="flex items-center gap-3">
                                    <i data-lucide="corner-up-left" class="nav-icon h-[18px] w-[18px]"></i>
                                    Purchase Returns
                                </span>
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>

                    
                    <?php if(has_module('expenses') && has_permission('expenses.view')): ?>
                        <a href="<?php echo e(route('admin.expenses.index')); ?>"
                            data-owns="/admin/expenses,/admin/expense-categories"
                            class="nav-item <?php echo e($navCls(['admin.expenses.*', 'admin.expense-categories.*'])); ?>">
                            <span class="flex items-center gap-3">
                                <i data-lucide="banknote" class="nav-icon h-[18px] w-[18px]"></i> Expenses
                            </span>
                        </a>
                    <?php endif; ?>

                    
                    <?php if(has_module('inventory') &&
                            has_permission([
                                'products.view',
                                'attributes.view',
                                'categories.view',
                                'units.view',
                                'labels.view',
                                'warehouses.view',
                                'stock_adjustments.view',
                                'inventory_reports.view',
                            ])): ?>
                        <div class="nav-section-label">Inventory</div>

                        
                        
                        <?php if(has_permission('products.view')): ?>
                            <a href="<?php echo e(route('admin.products.index')); ?>"
                                data-owns="/admin/categories,/admin/units,/admin/attributes"
                                class="nav-item <?php echo e($navCls(['admin.products.*', 'admin.categories.*', 'admin.units.*', 'admin.attributes.*'])); ?>">
                                <span class="flex items-center gap-3">
                                    <i data-lucide="package" class="nav-icon h-[18px] w-[18px]"></i> Products
                                </span>
                            </a>
                        <?php endif; ?>

                        
                        <?php if(has_permission('warehouses.view')): ?>
                            <a href="<?php echo e(route('admin.warehouses.index')); ?>"
                                class="nav-item <?php echo e($navCls('admin.warehouses.*')); ?>">
                                <span class="flex items-center gap-3">
                                    <i data-lucide="warehouse" class="nav-icon h-[18px] w-[18px]"></i> Warehouses
                                </span>
                            </a>
                        <?php endif; ?>

                        
                        <?php if(has_permission(['stock_adjustments.view', 'warehouses.view'])): ?>
                            <a href="<?php echo e(route('admin.stock-adjustments.index')); ?>"
                                class="nav-item <?php echo e($navCls('admin.stock-adjustments.*')); ?>">
                                <span class="flex items-center gap-3">
                                    <i data-lucide="sliders-horizontal" class="nav-icon h-[18px] w-[18px]"></i>
                                    Stock Adjustments
                                </span>
                            </a>
                        <?php endif; ?>

                        
                        <?php if(has_permission(['inventory_reports.view'])): ?>
                            <a href="<?php echo e(route('admin.inventory.reports.index')); ?>"
                                class="nav-item <?php echo e($navCls('admin.inventory.reports.*')); ?>">
                                <span class="flex items-center gap-3">
                                    <i data-lucide="clipboard-list" class="nav-icon h-[18px] w-[18px]"></i>
                                    Inventory Report
                                </span>
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>

                    
                    <?php if(has_module('production') &&
                            has_permission([
                                'production_layout.view',
                                'production_plans.view',
                                'production_plant_batches.view',
                                'production_activity_templates.view',
                                'production_zone_assignments.view',
                                'production_harvest_lots.view',
                            ])): ?>
                        <div class="nav-section-label">Production</div>

                        <a href="<?php echo e(route('admin.production.dashboard')); ?>"
                            class="nav-item <?php echo e($navCls(['admin.production.dashboard'])); ?>">
                            <span class="flex items-center gap-3">
                                <i data-lucide="sprout" class="nav-icon h-[18px] w-[18px]"></i>
                                Production Dashboard
                            </span>
                        </a>

                        
                        <?php if(has_permission('production_layout.view')): ?>
                            <a href="<?php echo e(route('admin.production.layout.index')); ?>" data-owns="/admin/production/map"
                                class="nav-item <?php echo e($navCls(['admin.production.layout.*', 'admin.production.map'])); ?>">
                                <span class="flex items-center gap-3">
                                    <i data-lucide="map" class="nav-icon h-[18px] w-[18px]"></i>
                                    Production Layout
                                </span>
                            </a>
                        <?php endif; ?>

                        
                        <?php if(has_permission('production_plans.view')): ?>
                            <a href="<?php echo e(route('admin.production.plans.index')); ?>"
                                class="nav-item <?php echo e($navCls(['admin.production.plans.*'])); ?>">
                                <span class="flex items-center gap-3">
                                    <i data-lucide="clipboard-list" class="nav-icon h-[18px] w-[18px]"></i>
                                    Production Plans
                                </span>
                            </a>
                        <?php endif; ?>

                        
                        <?php if(has_permission('production_plant_batches.view')): ?>
                            <a href="<?php echo e(route('admin.production.plant-batches.index')); ?>"
                                class="nav-item <?php echo e($navCls(['admin.production.plant-batches.*'])); ?>">
                                <span class="flex items-center gap-3">
                                    <i data-lucide="package-2" class="nav-icon h-[18px] w-[18px]"></i>
                                    Plant Batches
                                </span>
                            </a>
                        <?php endif; ?>

                        
                        <?php if(has_permission('production_activity_templates.view')): ?>
                            <a href="<?php echo e(route('admin.production.activity-templates.index')); ?>"
                                class="nav-item <?php echo e($navCls(['admin.production.activity-templates.*'])); ?>">
                                <span class="flex items-center gap-3">
                                    <i data-lucide="list-checks" class="nav-icon h-[18px] w-[18px]"></i>
                                    Activity Templates
                                </span>
                            </a>
                        <?php endif; ?>

                        
                        <?php if(has_permission('production_zone_assignments.view')): ?>
                            <a href="<?php echo e(route('admin.production.zone-assignments.index')); ?>"
                                class="nav-item <?php echo e($navCls(['admin.production.zone-assignments.*'])); ?>">
                                <span class="flex items-center gap-3">
                                    <i data-lucide="map-pin" class="nav-icon h-[18px] w-[18px]"></i>
                                    Zone Assignments
                                </span>
                            </a>
                        <?php endif; ?>

                        
                        <?php if(has_permission('production_harvest_lots.view')): ?>
                            <a href="<?php echo e(route('admin.production.harvest-lots.index')); ?>"
                                class="nav-item <?php echo e($navCls(['admin.production.harvest-lots.*'])); ?>">
                                <span class="flex items-center gap-3">
                                    <i data-lucide="clipboard-list" class="nav-icon h-[18px] w-[18px]"></i>
                                    Harvest LOT
                                </span>
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>

                    
                    <?php if(has_module('crm') &&
                            has_permission([
                                'crm_dashboard.view',
                                'crm_leads.view',
                                'crm_pipelines.view',
                                'crm_sources.view',
                                'crm_reports.view',
                            ])): ?>
                        <div class="nav-section-label">CRM</div>

                        <?php if(has_permission('crm_dashboard.view')): ?>
                            <a href="<?php echo e(route('admin.crm.dashboard')); ?>"
                                class="nav-item <?php echo e($navCls('admin.crm.dashboard')); ?>">
                                <span class="flex items-center gap-3">
                                    <i data-lucide="layout-dashboard" class="nav-icon h-[18px] w-[18px]"></i>
                                    CRM Dashboard
                                </span>
                            </a>
                        <?php endif; ?>

                        <?php if(has_permission('crm_leads.view')): ?>
                            <a href="<?php echo e(route('admin.crm.leads.index')); ?>"
                                class="nav-item <?php echo e($navCls('admin.crm.leads.*')); ?>">
                                <span class="flex items-center gap-3">
                                    <i data-lucide="users" class="nav-icon h-[18px] w-[18px]"></i> Leads
                                </span>
                            </a>
                        <?php endif; ?>

                        <?php if(has_permission('crm_reports.view')): ?>
                            <a href="<?php echo e(route('admin.crm.performance.index')); ?>"
                                class="nav-item <?php echo e($navCls('admin.crm.performance.*')); ?>">
                                <span class="flex items-center gap-3">
                                    <i data-lucide="trending-up" class="nav-icon h-[18px] w-[18px]"></i> Team
                                    Performance
                                </span>
                            </a>
                        <?php endif; ?>

                        <?php if(has_permission(['crm_pipelines.view', 'crm_sources.view'])): ?>
                            <a href="<?php echo e(route('admin.crm.pipelines.index')); ?>"
                                data-owns="/admin/crm/stages,/admin/crm/pipelines,/admin/crm/sources,/admin/crm/tags"
                                class="nav-item <?php echo e($navCls(['admin.crm.pipelines.*', 'admin.crm.stages.*', 'admin.crm.sources.*', 'admin.crm.tags.*'])); ?>">
                                <span class="flex items-center gap-3">
                                    <i data-lucide="git-branch" class="nav-icon h-[18px] w-[18px]"></i> CRM Settings
                                </span>
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>

                    
                    <?php if(has_module('projects') &&
                            has_permission(['projects.view', 'project_services.view', 'project_client_services.view'])): ?>
                        <div class="nav-section-label">Projects Tracker</div>

                        <?php if(has_permission('projects.view')): ?>
                            <a href="<?php echo e(route('admin.projects.index')); ?>"
                                class="nav-item <?php echo e($navCls('admin.projects.*')); ?>">
                                <span class="flex items-center gap-3">
                                    <i data-lucide="folder-kanban" class="nav-icon h-[18px] w-[18px]"></i> Active
                                    Projects
                                </span>
                            </a>
                        <?php endif; ?>

                        <?php if(has_permission('project_client_services.view')): ?>
                            <a href="<?php echo e(route('admin.project_renewals.index')); ?>"
                                class="nav-item <?php echo e($navCls('admin.project_renewals.*')); ?>">
                                <span class="flex items-center gap-3">
                                    <i data-lucide="calendar-clock" class="nav-icon h-[18px] w-[18px]"></i> Renewals
                                </span>
                            </a>
                        <?php endif; ?>

                        <?php if(has_permission('project_services.view')): ?>
                            <a href="<?php echo e(route('admin.services.index')); ?>"
                                class="nav-item <?php echo e($navCls('admin.services.*')); ?>">
                                <span class="flex items-center gap-3">
                                    <i data-lucide="box" class="nav-icon h-[18px] w-[18px]"></i> Services Catalog
                                </span>
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if(has_permission(['suppliers.view', 'clients.view'])): ?>
                        <div class="nav-section-label">Contacts</div>

                        <?php if(has_permission('clients.view')): ?>
                            <a href="<?php echo e(route('admin.clients.index')); ?>"
                                class="nav-item <?php echo e($navCls('admin.clients.*')); ?>">
                                <span class="flex items-center gap-3">
                                    <i data-lucide="briefcase" class="nav-icon h-[18px] w-[18px]"></i>
                                    Clients
                                </span>
                            </a>
                        <?php endif; ?>

                        <?php if(has_permission('suppliers.view') && has_module('purchases')): ?>
                            <a href="<?php echo e(route('admin.suppliers.index')); ?>"
                                class="nav-item <?php echo e($navCls('admin.suppliers.*')); ?>">
                                <span class="flex items-center gap-3">
                                    <i data-lucide="building-2" class="nav-icon h-[18px] w-[18px]"></i>
                                    Suppliers
                                </span>
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>

                    

                    <?php if(has_module('storefront') && has_permission(['storefront_builder.view', 'pages.view', 'banners.view'])): ?>
                        <div class="nav-section-label">Website & CMS</div>

                        
                        <?php if(has_permission('storefront_builder.view')): ?>
                            <a href="<?php echo e(route('admin.storefront-builder.index')); ?>"
                                class="nav-item <?php echo e($navCls('admin.storefront-builder.*')); ?>">
                                <span class="flex items-center gap-3">
                                    <i data-lucide="layout" class="nav-icon h-[18px] w-[18px]"></i>
                                    Visual Builder
                                </span>
                            </a>
                        <?php endif; ?>

                        
                        <?php if(has_permission('banners.view')): ?>
                            <a href="<?php echo e(route('admin.banners.index')); ?>"
                                class="nav-item <?php echo e($navCls('admin.banners.*')); ?>">
                                <span class="flex items-center gap-3">
                                    <i data-lucide="image" class="nav-icon h-[18px] w-[18px]"></i>
                                    Promotional Banners
                                </span>
                            </a>
                        <?php endif; ?>

                        
                        <?php if(has_permission(['storefront_builder.view', 'pages.view'])): ?>
                            <div class="acc-group"
                                data-open="<?php echo e($accOpen(['admin.merchandising.*', 'admin.storefront-sections.*', 'admin.pages.*'])); ?>">
                                <button
                                    class="nav-item acc-trigger <?php echo e($navCls(['admin.merchandising.*', 'admin.storefront-sections.*', 'admin.pages.*'])); ?>">
                                    <span class="flex items-center gap-3">
                                        <i data-lucide="globe" class="nav-icon h-[18px] w-[18px]"></i>
                                        Customization
                                    </span>
                                    <i data-lucide="chevron-right" class="nav-chevron"></i>
                                </button>

                                <div class="acc-wrap">
                                    <div class="sub-menu">
                                        
                                        <?php if(has_permission('storefront_builder.view')): ?>
                                            <a href="<?php echo e(route('admin.merchandising.index')); ?>"
                                                class="sub-item <?php echo e($subCls('admin.merchandising.*')); ?>">
                                                <i data-lucide="layout-grid" class="h-4 w-4"></i>
                                                Merchandising
                                            </a>
                                        <?php endif; ?>

                                        
                                        <?php if(has_permission('storefront_builder.view')): ?>
                                            <a href="<?php echo e(route('admin.storefront-sections.index')); ?>"
                                                class="sub-item <?php echo e($subCls('admin.storefront-sections.*')); ?>">
                                                <i data-lucide="blocks" class="h-4 w-4"></i>
                                                Homepage Sections
                                            </a>
                                        <?php endif; ?>

                                        
                                        <?php if(has_permission('pages.view')): ?>
                                            <a href="<?php echo e(route('admin.pages.index')); ?>"
                                                class="sub-item <?php echo e($subCls('admin.pages.*')); ?>">
                                                <i data-lucide="files" class="h-4 w-4"></i>
                                                Custom Pages
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if(has_module('hrm') &&
                            has_permission([
                                'hrm_dashboard.view',
                                'employees.view',
                                'announcements.view',
                                'hrm_tasks.view',
                                'attendance.view',
                                'work_logs.view',
                                'leaves.view',
                                'salary_slips.view',
                                'salary_components.view',
                                'departments.view',
                                'designations.view',
                                'shifts.view',
                                'holidays.view',
                                'attendance_rules.view',
                            ])): ?>
                        <div class="nav-section-label">Team Management</div>

                        
                        <?php if(has_permission('hrm_dashboard.view')): ?>
                            <a href="<?php echo e(route('admin.hrm.dashboard')); ?>"
                                class="nav-item <?php echo e($navCls('admin.hrm.dashboard')); ?>">
                                <span class="flex items-center gap-3">
                                    <i data-lucide="building-2" class="nav-icon h-[18px] w-[18px]"></i>
                                    HRM Dashboard
                                </span>
                            </a>
                        <?php endif; ?>

                        
                        <?php if(has_permission(['employees.view', 'announcements.view', 'hrm_tasks.view'])): ?>
                            <?php if(has_permission('employees.view')): ?>
                                <a href="<?php echo e(route('admin.hrm.employees.index')); ?>"
                                    class="nav-item <?php echo e($navCls('admin.hrm.employees.*')); ?>">
                                    <span class="flex items-center gap-3">
                                        <i data-lucide="users" class="nav-icon h-[18px] w-[18px]"></i>
                                        Employees
                                    </span>
                                </a>
                            <?php endif; ?>

                            <?php if(has_permission('announcements.view')): ?>
                                <a href="<?php echo e(route('admin.hrm.announcements.index')); ?>"
                                    class="nav-item <?php echo e($navCls('admin.hrm.announcements.*')); ?>">
                                    <span class="flex items-center gap-3">
                                        <i data-lucide="megaphone" class="nav-icon h-[18px] w-[18px]"></i>
                                        Announcements
                                    </span>
                                </a>
                            <?php endif; ?>

                            <?php if(has_permission('hrm_tasks.view')): ?>
                                <a href="<?php echo e(route('admin.hrm.tasks.index')); ?>"
                                    class="nav-item <?php echo e($navCls('admin.hrm.tasks.*')); ?>">
                                    <span class="flex items-center gap-3">
                                        <i data-lucide="check-square" class="nav-icon h-[18px] w-[18px]"></i>
                                        Tasks
                                    </span>
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>

                        
                        <?php if(has_permission(['attendance.view', 'work_logs.view', 'leaves.view'])): ?>
                            <div class="acc-group"
                                data-open="<?php echo e($accOpen(['admin.hrm.attendance.*', 'admin.hrm.office-locations.*', 'admin.hrm.work-logs.*', 'admin.hrm.leaves.*', 'admin.hrm.leave-types.*', 'admin.hrm.leave-balances.*'])); ?>">
                                <button
                                    class="nav-item acc-trigger <?php echo e($navCls(['admin.hrm.attendance.*', 'admin.hrm.work-logs.*', 'admin.hrm.leaves.*', 'admin.hrm.leave-types.*', 'admin.hrm.leave-balances.*'])); ?>">
                                    <span class="flex items-center gap-3">
                                        <i data-lucide="clock" class="nav-icon h-[18px] w-[18px]"></i> Time &
                                        Attendance
                                    </span>
                                    <i data-lucide="chevron-right" class="nav-chevron"></i>
                                </button>
                                <div class="acc-wrap">
                                    <div class="sub-menu">
                                        <?php if(has_permission('attendance.view')): ?>
                                            <a href="<?php echo e(route('admin.hrm.attendance.today')); ?>"
                                                class="sub-item <?php echo e($subCls('admin.hrm.attendance.today')); ?>">
                                                <i data-lucide="calendar-check" class="h-4 w-4"></i> Today's
                                                Attendance
                                            </a>
                                        <?php endif; ?>
                                        <?php if(has_permission('attendance.report')): ?>
                                            <a href="<?php echo e(route('admin.hrm.attendance.report')); ?>"
                                                class="sub-item <?php echo e($subCls('admin.hrm.attendance.report')); ?>">
                                                <i data-lucide="bar-chart-3" class="h-4 w-4"></i> Attendance Report
                                            </a>
                                        <?php endif; ?>
                                        <?php if(has_permission('work_logs.view')): ?>
                                            <a href="<?php echo e(route('admin.hrm.work-logs.index')); ?>"
                                                class="sub-item <?php echo e($subCls('admin.hrm.work-logs.*')); ?>">
                                                <i data-lucide="timer" class="h-4 w-4"></i> Work Logs
                                            </a>
                                        <?php endif; ?>
                                        <?php if(has_permission('leaves.view')): ?>
                                            <a href="<?php echo e(route('admin.hrm.leaves.index')); ?>"
                                                class="sub-item <?php echo e($subCls('admin.hrm.leaves.*')); ?>">
                                                <i data-lucide="calendar-clock" class="h-4 w-4"></i> Leave Requests
                                            </a>
                                            <a href="<?php echo e(route('admin.hrm.leave-balances.index')); ?>"
                                                class="sub-item <?php echo e($subCls('admin.hrm.leave-balances.*')); ?>">
                                                <i data-lucide="pie-chart" class="h-4 w-4"></i> Leave Balances
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        
                        <?php if(has_permission(['salary_slips.view', 'salary_components.view'])): ?>
                            <div class="acc-group"
                                data-open="<?php echo e($accOpen(['admin.hrm.salary-slips.*', 'admin.hrm.salary-components.*'])); ?>">
                                <button
                                    class="nav-item acc-trigger <?php echo e($navCls(['admin.hrm.salary-slips.*', 'admin.hrm.salary-components.*'])); ?>">
                                    <span class="flex items-center gap-3">
                                        <i data-lucide="banknote" class="nav-icon h-[18px] w-[18px]"></i> Payroll
                                    </span>
                                    <i data-lucide="chevron-right" class="nav-chevron"></i>
                                </button>
                                <div class="acc-wrap">
                                    <div class="sub-menu">
                                        <?php if(has_permission('salary_slips.view')): ?>
                                            <a href="<?php echo e(route('admin.hrm.salary-slips.index')); ?>"
                                                class="sub-item <?php echo e($subCls('admin.hrm.salary-slips.*')); ?>">
                                                <i data-lucide="file-text" class="h-4 w-4"></i> Salary Slips
                                            </a>
                                        <?php endif; ?>
                                        <?php if(has_permission('salary_components.view')): ?>
                                            <a href="<?php echo e(route('admin.hrm.salary-components.index')); ?>"
                                                class="sub-item <?php echo e($subCls('admin.hrm.salary-components.*')); ?>">
                                                <i data-lucide="list-checks" class="h-4 w-4"></i> Components
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        
                        <?php if(has_permission(['departments.view', 'designations.view', 'shifts.view', 'holidays.view', 'attendance_rules.view'])): ?>
                            <div class="acc-group"
                                data-open="<?php echo e($accOpen(['admin.hrm.departments.*', 'admin.hrm.designations.*', 'admin.hrm.shifts.*', 'admin.hrm.holidays.*', 'admin.hrm.attendance-rules.*', 'admin.hrm.leave-types.*', 'admin.hrm.office-locations.*'])); ?>">
                                <button
                                    class="nav-item acc-trigger <?php echo e($navCls(['admin.hrm.departments.*', 'admin.hrm.designations.*', 'admin.hrm.shifts.*', 'admin.hrm.holidays.*', 'admin.hrm.attendance-rules.*', 'admin.hrm.leave-types.*'])); ?>">
                                    <span class="flex items-center gap-3">
                                        <i data-lucide="settings-2" class="nav-icon h-[18px] w-[18px]"></i> HRM
                                        Setup
                                    </span>
                                    <i data-lucide="chevron-right" class="nav-chevron"></i>
                                </button>
                                <div class="acc-wrap">
                                    <div class="sub-menu">
                                        <?php if(has_permission('departments.view')): ?>
                                            <a href="<?php echo e(route('admin.hrm.departments.index')); ?>"
                                                class="sub-item <?php echo e($subCls('admin.hrm.departments.*')); ?>">
                                                <i data-lucide="building-2" class="h-4 w-4"></i> Departments
                                            </a>
                                        <?php endif; ?>
                                        <?php if(has_permission('designations.view')): ?>
                                            <a href="<?php echo e(route('admin.hrm.designations.index')); ?>"
                                                class="sub-item <?php echo e($subCls('admin.hrm.designations.*')); ?>">
                                                <i data-lucide="id-card" class="h-4 w-4"></i> Designations
                                            </a>
                                        <?php endif; ?>
                                        <?php if(has_permission('shifts.view')): ?>
                                            <a href="<?php echo e(route('admin.hrm.shifts.index')); ?>"
                                                class="sub-item <?php echo e($subCls('admin.hrm.shifts.*')); ?>">
                                                <i data-lucide="clock-8" class="h-4 w-4"></i> Shifts
                                            </a>
                                        <?php endif; ?>
                                        <?php if(has_permission('holidays.view')): ?>
                                            <a href="<?php echo e(route('admin.hrm.holidays.index')); ?>"
                                                class="sub-item <?php echo e($subCls('admin.hrm.holidays.*')); ?>">
                                                <i data-lucide="calendar-days" class="h-4 w-4"></i> Holidays
                                            </a>
                                        <?php endif; ?>
                                        <?php if(has_permission('leaves.view')): ?>
                                            
                                            <a href="<?php echo e(route('admin.hrm.leave-types.index')); ?>"
                                                class="sub-item <?php echo e($subCls('admin.hrm.leave-types.*')); ?>">
                                                <i data-lucide="layers" class="h-4 w-4"></i> Leave Types
                                            </a>
                                        <?php endif; ?>
                                        <?php if(has_permission('attendance_rules.view')): ?>
                                            <a href="<?php echo e(route('admin.hrm.attendance-rules.index')); ?>"
                                                class="sub-item <?php echo e($subCls('admin.hrm.attendance-rules.*')); ?>">
                                                <i data-lucide="check-square" class="h-4 w-4"></i> Attendance Rules
                                            </a>
                                        <?php endif; ?>
                                        <?php if(has_permission('office_locations.view')): ?>
                                            <a href="<?php echo e(route('admin.hrm.office-locations.index')); ?>"
                                                class="sub-item <?php echo e($subCls('admin.hrm.office-locations.*')); ?>">
                                                <i data-lucide="map-pin" class="h-4 w-4"></i> Office Locations
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if(has_permission(['stores.view', 'payment_methods.view', 'roles.view', 'users.view', 'settings.view'])): ?>
                        <div class="nav-section-label">System</div>

                        <?php if(has_permission(['roles.view', 'users.view'])): ?>
                            <div class="acc-group" data-open="<?php echo e($accOpen(['admin.roles.*', 'admin.users.*'])); ?>">
                                <button
                                    class="nav-item acc-trigger <?php echo e($navCls(['admin.roles.*', 'admin.users.*'])); ?>">
                                    <span class="flex items-center gap-3">
                                        <i data-lucide="users" class="nav-icon h-[18px] w-[18px]"></i> User
                                        Management
                                    </span>
                                    <i data-lucide="chevron-right" class="nav-chevron"></i>
                                </button>

                                <div class="acc-wrap">
                                    <div class="sub-menu">
                                        
                                        <?php if(has_permission('roles.view')): ?>
                                            <a href="<?php echo e(route('admin.roles.index')); ?>"
                                                class="sub-item <?php echo e($subCls('admin.roles.*')); ?>">
                                                <i data-lucide="shield-check" class="h-4 w-4"></i>
                                                Roles & Permissions
                                            </a>
                                        <?php endif; ?>

                                        
                                        <?php if(has_permission('users.view')): ?>
                                            <a href="<?php echo e(route('admin.users.index')); ?>"
                                                class="sub-item <?php echo e($subCls('admin.users.*')); ?>">
                                                <i data-lucide="user" class="h-4 w-4"></i>
                                                Users
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        
                        <?php if(has_permission('stores.view')): ?>
                            <a href="<?php echo e(route('admin.stores.index')); ?>"
                                class="nav-item <?php echo e($navCls('admin.stores.*')); ?>">
                                <span class="flex items-center gap-3">
                                    <i data-lucide="store" class="nav-icon h-[18px] w-[18px]"></i>
                                    Stores / Outlets
                                </span>
                            </a>
                        <?php endif; ?>

                        <?php if(has_permission('payment_methods.view')): ?>
                            <a href="<?php echo e(route('admin.payment-methods.index')); ?>"
                                class="nav-item <?php echo e($navCls('admin.payment-methods.*')); ?>">
                                <span class="flex items-center gap-3">
                                    <i data-lucide="credit-card" class="nav-icon h-[18px] w-[18px]"></i>
                                    Payment Methods
                                </span>
                            </a>
                        <?php endif; ?>

                        <?php if(has_permission('settings.view')): ?>
                            <a href="<?php echo e(route('admin.settings.index')); ?>"
                                class="nav-item <?php echo e($navCls(['admin.settings.*'])); ?>">
                                <span class="flex items-center gap-3">
                                    <i data-lucide="settings" class="nav-icon h-[18px] w-[18px]"></i>
                                    Settings
                                </span>
                            </a>
                        <?php endif; ?>

                        <a href="<?php echo e(route('admin.help.index')); ?>"
                            class="nav-item <?php echo e($navCls('admin.help.index')); ?>">
                            <span class="flex items-center gap-3"><i data-lucide="help-circle"
                                    class="nav-icon h-[18px] w-[18px]"></i> Help
                                Center</span>
                        </a>

                        <?php if(has_module('storefront') && $companySlug): ?>
                            <a href="<?php echo e($companyStorefrontUrl); ?>" target="_blank" data-no-spa class="nav-item">
                                <span class="flex items-center gap-3">
                                    <i data-lucide="external-link" class="nav-icon h-[18px] w-[18px]"></i>
                                    Visit Site
                                </span>
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </nav>

                <?php if($subscription && is_company_admin()): ?>
                    <div class="subscription-box flex-shrink-0 px-3 py-3">
                        <div class="rounded-xl border border-[#fce4d6] bg-[#fff4ed] p-3.5 text-center">
                            <?php if($isLifetime): ?>
                                <p class="text-[11px] font-semibold text-gray-600">
                                    Plan:
                                    <span
                                        class="tracking-wider uppercase"><?php echo e($subscription->plan->name ?? 'Lifetime'); ?></span>
                                </p>
                                <p class="mt-0.5 text-sm font-bold text-[#e06623]">Never Expires</p>
                            <?php else: ?>
                                <p class="text-[11px] font-semibold text-gray-600">Expires:
                                    <?php echo e(\Carbon\Carbon::parse($expiresAt)->format('d M, Y')); ?></p>
                                <p class="mt-0.5 text-sm font-bold text-[#e06623]"><?php echo e($daysLeft); ?> days left</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </aside>

            
            <div class="relative flex min-w-0 flex-1 flex-col overflow-hidden">
                <header
                    class="sticky top-0 z-30 flex h-[60px] items-center justify-between border-b border-gray-100 bg-white px-5">
                    
                    <div class="flex min-w-0 flex-1 items-center gap-3">
                        <button id="hamburger-btn" onclick="toggleSidebar()"
                            class="flex h-9 w-9 flex-col items-center justify-center gap-[5px] rounded-lg transition-colors hover:bg-gray-100 focus:outline-none lg:hidden"
                            aria-label="Toggle menu">
                            <span class="hb-line"></span>
                            <span class="hb-line"></span>
                            <span class="hb-line"></span>
                        </button>
                        
                        <button onclick="toggleDesktopSidebar()"
                            class="hidden h-9 w-9 items-center justify-center rounded-lg text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-800 lg:flex">
                            <i data-lucide="menu" class="h-5 w-5"></i>
                        </button>
                        <div id="page-header-title" class="hidden sm:block"><?php echo $__env->yieldContent('header-title'); ?></div>
                        
                        <h1 id="page-header-title-mobile" data-app-suffix="<?php echo e($siteName); ?>"
                            class="min-w-0 flex-1 truncate text-sm font-bold text-gray-900 sm:hidden"></h1>
                    </div>

                    
                    <div class="ml-auto flex flex-shrink-0 items-center gap-2 sm:gap-4">
                        
                        
                        <?php if($canSwitchStore): ?>
                            <div x-data="{ open: false }" class="relative hidden sm:block">
                                <button @click="open = !open" type="button"
                                    class="flex items-center gap-1 rounded-lg border border-gray-100 bg-gray-50 px-2 py-1 text-[11px] font-semibold text-gray-700 transition-colors hover:bg-gray-100 sm:gap-2 sm:px-3 sm:py-1.5 sm:text-sm">
                                    <?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'store','class' => 'h-4 w-4 text-gray-400']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'store','class' => 'h-4 w-4 text-gray-400']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
                                    <span class="max-w-[80px] truncate sm:max-w-none">
                                        <?php echo e($currentStore->name ?? (is_company_admin() ? 'All Branches' : 'Select Store')); ?>

                                    </span>

                                    <?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'chevron-down','class' => 'h-4 w-4 text-gray-400']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'chevron-down','class' => 'h-4 w-4 text-gray-400']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
                                </button>
                                <div x-cloak x-show="open" @click.away="open = false" x-transition
                                    class="absolute right-0 z-50 mt-2 w-52 overflow-hidden rounded-lg border border-gray-100 bg-white shadow-xl">
                                    <?php $__currentLoopData = $stores; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $store): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <form method="POST" action="<?php echo e(route('admin.store.switch')); ?>">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="store_id" value="<?php echo e($store->id); ?>" />
                                            <button type="submit"
                                                class="w-full text-left px-4 py-2.5 text-[13px] hover:bg-gray-50 <?php echo e($currentStore && $currentStore->id == $store->id ? 'bg-brand-50/50 text-brand-700 font-bold border-l-2 border-brand-500' : 'text-gray-700 font-medium'); ?>">
                                                <?php echo e($store->name); ?>

                                            </button>
                                        </form>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </div>
                            </div>
                        <?php elseif($currentStore): ?>
                            <div
                                class="hidden items-center gap-2 rounded-lg border border-gray-100 bg-gray-50 px-3 py-1.5 text-sm font-semibold text-gray-600 sm:flex">
                                <?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'store','class' => 'h-4 w-4 text-gray-400']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'store','class' => 'h-4 w-4 text-gray-400']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
                                <span><?php echo e($currentStore->name); ?></span>
                            </div>
                        <?php endif; ?>

                        
                        <div x-data="notificationBell()" @click.outside="open = false"
                            class="relative flex items-center">
                            
                            <button @click="open = !open" type="button"
                                class="relative flex h-9 w-9 items-center justify-center rounded-full text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-800 focus:outline-none">
                                <?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'bell','class' => 'h-[18px] w-[18px]']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'bell','class' => 'h-[18px] w-[18px]']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>

                                
                                <span x-show="unreadCount > 0" x-cloak class="absolute -top-1 -right-1 flex h-4 w-4">
                                    <span
                                        class="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-400 opacity-75"></span>
                                    <span x-text="unreadCount > 9 ? '9+' : unreadCount"
                                        class="relative inline-flex h-4 w-4 items-center justify-center rounded-full border border-white bg-red-500 text-[9px] font-bold text-white"></span>
                                </span>
                            </button>

                            
                            <div x-cloak x-show="open" x-transition:enter="transition ease-out duration-150"
                                x-transition:enter-start="opacity-0 translate-y-1 scale-95"
                                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                x-transition:leave="transition ease-in duration-100"
                                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                                x-transition:leave-end="opacity-0 translate-y-1 scale-95"
                                class="absolute top-full right-0 z-50 mt-2.5 w-72 origin-top-right overflow-hidden rounded-xl border border-gray-100 bg-white shadow-xl">
                                
                                <div
                                    class="flex items-center justify-between border-b border-gray-100 bg-gray-50/50 px-4 py-3">
                                    <h3 class="text-[11px] font-black tracking-wider text-gray-500 uppercase">
                                        Notifications
                                    </h3>
                                    <span x-show="unreadCount > 0" x-cloak
                                        class="bg-brand-50 text-brand-600 border-brand-100 rounded-full border px-2 py-0.5 text-[9px] font-bold"
                                        x-text="unreadCount + ' New'"></span>
                                </div>

                                
                                <div id="notif-list" class="nav-scroll max-h-[320px] overflow-y-auto">
                                    
                                    <template x-for="item in notifications" :key="item.id">
                                        <a href="javascript:void(0)" @click="markAsRead(item.id, item.link)"
                                            class="block border-b border-gray-50 px-4 py-3 transition-colors hover:bg-gray-50">
                                            <div class="flex gap-3">
                                                
                                                <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg"
                                                    :class="`bg-${item.color}-50`" :data-lucide-icon="item.icon"
                                                    :data-lucide-color="item.color">
                                                    <div x-ignore
                                                        class="notif-icon-cell flex h-full w-full items-center justify-center">
                                                        <i class="h-4 w-4"></i>
                                                    </div>
                                                </div>

                                                <div class="min-w-0">
                                                    <p class="truncate text-[12px] leading-snug font-semibold text-gray-800"
                                                        x-text="item.title"></p>
                                                    <p class="mt-0.5 line-clamp-2 text-[11px] leading-relaxed text-gray-500"
                                                        x-text="item.message"></p>
                                                    <p
                                                        class="mt-1.5 flex items-center gap-1 text-[9px] font-medium text-gray-400">
                                                        <?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'clock','class' => 'h-3 w-3']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'clock','class' => 'h-3 w-3']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
                                                        <span x-text="item.time"></span>
                                                    </p>
                                                </div>
                                            </div>
                                        </a>
                                    </template>

                                    
                                    <div x-show="notifications.length === 0" x-cloak class="py-10 text-center">
                                        <div
                                            class="mx-auto mb-2 flex h-10 w-10 items-center justify-center rounded-full bg-gray-50">
                                            <?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'check-circle','class' => 'h-5 w-5 text-gray-300']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'check-circle','class' => 'h-5 w-5 text-gray-300']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
                                        </div>
                                        <p class="text-[11px] font-bold tracking-widest text-gray-400 uppercase">All
                                            caught up!</p>
                                    </div>
                                </div>

                                <a href="<?php echo e(route('admin.notifications.index')); ?>" @click="open = false"
                                    class="block border-t border-gray-100 bg-gray-50/50 px-4 py-2.5 text-center transition-colors hover:bg-gray-100">
                                    <span class="text-[11px] font-bold text-gray-600">View All History</span>
                                </a>
                            </div>
                        </div>

                        
                        <div x-data="{ open: false }" class="relative">
                            <button @click="open = !open" @click.away="open = false"
                                class="group flex items-center focus:outline-none">
                                <div class="relative flex h-9 w-9 items-center justify-center rounded-full text-sm font-bold text-white shadow-sm ring-2 ring-transparent transition-all duration-200 select-none group-hover:ring-[#82c43c]/30"
                                    style="background: var(--brand-600)">
                                    <?php echo e(strtoupper(substr(auth()->user()->name, 0, 1))); ?>

                                    <span
                                        class="absolute right-0 bottom-0 h-2.5 w-2.5 rounded-full border-2 border-white bg-[#4ade80]"></span>
                                </div>
                            </button>
                            <div x-cloak x-show="open" x-transition:enter="transition ease-out duration-150"
                                x-transition:enter-start="opacity-0 translate-y-1 scale-95"
                                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                x-transition:leave="transition ease-in duration-100"
                                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                                x-transition:leave-end="opacity-0 translate-y-1 scale-95"
                                class="absolute right-0 z-50 mt-2.5 w-56 origin-top-right overflow-hidden rounded-xl border border-gray-100 bg-white shadow-xl">
                                <div
                                    class="flex items-center gap-3 border-b border-gray-100 bg-gray-50/60 px-4 py-3.5">
                                    <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full text-sm font-bold text-white select-none"
                                        style="background: var(--brand-600)">
                                        <?php echo e(strtoupper(substr(auth()->user()->name, 0, 1))); ?>

                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-bold text-gray-900">
                                            <?php echo e(auth()->user()->name); ?></p>
                                        <p class="text-xs font-medium text-gray-500">
                                            <?php
                                                $user = auth()->user();
                                            ?>

                                            <?php if($user->isCompanyAdmin()): ?>
                                                <?php echo e($user->user_type->label()); ?>

                                            <?php else: ?>
                                                <?php echo e($user->roles->first()->name ?? $user->user_type->label()); ?>

                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="py-1.5">
                                    <form method="POST" action="<?php echo e(route('logout')); ?>">
                                        <?php echo csrf_field(); ?>
                                        <button type="submit"
                                            class="flex w-full items-center gap-3 px-4 py-2.5 text-sm font-semibold text-[#ef4444] transition-colors hover:bg-red-50">
                                            <i data-lucide="power" class="h-4 w-4"></i> Log Out
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </header>

                <main id="page-content" class="flex flex-1 flex-col overflow-x-hidden overflow-y-auto p-5">
                    <?php echo $__env->yieldContent('content'); ?>
                    <footer class="mt-auto py-6 text-center text-xs text-gray-400">
                        &copy; <?php echo e(date('Y')); ?> Powered by <span
                            class="font-semibold text-gray-600">Qlinkon</span>
                    </footer>
                </main>
            </div>

            
            <div x-data="announcementPopup()" x-cloak>
                <template x-if="announcements.length > 0">
                    <div class="fixed inset-0 z-[9999] flex items-center justify-center p-4 sm:p-6"
                        @keydown.escape.window="dismissCurrent()">
                        
                        <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"
                            :class="currentAnnouncement?.requires_acknowledgement ? '' : 'cursor-pointer'"
                            @click="!currentAnnouncement?.requires_acknowledgement && dismissCurrent()"></div>
                        
                        <div class="relative flex w-full max-w-[520px] animate-[slideUp_0.3s_ease-out] flex-col overflow-hidden rounded-[24px] bg-white shadow-2xl"
                            style="max-height: 90vh">
                            
                            <div
                                class="flex shrink-0 items-center justify-between border-b border-gray-50 bg-gray-50/30 px-6 py-5">
                                <div class="flex items-center gap-3">
                                    
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full"
                                        :style="'background:' +
                                        (currentAnnouncement?.type_color?.text || '#4b5563') +
                                        '15; color:' +
                                        (currentAnnouncement?.type_color?.text || '#4b5563')">
                                        <svg x-show="currentAnnouncement?.requires_acknowledgement" width="20"
                                            height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <line x1="12" y1="8" x2="12" y2="12">
                                            </line>
                                            <line x1="12" y1="16" x2="12.01" y2="16">
                                            </line>
                                        </svg>
                                        <svg x-show="!currentAnnouncement?.requires_acknowledgement" width="20"
                                            height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                                            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                                        </svg>
                                    </div>
                                    
                                    <div class="mt-0.5 flex flex-wrap items-center gap-2">
                                        <span class="text-[11px] font-black tracking-widest uppercase"
                                            :style="'color:' + (currentAnnouncement?.type_color?.text || '#4b5563')"
                                            x-text="currentAnnouncement?.type_label || 'GENERAL'"></span>
                                        <span x-show="currentAnnouncement?.requires_acknowledgement"
                                            class="rounded-md bg-[#fef2f2] px-2 py-0.5 text-[10px] font-black tracking-widest text-[#dc2626] uppercase">
                                            Mandatory
                                        </span>
                                    </div>
                                </div>
                                
                                <div x-show="announcements.length > 1"
                                    class="shrink-0 rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-bold text-gray-400">
                                    <span x-text="currentIndex + 1"></span> /
                                    <span x-text="announcements.length"></span>
                                </div>
                            </div>
                            
                            <div class="no-scrollbar flex-1 overflow-y-auto px-6 py-6">
                                
                                <div class="mb-5">
                                    <h2 class="mb-1 text-[20px] leading-tight font-black tracking-tight text-gray-900"
                                        x-text="currentAnnouncement?.title"></h2>
                                    <p class="text-[13px] font-medium text-gray-400"
                                        x-text="currentAnnouncement?.published_at"></p>
                                </div>
                                
                                <div class="prose prose-sm sm:prose-base max-w-none leading-relaxed text-[#4b5563]"
                                    x-html="currentAnnouncement?.content"></div>
                                
                                <template x-if="currentAnnouncement?.attachment_url">
                                    <a :href="currentAnnouncement.attachment_url" target="_blank"
                                        class="group mt-5 inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm font-bold text-gray-700 transition-all hover:bg-gray-100 hover:text-gray-900">
                                        <svg class="text-gray-400 transition-colors group-hover:text-gray-600"
                                            width="16" height="16" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round">
                                            <path
                                                d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48">
                                            </path>
                                        </svg>
                                        <span x-text="currentAnnouncement.attachment_name || 'View Attachment'"></span>
                                    </a>
                                </template>
                                
                                <template
                                    x-if="
                                            currentAnnouncement?.priority === 'critical' ||
                                            currentAnnouncement?.priority === 'high'
                                        ">
                                    <div class="mt-6 flex items-center gap-2.5 rounded-xl p-3.5 text-sm font-bold"
                                        :class="currentAnnouncement.priority === 'critical' ?
                                            'bg-[#fdf2f2] text-[#d32f2f]' :
                                            'bg-amber-50 text-amber-700'">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                                            stroke-linejoin="round">
                                            <path
                                                d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z">
                                            </path>
                                            <line x1="12" y1="9" x2="12" y2="13">
                                            </line>
                                            <line x1="12" y1="17" x2="12.01" y2="17">
                                            </line>
                                        </svg>
                                        <span
                                            x-text="
                                                    currentAnnouncement.priority === 'critical'
                                                        ? 'Critical Priority'
                                                        : 'High Priority'
                                                "></span>
                                    </div>
                                </template>
                            </div>
                            
                            <div class="flex shrink-0 items-center gap-3 border-t border-gray-100 bg-white px-6 py-5"
                                :class="currentAnnouncement?.requires_acknowledgement ?
                                    'justify-center' :
                                    'justify-end'">
                                
                                <button x-show="!currentAnnouncement?.requires_acknowledgement"
                                    @click="dismissCurrent()" :disabled="processing"
                                    class="rounded-[14px] px-5 py-3 text-[14px] font-bold text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-800 active:scale-95 disabled:opacity-50">
                                    Dismiss
                                </button>
                                
                                <button @click="acknowledgeCurrent()" :disabled="processing"
                                    class="flex items-center justify-center rounded-[14px] px-8 py-3.5 text-[15px] font-extrabold text-white transition-all active:scale-95"
                                    :class="processing
                                        ?
                                        'bg-gray-400 cursor-wait shadow-none' :
                                        currentAnnouncement?.requires_acknowledgement ?
                                        'bg-[#d32f2f] hover:bg-[#b71c1c] w-[260px]' :
                                        'bg-brand-600 hover:bg-brand-700 w-auto'">
                                    <span x-show="!processing"
                                        x-text="
                                                currentAnnouncement?.requires_acknowledgement
                                                    ? 'I Accept & Acknowledge'
                                                    : 'Got It'
                                            "></span>
                                    <span x-show="processing" class="flex items-center gap-2">
                                        <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                            <circle cx="12" cy="12" r="10" stroke="currentColor"
                                                stroke-width="4" opacity="0.25"></circle>
                                            <path fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                                        </svg>
                                        Processing...
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <script>
                /* ── Lucide global init ── */

                window.initIcons = function(scope) {
                    if (typeof lucide === "undefined") {
                        setTimeout(() => window.initIcons(scope), 80);
                        return;
                    }
                    lucide.createIcons(
                        scope ? {
                            nodes: scope.querySelectorAll("[data-lucide]"),
                        } :
                        undefined,
                    );
                };
                window.addEventListener("pageshow", function(event) {
                    if (event.persisted) {
                        if (typeof Swal !== "undefined") {
                            Swal.close();
                        }
                    }
                });
                document.addEventListener("visibilitychange", function() {
                    if (document.visibilityState === "visible") {
                        if (typeof Swal !== "undefined") {
                            Swal.close();
                        }
                    }
                });

                /* ── Page cover fade ── */
                (function() {
                    const cover = document.getElementById("page-cover");
                    document.addEventListener("DOMContentLoaded", () => {
                        window.initIcons();
                        requestAnimationFrame(() => {
                            cover.style.opacity = "0";
                            setTimeout(() => cover.remove(), 240);
                        });
                    });
                })();

                /* ── Accordion — functions exposed globally for navigate() ── */
                (function() {
                    const groups = Array.from(document.querySelectorAll(".acc-group"));

                    window.accOpen = function(group, instant) {
                        const wrap = group.querySelector(".acc-wrap");
                        const chevron = group.querySelector(".nav-chevron");
                        const trigger = group.querySelector(".acc-trigger");
                        if (instant) {
                            wrap.style.transition = "none";
                            wrap.style.maxHeight = wrap.scrollHeight + "px";
                            wrap.style.opacity = "1";
                            requestAnimationFrame(() =>
                                requestAnimationFrame(() => {
                                    wrap.style.transition = "";
                                }),
                            );
                        } else {
                            wrap.style.maxHeight = wrap.scrollHeight + "px";
                            wrap.style.opacity = "1";
                        }
                        chevron?.classList.add("rotated");
                        trigger?.classList.add("acc-open");
                        group._open = true;
                    };

                    window.accClose = function(group) {
                        const wrap = group.querySelector(".acc-wrap");
                        const chevron = group.querySelector(".nav-chevron");
                        const trigger = group.querySelector(".acc-trigger");
                        wrap.style.maxHeight = "0";
                        wrap.style.opacity = "0";
                        chevron?.classList.remove("rotated");
                        trigger?.classList.remove("acc-open");
                        group._open = false;
                    };

                    groups.forEach((group) => {
                        const shouldOpen = group.dataset.open === "true";
                        group._open = shouldOpen;
                        shouldOpen ? window.accOpen(group, true) : window.accClose(group);

                        group.querySelector(".acc-trigger")?.addEventListener("click", () => {
                            const isOpen = group._open;
                            groups.forEach((g) => {
                                if (g !== group && g._open) window.accClose(g);
                            });
                            isOpen ? window.accClose(group) : window.accOpen(group, false);
                        });
                    });
                })();

                /* ── Sidebar (mobile) ── */
                function toggleSidebar() {
                    document.getElementById("main-sidebar").classList.contains("sidebar-open") ?
                        closeSidebar() :
                        openSidebar();
                }

                function openSidebar() {
                    document.getElementById("main-sidebar").classList.add("sidebar-open");
                    document.getElementById("sidebar-overlay").classList.add("active");
                    document.getElementById("hamburger-btn").classList.add("is-open");
                    document.body.style.overflow = "hidden";
                }

                function closeSidebar() {
                    document.getElementById("main-sidebar").classList.remove("sidebar-open");
                    document.getElementById("sidebar-overlay").classList.remove("active");
                    document.getElementById("hamburger-btn")?.classList.remove("is-open");
                    document.body.style.overflow = "";
                }
                window.addEventListener("resize", () => {
                    if (window.innerWidth >= 1024) closeSidebar();
                });

                /* ══════════════════════════════════════════════════════
                           MOBILE HEADER TITLE — compact <title>-derived text shown
                           only below the sm breakpoint. Runs on initial load AND
                           after every SPA navigation (title swap), so every page
                           gets a working mobile title with zero per-page changes.
                        ══════════════════════════════════════════════════════ */
                function updateMobileHeaderTitle() {
                    const el = document.getElementById("page-header-title-mobile");
                    if (!el) return;
                    const suffix = el.dataset.appSuffix || "";
                    const suffixPattern = new RegExp(
                        "\\s*[—-]\\s*" + suffix.replace(/[.*+?^${}()|[\]\\]/g, "\\$&") + "\\s*$",
                        "i",
                    );
                    el.textContent = document.title.replace(suffixPattern, "").trim();
                }
                updateMobileHeaderTitle();

                /* ══════════════════════════════════════════════════════
                           SPA NAVIGATION ENGINE
                        ══════════════════════════════════════════════════════ */
                (function() {
                    const getContent = () => document.getElementById("page-content");
                    const getHeaderTitle = () => document.getElementById("page-header-title");
                    const progress = document.getElementById("nav-progress");

                    function progressStart() {
                        progress.style.opacity = "1";
                        progress.style.width = "40%";
                    }

                    function progressDone() {
                        progress.style.width = "100%";
                        setTimeout(() => {
                            progress.style.opacity = "0";
                            setTimeout(() => {
                                progress.style.width = "0%";
                            }, 300);
                        }, 200);
                    }

                    /* Re-execute <script> tags inside swapped content */
                    function rerunScripts(container) {
                        container.querySelectorAll("script").forEach((old) => {
                            const s = document.createElement("script");
                            [...old.attributes].forEach((a) => s.setAttribute(a.name, a.value));
                            s.textContent = old.textContent;
                            old.replaceWith(s);
                        });
                    }

                    /* Update sidebar active state after navigation */
                    function updateActiveNav(url) {
                        const path = new URL(url, location.origin).pathname;

                        document
                            .querySelectorAll("#sidebar-nav .active")
                            .forEach((el) => el.classList.remove("active"));
                        document
                            .querySelectorAll("#sidebar-nav .acc-open")
                            .forEach((el) => el.classList.remove("acc-open"));
                        document.querySelectorAll(".acc-group").forEach((g) => window.accClose(g));

                        document.querySelectorAll("#sidebar-nav a[href]").forEach((el) => {
                            try {
                                const href = el.getAttribute("href");
                                if (!href || href === "#") return;
                                const elPath = new URL(el.href, location.origin).pathname;

                                // ── Exact match OR prefix match (covers create/edit/show) ──
                                // path.startsWith(elPath + '/') ensures /admin/products matches
                                // /admin/products/create but NOT /admin/products-returns
                                let isMatch =
                                    path === elPath || (elPath.length > 7 && path.startsWith(elPath + "/"));

                                // Some screens have no nav item of their own and belong under
                                // another one — Categories under Products, the Production Map
                                // under Layout. Blade resolves that by route name, which this
                                // path-based pass cannot see, so the link declares those paths
                                // itself via data-owns.
                                if (!isMatch && el.dataset.owns) {
                                    isMatch = el.dataset.owns
                                        .split(",")
                                        .map((p) => p.trim())
                                        .filter(Boolean)
                                        .some((p) => path === p || path.startsWith(p + "/"));
                                }

                                if (!isMatch) return;

                                el.classList.add("active");
                                const group = el.closest(".acc-group");
                                if (group) {
                                    window.accOpen(group, false);
                                    group.querySelector(".acc-trigger")?.classList.add("acc-open");
                                }
                            } catch (_) {}
                        });
                    }

                    /* Core navigate function */
                    let isNavigating = false;

                    async function navigate(url, pushState = true) {
                        if (isNavigating) return;
                        isNavigating = true;

                        document.body.classList.remove("modal-open");
                        document.body.style.overflow = "";

                        try {
                            const t = new URL(url, location.origin);
                            if (t.origin !== location.origin) {
                                isNavigating = false;
                                return;
                            }
                        } catch (_) {
                            isNavigating = false;
                            return;
                        }

                        progressStart();

                        try {
                            const res = await fetch(url, {
                                headers: {
                                    "X-Requested-With": "XMLHttpRequest",
                                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content ??
                                        "",
                                },
                            });

                            progress.style.width = "70%";

                            if (res.redirected && res.url.includes("login")) {
                                isNavigating = false;
                                location.href = res.url;
                                return;
                            }

                            if (!res.ok) throw new Error("HTTP " + res.status);

                            const html = await res.text();
                            const parser = new DOMParser();
                            const doc = parser.parseFromString(html, "text/html");

                            const newContent = doc.getElementById("ajax-main-content");
                            const newHeader = doc.getElementById("ajax-header-content");
                            const newTitle = doc.querySelector("title");

                            if (!newContent) {
                                isNavigating = false;
                                location.href = url;
                                return;
                            }

                            // 1. Handle Dynamic Page Styles (From your code)
                            const ajaxStylesEl = doc.getElementById("ajax-styles");
                            if (ajaxStylesEl) {
                                const rawStyles = ajaxStylesEl.textContent.trim();
                                if (rawStyles) {
                                    let hash = 0;
                                    for (let i = 0; i < rawStyles.length; i++) {
                                        hash = (hash << 5) - hash + rawStyles.charCodeAt(i);
                                        hash |= 0;
                                    }
                                    const key = "dyn-style-" + Math.abs(hash);
                                    if (!document.getElementById(key)) {
                                        document
                                            .querySelectorAll("style[data-spa-page]")
                                            .forEach((s) => s.remove());
                                        const tmp = document.createElement("div");
                                        tmp.innerHTML = rawStyles;
                                        tmp.querySelectorAll("style").forEach((s) => {
                                            s.id = key;
                                            s.setAttribute("data-spa-page", "1");
                                            document.head.appendChild(s);
                                        });
                                    }
                                }
                            }

                            const pageRoot = getContent();

                            // 2. Destroy Old Alpine State (Robust version)
                            if (window.Alpine) {
                                pageRoot.querySelectorAll("[x-data]").forEach((el) => {
                                    try {
                                        if (typeof window.Alpine.destroyTree === "function") {
                                            window.Alpine.destroyTree(el);
                                        } else if (el._x_dataStack) {
                                            delete el._x_dataStack;
                                            delete el._x_effects;
                                        }
                                    } catch (_) {}
                                });
                            }

                            // 3. Swap Content & Meta
                            pageRoot.innerHTML = newContent.innerHTML;

                            if (newHeader && getHeaderTitle()) {
                                getHeaderTitle().innerHTML = newHeader.innerHTML;
                            }
                            if (newTitle) document.title = newTitle.textContent;
                            updateMobileHeaderTitle();
                            if (pushState)
                                history.pushState({
                                        url,
                                    },
                                    "",
                                    url,
                                );

                            pageRoot.scrollTop = 0;

                            // 4. App-Specific UI Updates
                            updateActiveNav(url);
                            if (window.innerWidth < 1024 && typeof closeSidebar === "function") {
                                closeSidebar();
                            }

                            // 5. Re-run scripts and initialize icons
                            rerunScripts(pageRoot);
                            window.initIcons(pageRoot);

                            // 6. The Paint & Initialize Sequence (The Future-Friendly Fix)
                            requestAnimationFrame(() => {
                                requestAnimationFrame(() => {
                                    try {
                                        if (window.Alpine && typeof window.Alpine.initTree === "function") {
                                            window.Alpine.initTree(pageRoot);
                                        }

                                        window.initIcons(pageRoot); // Catch any icons Alpine just generated

                                        window.dispatchEvent(
                                            new CustomEvent("spa:navigated", {
                                                detail: {
                                                    url,
                                                },
                                            }),
                                        );
                                        document.dispatchEvent(
                                            new CustomEvent("spa:page-loaded", {
                                                detail: {
                                                    url,
                                                },
                                            }),
                                        );
                                    } catch (initErr) {
                                        console.error("[spa:alpine-init] Component init error:", initErr);
                                    } finally {
                                        progressDone();
                                        isNavigating = false;
                                    }
                                });
                            });
                        } catch (err) {
                            console.error("[navigate] error:", err);
                            progressDone();
                            isNavigating = false;
                            location.href = url; // Hard fallback on crash
                        }
                    }

                    /* Intercept sidebar link clicks */
                    document.addEventListener("click", function(e) {
                        const link = e.target.closest("a[href]");
                        if (!link) return;

                        // Let browser handle modifier clicks (Ctrl+click, Cmd+click = new tab)
                        if (e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) return;
                        if (e.button !== 0) return;

                        const href = link.getAttribute("href");

                        if (!href || href === "#") return;
                        if (link.target === "_blank") return;
                        if (href.startsWith("mailto:")) return;
                        if (href.startsWith("tel:")) return;
                        if (href.startsWith("javascript:")) return;

                        try {
                            const url = new URL(href, location.origin);
                            if (url.origin !== location.origin) return;
                        } catch (_) {
                            return;
                        }

                        if (link.hasAttribute("data-no-spa")) return;

                        e.preventDefault();
                        navigate(link.href);
                    });

                    /* Browser back / forward */
                    // window.addEventListener('popstate', function(e) {
                    //     // Whether or not state exists, navigate to current URL
                    //     const url = e.state?.url || location.href;
                    //     navigate(url, false);
                    // });
                    // Browser back / forward
                    window.addEventListener("popstate", function(e) {
                        const url = e.state?.url || location.href;

                        // If navigating back to/from the POS (or any standalone page),
                        // do a hard reload instead of AJAX — they don't share the admin layout
                        const standaloneRoutes = ["/admin/pos"];
                        const currentIsSpa = !standaloneRoutes.some((r) => location.pathname.startsWith(r));
                        const targetIsSpa = !standaloneRoutes.some((r) =>
                            new URL(url, location.origin).pathname.startsWith(r),
                        );

                        if (!currentIsSpa || !targetIsSpa) {
                            location.href = url;
                            return;
                        }

                        navigate(url, false);
                    });
                    /* Set initial history state */
                    history.replaceState({
                            url: location.href,
                        },
                        "",
                        location.href,
                    );

                    /* Safety net: bfcache restore can freeze isNavigating (or
                       any future stuck flag) at whatever value it held when
                       the tab was last navigated away — self-heal so back/
                       forward from a hard-reloaded page never leaves clicks
                       silently dead. */
                    window.addEventListener("pageshow", function(e) {
                        if (e.persisted) {
                            isNavigating = false;
                            progressDone();
                        }
                    });
                })();
            </script>

            <script src="<?php echo e(asset('assets/js/swal.js')); ?>"></script>

            <audio id="qlinkon-notif-audio" src="<?php echo e(asset('assets/audio/notification.mp3')); ?>" preload="auto"
                style="display: none"></audio>
            <script>
                window.announcementPopup = function() {
                    return {
                        announcements: [],
                        currentIndex: 0,
                        processing: false,
                        loaded: false,

                        get currentAnnouncement() {
                            return this.announcements[this.currentIndex] || null;
                        },

                        async init() {
                            // Small delay to not block initial page paint
                            await new Promise((r) => setTimeout(r, 800));
                            await this.fetchPending();

                            // Re-check after every SPA navigation
                            window.addEventListener("spa:navigated", () => {
                                // Only re-fetch if popup is not currently showing
                                if (this.announcements.length === 0) {
                                    this.fetchPending();
                                }
                            });

                            // Re-check when attendance scanner requests it
                            window.addEventListener("announcements:recheck", () => {
                                this.fetchPending();
                            });
                        },

                        async fetchPending() {
                            try {
                                const res = await fetch("<?php echo e(url('admin/announcements-popup/pending')); ?>", {
                                    headers: {
                                        Accept: "application/json",
                                        "X-Requested-With": "XMLHttpRequest",
                                    },
                                });
                                if (!res.ok) return;
                                const json = await res.json();
                                this.announcements = json.data || [];

                                // Mark first one as read
                                if (this.announcements.length > 0) {
                                    this.markRead(this.announcements[0].id);
                                }
                            } catch (e) {
                                console.error("Announcement popup fetch failed:", e);
                            }
                        },

                        async markRead(id) {
                            try {
                                await fetch(`<?php echo e(url('admin/announcements-popup')); ?>/${id}/read`, {
                                    method: "POST",
                                    headers: {
                                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                                        Accept: "application/json",
                                        "X-Requested-With": "XMLHttpRequest",
                                    },
                                });
                            } catch (_) {}
                        },

                        async acknowledgeCurrent() {
                            const ann = this.currentAnnouncement;
                            if (!ann || this.processing) return;

                            this.processing = true;
                            try {
                                const endpoint = ann.requires_acknowledgement ? "acknowledge" : "dismiss";
                                const res = await fetch(`<?php echo e(url('admin/announcements-popup')); ?>/${ann.id}/${endpoint}`, {
                                    method: "POST",
                                    headers: {
                                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                                        Accept: "application/json",
                                        "X-Requested-With": "XMLHttpRequest",
                                    },
                                });

                                if (res.ok) {
                                    this.removeCurrentAndAdvance();
                                }
                            } catch (e) {
                                console.error("Acknowledge failed:", e);
                            } finally {
                                this.processing = false;
                            }
                        },

                        async dismissCurrent() {
                            const ann = this.currentAnnouncement;
                            if (!ann || this.processing) return;

                            // Mandatory cannot be dismissed
                            if (ann.requires_acknowledgement) return;

                            this.processing = true;
                            try {
                                const res = await fetch(`<?php echo e(url('admin/announcements-popup')); ?>/${ann.id}/dismiss`, {
                                    method: "POST",
                                    headers: {
                                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                                        Accept: "application/json",
                                        "X-Requested-With": "XMLHttpRequest",
                                    },
                                });

                                if (res.ok) {
                                    this.removeCurrentAndAdvance();
                                }
                            } catch (e) {
                                console.error("Dismiss failed:", e);
                            } finally {
                                this.processing = false;
                            }
                        },

                        removeCurrentAndAdvance() {
                            this.announcements.splice(this.currentIndex, 1);

                            if (this.announcements.length === 0) {
                                this.currentIndex = 0;
                                return;
                            }

                            if (this.currentIndex >= this.announcements.length) {
                                this.currentIndex = 0;
                            }

                            // Mark next one as read
                            if (this.announcements[this.currentIndex]) {
                                this.markRead(this.announcements[this.currentIndex].id);
                            }
                        },
                    };
                };
                /* ── Desktop Minimized Sidebar ── */
                function toggleDesktopSidebar() {
                    const sidebar = document.getElementById("main-sidebar");
                    sidebar.classList.toggle("is-minimized");
                    // Save preference so it remembers across page loads
                    localStorage.setItem("sidebar_minimized", sidebar.classList.contains("is-minimized"));
                }

                // Restore state immediately on load
                if (localStorage.getItem("sidebar_minimized") === "true") {
                    document.getElementById("main-sidebar").classList.add("is-minimized");
                }
            </script>

            <style>
                @keyframes slideUp {
                    from {
                        opacity: 0;
                        transform: translateY(30px) scale(0.97);
                    }

                    to {
                        opacity: 1;
                        transform: translateY(0) scale(1);
                    }
                }
            </style>

            <script>
                window.notificationBell = function() {
                    return {
                        open: false,
                        _timer: null,
                        _inFlight: false,
                        _visibilityHandler: null,
                        audioPlayer: null,
                        unreadCount: <?php echo json_encode($unreadCount, 15, 512) ?>,
                        notifications: <?php echo json_encode($notifItems, 15, 512) ?>,
                        latestId: <?php echo json_encode($notifLatestId, 15, 512) ?>,

                        init() {
                            this._renderIcons();
                            this.audioPlayer = new Audio("<?php echo e(asset('assets/audio/notification.mp3')); ?>");
                            this.audioPlayer.preload = "auto";

                            // 🌟 ROOT FIX: Browser Autoplay Policy Audio Unlocker
                            // This silently plays/pauses the audio on the user's first click,
                            // permanently unlocking the audio object for your background interval.
                            const unlockAudio = () => {
                                this.audioPlayer.volume = 0; // Mute it so they don't hear a blip
                                let playPromise = this.audioPlayer.play();

                                if (playPromise !== undefined) {
                                    playPromise
                                        .then(() => {
                                            this.audioPlayer.pause();
                                            this.audioPlayer.currentTime = 0;
                                            this.audioPlayer.volume = 1; // Unmute for the real notifications
                                        })
                                        .catch(() => {
                                            // Silently handle exceptions if the browser is being overly strict
                                        });
                                }
                            };

                            // Attach to the first time the user touches the page anywhere
                            document.addEventListener("click", unlockAudio, {
                                once: true,
                            });
                            document.addEventListener("keydown", unlockAudio, {
                                once: true,
                            });
                            document.addEventListener("touchstart", unlockAudio, {
                                once: true,
                            });

                            // Defensive guard: if a previous instance of this component is
                            // still alive on this page, kill its timer before starting a new
                            // one. Exactly one polling loop should exist per page.
                            if (window.__qlinkonNotifTimer) {
                                clearTimeout(window.__qlinkonNotifTimer);
                                window.__qlinkonNotifTimer = null;
                            }

                            // Hidden tabs must not poll. Coming back to the tab does one
                            // immediate fetch so the bell is current, then resumes the loop.
                            this._visibilityHandler = () => {
                                if (document.hidden) {
                                    this._stopPolling();
                                } else {
                                    this._poll().finally(() => this._scheduleNext());
                                }
                            };
                            document.addEventListener("visibilitychange", this._visibilityHandler);

                            this._scheduleNext();
                        },

                        // ── Timer control ────────────────────────────────────────
                        _stopPolling() {
                            if (this._timer) {
                                clearTimeout(this._timer);
                            }
                            this._timer = null;
                            window.__qlinkonNotifTimer = null;
                        },

                        // Self-scheduling timer: the next poll is only queued once the
                        // previous one has fully settled, so requests can never stack up
                        // the way a fixed setInterval allows.
                        _scheduleNext() {
                            this._stopPolling();

                            if (document.hidden) {
                                return;
                            }

                            this._timer = window.__qlinkonNotifTimer = setTimeout(() => {
                                this._poll().finally(() => this._scheduleNext());
                            }, 30_000);
                        },

                        destroy() {
                            this._stopPolling();
                            if (this._visibilityHandler) {
                                document.removeEventListener("visibilitychange", this._visibilityHandler);
                                this._visibilityHandler = null;
                            }
                        },

                        // ── Polling ──────────────────────────────────────────────
                        async _poll() {
                            // In-flight guard: never allow a second request to start while
                            // one is still open, regardless of what triggered it.
                            if (this._inFlight) {
                                return;
                            }
                            this._inFlight = true;

                            try {
                                const res = await fetch("<?php echo e(route('admin.notifications.fetch-recent')); ?>", {
                                    headers: {
                                        "X-Requested-With": "XMLHttpRequest",
                                        Accept: "application/json",
                                    },
                                });
                                if (!res.ok) {
                                    return;
                                }
                                const data = await res.json();

                                const prevCount = this.unreadCount;
                                const prevLatestId = this.latestId;
                                const nextCount = data.count ?? 0;
                                const nextLatestId = data.latest_id ?? null;

                                // Nothing changed — skip the state assignment entirely so
                                // Alpine does not re-render the list and Lucide does not
                                // rebuild icons every 30 seconds for identical data.
                                if (nextCount === prevCount && nextLatestId === prevLatestId) {
                                    return;
                                }

                                this.notifications = data.items ?? [];
                                this.unreadCount = nextCount;
                                this.latestId = nextLatestId;

                                if (nextLatestId && nextLatestId !== prevLatestId && nextCount > prevCount) {
                                    this._notify(data.items?.[0]);
                                }

                                this._renderIcons();
                            } catch (_) {
                                /* network blip — silently ignore */
                            } finally {
                                this._inFlight = false;
                            }
                        },

                        // ── New-notification alert ───────────────────────────────
                        _notify(item) {
                            const title = item?.title ?? "New Notification";
                            const msg = item?.message ?? "";

                            if (typeof BizAlert !== "undefined" && BizAlert.toast) {
                                BizAlert.toast(title + (msg ? ": " + msg : ""), "info");
                            }

                            // No recipient check here on purpose. The bell only ever
                            // holds this user's own notifications, and who receives an
                            // event was already decided server-side by
                            // NotificationDispatcher via Settings > Notifications.
                            // Re-checking on the client could only ever disagree with it.
                            const audioEl = document.getElementById("qlinkon-notif-audio");

                            if (!audioEl) {
                                return;
                            }

                            try {
                                audioEl.currentTime = 0;
                                audioEl.volume = 1;
                                const playPromise = audioEl.play();

                                if (playPromise !== undefined) {
                                    // Browsers block autoplay until the page has been
                                    // interacted with; a silent notification is fine.
                                    playPromise.catch(() => {});
                                }
                            } catch (err) {
                                // Audio is decorative — never let it break the bell.
                            }
                        },

                        // ── Lucide icon renderer ─────────────────────────────────
                        _renderIcons() {
                            this.$nextTick(() => {
                                document.querySelectorAll("#notif-list [data-lucide-icon] .notif-icon-cell i").forEach((
                                    el) => {
                                    const wrapper = el.closest("[data-lucide-icon]");
                                    const iconName = wrapper?.getAttribute("data-lucide-icon") || "bell";
                                    const color = wrapper?.getAttribute("data-lucide-color") || "blue";
                                    el.setAttribute("data-lucide", iconName);
                                    el.className = `w-4 h-4 text-${color}-600`;
                                });

                                if (window.lucide) {
                                    lucide.createIcons({
                                        nodes: document.querySelectorAll("#notif-list [data-lucide]"),
                                    });
                                }
                            });
                        },

                        // ── Mark as read + navigate ──────────────────────────────
                        async markAsRead(id, link) {
                            try {
                                await fetch(`/admin/notifications/${id}/read`, {
                                    method: "POST",
                                    headers: {
                                        "X-CSRF-TOKEN": "<?php echo e(csrf_token()); ?>",
                                        "X-Requested-With": "XMLHttpRequest",
                                    },
                                });
                            } catch (_) {
                                /* best-effort */
                            }

                            window.location.href = link || "#";
                        },
                    };
                };
            </script>

            <script>
                /**
                 * Alpine factory for the x-custom-select component.
                 * Kept global (window.customSelect) — same pattern as invoiceIndex(),
                 * purchaseIndex(), etc. — so it survives SPA page swaps and re-inits
                 * cleanly via Alpine.initTree() without re-registering.
                 */
                window.customSelect = function({
                    options = {},
                    icons = {},
                    initial = "",
                    placeholder = "Select",
                }) {
                    return {
                        options,
                        icons,
                        placeholder,
                        value: initial,
                        open: false,

                        get optionList() {
                            return Object.entries(this.options).map(([value, label]) => ({
                                value,
                                label,
                            }));
                        },

                        get label() {
                            return this.value === "" ?
                                this.placeholder :
                                (this.options[this.value] ?? this.placeholder);
                        },

                        select(val) {
                            this.value = val;
                            this.open = false;

                            this.$nextTick(() => {
                                if (this.$refs.nativeSelect) {
                                    this.$refs.nativeSelect.value = val;
                                    this.$refs.nativeSelect.dispatchEvent(
                                        new Event("change", {
                                            bubbles: true,
                                        }),
                                    );
                                }
                                if (typeof lucide !== "undefined") lucide.createIcons();
                            });
                        },

                        init() {
                            this.$nextTick(() => {
                                if (typeof lucide !== "undefined") lucide.createIcons();
                            });
                        },
                    };
                };

                /**
                 * Focus and highlight an x-custom-select that failed validation.
                 *
                 * The component's own required attribute sits on a visually
                 * hidden, aria-hidden select. Chrome refuses to focus that and
                 * blocks the submit with a console error instead of showing
                 * anything, so the native required path is unusable here and
                 * the trigger button is focused directly.
                 *
                 * Located through the hidden select's id, which is stable —
                 * the trigger's own id carries a random suffix.
                 *
                 * @param {string} fieldId  The component's name/id.
                 * @returns {boolean} false, so callers can `return`.
                 */
                window.focusCustomSelect = function(fieldId) {
                    const nativeSelect = document.getElementById(fieldId);
                    if (!nativeSelect) return false;

                    const trigger = nativeSelect.parentElement?.querySelector('button[role="combobox"]');
                    if (!trigger) return false;

                    // Classes chosen so they do not collide with the trigger's
                    // own :class binding, which would strip them on open.
                    const errorClasses = ["border-red-400", "ring-2", "ring-red-200"];
                    trigger.classList.add(...errorClasses);

                    trigger.scrollIntoView({
                        behavior: "smooth",
                        block: "center"
                    });
                    trigger.focus({
                        preventScroll: true
                    });

                    // Clear on the next selection, and on blur so the field does
                    // not stay red once the user has moved on.
                    const clear = () => {
                        trigger.classList.remove(...errorClasses);
                        nativeSelect.removeEventListener("change", clear);
                        trigger.removeEventListener("blur", clear);
                    };

                    nativeSelect.addEventListener("change", clear);
                    trigger.addEventListener("blur", clear);

                    return false;
                };
            </script>

            <?php echo $__env->yieldPushContent('scripts'); ?>
            
            <script src="<?php echo e(asset('assets/js/lucide.min.js')); ?>"></script>
            <script src="<?php echo e(asset('assets/js/sweetalert2.js')); ?>"></script>
            <script src="<?php echo e(asset('assets/js/number-input-guard.js')); ?>"></script>
            
            <script src="<?php echo e(asset_v('assets/js/helpers.js')); ?>"></script>
            <script src="<?php echo e(asset('assets/js/alpinejs.min.js')); ?>"></script>
    </body>

    </html>
<?php endif; ?>
<?php /**PATH C:\Users\qlinkongraphics\Desktop\MyLab\plantiq-local\resources\views/layouts/admin.blade.php ENDPATH**/ ?>