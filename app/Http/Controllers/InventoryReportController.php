<?php

namespace App\Http\Controllers;

use App\Models\ProductVariant;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Owner-only inventory report: stock on hand per product variant, with low-stock items,
 * plus what moved in the chosen Day / Week / Month / Year (received, sold, adjusted by a count).
 */
class InventoryReportController extends Controller
{
    private const PERIODS = ['day', 'week', 'month', 'year'];

    public function index(Request $request)
    {
        $onlyAttention = $request->boolean('attention');

        // Period filter: Day / Week / Month / Year around a chosen date (today by default).
        $period = in_array($request->query('period'), self::PERIODS, true) ? $request->query('period') : 'month';
        $date   = $this->safeDate($request->query('date'));
        if ($date->gt(now())) { $date = now(); }   // no future dates

        [$from, $to, $periodLabel] = match ($period) {
            'day'   => [$date->copy()->startOfDay(),   $date->copy()->endOfDay(),   $date->format('M d, Y')],
            'week'  => [$date->copy()->startOfWeek(),  $date->copy()->endOfWeek(),  $date->copy()->startOfWeek()->format('M d') . ' – ' . $date->copy()->endOfWeek()->format('M d, Y')],
            'month' => [$date->copy()->startOfMonth(), $date->copy()->endOfMonth(), $date->format('F Y')],
            'year'  => [$date->copy()->startOfYear(),  $date->copy()->endOfYear(),  $date->format('Y')],
        };

        $prevDate = match ($period) {
            'day'   => $date->copy()->subDay(),
            'week'  => $date->copy()->subWeek(),
            'month' => $date->copy()->subMonthNoOverflow(),
            'year'  => $date->copy()->subYearNoOverflow(),
        };
        $nextDate = match ($period) {
            'day'   => $date->copy()->addDay(),
            'week'  => $date->copy()->addWeek(),
            'month' => $date->copy()->addMonthNoOverflow(),
            'year'  => $date->copy()->addYearNoOverflow(),
        };

        // Never step into the future.
        if ($nextDate->gt(now())) { $nextDate = now(); }

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
            'period', 'date', 'periodLabel', 'prevDate', 'nextDate',
            'periodReceived', 'periodSold', 'periodAdjusted'
        ));
    }

    /** A date from the link, or today when it is empty or not a real date (so a bad link never crashes the page). */
    private function safeDate($value): Carbon
    {
        try {
            return is_string($value) && trim($value) !== '' ? Carbon::parse($value) : now();
        } catch (\Throwable $e) {
            return now();
        }
    }
}
