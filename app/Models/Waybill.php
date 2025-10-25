<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Waybill extends Model
{
    protected $fillable = [
        'delivery_request_id',
        'generated_by',
        'waybill_number',
        'file_path',
        'notes',
        'archived_at' // ✅ ADDED
    ];

    protected $casts = [
        'archived_at' => 'datetime', // ✅ ADDED
    ];

    public function deliveryOrder()
    {
        return $this->hasOneThrough(
            DeliveryOrder::class,
            DeliveryRequest::class,
            'id', // Foreign key on DeliveryRequest table
            'delivery_request_id', // Foreign key on DeliveryOrder table
            'delivery_request_id', // Local key on Waybill table
            'id' // Local key on DeliveryRequest table
        );
    }

    public function deliveryRequest(): BelongsTo
    {
        return $this->belongsTo(DeliveryRequest::class);
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    // ✅ ADDED: Scope for non-archived records
    public function scopeActive($query)
    {
        return $query->whereNull('archived_at');
    }

    // ✅ ADDED: Scope for archived records
    public function scopeArchived($query)
    {
        return $query->whereNotNull('archived_at');
    }
}