<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryConfirmation extends Model
{
    use HasFactory;

protected $fillable = [
    'package_id',
    'region_id',
    'confirmed_by',
    'confirmed_at',
    'notes'
];

protected $casts = [
    'confirmed_at' => 'datetime'
];

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}