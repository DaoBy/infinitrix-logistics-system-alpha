<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfileComplete
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        
        // Check if user is a customer and profile is not complete
        if ($user && $user->isCustomer() && (!$user->customer || !$user->customer->is_profile_complete)) {
            // Allow access to profile completion and logout routes
           if (!$request->is('complete-profile') && !$request->is('logout')) {
                return redirect()->route('profile.complete')
                    ->with('warning', 'Please complete your profile to access all features.');
            }

        }

        return $next($request);
    }
}