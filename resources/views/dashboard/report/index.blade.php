@extends('dashboard.layouts.wrapper')

@section('title', 'Laporan')

@section('content')
@php
    $k = $r['kpi'];
    $p = $r['previous'];
    $num = fn ($v) => number_format((float) $v, 0, ',', '.');
    $rp = fn ($v) => 'Rp' . number_format((float) $v, 0, ',', '.');
    // Rupiah ringkas untuk kartu: Rp45,5 jt / Rp1,2 M.
    $rpShort = function ($v) use ($rp) {
        $v = (float) $v;
        return match (true) {
            $v >= 1e12 => 'Rp' . number_format($v / 1e12, 1, ',', '.') . ' T',
            $v >= 1e9 => 'Rp' . number_format($v / 1e9, 1, ',', '.') . ' M',
            $v >= 1e6 => 'Rp' . number_format($v / 1e6, 1, ',', '.') . ' jt',
            default => $rp($v),
        };
    };
    $dur = fn ($h) => app(\App\Services\Reports\AssetReport::class)->duration($h);
    $chg = function ($now, $before) {
        $c = \App\Services\Reports\AssetReport::change($now, $before);
        if ($c === null) return '';
        $cls = $c > 0 ? 'up' : ($c < 0 ? 'down' : 'flat');
        return '<span class="rp-chg ' . $cls . '" title="Dibanding periode sebelumnya">' . ($c > 0 ? '▲' : ($c < 0 ? '▼' : '•')) . ' ' . number_format(abs($c), 0, ',', '.') . '%</span>';
    };
    $canExport = auth()->user()->hasPermission('report.export');
    $unit = ['day' => 'per hari', 'week' => 'per minggu', 'month' => 'per bulan'][$r['meta']['granularity']] ?? '';
@endphp

