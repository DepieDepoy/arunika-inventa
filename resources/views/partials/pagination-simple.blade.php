{{-- Paginasi sederhana (simplePaginate / cursorPaginate): tanpa COUNT total. --}}
@if ($paginator->hasPages())
    <nav class="d-flex justify-content-end" aria-label="Navigasi halaman">
        <ul class="pagination pagination-sm mb-0">
            <li class="page-item {{ $paginator->onFirstPage() ? 'disabled' : '' }}">
                <a class="page-link" href="{{ $paginator->onFirstPage() ? '#' : $paginator->previousPageUrl() }}" rel="prev">&lsaquo; Sebelumnya</a>
            </li>
            <li class="page-item {{ $paginator->hasMorePages() ? '' : 'disabled' }}">
                <a class="page-link" href="{{ $paginator->hasMorePages() ? $paginator->nextPageUrl() : '#' }}" rel="next">Berikutnya &rsaquo;</a>
            </li>
        </ul>
    </nav>
@endif
