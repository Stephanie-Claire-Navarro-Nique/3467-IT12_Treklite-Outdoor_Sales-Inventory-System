{{-- Export (PDF) + Print. Pass $exportRoute. The export keeps the period / filters that are in the page link. --}}
<div class="d-flex gap-2 no-print">
    <a class="btn btn-outline-secondary btn-icon" href="{{ route($exportRoute, array_merge(request()->query(), ['format' => 'pdf'])) }}" title="Export (PDF)" aria-label="Export PDF"><i class="bi bi-file-earmark-pdf"></i></a>
    <button onclick="window.print()" class="btn btn-outline-dark btn-icon" title="Print" aria-label="Print"><i class="bi bi-printer"></i></button>
</div>
