<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\FormResponse;
use Stancl\Tenancy\Facades\Tenancy;

class SlackService
{
    protected $token;

    public function __construct($tenant = null)
    {
      // Use provided tenant or fall back to tenant() if available
      $this->tenant = $tenant ?? tenant();
      if (!$this->tenant || !$this->tenant->slack_bot_token) {
        throw new \Exception('No valid tenant or Slack bot token provided');
      }
      $this->token = $this->tenant->slack_bot_token;
    }
    
    public function postLeadToChannel(FormResponse $response, string $channelId)
    {
        $response->update(['assigned_slack_channel' => $channelId]);

        $payload = [
            'channel' => $channelId,
            'text' => "New lead: *{$response->full_name}*",
            'blocks' => [
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => "New lead: *{$response->full_name}*\nStage: {$response->stage}",
                    ],
                ],
                [
                    'block_id' => 'accept_lead_block', // Unique block ID for updates
                    'type' => 'actions',
                    'elements' => [
                        [
                            'type' => 'button',
                            'text' => ['type' => 'plain_text', 'text' => 'Accept Lead'],
                            'action_id' => 'accept_lead',
                            'value' => (string) $response->id,
                        ],
                    ],
                ],
            ],
        ];

        $slackResponse = Http::withToken($this->token)
            ->post('https://slack.com/api/chat.postMessage', $payload);

        if (!$slackResponse->json('ok')) {
            \Log::error('Slack postLeadToChannel failed', [
                'error' => $slackResponse->json('error'),
                'response' => $slackResponse->json(),
            ]);
            throw new \Exception('Failed to post to Slack: ' . ($slackResponse->json('error') ?? 'Unknown error'));
        }

        // Store the message timestamp for later updates
        $response->update(['slack_message_ts' => $slackResponse->json('ts')]);
    }

    public function updateLeadMessage(FormResponse $response, string $slackUserId, string $slackUsername)
    {
        $payload = [
            'channel' => $response->assigned_slack_channel,
            'ts' => $response->slack_message_ts,
            'text' => "Lead assigned: *{$response->full_name}*",
            'blocks' => [
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => "Lead assigned: *{$response->full_name}*\nStage: {$response->stage}\nAssigned to: <@$slackUserId> ($slackUsername)",
                    ],
                ],
                // Removed the actions block to disable the button
            ],
        ];

        $slackResponse = Http::withToken($this->token)
            ->post('https://slack.com/api/chat.update', $payload);

        if (!$slackResponse->json('ok')) {
            \Log::error('Slack updateLeadMessage failed', [
                'error' => $slackResponse->json('error'),
                'response' => $slackResponse->json(),
            ]);
        }
    }

    public function dmLeadDetails(string $slackUserId, FormResponse $response)
    {
        $payload = [
            'channel' => $slackUserId, // Send to the clicking user, not the bot
            'text' => "Lead assigned: *{$response->full_name}*\nStage: {$response->stage}\nDetails: ...",
        ];

        $slackResponse = Http::withToken($this->token)
            ->post('https://slack.com/api/chat.postMessage', $payload);

        if (!$slackResponse->json('ok')) {
            \Log::error('Slack dmLeadDetails failed', [
                'error' => $slackResponse->json('error'),
                'response' => $slackResponse->json(),
            ]);
        }
    }

    public function getChannels()
    {
        $response = Http::withToken(tenant()->slack_bot_token)->get('https://slack.com/api/conversations.list');
        return $response->json();
    }
}