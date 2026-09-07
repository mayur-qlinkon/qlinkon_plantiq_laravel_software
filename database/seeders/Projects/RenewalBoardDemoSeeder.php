<?php

namespace Database\Seeders\Projects;

use App\Enums\Auth\UserType;
use App\Enums\Project\ClientServiceStatus;
use App\Models\Client;
use App\Models\Project\ProjectClientService;
use App\Models\Project\ProjectService;
use App\Models\User;
use App\Services\Project\ClientServiceRenewalService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

/**
 * Fills every bucket of the renewal board with something to look at.
 *
 * Rows are created through ClientServiceRenewalService::sell() so each one also
 * gets a real first charge, then their period is moved to an exact target date.
 * The dates are forced rather than derived from started_at because the point
 * here is bucket coverage: a yearly service seeded "naturally" lands twelve
 * months out and three of the eight tabs stay empty.
 *
 * Overdue rows keep status = active on purpose. That is the honest state of a
 * lapsed service nobody has renewed, and it is exactly the row the board exists
 * to surface — an "expired" badge would mean somebody already noticed.
 *
 * Run:  php artisan db:seed --class="Database\Seeders\Projects\RenewalBoardDemoSeeder"
 */
class RenewalBoardDemoSeeder extends Seeder
{
    private const CLIENTS = [
        ['phone' => '9100000001', 'name' => 'Demo — Kiran Textiles',   'city' => 'Rajkot'],
        ['phone' => '9100000002', 'name' => 'Demo — Nova Nursery',     'city' => 'Junagadh'],
        ['phone' => '9100000003', 'name' => 'Demo — Shakti Foods',     'city' => 'Ahmedabad'],
        ['phone' => '9100000004', 'name' => 'Demo — Vinayak Jewels',   'city' => 'Surat'],
    ];

    /**
     * offset_days is measured from today and becomes current_period_end, which
     * is the single column every bucket is defined against.
     */
    private const ROWS = [
        // Overdue — period ended, never renewed.
        ['client' => 0, 'service' => 'Shared Hosting',   'offset_days' => -140, 'status' => 'active'],
        ['client' => 1, 'service' => 'Domain Renewal',   'offset_days' => -40,  'status' => 'active'],
        ['client' => 2, 'service' => 'Website AMC',      'offset_days' => -6,   'status' => 'active'],

        // Today.
        ['client' => 3, 'service' => 'Domain Renewal',   'offset_days' => 0,    'status' => 'active'],
        ['client' => 0, 'service' => 'SEO Retainer',     'offset_days' => 0,    'status' => 'active'],

        // This week.
        ['client' => 1, 'service' => 'Shared Hosting',   'offset_days' => 2,    'status' => 'active'],
        ['client' => 2, 'service' => 'SEO Retainer',     'offset_days' => 5,    'status' => 'active'],
        ['client' => 3, 'service' => 'Website AMC',      'offset_days' => 7,    'status' => 'active'],

        // This month.
        ['client' => 0, 'service' => 'Website AMC',      'offset_days' => 12,   'status' => 'active'],
        ['client' => 1, 'service' => 'Priority Support', 'offset_days' => 21,   'status' => 'active'],
        ['client' => 2, 'service' => 'Domain Renewal',   'offset_days' => 29,   'status' => 'active'],

        // Running, comfortably ahead.
        ['client' => 3, 'service' => 'Shared Hosting',   'offset_days' => 95,   'status' => 'active'],
        ['client' => 0, 'service' => 'Priority Support', 'offset_days' => 210,  'status' => 'active'],

        // Already marked expired by someone.
        ['client' => 1, 'service' => 'Website AMC',      'offset_days' => -300, 'status' => 'expired'],
        ['client' => 3, 'service' => 'SEO Retainer',     'offset_days' => -75,  'status' => 'expired'],

        // Cancelled.
        ['client' => 2, 'service' => 'Shared Hosting',   'offset_days' => 45,   'status' => 'cancelled'],
    ];

    private const CATALOG = [
        'Domain Renewal'   => ['service_type' => 'domain',      'billing_cycle' => 'yearly',  'price' => 1200,  'tax_rate' => 18],
        'Shared Hosting'   => ['service_type' => 'hosting',     'billing_cycle' => 'yearly',  'price' => 8000,  'tax_rate' => 18],
        'Website AMC'      => ['service_type' => 'maintenance', 'billing_cycle' => 'yearly',  'price' => 18000, 'tax_rate' => 18],
        'SEO Retainer'     => ['service_type' => 'marketing',   'billing_cycle' => 'monthly', 'price' => 5000,  'tax_rate' => 18],
        'Priority Support' => ['service_type' => 'support',     'billing_cycle' => 'custom',  'price' => 4500,  'tax_rate' => 18, 'duration_days' => 90],
    ];

