<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfileIsComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        if ($user && $user->isCustomer()) {
            $customer = $user->customer;
            
            if (!$customer || !$customer->first_name || !$customer->last_name || !$customer->mobile) {
                return redirect()->route('profile.complete')
                 ->with('info', 'Please complete your profile to continue');
            }
        }

        return $next($request);
    }
}