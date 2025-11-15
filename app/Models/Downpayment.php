<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Downpayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_request_id',
        'amount',
        'method',
        'reference_number',
        'receipt_image',
        'paid_at',
        'status',
        'submitted_by_type',
        'submitted_by_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function deliveryRequest()
    {
        return $this->belongsTo(DeliveryRequest::class);
    }

    public function submittedBy()
    {
        return $this->morphTo();
    }

    /**
     * Accessors
     */
    public function getReceiptImageUrlAttribute()
    {
        return $this->receipt_image ? Storage::url($this->receipt_image) : null;
    }

    public function getFormattedAmountAttribute()
    {
        return '₱' . number_format($this->amount, 2);
    }

    public function getMethodNameAttribute()
    {
        return strtoupper($this->method);
    }

    /**
     * Scopes
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeByMethod($query, $method)
    {
        return $query->where('method', $method);
    }
}