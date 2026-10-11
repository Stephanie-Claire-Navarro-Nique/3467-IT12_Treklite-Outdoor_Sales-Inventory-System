@extends('layouts.app')

@section('title', 'Sales Records')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Sales Records</h3>
        <div class="d-flex gap-2">
            @if (auth()->user()->hasFullAccess())
                {{-- Downloads exactly what the filters below show --}}
                <a href="{{ route('sales.export', request()->query()) }}" class="btn btn-outline-secondary"><i class="bi bi-download"></i> Export CSV</a>
            @endif
            <a href="{{ route('sales.create') }}" class="btn btn-success">+ New Sale</a>
        </div>
    </div>

    @php
        $hasFilter = $q !== '' || $from || $to || $cashier || $payment !== '';
        $today     = now()->toDateString();
    @endphp

    {{-- Filters --}}
    <form method="GET" action="{{ route('sales.index') }}" class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-lg-3 col-md-6">
                    <label class="form-label small mb-0">Search</label>
                    <input type="search" name="q" value="{{ $q }}" class="form-control form-control-sm" placeholder="Invoice no., customer, contact">
                </div>
                <div class="col-lg-2 col-md-3 col-6">
                    <label class="form-label small mb-0">From</label>
                    <input type="date" name="from" value="{{ $from }}" class="form-control form-control-sm">
                </div>
                <div class="col-lg-2 col-md-3 col-6">
                    <label class="form-label small mb-0">To</label>
                    <input type="date" name="to" value="{{ $to }}" class="form-control form-control-sm">
                </div>
                @if ($isOwner)
                    <div class="col-lg-2 col-md-4 col-6">
                        <label class="form-label small mb-0">Cashier</label>
                        <select name="cashier" class="form-select form-select-sm">
                            <option value="">All cashiers</option>
                            @foreach ($cashiers as $c)
                                <option value="{{ $c->id }}" @selected($cashier === $c->id)>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-lg-2 col-md-4 col-6">
                    <label class="form-label small mb-0">Payment</label>
                    <select name="payment" class="form-select form-select-sm">
                        <option value="">All payments</option>
                        @foreach (\App\Models\Sale::PAYMENT_METHODS as $key => $label)
                            <option value="{{ $key }}" @selected($payment === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto d-flex gap-2">
                    <button class="btn btn-sm btn-success"><i class="bi bi-funnel"></i> Filter</button>
                    @if ($hasFilter)
                        <a href="{{ route('sales.index') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
                    @endif
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2 mt-2 small align-items-center">
                <span class="text-muted">Quick:</span>
                <a class="btn btn-sm btn-outline-secondary py-0" href="{{ route('sales.index', ['from' => $today, 'to' => $today]) }}">Today</a>
                <a class="btn btn-sm btn-outline-secondary py-0" href="{{ route('sales.index', ['from' => now()->startOfWeek()->toDateString(), 'to' => $today]) }}">This week</a>
                <a class="btn btn-sm btn-outline-secondary py-0" href="{{ route('sales.index', ['from' => now()->startOfMonth()->toDateString(), 'to' => $today]) }}">This month</a>
            </div>
        </div>
    </form>

    <div class="small text-muted mb-2">
        {{ number_format($summary->cnt) }} sale(s) &middot; Total <strong class="text-dark">₱{{ number_format($summary->total, 2) }}</strong>
        @if ($hasFilter) <span>(with the filters above)</span> @endif
        @if ($voidedCount > 0) &middot; <span class="text-danger">{{ $voidedCount }} voided (not counted)</span> @endif
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>Processed By</th>
                        <th>Payment</th>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sales as $sale)
                        <tr>
                            <td>
                                <a href="{{ route('sales.show', $sale) }}" class="{{ $sale->isVoided() ? 'text-decoration-line-through text-muted' : '' }}">{{ $sale->invoice_number }}</a>
                                @if ($sale->isVoided()) <span class="badge bg-danger">VOIDED</span> @endif
                            </td>
                            <td>{{ $sale->sold_at->format('M d, Y h:i A') }}</td>
                            <td>{{ $sale->customer_name }}</td>
                            <td>{{ $sale->user->name }}</td>
                            <td>
                                {{ $sale->payment_label }}
                                @if ($sale->payment_reference)
                                    <div class="small text-muted">Ref: {{ $sale->payment_reference }}</div>
                                @endif
                            </td>
                            <td class="text-end {{ $sale->isVoided() ? 'text-decoration-line-through text-muted' : '' }}">₱{{ number_format($sale->total_amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">{{ $hasFilter ? 'No sales match these filters.' : 'No sales recorded yet.' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $sales->links() }}</div>
@endsection
