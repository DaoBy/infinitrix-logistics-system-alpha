<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

class DeliveryRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'pick_up_region_id',
        'drop_off_region_id',
        'payment_type',
        'payment_method',
        'payment_terms',           // <-- Add this
        'payment_due_date',        // <-- Add this
        'non_payment_reason',      // <-- Add this
        'total_price',
        'base_fee',
        'volume_fee',
        'weight_fee',
        'package_fee',
        'price_breakdown',
        'status',
        'rejection_reason',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'created_by',
        'payment_status',
        'payment_verified',
        'reference_number',
    ];

    protected $casts = [
        'total_price' => 'decimal:2',
        'base_fee' => 'decimal:2',
        'volume_fee' => 'decimal:2',
        'weight_fee' => 'decimal:2',
        'package_fee' => 'decimal:2',
        'price_breakdown' => 'array',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'payment_verified' => 'boolean',
        'payment_due_date' => 'date', // <-- Add this
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($deliveryRequest) {
            if (!$deliveryRequest->status) {
                $deliveryRequest->status = 'pending';
            }
            // Remove this block:
            // if (is_null($deliveryRequest->payment_method)) {
            //     $deliveryRequest->payment_method = 'cash'; // or any default you want
            // }
        });
    }

    // Relationships
    public function sender(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'sender_id');
    }

public function waybill(): \Illuminate\Database\Eloquent\Relations\HasOne
{
    return $this->hasOne(Waybill::class);
}
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'receiver_id');
    }

    public function pickUpRegion(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'pick_up_region_id');
    }

    public function dropOffRegion(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'drop_off_region_id');
    }

   public function packages(): HasMany
    {
        return $this->hasMany(Package::class, 'delivery_request_id');
        // Ensure the foreign key matches
    }

    public function deliveryOrder(): HasOne
    {
        return $this->hasOne(DeliveryOrder::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function generateReferenceNumber(): string
    {
        $this->reference_number = 'INF-' . now()->format('Y') . '-' . str_pad($this->id, 6, '0', STR_PAD_LEFT);
        $this->save();
        return $this->reference_number;
    }

    public function markAsPaid(Payment $payment): void
    {
        $this->update([
            'payment_status' => 'paid',
            'payment_verified' => true,
        ]);
        // Update all packages to 'preparing' (use only allowed status)
        $this->packages()->update(['status' => 'preparing']);
    }

    // Payment helpers
    public function isPrepaid(): bool
    {
        return $this->payment_type === 'prepaid';
    }

    public function isPostpaid(): bool
    {
        return $this->payment_type === 'postpaid';
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid' && $this->payment_verified;
    }

    public function getPaymentTypeAttribute(): string
    {
        return in_array($this->payment_method, ['cash', 'gcash', 'bank']) 
            ? 'prepaid' 
            : 'postpaid';
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function pick_up_region()
    {
        return $this->belongsTo(Region::class, 'pick_up_region_id');
    }

    public function drop_off_region()
    {
        return $this->belongsTo(Region::class, 'drop_off_region_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    // Helper Methods
    public function recalculateTotalPrice(): void
    {
        $priceMatrix = PriceMatrix::first();
        if (!$priceMatrix) return;

        $total = $priceMatrix->base_fee;
        
        foreach ($this->packages as $package) {
            $volume = ($package->height * $package->width * $package->length) / 1000000;
            $total += ($volume * $priceMatrix->volume_rate) 
                    + ($package->weight * $priceMatrix->weight_rate)
                    + $priceMatrix->package_rate;
        }

        $this->total_price = $total;
        $this->save();
    }

    // Add payment type accessor
    public function approve(User $approvedBy): void
    {
        DB::transaction(function () use ($approvedBy) {
            $this->update([
                'status' => 'approved',
                'approved_by' => $approvedBy->id,
                'approved_at' => now(),
            ]);

            $this->packages()->each(function($package) {
                $package->updateStatus('ready_for_pickup', auth()->user(), 'Request approved');
            });
        });
    }

    public function reject(User $rejectedBy, string $reason): void
    {
        DB::transaction(function () use ($rejectedBy, $reason) {
            $this->update([
                'status' => 'rejected',
                'rejection_reason' => $reason,
                'rejected_by' => $rejectedBy->id,
                'rejected_at' => now(),
            ]);

            $this->packages()->each(function($package) use ($reason) {
                $package->updateStatus('rejected', auth()->user(), $reason);
            });
        });
    }

    public function statusHistory(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(
            \App\Models\PackageStatusHistory::class,
            \App\Models\Package::class,
            'delivery_request_id', // Foreign key on Package table...
            'package_id',          // Foreign key on PackageStatusHistory table...
            'id',                  // Local key on DeliveryRequest table...
            'id'                   // Local key on Package table...
        );
    }
}