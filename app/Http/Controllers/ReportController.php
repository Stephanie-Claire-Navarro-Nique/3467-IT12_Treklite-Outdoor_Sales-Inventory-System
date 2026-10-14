<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function sales(Request $request)
    {
        $periods = ['day', 'week', 'month', 'year', 'custom'];
        $period  = in_array($request->query('period'), $periods, true) ? $request->query('period') : null;

        // Old links (?from=&to=) keep working and are treated as a custom range.
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
            $prevDate = $nextDate = $date;
        } else {
            [$from, $to] = match ($period) {
                'day'   => [$date->copy()->startOfDay(),   $date->copy()->endOfDay()],
                'week'  => [$date->copy()->startOfWeek(),  $date->copy()->endOfWeek()],
                'month' => [$date->copy()->startOfMonth(), $date->copy()->endOfMonth()],
                'year'  => [$date->copy()->startOfYear(),  $date->copy()->endOfYear()],
            };
            $prevDate = match ($period) {
                'day' => $date->copy()->subDay(), 'week' => $date->copy()->subWeek(),
                'month' => $date->copy()->subMonthNoOverflow(), 'year' => $date->copy()->subYearNoOverflow(),
            };
            $nextDate = match ($period) {
                'day' => $date->copy()->addDay(), 'week' => $date->copy()->addWeek(),
                'month' => $date->copy()->addMonthNoOverflow(), 'year' => $date->copy()->addYearNoOverflow(),
            };
        }

        // The range never reaches past today.
        if ($to->gt(now())) { $to = now()->endOfDay(); }

        // Never step into the future.
        if ($nextDate->gt(now())) { $nextDate = now(); }

        $sales = Sale::with('user')->active()
            ->whereBetween('sold_at', [$from, $to])
            ->orderBy('sold_at')
            ->get();

        // Voided sales are not counted in the report, but the Owner is told how many were left out.
        $voided      = Sale::whereHas('voidRecord')->whereBetween('sold_at', [$from, $to]);
        $voidedCount = (clone $voided)->count();
        $voidedTotal = (float) (clone $voided)->sum('total_amount');

        $totalRevenue = $sales->sum('total_amount');
        $byPayment    = $sales->groupBy('payment_method')->map(fn ($g) => [
            'count' => $g->count(),
            'total' => $g->sum('total_amount'),
        ]);

        return view('reports.sales', compact('sales', 'from', 'to', 'period', 'date', 'prevDate', 'nextDate', 'totalRevenue', 'byPayment', 'voidedCount', 'voidedTotal'));
    }

    /** A date from the link, or the default when it is empty or not a real date (so a bad link never crashes the page). */
    private function safeDate($value, Carbon $default): Carbon
    {
        try {
            return is_string($value) && trim($value) !== '' ? Carbon::parse($value) : $default->copy();
        } catch (\Throwable $e) {
            return $default->copy();
        }
    }
}
