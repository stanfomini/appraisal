<?php

// app/Models/Comment.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comment extends Model
{
    protected $fillable = [
        'tenant_id', 'form_response_id', 'user_id', 'comment',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function formResponse(): BelongsTo
    {
        return $this->belongsTo(FormResponse::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
