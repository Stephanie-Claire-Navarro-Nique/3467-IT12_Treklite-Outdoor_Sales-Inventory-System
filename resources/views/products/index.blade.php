@extends('layouts.app')

@section('title', 'Products')

@section('content')
    @php
        $hasFilter = $q !== '' || $brand !== '' || $category !== '' || $status !== 'active';
        $noBrand   = \App\Http\Controllers\InventoryController::NO_BRAND;
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Products</h3>
        <a href="{{ route('products.create') }}" class="btn btn-success">+ Add Product</a>
    </div>

    <div class="alert alert-light border small py-2">
        <i class="bi bi-info-circle"></i> This page is for <strong>creating and editing products</strong> (name, brand, category, price, sizes and colors).
        To receive deliveries or count stock, use <strong>Manage Inventory</strong> on the product.
    </div>

    <form method="GET" action="{{ route('products.index') }}" class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <div class="row g-2 align-items-end">
                <div class="col-lg-4 col-md-6">
                    <label class="form-label small mb-0">Search</label>
                    <input type="search" name="q" value="{{ $q }}" class="form-control form-control-sm" placeholder="Model, brand or category">
                </div>
                <div class="col-lg-3 col-md-3 col-6">
                    <label class="form-label small mb-0">Brand</label>
                    <select name="brand" class="form-select form-select-sm">
                        <option value="">All brands</option>
                        @foreach ($brands as $b)<option value="{{ $b }}" @selected($brand === $b)>{{ $b }}</option>@endforeach
                        <option value="{{ $noBrand }}" @selected($brand === $noBrand)>No brand</option>
                    </select>
                </div>
                <div class="col-lg-3 col-md-3 col-6">
                    <label class="form-label small mb-0">Category</label>
                    <select name="category" class="form-select form-select-sm">
                        <option value="">All categories</option>
                        @foreach ($categories as $c)<option value="{{ $c }}" @selected($category === $c)>{{ $c }}</option>@endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-md-3 col-6">
                    <label class="form-label small mb-0">Show</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="active" @selected($status === 'active')>Active</option>
                        <option value="archived" @selected($status === 'archived')>Archived</option>
                        <option value="all" @selected($status === 'all')>All</option>
                    </select>
                </div>
                <div class="col-auto d-flex gap-2">
                    <button class="btn btn-sm btn-success"><i class="bi bi-funnel"></i> Filter</button>
                    @if ($hasFilter)<a href="{{ route('products.index') }}" class="btn btn-sm btn-outline-secondary">Clear</a>@endif
                </div>
            </div>
        </div>
    </form>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead>
                    <tr>
                        <th style="width:64px"></th>
                        <th>Model</th>
                        <th>Brand</th>
                        <th>Category</th>
                        <th class="text-end">Price</th>
                        <th class="text-center">Variants</th>
                        <th class="text-end" style="width:420px"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $p)
                        <tr>
                            <td>
                                @if ($p->image_url)
                                    <img src="{{ $p->image_url }}" alt="" width="48" height="48" class="rounded" style="object-fit:cover">
                                @else
                                    <div class="rounded bg-light text-secondary d-flex align-items-center justify-content-center" style="width:48px;height:48px"><i class="bi bi-image"></i></div>
                                @endif
                            </td>
                            <td class="fw-semibold">
                                {{ $p->name }}
                                @unless ($p->is_active) <span class="badge text-bg-warning">Archived</span> @endunless
                            </td>
                            <td>
                                @if ($p->brand)<span class="badge text-bg-dark">{{ $p->brand }}</span>@else<span class="badge text-bg-light border text-muted">No brand</span>@endif
                            </td>
                            <td>
                                @if ($p->category)<span class="badge text-bg-secondary">{{ $p->category }}</span>@else<span class="text-muted">&mdash;</span>@endif
                            </td>
                            <td class="text-end">₱{{ number_format($p->price, 2) }}</td>
                            <td class="text-center">{{ $p->variants->count() }}</td>
                            <td class="text-end">
                                @if ($p->is_active)
                                    <a href="{{ route('inventory.index', ['product' => $p->id]) }}" class="btn btn-sm btn-outline-success"><i class="bi bi-box-seam"></i> Manage Inventory</a>
                                    <a href="{{ route('products.edit', $p) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                    <form method="POST" action="{{ route('products.archive', $p) }}" class="d-inline"
                                          onsubmit="return confirm('Archive {{ addslashes($p->name) }}? It will no longer appear in New Sale or Inventory. Past sales are not affected.');">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-archive"></i> Archive</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('products.restore', $p) }}" class="d-inline">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-success"><i class="bi bi-arrow-counterclockwise"></i> Restore</button>
                                    </form>
                                @endif
                                @if ($deletable->contains($p->id))
                                    <form method="POST" action="{{ route('products.destroy', $p) }}" class="d-inline"
                                          onsubmit="return confirm('Permanently delete {{ addslashes($p->name) }}? This cannot be undone.');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-danger" title="No sales or stock records yet, so it can be deleted"><i class="bi bi-trash"></i> Delete</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">{{ $hasFilter ? 'No products match these filters.' : 'No products yet.' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $products->links() }}</div>
@endsection
