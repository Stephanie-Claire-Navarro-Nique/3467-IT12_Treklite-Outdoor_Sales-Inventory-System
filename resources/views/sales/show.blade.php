@extends('layouts.app')

@section('title', 'Invoice ' . $sale->invoice_number)

@section('content')
    <style>
        @media print {
            .sidebar, .topbar, .no-print, .alert, .sidebar-backdrop { display: none !important; }
            .main-wrapper { margin-left: 0 !important; }
            body { background: #fff !important; }
            .invoice { box-shadow: none !important; border: 0 !important; }
        }
    </style>

    <div class="d-flex justify-content-between mb-3 no-print">
        <a href="{{ route('sales.create') }}" class="btn btn-success"><i class="bi bi-cart-plus"></i> New Sale</a>
        <div class="d-flex gap-2">
            <a href="{{ route('sales.index') }}" class="btn btn-outline-secondary">Sales Records</a>
            <button onclick="window.print()" class="btn btn-outline-dark"><i class="bi bi-printer"></i> Print</button>
            @if (auth()->user()->hasFullAccess() && ! $sale->isVoided())
                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#void-modal"><i class="bi bi-x-octagon"></i> Void Sale</button>
            @endif
        </div>
    </div>

    @if ($errors->has('reason'))
        <div class="alert alert-danger no-print">{{ $errors->first('reason') }}</div>
    @endif

    @if ($sale->isVoided())
        <div class="alert alert-danger mx-auto" style="max-width: 760px;">
            <strong><i class="bi bi-x-octagon"></i> THIS INVOICE WAS VOIDED</strong> and is not counted in sales totals.
            <div class="small mt-1">
                {{ $sale->voided_at->format('M d, Y h:i A') }} by {{ $sale->voidedBy?->name ?? 'unknown' }}.
                Reason: {{ $sale->void_reason }}
            </div>
        </div>
    @endif

    <div class="card shadow-sm invoice mx-auto {{ $sale->isVoided() ? 'border-danger' : '' }}" style="max-width: 760px;">
        <div class="card-body p-4">
            <div class="text-center mb-3">
                <h4 class="mb-0">Treklite Outdoor</h4>
                <div class="small text-muted">Door 1, Amarelio Building, Green Meadow Subdivision</div>
                <div class="fw-bold mt-2 text-uppercase">Sales Invoice @if ($sale->isVoided()) <span class="badge bg-danger">VOIDED</span> @endif</div>
            </div>

            <div class="row small mb-3">
                <div class="col-7">
                    <div><span class="text-muted">Sold to:</span> <strong>{{ $sale->customer_name }}</strong></div>
                    <div><span class="text-muted">Address:</span> {{ $sale->customer_address ?: '—' }}</div>
                    <div><span class="text-muted">Contact:</span> {{ $sale->customer_contact ?: '—' }}</div>
                </div>
                <div class="col-5 text-end">
                    <div><span class="text-muted">Invoice No.:</span> <strong>{{ $sale->invoice_number }}</strong></div>
                    <div><span class="text-muted">Date:</span> {{ $sale->sold_at->format('M d, Y h:i A') }}</div>
                    <div><span class="text-muted">Cashier:</span> {{ $sale->user->name }}</div>
                </div>
            </div>

            <table class="table table-sm">
                <thead>
                    <tr><th>Item</th><th>Size / Color</th><th class="text-end">Qty</th><th class="text-end">Unit Price</th><th class="text-end">Amount</th></tr>
                </thead>
                <tbody>
                    @foreach ($sale->items as $item)
                        <tr>
                            <td>{{ $item->variant->product->name }}<div class="small text-muted">{{ $item->variant->label }}</div></td>
                            <td>{{ $item->variant->label }}</td>
                            <td class="text-end">{{ $item->quantity }}</td>
                            <td class="text-end">₱{{ number_format($item->unit_price, 2) }}</td>
                            <td class="text-end">₱{{ number_format($item->subtotal, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr><th colspan="4" class="text-end">Total</th><th class="text-end">₱{{ number_format($sale->total_amount, 2) }}</th></tr>
                </tfoot>
            </table>

            <div class="small">
                <div><span class="text-muted">Payment method:</span> {{ $sale->payment_label }}</div>
                <div><span class="text-muted">Amount received:</span> ₱{{ number_format($sale->amount_tendered, 2) }}
                    &nbsp; <span class="text-muted">Change:</span> ₱{{ number_format($sale->change_amount, 2) }}</div>
                @if ($sale->payment_reference)
                    {{-- Only old sales have a reference number --}}
                    <div><span class="text-muted">Reference no.:</span> {{ $sale->payment_reference }}</div>
                @endif
            </div>

            @if ($sale->payment_proof_url)
                <div class="mt-3 no-print">
                    <div class="small text-muted">Payment confirmation</div>
                    <a href="{{ $sale->payment_proof_url }}" target="_blank"><img src="{{ $sale->payment_proof_url }}" alt="Payment proof" style="max-height: 160px;" class="img-thumbnail"></a>
                </div>
            @endif

            <div class="text-center text-muted small mt-4">Thank you for shopping at Treklite Outdoor!</div>
        </div>
    </div>

    @if (auth()->user()->hasFullAccess() && ! $sale->isVoided())
        <div class="modal fade no-print" id="void-modal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('sales.void', $sale) }}" class="modal-content">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Void {{ $sale->invoice_number }}?</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="small text-muted mb-2">
                            The sale stays on record as VOIDED, the items go back to stock, and it is no longer counted in sales totals.
                            If the customer already paid, return their money yourself - the system does not handle refunds.
                        </p>
                        <label for="void-reason" class="form-label">Reason <span class="text-danger">*</span></label>
                        <textarea name="reason" id="void-reason" rows="3" maxlength="255" class="form-control" required placeholder="e.g. Customer returned the tent, wrong size encoded">{{ old('reason') }}</textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button class="btn btn-danger">Void this sale</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection
