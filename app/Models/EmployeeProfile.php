<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'employee_id',
        'phone',
        'mobile',
        'building_number',
        'street',
        'barangay',
        'city',
        'province',
        'zip_code',
        'hire_date',
        'termination_date',
        'archived_at',
        'notes',
        'region_id'
    ];

  protected $casts = [
    'hire_date' => 'date:Y-m-d',
    'termination_date' => 'date:Y-m-d',
    'archived_at' => 'datetime',
];

    public function user()
    {
        // Make sure this is the correct relationship and foreign key
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function region()
    {
        return $this->belongsTo(Region::class, 'region_id');
    }

    public function scopeActive($query)
    {
        return $query->whereNull('archived_at');
    }

    public function getFullAddressAttribute()
    {
        $parts = [
            $this->building_number,
            $this->street,
            $this->barangay,
            $this->city,
            $this->province,
            $this->zip_code
        ];
        
        return implode(', ', array_filter($parts));
    }

    public function updateEmployeeId(): void
    {
        $user = $this->user;
        
        if (!$user) {
            throw new \Exception("User must be set before updating EmployeeProfile.");
        }

        $roleAbbr = match($user->role) {
            'admin' => 'ADM',
            'staff' => 'STA',
            'driver' => 'DRI',
            'collector' => 'COL',
            default => 'EMP',
        };

        $prefix = "EMP-{$roleAbbr}-";

        $currentNumber = $this->employee_id ? 
            (int) substr($this->employee_id, strrpos($this->employee_id, '-') + 1) : 
            0;

        if ($currentNumber > 0) {
            $this->employee_id = $prefix . sprintf('%04d', $currentNumber);
            return;
        }

        $existingIds = self::where('employee_id', 'like', $prefix . '%')
            ->pluck('employee_id')
            ->toArray();

        $nextNumber = 1;
        if (!empty($existingIds)) {
            $numbers = array_map(function($id) {
                return (int) substr($id, strrpos($id, '-') + 1);
            }, $existingIds);
            $nextNumber = max($numbers) + 1;
        }

        $this->employee_id = $prefix . sprintf('%04d', $nextNumber);
    }

    protected static function booted()
    {
        static::creating(function ($profile) {
            if (empty($profile->employee_id)) {
                $user = $profile->user ?? $profile->user()->first();
                
                if (!$user) {
                    throw new \Exception("User must be set before creating an EmployeeProfile.");
                }

                $roleAbbr = match($user->role) {
                    'admin' => 'ADM',
                    'staff' => 'STA',
                    'driver' => 'DRI',
                    'collector' => 'COL',
                    default => 'EMP',
                };

                $prefix = "EMP-{$roleAbbr}-";

                $existingIds = self::where('employee_id', 'like', $prefix . '%')
                    ->pluck('employee_id')
                    ->toArray();

                $nextNumber = 1;
                if (!empty($existingIds)) {
                    $numbers = array_map(function($id) {
                        return (int) substr($id, strrpos($id, '-') + 1);
                    }, $existingIds);
                    $nextNumber = max($numbers) + 1;
                }

                $profile->employee_id = $prefix . sprintf('%04d', $nextNumber);
            }
        });
    }
}