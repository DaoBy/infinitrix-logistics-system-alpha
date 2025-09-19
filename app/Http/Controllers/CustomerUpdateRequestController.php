<?php

namespace App\Http\Controllers;

use App\Models\CustomerUpdateRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CustomerUpdateRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = CustomerUpdateRequest::with(['customer', 'reviewer'])
            ->where('status', 'pending')
            ->latest();

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->whereHas('customer', function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%");
            });
        }

        $requests = $query->paginate(10);

        return inertia('Admin/CustomerUpdateRequests/Index', [
            'requests' => $requests,
            'filters' => $request->only(['search']),
        ]);
    }

    public function show(CustomerUpdateRequest $customerUpdateRequest)
    {
        $customerUpdateRequest->load(['customer', 'reviewer']);

        return inertia('Admin/CustomerUpdateRequests/Show', [
            'request' => $customerUpdateRequest,
            'customer' => $customerUpdateRequest->customer,
        ]);
    }

    public function approve(Request $request, CustomerUpdateRequest $customerUpdateRequest)
    {
        if ($customerUpdateRequest->status !== 'pending') {
            return back()->with('error', 'This request has already been processed.');
        }

        DB::transaction(function () use ($customerUpdateRequest) {
            // Update customer profile with the changed fields
            $customer = $customerUpdateRequest->customer;
            $changedFields = $customerUpdateRequest->getChangedFields();

            foreach ($changedFields as $field) {
                $customer->$field = $customerUpdateRequest->$field;
            }

            $customer->save();

            // Update the request status
            $customerUpdateRequest->status = 'approved';
            $customerUpdateRequest->reviewed_by = Auth::id();
            $customerUpdateRequest->reviewed_at = now();
            $customerUpdateRequest->save();
        });

        return redirect()->route('admin.customer-update-requests.index')
            ->with('success', 'Profile update request approved successfully.');
    }

    public function reject(Request $request, CustomerUpdateRequest $customerUpdateRequest)
    {
        $request->validate([
            'rejection_reason' => 'required|string|min:10',
        ]);

        if ($customerUpdateRequest->status !== 'pending') {
            return back()->with('error', 'This request has already been processed.');
        }

        $customerUpdateRequest->status = 'rejected';
        $customerUpdateRequest->reviewed_by = Auth::id();
        $customerUpdateRequest->reviewed_at = now();
        $customerUpdateRequest->reason = $request->rejection_reason;
        $customerUpdateRequest->save();

        return redirect()->route('admin.customer-update-requests.index')
            ->with('success', 'Profile update request rejected successfully.');
    }
}