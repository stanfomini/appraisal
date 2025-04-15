<?php

// app/Events/StageUpdated.php
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StageUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $responseId;

    public function __construct($responseId)
    {
        $this->responseId = $responseId;
    }

    public function broadcastOn()
    {
        return new Channel('kanban');
    }

    public function broadcastAs()
    {
        return 'stage.updated';
    }

    public function broadcastWith()
    {
        return ['response_id' => $this->responseId];
    }
}