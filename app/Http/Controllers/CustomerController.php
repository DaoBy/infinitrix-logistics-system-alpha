<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\User;
use App\Models\DeliveryRequest;
use App\Models\DeliveryOrder; 
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::query()
            ->with(['user', 'sentDeliveries', 'receivedDeliveries'])
            ->withCount(['sentDeliveries', 'receivedDeliveries'])
            ->whereHas('user', function ($query) {
                $query->where('is_active', true);
            });

        // Search filter
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('mobile', 'like', "%{$search}%");
            });
        }

        // Category filter
        if ($request->has('customer_category') && $request->customer_category) {
            $query->where('customer_category', $request->customer_category);
        }

        // Frequency filter
        if ($request->has('frequency_type') && $request->frequency_type) {
            $query->where('frequency_type', $request->frequency_type);
        }

        // Sorting
        $sortField = $request->get('sort_field', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        
        if (in_array($sortField, ['name', 'email', 'customer_category', 'frequency_type', 'created_at'])) {
            $query->orderBy($sortField, $sortDirection);
        }

        // Pagination
        $customers = $query->paginate(10)->through(function ($customer) {
            return [
                'id' => $customer->id,
                'first_name' => $customer->first_name,
                'last_name' => $customer->last_name,
                'company_name' => $customer->company_name,
                'email' => $customer->email,
                'mobile' => $customer->mobile,
                'customer_category' => $customer->customer_category,
                'frequency_type' => $customer->frequency_type,
                'total_orders' => $customer->sent_deliveries_count + $customer->received_deliveries_count,
                'created_at' => $customer->created_at->format('Y-m-d H:i:s'),
                'user_id' => $customer->user_id,
                'is_active' => $customer->user?->is_active,
            ];
        });

        return Inertia::render('Admin/Customers/Index', [
            'customers' => $customers,
            'filters' => $request->only(['search', 'customer_category', 'frequency_type']),
            'sort_field' => $sortField,
            'sort_direction' => $sortDirection,
            'status' => session('status'),
            'success' => session('success'),
            'error' => session('error'),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Customers/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
           'customer_category' => 'required|in:individual,company',
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'company_name' => 'nullable|required_if:customer_category,company|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'mobile' => 'required|string|max:20',
            'phone' => 'nullable|string|max:20',
            'building_number' => 'nullable|string|max:50',
            'street' => 'nullable|string|max:255',
            'barangay' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
            'zip_code' => 'nullable|string|max:4',
            'frequency_type' => 'required|in:regular,occasional',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $password = Str::random(12);
            
            $userName = $validated['customer_category'] === 'company' 
                ? $validated['company_name'] 
                : trim($validated['first_name'] . ' ' . $validated['last_name']);

            $user = User::create([
                'name' => $userName,
                'email' => $validated['email'],
                'password' => Hash::make($password),
                'role' => 'customer',
                'is_active' => true,
            ]);

            $user->customer()->create($validated);

            DB::commit();
            return redirect()->route('admin.customers.index')
                ->with('success', 'Customer created successfully! Password: ' . $password);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withErrors(['error' => 'Customer creation failed: ' . $e->getMessage()])
                ->withInput();
        }
    }

    public function show(Customer $customer)
    {
        $customer->load([
            'sentDeliveries' => fn($q) => $q->with('packages')->latest()->take(5),
            'receivedDeliveries' => fn($q) => $q->with('packages')->latest()->take(5),
            'user'
        ]);

        // Add these queries for the tables
        $deliveryRequests = $customer->sentDeliveries()
            ->with(['receiver', 'pickUpRegion', 'dropOffRegion']) // updated
            ->latest()
            ->paginate(5);

        $transactions = DeliveryOrder::whereHas('deliveryRequest', function($q) use ($customer) {
                $q->where('sender_id', $customer->id);
            })
            ->with(['deliveryRequest.receiver'])
            ->latest()
            ->paginate(5);

        return Inertia::render('Admin/Customers/Show', [
            'customer' => $customer,
            'recent_sent_deliveries' => $customer->sentDeliveries,
            'recent_received_deliveries' => $customer->receivedDeliveries,
            'deliveryRequests' => $deliveryRequests, // Add this
            'transactions' => $transactions, // Add this
        ]);
    }

    public function edit(Customer $customer)
    {
        return Inertia::render('Admin/Customers/Edit', [
            'customer' => $customer
        ]);
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
              'customer_category' => 'required|in:individual,company',
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'company_name' => 'nullable|required_if:customer_category,company|string|max:255',
            'email' => 'nullable|email|max:255',
            'mobile' => 'required|string|max:20',
            'phone' => 'nullable|string|max:20',
            'building_number' => 'nullable|string|max:50',
            'street' => 'nullable|string|max:255',
            'barangay' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
            'zip_code' => 'nullable|string|max:4',
            'frequency_type' => 'required|in:regular,occasional',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);
    
        DB::beginTransaction();
        try {
            $customer->update($validated);
    
            if ($customer->user) {
                $userName = $validated['customer_category'] === 'company'
                    ? $validated['company_name']
                    : trim($validated['first_name'] . ' ' . $validated['last_name']);
    
                $customer->user->update([
                    'email' => $validated['email'] ?? $customer->user->email,
                    'name' => $userName,
                    'is_active' => $validated['is_active'] ?? $customer->user->is_active,
                ]);
            }
    
            DB::commit();
            return redirect()->route('admin.customers.index')
                ->with('success', 'Customer updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withErrors(['error' => 'Update failed: ' . $e->getMessage()])
                ->withInput();
        }
    }

    public function archive(Customer $customer)
    {
        DB::transaction(function () use ($customer) {
            if ($customer->user) {
                $customer->user->update(['is_active' => false]);
            }
            $customer->update(['archived_at' => now()]);
        });
    
        return redirect()->back()
            ->with('success', 'Customer archived successfully');
    }

    public function restore(Customer $customer)
    {
        DB::transaction(function () use ($customer) {
            if ($customer->user) {
                $customer->user->update(['is_active' => true]);
            }
            $customer->update(['archived_at' => null]);
        });
    
        return redirect()->back()
            ->with('success', 'Customer restored successfully');
    }

    public function archived(Request $request)
    {
        $query = Customer::query()
            ->whereNotNull('archived_at')
            ->orWhereHas('user', fn($q) => $q->where('is_active', false))
            ->with(['user', 'sentDeliveries', 'receivedDeliveries'])
            ->withCount(['sentDeliveries', 'receivedDeliveries']);

        if ($request->has('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('company_name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%")
                  ->orWhere('mobile', 'like', "%{$request->search}%");
            });
        }

        if ($request->has('customer_category')) {
            $query->where('customer_category', $request->customer_category);
        }

        if ($request->has('frequency_type')) {
            $query->where('frequency_type', $request->frequency_type);
        }

        $sortField = $request->get('sort_field', 'archived_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        
        if (in_array($sortField, ['name', 'email', 'customer_category', 'frequency_type', 'archived_at'])) {
            $query->orderBy($sortField, $sortDirection);
        }

        $customers = $query->paginate(10)->through(fn($customer) => [
            'id' => $customer->id,
            'name' => $customer->name,
            'company_name' => $customer->company_name,
            'email' => $customer->email,
            'mobile' => $customer->mobile,
            'customer_category' => $customer->customer_category,
            'frequency_type' => $customer->frequency_type,
            'total_orders' => $customer->sent_deliveries_count + $customer->received_deliveries_count,
            'created_at' => $customer->created_at->format('Y-m-d H:i:s'),
            'archived_at' => $customer->archived_at?->format('Y-m-d H:i:s'),
            'user_id' => $customer->user_id,
            'is_active' => $customer->user?->is_active,
        ]);

        return Inertia::render('Admin/Customers/Archived', [
            'customers' => $customers,
            'filters' => $request->only(['search', 'customer_category', 'frequency_type']),
            'sort_field' => $sortField,
            'sort_direction' => $sortDirection,
        ]);
    }
    
    public function destroy(Customer $customer)
    {
        if ($customer->is_active || is_null($customer->archived_at)) {
            return back()->with('error', 'Only archived customers can be deleted');
        }

        if ($customer->sentDeliveries()->exists() || $customer->receivedDeliveries()->exists()) {
            return back()->with('error', 'Cannot delete customer with delivery records');
        }

        DB::beginTransaction();
        try {
            $customer->delete();
            
            if ($customer->user) {
                $customer->user->delete();
            }
            
            DB::commit();
            return redirect()->route('admin.customers.archived')
                   ->with('success', 'Customer permanently deleted');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withErrors(['error' => 'Deletion failed: ' . $e->getMessage()]);
        }
    }
}