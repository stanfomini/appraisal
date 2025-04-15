<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleTenant
{
    public function handle($request, Closure $next)
    {
        $currentTenant = tenant('id'); 
        $userTenant = auth()->user()->tenant_id;
    
        if ($currentTenant !== $userTenant) {
            abort(403, 'You do not belong to this tenant.');
        }
    
        return $next($request);
    }
}
