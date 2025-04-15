<?php

namespace App\Events;

use App\Models\FormResponse;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Broadcasting\Channel;

class FormResponseUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $formResponseId;

    public function __construct($formResponseId)
    {
        $this->formResponseId = $formResponseId;
    }

    /**
     * The channel the event should broadcast on.
     */
    public function broadcastOn()
    {
        // You can do a public channel or presence channel
        return new Channel('form-responses');
    }

    /**
     * The data to broadcast
     */
    public function broadcastWith()
    {
        $formResponse = FormResponse::find($this->formResponseId);

        // Return the data you want on the frontend
        return [
            'id' => $formResponse->id,
            'stage' => $formResponse->stage,
            'appraised_by' => $formResponse->appraised_by,
            'assigned_slack_user_id' => $formResponse->assigned_slack_user_id,
            // any other fields you need
        ];
    }
}
