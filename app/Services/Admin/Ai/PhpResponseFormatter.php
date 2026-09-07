<?php

namespace App\Services\Admin\Ai;

/**
 * Layer 2 — PHP Response Formatter
 *
 * Converts raw ERP data arrays (from executeQuery) into human-readable
 * English reply strings — without any Gemini API call.
 *
 * Each format*() method mirrors one module+action combination and
 * matches the exact data schema returned by AiChatbotService::executeQuery().
 *
 * Returns:
 *   string  → formatted reply ready to send to the user
 *   null    → this data shape is too complex for PHP formatting
 *             (caller may optionally fall back to Gemini formatReply)
 *
 * Design principles:
 *   - Match the data keys exactly as executeQuery() produces them
 *   - Always handle count = 0 gracefully
 *   - Use ₹ and number_format() for all currency amounts
 *   - Keep replies concise (2–4 lines max for counts/totals)
 *   - Numbered lists for row results
 *   - Never hallucinate or calculate — trust the data exactly
 *
 * English only — multi-language support intentionally deferred.
 */
class PhpResponseFormatter
{
    /**
     * Format a data array into a human-readable reply.
     *
     * @param  array  $data   Raw output from executeQuery()
     * @param  array  $plan   The plan that produced the data (module, action, period…)
     * @param  array  $ctx    AI context (language, store info, etc.)
     * @return string|null    Formatted reply, or null if PHP cannot handle this shape
     */
    public static function format(array $data, array $plan, array $ctx): ?string
    {
        $module = $data['module'] ?? $plan['module'] ?? 'unknown';
        $action = $data['action'] ?? $plan['action'] ?? 'count';

        return match ($module) {
            'invoices'       => self::formatInvoices($data, $plan),
            'orders'         => self::formatOrders($data, $plan),
            'expenses'       => self::formatExpenses($data, $plan),
            'purchases'      => self::formatPurchases($data, $plan),
            'payments'       => self::formatPayments($data, $plan),
            'quotations'     => self::formatQuotations($data, $plan),
            'challans'       => self::formatChallans($data, $plan),
            'hrm_attendance' => self::formatAttendance($data, $plan),
            'clients'        => self::formatClients($data, $plan),
            'products'       => self::formatProducts($data, $plan),
            'suppliers'      => self::formatSuppliers($data, $plan),
            'crm_leads'      => self::formatCrmLeads($data, $plan),
            default          => null,
        };
    }

    // ─────────────────────────────────────────────────────────────────────────
    // INVOICES / SALES
    // ─────────────────────────────────────────────────────────────────────────

