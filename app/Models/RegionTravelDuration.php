<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegionTravelDuration extends Model
{
    use HasFactory;

    protected $fillable = [
        'from_region_id',
        'to_region_id',
        'estimated_minutes'
    ];

    public function fromRegion()
    {
        return $this->belongsTo(Region::class, 'from_region_id');
    }

    public function toRegion()
    {
        return $this->belongsTo(Region::class, 'to_region_id');
    }
}
