<?php

namespace App\Services\Admin\Ai;

/**
 * Layer 2 — PHP ERP Command Router
 *
 * Matches common, deterministic ERP queries using keyword-group patterns
 * and returns a plan array directly — without any Gemini API call.
 *
 * Matching algorithm per pattern:
 *   - ALL keyword groups must have at least ONE keyword present (AND between groups)
 *   - Within each group, any single match is enough           (OR within group)
 *   - If COMPLEX signals found (compare, why, analyze…)  → null (Gemini needed)
 *   - If a store name from context is found               → null (Gemini resolves store_id)
 *
 * Returns:
 *   array   → valid plan (same schema as routeIntent output) → caller executes it
 *   null    → cannot match → caller falls through to Gemini
 *
 * The returned plan is intentionally identical in structure to what
 * routeIntent() returns, so the existing authorizeQuery() / executeQuery()
 * / formatReply() pipeline works unchanged.
 *
 * Zero Gemini calls. Read-only.
 */
class PhpErpCommandRouter
{
    // ─────────────────────────────────────────────────────────────────────────
    // COMPLEX SIGNAL BLOCKLIST
    // If ANY of these are found → analysis/reasoning needed → return null.
    // ─────────────────────────────────────────────────────────────────────────

    private const COMPLEX_SIGNALS = [
        'compare', 'comparison', 'versus', ' vs ', 'v/s', 'difference',
        'why', 'reason', 'because', 'explain', 'how come',
        'analyze', 'analyse', 'analysis', 'analytics',
        'trend', 'trending', 'growth', 'decline', 'forecast', 'predict', 'projection',
        'suggest', 'suggestion', 'recommend', 'recommendation',
        'best', 'worst', 'top', 'bottom', 'ranking', 'rank',
        'insight', 'insights', 'overview', 'breakdown', 'breakup',
        'between', 'vs store', 'across store',
        'summarize', 'summarise', 'summary',
    ];

    // ─────────────────────────────────────────────────────────────────────────
    // KEYWORD GROUPS  (reused across multiple patterns)
    // ─────────────────────────────────────────────────────────────────────────

    private const KW_TIME_TODAY = [
        'today', 'aaj', 'today\'s', 'todays', 'current day', 'this day',
        'abhi', 'iss waqt', 'abhi tak',
    ];

    private const KW_TIME_WEEK = [
        'this week', 'week', 'weekly', 'is hafte', 'is week',
        'hafte mein', 'hafta', 'week mein',
    ];

    private const KW_TIME_MONTH = [
        'this month', 'month', 'monthly', 'is mahine', 'is month',
        'mahine mein', 'mahine ka', 'mahina', 'maheena',
    ];

    private const KW_TIME_YEAR = [
        'this year', 'year', 'yearly', 'annual', 'annually',
        'is saal', 'is year', 'saal mein', 'saal ka', 'varsh',
    ];

    private const KW_SALES = [
        'sale', 'sales', 'revenue', 'income', 'earning', 'earnings',
        'selling', 'bikri', 'bechna', 'bech',
    ];

    private const KW_INVOICE = [
        'invoice', 'invoices', 'bill', 'bills', 'billed', 'billing',
    ];

    private const KW_ORDER = [
        'order', 'orders', 'ordering',
    ];

    private const KW_EXPENSE = [
        'expense', 'expenses', 'kharcha', 'kharche', 'kharchi', 'kharch', 'khareed',
        'expenditure', 'spending', 'spent',
    ];

    private const KW_PURCHASE = [
        'purchase', 'purchases', 'purchased', 'kharidi', 'kharidari',
        'buying', 'bought', 'procurement',
    ];

    private const KW_PAYMENT = [
        'payment', 'payments', 'paid', 'received payment', 'payment received',
        'collection', 'collections',
    ];

    private const KW_QUOTATION = [
        'quotation', 'quotations', 'quote', 'quotes', 'estimate', 'estimates',
    ];

    private const KW_CHALLAN = [
        'challan', 'challans', 'delivery challan', 'dispatch',
    ];

    private const KW_ATTENDANCE = [
        'attendance', 'hajiri', 'haajri', 'present count', 'absent count',
        'who came', 'who is in', 'who is present',
    ];

    private const KW_CUSTOMER = [
        'customer', 'customers', 'client', 'clients', 'grahak', 'buyer', 'buyers',
    ];

    private const KW_PRODUCT = [
        'product', 'products', 'item', 'items', 'goods',
    ];

