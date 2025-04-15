<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FormResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Tenant;
use App\Services\SlackServices;
use App\Events\LeadAssigned;

class SlackController extends Controller
{
    public function redirectToSlack(Request $request)
    {
        $tenantId = $request->query('tenant');
        $tenant = Tenant::where('id', $tenantId)->firstOrFail();
    
        $redirectUri = env('SLACK_REDIRECT_URI');
        $url = 'https://slack.com/oauth/v2/authorize?' . http_build_query([
            'client_id' => env('SLACK_CLIENT_ID'),
            'scope' => 'chat:write,users:read,im:write,channels:read,groups:read,im:read,mpim:read,usergroups:read,team:read,channels:read',
            'redirect_uri' => $redirectUri,
            'state' => $tenant->id, // This is what Slack sends back to your callback
        ]);
    
        return redirect()->away($url);
    }
    

    public function handleCallback(Request $request)
    {
        $tenantId = $request->input('state');
        $tenant = Tenant::where('id', $tenantId)->firstOrFail();
    
        $code = $request->input('code');
        $redirectUri = env('SLACK_REDIRECT_URI');
    
        Log::info('OAuth Callback Received', [
            'code' => $code,
            'state' => $tenantId,
            'request' => $request->all(),
            'redirect_uri' => $redirectUri,
        ]);
    
        $response = Http::asForm()->post('https://slack.com/api/oauth.v2.access', [
            'client_id'     => env('SLACK_CLIENT_ID'),
            'client_secret' => env('SLACK_CLIENT_SECRET'),
            'code'          => $code,
            'redirect_uri'  => $redirectUri,
        ]);
        
        $responseData = $response->json();
        Log::info('Slack OAuth Response', ['response' => $responseData]);
    
        if (!$response->successful() || !isset($responseData['access_token'])) {
            $error = $responseData['error'] ?? 'Unknown error';
            Log::error('Slack OAuth Failed', ['error' => $error]);
            return redirect("/{$tenant->id}/dashboard")->with('error', "Slack connection failed: $error");
        }
    
        $token = $responseData['access_token'];
        $tenant->update(['slack_bot_token' => $token]);
    
        return redirect()->route('dashboard', ['tenant' => $tenant->id])
            ->with('message', 'Slack connected!');
    }

    // public function handleAction(Request $request)
    // {
    //   Log::info('Slack Action Request Received', [
    //     'headers' => $request->headers->all(),
    //     'body' => $request->all(),
    //   ]);
    
    //   $payload = json_decode($request->input('payload'), true);
    //   if (!$payload) {
    //     Log::error('Failed to parse Slack payload', ['body' => $request->input()]);
    //     return response()->json(['text' => 'Invalid payload'], 400);
    //   }
    
    //   Log::info('Slack Action Payload Parsed', ['payload' => $payload]);
    
    //   $leadId = $payload['actions'][0]['value'] ?? null;
    //   $slackUserId = $payload['user']['id'] ?? null;
    //   $slackUsername = $payload['user']['username'] ?? 'Unknown';
    
    //   if (!$leadId || !$slackUserId) {
    //     Log::error('Invalid Slack Action Payload', ['payload' => $payload]);
    //     return response()->json(['text' => 'Invalid request'], 400);
    //   }
    
    //   $formResponse = FormResponse::findOrFail($leadId);
    
    //   // Get the tenant from the FormResponse's tenant_id (assuming it exists)
    //   $tenant = Tenant::where('id', $formResponse->tenant_id)->first();
    //   if (!$tenant || !$tenant->slack_bot_token) {
    //     Log::error('No tenant or Slack bot token found for form response', [
    //       'lead_id' => $leadId,
    //       'tenant_id' => $formResponse->tenant_id,
    //     ]);
    //     return response()->json(['text' => 'No Slack bot token available'], 500);
    //   }
    
    //   // Update with the clicking user's info every time
    //   $formResponse->update([
    //     'assigned_slack_user_id' => $slackUserId,
    //     'slack_username' => $slackUsername,
    //   ]);
    
    //   // Pass the tenant explicitly to SlackService
    //   $slackService = new \App\Services\SlackService($tenant);
    
    //   try {
    //     $slackService->updateLeadMessage($formResponse, $slackUserId, $slackUsername);
    //     $slackService->dmLeadDetails($slackUserId, $formResponse);
    //   } catch (\Exception $e) {
    //     Log::error('Slack Action Processing Failed', [
    //       'error' => $e->getMessage(),
    //       'lead_id' => $leadId,
    //       'user_id' => $slackUserId,
    //     ]);
    //     return response()->json(['text' => 'Error processing action'], 500);
    //   }
    
    //   // Keep the assignment log for tracking
    //   Log::info('Lead assigned or updated', [
    //     'lead_id' => $leadId,
    //     'assigned_to' => $slackUserId,
    //   ]);
    //   broadcast(new LeadAssigned($formResponse))->toOthers();
    
