<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VerifyCodeController extends Controller
{
   public function verify(Request $request)
{
    $request->validate(['code' => 'required|digits:6']);

    $user = Auth::user();

    // Check if this is for standard email verification
    if (
        $user->email_verification_code === $request->code &&
        $user->email_verification_code_expires_at &&
        now()->lessThanOrEqualTo($user->email_verification_code_expires_at)
    ) {
        $hasPendingEmailChange = !empty($user->pending_email);
        
        // If there's a pending email change, handle it
        if ($hasPendingEmailChange) {
            $user->email = $user->pending_email;
            $user->pending_email = null;
        }
        
        // Mark email as verified
        $user->markEmailAsVerified();
        $user->email_verification_code = null;
        $user->email_verification_code_expires_at = null;
        $user->save();

        // For email changes, redirect back to profile with success message
        if ($hasPendingEmailChange) {
            return redirect()->route('profile.edit')
                ->with('status', __('Email successfully changed and verified!'));
        }

        // For new registrations - check if profile needs completion
        if ($user->isCustomer() && (!$user->customer || !$user->customer->is_profile_complete)) {
            return redirect()->route('profile.complete')
                ->with('success', 'Email verified! Please complete your profile.');
        }

        // For verified users with complete profile
        return redirect()->route('customer.home')
            ->with('verified', true)
            ->with('status', __('Your email has been verified!'));
    }

    return back()->withErrors(['code' => 'Invalid or expired code.']);
}
}