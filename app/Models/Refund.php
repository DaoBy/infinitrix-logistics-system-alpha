<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Refund extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_request_id',
        'processed_by',
        'refund_amount',
        'original_amount',
        'adjusted_amount', // NEW: for postpaid adjustments
        'reason',
        'description',
        'refunded_packages',
        'type', // NEW: refund or adjustment
        'status',
        'processed_at',
        'notes'
    ];

    protected $appends = [
    'reason_label',
    'status_label', 
    'type_label'
];

    protected $casts = [
        'refund_amount' => 'decimal:2',
        'original_amount' => 'decimal:2',
        'adjusted_amount' => 'decimal:2', // NEW
        'refunded_packages' => 'array',
        'processed_at' => 'datetime',
    ];

    // Reason labels for display
    public const REASONS = [
        'damaged' => 'Package Damaged',
        'lost' => 'Package Lost',
        'delayed' => 'Delivery Delayed',
        'incomplete' => 'Incomplete Delivery',
        'customer_request' => 'Customer Request',
        'wrong_delivery' => 'Wrong Delivery Address',
        'other' => 'Other Reason'
    ];

    // NEW: Type options
    public const TYPES = [
        'refund' => 'Refund',
        'adjustment' => 'Invoice Adjustment'
    ];

    // UPDATED: Status options
    public const STATUSES = [
        'pending' => 'Pending Negotiation',
        'pending_adjustment' => 'Pending Adjustment', // NEW
        'processed' => 'Processed',
        'adjusted' => 'Adjusted' // NEW
    ];

    // Relationships
    public function deliveryRequest(): BelongsTo
    {
        return $this->belongsTo(DeliveryRequest::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    // Accessors
    public function getReasonLabelAttribute(): string
    {
        return self::REASONS[$this->reason] ?? $this->reason;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    // NEW: Type label accessor
    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function getRefundedPackagesListAttribute(): array
    {
        if (!$this->refunded_packages) {
            return [];
        }

        return Package::whereIn('id', $this->refunded_packages)
            ->get()
            ->map(function($package) {
                return [
                    'id' => $package->id,
                    'item_name' => $package->item_name,
                    'value' => $package->value,
                    'status' => $package->status,
                ];
            })
            ->toArray();
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->whereIn('status', ['pending', 'pending_adjustment']);
    }

    public function scopeProcessed($query)
    {
        return $query->whereIn('status', ['processed', 'adjusted']);
    }

    public function scopeRefunds($query)
    {
        return $query->where('type', 'refund');
    }

    public function scopeAdjustments($query)
    {
        return $query->where('type', 'adjustment');
    }

    public function scopeThisMonth($query)
    {
        return $query->whereBetween('created_at', [
            now()->startOfMonth(),
            now()->endOfMonth()
        ]);
    }

    public function scopeFilter($query, array $filters)
{
    $query->when($filters['search'] ?? null, function ($query, $search) {
        $query->where(function ($query) use ($search) {
            $query->whereHas('deliveryRequest', function ($query) use ($search) {
                $query->where('reference_number', 'like', '%'.$search.'%')
                      ->orWhereHas('sender', function ($query) use ($search) {
                          $q->where('name', 'like', '%'.$search.'%');
                      });
            });
        });
    })->when($filters['status'] ?? null, function ($query, $status) {
        $query->where('status', $status);
    })->when($filters['reason'] ?? null, function ($query, $reason) {
        $query->where('reason', $reason);
    })->when($filters['type'] ?? null, function ($query, $type) {
        $query->where('type', $type); // This line should exist
    });
}

    public function calculateMaxRefundableAmount(): float
    {
        $deliveryRequest = $this->deliveryRequest;
        
        if (!$this->refunded_packages) {
            // No specific packages = assume complete failure
            $packageValue = $deliveryRequest->packages()->sum('value');
            return $deliveryRequest->total_price + $packageValue;
        }

        // Calculate based on specific packages
        $packageValue = Package::whereIn('id', $this->refunded_packages)->sum('value');
        
        // Check if this is a complete failure (all packages affected)
        $totalPackages = $deliveryRequest->packages()->count();
        $affectedPackages = count($this->refunded_packages);
        
        if ($affectedPackages === $totalPackages) {
            // Complete failure: delivery cost + package values
            return $deliveryRequest->total_price + $packageValue;
        } else {
            // Partial failure: only package values
            return $packageValue;
        }
    }

    // Methods
    public function markAsProcessed(): void
    {
        DB::transaction(function () {
            $this->update([
                'status' => 'processed',
                'processed_at' => now(),
            ]);

            // Update delivery request payment status to refunded
            $this->deliveryRequest->update([
                'payment_status' => 'refunded',
            ]);

            // Release undamaged packages after refund processing
            $undamagedPackages = $this->deliveryRequest->packages()
                ->whereNotIn('id', $this->refunded_packages ?? [])
                ->whereNotIn('status', ['damaged_in_transit', 'lost_in_transit'])
                ->get();
                
            foreach ($undamagedPackages as $package) {
                $package->updateStatus('completed', auth()->user(), "Released after refund processing");
            }

            // Finally complete the delivery order
            $deliveryOrder = $this->deliveryRequest->deliveryOrder;
            if ($deliveryOrder) {
                $deliveryOrder->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'completed_by' => auth()->id(),
                ]);
                
                // Also mark delivery request as completed
                $this->deliveryRequest->update(['status' => 'completed']);
            }
        });
    }

    // NEW: Method for processing adjustments
    public function markAsAdjusted(): void
    {
        DB::transaction(function () {
            $deliveryRequest = $this->deliveryRequest;
            
            // Calculate new amount due
            $newAmountDue = $deliveryRequest->total_price - $this->refund_amount;
            
            if ($newAmountDue < 0) {
                throw new \Exception("Adjustment would result in negative amount due.");
            }

            $this->update([
                'status' => 'adjusted',
                'adjusted_amount' => $newAmountDue,
                'processed_at' => now(),
            ]);

            // Update delivery request with adjusted amount
            $paymentStatus = $newAmountDue > 0 ? 'unpaid' : 'paid';
            
            $deliveryRequest->update([
                'total_price' => $newAmountDue,
                'payment_status' => $paymentStatus,
                'status' => 'completed',
            ]);

            // Release undamaged packages after adjustment processing
            $undamagedPackages = $deliveryRequest->packages()
                ->whereNotIn('id', $this->refunded_packages ?? [])
                ->whereNotIn('status', ['damaged_in_transit', 'lost_in_transit'])
                ->get();
                
            foreach ($undamagedPackages as $package) {
                $package->updateStatus('completed', auth()->user(), "Released after invoice adjustment");
            }

            // Complete the delivery order
            $deliveryOrder = $deliveryRequest->deliveryOrder;
            if ($deliveryOrder) {
                $deliveryOrder->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'completed_by' => auth()->id(),
                ]);
            }
        });
    }

    public static function rules(): array
    {
        return [
            'delivery_request_id' => 'required|exists:delivery_requests,id',
            'refund_amount' => 'required|numeric|min:0.01',
            'reason' => 'required|in:damaged,lost,delayed,incomplete,customer_request,wrong_delivery,other',
            'description' => 'required|string|min:10|max:1000',
            'refunded_packages' => 'nullable|array',
            'refunded_packages.*' => 'exists:packages,id',
            'notes' => 'nullable|string|max:500',
        ];
    }
}