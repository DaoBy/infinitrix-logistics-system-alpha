<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_request_id',
        'driver_id',
        'truck_id',
        'driver_truck_assignment_id', // ADD THIS if not exists
        'current_region_id',
        'assigned_by',
        'dispatched_by',
        'status',
        'dispatched_at',
        'estimated_departure',
        'estimated_arrival',
        'actual_departure',
        'actual_arrival',
        'notes',
        'payment_type',
        'payment_status',
        'payment_verified_at',
    ];

    protected $casts = [
        'estimated_departure' => 'datetime',
        'estimated_arrival' => 'datetime',
        'actual_departure' => 'datetime',
        'actual_arrival' => 'datetime',
        'dispatched_at' => 'datetime',
    ];

    // NEW: Status constants for delivery outcomes
    const STATUS_DELIVERED = 'delivered';
    const STATUS_PARTIALLY_DELIVERED = 'partially_delivered'; // NEW
    const STATUS_DELIVERY_FAILED = 'delivery_failed'; // NEW
    const STATUS_PENDING = 'pending';
    const STATUS_READY = 'ready';
    const STATUS_ASSIGNED = 'assigned';
    const STATUS_IN_TRANSIT = 'in_transit';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    public function deliveryRequest(): BelongsTo
    {
        return $this->belongsTo(DeliveryRequest::class, 'delivery_request_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function truck(): BelongsTo
    {
        return $this->belongsTo(Truck::class);
    }

    // NEW: Relationship to driver-truck assignment
    public function driverTruckAssignment(): BelongsTo
    {
        return $this->belongsTo(DriverTruckAssignment::class, 'driver_truck_assignment_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function dispatchedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispatched_by');
    }

    public function packages(): HasMany
    {
        return $this->hasMany(Package::class, 'delivery_request_id', 'delivery_request_id');
    }

    // --- Region relationship and logging methods ---

    public function currentRegion(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'current_region_id');
    }

    public function recordRegionArrival(int $regionId): void
    {
        $this->current_region_id = $regionId;
        $this->save();

        // Update all packages on this delivery
        $this->packages()->update(['current_region_id' => $regionId]);

        // Log the arrival
        $this->driver->regionLogs()->create([
            'region_id' => $regionId,
            'delivery_order_id' => $this->id,
            'type' => 'arrival',
            'logged_at' => now()
        ]);

        // Update driver's current region
        $this->driver()->update([
            'current_region_id' => $regionId,
            'last_region_update' => now()
        ]);

        // NEW: Check for backhaul eligibility after region arrival
        $this->checkBackhaulEligibility();
    }

    // NEW: Check if this delivery order completion triggers backhaul eligibility
    private function checkBackhaulEligibility(): void
    {
        $driver = $this->driver;
        $assignment = $driver->currentTruckAssignment;

        if ($assignment && !$assignment->available_for_backhaul) {
            // Check if driver is in different region from home and all packages delivered
            if ($driver->current_region_id != $assignment->region_id && $driver->allPackagesDelivered()) {
                $assignment->enableBackhaul();
            }
        }
    }

    public function recordRegionDeparture(int $regionId): void
    {
        $this->driver->regionLogs()->create([
            'region_id' => $regionId,
            'delivery_order_id' => $this->id,
            'type' => 'departure',
            'logged_at' => now()
        ]);
    }

    public function regionLogs()
    {
        return $this->hasMany(\App\Models\DriverRegionLog::class, 'delivery_order_id');
    }

    // Remove this as a relationship, make it an accessor:
    public function getPaymentAttribute()
    {
        return $this->deliveryRequest ? $this->deliveryRequest->payment : null;
    }

    // NEW: Check if this is a backhaul assignment
    public function isBackhaulAssignment(): bool
    {
        $assignment = $this->driverTruckAssignment;
        return $assignment && $assignment->available_for_backhaul;
    }

    // Status helper methods - UPDATED with new statuses
    public function isPending(): bool { return $this->status === 'pending'; }
    public function isReady(): bool { return $this->status === 'ready'; }
    public function isAssigned(): bool { return $this->status === 'assigned'; }
    public function isInTransit(): bool { return $this->status === 'in_transit'; }
    public function isDelivered(): bool { return $this->status === 'delivered'; }
    public function isPartiallyDelivered(): bool { return $this->status === 'partially_delivered'; } // NEW
    public function isDeliveryFailed(): bool { return $this->status === 'delivery_failed'; } // NEW
    public function isCompleted(): bool { return $this->status === 'completed'; }
    public function isCancelled(): bool { return $this->status === 'cancelled'; }

    // NEW: Check if delivery order is in final status (no more active packages)
    public function isFinalStatus(): bool
    {
        return in_array($this->status, ['delivered', 'partially_delivered', 'delivery_failed', 'completed', 'cancelled']);
    }

    public function isPaid(): bool
    {
        if (!$this->deliveryRequest) return false;
        return $this->deliveryRequest->isPaid();
    }

    public function requiresPayment(): bool
    {
        if (!$this->deliveryRequest) return false;
        return $this->deliveryRequest->isPostpaid() && !$this->isPaid();
    }

    // Scopes
  
    // NEW: Scope for backhaul assignments
    public function scopeBackhaulAssignments($query)
    {
        return $query->whereHas('driverTruckAssignment', function($q) {
            $q->where('available_for_backhaul', true);
        });
    }

    // NEW: Scope for regular assignments
    public function scopeRegularAssignments($query)
    {
        return $query->whereHas('driverTruckAssignment', function($q) {
            $q->where('available_for_backhaul', false);
        });
    }

    // NEW: Scope for delivery orders with final outcomes
    public function scopeWithFinalOutcome($query)
    {
        return $query->whereIn('status', ['delivered', 'partially_delivered', 'delivery_failed']);
    }

    // NEW: Scope for active delivery orders (not in final status)
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['delivered', 'partially_delivered', 'delivery_failed', 'completed', 'cancelled']);
    }

