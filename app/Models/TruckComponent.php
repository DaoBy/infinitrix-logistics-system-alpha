<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TruckComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'truck_id',
        'name',
        'type',
        'serial_number',
        'installation_date',
        'last_maintenance_date',
        'condition',
        'notes',
    ];

    protected $casts = [
        'installation_date' => 'date',
        'last_maintenance_date' => 'date',
    ];

    public static function getTypes(): array
    {
        return [
            'engine' => 'Engine',
            'transmission' => 'Transmission',
            'brakes' => 'Brakes',
            'tires' => 'Tires',
            'battery' => 'Battery',
            'electrical' => 'Electrical System',
            'suspension' => 'Suspension',
            'exhaust' => 'Exhaust System',
            'other' => 'Other',
        ];
    }

    public static function getConditions(): array
    {
        return [
            'new' => 'New',
            'good' => 'Good',
            'fair' => 'Fair',
            'poor' => 'Poor',
            'needs_replacement' => 'Needs Replacement',
        ];
    }

    public function truck(): BelongsTo
    {
        return $this->belongsTo(Truck::class);
    }

   public function maintenanceRecords()
{
    return $this->hasMany(TruckMaintenance::class, 'component_id', 'id');
}
}