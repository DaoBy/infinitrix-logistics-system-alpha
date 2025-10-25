<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Manifest extends Model
{
    use HasFactory;

    protected $fillable = [
        'manifest_number',
        'truck_id',
        'driver_id',
        'status',
        'package_ids',
        'generated_by',
        'manifest_pdf_path',
        'notes',
        'archived_at' // ✅ ADDED
    ];

    protected $casts = [
        'package_ids' => 'array',
        'archived_at' => 'datetime', // ✅ ADDED
    ];

    // Relationships
    public function truck(): BelongsTo
    {
        return $this->belongsTo(Truck::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    // Accessor for clean short label (optional ✨)
    public function getShortLabelAttribute(): string
    {
        return Str::replace('MNF-', '', $this->manifest_number);
    }

    // Example: Retrieve package models (⚠️ only if you want to resolve full models later)
    public function getPackages()
    {
        return Package::whereIn('id', $this->package_ids)->get();
    }

    // ✅ ADDED: Scope for non-archived records
    public function scopeActive($query)
    {
        return $query->whereNull('archived_at');
    }

    // ✅ ADDED: Scope for archived records
    public function scopeArchived($query)
    {
        return $query->whereNotNull('archived_at');
    }
}