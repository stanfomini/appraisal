<?php

// app/Models/Certificate.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certificate extends Model
{
    protected $fillable = [
        'tenant_id', 'form_response_id', 'path', 'expiry',
    ];

    protected $casts = [
        'expiry' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function formResponse(): BelongsTo
    {
        return $this->belongsTo(FormResponse::class);
    }
}
