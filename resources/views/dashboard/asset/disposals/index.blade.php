@extends('dashboard.layouts.wrapper')

@section('title', 'Penghapusan Aset')

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
    .dp-empty { text-align:center; color:var(--dp-muted); padding:42px 10px; }
    .dp-empty i { font-size:2rem; display:block; margin-bottom:8px; opacity:.6; }
</style>

<div class="container-xxl flex-grow-1 container-p-y dp-wrap">

    <div class="dp-card p-3 p-md-4 mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <a href="{{ route('assets.index') }}" class="text-muted small d-inline-flex align-items-center gap-1 mb-1"><i class="bi bi-arrow-left"></i> Data Aset</a>
                <h4 class="mb-1 fw-bold">Penghapusan Aset</h4>
                <div class="text-muted small">Aset yang rusak & dimusnahkan, hilang, dijual, dihibahkan, atau dikembalikan ke vendor &middot; {{ $periodText }}</div>
            </div>
            <a href="{{ route('assets.disposals.export', request()->query()) }}" class="btn btn-outline-success"><i class="bi bi-file-earmark-excel me-1"></i>Export Excel</a>
        </div>

        <form method="GET" class="d-flex flex-wrap gap-2 mt-3">
            <select name="period" class="form-select" style="width:auto" onchange="this.form.submit()" aria-label="Periode">
                @foreach($periods as $k => $label)<option value="{{ $k }}" @selected($filters['period'] === $k)>{{ $label }}</option>@endforeach
            </select>
            <select name="type" class="form-select" style="width:auto" onchange="this.form.submit()" aria-label="Alasan">
                <option value="">Semua alasan</option>
                @foreach($types as $k => $label)<option value="{{ $k }}" @selected($filters['type'] === $k)>{{ $label }}</option>@endforeach
            </select>
            <input type="search" name="q" value="{{ $filters['q'] }}" class="form-control" placeholder="Cari kode / nama aset" style="max-width:260px">
            <div class="form-check align-self-center ms-1">
                <input class="form-check-input" type="checkbox" name="show_cancelled" value="1" id="dpCancelled" @checked($filters['show_cancelled']) onchange="this.form.submit()">
                <label class="form-check-label small" for="dpCancelled">Tampilkan yang dibatalkan</label>
            </div>
            <button class="btn btn-primary"><i class="bi bi-search"></i></button>
            @if(request()->hasAny(['q', 'type', 'period', 'show_cancelled']))<a href="{{ route('assets.disposals.index') }}" class="btn btn-outline-secondary">Reset</a>@endif
        </form>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6">
            <div class="dp-card dp-kpi">
                <small><i class="bi bi-archive me-1"></i>Aset dihapuskan</small>
                <b>{{ number_format($summary['count'], 0, ',', '.') }}</b>
                <span>harga perolehan {{ $rp($summary['purchase']) }}</span>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="dp-card dp-kpi">
                <small><i class="bi bi-graph-down me-1"></i>Nilai buku dihapuskan</small>
                <b>{{ $rp($summary['book']) }}</b>
                <span>sisa nilai yang belum disusutkan</span>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="dp-card dp-kpi">
                <small><i class="bi bi-cash-coin me-1"></i>Hasil penjualan / ganti</small>
                <b>{{ $rp($summary['proceeds']) }}</b>
                <span>uang yang diterima</span>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="dp-card dp-kpi">
                <small><i class="bi bi-calculator me-1"></i>{{ $gl > 0 ? 'Laba' : 'Kerugian' }} penghapusan</small>
                <b class="{{ $gl < 0 ? 'text-danger' : ($gl > 0 ? 'text-success' : '') }}">{{ $money($gl) }}</b>
                <span>hasil dikurangi nilai buku</span>
            </div>
        </div>
    </div>

    <div class="dp-card">
        @if($byType->isNotEmpty())
            <div class="p-3 border-bottom dp-types">
                <a href="{{ route('assets.disposals.index', array_merge(request()->except(['type', 'page']))) }}" class="{{ $filters['type'] === '' ? 'on' : '' }}">Semua ({{ $byType->sum('total') }})</a>
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
                        <th>Tanggal</th>
                        <th>Aset</th>
                        <th>Alasan</th>
                        <th class="text-end">Nilai buku</th>
                        <th class="text-end">Hasil</th>
                        <th class="text-end">Laba (rugi)</th>
                        <th>Dicatat oleh</th>
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
                                @if($d->isCancelled())<small class="d-block text-muted mt-1">Dibatalkan{{ $d->canceller ? ' oleh ' . $d->canceller->name : '' }}</small>@endif
                                @if($d->notes)<small class="d-block text-muted mt-1" title="{{ $d->notes }}">{{ \Illuminate\Support\Str::limit($d->notes, 60) }}</small>@endif
                            </td>
                            <td class="dp-num">{{ $money($d->book_value) }}</td>
                            <td class="dp-num">{{ $rp($d->proceeds) }}</td>
                            <td class="dp-num {{ (float) $d->gain_loss < 0 ? 'text-danger' : ((float) $d->gain_loss > 0 ? 'text-success' : '') }}">{{ $money($d->gain_loss) }}</td>
                            <td class="text-nowrap">
                                {{ $d->creator?->name ?? '—' }}
                                @if($d->document_path)
                                    <a href="{{ asset('storage/' . $d->document_path) }}" target="_blank" class="ms-1" title="Berita acara"><i class="bi bi-file-earmark-text"></i></a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="dp-empty"><i class="bi bi-archive"></i>Belum ada aset yang dihapuskan pada filter ini.<br>
                            <small>Untuk menghapuskan aset, buka detail aset lalu pilih <b>&hellip; &rarr; Hapuskan aset</b>.</small></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($disposals->hasPages())<div class="p-3">{{ $disposals->links() }}</div>@endif
    </div>
</div>
@endsection
