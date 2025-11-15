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
        'payment_terms',
        'payment_due_date',
        'non_payment_reason',
        'total_price',
        'processing_fee_paid',
        'processing_fee_amount',
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
        'cancellation_reason',
        'cancelled_by',
        'cancelled_at',
        // REMOVE 'net_price' from this array
    ];

    protected $casts = [
        'total_price' => 'decimal:2',
        // REMOVE THIS LINE: 'net_price' => 'decimal:2',
        'processing_fee_amount' => 'decimal:2',
        'base_fee' => 'decimal:2',
        'volume_fee' => 'decimal:2',
        'weight_fee' => 'decimal:2',
        'package_fee' => 'decimal:2',
        'price_breakdown' => 'array',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'payment_verified' => 'boolean',
        'payment_due_date' => 'date',
        'processing_fee_paid' => 'boolean',
        'cancelled_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($deliveryRequest) {
            if (!$deliveryRequest->status) {
                $deliveryRequest->status = 'pending';
            }
            
            // Set default processing fee values
            if ($deliveryRequest->processing_fee_amount === null) {
                $deliveryRequest->processing_fee_amount = 200.00;
            }
            
            if ($deliveryRequest->processing_fee_paid === null) {
                $deliveryRequest->processing_fee_paid = false;
            }
            
            // REMOVE net_price calculation - this column doesn't exist
            // if ($deliveryRequest->net_price === null && $deliveryRequest->total_price !== null) {
            //     $deliveryRequest->net_price = $deliveryRequest->total_price - $deliveryRequest->processing_fee_amount;
            // }
        });

        static::updating(function ($deliveryRequest) {
            // REMOVE net_price recalculation - this column doesn't exist
            // if ($deliveryRequest->isDirty('total_price') || $deliveryRequest->isDirty('processing_fee_amount')) {
            //     $deliveryRequest->net_price = $deliveryRequest->total_price - $deliveryRequest->processing_fee_amount;
            // }
        });
    }

    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    // Relationships
    public function sender(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'sender_id');
    }

    public function waybill(): HasOne
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
    }

    public function deliveryOrder(): HasOne
    {
        return $this->hasOne(DeliveryOrder::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function downpayment(): HasOne
    {
        return $this->hasOne(Downpayment::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
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

    // Accessors

    public function getProcessingFeeAttribute()
    {
        return $this->processing_fee_amount ?? 200.00;
    }

    // REPLACE net_price accessor with a computed property
    public function getNetPriceAttribute()
    {
        // Calculate net price on the fly instead of storing in database
        return $this->total_price - $this->processing_fee_amount;
    }

    public function getFormattedNetPriceAttribute()
    {
        return '₱' . number_format($this->net_price, 2);
    }

    public function getFormattedProcessingFeeAttribute()
    {
        return '₱' . number_format($this->processing_fee, 2);
    }

    public function getFormattedTotalPriceAttribute()
    {
        return '₱' . number_format($this->total_price, 2);
    }

    public function getHasProcessingFeeAttribute()
    {
        return $this->processing_fee_paid && $this->processing_fee_amount > 0;
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

    public function scopeWithProcessingFeePaid($query)
    {
        return $query->where('processing_fee_paid', true);
    }

    public function scopeWithProcessingFeeUnpaid($query)
    {
        return $query->where('processing_fee_paid', false);
    }

    public function scopeWithDownpayment($query)
    {
        return $query->whereHas('downpayment');
    }

    // Helper Methods
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

        // Update all packages to 'preparing'
        $this->packages()->update(['status' => 'preparing']);
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'payment_status' => $this->isPostpaid() ? 'awaiting_payment' : 'paid'
        ]);
    }

    public function isAwaitingPostpaidPayment(): bool
    {
        return $this->isPostpaid() && 
               $this->status === 'completed' && 
               $this->payment_status === 'awaiting_payment';
    }

    public function canPayOnline(): bool
    {
        // Prepaid can pay online before delivery
        if ($this->isPrepaid() && in_array($this->status, ['approved', 'pending_payment'])) {
            return true;
        }
        
        // Postpaid can pay online after delivery
        if ($this->isPostpaid() && in_array($this->status, ['completed', 'delivered']) && 
            in_array($this->payment_status, ['awaiting_payment', 'unpaid', 'pending'])) {
            return true;
        }
        
        return false;
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

    /**
     * Check if delivery request has been refunded
     */
    public function hasRefund(): bool
    {
        return $this->refunds()
            ->where('status', 'processed')
            ->exists();
    }

    /**
     * Get total refunded amount
     */
    public function totalRefundedAmount(): float
    {
        return $this->refunds()
            ->where('status', 'processed')
            ->sum('refund_amount');
    }

    /**
     * Check if processing fee has been paid
     */
    public function hasProcessingFeePaid(): bool
    {
        return $this->processing_fee_paid && $this->downpayment()->exists();
    }

    /**
     * Get the downpayment record
     */
    public function getDownpaymentAttribute()
    {
        return $this->downpayment()->first();
    }

    /**
     * Mark processing fee as paid
     */
    public function markProcessingFeeAsPaid(string $method, string $referenceNumber, string $receiptImage = null): Downpayment
    {
        $downpayment = $this->downpayment()->create([
            'amount' => $this->processing_fee_amount,
            'method' => $method,
            'reference_number' => $referenceNumber,
            'receipt_image' => $receiptImage,
            'paid_at' => now(),
            'status' => 'paid',
            'submitted_by_type' => get_class(auth()->user()),
            'submitted_by_id' => auth()->id(),
        ]);

        $this->update([
            'processing_fee_paid' => true,
        ]);

        return $downpayment;
    }

    // Recalculate total price method
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
        // REMOVE net_price assignment since column doesn't exist
        // $this->net_price = $total - $this->processing_fee_amount;
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
            'delivery_request_id',
            'package_id',
            'id',
            'id'
        );
    }

    /**
     * Get the final amount due (net price for processing fee paid requests)
     */
    public function getFinalAmountDueAttribute(): float
    {
        return $this->processing_fee_paid ? $this->net_price : $this->total_price;
    }

    /**
     * Get formatted final amount due
     */
    public function getFormattedFinalAmountDueAttribute(): string
    {
        return '₱' . number_format($this->final_amount_due, 2);
    }

    /**
     * Check if request can be processed (has processing fee paid)
     */
    public function canBeProcessed(): bool
    {
        return $this->processing_fee_paid && $this->status === 'pending';
    }

    /**
     * Get the original calculated price (before processing fee deduction)
     */
    public function getOriginalCalculatedPriceAttribute(): float
    {
        return $this->total_price + $this->processing_fee_amount;
    }
}