@extends('layouts.app')

@section('title', 'Inventory')

@section('content')
    @php
        $isOwner = auth()->user()->hasFullAccess();
        $noBrand = \App\Http\Controllers\InventoryController::NO_BRAND;
    @endphp

    <style>
        .thumb { width: 40px; height: 40px; flex-shrink: 0; }
        .status-badge { display: inline-block; width: 90px; }
        .brand-card { transition: box-shadow .15s, transform .15s, border-color .15s; border: 1px solid #e3e6e4; }
        .brand-card:hover { border-color: #1e5b31; box-shadow: 0 .4rem .9rem rgba(0,0,0,.12); transform: translateY(-2px); }
        /* The form is exactly as wide as the controls row (number box -> history button),
           pushed to the right of the cell. The note + hint underneath stretch to that same width. */
        .stock-form { display: grid; grid-template-columns: repeat(4, auto); gap: .25rem; width: fit-content; margin-left: auto; }
        .stock-form .qty { width: 105px; }
        .stock-form .mode { width: 100px; }
        .stock-form .note,
        .stock-form .hint { grid-column: 1 / -1; width: 0 !important; min-width: 100%; }
    </style>

    {{-- ===== Header: title + search ===== --}}
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h3 class="mb-0">Inventory</h3>
        <div class="d-flex gap-2 flex-wrap">
            @if ($isOwner)
                <a href="{{ route('inventory.history') }}" class="btn btn-outline-secondary"><i class="bi bi-clock-history"></i> Stock History</a>
                <a href="{{ route('products.create') }}" class="btn btn-outline-success"><i class="bi bi-plus-lg"></i> Add Product</a>
            @endif
        </div>
    </div>

    @if ($isOwner)
        <div class="alert alert-light border small py-2">
            <i class="bi bi-info-circle"></i>
            This page is for <strong>receiving deliveries</strong> and <strong>counting</strong> stock of items that already exist.
            New items (with their sizes, colors and opening stock) are added with the <strong>Add Product</strong> button above.
        </div>
    @endif

    <form method="GET" action="{{ route('inventory.index') }}" class="d-flex gap-2 mb-3">
        <input type="search" name="q" value="{{ $q }}" class="form-control" placeholder="Search model, brand, size, color...">
        <button class="btn btn-outline-secondary">Search</button>
    </form>

    {{-- ====================== 1. BRAND OVERVIEW ====================== --}}
    @if ($mode === 'brands')
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="text-uppercase text-muted mb-0">Brands</h6>
            <form method="GET" action="{{ route('inventory.index') }}" class="d-flex align-items-center gap-2">
                <label for="brand-status" class="small text-muted mb-0">Status</label>
                <select name="status" id="brand-status" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                    <option value="">All</option>
                    <option value="ok">OK</option>
                    <option value="low">Low stock</option>
                    <option value="out">Out of stock</option>
                </select>
            </form>
        </div>

        <div class="row g-3">
            @forelse ($brands as $b)
                @php $isNone = $b->brand_name === ''; @endphp
                <div class="col-xl-3 col-lg-4 col-sm-6">
                    <a href="{{ route('inventory.index', ['brand' => $isNone ? $noBrand : $b->brand_name]) }}" class="text-decoration-none text-reset">
                        <div class="card brand-card shadow-sm h-100">
                            <div class="card-body">
                                <div class="fw-bold fs-5">{{ $isNone ? 'No brand' : $b->brand_name }}</div>
                                <div class="text-muted small">{{ $b->product_count }} model(s)</div>
                                <div class="mt-2 fs-4 fw-semibold">{{ number_format($b->units) }} <span class="fs-6 text-muted">units</span></div>
                                <div class="mt-1">
                                    @if ($b->out_count > 0)<span class="badge bg-danger">{{ $b->out_count }} out of stock</span>@endif
                                    @if ($b->low_count > 0)<span class="badge bg-warning text-dark">{{ $b->low_count }} low</span>@endif
                                    @if ($b->out_count == 0 && $b->low_count == 0)<span class="badge bg-success">All OK</span>@endif
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            @empty
                <div class="col-12">
                    <div class="card shadow-sm"><div class="card-body text-center text-muted py-4">
                        No products yet.
                        @if ($isOwner) Use <strong>Add Product</strong> above to start tracking stock. @endif
                    </div></div>
                </div>
            @endforelse
        </div>

    {{-- ====================== 2. ITEM LIST ====================== --}}
    @else
        {{-- Where am I? --}}
        <nav class="small mb-2">
            <a href="{{ route('inventory.index') }}"><i class="bi bi-grid"></i> All brands</a>
            @if ($brand !== '')
                &rsaquo; <a href="{{ route('inventory.index', ['brand' => $brand]) }}">{{ $brand === $noBrand ? 'No brand' : $brand }}</a>
            @endif
            @if ($product)
                &rsaquo; <strong>{{ $product->name }}</strong>
            @endif
        </nav>

        {{-- Filters --}}
        <form method="GET" action="{{ route('inventory.index') }}" class="card shadow-sm mb-3">
            <div class="card-body py-2">
                <input type="hidden" name="brand" value="{{ $brand }}">
                @if ($product)<input type="hidden" name="product" value="{{ $product->id }}">@endif
                <div class="row g-2 align-items-end">
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label small mb-0">Search</label>
                        <input type="search" name="q" value="{{ $q }}" class="form-control form-control-sm" placeholder="Model name, size, color">
                    </div>
                    <div class="col-lg-2 col-md-3 col-6">
                        <label class="form-label small mb-0">Category</label>
                        <select name="category" class="form-select form-select-sm">
                            <option value="">All</option>
                            @foreach ($categories as $c)<option value="{{ $c }}" @selected($category === $c)>{{ $c }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-3 col-6">
                        <label class="form-label small mb-0">Size</label>
                        <select name="size" class="form-select form-select-sm">
                            <option value="">All</option>
                            @foreach ($sizes as $c)<option value="{{ $c }}" @selected($size === $c)>{{ $c }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-3 col-6">
                        <label class="form-label small mb-0">Color</label>
                        <select name="color" class="form-select form-select-sm">
                            <option value="">All</option>
                            @foreach ($colors as $c)<option value="{{ $c }}" @selected($color === $c)>{{ $c }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-3 col-6">
                        <label class="form-label small mb-0">Status</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">All</option>
                            <option value="ok"  @selected($status === 'ok')>OK</option>
                            <option value="low" @selected($status === 'low')>Low stock</option>
                            <option value="out" @selected($status === 'out')>Out of stock</option>
                        </select>
                    </div>
                    <div class="col-auto d-flex gap-2">
                        <button class="btn btn-sm btn-success"><i class="bi bi-funnel"></i> Filter</button>
                        <a href="{{ route('inventory.index', array_filter(['brand' => $brand, 'product' => $product?->id])) }}" class="btn btn-sm btn-outline-secondary">Clear</a>
                    </div>
                </div>
            </div>
        </form>

        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <thead>
                        <tr>
                            <th style="width:56px"></th>
                            <th>Model</th>
                            <th>Size / Color</th>
                            <th class="text-end" style="width:90px">In stock</th>
                            <th class="text-center" style="width:120px">Status</th>
                            @if ($isOwner)
                                <th class="text-end" style="width:360px">Receive / Count</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($variants as $v)
                            <tr class="{{ $v->isOutOfStock() || $v->isLowStock() ? 'table-danger' : '' }}">
                                <td>
                                    @if ($v->product->image_url)
                                        <img src="{{ $v->product->image_url }}" alt="" class="rounded thumb" style="object-fit:cover">
                                    @else
                                        <div class="rounded bg-light text-secondary d-flex align-items-center justify-content-center thumb"><i class="bi bi-image"></i></div>
                                    @endif
                                </td>
                                <td>
                                    {{ $v->product->name }}
                                    <div class="small text-muted">{{ collect([$v->product->brand, $v->product->category])->filter()->implode(' · ') }}</div>
                                </td>
                                <td>{{ $v->label }}</td>
                                <td class="text-end fw-semibold">{{ $v->quantity }}</td>
                                <td class="text-center">
                                    @if ($v->isOutOfStock())
                                        <span class="badge bg-danger status-badge">Out of stock</span>
                                    @elseif ($v->isLowStock())
                                        <span class="badge bg-warning text-dark status-badge">Low stock</span>
                                    @else
                                        <span class="badge bg-success status-badge">OK</span>
                                    @endif
                                </td>
                                @if ($isOwner)
                                    <td>
                                        {{-- One form: number + Receive/Count + Submit. The note box appears when a count does not match. --}}
                                        <form method="POST" action="{{ route('inventory.stock', $v) }}" class="stock-form" data-current="{{ $v->quantity }}">
                                            @csrf
                                            <input type="number" name="quantity" min="0" class="form-control form-control-sm qty" placeholder="Number" required>
                                            <select name="mode" class="form-select form-select-sm mode">
                                                <option value="receive">Receive</option>
                                                <option value="count">Count</option>
                                            </select>
                                            <button class="btn btn-sm btn-success">Submit</button>
                                            <a href="{{ route('inventory.history', ['variant' => $v->id]) }}" class="btn btn-sm btn-outline-secondary" title="Stock history"><i class="bi bi-clock-history"></i></a>
                                            <input type="text" name="note" maxlength="255" class="form-control form-control-sm note d-none" placeholder="Why does the count differ?">
                                            <div class="small w-100 text-end text-muted hint"></div>
                                        </form>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">No items match.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-3">{{ $variants->links() }}</div>
    @endif

    {{-- Receive / Count form behaviour --}}
    @if ($isOwner && $mode === 'items')
        <script>
            document.querySelectorAll('.stock-form').forEach(form => {
                const current = parseInt(form.dataset.current, 10);
                const qty = form.querySelector('.qty'), mode = form.querySelector('.mode');
                const note = form.querySelector('.note'), hint = form.querySelector('.hint');

                function refresh() {
                    const n = parseInt(qty.value, 10);
                    const counting = mode.value === 'count';
                    const mismatch = counting && !isNaN(n) && n !== current;

                    note.classList.toggle('d-none', !mismatch);
                    note.required = mismatch;

                    if (counting && !isNaN(n)) {
                        hint.textContent = n === current
                            ? 'Matches the system (' + current + ').'
                            : 'System has ' + current + ', you counted ' + n + ' (' + (n > current ? 'over by ' + (n - current) : 'short by ' + (current - n)) + ').';
                        hint.className = 'small w-100 text-end hint ' + (n === current ? 'text-success' : 'text-danger');
                    } else if (!counting && !isNaN(n) && n > 0) {
                        hint.textContent = 'Stock will go from ' + current + ' to ' + (current + n) + '.';
                        hint.className = 'small w-100 text-end hint text-muted';
                    } else {
                        hint.textContent = '';
                    }
                }

                qty.addEventListener('input', refresh);
                mode.addEventListener('change', refresh);

                form.addEventListener('submit', e => {
                    const n = parseInt(qty.value, 10);
                    if (mode.value === 'receive' && !(n >= 1)) { e.preventDefault(); alert('Enter how many units were received (at least 1).'); return; }
                    if (mode.value === 'count' && n !== current &&
                        !confirm('The system shows ' + current + ' but you counted ' + n + '.\nSave this count? The difference will be recorded in Stock History.')) {
                        e.preventDefault();
                    }
                });
            });
        </script>
    @endif
@endsection
