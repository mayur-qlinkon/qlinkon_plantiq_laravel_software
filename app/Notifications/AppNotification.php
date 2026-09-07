<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AppNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected string $title,
        protected string $message,
        protected ?string $link = null,
        protected string $icon = 'bell',   
        protected string $color = 'blue',  
        protected string $type = 'info',   
        protected array $extra = [],       
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return array_merge([
            'title'   => $this->title,
            'message' => $this->message,
            'link'    => $this->link,
            'icon'    => $this->icon,
            'color'   => $this->color,
            'type'    => $this->type,
        ], $this->extra);
    }
}