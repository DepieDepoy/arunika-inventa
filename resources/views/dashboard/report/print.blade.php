@php
    $company = auth()->user()->company;
    $logoSrc = \App\Services\CompanyBranding::reportLogoUrl($company);
    $k = $r['kpi'];
    $p = $r['previous'];
    $num = fn ($v) => number_format((float) $v, 0, ',', '.');
    $rp = fn ($v) => 'Rp' . number_format((float) $v, 0, ',', '.');
    $dur = fn ($h) => app(\App\Services\Reports\AssetReport::class)->duration($h);
    $chg = function ($now, $before) {
        $c = \App\Services\Reports\AssetReport::change($now, $before);
        if ($c === null) return '';
        return '<span class="chg ' . ($c >= 0 ? 'up' : 'down') . '">' . ($c >= 0 ? '▲' : '▼') . ' ' . number_format(abs($c), 0, ',', '.') . '%</span>';
    };
    $trendMax = max(1, collect($r['trend'])->max(fn ($t) => max($t['requests_created'], $t['requests_completed'])));
    $costMax = max(1, collect($r['trend'])->max('maintenance_cost'));
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>{{ $r['meta']['title'] }} - {{ $r['meta']['company'] }}</title>
<meta name="robots" content="noindex">
<style>
    * { box-sizing:border-box; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
    body { margin:0; background:#eef2f7; font-family:"Segoe UI", Arial, sans-serif; color:#24364d; font-size:12px; }
    .bar-top { position:sticky; top:0; z-index:5; display:flex; justify-content:flex-end; gap:8px; padding:12px 20px; background:#eef2f7; }
    .bar-top button { border:0; border-radius:10px; padding:9px 18px; font-weight:700; cursor:pointer; background:#1f5fbf; color:#fff; }
    .bar-top button.ghost { background:#fff; color:#24364d; }
    .page { width:210mm; min-height:297mm; margin:0 auto 20px; background:#fff; padding:16mm 15mm; box-shadow:0 8px 30px rgba(15,27,45,.12); }
    .head { display:flex; justify-content:space-between; align-items:flex-start; border-bottom:3px solid #0b2447; padding-bottom:14px; margin-bottom:16px; }
    .head img { max-height:48px; max-width:200px; object-fit:contain; }
    .head .addr { font-size:10px; color:#8592a6; max-width:260px; margin-top:4px; white-space:pre-line; }
    .head .t { text-align:right; }
    .head .t .kick { font-size:10px; font-weight:800; letter-spacing:.14em; color:#14b8a6; text-transform:uppercase; }
    .head .t h1 { margin:2px 0; font-size:20px; color:#0b2447; }
    .head .t div { color:#8592a6; }
    h2 { font-size:13px; color:#0b2447; margin:18px 0 8px; text-transform:uppercase; letter-spacing:.06em; }
    .kpis { display:grid; grid-template-columns:repeat(4, 1fr); gap:8px; }
    .kpi { border:1px solid #dce3ec; border-left:4px solid #1f5fbf; border-radius:8px; padding:9px 10px; }
    .kpi.t { border-left-color:#14b8a6; }
    .kpi .l { font-size:10px; color:#8592a6; font-weight:700; }
    .kpi .v { font-size:16px; font-weight:800; color:#0b2447; margin:3px 0 2px; }
    .kpi .s { font-size:10px; color:#8592a6; }
    .chg { font-size:9px; font-weight:800; padding:1px 5px; border-radius:8px; margin-right:4px; }
    .chg.up { background:#dcfce7; color:#166534; } .chg.down { background:#fee2e2; color:#991b1b; }
    .hl { margin:0; padding:10px 12px 10px 28px; background:#f4f7fb; border-radius:8px; }
    .hl li { margin:4px 0; }
    .cols { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
    .chart { display:flex; align-items:flex-end; gap:2px; height:120px; border-bottom:1px solid #b6c2d2; padding-top:6px; }
    .chart .g { flex:1; display:flex; align-items:flex-end; justify-content:center; gap:1px; height:100%; }
    .chart .b { width:45%; border-radius:2px 2px 0 0; min-height:0; }
    .chart-l { display:flex; gap:2px; font-size:8px; color:#8592a6; margin-top:3px; }
    .chart-l span { flex:1; text-align:center; overflow:hidden; white-space:nowrap; }
    .legend { font-size:10px; color:#4b5a6e; margin-bottom:4px; }
    .legend i { display:inline-block; width:8px; height:8px; border-radius:2px; margin:0 4px 0 8px; }
    .hb { margin-bottom:7px; }
    .hb .top { display:flex; justify-content:space-between; font-size:11px; margin-bottom:2px; }
    .hb .tr { height:6px; background:#eef2f7; border-radius:3px; }
    .hb .fl { height:6px; background:#2a78d6; border-radius:3px; }
    table { width:100%; border-collapse:collapse; font-size:11px; }
    th { background:#0b2447; color:#fff; text-align:left; padding:6px 8px; font-size:10px; }
    td { padding:5px 8px; border-bottom:1px solid #eef2f7; }
    td.r, th.r { text-align:right; }
    .empty { color:#8592a6; font-style:italic; padding:8px 0; }
    .foot { margin-top:18px; padding-top:8px; border-top:1px solid #dce3ec; color:#8592a6; font-size:10px; display:flex; justify-content:space-between; }
    .pb { page-break-before:always; }
    @page { size:A4; margin:0; }
    @media print {
        body { background:#fff; }
        .bar-top { display:none; }
        .page { margin:0; box-shadow:none; }
    }
</style>
</head>
<body>
<div class="bar-top">
    <button class="ghost" onclick="window.close()">{{ __('Tutup') }}</button>
    <button onclick="window.print()">{{ __('Cetak / Simpan PDF') }}</button>
</div>

<div class="page">
    <div class="head">
        <img src="{{ $logoSrc }}" alt="{{ __('Logo') }}">
        <div class="t">
            <div class="kick">{{ $r['meta']['title'] }}</div>
            <h1>{{ $r['meta']['company'] }}</h1>
            <div>{{ $r['meta']['period'] }}</div>
            @if($company?->address)<div class="addr" style="margin-left:auto">{{ $company->address }}</div>@endif
        </div>
    </div>

    <h2>{{ __('Ringkasan') }}</h2>
    <ul class="hl">
        @forelse($r['highlights'] as $h)<li>{{ $h }}</li>@empty<li>{{ __('Belum ada aktivitas pada periode ini.') }}</li>@endforelse
    </ul>

    <h2>{{ __('Angka utama') }} <span style="text-transform:none;letter-spacing:0;color:#8592a6;font-weight:400">{{ __('· dibanding :p1', ['p1' => $r['meta']['previous']]) }}</span></h2>
    <div class="kpis">
        <div class="kpi"><div class="l">{{ __('Total aset') }}</div><div class="v">{{ $num($k['total_assets']) }}</div><div class="s">{{ __(':p1 aktif', ['p1' => $num($k['active_assets'])]) }}</div></div>
        <div class="kpi"><div class="l">{{ __('Nilai perolehan') }}</div><div class="v">{{ $rp($k['total_value']) }}</div><div class="s">{{ __('nilai buku :p1', ['p1' => $rp($k['book_value'])]) }}</div></div>
        <div class="kpi"><div class="l">{{ __('Aset baru') }}</div><div class="v">{{ $num($k['new_assets']) }}</div><div class="s">{!! $chg($k['new_assets'], $p['new_assets']) !!}{{ $rp($k['new_assets_value']) }}</div></div>
        <div class="kpi"><div class="l">{{ __('Penugasan') }}</div><div class="v">{{ $num($k['assignments']) }}</div><div class="s">{!! $chg($k['assignments'], $p['assignments']) !!}serah terima</div></div>
        <div class="kpi t"><div class="l">{{ __('Maintenance') }}</div><div class="v">{{ $num($k['maintenance_count']) }}</div><div class="s">{!! $chg($k['maintenance_count'], $p['maintenance_count']) !!}{{ $num($k['maintenance_done']) }} selesai</div></div>
        <div class="kpi t"><div class="l">{{ __('Biaya maintenance') }}</div><div class="v">{{ $rp($k['maintenance_cost']) }}</div><div class="s">{!! $chg($k['maintenance_cost'], $p['maintenance_cost']) !!}realisasi</div></div>
        <div class="kpi t"><div class="l">{{ __('Request masuk') }}</div><div class="v">{{ $num($k['requests_created']) }}</div><div class="s">{!! $chg($k['requests_created'], $p['requests_created']) !!}{{ $num($k['requests_open']) }} terbuka</div></div>
        <div class="kpi t"><div class="l">{{ __('Rata-rata selesai') }}</div><div class="v">{{ $dur($k['avg_resolution_hours']) }}</div><div class="s">{{ __(':p1 request selesai', ['p1' => $num($k['requests_completed'])]) }}</div></div>
    </div>

    <div class="cols">
        <div>
            <h2>{{ __('Request perbaikan') }}</h2>
            <div class="legend"><i style="background:#2a78d6;margin-left:0"></i>{{ __('Masuk') }} <i style="background:#1baf7a"></i>{{ __('Selesai') }}</div>
            <div class="chart">
                @foreach($r['trend'] as $t)
                    <div class="g">
                        <div class="b" style="height:{{ $t['requests_created'] / $trendMax * 100 }}%;background:#2a78d6"></div>
                        <div class="b" style="height:{{ $t['requests_completed'] / $trendMax * 100 }}%;background:#1baf7a"></div>
                    </div>
                @endforeach
            </div>
            @php $every = max(1, (int) ceil(count($r['trend']) / 8)); @endphp
            <div class="chart-l">@foreach($r['trend'] as $i => $t)<span>{{ $i % $every === 0 ? $t['label'] : '' }}</span>@endforeach</div>
        </div>
        <div>
            <h2>{{ __('Biaya maintenance') }}</h2>
            <div class="legend">{{ __('maks :p1', ['p1' => $rp($costMax > 1 ? $costMax : 0)]) }}</div>
            <div class="chart">
                @foreach($r['trend'] as $t)
                    <div class="g"><div class="b" style="width:70%;height:{{ $t['maintenance_cost'] / $costMax * 100 }}%;background:#eb6834"></div></div>
                @endforeach
            </div>
            <div class="chart-l">@foreach($r['trend'] as $i => $t)<span>{{ $i % $every === 0 ? $t['label'] : '' }}</span>@endforeach</div>
        </div>
    </div>

    <div class="cols">
        @foreach([['by_category', 'Nilai aset per kategori', 'value', $rp], ['by_location', 'Aset per lokasi', 'total', fn ($v) => $num($v) . ' aset']] as [$key, $title, $field, $fmt])
            <div>
                <h2>{{ $title }}</h2>
                @php $max = max(1, collect($r[$key])->max($field)); @endphp
                @forelse($r[$key] as $row)
                    <div class="hb"><div class="top"><span>{{ $row['label'] }}</span><b>{{ $fmt($row[$field]) }}</b></div><div class="tr"><div class="fl" style="width:{{ $row[$field] / $max * 100 }}%"></div></div></div>
                @empty
                    <div class="empty">{{ __('Belum ada data.') }}</div>
                @endforelse
            </div>
        @endforeach
    </div>

    <div class="foot"><span>{{ __('Vasetra Asset Lifecycle Platform') }}</span><span>{{ __('Dicetak :p1 · :p2', ['p1' => now()->locale('id')->translatedFormat('d F Y H:i'), 'p2' => auth()->user()->name]) }}</span></div>
</div>

<div class="page">
    <div class="head">
        <img src="{{ $logoSrc }}" alt="{{ __('Logo') }}">
        <div class="t"><div class="kick">{{ $r['meta']['title'] }}</div><h1>{{ __('Maintenance & request') }}</h1><div>{{ $r['meta']['period'] }}</div></div>
    </div>

    <div class="cols">
        <div>
            <h2>{{ __('Maintenance per jenis') }}</h2>
            <table><tr><th>{{ __('Jenis') }}</th><th class="r">{{ __('Jumlah') }}</th><th class="r">{{ __('Biaya') }}</th></tr>
                @forelse($r['maintenance_by_type'] as $m)<tr><td>{{ $m['label'] }}</td><td class="r">{{ $num($m['total']) }}</td><td class="r">{{ $rp($m['cost']) }}</td></tr>@empty<tr><td colspan="3" class="empty">{{ __('Tidak ada.') }}</td></tr>@endforelse
            </table>
        </div>
        <div>
            <h2>{{ __('Status request') }}</h2>
            <table><tr><th>{{ __('Status') }}</th><th class="r">{{ __('Jumlah') }}</th></tr>
                @forelse($r['requests_by_status'] as $m)<tr><td>{{ $m['label'] }}</td><td class="r">{{ $num($m['total']) }}</td></tr>@empty<tr><td colspan="2" class="empty">{{ __('Tidak ada.') }}</td></tr>@endforelse
            </table>
        </div>
    </div>

    <h2>{{ __('Aset dengan biaya maintenance terbesar') }}</h2>
    <table><tr><th>{{ __('Kode') }}</th><th>{{ __('Nama aset') }}</th><th class="r">{{ __('Kali') }}</th><th class="r">{{ __('Biaya') }}</th></tr>
        @forelse($r['top_cost_assets'] as $a)<tr><td>{{ $a['code'] }}</td><td>{{ $a['name'] }}</td><td class="r">{{ $num($a['total']) }}</td><td class="r">{{ $rp($a['cost']) }}</td></tr>@empty<tr><td colspan="4" class="empty">{{ __('Tidak ada maintenance pada periode ini.') }}</td></tr>@endforelse
    </table>

    <h2>{{ __('Teknisi paling aktif') }}</h2>
    <table><tr><th>{{ __('Teknisi') }}</th><th class="r">{{ __('Selesai') }}</th><th class="r">{{ __('Rata-rata waktu') }}</th></tr>
        @forelse($r['top_technicians'] as $t)<tr><td>{{ $t['name'] }}</td><td class="r">{{ $num($t['total']) }}</td><td class="r">{{ $dur($t['avg_hours']) }}</td></tr>@empty<tr><td colspan="3" class="empty">{{ __('Belum ada request yang diselesaikan.') }}</td></tr>@endforelse
    </table>

    <div class="cols">
        @foreach([['upcoming_maintenance', 'Maintenance 30 hari ke depan'], ['warranty_expiring', 'Garansi habis 60 hari ke depan']] as [$key, $title])
            <div>
                <h2>{{ $title }} ({{ $num($r[$key]['total']) }})</h2>
                <table><tr><th>{{ __('Tanggal') }}</th><th>{{ __('Kode') }}</th><th>{{ __('Aset') }}</th></tr>
                    @forelse($r[$key]['items'] as $i)<tr><td>{{ \Illuminate\Support\Carbon::parse($i['date'])->format('d/m/Y') }}</td><td>{{ $i['code'] }}</td><td>{{ $i['name'] }}</td></tr>@empty<tr><td colspan="3" class="empty">{{ __('Tidak ada.') }}</td></tr>@endforelse
                </table>
            </div>
        @endforeach
    </div>

    <div class="foot"><span>{{ __('Vasetra Asset Lifecycle Platform') }}</span><span>{{ __('Halaman 2') }}</span></div>
</div>
</body>
</html>
