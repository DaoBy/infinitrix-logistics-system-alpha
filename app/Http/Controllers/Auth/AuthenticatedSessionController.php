<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
   public function store(LoginRequest $request): RedirectResponse
{
    $request->authenticate();
    $user = Auth::user();

    // First, check if user is active (for all roles)
    if (!$user->is_active) {
        Auth::logout();
        return redirect()->route('login')
            ->with('error', 'Your account is inactive. Please contact administrator.');
    }

    // For CUSTOMERS only, check verification and profile completion
    if ($user->isCustomer()) {
        // 1. Check if email is NOT verified
        if (!$user->hasVerifiedEmail()) {
            // Keep user logged in but redirect to verification
            return redirect()->route('verification.notice')
                ->with('error', __('You must verify your email address before accessing your account.'));
        }

        // 2. If verified, check if profile is incomplete
        if (!$user->customer || !$user->customer->is_profile_complete) {
            return redirect()->route('profile.complete')
                ->with('show_modal', true)
                ->with('warning', 'Please complete your profile to access all features.');
        }
    }

    // For employees (admin, staff, etc.) - no verification/profile checks needed
    $request->session()->regenerate();

    // Role-specific redirect logic
    return match ($user->role) {
        'customer'  => redirect()->intended(route('customer.home')),
        'admin'     => redirect()->intended(route('admin.dashboard')),
        'staff'     => redirect()->intended(route('staff.dashboard')),
        'driver'    => redirect()->intended(route('driver.dashboard')),
        'collector' => redirect()->intended(route('collector.payments.dashboard')),
        default     => redirect()->route('login'),
    };
}

    /**
     * Destroy an authenticated session (Logout).
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::user(); // Capture user before logout
    
        Auth::guard('web')->logout();
    
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // ✅ Prevents errors if user is null
        if (!$user) {
            return redirect('/'); // Fallback
        }
    
        // ✅ Redirect based on role after logout
        return match ($user->role) {
            'customer'  => redirect('/'),
            'admin', 'staff', 'driver', 'collector' => redirect()->route('login'),
            default     => redirect('/'), // Fallback for unknown roles
        };
    }

    public function canLogin(): bool
    {
        return $this->isActive() && (
            $this->isEmployee() || $this->hasVerifiedEmail()
        );
    }
}