<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnforceCompanyAccess
{
    public function handle(Request $request, Closure $next)
    {
        // Skip this check if user is not logged in
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        // Here we already know the user passed canAccessPanel()
        // Just store company_id in session for convenience
        session(['current_company_id' => $user->company_id]);

        return $next($request);
    }
}
