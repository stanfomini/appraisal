<?php
// app/Http/Controllers/IntakeController.php
namespace App\Http\Controllers;

use App\Models\FormResponse;
use App\Models\Certificate;
use App\Models\User;
use App\Notifications\FormSubmittedNotification as FormSubmittedNotification;
use App\Notifications\AppraisalCertificateNotification as AppraisalCertificateNotification;
use Illuminate\Support\Facades\Notification as FacadeNotification;
use App\Events\FormSubmitted;
use App\Events\StageUpdated;
use App\Events\FormAppraised;
use App\Events\LeadAssigned;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use PDF;
use App\Services\SlackService;
use App\Events\FormResponseUpdated;

class IntakeController extends Controller
{
    public function store(Request $request)
    {
        // Log raw input
        \Log::info('Current tenant ID:', [tenant('id')]);
        \Log::info('Raw form data:', $request->all());

        // Validate the request
        $data = $request->validate([
            'vin' => 'required|string|size:17',
            'year' => 'nullable|string|max:4',
            'make' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'fuelType' => 'nullable|string|in:gas,diesel,hybrid,electric',
            'mileage' => 'nullable|string|max:255',
            'trimLevel' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:255',
            'motorSize' => 'nullable|string|max:255',
            'intent' => 'nullable|string|in:selling,trading,both',
            'hasInteriorFeatures' => 'nullable|boolean',
            'interiorFeatures' => 'nullable|string|max:1000',
            'interiorCondition' => 'nullable|integer|between:1,10',
            'interiorCondition-Comment' => 'nullable|string|max:1000',
            'hasExteriorFeatures' => 'nullable|boolean',
            'exteriorFeatures' => 'nullable|string|max:1000',
            'bodyCondition' => 'nullable|integer|between:1,10',
            'bodyCondition-Comment' => 'nullable|string|max:1000',
            'needsWindshield' => 'nullable|boolean',
            'needsTires' => 'nullable|boolean',
            'hasWarningLights' => 'nullable|boolean',
            'warningLightsDetails' => 'nullable|string|max:1000',
            'hasMechanicalIssues' => 'nullable|boolean',
            'mechanicalIssuesDetails' => 'nullable|string|max:1000',
            'photos' => 'nullable|array',
            'photos.*.content' => 'required_with:photos|string',
            'photos.*.name' => 'required_with:photos|string|max:255',
            'fullName' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phoneNumber' => 'required|string|max:20',
            'tenant_id' => tenant('id'),
        ]);

        // Handle file uploads
        $photoPaths = [];
        if (!empty($data['photos'])) {
            foreach ($data['photos'] as $photo) {
                $fileData = base64_decode(preg_replace('#^data:[^;]+;base64,#i', '', $photo['content']));
                $fileName = 'photos/' . uniqid() . '_' . $photo['name'];
                Storage::disk('public')->put($fileName, $fileData);
                $photoPaths[] = $fileName;
            }
        }
       
        $tenantId = tenant('id');
        
        // Map data to model attributes
        $formData = [
            'tenant_id' => $tenantId ?? null,
            'vin' => $data['vin'],
            'year' => $data['year'],
            'make' => $data['make'],
            'model' => $data['model'],
            'fuel_type' => $data['fuelType'] ?? null,
            'mileage' => $data['mileage'] ?? null,
            'trim_level' => $data['trimLevel'] ?? null,
            'color' => $data['color'] ?? null,
            'motor_size' => $data['motorSize'] ?? null,
            'intent' => $data['intent'] ?? null,
            'has_interior_features' => $data['hasInteriorFeatures'] ?? null,
            'interior_features' => $data['interiorFeatures'] ?? null,
            'interior_condition' => $data['interiorCondition'] ?? null,
            'interior_condition_comment' => $data['interiorCondition-Comment'] ?? null,
            'has_exterior_features' => $data['hasExteriorFeatures'] ?? null,
            'exterior_features' => $data['exteriorFeatures'] ?? null,
            'body_condition' => $data['bodyCondition'] ?? null,
            'body_condition_comment' => $data['bodyCondition-Comment'] ?? null,
            'needs_windshield' => $data['needsWindshield'] ?? null,
            'needs_tires' => $data['needsTires'] ?? null,
            'has_warning_lights' => $data['hasWarningLights'] ?? null,
            'warning_lights_details' => $data['warningLightsDetails'] ?? null,
            'has_mechanical_issues' => $data['hasMechanicalIssues'] ?? null,
            'mechanical_issues_details' => $data['mechanicalIssuesDetails'] ?? null,
            'photos' => $photoPaths ?? null,
            'full_name' => $data['fullName'],
            'email' => $data['email'],
            'phone_number' => $data['phoneNumber'],
            'stage' => 'New Lead',
        ];

        // Create the form response
        $formResponse = FormResponse::create($formData);

        // Notify all users with the "manager" role in the tenant
        $managers = User::where('tenant_id', $formResponse->tenant_id)
            ->whereHas('roles', function ($query) {
                $query->where('name', 'manager');
            })
            ->get();
        foreach ($managers as $manager) {
            $manager->notify(new FormSubmittedNotification($formResponse));
        }

        // Broadcast the form submission
        event(new LeadAssigned($formResponse));

        return redirect()->route('starthere', ['tenant' => tenant('id')])->with('success', 'Form submitted successfully!');
    }

