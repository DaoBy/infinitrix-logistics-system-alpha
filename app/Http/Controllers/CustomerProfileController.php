<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerUpdateRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CustomerProfileController extends Controller
{
    public function create()
    {
        $customer = Auth::user()->customer;
        
        return inertia('Customer/ProfileUpdate/Create', [
            'customer' => $customer,
        ]);
    }

    public function store(Request $request)
    {
        $customer = Auth::user()->customer;
        
        $validated = $request->validate([
            'first_name' => ['nullable', 'string', 'max:255', 'different:current_first_name'],
            'middle_name' => ['nullable', 'string', 'max:255', 'different:current_middle_name'],
            'last_name' => ['nullable', 'string', 'max:255', 'different:current_last_name'],
            'email' => ['nullable', 'email', 'max:255', 'different:current_email'],
            'mobile' => ['nullable', 'string', 'max:20', 'different:current_mobile'],
            'phone' => ['nullable', 'string', 'max:20', 'different:current_phone'],
            'building_number' => ['nullable', 'string', 'max:50', 'different:current_building_number'],
            'street' => ['nullable', 'string', 'max:255', 'different:current_street'],
            'barangay' => ['nullable', 'string', 'max:255', 'different:current_barangay'],
            'city' => ['nullable', 'string', 'max:255', 'different:current_city'],
            'province' => ['nullable', 'string', 'max:255', 'different:current_province'],
            'zip_code' => ['nullable', 'string', 'max:4', 'different:current_zip_code'],
            'reason' => ['required', 'string', 'min:10'],
        ]);

        // Check if at least one field is different
        $hasChanges = false;
        $profileFields = [
            'first_name', 'middle_name', 'last_name', 'email', 'mobile', 'phone',
            'building_number', 'street', 'barangay', 'city', 'province', 'zip_code'
        ];

        foreach ($profileFields as $field) {
            if ($request->has($field) && $request->$field !== $customer->$field) {
                $hasChanges = true;
                break;
            }
        }

        if (!$hasChanges) {
            return back()->withErrors(['error' => 'At least one field must be different from your current profile.']);
        }

        // Create update request
        $updateRequest = new CustomerUpdateRequest();
        $updateRequest->customer_id = $customer->id;
        $updateRequest->reason = $validated['reason'];
        $updateRequest->status = 'pending';

        // Only set fields that are different
        foreach ($profileFields as $field) {
            if ($request->has($field) && $request->$field !== $customer->$field) {
                $updateRequest->$field = $request->$field;
            }
        }

        $updateRequest->save();

        return redirect()->route('customer.home')
            ->with('success', 'Profile update request submitted successfully. It will be reviewed by our staff.');
    }
}