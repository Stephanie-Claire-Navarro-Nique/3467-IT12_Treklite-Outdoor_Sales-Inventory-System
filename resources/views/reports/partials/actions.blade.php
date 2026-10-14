{{-- Export (PDF / Excel) + Print. Pass $exportRoute. The export keeps the period / filters that are in the page link. --}}
<div class="d-flex gap-2 no-print">
    <div class="dropdown">
        <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-download"></i> Export
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="{{ route($exportRoute, array_merge(request()->query(), ['format' => 'pdf'])) }}"><i class="bi bi-file-earmark-pdf text-danger"></i> PDF</a></li>
            <li><a class="dropdown-item" href="{{ route($exportRoute, array_merge(request()->query(), ['format' => 'xlsx'])) }}"><i class="bi bi-file-earmark-excel text-success"></i> Excel (.xlsx)</a></li>
        </ul>
    </div>
    <button onclick="window.print()" class="btn btn-outline-dark"><i class="bi bi-printer"></i> Print</button>
</div>
