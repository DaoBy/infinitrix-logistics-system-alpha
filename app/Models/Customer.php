<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
         'user_id',
        'first_name',
        'middle_name',
        'last_name',
        'company_name',
        'contact_person',
        'email',
        'mobile',
        'phone',
        'building_number',
        'street',
        'barangay',
        'city',
        'province',
        'zip_code',
        'customer_category',
        'frequency_type',
        'notes',
        'archived_at',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'archived_at' => 'datetime',
    ];

protected $appends = ['name', 'address', 'completed_deliveries_count'];

public function getAddressAttribute(): string
{
    $parts = array_filter([
        $this->building_number,
        $this->street,
        $this->barangay,
        $this->city,
        $this->province,
        $this->zip_code,
    ]);

    return count($parts) ? implode(', ', $parts) : 'N/A';
}

    // Name accessor
    public function getNameAttribute()
    {
        if ($this->company_name) {
            return $this->company_name;
        }
        return trim(implode(' ', array_filter([
            $this->first_name,
            $this->middle_name,
            $this->last_name
        ])));
    }

    public function getCompletedDeliveriesCountAttribute()
    {
        return $this->deliveryRequestsSent()
            ->where('status', 'completed')
            ->count();
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sentDeliveries(): HasMany
    {
        return $this->hasMany(DeliveryRequest::class, 'sender_id');
    }

    public function receivedDeliveries(): HasMany
    {
        return $this->hasMany(DeliveryRequest::class, 'receiver_id');
    }

    public function deliveryRequestsSent()
    {
        return $this->hasMany(\App\Models\DeliveryRequest::class, 'sender_id');
    }

    // Scopes

    public function scopeIndividuals($query)
    {
        return $query->where('customer_category', 'individual');
    }

    public function scopeCompanies($query)
    {
        return $query->where('customer_category', 'company');
    }
}