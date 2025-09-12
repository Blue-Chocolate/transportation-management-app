<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnforceCompanyAccess
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();

            // Only allow company admins with a valid company_id
            if ($user->role !== 'admin' || !$user->company_id) {
                abort(403, 'Access denied: Company admin only.');
            }

            // Store the current company_id in the session
            session(['current_company_id' => $user->company_id]);
        } else {
            abort(403, 'Unauthorized access.');
        }

        return $next($request);
    }
}