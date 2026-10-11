@extends('layouts.app')

@section('title', 'Stock History')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h3 class="mb-0">Stock History</h3>
        <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back to Inventory</a>
    </div>

    @if ($variant)
        <div class="alert alert-light border py-2 small">
            Showing only <strong>{{ $variant->product->name }}</strong> ({{ $variant->label }}) &middot; now in stock: <strong>{{ $variant->quantity }}</strong>
            &middot; <a href="{{ route('inventory.history') }}">show everything</a>
        </div>
    @elseif ($product)
        <div class="alert alert-light border py-2 small">
            Showing only <strong>{{ $product->name }}</strong> &middot; <a href="{{ route('inventory.history') }}">show everything</a>
        </div>
    @endif

    <form method="GET" action="{{ route('inventory.history') }}" class="card shadow-sm mb-3">
        <div class="card-body py-2 d-flex flex-wrap gap-3 align-items-end">
            @if ($variant)<input type="hidden" name="variant" value="{{ $variant->id }}">@endif
            @if ($product)<input type="hidden" name="product" value="{{ $product->id }}">@endif
            <div>
                <label class="form-label small mb-0">Type</label>
                <select name="type" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="receive" @selected($type === 'receive')>Received (deliveries)</option>
                    <option value="count"   @selected($type === 'count')>Counted</option>
                    <option value="sale"    @selected($type === 'sale')>Sold</option>
                    <option value="void"    @selected($type === 'void')>Sale voided</option>
                </select>
            </div>
            <div class="form-check mb-1">
                <input class="form-check-input" type="checkbox" name="issues" value="1" id="issues" @checked($issues)>
                <label class="form-check-label small" for="issues">Only counts that did not match</label>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-success">Filter</button>
                <a href="{{ route('inventory.history', array_filter(['variant' => $variant?->id, 'product' => $product?->id])) }}" class="btn btn-sm btn-outline-secondary">Clear</a>
            </div>
        </div>
    </form>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Item</th>
                        <th>What happened</th>
                        <th class="text-end">Before</th>
                        <th class="text-end">Change</th>
                        <th class="text-end">After</th>
                        <th>By</th>
                        <th>Note</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($movements as $m)
                        @php $mismatch = $m->isMismatch(); @endphp
                        <tr class="{{ $mismatch ? 'table-danger' : '' }}">
                            <td class="small text-nowrap">{{ $m->created_at->format('M d, Y h:i A') }}</td>
                            <td>
                                {{ $m->variant->product->name }}
                                <div class="small text-muted">{{ $m->variant->label }}</div>
                            </td>
                            <td>
                                @if ($m->type === 'count')
                                    <span class="badge {{ $mismatch ? 'bg-danger' : 'bg-secondary' }}">Counted</span>
                                    <div class="small {{ $mismatch ? 'text-danger fw-semibold' : 'text-muted' }}">
                                        @if ($mismatch)
                                            System {{ $m->expected }} &rarr; counted {{ $m->quantity_after }}
                                            ({{ $m->quantity_change > 0 ? 'over by ' . $m->quantity_change : 'short by ' . abs($m->quantity_change) }})
                                        @else
                                            Matched the system
                                        @endif
                                    </div>
                                @elseif ($m->type === 'receive')
                                    <span class="badge bg-success">Received</span>
                                    <div class="small text-muted">{{ $m->quantity_change }} unit(s) delivered</div>
                                @elseif ($m->type === 'void')
                                    <span class="badge bg-warning text-dark">Sale voided</span>
                                    <div class="small text-muted">{{ abs($m->quantity_change) }} unit(s) returned to stock</div>
                                @else
                                    <span class="badge bg-primary">Sold</span>
                                @endif
                            </td>
                            <td class="text-end">{{ $m->expected }}</td>
                            <td class="text-end fw-semibold {{ $m->quantity_change > 0 ? 'text-success' : ($m->quantity_change < 0 ? 'text-danger' : 'text-muted') }}">
                                {{ $m->quantity_change > 0 ? '+' : '' }}{{ $m->quantity_change }}
                            </td>
                            <td class="text-end">{{ $m->quantity_after }}</td>
                            <td class="small">{{ $m->user->name ?? '—' }}</td>
                            <td class="small text-muted">{{ $m->note }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">No stock movements found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $movements->links() }}</div>
@endsection
