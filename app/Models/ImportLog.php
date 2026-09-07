<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportLog extends Model
{
    protected $fillable = [
        'import_id',
        'row_number',
        'row_data',
        'error_message',
    ];

    protected $casts = [
        'row_data' => 'array',
        'row_number' => 'integer',
    ];

    public function import(): BelongsTo
    {
        return $this->belongsTo(Import::class);
    }
}