    private const KW_SUPPLIER = [
        'supplier', 'suppliers', 'vendor', 'vendors', 'party', 'parties',
    ];

    private const KW_LEADS = [
        'lead', 'leads', 'crm lead', 'crm leads', 'prospect', 'prospects',
    ];

    // Quantifier words that indicate a count/total intent without specifics
    private const KW_QUANTIFIER = [
        'how many', 'total', 'count', 'number of', 'kitne', 'kul', 'sab',
        'all', 'overall',
    ];

    // Status words for invoices/payments
    private const KW_PENDING = [
        'pending', 'unpaid', 'overdue', 'outstanding', 'due', 'baaki', 'baki',
        'not paid', 'not received',
    ];

    private const KW_PRESENT = ['present', 'came', 'arrived', 'hazir', 'aa gaye', 'aaye'];
    private const KW_ABSENT  = ['absent', 'missing', 'not present', 'nahi aaya', 'na aaya'];
    private const KW_LATE    = ['late', 'late comers', 'latecomer', 'der se aaya'];

    // ─────────────────────────────────────────────────────────────────────────
    // PLAN TEMPLATE — default empty plan to spread into each pattern
    // ─────────────────────────────────────────────────────────────────────────

    private const BASE_PLAN = [
        'module'                 => 'unknown',
        'action'                 => 'count',
        'period'                 => null,
        'status_filter'          => null,
        'payment_status_filter'  => null,
        'search'                 => null,
        'store_filter'           => null,
        'extra'                  => [],
    ];

