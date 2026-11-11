@extends('dashboard.layouts.wrapper')

@section('title', __('Penghapusan Aset'))

@section('content')
@php
    $rp = fn ($v) => 'Rp' . number_format((float) $v, 0, ',', '.');
    $money = fn ($v) => $v === null ? '—' : ((float) $v < 0 ? '(' . $rp(abs($v)) . ')' : $rp($v));
    $periodText = $from ? $from->translatedFormat('d M Y') . ' – ' . $to->translatedFormat('d M Y') : 'Semua waktu';
    $gl = $summary['gain_loss'];
@endphp
<style>
    .dp-wrap { --dp-line: rgba(15,27,45,.08); --dp-muted:#8592a6; }
    .dp-card { background:var(--bs-body-bg); border:1px solid var(--dp-line); border-radius:16px; box-shadow:0 1px 2px rgba(15,27,45,.04), 0 8px 24px -16px rgba(15,27,45,.16); }
    .dp-kpi { padding:16px 18px; height:100%; }
    .dp-kpi small { display:block; font-size:.7rem; font-weight:800; letter-spacing:.05em; text-transform:uppercase; color:var(--dp-muted); }
    .dp-kpi b { display:block; font-size:1.3rem; font-weight:800; margin-top:4px; font-variant-numeric:tabular-nums; }
    .dp-kpi span { font-size:.8rem; color:var(--dp-muted); }
    .dp-types { display:flex; flex-wrap:wrap; gap:6px; }
    .dp-types a { font-size:.78rem; font-weight:700; padding:5px 11px; border-radius:999px; background:var(--bs-tertiary-bg, #f4f7fb); color:inherit; text-decoration:none; border:1px solid transparent; }
    .dp-types a.on { border-color:#1f5fbf; color:#1f5fbf; background:#e8f0fd; }
    .dp-table th { font-size:.72rem; text-transform:uppercase; letter-spacing:.04em; color:var(--dp-muted); font-weight:800; white-space:nowrap; }
    .dp-table td { vertical-align:middle; font-size:.88rem; }
    .dp-num { font-variant-numeric:tabular-nums; white-space:nowrap; text-align:right; }
    .dp-reason { max-width:240px; }
    .dp-reason small { white-space:normal; overflow-wrap:anywhere; }
    .dp-asset { min-width:190px; max-width:260px; }
    .dp-chip { display:inline-flex; align-items:center; gap:5px; font-size:.72rem; font-weight:800; padding:3px 10px; border-radius:999px; background:#fee2e2; color:#b91c1c; white-space:nowrap; }
    .dp-chip.gray { background:#eef2f7; color:#4b5a6e; }
    .dp-steps { display:flex; flex-wrap:wrap; align-items:center; gap:8px 10px; font-size:.8rem; color:var(--dp-muted); }
    .dp-steps span { display:inline-flex; align-items:center; gap:6px; }
    .dp-steps span > b:first-child { width:20px; height:20px; border-radius:50%; display:inline-grid; place-items:center; background:#fee2e2; color:#b91c1c; font-size:.7rem; }
    .dp-steps i { font-size:.7rem; opacity:.6; }
    .dp-pick { list-style:none; margin:0; padding:0; max-height:360px; overflow-y:auto; }
    .dp-pick a { display:flex; justify-content:space-between; gap:12px; align-items:center; padding:10px 12px; border-radius:10px; text-decoration:none; color:inherit; border:1px solid transparent; }
    .dp-pick a:hover, .dp-pick a:focus { border-color:#fecaca; background:#fff5f5; }
    .dp-pick b { display:block; }
    .dp-pick small { color:var(--dp-muted); }
    .dp-empty { text-align:center; color:var(--dp-muted); padding:42px 10px; }
    .dp-empty i { font-size:2rem; display:block; margin-bottom:8px; opacity:.6; }
</style>

<div class="container-xxl flex-grow-1 container-p-y dp-wrap">

    <div class="dp-card p-3 p-md-4 mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <a href="{{ route('assets.index') }}" class="text-muted small d-inline-flex align-items-center gap-1 mb-1"><i class="bi bi-arrow-left"></i> {{ __('Data Aset') }}</a>
                <h4 class="mb-1 fw-bold">{{ __('Penghapusan Aset') }}</h4>
                <div class="text-muted small">{{ __('Aset yang rusak & dimusnahkan, hilang, dijual, dihibahkan, atau dikembalikan ke vendor · :p1', ['p1' => $periodText]) }}</div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('assets.disposals.export', request()->query()) }}" class="btn btn-outline-success"><i class="bi bi-file-earmark-excel me-1"></i>{{ __('Export Excel') }}</a>
                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#pickAssetModal"><i class="bi bi-archive me-1"></i>{{ __('Hapuskan aset') }}</button>
            </div>
        </div>

        <div class="dp-steps mt-3">
            <span><b>1</b> {{ __('Klik') }} <b>{{ __('Hapuskan aset') }}</b> {{ __('& pilih asetnya') }}</span>
            <i class="bi bi-chevron-right"></i>
            <span><b>2</b> {{ __('Pilih alasan, tanggal & hasil penjualan') }}</span>
            <i class="bi bi-chevron-right"></i>
            <span><b>3</b> {{ __('Lampirkan berita acara & konfirmasi') }}</span>
            <i class="bi bi-chevron-right"></i>
            <span><b>4</b> {{ __('Aset keluar dari daftar aktif; riwayat tetap tersimpan') }}</span>
        </div>

        <form method="GET" class="d-flex flex-wrap gap-2 mt-3">
            <select name="period" class="form-select" style="width:auto" onchange="this.form.submit()" aria-label="{{ __('Periode') }}">
                @foreach($periods as $k => $label)<option value="{{ $k }}" @selected($filters['period'] === $k)>{{ $label }}</option>@endforeach
            </select>
            <select name="type" class="form-select" style="width:auto" onchange="this.form.submit()" aria-label="{{ __('Alasan') }}">
                <option value="">{{ __('Semua alasan') }}</option>
                @foreach($types as $k => $label)<option value="{{ $k }}" @selected($filters['type'] === $k)>{{ $label }}</option>@endforeach
            </select>
            <input type="search" name="q" value="{{ $filters['q'] }}" class="form-control" placeholder="{{ __('Cari kode / nama aset') }}" style="max-width:260px">
            <div class="form-check align-self-center ms-1">
                <input class="form-check-input" type="checkbox" name="show_cancelled" value="1" id="dpCancelled" @checked($filters['show_cancelled']) onchange="this.form.submit()">
                <label class="form-check-label small" for="dpCancelled">{{ __('Tampilkan yang dibatalkan') }}</label>
            </div>
            <button class="btn btn-primary"><i class="bi bi-search"></i></button>
            @if(request()->hasAny(['q', 'type', 'period', 'show_cancelled']))<a href="{{ route('assets.disposals.index') }}" class="btn btn-outline-secondary">{{ __('Reset') }}</a>@endif
        </form>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6">
            <div class="dp-card dp-kpi">
                <small><i class="bi bi-archive me-1"></i>{{ __('Aset dihapuskan') }}</small>
                <b>{{ number_format($summary['count'], 0, ',', '.') }}</b>
                <span>{{ __('harga perolehan :p1', ['p1' => $rp($summary['purchase'])]) }}</span>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="dp-card dp-kpi">
                <small><i class="bi bi-graph-down me-1"></i>{{ __('Nilai buku dihapuskan') }}</small>
                <b>{{ $rp($summary['book']) }}</b>
                <span>{{ __('sisa nilai yang belum disusutkan') }}</span>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="dp-card dp-kpi">
                <small><i class="bi bi-cash-coin me-1"></i>{{ __('Hasil penjualan / ganti') }}</small>
                <b>{{ $rp($summary['proceeds']) }}</b>
                <span>{{ __('uang yang diterima') }}</span>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="dp-card dp-kpi">
                <small><i class="bi bi-calculator me-1"></i>{{ __(':p1 penghapusan', ['p1' => $gl > 0 ? 'Laba' : 'Kerugian']) }}</small>
                <b class="{{ $gl < 0 ? 'text-danger' : ($gl > 0 ? 'text-success' : '') }}">{{ $money($gl) }}</b>
                <span>{{ __('hasil dikurangi nilai buku') }}</span>
            </div>
        </div>
    </div>

    <div class="dp-card">
        @if($byType->isNotEmpty())
            <div class="p-3 border-bottom dp-types">
                <a href="{{ route('assets.disposals.index', array_merge(request()->except(['type', 'page']))) }}" class="{{ $filters['type'] === '' ? 'on' : '' }}">{{ __('Semua (:p1)', ['p1' => $byType->sum('total')]) }}</a>
                @foreach($types as $k => $label)
                    @if($byType->has($k))
                        <a href="{{ route('assets.disposals.index', array_merge(request()->except('page'), ['type' => $k])) }}" class="{{ $filters['type'] === $k ? 'on' : '' }}">{{ $label }} ({{ $byType[$k]->total }})</a>
                    @endif
                @endforeach
            </div>
        @endif

        <div class="table-responsive">
            <table class="table mb-0 dp-table">
                <thead>
                    <tr>
                        <th>{{ __('Tanggal') }}</th>
                        <th>{{ __('Aset') }}</th>
                        <th>{{ __('Alasan') }}</th>
                        <th class="text-end">{{ __('Nilai buku') }}</th>
                        <th class="text-end">{{ __('Hasil') }}</th>
                        <th class="text-end">{{ __('Laba (rugi)') }}</th>
                        <th>{{ __('Dicatat oleh') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($disposals as $d)
                        <tr @class(['opacity-50' => $d->isCancelled()])>
                            <td class="text-nowrap">{{ $d->disposal_date->translatedFormat('d M Y') }}</td>
                            <td class="dp-asset">
                                @if($d->asset && !$d->asset->trashed())
                                    <a href="{{ route('assets.show', encryptId($d->asset->id)) }}" class="fw-bold">{{ $d->asset->asset_code }}</a>
                                @else
                                    <span class="fw-bold">{{ $d->asset?->asset_code ?? '—' }}</span>
                                @endif
                                <small class="d-block text-muted">{{ $d->asset?->asset_name }}{{ $d->asset?->category ? ' · ' . $d->asset->category->category_name : '' }}</small>
                            </td>
                            <td class="dp-reason">
                                <span class="dp-chip {{ $d->isCancelled() ? 'gray' : '' }}">{{ $d->typeLabel() }}</span>
                                @if($d->isCancelled())<small class="d-block text-muted mt-1">{{ __('Dibatalkan:p1', ['p1' => $d->canceller ? ' oleh ' . $d->canceller->name : '']) }}</small>@endif
                                @if($d->notes)<small class="d-block text-muted mt-1" title="{{ $d->notes }}">{{ \Illuminate\Support\Str::limit($d->notes, 60) }}</small>@endif
                            </td>
                            <td class="dp-num">{{ $money($d->book_value) }}</td>
                            <td class="dp-num">{{ $rp($d->proceeds) }}</td>
                            <td class="dp-num {{ (float) $d->gain_loss < 0 ? 'text-danger' : ((float) $d->gain_loss > 0 ? 'text-success' : '') }}">{{ $money($d->gain_loss) }}</td>
                            <td class="text-nowrap">
                                {{ $d->creator?->name ?? '—' }}
                                @if($d->document_path)
                                    <a href="{{ asset('storage/' . $d->document_path) }}" target="_blank" class="ms-1" title="{{ __('Berita acara') }}"><i class="bi bi-file-earmark-text"></i></a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="dp-empty"><i class="bi bi-archive"></i>{{ __('Belum ada aset yang dihapuskan pada filter ini.') }}<br>
                            <small>{{ __('Klik') }} <b>{{ __('Hapuskan aset') }}</b> {{ __('di atas, atau tombol') }} <b>{{ __('Hapuskan') }}</b> {{ __('di halaman detail aset.') }}</small></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($disposals->hasPages())<div class="p-3">{{ $disposals->links() }}</div>@endif
    </div>
</div>

{{-- Pilih aset yang akan dihapuskan --}}
<div class="modal fade" id="pickAssetModal" tabindex="-1" aria-labelledby="pickAssetTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="pickAssetTitle"><i class="bi bi-archive me-2 text-danger"></i>{{ __('Pilih aset yang dihapuskan') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Tutup') }}"></button>
            </div>
            <div class="modal-body">
                <input type="search" id="dpPickSearch" class="form-control mb-3" placeholder="{{ __('Cari kode, nama, atau nomor seri aset') }}" autocomplete="off">
                <ul class="dp-pick" id="dpPickList" aria-live="polite"></ul>
                <div class="text-muted small mt-2">{{ __('Setelah memilih, form penghapusan aset tersebut terbuka. Aset yang sudah dihapuskan tidak tampil di sini.') }}</div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var modal = document.getElementById('pickAssetModal');
    var input = document.getElementById('dpPickSearch');
    var list = document.getElementById('dpPickList');
    var url = @json(route('assets.disposals.candidates'));
    var timer = null;
    var esc = function (s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); };

    function load() {
        list.innerHTML = '<li class="text-muted small p-2">Memuat...</li>';
        fetch(url + '?q=' + encodeURIComponent(input.value.trim()), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                var rows = (res && res.data) || [];
                if (!rows.length) { list.innerHTML = '<li class="text-muted small p-2">Aset tidak ditemukan.</li>'; return; }
                list.innerHTML = rows.map(function (a) {
                    var meta = [a.category, a.location, a.holder ? 'dipegang ' + a.holder : null].filter(Boolean).join(' · ');
                    return '<li><a href="' + esc(a.url) + '"><span><b>' + esc(a.code) + ' &middot; ' + esc(a.name) + '</b><small>' + esc(meta || a.status) + '</small></span><i class="bi bi-chevron-right text-danger"></i></a></li>';
                }).join('');
            })
            .catch(function () { list.innerHTML = '<li class="text-danger small p-2">Gagal memuat daftar aset.</li>'; });
    }

    input.addEventListener('input', function () { clearTimeout(timer); timer = setTimeout(load, 250); });
    modal.addEventListener('shown.bs.modal', function () { input.focus(); if (!list.children.length) load(); });
});
</script>
@endsection
