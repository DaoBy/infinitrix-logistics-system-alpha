<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Log;

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
        'sticker_printed_at',
        'sticker_printed_by',
        // NEW: Incident reporting fields
        'incident_evidence',
        'incident_description',
        'incident_reported_at',
        'incident_reported_by',
        'incident_resolved_at',
        'incident_resolved_by',
    ];

    protected $appends = ['photo_url'];

  protected $casts = [
    'sticker_printed_at' => 'datetime',
    'height' => 'decimal:2',
    'width' => 'decimal:2',
    'length' => 'decimal:2',
    'volume' => 'decimal:2',
    'weight' => 'decimal:2',
    'value' => 'decimal:2',
    'incident_evidence' => 'array',
    'incident_reported_at' => 'datetime',
    'incident_resolved_at' => 'datetime',
    'photo_path' => 'array', // Add this line
];
    
    public static function getStatuses(): array
    {
        return [
            'preparing' => 'Preparing',
            'ready_for_pickup' => 'Ready for Pickup',
            'loaded' => 'Loaded',
            'in_transit' => 'In Transit',
            'delivered' => 'Delivered',
            'completed' => 'Completed', 
            'returned' => 'Returned',
            'rejected' => 'Rejected',
            'damaged_in_transit' => 'Damaged in Transit',
            'lost_in_transit' => 'Lost in Transit'
        ];
    }

       // NEW: Incident reporting methods
    public function reportIncident(string $status, User $reportedBy, ?string $description = null, ?array $evidence = null): void
    {
        if (!in_array($status, ['damaged_in_transit', 'lost_in_transit'])) {
            throw new \Exception('Invalid incident status. Must be damaged_in_transit or lost_in_transit.');
        }

        $this->updateStatus($status, $reportedBy, $description ?? 'Incident reported');
        
        // Set incident reporting fields
        $this->incident_description = $description;
        $this->incident_evidence = $evidence;
        $this->incident_reported_at = now();
        $this->incident_reported_by = $reportedBy->id;
        $this->save();

        Log::info("Package incident reported", [
            'package_id' => $this->id,
            'status' => $status,
            'reported_by' => $reportedBy->id,
            'has_evidence' => !empty($evidence)
        ]);
    }

    public function resolveIncident(User $resolvedBy, ?string $resolutionNotes = null): void
    {
        $this->incident_resolved_at = now();
        $this->incident_resolved_by = $resolvedBy->id;
        $this->save();

        Log::info("Package incident resolved", [
            'package_id' => $this->id,
            'resolved_by' => $resolvedBy->id,
            'original_status' => $this->status
        ]);
    }

    public function hasIncident(): bool
    {
        return in_array($this->status, ['damaged_in_transit', 'lost_in_transit']) && 
               !is_null($this->incident_reported_at);
    }

    public function isIncidentResolved(): bool
    {
        return $this->hasIncident() && !is_null($this->incident_resolved_at);
    }

    public function incidentReporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'incident_reported_by');
    }

    public function incidentResolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'incident_resolved_by');
    }

    // NEW: Get incident evidence URLs
    public function getIncidentEvidenceUrlsAttribute(): array
    {
        if (empty($this->incident_evidence)) {
            return [];
        }

        return array_map(function($path) {
            return asset('storage/'.$path);
        }, $this->incident_evidence);
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
    if (empty($this->photo_path)) {
        return [];
    }

    return array_map(function($path) {
        return asset('storage/'.$path);
    }, $this->photo_path);
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
        // When a package reaches final status
        if (in_array($package->status, ['delivered', 'damaged_in_transit', 'lost_in_transit', 'completed', 'returned'])) {
            $driver = $package->deliveryRequest->deliveryOrder->driver ?? null;
            
            if ($driver) {
                $activeOrders = $driver->deliveryOrders()
                    ->whereIn('status', ['assigned', 'dispatched', 'in_transit', 'needs_review'])
                    ->with(['packages', 'deliveryRequest'])
                    ->get();

                foreach ($activeOrders as $order) {
                    $packages = $order->packages;
                    $deliveredCount = $packages->where('status', 'delivered')->count();
                    $totalPackages = $packages->count();
                    $finalStatusPackages = $packages->whereIn('status', ['delivered', 'damaged_in_transit', 'lost_in_transit', 'completed', 'returned'])->count();

                    // Only update if ALL packages have final status
                    if ($finalStatusPackages === $totalPackages && $totalPackages > 0) {
                        // SIMPLE 2-STATUS SYSTEM
                        if ($deliveredCount === $totalPackages) {
                            $newOrderStatus = 'delivered';
                        } else {
                            $newOrderStatus = 'needs_review'; // ANY issues
                        }

                        if ($order->status !== $newOrderStatus) {
                            $order->update([
                                'status' => $newOrderStatus,
                                'actual_arrival' => now(),
                            ]);

                            \Log::info("Delivery order {$order->id} auto-updated to {$newOrderStatus}", [
                                'delivered' => $deliveredCount,
                                'total' => $totalPackages,
                                'has_issues' => $newOrderStatus === 'needs_review'
                            ]);
                        }
                    }
                }

                // Check driver assignment for backhaul
                $assignment = $driver->currentTruckAssignment;
                if ($assignment && $assignment->allPackagesDelivered()) {
                    $assignment->completeDeliveries();
                }
            }
        }
    });
}

    /**
     * Get the user who printed the sticker for this package.
     */
    public function printedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sticker_printed_by');
    }

    public function getHasStickerAttribute(): bool
    {
        return !is_null($this->sticker_printed_at);
    }
}