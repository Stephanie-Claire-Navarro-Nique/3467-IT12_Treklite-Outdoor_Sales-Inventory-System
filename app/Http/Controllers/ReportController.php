<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function sales(Request $request)
    {
        $from = $this->safeDate($request->query('from'), now()->startOfMonth())->startOfDay();
        $to   = $this->safeDate($request->query('to'), now())->endOfDay();

        $sales = Sale::with('user')->active()
            ->whereBetween('sold_at', [$from, $to])
            ->orderBy('sold_at')
            ->get();

        // Voided sales are not counted in the report, but the Owner is told how many were left out.
        $voided      = Sale::whereNotNull('voided_at')->whereBetween('sold_at', [$from, $to]);
        $voidedCount = (clone $voided)->count();
        $voidedTotal = (float) (clone $voided)->sum('total_amount');

        $totalRevenue = $sales->sum('total_amount');
        $byPayment    = $sales->groupBy('payment_method')->map(fn ($g) => [
            'count' => $g->count(),
            'total' => $g->sum('total_amount'),
        ]);

        return view('reports.sales', compact('sales', 'from', 'to', 'totalRevenue', 'byPayment', 'voidedCount', 'voidedTotal'));
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