    public function dashboard(Request $request) 
    {

        $stages = ['New Lead', 'Appraisal Completed', 'Lead Generated'];


        return Inertia::render('Tenant/Dashboard', [
            'stages' => $stages,
            'tenantName' => tenant('name'),
            'slack_bot_token' => tenant('slack_bot_token'),
        ]);
    }

    public function kanban(Request $request)
    {
        $stages = ['New Lead', 'Appraisal Completed', 'Lead Generated'];

        $responses = FormResponse::where('tenant_id', tenant('id'))
            ->get()
            ->groupBy('stage')
            ->mapWithKeys(function ($group, $stage) use ($stages) {
                return [$stage => $group->map(fn($r) => [
                    'id' => $r->id,
                    'tenant_id' => $r->tenant_id,
                    'vin' => $r->vin,
                    'year' => $r->year,
                    'make' => $r->make,
                    'model' => $r->model,
                    'full_name' => $r->full_name,
                    'email' => $r->email,
                    'phone_number' => $r->phone_number,
                    'fuelType' => $r->fuel_type,
                    'mileage' => $r->mileage,
                    'intent' => $r->intent,
                    'photos' => $r->photos,
                    'hasInteriorFeatures' => $r->has_interior_features,
                    'interiorFeatures' => $r->interior_features,
                    'interiorCondition' => $r->interior_condition,
                    'interiorConditionComment' => $r->interior_condition_comment,
                    'hasExteriorFeatures' => $r->has_exterior_features,
                    'exteriorFeatures' => $r->exterior_features,
                    'bodyCondition' => $r->body_condition,
                    'bodyConditionComment' => $r->body_condition_comment,
                    'needsWindshield' => $r->needs_windshield,
                    'needsTires' => $r->needs_tires,
                    'hasWarningLights' => $r->has_warning_lights,
                    'warningLightsDetails' => $r->warning_lights_details,
                    'hasMechanicalIssues' => $r->has_mechanical_issues,
                    'mechanicalIssuesDetails' => $r->mechanical_issues_details,
                    'appraisal_value' => $r->appraisal_value,
                    'appraised_at' => $r->appraised_at,
                    'appraised_by' => $r->appraised_by,
                    'certificate' => $r->certificates->last(), // Get the latest certificate
                ])->all()];
            })->all();

        foreach ($stages as $stage) {
            if (!isset($responses[$stage])) {
                $responses[$stage] = [];
            }
        }
       


          // e.g., an array of role names ["manager", "admin"]
   // $userRoles = $request->user()->getRoleNames(); 
    // Or if you only care about "isManager":
    $isManager = $request->user()->hasRole('manager');

        return Inertia::render('Tenant/DealerApp', [
            'stages' => $stages,
            'blocks' => $responses,
            'tenantName' => tenant('name'),
            'auth' => [
            'user' => $request->user(),
            'slack_bot_token' => tenant('slack_bot-token'), 
             'isManager' => $isManager,
        ],
        ]);
    }

