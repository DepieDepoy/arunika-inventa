@extends('backoffice.layouts.app')

@section('title', 'Dashboard')

@section('content')
@include('backoffice.partials.style')
@include('backoffice.customers._style')
@php $statusIcon = \App\Services\Backoffice\CustomerDirectory::STATUS_ICON; @endphp
@php $s = $summary; $statuses = \App\Services\Backoffice\CustomerDirectory::STATUS; @endphp

<div class="container-xxl flex-grow-1 container-p-y">

    <div class="cms-card p-3 p-md-4 mb-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <span class="cms-badge"><i class="bi bi-speedometer2"></i> Ringkasan bisnis</span>
            <h4 class="mb-1 mt-2">Halo, {{ auth('backoffice')->user()->name }} 👋</h4>
            <div class="text-muted small">Kondisi pelanggan Vasetra per {{ now()->locale('id')->translatedFormat('l, d F Y') }}.</div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('backoffice.customers.index') }}" class="btn btn-primary"><i class="bi bi-buildings me-1"></i> Semua pelanggan</a>
            <a href="{{ route('backoffice.analytics.index') }}" class="btn btn-outline-primary"><i class="bi bi-graph-up-arrow me-1"></i> Analitik website</a>
        </div>
    </div>

    {{-- KPI utama --}}
    <div class="row g-3 mb-3">
        <div class="col-md-6 col-xl-3">
            <div class="cms-card bx-kpi hero">
                <div class="lbl"><i class="bi bi-buildings"></i> Total perusahaan</div>
                <div class="val">{{ bx_num($s['companies']) }}</div>
                <div class="sub">+{{ bx_num($s['new_this_month']) }} bulan ini · {{ bx_num($s['new_last_month']) }} bulan lalu</div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="cms-card bx-kpi">
                <div class="lbl"><i class="bi bi-cash-stack"></i> Estimasi pendapatan bulanan (MRR)</div>
                <div class="val">{{ bx_rp($s['mrr']) }}</div>
                <div class="sub">dari {{ bx_num($s['paid']) }} pelanggan berbayar · masuk bulan ini {{ bx_rp($s['paid_revenue_month']) }}</div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="cms-card bx-kpi">
                <div class="lbl"><i class="bi bi-bullseye"></i> Konversi trial → berbayar</div>
                @php $conv = ($s['trial'] + $s['paid'] + $s['expired']) ? round($s['paid'] / ($s['trial'] + $s['paid'] + $s['expired']) * 100, 1) : 0; @endphp
                <div class="val">{{ number_format($conv, 1, ',', '.') }}%</div>
                <div class="sub">{{ bx_num($s['paid']) }} berbayar dari {{ bx_num($s['trial'] + $s['paid'] + $s['expired']) }} perusahaan</div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="cms-card bx-kpi">
                <div class="lbl"><i class="bi bi-activity"></i> Aktif 7 hari terakhir</div>
                <div class="val">{{ bx_num($s['active_7d']) }}</div>
                <div class="sub">perusahaan dengan user yang login</div>
            </div>
        </div>
    </div>

    {{-- Status + pemakaian --}}
    <div class="row g-3 mb-4">
        @foreach(['trial' => 'Sedang trial', 'paid' => 'Berbayar aktif', 'expired' => 'Kedaluwarsa', 'suspended' => 'Ditangguhkan'] as $k => $label)
            <div class="col-6 col-xl">
                <a href="{{ route('backoffice.customers.index', ['status' => $k]) }}" class="cms-card bx-kpi d-block text-reset">
                    <div class="lbl"><span class="bx-st {{ $k }}"><i class="bi {{ $statusIcon[$k] }}"></i> {{ $statuses[$k] }}</span></div>
                    <div class="val">{{ bx_num($s[$k]) }}</div>
                    <div class="sub">{{ $label }}</div>
                </a>
            </div>
        @endforeach
        <div class="col-6 col-xl">
            <div class="cms-card bx-kpi">
                <div class="lbl"><i class="bi bi-box-seam"></i> Total aset</div>
                <div class="val">{{ bx_num($s['assets']) }}</div>
                <div class="sub">{{ bx_num($s['users']) }} user · {{ bx_bytes($s['storage']) }}</div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-8">
            <div class="cms-card h-100">
                <div class="bx-h">
                    <div><h6>Perusahaan baru per bulan</h6><div class="hint">12 bulan terakhir · {{ bx_num($signups30) }} pendaftar dari {{ bx_num($visitors30) }} pengunjung website (30 hari)</div></div>
                </div>
                <div id="bxMonthly" class="px-2"></div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="cms-card h-100">
                <div class="bx-h"><div><h6>Paket yang dipakai</h6><div class="hint">Perusahaan trial &amp; berbayar aktif</div></div></div>
                @if(empty($planMix))
                    <div class="bx-empty">Belum ada data.</div>
                @else
                    @php $maxPlan = max(1, collect($planMix)->sum('total')); @endphp
                    <div class="bx-bars">
                        @foreach($planMix as $p)
                            <div class="bx-bar">
                                <div class="top"><span>{{ $p['label'] }}</span><span>{{ bx_num($p['total']) }} PT · {{ number_format($p['total'] / $maxPlan * 100, 0) }}%</span></div>
                                <div class="track"><div class="fill" style="width:{{ $p['total'] / $maxPlan * 100 }}%"></div></div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-4 col-md-6">
            <div class="cms-card h-100">
                <div class="bx-h">
                    <div><h6><i class="bi bi-alarm text-warning me-1"></i> Segera berakhir</h6><div class="hint">≤ 7 hari lagi — waktunya follow-up</div></div>
                    <a class="more" href="{{ route('backoffice.customers.index', ['sort' => 'ending']) }}">Lihat</a>
                </div>
                @if($expiring->isEmpty())
                    <div class="bx-empty">Tidak ada langganan yang segera berakhir.</div>
                @else
                    <ul class="bx-list">
                        @foreach($expiring as $c)
                            <li>
                                <a href="{{ route('backoffice.customers.show', $c->id) }}" class="bx-co text-reset">
                                    <span class="av">{{ bx_initials($c->company_name) }}</span>
                                    <span style="min-width:0"><b>{{ $c->company_name }}</b><small><span class="bx-st {{ $c->customer_status }}">{{ $statuses[$c->customer_status] }}</span> {{ $c->plan_label }}</small></span>
                                </a>
                                <span class="r"><b>{{ $c->days_left === 0 ? 'Hari ini' : $c->days_left . ' hari' }}</b>{{ $c->ends_at?->locale('id')->translatedFormat('d M') }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
        <div class="col-xl-4 col-md-6">
            <div class="cms-card h-100">
                <div class="bx-h">
                    <div><h6><i class="bi bi-person-plus text-primary me-1"></i> Pendaftar terbaru</h6><div class="hint">Perusahaan yang baru bergabung</div></div>
                    <a class="more" href="{{ route('backoffice.customers.index') }}">Lihat</a>
                </div>
                @if($recent->isEmpty())
                    <div class="bx-empty">Belum ada pelanggan.</div>
                @else
                    <ul class="bx-list">
                        @foreach($recent as $c)
                            <li>
                                <a href="{{ route('backoffice.customers.show', $c->id) }}" class="bx-co text-reset">
                                    <span class="av">{{ bx_initials($c->company_name) }}</span>
                                    <span style="min-width:0"><b>{{ $c->company_name }}</b><small>{{ bx_num($c->users_count) }} user · {{ bx_num($c->assets_count) }} aset</small></span>
                                </a>
                                <span class="r"><span class="bx-st {{ $c->customer_status }}">{{ $statuses[$c->customer_status] }}</span><br>{{ $c->created_at?->locale('id')->diffForHumans() }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
        <div class="col-xl-4 col-md-12">
            <div class="cms-card h-100">
                <div class="bx-h">
                    <div><h6><i class="bi bi-trophy text-success me-1"></i> Aset terbanyak</h6><div class="hint">Pelanggan paling aktif mencatat aset</div></div>
                    <a class="more" href="{{ route('backoffice.customers.index', ['sort' => 'assets']) }}">Lihat</a>
                </div>
                @if($topAssets->isEmpty())
                    <div class="bx-empty">Belum ada data.</div>
                @else
                    <ul class="bx-list">
                        @foreach($topAssets as $c)
                            @php $pct = $c->max_assets ? bx_pct($c->assets_count, $c->max_assets) : null; @endphp
                            <li>
                                <a href="{{ route('backoffice.customers.show', $c->id) }}" class="bx-co text-reset">
                                    <span class="av">{{ bx_initials($c->company_name) }}</span>
                                    <span style="min-width:0"><b>{{ $c->company_name }}</b><small>{{ $c->plan_label }}</small></span>
                                </a>
                                <span class="r"><b>{{ bx_num($c->assets_count) }}</b>{{ $c->max_assets ? $pct . '% kuota' : 'tanpa batas' }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>

    @if($storageAlerts->isNotEmpty())
        <div class="cms-card mb-4">
            <div class="bx-h"><div><h6><i class="bi bi-hdd text-danger me-1"></i> Storage hampir penuh</h6><div class="hint">Pemakaian ≥ 80% — peluang menawarkan add-on storage</div></div></div>
            <div class="row g-3 p-3">
                @foreach($storageAlerts as $c)
                    @php $pct = bx_pct($c->storage_used, $c->storage_limit); @endphp
                    <div class="col-md-6 col-xl-4">
                        <a href="{{ route('backoffice.customers.show', $c->id) }}" class="d-block text-reset">
                            <div class="bx-meter">
                                <div class="top"><b class="text-dark">{{ $c->company_name }}</b><span>{{ bx_bytes($c->storage_used) }} / {{ bx_bytes($c->storage_limit) }}</span></div>
                                <div class="track"><div class="fill {{ bx_level($pct) }}" style="width:{{ $pct }}%"></div></div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection

@section('js')
<script src="{{ asset('assets/vendor/apexcharts/apexcharts.min.js') }}"></script>
<script>
(function () {
    if (typeof ApexCharts === 'undefined') return;
    var rows = @json($monthly);
    var axis = { colors: '#8592a6', fontSize: '11px' };
    new ApexCharts(document.getElementById('bxMonthly'), {
        chart: { type: 'bar', height: 280, toolbar: { show: false }, fontFamily: '"Plus Jakarta Sans", system-ui, sans-serif', animations: { enabled: true, speed: 400 } },
        series: [{ name: 'Perusahaan baru', data: rows.map(function (r) { return r.total; }) }],
        colors: ['#2a78d6'],
        plotOptions: { bar: { columnWidth: '52%', borderRadius: 4, borderRadiusApplication: 'end' } },
        dataLabels: { enabled: false },
        stroke: { show: false },
        grid: { borderColor: 'rgba(15,27,45,.07)', xaxis: { lines: { show: false } } },
        xaxis: { categories: rows.map(function (r) { return r.label; }), labels: { style: axis }, axisBorder: { show: false }, axisTicks: { show: false } },
        yaxis: { min: 0, forceNiceScale: true, labels: { style: axis, formatter: function (v) { return Math.round(v).toLocaleString('id-ID'); } } },
        tooltip: { y: { formatter: function (v) { return v.toLocaleString('id-ID') + ' perusahaan'; } } }
    }).render();
})();
</script>
@endsection
