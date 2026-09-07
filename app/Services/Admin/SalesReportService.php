<?php

namespace App\Services\Admin;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Support\Facades\DB;

class SalesReportService
{
    /**
     * Get Sales Summary (Gross Sales, Returns, Net Sales)
     */
    public function getSalesSummary(array $storeIds, string $startDate, string $endDate)
    {
        // 1. Gross Sales (Confirmed Invoices) — store-filtered
        $grossSales = Invoice::whereIn('store_id', $storeIds)
            ->where('status', 'confirmed')
            ->whereBetween('invoice_date', [$startDate, $endDate])
            ->sum('grand_total');

        // 2. Total Returns (Confirmed Credit Notes) — store-filtered
        $totalReturns = DB::table('invoice_returns')
            ->whereIn('store_id', $storeIds)
            ->where('status', 'confirmed')
            ->whereBetween('return_date', [$startDate, $endDate])
            ->sum('grand_total');

        return [
            'gross_sales' => $grossSales,
            'returns' => $totalReturns,
            'net_sales' => $grossSales - $totalReturns,
        ];
    }

    /**
     * Get Top Selling Products (By Quantity or Revenue)
     */
    public function getProductPerformance(array $storeIds, string $startDate, string $endDate, int $limit = 10, string $orderBy = 'desc')
    {
        // We join invoice_items with invoices to ensure we only count 'confirmed' sales
        return InvoiceItem::join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->join('product_skus', 'invoice_items.product_sku_id', '=', 'product_skus.id')
            ->join('products', 'product_skus.product_id', '=', 'products.id')
            // 🌟 UX UPGRADE: Join Attribute Tables to fetch variant values
            ->leftJoin('product_sku_values', 'product_skus.id', '=', 'product_sku_values.product_sku_id')
            ->leftJoin('attribute_values', 'product_sku_values.attribute_value_id', '=', 'attribute_values.id')
            ->whereIn('invoices.store_id', $storeIds)
            ->where('invoices.status', 'confirmed')
            ->whereBetween('invoices.invoice_date', [$startDate, $endDate])
            ->select(
                'products.name as product_name',
                'product_skus.sku as sku_code',
                DB::raw('SUM(invoice_items.quantity) as total_qty_sold'),
                DB::raw('SUM(invoice_items.total_amount) as total_revenue'),
                // 🌟 UX UPGRADE: Use GROUP_CONCAT to merge attributes directly in MySQL
                DB::raw("CONCAT(products.name, IF(COUNT(attribute_values.value) > 0, CONCAT(' - ', GROUP_CONCAT(DISTINCT attribute_values.value SEPARATOR ' - ')), '')) as display_name")
            )
            ->groupBy('product_skus.id', 'products.name', 'product_skus.sku')
            // Order by highest or lowest quantity sold
            ->orderBy('total_qty_sold', $orderBy)
            ->limit($limit)
            ->get();
    }

    /**
     * Get Top Customers by revenue (Confirmed Invoices only).
     * Requires Invoice::customer_id → clients.id (see Invoice::client()/customer()).
     */
    public function getTopCustomers(array $storeIds, string $startDate, string $endDate, int $limit = 10)
    {
        return Invoice::join('clients', 'invoices.customer_id', '=', 'clients.id')
            ->whereIn('invoices.store_id', $storeIds)
            ->where('invoices.status', 'confirmed')
            ->whereBetween('invoices.invoice_date', [$startDate, $endDate])
            ->select(
                'clients.id as client_id',
                'clients.name as client_name',
                'clients.phone as client_phone',
                DB::raw('COUNT(invoices.id) as invoice_count'),
                DB::raw('SUM(invoices.grand_total) as total_spent')
            )
            ->groupBy('clients.id', 'clients.name', 'clients.phone')
            ->orderByDesc('total_spent')
            ->limit($limit)
            ->get();
    }

    /**
     * Get Sales grouped by Source (POS vs Online vs Direct)
     */
    public function getSalesBySource(array $storeIds, string $startDate, string $endDate)
    {
        return Invoice::whereIn('store_id', $storeIds)
            ->where('status', 'confirmed')
            ->whereBetween('invoice_date', [$startDate, $endDate])
            ->select('source', DB::raw('SUM(grand_total) as total_revenue'), DB::raw('COUNT(id) as invoice_count'))
            ->groupBy('source')
            ->get();
    }
}