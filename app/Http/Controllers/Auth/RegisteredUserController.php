<?php
// app/Http/Controllers/Auth/RegisteredUserController.php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Illuminate\Support\Str;

class RegisteredUserController extends Controller
{
    public function create()
    {
        return Inertia::render('auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'company' => 'required|string|max:255|unique:tenants,name',
        ]);

        // Create the tenant
        $tenant = Tenant::create([
            'id' => Str::slug($request->company),
            'name' => $request->company,
        ]);

        // Ensure tenant was created successfully
        if (!$tenant || !$tenant->id) {
            throw new \Exception('Failed to create tenant');
        }

        // Create the user with tenant_id
        $user = new User([
            'tenant_id' => $tenant->id,
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);
        $user->save(); // Explicitly save to ensure persistence

        // Verify tenant_id was set
        if (!$user->tenant_id) {
            throw new \Exception('Tenant ID not assigned to user');
        }

        $user->assignRole('manager');

        event(new Registered($user));
        Auth::login($user);

        // Redirect to dashboard with tenant parameter
        return redirect()->route('dashboard', ['tenant' => $tenant->id]);
    }
}