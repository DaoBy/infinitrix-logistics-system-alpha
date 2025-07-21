<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DriverTruckAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'driver_id',
        'truck_id',
        'region_id',
        'is_active',
        'assigned_at',
        'unassigned_at'
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'unassigned_at' => 'datetime',
        'is_active' => 'boolean'
    ];

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id')->with('employeeProfile');
    }

    public function truck()
    {
        return $this->belongsTo(Truck::class, 'truck_id');
    }

    public function region()
    {
        return $this->belongsTo(Region::class, 'region_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function deactivate()
    {
        if ($this->is_active) {
            $this->update([
                'is_active' => false,
                'unassigned_at' => now()
            ]);

            // Optional: Update related truck status
            if ($this->truck) {
                $this->truck()->update(['status' => \App\Models\Truck::STATUS_AVAILABLE]);
            }
        }
    }
}