<?php

namespace Database\Seeders\Projects;

use App\Enums\Auth\UserType;
use App\Models\Client;
use App\Models\PaymentMethod;
use App\Models\Project\Project;
use App\Models\Project\ProjectCharge;
use App\Models\Project\ProjectService;
use App\Models\User;
use App\Services\Project\ChargeAllocationService;
use App\Services\Project\ClientServiceRenewalService;
use App\Services\Project\ProjectChargeService;
use App\Services\Project\ProjectPaymentService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * A walkthrough of the Projects module as a story rather than a data dump.
 *
 * Everything goes through the service layer, never straight into the tables, so
 * the seeder exercises the same code paths the UI does. If allocation, renewal
 * or write-off logic breaks, this breaks with it.
 *
 * One client per scenario. Payments belong to a client, not a project — a
 * single payment can settle charges across several projects — so mixing every
 * scenario into one client made the workspace impossible to read: a ₹7,000
 * project showed ₹52,000 of payments that had nothing to do with it.
 *
 * Scenarios:
 *   A. Sharma Traders  — yearly hosting renewed, lapsed a year, resumed;
 *                        partial payment, goodwill write-off, leftover credit
 *   B. Patel Interiors — one-time job paid by a cheque that bounced
 *   C. Mehta Foods     — monthly retainer running behind, plus a draft project
 *
 * Run:  php artisan db:seed --class="Database\Seeders\Projects\ProjectDemoSeeder"
 */
class ProjectDemoSeeder extends Seeder
{
    /** Phone doubles as the lookup key, so re-runs update instead of duplicating. */
    private const CLIENTS = [
        'sharma' => ['phone' => '9000000001', 'name' => 'Demo — Sharma Traders',  'company' => 'Sharma Traders Pvt Ltd', 'city' => 'Rajkot'],
        'patel'  => ['phone' => '9000000002', 'name' => 'Demo — Patel Interiors', 'company' => 'Patel Interiors',        'city' => 'Junagadh'],
        'mehta'  => ['phone' => '9000000003', 'name' => 'Demo — Mehta Foods',     'company' => 'Mehta Foods LLP',        'city' => 'Ahmedabad'],
    ];

    private CarbonImmutable $today;

    private array $methods = [];

    private array $clients = [];
    private int $companyId;
    private int $storeId;

    public function run(): void
    {
        // The services read Auth::user() for company_id, store_id and
        // created_by. Without a logged-in user every insert lands with a null
        // tenant, so the seeder signs in as a company admin first.
        $admin = $this->resolveAdmin();

        if (! $admin) {
            return;
        }

        $this->companyId = (int) $admin->company_id;
        $this->storeId   = (int) ($admin->store_id ?? DB::table('stores')->where('company_id', $this->companyId)->value('id') ?? 1);

        $admin->store_id = $this->storeId;
        Auth::login($admin);


        $this->command->info("Seeding as {$admin->name} (company #{$this->companyId}, store #{$this->storeId}).");

        $this->today = CarbonImmutable::today();

        $this->methods = PaymentMethod::where('is_active', true)->pluck('id', 'slug')->toArray();

        if (empty($this->methods)) {
            $this->command->error(
                "Company #{$admin->company_id} has no active payment methods. ".
                'Payment methods are per-company — add them under Settings for this company first.'
            );

            return;
        }

        foreach (self::CLIENTS as $key => $data) {
            $this->clients[$key] = $this->demoClient($admin->company_id, $data);
        }

        $this->wipePreviousRun();

        $catalog = $this->seedCatalog($admin->company_id);

        $this->scenarioHostingWithLapse($catalog);
        $this->scenarioBouncedCheque($catalog);
        $this->scenarioMonthlyRetainer($catalog);

        $this->report();
    }

    // ─────────────────────────────────────────────────────────
    // SETUP
    // ─────────────────────────────────────────────────────────