    private CarbonImmutable $today;

    private int $companyId;

    public function run(): void
    {
        // 1. Temporarily restore model events so Tenantable/StoreScoped hooks can inject company_id naturally
        $dispatcher = \Illuminate\Database\Eloquent\Model::getEventDispatcher();
        \Illuminate\Database\Eloquent\Model::setEventDispatcher(app('events'));

        $admin = User::where('user_type', UserType::COMPANY_ADMIN->value)
            ->whereNotNull('company_id')
            ->when(env('DEMO_COMPANY_ID',2), fn ($q, $id) => $q->where('company_id', $id))
            ->first();

        if (! $admin) {
            $this->command->error('No company admin found. Seed a company first.');

            // Ensure dispatcher is restored even on early exit
            \Illuminate\Database\Eloquent\Model::setEventDispatcher($dispatcher);
            return;
        }

        Auth::login($admin);

        $this->companyId = $admin->company_id;
        $this->today     = CarbonImmutable::today();

        $this->command->info("Seeding renewal board demo into company #{$this->companyId}.");

        $clients = $this->seedClients();
        $catalog = $this->seedCatalog();

        $this->wipePreviousRun($clients);

        $renewals = app(ClientServiceRenewalService::class);

        foreach (self::ROWS as $row) {
            $client  = $clients[$row['client']];
            $service = $catalog[$row['service']];

            $periodEnd = $this->today->addDays($row['offset_days']);

            // Because model events are restored above, sell() will naturally trigger 
            // the Tenantable trait, injecting company_id and store_id automatically.
            $clientService = $renewals->sell([
                'client_id'    => $client->id,
                'service_id'   => $service->id,
                'price'        => $service->price,
                'tax_rate'     => $service->tax_rate,
                'started_at'   => $periodEnd->subDays(30)->toDateString(),
                'raise_charge' => true,
            ]);

            $clientService->forceFill([
                'current_period_start' => $periodEnd->subDays($this->spanFor($service->billing_cycle->value))->toDateString(),
                'current_period_end'   => $periodEnd->toDateString(),
                'status'               => $row['status'],
                'auto_renew'           => $row['status'] === ClientServiceStatus::Active->value,
                'cancelled_at'         => $row['status'] === ClientServiceStatus::Cancelled->value
                    ? $this->today->subDays(10)->toDateString()
                    : null,
            ])->save();
        }

        $this->report();

        // 2. Mute events again to respect DatabaseSeeder's WithoutModelEvents state
        \Illuminate\Database\Eloquent\Model::setEventDispatcher($dispatcher);
    }

    /** Days in one billing period, used only to backfill a plausible period start. */
    private function spanFor(string $cycle): int
    {
        return match ($cycle) {
            'monthly'   => 30,
            'quarterly' => 90,
            'custom'    => 90,
            default     => 365,
        };
    }

    /** @return array<int, Client> */
    private function seedClients(): array
    {
        $clients = [];

        foreach (self::CLIENTS as $data) {
            $clients[] = Client::firstOrCreate(
                ['company_id' => $this->companyId, 'phone' => $data['phone']],
                [
                    'name'              => $data['name'],
                    'registration_type' => 'unregistered',
                    'city'              => $data['city'],
                    'notes'             => 'Seeded by RenewalBoardDemoSeeder. Safe to delete.',
                    'is_active'         => true,
                ]
            );
        }

        return $clients;
    }

    /** @return array<string, ProjectService> */
    private function seedCatalog(): array
    {
        $catalog = [];

        foreach (self::CATALOG as $name => $entry) {
            $catalog[$name] = ProjectService::updateOrCreate(
                ['company_id' => $this->companyId, 'name' => $name],
                $entry + ['is_active' => true, 'description' => 'Demo catalog entry.']
            );
        }

        return $catalog;
    }

    /**
     * Only these four demo clients are cleared. Truncating the tables would take
     * real data with it the first time this ran against a copy of production.
     *
     * @param  array<int, Client>  $clients
     */
    private function wipePreviousRun(array $clients): void
    {
        $clientIds = array_map(fn (Client $c) => $c->id, $clients);

        ProjectClientService::withoutGlobalScopes()
            ->whereIn('client_id', $clientIds)
            ->forceDelete();
    }

    private function report(): void
    {
        $rows = [];

        foreach (['overdue', 'today', 'week', 'month', 'active', 'expired', 'cancelled', 'all'] as $bucket) {
            $rows[] = [
                $bucket,
                ProjectClientService::query()->renewalBucket($bucket)->count(),
            ];
        }

        $this->command->table(['Bucket', 'Rows'], $rows);
    }
}