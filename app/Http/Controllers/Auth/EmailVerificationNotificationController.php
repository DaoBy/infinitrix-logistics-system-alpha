<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification code.
     */
    public function store(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('customer.home'));
        }

        // Generate new code
        $code = random_int(100000, 999999);
        $user = $request->user();
        $user->email_verification_code = $code;
        $user->email_verification_code_expires_at = now()->addMinutes(10);
        $user->save();

        Mail::to($user->email)->send(new \App\Mail\EmailVerificationCode($code));

        return back()->with('status', __('A new verification code has been sent to your email address.'));
    }
}
