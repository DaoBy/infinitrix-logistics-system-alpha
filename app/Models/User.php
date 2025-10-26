<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'current_region_id',
        'last_region_update',
        'pending_email',
        'email_change_verification_code',
        'email_change_verification_code_expires_at',
        'last_assigned_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'email_verified_at' => 'datetime',
        'last_region_update' => 'datetime',
        'last_assigned_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($user) {
            // Use forceDelete() if using soft deletes
            $user->customer?->delete();
            $user->employeeProfile?->delete();
        });
    }

    public function region()
{
    return $this->hasOneThrough(
        Region::class,
        EmployeeProfile::class,
        'user_id', // Foreign key on EmployeeProfile table
        'id', // Foreign key on Region table  
        'id', // Local key on User table
        'region_id' // Local key on EmployeeProfile table
    );
}
    protected static function booted()
    {
        static::retrieved(function ($user) {
            if ($user->role === 'driver' && !$user->current_region_id) {
                // Try to get region from active assignment
                $assignment = $user->truckAssignments()
                    ->where('is_active', true)
                    ->latest()
                    ->first();

                if ($assignment) {
                    $user->current_region_id = $assignment->region_id;
                    $user->save();
                }
            }
            
            // Process automatic transitions for driver assignments on retrieval
            if ($user->role === 'driver') {
                $assignment = $user->currentTruckAssignment;
                if ($assignment) {
                    $assignment->processAutomaticTransitions();
                }
            }
        });
    }

    // Add to relationships
    public function truckAssignments()
    {
        return $this->hasMany(DriverTruckAssignment::class, 'driver_id');
    }

    public function currentTruckAssignment()
{
    return $this->hasOne(DriverTruckAssignment::class, 'driver_id')
        ->where('is_active', true)
        ->notDeleted();
}

    // NEW: Get driver's current assignment with status
    public function getCurrentAssignmentAttribute()
    {
        return $this->currentTruckAssignment;
    }

    // NEW: Check if driver is available for backhaul (automated)
    public function isAvailableForBackhaul(): bool
    {
        $assignment = $this->currentTruckAssignment;
        return $assignment && $assignment->isEligibleForBackhaul();
    }

    // NEW: Check if driver is in cooldown period
    public function isInCooldown(): bool
    {
        $assignment = $this->currentTruckAssignment;
        return $assignment && $assignment->current_status === \App\Models\DriverTruckAssignment::STATUS_COOLDOWN;
    }

    // NEW: Check if driver is eligible for backhaul (all packages delivered and verified)
    public function isEligibleForBackhaul(): bool
    {
        $assignment = $this->currentTruckAssignment;
        if (!$assignment) return false;
        
        // Process automatic transitions first
        $assignment->processAutomaticTransitions();
        
        return $assignment->current_status === \App\Models\DriverTruckAssignment::STATUS_BACKHAUL_ELIGIBLE;
    }

    // Add to methods
    public function canAcceptNewAssignment(): bool
    {
        // Implement your logic for max assignments, e.g.:
        $maxAssignments = $this->max_assignments ?? 60;
        $currentAssignments = $this->deliveryOrders()
            ->whereIn('status', ['assigned', 'dispatched', 'in_transit'])
            ->count();
        return $currentAssignments < $maxAssignments;
    }

    public function isAvailable(): bool
    {
        // A driver is available if active and under max assignments, regardless of current assignments
        return $this->isActive() && $this->canAcceptNewAssignment();
    }

    public function hasRole($role)
{
    if (is_array($role)) {
        return in_array($this->role, $role);
    }
    
    return $this->role === $role;
}

    public function employeeProfile()
    {
        // Make sure this is the correct relationship and foreign key
        return $this->hasOne(EmployeeProfile::class, 'user_id', 'id');
    }

    public function customer(): HasOne
    {
        return $this->hasOne(Customer::class);
    }

    public function isEmployee(): bool
    {
        return in_array($this->role, ['admin', 'staff', 'driver', 'collector']);
    }

    public function isCustomer(): bool
    {
        return $this->role === 'customer';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }

    public function isActive(): bool
    {
        // Implement your logic for checking if the user is active
        return $this->is_active ?? true;
    }

    public function isArchived(): bool
    {
        return $this->isEmployee() 
            && $this->employeeProfile 
            && !is_null($this->employeeProfile->archived_at);
    }

    
    public function getRoleName(): string
    {
        return match($this->role) {
            'admin' => 'Administrator',
            'staff' => 'Staff',
            'driver' => 'Driver',
            'collector' => 'Collector',
            'customer' => 'Customer',
            default => Str::title($this->role),
        };
    }

    public function deliveryOrders(): HasMany
    {
        return $this->hasMany(DeliveryOrder::class, 'driver_id');
    }

    public function canLogin(): bool
    {
        return $this->isActive() && $this->hasVerifiedEmail();
    }

    public function assignedDeliveryOrders(): HasMany
    {
        return $this->hasMany(DeliveryOrder::class, 'assigned_by');
    }

    public function dispatchedDeliveryOrders(): HasMany
    {
        return $this->hasMany(DeliveryOrder::class, 'dispatched_by');
    }

    // --- New region-related relationships and attribute ---

    public function currentRegion(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'current_region_id');
    }

    public function regionLogs(): HasMany
    {
        return $this->hasMany(DriverRegionLog::class, 'driver_id');
    }

    public function getCurrentDeliveryOrderAttribute()
    {
        return $this->deliveryOrders()
            ->whereIn('status', ['assigned', 'dispatched', 'in_transit'])
            ->first();
    }

    public function generatedReports(): HasMany
    {
        return $this->hasMany(Report::class, 'generated_by');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function sendEmailVerificationNotification()
    {
        $this->notify(new \App\Notifications\CustomVerifyEmail);
    }

    // NEW: Get all undelivered packages for this driver
    public function getUndeliveredPackagesCount(): int
    {
        return Package::whereHas('deliveryRequest.deliveryOrder', function($query) {
                $query->where('driver_id', $this->id)
                      ->whereIn('status', ['assigned', 'dispatched', 'in_transit']);
            })
            ->whereNotIn('status', ['delivered', 'completed', 'returned'])
            ->count();
    }

    // NEW: Check if all packages are delivered (for backhaul eligibility)
    public function allPackagesDelivered(): bool
    {
        return $this->getUndeliveredPackagesCount() === 0;
    }

    // NEW: Check if driver is returning to base
    public function isReturning(): bool
    {
        $assignment = $this->currentTruckAssignment;
        return $assignment && $assignment->current_status === \App\Models\DriverTruckAssignment::STATUS_RETURNING;
    }

    // NEW: Check if driver has active assignment
    public function hasActiveAssignment(): bool
    {
        return $this->currentTruckAssignment !== null;
    }

    // NEW: Get driver's home region from assignment
    public function getHomeRegionAttribute()
    {
        $assignment = $this->currentTruckAssignment;
        return $assignment ? $assignment->region : null;
    }

    // NEW: Check if driver can skip cooldown (Option A)
    public function canSkipCooldown(): bool
    {
        $assignment = $this->currentTruckAssignment;
        return $assignment && $assignment->current_status === \App\Models\DriverTruckAssignment::STATUS_COOLDOWN;
    }

    // NEW: Check if driver can return without backhaul (Option B)
    public function canReturnWithoutBackhaul(): bool
    {
        $assignment = $this->currentTruckAssignment;
        return $assignment && in_array($assignment->current_status, [
            \App\Models\DriverTruckAssignment::STATUS_ACTIVE,
            \App\Models\DriverTruckAssignment::STATUS_BACKHAUL_ELIGIBLE,
            \App\Models\DriverTruckAssignment::STATUS_COOLDOWN
        ]);
    }
}