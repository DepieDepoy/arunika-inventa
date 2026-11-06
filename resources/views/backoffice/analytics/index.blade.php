@extends('backoffice.layouts.app')

@section('title', 'Analitik Pengunjung')

@section('content')
@include('backoffice.partials.style')
@php
    $num = fn ($v) => number_format((float) $v, 0, ',', '.');
    $deviceIcon = ['desktop' => 'bi-display', 'mobile' => 'bi-phone', 'tablet' => 'bi-tablet'];
    $deviceLabel = ['desktop' => 'Desktop', 'mobile' => 'HP', 'tablet' => 'Tablet'];
    $localeLabel = ['id' => 'Bahasa Indonesia', 'en' => 'English'];
    $hasData = $kpi['pageviews']['value'] > 0 || $kpi['signups']['value'] > 0;
@endphp

<style>
    .an-range { display:inline-flex; padding:4px; border-radius:12px; background:#eef2f7; gap:2px; }
    .an-range a { padding:6px 14px; border-radius:9px; font-weight:700; font-size:.82rem; color:#4b5a6e; }
    .an-range a.on { background:#fff; color:#0b2447; box-shadow:0 1px 3px rgba(15,27,45,.12); }
    .an-kpi { padding:18px 20px; height:100%; }
    .an-kpi .lbl { font-size:.78rem; font-weight:700; color:#8592a6; display:flex; align-items:center; gap:6px; }
    .an-kpi .val { font-size:1.75rem; font-weight:800; color:#0b2447; line-height:1.15; margin-top:6px; font-variant-numeric:tabular-nums; }
    .an-kpi .sub { font-size:.76rem; color:#8592a6; margin-top:4px; }
    .an-delta { font-weight:800; font-size:.74rem; padding:1px 7px; border-radius:999px; }
    .an-delta.up { background:#dcfce7; color:#166534; }
    .an-delta.down { background:#fee2e2; color:#991b1b; }
    .an-delta.flat { background:#eef2f7; color:#4b5a6e; }
    .an-live { width:8px; height:8px; border-radius:50%; background:#14b8a6; box-shadow:0 0 0 0 rgba(20,184,166,.6); animation:anPulse 2s infinite; }
    @keyframes anPulse { 70% { box-shadow:0 0 0 8px rgba(20,184,166,0); } 100% { box-shadow:0 0 0 0 rgba(20,184,166,0); } }
    .an-card-h { padding:18px 20px 0; display:flex; justify-content:space-between; align-items:flex-start; gap:10px; flex-wrap:wrap; }
    .an-card-h h6 { margin:0; font-weight:800; color:#0b2447; }
    .an-card-h .hint { font-size:.76rem; color:#8592a6; }
    .an-legend { display:flex; gap:14px; font-size:.78rem; color:#4b5a6e; font-weight:600; }
    .an-legend i { display:inline-block; width:12px; height:3px; border-radius:2px; vertical-align:middle; margin-right:6px; }
    .an-bars { padding:14px 20px 18px; }
    .an-bar { margin-bottom:12px; }
    .an-bar:last-child { margin-bottom:0; }
    .an-bar-top { display:flex; justify-content:space-between; gap:8px; font-size:.84rem; margin-bottom:5px; }
    .an-bar-top span:first-child { font-weight:600; color:#24364d; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .an-bar-top span:last-child { color:#4b5a6e; font-variant-numeric:tabular-nums; white-space:nowrap; }
    .an-bar-top small { color:#8592a6; margin-left:6px; }
    .an-track { height:8px; border-radius:4px; background:#eef2f7; overflow:hidden; }
    .an-fill { height:100%; border-radius:4px; background:#2a78d6; min-width:3px; }
    .an-funnel { padding:16px 20px 20px; }
    .an-step { display:grid; grid-template-columns:minmax(150px, 220px) 1fr 110px; gap:14px; align-items:center; padding:10px 0; border-bottom:1px dashed rgba(15,27,45,.08); }
    .an-step:last-child { border-bottom:0; }
    .an-step .name { font-weight:700; font-size:.86rem; color:#24364d; display:flex; gap:10px; align-items:center; }
    .an-step .name i { width:32px; height:32px; border-radius:9px; display:grid; place-items:center; background:#e8f0fd; color:#1f5fbf; flex:none; }
    .an-step .bar { height:26px; border-radius:6px; background:#eef2f7; overflow:hidden; }
    .an-step .bar div { height:100%; border-radius:6px; background:linear-gradient(90deg,#1f5fbf,#2a78d6); min-width:4px; display:flex; align-items:center; padding-left:10px; color:#fff; font-weight:800; font-size:.8rem; }
    .an-step .bar div.out { color:#24364d; }
    .an-step .rate { text-align:right; font-size:.76rem; color:#8592a6; line-height:1.3; }
    .an-step .rate b { display:block; color:#0b2447; font-size:.9rem; }
    .an-empty { padding:28px 20px; text-align:center; color:#8592a6; font-size:.86rem; }
    .an-table th { font-size:.7rem; letter-spacing:.06em; text-transform:uppercase; color:#8592a6; font-weight:800; white-space:nowrap; }
    .an-table td { font-size:.84rem; }
    .an-ip { font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace; font-size:.8rem; color:#24364d; }
    .an-dev { display:flex; gap:8px; align-items:center; }
    .an-dev i { width:30px; height:30px; border-radius:8px; background:#eef2f7; display:grid; place-items:center; color:#4b5a6e; flex:none; }
    .an-dev small { display:block; color:#8592a6; }
    .an-ok { background:#dcfce7; color:#166534; }
    .an-warn { background:#fef3c7; color:#92400e; }
    #anDaily { min-height:300px; }
    #anSignup { min-height:200px; }
    @media (max-width: 767.98px) {
        .an-step { grid-template-columns:1fr; gap:6px; }
        .an-step .rate { text-align:left; }
    }
</style>

<div class="container-xxl flex-grow-1 container-p-y">

    {{-- Header --}}
    <div class="cms-card p-3 p-md-4 mb-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <span class="cms-badge"><i class="bi bi-graph-up-arrow"></i> Analitik website</span>
            <h4 class="mb-1 mt-2">Pengunjung &amp; konversi</h4>
            <div class="text-muted small">
                Landing page, halaman daftar &amp; login · {{ $from->locale('id')->translatedFormat('d M Y') }} – hari ini.
                Bot dan pegawai yang sedang login backoffice tidak ikut dihitung.
            </div>
        </div>
        <div class="an-range" role="tablist" aria-label="Rentang waktu">
            @foreach($ranges as $days => $label)
                <a href="{{ route('backoffice.analytics.index', ['range' => $days]) }}" class="{{ $range === $days ? 'on' : '' }}">{{ $label }}</a>
            @endforeach
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success"><i class="bi bi-check2-circle me-1"></i> {{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-1"></i> {{ $errors->first() }}</div>
    @endif
    @unless($enabled)
        <div class="alert alert-warning"><i class="bi bi-pause-circle me-1"></i> Pencatatan sedang dimatikan (<code>ANALYTICS_ENABLED=false</code>). Data lama tetap tampil.</div>
    @endunless

    {{-- KPI --}}
    @php
        $delta = function ($k) {
            if (($k['delta'] ?? null) === null) return '';
            $cls = $k['delta'] > 0 ? 'up' : ($k['delta'] < 0 ? 'down' : 'flat');
            $arrow = $k['delta'] > 0 ? '↑' : ($k['delta'] < 0 ? '↓' : '→');
            return '<span class="an-delta ' . $cls . '" title="Dibanding periode sebelumnya">' . $arrow . ' ' . abs($k['delta']) . '%</span>';
        };
    @endphp
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl">
            <div class="cms-card an-kpi">
                <div class="lbl"><i class="bi bi-people"></i> Pengunjung unik</div>
                <div class="val">{{ $num($kpi['visitors']['value']) }}</div>
                <div class="sub">{!! $delta($kpi['visitors']) !!} sebelumnya {{ $num($kpi['visitors']['prev']) }}</div>
            </div>
        </div>
        <div class="col-6 col-xl">
            <div class="cms-card an-kpi">
                <div class="lbl"><i class="bi bi-file-earmark-text"></i> Halaman dibuka</div>
                <div class="val">{{ $num($kpi['pageviews']['value']) }}</div>
                <div class="sub">{!! $delta($kpi['pageviews']) !!} sebelumnya {{ $num($kpi['pageviews']['prev']) }}</div>
            </div>
        </div>
        <div class="col-6 col-xl">
            <div class="cms-card an-kpi">
                <div class="lbl"><i class="bi bi-person-check"></i> Pendaftar baru</div>
                <div class="val">{{ $num($kpi['signups']['value']) }}</div>
                <div class="sub">{!! $delta($kpi['signups']) !!} sebelumnya {{ $num($kpi['signups']['prev']) }}</div>
            </div>
        </div>
        <div class="col-6 col-xl">
            <div class="cms-card an-kpi">
                <div class="lbl"><i class="bi bi-bullseye"></i> Konversi</div>
                <div class="val">{{ number_format($kpi['conversion']['value'], 1, ',', '.') }}%</div>
                <div class="sub">pendaftar ÷ pengunjung landing</div>
            </div>
        </div>
        <div class="col-12 col-xl">
            <div class="cms-card an-kpi">
                <div class="lbl"><span class="an-live"></span> Aktif 30 menit terakhir</div>
                <div class="val">{{ $num($kpi['active']) }}</div>
                <div class="sub">pengunjung unik</div>
            </div>
        </div>
    </div>

    {{-- Grafik harian --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-8">
            <div class="cms-card h-100">
                <div class="an-card-h">
                    <div>
                        <h6>Trafik harian</h6>
                        <div class="hint">Pengunjung unik &amp; halaman dibuka per hari</div>
                    </div>
                    <div class="an-legend">
                        <span><i style="background:#2a78d6"></i>Pengunjung unik</span>
                        <span><i style="background:#eb6834"></i>Halaman dibuka</span>
                    </div>
                </div>
                <div id="anDaily" class="px-2"></div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="cms-card h-100">
                <div class="an-card-h">
                    <div>
                        <h6>Pendaftar per hari</h6>
                        <div class="hint">Perusahaan baru yang mulai trial</div>
                    </div>
                </div>
                <div id="anSignup" class="px-2"></div>
            </div>
        </div>
    </div>

    {{-- Funnel --}}
    <div class="cms-card mb-4">
        <div class="an-card-h">
            <div>
                <h6>Funnel konversi</h6>
                <div class="hint">Dari pengunjung sampai pelanggan berbayar. Tahap daftar, login &amp; upgrade diambil dari data akun asli.</div>
            </div>
        </div>
        <div class="an-funnel">
            @foreach($funnel as $i => $step)
                <div class="an-step">
                    <div class="name"><i class="bi {{ $step['icon'] }}"></i> {{ $step['label'] }}</div>
                    <div class="bar" title="{{ $num($step['value']) }} · {{ $step['pct_total'] }}% dari pengunjung landing">
                        <div class="{{ $step['pct_total'] < 12 ? 'out' : '' }}" style="width:{{ max($step['pct_total'], 0.5) }}%;{{ $step['pct_total'] < 12 ? 'background:transparent;min-width:0' : '' }}">{{ $num($step['value']) }}</div>
                    </div>
                    <div class="rate">
                        @if($i === 0)
                            <b>100%</b>titik awal
                        @else
                            <b>{{ $step['pct_prev'] !== null ? number_format($step['pct_prev'], 1, ',', '.') . '%' : '—' }}</b>dari tahap sebelumnya
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Rincian --}}
    @php
        $blocks = [
            ['key' => 'device', 'title' => 'Perangkat', 'hint' => 'Desktop, HP, atau tablet', 'map' => $deviceLabel],
            ['key' => 'source', 'title' => 'Sumber kunjungan', 'hint' => 'Google, media sosial, link langsung, utm_source', 'map' => []],
            ['key' => 'os', 'title' => 'Sistem operasi', 'hint' => 'Windows, Android, iOS, dll.', 'map' => []],
            ['key' => 'browser', 'title' => 'Browser', 'hint' => 'Termasuk browser dalam aplikasi (Instagram, dll.)', 'map' => []],
            ['key' => 'locale', 'title' => 'Bahasa landing', 'hint' => 'Versi / (Indonesia) atau /en (English)', 'map' => $localeLabel],
            ['key' => 'campaign', 'title' => 'Kampanye (utm_campaign)', 'hint' => 'Isi saat memasang iklan: ?utm_campaign=nama', 'map' => []],
        ];
    @endphp
    <div class="row g-3 mb-4">
        @foreach($blocks as $b)
            <div class="col-md-6 col-xl-4">
                <div class="cms-card h-100">
                    <div class="an-card-h">
                        <div><h6>{{ $b['title'] }}</h6><div class="hint">{{ $b['hint'] }}</div></div>
                    </div>
                    @if(empty($breakdowns[$b['key']]))
                        <div class="an-empty">Belum ada data.</div>
                    @else
                        <div class="an-bars">
                            @foreach($breakdowns[$b['key']] as $row)
                                <div class="an-bar">
                                    <div class="an-bar-top">
                                        <span>
                                            @if($b['key'] === 'device')<i class="bi {{ $deviceIcon[$row['label']] ?? 'bi-question' }} me-1"></i>@endif
                                            {{ $b['map'][$row['label']] ?? $row['label'] }}
                                        </span>
                                        <span>{{ $num($row['total']) }}<small>{{ number_format($row['pct'], 1, ',', '.') }}%</small></span>
                                    </div>
                                    <div class="an-track"><div class="an-fill" style="width:{{ $row['pct'] }}%"></div></div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    {{-- Pendaftar terbaru + halaman --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-8">
            <div class="cms-card h-100">
                <div class="an-card-h">
                    <div><h6>Pendaftar terbaru</h6><div class="hint">10 perusahaan terakhir yang mendaftar di periode ini</div></div>
                </div>
                <div class="table-responsive mt-2">
                    <table class="table align-middle mb-0 an-table">
                        <thead><tr><th>Perusahaan</th><th>Admin</th><th>Daftar</th><th>Sumber</th><th>Status</th></tr></thead>
                        <tbody>
                            @forelse($signupList as $s)
                                <tr>
                                    <td class="fw-bold text-dark">{{ $s['company'] }}</td>
                                    <td>{{ $s['name'] }}<small class="d-block text-muted">{{ $s['email'] }}</small></td>
                                    <td class="text-nowrap">{{ $s['created_at']?->locale('id')->translatedFormat('d M, H:i') }}<small class="d-block text-muted">{{ $s['created_at']?->locale('id')->diffForHumans() }}</small></td>
                                    <td>
                                        {{ $s['source'] ?? '—' }}
                                        @if($s['device'])<small class="d-block text-muted"><i class="bi {{ $deviceIcon[$s['device']] ?? '' }}"></i> {{ $deviceLabel[$s['device']] ?? $s['device'] }}{{ $s['ip'] ? ' · ' . $s['ip'] : '' }}</small>@endif
                                    </td>
                                    <td class="text-nowrap">
                                        @if($s['paid'])
                                            <span class="cms-chip an-ok"><i class="bi bi-credit-card"></i> Berbayar</span>
                                        @else
                                            <span class="cms-chip an-warn">Trial</span>
                                        @endif
                                        <span class="cms-chip {{ $s['logged_in'] ? 'cms-chip-blue' : '' }}">{{ $s['logged_in'] ? 'Sudah login' : 'Belum login' }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="an-empty">Belum ada pendaftar di periode ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="cms-card h-100">
                <div class="an-card-h">
                    <div><h6>Halaman</h6><div class="hint">Halaman dibuka · pengunjung unik</div></div>
                </div>
                @if(empty($pages))
                    <div class="an-empty">Belum ada data.</div>
                @else
                    @php $maxPv = max(1, collect($pages)->max('pageviews')); @endphp
                    <div class="an-bars">
                        @foreach($pages as $p)
                            <div class="an-bar">
                                <div class="an-bar-top">
                                    <span>{{ $p['label'] }}</span>
                                    <span>{{ $num($p['pageviews']) }}<small>{{ $num($p['visitors']) }} org</small></span>
                                </div>
                                <div class="an-track"><div class="an-fill" style="width:{{ round($p['pageviews'] / $maxPv * 100, 1) }}%"></div></div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Kunjungan terbaru --}}
    <div class="cms-card mb-4" id="kunjungan">
        <div class="an-card-h">
            <div>
                <h6>Kunjungan terbaru</h6>
                <div class="hint">IP disimpan {{ $ipDays }} hari, setelah itu dikosongkan otomatis. Data statistik lain tetap ada.</div>
            </div>
            <form method="GET" action="{{ route('backoffice.analytics.index') }}#kunjungan" class="d-flex gap-2 flex-wrap">
                <input type="hidden" name="range" value="{{ $range }}">
                <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Cari IP / sumber / kampanye" style="width:210px">
                <select name="page_type" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                    <option value="">Semua halaman</option>
                    @foreach(\App\Models\AnalyticsHit::PAGES as $k => $label)<option value="{{ $k }}" @selected(request('page_type') === $k)>{{ $label }}</option>@endforeach
                </select>
                <select name="device" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                    <option value="">Semua perangkat</option>
                    @foreach($deviceLabel as $k => $label)<option value="{{ $k }}" @selected(request('device') === $k)>{{ $label }}</option>@endforeach
                </select>
                <button class="btn btn-sm btn-primary"><i class="bi bi-search"></i></button>
            </form>
        </div>
        <div class="table-responsive mt-3">
            <table class="table align-middle mb-0 an-table">
                <thead><tr><th>Waktu</th><th>IP</th><th>Perangkat</th><th>Halaman</th><th>Sumber</th><th>Hasil</th></tr></thead>
                <tbody>
                    @forelse($visits as $v)
                        <tr>
                            <td class="text-nowrap">{{ $v->created_at?->locale('id')->translatedFormat('d M, H:i') }}<small class="d-block text-muted">{{ $v->created_at?->locale('id')->diffForHumans() }}</small></td>
                            <td>
                                @if($v->ip)
                                    <a href="{{ route('backoffice.analytics.index', ['range' => $range, 'q' => $v->ip]) }}#kunjungan" class="an-ip" title="Lihat semua kunjungan dari IP ini">{{ $v->ip }}</a>
                                @else
                                    <span class="text-muted small">dihapus (&gt; {{ $ipDays }} hari)</span>
                                @endif
                            </td>
                            <td>
                                <div class="an-dev">
                                    <i class="bi {{ $deviceIcon[$v->device] ?? 'bi-question' }}" title="{{ $deviceLabel[$v->device] ?? $v->device }}"></i>
                                    <span>{{ $v->os }}<small>{{ $v->browser }}</small></span>
                                </div>
                            </td>
                            <td class="text-nowrap">{{ \App\Models\AnalyticsHit::PAGES[$v->page] ?? $v->page }}@if($v->page === 'landing')<small class="d-block text-muted">{{ strtoupper((string) $v->locale) }}</small>@endif</td>
                            <td>
                                {{ $v->source }}
                                @if($v->utm_campaign)<small class="d-block text-muted">{{ $v->utm_campaign }}</small>@elseif($v->referrer_host && $v->referrer_host !== strtolower($v->source))<small class="d-block text-muted">{{ $v->referrer_host }}</small>@endif
                            </td>
                            <td>
                                @if($v->signed_up_company)
                                    <span class="cms-chip an-ok" title="Pengunjung ini kemudian mendaftar"><i class="bi bi-person-check"></i> {{ \Illuminate\Support\Str::limit($v->signed_up_company, 22) }}</span>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="an-empty">Belum ada kunjungan{{ request('q') || request('page_type') || request('device') ? ' yang cocok dengan filter' : '' }}.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($visits->hasPages())<div class="p-3">{{ $visits->fragment('kunjungan')->links() }}</div>@endif
    </div>

    {{-- Pengaturan --}}
    <div class="row g-3">
        <div class="col-lg-7">
            <div class="cms-card p-3 p-md-4 h-100">
                <h6 class="fw-bold text-dark mb-1"><i class="bi bi-google me-1"></i> Google Analytics 4 <span class="cms-chip ms-1">opsional</span></h6>
                <p class="text-muted small mb-3">
                    Analitik di halaman ini sudah berjalan tanpa Google. Isi Measurement ID kalau ingin data juga masuk ke Google Analytics
                    (mis. untuk lokasi pengunjung atau iklan Google Ads). Kosongkan untuk mematikan.
                </p>
                <form method="POST" action="{{ route('backoffice.analytics.settings') }}" class="d-flex gap-2 flex-wrap align-items-start">
                    @csrf
                    <div>
                        <input type="text" name="ga4_id" value="{{ old('ga4_id', $ga4Id) }}" class="form-control" placeholder="G-XXXXXXXXXX" style="width:220px;text-transform:uppercase" maxlength="20">
                        <div class="cms-help">Google Analytics › Admin › Data streams › Measurement ID</div>
                    </div>
                    <button class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Simpan</button>
                    @if($ga4Id)<span class="cms-chip an-ok align-self-center"><i class="bi bi-check-circle"></i> Aktif</span>@endif
                </form>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="cms-card p-3 p-md-4 h-100">
                <h6 class="fw-bold text-dark mb-2"><i class="bi bi-lightbulb me-1"></i> Tips melacak iklan</h6>
                <p class="text-muted small mb-2">Tambahkan parameter UTM di link yang dibagikan, supaya sumbernya terbaca jelas:</p>
                <code class="small d-block p-2 rounded" style="background:#f4f7fb;word-break:break-all">{{ url('/') }}?utm_source=instagram&amp;utm_campaign=promo-november</code>
                <p class="text-muted small mt-2 mb-0">Pendaftar dari link itu akan tercatat dengan sumber <b>Instagram</b> &amp; kampanye <b>promo-november</b>.</p>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="{{ asset('assets/vendor/apexcharts/apexcharts.min.js') }}"></script>
<script>
(function () {
    if (typeof ApexCharts === 'undefined') return;

    var daily = @json($daily);
    var labels = daily.map(function (d) { return d.label; });
    var fmt = function (v) { return Number(v || 0).toLocaleString('id-ID'); };
    var base = {
        fontFamily: '"Plus Jakarta Sans", system-ui, sans-serif',
        toolbar: { show: false }, zoom: { enabled: false },
        animations: { enabled: true, speed: 400 }
    };
    var axisText = { colors: '#8592a6', fontSize: '11px' };
    var grid = { borderColor: 'rgba(15,27,45,.07)', strokeDashArray: 0, xaxis: { lines: { show: false } }, padding: { left: 8, right: 8 } };
    var tickAmount = Math.min(daily.length, 10);

    new ApexCharts(document.getElementById('anDaily'), {
        chart: Object.assign({ type: 'area', height: 300 }, base),
        series: [
            { name: 'Pengunjung unik', data: daily.map(function (d) { return d.visitors; }) },
            { name: 'Halaman dibuka', data: daily.map(function (d) { return d.pageviews; }) }
        ],
        colors: ['#2a78d6', '#eb6834'],
        stroke: { width: 2, curve: 'smooth' },
        fill: { type: 'gradient', gradient: { opacityFrom: [.22, 0], opacityTo: [0, 0] } },
        markers: { size: 0, hover: { size: 5 }, strokeColors: '#fff', strokeWidth: 2 },
        dataLabels: { enabled: false },
        legend: { show: false },
        grid: grid,
        xaxis: { categories: labels, tickAmount: tickAmount, labels: { style: axisText, rotate: 0, hideOverlappingLabels: true }, axisBorder: { show: false }, axisTicks: { show: false }, crosshairs: { stroke: { color: '#b6c2d2', dashArray: 3 } } },
        yaxis: { min: 0, forceNiceScale: true, labels: { style: axisText, formatter: function (v) { return fmt(Math.round(v)); } } },
        tooltip: { shared: true, intersect: false, y: { formatter: fmt } }
    }).render();

    new ApexCharts(document.getElementById('anSignup'), {
        chart: Object.assign({ type: 'bar', height: 230 }, base),
        series: [{ name: 'Pendaftar', data: daily.map(function (d) { return d.signups; }) }],
        colors: ['#2a78d6'],
        plotOptions: { bar: { columnWidth: daily.length > 40 ? '70%' : '55%', borderRadius: 4, borderRadiusApplication: 'end' } },
        stroke: { show: false },
        dataLabels: { enabled: false },
        legend: { show: false },
        grid: grid,
        xaxis: { categories: labels, tickAmount: Math.min(daily.length, 6), labels: { style: axisText, rotate: 0, hideOverlappingLabels: true }, axisBorder: { show: false }, axisTicks: { show: false } },
        yaxis: { min: 0, forceNiceScale: true, labels: { style: axisText, formatter: function (v) { return fmt(Math.round(v)); } } },
        tooltip: { y: { formatter: function (v) { return fmt(v) + ' perusahaan'; } } },
        states: { hover: { filter: { type: 'darken', value: .85 } } }
    }).render();
})();
</script>
@endsection
