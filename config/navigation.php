<?php

/**
 * Catalogue of everything the dashboard launcher can link to.
 *
 * Single source of truth for the home screen tiles, the category tabs and the
 * client-side search index. Adding a destination means adding one row here —
 * the Blade never changes.
 *
 * Each row:
 *   label       Shown on the tile and matched by search
 *   route       Laravel route name (skipped silently if it does not exist)
 *   icon        Lucide icon name
 *   category    Key from the categories map below
 *   module      Module slug required, or null when always available
 *   permission  Permission slug required, or null when none
 *   requires    Extra condition beyond module/permission. Currently only
 *               'employee', meaning the user must have an HR profile.
 *   quick       Whether it also appears on the "Quick" tab
 *   keywords    Extra search terms that are not in the label
 */
return [

    'categories' => [
        'quick'      => 'Quick',
        'sales'      => 'Sales',
        'inventory'  => 'Inventory',
        'production' => 'Production',
        'crm'        => 'CRM',
        'contacts'   => 'Contacts',
        'hrm'        => 'HRM',
        'web'        => 'Web',
        'system'     => 'System',
        'tools'      => 'Tools',
    ],

    'actions' => [

        // ── My Work (employee self-service) ──
        //
        // Gated on 'employee' rather than a module or permission: these pages
        // show a person their own records, so having an HR profile is the only
        // thing that qualifies. They stay off the launcher for logins with no
        // profile, where every one of them would be an empty page.
        ['label' => 'My Work',         'route' => 'admin.employee.dashboard',          'icon' => 'layout-dashboard', 'category' => 'quick', 'module' => null, 'permission' => null, 'requires' => 'employee', 'quick' => true, 'keywords' => 'my dashboard personal'],
        ['label' => 'My Attendance',   'route' => 'admin.hrm.my-attendance.index',     'icon' => 'clock',            'category' => 'quick', 'module' => null, 'permission' => null, 'requires' => 'employee', 'quick' => true, 'keywords' => 'check in out punch'],
        ['label' => 'My Tasks',        'route' => 'admin.hrm.my-tasks.index',          'icon' => 'check-square',     'category' => 'quick', 'module' => null, 'permission' => null, 'requires' => 'employee', 'quick' => true, 'keywords' => 'todo assigned work'],
        ['label' => 'My Leaves',       'route' => 'admin.hrm.my-leaves.index',         'icon' => 'calendar-off',     'category' => 'quick', 'module' => null, 'permission' => null, 'requires' => 'employee', 'quick' => true, 'keywords' => 'time off holiday apply'],
        ['label' => 'My Work Logs',    'route' => 'admin.hrm.my-work-logs.index',      'icon' => 'clipboard-list',   'category' => 'quick', 'module' => null, 'permission' => null, 'requires' => 'employee', 'quick' => true, 'keywords' => 'timesheet hours'],
        ['label' => 'My Salary Slips', 'route' => 'admin.hrm.my-salary-slips.index',   'icon' => 'banknote',         'category' => 'quick', 'module' => null, 'permission' => null, 'requires' => 'employee', 'quick' => true, 'keywords' => 'payslip payroll salary'],

        // ── Sales & Finance ──
        ['label' => 'POS Counter',        'route' => 'admin.pos.index',              'icon' => 'calculator',    'category' => 'sales', 'module' => 'pos',       'permission' => 'pos.access',        'quick' => true,  'keywords' => 'billing counter checkout'],
        ['label' => 'Sales Dashboard',    'route' => 'admin.sales.dashboard',        'icon' => 'trending-up',   'category' => 'sales', 'module' => 'invoicing', 'permission' => 'sales_dashboard.view', 'quick' => false, 'keywords' => 'revenue finance overview'],
        ['label' => 'Invoices',           'route' => 'admin.invoices.index',         'icon' => 'file-text',     'category' => 'sales', 'module' => 'invoicing', 'permission' => 'invoices.view',     'quick' => true,  'keywords' => 'bill tax gst'],
        ['label' => 'Invoice Returns',    'route' => 'admin.invoice-returns.index',  'icon' => 'undo-2',        'category' => 'sales', 'module' => 'invoicing', 'permission' => 'invoices.view',     'quick' => false, 'keywords' => 'credit note refund'],
        ['label' => 'Quotations',         'route' => 'admin.quotations.index',       'icon' => 'file-signature','category' => 'sales', 'module' => 'invoicing', 'permission' => 'quotations.view',   'quick' => false, 'keywords' => 'estimate proposal'],
        ['label' => 'Challans',           'route' => 'admin.challans.index',         'icon' => 'truck',         'category' => 'sales', 'module' => 'invoicing', 'permission' => 'challans.view',     'quick' => false, 'keywords' => 'delivery note'],
        ['label' => 'Challan Returns',    'route' => 'admin.challan-returns.index',  'icon' => 'undo-2',        'category' => 'sales', 'module' => 'invoicing', 'permission' => 'challans.view',     'quick' => false, 'keywords' => null],
        ['label' => 'Customer Ledger',    'route' => 'admin.ledger.index',           'icon' => 'book-open',     'category' => 'sales', 'module' => 'invoicing', 'permission' => 'invoices.view',     'quick' => false, 'keywords' => 'account statement outstanding'],
        ['label' => 'Sales Reports',      'route' => 'admin.reports.index',          'icon' => 'bar-chart-3',   'category' => 'sales', 'module' => 'invoicing', 'permission' => 'invoices.view',     'quick' => false, 'keywords' => 'analytics'],
        ['label' => 'Purchases',          'route' => 'admin.purchases.index',        'icon' => 'shopping-bag',  'category' => 'sales', 'module' => 'purchases', 'permission' => 'purchases.view',    'quick' => false, 'keywords' => 'supplier bill'],
        ['label' => 'Purchase Returns',   'route' => 'admin.purchase-returns.index', 'icon' => 'undo-2',        'category' => 'sales', 'module' => 'purchases', 'permission' => 'purchase_returns.view', 'quick' => false, 'keywords' => 'debit note'],
        ['label' => 'Expenses',           'route' => 'admin.expenses.index',         'icon' => 'wallet',        'category' => 'sales', 'module' => 'expenses',  'permission' => 'expenses.view',     'quick' => true,  'keywords' => 'spend cost'],
        ['label' => 'Orders',             'route' => 'admin.orders.index',           'icon' => 'inbox',         'category' => 'sales', 'module' => 'inquiry',   'permission' => 'inquiries.view',    'quick' => false, 'keywords' => 'inquiry enquiry'],

        // ── Inventory ──
        ['label' => 'Products',           'route' => 'admin.products.index',            'icon' => 'package',      'category' => 'inventory', 'module' => 'inventory', 'permission' => 'products.view',           'quick' => false, 'keywords' => 'catalog sku item'],
        ['label' => 'Warehouses',         'route' => 'admin.warehouses.index',          'icon' => 'warehouse',    'category' => 'inventory', 'module' => 'inventory', 'permission' => 'warehouses.view',         'quick' => false, 'keywords' => 'godown'],
        ['label' => 'Stock Adjustments',  'route' => 'admin.stock-adjustments.index',   'icon' => 'sliders',      'category' => 'inventory', 'module' => 'inventory', 'permission' => 'stock_adjustments.view',  'quick' => false, 'keywords' => 'damage correction'],
        ['label' => 'Inventory Reports',  'route' => 'admin.inventory.reports.index',   'icon' => 'clipboard-list','category' => 'inventory','module' => 'inventory', 'permission' => 'inventory_reports.view',  'quick' => false, 'keywords' => 'stock valuation'],

        // ── Production ──
        ['label' => 'Production Dashboard','route' => 'admin.production.dashboard',              'icon' => 'sprout',      'category' => 'production', 'module' => 'production', 'permission' => null, 'quick' => false, 'keywords' => 'plantiq nursery'],
        ['label' => 'Plant Batches',       'route' => 'admin.production.plant-batches.index',    'icon' => 'boxes',       'category' => 'production', 'module' => 'production', 'permission' => null, 'quick' => true,  'keywords' => 'lot crop'],
        ['label' => 'Harvest Lots',        'route' => 'admin.production.harvest-lots.index',     'icon' => 'scissors',    'category' => 'production', 'module' => 'production', 'permission' => null, 'quick' => true,  'keywords' => 'picking yield'],
        ['label' => 'Production Plans',    'route' => 'admin.production.plans.index',            'icon' => 'calendar-range','category' => 'production','module' => 'production', 'permission' => null, 'quick' => false, 'keywords' => 'schedule'],
        ['label' => 'Layout',              'route' => 'admin.production.layout.index',           'icon' => 'layout-grid', 'category' => 'production', 'module' => 'production', 'permission' => null, 'quick' => false, 'keywords' => 'sites zones growing space'],
        ['label' => 'Activity Templates',  'route' => 'admin.production.activity-templates.index','icon' => 'list-checks','category' => 'production', 'module' => 'production', 'permission' => null, 'quick' => false, 'keywords' => 'task template'],
        ['label' => 'Zone Assignments',    'route' => 'admin.production.zone-assignments.index', 'icon' => 'users',       'category' => 'production', 'module' => 'production', 'permission' => null, 'quick' => false, 'keywords' => 'worker allocation'],

        // ── CRM ──
        ['label' => 'CRM Dashboard',      'route' => 'admin.crm.dashboard',        'icon' => 'gauge',      'category' => 'crm', 'module' => 'crm', 'permission' => null,          'quick' => false, 'keywords' => null],
        ['label' => 'All Leads',          'route' => 'admin.crm.leads.index',      'icon' => 'user-plus',  'category' => 'crm', 'module' => 'crm', 'permission' => 'leads.view',  'quick' => true,  'keywords' => 'prospect enquiry'],
        ['label' => 'Pipelines',          'route' => 'admin.crm.pipelines.index',  'icon' => 'git-branch', 'category' => 'crm', 'module' => 'crm', 'permission' => null,          'quick' => false, 'keywords' => 'stages funnel'],
        ['label' => 'Lead Sources',       'route' => 'admin.crm.sources.index',    'icon' => 'radio',      'category' => 'crm', 'module' => 'crm', 'permission' => null,          'quick' => false, 'keywords' => null],
        ['label' => 'Tags',               'route' => 'admin.crm.tags.index',       'icon' => 'tag',        'category' => 'crm', 'module' => 'crm', 'permission' => null,          'quick' => false, 'keywords' => null],
        // Projects is its own licensable module, not part of CRM. Gating these
        // on 'crm' hid them from tenants who bought Projects alone, and showed
        // them to CRM-only tenants whose clicks then hit a 403 from
        // module:projects. The permission slugs must match PermissionSeeder.
        ['label' => 'Team Performance',   'route' => 'admin.crm.performance.index', 'icon' => 'bar-chart-3', 'category' => 'crm', 'module' => 'crm', 'permission' => 'crm_reports.view', 'quick' => true, 'keywords' => 'employee report productivity tracking calls'],
        ['label' => 'Active Projects',    'route' => 'admin.projects.index',  'icon' => 'folder-kanban','category' => 'crm','module' => 'projects',  'permission' => 'projects.view',          'quick' => true,  'keywords' => 'amc renewal'],
        ['label' => 'Services',           'route' => 'admin.services.index',  'icon' => 'wrench',      'category' => 'crm', 'module' => 'projects', 'permission' => 'project_services.view',  'quick' => false, 'keywords' => 'catalog price list'],
        ['label' => 'Renewals',           'route' => 'admin.project_renewals.index', 'icon' => 'calendar-clock', 'category' => 'crm', 'module' => 'projects', 'permission' => 'project_client_services.view', 'quick' => true, 'keywords' => 'expiry due amc hosting domain renew'],


        // ── Contacts & Projects ──
        ['label' => 'Clients',            'route' => 'admin.clients.index',   'icon' => 'contact',     'category' => 'contacts', 'module' => null,        'permission' => 'clients.view',   'quick' => false, 'keywords' => 'customer party'],
        ['label' => 'Suppliers',          'route' => 'admin.suppliers.index', 'icon' => 'factory',     'category' => 'contacts', 'module' => 'purchases', 'permission' => 'suppliers.view', 'quick' => false, 'keywords' => 'vendor'],        

        // ── HRM ──
        ['label' => 'HRM Dashboard',      'route' => 'admin.hrm.dashboard',            'icon' => 'gauge',        'category' => 'hrm', 'module' => 'hrm', 'permission' => 'hrm_dashboard.view', 'quick' => false, 'keywords' => null],
        ['label' => 'Employees',          'route' => 'admin.hrm.employees.index',      'icon' => 'users',        'category' => 'hrm', 'module' => 'hrm', 'permission' => 'employees.view',   'quick' => false, 'keywords' => 'staff team'],
        ['label' => 'Attendance Today',   'route' => 'admin.hrm.attendance.today',     'icon' => 'clock',        'category' => 'hrm', 'module' => 'hrm', 'permission' => 'attendance.view',  'quick' => false, 'keywords' => 'check in out'],
        ['label' => 'Attendance Report',  'route' => 'admin.hrm.attendance.report',    'icon' => 'bar-chart-2',  'category' => 'hrm', 'module' => 'hrm', 'permission' => 'attendance.view',  'quick' => true,  'keywords' => 'muster'],
        ['label' => 'Tasks',              'route' => 'admin.hrm.tasks.index',          'icon' => 'check-square', 'category' => 'hrm', 'module' => 'hrm', 'permission' => 'tasks.view',       'quick' => true,  'keywords' => 'todo assignment'],
        ['label' => 'Leaves',             'route' => 'admin.hrm.leaves.index',         'icon' => 'calendar-off', 'category' => 'hrm', 'module' => 'hrm', 'permission' => 'leaves.view',      'quick' => false, 'keywords' => 'time off holiday'],
        ['label' => 'Salary Slips',       'route' => 'admin.hrm.salary-slips.index',   'icon' => 'indian-rupee',      'category' => 'hrm', 'module' => 'hrm', 'permission' => 'salary_slips.view','quick' => false, 'keywords' => 'payroll payslip'],
        ['label' => 'Work Logs',          'route' => 'admin.hrm.work-logs.index',      'icon' => 'timer',        'category' => 'hrm', 'module' => 'hrm', 'permission' => 'work_logs.view',   'quick' => false, 'keywords' => 'timesheet'],
        ['label' => 'Announcements',      'route' => 'admin.hrm.announcements.index',  'icon' => 'megaphone',    'category' => 'hrm', 'module' => 'hrm', 'permission' => 'announcements.view', 'quick' => false, 'keywords' => 'notice'],
        ['label' => 'Departments',        'route' => 'admin.hrm.departments.index',    'icon' => 'network',      'category' => 'hrm', 'module' => 'hrm', 'permission' => 'departments.view', 'quick' => false, 'keywords' => null],
        ['label' => 'Designations',       'route' => 'admin.hrm.designations.index',   'icon' => 'award',        'category' => 'hrm', 'module' => 'hrm', 'permission' => 'designations.view','quick' => false, 'keywords' => 'title role'],
        ['label' => 'Shifts',             'route' => 'admin.hrm.shifts.index',         'icon' => 'clock-4',      'category' => 'hrm', 'module' => 'hrm', 'permission' => 'shifts.view',      'quick' => false, 'keywords' => null],
        ['label' => 'Holidays',           'route' => 'admin.hrm.holidays.index',       'icon' => 'palmtree',     'category' => 'hrm', 'module' => 'hrm', 'permission' => 'holidays.view',    'quick' => false, 'keywords' => null],

        // ── Web / Storefront ──
        ['label' => 'Storefront Builder', 'route' => 'admin.storefront-builder.index',  'icon' => 'layout-dashboard','category' => 'web', 'module' => 'storefront', 'permission' => 'storefront_builder.view', 'quick' => false, 'keywords' => 'homepage design'],
        ['label' => 'Merchandising',      'route' => 'admin.merchandising.index',       'icon' => 'sparkles',        'category' => 'web', 'module' => 'storefront', 'permission' => 'storefront_builder.view', 'quick' => false, 'keywords' => 'featured'],
        ['label' => 'Sections',           'route' => 'admin.storefront-sections.index', 'icon' => 'rows-3',          'category' => 'web', 'module' => 'storefront', 'permission' => 'storefront_builder.view', 'quick' => false, 'keywords' => null],
        ['label' => 'Pages',              'route' => 'admin.pages.index',               'icon' => 'file',            'category' => 'web', 'module' => 'storefront', 'permission' => 'pages.view',              'quick' => false, 'keywords' => 'cms content'],
        ['label' => 'Banners',            'route' => 'admin.banners.index',             'icon' => 'image',           'category' => 'web', 'module' => 'storefront', 'permission' => 'banners.view',            'quick' => false, 'keywords' => 'slider hero'],
        ['label' => 'Appointments',       'route' => 'admin.appointments.index',        'icon' => 'calendar-check',  'category' => 'web', 'module' => 'appointments','permission' => 'appointments.view',      'quick' => false, 'keywords' => 'booking slot'],

        // ── System ──
        ['label' => 'Settings',           'route' => 'admin.settings.index',        'icon' => 'settings',    'category' => 'system', 'module' => null, 'permission' => 'settings.view',         'quick' => true,  'keywords' => 'configuration company'],
        ['label' => 'Stores',             'route' => 'admin.stores.index',          'icon' => 'store',       'category' => 'system', 'module' => null, 'permission' => 'stores.view',           'quick' => false, 'keywords' => 'branch outlet'],
        ['label' => 'Users',              'route' => 'admin.users.index',           'icon' => 'user-cog',    'category' => 'system', 'module' => null, 'permission' => 'users.view',            'quick' => false, 'keywords' => 'login account access'],
        ['label' => 'Roles',              'route' => 'admin.roles.index',           'icon' => 'shield',      'category' => 'system', 'module' => null, 'permission' => 'roles.view',            'quick' => false, 'keywords' => 'permission'],
        ['label' => 'Payment Methods',    'route' => 'admin.payment-methods.index', 'icon' => 'credit-card', 'category' => 'system', 'module' => null, 'permission' => 'payment_methods.view',  'quick' => false, 'keywords' => null],
        ['label' => 'Notifications',      'route' => 'admin.notifications.index',   'icon' => 'bell',        'category' => 'system', 'module' => null, 'permission' => null,                    'quick' => false, 'keywords' => 'alerts'],
        ['label' => 'Help Center',        'route' => 'admin.help.index',            'icon' => 'life-buoy',   'category' => 'system', 'module' => null, 'permission' => null,                    'quick' => false, 'keywords' => 'support docs guide'],

        // ── Tools ──
        ['label' => 'OCR Scanner',        'route' => 'admin.ocr-scanner.index', 'icon' => 'scan-text', 'category' => 'tools', 'module' => 'ocr_scanner',    'permission' => 'ocr_scanner.access', 'quick' => false, 'keywords' => 'scan extract document'],
        ['label' => 'Label Printing',     'route' => 'admin.labels.index',      'icon' => 'tag',       'category' => 'tools', 'module' => 'label_printing', 'permission' => 'labels.view',        'quick' => false, 'keywords' => 'barcode sticker print'],
        ['label' => 'Bulk Import',        'route' => 'admin.bulk-import.index', 'icon' => 'upload',    'category' => 'tools', 'module' => 'bulk_import',    'permission' => 'bulk_import.view',   'quick' => false, 'keywords' => 'csv excel migrate'],
    ],
];