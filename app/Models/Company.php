<?php

namespace App\Models;

use App\Enums\UsageHealth;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'companies';

    /*
    |--------------------------------------------------------------------------
    | Mutators — normalize host-matching fields
    |--------------------------------------------------------------------------
    | TenantResolver always compares against a lowercased request host
    | (strtolower($request->getHost())). If a subdomain/domain is ever saved
    | with mixed case (e.g. an admin types "Acme"), it would never match and
    | that tenant's site would silently 404 on every request. Lowercasing on
    | write here is a single, guaranteed choke point — every create/update
    | path (onboarding services, StoreController, future admin UI) goes
    | through this automatically.
    */

    public function setSubdomainAttribute(?string $value): void
    {
        $this->attributes['subdomain'] = $value !== null && $value !== ''
            ? strtolower(trim($value))
            : null;
    }

    public function setDomainAttribute(?string $value): void
    {
        $this->attributes['domain'] = $value !== null && $value !== ''
            ? strtolower(trim($value))
            : null;
    }

    /*
    |--------------------------------------------------------------------------
    | Fillable Fields
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'name',
        'slug',
        'domain',
        'subdomain',
        'email',
        'phone',
        'logo',
        'gst_number',
        'currency',
        'address',
        'city',
        'state_id',
        'zip_code',
        'country',
        'is_active',
    ];

    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    protected $casts = [
        'is_active' => 'boolean',
        'last_active_at' => 'datetime',
        'usage_computed_at' => 'datetime',
        'usage_snapshot' => 'array',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */
    public function state()
    {
        return $this->belongsTo(State::class);
    }

    /**
     * Company has many users
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Company has many stores
     */
    public function stores()
    {
        return $this->hasMany(Store::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Boot Method
    |--------------------------------------------------------------------------
    */

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($company) {

            if (empty($company->slug)) {
                $company->slug = Str::slug($company->name);
            }

            // Auto-set subdomain from slug if not explicitly provided.
            // Company can override this from admin settings later.
            // if (empty($company->subdomain)) {
            //     $company->subdomain = $company->slug;
            // }

        });
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    /**
     * Canonical public storefront URL for this company.
     * Priority: custom domain → subdomain → slug (apex).
     */
    public function getStorefrontUrlAttribute(): string
    {
        $scheme = request()->getScheme() ?: 'https';

        if (! empty($this->domain)) {
            return $scheme . '://' . $this->domain;
        }

        $central = config('app.central_domain');
        if (! empty($this->subdomain) && ! empty($central)) {
            return $scheme . '://' . $this->subdomain . '.' . $central;
        }

        return rtrim(config('app.url'), '/') . '/' . $this->slug;
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function subscription()
    {
        return $this->hasOne(CompanySubscription::class);
    }
    /**
     * Check if this company's plan includes a specific module.
     */
    public function hasModule($moduleSlug)
    {
        $subscription = $this->subscription;
        
        if (! $subscription || ! $subscription->plan) {
            return false;
        }

        return $subscription->plan->modules->contains('slug', $moduleSlug);
    }


        /*
    |--------------------------------------------------------------------------
    | Usage health
    |--------------------------------------------------------------------------
    | Written nightly by companies:sync-usage. Everything here reads the
    | snapshot — none of it queries activity_log, which is far too large to
    | touch while rendering a list of tenants.
    */

    /**
     * The real last-active moment, live.
     *
     * The stored column is only ever as fresh as the last nightly run, which
     * makes it useless during the working day: a tenant who logged in an hour
     * ago still reads as dormant until tomorrow morning, and that is exactly
     * when someone would want to call them.
     *
     * sessions.last_activity moves on every request, so it wins whenever it is
     * newer. The column remains the fallback — sessions expire and are pruned,
     * so it is the only thing that remembers a tenant quiet for a month.
     */
    public function lastActiveAt(): ?Carbon
    {
        static $resolved = [];

        if (! array_key_exists($this->id, $resolved)) {
            $latestSession = DB::table('sessions')
                ->join('users', 'users.id', '=', 'sessions.user_id')
                ->where('users.company_id', $this->id)
                ->max('sessions.last_activity');

            $live = $latestSession
                ? Carbon::createFromTimestamp((int) $latestSession)
                : null;

            $stored = $this->last_active_at;

            $resolved[$this->id] = match (true) {
                $live && $stored => $live->gt($stored) ? $live : $stored,
                default => $live ?? $stored,
            };
        }

        return $resolved[$this->id];
    }

    /**
     * Days since the last sign of life, or null if there never was one.
     */
    public function idleDays(): ?int
    {
        $lastActive = $this->lastActiveAt();

        // copy() first: startOfDay() mutates the Carbon instance in place, and
        // this one is held in the static cache below. Without the copy the
        // first call to idleDays() rewinds the cached value to midnight, and
        // every later read of lastActiveAt() reports the time since midnight
        // instead of since the last request.
        return $lastActive
            ? (int) $lastActive->copy()->startOfDay()->diffInDays(now()->startOfDay())
            : null;
    }

    /**
     * Health from the live idle count, not the stored label.
     *
     * The stored value is a nightly photograph. Classifying from idleDays()
     * instead means a tenant who comes back at 11 AM stops reading "Dormant"
     * immediately, rather than at 1:15 the following morning.
     */
    public function health(): UsageHealth
    {
        return UsageHealth::fromIdleDays($this->idleDays());
    }

    /**
     * One value out of usage_snapshot, or a default.
     *
     * Read through here rather than $company->usage_snapshot['x'] so a key
     * added later does not break rows written before it existed.
     */
    public function usageStat(string $key, mixed $default = 0): mixed
    {
        return $this->usage_snapshot[$key] ?? $default;
    }

    /**
     * Thirty booleans, oldest first — one per day, true if anything happened.
     *
     * Padded on read because a snapshot written before the company was 30 days
     * old is genuinely shorter, and the sparkline still wants 30 cells.
     *
     * @return list<bool>
     */
    public function activityBitmap(): array
    {
        $bitmap = array_map('boolval', (array) $this->usageStat('bitmap_30', []));

        return array_slice(
            array_pad($bitmap, -30, false),
            -30
        );
    }

    /**
     * Users with a session touched in the last five minutes.
     *
     * Queried live rather than stored: the sessions table is small, prunes
     * itself, and a number labelled "online now" is worthless if it is a day
     * old.
     */
    public function onlineUserCount(): int
    {
        return DB::table('sessions')
            ->join('users', 'users.id', '=', 'sessions.user_id')
            ->where('users.company_id', $this->id)
            ->where('sessions.last_activity', '>=', now()->subMinutes(5)->getTimestamp())
            ->distinct('sessions.user_id')
            ->count('sessions.user_id');
    }
}
