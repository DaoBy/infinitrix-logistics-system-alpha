<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Auth\{
    AuthenticatedSessionController,
    ConfirmablePasswordController,
    EmailVerificationNotificationController,
    EmailVerificationPromptController,
    NewPasswordController,
    PasswordController,
    PasswordResetLinkController,
    RegisteredUserController,
    VerifyEmailController,
    CompleteProfileController,
};
use App\Http\Controllers\Auth\VerifyCodeController;

// ─────────────────────────────────────────
//  Guest Routes (Login, Register, Forgot Password)
// ─────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('register', [RegisteredUserController::class, 'create'])->name('customer.register');
    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

// ─────────────────────────────────────────
//  Email Verification Routes
// ─────────────────────────────────────────
Route::middleware(['auth'])->group(function () {
    Route::get('/verify-email', [EmailVerificationPromptController::class, '__invoke'])->name('verification.notice');
    Route::post('/verify-code', [VerifyCodeController::class, 'verify'])->name('verification.code.verify');
    Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])->name('verification.send');

    Route::get('email/verify/{id}/{hash}', [VerifyEmailController::class, '__invoke'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::get('email/verify/success', fn () => Inertia::render('Auth/VerifyEmailSuccess'))
        ->middleware('verified')
        ->name('verification.success');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])->name('password.confirm');
    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');
});

// ─────────────────────────────────────────
//  Profile Completion (After verification)
// ─────────────────────────────────────────
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/complete-profile', [CompleteProfileController::class, 'show'])->name('profile.complete');
    Route::post('/complete-profile', [CompleteProfileController::class, 'store']);
});

// ─────────────────────────────────────────
//  Logout
// ─────────────────────────────────────────
Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
