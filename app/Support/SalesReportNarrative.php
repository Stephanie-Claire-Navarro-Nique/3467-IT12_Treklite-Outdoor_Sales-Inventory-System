<?php

namespace App\Support;

use App\Models\Sale;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

/**
 * Everything the Sales Report says in words and charts, built ONCE and used by the screen, the printout
 * and the PDF export, so all three always show the same information.
 */
class SalesReportNarrative
{
    public static function build(array $r, $user): array
    {
        /** @var \Illuminate\Support\Collection $sales */
        $sales   = $r['sales'];
        /** @var Carbon $from */
        $from    = $r['from'];
        /** @var Carbon $to */
        $to      = $r['to'];
        $total   = (float) $r['totalRevenue'];
        $count   = $sales->count();
        $money   = fn ($n) => '₱' . number_format((float) $n, 2);
        $dateFmt = 'M d, Y';

        $periodText = $from->isSameDay($to)
            ? $from->format($dateFmt)
            : $from->format($dateFmt) . ' – ' . $to->format($dateFmt);

        // ---------- chart 1: revenue over time ----------
        $singleDay   = $from->isSameDay($to);
        $byMonthView = ! $singleDay && $from->diffInDays($to) > 120;
        $grain       = $singleDay ? 'hour' : ($byMonthView ? 'month' : 'day');
        $labels = $revenue = $counts = [];

        if ($singleDay) {
            $byHour = $sales->groupBy(fn ($s) => (int) $s->sold_at->format('G'));
            $hours  = $byHour->keys()->all();
            $minH   = count($hours) ? min(8, min($hours)) : 8;
            $maxH   = count($hours) ? max(20, max($hours)) : 20;
            foreach (range($minH, $maxH) as $h) {
                $g = $byHour->get($h, collect());
                $labels[]  = Carbon::createFromTime($h)->format('g A');
                $revenue[] = round((float) $g->sum('total_amount'), 2);
                $counts[]  = $g->count();
            }
        } elseif ($byMonthView) {
            $byMonth = $sales->groupBy(fn ($s) => $s->sold_at->format('Y-m'));
            foreach (CarbonPeriod::create($from->copy()->startOfMonth(), '1 month', $to->copy()->startOfMonth()) as $m) {
                $g = $byMonth->get($m->format('Y-m'), collect());
                $labels[]  = $m->format('M Y');
                $revenue[] = round((float) $g->sum('total_amount'), 2);
                $counts[]  = $g->count();
            }
        } else {
            $byDay = $sales->groupBy(fn ($s) => $s->sold_at->format('Y-m-d'));
            foreach (CarbonPeriod::create($from->copy()->startOfDay(), $to->copy()->startOfDay()) as $day) {
                $g = $byDay->get($day->format('Y-m-d'), collect());
                $labels[]  = $day->format('M d');
                $revenue[] = round((float) $g->sum('total_amount'), 2);
                $counts[]  = $g->count();
            }
        }

        // ---------- chart 2: payment methods ----------
        $pay = [];
        foreach (Sale::PAYMENT_LABELS as $key => $label) {
            $t = (float) ($r['byPayment'][$key]['total'] ?? 0);
            $n = (int) ($r['byPayment'][$key]['count'] ?? 0);
            if ($t > 0) {
                $pay[] = ['label' => $label, 'total' => round($t, 2), 'count' => $n, 'pct' => $total > 0 ? round($t / $total * 100) : 0];
            }
        }
        usort($pay, fn ($a, $b) => $b['total'] <=> $a['total']);

        // ---------- the words ----------
        $peakIdx   = $count ? array_search(max($revenue), $revenue, true) : null;
        $peakLabel = $peakIdx !== null ? $labels[$peakIdx] : null;
        if ($peakLabel !== null && $grain === 'day') {
            $peakLabel = Carbon::parse($from)->copy()->startOfDay()->addDays($peakIdx)->format($dateFmt);
        }
        $grainWord = ['hour' => 'hour', 'day' => 'day', 'month' => 'month'][$grain];
        $top       = $pay[0] ?? null;

        if ($count === 0) {
            $summary = ["No completed sales were recorded for {$periodText}."];
            $timeExplain = 'There are no sales in this period, so the chart is empty.';
            $payExplain  = 'There are no sales in this period, so there is no payment breakdown.';
            $conclusion  = "No sales were recorded for {$periodText}.";
        } else {
            $avg = $total / $count;
            $summary = [
                "This report covers {$periodText}. {$count} completed sale(s) were recorded, with a total revenue of {$money($total)} and an average of {$money($avg)} per sale.",
                'Sales per payment method: ' . collect($pay)->map(fn ($p) => "{$p['label']} {$money($p['total'])} ({$p['pct']}%)")->implode(', ') . '.',
            ];
            $timeExplain = "The chart of revenue by {$grainWord} shows when the sales were made. Each bar is the money collected in that {$grainWord}; the highest was {$peakLabel} with {$money(max($revenue))}.";
            $payExplain  = 'The doughnut chart shows how each payment method contributes to the total revenue. '
                . "{$top['label']} is the largest share at {$top['pct']}% ({$money($top['total'])}).";
            $conclusion  = "Sales for this period totalled {$money($total)} from {$count} transaction(s). The largest share came from {$top['label']}, and {$peakLabel} was the busiest {$grainWord}.";
        }
        if (($r['voidedCount'] ?? 0) > 0) {
            $summary[]  = "{$r['voidedCount']} voided sale(s) ({$money($r['voidedTotal'])}) in this period are not counted in these figures.";
            $conclusion .= ' Voided sales are not included.';
        }

        return [
            'periodText'   => $periodText,
            'preparedBy'   => $user?->name ?? '—',
            'preparedRole' => $user ? ucfirst($user->role) : '',
            'datePrepared' => now()->format('M d, Y h:i A'),
            'summary'      => $summary,
            'timeExplain'  => $timeExplain,
            'payExplain'   => $payExplain,
            'conclusion'   => $conclusion,
            'grain'        => $grain,
            'timeTitle'    => 'Revenue by ' . $grainWord,
            'timeLabels'   => $labels,
            'timeRevenue'  => $revenue,
            'timeCounts'   => $counts,
            'pay'          => $pay,
            'payLabels'    => array_column($pay, 'label'),
            'payTotals'    => array_column($pay, 'total'),
        ];
    }
}
