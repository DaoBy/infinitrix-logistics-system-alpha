<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'collector_id',
        'status',
        'verification_proof',
        'notes'
    ];

    protected $casts = [
        'verified_at' => 'datetime'
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collector_id');
    }

    public function markAsVerified(): void
    {
        $this->status = 'verified';
        $this->verified_at = now();
        $this->save();
    }
}