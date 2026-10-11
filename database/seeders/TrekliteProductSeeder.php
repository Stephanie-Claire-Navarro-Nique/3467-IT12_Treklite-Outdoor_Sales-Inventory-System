<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Treklite's real product list, each with its starting stock.
 *
 *   php artisan db:seed --class=TrekliteProductSeeder
 *
 * Safe to run again: a model whose name already exists is skipped (nothing is duplicated or changed).
 * None of these items has a size or color, so each one gets a single "Standard" row.
 * Starting stock is recorded in Stock History as "Received - Opening stock", like any other delivery.
 */
class TrekliteProductSeeder extends Seeder
{
    private const OPENING_STOCK = 30;
    private const LOW_STOCK_AT  = 5;

    public function run(): void
    {
        $owner = User::where('role', 'owner')->first();

        // brand, model, category, price
        $products = [
            ['TUKAD',    'Bagtas',             'Slippers', 435],
            ['TUKAD',    'Edge',               'Slippers', 585],
            ['TUKAD',    'Grove',              'Slippers', 585],
            ['BOATMAN',  'TBM C',              'Slippers', 540],
            ['BOATMAN',  'TBM B',              'Slippers', 520],
            ['BOATMAN',  'Lakbay',             'Slippers', 495],
            ['BOATMAN',  'Dahican',            'Slippers', 375],
            ['BOATMAN',  'Boulder C',          'Slippers', 640],
            ['BOATMAN',  'Boulder B',          'Slippers', 620],
            ['BOATMAN',  'TBM P',              'Slippers', 499],
            ['SANDERS',  'Sanders C',          'Slippers', 535],
            ['SANDERS',  'Sanders B',          'Slippers', 500],
            ['TREKLITE', 'Treklite Offroad C', 'Slippers', 500],
            ['TREKLITE', 'Treklite Offroad B', 'Slippers', 480],
            ['TREKLITE', 'Treklite Sandal',    'Slippers', 620],
        ];

        $added = 0;

        foreach ($products as [$brand, $name, $category, $price]) {
            if (Product::where('name', $name)->exists()) {
                continue;   // already in the system: leave it exactly as it is
            }

            DB::transaction(function () use ($owner, $brand, $name, $category, $price) {
                $product = Product::create([
                    'name' => $name, 'brand' => $brand, 'category' => $category, 'price' => $price, 'is_active' => true,
                ]);

                $variant = $product->variants()->create([
                    'sku'                 => $this->makeSku($product),
                    'size'                => null,
                    'color'               => null,
                    'price'               => null,
                    'quantity'            => self::OPENING_STOCK,
                    'low_stock_threshold' => self::LOW_STOCK_AT,
                ]);

                if ($owner) {
                    StockMovement::create([
                        'variant_id'      => $variant->id,
                        'user_id'         => $owner->id,
                        'type'            => 'receive',
                        'quantity_before' => 0,
                        'quantity_change' => self::OPENING_STOCK,
                        'quantity_after'  => self::OPENING_STOCK,
                        'note'            => 'Opening stock',
                    ]);
                }
            });

            $added++;
        }

        $skipped = count($products) - $added;
        $this->command?->info($added . ' product(s) added' . ($skipped ? ", {$skipped} already existed and were skipped." : '.'));
    }

    /** Same internal item code the Products page makes, e.g. TL-0007-K3QZ. */
    private function makeSku(Product $product): string
    {
        do {
            $sku = sprintf('TL-%04d-%s', $product->id, Str::upper(Str::random(4)));
        } while (ProductVariant::where('sku', $sku)->exists());

        return $sku;
    }
}
