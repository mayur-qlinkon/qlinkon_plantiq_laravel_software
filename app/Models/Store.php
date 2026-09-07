<?php

namespace App\Models;

use App\Models\Setting;
use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Store extends Model
{
    use HasFactory, SoftDeletes, Tenantable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id', 
        'name', 
        'slug', 
        'domain', 
        'subdomain', 
        'email', 
        'phone', 
        'upi_id', 
        'logo', 
        'signature',
        'gst_number', 
        'currency', 
        'address', 
        'city', 
        'state_id', 
        'zip_code', 
        'country',
        'office_lat', 
        'office_lng', 
        'gps_radius_meters', 
        'is_active',
        
        // Billing & Banking Fields
        'bank_name', 
        'account_name', 
        'account_number', 
        'ifsc_code', 
        'branch_name',
        'invoice_prefix', 
        'quotation_prefix', 
        'purchase_prefix', 
        'next_invoice_number',
        'default_tax_type', 
        'default_payment_terms', 
        'default_payment_method_id', 
        'round_off_amounts',
        'invoice_footer_note', 
        'invoice_terms',

        // Public storefront identity
        'tagline',
        'description',
        'whatsapp',
        'instagram',
        'facebook',
        'twitter',
        'business_hours',
        'map_embed_url',
        'seo_title',
        'seo_description',
        'storefront_enabled',
        'is_primary',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'next_invoice_number' => 'integer',
        'office_lat' => 'float',
        'office_lng' => 'float',
        'gps_radius_meters' => 'integer',
        'round_off_amounts' => 'boolean',
        'storefront_enabled' => 'boolean',
        'is_primary' => 'boolean',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function ($store) {
            if (empty($store->slug)) {
                $store->slug = Str::slug($store->name) . '-' . Str::random(5);
            }
        });
        // Exactly one primary store per company. This drives storefront order
        // routing, so two of them would make the destination arbitrary.
        static::saved(function (self $store) {
            if ($store->is_primary) {
                static::withoutEvents(fn () => static::where('company_id', $store->company_id)
                    ->whereKeyNot($store->id)
                    ->update(['is_primary' => false]));
            }
        });

        $flush = function (self $store) {
            // Store columns feed store_setting() through config(), so a stale
            // settings cache would keep serving the previous billing details.
            forget_settings_cache($store->company_id);

            // Clear owner key + all staff keys for this company
            Cache::forget("ai_store_ctx_{$store->company_id}_all");

            // Drop the EnsureStoreExists fast-path flag for the acting user so
            // the onboarding guard re-evaluates against the real table.
            if (session()->isStarted()) {
                session()->forget('has_store');
            }
            // Staff keys are user-scoped — flush by pattern if cache driver supports it,
            // otherwise they expire naturally within 5 minutes (TTL = 300s).
            // For Redis: use Cache::deletePattern if your project adds that helper.
        };
        static::created($flush);
        static::updated($flush);
        static::deleted($flush);
    }

    /*
    |--------------------------------------------------------------------------
    | Core Relationships
    |--------------------------------------------------------------------------
    */

    public function defaultPaymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'default_payment_method_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'store_user');
    }

    /*
    |--------------------------------------------------------------------------
    | Operational Module Relationships
    |--------------------------------------------------------------------------
    */

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function warehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Local Query Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope a query to only include active stores.
     */
    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    /**
     * Scope a query to only include stores belonging to a specific company.
     */
    public function scopeForCompany(Builder $q, int $companyId): Builder
    {
        return $q->where('company_id', $companyId);
    }

    /*
    |--------------------------------------------------------------------------
    | Configuration
    |--------------------------------------------------------------------------
    | A store owns its billing, banking and public identity outright — there is
    | deliberately no company-level fallback. An empty field means the store has
    | genuinely not configured it, not that it should borrow someone else's.
    |
    | config() is the single read API. Callers do not need to know whether a
    | given key is a real column or a key-value row, which means new settings
    | can be added later without touching call sites.
    */

    /** Defaults applied when a store has left a config field empty. */
    public const CONFIG_DEFAULTS = [
        'invoice_prefix' => 'INV-',
        'quotation_prefix' => 'QTN-',
        'purchase_prefix' => 'PO-',
        'next_invoice_number' => 1,
        'default_tax_type' => 'cgst_sgst',
        'default_payment_terms' => 'immediate',
        'round_off_amounts' => true,
        'currency' => 'INR',
    ];

    /**
     * Read one configuration value.
     *
     * Columns win, because every field that already has one is authoritative
     * there. Anything else falls through to the store-scoped settings table,
     * which is where genuinely new configuration lands.
     */
    public function config(string $key, $default = null)
    {
        $default = $default ?? self::CONFIG_DEFAULTS[$key] ?? null;

        if (array_key_exists($key, $this->attributes)) {
            $value = $this->getAttribute($key);

            return ($value === null || $value === '') ? $default : $value;
        }

        return store_setting($key, $default, $this->id);
    }

    /**
     * Write one configuration value to whichever store it belongs in.
     * Column writes are left unsaved so several can be batched by the caller.
     */
    public function setConfig(string $key, $value): void
    {
        if (array_key_exists($key, $this->attributes) || in_array($key, $this->getFillable(), true)) {
            $this->setAttribute($key, $value);

            return;
        }

        Setting::set($key, $value, $this->company_id, null, null, $this->id);

        forget_settings_cache($this->company_id);
    }

    /** Key-value configuration rows scoped to this store. */
    public function settings(): HasMany
    {
        return $this->hasMany(Setting::class);
    }

    /**
     * Get the resolved URL path for the store logo.
     * Cascades down to Company Logo -> Dynamic UI Avatar.
     */
    public function getLogoUrlAttribute(): string
    {
        if ($this->logo) {
            return asset('storage/' . $this->logo);
        }

        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name);
    }

    /**
     * Get the resolved URL path for the store authorized signature.
     */
    public function getSignatureUrlAttribute(): ?string
    {
        return $this->signature
            ? asset('storage/' . $this->signature)
            : null;
    }

    /**
     * Get the contextual store-specific public storefront URL route.
     */
    public function getPublicUrlAttribute(): string
    {
        return url("/{$this->company->slug}/{$this->slug}");
    }
}