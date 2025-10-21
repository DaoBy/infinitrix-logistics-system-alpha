<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerUpdateRequest;
use App\Models\CustomerProfileAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CustomerUpdateRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = CustomerUpdateRequest::with(['customer', 'reviewer'])
            ->latest();

        // Filter by status if provided
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        } else {
            // Default to pending if no status filter
            $query->where('status', 'pending');
        }

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

        // Get stats for all requests
        $stats = [
            'pending' => CustomerUpdateRequest::where('status', 'pending')->count(),
            'approved' => CustomerUpdateRequest::where('status', 'approved')->count(),
            'rejected' => CustomerUpdateRequest::where('status', 'rejected')->count(),
            'total' => CustomerUpdateRequest::count(),
        ];

        return inertia('Admin/CustomerUpdateRequests/Index', [
            'requests' => $requests,
            'filters' => $request->only(['search', 'status', 'sort_field', 'sort_direction']),
            'stats' => $stats,
        ]);
    }

    public function show(CustomerUpdateRequest $customerUpdateRequest)
    {
        $customerUpdateRequest->load(['customer', 'reviewer']);

        return inertia('Admin/CustomerUpdateRequests/Show', [
            'request' => $customerUpdateRequest,
            'customer' => $customerUpdateRequest->customer,
            'lockedFields' => $customerUpdateRequest->customer->areCriticalFieldsLocked() ? 
                Customer::CRITICAL_FIELDS : [],
        ]);
    }

    public function approve(Request $request, CustomerUpdateRequest $customerUpdateRequest)
{
    if ($customerUpdateRequest->status !== 'pending') {
        return back()->with('error', 'This request has already been processed.');
    }

    // Check if there are actual profile changes
    if (!$customerUpdateRequest->hasProfileChanges()) { // CHANGED: hasProfileChanges()
        return back()->with('error', 'This update request has no profile changes to apply.');
    }

    DB::transaction(function () use ($customerUpdateRequest, $request) {
        // Update customer profile with the changed fields
        $customer = $customerUpdateRequest->customer;
        $changedFields = $customerUpdateRequest->getChangedFields();

        $changes = [];
        foreach ($changedFields as $field) {
            $changes[$field] = [
                'old' => $customer->$field,
                'new' => $customerUpdateRequest->$field
            ];
            $customer->$field = $customerUpdateRequest->$field;
        }

        $customer->save();

        // Log the approved changes
        CustomerProfileAudit::logChange(
            $customer,
            Auth::id(),
            CustomerProfileAudit::CHANGE_TYPE_APPROVED_REQUEST,
            $changes,
            $customerUpdateRequest->reason
        );

        // Update the request status
        $customerUpdateRequest->status = 'approved';
        $customerUpdateRequest->reviewed_by = Auth::id();
        $customerUpdateRequest->reviewed_at = now();
        $customerUpdateRequest->admin_notes = $request->admin_notes;
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

        // FIXED: Use admin_notes instead of overwriting original reason
        $customerUpdateRequest->status = 'rejected';
        $customerUpdateRequest->reviewed_by = Auth::id();
        $customerUpdateRequest->reviewed_at = now();
        $customerUpdateRequest->admin_notes = $request->rejection_reason; // Store in admin_notes
        $customerUpdateRequest->save();

        return redirect()->route('admin.customer-update-requests.index')
            ->with('success', 'Profile update request rejected successfully.');
    }

    public function unlockFields(Request $request, Customer $customer)
    {
        $request->validate([
            'reason' => 'required|string|min:10',
        ]);

        $customer->unlockCriticalFields();

        // Log the unlock action
        CustomerProfileAudit::logChange(
            $customer,
            Auth::id(),
            CustomerProfileAudit::CHANGE_TYPE_ADMIN_UPDATE,
            ['critical_fields_locked' => ['old' => true, 'new' => false]],
            $request->reason
        );

        return back()->with('success', 'Critical fields unlocked successfully.');
    }

    public function lockFields(Request $request, Customer $customer)
    {
        $request->validate([
            'reason' => 'required|string|min:10',
        ]);

        $customer->lockCriticalFields();

        // Log the lock action
        CustomerProfileAudit::logChange(
            $customer,
            Auth::id(),
            CustomerProfileAudit::CHANGE_TYPE_ADMIN_UPDATE,
            ['critical_fields_locked' => ['old' => false, 'new' => true]],
            $request->reason
        );

        return back()->with('success', 'Critical fields locked successfully.');
    }

    public function auditLogs(Request $request)
    {
        $query = CustomerProfileAudit::with(['customer', 'changedBy'])
            ->latest();

        // Search filters
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->whereHas('customer', function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%");
            });
        }

        if ($request->has('change_type') && $request->change_type) {
            $query->where('change_type', $request->change_type);
        }

        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $auditLogs = $query->paginate(10);

        return inertia('Admin/CustomerAuditLogs/Index', [
            'auditLogs' => $auditLogs,
            'filters' => $request->only(['search', 'change_type', 'date_from', 'date_to']),
            'changeTypes' => [
                CustomerProfileAudit::CHANGE_TYPE_CUSTOMER_UPDATE,
                CustomerProfileAudit::CHANGE_TYPE_ADMIN_UPDATE,
                CustomerProfileAudit::CHANGE_TYPE_AUTO_LOCKED,
                CustomerProfileAudit::CHANGE_TYPE_APPROVED_REQUEST,
            ],
        ]);
    }

    public function customerAuditLogs(Request $request, Customer $customer)
    {
        $query = $customer->profileAuditLogs()
            ->with(['changedBy'])
            ->latest();

        if ($request->has('change_type') && $request->change_type) {
            $query->where('change_type', $request->change_type);
        }

        $auditLogs = $query->paginate(6);

        return inertia('Admin/CustomerAuditLogs/Show', [
            'customer' => $customer,
            'auditLogs' => $auditLogs,
            'filters' => $request->only(['change_type']),
            'changeTypes' => [
                CustomerProfileAudit::CHANGE_TYPE_CUSTOMER_UPDATE,
                CustomerProfileAudit::CHANGE_TYPE_ADMIN_UPDATE,
                CustomerProfileAudit::CHANGE_TYPE_AUTO_LOCKED,
                CustomerProfileAudit::CHANGE_TYPE_APPROVED_REQUEST,
            ],
        ]);
    }
}