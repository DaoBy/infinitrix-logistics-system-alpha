<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Customer;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the customer registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming customer registration request.
     */
       public function store(Request $request): RedirectResponse
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:users,email',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Create the user with customer role
        $user = User::create([
            'name' => $validatedData['name'], 
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
            'role' => 'customer',
            'is_active' => true,
            'email_verified_at' => null, // Ensure email is not verified
        ]);

        // Create minimal customer profile
        $user->customer()->create([
            'email' => $validatedData['email'],
        ]);

        // Generate and save code
        $code = mt_rand(100000, 999999);
        $user->email_verification_code = $code;
        $user->email_verification_code_expires_at = now()->addMinutes(10);
        $user->save();

        // Send verification email
        Mail::to($user->email)->send(new \App\Mail\EmailVerificationCode($code));

        // Login the user FIRST
        Auth::login($user);
        
        // Fire the registered event AFTER login
        event(new Registered($user));

        // Then redirect to verification notice
        return redirect()->route('verification.notice')
               ->with('status', __('A verification code has been sent to your email address.'));
    }
}