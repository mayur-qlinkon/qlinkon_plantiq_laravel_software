<?php

namespace App\Services\Inventory;

use App\Enums\NotificationEvent;
use App\Mail\DynamicMail;
use App\Models\Company;
use App\Models\ProductSku;
use App\Notifications\AppNotification;
use App\Services\NotificationDispatcher;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Daily low-stock digest.
 *
 * Runs from cron only, so nothing here may depend on an authenticated user:
 * the Tenantable trait registers no global scope without Auth, and a query
 * that forgets its own company_id would silently run across every tenant.
 * Every query below states company_id explicitly for that reason.
 *
 * WHY A DIGEST AND NOT A LIVE CHECK
 * A check inside deductStock() would fire on every POS sale and invoice, and
 * a SKU sitting near its threshold would re-announce itself on each one. With
 * no queue worker on shared hosting every dispatch is synchronous, so it would
 * also add work to the sale request itself.
 *
 * WHY low_stock_notified_at
 * Without a flag the same SKUs would be reported every single night until
 * somebody restocked them, and the alert would be ignored within a week. The
 * column is the entire state machine:
 *   NULL     — not currently flagged; if the SKU is low, announce it and stamp it
 *   stamped  — already announced; stay quiet
 *   back above the threshold (or deactivated) — clear the stamp so the next dip
 *              announces again
 */
class LowStockNotificationService
{
    /** Names listed inline in the in-app message before it collapses to "and N more". */
    private const MAX_NAMES_IN_MESSAGE = 3;

    /** Rows listed in the email table before it collapses to "and N more". */
    private const MAX_ROWS_IN_EMAIL = 25;

    public function __construct(private NotificationDispatcher $dispatcher) {}

    /**
     * Scan one company, notify about newly-low SKUs, and clear recovered ones.
     *
     * @return array{low: int, notified: int, recovered: int}
     */
    public function runForCompany(Company $company, bool $dryRun = false): array
    {
        $lowStockSkus = $this->lowStockSkus($company->id);
        $lowIds       = $lowStockSkus->pluck('id')->all();

        // Recovered first. A SKU that climbed back above its threshold, had its
        // alert level set to 0, or was deactivated is no longer in $lowIds, so
        // this single query covers all three cases without special-casing them.
        $recoveredQuery = $this->recoveredQuery($company->id, $lowIds);

        $recovered = $dryRun
            ? $recoveredQuery->count()
            : $recoveredQuery->update(['low_stock_notified_at' => null]);

        // Only SKUs we have not already announced.
        $newlyLow = $lowStockSkus->whereNull('low_stock_notified_at')->values();

        if ($newlyLow->isEmpty()) {
            return [
                'low'       => $lowStockSkus->count(),
                'notified'  => 0,
                'recovered' => $recovered,
            ];
        }

        if (! $dryRun) {
            // Dispatch before stamping, deliberately. If the dispatch throws,
            // the stamps stay NULL and tomorrow's run retries — the opposite
            // order would swallow the alert permanently.
            $this->dispatchDigest($company, $newlyLow);

            $this->flagAsNotified($company->id, $newlyLow->pluck('id')->all());
        }

        return [
            'low'       => $lowStockSkus->count(),
            'notified'  => $newlyLow->count(),
            'recovered' => $recovered,
        ];
    }

    // ══════════════════════════════════════════════════════════
    //  QUERIES
    // ══════════════════════════════════════════════════════════

