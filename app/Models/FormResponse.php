<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormResponse extends Model
{
    protected $fillable = [
        'vin', 'year', 'make', 'model', 'fuel_type', 'mileage', 'trim_level', 'color', 'motor_size',
        'intent', 'has_interior_features', 'interior_features', 'interior_condition', 'interior_condition_comment',
        'has_exterior_features', 'exterior_features', 'body_condition', 'body_condition_comment',
        'needs_windshield', 'needs_tires', 'has_warning_lights', 'warning_lights_details',
        'has_mechanical_issues', 'mechanical_issues_details', 'photos', 'full_name', 'email', 'phone_number'
    ];

    protected $casts = [
        'has_interior_features' => 'boolean',
        'has_exterior_features' => 'boolean',
        'needs_windshield' => 'boolean',
        'needs_tires' => 'boolean',
        'has_warning_lights' => 'boolean',
        'has_mechanical_issues' => 'boolean',
        'photos' => 'array'
    ];
}
