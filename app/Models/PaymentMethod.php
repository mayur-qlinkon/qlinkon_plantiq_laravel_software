<?php

namespace App\Models;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; 

class PaymentMethod extends Model
{
    use SoftDeletes,HasFactory,Tenantable;

    protected $fillable = [        
        'slug',
        'label',
        'gateway',
        'is_online',
        'is_active',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_online' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public static function getForSelector()
    {
        return self::where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'label', 'slug']);
    }    
}
