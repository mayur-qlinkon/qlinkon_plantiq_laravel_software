<?php

namespace App\Events\Hrm;

use App\Models\Hrm\Leave;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LeaveRequested
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $leave;

    /**
     * Create a new event instance.
     */
    public function __construct(Leave $leave)
    {
        $this->leave = $leave;
    }
}
