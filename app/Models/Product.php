<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $table = 'product';

    protected $fillable = ['name', 'image_id', 'brand_id', 'category_id', 'description', 'price', 'is_active'];

    protected $casts = ['price' => 'decimal:2', 'is_active' => 'boolean'];

    /** Archived products are hidden from New Sale and stock lists but keep all their history. */
    public function scopeActive($query)
    {
        return $query->where('product.is_active', true);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function image(): BelongsTo
    {
        return $this->belongsTo(Image::class);
    }

    /** Link to the photo kept in the database (null = no photo, the placeholder is shown). */
    public function getImageUrlAttribute(): ?string
    {
        return $this->image_id ? route('images.show', $this->image_id) : null;
    }

    /**
     * Product without a photo: a black-and-white line drawing matching its category (slippers, cap, apparel...) and a short tooltip that says
     * which product it is, e.g. "Slippers - Edge: no photo uploaded yet".
     */
    public function getPlaceholderAttribute(): array
    {
        $kind = trim((string) $this->category?->name);
        $name = trim((string) $this->name);

        return [
            'icon' => \App\Support\PlaceholderIcon::svg($kind),   // black-and-white line drawing of that kind of product
            'tip' => ($kind !== '' ? $kind . ' - ' : '') . ($name !== '' ? $name . ': ' : '') . 'no photo uploaded yet',
        ];
    }

    public function getTotalStockAttribute(): int
    {
        return (int) $this->variants->sum('quantity');
    }
}
