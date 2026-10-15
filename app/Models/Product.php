<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = ['name', 'image_id', 'brand_id', 'category_id', 'description', 'price', 'is_active'];

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
     * What to show when the product has no photo: a generic icon for its kind of product
     * (slippers, bag, tent...) and a tooltip that says what it is. Category is checked first, then the model name.
     */
    public const PLACEHOLDERS = [
        ['icon' => '🩴', 'label' => 'Slippers / sandals', 'words' => ['slipper', 'slippers', 'sandal', 'sandals', 'flip flop', 'flip-flop', 'slide', 'slides']],
        ['icon' => '🥾', 'label' => 'Footwear',           'words' => ['shoe', 'shoes', 'boot', 'boots', 'sneaker', 'sneakers', 'footwear']],
        ['icon' => '🛏️', 'label' => 'Sleeping gear',      'words' => ['sleeping', 'mat', 'mattress', 'pillow', 'bedroll']],
        ['icon' => '🎒', 'label' => 'Bag / backpack',     'words' => ['backpack', 'backpacks', 'bag', 'bags', 'pack', 'duffel', 'sack']],
        ['icon' => '⛺', 'label' => 'Tent / shelter',     'words' => ['tent', 'tents', 'shelter', 'tarp', 'hammock', 'canopy']],
        ['icon' => '🧥', 'label' => 'Jacket / outerwear', 'words' => ['jacket', 'jackets', 'raincoat', 'poncho', 'windbreaker', 'fleece', 'hoodie', 'coat']],
        ['icon' => '👕', 'label' => 'Apparel',            'words' => ['shirt', 'shirts', 'tee', 'jersey', 'shorts', 'pants', 'trousers', 'apparel', 'clothing']],
        ['icon' => '🧢', 'label' => 'Headwear',           'words' => ['cap', 'caps', 'hat', 'hats', 'beanie', 'bucket']],
        ['icon' => '🧦', 'label' => 'Socks',              'words' => ['sock', 'socks']],
        ['icon' => '🧤', 'label' => 'Gloves',             'words' => ['glove', 'gloves', 'mitten', 'mittens']],
        ['icon' => '🔦', 'label' => 'Light / lantern',    'words' => ['flashlight', 'lamp', 'lantern', 'headlamp', 'torch', 'light', 'lights']],
        ['icon' => '🍳', 'label' => 'Cooking gear',       'words' => ['stove', 'burner', 'cookware', 'cook', 'pot', 'pan', 'kettle', 'utensil', 'cutlery', 'grill']],
        ['icon' => '🥤', 'label' => 'Bottle / hydration', 'words' => ['bottle', 'bottles', 'tumbler', 'flask', 'jug', 'canteen', 'hydration']],
        ['icon' => '🪢', 'label' => 'Rope / cord',        'words' => ['rope', 'cord', 'paracord', 'carabiner', 'strap']],
        ['icon' => '🧭', 'label' => 'Navigation',         'words' => ['compass', 'map', 'gps']],
        ['icon' => '🛠️', 'label' => 'Outdoor tool',       'words' => ['knife', 'multitool', 'tool', 'axe', 'saw']],
    ];

    /** ['icon' => '🩴', 'tip' => 'Slippers / sandals - generic picture. No photo has been uploaded for "Dahican" yet.'] */
    public function getPlaceholderAttribute(): array
    {
        $found = null;

        // Category first (it is the clearest hint), then the model name.
        foreach ([$this->category?->name, $this->name] as $text) {
            $text = mb_strtolower((string) $text);
            foreach (self::PLACEHOLDERS as $kind) {
                foreach ($kind['words'] as $word) {
                    if ($text !== '' && preg_match('/(?<![a-z])' . preg_quote($word, '/') . '(?![a-z])/u', $text)) {
                        $found = $kind;
                        break 3;
                    }
                }
            }
        }

        $found ??= ['icon' => '🏕️', 'label' => 'Outdoor gear'];

        return [
            'icon' => $found['icon'],
            'tip'  => $found['label'] . ' - generic picture. '
                . (trim((string) $this->name) !== '' ? 'No photo has been uploaded for "' . $this->name . '" yet.' : 'No photo has been uploaded yet.'),
        ];
    }

    public function getTotalStockAttribute(): int
    {
        return (int) $this->variants->sum('quantity');
    }
}
