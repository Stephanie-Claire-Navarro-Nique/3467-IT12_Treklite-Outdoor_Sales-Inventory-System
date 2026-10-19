<?php

namespace App\Support;

/**
 * Words and chart figures of the Inventory Report, built once and used by the screen, the printout
 * and the PDF / Excel export so all of them always say the same thing.
 */
class InventoryReportNarrative
{
    public static function build(array $r, $user): array
    {
        /** @var \Illuminate\Support\Collection $variants */
        $variants = $r['variants'];
        $money    = fn ($n) => '₱' . number_format((float) $n, 2);
        $items    = $variants->count();
        $units    = (int) $r['totalUnits'];
        $value    = (float) $r['totalValue'];
        $low      = (int) $r['lowCount'];
        $out      = (int) $r['outCount'];
        $ok       = max(0, $items - $low - $out);
        $label    = $r['periodLabel'];

        // ---------- chart 1: stock status of every item (size / color) ----------
        $status = [];
        foreach ([['In stock', $ok], ['Low stock', $low], ['Out of stock', $out]] as [$name, $n]) {
            if ($n > 0) {
                $status[] = ['label' => $name, 'count' => $n, 'total' => $n, 'pct' => $items ? round($n / $items * 100) : 0,
                             'text' => $n . ' item(s) · ' . ($items ? round($n / $items * 100) : 0) . '%'];
            }
        }

        // ---------- chart 2: products holding the most stock value ----------
        $byProduct = $variants->groupBy(fn ($v) => $v->product->name)
            ->map(fn ($g) => (float) $g->sum(fn ($v) => $v->quantity * $v->selling_price))
            ->sortDesc()->take(8);
        $topProducts = [];
        foreach ($byProduct as $name => $val) {
            $pct = $value > 0 ? round($val / $value * 100) : 0;
            $topProducts[] = ['label' => $name, 'count' => 0, 'total' => round($val, 2), 'pct' => $pct, 'text' => $money($val) . ' · ' . $pct . '%'];
        }

        // ---------- the words ----------
        $attention = $variants->filter(fn ($v) => $v->isOutOfStock() || $v->isLowStock())
            ->sortBy(fn ($v) => $v->quantity)->take(3)
            ->map(fn ($v) => $v->product->name . ' (' . $v->label . ')')->values()->all();

        if ($items === 0) {
            $summary = ['No items match this report. Stock figures are as of ' . now()->format('M d, Y h:i A') . '.'];
            $statusExplain = 'There are no items to show, so the chart is empty.';
            $valueExplain  = 'There are no items to show, so there is no stock value breakdown.';
            $conclusion    = 'No items match this report.';
        } else {
            $summary = [
                'Stock is as of ' . now()->format('M d, Y h:i A') . ($r['onlyAttention'] ? ' (showing low and out-of-stock items only)' : '')
                    . ". The report lists {$items} item(s) (product, size and color) with {$units} unit(s) on hand worth {$money($value)}.",
                "{$low} item(s) are low on stock and {$out} item(s) are out of stock.",
                "Movement for {$label}: {$r['periodReceived']} unit(s) received, {$r['periodSold']} unit(s) sold"
                    . ($r['periodAdjusted'] !== 0 ? ', and ' . ($r['periodAdjusted'] > 0 ? '+' : '') . $r['periodAdjusted'] . ' unit(s) changed by physical counts.' : ', with no changes from physical counts.'),
            ];
            $statusExplain = 'The doughnut chart shows how many items are in stock, low on stock (at or below their "Low at" level) or out of stock.';
            $topName = array_key_first($byProduct->all());
            $valueExplain  = 'The bar chart shows the products holding the most stock value'
                . ($topName !== null ? "; {$topName} is the largest at {$money($byProduct->first())}." : '.');
            $conclusion = "Stock on hand is {$units} unit(s) worth {$money($value)}."
                . ($low + $out > 0
                    ? " {$low} item(s) are low and {$out} are out of stock; attention is needed on " . implode(', ', $attention) . '.'
                    : ' All listed items are above their low-stock level.')
                . " In {$label}, {$r['periodReceived']} unit(s) came in and {$r['periodSold']} were sold.";
        }

        return [
            'periodLabel'   => $label,
            'preparedBy'    => $user?->name ?? '—',
            'preparedRole'  => $user ? ucfirst($user->role) : '',
            'datePrepared'  => now()->format('M d, Y h:i A'),
            'summary'       => $summary,
            'statusExplain' => $statusExplain,
            'valueExplain'  => $valueExplain,
            'conclusion'    => $conclusion,
            'status'        => $status,
            'statusLabels'  => array_column($status, 'label'),
            'statusCounts'  => array_column($status, 'count'),
            'topProducts'   => $topProducts,
            'topLabels'     => array_column($topProducts, 'label'),
            'topValues'     => array_column($topProducts, 'total'),
        ];
    }
}
