<?php

namespace App\Models;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiMessage extends Model
{
    use Tenantable;

    public const ROLE_USER      = 'user';
    public const ROLE_ASSISTANT = 'assistant';

    protected $fillable = [
        'conversation_id',
        'company_id',
        'role',
        'content',
        'tokens',
    ];

    protected $casts = [
        'tokens' => 'integer',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiConversation::class, 'conversation_id');
    }
}