    public function updateStage(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:form_responses,id',
            'stage' => 'required|in:New Lead,Appraisal Completed,Lead Generated',
        ]);

        $response = FormResponse::where('tenant_id', tenant('id'))->findOrFail($request->id);
        $response->update(['stage' => $request->stage]);
      //  if ($request->stage === 'Appraisal Completed') {
       //     $slackService = new SlackService();
       //     $slackService->sendLeadAcceptanceMessage($response);
      //  }

      event(new LeadAssigned($response));

        return response()->json(['success' => true]);
    }

    public function broadcastAppraisal(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:form_responses,id',
            'appraisal_value' => 'nullable|numeric|min:0',
        ]);
    
        $formResponse = FormResponse::where('tenant_id', tenant('id'))->findOrFail($request->id);
        broadcast(new LeadAssigned($formResponse, auth()->id(), $request->appraisal_value))->toOthers();
    
        return response()->json(['success' => true]);
    }

public function appraise(Request $request)
{
    $request->validate([
        'id' => 'required|exists:form_responses,id',
        'appraisal_value' => 'required|numeric|min:0',
    ]);

    $response = FormResponse::where('tenant_id', tenant('id'))->findOrFail($request->id);
    $response->update([
        'appraisal_value' => $request->appraisal_value,
        'appraised_at' => now(),
        'appraised_by' => auth()->id(),
    ]);

    $certificate = Certificate::create([
        'tenant_id' => $response->tenant_id,
        'form_response_id' => $response->id,
        'path' => null,
        'expiry' => now()->addDays(config('app.certificate_expiry_days')),
    ]);

    $pdf = PDF::loadView('pdf.certificate', ['formResponse' => $response, 'certificate' => $certificate]);
    $uniqueName = uniqid() . '_certificate_' . $response->id . '.pdf';
    $filePath = 'certificates/' . $uniqueName;
    \Storage::disk('public')->put($filePath, $pdf->output());
    $certificate->update(['path' => $filePath]);

    broadcast(new LeadAssigned($response))->toOthers();

    return response()->json([
        'success' => true,
        'formResponse' => $response->fresh()->load('certificate'),
    ]);
}

    public function downloadCertificate(Request $request, $certificateId)
    {
        $certificate = Certificate::where('tenant_id', tenant('id'))->findOrFail($certificateId);
        $formResponse = $certificate->formResponse;

        // Check authorization (e.g., only the appraiser can download)
        if (auth()->id() !== $formResponse->appraised_by) {
            abort(403, 'Unauthorized action.');
        }

        $path = storage_path('/app/public/' . $certificate->path);
        if (!file_exists($path)) {
            abort(404, 'Certificate not found.');
        }

        return response()->download($path, 'certificate.pdf');
    }
    public function showCertificate(Request $request, $certificateId)
{
    // 1) Find the certificate for this tenant
    $certificate = Certificate::where('tenant_id', tenant('id'))->findOrFail($certificateId);
    $formResponse = $certificate->formResponse;

    // 2) Check if the logged-in user can view it
    //    If you want the same rule as "downloadCertificate" (only the appraiser can see),
    //    uncomment the code below:
    // if (auth()->id() !== $formResponse->appraised_by) {
    //     abort(403, 'Unauthorized action.');
    // }

    // 3) Return a Blade view. We can reuse "pdf.certificate" or use a dedicated view like "certificates.show"
    return view('pdf.certificate', [
        'formResponse' => $formResponse,
        'certificate'  => $certificate,
    ]);
}

public function acceptLead(Request $request, $id)
{
    // 1. Find the form response
    $formResponse = FormResponse::findOrFail($id);
    
    // 2. Assign the logged-in user
    $formResponse->update([
        'appraised_by' => auth()->id(),
    ]);

    // 3. Broadcast an event so others see the update in real-time
    broadcast(new LeadAssigned($formResponse))->toOthers();

    return response()->json([
        'message' => 'Lead accepted',
        'data' => $formResponse,
        'appraised_by' => auth()->id(), // Include user ID
        'user_name' => auth()->user()->name, // Include user name
    ]);
}

public function show(Request $request, $id)
{
    $formResponse = FormResponse::where('tenant_id', tenant('id'))->findOrFail($id);
    return response()->json($formResponse);
}

/**
 * Post the lead to a Slack channel.
 *
 * Example usage: user picks a Slack channel from a dropdown -> calls this endpoint with channel ID.
 */


}