    /**
     * Picks the company to seed into.
     *
     * Payment methods, clients and everything else here are per-company, so
     * grabbing the first company admin found would happily sign in to an empty
     * company and fail on the first lookup. Companies that already have payment
     * methods are offered first, because those are the ones actually set up.
     *
     * Set DEMO_COMPANY_ID in .env to skip the prompt.
     */
    private function resolveAdmin(): ?User
    {
        $query = User::where('user_type', UserType::COMPANY_ADMIN->value)->whereNotNull('company_id');

        if ($forced = env('DEMO_COMPANY_ID',"2")) {
            $admin = (clone $query)->where('company_id', $forced)->first();

            if (! $admin) {
                $this->command->error("No company admin found for company #{$forced}.");
            }

            return $admin;
        }

        $companyIds = PaymentMethod::withoutGlobalScopes()
            ->where('is_active', true)
            ->distinct()
            ->pluck('company_id');

        $admins = (clone $query)->whereIn('company_id', $companyIds)->get();

        if ($admins->isEmpty()) {
            $admins = $query->get();
        }

        if ($admins->isEmpty()) {
            $this->command->error('No company admin found. Seed a company first.');

            return null;
        }

        if ($admins->count() === 1) {
            return $admins->first();
        }

        $options = $admins
            ->mapWithKeys(fn (User $u) => [$u->id => "#{$u->company_id} — {$u->name} ({$u->email})"])
            ->toArray();

        $choice = $this->command->choice('Which company should the demo data go into?', $options);

        return $admins->first(fn (User $u) => $options[$u->id] === $choice);
    }

    private function method(string $slug): int
    {
        return $this->methods[$slug] ?? reset($this->methods);
    }

    private function demoClient(int $companyId, array $data): Client
    {
        return Client::firstOrCreate(
            ['company_id' => $companyId, 'phone' => $data['phone']],
            [
                'name'              => $data['name'],
                'company_name'      => $data['company'],
                'registration_type' => 'unregistered',
                'city'              => $data['city'],
                'notes'             => 'Seeded by ProjectDemoSeeder. Safe to delete.',
                'is_active'         => true,
            ]
        );
    }

    /**
     * Clears only the demo clients' project data so the seeder can be re-run.
     *
     * Scoped to these three clients on purpose — a seeder that truncates
     * project tables would take real data with it the first time someone ran it
     * against a live copy.
     */
    private function wipePreviousRun(): void
    {
        $clientIds = array_map(fn (Client $c) => $c->id, $this->clients);

        DB::transaction(function () use ($clientIds) {
            $chargeIds = DB::table('project_charges')->whereIn('client_id', $clientIds)->pluck('id');

            DB::table('project_charge_allocations')->whereIn('charge_id', $chargeIds)->delete();
            DB::table('project_charges')->whereIn('client_id', $clientIds)->delete();
            DB::table('project_client_services')->whereIn('client_id', $clientIds)->delete();
            DB::table('projects')->whereIn('client_id', $clientIds)->delete();

            DB::table('payments')
                ->where('party_type', 'customer')
                ->whereIn('party_id', $clientIds)
                ->where('payment_for', 'project')
                ->delete();
        });
    }

