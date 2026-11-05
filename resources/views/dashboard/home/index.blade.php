@extends('dashboard.layouts.wrapper')

@section('title', 'Dashboard')

@section('content')
@php
    use Illuminate\Support\Str;

    $svc = app(\App\Services\Subscription\SubscriptionService::class);

    /* ---------- Format angka ---------- */
    $rupiahShort = function (float $value): string {
        return match (true) {
            $value >= 1e12 => 'Rp' . rtrim(rtrim(number_format($value / 1e12, 1, ',', '.'), '0'), ',') . ' T',
            $value >= 1e9 => 'Rp' . rtrim(rtrim(number_format($value / 1e9, 1, ',', '.'), '0'), ',') . ' M',
            $value >= 1e6 => 'Rp' . rtrim(rtrim(number_format($value / 1e6, 1, ',', '.'), '0'), ',') . ' jt',
            default => 'Rp' . number_format($value, 0, ',', '.'),
        };
    };
    $num = fn ($v) => number_format((int) $v, 0, ',', '.');
    $bytes = function ($b) {
        $b = (int) $b;
        return $b >= 1024 ** 3
            ? rtrim(rtrim(number_format($b / 1024 ** 3, 1, ',', '.'), '0'), ',') . ' GB'
            : number_format($b / 1024 ** 2, 0, ',', '.') . ' MB';
    };

    /* ---------- Delta aset baru ---------- */
    $assetDelta = $newThisMonth - $newLastMonth;

    /* ---------- Kondisi aset (urut terbaik -> terburuk) ---------- */
    $conditionMeta = [
        'new'     => ['label' => 'Baru',            'icon' => 'bi-stars',          'tone' => 'new'],
        'good'    => ['label' => 'Baik',            'icon' => 'bi-check-circle',   'tone' => 'good'],
        'used'    => ['label' => 'Bekas',           'icon' => 'bi-dash-circle',    'tone' => 'warning'],
        'poor'    => ['label' => 'Perlu Perbaikan', 'icon' => 'bi-tools',          'tone' => 'serious'],
        'damaged' => ['label' => 'Rusak',           'icon' => 'bi-x-octagon',      'tone' => 'critical'],
    ];
    $conditionTotal = max(1, (int) $assetConditions->sum());
    $conditions = collect($conditionMeta)
        ->map(fn ($meta, $key) => $meta + ['key' => $key, 'total' => (int) ($assetConditions[$key] ?? 0)])
        ->filter(fn ($c) => $c['total'] > 0)
        ->values();
    $otherConditions = $assetConditions->except(array_keys($conditionMeta))->sum();
    if ($otherConditions > 0) {
        $conditions->push(['key' => 'other', 'label' => 'Lainnya', 'icon' => 'bi-question-circle', 'tone' => 'muted', 'total' => (int) $otherConditions]);
    }

    $categoryMax = max(1, (int) ($assetCategories->max('total') ?? 1));

    /* ---------- Tren 12 bulan ---------- */
    $trendNewTotal = $trend->sum('new_assets');
    $trendMaintTotal = $trend->sum('maintenance');

    /* ---------- Aktivitas (audit) ---------- */
    $eventIcon = [
        'created' => ['bi-plus-lg', 'good'],
        'updated' => ['bi-pencil', 'new'],
        'deleted' => ['bi-trash3', 'critical'],
        'login' => ['bi-box-arrow-in-right', 'muted'],
        'logout' => ['bi-box-arrow-right', 'muted'],
        'login_failed' => ['bi-shield-exclamation', 'warning'],
        'login_locked' => ['bi-shield-lock', 'critical'],
        'exported' => ['bi-download', 'muted'],
        'printed' => ['bi-printer', 'muted'],
        'subscription_activated' => ['bi-patch-check', 'good'],
        'subscription_upgraded' => ['bi-rocket-takeoff', 'new'],
        'addon_purchased' => ['bi-plus-circle', 'good'],
    ];

    $can = fn ($perm) => auth()->user()->hasPermission($perm);
@endphp

