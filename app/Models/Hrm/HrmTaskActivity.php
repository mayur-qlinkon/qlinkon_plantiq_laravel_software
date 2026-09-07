<?php

namespace App\Models\Hrm;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrmTaskActivity extends Model
{
    protected $fillable = [
        'hrm_task_id',
        'user_id',
        'description',
        'event',
        'old_value',
        'new_value',
        'meta',
    ];
    protected $casts = [
        'meta' => 'array',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(HrmTask::class, 'hrm_task_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}