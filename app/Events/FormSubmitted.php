<?php

// app/Events/FormSubmitted.php
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FormSubmitted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $formResponseId;

    public function __construct($formResponseId)
    {
        $this->formResponseId = $formResponseId;
    }

    public function broadcastOn()
    {
        return new Channel('kanban');
    }

    public function broadcastAs()
    {
        return 'form.submitted';
    }

    public function broadcastWith()
    {
        return ['form_response_id' => $this->formResponseId];
    }
}
