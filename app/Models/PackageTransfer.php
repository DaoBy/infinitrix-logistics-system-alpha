<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackageTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'package_id',
        'from_region_id',
        'to_region_id',
        'processed_by',
        'transferred_at',
        'arrived_at',
        'received_by', 
        'is_return',
        'remarks'
    ];

    protected $casts = [
        'transferred_at' => 'datetime',
        'arrived_at' => 'datetime',
        'is_return' => 'boolean',
    ];

public function receiver(): BelongsTo
{
    return $this->belongsTo(User::class, 'received_by');
}


    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function fromRegion(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'from_region_id');
    }

    public function toRegion(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'to_region_id');
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

public function markAsArrived(User $receiver): void
{
    $this->arrived_at = now();
    $this->received_by = $receiver->id;
    $this->save();

    $this->package->updateStatus('delivered', $receiver, 'Package arrived at destination', $this);
}
    protected static function booted()
{
    static::creating(function ($transfer) {
        $transfer->transferred_at ??= now(); // Auto-fill if not set
    });
}


}