    /**
     * The company's price list. Nothing here is financial truth — every sale
     * snapshots these values, so repricing later never rewrites history.
     */
    private function seedCatalog(int $companyId)
    {
        $entries = [
            ['name' => 'Domain Registration',        'service_type' => 'domain',      'billing_cycle' => 'yearly',   'price' => 1200,  'tax_rate' => 18, 'description' => 'Annual .com / .in domain renewal.'],
            ['name' => 'Shared Hosting',             'service_type' => 'hosting',     'billing_cycle' => 'yearly',   'price' => 8000,  'tax_rate' => 18, 'description' => '10 GB SSD, unlimited bandwidth, daily backups.'],
            ['name' => 'Website AMC',                'service_type' => 'maintenance', 'billing_cycle' => 'yearly',   'price' => 18000, 'tax_rate' => 18, 'description' => 'Updates, patches and 4 content changes a month.'],
            ['name' => 'SEO Retainer',               'service_type' => 'marketing',   'billing_cycle' => 'monthly',  'price' => 5000,  'tax_rate' => 18, 'description' => 'On-page work, reporting and a monthly review call.'],
            ['name' => 'Logo & Branding',            'service_type' => 'design',      'billing_cycle' => 'one_time', 'price' => 6000,  'tax_rate' => 18, 'description' => 'Logo, colour palette and a one-page brand sheet.'],
            ['name' => 'Priority Support (90 days)', 'service_type' => 'support',     'billing_cycle' => 'custom',   'price' => 4500,  'tax_rate' => 18, 'duration_days' => 90, 'description' => 'Same-day response window. Demonstrates the custom cycle.'],
        ];

        foreach ($entries as $entry) {
            ProjectService::updateOrCreate(
                ['company_id' => $companyId, 'name' => $entry['name']],
                $entry + ['is_active' => true]
            );
        }

        return ProjectService::where('company_id', $companyId)->get()->keyBy('name');
    }

    /** The first charge raised by a sale, used to reference it without guessing by title. */
    private function chargeOf($clientService): ?ProjectCharge
    {
        return ProjectCharge::where('client_service_id', $clientService->id)->orderBy('id')->first();
    }

    // ─────────────────────────────────────────────────────────
    // SCENARIO A — renew, lapse, return, write-off, leftover credit
    // ─────────────────────────────────────────────────────────

    /**
     * The most instructive case in the module.
     *
     * Hosting paid on time in year one, paid short in year two, then the client
     * disappeared for a year and came back. The lapsed year is never billed —
     * they did not use it — which is the whole reason renewal refuses to guess
     * a period across a gap.
     *
     * Amounts (all include 18% tax):
     *   Year 1 charge  ₹9,440  ← settled by CASH-Y1
     *   Year 2 charge  ₹9,440  ← ₹5,000 paid, ₹4,440 written off
     *   Year 3         no charge at all — the lapse
     *   Year 4 charge  ₹10,030 ← settled from NEFT-15K
     *   SSL charge     ₹1,770  ← left open on purpose
     *   Credit left    ₹4,970  ← available to allocate against the SSL charge
     */
    private function scenarioHostingWithLapse($catalog): void
    {
        $client      = $this->clients['sharma'];
        $renewals    = app(ClientServiceRenewalService::class);
        $charges     = app(ProjectChargeService::class);
        $payments    = app(ProjectPaymentService::class);
        $allocations = app(ChargeAllocationService::class);

        $project = Project::create([
            'company_id'  => $this->companyId,
            'store_id'    => $this->storeId,
            'client_id'   => $client->id,
            'title'       => 'Sharma Traders — Website & Hosting',
            'description' => 'Corporate site with hosting. Renewed yearly.',
            'status'      => 'active',
            'start_date'  => $this->today->subYears(3)->toDateString(),
        ]);

        $hosting = $renewals->sell([
            'company_id'   => $this->companyId,
            'client_id'    => $client->id,
            'project_id'   => $project->id,
            'service_id'   => $catalog['Shared Hosting']->id,
            'started_at'   => $this->today->subYears(3)->toDateString(),
            'price'        => 8000,
            'raise_charge' => true,
        ]);

        // Year 2 — renewed on time.
        $yearTwo = $renewals->renew($hosting, [
            'period_start' => $this->today->subYears(2)->toDateString(),
            'price'        => 8000,
        ]);

        // Year 3 is skipped deliberately. No charge exists for it.

        // Year 4 — the client returns. period_start is explicit because the
        // service will not guess across the gap.
        $renewals->renew($hosting, [
            'period_start' => $this->today->toDateString(),
            'price'        => 8500, // Price rose while they were away.
        ]);

        $payments->record([
            'client_id'         => $client->id,
            'payment_method_id' => $this->method('cash'),
            'amount'            => 9440,
            'payment_date'      => $this->today->subYears(3)->addDays(3)->toDateString(),
            'reference'         => 'CASH-Y1',
            'notes'             => 'First year hosting, paid at the office.',
        ]);

        // Paid short. FIFO puts it on the oldest unpaid charge, which leaves
        // that charge partially paid rather than untouched.
        $payments->record([
            'client_id'         => $client->id,
            'payment_method_id' => $this->method('upi'),
            'amount'            => 5000,
            'payment_date'      => $this->today->subYears(2)->addDays(20)->toDateString(),
            'reference'         => 'UPI-Y2-PART',
            'notes'             => 'Part payment. Client asked for time on the rest.',
        ]);

        // The balance is forgiven rather than chased. A write-off is an
        // allocation like any other, so the charge closes without pretending
        // money arrived — and the ₹5,000 that did arrive stays visible.
        $yearTwo->refresh();

        if ($yearTwo->balance > 0) {
            $allocations->writeOff(
                $yearTwo,
                (float) $yearTwo->balance,
                'Goodwill discount agreed for the delay in service.'
            );
        }

        // The returning client pays a round sum. It clears year four and leaves
        // ₹4,970 sitting on their account.
        $payments->record([
            'client_id'         => $client->id,
            'payment_method_id' => $this->method('bank_transfer'),
            'amount'            => 15000,
            'payment_date'      => $this->today->subDays(2)->toDateString(),
            'reference'         => 'NEFT-RETURN-15K',
            'notes'             => 'Lump sum on returning. Anything spare stays as advance.',
        ]);

        // Raised after that payment and with credit application switched off,
        // so the workspace has one open charge sitting next to unapplied money
        // — which is exactly the situation the Allocate button exists for.
        $charges->create([
            'client_id'    => $client->id,
            'project_id'   => $project->id,
            'title'        => 'SSL Certificate (wildcard)',
            'subtotal'     => 1500,
            'tax_rate'     => 18,
            'charge_date'  => $this->today->subDay()->toDateString(),
            'due_date'     => $this->today->addDays(5)->toDateString(),
            'apply_credit' => false,
        ]);
    }

