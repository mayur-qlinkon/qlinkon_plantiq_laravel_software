<?php

namespace App\Enums;

/**
 * Declares WHY a SKU search is being performed, which in turn determines the
 * filtering and the shape of the response.
 *
 * This exists because the previous implementation took these decisions from
 * query parameters sent by the browser (`in_stock_only=1`). That made the
 * rules bypassable: removing the parameter in devtools was enough to pull
 * out-of-stock items into an invoice. Encoding the rules here means the caller
 * declares its intent and the server decides the consequences.
 */
enum SkuSearchContext: string
{
    /** Invoices, quotations and orders — stock is committed, so it must exist. */
    case Sales = 'sales';

    /** Challans — goods move, but stock validation happens on submit, not search. */
    case Delivery = 'delivery';

    /** Purchases — stock is being created, so current levels are irrelevant. */
    case Procurement = 'procurement';

    /**
     * Whether a warehouse must be supplied and authorised for this context.
     *
     * Procurement searches happen before a warehouse is necessarily chosen, so
     * it is the only context that can run without one.
     */
    public function requiresWarehouse(): bool
    {
        return $this !== self::Procurement;
    }

    /**
     * Whether results are limited to SKUs with stock on hand.
     *
     * Only Sales enforces this. Delivery deliberately does not: a challan can
     * legitimately be prepared for goods still being picked, and the previous
     * implementation behaved the same way.
     */
    public function inStockOnly(): bool
    {
        return $this === self::Sales;
    }

    /**
     * Whether the purchase cost is included in the response.
     *
     * Cost is commercially sensitive and was previously returned to every
     * caller, including sales screens operated by staff who have no reason to
     * see supplier pricing. Only procurement needs it.
     */
    public function includesCost(): bool
    {
        return $this === self::Procurement;
    }

    /**
     * Whether results are restricted to sellable products, excluding catalog
     * items that exist for display only and are never transacted.
     *
     * Currently true everywhere, which preserves the previous behaviour
     * exactly. If catalog items ever need to be purchasable, returning false
     * for Procurement here is the single change required.
     */
    public function sellableOnly(): bool
    {
        return true;
    }

    /**
     * Whether current stock levels are attached to the response.
     *
     * True everywhere, including Procurement: the purchase picker shows stock
     * on hand so a buyer can see what is already held before ordering more.
     * Procurement does not *require* a warehouse, so when none is supplied the
     * lookup is skipped and levels come back as zero.
     *
     * This is distinct from inStockOnly(), which decides whether stock is
     * used to *filter* results rather than merely reported.
     */
    public function includesStock(): bool
    {
        return true;
    }
}