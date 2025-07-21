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
        'payment_type', // <-- add this
        'payment_status', // <-- add this
        'payment_verified_at',
    ];

    protected $casts = [
        'estimated_departure' => 'datetime',
        'estimated_arrival' => 'datetime',
        'actual_departure' => 'datetime',
        'actual_arrival' => 'datetime',
        'dispatched_at' => 'datetime',
    ];

    public function deliveryRequest(): BelongsTo
    {
        return $this->belongsTo(DeliveryRequest::class, 'delivery_request_id');
        // Ensure the foreign key matches DB
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function truck(): BelongsTo
    {
        return $this->belongsTo(Truck::class);
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

    // Status helper methods
    public function isPending(): bool { return $this->status === 'pending'; }
    public function isReady(): bool { return $this->status === 'ready'; }
    public function isAssigned(): bool { return $this->status === 'assigned'; }
    public function isInTransit(): bool { return $this->status === 'in_transit'; }
    public function isDelivered(): bool { return $this->status === 'delivered'; }
    public function isCompleted(): bool { return $this->status === 'completed'; }
    public function isCancelled(): bool { return $this->status === 'cancelled'; }

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


}