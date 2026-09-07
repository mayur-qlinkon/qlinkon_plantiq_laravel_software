<?php

namespace App\Http\Requests\Admin;

use App\Models\InvoiceItem;
use App\Models\InvoiceReturn;
use Illuminate\Contracts\Validation\Validator;

/**
 * Shared helper that enforces the "return qty ≤ remaining returnable qty" rule
 * inside Store/UpdateInvoiceReturnRequest. Kept as a single source of truth
 * so Create and Edit forms behave identically.
 */
class InvoiceReturnQuantityValidator
{
    /**
     * Validate the submitted return items against remaining capacity per invoice line.
     *
     * @param  array<int, array<string, mixed>>  $items  The items[] array from the request
     * @param  int|null  $excludeReturnId  When editing a draft, exclude its own prior line quantities from the
     *                                     "already returned" aggregate so users can re-save the same draft.
     */
    public static function validate(Validator $validator, array $items, int $invoiceId, ?int $excludeReturnId = null): void
    {
        // Aggregate requested qty per invoice_item_id. Multiple submitted rows for the same
        // invoice line share one capacity bucket.
        $requestedByItem = [];
        $firstRowIndexByItem = [];
        foreach ($items as $idx => $row) {
            $itemId = (int) ($row['invoice_item_id'] ?? 0);
            if ($itemId <= 0) {
                continue;
            }
            $qty = (float) ($row['quantity'] ?? 0);
            $requestedByItem[$itemId] = ($requestedByItem[$itemId] ?? 0) + $qty;
            $firstRowIndexByItem[$itemId] = $firstRowIndexByItem[$itemId] ?? $idx;
        }

        if (empty($requestedByItem)) {
            return;
        }

        // Load invoice lines and make sure they belong to the referenced invoice.
        $invoiceItems = InvoiceItem::with('sku')
            ->whereIn('id', array_keys($requestedByItem))
            ->get()
            ->keyBy('id');

        // Pre-compute how much of each line has been returned on DRAFT returns that aren't
        // this one — those quantities aren't in return_quantity yet, but still consume capacity
        // the moment they're confirmed. We include them so the UI math stays honest.
        $draftAggregates = self::draftReturnedByItem(
            invoiceId: $invoiceId,
            invoiceItemIds: array_keys($requestedByItem),
            excludeReturnId: $excludeReturnId,
        );

        foreach ($requestedByItem as $invoiceItemId => $requestedQty) {
            $invoiceItem = $invoiceItems->get($invoiceItemId);
            $fieldIndex = $firstRowIndexByItem[$invoiceItemId] ?? 0;
            $fieldKey = "items.{$fieldIndex}.quantity";

            if (! $invoiceItem || (int) $invoiceItem->invoice_id !== $invoiceId) {
                $validator->errors()->add(
                    $fieldKey,
                    'One of the selected items does not belong to this invoice.'
                );

                continue;
            }

            $original = (float) $invoiceItem->quantity;
            $confirmedReturned = (float) ($invoiceItem->return_quantity ?? 0);
            $draftReturned = (float) ($draftAggregates[$invoiceItemId] ?? 0);
            $alreadyReturned = $confirmedReturned + $draftReturned;
            $remaining = max(0.0, $original - $alreadyReturned);

            if ($requestedQty > $remaining + 0.00005) {
                $validator->errors()->add(
                    $fieldKey,
                    self::friendlyMessage(
                        productLabel: self::buildProductLabel($invoiceItem),
                        original: $original,
                        remaining: $remaining,
                        requested: $requestedQty,
                    )
                );
            }
        }
    }

    /**
     * Sum quantities booked on other DRAFT returns per invoice line (to prevent two drafts
     * from both "reserving" capacity that can only be cashed in once).
     *
     * @param  array<int>  $invoiceItemIds
     * @return array<int, float>
     */
    private static function draftReturnedByItem(int $invoiceId, array $invoiceItemIds, ?int $excludeReturnId): array
    {
        if (empty($invoiceItemIds)) {
            return [];
        }

        $query = InvoiceReturn::where('invoice_id', $invoiceId)
            ->where('status', 'draft')
            ->when($excludeReturnId, fn ($q) => $q->where('id', '!=', $excludeReturnId))
            ->with(['items' => fn ($q) => $q->whereIn('invoice_item_id', $invoiceItemIds)]);

        $out = [];
        foreach ($query->get() as $draft) {
            foreach ($draft->items as $line) {
                $key = (int) $line->invoice_item_id;
                $out[$key] = ($out[$key] ?? 0) + (float) $line->quantity;
            }
        }

        return $out;
    }

    /**
     * Tenant-friendly, non-technical error message.
     */
    private static function friendlyMessage(string $productLabel, float $original, float $remaining, float $requested): string
    {
        $requestedFmt = self::formatQty($requested);
        $remainingFmt = self::formatQty($remaining);
        $originalFmt = self::formatQty($original);

        if ($remaining <= 0) {
            return "You cannot return {$requestedFmt} units for {$productLabel} because this item has already been fully returned against the original sale of {$originalFmt} units.";
        }

        return "You cannot return {$requestedFmt} units for {$productLabel} because only {$remainingFmt} units are still returnable from the original sale of {$originalFmt} units.";
    }

    private static function buildProductLabel(InvoiceItem $invoiceItem): string
    {
        $name = trim((string) ($invoiceItem->product_name ?? ''));
        $sku = $invoiceItem->sku?->sku ?? $invoiceItem->sku?->sku_code ?? null;

        if ($name !== '' && $sku) {
            return "{$name} ({$sku})";
        }

        if ($name !== '') {
            return $name;
        }

        return $sku ?: 'this item';
    }

    private static function formatQty(float $qty): string
    {
        $formatted = number_format($qty, 4, '.', '');

        return rtrim(rtrim($formatted, '0'), '.');
    }
}