    // ─────────────────────────────────────────────────────────
    // SCENARIO B — a cheque that bounced
    // ─────────────────────────────────────────────────────────

    /**
     * Reversal, not deletion. The bounced payment stays in the ledger marked
     * reversed, and the charge it had settled reopens on its own.
     */
    private function scenarioBouncedCheque($catalog): void
    {
        $client   = $this->clients['patel'];
        $renewals = app(ClientServiceRenewalService::class);
        $payments = app(ProjectPaymentService::class);

        $project = Project::create([
            'company_id'   => $this->companyId,
            'store_id'    => $this->storeId,
            'client_id'    => $client->id,
            'title'        => 'Patel Interiors — Logo & Branding',
            'description'  => 'One-off design job. A one-time service never renews.',
            'status'       => 'completed',
            'start_date'   => $this->today->subMonths(5)->toDateString(),
            'completed_at' => $this->today->subMonths(4)->toDateString(),
        ]);

        $renewals->sell([
            'company_id'   => $this->companyId,
            'client_id'    => $client->id,
            'project_id'   => $project->id,
            'service_id'   => $catalog['Logo & Branding']->id,
            'started_at'   => $this->today->subMonths(5)->toDateString(),
            'price'        => 6000,
            'raise_charge' => true,
        ]);

        $cheque = $payments->record([
            'client_id'         => $client->id,
            'payment_method_id' => $this->method('cheque'),
            'amount'            => 7080,
            'payment_date'      => $this->today->subMonths(5)->addDays(7)->toDateString(),
            'reference'         => 'CHQ-114522',
            'notes'             => 'Cheque handed over at delivery.',
        ]);

        $payments->reverse($cheque, 'Cheque returned by the bank — insufficient funds.');

        $payments->record([
            'client_id'         => $client->id,
            'payment_method_id' => $this->method('cash'),
            'amount'            => 7080,
            'payment_date'      => $this->today->subMonths(5)->addDays(15)->toDateString(),
            'reference'         => 'CASH-REPLACE-CHQ',
            'notes'             => 'Replacement for the bounced cheque.',
        ]);
    }