<div class="vx-home">

    {{-- =========================================================
         KPI
    ========================================================== --}}
    <div class="row g-3 vx-kpi-row">

        {{-- Total aset --}}
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card vx-kpi h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="vx-kpi-label">Total Aset</div>
                            <div class="vx-kpi-value">{{ $num($totalAssets) }}</div>
                        </div>
                        <span class="vx-kpi-icon tone-blue"><i class="bi bi-box-seam"></i></span>
                    </div>
                    <div class="vx-kpi-meta">
                        <span class="vx-delta {{ $assetDelta > 0 ? 'is-up' : ($assetDelta < 0 ? 'is-down' : '') }}">
                            <i class="bi {{ $assetDelta > 0 ? 'bi-arrow-up-right' : ($assetDelta < 0 ? 'bi-arrow-down-right' : 'bi-dash') }}"></i>
                            {{ $newThisMonth > 0 ? '+' . $num($newThisMonth) : '0' }} bulan ini
                        </span>
                        <span class="text-muted">{{ $num($activeAssets) }} aktif</span>
                    </div>
                    <div class="vx-spark" id="sparkAssets" role="img"
                         aria-label="Total aset 12 bulan terakhir"></div>
                </div>
            </div>
        </div>

        {{-- Nilai aset --}}
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card vx-kpi h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="vx-kpi-label">Nilai Perolehan Aset</div>
                            <div class="vx-kpi-value" title="Rp{{ number_format($assetValue, 0, ',', '.') }}">
                                {{ $rupiahShort($assetValue) }}
                            </div>
                        </div>
                        <span class="vx-kpi-icon tone-teal"><i class="bi bi-cash-stack"></i></span>
                    </div>
                    <div class="vx-kpi-meta">
                        <span class="text-muted">
                            Biaya maintenance {{ now()->year }}:
                            <strong class="text-body">{{ $rupiahShort($maintenanceCostYear) }}</strong>
                        </span>
                    </div>
                    <div class="vx-kpi-foot">
                        <span><i class="bi bi-person-check me-1"></i>{{ $num($totalAssets - $unassignedAssets) }} ada PIC</span>
                        <span class="{{ $unassignedAssets > 0 ? 'text-warning-emphasis' : '' }}">
                            <i class="bi bi-person-dash me-1"></i>{{ $num($unassignedAssets) }} tanpa PIC
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Maintenance terbuka --}}
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card vx-kpi h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="vx-kpi-label">Jadwal Maintenance</div>
                            <div class="vx-kpi-value">{{ $num($maintenanceOpen) }}</div>
                        </div>
                        <span class="vx-kpi-icon tone-orange"><i class="bi bi-calendar2-check"></i></span>
                    </div>
                    <div class="vx-status-row">
                        <span class="vx-status tone-critical {{ $maintenanceOverdue ? '' : 'is-zero' }}">
                            <i class="bi bi-exclamation-octagon"></i> {{ $num($maintenanceOverdue) }} terlambat
                        </span>
                        <span class="vx-status tone-warning {{ $maintenanceToday ? '' : 'is-zero' }}">
                            <i class="bi bi-clock"></i> {{ $num($maintenanceToday) }} hari ini
                        </span>
                        <span class="vx-status tone-new {{ $maintenanceThisWeek ? '' : 'is-zero' }}">
                            <i class="bi bi-calendar-week"></i> {{ $num($maintenanceThisWeek) }} minggu ini
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Request maintenance --}}
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card vx-kpi h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="vx-kpi-label">Request Perbaikan</div>
                            <div class="vx-kpi-value">{{ $num($requestPending + $requestInProgress) }}</div>
                        </div>
                        <span class="vx-kpi-icon tone-violet"><i class="bi bi-wrench-adjustable-circle"></i></span>
                    </div>
                    <div class="vx-status-row">
                        <span class="vx-status tone-warning {{ $requestPending ? '' : 'is-zero' }}">
                            <i class="bi bi-hourglass-split"></i> {{ $num($requestPending) }} menunggu
                        </span>
                        <span class="vx-status tone-new {{ $requestInProgress ? '' : 'is-zero' }}">
                            <i class="bi bi-gear"></i> {{ $num($requestInProgress) }} dikerjakan
                        </span>
                    </div>
                    @if($can('maintenance.view'))
                        <a href="{{ route('maintenance.requests.index') }}" class="vx-link mt-2">
                            Kelola request <i class="bi bi-arrow-right"></i>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- =========================================================
         AKSI CEPAT
    ========================================================== --}}
    <div class="vx-actions">
        @if($can('asset.create'))
            <a href="{{ route('assets.create') }}" class="vx-action">
                <span class="vx-action-icon tone-blue"><i class="bi bi-plus-lg"></i></span> Tambah Aset
            </a>
        @endif
        @if($can('asset.import'))
            <a href="{{ route('assets.import') }}" class="vx-action">
                <span class="vx-action-icon tone-teal"><i class="bi bi-file-earmark-arrow-up"></i></span> Import Excel
            </a>
        @endif
        @if($can('asset.view'))
            <a href="{{ route('assets.index') }}" class="vx-action">
                <span class="vx-action-icon tone-violet"><i class="bi bi-qr-code"></i></span> Cetak QR Aset
            </a>
        @endif
        @if($can('maintenance.create'))
            <a href="{{ route('my-assets.index') }}" class="vx-action">
                <span class="vx-action-icon tone-orange"><i class="bi bi-tools"></i></span> Ajukan Perbaikan
            </a>
        @endif
        <a href="{{ route('calendar.index') }}" class="vx-action">
            <span class="vx-action-icon tone-blue"><i class="bi bi-calendar3"></i></span> Kalender
        </a>
        @if($isAdministrator)
            <a href="{{ route('subscription.usage') }}" class="vx-action">
                <span class="vx-action-icon tone-teal"><i class="bi bi-speedometer2"></i></span> Kuota & Paket
            </a>
        @endif
    </div>

    {{-- =========================================================
         TREN + KONDISI
    ========================================================== --}}
    <div class="row g-3 mt-1">

        <div class="col-12 col-xl-8">
            <div class="card h-100">
                <div class="card-body">
                    <div class="vx-card-head">
                        <div>
                            <h5 class="vx-card-title">Aktivitas 12 Bulan Terakhir</h5>
                            <div class="vx-card-sub">Aset baru didaftarkan &amp; maintenance dilakukan per bulan</div>
                        </div>
                        <div class="btn-group btn-group-sm vx-view-toggle" role="group" aria-label="Tampilan">
                            <button type="button" class="btn btn-light active" data-view="chart" data-target="trend">
                                <i class="bi bi-bar-chart"></i> Grafik
                            </button>
                            <button type="button" class="btn btn-light" data-view="table" data-target="trend">
                                <i class="bi bi-table"></i> Tabel
                            </button>
                        </div>
                    </div>

                    <div class="vx-legend">
                        <span><i class="vx-swatch series-1"></i> Aset baru <strong>{{ $num($trendNewTotal) }}</strong></span>
                        <span><i class="vx-swatch series-2"></i> Maintenance <strong>{{ $num($trendMaintTotal) }}</strong></span>
                    </div>

                    <div data-panel="trend" data-mode="chart">
                        <div id="trendChart" class="vx-chart" role="img"
                             aria-label="Grafik kolom aset baru dan maintenance per bulan, 12 bulan terakhir"></div>
                    </div>

                    <div data-panel="trend" data-mode="table" hidden>
                        <div class="table-responsive vx-table-wrap">
                            <table class="table table-sm align-middle mb-0 vx-num-table">
                                <thead>
                                    <tr><th>Bulan</th><th class="text-end">Aset baru</th><th class="text-end">Total aset</th><th class="text-end">Maintenance</th></tr>
                                </thead>
                                <tbody>
                                    @foreach($trend->reverse() as $row)
                                        <tr>
                                            <td>{{ $row['label'] }}</td>
                                            <td class="text-end">{{ $num($row['new_assets']) }}</td>
                                            <td class="text-end">{{ $num($row['total_assets']) }}</td>
                                            <td class="text-end">{{ $num($row['maintenance']) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="vx-card-head">
                        <div>
                            <h5 class="vx-card-title">Kondisi Aset</h5>
                            <div class="vx-card-sub">{{ $num($assetConditions->sum()) }} aset tercatat</div>
                        </div>
                    </div>

                    @if($conditions->isEmpty())
                        <div class="vx-empty">
                            <i class="bi bi-box"></i>
                            <div>Belum ada aset.</div>
                            @if($can('asset.create'))
                                <a href="{{ route('assets.create') }}" class="btn btn-sm btn-primary mt-2">Tambah aset pertama</a>
                            @endif
                        </div>
                    @else
                        {{-- Tall segmented bar: satu keseluruhan dibagi per kondisi --}}
                        <div class="vx-segbar" role="img"
                             aria-label="Komposisi kondisi aset: {{ $conditions->map(fn ($c) => $c['label'] . ' ' . $c['total'])->implode(', ') }}">
                            @foreach($conditions as $c)
                                @php $pct = round($c['total'] / $conditionTotal * 100); @endphp
                                <div class="vx-seg tone-{{ $c['tone'] }}" style="flex: {{ $c['total'] }} 1 0"
                                     title="{{ $c['label'] }}: {{ $c['total'] }} ({{ $pct }}%)">
                                    @if($pct >= 18)
                                        <span>{{ $pct }}%</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        <ul class="vx-cond-list">
                            @foreach($conditions as $c)
                                <li>
                                    <span class="vx-cond-name">
                                        <i class="vx-swatch tone-{{ $c['tone'] }}"></i>
                                        <i class="bi {{ $c['icon'] }} text-muted"></i>
                                        {{ $c['label'] }}
                                    </span>
                                    <span class="vx-cond-val">
                                        <strong>{{ $num($c['total']) }}</strong>
                                        <small class="text-muted">{{ round($c['total'] / $conditionTotal * 100) }}%</small>
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- =========================================================
         KATEGORI + JADWAL + KUOTA
    ========================================================== --}}
    <div class="row g-3 mt-1">

        {{-- Aset per kategori (ranked bars) --}}
        <div class="col-12 col-lg-6 col-xl-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="vx-card-head">
                        <div>
                            <h5 class="vx-card-title">Aset per Kategori</h5>
                            <div class="vx-card-sub">6 kategori terbanyak</div>
                        </div>
                        @if($can('asset.view'))
                            <a href="{{ route('assets.index') }}" class="vx-link">Lihat semua <i class="bi bi-arrow-right"></i></a>
                        @endif
                    </div>

                    @forelse($assetCategories as $cat)
                        <div class="vx-rank" title="{{ $cat['name'] }}: {{ $cat['total'] }} aset">
                            <div class="vx-rank-top">
                                <span class="text-truncate">{{ $cat['name'] }}</span>
                                <strong>{{ $num($cat['total']) }}</strong>
                            </div>
                            <div class="vx-rank-track">
                                <div class="vx-rank-fill" style="width: {{ max(2, round($cat['total'] / $categoryMax * 100)) }}%"></div>
                            </div>
                        </div>
                    @empty
                        <div class="vx-empty"><i class="bi bi-tags"></i><div>Belum ada data kategori.</div></div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Jadwal maintenance --}}
        <div class="col-12 col-lg-6 col-xl-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="vx-card-head">
                        <div>
                            <h5 class="vx-card-title">Jadwal Maintenance</h5>
                            <div class="vx-card-sub">Terdekat &amp; terlambat</div>
                        </div>
                        @if($can('maintenance.view'))
                            <a href="{{ route('maintenance.index') }}" class="vx-link">Semua <i class="bi bi-arrow-right"></i></a>
                        @endif
                    </div>

                    <ul class="vx-timeline">
                        @forelse($upcomingMaintenance as $m)
                            @php
                                $date = \Carbon\Carbon::parse($m->maintenance_date);
                                $late = $date->lt(today());
                                $isToday = $date->isToday();
                            @endphp
                            <li class="{{ $late ? 'is-late' : ($isToday ? 'is-today' : '') }}">
                                <div class="vx-date">
                                    <span>{{ $date->translatedFormat('d') }}</span>
                                    <small>{{ $date->translatedFormat('M') }}</small>
                                </div>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-semibold text-truncate">{{ $m->asset?->asset_name ?? '-' }}</div>
                                    <small class="text-muted text-truncate d-block">
                                        {{ ucfirst((string) $m->maintenance_type) }}
                                        · {{ $m->asset?->category?->category_name ?? '-' }}
                                    </small>
                                </div>
                                @if($late)
                                    <span class="vx-status tone-critical"><i class="bi bi-exclamation-octagon"></i> Terlambat</span>
                                @elseif($isToday)
                                    <span class="vx-status tone-warning"><i class="bi bi-clock"></i> Hari ini</span>
                                @else
                                    <span class="vx-status tone-muted">{{ $date->diffForHumans(today(), ['parts' => 1, 'syntax' => \Carbon\CarbonInterface::DIFF_RELATIVE_TO_NOW]) }}</span>
                                @endif
                            </li>
                        @empty
                            <li class="vx-empty border-0"><i class="bi bi-calendar2-check"></i><div>Tidak ada jadwal maintenance terbuka.</div></li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        {{-- Kuota (admin) / Aset saya --}}
        <div class="col-12 col-xl-4">
            <div class="card h-100 {{ $isAdministrator ? '' : 'vx-mine' }}">
                <div class="card-body">
                    @if($isAdministrator && $quota)
                        <div class="vx-card-head">
                            <div>
                                <h5 class="vx-card-title">Kapasitas Paket</h5>
                                <div class="vx-card-sub">
                                    {{ $quota['plan']?->plan_name ?? 'Tidak ada paket aktif' }}
                                    @if($currentSubscription?->end_date)
                                        · s/d {{ $currentSubscription->end_date->translatedFormat('d M Y') }}
                                    @endif
                                </div>
                            </div>
                            <a href="{{ route('subscription.usage') }}" class="vx-link">Detail <i class="bi bi-arrow-right"></i></a>
                        </div>

                        @foreach([
                            ['Aset', 'bi-box-seam', $quotaUsage['assets'], $quota['assets'], fn ($v) => $num($v)],
                            ['User', 'bi-people', $quotaUsage['users'], $quota['users'], fn ($v) => $num($v)],
                            ['Storage', 'bi-hdd-stack', $quotaUsage['storage'], $quota['storage'], $bytes],
                        ] as [$label, $icon, $used, $limit, $fmt])
                            @php
                                $pct = $limit ? min(100, round($used / max(1, $limit) * 100)) : 0;
                                $over = $limit !== null && $used > $limit;
                                $tone = $over || $pct >= 95 ? 'critical' : ($pct >= 80 ? 'warning' : 'ok');
                            @endphp
                            <div class="vx-meter">
                                <div class="vx-meter-top">
                                    <span><i class="bi {{ $icon }} me-1 text-muted"></i>{{ $label }}</span>
                                    <span>
                                        <strong>{{ $fmt($used) }}</strong>
                                        <small class="text-muted">/ {{ $limit === null ? 'Unlimited' : $fmt($limit) }}</small>
                                    </span>
                                </div>
                                <div class="vx-meter-track" role="meter" aria-valuemin="0" aria-valuemax="100"
                                     aria-valuenow="{{ $pct }}" aria-label="Pemakaian {{ $label }}">
                                    <div class="vx-meter-fill tone-{{ $tone }}" style="width: {{ $limit === null ? 0 : max(2, $pct) }}%"></div>
                                </div>
                                @if($over)
                                    <small class="vx-meter-note text-danger"><i class="bi bi-exclamation-octagon me-1"></i>Melebihi kuota — penambahan diblokir</small>
                                @elseif($tone === 'warning' || $tone === 'critical')
                                    <small class="vx-meter-note text-warning-emphasis"><i class="bi bi-exclamation-triangle me-1"></i>{{ $pct }}% terpakai</small>
                                @endif
                            </div>
                        @endforeach

                        <a href="{{ route('subscription.index') }}#addon-card" class="btn btn-sm btn-outline-primary w-100 mt-2">
                            <i class="bi bi-plus-circle me-1"></i> Tambah kapasitas
                        </a>
                    @else
                        <div class="vx-card-head">
                            <div>
                                <h5 class="vx-card-title">Aset Saya</h5>
                                <div class="vx-card-sub">Aset yang menjadi tanggung jawab Anda</div>
                            </div>
                        </div>
                        <div class="vx-mine-hero">
                            <div class="vx-kpi-value">{{ $num($myAssets) }}</div>
                            <div class="text-muted">aset</div>
                        </div>
                        @if($can('maintenance.create'))
                            <a href="{{ route('my-assets.index') }}" class="btn btn-primary w-100">
                                <i class="bi bi-tools me-1"></i> Lihat &amp; ajukan perbaikan
                            </a>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- =========================================================
         AKTIVITAS + ASET TERBARU
    ========================================================== --}}
    <div class="row g-3 mt-1 mb-4">

        @if($isAdministrator)
            <div class="col-12 col-xl-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="vx-card-head">
                            <div>
                                <h5 class="vx-card-title">Aktivitas Terbaru</h5>
                                <div class="vx-card-sub">Dari audit trail perusahaan</div>
                            </div>
                            <a href="{{ route('audit-trail.index') }}" class="vx-link">Audit trail <i class="bi bi-arrow-right"></i></a>
                        </div>

                        <ul class="vx-feed">
                            @forelse($recentActivity as $log)
                                @php [$ic, $tone] = $eventIcon[$log->event] ?? ['bi-activity', 'muted']; @endphp
                                <li>
                                    <span class="vx-feed-icon tone-{{ $tone }}"><i class="bi {{ $ic }}"></i></span>
                                    <div class="min-w-0 flex-grow-1">
                                        <div class="text-truncate">{{ $log->description }}</div>
                                        <small class="text-muted">
                                            {{ $log->user_name ?? 'Sistem' }} · {{ $log->created_at?->diffForHumans() }}
                                        </small>
                                    </div>
                                </li>
                            @empty
                                <li class="vx-empty border-0"><i class="bi bi-activity"></i><div>Belum ada aktivitas.</div></li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <div class="col-12 {{ $isAdministrator ? 'col-xl-6' : '' }}">
            <div class="card h-100">
                <div class="card-body">
                    <div class="vx-card-head">
                        <div>
                            <h5 class="vx-card-title">Aset Terbaru</h5>
                            <div class="vx-card-sub">5 aset terakhir didaftarkan</div>
                        </div>
                        @if($can('asset.view'))
                            <a href="{{ route('assets.index') }}" class="vx-link">Semua aset <i class="bi bi-arrow-right"></i></a>
                        @endif
                    </div>

                    <ul class="vx-feed">
                        @forelse($recentAssets as $asset)
                            <li>
                                <span class="vx-avatar">{{ Str::upper(Str::substr($asset->asset_name, 0, 2)) }}</span>
                                <div class="min-w-0 flex-grow-1">
                                    @if($can('asset.view'))
                                        <a href="{{ route('assets.show', encryptId($asset->id)) }}" class="fw-semibold text-body text-truncate d-block">
                                            {{ $asset->asset_name }}
                                        </a>
                                    @else
                                        <div class="fw-semibold text-truncate">{{ $asset->asset_name }}</div>
                                    @endif
                                    <small class="text-muted">
                                        {{ $asset->asset_code }} · {{ $asset->category?->category_name ?? '-' }}
                                        · PIC {{ $asset->responsibleUser?->name ?? '-' }}
                                    </small>
                                </div>
                                <small class="text-muted text-nowrap">{{ $asset->created_at?->diffForHumans() }}</small>
                            </li>
                        @empty
                            <li class="vx-empty border-0"><i class="bi bi-box"></i><div>Belum ada aset.</div></li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* ==========================================================
   DASHBOARD - token warna data (divalidasi: CVD & kontras,
   terang vs #ffffff, gelap vs #222738)
   ========================================================== */
.vx-home {
    --series-1: #2a78d6;
    --series-2: #eb6834;
    --good: #0ca30c;
    --warning: #fab219;
    --serious: #ec835a;
    --critical: #d03b3b;
    --muted-mark: #b8c0cc;
    --grid: #e6eaf0;
    --axis: #8592a6;
    --track: #edf1f6;
    --ink: #0f1b2d;
    --ink-2: #4b5a6e;

    position: relative;
    margin-top: -0.75rem;
    z-index: 2;
}
body.dark .vx-home {
    --series-1: #3987e5;
    --series-2: #d95926;
    --muted-mark: #5b647a;
    --grid: #30384f;
    --axis: #8a92a6;
    --track: #2b3247;
    --ink: #eef1f6;
    --ink-2: #b4bccb;
}

/* KPI */
.vx-kpi { transition: transform .2s ease, box-shadow .2s ease; overflow: hidden; }
.vx-kpi:hover { transform: translateY(-2px); box-shadow: var(--vx-shadow-hover) !important; }
.vx-kpi .card-body { padding: 1.15rem 1.25rem 1rem; display: flex; flex-direction: column; }
.vx-kpi-label { font-size: .8rem; font-weight: 600; color: var(--ink-2); text-transform: uppercase; letter-spacing: .04em; }
.vx-kpi-value { font-size: 2rem; font-weight: 800; color: var(--ink); line-height: 1.15; letter-spacing: -.02em; margin-top: .2rem; }
.vx-kpi-icon {
    width: 44px; height: 44px; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center;
    font-size: 1.25rem; flex-shrink: 0;
}
.vx-kpi-meta { display: flex; flex-wrap: wrap; gap: .5rem .75rem; align-items: center; margin-top: .6rem; font-size: .82rem; }
.vx-kpi-foot { display: flex; flex-wrap: wrap; justify-content: space-between; gap: .4rem; margin-top: auto; padding-top: .8rem; font-size: .8rem; color: var(--ink-2); border-top: 1px solid var(--vx-hairline); }
.vx-spark { margin: .35rem -.5rem -.6rem; min-height: 56px; }

.tone-blue   { background: rgba(31, 95, 191, .1);  color: #1f5fbf; }
.tone-teal   { background: rgba(20, 184, 166, .12); color: #0d8f81; }
.tone-orange { background: rgba(235, 104, 52, .12); color: #c4521f; }
.tone-violet { background: rgba(98, 80, 214, .12);  color: #5241c4; }
body.dark .tone-blue   { color: #8fb8f2; }
body.dark .tone-teal   { color: #4fd8c7; }
body.dark .tone-orange { color: #f29a72; }
body.dark .tone-violet { color: #a99df0; }

.vx-delta { display: inline-flex; align-items: center; gap: .25rem; font-weight: 600; color: var(--ink-2); }
.vx-delta.is-up { color: #067a06; }
.vx-delta.is-down { color: #b42f2f; }
body.dark .vx-delta.is-up { color: #3fc93f; }

/* Status chip: warna + ikon + teks (tidak pernah warna saja) */
.vx-status-row { display: flex; flex-wrap: wrap; gap: .4rem; margin-top: .8rem; }
.vx-status {
    display: inline-flex; align-items: center; gap: .3rem; padding: .22rem .6rem; border-radius: 999px;
    font-size: .76rem; font-weight: 600; white-space: nowrap; border: 1px solid transparent;
}
.vx-status.tone-critical { background: rgba(208, 59, 59, .1); color: #a32a2a; border-color: rgba(208, 59, 59, .2); }
.vx-status.tone-warning  { background: rgba(250, 178, 25, .14); color: #8a5a00; border-color: rgba(250, 178, 25, .3); }
.vx-status.tone-new      { background: rgba(42, 120, 214, .1); color: #1d5aa6; border-color: rgba(42, 120, 214, .2); }
.vx-status.tone-muted    { background: var(--track); color: var(--ink-2); }
.vx-status.is-zero { opacity: .55; }
body.dark .vx-status.tone-critical { color: #f08a8a; }
body.dark .vx-status.tone-warning  { color: #fcc95a; }
body.dark .vx-status.tone-new      { color: #8fb8f2; }

.vx-link { font-size: .82rem; font-weight: 600; color: var(--vx-blue); text-decoration: none; white-space: nowrap; display: inline-flex; gap: .3rem; align-items: center; }
.vx-link:hover { text-decoration: underline; }
body.dark .vx-link { color: #8fb8f2; }

/* Aksi cepat */
.vx-actions { display: flex; flex-wrap: wrap; gap: .6rem; margin-top: 1rem; }
.vx-action {
    display: inline-flex; align-items: center; gap: .55rem; padding: .45rem .9rem .45rem .45rem; border-radius: 999px;
    background: var(--bs-body-bg, #fff); border: 1px solid var(--vx-hairline); color: var(--ink); font-weight: 600; font-size: .86rem;
    text-decoration: none; box-shadow: var(--vx-shadow); transition: transform .15s ease, border-color .15s ease;
}
.vx-action:hover { transform: translateY(-1px); border-color: rgba(31, 95, 191, .35); color: var(--ink); }
.vx-action-icon { width: 30px; height: 30px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; }
body.dark .vx-action { background: #222738; border-color: rgba(255,255,255,.06); }

/* Card */
.vx-card-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; margin-bottom: 1rem; }
.vx-card-title { font-size: 1.02rem; font-weight: 700; margin: 0; color: var(--ink); }
.vx-card-sub { font-size: .8rem; color: var(--ink-2); margin-top: .15rem; }
.vx-view-toggle .btn { font-size: .78rem; font-weight: 600; }
.vx-view-toggle .btn.active { background: var(--vx-blue); color: #fff; border-color: var(--vx-blue); }

/* Legend */
.vx-legend { display: flex; flex-wrap: wrap; gap: 1.1rem; font-size: .82rem; color: var(--ink-2); margin-bottom: .25rem; }
.vx-legend strong { color: var(--ink); margin-left: .15rem; }
.vx-swatch { display: inline-block; width: 10px; height: 10px; border-radius: 3px; vertical-align: -1px; margin-right: .3rem; }
.vx-swatch.series-1 { background: var(--series-1); }
.vx-swatch.series-2 { background: var(--series-2); }
.vx-chart { min-height: 300px; }
.vx-table-wrap { max-height: 320px; overflow: auto; }
.vx-num-table td, .vx-num-table th { font-variant-numeric: tabular-nums; }

/* Tall segmented bar (kondisi) */
.vx-segbar { display: flex; gap: 2px; height: 44px; border-radius: 10px; overflow: hidden; margin-bottom: 1rem; }
.vx-seg { display: flex; align-items: center; justify-content: center; min-width: 6px; font-size: .78rem; font-weight: 700; color: #fff; }
.vx-seg.tone-warning { color: #0b0b0b; }
.tone-new.vx-seg, .vx-swatch.tone-new { background: var(--series-1); }
.tone-good.vx-seg, .vx-swatch.tone-good { background: var(--good); }
.tone-warning.vx-seg, .vx-swatch.tone-warning { background: var(--warning); }
.tone-serious.vx-seg, .vx-swatch.tone-serious { background: var(--serious); }
.tone-critical.vx-seg, .vx-swatch.tone-critical { background: var(--critical); }
.tone-muted.vx-seg, .vx-swatch.tone-muted { background: var(--muted-mark); }
.vx-cond-list { list-style: none; padding: 0; margin: 0; }
.vx-cond-list li { display: flex; justify-content: space-between; align-items: center; padding: .55rem 0; border-bottom: 1px solid var(--vx-hairline); font-size: .88rem; }
.vx-cond-list li:last-child { border-bottom: 0; }
.vx-cond-name { display: inline-flex; align-items: center; gap: .35rem; color: var(--ink); }
.vx-cond-val { display: inline-flex; gap: .5rem; align-items: baseline; font-variant-numeric: tabular-nums; }

/* Ranked bars */
.vx-rank { margin-bottom: .85rem; }
.vx-rank-top { display: flex; justify-content: space-between; gap: .75rem; font-size: .86rem; color: var(--ink); margin-bottom: .3rem; }
.vx-rank-track { height: 8px; background: var(--track); border-radius: 4px; overflow: hidden; }
.vx-rank-fill { height: 100%; background: var(--series-1); border-radius: 0 4px 4px 0; transition: width .6s ease; }
.vx-rank:hover .vx-rank-fill { filter: brightness(1.1); }

/* Timeline */
.vx-timeline, .vx-feed { list-style: none; padding: 0; margin: 0; }
.vx-timeline li, .vx-feed li { display: flex; align-items: center; gap: .8rem; padding: .6rem 0; border-bottom: 1px solid var(--vx-hairline); }
.vx-timeline li:last-child, .vx-feed li:last-child { border-bottom: 0; }
.vx-date {
    width: 46px; flex-shrink: 0; text-align: center; border-radius: 10px; padding: .3rem 0;
    background: var(--track); line-height: 1.1;
}
.vx-date span { display: block; font-weight: 800; font-size: 1.05rem; color: var(--ink); }
.vx-date small { font-size: .68rem; text-transform: uppercase; color: var(--ink-2); font-weight: 600; }
.vx-timeline li.is-late .vx-date { background: rgba(208, 59, 59, .12); }
.vx-timeline li.is-today .vx-date { background: rgba(250, 178, 25, .18); }
.min-w-0 { min-width: 0; }

/* Meter kuota */
.vx-meter { margin-bottom: 1rem; }
.vx-meter-top { display: flex; justify-content: space-between; font-size: .86rem; color: var(--ink); margin-bottom: .35rem; }
.vx-meter-track { height: 10px; background: var(--track); border-radius: 5px; overflow: hidden; }
.vx-meter-fill { height: 100%; border-radius: 0 5px 5px 0; transition: width .6s ease; }
.vx-meter-fill.tone-ok { background: var(--series-1); }
.vx-meter-fill.tone-warning { background: var(--warning); }
.vx-meter-fill.tone-critical { background: var(--critical); }
.vx-meter-note { display: block; margin-top: .3rem; font-size: .76rem; }

/* Feed */
.vx-feed-icon, .vx-avatar {
    width: 36px; height: 36px; border-radius: 10px; flex-shrink: 0; display: inline-flex; align-items: center; justify-content: center;
}
.vx-feed-icon.tone-good { background: rgba(12, 163, 12, .1); color: #067a06; }
.vx-feed-icon.tone-new { background: rgba(42, 120, 214, .1); color: #1d5aa6; }
.vx-feed-icon.tone-critical { background: rgba(208, 59, 59, .1); color: #a32a2a; }
.vx-feed-icon.tone-warning { background: rgba(250, 178, 25, .16); color: #8a5a00; }
.vx-feed-icon.tone-muted { background: var(--track); color: var(--ink-2); }
.vx-avatar { background: linear-gradient(135deg, #1f5fbf, #14b8a6); color: #fff; font-weight: 700; font-size: .8rem; }
.vx-feed li { font-size: .88rem; color: var(--ink); }

.vx-mine-hero { text-align: center; padding: 1.5rem 0 1.25rem; }
.vx-mine-hero .vx-kpi-value { font-size: 3.25rem; }

.vx-empty { text-align: center; color: var(--ink-2); padding: 1.75rem .5rem; font-size: .88rem; display: block !important; }
.vx-empty > i { font-size: 1.8rem; display: block; margin-bottom: .4rem; opacity: .6; }

/* Animasi masuk */
@media (prefers-reduced-motion: no-preference) {
    .vx-home .card { animation: vxRise .5s ease both; }
    .vx-kpi-row > div:nth-child(2) .card { animation-delay: .05s; }
    .vx-kpi-row > div:nth-child(3) .card { animation-delay: .1s; }
    .vx-kpi-row > div:nth-child(4) .card { animation-delay: .15s; }
    @keyframes vxRise { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: none; } }
}

/* Template memakai margin positif untuk .content-inner di layar kecil;
   kompensasi agar kartu tetap menumpuk di tepi bawah banner. */
@media (max-width: 991.98px) { .vx-home { margin-top: -4.5rem; } }
@media (max-width: 767.98px) { .vx-home { margin-top: -7.5rem; } }

@media (max-width: 575.98px) {
    .vx-kpi-value { font-size: 1.7rem; }
}
</style>
@endsection

@section('js')
<script src="{{ asset('assets/vendor/apexcharts/apexcharts.min.js') }}"></script>
<script>
(function () {
    const trend = @json($trend);

    const css = (name) => getComputedStyle(document.querySelector('.vx-home')).getPropertyValue(name).trim();
    const nf = new Intl.NumberFormat('id-ID');
    const charts = [];

    function baseOptions() {
        return {
            chart: {
                fontFamily: 'Plus Jakarta Sans, system-ui, sans-serif',
                toolbar: { show: false },
                zoom: { enabled: false },
                animations: { enabled: !window.matchMedia('(prefers-reduced-motion: reduce)').matches, speed: 500 },
                background: 'transparent',
            },
            theme: { mode: document.body.classList.contains('dark') ? 'dark' : 'light' },
            grid: { borderColor: css('--grid'), strokeDashArray: 0, padding: { left: 8, right: 8 } },
            dataLabels: { enabled: false },
            legend: { show: false },
        };
    }

    function render() {
        charts.splice(0).forEach(c => c.destroy());

        /* --- Sparkline total aset --- */
        const spark = document.getElementById('sparkAssets');
        if (spark && typeof ApexCharts !== 'undefined') {
            const c = new ApexCharts(spark, Object.assign(baseOptions(), {
                chart: Object.assign(baseOptions().chart, { type: 'area', height: 56, sparkline: { enabled: true } }),
                series: [{ name: 'Total aset', data: trend.map(t => t.total_assets) }],
                labels: trend.map(t => t.label),
                colors: [css('--series-1')],
                stroke: { width: 2, curve: 'smooth' },
                fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: .28, opacityTo: 0, stops: [0, 100] } },
                tooltip: {
                    x: { show: true },
                    y: { formatter: v => nf.format(v) + ' aset', title: { formatter: () => '' } },
                    marker: { show: false },
                },
            }));
            c.render();
            charts.push(c);
        }

        /* --- Kolom aktivitas 12 bulan --- */
        const trendEl = document.getElementById('trendChart');
        if (trendEl && typeof ApexCharts !== 'undefined') {
            const c = new ApexCharts(trendEl, Object.assign(baseOptions(), {
                chart: Object.assign(baseOptions().chart, { type: 'bar', height: 300 }),
                series: [
                    { name: 'Aset baru', data: trend.map(t => t.new_assets) },
                    { name: 'Maintenance', data: trend.map(t => t.maintenance) },
                ],
                colors: [css('--series-1'), css('--series-2')],
                plotOptions: {
                    bar: { columnWidth: '52%', borderRadius: 4, borderRadiusApplication: 'end' },
                },
                stroke: { show: true, width: 2, colors: ['transparent'] }, // celah 2px antar batang
                xaxis: {
                    categories: trend.map(t => t.label),
                    axisBorder: { color: css('--grid') },
                    axisTicks: { show: false },
                    labels: { style: { colors: css('--axis'), fontSize: '12px' } },
                },
                yaxis: {
                    min: 0,
                    forceNiceScale: true,
                    labels: { formatter: v => nf.format(Math.round(v)), style: { colors: css('--axis'), fontSize: '12px' } },
                },
                states: { hover: { filter: { type: 'darken', value: 0.9 } } },
                tooltip: {
                    shared: true,
                    intersect: false,
                    y: { formatter: v => nf.format(v) },
                },
            }));
            c.render();
            charts.push(c);
        }
    }

    /* --- Toggle grafik / tabel --- */
    document.querySelectorAll('.vx-view-toggle button').forEach(btn => {
        btn.addEventListener('click', () => {
            const target = btn.dataset.target;
            btn.parentElement.querySelectorAll('button').forEach(b => b.classList.toggle('active', b === btn));
            document.querySelectorAll('[data-panel="' + target + '"]').forEach(p => {
                p.hidden = p.dataset.mode !== btn.dataset.view;
            });
        });
    });

    function boot() {
        if (typeof ApexCharts === 'undefined') {
            console.warn('Dashboard: ApexCharts gagal dimuat; tampilan tabel tetap tersedia.');
            document.querySelectorAll('[data-view="table"]').forEach(b => b.click());
            return;
        }
        render();

        // Render ulang saat mode gelap/terang diganti.
        new MutationObserver(() => render())
            .observe(document.body, { attributes: true, attributeFilter: ['class'] });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
</script>
@endsection
