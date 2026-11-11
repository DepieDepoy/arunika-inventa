{{--
    Paginasi bawaan seluruh aplikasi (dashboard & backoffice), Bootstrap 5.
    Dipasang lewat Paginator::defaultView() di AppServiceProvider.
    Ringkas: maks. ~7 tombol nomor halaman, tetap rapi walau jutaan baris.
--}}
@if ($paginator->hasPages())
    @php
        $current = $paginator->currentPage();
        $last = $paginator->lastPage();
        $start = max(1, $current - 2);
        $end = min($last, $current + 2);
    @endphp
    <nav class="vs-pager d-flex flex-wrap align-items-center justify-content-between gap-2" aria-label="Navigasi halaman">
        <div class="vs-pager-info">
            Menampilkan <b>{{ number_format($paginator->firstItem() ?? 0, 0, ',', '.') }}</b>–<b>{{ number_format($paginator->lastItem() ?? 0, 0, ',', '.') }}</b>
            dari <b>{{ number_format($paginator->total(), 0, ',', '.') }}</b>
        </div>

        <ul class="pagination pagination-sm mb-0">
            <li class="page-item {{ $paginator->onFirstPage() ? 'disabled' : '' }}">
                <a class="page-link" href="{{ $paginator->onFirstPage() ? '#' : $paginator->previousPageUrl() }}" rel="prev" aria-label="Sebelumnya">&lsaquo;</a>
            </li>

            @if ($start > 1)
                <li class="page-item"><a class="page-link" href="{{ $paginator->url(1) }}">1</a></li>
                @if ($start > 2)<li class="page-item disabled"><span class="page-link">…</span></li>@endif
            @endif

            @for ($page = $start; $page <= $end; $page++)
                @if ($page === $current)
                    <li class="page-item active" aria-current="page"><span class="page-link">{{ number_format($page, 0, ',', '.') }}</span></li>
                @else
                    <li class="page-item"><a class="page-link" href="{{ $paginator->url($page) }}">{{ number_format($page, 0, ',', '.') }}</a></li>
                @endif
            @endfor

            @if ($end < $last)
                @if ($end < $last - 1)<li class="page-item disabled"><span class="page-link">…</span></li>@endif
                <li class="page-item"><a class="page-link" href="{{ $paginator->url($last) }}">{{ number_format($last, 0, ',', '.') }}</a></li>
            @endif

            <li class="page-item {{ $paginator->hasMorePages() ? '' : 'disabled' }}">
                <a class="page-link" href="{{ $paginator->hasMorePages() ? $paginator->nextPageUrl() : '#' }}" rel="next" aria-label="Berikutnya">&rsaquo;</a>
            </li>
        </ul>
    </nav>

    @once
        <style>
            .vs-pager-info { font-size: .8rem; color: #6c757d; }
            .vs-pager .pagination { flex-wrap: wrap; gap: 2px; }
            .vs-pager .page-link { min-width: 32px; text-align: center; border-radius: 6px !important; line-height: 1.4; }
            .vs-pager svg { width: 1em; height: 1em; }
        </style>
    @endonce
@endif
