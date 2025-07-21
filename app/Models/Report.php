<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
 protected $fillable = [
    'type',
    'generated_by',
    'parameters',
    'file_path' 
];

    protected $casts = [
        'parameters' => 'array'
    ];

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
