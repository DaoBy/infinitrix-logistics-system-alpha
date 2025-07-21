<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\EmployeeProfile;
use App\Models\Region;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\DB;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        // Base query with relationships
        $query = User::with(['employeeProfile' => function($query) {
                $query->select(['id', 'user_id', 'employee_id', 'region_id'])
                    ->with(['region:id,name']);
            }])
            ->whereIn('role', ['admin', 'staff', 'driver', 'collector'])
            ->where('is_active', true);

        // Search filter
        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $query->where(function($q) use ($searchTerm) {
                $q->where('name', 'like', '%'.$searchTerm.'%')
                  ->orWhere('email', 'like', '%'.$searchTerm.'%')
                  ->orWhereHas('employeeProfile', function($q) use ($searchTerm) {
                      $q->where('employee_id', 'like', '%'.$searchTerm.'%')
                        ->orWhereHas('region', function($q) use ($searchTerm) {
                            $q->where('name', 'like', '%'.$searchTerm.'%');
                        });
                  });
            });
        }

        // Role filter
        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }

        // Region filter
        if ($request->filled('region')) {
            $query->whereHas('employeeProfile', function($q) use ($request) {
                $q->where('region_id', $request->input('region'));
            });
        }

        // Sorting
        $sortField = $request->input('sort_field', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');

        // Handle nested sorting (for relations)
        if (str_contains($sortField, '.')) {
            [$relation, $field] = explode('.', $sortField);
            
            if ($relation === 'employee_profile') {
                $query->leftJoin('employee_profiles', 'users.id', '=', 'employee_profiles.user_id')
                     ->orderBy('employee_profiles.'.$field, $sortDirection)
                     ->select('users.*');
            } else {
                $query->with([$relation => function($q) use ($field, $sortDirection) {
                    $q->orderBy($field, $sortDirection);
                }]);
            }
        } else {
            $query->orderBy($sortField, $sortDirection);
        }

        // Pagination
        $employees = $query->paginate(10)->withQueryString();

        // Format the data for the frontend
        $formattedEmployees = $employees->getCollection()->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'is_active' => $user->is_active,
                'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                'employee_profile' => $user->employeeProfile ? [
                    'employee_id' => $user->employeeProfile->employee_id,
                    'region' => $user->employeeProfile->region ? [
                        'id' => $user->employeeProfile->region->id,
                        'name' => $user->employeeProfile->region->name
                    ] : null
                ] : null
            ];
        });

        // Replace the collection with formatted data while keeping pagination
        $employees->setCollection($formattedEmployees);

        return Inertia::render('Admin/Employees/Index', [
            'employees' => $employees,
            'filters' => $request->only(['search', 'role', 'region', 'sort_field', 'sort_direction']),
            'status' => session('status'),
            'success' => session('success'),
            'error' => session('error'),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Employees/Create', [
            'regions' => Region::where('is_active', true)->get(['id', 'name']),
            'status' => session('status'),
        ]);
    }

    protected function generateEmployeeId(): string
    {
        $prefix = 'EMP';
        $numberLength = 4;

        // Get the highest existing number
        $latest = EmployeeProfile::where('employee_id', 'like', "{$prefix}-%")
            ->orderByRaw('CAST(SUBSTR(employee_id, ?) AS INTEGER) DESC', [strlen($prefix) + 2])
            ->first('employee_id');

        $lastNumber = $latest 
            ? (int) substr($latest->employee_id, strlen($prefix) + 1) 
            : 0;

        // Generate next number
        $nextNumber = $lastNumber + 1;

        // Format with leading zeros
        $formattedNumber = str_pad($nextNumber, $numberLength, '0', STR_PAD_LEFT);

        return "{$prefix}-{$formattedNumber}";
    }

   public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => ['required', 'confirmed', Rules\Password::defaults()],
                'role' => 'required|in:admin,staff,driver,collector',
                'phone' => 'nullable|string|max:20',
                'mobile' => 'required|string|max:20|unique:employee_profiles,mobile',
                'building_number' => 'nullable|string|max:50',
                'street' => 'nullable|string|max:100',
                'barangay' => 'nullable|string|max:100',
                'city' => 'nullable|string|max:100',
                'province' => 'nullable|string|max:100',
                'zip_code' => 'nullable|string|max:4',
                'hire_date' => 'nullable|date|before_or_equal:today',
                'region_id' => 'nullable|exists:regions,id',
            ]);

            DB::beginTransaction();

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => $validated['role'],
                'is_active' => true,
            ]);

            // Employee ID will be automatically generated by the EmployeeProfile model's booted method
            $user->employeeProfile()->create([
                'phone' => $validated['phone'] ?? null,
                'mobile' => $validated['mobile'],
                'building_number' => $validated['building_number'] ?? null,
                'street' => $validated['street'] ?? null,
                'barangay' => $validated['barangay'] ?? null,
                'city' => $validated['city'] ?? null,
                'province' => $validated['province'] ?? null,
                'zip_code' => $validated['zip_code'] ?? null,
                'hire_date' => $validated['hire_date'] ?? now(),
                'region_id' => $validated['region_id'] ?? null,
            ]);

            DB::commit();
            
            return redirect()->route('admin.employees.index')
                ->with('success', 'Employee created successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withErrors(['error' => 'Employee creation failed: ' . $e->getMessage()])
                ->withInput();
        }
    }

    public function show($id)
    {
        $employee = User::with(['employeeProfile' => function($query) {
            $query->select([
                'user_id',
                'employee_id',
                'phone',
                'mobile',
                'building_number',
                'street',
                'barangay',
                'city',
                'province',
                'zip_code',
                'hire_date',
                'termination_date',
                'archived_at',
                'notes',
                'region_id'
            ])->with('region:id,name');
        }])
        ->whereIn('role', ['admin', 'staff', 'driver', 'collector'])
        ->findOrFail($id);
    
        return Inertia::render('Admin/Employees/Show', [
            'employee' => $employee->makeVisible('employee_profile'),
            'status' => session('status'),
        ]);
    }

    public function edit(User $employee)
    {
        if (!in_array($employee->role, ['admin', 'staff', 'driver', 'collector'])) {
            abort(404);
        }

        $employee->load(['employeeProfile' => function($query) {
            $query->with('region:id,name');
        }]);

        return Inertia::render('Admin/Employees/Edit', [
            'employee' => $employee,
            'regions' => Region::where('is_active', true)->get(['id', 'name']),
            'status' => session('status'),
        ]);
    }

    public function update(Request $request, User $employee)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$employee->id,
            'role' => 'required|in:admin,staff,driver,collector',
            'phone' => 'nullable|string|max:20',
            'mobile' => 'nullable|string|max:20|unique:employee_profiles,mobile,'.$employee->employeeProfile->id,
            'building_number' => 'nullable|string|max:50',
            'street' => 'nullable|string|max:100',
            'barangay' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|max:4',
            'hire_date' => 'nullable|date',
            'termination_date' => 'nullable|date|after_or_equal:hire_date',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
            'region_id' => 'nullable|exists:regions,id',
        ]);

        DB::beginTransaction();
        try {
        $roleChanged = $employee->role !== $validated['role'];
        $employee->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'is_active' => $validated['is_active'] ?? $employee->is_active,
        ]);
        
        if ($employee->employeeProfile) {
                $profileData = [
                    'phone' => $validated['phone'] ?? null,
                    'mobile' => $validated['mobile'] ?? null,
                    'building_number' => $validated['building_number'] ?? null,
                    'street' => $validated['street'] ?? null,
                    'barangay' => $validated['barangay'] ?? null,
                    'city' => $validated['city'] ?? null,
                    'province' => $validated['province'] ?? null,
                    'zip_code' => $validated['zip_code'] ?? null,
                    'hire_date' => $validated['hire_date'] ?? null,
                    'termination_date' => $validated['termination_date'] ?? null,
                    'region_id' => $validated['region_id'] ?? null,
                    'archived_at' => $validated['termination_date'] ? now() : null,
                    'notes' => $validated['notes'] ?? null,
                ];

                if ($roleChanged) {
                $employee->employeeProfile->updateEmployeeId();
                $employee->employeeProfile->save();
            }
                $employee->employeeProfile()->update($profileData);

            }

                    DB::commit();
                return redirect()->route('admin.employees.index')
                    ->with('success', 'Employee updated successfully!');
            } catch (\Exception $e) {
                DB::rollBack();
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['error' => 'Update failed: ' . $e->getMessage()]);
            }
    }

    public function archive(User $employee)
    {
        if (!in_array($employee->role, ['admin', 'staff', 'driver', 'collector'])) {
            return back()->with('error', 'Only employees can be archived');
        }
    
        DB::transaction(function () use ($employee) {
            $employee->update([
                'is_active' => false,
            ]);
            
            $employee->employeeProfile()->update([
                'archived_at' => now(),
                'termination_date' => now(),
            ]);
        });
    
        return redirect()->back()
            ->with('success', 'Employee archived successfully');
    }

    public function restore(User $employee)
    {
        if (!in_array($employee->role, ['admin', 'staff', 'driver', 'collector'])) {
            return back()->with('error', 'Only employees can be restored');
        }
    
        DB::transaction(function () use ($employee) {
            $employee->update([
                'is_active' => true,
            ]);
            
            $employee->employeeProfile()->update([
                'archived_at' => null,
                'termination_date' => null,
            ]);
        });
    
        return redirect()->back()
            ->with('success', 'Employee restored successfully');
    }



      public function destroy(User $employee)
    {
        if (!in_array($employee->role, ['admin', 'staff', 'driver', 'collector'])) {
            return back()->with('error', 'Only employees can be deleted');
        }

        if ($employee->is_active || is_null($employee->employeeProfile?->archived_at)) {
            return back()->with('error', 'Only archived employees can be permanently deleted');
        }

        DB::beginTransaction();
        try {
            // Delete the employee profile first
            if ($employee->employeeProfile) {
                $employee->employeeProfile->delete();
            }
            
            // Then delete the user
            $employee->delete();
            
            DB::commit();
            return redirect()->route('admin.employees.archived')
                   ->with('success', 'Employee permanently deleted');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withErrors(['error' => 'Deletion failed: ' . $e->getMessage()]);
        }
    }

 public function archived(Request $request)
{
    $query = User::with(['employeeProfile' => function($query) {
            $query->select(['id', 'user_id', 'employee_id', 'region_id', 'archived_at'])
                  ->with(['region:id,name']);
        }])
        ->whereIn('role', ['admin', 'staff', 'driver', 'collector'])
        ->where('is_active', false)
        ->whereHas('employeeProfile', function($query) {
            $query->whereNotNull('archived_at');
        });

    // Search filter
    if ($request->filled('search')) {
        $searchTerm = $request->input('search');
        $query->where(function($q) use ($searchTerm) {
            $q->where('name', 'like', '%'.$searchTerm.'%')
              ->orWhere('email', 'like', '%'.$searchTerm.'%')
              ->orWhereHas('employeeProfile', function($q) use ($searchTerm) {
                  $q->where('employee_id', 'like', '%'.$searchTerm.'%')
                    ->orWhereHas('region', function($q) use ($searchTerm) {
                        $q->where('name', 'like', '%'.$searchTerm.'%');
                    });
              });
        });
    }

    // Role filter
    if ($request->filled('role')) {
        $query->where('role', $request->input('role'));
    }

    // Region filter
    if ($request->filled('region')) {
        $query->whereHas('employeeProfile', function($q) use ($request) {
            $q->where('region_id', $request->input('region'));
        });
    }

    // Sorting
    $sortField = $request->input('sort_field', 'employee_profile.archived_at');
    $sortDirection = $request->input('sort_direction', 'desc');

    // Handle nested sorting
    if (str_contains($sortField, '.')) {
        [$relation, $field] = explode('.', $sortField);

        if ($relation === 'employee_profile') {
            $query->leftJoin('employee_profiles', 'users.id', '=', 'employee_profiles.user_id')
                  ->orderBy("employee_profiles.$field", $sortDirection)
                  ->select('users.*');
        } else {
            $query->with([$relation => function($q) use ($field, $sortDirection) {
                $q->orderBy($field, $sortDirection);
            }]);
        }
    } else {
        $query->orderBy($sortField, $sortDirection);
    }

    $employees = $query->paginate(10)->withQueryString();

    // Format data for DataTable
    $formattedEmployees = $employees->getCollection()->map(function ($user) {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'is_active' => $user->is_active,
            'created_at' => $user->created_at->format('Y-m-d H:i:s'),
            'employee_profile' => $user->employeeProfile ? [
                'employee_id' => $user->employeeProfile->employee_id ?? 'N/A',
                'archived_at' => $user->employeeProfile->archived_at 
                    ? \Carbon\Carbon::parse($user->employeeProfile->archived_at)->format('Y-m-d') 
                    : 'Not archived',
                'region' => $user->employeeProfile->region ? [
                    'id' => $user->employeeProfile->region->id,
                    'name' => $user->employeeProfile->region->name
                ] : null
            ] : null
        ];
    });

    $employees->setCollection($formattedEmployees);

    return Inertia::render('Admin/Employees/Archived', [
        'employees' => $employees,
        'filters' => $request->only(['search', 'role', 'region', 'sort_field', 'sort_direction']),
        'status' => session('status'),
        'success' => session('success'),
        'error' => session('error'),
    ]);
}

}