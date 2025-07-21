<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PriceMatrix extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'base_fee',
        'volume_rate',
        'weight_rate',
        'package_rate',
    ];
    
    protected $casts = [
        'base_fee' => 'decimal:2',
        'volume_rate' => 'decimal:4',
        'weight_rate' => 'decimal:2',
        'package_rate' => 'decimal:2',
    ];
    
    protected $table = 'price_matrix';
}