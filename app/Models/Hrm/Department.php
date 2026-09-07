<?php

namespace App\Models\Hrm;

use App\Models\User;
use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
// use Spatie\Activitylog\LogOptions;
// use Spatie\Activitylog\Traits\LogsActivity;

class Department extends Model
{
    use  SoftDeletes, Tenantable;

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'description',
        'head_id',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    // public function getActivitylogOptions(): LogOptions
    // {
    //     return LogOptions::defaults()
    //         ->logAll()
    //         ->logOnlyDirty()
    //         ->dontSubmitEmptyLogs()
    //         ->setDescriptionForEvent(fn (string $event) => "Department {$this->name} was {$event}");
    // }

    // ── Relationships ──

    public function head(): BelongsTo
    {
        return $this->belongsTo(User::class, 'head_id');
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    // ── Scopes ──

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
