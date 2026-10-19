@php
    $cols      = $d['columns'];
    $widthSum  = max(1, array_sum(array_map(fn ($c) => $c['width'] ?? 15, $cols)));
    $pct       = fn ($c) => round((($c['width'] ?? 15) / $widthSum) * 100, 2);
    $keys      = array_column($cols, 'key');

    // The "Total" label spans every column in front of the first total value.
    $span = 1;
    if (! empty($d['totals'])) {
        foreach ($keys as $i => $k) {
            if (isset($d['totals']['values'][$k])) { $span = max(1, $i); break; }
        }
    }
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $d['title'] }}</title>
    <style>
        @page { margin: 14mm 10mm 18mm 10mm; }
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 7.5pt; color: #1f2933; }

        .brand   { font-size: 8pt; font-weight: bold; color: #1e5b31; letter-spacing: .5px; text-transform: uppercase; }
        h1       { font-size: 15pt; margin: 2px 0 4px 0; color: #111; }
        .sub     { font-size: 7.5pt; color: #6b7a72; margin: 0 0 2px 0; }

        table.summary { width: 100%; border-collapse: separate; border-spacing: 4px 0; margin: 8px -4px 10px -4px; }
        table.summary td { border: 1px solid #d3dbd6; background: #f7faf8; padding: 5px 8px; width: 25%; }
        .s-label { font-size: 6.5pt; color: #6b7a72; }
        .s-value { font-size: 10pt; font-weight: bold; color: #111; }

        table.data { width: 100%; border-collapse: collapse; table-layout: fixed; margin-top: 4px; }
        table.data th { background: #1e5b31; color: #fff; font-weight: bold; padding: 5px 5px; border: 1px solid #1e5b31; font-size: 7pt; }
        table.data td { padding: 4px 5px; border: 1px solid #d3dbd6; vertical-align: middle; word-wrap: break-word; }
        table.data thead { display: table-header-group; }
        table.data tr { page-break-inside: avoid; }
        table.data tbody tr.alt td { background: #f7faf8; }
        table.data tr.muted td { color: #8a948e; text-decoration: line-through; }
        table.data tr.total td { background: #e6f4ea; font-weight: bold; border-top: 2px solid #1e5b31; }

        .l { text-align: left; } .r { text-align: right; } .c { text-align: center; }
        .empty { text-align: center; color: #6b7a72; padding: 14px 0; }
        .note { margin-top: 8px; font-size: 7pt; color: #6b7a72; font-style: italic; }

        .box { border: 1px solid #d3dbd6; background: #f7faf8; padding: 6px 9px; margin: 6px 0 8px 0; }
        .box .bt { font-size: 8pt; font-weight: bold; color: #1e5b31; margin-bottom: 3px; }
        .box p { margin: 0 0 3px 0; font-size: 7.5pt; line-height: 1.4; }
        table.charts { width: 100%; border-collapse: separate; border-spacing: 6px 0; margin: 0 -6px 8px -6px; page-break-inside: avoid; }
        table.charts > tbody > tr > td { border: 1px solid #d3dbd6; padding: 6px 8px; vertical-align: top; }
        .ct { font-size: 8pt; font-weight: bold; margin-bottom: 4px; }
        .cx { font-size: 6.8pt; color: #4b5a52; margin-top: 4px; line-height: 1.4; }
        table.vbars { width: 100%; table-layout: fixed; border-collapse: collapse; }
        table.vbars td { vertical-align: bottom; text-align: center; padding: 0 1px; }
        .vbar { background: #1e5b31; width: 100%; }
        .vlab { font-size: 5.5pt; color: #6b7a72; }
        .vval { font-size: 5.5pt; color: #333; }
        .hbar-track { background: #e9eeeb; height: 7px; width: 100%; }
        .hbar { background: #1e5b31; height: 7px; }
        .sig { width: 100%; margin-top: 34px; page-break-inside: avoid; }
        .sig td { width: 45%; text-align: center; border-top: 1px solid #333; padding-top: 3px; font-size: 7.5pt; }
        .sig td.gap { width: 10%; border-top: 0; }

        .footer { position: fixed; bottom: -12mm; left: 0; right: 0; font-size: 6.5pt; color: #6b7a72; border-top: 1px solid #d3dbd6; padding-top: 3px; }
    </style>
</head>
<body>
    <div class="footer">
        Treklite Outdoor &middot; {{ $d['title'] }} &middot; Generated {{ now()->format('M d, Y h:i A') }}
    </div>

    <div class="brand">Treklite Outdoor</div>
    <h1>{{ $d['title'] }}</h1>
    @foreach ($d['subtitle'] as $line)
        <div class="sub">{{ $line }}</div>
    @endforeach

    @if (! empty($d['summary']))
        <table class="summary">
            @foreach (array_chunk($d['summary'], 4) as $chunk)
                <tr>
                    @foreach ($chunk as [$label, $value, $type])
                        <td>
                            <div class="s-label">{{ $label }}</div>
                            <div class="s-value">{{ \App\Support\TableExport::text(['type' => $type], $value) }}</div>
                        </td>
                    @endforeach
                    @for ($pad = count($chunk); $pad < 4; $pad++)
                        <td style="border-color:#fff; background:#fff;">&nbsp;</td>
                    @endfor
                </tr>
            @endforeach
        </table>
    @endif

    @if (! empty($d['explanation']['lines']))
        <div class="box">
            <div class="bt">{{ $d['explanation']['heading'] }}</div>
            @foreach ($d['explanation']['lines'] as $line)
                <p>{{ $line }}</p>
            @endforeach
        </div>
    @endif

    @if (! empty($d['charts']))
        @php $palette = ['#1e5b31', '#0d6efd', '#6f42c1', '#fd7e14', '#20c997']; @endphp
        <table class="charts">
            <tr>
                @foreach ($d['charts'] as $ch)
                    <td style="width: {{ count($d['charts']) > 1 && ($ch['type'] ?? '') === 'bars' ? 58 : (count($d['charts']) > 1 && collect($d['charts'])->contains(fn ($c) => ($c['type'] ?? '') === 'bars') ? 42 : round(100 / max(1, count($d['charts'])))) }}%">
                        <div class="ct">{{ $ch['title'] }}</div>
                        @if (($ch['type'] ?? '') === 'bars')
                            @php
                                $vals = $ch['values'] ?? [];
                                $max  = max(1, count($vals) ? max($vals) : 1);
                                $n    = count($vals);
                                // Long ranges: label about every Nth bar so text never overlaps
                                $step = max(1, (int) ceil($n / 12));
                            @endphp
                            @if (array_sum($vals) <= 0)
                                <div class="empty">No sales in this range.</div>
                            @else
                                <table class="vbars">
                                    <tr style="height: 80px">
                                        @foreach ($vals as $i => $v)
                                            <td><div class="vbar" style="height: {{ $v > 0 ? max(2, round($v / $max * 70)) : 0 }}px"></div></td>
                                        @endforeach
                                    </tr>
                                    <tr>
                                        @foreach ($ch['labels'] as $i => $lab)
                                            <td class="vlab">{{ $i % $step === 0 ? $lab : '' }}</td>
                                        @endforeach
                                    </tr>
                                </table>
                                <div class="cx">Highest bar: ₱{{ number_format($max, 2) }}</div>
                            @endif
                        @else
                            @if (empty($ch['rows']))
                                <div class="empty">No sales in this range.</div>
                            @else
                                @foreach ($ch['rows'] as $i => $row)
                                    <div style="font-size: 7pt; margin-top: 4px;">
                                        <span style="color: {{ $palette[$i % count($palette)] }}; font-weight: bold;">&#9632;</span>
                                        {{ $row['label'] }} &ndash; {{ $row['text'] ?? ('(' . $row['count'] . ') ₱' . number_format($row['total'], 2) . ' · ' . $row['pct'] . '%') }}
                                    </div>
                                    <div class="hbar-track"><div class="hbar" style="width: {{ max(1, $row['pct']) }}%; background: {{ $palette[$i % count($palette)] }}"></div></div>
                                @endforeach
                            @endif
                        @endif
                        @if (! empty($ch['explain']))
                            <div class="cx">{{ $ch['explain'] }}</div>
                        @endif
                    </td>
                @endforeach
            </tr>
        </table>
    @endif

    <table class="data">
        <thead>
            <tr>
                @foreach ($cols as $c)
                    <th class="{{ ['left' => 'l', 'right' => 'r', 'center' => 'c'][$c['align'] ?? 'left'] }}" style="width: {{ $pct($c) }}%">{{ $c['label'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($d['rows'] as $n => $row)
                <tr class="{{ in_array($n, $d['muted'], true) ? 'muted' : ($n % 2 ? 'alt' : '') }}">
                    @foreach ($cols as $c)
                        <td class="{{ ['left' => 'l', 'right' => 'r', 'center' => 'c'][$c['align'] ?? 'left'] }}">{{ \App\Support\TableExport::text($c, $row[$c['key']] ?? null) }}</td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ count($cols) }}" class="empty">Nothing to show.</td></tr>
            @endforelse

            @if (! empty($d['totals']) && count($d['rows']) > 0)
                <tr class="total">
                    <td colspan="{{ $span }}" class="r">{{ $d['totals']['label'] }}</td>
                    @foreach ($cols as $i => $c)
                        @continue($i < $span)
                        <td class="{{ ['left' => 'l', 'right' => 'r', 'center' => 'c'][$c['align'] ?? 'left'] }}">{{ \App\Support\TableExport::text($c, $d['totals']['values'][$c['key']] ?? null) }}</td>
                    @endforeach
                </tr>
            @endif
        </tbody>
    </table>

    @if ($d['footnote'])
        <div class="note">{{ $d['footnote'] }}</div>
    @endif

    @if (! empty($d['conclusion']))
        <div class="box" style="margin-top: 10px;">
            <div class="bt">Conclusion</div>
            <p>{{ $d['conclusion'] }}</p>
        </div>
    @endif

    @if (! empty($d['signatures']))
        <table class="sig">
            <tr>
                @foreach ($d['signatures'] as $i => $line)
                    @if ($i > 0)<td class="gap"></td>@endif
                    <td>{{ $line }}</td>
                @endforeach
            </tr>
        </table>
    @endif
</body>
</html>
