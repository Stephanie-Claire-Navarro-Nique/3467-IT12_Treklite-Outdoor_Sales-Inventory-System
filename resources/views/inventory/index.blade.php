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
        .btn-icon { width: 34px; height: 31px; padding: 0; display: inline-flex; align-items: center; justify-content: center; }
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
                            <th style="width:140px">Size</th>
                            <th style="width:140px">Color</th>
                            <th class="text-end" style="width:90px">In stock</th>
                            <th class="text-center" style="width:120px">Status</th>
                            @if ($isOwner)
                                <th class="text-end" style="width:130px">Receive / Count</th>
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
                                    <div class="small text-muted">{{ collect([$v->product->brand?->name, $v->product->category?->name])->filter()->implode(' · ') }}</div>
                                </td>
                                <td>{{ $v->size?->name ?? '—' }}</td>
                                <td>{{ $v->color?->name ?? '—' }}</td>
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
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-1">
                                            {{-- Opens the Receive / Count pop-up for this item --}}
                                            <button type="button" class="btn btn-sm btn-success btn-icon" title="Receive / Count stock" aria-label="Receive or count stock"
                                                    data-stock-open
                                                    data-action="{{ route('inventory.stock', $v) }}"
                                                    data-product="{{ $v->product->name }}"
                                                    data-size="{{ $v->size?->name ?? '—' }}"
                                                    data-color="{{ $v->color?->name ?? '—' }}"
                                                    data-current="{{ $v->quantity }}">
                                                <i class="bi bi-clipboard2-plus"></i>
                                            </button>
                                            <a href="{{ route('inventory.history', ['variant' => $v->id]) }}" class="btn btn-sm btn-outline-secondary btn-icon" title="Stock history"><i class="bi bi-clock-history"></i></a>
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">No items match.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-3">{{ $variants->links() }}</div>
    @endif

    {{-- ===== Receive / Count pop-up ===== --}}
    @if ($isOwner && $mode === 'items')
        <div class="modal fade" id="stock-modal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" class="modal-content" id="stock-form">
                    @csrf
                    <input type="hidden" name="mode" id="stock-mode" value="receive">
                    <div class="modal-header">
                        <h5 class="modal-title">Receive / Count stock</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="border rounded p-2 mb-3 bg-light">
                            <div class="fw-semibold" id="stock-product"></div>
                            <div class="small text-muted"><span id="stock-size"></span> &middot; <span id="stock-color"></span></div>
                            <div class="mt-1">In stock now: <strong class="fs-5" id="stock-current"></strong></div>
                        </div>

                        <div class="btn-group w-100 mb-3" role="group" aria-label="What are you doing?">
                            <input type="radio" class="btn-check" name="stock_mode_pick" id="pick-receive" value="receive" checked>
                            <label class="btn btn-outline-success" for="pick-receive"><i class="bi bi-box-arrow-in-down"></i> Receive delivery</label>
                            <input type="radio" class="btn-check" name="stock_mode_pick" id="pick-count" value="count">
                            <label class="btn btn-outline-success" for="pick-count"><i class="bi bi-clipboard-check"></i> Count stock</label>
                        </div>

                        <label class="form-label" for="stock-qty" id="stock-qty-label">How many units arrived?</label>
                        <input type="number" name="quantity" id="stock-qty" min="0" class="form-control form-control-lg" placeholder="Number" required>
                        <div class="small mt-1" id="stock-hint"></div>

                        <div class="mt-3 d-none" id="stock-note-wrap">
                            <label class="form-label" for="stock-note">Why does the count differ?</label>
                            <input type="text" name="note" id="stock-note" maxlength="255" class="form-control" placeholder="e.g. 2 damaged, 1 sold without recording">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success" id="stock-submit">Submit</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            window.addEventListener('load', () => {
                const modal = new bootstrap.Modal('#stock-modal');
                const $ = id => document.getElementById(id);
                const form = $('stock-form'), qty = $('stock-qty'), hint = $('stock-hint');
                const noteWrap = $('stock-note-wrap'), note = $('stock-note'), modeInput = $('stock-mode');
                let current = 0;

                const mode = () => modeInput.value;

                function refresh() {
                    const n = parseInt(qty.value, 10), counting = mode() === 'count';
                    const mismatch = counting && !isNaN(n) && n !== current;

                    noteWrap.classList.toggle('d-none', !mismatch);
                    note.required = mismatch;
                    $('stock-qty-label').textContent = counting ? 'How many did you physically count?' : 'How many units arrived?';

                    if (counting && !isNaN(n)) {
                        hint.textContent = n === current
                            ? 'Matches the system (' + current + ').'
                            : 'System has ' + current + ', you counted ' + n + ' (' + (n > current ? 'over by ' + (n - current) : 'short by ' + (current - n)) + ').';
                        hint.className = 'small mt-1 ' + (n === current ? 'text-success' : 'text-danger');
                    } else if (!counting && !isNaN(n) && n > 0) {
                        hint.textContent = 'Stock will go from ' + current + ' to ' + (current + n) + '.';
                        hint.className = 'small mt-1 text-muted';
                    } else {
                        hint.textContent = '';
                    }
                }

                document.querySelectorAll('[data-stock-open]').forEach(btn => btn.addEventListener('click', () => {
                    current = parseInt(btn.dataset.current, 10);
                    form.action = btn.dataset.action;
                    $('stock-product').textContent = btn.dataset.product;
                    $('stock-size').textContent = 'Size: ' + btn.dataset.size;
                    $('stock-color').textContent = 'Color: ' + btn.dataset.color;
                    $('stock-current').textContent = current;
                    $('pick-receive').checked = true;
                    modeInput.value = 'receive';
                    qty.value = ''; note.value = '';
                    refresh();
                    modal.show();
                }));

                document.querySelectorAll('input[name="stock_mode_pick"]').forEach(r => r.addEventListener('change', () => { modeInput.value = r.value; refresh(); qty.focus(); }));
                qty.addEventListener('input', refresh);
                $('stock-modal').addEventListener('shown.bs.modal', () => qty.focus());

                form.addEventListener('submit', e => {
                    const n = parseInt(qty.value, 10);
                    if (isNaN(n)) { e.preventDefault(); return; }
                    if (mode() === 'receive' && !(n >= 1)) { e.preventDefault(); alert('Enter how many units were received (at least 1).'); return; }
                    if (mode() === 'count' && n !== current &&
                        !confirm('The system shows ' + current + ' but you counted ' + n + '.\nSave this count? The difference will be recorded in Stock History.')) {
                        e.preventDefault();
                        return;
                    }
                    $('stock-submit').disabled = true;   // no double submit
                });
            });
        </script>
    @endif
@endsection
