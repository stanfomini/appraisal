<?php
// app/Http/Controllers/IntakeController.php
namespace App\Http\Controllers;

use App\Models\FormResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class IntakeController extends Controller
{
 public function store(Request $request)
 {
 // Log raw input
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
 'interiorCondition-Comment' => 'nullable|string|max:1000', // Match SurveyJS key
 'hasExteriorFeatures' => 'nullable|boolean',
 'exteriorFeatures' => 'nullable|string|max:1000',
 'bodyCondition' => 'nullable|integer|between:1,10',
 'bodyCondition-Comment' => 'nullable|string|max:1000', // Match SurveyJS key
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
 'phoneNumber' => 'required|string|max:20'
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

 // Map data to model attributes
 $formData = [
 'vin' => $data['vin'],
 'year' => $data['year'],
 'make' => $data['make'],
 'model' => $data['model'],
 'fuel_type' => $data['fuelType'],
 'mileage' => $data['mileage'],
 'trim_level' => $data['trimLevel'],
 'color' => $data['color'],
 'motor_size' => $data['motorSize'],
 'intent' => $data['intent'],
 'has_interior_features' => $data['hasInteriorFeatures'],
 'interior_features' => $data['interiorFeatures'] ?? null,
 'interior_condition' => $data['interiorCondition'],
 'interior_condition_comment' => $data['interiorCondition-Comment'] ?? null,
 'has_exterior_features' => $data['hasExteriorFeatures'],
 'exterior_features' => $data['exteriorFeatures'] ?? null,
 'body_condition' => $data['bodyCondition'],
 'body_condition_comment' => $data['bodyCondition-Comment'] ?? null,
 'needs_windshield' => $data['needsWindshield'],
 'needs_tires' => $data['needsTires'],
 'has_warning_lights ' => $data['hasWarningLights'],
 'warning_lights_details' => $data['warningLightsDetails'] ?? null,
 'has_mechanical_issues' => $data['hasMechanicalIssues'],
 'mechanical_issues_details' => $data['mechanicalIssuesDetails'] ?? null,
 'photos' => $photoPaths,
 'full_name' => $data['fullName'],
 'email' => $data['email'],
 'phone_number' => $data['phoneNumber']
 ];

 // Create the form response
 FormResponse::create($formData);

 return redirect()->route('starthere')->with('success', 'Form submitted successfully!');
 }
}