<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Truck extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'make',
        'model',
        'license_plate',
        'volume_capacity',       // max volume in m³
        'current_volume',        // current volume load
        'weight_capacity',       // max weight in kg
        'current_weight',        // current weight load
        'status',
        'year',
        'vin',
        'purchase_date',
        'purchase_price',
        'current_value',
        'notes',
        'is_active',
        'region_id', // Add this
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'purchase_price' => 'decimal:2',
        'current_value' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'current_volume',
        'current_weight'
    ];

    // 🚦 Status Constants
        const STATUS_AVAILABLE = 'available';
        const STATUS_NEARLY_FULL = 'nearly_full';
        const STATUS_ASSIGNED = 'assigned';
        const STATUS_IN_TRANSIT = 'in_transit';
        const STATUS_RETURNING = 'returning';
        const STATUS_MAINTENANCE = 'maintenance';
        const STATUS_UNAVAILABLE = 'unavailable';


    public static function statuses(): array
    {
        return [
            self::STATUS_AVAILABLE     => 'Available',
            self::STATUS_NEARLY_FULL   => 'Nearly Full',
            self::STATUS_ASSIGNED      => 'Assigned',
            self::STATUS_IN_TRANSIT    => 'In Transit',
            self::STATUS_RETURNING     => 'Returning',
            self::STATUS_MAINTENANCE   => 'In Maintenance',
            self::STATUS_UNAVAILABLE   => 'Unavailable',
        ];
    }

    public function maintenance(): HasMany
    {
        return $this->hasMany(TruckMaintenance::class);
    }

    public function components(): HasMany
    {
        return $this->hasMany(TruckComponent::class);
    }

    public function deliveryOrders(): HasMany
    {
        return $this->hasMany(DeliveryOrder::class);
    }

    public function manifests()
    {
        return $this->hasMany(Manifest::class);
    }

    // 🧠 VOLUME: Calculate total volume of current packages
    public function calculateCurrentVolume(): float
    {
        return $this->deliveryOrders()
            ->whereIn('status', ['assigned', 'dispatched', 'in_transit'])
            ->with(['deliveryRequest.packages'])
            ->get()
            ->sum(function ($order) {
                return $order->deliveryRequest->packages->sum('volume');
            });
    }

        public function getHasAssignmentsAttribute()
        {
        return $this->deliveryOrders()
            ->where('status', 'assigned')
            ->exists();
    }

    public function getAvailableVolumeCapacityAttribute(): float
    {
        return $this->volume_capacity - $this->calculateCurrentVolume();
    }

    // ⚖️ WEIGHT: Calculate total weight of current packages
    public function calculateCurrentWeight(): float
    {
        return $this->deliveryOrders()
            ->whereIn('status', ['assigned', 'dispatched', 'in_transit'])
            ->with(['deliveryRequest.packages'])
            ->get()
            ->sum(function ($order) {
                return $order->deliveryRequest->packages->sum('weight');
            });
    }

    public function getAvailableWeightCapacityAttribute(): float
    {
        return $this->weight_capacity - $this->calculateCurrentWeight();
    }

    // ✅ Combined check
    public function canAcceptDelivery(float $requiredVolume, float $requiredWeight): bool
    {
        return $this->available_volume_capacity >= $requiredVolume
            && $this->available_weight_capacity >= $requiredWeight;
    }

    // 🔁 Status auto-update based on worst load %
    public function updateStatus(): void
    {
        if (!$this->is_active) {
            $this->status = self::STATUS_UNAVAILABLE;
            $this->save();
            return;
        }

        if ($this->volume_capacity <= 0 || $this->weight_capacity <= 0) {
            $this->status = self::STATUS_UNAVAILABLE;
            $this->save();
            return;
        }

        $volumePercentage = $this->volume_capacity > 0 ? ($this->calculateCurrentVolume() / $this->volume_capacity) * 100 : 0;
        $weightPercentage = $this->weight_capacity > 0 ? ($this->calculateCurrentWeight() / $this->weight_capacity) * 100 : 0;
        $loadPercentage = max($volumePercentage, $weightPercentage);

        if ($loadPercentage >= 90) {
            $this->status = self::STATUS_NEARLY_FULL;
        } elseif ($loadPercentage >= 75) {
            $this->status = self::STATUS_NEARLY_FULL;
        } else {
            $this->status = self::STATUS_AVAILABLE;
        }

        $this->save();
    }

    public function isAvailable(): bool
    {
        // Truck is available if active, status is available or nearly full, and has capacity left
        return ($this->is_active ?? true)
            && in_array($this->status, [self::STATUS_AVAILABLE, self::STATUS_NEARLY_FULL])
            && ($this->available_volume_capacity ?? 1) > 0
            && ($this->available_weight_capacity ?? 1) > 0;
    }

    // Add a new method to check if truck can be dispatched
    public function canBeDispatched(): bool
    {
        return $this->has_assignments
            && $this->status !== self::STATUS_ASSIGNED // Changed from STATUS_IN_USE to STATUS_ASSIGNED
            && $this->deliveryOrders()
                ->where('status', 'assigned')
                ->whereNotNull('driver_id')
                ->exists();
    }

    // Add to relationships
    public function driverAssignments()
    {
        return $this->hasMany(DriverTruckAssignment::class, 'truck_id');
    }

    public function currentDriverAssignment()
    {
        return $this->hasOne(DriverTruckAssignment::class)
            ->where('is_active', true)
            ->latest();
    }

    public function getCapacityStatusAttribute(): string
    {
        $volumePercentage = $this->volume_capacity > 0 ? $this->calculateCurrentVolume() / $this->volume_capacity : 0;
        $weightPercentage = $this->weight_capacity > 0 ? $this->calculateCurrentWeight() / $this->weight_capacity : 0;
        $maxPercentage = max($volumePercentage, $weightPercentage);

        $thresholds = config('delivery.capacity_thresholds', [
            'warning' => 0.75,
            'critical' => 0.9,
        ]);

        if ($maxPercentage >= $thresholds['critical']) {
            return 'critical';
        }

        if ($maxPercentage >= $thresholds['warning']) {
            return 'warning';
        }

        return 'normal';
    }

    public function getCapacityStatusColorAttribute(): string
    {
        return match($this->capacity_status) {
            'critical' => 'red',
            'warning' => 'yellow',
            default => 'green'
        };
    }

    // Add this relationship
    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function getCurrentVolumeAttribute()
    {
        return $this->calculateCurrentVolume();
    }

    public function getCurrentWeightAttribute()
    {
        return $this->calculateCurrentWeight();
    }
}
