<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerUpdateRequest;
use App\Models\CustomerProfileAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CustomerProfileController extends Controller
{
    public function create()
    {
        $customer = Auth::user()->customer;
        
        // Ensure delivery status is up to date
        $customer->checkAndUpdateDeliveryStatus();
        
        $hasDeliveryHistory = $customer->hasDeliveryHistory();
        $canRequestChanges = $customer->canRequestChanges();
        $activeCount = $customer->getActiveDeliveriesCount();
        $unpaidCount = $customer->getUnpaidDeliveriesCount();
        
        // FIXED: lockedFields should only contain critical fields if there are active/unpaid deliveries
        $lockedFields = !$canRequestChanges ? Customer::CRITICAL_FIELDS : [];
        
        // ADD THIS: Get the latest pending request for this customer
        $existingRequest = CustomerUpdateRequest::where('customer_id', $customer->id)
            ->whereIn('status', ['pending', 'rejected', 'approved'])
            ->latest()
            ->first();

        return inertia('Customer/ProfileUpdate/Create', [
            'customer' => $customer,
            'lockedFields' => $lockedFields, // This should be empty for completed/paid customers
            'editableFields' => $customer->getEditableFields(),
            'hasDeliveryHistory' => $hasDeliveryHistory,
            'canRequestChanges' => $canRequestChanges,
            'activeDeliveriesCount' => $activeCount,
            'unpaidDeliveriesCount' => $unpaidCount,
            'existingRequest' => $existingRequest, // Add this
        ]);
    }

    public function store(Request $request)
    {
        $customer = Auth::user()->customer;
        $customer->checkAndUpdateDeliveryStatus(); // Ensure current status
        
        $hasDeliveryHistory = $customer->hasDeliveryHistory();
        $canRequestChanges = $customer->canRequestChanges();
        
        // ADD THIS: Check for existing pending request
        $existingPendingRequest = CustomerUpdateRequest::where('customer_id', $customer->id)
            ->where('status', 'pending')
            ->first();
            
        if ($existingPendingRequest) {
            return back()->withErrors([
                'error' => 'You already have a pending update request. Please wait for it to be processed before submitting a new one.'
            ]);
        }
        
        // If customer has active/unpaid deliveries, prevent any changes
        if (!$canRequestChanges) {
            return back()->withErrors([
                'error' => 'You cannot update your delivery information while you have active deliveries or unpaid payments. Please complete all deliveries and payments first.'
            ]);
        }
        
        $validationRules = [];
        $profileFields = [
            'first_name', 'middle_name', 'last_name', 
            'mobile', 'phone',
            'building_number', 'street', 'barangay', 'city', 'province', 'zip_code'
        ];

        // Only validate fields that are actually present in the request
        foreach ($profileFields as $field) {
            if ($request->has($field)) {
                $validationRules[$field] = ['nullable', 'string', 'max:255'];
                
                if ($field === 'mobile' || $field === 'phone') {
                    $validationRules[$field][] = 'max:20';
                }
                
                if ($field === 'zip_code') {
                    $validationRules[$field][] = 'max:4';
                }
            }
        }

        // Reason is required if there are locked field changes (has delivery history)
        if ($hasDeliveryHistory) {
            $validationRules['reason'] = ['required', 'string', 'min:10'];
        } else {
            $validationRules['reason'] = ['nullable', 'string', 'min:10'];
        }

        $validated = $request->validate($validationRules);

        // Check for actual changes - only consider fields that were submitted
        $hasChanges = false;
        $changes = [];
        $hasLockedFieldChanges = false;

        foreach ($profileFields as $field) {
            // Only check fields that were actually submitted in the request
            if ($request->has($field)) {
                $newValue = $request->$field;
                $currentValue = $customer->$field;
                
                // Check if the value actually changed
                if ($newValue != $currentValue) {
                    $hasChanges = true;
                    $changes[$field] = [
                        'old' => $currentValue,
                        'new' => $newValue
                    ];
                    
                    // Check if this is a locked field change
                    if ($hasDeliveryHistory && $customer->isFieldLocked($field)) {
                        $hasLockedFieldChanges = true;
                    }
                }
            }
        }

        if (!$hasChanges && empty($request->reason)) {
            return back()->withErrors(['error' => 'No changes detected. Please modify at least one field or provide a reason.']);
        }

        // If no delivery history, update directly (first-time customer)
        if (!$hasDeliveryHistory) {
            return $this->updateDirectly($customer, $changes, $validated['reason'] ?? 'Direct profile update');
        }

        // If has delivery history, create update request (requires approval)
        return $this->createUpdateRequest($customer, $changes, $validated['reason']);
    }

    private function updateDirectly(Customer $customer, array $changes, string $reason): \Illuminate\Http\RedirectResponse
    {
        // Update only the changed fields in customer profile
        foreach ($changes as $field => $change) {
            $customer->$field = $change['new'];
        }

        $customer->save();

        // Log the changes
        if (!empty($changes)) {
            CustomerProfileAudit::logChange(
                $customer,
                Auth::id(),
                CustomerProfileAudit::CHANGE_TYPE_CUSTOMER_UPDATE,
                $changes,
                $reason
            );
        }

        return redirect()->route('customer.home') // Make sure this matches your customer dashboard route
    ->with('success', 'Profile updated successfully. Changes have been logged for security purposes.');
    }

    private function createUpdateRequest(Customer $customer, array $changes, string $reason): \Illuminate\Http\RedirectResponse
    {
        // Create update request for all changed fields (requires approval)
        $updateRequest = new CustomerUpdateRequest();
        $updateRequest->customer_id = $customer->id;
        $updateRequest->reason = $reason;
        $updateRequest->status = 'pending';

        // Set all changed fields in the update request
        foreach ($changes as $field => $change) {
            $updateRequest->$field = $change['new'];
        }

        $updateRequest->save();

        // Log the update request
        CustomerProfileAudit::logChange(
            $customer,
            Auth::id(),
            CustomerProfileAudit::CHANGE_TYPE_APPROVED_REQUEST,
            $changes,
            "Update request submitted: " . $reason
        );

        return redirect()->route('customer.home') // Make sure this matches your customer dashboard route
    ->with('success', 'Profile update request submitted successfully. Our team will review your request and you will be notified once it is processed.');
    }   
}