<style>
    .rp-card { background:var(--bs-body-bg); border:1px solid rgba(15,27,45,.08); border-radius:16px; box-shadow:0 1px 2px rgba(15,27,45,.04), 0 8px 24px -16px rgba(15,27,45,.16); }
    .rp-hero { background:linear-gradient(125deg,#0b2447 0%,#163f86 45%,#1f5fbf 75%,#14b8a6 130%); color:#fff; border:0; }
    .rp-hero h4 { color:#fff !important; margin:0; font-weight:800; }
    .rp-hero .sub { color:rgba(255,255,255,.75); font-size:.88rem; }
    .rp-logo { width:132px; height:62px; border-radius:12px; background:#fff; display:grid; place-items:center; padding:8px 12px; flex:none; }
    .rp-logo img { max-width:100%; max-height:100%; object-fit:contain; }
    .rp-kicker { font-size:.72rem; font-weight:800; letter-spacing:.12em; text-transform:uppercase; color:#67e8f9; }
    .rp-btn { display:inline-flex; align-items:center; gap:8px; font-weight:700; font-size:.86rem; padding:9px 16px; border-radius:11px; border:1px solid rgba(255,255,255,.35); color:#fff; background:rgba(255,255,255,.08); }
    .rp-btn:hover { background:rgba(255,255,255,.18); color:#fff; }
    .rp-btn.solid { background:#fff; color:#0b2447; border-color:#fff; }
    .rp-btn.solid:hover { background:#e8f0fd; color:#0b2447; }
    .rp-presets { display:flex; flex-wrap:wrap; gap:6px; }
    .rp-presets a, .rp-presets button { font-size:.82rem; font-weight:700; padding:7px 14px; border-radius:999px; border:1px solid rgba(15,27,45,.12); color:#4b5a6e; background:var(--bs-body-bg); }
    .rp-presets a.on, .rp-presets button.on { background:#0b2447; color:#fff; border-color:#0b2447; }
    .rp-kpi { padding:18px 20px; height:100%; }
    .rp-kpi .lbl { font-size:.78rem; font-weight:700; color:#8592a6; display:flex; gap:6px; align-items:center; }
    .rp-kpi .val { font-size:1.55rem; font-weight:800; color:#0b2447; margin-top:6px; line-height:1.15; font-variant-numeric:tabular-nums; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .rp-kpi .sub { font-size:.76rem; color:#8592a6; margin-top:4px; display:flex; gap:6px; align-items:center; flex-wrap:wrap; }
    .rp-chg { font-size:.7rem; font-weight:800; padding:1px 7px; border-radius:999px; }
    .rp-chg.up { background:#dcfce7; color:#166534; } .rp-chg.down { background:#fee2e2; color:#991b1b; } .rp-chg.flat { background:#eef2f7; color:#4b5a6e; }
    .rp-h { padding:18px 20px 0; display:flex; justify-content:space-between; gap:10px; flex-wrap:wrap; }
    .rp-h h6 { margin:0; font-weight:800; color:#0b2447; }
    .rp-h .hint { font-size:.76rem; color:#8592a6; }
    .rp-legend { display:flex; gap:14px; font-size:.78rem; font-weight:600; color:#4b5a6e; }
    .rp-legend i { display:inline-block; width:10px; height:10px; border-radius:3px; margin-right:6px; vertical-align:-1px; }
    .rp-hl { list-style:none; margin:0; padding:14px 20px 20px; display:grid; gap:10px; }
    .rp-hl li { display:flex; gap:10px; font-size:.9rem; color:#24364d; }
    .rp-hl li i { color:#14b8a6; margin-top:2px; }
    .rp-bars { padding:14px 20px 20px; }
    .rp-bar { margin-bottom:12px; } .rp-bar:last-child { margin-bottom:0; }
    .rp-bar .top { display:flex; justify-content:space-between; gap:8px; font-size:.84rem; margin-bottom:5px; }
    .rp-bar .top span:first-child { font-weight:600; color:#24364d; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .rp-bar .top span:last-child { color:#4b5a6e; font-variant-numeric:tabular-nums; white-space:nowrap; }
    .rp-track { height:8px; border-radius:4px; background:#eef2f7; overflow:hidden; }
    .rp-fill { height:100%; border-radius:4px; background:#2a78d6; min-width:3px; }
    .rp-table th { font-size:.7rem; letter-spacing:.06em; text-transform:uppercase; color:#8592a6; font-weight:800; white-space:nowrap; }
    .rp-table td { font-size:.85rem; }
    .rp-empty { padding:26px 20px; text-align:center; color:#8592a6; font-size:.86rem; }
    .rp-formats { display:grid; grid-template-columns:repeat(auto-fit, minmax(210px, 1fr)); gap:12px; padding:16px 20px 20px; }
    .rp-format { display:flex; gap:12px; align-items:center; padding:14px; border-radius:14px; border:1px solid rgba(15,27,45,.08); color:inherit; }
    .rp-format:hover { border-color:#1f5fbf; color:inherit; box-shadow:0 10px 24px -18px rgba(31,95,191,.6); }
    .rp-format i { width:42px; height:42px; border-radius:12px; display:grid; place-items:center; font-size:1.3rem; flex:none; }
    .rp-format b { display:block; color:#0b2447; }
    .rp-format small { color:#8592a6; }
    #rpRequests, #rpCost { min-height:260px; }
</style>

<div class="content-wrapper">
<div class="container-xxl flex-grow-1 container-p-y">

    {{-- Header --}}
    <div class="rp-card rp-hero p-3 p-md-4 mb-3">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                @php $companyLogo = \App\Services\CompanyBranding::logoUrl(auth()->user()->company); @endphp
                <div class="rp-logo" title="{{ $companyLogo ? 'Logo perusahaan' : 'Belum ada logo perusahaan — laporan memakai logo Vasetra' }}">
                    <img src="{{ $companyLogo ?? asset('assets/images/brand/vasetra-logo.png') }}" alt="Logo">
                </div>
                <div>
                    <div class="rp-kicker">{{ $r['meta']['title'] }}</div>
                    <h4 class="mt-1">{{ $r['meta']['company'] }}</h4>
                    <div class="sub">{{ $r['meta']['period'] }} · dibanding {{ $r['meta']['previous'] }}</div>
                    @if(!$companyLogo && auth()->user()->isAdministrator())
                        <a href="{{ route('company.edit') }}" class="d-inline-block mt-2 small fw-bold" style="color:#67e8f9"><i class="bi bi-image me-1"></i>Pasang logo perusahaan untuk laporan</a>
                    @endif
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('reports.print', $query) }}" target="_blank" class="rp-btn"><i class="bi bi-printer"></i> Cetak / PDF</a>
                @if($canExport)
                    <a href="{{ route('reports.excel', $query) }}" class="rp-btn"><i class="bi bi-file-earmark-spreadsheet"></i> Excel</a>
                    <a href="{{ route('reports.pptx', $query) }}" class="rp-btn solid"><i class="bi bi-file-earmark-slides"></i> PowerPoint</a>
                @endif
            </div>
        </div>
    </div>

    {{-- Pilih periode --}}
    <div class="rp-card p-3 mb-4">
        <form method="GET" action="{{ route('reports.index') }}" class="d-flex flex-wrap gap-2 align-items-center" id="rpForm">
            <div class="rp-presets">
                @foreach($presets as $key => $label)
                    @if($key === 'custom')
                        <button type="button" class="{{ $period->preset === 'custom' ? 'on' : '' }}" id="rpCustomBtn"><i class="bi bi-calendar-range me-1"></i>{{ $label }}</button>
                    @else
                        <a href="{{ route('reports.index', ['range' => $key]) }}" class="{{ $period->preset === $key ? 'on' : '' }}">{{ $label }}</a>
                    @endif
                @endforeach
            </div>
            <div class="d-flex gap-2 align-items-center ms-auto {{ $period->preset === 'custom' ? '' : 'd-none' }}" id="rpCustom">
                <input type="hidden" name="range" value="custom">
                <input type="date" name="from" value="{{ $period->from->toDateString() }}" class="form-control form-control-sm" style="width:auto">
                <span class="text-muted small">s/d</span>
                <input type="date" name="to" value="{{ $period->to->toDateString() }}" class="form-control form-control-sm" style="width:auto">
                <button class="btn btn-sm btn-primary">Tampilkan</button>
            </div>
        </form>
    </div>

    {{-- Ringkasan + KPI --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-5">
            <div class="rp-card h-100">
                <div class="rp-h"><div><h6><i class="bi bi-stars text-primary me-1"></i> Ringkasan otomatis</h6><div class="hint">Poin penting yang juga tampil di PowerPoint</div></div></div>
                <ul class="rp-hl">
                    @forelse($r['highlights'] as $h)
                        <li><i class="bi bi-check2-circle"></i><span>{{ $h }}</span></li>
                    @empty
                        <li class="text-muted">Belum ada aktivitas pada periode ini.</li>
                    @endforelse
                </ul>
            </div>
        </div>
        <div class="col-xl-7">
            <div class="row g-3">
                @php
                    $tiles = [
                        ['bi-box-seam', 'Total aset', $num($k['total_assets']), $num($k['active_assets']) . ' aktif', ''],
                        ['bi-cash-coin', 'Nilai perolehan', $rpShort($k['total_value']), 'nilai buku ' . $rpShort($k['book_value']), '', $rp($k['total_value'])],
                        ['bi-plus-square', 'Aset baru', $num($k['new_assets']), $rpShort($k['new_assets_value']), $chg($k['new_assets'], $p['new_assets'])],
                        ['bi-arrow-left-right', 'Penugasan', $num($k['assignments']), 'serah terima / pindah', $chg($k['assignments'], $p['assignments'])],
                        ['bi-tools', 'Maintenance', $num($k['maintenance_count']), $num($k['maintenance_done']) . ' selesai', $chg($k['maintenance_count'], $p['maintenance_count'])],
                        ['bi-receipt', 'Biaya maintenance', $rpShort($k['maintenance_cost']), 'realisasi', $chg($k['maintenance_cost'], $p['maintenance_cost']), $rp($k['maintenance_cost'])],
                        ['bi-wrench-adjustable', 'Request masuk', $num($k['requests_created']), $num($k['requests_open']) . ' masih terbuka', $chg($k['requests_created'], $p['requests_created'])],
                        ['bi-stopwatch', 'Rata-rata selesai', $dur($k['avg_resolution_hours']), $num($k['requests_completed']) . ' request selesai', ''],
                    ];
                @endphp
                @foreach($tiles as $tile)
                    @php [$icon, $label, $value, $sub, $c] = $tile; $full = $tile[5] ?? $value; @endphp
                    <div class="col-6 col-md-3">
                        <div class="rp-card rp-kpi">
                            <div class="lbl"><i class="bi {{ $icon }}"></i> {{ $label }}</div>
                            <div class="val" title="{{ $full }}" style="{{ mb_strlen($value) > 7 ? 'font-size:1.2rem' : '' }}">{{ $value }}</div>
                            <div class="sub">{!! $c !!} <span>{{ $sub }}</span></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Tren --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-6">
            <div class="rp-card h-100">
                <div class="rp-h">
                    <div><h6>Request perbaikan</h6><div class="hint">Masuk vs selesai {{ $unit }}</div></div>
                    <div class="rp-legend"><span><i style="background:#2a78d6"></i>Masuk</span><span><i style="background:#1baf7a"></i>Selesai</span></div>
                </div>
                <div id="rpRequests" class="px-2"></div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="rp-card h-100">
                <div class="rp-h">
                    <div><h6>Biaya maintenance</h6><div class="hint">Realisasi (maintenance selesai) {{ $unit }}</div></div>
                </div>
                <div id="rpCost" class="px-2"></div>
            </div>
        </div>
    </div>

    {{-- Komposisi --}}
    <div class="row g-3 mb-4">
        @foreach([['by_category', 'Nilai aset per kategori', 'value', $rp], ['by_location', 'Jumlah aset per lokasi', 'total', fn ($v) => $num($v) . ' aset']] as [$key, $title, $field, $fmt])
            <div class="col-xl-6">
                <div class="rp-card h-100">
                    <div class="rp-h"><div><h6>{{ $title }}</h6><div class="hint">Posisi akhir periode</div></div></div>
                    @if(empty($r[$key]))
                        <div class="rp-empty">Belum ada data.</div>
                    @else
                        @php $max = max(1, collect($r[$key])->max($field)); @endphp
                        <div class="rp-bars">
                            @foreach($r[$key] as $row)
                                <div class="rp-bar">
                                    <div class="top"><span>{{ $row['label'] }}</span><span>{{ $fmt($row[$field]) }}</span></div>
                                    <div class="rp-track"><div class="rp-fill" style="width:{{ $row[$field] / $max * 100 }}%"></div></div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    {{-- Maintenance & teknisi --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-6">
            <div class="rp-card h-100">
                <div class="rp-h"><div><h6>Aset dengan biaya maintenance terbesar</h6><div class="hint">Top 5 pada periode ini</div></div></div>
                <div class="table-responsive mt-2">
                    <table class="table align-middle mb-0 rp-table">
                        <thead><tr><th>Kode</th><th>Nama aset</th><th class="text-end">Kali</th><th class="text-end">Biaya</th></tr></thead>
                        <tbody>
                            @forelse($r['top_cost_assets'] as $a)
                                <tr><td class="fw-bold text-primary">{{ $a['code'] }}</td><td>{{ $a['name'] }}</td><td class="text-end">{{ $num($a['total']) }}</td><td class="text-end">{{ $rp($a['cost']) }}</td></tr>
                            @empty
                                <tr><td colspan="4" class="rp-empty">Tidak ada maintenance pada periode ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="rp-card h-100">
                <div class="rp-h"><div><h6>Teknisi paling aktif</h6><div class="hint">Request yang diselesaikan</div></div></div>
                <div class="table-responsive mt-2">
                    <table class="table align-middle mb-0 rp-table">
                        <thead><tr><th>Teknisi</th><th class="text-end">Selesai</th><th class="text-end">Rata-rata</th></tr></thead>
                        <tbody>
                            @forelse($r['top_technicians'] as $t)
                                <tr><td class="fw-bold">{{ $t['name'] }}</td><td class="text-end">{{ $num($t['total']) }}</td><td class="text-end">{{ $dur($t['avg_hours']) }}</td></tr>
                            @empty
                                <tr><td colspan="3" class="rp-empty">Belum ada request yang diselesaikan.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Ke depan --}}
    <div class="row g-3 mb-4">
        @foreach([['upcoming_maintenance', 'Maintenance 30 hari ke depan', 'bi-calendar-check'], ['warranty_expiring', 'Garansi habis 60 hari ke depan', 'bi-shield-exclamation']] as [$key, $title, $icon])
            <div class="col-xl-6">
                <div class="rp-card h-100">
                    <div class="rp-h"><div><h6><i class="bi {{ $icon }} me-1 text-warning"></i> {{ $title }} ({{ $num($r[$key]['total']) }})</h6><div class="hint">Setelah akhir periode laporan</div></div></div>
                    <div class="table-responsive mt-2">
                        <table class="table align-middle mb-0 rp-table">
                            <thead><tr><th>Tanggal</th><th>Kode</th><th>Aset</th></tr></thead>
                            <tbody>
                                @forelse($r[$key]['items'] as $i)
                                    <tr><td class="text-nowrap">{{ \Illuminate\Support\Carbon::parse($i['date'])->locale('id')->translatedFormat('d M Y') }}</td><td class="fw-bold text-primary">{{ $i['code'] }}</td><td>{{ $i['name'] }}</td></tr>
                                @empty
                                    <tr><td colspan="3" class="rp-empty">Tidak ada.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Format unduhan --}}
    <div class="rp-card mb-2">
        <div class="rp-h"><div><h6>Bagikan laporan</h6><div class="hint">Semua format memakai angka yang sama dengan halaman ini</div></div></div>
        <div class="rp-formats">
            <a class="rp-format" href="{{ route('reports.print', $query) }}" target="_blank">
                <i class="bi bi-filetype-pdf" style="background:#fee2e2;color:#b91c1c"></i>
                <span><b>PDF / Cetak</b><small>Pilih "Simpan sebagai PDF" di jendela cetak</small></span>
            </a>
            @if($canExport)
                <a class="rp-format" href="{{ route('reports.excel', $query) }}">
                    <i class="bi bi-file-earmark-spreadsheet" style="background:#dcfce7;color:#15803d"></i>
                    <span><b>Excel</b><small>Ringkasan, tren, kategori & data rinci per sheet</small></span>
                </a>
                <a class="rp-format" href="{{ route('reports.pptx', $query) }}">
                    <i class="bi bi-file-earmark-slides" style="background:#ffedd5;color:#c2410c"></i>
                    <span><b>PowerPoint</b><small>9 slide siap presentasi rapat</small></span>
                </a>
            @endif
        </div>
    </div>
</div>
</div>
@endsection

@section('js')
<script src="{{ asset('assets/vendor/apexcharts/apexcharts.min.js') }}"></script>
<script>
(function () {
    var btn = document.getElementById('rpCustomBtn');
    if (btn) btn.addEventListener('click', function () { document.getElementById('rpCustom').classList.toggle('d-none'); });

    if (typeof ApexCharts === 'undefined') return;
    var rows = @json($r['trend']);
    var labels = rows.map(function (r) { return r.label; });
    var axis = { colors: '#8592a6', fontSize: '11px' };
    var num = function (v) { return Math.round(v).toLocaleString('id-ID'); };
    var rp = function (v) {
        v = Number(v || 0);
        if (v >= 1e9) return 'Rp' + (v / 1e9).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' M';
        if (v >= 1e6) return 'Rp' + (v / 1e6).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' jt';
        return 'Rp' + Math.round(v).toLocaleString('id-ID');
    };
    var base = function (height) {
        return { type: 'bar', height: height, toolbar: { show: false }, fontFamily: 'inherit', animations: { enabled: true, speed: 350 } };
    };
    var common = {
        dataLabels: { enabled: false },
        legend: { show: false },
        grid: { borderColor: 'rgba(15,27,45,.07)', xaxis: { lines: { show: false } } },
        xaxis: { categories: labels, tickAmount: Math.min(labels.length, 12), labels: { style: axis, rotate: 0, hideOverlappingLabels: true }, axisBorder: { show: false }, axisTicks: { show: false } }
    };

    // Tanpa data: tampilkan keterangan, bukan grafik kosong dengan sumbu aneh.
    var empty = function (el, keys) {
        var has = rows.some(function (r) { return keys.some(function (k) { return Number(r[k]) > 0; }); });
        if (!has) el.innerHTML = '<div class="rp-empty" style="padding-top:90px">Tidak ada data pada periode ini.</div>';
        return !has;
    };
    var elReq = document.getElementById('rpRequests');
    var elCost = document.getElementById('rpCost');

    if (!empty(elReq, ['requests_created', 'requests_completed'])) new ApexCharts(elReq, Object.assign({}, common, {
        chart: base(270),
        series: [
            { name: 'Masuk', data: rows.map(function (r) { return r.requests_created; }) },
            { name: 'Selesai', data: rows.map(function (r) { return r.requests_completed; }) }
        ],
        colors: ['#2a78d6', '#1baf7a'],
        plotOptions: { bar: { columnWidth: rows.length > 20 ? '80%' : '55%', borderRadius: 3, borderRadiusApplication: 'end' } },
        stroke: { show: true, width: 2, colors: ['transparent'] },
        yaxis: { min: 0, forceNiceScale: true, labels: { style: axis, formatter: num } },
        tooltip: { shared: true, intersect: false, y: { formatter: function (v) { return num(v) + ' request'; } } }
    })).render();

    if (!empty(elCost, ['maintenance_cost'])) new ApexCharts(elCost, Object.assign({}, common, {
        chart: base(270),
        series: [{ name: 'Biaya', data: rows.map(function (r) { return r.maintenance_cost; }) }],
        colors: ['#eb6834'],
        plotOptions: { bar: { columnWidth: rows.length > 20 ? '70%' : '50%', borderRadius: 3, borderRadiusApplication: 'end' } },
        yaxis: { min: 0, forceNiceScale: true, labels: { style: axis, formatter: rp } },
        tooltip: { y: { formatter: function (v) { return 'Rp' + Math.round(v).toLocaleString('id-ID'); } } }
    })).render();
})();
</script>
@endsection
