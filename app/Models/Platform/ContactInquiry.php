<?php

namespace App\Models\Platform;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Platform-level contact/inquiry from the public landing page.
 * Deliberately excludes Tenantable — this belongs to no company.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property string $message
 * @property bool $is_read
 * @property Carbon $created_at
 */
class ContactInquiry extends Model
{
    /** No updated_at — these are immutable once submitted. */
    public const UPDATED_AT = null;

    protected $fillable = ['user_id', 'name', 'email', 'phone', 'message', 'is_read'];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Mark this inquiry as read.
     */
    public function markRead(): void
    {
        $this->update(['is_read' => true]);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
