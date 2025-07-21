<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Region extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'warehouse_address',
        'latitude',
        'longitude',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float'
    ];

    protected $appends = ['geographic_location', 'address'];


    public function getAddressAttribute(): ?string
    {
        return $this->warehouse_address ?: null;
    }

    // Relationships
    public function deliveryRequestsFrom(): HasMany
    {
        return $this->hasMany(DeliveryRequest::class, 'pick_up_region_id');
    }

    public function deliveryRequestsTo(): HasMany
    {
        return $this->hasMany(DeliveryRequest::class, 'drop_off_region_id');
    }

    public function packages(): HasMany
    {
        return $this->hasMany(Package::class, 'current_region_id');
    }

    public function activeDrivers(): HasMany
    {
        return $this->hasMany(User::class, 'current_region_id')
            ->where('role', 'driver')
            ->where('is_active', true);
    }

    public function activeDeliveryOrders(): HasMany
    {
        return $this->hasMany(DeliveryOrder::class, 'current_region_id')
            ->whereIn('status', ['assigned', 'dispatched', 'in_transit']);
    }

    // Accessors
    public function getGeographicLocationAttribute(): array
    {
        return [
            'latitude' => $this->latitude,
            'longitude' => $this->longitude
        ];
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Keep your custom toArray if needed
    public function toArray()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'warehouse_address' => $this->warehouse_address,
            'geographic_location' => $this->geographic_location,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at ? $this->created_at->toISOString() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toISOString() : null
        ];
    }
}