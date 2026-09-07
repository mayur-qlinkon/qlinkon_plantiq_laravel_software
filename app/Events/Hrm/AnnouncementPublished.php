<?php

namespace App\Events\Hrm;

use App\Models\Hrm\Announcement;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AnnouncementPublished
{
    use Dispatchable, SerializesModels;

    public function __construct(public Announcement $announcement) {}
}