    // ─────────────────────────────────────────────────────────
    // SCENARIO C — retainer running behind, and an unbilled project
    // ─────────────────────────────────────────────────────────

    /** Three months billed, one paid. Gives the list screen a genuinely overdue project. */
    private function scenarioMonthlyRetainer($catalog): void
    {
        $client   = $this->clients['mehta'];
        $renewals = app(ClientServiceRenewalService::class);
        $payments = app(ProjectPaymentService::class);

        $project = Project::create([
            'company_id'   => $this->companyId,
            'store_id'    => $this->storeId,
            'client_id'   => $client->id,
            'title'       => 'Mehta Foods — Monthly SEO',
            'description' => 'Rolling retainer with auto-renew on.',
            'status'      => 'active',
            'start_date'  => $this->today->subMonths(3)->toDateString(),
        ]);

        $seo = $renewals->sell([
            'company_id'   => $this->companyId,
            'client_id'    => $client->id,
            'project_id'   => $project->id,
            'service_id'   => $catalog['SEO Retainer']->id,
            'started_at'   => $this->today->subMonths(3)->toDateString(),
            'price'        => 5000,
            'auto_renew'   => true,
            'raise_charge' => true,
        ]);

        $renewals->renew($seo, ['period_start' => $this->today->subMonths(2)->toDateString(), 'price' => 5000]);
        $renewals->renew($seo, ['period_start' => $this->today->subMonth()->toDateString(),   'price' => 5000]);

        // Only the first month was paid, so two months sit outstanding.
        $payments->record([
            'client_id'         => $client->id,
            'payment_method_id' => $this->method('upi'),
            'amount'            => 5900,
            'payment_date'      => $this->today->subMonths(3)->addDays(5)->toDateString(),
            'reference'         => 'UPI-SEO-M1',
        ]);

        // A project is work, not money. This one legitimately has neither yet,
        // which is what the "Unbilled" filter is for.
        Project::create([
            'company_id'        => $this->companyId,
            'store_id'    => $this->storeId,
            'client_id'         => $client->id,
            'title'             => 'Mehta Foods — Mobile App Prototype',
            'description'       => 'Scoping stage. Nothing agreed on price yet.',
            'status'            => 'draft',
            'start_date'        => $this->today->addDays(7)->toDateString(),
            'expected_end_date' => $this->today->addMonths(3)->toDateString(),
        ]);
    }

    // ─────────────────────────────────────────────────────────
    // REPORT
    // ─────────────────────────────────────────────────────────

    /** Prints what was created so the numbers on screen can be checked against intent. */
    private function report(): void
    {
        $rows = [];

        foreach ($this->clients as $client) {
            $charged = ProjectCharge::where('client_id', $client->id)->sum('total_amount');
            $paid    = ProjectCharge::where('client_id', $client->id)->sum('paid_amount');
            $written = ProjectCharge::where('client_id', $client->id)->sum('written_off_amount');

            $credit = app(ChargeAllocationService::class)->clientCreditBalance($client->id);

            $rows[] = [
                $client->name,
                number_format((float) $charged, 2),
                number_format((float) $paid, 2),
                number_format((float) $written, 2),
                number_format((float) $charged - $paid - $written, 2),
                number_format((float) $credit, 2),
            ];
        }

        $this->command->table(
            ['Client', 'Charged', 'Paid', 'Written off', 'Outstanding', 'Credit'],
            $rows
        );
    }
}




