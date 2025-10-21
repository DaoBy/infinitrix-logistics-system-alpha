<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverStatusLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'driver_truck_assignment_id',
        'previous_status',
        'new_status',
        'remarks',
        'changed_at'
    ];

    protected $casts = [
        'changed_at' => 'datetime'
    ];

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(DriverTruckAssignment::class, 'driver_truck_assignment_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }
}