    private static function formatInvoices(array $data, array $plan): ?string
    {
        $action = $data['action'] ?? 'count';
        $period = $data['period'] ?? null;
        $count  = (int)   ($data['count']       ?? 0);
        $total  = (float) ($data['grand_total']  ?? 0);
        $label  = self::periodLabel($period);

        if ($action === 'total') {
            if ($count === 0) {
                return "No invoices found{$label}.";
            }
            return "**{$label}Sales Summary**\n"
                 . "Total: " . self::currency($total) . "\n"
                 . "Invoices: {$count}";
        }

        if ($action === 'count') {
            if ($count === 0) {
                return "No invoices found{$label}.";
            }
            $plural = $count === 1 ? 'invoice' : 'invoices';
            return "{$count} {$plural}{$label}.";
        }

        if ($action === 'list') {
            $rows  = $data['rows'] ?? [];
            $status = $plan['payment_status_filter'] ?? $plan['status_filter'] ?? null;
            $statusLabel = $status ? ucfirst($status) . ' ' : '';

            if (empty($rows)) {
                return "No {$statusLabel}invoices found.";
            }

            $lines = ["{$statusLabel}Invoices ({$count}):"];
            foreach (array_slice($rows, 0, 10) as $i => $row) {
                $num    = $row['invoice_number'] ?? '—';
                $name   = $row['customer_name']  ?? '—';
                $amount = self::currency((float)($row['grand_total'] ?? 0));
                $pst    = ucfirst($row['payment_status'] ?? '—');
                $lines[] = ($i + 1) . ". {$num} — {$name} — {$amount} ({$pst})";
            }
            return implode("\n", $lines);
        }

        return null;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ORDERS
    // ─────────────────────────────────────────────────────────────────────────

    private static function formatOrders(array $data, array $plan): ?string
    {
        $action = $data['action'] ?? 'count';
        $period = $data['period'] ?? null;
        $count  = (int)   ($data['count']       ?? 0);
        $total  = (float) ($data['grand_total']  ?? 0);
        $label  = self::periodLabel($period);

        if ($action === 'total') {
            if ($count === 0) {
                return "No orders found{$label}.";
            }
            return "**Orders{$label}**\n"
                 . "Total Value: " . self::currency($total) . "\n"
                 . "Count: {$count} orders";
        }

        if ($action === 'count') {
            if ($count === 0) {
                return "No orders received{$label}.";
            }
            $plural = $count === 1 ? 'order' : 'orders';
            return "{$count} {$plural} received{$label}.";
        }

        if ($action === 'list') {
            $rows = $data['rows'] ?? [];
            if (empty($rows)) {
                return "No orders found{$label}.";
            }
            $lines = ["Orders{$label} ({$count}):"];
            foreach (array_slice($rows, 0, 10) as $i => $row) {
                $num    = $row['order_number']   ?? '—';
                $name   = $row['customer_name']  ?? '—';
                $amount = self::currency((float)($row['total_amount'] ?? 0));
                $status = ucfirst($row['status'] ?? '—');
                $lines[] = ($i + 1) . ". {$num} — {$name} — {$amount} ({$status})";
            }
            return implode("\n", $lines);
        }

        return null;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // EXPENSES
    // ─────────────────────────────────────────────────────────────────────────

    private static function formatExpenses(array $data, array $plan): ?string
    {
        $period = $data['period'] ?? null;
        $count  = (int)   ($data['count']      ?? 0);
        $total  = (float) ($data['grand_total'] ?? 0);
        $label  = self::periodLabel($period);

        if ($count === 0) {
            return "No expenses recorded{$label}.";
        }

        $plural = $count === 1 ? 'expense entry' : 'expense entries';
        return "**Expenses{$label}**\n"
             . "Total: " . self::currency($total) . "\n"
             . "{$count} {$plural}";
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PURCHASES
    // ─────────────────────────────────────────────────────────────────────────

    private static function formatPurchases(array $data, array $plan): ?string
    {
        $action = $data['action'] ?? 'total';
        $period = $data['period'] ?? null;
        $count  = (int)   ($data['count']      ?? 0);
        $total  = (float) ($data['grand_total'] ?? 0);
        $label  = self::periodLabel($period);

        if ($action === 'list') {
            $rows = $data['rows'] ?? [];
            if (empty($rows)) {
                return "No purchases found{$label}.";
            }
            $lines = ["Purchases{$label} ({$count}):"];
            foreach (array_slice($rows, 0, 10) as $i => $row) {
                $num    = $row['purchase_number']                          ?? '—';
                $supp   = $row['supplier']['name'] ?? $row['supplier_id'] ?? '—';
                $amount = self::currency((float)($row['total_amount']      ?? 0));
                $status = ucfirst($row['status']                           ?? '—');
                $lines[] = ($i + 1) . ". {$num} — {$supp} — {$amount} ({$status})";
            }
            return implode("\n", $lines);
        }

        // total / count
        if ($count === 0) {
            return "No purchases found{$label}.";
        }

        $plural = $count === 1 ? 'purchase' : 'purchases';
        return "**Purchases{$label}**\n"
             . "Total: " . self::currency($total) . "\n"
             . "{$count} {$plural}";
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PAYMENTS
    // ─────────────────────────────────────────────────────────────────────────

    private static function formatPayments(array $data, array $plan): ?string
    {
        $period = $data['period'] ?? null;
        $count  = (int)   ($data['count']      ?? 0);
        $total  = (float) ($data['grand_total'] ?? 0);
        $label  = self::periodLabel($period);

        if ($count === 0) {
            return "No payments received{$label}.";
        }

        $plural = $count === 1 ? 'payment' : 'payments';
        return "**Payments Received{$label}**\n"
             . "Total: " . self::currency($total) . "\n"
             . "{$count} {$plural}";
    }

    // ─────────────────────────────────────────────────────────────────────────
    // QUOTATIONS
    // ─────────────────────────────────────────────────────────────────────────

    private static function formatQuotations(array $data, array $plan): ?string
    {
        $action = $data['action'] ?? 'count';
        $period = $data['period'] ?? null;
        $count  = (int)($data['count'] ?? 0);
        $label  = self::periodLabel($period);

        $status      = $plan['status_filter'] ?? null;
        $statusLabel = $status ? ' ' . ucfirst($status) : '';

        if ($action === 'count') {
            if ($count === 0) {
                return "No{$statusLabel} quotations found{$label}.";
            }
            $plural = $count === 1 ? 'quotation' : 'quotations';
            return "{$count}{$statusLabel} {$plural}{$label}.";
        }

        if ($action === 'list') {
            $rows = $data['rows'] ?? [];
            if (empty($rows)) {
                return "No{$statusLabel} quotations found{$label}.";
            }
            $lines = ["{$statusLabel} Quotations{$label} ({$count}):"];
            foreach (array_slice($rows, 0, 10) as $i => $row) {
                $num    = $row['quotation_number'] ?? '—';
                $name   = $row['customer_name']    ?? '—';
                $amount = self::currency((float)($row['grand_total'] ?? 0));
                $status = ucfirst($row['status'] ?? '—');
                $lines[] = ($i + 1) . ". {$num} — {$name} — {$amount} ({$status})";
            }
            return implode("\n", $lines);
        }

        return null;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CHALLANS
    // ─────────────────────────────────────────────────────────────────────────

    private static function formatChallans(array $data, array $plan): ?string
    {
        $action = $data['action'] ?? 'count';
        $period = $data['period'] ?? null;
        $count  = (int)($data['count'] ?? 0);
        $label  = self::periodLabel($period);

        $status      = $plan['status_filter'] ?? null;
        $statusLabel = $status ? ' ' . ucfirst($status) : '';

        if ($action === 'count') {
            if ($count === 0) {
                return "No{$statusLabel} challans found{$label}.";
            }
            $plural = $count === 1 ? 'challan' : 'challans';
            return "{$count}{$statusLabel} {$plural}{$label}.";
        }

        if ($action === 'list') {
            $rows = $data['rows'] ?? [];
            if (empty($rows)) {
                return "No{$statusLabel} challans found{$label}.";
            }
            $lines = ["Challans{$label} ({$count}):"];
            foreach (array_slice($rows, 0, 10) as $i => $row) {
                $num    = $row['challan_number'] ?? '—';
                $party  = $row['party_name']     ?? '—';
                $status = ucfirst($row['status'] ?? '—');
                $lines[] = ($i + 1) . ". {$num} — {$party} ({$status})";
            }
            return implode("\n", $lines);
        }

        return null;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HRM ATTENDANCE
    // ─────────────────────────────────────────────────────────────────────────

    private static function formatAttendance(array $data, array $plan): ?string
    {
        $action = $data['action'] ?? 'count';
        $count  = (int)($data['count'] ?? 0);
        $status = $data['status'] ?? $plan['status_filter'] ?? null;

        $statusLabel = match ($status) {
            'present'  => 'present',
            'absent'   => 'absent',
            'late'     => 'marked late',
            'half_day' => 'on half day',
            default    => 'marked',
        };

        if ($action === 'count') {
            if ($count === 0) {
                return "No employees {$statusLabel} today.";
            }
            $plural = $count === 1 ? 'employee is' : 'employees are';
            return "Today, {$count} {$plural} {$statusLabel}.";
        }

        if ($action === 'list') {
            $rows = $data['rows'] ?? [];
            if (empty($rows)) {
                return "No employees {$statusLabel} today.";
            }
            $lines = ["Employees {$statusLabel} today ({$count}):"];
            foreach (array_slice($rows, 0, 10) as $i => $row) {
                $name = $row['name']         ?? 'N/A';
                $code = $row['employee_code'] ?? '';
                $in   = $row['check_in']     ?? '—';
                $out  = $row['check_out']    ?? '—';
                $codeStr = $code ? " ({$code})" : '';
                $lines[] = ($i + 1) . ". {$name}{$codeStr} — In: {$in}  Out: {$out}";
            }
            return implode("\n", $lines);
        }

        return null;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CLIENTS / CUSTOMERS
    // ─────────────────────────────────────────────────────────────────────────

    private static function formatClients(array $data, array $plan): ?string
    {
        $action = $data['action'] ?? 'count';
        $count  = (int)($data['count'] ?? 0);

        if ($action === 'count') {
            if ($count === 0) {
                return "No customers found in your account.";
            }
            $plural = $count === 1 ? 'customer' : 'customers';
            return "You have {$count} {$plural} in your account.";
        }

        if ($action === 'list') {
            $rows = $data['rows'] ?? [];
            if (empty($rows)) {
                return "No customers found.";
            }
            $lines = ["Customers ({$count}):"];
            foreach (array_slice($rows, 0, 10) as $i => $row) {
                $name    = $row['name']         ?? '—';
                $company = $row['company_name'] ?? '';
                $phone   = $row['phone']        ?? '';
                $compStr = $company ? " — {$company}" : '';
                $phStr   = $phone   ? " ({$phone})"   : '';
                $lines[] = ($i + 1) . ". {$name}{$compStr}{$phStr}";
            }
            return implode("\n", $lines);
        }

        return null;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PRODUCTS
    // ─────────────────────────────────────────────────────────────────────────

    private static function formatProducts(array $data, array $plan): ?string
    {
        $action = $data['action'] ?? 'count';
        $count  = (int)($data['count'] ?? 0);

        if ($action === 'count') {
            if ($count === 0) {
                return "No active products found in your catalog.";
            }
            $plural = $count === 1 ? 'product' : 'products';
            return "You have {$count} active {$plural} in your catalog.";
        }

        if ($action === 'list') {
            $rows = $data['rows'] ?? [];
            if (empty($rows)) {
                return "No products found.";
            }
            $lines = ["Products ({$count}):"];
            foreach (array_slice($rows, 0, 10) as $i => $row) {
                $name  = $row['name']        ?? '—';
                $stock = $row['total_stock'] ?? 0;
                $price = isset($row['price']) ? ' — ' . self::currency((float)$row['price']) : '';
                $lines[] = ($i + 1) . ". {$name}{$price} — Stock: {$stock}";
            }
            return implode("\n", $lines);
        }

        return null;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SUPPLIERS
    // ─────────────────────────────────────────────────────────────────────────

    private static function formatSuppliers(array $data, array $plan): ?string
    {
        $count = (int)($data['count'] ?? 0);

        if ($count === 0) {
            return "No suppliers registered in your account.";
        }
        $plural = $count === 1 ? 'supplier' : 'suppliers';
        return "You have {$count} {$plural} registered.";
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CRM LEADS
    // ─────────────────────────────────────────────────────────────────────────

    private static function formatCrmLeads(array $data, array $plan): ?string
    {
        $action = $data['action'] ?? 'count';
        $period = $data['period'] ?? null;
        $count  = (int)($data['count'] ?? 0);
        $extra  = $data['extra'] ?? [];
        $label  = self::periodLabel($period);

        // Determine context label from extras
        $contextLabel = '';
        if (! empty($extra['score_hot'])) {
            $contextLabel = ' hot';
        } elseif (! empty($extra['followup_pending'])) {
            $contextLabel = ' follow-up pending';
        }

        if ($action === 'count') {
            if ($count === 0) {
                return "No{$contextLabel} leads found{$label}.";
            }
            $plural = $count === 1 ? 'lead' : 'leads';
            return "{$count}{$contextLabel} {$plural}{$label}.";
        }

        if ($action === 'list') {
            $rows = $data['rows'] ?? [];
            if (empty($rows)) {
                return "No{$contextLabel} leads found{$label}.";
            }
            $lines = [ucfirst(ltrim("{$contextLabel} Leads{$label}")) . " ({$count}):"];
            foreach (array_slice($rows, 0, 10) as $i => $row) {
                $name    = $row['name']    ?? '—';
                $company = $row['company_name'] ?? '';
                $phone   = $row['phone']   ?? '';
                $score   = isset($row['score']) ? " (score: {$row['score']})" : '';
                $compStr = $company ? " — {$company}" : '';
                $phStr   = $phone   ? " — {$phone}"   : '';
                $lines[] = ($i + 1) . ". {$name}{$compStr}{$phStr}{$score}";
            }
            return implode("\n", $lines);
        }

        return null;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SHARED HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Format a number as Indian-style currency with ₹ symbol.
     * e.g. 125000.50 → "₹1,25,000.50"
     */
    private static function currency(float $amount): string
    {
        // Use PHP's number_format then convert to Indian comma style
        $formatted = number_format(abs($amount), 2);

        // Convert western commas (1,234,567.00) to Indian style (12,34,567.00)
        // Split at decimal
        [$intPart, $decPart] = explode('.', $formatted);
        $intPart = str_replace(',', '', $intPart); // remove western commas

        // Indian number formatting: last 3 digits, then groups of 2
        $len = strlen($intPart);
        if ($len <= 3) {
            $indian = $intPart;
        } else {
            $last3  = substr($intPart, -3);
            $rest   = substr($intPart, 0, $len - 3);
            $rest   = strrev(implode(',', str_split(strrev($rest), 2)));
            $indian = $rest . ',' . $last3;
        }

        $sign = $amount < 0 ? '-' : '';
        return "{$sign}₹{$indian}.{$decPart}";
    }

    /**
     * Return a human-friendly period phrase for use in sentences.
     * Includes a leading space so callers can concatenate directly.
     * e.g. 'today' → " today"   'this_month' → " this month"
     */
    private static function periodLabel(?string $period): string
    {
        return match ($period) {
            'today'      => ' today',
            'this_week'  => ' this week',
            'this_month' => ' this month',
            'this_year'  => ' this year',
            'all', null  => '',
            default      => '',
        };
    }
}