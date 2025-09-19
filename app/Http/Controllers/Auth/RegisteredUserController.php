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
        // First, let's debug what's coming in
        \Log::info('Registration attempt:', $request->all());
        
        $validatedData = $request->validate([
            'name' => 'required|string|max:255', // Keep name if your original had it
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
        ]);

        // Create minimal customer profile
        $user->customer()->create([
            'email' => $validatedData['email'],
            // Mobile will be filled later during profile completion
        ]);

        // Generate and save code
        $code = random_int(100000, 999999);
        $user->email_verification_code = $code;
        $user->email_verification_code_expires_at = now()->addMinutes(10);
        $user->save();

        // Send code via email
        Mail::to($user->email)->send(new \App\Mail\EmailVerificationCode($code));

        Auth::login($user);
        event(new Registered($user));

        return redirect()->route('verification.notice')
               ->with('status', __('A verification code has been sent to your email address.'));
    }
}