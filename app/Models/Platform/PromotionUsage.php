<?php

namespace App\Models\Platform;

use App\Models\Client;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PromotionUsage extends Model
{
    use HasFactory;

    protected $fillable = [
        'promotion_id',
        'company_id',
        'client_id',
        'usable_type',
        'usable_id',
        'discount_amount',
    ];

    protected function casts(): array
    {
        return [
            'discount_amount' => 'decimal:2',
        ];
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function usable(): MorphTo
    {
        return $this->morphTo();
    }
}