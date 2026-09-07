<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Plan extends Model
{
    use SoftDeletes; // 🌟 Added SoftDeletes trait

    protected $fillable = [
        'company_id',
        'name',
        'slug',
        'description',
        'price',
        'billing_cycle',
        'trial_days',
        'user_limit',
        'store_limit',
        'product_limit',
        'employee_limit',
        'ocr_scan_limit',
        'ai_chat_daily_limit',
        'ai_token_daily_limit',
        'is_recommended',
        'button_text',
        'button_link',
        'sort_order',
        'is_active',
        'builder_selection',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_recommended' => 'boolean',
        'is_active' => 'boolean',
        'user_limit' => 'integer',
        'store_limit' => 'integer',
        'product_limit' => 'integer',
        'employee_limit' => 'integer',
        'ocr_scan_limit' => 'integer',
        'ai_chat_daily_limit' => 'integer',        
        'ai_token_daily_limit' => 'integer',
        'trial_days' => 'integer',
        'sort_order' => 'integer',
        'builder_selection' => 'array',
    ];

    public function modules()
    {
        return $this->belongsToMany(Module::class, 'plan_modules');
    }

    public function subscriptions()
    {
        return $this->hasMany(CompanySubscription::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Public / reusable plans only (no owning company).
     * Per-tenant plans (company_id set) are excluded from listings.
     */
    public function scopePublic($query)
    {
        return $query->whereNull('company_id');
    }
}
