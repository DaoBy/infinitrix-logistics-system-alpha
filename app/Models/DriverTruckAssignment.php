<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB; 

class DriverTruckAssignment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'driver_id',
        'truck_id', 
        'region_id',
        'current_region_id',
        'current_status',
        'assigned_at',
        'completed_at',
        'is_active',
        'available_for_backhaul',
        'backhaul_eligible_at',
        'cooldown_ends_at',
        'deleted_reason',
        'deleted_by',
        'deleted_at',
        'is_final_cooldown',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'completed_at' => 'datetime',
        'backhaul_eligible_at' => 'datetime',
        'cooldown_ends_at' => 'datetime',
        'is_active' => 'boolean',
        'available_for_backhaul' => 'boolean',
        'is_final_cooldown' => 'boolean',
        'deleted_at' => 'datetime'
    ];

    // Status Constants
    const STATUS_ACTIVE = 'active';
    const STATUS_BACKHAUL_ELIGIBLE = 'backhaul_eligible';
    const STATUS_RETURNING = 'returning';
    const STATUS_COOLDOWN = 'cooldown';
    const STATUS_COMPLETED = 'completed';
    const STATUS_IN_TRANSIT = 'in_transit';
    const STATUS_CANCELLED = 'cancelled';

    public static function getStatuses(): array
    {
        return [
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_BACKHAUL_ELIGIBLE => 'Backhaul Eligible',
            self::STATUS_RETURNING => 'Returning to Base',
            self::STATUS_COOLDOWN => 'In Cooldown',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_IN_TRANSIT => 'In Transit',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    public static function getDeleteReasons(): array
    {
        return [
            'driver_unavailable' => 'Driver Unavailable',
            'truck_maintenance' => 'Truck Maintenance',
            'reassigned' => 'Reassigned',
            'other' => 'Other Reason'
        ];
    }

    // Relationships
    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function truck(): BelongsTo
    {
        return $this->belongsTo(Truck::class, 'truck_id');
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'region_id');
    }

    public function currentRegion(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'current_region_id');
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(DriverStatusLog::class);
    }

    public function deliveryOrders(): HasMany
    {
        return $this->hasMany(DeliveryOrder::class, 'driver_truck_assignment_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeNotDeleted($query)
    {
        return $query->whereNull('deleted_at')
                 ->where('current_status', '!=', self::STATUS_CANCELLED);
    }

    public function scopeBackhaulEligible($query)
    {
        return $query->where('current_status', self::STATUS_BACKHAUL_ELIGIBLE)
                    ->where('is_active', true);
    }

    public function scopeInCooldown($query)
    {
        return $query->where('current_status', self::STATUS_COOLDOWN)
                    ->where('is_active', true);
    }

    public function scopeAvailableForAssignment($query)
    {
        return $query->whereIn('current_status', [self::STATUS_ACTIVE, self::STATUS_BACKHAUL_ELIGIBLE])
                    ->where('is_active', true);
    }

    // Check if all packages are delivered
   public function allPackagesDelivered(): bool
{
    $hasAnyPackages = Package::whereHas('deliveryRequest.deliveryOrder', function($query) {
            $query->where('driver_truck_assignment_id', $this->id);
        })->exists();

    if (!$hasAnyPackages) {
        return false;
    }

    $undeliveredPackages = Package::whereHas('deliveryRequest.deliveryOrder', function($query) {
            $query->where('driver_truck_assignment_id', $this->id)
                  ->whereIn('status', ['assigned', 'dispatched', 'in_transit']);
        })
        ->whereNotIn('status', ['delivered', 'completed', 'returned', 'damaged_in_transit', 'lost_in_transit'])
        ->count();

    return $undeliveredPackages === 0;
}

    // Check backhaul eligibility - ONLY from COOLDOWN status
    public function checkBackhaulEligibility(): bool
    {
        if ($this->current_status !== self::STATUS_COOLDOWN) {
            return false;
        }
        
        $cooldownFinished = $this->cooldown_ends_at && $this->cooldown_ends_at->isPast();
        $allDelivered = $this->allPackagesDelivered();
        
        $eligible = $cooldownFinished && $allDelivered;
        
        if ($eligible) {
            $this->updateStatus(self::STATUS_BACKHAUL_ELIGIBLE, 'Cooldown period finished - auto-eligible for backhaul');
            return true;
        }
        
        return false;
    }

    // Complete deliveries - ALWAYS go to COOLDOWN first
   public function completeDeliveries(): void
{
        if ($this->truck) {
        $this->truck->updateStatus();
    }
        $this->processAutomaticTransitions();
}

    // Return without backhaul - OPTION B
     public function returnWithoutBackhaul(string $reason, ?int $staffId = null): void
    {
        \Log::info("Return without backhaul initiated", [
            'assignment_id' => $this->id,
            'current_status' => $this->current_status,
            'reason' => $reason
        ]);

        if (!in_array($this->current_status, [
            self::STATUS_ACTIVE, 
            self::STATUS_BACKHAUL_ELIGIBLE,
            self::STATUS_COOLDOWN
        ])) {
            throw new \Exception('Cannot return without backhaul from current status: ' . $this->current_status);
        }
        
        if ($this->driver->current_region_id == $this->region_id) {
            \Log::info("Driver already in home region - starting final cooldown", [
                'assignment_id' => $this->id,
                'driver_region' => $this->driver->current_region_id,
                'home_region' => $this->region_id
            ]);
            
            $this->updateStatus(self::STATUS_COOLDOWN, 'Driver already in home region - starting final cooldown (Option B)');
            $this->cooldown_ends_at = now()->addHour();
            $this->is_final_cooldown = true;
            $this->save();
            return;
        }

        $this->updateStatus(self::STATUS_RETURNING, $reason . ' (Option B - No Backhaul)');
        
        DriverRegionLog::create([
            'driver_id' => $this->driver_id,
            'region_id' => $this->current_region_id,
            'type' => 'return_initiated',
            'remarks' => $reason . ($staffId ? ' (by staff)' : '') . ' - Option B',
            'logged_at' => now()
        ]);

        if ($this->truck) {
            $this->truck->updateStatus();
        }

        \Log::info("Return without backhaul initiated successfully", [
            'assignment_id' => $this->id,
            'new_status' => $this->current_status
        ]);
    }

    // Complete assignment (when driver arrives at home region) - OPTION B COMPLETION
    public function completeAssignment(): void
{
    \Log::info("Completing assignment - Option B", [
        'assignment_id' => $this->id,
        'current_status' => $this->current_status
    ]);

    if ($this->current_status !== self::STATUS_RETURNING) {
        throw new \Exception('Cannot complete assignment from current status: ' . $this->current_status);
    }

    $this->load('driver');
    
    if ($this->driver->current_region_id != $this->region_id) {
        throw new \Exception('Driver must be in home region to complete assignment.');
    }

    $this->updateStatus(self::STATUS_COOLDOWN, 'Driver arrived at home region (Option B completion)');
    
    $this->cooldown_ends_at = now()->addHour();
    $this->is_final_cooldown = true;
    $this->save();

    // NO TRUCK STATUS UPDATE - let the system handle it automatically
    \Log::info("Assignment completion successful - now in final cooldown");
}



    // Complete cooldown period - FINISHES ASSIGNMENT
public function completeCooldown(): void
{
    \Log::info("Completing cooldown", [
        'assignment_id' => $this->id,
        'current_status' => $this->current_status,
        'is_final_cooldown' => $this->is_final_cooldown
    ]);

    if ($this->current_status !== self::STATUS_COOLDOWN) {
        throw new \Exception('Cannot complete cooldown from current status: ' . $this->current_status);
    }

    if (!$this->is_final_cooldown) {
        throw new \Exception('Cannot complete regular cooldown. Assignment must be in final cooldown status.');
    }

    $this->updateStatus(self::STATUS_COMPLETED, 'Trip completed - assignment finished');
    $this->is_active = false;
    $this->completed_at = now();
    $this->save();

    // NO TRUCK STATUS UPDATE - the assignment status change will trigger automatic updates
    $this->driver->current_region_id = $this->region_id;
    $this->driver->save();

    \Log::info("🎯 ASSIGNMENT COMPLETED - DRIVER/TRUCK NOW AVAILABLE");
}

    // SINGLE SOURCE OF TRUTH: Update status with logging
   public function updateStatus(string $newStatus, ?string $remarks = null): void
    {
        $previousStatus = $this->current_status;
        
        static $recentUpdates = [];
        $updateKey = $this->id . '_' . $previousStatus . '_' . $newStatus . '_' . ($remarks ?? '');
        
        if (in_array($updateKey, $recentUpdates)) {
            \Log::warning("Blocking duplicate update status", [
                'assignment_id' => $this->id,
                'from' => $previousStatus,
                'to' => $newStatus,
                'remarks' => $remarks
            ]);
            return;
        }
        
        $recentUpdates[] = $updateKey;
        if (count($recentUpdates) > 50) {
            array_shift($recentUpdates);
        }

        \Log::info("Updating assignment status", [
            'assignment_id' => $this->id,
            'from' => $previousStatus,
            'to' => $newStatus,
            'is_final_cooldown' => $this->is_final_cooldown,
            'remarks' => $remarks
        ]);
        
        if ($previousStatus === $newStatus) {
            \Log::info("No status change needed - already in {$newStatus}", [
                'assignment_id' => $this->id
            ]);
            return;
        }
        
        if (!$this->isValidStatusTransition($previousStatus, $newStatus)) {
            \Log::error("Invalid status transition blocked", [
                'assignment_id' => $this->id,
                'from' => $previousStatus,
                'to' => $newStatus
            ]);
            throw new \Exception("Invalid status transition: {$previousStatus} → {$newStatus}");
        }

        $this->current_status = $newStatus;
        $this->save();

        DriverStatusLog::create([
            'driver_truck_assignment_id' => $this->id,
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
            'remarks' => $remarks ?: "Status changed from {$previousStatus} to {$newStatus}",
            'changed_at' => now()
        ]);

      if ($this->truck) {
    // Let the truck's automatic updateStatus handle the mapping
    // Don't manually set any status except for completed assignment
    $this->truck->updateStatus();
}

        \Log::info("Status transition completed", [
            'assignment_id' => $this->id,
            'from' => $previousStatus,
            'to' => $newStatus
        ]);
    }

    private function isValidStatusTransition(string $from, string $to): bool
    {
        $validTransitions = [
            self::STATUS_ACTIVE => [
                self::STATUS_COOLDOWN,
                self::STATUS_RETURNING,
                self::STATUS_IN_TRANSIT,
                self::STATUS_CANCELLED,
            ],
            self::STATUS_BACKHAUL_ELIGIBLE => [
                self::STATUS_COOLDOWN,
                self::STATUS_RETURNING,
                self::STATUS_CANCELLED,
            ],
            self::STATUS_RETURNING => [
                self::STATUS_COOLDOWN,
            ],
            self::STATUS_COOLDOWN => [
                self::STATUS_BACKHAUL_ELIGIBLE,
                self::STATUS_RETURNING,
                self::STATUS_COMPLETED,
                self::STATUS_CANCELLED,
            ],
            self::STATUS_IN_TRANSIT => [
                self::STATUS_ACTIVE,
                self::STATUS_COOLDOWN,
            ],
            self::STATUS_COMPLETED => [
                // Completed is final status - no transitions out
            ],
            self::STATUS_CANCELLED => [
                // Cancelled is final status - no transitions out
            ],
        ];

        $isValid = in_array($to, $validTransitions[$from] ?? []);
        
        if ($from === self::STATUS_COOLDOWN && $to === self::STATUS_BACKHAUL_ELIGIBLE) {
            $isValid = $isValid && !$this->is_final_cooldown;
        }
        
        if ($from === self::STATUS_COOLDOWN && $to === self::STATUS_COMPLETED) {
            $isValid = $isValid && $this->is_final_cooldown;
        }

        return $isValid;
    }

    // Check if assignment can be cancelled
  public function canBeCancelled(): bool
{
    // Do not allow cancellation for these statuses
    $excludedStatuses = [
        self::STATUS_IN_TRANSIT, 
        self::STATUS_BACKHAUL_ELIGIBLE
    ];
    
    return !in_array($this->current_status, $excludedStatuses) &&
           in_array($this->current_status, [
               self::STATUS_ACTIVE, 
               self::STATUS_COOLDOWN
           ]) && 
           $this->is_active;
}

    public function canBeForceCompleted(): bool
    {
        return $this->current_status === self::STATUS_COOLDOWN 
            && $this->is_final_cooldown 
            && $this->allPackagesDelivered();
    }

    // Check if there are active deliveries
    public function hasActiveDeliveries(): bool
    {
        return $this->deliveryOrders()
            ->whereIn('status', ['assigned', 'dispatched', 'in_transit'])
            ->exists();
    }

      public function cancelAssignment(string $reason, int $cancelledBy): void
    {
        \Log::info("Cancelling assignment", [
            'assignment_id' => $this->id,
            'before_status' => $this->current_status,
            'before_is_active' => $this->is_active
        ]);

        try {
            DB::beginTransaction();

            $this->updateStatus(self::STATUS_CANCELLED, 'Assignment cancelled: ' . $reason);
            
            $this->is_active = false;
            $this->available_for_backhaul = false;
            $this->is_final_cooldown = false;
            $this->cooldown_ends_at = null;
            $this->backhaul_eligible_at = null;
            $this->current_region_id = $this->region_id;
            $this->deleted_reason = $reason;
            $this->deleted_by = $cancelledBy;
            $this->deleted_at = now();
            $this->save();

            \Log::info("Assignment cancelled", [
                'assignment_id' => $this->id,
                'after_status' => $this->current_status,
                'after_is_active' => $this->is_active
            ]);

            if ($this->truck) {
                $this->truck->update(['status' => Truck::STATUS_AVAILABLE]);
            }

            $this->driver->current_region_id = $this->region_id;
            $this->driver->save();

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to cancel assignment: ' . $e->getMessage(), [
                'assignment_id' => $this->id
            ]);
            throw $e;
        }
    }

    public function setIsFinalCooldownAttribute($value)
    {
        $this->attributes['is_final_cooldown'] = $value;
    }

    // Check if eligible for backhaul (simplified)
    public function isEligibleForBackhaul(): bool
    {
        return $this->current_status === self::STATUS_BACKHAUL_ELIGIBLE 
            && $this->is_active;
    }

  public function skipCooldown(): bool
{
    try {
        \Log::info("🎯 ULTRA SIMPLE: Skip cooldown", [
            'assignment_id' => $this->id,
            'current_status' => $this->current_status
        ]);

        // Force the update
        $this->current_status = self::STATUS_BACKHAUL_ELIGIBLE;
        $this->available_for_backhaul = true;
        $this->is_final_cooldown = false;
        $this->cooldown_ends_at = null;
        
        $result = $this->save();
        
        \Log::info("🎯 ULTRA SIMPLE: Result", [
            'assignment_id' => $this->id,
            'save_result' => $result,
            'new_status' => $this->current_status
        ]);

        return (bool) $result;

    } catch (\Exception $e) {
        \Log::error('🎯 ULTRA SIMPLE: Error: ' . $e->getMessage());
        return false;
    }
}

    // Get the current assignment for a driver
    public static function getCurrentAssignmentForDriver(int $driverId): ?self
    {
        return static::where('driver_id', $driverId)
            ->where('is_active', true)
            ->notDeleted()
            ->latest()
            ->first();
    }

    // Check if driver can accept new deliveries
    public function canAcceptNewDeliveries(): bool
    {
        return in_array($this->current_status, [
            self::STATUS_ACTIVE, 
            self::STATUS_BACKHAUL_ELIGIBLE,
            self::STATUS_IN_TRANSIT
        ]) && $this->is_active && $this->truck && $this->truck->isAvailable();
    }

    public function getValidNextStatuses(): array
    {
        $validTransitions = [
            self::STATUS_ACTIVE => [
                self::STATUS_RETURNING => 'Return to Base (Option B)',
                self::STATUS_IN_TRANSIT => 'Go In Transit',
            ],
            self::STATUS_BACKHAUL_ELIGIBLE => [
                self::STATUS_RETURNING => 'Return to Base (Option B)',
            ],
            self::STATUS_RETURNING => [
                self::STATUS_COOLDOWN => 'Arrive at Home (Option B Completion)',
            ],
            self::STATUS_COOLDOWN => [
                self::STATUS_BACKHAUL_ELIGIBLE => 'Skip Cooldown (Option A)',
                self::STATUS_RETURNING => 'Return Without Backhaul (Option B)',
                self::STATUS_COMPLETED => 'Complete Assignment',
            ],
            self::STATUS_IN_TRANSIT => [
                self::STATUS_ACTIVE => 'Return to Active',
            ],
        ];

        return $validTransitions[$this->current_status] ?? [];
    }

    // Check if cooldown timer has finished
    public function isCooldownFinished(): bool
    {
        return $this->current_status === self::STATUS_COOLDOWN 
            && $this->cooldown_ends_at 
            && $this->cooldown_ends_at->isPast();
    }

    // AUTOMATIC TRANSITIONS
    public function processAutomaticTransitions(): void
{
    static $recentlyProcessed = [];
    $key = $this->id . '_' . $this->current_status . '_' . now()->format('Hi');
    
    if (in_array($key, $recentlyProcessed)) {
        return;
    }
    
    $recentlyProcessed[] = $key;
    if (count($recentlyProcessed) > 20) {
        array_shift($recentlyProcessed);
    }

    $previousStatus = $this->current_status;

    // 1. COOLDOWN → BACKHAUL_ELIGIBLE (when timer finishes - FIRST cooldown only)
    if ($this->current_status === self::STATUS_COOLDOWN && 
        $this->isCooldownFinished() && 
        !$this->is_final_cooldown) {
        
        $this->updateStatus(
            self::STATUS_BACKHAUL_ELIGIBLE, 
            'Cooldown period finished - automatically eligible for backhaul'
        );
        return;
    }
    
    // 2. BACKHAUL_ELIGIBLE → COOLDOWN (Only trigger when NEW packages are delivered)
    if ($this->current_status === self::STATUS_BACKHAUL_ELIGIBLE && $this->allPackagesDelivered()) {
        
        $backhaulEligibleTime = $this->backhaul_eligible_at ?? $this->updated_at;
        
        $packagesDeliveredAfterBackhaul = Package::whereHas('deliveryRequest.deliveryOrder', function($query) use ($backhaulEligibleTime) {
                $query->where('driver_truck_assignment_id', $this->id);
            })
            ->whereIn('status', ['delivered', 'completed', 'damaged_in_transit', 'lost_in_transit'])
            ->where('updated_at', '>=', $backhaulEligibleTime)
            ->exists();
        
        if ($this->available_for_backhaul && $packagesDeliveredAfterBackhaul) {
            $this->updateStatus(
                self::STATUS_COOLDOWN,
                'Backhaul packages delivered - FINAL cooldown'
            );
            
            $this->cooldown_ends_at = now()->addMinutes(30);
            $this->is_final_cooldown = true;
            $this->save();
        }
        return;
    }

    // 3. COOLDOWN → COMPLETED (when FINAL cooldown finishes)
    if ($this->current_status === self::STATUS_COOLDOWN && 
        $this->isCooldownFinished() && 
        $this->is_final_cooldown) {
        
        $this->completeCooldown();
        return;
    }

    // 4. BACKHAUL_ELIGIBLE → COOLDOWN (auto-timeout after 24 hours)
    if ($this->current_status === self::STATUS_BACKHAUL_ELIGIBLE) {
        $backhaulTimeout = $this->backhaul_eligible_at && 
                          $this->backhaul_eligible_at->addHours(24)->isPast();
        
        if ($backhaulTimeout && $this->allPackagesDelivered()) {
            $isInHomeRegion = false;
            try {
                if (!$this->relationLoaded('driver')) $this->load('driver');
                if (!$this->relationLoaded('region')) $this->load('region');
                
                if ($this->driver && $this->driver->current_region_id && $this->region) {
                    $isInHomeRegion = $this->driver->current_region_id == $this->region->id;
                }
            } catch (\Exception $e) {
            }
            
            $this->updateStatus(
                self::STATUS_COOLDOWN,
                $isInHomeRegion ?
                    'Backhaul eligibility timeout at home region - starting FINAL cooldown' :
                    'Backhaul eligibility timeout - starting cooldown period'
            );
            
            if ($isInHomeRegion) {
                $this->cooldown_ends_at = now()->addMinutes(30);
                $this->is_final_cooldown = true;
            } else {
                $this->cooldown_ends_at = now()->addHour();
                $this->is_final_cooldown = false;
            }
            $this->save();
            return;
        }
    }

    // 5. AUTOMATIC: Any status → COOLDOWN when all packages delivered
    if ($this->current_status !== self::STATUS_COOLDOWN && 
        $this->allPackagesDelivered() && 
        in_array($this->current_status, [self::STATUS_ACTIVE, self::STATUS_IN_TRANSIT, self::STATUS_BACKHAUL_ELIGIBLE])) {
        
        $this->updateStatus(
            self::STATUS_COOLDOWN,
            'All packages delivered - automatic cooldown start'
        );
        
        $this->cooldown_ends_at = now()->addHour();
        $this->is_final_cooldown = false;
        $this->save();
        return;
    }

    // 6. AUTOMATIC: RETURNING → COOLDOWN when driver arrives at home region
    if ($this->current_status === self::STATUS_RETURNING && 
        $this->driver->current_region_id == $this->region_id) {
        
        $this->completeAssignment();
        return;
    }
}