    //   return response()->json([]);
    // }
    public function handleAction(Request $request)
{
    Log::info('Slack Action Request Received', [
        'headers' => $request->headers->all(),
        'body' => $request->all(),
    ]);

    $payload = json_decode($request->input('payload'), true);
    if (!$payload) {
        Log::error('Failed to parse Slack payload', ['body' => $request->input()]);
        return response()->json(['text' => 'Invalid payload'], 400);
    }

    Log::info('Slack Action Payload Parsed', ['payload' => $payload]);

    $leadId = $payload['actions'][0]['value'] ?? null;
    $slackUserId = $payload['user']['id'] ?? null;

    if (!$leadId || !$slackUserId) {
        Log::error('Invalid Slack Action Payload', ['payload' => $payload]);
        return response()->json(['text' => 'Invalid request'], 400);
    }

    $formResponse = FormResponse::findOrFail($leadId);

    // Get the tenant from the FormResponse's tenant_id (assuming it exists)
    $tenant = Tenant::where('id', $formResponse->tenant_id)->first();
    if (!$tenant || !$tenant->slack_bot_token) {
        Log::error('No tenant or Slack bot token found for form response', [
            'lead_id' => $leadId,
            'tenant_id' => $formResponse->tenant_id,
        ]);
        return response()->json(['text' => 'No Slack bot token available'], 500);
    }

    // Fetch display name instead of using payload username
    $slackResponse = Http::withToken($tenant->slack_bot_token)
        ->get('https://slack.com/api/users.info', ['user' => $slackUserId]);

    $slackUsername = 'Unknown';
    if ($slackResponse->json('ok')) {
        $user = $slackResponse->json('user');
        $slackUsername = $user['profile']['display_name'] ?: $user['real_name'] ?: 'Unknown';
    }

    // Update with the clicking user's info every time
    $formResponse->update([
        'assigned_slack_user_id' => $slackUserId,
        'slack_username' => $slackUsername,
    ]);

    // Pass the tenant explicitly to SlackService
    $slackService = new \App\Services\SlackService($tenant);

    try {
        $slackService->updateLeadMessage($formResponse, $slackUserId, $slackUsername);
        $slackService->dmLeadDetails($slackUserId, $formResponse);
    } catch (\Exception $e) {
        Log::error('Slack Action Processing Failed', [
            'error' => $e->getMessage(),
            'lead_id' => $leadId,
            'user_id' => $slackUserId,
        ]);
        return response()->json(['text' => 'Error processing action'], 500);
    }

    // Keep the assignment log for tracking
    Log::info('Lead assigned or updated', [
        'lead_id' => $leadId,
        'assigned_to' => $slackUserId,
    ]);
    broadcast(new LeadAssigned($formResponse))->toOthers();

    return response()->json([]);
}
    public function listChannels(Request $request)
    {
        $tenantSlugOrId = $request->query('tenant'); 
        if (!$tenantSlugOrId) {
            return response()->json(['error' => 'No tenant parameter provided'], 400);
        }

        $tenant = Tenant::where('id', $tenantSlugOrId)->first();
        if (!$tenant) {
            return response()->json([
                'error' => "Tenant '$tenantSlugOrId' not found"
            ], 404);
        }

        $slackToken = $tenant->slack_bot_token;
        if (!$slackToken) {
            return response()->json(['error' => 'No Slack bot token for this tenant'], 400);
        }

        $slackResponse = Http::withToken($slackToken)
            ->get('https://slack.com/api/conversations.list', [
                'types' => 'public_channel,private_channel',
                'limit' => 100,
            ]);

        if (!$slackResponse->json('ok')) {
            return response()->json([
                'error' => $slackResponse->json('error') ?? 'Unknown Slack error'
            ], 400);
        }

        return response()->json([
            'channels' => $slackResponse->json('channels'),
        ]);
    }

    public function listChannelMembers(Request $request)
{
  $tenantSlugOrId = $request->query('tenant');
  if (!$tenantSlugOrId) {
    return response()->json(['error' => 'No tenant parameter provided'], 400);
  }

  $tenant = Tenant::where('id', $tenantSlugOrId)->first();
  if (!$tenant) {
    return response()->json(['error' => "Tenant '$tenantSlugOrId' not found"], 404);
  }

  $slackToken = $tenant->slack_bot_token;
  if (!$slackToken) {
    return response()->json(['error' => 'No Slack bot token for this tenant'], 400);
  }

  $channelId = $request->query('channel');
  if (!$channelId) {
    return response()->json(['error' => 'No channel parameter provided'], 400);
  }

  $slackResponse = Http::withToken($slackToken)
    ->get('https://slack.com/api/conversations.members', [
      'channel' => $channelId,
    ]);

  if (!$slackResponse->json('ok')) {
    return response()->json(['error' => $slackResponse->json('error') ?? 'Unknown Slack error'], 400);
  }

  $memberIds = $slackResponse->json('members', []);
  $members = [];

  foreach ($memberIds as $memberId) {
    $userResponse = Http::withToken($slackToken)
      ->get('https://slack.com/api/users.info', [
        'user' => $memberId,
      ]);
    if ($userResponse->json('ok')) {
      $user = $userResponse->json('user');
      $members[] = [
        'id' => $memberId,
        'name' => $user['profile']['display_name'] ?: $user['real_name'] ?: 'Unknown', // Prefer display_name, fallback to real_name
      ];
    }
  }

  return response()->json(['members' => $members]);
}

