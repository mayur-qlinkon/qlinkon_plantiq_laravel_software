<?php

namespace App\Models;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttributeValue extends Model
{
    use HasFactory, SoftDeletes, Tenantable;

    protected $fillable = [
        'attribute_id',
        'value',
        'color_code',
        'position',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function attribute()
    {
        return $this->belongsTo(Attribute::class);
    }
}
