<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Waybill extends Model
{
    protected $fillable = [
        'delivery_request_id',
        'generated_by',
        'waybill_number',
        'file_path',
        'notes'
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

public function deliveryRequest(): \Illuminate\Database\Eloquent\Relations\BelongsTo
{
    return $this->belongsTo(DeliveryRequest::class);
}

public function generator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
{
    return $this->belongsTo(User::class, 'generated_by');
}
}
