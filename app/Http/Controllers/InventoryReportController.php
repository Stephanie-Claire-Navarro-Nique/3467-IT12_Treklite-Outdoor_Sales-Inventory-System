<?php

namespace App\Http\Controllers;

use App\Models\ProductVariant;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Owner-only inventory report: stock on hand per product variant, with low-stock items,
 * plus what moved in the chosen Today / Week / Month / Year or custom range (received, sold, adjusted by a count).
 */
class InventoryReportController extends Controller
{
    private const PERIODS = ['day', 'week', 'month', 'year', 'custom'];

    public function index(Request $request)
    {
        $onlyAttention = $request->boolean('attention');

        // Period filter: Today / Week / Month / Year, or a Custom From -> To range (same as the Sales Report).
        $period = in_array($request->query('period'), self::PERIODS, true) ? $request->query('period') : null;

        // Links with ?from=&to= are treated as a custom range.
        if ($period === null) {
            $period = ($request->filled('from') || $request->filled('to')) ? 'custom' : 'month';
        }

        $date = $this->safeDate($request->query('date'), now());
        if ($date->gt(now())) { $date = now(); }   // no future dates

        if ($period === 'custom') {
            $from = $this->safeDate($request->query('from'), now()->startOfMonth())->startOfDay();
            $to   = $this->safeDate($request->query('to'), now())->endOfDay();
            if ($from->gt(now())) { $from = now()->startOfDay(); }   // no future dates
            if ($to->gt(now()))   { $to   = now()->endOfDay(); }
            if ($from->gt($to)) {
                [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
            }
        } else {
            [$from, $to] = match ($period) {
                'day'   => [$date->copy()->startOfDay(),   $date->copy()->endOfDay()],
                'week'  => [$date->copy()->startOfWeek(),  $date->copy()->endOfWeek()],
                'month' => [$date->copy()->startOfMonth(), $date->copy()->endOfMonth()],
                'year'  => [$date->copy()->startOfYear(),  $date->copy()->endOfYear()],
            };
        }

        // The range never reaches past today.
        if ($to->gt(now())) { $to = now()->endOfDay(); }

        $periodLabel = match (true) {
            $period === 'custom'  => $from->format('M d, Y') . ' – ' . $to->format('M d, Y'),
            $period === 'day'     => $from->format('M d, Y'),
            $period === 'week'    => $from->format('M d') . ' – ' . $from->copy()->endOfWeek()->format('M d, Y'),
            $period === 'month'   => $from->format('F Y'),
            default               => $from->format('Y'),
        };

        $variants = ProductVariant::with('product')
            ->whereHas('product', fn ($p) => $p->where('is_active', true))
            ->when($onlyAttention, fn ($q) => $q->whereColumn('quantity', '<=', 'low_stock_threshold'))
            ->get()
            ->sortBy(fn ($v) => strtolower($v->product->name . ' ' . $v->label))
            ->values();

        // Stock movement inside the chosen period, per variant.
        $moves = StockMovement::query()
            ->whereBetween('created_at', [$from, $to])
            ->whereIn('variant_id', $variants->pluck('id'))
            ->selectRaw('variant_id, type, SUM(quantity_change) as total')
            ->groupBy('variant_id', 'type')
            ->get()
            ->groupBy('variant_id')
            ->map(fn ($rows) => $rows->pluck('total', 'type'));

        foreach ($variants as $v) {
            $m = $moves->get($v->id, collect());
            $v->period_received = (int) ($m['receive'] ?? 0);
            // Sales take stock out (negative), a voided sale puts it back (positive): net units sold.
            $v->period_sold     = -((int) ($m['sale'] ?? 0) + (int) ($m['void'] ?? 0));
            $v->period_adjusted = (int) ($m['count'] ?? 0);
        }

        $totalUnits      = (int) $variants->sum('quantity');
        $totalValue      = (float) $variants->sum(fn ($v) => $v->quantity * $v->selling_price);
        $lowCount        = $variants->filter(fn ($v) => $v->isLowStock())->count();
        $outCount        = $variants->filter(fn ($v) => $v->isOutOfStock())->count();
        $periodReceived  = (int) $variants->sum('period_received');
        $periodSold      = (int) $variants->sum('period_sold');
        $periodAdjusted  = (int) $variants->sum('period_adjusted');

        return view('reports.inventory', compact(
            'variants', 'onlyAttention', 'totalUnits', 'totalValue', 'lowCount', 'outCount',
            'period', 'date', 'from', 'to', 'periodLabel',
            'periodReceived', 'periodSold', 'periodAdjusted'
        ));
    }

    /** A date from the link, or today when it is empty or not a real date (so a bad link never crashes the page). */
    private function safeDate($value, Carbon $default): Carbon
    {
        try {
            return is_string($value) && trim($value) !== '' ? Carbon::parse($value) : $default->copy();
        } catch (\Throwable $e) {
            return $default->copy();
        }
    }
}
