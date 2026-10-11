<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    protected $fillable = ['name', 'category', 'brand', 'description', 'image_path', 'price', 'is_active'];

    protected $casts = ['price' => 'decimal:2', 'is_active' => 'boolean'];

    /** Archived products are hidden from New Sale and stock lists but keep all their history. */
    public function scopeActive($query)
    {
        return $query->where('products.is_active', true);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }

    public function getTotalStockAttribute(): int
    {
        return (int) $this->variants->sum('quantity');
    }
}
