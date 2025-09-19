<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    /**
     * Update the user's profile information with email verification.
     */
  public function updateWithVerification(Request $request)
{
    $user = $request->user();
    
    $validated = $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
    ]);
    
    // If email is changing
    if ($user->email !== $validated['email']) {
        // Store pending email
        $user->pending_email = $validated['email'];
        
        // Generate verification code for the NEW email
        $user->email_verification_code = random_int(100000, 999999);
        $user->email_verification_code_expires_at = now()->addMinutes(10);
        $user->save();
        
        // Send verification email to the NEW address
        Mail::to($validated['email'])->send(new \App\Mail\EmailVerificationCode($user->email_verification_code));
        
        return back()->with('status', 'verification-code-sent');
    }
    
    // Regular update if email isn't changing
    $user->fill($validated);
    $user->save();
    
    return back()->with('status', 'profile-updated');
}

    /**
     * Verify the email change with the provided code.
     */
   public function verifyEmailChange(Request $request)
{
    $request->validate(['code' => 'required|digits:6']);
    
    $user = $request->user();
    
    // Use the same verification logic as the standard email verification
    if (
        $user->email_verification_code === $request->code &&
        $user->email_verification_code_expires_at &&
        now()->lessThanOrEqualTo($user->email_verification_code_expires_at) &&
        $user->pending_email // Only allow if there's a pending email change
    ) {
        // Update email and mark as verified
        $user->email = $user->pending_email;
        $user->email_verified_at = now(); // Mark as verified immediately
        $user->pending_email = null;
        $user->email_verification_code = null;
        $user->email_verification_code_expires_at = null;
        $user->save();
        
        return back()->with('status', 'email-verified-and-changed');
    }
    
    return back()->withErrors(['code' => 'Invalid or expired code.']);
}
}
