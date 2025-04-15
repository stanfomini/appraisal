<?php

// app/Http/Controllers/Auth/AuthenticatedSessionController.php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    public function create()
    {
        return inertia('auth/Login');
    }

    public function store(LoginRequest $request)
    {
        $request->authenticate();
        $request->session()->regenerate();

        $tenant = Auth::user()->tenant;
        if (!$tenant) {
            \Log::error('No tenant for user', ['user_id' => Auth::id()]);
            throw new \Exception('User has no tenant');
        }
        return redirect()->intended(route('dashboard', ['tenant' => $tenant->id]));
    }

    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}
