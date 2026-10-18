<?php

namespace App\Support;

use Barryvdh\DomPDF\Facade\Pdf;

/**
 * One layout for every export (Sales Records, Sales Report, Inventory Report), as a PDF.
 *
 * $d = [
 *   'title'       => 'Inventory Report',
 *   'filename'    => 'treklite-inventory-20261015-0731',      // no extension
 *   'subtitle'    => ['line 1', 'line 2'],                    // small grey lines under the title
 *   'summary'     => [['Units on hand', 450, 'int'], ...],    // [label, value, type]
 *   'columns'     => [['key' => 'product', 'label' => 'Product', 'type' => 'text', 'align' => 'left', 'width' => 24, 'pdf' => true], ...],
 *   'rows'        => [['product' => 'Bagtas', ...], ...],     // raw values, keyed by column key
 *   'muted'       => [3, 7],                                  // row numbers to grey out + strike through (e.g. voided sales)
 *   'totals'      => ['label' => 'Total', 'values' => ['quantity' => 450]],   // or null
 *   'footnote'    => 'text',                                  // or null
 *   'orientation' => 'landscape' | 'portrait',
 * ]
 *
 * Column types: text, int, dash (0 shows as a dash), signed (+/-, 0 as a dash), money, datetime.
 */
class TableExport
{
    private const GREEN = '1E5B31';

    public static function download(array $d, string $format)
    {
        $d += ['subtitle' => [], 'summary' => [], 'muted' => [], 'totals' => null, 'footnote' => null, 'orientation' => 'portrait',
               'analysis' => [], 'conclusion' => null, 'prepared_by' => auth()->user()?->name ?? ''];

        // PDF only: an Excel file can be edited (prices, totals), which would put the reports at risk.
        return self::pdf($d);
    }

    /** Text as it prints in the PDF (same look as the screen). */
    public static function text(array $col, $v): string
    {
        if ($v === null || $v === '') {
            return '';
        }

        return match ($col['type'] ?? 'text') {
            'money'    => '₱' . number_format((float) $v, 2),
            'int'      => number_format((float) $v),
            'dash'     => (float) $v == 0 ? '–' : number_format((float) $v),
            'signed'   => (float) $v == 0 ? '–' : (((float) $v > 0 ? '+' : '') . number_format((float) $v)),
            'datetime' => $v instanceof \DateTimeInterface ? $v->format('M d, Y h:i A') : (string) $v,
            default    => (string) $v,
        };
    }

    // ------------------------------------------------------------------ PDF

    private static function pdf(array $d)
    {
        @set_time_limit(120);

        // Only the columns meant for paper.
        $d['columns'] = array_values(array_filter($d['columns'], fn ($c) => $c['pdf'] ?? true));

        return Pdf::loadView('exports.table', ['d' => $d])
            ->setPaper('a4', $d['orientation'])
            ->download($d['filename'] . '.pdf');
    }
}
