<?php

// app/Models/Notification.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CertificateNotification extends Model
{
    protected $fillable = [
        'tenant_id', 'notifiable_type', 'notifiable_id', 'type', 'data', 'sent_at', 'status',
    ];

    protected $casts = [
        'data' => 'array',
        'sent_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
