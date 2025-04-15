<?php

// app/Events/FormAppraised.php
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FormAppraised implements ShouldBroadcast
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
        return 'form.appraised';
    }

    public function broadcastWith()
    {
        return ['form_response_id' => $this->formResponseId];
    }
}