<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormResponse extends Model
{
    protected $fillable = [
        'vin', 'year', 'make', 'model', 'fuel_type', 'mileage', 'trim_level', 'color', 'motor_size',
        'intent', 'has_interior_features', 'interior_features', 'interior_condition', 'interior_condition_comment',
        'has_exterior_features', 'exterior_features', 'body_condition', 'body_condition_comment',
        'needs_windshield', 'needs_tires', 'has_warning_lights', 'warning_lights_details',
        'has_mechanical_issues', 'mechanical_issues_details', 'photos', 'full_name', 'email', 'phone_number', 'stage', 'appraisal_value', 'appraised_at', 'appraised_by', 'tenant_id',   'assigned_slack_user_id',
        'assigned_slack_channel','slack_message_ts', 'slack_username',
    ];

    protected $casts = [
        'has_interior_features' => 'boolean',
        'has_exterior_features' => 'boolean',
        'needs_windshield' => 'boolean',
        'needs_tires' => 'boolean',
        'has_warning_lights' => 'boolean',
        'has_mechanical_issues' => 'boolean',
        'photos' => 'array',
        'appraised_at' => 'datetime',
    ];

    protected $attributes = [
        'stage' => 'submitted' // Default value
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function appraiser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'appraised_by');
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function certificate()
{
    return $this->hasOne(Certificate::class, 'form_response_id');
}
  
}
