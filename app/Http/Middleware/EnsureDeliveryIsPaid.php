<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDeliveryIsPaid
{
    public function handle(Request $request, Closure $next): Response
    {
        $delivery = $request->route('delivery');
        
        if ($delivery->payment_method === 'prepaid' && !$delivery->isPaid()) {
            abort(403, 'Prepaid deliveries must be paid before assignment');
        }

        return $next($request);
    }
}