/**
 * Cancel assignment with delivery reversion - COMPLETE IMPLEMENTATION
 */
public function cancelAssignmentWithRevert(string $reason, int $cancelledBy): void
{
    \Log::info("Cancelling assignment with delivery reversion", [
        'assignment_id' => $this->id,
        'before_status' => $this->current_status,
        'active_deliveries_count' => $this->deliveryOrders()
            ->whereIn('status', ['assigned', 'dispatched', 'in_transit'])
            ->count()
    ]);

    try {
        DB::beginTransaction();

        // 1. REVERT ACTIVE DELIVERIES FIRST
        $this->revertActiveDeliveries($reason, $cancelledBy);

        // 2. UPDATE ASSIGNMENT STATUS
        $this->updateStatus(self::STATUS_CANCELLED, 'Assignment cancelled: ' . $reason);
        
        $this->is_active = false;
        $this->available_for_backhaul = false;
        $this->is_final_cooldown = false;
        $this->cooldown_ends_at = null;
        $this->backhaul_eligible_at = null;
        $this->current_region_id = $this->region_id;
        $this->deleted_reason = $reason;
        $this->deleted_by = $cancelledBy;
        $this->deleted_at = now();
        $this->save();

        // 3. UPDATE TRUCK STATUS
        if ($this->truck) {
            $this->truck->update(['status' => Truck::STATUS_AVAILABLE]);
            $this->truck->updateStatus();
        }

        // 4. RESET DRIVER REGION
        $this->driver->current_region_id = $this->region_id;
        $this->driver->save();

        DB::commit();

        \Log::info("Assignment cancelled successfully with delivery reversion", [
            'assignment_id' => $this->id,
            'reverted_deliveries' => $this->deliveryOrders()
                ->whereIn('status', ['ready', 'pending_payment'])
                ->count()
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('Failed to cancel assignment: ' . $e->getMessage(), [
            'assignment_id' => $this->id
        ]);
        throw $e;
    }
}

/**
 * Revert active deliveries to available status - CORRECTED
 */
private function revertActiveDeliveries(string $reason, int $cancelledBy): void
{
    $activeDeliveries = $this->deliveryOrders()
        ->whereIn('status', ['assigned', 'dispatched', 'in_transit'])
        ->with(['deliveryRequest.packages'])
        ->get();

    foreach ($activeDeliveries as $delivery) {
        // Revert delivery order status back to 'ready'
        $previousStatus = $delivery->status;
        
        // Determine the appropriate status to revert to
        $revertStatus = $this->getRevertStatus($delivery); // Returns 'ready' or 'pending_payment'
        
        $delivery->update([
            'status' => $revertStatus, // ← DELIVERY ORDER GOES TO READY
            'driver_id' => null,
            'truck_id' => null,
            'driver_truck_assignment_id' => null,
            'assigned_at' => null,
            'assigned_by' => null,
            'estimated_departure' => null,
            'estimated_arrival' => null,
            'actual_departure' => null,
            'cancellation_reason' => "Assignment cancelled: " . $reason,
            'cancelled_by' => $cancelledBy,
            'cancelled_at' => now()
        ]);

        // Also revert related packages to 'preparing'
        $this->revertPackagesStatus($delivery, $reason, $cancelledBy);

        \Log::info("Reverted delivery order status", [
            'delivery_order_id' => $delivery->id,
            'from_status' => $previousStatus,
            'to_status' => $revertStatus, // Should be 'ready'
            'package_status' => 'preparing', // Packages went to preparing
            'assignment_id' => $this->id
        ]);
    }
}
/**
 * Revert package statuses with history logging - CORRECTED
 */
private function revertPackagesStatus(DeliveryOrder $delivery, string $reason, int $cancelledBy): void
{
    $packages = $delivery->deliveryRequest->packages;
    
    foreach ($packages as $package) {
        $previousStatus = $package->status;
        
        // Update package status from 'loaded' back to 'preparing'
        $package->update([
            'status' => 'preparing', // ← PACKAGES GO TO PREPARING
            'current_driver_id' => null,
            'current_truck_id' => null,
            'loaded_at' => null,
            'current_region_id' => $package->deliveryRequest->pick_up_region_id
        ]);
        
        // Log the status change in package_status_history
        $package->statusHistory()->create([
            'status' => 'preparing',
            'remarks' => "Reverted due to assignment cancellation: " . $reason,
            'updated_by' => $cancelledBy
        ]);

        \Log::info("Reverted package status", [
            'package_id' => $package->id,
            'from_status' => $previousStatus,
            'to_status' => 'preparing',
            'delivery_order_id' => $delivery->id
        ]);
    }
}

/**
 * Determine the appropriate status to revert delivery order to - CORRECTED
 */
private function getRevertStatus(DeliveryOrder $delivery): string
{
    // Check payment status to determine if it should go back to pending_payment
    $deliveryRequest = $delivery->deliveryRequest;
    
    if ($deliveryRequest->payment_method === 'postpaid' && 
        $deliveryRequest->payment_status === 'pending') {
        return 'pending_payment';
    }
    
    // Default revert to 'ready' for Delivery Order
    return 'ready'; // ← DELIVERY ORDER GOES TO READY
}


    // Force complete assignment (admin override)
    public function forceCompleteAssignment(): void
    {
        $this->updateStatus(self::STATUS_COMPLETED, 'Assignment force-completed by system');
        $this->is_active = false;
        $this->completed_at = now();
        $this->save();

        if ($this->truck) {
            $this->truck->update(['status' => Truck::STATUS_AVAILABLE]);
        }

        $this->driver->current_region_id = $this->region_id;
        $this->driver->save();
    }

    // Helper methods
    private function hasAnyDeliveries(): bool
    {
        return $this->deliveryOrders()->exists();
    }

    private function hasAnyPackages(): bool
    {
        return Package::whereHas('deliveryRequest.deliveryOrder', function($query) {
            $query->where('driver_truck_assignment_id', $this->id);
        })->exists();
    }
}