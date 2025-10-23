<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmailVerificationPromptController extends Controller
{
    /**
     * Display the email verification prompt.
     */
  public function __invoke(Request $request): RedirectResponse|Response
{
    $user = $request->user();
    
    if ($user->hasVerifiedEmail()) {
        // If verified, check if customer needs to complete profile
        if ($user->isCustomer() && (!$user->customer || !$user->customer->is_profile_complete)) {
            return redirect()->route('profile.complete');
        }
        
        // Otherwise go to intended page
        return redirect()->intended(route('customer.home', absolute: false));
    }

    return Inertia::render('Auth/VerifyCode', ['status' => session('status')]);
}
}
