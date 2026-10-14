@extends('layouts.app')

@section('title', 'Inventory Report')

@section('content')
    <style>
        @media print {
            .sidebar, .topbar, .no-print, .sidebar-backdrop { display: none !important; }
            .main-wrapper { margin-left: 0 !important; }
            body { background: #fff !important; }
        }
    </style>

    @php
        // Every link on this page keeps the chosen period, date and the low-stock switch.
        $link = fn (array $over = []) => route('reports.inventory', array_filter(array_merge([
            'period'    => $period,
            'date'      => $date->toDateString(),
            'attention' => $onlyAttention ? 1 : null,
        ], $over), fn ($v) => $v !== null));
        $periods = ['day' => 'Day', 'week' => 'Week', 'month' => 'Month', 'year' => 'Year'];
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Inventory Report</h3>
        <button onclick="window.print()" class="btn btn-outline-dark no-print"><i class="bi bi-printer"></i> Print</button>
    </div>

    {{-- Report tabs --}}
    <ul class="nav nav-tabs mb-3 no-print">
        <li class="nav-item"><a class="nav-link" href="{{ route('reports.sales') }}">Sales Report</a></li>
        <li class="nav-item"><a class="nav-link active" href="{{ route('reports.inventory') }}">Inventory Report</a></li>
    </ul>

    {{-- Filter: Day / Week / Month / Year --}}
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3 no-print">
        <div class="btn-group" role="group" aria-label="Report period">
            @foreach ($periods as $key => $label)
                <a href="{{ $link(['period' => $key]) }}"
                   class="btn btn-sm {{ $period === $key ? 'btn-success' : 'btn-outline-success' }}">{{ $label }}</a>
            @endforeach
        </div>

        <div class="btn-group" role="group" aria-label="Move period">
            <a href="{{ $link(['date' => $prevDate->toDateString()]) }}" class="btn btn-sm btn-outline-secondary" title="Previous {{ $period }}"><i class="bi bi-chevron-left"></i></a>
            <a href="{{ $link(['date' => now()->toDateString()]) }}" class="btn btn-sm btn-outline-secondary">Today</a>
            <a href="{{ $link(['date' => $nextDate->toDateString()]) }}" class="btn btn-sm btn-outline-secondary" title="Next {{ $period }}"><i class="bi bi-chevron-right"></i></a>
        </div>

        <form method="GET" class="d-flex align-items-center gap-2 mb-0">
            <input type="hidden" name="period" value="{{ $period }}">
            @if ($onlyAttention)<input type="hidden" name="attention" value="1">@endif
            <input type="date" name="date" value="{{ $date->toDateString() }}" class="form-control form-control-sm"
                   style="width:auto" onchange="this.form.submit()" aria-label="Pick a date">
        </form>

        <form method="GET" class="form-check form-switch mb-0 ms-md-3">
            <input type="hidden" name="period" value="{{ $period }}">
            <input type="hidden" name="date" value="{{ $date->toDateString() }}">
            <input class="form-check-input" type="checkbox" role="switch" name="attention" value="1" id="attention"
                   @checked($onlyAttention) onchange="this.form.submit()">
            <label class="form-check-label" for="attention">Show only low-stock and out-of-stock items</label>
        </form>
    </div>

    <div class="small text-muted mb-2">
        Stock as of {{ now()->format('M d, Y h:i A') }}
        &middot; Received / Sold / Adjusted for <strong>{{ $periodLabel }}</strong>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3"><div class="card shadow-sm h-100"><div class="card-body">
            <div class="text-muted small">Units on hand</div><div class="fs-4 fw-bold">{{ number_format($totalUnits) }}</div></div></div></div>
        <div class="col-6 col-md-3"><div class="card shadow-sm h-100"><div class="card-body">
            <div class="text-muted small">Stock value</div><div class="fs-4 fw-bold">₱{{ number_format($totalValue, 2) }}</div></div></div></div>
        {{-- Low stock: black number on a yellow background --}}
        <div class="col-6 col-md-3"><div class="card shadow-sm h-100 border-0 bg-warning"><div class="card-body">
            <div class="small text-dark">Low stock</div><div class="fs-4 fw-bold text-dark">{{ $lowCount }}</div></div></div></div>
        {{-- Out of stock: black number on a red background --}}
        <div class="col-6 col-md-3"><div class="card shadow-sm h-100 border-0 bg-danger"><div class="card-body">
            <div class="small text-dark">Out of stock</div><div class="fs-4 fw-bold text-dark">{{ $outCount }}</div></div></div></div>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Size / Color</th>
                        <th class="text-end">Quantity</th>
                        <th class="text-end" title="Delivered in {{ $periodLabel }}">Received</th>
                        <th class="text-end" title="Sold in {{ $periodLabel }} (voided sales taken out)">Sold</th>
                        <th class="text-end" title="Changed by a physical count in {{ $periodLabel }}">Adjusted</th>
                        <th class="text-end">Low at</th>
                        <th class="text-center">Status</th>
                        <th class="text-end">Unit price</th>
                        <th class="text-end">Stock value</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($variants as $v)
                        <tr>
                            <td>{{ $v->product->name }}</td>
                            <td>{{ $v->label }}</td>
                            <td class="text-end">{{ $v->quantity }}</td>
                            <td class="text-end {{ $v->period_received ? '' : 'text-muted' }}">{{ $v->period_received ?: '–' }}</td>
                            <td class="text-end {{ $v->period_sold ? '' : 'text-muted' }}">{{ $v->period_sold ?: '–' }}</td>
                            <td class="text-end {{ $v->period_adjusted ? '' : 'text-muted' }}">{{ $v->period_adjusted ? ($v->period_adjusted > 0 ? '+' : '') . $v->period_adjusted : '–' }}</td>
                            <td class="text-end text-muted">{{ $v->low_stock_threshold }}</td>
                            <td class="text-center">
                                @if ($v->isOutOfStock())
                                    <span class="badge bg-danger">Out of stock</span>
                                @elseif ($v->isLowStock())
                                    <span class="badge bg-warning text-dark">Low stock</span>
                                @else
                                    <span class="badge bg-success">In-Stock</span>
                                @endif
                            </td>
                            <td class="text-end">₱{{ number_format($v->selling_price, 2) }}</td>
                            <td class="text-end">₱{{ number_format($v->quantity * $v->selling_price, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-center text-muted py-4">Nothing to show.</td></tr>
                    @endforelse
                </tbody>
                @if ($variants->isNotEmpty())
                    <tfoot>
                        <tr class="fw-bold">
                            <td colspan="2" class="text-end">Total</td>
                            <td class="text-end">{{ number_format($totalUnits) }}</td>
                            <td class="text-end">{{ number_format($periodReceived) }}</td>
                            <td class="text-end">{{ number_format($periodSold) }}</td>
                            <td class="text-end">{{ ($periodAdjusted > 0 ? '+' : '') . number_format($periodAdjusted) }}</td>
                            <td colspan="3"></td>
                            <td class="text-end">₱{{ number_format($totalValue, 2) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
@endsection
