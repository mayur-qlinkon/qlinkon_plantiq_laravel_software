<?php

namespace App\Models;

use App\Enums\NotificationEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One tenant's decision about a single recipient of a single event.
 *
 * Deliberately without the Tenantable trait: the dispatcher runs from cron
 * for production task events, where there is no authenticated user and the
 * global scope would silently match nothing. company_id is always passed
 * explicitly instead.
 */
class NotificationPreference extends Model
{
    /** Resolves at send time to whoever currently holds the permission. */
    public const TYPE_PERMISSION = 'permission';

    /** recipient_value holds a users.id. */
    public const TYPE_USER = 'user';

    /** Laravel's own channel names, so they can be used as-is. */
    public const CHANNEL_DATABASE = 'database';
    public const CHANNEL_MAIL = 'mail';

    protected $fillable = [
        'company_id',
        'event',
        'recipient_type',
        'recipient_value',
        'channels',
    ];

    protected $casts = [
        'event' => NotificationEvent::class,
        'channels' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function isPermissionRow(): bool
    {
        return $this->recipient_type === self::TYPE_PERMISSION;
    }

    public function wants(string $channel): bool
    {
        return in_array($channel, $this->channels ?? [], true);
    }

    /**
     * Channels a recipient of the given type is allowed to use.
     *
     * Permission rows are in-app only: they resolve to however many people
     * currently hold the permission, so allowing email would let one setting
     * quietly turn into dozens of sends per event. Named users are an explicit
     * choice and may receive email.
     *
     * @return list<string>
     */
    public static function allowedChannelsFor(string $recipientType): array
    {
        return $recipientType === self::TYPE_PERMISSION
            ? [self::CHANNEL_DATABASE]
            : [self::CHANNEL_DATABASE, self::CHANNEL_MAIL];
    }
}