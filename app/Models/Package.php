<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
class Package extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_code',
        'item_name',
        'category',
        'height',
        'width',
        'length',
        'volume',
        'weight',
        'value',
        'description',
        'photo_path',
        'delivery_request_id',
        'current_region_id',
        'status',
        'delivery_confirmation_id',
    ];

    protected $appends = ['photo_url'];

public static function getStatuses(): array
{
    return [
        'preparing' => 'Preparing',
        'ready_for_pickup' => 'Ready for Pickup',
        'loaded' => 'Loaded',
        'in_transit' => 'In Transit',
        'delivered' => 'Delivered',
        'completed' => 'Completed', // Ensure this exists
        'returned' => 'Returned',
        'rejected' => 'Rejected'
    ];
}



public function shouldMarkAsReturn(int $toRegionId): bool
{
    // Get the delivery request relationship if not already loaded
    if (!$this->relationLoaded('deliveryRequest')) {
        $this->load('deliveryRequest');
    }

    // Check if this is a return to the sender's region
    return $this->deliveryRequest && 
           $this->deliveryRequest->pick_up_region_id === $toRegionId;
}

    /**
     * Eloquent relationship for eager loading.
     * This is needed for with('waybill') to work.
     */
    public function waybill()
    {
        return $this->hasOne(\App\Models\Waybill::class, 'delivery_request_id', 'delivery_request_id');
    }

public function transfers(): \Illuminate\Database\Eloquent\Relations\HasMany
{
    return $this->hasMany(PackageTransfer::class)->latest();
}

public function statusHistory(): \Illuminate\Database\Eloquent\Relations\HasMany
{
    return $this->hasMany(PackageStatusHistory::class)->latest();
}

public function currentRegion(): \Illuminate\Database\Eloquent\Relations\BelongsTo
{
    return $this->belongsTo(Region::class, 'current_region_id');
}

public function deliveryRequest(): \Illuminate\Database\Eloquent\Relations\BelongsTo
{
    return $this->belongsTo(DeliveryRequest::class);
}

    public function getPhotoUrlAttribute()
    {
        return $this->photo_path ? asset('storage/'.$this->photo_path) : null;
    }

    /**
     * This is NOT a relationship. This is an accessor for $package->waybill.
     * Do NOT define a public function waybill() relationship.
     */
    public function getWaybillAttribute()
    {
        // Always access as a property, not as a relationship
        // Defensive: get the deliveryRequest relation or fetch if not loaded
        $deliveryRequest = $this->relationLoaded('deliveryRequest')
            ? $this->getRelation('deliveryRequest')
            : $this->deliveryRequest()->first();

        // Return the waybill property (not as a relationship method)
        return $deliveryRequest ? $deliveryRequest->waybill : null;
    }

    public function updateStatus(string $status, ?User $updatedBy = null, ?string $remarks = null, ?PackageTransfer $transfer = null): void
    {
        $this->status = $status;
        $this->save();

        $historyData = [
            'status' => $status,
            'updated_by' => $updatedBy?->id,
            'remarks' => $remarks,
        ];

        // Only set transfer_id if we have a transfer and status is transit/delivered
        if ($transfer && in_array($status, ['in_transit', 'delivered', 'returned'])) {
            $historyData['package_transfer_id'] = $transfer->id;
        }

        $this->statusHistory()->create($historyData);
    }

    public function transferToRegion(int $toRegionId, User $processor, ?string $notes = null): PackageTransfer
    {
        $transfer = $this->transfers()->create([
            'from_region_id' => $this->current_region_id,
            'to_region_id' => $toRegionId,
            'processed_by' => $processor->id,
            'remarks' => $notes,
            'is_return' => $this->shouldMarkAsReturn($toRegionId),
        ]);

        // Update package's current region
        $this->current_region_id = $toRegionId;
        
        // Determine status based on transfer direction
        $newStatus = $this->shouldMarkAsReturn($toRegionId) ? 'returned' : 'in_transit';
        $this->updateStatus($newStatus, $processor, 'Transfer initiated', $transfer);

        $this->save();

        return $transfer;
    }


    protected static function boot()
    {
        parent::boot();

        static::saving(function ($package) {
            // Calculate volume in cubic meters (m³)
            if ($package->height && $package->width && $package->length) {
                $package->volume = ($package->height * $package->width * $package->length) / 1000000;
            } else {
                $package->volume = 0;
            }
        });

        static::created(function ($package) {
            $package->updateStatus('preparing', auth()->user(), 'Package created');
        });
    }

    // --- Delivery confirmation relationship and logic ---

    public function deliveryConfirmation(): BelongsTo
    {
        return $this->belongsTo(DeliveryConfirmation::class);
    }

    public function confirmDelivery(User $confirmedBy, ?string $notes = null): DeliveryConfirmation
    {
        $confirmation = DeliveryConfirmation::create([
            'package_id' => $this->id,
            'region_id' => $this->current_region_id,
            'confirmed_by' => $confirmedBy->id,
            'confirmed_at' => now(),
            'notes' => $notes
        ]);

        $this->delivery_confirmation_id = $confirmation->id;
        $this->save();

        return $confirmation;
    }

    /**
     * Find a package with the same name and dimensions.
     * Prevents duplicate items with identical specs.
     *
     * @param string $itemName
     * @param array $dimensions ['height' => ..., 'width' => ..., 'length' => ...]
     * @return Package|null
     */
    public static function findSimilar($itemName, $dimensions)
    {
        return self::where('item_name', $itemName)
            ->where('height', $dimensions['height'])
            ->where('width', $dimensions['width'])
            ->where('length', $dimensions['length'])
            ->first();
    }

    public static function booted()
    {
        static::saved(function ($package) {
            // When a package is saved and its status is delivered, check the parent delivery order
            if ($package->status === 'delivered' && $package->deliveryRequest && $package->deliveryRequest->deliveryOrder) {
                $deliveryOrder = $package->deliveryRequest->deliveryOrder;
                
                // Check if all packages for this delivery order are delivered
                $allDelivered = $deliveryOrder->packages()->where('status', '!=', 'delivered')->count() === 0;
                
                if ($allDelivered && $deliveryOrder->status !== 'delivered') {
                    $deliveryOrder->update([
                        'status' => 'delivered',
                        'actual_arrival' => now(), // <-- Add this line
                    ]);
                    
                    // Optionally, add a status history if your model supports it
                    if (method_exists($deliveryOrder, 'statusHistories')) {
                        $deliveryOrder->statusHistories()->create([
                            'status' => 'delivered',
                            'remarks' => 'All packages delivered',
                            'updated_by' => auth()->id()
                        ]);
                    }
                    
                    // Update truck status to returning
                    if ($deliveryOrder->truck) {
                        $deliveryOrder->truck->update(['status' => \App\Models\Truck::STATUS_RETURNING]);
                    }
                }
            }
        });
    }
}