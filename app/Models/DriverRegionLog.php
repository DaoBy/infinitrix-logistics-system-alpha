<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverRegionLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'driver_id',
        'region_id',
        'delivery_order_id',
        'type',
        'logged_at'
    ];

    protected $casts = [
        'logged_at' => 'datetime'
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function deliveryOrder(): BelongsTo
    {
        return $this->belongsTo(DeliveryOrder::class);
    }

    // Add this relationship for compatibility with code expecting fromRegion
    public function fromRegion(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'region_id');
    }
}