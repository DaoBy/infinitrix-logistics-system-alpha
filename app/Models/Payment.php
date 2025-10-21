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

        'reference_number',

        'source',

        'amount',

        'paid_at',

        'verified_by',

        'receipt_image',

        'notes',

        'collected_by',

        'collected_at',

        'submitted_by_type',

        'submitted_by_id',

        'status',

        'verified_at',

        'rejected_by',

        'rejected_at',

        'rejection_reason'

    ];



    protected $casts = [

        'paid_at' => 'datetime',

        'verified_at' => 'datetime',

        'rejected_at' => 'datetime',

        'collected_at' => 'datetime',

        'amount' => 'decimal:2',

    ];



    public const TYPES = ['prepaid', 'postpaid'];

    public const METHODS = ['cash', 'gcash', 'bank'];

    public const SOURCES = [

        'branch_staff', 

        'collector', 

        'customer_online',

        'customer_online_postpaid'

    ];

    public const STATUSES = [

        'pending_verification',

        'verified', 

        'rejected',

        'cancelled',

        'uncollectible'

    ];

    

    // Add this method to ensure data completeness

    public function ensureCompleteData(): void

    {

        $updates = [];



        // Ensure delivery_order_id is set

        if (empty($this->delivery_order_id) && $this->deliveryRequest && $this->deliveryRequest->deliveryOrder) {

            $updates['delivery_order_id'] = $this->deliveryRequest->deliveryOrder->id;

        }



        // Ensure submitted_by fields for collector payments

        if ($this->source === 'collector' && empty($this->submitted_by_type) && $this->collected_by) {

            $updates['submitted_by_type'] = get_class($this->collectedBy);

            $updates['submitted_by_id'] = $this->collected_by;

        }



        // Ensure reference number for cash collector payments

        if ($this->source === 'collector' && $this->method === 'cash' && empty($this->reference_number)) {

            $updates['reference_number'] = 'CASH-' . now()->format('Ymd-His') . '-' . $this->id;

        }



        // Save updates if any

        if (!empty($updates)) {

            $this->update($updates);

        }

    }



    // Keep all your existing relationships and methods below...

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



    public function requiresReference(): bool

    {

        return in_array($this->method, ['gcash', 'bank']);

    }

}