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

        if (
            $user->email_verification_code === $request->code &&
            $user->email_verification_code_expires_at &&
            now()->lessThanOrEqualTo($user->email_verification_code_expires_at)
        ) {
            $user->markEmailAsVerified();
            $user->email_verification_code = null;
            $user->email_verification_code_expires_at = null;
            $user->save();

            return redirect()->route('verification.success')
                ->with('verified', true)
                ->with('status', __('Your email has been verified!'));
        }

        return back()->withErrors(['code' => 'Invalid or expired code.']);
    }
}