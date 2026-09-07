<?php

namespace App\Models\Hrm;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class HrmTaskComment extends Model
{
    use SoftDeletes;

    protected $table = 'hrm_task_comments';

    protected $fillable = [
        'hrm_task_id', 'user_id', 'parent_id',
        'body', 'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    // ── Relationships ──

    public function task(): BelongsTo
    {
        return $this->belongsTo(HrmTask::class, 'hrm_task_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(HrmTaskComment::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(HrmTaskComment::class, 'parent_id')->orderBy('created_at');
    }
}
