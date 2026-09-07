<?php

namespace App\Models;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attribute extends Model
{
    use HasFactory, SoftDeletes, Tenantable;

    protected $fillable = [
        'name',
        'type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function values()
    {
        return $this->hasMany(AttributeValue::class)->orderBy('position');
    }

    public function attribute()
    {
        return $this->belongsTo(Attribute::class);
    }
}
