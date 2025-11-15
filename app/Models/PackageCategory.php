<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackageCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'image',
        'dimensions',
        'is_active',
        'sort_order'
    ];

    protected $casts = [
        'dimensions' => 'array',
        'is_active' => 'boolean'
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

   // In PackageCategory.php model
public function getImageUrlAttribute()
{
    if (!$this->image) {
        return null;
    }
    
    // If image path starts with 'images/' it's a public image
    if (str_starts_with($this->image, 'images/')) {
        return asset($this->image);
    }
    
    // Otherwise it's a storage image
    return asset('storage/' . $this->image);
}

    public function getFormattedDimensionsAttribute()
    {
        if (!$this->dimensions || !isset($this->dimensions['length'])) {
            return null;
        }

        return "L {$this->dimensions['length']}cm × H {$this->dimensions['height']}cm × W {$this->dimensions['width']}cm";
    }
}