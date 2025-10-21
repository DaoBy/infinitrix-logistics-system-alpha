<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerProfileAudit extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'changed_by',
        'change_type',
        'field_name',
        'old_value',
        'new_value',
        'change_reason',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    const CHANGE_TYPE_CUSTOMER_UPDATE = 'customer_update';
    const CHANGE_TYPE_ADMIN_UPDATE = 'admin_update';
    const CHANGE_TYPE_AUTO_LOCKED = 'auto_locked';
    const CHANGE_TYPE_APPROVED_REQUEST = 'approved_request';

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public static function logChange(
        Customer $customer,
        int $changedBy,
        string $changeType,
        array $changes,
        string $reason = null
    ): void {
        foreach ($changes as $field => $values) {
            self::create([
                'customer_id' => $customer->id,
                'changed_by' => $changedBy,
                'change_type' => $changeType,
                'field_name' => $field,
                'old_value' => $values['old'] ?? null,
                'new_value' => $values['new'] ?? null,
                'change_reason' => $reason,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        }
    }
}