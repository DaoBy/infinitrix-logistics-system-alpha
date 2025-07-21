<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TruckMaintenance extends Model
{
protected $fillable = [
    'truck_id',
    'component_id', 
    'maintenance_date',
    'service_details',
    'cost',
    'service_provider',
    'notes',
    'type',
];

    protected $casts = [
        'maintenance_date' => 'date',
        'cost' => 'decimal:2',
    ];

    public function truck(): BelongsTo
    {
        return $this->belongsTo(Truck::class);
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(TruckComponent::class);
    }
}