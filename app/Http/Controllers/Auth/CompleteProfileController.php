<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class CompleteProfileController extends Controller
{
    public function show(): Response
    {
        $user = Auth::user();
        $customer = $user->customer ?? null;
        
        // Check if profile is already complete
        if ($customer && $customer->is_profile_complete) {
            return redirect()->route('customer.home');
        }
        
        return Inertia::render('Auth/CompleteProfile', [
            'initialValues' => [
                'first_name' => $customer->first_name ?? '',
                'middle_name' => $customer->middle_name ?? '',
                'last_name' => $customer->last_name ?? '',
                'email' => $user->email, 
                'mobile' => $customer->mobile ?? $user->customer->mobile, 
                'phone' => $customer->phone ?? $user->customer->phone,
                'customer_category' => $customer->customer_category ?? 'individual',
                'company_name' => $customer->company_name ?? '',
                'building_number' => $customer->building_number ?? '',
                'street' => $customer->street ?? '',
                'barangay' => $customer->barangay ?? '',
                'city' => $customer->city ?? '',
                'province' => $customer->province ?? '',
                'zip_code' => $customer->zip_code ?? '',
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'mobile' => 'required|string|max:11|regex:/^[0-9]{11}$/',
            'phone' => 'nullable|string|max:9|regex:/^[0-9]{9}$/',
            'customer_category' => 'required|in:individual,company',
            'company_name' => 'nullable|required_if:customer_category,company|string|max:255',
            'building_number' => 'nullable|string|max:50',
            'street' => 'nullable|string|max:255',
            'barangay' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
            'zip_code' => 'nullable|string|max:4|regex:/^[0-9]{4}$/',
        ]);

        $user = Auth::user();
        
        // Update user name
        $user->update([
            'name' => trim("{$validated['first_name']} {$validated['last_name']}"),
        ]);

        // Update or create customer profile and mark as complete
        $user->customer()->updateOrCreate(
            ['user_id' => $user->id],
            array_merge($validated, ['is_profile_complete' => true])
        );

        return redirect()->route('customer.home')
               ->with('success', 'Profile completed successfully!');
    }
}