    // ─────────────────────────────────────────────────────────────────────────
    // PUBLIC API
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Attempt to match the message to a known deterministic ERP plan.
     *
     * @param  string  $message  Raw user message
     * @param  array   $ctx      Context from AiContextBuilder::build()
     * @return array|null        Plan array or null
     */
    public static function matchPlan(string $message, array $ctx): ?array
    {
        $msg = self::normalize($message);

        // If message contains analysis/comparison keywords → needs Gemini reasoning
        if (self::hasComplexSignal($msg)) {
            return null;
        }

        // If message mentions a specific store name → Gemini must resolve store_id
        if (self::mentionsStoreName($msg, $ctx)) {
            return null;
        }

        // Try each pattern in priority order — first match wins
        return self::matchPatterns($msg);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PATTERN MATCHING ENGINE
    // ─────────────────────────────────────────────────────────────────────────

    private static function matchPatterns(string $msg): ?array
    {
        // ════════════════════════════════════════════════════════════════════
        // INVOICES / SALES
        // ════════════════════════════════════════════════════════════════════

        // Today's total sales / invoices
        if (
            self::containsAny($msg, self::KW_TIME_TODAY) &&
            self::containsAny($msg, array_merge(self::KW_SALES, self::KW_INVOICE))
        ) {
            return self::plan('invoices', 'total', 'today');
        }

        // This week's sales / invoices
        if (
            self::containsAny($msg, self::KW_TIME_WEEK) &&
            self::containsAny($msg, array_merge(self::KW_SALES, self::KW_INVOICE))
        ) {
            return self::plan('invoices', 'total', 'this_week');
        }

        // This month's sales / invoices
        if (
            self::containsAny($msg, self::KW_TIME_MONTH) &&
            self::containsAny($msg, array_merge(self::KW_SALES, self::KW_INVOICE))
        ) {
            return self::plan('invoices', 'total', 'this_month');
        }

        // This year's sales / invoices
        if (
            self::containsAny($msg, self::KW_TIME_YEAR) &&
            self::containsAny($msg, array_merge(self::KW_SALES, self::KW_INVOICE))
        ) {
            return self::plan('invoices', 'total', 'this_year');
        }

        // Pending / unpaid invoices
        if (
            self::containsAny($msg, self::KW_PENDING) &&
            self::containsAny($msg, self::KW_INVOICE)
        ) {
            return self::plan('invoices', 'list', 'all', payment_status: 'unpaid');
        }

        // Total invoice count (no time — "how many invoices", "total invoices")
        if (
            self::containsAny($msg, self::KW_QUANTIFIER) &&
            self::containsAny($msg, self::KW_INVOICE) &&
            ! self::containsAny($msg, self::KW_TIME_TODAY) &&
            ! self::containsAny($msg, self::KW_TIME_WEEK) &&
            ! self::containsAny($msg, self::KW_TIME_MONTH) &&
            ! self::containsAny($msg, self::KW_TIME_YEAR)
        ) {
            return self::plan('invoices', 'count', 'all');
        }

        // ════════════════════════════════════════════════════════════════════
        // ORDERS
        // ════════════════════════════════════════════════════════════════════

        // Today orders
        if (
            self::containsAny($msg, self::KW_TIME_TODAY) &&
            self::containsAny($msg, self::KW_ORDER)
        ) {
            return self::plan('orders', 'count', 'today');
        }

        // This week orders
        if (
            self::containsAny($msg, self::KW_TIME_WEEK) &&
            self::containsAny($msg, self::KW_ORDER)
        ) {
            return self::plan('orders', 'count', 'this_week');
        }

        // This month orders
        if (
            self::containsAny($msg, self::KW_TIME_MONTH) &&
            self::containsAny($msg, self::KW_ORDER)
        ) {
            return self::plan('orders', 'count', 'this_month');
        }

        // Total / all orders
        if (
            self::containsAny($msg, self::KW_QUANTIFIER) &&
            self::containsAny($msg, self::KW_ORDER) &&
            ! self::containsAny($msg, self::KW_TIME_TODAY) &&
            ! self::containsAny($msg, self::KW_TIME_WEEK) &&
            ! self::containsAny($msg, self::KW_TIME_MONTH)
        ) {
            return self::plan('orders', 'count', 'all');
        }

        // ════════════════════════════════════════════════════════════════════
        // EXPENSES
        // ════════════════════════════════════════════════════════════════════

        if (
            self::containsAny($msg, self::KW_TIME_TODAY) &&
            self::containsAny($msg, self::KW_EXPENSE)
        ) {
            return self::plan('expenses', 'total', 'today');
        }

        if (
            self::containsAny($msg, self::KW_TIME_WEEK) &&
            self::containsAny($msg, self::KW_EXPENSE)
        ) {
            return self::plan('expenses', 'total', 'this_week');
        }

        if (
            self::containsAny($msg, self::KW_TIME_MONTH) &&
            self::containsAny($msg, self::KW_EXPENSE)
        ) {
            return self::plan('expenses', 'total', 'this_month');
        }

        if (
            self::containsAny($msg, self::KW_TIME_YEAR) &&
            self::containsAny($msg, self::KW_EXPENSE)
        ) {
            return self::plan('expenses', 'total', 'this_year');
        }

        // Total expenses (no time)
        if (
            self::containsAny($msg, self::KW_QUANTIFIER) &&
            self::containsAny($msg, self::KW_EXPENSE) &&
            ! self::containsAny($msg, self::KW_TIME_TODAY) &&
            ! self::containsAny($msg, self::KW_TIME_MONTH)
        ) {
            return self::plan('expenses', 'total', 'all');
        }

        // ════════════════════════════════════════════════════════════════════
        // PURCHASES
        // ════════════════════════════════════════════════════════════════════

        if (
            self::containsAny($msg, self::KW_TIME_TODAY) &&
            self::containsAny($msg, self::KW_PURCHASE)
        ) {
            return self::plan('purchases', 'total', 'today');
        }

        if (
            self::containsAny($msg, self::KW_TIME_MONTH) &&
            self::containsAny($msg, self::KW_PURCHASE)
        ) {
            return self::plan('purchases', 'total', 'this_month');
        }

        if (
            self::containsAny($msg, self::KW_TIME_YEAR) &&
            self::containsAny($msg, self::KW_PURCHASE)
        ) {
            return self::plan('purchases', 'total', 'this_year');
        }

        // Total purchases (no time)
        if (
            self::containsAny($msg, self::KW_QUANTIFIER) &&
            self::containsAny($msg, self::KW_PURCHASE) &&
            ! self::containsAny($msg, self::KW_TIME_TODAY) &&
            ! self::containsAny($msg, self::KW_TIME_MONTH)
        ) {
            return self::plan('purchases', 'total', 'all');
        }

        // ════════════════════════════════════════════════════════════════════
        // PAYMENTS RECEIVED
        // ════════════════════════════════════════════════════════════════════

        if (
            self::containsAny($msg, self::KW_TIME_TODAY) &&
            self::containsAny($msg, self::KW_PAYMENT)
        ) {
            return self::plan('payments', 'total', 'today');
        }

        if (
            self::containsAny($msg, self::KW_TIME_WEEK) &&
            self::containsAny($msg, self::KW_PAYMENT)
        ) {
            return self::plan('payments', 'total', 'this_week');
        }

        if (
            self::containsAny($msg, self::KW_TIME_MONTH) &&
            self::containsAny($msg, self::KW_PAYMENT)
        ) {
            return self::plan('payments', 'total', 'this_month');
        }

        // ════════════════════════════════════════════════════════════════════
        // QUOTATIONS
        // ════════════════════════════════════════════════════════════════════

        // Pending quotations
        if (
            self::containsAny($msg, self::KW_PENDING) &&
            self::containsAny($msg, self::KW_QUOTATION)
        ) {
            return self::plan('quotations', 'count', 'all', status: 'draft');
        }

        // Today quotations
        if (
            self::containsAny($msg, self::KW_TIME_TODAY) &&
            self::containsAny($msg, self::KW_QUOTATION)
        ) {
            return self::plan('quotations', 'count', 'today');
        }

        // This month quotations
        if (
            self::containsAny($msg, self::KW_TIME_MONTH) &&
            self::containsAny($msg, self::KW_QUOTATION)
        ) {
            return self::plan('quotations', 'count', 'this_month');
        }

        // Total quotations
        if (
            self::containsAny($msg, self::KW_QUANTIFIER) &&
            self::containsAny($msg, self::KW_QUOTATION) &&
            ! self::containsAny($msg, self::KW_TIME_TODAY) &&
            ! self::containsAny($msg, self::KW_TIME_MONTH)
        ) {
            return self::plan('quotations', 'count', 'all');
        }

        // ════════════════════════════════════════════════════════════════════
        // CHALLANS
        // ════════════════════════════════════════════════════════════════════

        if (
            self::containsAny($msg, self::KW_TIME_TODAY) &&
            self::containsAny($msg, self::KW_CHALLAN)
        ) {
            return self::plan('challans', 'count', 'today');
        }

        if (
            self::containsAny($msg, self::KW_TIME_MONTH) &&
            self::containsAny($msg, self::KW_CHALLAN)
        ) {
            return self::plan('challans', 'count', 'this_month');
        }

        // Pending challans
        if (
            self::containsAny($msg, self::KW_PENDING) &&
            self::containsAny($msg, self::KW_CHALLAN)
        ) {
            return self::plan('challans', 'count', 'all', status: 'draft');
        }

        // ════════════════════════════════════════════════════════════════════
        // HRM ATTENDANCE
        // ════════════════════════════════════════════════════════════════════

        // Present employees today
        if (
            self::containsAny($msg, self::KW_TIME_TODAY) &&
            self::containsAny($msg, self::KW_PRESENT) &&
            ! self::containsAny($msg, self::KW_ATTENDANCE) // avoid "attendance present today" ambiguity
        ) {
            return self::plan('hrm_attendance', 'count', 'today', status: 'present');
        }
        if (
            self::containsAny($msg, self::KW_ATTENDANCE) &&
            self::containsAny($msg, self::KW_PRESENT)
        ) {
            return self::plan('hrm_attendance', 'count', 'today', status: 'present');
        }

        // Absent employees today
        if (
            self::containsAny($msg, self::KW_ABSENT)
        ) {
            return self::plan('hrm_attendance', 'count', 'today', status: 'absent');
        }

        // Late employees today
        if (
            self::containsAny($msg, self::KW_LATE) &&
            (self::containsAny($msg, self::KW_TIME_TODAY) || self::containsAny($msg, self::KW_ATTENDANCE))
        ) {
            return self::plan('hrm_attendance', 'count', 'today', status: 'late');
        }

        // General attendance count / who came / today attendance
        if (
            self::containsAny($msg, self::KW_TIME_TODAY) &&
            self::containsAny($msg, self::KW_ATTENDANCE)
        ) {
            return self::plan('hrm_attendance', 'count', 'today');
        }

        // ════════════════════════════════════════════════════════════════════
        // CUSTOMERS / CLIENTS
        // ════════════════════════════════════════════════════════════════════

        if (
            self::containsAny($msg, self::KW_CUSTOMER) &&
            (
                self::containsAny($msg, self::KW_QUANTIFIER) ||
                str_contains($msg, 'count') ||
                str_contains($msg, 'list') ||
                str_contains($msg, 'show') ||
                str_contains($msg, 'total')
            )
        ) {
            return self::plan('clients', 'count');
        }

        // "customers" alone (short message) — e.g. "customers?" "clients?"
        if (
            self::containsAny($msg, self::KW_CUSTOMER) &&
            mb_strlen($msg) <= 25
        ) {
            return self::plan('clients', 'count');
        }

        // ════════════════════════════════════════════════════════════════════
        // PRODUCTS
        // ════════════════════════════════════════════════════════════════════

        if (
            self::containsAny($msg, self::KW_PRODUCT) &&
            (
                self::containsAny($msg, self::KW_QUANTIFIER) ||
                str_contains($msg, 'count') ||
                str_contains($msg, 'total') ||
                str_contains($msg, 'list') ||
                str_contains($msg, 'show')
            )
        ) {
            return self::plan('products', 'count');
        }

        // "products?" alone
        if (
            self::containsAny($msg, self::KW_PRODUCT) &&
            mb_strlen($msg) <= 25 &&
            ! str_contains($msg, 'stock') &&
            ! str_contains($msg, 'low') &&
            ! str_contains($msg, 'search')
        ) {
            return self::plan('products', 'count');
        }

        // ════════════════════════════════════════════════════════════════════
        // SUPPLIERS / VENDORS
        // ════════════════════════════════════════════════════════════════════

        if (
            self::containsAny($msg, self::KW_SUPPLIER) &&
            (self::containsAny($msg, self::KW_QUANTIFIER) || mb_strlen($msg) <= 30)
        ) {
            return self::plan('suppliers', 'count');
        }

        // ════════════════════════════════════════════════════════════════════
        // CRM LEADS
        // ════════════════════════════════════════════════════════════════════

        // Today's leads
        if (
            self::containsAny($msg, self::KW_TIME_TODAY) &&
            self::containsAny($msg, self::KW_LEADS)
        ) {
            return self::plan('crm_leads', 'count', 'today');
        }

        // This month leads
        if (
            self::containsAny($msg, self::KW_TIME_MONTH) &&
            self::containsAny($msg, self::KW_LEADS)
        ) {
            return self::plan('crm_leads', 'count', 'this_month');
        }

        // Total leads / lead count
        if (
            self::containsAny($msg, self::KW_LEADS) &&
            (
                self::containsAny($msg, self::KW_QUANTIFIER) ||
                str_contains($msg, 'count') ||
                mb_strlen($msg) <= 25
            )
        ) {
            return self::plan('crm_leads', 'count', 'all');
        }

        return null; // No pattern matched → let Gemini handle it
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Normalize: lowercase, strip punctuation, collapse whitespace.
     */
    private static function normalize(string $message): string
    {
        $m = mb_strtolower(trim($message));
        $m = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $m);
        $m = preg_replace('/\s+/', ' ', $m);
        return trim($m);
    }

    /**
     * Returns true if any of the keyword terms appear in the message.
     */
    private static function containsAny(string $msg, array $terms): bool
    {
        foreach ($terms as $term) {
            if (str_contains($msg, $term)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns true if any complex/analytical signal is found.
     */
    private static function hasComplexSignal(string $msg): bool
    {
        foreach (self::COMPLEX_SIGNALS as $signal) {
            if (str_contains($msg, $signal)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns true if the message contains a store/branch name from context.
     * When a specific store is mentioned, Gemini must resolve the store_id.
     */
    private static function mentionsStoreName(string $msg, array $ctx): bool
    {
        $stores = $ctx['store']['all_stores'] ?? [];

        foreach ($stores as $store) {
            $name = mb_strtolower(trim($store['name'] ?? ''));
            $city = mb_strtolower(trim($store['city'] ?? ''));

            if ($name !== '' && str_contains($msg, $name)) {
                return true;
            }
            // Only check city if it's meaningful (> 3 chars to avoid short hits like "hub")
            if ($city !== '' && mb_strlen($city) > 3 && str_contains($msg, $city)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Build a complete plan array (identical schema to routeIntent output).
     * Named arguments used for clarity at call sites.
     */
    private static function plan(
        string  $module,
        string  $action      = 'count',
        ?string $period      = null,
        ?string $status      = null,
        ?string $payment_status = null,
        ?string $search      = null,
        array   $extra       = [],
    ): array {
        return array_merge(self::BASE_PLAN, [
            'module'                => $module,
            'action'                => $action,
            'period'                => $period,
            'status_filter'         => $status,
            'payment_status_filter' => $payment_status,
            'search'                => $search,
            'extra'                 => $extra,
        ]);
    }
}