<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\FormResponse;

class LeadAssigned implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $formResponse;
    public $typingUserId;
    public $appraisalValue;

    public function __construct(FormResponse $formResponse, $typingUserId = null, $appraisalValue = null)
    {
        $this->formResponse = $formResponse;
        $this->typingUserId = $typingUserId;
        $this->appraisalValue = $appraisalValue;
    }

    public function broadcastOn()
    {
        return new Channel('leads.' . $this->formResponse->id);
    }

    public function broadcastWith()
    {
        return [
            'id' => $this->formResponse->id,
            'stage' => $this->formResponse->stage,
            'appraised_by' => $this->formResponse->appraised_by,
            'user_name' => $this->formResponse->appraised_by ? \App\Models\User::find($this->formResponse->appraised_by)->name : null,
            'assigned_slack_user_id' => $this->formResponse->assigned_slack_user_id,
            'slack_username' => $this->formResponse->slack_username,
            'appraisal_value' => $this->appraisalValue ?? $this->formResponse->appraisal_value,
            'typing_user_id' => $this->typingUserId,
            'typing_user_name' => $this->typingUserId ? \App\Models\User::find($this->typingUserId)->name : null,
            'assigned_slack_channel' => $this->formResponse->assigned_slack_channel,
            'certificate' => $this->formResponse->certificate ? [
                'id' => $this->formResponse->certificate->id,
                'path' => $this->formResponse->certificate->path,
                'created_at' => $this->formResponse->certificate->created_at,
                'updated_at' => $this->formResponse->certificate->updated_at,
                'expiry' => $this->formResponse->certificate->expiry,
                'form_response_id' => $this->formResponse->certificate->form_response_id,
                'tenant_id' => $this->formResponse->certificate->tenant_id,
            ] : null,
        ];
    }
}