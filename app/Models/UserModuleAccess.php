<?php

namespace App\Models;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserModuleAccess extends Model
{
    use Tenantable;
    protected $table = 'user_module_access';

    protected $fillable = [
        'company_id',
        'user_id',
        'module_id',
        'assigned_by',
        'assigned_at',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}