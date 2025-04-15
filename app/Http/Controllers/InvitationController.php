<?php

// app/Http/Controllers/InvitationController.php
namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;

class InvitationController extends Controller
{
    public function store(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $invitation = Invitation::create([
            'tenant_id' => tenant('id'),
            'email' => $request->email,
            'token' => Str::random(32),
        ]);

        // Send invitation email
        Mail::send('emails.invite', ['invitation' => $invitation], function ($m) use ($invitation) {
            $m->to($invitation->email)->subject('You are invited to join ' . tenant('name') . '!');
        });

        return back()->with('success', 'Invitation sent!');
    }

    public function accept($tenant, $token)
    {
        $invitation = Invitation::where('token', $token)->firstOrFail();
        return inertia('Tenant/AcceptInvitation', ['invitation' => $invitation]);
    }

    public function complete(Request $request, $tenant, $token)
    {
        $invitation = Invitation::where('token', $token)->firstOrFail();

        $request->validate([
            'name' => 'required|string',
            'password' => 'required|min:6|confirmed',
        ]);

        $user = User::create([
            'tenant_id' => $invitation->tenant_id,
            'email' => $invitation->email,
            'name' => $request->name,
            'password' => bcrypt($request->password),
        ]);

        $user->assignRole('user');

        $invitation->delete();

        Auth::login($user);

        return redirect()->route('dashboard', ['tenant' => $tenant]);
    }
}
