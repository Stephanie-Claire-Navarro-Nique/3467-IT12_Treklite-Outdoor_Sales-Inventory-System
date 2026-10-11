<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Models\ActivityLog;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleController extends Controller
{
    /** Sales list with search, date range, cashier and payment filters. */
    public function index(Request $request)
    {
        $isOwner = auth()->user()->hasFullAccess();
        [$query, $filters] = $this->filteredSales($request, $isOwner);

        // Voided sales are listed (with a badge) but never counted in the totals.
        $summary     = (clone $query)->active()->selectRaw('COUNT(*) as cnt, COALESCE(SUM(total_amount), 0) as total')->first();
        $voidedCount = (clone $query)->whereNotNull('voided_at')->count();
        $sales    = $query->with('user')->latest('sold_at')->paginate(20)->withQueryString();
        $cashiers = $isOwner ? User::orderBy('name')->get(['id', 'name']) : collect();

        return view('sales.index', $filters + compact('sales', 'summary', 'voidedCount', 'cashiers', 'isOwner'));
    }

    /** Owner only: the same filtered list as Sales Records, downloaded as a CSV file (opens in Excel). */
    public function export(Request $request)
    {
        [$query] = $this->filteredSales($request, true);
        $rows = $query->with('user')->latest('sold_at')->get();

        // A text cell that starts with = + - @ could run as a formula in Excel, so it gets a leading apostrophe.
        $safe = fn ($v) => is_string($v) && $v !== '' && strpbrk($v[0], '=+-@') !== false ? "'" . $v : $v;

        return response()->streamDownload(function () use ($rows, $safe) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");   // UTF-8 marker so Excel shows the peso sign and accents correctly
            fputcsv($out, ['Invoice', 'Date', 'Customer', 'Contact', 'Cashier', 'Payment', 'Total', 'Amount received', 'Change', 'Status', 'Void reason'], ',', '"', '');
            foreach ($rows as $s) {
                fputcsv($out, [
                    $s->invoice_number,
                    $s->sold_at->format('Y-m-d H:i'),
                    $safe($s->customer_name),
                    $safe($s->customer_contact),
                    $safe($s->user?->name),
                    $s->payment_label,
                    number_format((float) $s->total_amount, 2, '.', ''),
                    number_format((float) $s->amount_tendered, 2, '.', ''),
                    number_format((float) $s->change_amount, 2, '.', ''),
                    $s->isVoided() ? 'VOIDED' : 'Completed',
                    $safe($s->void_reason),
                ], ',', '"', '');
            }
            fclose($out);
        }, 'treklite-sales-' . now()->format('Ymd-His') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Filters used by Sales Records and the CSV export. Staff only ever see the sales they processed. */
    private function filteredSales(Request $request, bool $isOwner): array
    {
        $q       = trim((string) $request->query('q'));
        $from    = $this->parseDate($request->query('from'));
        $to      = $this->parseDate($request->query('to'));
        $cashier = $isOwner && $request->filled('cashier') ? (int) $request->query('cashier') : null;
        $payment = is_string($request->query('payment')) && array_key_exists($request->query('payment'), Sale::PAYMENT_LABELS) ? $request->query('payment') : '';

        $query = Sale::query()
            ->when(! $isOwner, fn ($w) => $w->where('user_id', auth()->id()))
            ->when($q !== '', fn ($w) => $w->where(fn ($s) => $s
                ->where('invoice_number', 'like', "%{$q}%")
                ->orWhere('customer_name', 'like', "%{$q}%")
                ->orWhere('customer_contact', 'like', "%{$q}%")))
            ->when($from, fn ($w) => $w->whereDate('sold_at', '>=', $from->toDateString()))
            ->when($to, fn ($w) => $w->whereDate('sold_at', '<=', $to->toDateString()))
            ->when($cashier, fn ($w) => $w->where('user_id', $cashier))
            ->when($payment !== '', fn ($w) => $w->where('payment_method', $payment));

        return [$query, [
            'q' => $q, 'from' => $from?->toDateString(), 'to' => $to?->toDateString(),
            'cashier' => $cashier, 'payment' => $payment,
        ]];
    }

    /** Returns a date, or null if the text is empty or not a real date (so a bad URL never crashes the page). */
    private function parseDate($value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }
        try {
            return Carbon::createFromFormat('Y-m-d', trim($value))->startOfDay();
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function create()
    {
        $products = Product::with('variants')
            ->where('is_active', true)
            ->whereHas('variants')
            ->orderBy('name')
            ->get()
            ->map(fn ($p) => [
                'id'       => $p->id,
                'name'     => $p->name,
                'category' => $p->category,
                'brand'    => $p->brand,
                'image'    => $p->image_url,
                'variants' => $p->variants->map(fn ($v) => [
                    'id'    => $v->id,
                    'sku'   => $v->sku,
                    'size'  => $v->size,
                    'color' => $v->color,
                    'label' => $v->label,
                    'price' => (float) ($v->price ?? $p->price),
                    'stock' => (int) $v->quantity,
                ])->values(),
            ])->values();

        $categories = Product::where('is_active', true)->whereNotNull('category')->distinct()->orderBy('category')->pluck('category');

        // Optional: put your shop's static QR Ph / GCash QR image here to show it at checkout.
        $paymentQr = file_exists(public_path('images/payment-qr.png')) ? asset('images/payment-qr.png') : null;

        return view('sales.create', compact('products', 'categories', 'paymentQr'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_name'      => ['nullable', 'string', 'max:255'],
            'customer_address'   => ['nullable', 'string', 'max:255'],
            'customer_contact'   => ['nullable', 'string', 'max:50'],
            'payment_method'     => ['required', 'in:cash,gcash,bank_transfer'],
            'amount_tendered'    => ['required', 'numeric', 'min:0'],
            'payment_reference'  => ['required_unless:payment_method,cash', 'nullable', 'string', 'max:100'],
            'payment_proof'      => ['required_unless:payment_method,cash', 'nullable', 'image', 'max:4096'],
            'items'              => ['required', 'array', 'min:1'],
            'items.*.variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'items.*.quantity'   => ['required', 'integer', 'min:1'],
        ], [
            'amount_tendered.required'        => 'Enter the amount received from the customer.',
            'payment_reference.required_unless' => 'Enter the reference number from the payment confirmation for GCash / Bank Transfer.',
            'payment_proof.required_unless'   => 'Take or upload a photo of the payment confirmation for GCash / Bank Transfer.',
        ]);

        // Reference number is only for GCash / Bank Transfer. Cash sales store nothing (NULL), never an empty text.
        $reference = $data['payment_method'] === 'cash' ? null : trim((string) ($data['payment_reference'] ?? ''));
        $reference = $reference === '' ? null : $reference;

        // The same reference number must not be used on two different sales.
        if ($reference !== null) {
            $usedRef = Sale::where('payment_reference', $reference)->first();
            if ($usedRef) {
                throw ValidationException::withMessages(['payment_reference' => ['This reference number was already used on invoice ' . $usedRef->invoice_number . '. Check the number on the payment confirmation.']]);
            }
        }

        // The same payment photo must not be used on two different sales.
        $proofHash = null;
        if ($request->hasFile('payment_proof')) {
            $proofHash = sha1_file($request->file('payment_proof')->getRealPath());
            $used = Sale::where('payment_proof_hash', $proofHash)->first();
            if ($used) {
                throw ValidationException::withMessages(['payment_proof' => ['This payment photo was already used on invoice ' . $used->invoice_number . '. Take a new photo of this payment.']]);
            }
        }

        $sale = DB::transaction(function () use ($data, $request, $proofHash, $reference) {
            // Merge duplicate variants, then lock them so two sales cannot take the same stock.
            $lines = collect($data['items'])->groupBy('variant_id')->map(fn ($g) => (int) $g->sum('quantity'));

            $variants = ProductVariant::with('product')
                ->whereIn('id', $lines->keys())
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // A cart left open on a screen must not sell an item that was archived in the meantime.
            $archived = $variants->filter(fn ($v) => ! $v->product->is_active)->map(fn ($v) => $v->product->name)->unique();
            if ($archived->isNotEmpty()) {
                throw ValidationException::withMessages(['items' => ['These items are no longer sold (archived): ' . $archived->implode(', ') . '. Remove them from the cart.']]);
            }

            $short = [];
            $total = 0.0;
            foreach ($lines as $id => $qty) {
                $v = $variants[$id];
                if ($qty > $v->quantity) {
                    $short[] = $v->product->name . ' (' . $v->label . '): only ' . $v->quantity . ' left';
                }
                $total += $v->selling_price * $qty;
            }
            if ($short) {
                throw ValidationException::withMessages(['items' => ['Not enough stock for: ' . implode('; ', $short)]]);
            }

            // The amount received is required and must cover the total. More than the total is fine (change is given).
            $tendered = (float) $data['amount_tendered'];
            if ($tendered + 0.001 < $total) {
                throw ValidationException::withMessages(['amount_tendered' => ['The amount received (₱' . number_format($tendered, 2) . ') is less than the total (₱' . number_format($total, 2) . ').']]);
            }

            // Invoice number = today's last number + 1. If two sales are saved at the same moment and pick the
            // same number, the database refuses the second one and we simply try the next number.
            $prefix = 'INV-' . now()->format('Ymd');
            $last   = Sale::where('invoice_number', 'like', $prefix . '-%')->lockForUpdate()->max('invoice_number');
            $seq    = $last ? ((int) substr($last, -4)) + 1 : 1;
            $proof  = $request->file('payment_proof')?->store('payment-proofs', 'public');

            $sale = null;
            for ($try = 0; $try < 5; $try++) {
                try {
                    $sale = Sale::create([
                        'invoice_number'     => sprintf('%s-%04d', $prefix, $seq + $try),
                        'user_id'            => auth()->id(),
                        'customer_name'      => $data['customer_name'] ?: 'Walk-in Customer',
                        'customer_address'   => $data['customer_address'] ?? null,
                        'customer_contact'   => $data['customer_contact'] ?? null,
                        'payment_method'     => $data['payment_method'],
                        'payment_reference'  => $reference,
                        'payment_proof_path' => $proof,
                        'payment_proof_hash' => $proofHash,
                        'amount_tendered'    => $tendered,
                        'change_amount'      => max(0, $tendered - $total),
                        'total_amount'       => $total,
                        'sold_at'            => now(),
                    ]);
                    break;
                } catch (UniqueConstraintViolationException $e) {
                    if ($try === 4) {
                        throw $e;
                    }
                }
            }

            foreach ($lines as $id => $qty) {
                $v = $variants[$id];
                $sale->items()->create([
                    'variant_id' => $v->id,
                    'quantity'   => $qty,
                    'unit_price' => $v->selling_price,
                    'subtotal'   => $v->selling_price * $qty,
                ]);

                // Selling is a stock change too, so it shows up in Stock History.
                $before = (int) $v->quantity;
                $v->update(['quantity' => $before - $qty]);
                StockMovement::create([
                    'variant_id'      => $v->id,
                    'user_id'         => auth()->id(),
                    'type'            => 'sale',
                    'quantity_before' => $before,
                    'quantity_change' => -$qty,
                    'quantity_after'  => $before - $qty,
                    'note'            => 'Sale ' . $sale->invoice_number,
                ]);
            }

            return $sale;
        });

        return redirect()->route('sales.show', $sale)->with('status', 'Sale recorded. Invoice ' . $sale->invoice_number . ' is ready to print.');
    }

    public function show(Sale $sale)
    {
        abort_unless(
            auth()->user()->hasFullAccess() || $sale->user_id === auth()->id(),
            403
        );

        $sale->load('items.variant.product', 'user', 'voidedBy');

        return view('sales.show', compact('sale'));
    }

    /**
     * Owner only: reverse a recorded sale (wrong item, customer returned everything, mistaken entry).
     * The sale stays on record as VOIDED (who, when, why), the items go back to stock and each
     * return appears in Stock History. A voided sale is no longer counted in totals and reports.
     */
    public function void(Request $request, Sale $sale)
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:255'],
        ], [
            'reason.required' => 'Please write why this sale is being voided.',
            'reason.min'      => 'The reason is too short. Please explain what happened.',
        ]);

        DB::transaction(function () use ($sale, $data) {
            $sale = Sale::lockForUpdate()->findOrFail($sale->id);

            if ($sale->isVoided()) {
                throw ValidationException::withMessages(['reason' => ['This sale was already voided.']]);
            }

            $sale->load('items');
            $variants = ProductVariant::whereIn('id', $sale->items->pluck('variant_id'))->lockForUpdate()->get()->keyBy('id');

            foreach ($sale->items as $item) {
                $v      = $variants[$item->variant_id];
                $before = (int) $v->quantity;
                $after  = $before + (int) $item->quantity;

                $v->update(['quantity' => $after]);
                StockMovement::create([
                    'variant_id'      => $v->id,
                    'user_id'         => auth()->id(),
                    'type'            => 'void',
                    'quantity_before' => $before,
                    'quantity_change' => (int) $item->quantity,
                    'quantity_after'  => $after,
                    'note'            => 'Void of ' . $sale->invoice_number . ': ' . $data['reason'],
                ]);
            }

            $sale->update([
                'voided_at'   => now(),
                'voided_by'   => auth()->id(),
                'void_reason' => $data['reason'],
            ]);

            ActivityLog::record('sale_void', 'Voided ' . $sale->invoice_number . ' (₱' . number_format((float) $sale->total_amount, 2) . '). Reason: ' . $data['reason']);
        });

        return redirect()->route('sales.show', $sale)->with('status', 'Invoice ' . $sale->invoice_number . ' was voided and its items were returned to stock.');
    }
}
