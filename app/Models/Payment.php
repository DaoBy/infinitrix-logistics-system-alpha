<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_request_id',
        'delivery_order_id',
        'type',
        'method',
        'reference_number', // Added
        'source', // Added
        'amount',
        'paid_at',
        'verified_by',
        'receipt_image',
        'notes',
        'collected_by',
        'collected_at',
        'submitted_by_type', // Added
        'submitted_by_id', // Added
        'status',          // Add this
        'verified_at',     // Add this
        'rejected_by',     // ADD THIS - missing field
        'rejected_at',     // ADD THIS - missing field
        'rejection_reason' // ADD THIS - missing field
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'verified_at' => 'datetime',
        'rejected_at' => 'datetime', // Add this
        'collected_at' => 'datetime', // Add this
        'amount' => 'decimal:2',
    ];

    public const TYPES = ['prepaid', 'postpaid'];
    public const METHODS = ['cash', 'gcash', 'bank'];
    public const SOURCES = [
        'branch_staff', 
        'collector', 
        'customer_online',
        'customer_online_postpaid' // Add this new source
    ];
    public const STATUSES = [
        'pending_verification',
        'verified',
        'rejected',
        'cancelled'
    ];
    
    // Relationships
    public function deliveryRequest(): BelongsTo
    {
        return $this->belongsTo(DeliveryRequest::class);
    }

    public function deliveryOrder(): BelongsTo
    {
        return $this->belongsTo(DeliveryOrder::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function collectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by');
    }

    // New relationship for submitter (could be User or Customer)
    public function submittedBy(): MorphTo
    {
        return $this->morphTo();
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function isVerified(): bool
    {
        return $this->verified_by !== null;
    }

    // Helper method to check if reference number is required
    public function requiresReference(): bool
    {
        return in_array($this->method, ['gcash', 'bank']);
    }
}