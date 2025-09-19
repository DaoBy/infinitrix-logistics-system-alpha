<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerUpdateRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'mobile',
        'phone',
        'building_number',
        'street',
        'barangay',
        'city',
        'province',
        'zip_code',
        'reason',
        'status',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // Check if any profile field has been changed
    public function hasChanges($changes = null, $attributes = null): bool
    {
        $profileFields = [
            'first_name', 'middle_name', 'last_name', 'email', 'mobile', 'phone',
            'building_number', 'street', 'barangay', 'city', 'province', 'zip_code'
        ];

        foreach ($profileFields as $field) {
            if (!is_null($this->$field)) {
                return true;
            }
        }

        return false;
    }

    // Get the changed fields
    public function getChangedFields(): array
    {
        $changed = [];
        $profileFields = [
            'first_name', 'middle_name', 'last_name', 'email', 'mobile', 'phone',
            'building_number', 'street', 'barangay', 'city', 'province', 'zip_code'
        ];

        foreach ($profileFields as $field) {
            if (!is_null($this->$field)) {
                $changed[] = $field;
            }
        }

        return $changed;
    }
}