    public function listUsers(Request $request)
    {
        $tenantSlugOrId = $request->query('tenant');
        if (!$tenantSlugOrId) {
            return response()->json(['error' => 'No tenant parameter provided'], 400);
        }

        $tenant = Tenant::where('id', $tenantSlugOrId)->first();
        // or Tenant::find($tenantSlugOrId);
        if (!$tenant) {
            return response()->json([
                'error' => "Tenant '$tenantSlugOrId' not found"
            ], 404);
        }

        $slackToken = $tenant->slack_bot_token;
        if (!$slackToken) {
            return response()->json(['error' => 'No Slack bot token for this tenant'], 400);
        }

        $slackResponse = Http::withToken($slackToken)
            ->get('https://slack.com/api/users.list');

        if (!$slackResponse->json('ok')) {
            return response()->json([
                'error' => $slackResponse->json('error') ?? 'Unknown Slack error'
            ], 400);
        }

        return response()->json([
            'users' => $slackResponse->json('members'),
        ]);
    }
        public function resetToken(Request $request)
        {
            // If needed, identify the tenant. For example:
            // $tenantId = $request->input('tenant_id');
            // $tenant = Tenant::findOrFail($tenantId);
    
            // Or if using tenant() from Stancl:
            $tenant = tenant(); // Or however you retrieve the current tenant
    
            $tenant->update(['slack_bot_token' => null]);
    
            Log::info('Slack bot token reset for tenant ID: '.$tenant->id);
    
            return response()->json(['message' => 'Slack bot token reset.']);
        }

        public function broadcastToSlackChannel(Request $request, $id)
    {
        $formResponse = FormResponse::findOrFail($id);

        $request->validate([
            'channel_id' => 'required|string',
        ]);

        $channelId = $request->input('channel_id');
        $slackService = new \App\Services\SlackService();

        $slackService->postLeadToChannel($formResponse, $channelId);

        broadcast(new LeadAssigned($formResponse))->toOthers();

        return response()->json([
            'message' => 'Lead posted to Slack channel',
            'data' => $formResponse->fresh(),
        ]);
    }

    public function dmUser(Request $request, $id)
{
    $formResponse = FormResponse::findOrFail($id);

    $request->validate(['slack_user_id' => 'required|string']);
    $slackUserId = $request->input('slack_user_id');

    // Fetch tenant and Slack token
    $tenant = Tenant::find($formResponse->tenant_id);
    if (!$tenant || !$tenant->slack_bot_token) {
        return response()->json(['error' => 'No Slack bot token'], 400);
    }

    // Get user's display name from Slack
    $slackResponse = Http::withToken($tenant->slack_bot_token)
        ->get('https://slack.com/api/users.info', ['user' => $slackUserId]);
    
    $slackUsername = 'Unknown';
    if ($slackResponse->json('ok')) {
        $user = $slackResponse->json('user');
        $slackUsername = $user['profile']['display_name'] ?: $user['real_name'] ?: 'Unknown';
    }

    // Update formResponse with assignment details
    $formResponse->update([
        'assigned_slack_user_id' => $slackUserId,
        'slack_username' => $slackUsername,
    ]);

    // Send DM and broadcast event
    $slackService = new \App\Services\SlackService(); // Ensure SlackService uses tenant's token if needed
    $slackService->dmLeadDetails($slackUserId, $formResponse);
    broadcast(new LeadAssigned($formResponse))->toOthers();

    return response()->json(['message' => 'DM sent to Slack user']);
}

    // public function dmUser(Request $request, $id)
    // {
    //     $formResponse = FormResponse::findOrFail($id);

    //     $request->validate([
    //         'slack_user_id' => 'required|string',
    //     ]);

    //     $slackUserId = $request->input('slack_user_id');
    //     $slackService = new \App\Services\SlackService();

    //     $slackService->dmLeadDetails($slackUserId, $formResponse);

    //     broadcast(new LeadAssigned($formResponse))->toOthers();

    //     return response()->json(['message' => 'DM sent to Slack user']);
    // }

    }