    /**
     * Every SKU currently at or below its stock alert level, company-wide.
     *
     * Shape copied from DashboardController::getLowStockAlerts() because that
     * query is already proven against MySQL strict mode: the SUM is grouped in
     * a subquery and LEFT JOINed, so no GROUP BY or HAVING is needed on the
     * outer query.
     *
     * Store scoping is intentionally absent — active_store_ids() reads the
     * session and there is none in cron. See the note in the command.
     *
     * @return Collection<int, ProductSku>
     */
    private function lowStockSkus(int $companyId): Collection
    {
        $stockTotals = DB::table('product_stocks')
            ->select('product_sku_id', DB::raw('SUM(qty) as total_qty'))
            ->where('company_id', $companyId)
            // Stock parked in a deleted warehouse must not count towards the
            // total, otherwise a SKU looks stocked when it is not reachable.
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('warehouses')
                    ->whereColumn('warehouses.id', 'product_stocks.warehouse_id')
                    ->whereNull('warehouses.deleted_at');
            })
            ->groupBy('product_sku_id');

        return ProductSku::query()
            ->select('product_skus.*', DB::raw('COALESCE(stock_totals.total_qty, 0) as current_stock'))
            ->leftJoinSub($stockTotals, 'stock_totals', function ($join) {
                $join->on('product_skus.id', '=', 'stock_totals.product_sku_id');
            })
            ->with('product:id,name')
            ->where('product_skus.company_id', $companyId)
            ->where('product_skus.is_active', true)
            ->where('product_skus.stock_alert', '>', 0)
            // Two things at once. Deleting a product soft-deletes the product
            // only, never its SKUs, so without the whereHas an orphaned SKU
            // would keep raising alerts for a product nobody can open any more.
            // The is_active check covers the other half: a product switched off
            // from products.toggle-status is not being sold, so restocking it
            // is not an action anybody needs prompting about.
            ->whereHas('product', fn ($q) => $q->where('is_active', true))
            ->whereRaw('COALESCE(stock_totals.total_qty, 0) <= product_skus.stock_alert')
            ->orderBy('product_skus.id')
            ->get();
    }

    /**
     * Stamped SKUs that are no longer in the low set.
     *
     * Written on the query builder rather than Eloquent so updated_at is left
     * alone — this is bookkeeping, not a change to the product itself, and
     * bumping the timestamp would reorder every "recently updated" listing.
     */
    private function recoveredQuery(int $companyId, array $lowIds)
    {
        $query = DB::table('product_skus')
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->whereNotNull('low_stock_notified_at');

        // Guarded rather than relying on whereNotIn([]) resolving to "always
        // true". It does, but the behaviour is easy to misread later, and here
        // an empty list genuinely means "clear every stamp".
        if ($lowIds !== []) {
            $query->whereNotIn('id', $lowIds);
        }

        return $query;
    }

    private function flagAsNotified(int $companyId, array $skuIds): void
    {
        DB::table('product_skus')
            ->where('company_id', $companyId)
            ->whereIn('id', $skuIds)
            ->update(['low_stock_notified_at' => now()]);
    }

    // ══════════════════════════════════════════════════════════
    //  DISPATCH
    // ══════════════════════════════════════════════════════════

    /**
     * @param  Collection<int, ProductSku>  $skus
     */
    private function dispatchDigest(Company $company, Collection $skus): void
    {
        $count   = $skus->count();
        $viewUrl = route('admin.inventory.reports.index', ['tab' => 'alerts']);

        $this->dispatcher->dispatch(
            NotificationEvent::InventoryLowStock,
            $company->id,
            notification: new AppNotification(
                title: 'Low Stock Alert',
                message: $this->buildMessage($skus),
                link: $viewUrl,
                icon: 'package-x',
                color: 'amber',
                type: 'inventory_low_stock',
                extra: [
                    'low_stock_count' => $count,
                    'sku_ids'         => $skus->pluck('id')->all(),
                ],
            ),
            mailable: new DynamicMail(
                $count === 1
                    ? 'Low stock: 1 product needs restocking'
                    : "Low stock: {$count} products need restocking",
                'emails.low-stock',
                [
                    'companyName'   => $company->name,
                    'lowStockCount' => $count,
                    'rows'          => $this->emailRows($skus),
                    'hiddenCount'   => max(0, $count - self::MAX_ROWS_IN_EMAIL),
                    'viewUrl'       => $viewUrl,
                ],
            ),
            // The permission row is company-wide; the module seat is separate.
            // Without this a user holding inventory_reports.view but no seat is
            // told about a page that answers 403.
            recipientFilter: fn ($user) => user_can_access_module($user, 'inventory'),
        );
    }

    // ══════════════════════════════════════════════════════════
    //  PRESENTATION
    // ══════════════════════════════════════════════════════════

    /**
     * @param  Collection<int, ProductSku>  $skus
     */
    private function buildMessage(Collection $skus): string
    {
        if ($skus->count() === 1) {
            $sku = $skus->first();

            return sprintf(
                '%s is down to %d, at or below its alert level of %d.',
                $this->label($sku),
                $this->currentStock($sku),
                (int) $sku->stock_alert,
            );
        }

        $names     = $skus->take(self::MAX_NAMES_IN_MESSAGE)->map(fn ($sku) => $this->label($sku))->all();
        $remaining = $skus->count() - count($names);
        $list      = implode(', ', $names);

        return $remaining > 0
            ? "{$skus->count()} products dropped to their stock alert level: {$list} and {$remaining} more."
            : "{$skus->count()} products dropped to their stock alert level: {$list}.";
    }

    /**
     * @param  Collection<int, ProductSku>  $skus
     * @return list<array{name: string, sku: string, current: int, alert: int}>
     */
    private function emailRows(Collection $skus): array
    {
        return $skus->take(self::MAX_ROWS_IN_EMAIL)
            ->map(fn (ProductSku $sku) => [
                'name'    => $sku->product?->name ?? 'Unknown product',
                'sku'     => $sku->sku,
                'current' => $this->currentStock($sku),
                'alert'   => (int) $sku->stock_alert,
            ])
            ->values()
            ->all();
    }

    private function label(ProductSku $sku): string
    {
        $name = $sku->product?->name ?? 'Unknown product';

        return "{$name} ({$sku->sku})";
    }

    /** current_stock comes back from SUM() as a string on MySQL. */
    private function currentStock(ProductSku $sku): int
    {
        return (int) ($sku->current_stock ?? 0);
    }
}