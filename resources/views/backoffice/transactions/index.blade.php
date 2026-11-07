@extends('backoffice.layouts.app')

@section('title', 'Transaksi')

@section('content')
@include('backoffice.partials.style')
@include('backoffice.customers._style')
@php
    $TC = \App\Http\Controllers\Backoffice\TransactionController::class;
    $stClass = ['paid' => 'paid', 'pending' => 'trial', 'failed' => 'suspended'];
    $stIcon = ['paid' => 'bi-check-circle', 'pending' => 'bi-hourglass-split', 'failed' => 'bi-x-circle'];
    $maxMonth = max(1, collect($monthly)->max('amount'));
    $periodText = $from ? $from->locale('id')->translatedFormat('d M Y') . ' – ' . $to->locale('id')->translatedFormat('d M Y') : 'Semua waktu';
@endphp
<style>
    .tx-cols { display:grid; grid-template-columns:repeat(12, minmax(0, 1fr)); gap:6px; align-items:end; height:150px; padding:16px 20px 0; }
    .tx-col { display:flex; flex-direction:column; justify-content:flex-end; height:100%; position:relative; }
    .tx-col .b { background:#2a78d6; border-radius:4px 4px 0 0; }
    .tx-col:hover .b { background:#1f5fbf; }
    .tx-col .tip { display:none; position:absolute; bottom:calc(100% + 4px); left:50%; transform:translateX(-50%); background:#0b2447; color:#fff; font-size:.72rem; padding:5px 8px; border-radius:8px; white-space:nowrap; z-index:2; }
    .tx-col:hover .tip { display:block; }
    .tx-lbl { display:grid; grid-template-columns:repeat(12, minmax(0, 1fr)); gap:6px; padding:6px 20px 16px; font-size:.68rem; color:#8592a6; text-align:center; border-top:1px solid #eef2f7; margin-top:0; }
    .tx-amt { font-variant-numeric:tabular-nums; font-weight:800; color:#0b2447; white-space:nowrap; }
</style>

<div class="container-xxl flex-grow-1 container-p-y">

    <div class="cms-card p-3 p-md-4 mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <span class="cms-badge"><i class="bi bi-receipt"></i> Transaksi</span>
                <h4 class="mb-1 mt-2">Pembayaran pelanggan</h4>
                <div class="text-muted small">Langganan, upgrade, add-on & storage dari semua perusahaan · {{ $periodText }}</div>
            </div>
            <a href="{{ route('backoffice.transactions.export', request()->query()) }}" class="btn btn-outline-primary"><i class="bi bi-file-earmark-excel me-1"></i> Export Excel</a>
        </div>

        <form method="GET" class="d-flex flex-wrap gap-2 mt-3">
            <select name="period" class="form-select" style="width:auto" onchange="this.form.submit()">
                @foreach($periods as $k => $label)<option value="{{ $k }}" @selected($filters['period'] === $k)>{{ $label }}</option>@endforeach
            </select>
            <select name="status" class="form-select" style="width:auto" onchange="this.form.submit()">
                <option value="">Semua status</option>
                @foreach($statuses as $k => $label)<option value="{{ $k }}" @selected($filters['status'] === $k)>{{ $label }}</option>@endforeach
            </select>
            <select name="type" class="form-select" style="width:auto" onchange="this.form.submit()">
                <option value="">Semua jenis</option>
                @foreach($types as $k => $label)<option value="{{ $k }}" @selected($filters['type'] === $k)>{{ $label }}</option>@endforeach
            </select>
            <input type="search" name="q" value="{{ $filters['q'] }}" class="form-control" placeholder="Cari no. order, nama PT, nama / email user" style="max-width:320px">
            <button class="btn btn-primary"><i class="bi bi-search"></i></button>
            @if(request()->hasAny(['q', 'status', 'type', 'period']))<a href="{{ route('backoffice.transactions.index') }}" class="btn btn-outline-secondary">Reset</a>@endif
        </form>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="cms-card bx-kpi hero">
                <div class="lbl"><i class="bi bi-cash-stack"></i> Pendapatan (lunas)</div>
                <div class="val">{{ bx_rp($summary['revenue']) }}</div>
                <div class="sub">{{ bx_num($summary['paid']) }} transaksi · {{ bx_num($summary['customers']) }} perusahaan</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="cms-card bx-kpi">
                <div class="lbl"><i class="bi bi-hourglass-split"></i> Menunggu bayar</div>
                <div class="val">{{ bx_num($summary['pending']) }}</div>
                <div class="sub">senilai {{ bx_rp($summary['pending_amount']) }}</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="cms-card bx-kpi">
                <div class="lbl"><i class="bi bi-x-circle"></i> Gagal / batal / kedaluwarsa</div>
                <div class="val">{{ bx_num($summary['failed']) }}</div>
                <div class="sub">dalam periode ini</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="cms-card bx-kpi">
                <div class="lbl"><i class="bi bi-pie-chart"></i> Lunas per jenis</div>
                @forelse($types as $k => $label)
                    @php $row = $byType[$k] ?? null; @endphp
                    @if($row)
                        <div class="d-flex justify-content-between small mt-2"><span>{{ $label }}</span><span class="tx-amt">{{ bx_rp($row->amount) }}</span></div>
                    @endif
                @empty
                @endforelse
                @if($byType->isEmpty())<div class="sub mt-2">Belum ada transaksi lunas.</div>@endif
            </div>
        </div>
    </div>

    <div class="cms-card mb-4">
        <div class="bx-h">
            <div><h6>Pendapatan 12 bulan terakhir</h6><div class="hint">Hanya transaksi lunas, semua jenis · arahkan kursor ke batang untuk nilai</div></div>
        </div>
        <div class="tx-cols" role="img" aria-label="Grafik pendapatan per bulan">
            @foreach($monthly as $m)
                <div class="tx-col">
                    <span class="tip">{{ $m['label'] }} · {{ bx_rp($m['amount']) }}</span>
                    <div class="b" style="height:{{ $m['amount'] > 0 ? max(2, $m['amount'] / $maxMonth * 100) : 0 }}%"></div>
                </div>
            @endforeach
        </div>
        <div class="tx-lbl">@foreach($monthly as $m)<span>{{ $m['label'] }}</span>@endforeach</div>
    </div>

    <div class="cms-card">
        <div class="table-responsive">
            <table class="table align-middle mb-0 bx-table">
                <thead><tr><th>Tanggal</th><th>Perusahaan</th><th>Dibuat oleh</th><th>Jenis / paket</th><th class="text-end">Total</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse($transactions as $t)
                        @php $sk = $TC::statusKey($t->transaction_status); @endphp
                        <tr>
                            <td class="text-nowrap">{{ $t->created_at?->locale('id')->translatedFormat('d M Y') }}<small class="d-block text-muted">{{ $t->created_at?->format('H:i') }} · {{ $t->order_id }}</small></td>
                            <td style="min-width:200px">
                                @if($t->company)
                                    <a href="{{ route('backoffice.customers.show', $t->company_id) }}" class="bx-co text-decoration-none">
                                        <span class="av">{{ bx_initials($t->company->company_name) }}</span>
                                        <span style="min-width:0"><b>{{ $t->company->company_name }}</b><small>{{ $t->company->company_code }}</small></span>
                                    </a>
                                @else
                                    <span class="text-muted">Perusahaan dihapus</span>
                                @endif
                            </td>
                            <td style="min-width:130px;max-width:190px;overflow-wrap:anywhere">{{ $t->creator?->name ?? '—' }}<small class="d-block text-muted">{{ $t->creator?->email }}</small></td>
                            <td class="text-nowrap">{{ $types[$t->purchase_type ?? 'subscription'] ?? $t->purchase_type }}<small class="d-block text-muted">{{ $t->plan_name ?? $t->plan?->plan_name }}{{ $t->billing_cycle ? ' · ' . $t->billing_cycle : '' }}</small></td>
                            <td class="text-end tx-amt">{{ bx_rp($t->gross_amount) }}</td>
                            <td><span class="bx-st {{ $stClass[$sk] }}"><i class="bi {{ $stIcon[$sk] }}"></i> {{ $statuses[$sk] }}</span>
                                @php $method = $TC::methodLabel($t); @endphp
                                @if($t->settlement_time || $method)
                                    <small class="d-block text-muted mt-1">{{ $method }}{{ $t->settlement_time && $method ? ' · ' : '' }}{{ $t->settlement_time?->locale('id')->translatedFormat('d M H:i') }}</small>
                                @endif
                            </td>
                            <td class="text-end"><a href="{{ route('backoffice.transactions.invoice', $t->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary" title="Lihat / cetak invoice" aria-label="Invoice"><i class="bi bi-printer"></i></a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="bx-empty"><i class="bi bi-receipt d-block fs-3 mb-1"></i>Belum ada transaksi untuk filter ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($transactions->hasPages())<div class="p-3">{{ $transactions->links() }}</div>@endif
    </div>
</div>
@endsection
