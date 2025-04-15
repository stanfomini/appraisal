<?php

// app/Models/Tenant.php
namespace App\Models;

use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant
{
    protected $fillable = ['id', 'name', 'domain', 'slack_bot_token'];

    public static function getCustomColumns(): array
    {
        return ['id', 'name', 'slack_bot_token'];
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function formResponses()
    {
        return $this->hasMany(FormResponse::class);
    }

    public function certificates()
    {
        return $this->hasMany(Certificate::class);
    }

    public function notifications()
    {
        return $this->hasMany(CertificateNotification::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
}