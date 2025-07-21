<?php

// app/Http/Middleware/CheckPostpaidEligibility.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckPostpaidEligibility
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->payment_type === 'postpaid' && 
            !$request->user()->customer->isEligibleForPostpaid()) {
            return redirect()->back()
                ->with('error', 'You need at least 3 completed deliveries to use postpaid');
        }

        return $next($request);
    }
}
