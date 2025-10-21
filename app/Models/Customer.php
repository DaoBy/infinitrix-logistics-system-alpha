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
        'is_profile_complete',
        'has_delivery_history',
        'critical_fields_locked',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'archived_at' => 'datetime',
        'is_profile_complete' => 'boolean',
        'has_delivery_history' => 'boolean',
        'critical_fields_locked' => 'boolean',
    ];

    protected $appends = ['name', 'address', 'completed_deliveries_count'];

    // Critical fields that should be locked for customers with delivery history
    const CRITICAL_FIELDS = [
        'first_name', 'last_name', 'mobile',
        'building_number', 'street', 'barangay', 'city', 'province', 'zip_code'
    ];

    // Check if customer has ANY delivery history (past or present)
    public function hasDeliveryHistory(): bool
    {
        if ($this->has_delivery_history !== null) {
            return $this->has_delivery_history;
        }

        $hasHistory = $this->sentDeliveries()->exists() || 
                     $this->receivedDeliveries()->exists();

        $this->update(['has_delivery_history' => $hasHistory]);
        
        return $hasHistory;
    }

    // FIXED: Check if customer has active/incomplete deliveries OR unpaid payments
    public function hasActiveOrUnpaidDeliveries(): bool
    {
        // Check for active delivery requests (not completed/cancelled/rejected)
        $hasActiveRequests = $this->sentDeliveries()
            ->whereNotIn('status', ['completed', 'cancelled', 'rejected'])
            ->exists();

        if ($hasActiveRequests) {
            return true;
        }

        // FIXED: Check for unpaid payments - only consider deliveries that are completed/delivered
        $hasUnpaidPayments = $this->sentDeliveries()
            ->where(function($query) {
                $query->where('payment_status', '!=', 'paid')
                      ->orWhere('payment_verified', false);
            })
            ->whereIn('status', ['completed', 'delivered']) // Only completed/delivered deliveries
            ->exists();

        return $hasUnpaidPayments;
    }

    // FIXED: Check if critical fields are locked
    public function areCriticalFieldsLocked(): bool
    {
        // Fields are ONLY locked if customer has active/unpaid deliveries
        // NOT just because they have delivery history
        return $this->hasActiveOrUnpaidDeliveries();
    }

    // FIXED: Check if customer can request changes (no active/unpaid deliveries)
    public function canRequestChanges(): bool
    {
        return !$this->hasActiveOrUnpaidDeliveries();
    }

    // FIXED: Check if a specific field is locked
    public function isFieldLocked(string $field): bool
    {
        if (!$this->areCriticalFieldsLocked()) {
            return false;
        }

        return in_array($field, self::CRITICAL_FIELDS);
    }

    // FIXED: Get editable fields based on customer status
    public function getEditableFields(): array
    {
        // If no active/unpaid deliveries, all fields are requestable
        // (direct edit if no history, approval request if has history)
        if (!$this->hasActiveOrUnpaidDeliveries()) {
            return self::CRITICAL_FIELDS;
        }

        // If active/unpaid deliveries, no fields are editable
        return [];
    }

    // FIXED: Get active delivery requests count
    public function getActiveDeliveriesCount(): int
    {
        return $this->sentDeliveries()
            ->whereNotIn('status', ['completed', 'cancelled', 'rejected'])
            ->count();
    }

    // FIXED: Get unpaid delivery requests count
    public function getUnpaidDeliveriesCount(): int
    {
        return $this->sentDeliveries()
            ->where(function($query) {
                $query->where('payment_status', '!=', 'paid')
                      ->orWhere('payment_verified', false);
            })
            ->whereIn('status', ['completed', 'delivered']) // Only completed/delivered
            ->count();
    }

    // FIXED: Update delivery status
    public function checkAndUpdateDeliveryStatus(): void
    {
        $hasHistory = $this->hasDeliveryHistory();
        $hasActiveOrUnpaid = $this->hasActiveOrUnpaidDeliveries();

        $this->update([
            'has_delivery_history' => $hasHistory,
            'critical_fields_locked' => $hasActiveOrUnpaid // Only lock if active/unpaid
        ]);
    }

    // Unlock critical fields (admin only)
    public function unlockCriticalFields(): void
    {
        $this->update(['critical_fields_locked' => false]);
    }

    // Lock critical fields
    public function lockCriticalFields(): void
    {
        $this->update(['critical_fields_locked' => true]);
    }

    // Rest of your existing methods...
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

    // NEW: Check if customer can edit directly (no delivery history)
    public function canEditDirectly(): bool
    {
        return !$this->hasDeliveryHistory() && !$this->hasActiveOrUnpaidDeliveries();
    }

    // NEW: Check if customer needs approval (has history but no active/unpaid)
    public function needsApprovalForChanges(): bool
    {
        return $this->hasDeliveryHistory() && !$this->hasActiveOrUnpaidDeliveries();
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

    public function updateRequests(): HasMany
    {
        return $this->hasMany(CustomerUpdateRequest::class);
    }

    public function profileAuditLogs(): HasMany
    {
        return $this->hasMany(CustomerProfileAudit::class);
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

    public function scopeProfileComplete($query)
    {
        return $query->where('is_profile_complete', true);
    }

    public function scopeProfileIncomplete($query)
    {
        return $query->where('is_profile_complete', false);
    }

    public function scopeWithLockedFields($query)
    {
        return $query->where('critical_fields_locked', true);
    }

    public function scopeWithUnlockedFields($query)
    {
        return $query->where('critical_fields_locked', false);
    }

    // Boot method to auto-detect delivery history
    protected static function boot()
    {
        parent::boot();

        // Auto-detect delivery history when customer is retrieved
        static::retrieved(function ($customer) {
            $customer->checkAndUpdateDeliveryStatus();
        });
    }
}