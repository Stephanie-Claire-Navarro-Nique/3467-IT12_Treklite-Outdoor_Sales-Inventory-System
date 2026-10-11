@extends('layouts.app')

@section('title', 'Activity Log')

@section('content')
    @php
        $hasFilter = $q !== '' || $action !== '';
        $badge = [
            'sale_void' => 'bg-danger', 'product_archive' => 'bg-warning text-dark', 'staff_delete' => 'bg-danger',
            'backup_restore' => 'bg-danger', 'backup_create' => 'bg-secondary',
        ];
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Activity Log</h3>
    </div>

    <div class="alert alert-light border small py-2">
        <i class="bi bi-info-circle"></i> Important actions are recorded here: voided sales, product changes, staff accounts and backups.
        Stock changes are in <a href="{{ route('inventory.history') }}">Stock History</a>.
    </div>

    <form method="GET" action="{{ route('activity.index') }}" class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <div class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small mb-0">Search</label>
                    <input type="search" name="q" value="{{ $q }}" class="form-control form-control-sm" placeholder="Invoice number, product, staff name...">
                </div>
                <div class="col-md-4">
                    <label class="form-label small mb-0">Action</label>
                    <select name="action" class="form-select form-select-sm">
                        <option value="">All actions</option>
                        @foreach (\App\Models\ActivityLog::ACTION_LABELS as $key => $label)
                            <option value="{{ $key }}" @selected($action === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto d-flex gap-2">
                    <button class="btn btn-sm btn-success"><i class="bi bi-funnel"></i> Filter</button>
                    @if ($hasFilter)<a href="{{ route('activity.index') }}" class="btn btn-sm btn-outline-secondary">Clear</a>@endif
                </div>
            </div>
        </div>
    </form>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr><th style="width:170px">When</th><th style="width:150px">Who</th><th style="width:170px">Action</th><th>Details</th></tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td class="small">{{ $log->created_at->format('M d, Y h:i A') }}</td>
                            <td>{{ $log->user?->name ?? 'Unknown' }}</td>
                            <td><span class="badge {{ $badge[$log->action] ?? 'bg-primary' }}">{{ $log->action_label }}</span></td>
                            <td class="small">{{ $log->description }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">{{ $hasFilter ? 'Nothing matches these filters.' : 'Nothing recorded yet.' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $logs->links() }}</div>
@endsection
