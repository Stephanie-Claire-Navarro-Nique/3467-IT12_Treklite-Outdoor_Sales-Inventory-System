<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SaleItem;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    /** Product list with search, brand and category filters. Stock is managed in Inventory, not here. */
    public function index(Request $request)
    {
        $q        = trim((string) $request->query('q'));
        $brand    = trim((string) $request->query('brand'));
        $category = trim((string) $request->query('category'));
        $status   = in_array($request->query('status'), ['archived', 'all'], true) ? $request->query('status') : 'active';

        $products = Product::with('variants')
            ->when($status === 'active', fn ($w) => $w->where('is_active', true))
            ->when($status === 'archived', fn ($w) => $w->where('is_active', false))
            ->when($q !== '', fn ($w) => $w->where(fn ($s) => $s
                ->where('name', 'like', "%{$q}%")
                ->orWhere('brand', 'like', "%{$q}%")
                ->orWhere('category', 'like', "%{$q}%")
                ->orWhereHas('variants', fn ($v) => $v->where('sku', 'like', "%{$q}%"))))
            ->when($brand === InventoryController::NO_BRAND, fn ($w) => $w->where(fn ($s) => $s->whereNull('brand')->orWhere('brand', '')))
            ->when($brand !== '' && $brand !== InventoryController::NO_BRAND, fn ($w) => $w->where('brand', $brand))
            ->when($category !== '', fn ($w) => $w->where('category', $category))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $brands     = $this->distinct('brand');
        $categories = $this->distinct('category');

        // Products with no sales and no stock records at all (e.g. created by mistake) can be deleted.
        $variantRows = ProductVariant::whereIn('product_id', $products->pluck('id'))->get(['id', 'product_id']);
        $usedIds     = SaleItem::whereIn('variant_id', $variantRows->pluck('id'))->pluck('variant_id')
            ->merge(StockMovement::whereIn('variant_id', $variantRows->pluck('id'))->pluck('variant_id'))
            ->unique();
        $deletable = $variantRows->groupBy('product_id')
            ->filter(fn ($g) => $g->pluck('id')->intersect($usedIds)->isEmpty())
            ->keys()
            ->merge($products->pluck('id')->diff($variantRows->pluck('product_id')))   // products with no variant rows at all
            ->values();

        return view('products.index', compact('products', 'q', 'brand', 'category', 'status', 'brands', 'categories', 'deletable'));
    }

    public function create()
    {
        return view('products.form', [
            'product'    => new Product(),
            'brands'     => $this->distinct('brand'),
            'categories' => $this->distinct('category'),
        ]);
    }

    /** Existing values of a product column, used for filter drop-downs and typing suggestions. */
    private function distinct(string $column)
    {
        return Product::whereNotNull($column)->where($column, '!=', '')->distinct()->orderBy($column)->pluck($column);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $product = DB::transaction(function () use ($request, $data) {
            $product = Product::create($this->productFields($request, $data));
            $this->saveVariants($product, $data['variants']);

            return $product;
        });

        ActivityLog::record('product_add', 'Added product ' . $product->name . ' (price ₱' . number_format((float) $product->price, 2) . ', ' . count($data['variants']) . ' size/color row(s)).');

        return redirect()->route('inventory.index', ['product' => $product->id])
            ->with('status', 'Product added with 0 stock. Use Receive below to add its first stock.');
    }

    public function edit(Product $product)
    {
        $product->load('variants');

        return view('products.form', [
            'product'    => $product,
            'brands'     => $this->distinct('brand'),
            'categories' => $this->distinct('category'),
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $data     = $this->validated($request);
        $oldName  = $product->name;
        $oldPrice = (float) $product->price;

        DB::transaction(function () use ($request, $data, $product) {
            $fields = $this->productFields($request, $data);
            if (isset($fields['image_path']) && $product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }
            $product->update($fields);
            $this->saveVariants($product, $data['variants']);
        });

        $note = '';
        if (round($oldPrice, 2) !== round((float) $data['price'], 2)) {
            $note .= ' Price ₱' . number_format($oldPrice, 2) . ' -> ₱' . number_format((float) $data['price'], 2) . '.';
        }
        if ($oldName !== $data['name']) {
            $note .= ' Renamed from "' . $oldName . '".';
        }
        ActivityLog::record('product_edit', 'Edited product ' . $data['name'] . '.' . $note);

        return redirect()->route('products.index')->with('status', 'Product updated.');
    }

    /** Archive = stop selling and hide from stock lists, but keep every past sale and stock record. */
    public function archive(Product $product)
    {
        $product->update(['is_active' => false]);
        ActivityLog::record('product_archive', 'Archived product ' . $product->name . '.');

        return redirect()->route('products.index')->with('status', $product->name . ' was archived. It no longer appears in New Sale or Inventory. You can restore it anytime.');
    }

    /** Only for a product that has never been sold and never had stock recorded. Everything else must be archived. */
    public function destroy(Product $product)
    {
        $ids = $product->variants()->pluck('id');

        if (SaleItem::whereIn('variant_id', $ids)->exists() || StockMovement::whereIn('variant_id', $ids)->exists()) {
            return back()->withErrors(['product' => $product->name . ' already has sales or stock records, so it cannot be deleted. Archive it instead.']);
        }

        $name = $product->name;

        DB::transaction(function () use ($product) {
            $product->variants()->delete();
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }
            $product->delete();
        });

        ActivityLog::record('product_delete', 'Deleted product ' . $name . ' (it had no sales or stock records).');

        return redirect()->route('products.index')->with('status', $name . ' was deleted.');
    }

    public function restore(Product $product)
    {
        $product->update(['is_active' => true]);
        ActivityLog::record('product_restore', 'Restored product ' . $product->name . '.');

        return redirect()->route('products.index', ['status' => 'archived'])->with('status', $product->name . ' is active again.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name'                         => ['required', 'string', 'max:255'],
            'category'                     => ['nullable', 'string', 'max:255'],
            'brand'                        => ['nullable', 'string', 'max:255'],
            'description'                  => ['nullable', 'string', 'max:2000'],
            'price'                        => ['required', 'numeric', 'min:0'],
            'image'                        => ['nullable', 'image', 'max:4096'],
            'variants'                     => ['required', 'array', 'min:1'],
            'variants.*.id'                => ['nullable', 'integer'],
            'variants.*.sku'               => ['nullable', 'string', 'max:255'],   // blank = the app makes an internal code
            'variants.*.size'              => ['nullable', 'string', 'max:50'],
            'variants.*.color'             => ['nullable', 'string', 'max:50'],
            'variants.*.price'             => ['nullable', 'numeric', 'min:0'],
            'variants.*.low_stock_threshold' => ['required', 'integer', 'min:0'],
        ]);
    }

    private function productFields(Request $request, array $data): array
    {
        $fields = collect($data)->only(['name', 'category', 'brand', 'description', 'price'])->all();

        if ($request->hasFile('image')) {
            $fields['image_path'] = $request->file('image')->store('products', 'public');
        }

        return $fields;
    }

    private function saveVariants(Product $product, array $rows): void
    {
        // The same size + color cannot be entered twice for one product.
        $seen = [];
        foreach ($rows as $i => $row) {
            $key = mb_strtolower(trim(($row['size'] ?? '') . '|' . ($row['color'] ?? '')));
            if (isset($seen[$key])) {
                throw ValidationException::withMessages(["variants.$i.size" => 'This size / color is entered twice. Combine them into one row.']);
            }
            $seen[$key] = true;
        }

        foreach ($rows as $i => $row) {
            $id = $row['id'] ?? null;

            $sku = trim((string) ($row['sku'] ?? ''));

            if ($sku !== '') {
                $skuTaken = ProductVariant::where('sku', $sku)->when($id, fn ($q) => $q->where('id', '!=', $id))->exists();
                if ($skuTaken) {
                    throw ValidationException::withMessages(["variants.$i.sku" => 'The SKU ' . $sku . ' is already used.']);
                }
            } else {
                // Models usually have no SKU: keep the existing code when editing, otherwise make an internal one.
                $sku = ($id ? $product->variants()->whereKey($id)->value('sku') : null) ?: $this->makeSku($product);
            }

            $attrs = [
                'sku'                 => $sku,
                'size'                => $row['size'] ?? null,
                'color'               => $row['color'] ?? null,
                'price'               => ($row['price'] ?? '') === '' ? null : $row['price'],
                'low_stock_threshold' => $row['low_stock_threshold'],
            ];

            if ($id) {
                // Quantity is never edited here; use Inventory > Receive / Adjust so every change is logged.
                $product->variants()->whereKey($id)->update($attrs);
            } else {
                // Every item starts at 0. The first stock is added in Inventory > Receive, so it is logged like all other stock.
                $product->variants()->create($attrs + ['quantity' => 0]);
            }
        }
    }

    /** Internal item code such as TL-0007-K3QZ, used when the owner leaves the SKU blank. */
    private function makeSku(Product $product): string
    {
        do {
            $sku = sprintf('TL-%04d-%s', $product->id, Str::upper(Str::random(4)));
        } while (ProductVariant::where('sku', $sku)->exists());

        return $sku;
    }
}
