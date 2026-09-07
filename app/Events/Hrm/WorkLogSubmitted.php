<?php

namespace App\Events\Hrm;

use App\Models\Hrm\WorkLog;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WorkLogSubmitted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $workLog;

    public function __construct(WorkLog $workLog)
    {
        $this->workLog = $workLog;
    }
}