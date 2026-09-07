<?php

namespace App\Models;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OcrScan extends Model
{
    use Tenantable;
    /*
    |--------------------------------------------------------------------------
    | Fillable
    |--------------------------------------------------------------------------
    */
    protected $fillable = [
        'company_id',        
        'user_id',
        'scan_type',
        'original_filename',
        'image_path',
        'raw_ocr_text',
        'extracted_data',
        'edited_data',
        'status',
        'ocr_engine',
        'is_archived',
        'notes',
    ];

    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */
    protected $casts = [
        'extracted_data' => 'array',
        'edited_data'    => 'array',
        'is_archived'    => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('scan_type', $type);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    /**
     * Returns the final confirmed data (edited > extracted > empty array).
     */
    public function getFinalDataAttribute(): array
    {
        return $this->edited_data ?? $this->extracted_data ?? [];
    }

    /**
     * Returns the display name — best field we can find in the final data.
     */
    public function getDisplayNameAttribute(): string
    {
        $data = $this->final_data;

        return $data['name'] ?? $data['company'] ?? $data['email'] ?? 'Untitled Scan';
    }

    /**
     * Public URL of the scanned image (if stored).
     */
    public function getImageUrlAttribute(): ?string
    {
        // Routed through the controller so every fetch is authenticated and
        // company-checked. asset('storage/…') exposed tenant documents to the
        // open internet.
        return $this->image_path
            ? route('admin.ocr-scanner.image', $this->id)
            : null;
    }
}
