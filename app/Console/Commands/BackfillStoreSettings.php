<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Setting;
use App\Models\Store;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-time move of company-level configuration onto each store.
 *
 * Billing, banking and public identity now live on the store. Tenants who
 * configured these before the split still hold them as company-level rows,
 * and without this copy their invoice prefixes and bank details would silently
 * fall back to hardcoded defaults — changing live invoice numbering.
 */
class BackfillStoreSettings extends Command
{
    protected $signature = 'settings:backfill-stores
                            {--dry-run : Show what would change without writing}
                            {--company= : Limit to a single company id}';

    protected $description = 'Copy company-level billing and identity settings onto each store';

    /**
     * settings key => stores column.
     *
     * Only keys with a real column are listed. Anything else stays in the
     * key-value table and needs no migration.
     */
    private const COLUMN_MAP = [
        'bank_name' => 'bank_name',
        'bank_holder' => 'account_name',
        'bank_ac' => 'account_number',
        'ifsc' => 'ifsc_code',
        'bank_branch' => 'branch_name',
        'upi_id' => 'upi_id',
        'signature' => 'signature',
        'invoice_prefix' => 'invoice_prefix',
        'quotation_prefix' => 'quotation_prefix',
        'invoice_start_number' => 'next_invoice_number',
        'default_tax_type' => 'default_tax_type',
        'payment_terms' => 'default_payment_terms',
        'round_off' => 'round_off_amounts',
        'invoice_footer_note' => 'invoice_footer_note',
        'default_terms' => 'invoice_terms',
        'storefront_tagline' => 'tagline',
        'whatsapp' => 'whatsapp',
        'instagram' => 'instagram',
        'facebook' => 'facebook',
        'twitter' => 'twitter',
        'business_hours' => 'business_hours',
        'seo_title' => 'seo_title',
        'seo_description' => 'seo_description',
    ];

    /** Keys with no column — copied into store-scoped key-value rows instead. */
    private const KV_KEYS = [
        'seo_keywords',
        'og_image',
        'support_email',
        'youtube',
        'linkedin',
        'google',
    ];

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $companies = Company::query()
            ->when($this->option('company'), fn ($q, $id) => $q->whereKey($id))
            ->get(['id', 'name']);

        if ($companies->isEmpty()) {
            $this->warn('No companies found.');

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->warn('DRY RUN — nothing will be written.');
        }

        foreach ($companies as $company) {
            $this->processCompany($company, $dryRun);
        }

        $this->newLine();
        $this->info($dryRun ? 'Dry run complete.' : 'Backfill complete.');

        return self::SUCCESS;
    }

    private function processCompany(Company $company, bool $dryRun): void
    {
        $settings = Setting::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('store_id', Setting::COMPANY_LEVEL)
            ->pluck('value', 'key');

        $stores = Store::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->get();

        if ($stores->isEmpty()) {
            $this->warn("  [{$company->id}] {$company->name} — no stores, skipped.");

            return;
        }

        $this->line("  [{$company->id}] {$company->name} — {$stores->count()} store(s)");

        foreach ($stores as $store) {
            $changes = [];

            foreach (self::COLUMN_MAP as $settingKey => $column) {
                // Never overwrite a value the store already set for itself.
                $current = $store->getAttributes()[$column] ?? null;

                if ($current !== null && $current !== '') {
                    continue;
                }

                $value = $settings[$settingKey] ?? null;

                if ($value === null || $value === '') {
                    continue;
                }

                $changes[$column] = $value;
            }

            if ($changes) {
                $this->line('      '.$store->name.' → '.implode(', ', array_keys($changes)));

                if (! $dryRun) {
                    DB::table('stores')->where('id', $store->id)->update($changes);
                }
            }

            $this->copyKeyValueRows($company->id, $store->id, $settings, $dryRun);
        }

        // Whichever store storefront orders were already routed to becomes the
        // primary, since default_storefront_store_id is being retired.
        $this->markPrimaryStore($company->id, $settings, $stores, $dryRun);

        if (! $dryRun) {
            forget_settings_cache($company->id);
        }
    }

    private function copyKeyValueRows(int $companyId, int $storeId, $settings, bool $dryRun): void
    {
        foreach (self::KV_KEYS as $key) {
            $value = $settings[$key] ?? null;

            if ($value === null || $value === '') {
                continue;
            }

            $exists = Setting::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->where('store_id', $storeId)
                ->where('key', $key)
                ->exists();

            if ($exists) {
                continue;
            }

            if (! $dryRun) {
                Setting::create([
                    'company_id' => $companyId,
                    'store_id' => $storeId,
                    'key' => $key,
                    'value' => $value,
                ]);
            }
        }
    }

    private function markPrimaryStore(int $companyId, $settings, $stores, bool $dryRun): void
    {
        if ($stores->firstWhere('is_primary', true)) {
            return;
        }

        $preferredId = $settings['default_storefront_store_id'] ?? null;

        $primary = $stores->firstWhere('id', (int) $preferredId)
            ?? $stores->firstWhere('is_active', true)
            ?? $stores->first();

        $this->line("      primary → {$primary->name}");

        if (! $dryRun) {
            DB::table('stores')->where('id', $primary->id)->update(['is_primary' => true]);
        }
    }
}