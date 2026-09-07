<?php

namespace App\Traits;

use App\Notifications\AppNotification;

/**
 * Drop this on any Notifiable model (User, etc.) for a one-line dispatch.
 */
trait HasSmartNotifications
{
    public function sendSmartNotification(
        string $title,
        string $message,
        ?string $link = null,
        string $icon = 'bell',
        string $color = 'blue',
        string $type = 'info',
        array $extra = [],
    ): void {
        $this->notify(new AppNotification(
            title: $title,
            message: $message,
            link: $link,
            icon: $icon,
            color: $color,
            type: $type,
            extra: $extra,
        ));
    }
}