// Dispatched timestamp helper
public function wasDispatched(): bool {
    return !is_null($this->dispatched_at);
}

public function scopeRecentlyDispatched($query)
{
    return $query->whereNotNull('dispatched_at')
                 ->orderByDesc('dispatched_at');
}

    public function scopeInProgress($query)
    {
        return $query->whereIn('status', ['assigned','in_transit']);
    }

    public function scopeFilter($query, $filters)
    {
        if (!empty($filters['search'])) {
            $query->whereHas('deliveryRequest', function($q) use ($filters) {
                $q->where('order_number', 'like', '%' . $filters['search'] . '%');
            });
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        return $query;
    }

    public function calculateCurrentLoad(): float
    {
        return $this->packages->sum(function($package) {
            return ($package->height * $package->width * $package->length) / 1000000;
        });
    }

    public function scopeWithReturnStatus($query)
    {
        return $query->addSelect([
            'return_status' => \App\Models\DriverRegionLog::selectRaw("
                CASE 
                    WHEN EXISTS (
                        SELECT 1 FROM driver_region_logs 
                        WHERE delivery_order_id = delivery_orders.id 
                        AND type = 'return_verified_by_staff'
                    ) THEN 'verified'
                    WHEN EXISTS (
                        SELECT 1 FROM driver_region_logs 
                        WHERE delivery_order_id = delivery_orders.id 
                        AND type = 'driver_returned'
                    ) THEN 'pending'
                    ELSE 'not_returned'
                END
            ")
        ]);
    }

    public function scopeReadyForAssignment($query)
    {
        return $query->whereHas('deliveryRequest', function($q) {
            $q->where(function($q) {
                // Upfront payment methods must be verified and paid
                $q->whereIn('payment_method', ['cash', 'gcash', 'bank'])
                  ->where('payment_verified', true)
                  ->where('payment_status', 'paid');
            })->orWhere(function($q) {
                // If you have postpaid or other methods, handle here if needed
                $q->whereNotIn('payment_method', ['cash', 'gcash', 'bank']);
            });
        })->where('status', 'pending_payment');
    }

    // NEW: Check if all packages are in final status
    public function allPackagesFinal(): bool
    {
        return $this->packages()
            ->whereNotIn('status', ['delivered', 'completed', 'returned', 'damaged_in_transit', 'lost_in_transit'])
            ->count() === 0;
    }

    // NEW: Get delivery outcome statistics
    public function getDeliveryOutcome(): array
    {
        $packages = $this->packages;
        
        return [
            'total' => $packages->count(),
            'delivered' => $packages->where('status', 'delivered')->count(),
            'damaged' => $packages->where('status', 'damaged_in_transit')->count(),
            'lost' => $packages->where('status', 'lost_in_transit')->count(),
            'success_rate' => $packages->count() > 0 ? 
                ($packages->where('status', 'delivered')->count() / $packages->count()) * 100 : 0
        ];
    }

    // NEW: Get packages with incidents for refund processing
public function getIncidentPackages()
{
    return $this->packages()
        ->whereIn('status', ['damaged_in_transit', 'lost_in_transit'])
        ->whereNotNull('incident_reported_at')
        ->get();
}

public function needsReview(): bool 
{ 
    return $this->status === 'needs_review'; 
}

// NEW: Check if delivery order has incidents
public function hasIncidents(): bool
{
    return $this->packages()
        ->whereIn('status', ['damaged_in_transit', 'lost_in_transit'])
        ->whereNotNull('incident_reported_at')
        ->exists();
}

// NEW: Get incident statistics for refund calculation
public function getIncidentStatistics(): array
{
    $packages = $this->packages;
    
    return [
        'total_packages' => $packages->count(),
        'delivered' => $packages->where('status', 'delivered')->count(),
        'damaged' => $packages->where('status', 'damaged_in_transit')->count(),
        'lost' => $packages->where('status', 'lost_in_transit')->count(),
        'total_incidents' => $packages->whereIn('status', ['damaged_in_transit', 'lost_in_transit'])->count(),
        'incident_packages' => $packages->whereIn('status', ['damaged_in_transit', 'lost_in_transit'])
            ->whereNotNull('incident_reported_at')
            ->values(),
        'total_value_affected' => $packages->whereIn('status', ['damaged_in_transit', 'lost_in_transit'])
            ->sum('value'),
    ];
}
}