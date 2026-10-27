
@extends('dashboard.layouts.wrapper')

@section('title', 'Dashboard')

@section('content')
@php
    $user = auth()->user();

    $companyName = data_get($user, 'company.name', 'Vasetra');

    $totalAssets = (int) ($totalAssets ?? 0);
    $activeAssets = (int) ($activeAssets ?? 0);
    $maintenanceDue = (int) ($maintenanceDue ?? 0);
    $maintenanceOverdue = (int) ($maintenanceOverdue ?? 0);
    $maintenanceToday = (int) ($maintenanceToday ?? 0);
    $maintenanceThisWeek = (int) ($maintenanceThisWeek ?? 0);

    $conditionLabels = [
        'new'     => 'Baru',
        'good'    => 'Baik',
        'used'    => 'Bekas',
        'damaged' => 'Rusak',
        'poor'    => 'Perlu Perbaikan',
    ];

    $conditionValues = [];
    foreach ($conditionLabels as $key => $label) {
        $conditionValues[] = (int) data_get($assetConditions ?? [], $key, 0);
    }

    $conditionColors = [
        '#2563eb',
        '#16a34a',
        '#f59e0b',
        '#ef4444',
        '#8b5cf6',
    ];

    $maintenanceLabels = ['Terlambat', 'Hari Ini', 'Minggu Ini'];
    $maintenanceValues = [
        $maintenanceOverdue,
        $maintenanceToday,
        $maintenanceThisWeek,
    ];

    $conditionTotal = array_sum($conditionValues);

    $formatDate = function ($date) {
        if (!$date) {
            return '-';
        }

        try {
            return \Illuminate\Support\Carbon::parse($date)
                ->locale('id')
                ->translatedFormat('d M Y');
        } catch (\Throwable $e) {
            return '-';
        }
    };

    $assetName = function ($asset) {
        return data_get($asset, 'asset_name')
            ?? data_get($asset, 'name')
            ?? data_get($asset, 'asset_code')
            ?? '-';
    };

    $conditionName = function ($asset) {
        $value = data_get($asset, 'condition')
            ?? data_get($asset, 'asset_condition')
            ?? data_get($asset, 'condition_status')
            ?? '-';

        return is_string($value)
            ? ucfirst(str_replace('_', ' ', $value))
            : '-';
    };
@endphp

<style>
    .vas-dashboard {
        --vas-navy: #102344;
        --vas-blue: #2563eb;
        --vas-muted: #718096;
        --vas-border: #e8edf5;
        color: #172b4d;
    }

    .vas-dashboard .vas-hero {
        position: relative;
        overflow: hidden;
        border-radius: 22px;
        padding: 30px;
        color: #fff;
        background: linear-gradient(120deg, #102344 0%, #173e72 58%, #2563eb 100%);
        box-shadow: 0 14px 35px rgba(16, 35, 68, .15);
    }

    .vas-dashboard .vas-hero::after {
        content: "";
        position: absolute;
        width: 260px;
        height: 260px;
        right: -75px;
        top: -130px;
        border: 40px solid rgba(255,255,255,.07);
        border-radius: 50%;
        pointer-events: none;
    }

    .vas-dashboard .vas-hero-content {
        position: relative;
        z-index: 1;
    }

    .vas-dashboard .vas-eyebrow {
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 1.8px;
        text-transform: uppercase;
        opacity: .78;
    }

    .vas-dashboard .vas-hero h2 {
        color: #fff;
        font-size: clamp(23px, 3vw, 31px);
        font-weight: 800;
        letter-spacing: -.7px;
        margin: 10px 0;
    }

    .vas-dashboard .vas-hero p {
        color: rgba(255,255,255,.76);
        margin-bottom: 0;
    }

    .vas-dashboard .vas-date {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 14px;
        border: 1px solid rgba(255,255,255,.2);
        background: rgba(255,255,255,.09);
        border-radius: 12px;
        font-size: 13px;
        white-space: nowrap;
    }

    .vas-dashboard .vas-stat-card,
    .vas-dashboard .vas-panel {
        background: #fff;
        border: 1px solid var(--vas-border);
        border-radius: 18px;
        box-shadow: 0 5px 20px rgba(16,35,68,.035);
        height: 100%;
    }

    .vas-dashboard .vas-stat-card {
        padding: 21px;
        transition: transform .2s ease, box-shadow .2s ease;
    }

    .vas-dashboard .vas-stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 28px rgba(16,35,68,.09);
    }

    .vas-dashboard .vas-stat-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
    }

    .vas-dashboard .vas-stat-label {
        font-size: 13px;
        font-weight: 600;
        color: var(--vas-muted);
    }

    .vas-dashboard .vas-stat-number {
        font-size: clamp(27px, 3vw, 34px);
        line-height: 1.2;
        letter-spacing: -1px;
        font-weight: 800;
        margin-top: 15px;
        color: var(--vas-navy);
        overflow-wrap: anywhere;
    }

    .vas-dashboard .vas-stat-foot {
        font-size: 12px;
        color: var(--vas-muted);
        margin-top: 8px;
    }

    .vas-dashboard .vas-icon {
        display: inline-flex;
        width: 45px;
        height: 45px;
        align-items: center;
        justify-content: center;
        border-radius: 14px;
        font-size: 21px;
        flex-shrink: 0;
    }

    .vas-dashboard .vas-blue {
        color: #2563eb;
        background: #eaf1ff;
    }

    .vas-dashboard .vas-green {
        color: #15803d;
        background: #e8f8ee;
    }

    .vas-dashboard .vas-orange {
        color: #c77700;
        background: #fff4dc;
    }

    .vas-dashboard .vas-red {
        color: #dc2626;
        background: #ffeded;
    }

    .vas-dashboard .vas-purple {
        color: #7c3aed;
        background: #f1eaff;
    }

    .vas-dashboard .vas-panel {
        padding: 22px;
    }

    .vas-dashboard .vas-panel-heading {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 12px;
        margin-bottom: 20px;
    }

    .vas-dashboard .vas-panel-title {
        color: var(--vas-navy);
        font-size: 16px;
        font-weight: 800;
        margin: 0;
    }

    .vas-dashboard .vas-panel-subtitle {
        color: var(--vas-muted);
        font-size: 12px;
        margin-top: 5px;
    }

    .vas-dashboard .vas-chart-wrap {
        position: relative;
        width: 100%;
        height: 260px;
    }

    .vas-dashboard .vas-chart-wrap.vas-doughnut {
        height: 275px;
    }

    .vas-dashboard .vas-mini-stat {
        padding: 14px;
        background: #f8faff;
        border: 1px solid #edf1f8;
        border-radius: 13px;
        height: 100%;
    }

    .vas-dashboard .vas-mini-stat span {
        display: block;
        color: var(--vas-muted);
        font-size: 12px;
    }

    .vas-dashboard .vas-mini-stat strong {
        display: block;
        margin-top: 6px;
        color: var(--vas-navy);
        font-size: 24px;
        font-weight: 800;
    }

    .vas-dashboard .vas-table {
        margin-bottom: 0;
        vertical-align: middle;
    }

    .vas-dashboard .vas-table thead th {
        padding: 12px 14px;
        color: #718096;
        background: #f8faff;
        border-bottom: 1px solid var(--vas-border);
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .5px;
        white-space: nowrap;
    }

    .vas-dashboard .vas-table tbody td {
        padding: 14px;
        border-bottom: 1px solid #f0f3f8;
        font-size: 13px;
    }

    .vas-dashboard .vas-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .vas-dashboard .vas-asset-name {
        font-weight: 700;
        color: var(--vas-navy);
    }

    .vas-dashboard .vas-muted {
        color: var(--vas-muted);
        font-size: 12px;
    }

    .vas-dashboard .vas-link {
        color: #2563eb;
        font-size: 12px;
        font-weight: 700;
        text-decoration: none;
        white-space: nowrap;
    }

    .vas-dashboard .vas-link:hover {
        text-decoration: underline;
    }

    .vas-dashboard .vas-schedule-item {
        display: flex;
        gap: 13px;
        padding: 14px 0;
        border-bottom: 1px solid #edf1f7;
    }

    .vas-dashboard .vas-schedule-item:last-child {
        border-bottom: 0;
        padding-bottom: 0;
    }

    .vas-dashboard .vas-schedule-date {
        flex-shrink: 0;
        width: 52px;
        height: 55px;
        border-radius: 13px;
        background: #edf3ff;
        color: #2563eb;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        font-weight: 800;
    }

    .vas-dashboard .vas-schedule-date small {
        font-size: 10px;
        text-transform: uppercase;
    }

    .vas-dashboard .vas-empty {
        padding: 30px 12px;
        text-align: center;
        color: var(--vas-muted);
        font-size: 13px;
    }

    .vas-dashboard .vas-condition-bar {
        height: 7px;
        border-radius: 99px;
        background: #edf1f7;
        overflow: hidden;
        margin-top: 7px;
    }

    .vas-dashboard .vas-condition-fill {
        height: 100%;
        border-radius: 99px;
    }

    @media (max-width: 767px) {
        .vas-dashboard .vas-hero {
            padding: 23px 20px;
            border-radius: 17px;
        }

        .vas-dashboard .vas-panel {
            padding: 17px;
        }

        .vas-dashboard .vas-stat-card {
            padding: 16px;
        }

        .vas-dashboard .vas-stat-number {
            font-size: 27px;
        }

        .vas-dashboard .vas-chart-wrap,
        .vas-dashboard .vas-chart-wrap.vas-doughnut {
            height: 240px;
        }
    }
</style>

<div class="container-fluid vas-dashboard">

    {{-- HERO --}}
    <div class="vas-hero mb-4">
        <div class="vas-hero-content">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <div class="vas-eyebrow">Asset Management Platform</div>
                    <h2>Selamat datang, {{ $user->name ?? 'User' }} 👋</h2>
                    <p>
                        Pantau aset, kondisi inventaris, dan jadwal maintenance
                        {{ $companyName ? 'di ' . $companyName : '' }}.
                    </p>
                </div>

                <div class="vas-date">
                    <span>📅</span>
                    <span>{{ now()->locale('id')->translatedFormat('l, d F Y') }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- KPI CARDS --}}
    <div class="row g-3 mb-4">

        <div class="col-6 col-xl-3">
            <div class="vas-stat-card">
                <div class="vas-stat-top">
                    <span class="vas-stat-label">Total Aset</span>
                    <span class="vas-icon vas-blue">▦</span>
                </div>
                <div class="vas-stat-number">{{ number_format($totalAssets) }}</div>
                <div class="vas-stat-foot">Seluruh aset terdaftar</div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="vas-stat-card">
                <div class="vas-stat-top">
                    <span class="vas-stat-label">Aset Aktif</span>
                    <span class="vas-icon vas-green">✓</span>
                </div>
                <div class="vas-stat-number">{{ number_format($activeAssets) }}</div>
                <div class="vas-stat-foot">Aset berstatus aktif</div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="vas-stat-card">
                <div class="vas-stat-top">
                    <span class="vas-stat-label">Jadwal Maintenance</span>
                    <span class="vas-icon vas-orange">◷</span>
                </div>
                <div class="vas-stat-number">{{ number_format($maintenanceDue) }}</div>
                <div class="vas-stat-foot">Maintenance yang perlu diperhatikan</div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="vas-stat-card">
                <div class="vas-stat-top">
                    <span class="vas-stat-label">Terlambat</span>
                    <span class="vas-icon vas-red">!</span>
                </div>
                <div class="vas-stat-number">{{ number_format($maintenanceOverdue) }}</div>
                <div class="vas-stat-foot">Maintenance melewati jadwal</div>
            </div>
        </div>

    </div>

    {{-- CHARTS --}}
    <div class="row g-3 mb-4">

        <div class="col-12 col-xl-5">
            <div class="vas-panel">
                <div class="vas-panel-heading">
                    <div>
                        <h5 class="vas-panel-title">Kondisi Aset</h5>
                        <div class="vas-panel-subtitle">Distribusi kondisi aset saat ini</div>
                    </div>
                    <span class="vas-icon vas-purple">◉</span>
                </div>

                <div class="vas-chart-wrap vas-doughnut">
                    <canvas id="vasAssetConditionChart"></canvas>
                </div>

                <div class="row g-2 mt-2">
                    @foreach ($conditionLabels as $key => $label)
                        @php
                            $index = array_search($key, array_keys($conditionLabels));
                            $value = $conditionValues[$index] ?? 0;
                            $percent = $conditionTotal > 0
                                ? round(($value / $conditionTotal) * 100)
                                : 0;
                        @endphp
                        <div class="col-6">
                            <div class="d-flex justify-content-between gap-2">
                                <span class="vas-muted">{{ $label }}</span>
                                <strong style="font-size:12px">{{ number_format($value) }}</strong>
                            </div>
                            <div class="vas-condition-bar">
                                <div class="vas-condition-fill"
                                     style="width:{{ $percent }}%;background:{{ $conditionColors[$index] }};">
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-7">
            <div class="vas-panel">
                <div class="vas-panel-heading">
                    <div>
                        <h5 class="vas-panel-title">Maintenance Overview</h5>
                        <div class="vas-panel-subtitle">Ringkasan jadwal perawatan aset</div>
                    </div>
                    <span class="vas-icon vas-orange">▥</span>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-4">
                        <div class="vas-mini-stat">
                            <span>Terlambat</span>
                            <strong>{{ number_format($maintenanceOverdue) }}</strong>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="vas-mini-stat">
                            <span>Hari Ini</span>
                            <strong>{{ number_format($maintenanceToday) }}</strong>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="vas-mini-stat">
                            <span>Minggu Ini</span>
                            <strong>{{ number_format($maintenanceThisWeek) }}</strong>
                        </div>
                    </div>
                </div>

                <div class="vas-chart-wrap">
                    <canvas id="vasMaintenanceChart"></canvas>
                </div>

                <div class="mt-3">
                    <a href="{{ route('maintenance.index') }}" class="vas-link">
                        Lihat semua maintenance →
                    </a>
                </div>
            </div>
        </div>

    </div>

    {{-- UPCOMING MAINTENANCE --}}
    <div class="vas-panel mb-4">
        <div class="vas-panel-heading">
            <div>
                <h5 class="vas-panel-title">Upcoming Maintenance</h5>
                <div class="vas-panel-subtitle">Jadwal perawatan aset mendatang</div>
            </div>
            <a href="{{ route('maintenance.index') }}" class="vas-link">
                Lihat semua →
            </a>
        </div>

        <div class="table-responsive">
            <table class="table vas-table">
                <thead>
                    <tr>
                        <th>Aset</th>
                        <th>Tanggal</th>
                        <th>Jenis / Keterangan</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse (($upcomingMaintenance ?? collect()) as $item)
                        @php
                            $itemAsset = data_get($item, 'asset');
                            $dateValue = data_get($item, 'maintenance_date')
                                ?? data_get($item, 'scheduled_date')
                                ?? data_get($item, 'next_maintenance_date')
                                ?? data_get($item, 'date');
                            $itemStatus = data_get($item, 'status', 'Terjadwal');
                            $itemTitle = data_get($item, 'title')
                                ?? data_get($item, 'maintenance_type')
                                ?? data_get($item, 'description')
                                ?? data_get($item, 'type')
                                ?? 'Maintenance';
                        @endphp
                        <tr>
                            <td>
                                <div class="vas-asset-name">
                                    {{ $itemAsset ? $assetName($itemAsset) : ($assetName($item) !== '-' ? $assetName($item) : 'Maintenance Aset') }}
                                </div>
                                <div class="vas-muted">
                                    {{ data_get($itemAsset, 'asset_code') ?? data_get($item, 'asset_code', '-') }}
                                </div>
                            </td>
                            <td>{{ $formatDate($dateValue) }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', (string) $itemTitle)) }}</td>
                            <td>
                                <span class="badge bg-label-primary">
                                    {{ ucfirst(str_replace('_', ' ', (string) $itemStatus)) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <div class="vas-empty">
                                    <div style="font-size:28px;margin-bottom:8px">✓</div>
                                    Tidak ada jadwal maintenance mendatang.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- RECENT ASSETS --}}
    <div class="vas-panel mb-4">
        <div class="vas-panel-heading">
            <div>
                <h5 class="vas-panel-title">Aset Terbaru</h5>
                <div class="vas-panel-subtitle">Data aset yang terakhir ditambahkan</div>
            </div>
            <a href="{{ route('assets.index') }}" class="vas-link">
                Lihat semua aset →
            </a>
        </div>

        <div class="table-responsive">
            <table class="table vas-table">
                <thead>
                    <tr>
                        <th>Nama Aset</th>
                        <th>Kategori</th>
                        <th>Penanggung Jawab</th>
                        <th>Kondisi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse (($recentAssets ?? collect()) as $asset)
                        <tr>
                            <td>
                                <div class="vas-asset-name">{{ $assetName($asset) }}</div>
                                <div class="vas-muted">
                                    {{ data_get($asset, 'asset_code', '-') }}
                                </div>
                            </td>
                            <td>
                                {{ data_get($asset, 'category.name')
                                    ?? data_get($asset, 'category.category_name')
                                    ?? '-' }}
                            </td>
                            <td>
                                {{ data_get($asset, 'responsibleUser.name')
                                    ?? data_get($asset, 'responsible_user.name')
                                    ?? '-' }}
                            </td>
                            <td>{{ $conditionName($asset) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <div class="vas-empty">
                                    Belum ada data aset untuk ditampilkan.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- MY UPCOMING MAINTENANCE --}}
    <div class="vas-panel mb-4">
        <div class="vas-panel-heading">
            <div>
                <h5 class="vas-panel-title">Maintenance Aset Saya</h5>
                <div class="vas-panel-subtitle">Jadwal perawatan aset yang menjadi tanggung jawab Anda</div>
            </div>
            <a href="{{ route('maintenance.index') }}" class="vas-link">
                Buka maintenance →
            </a>
        </div>

        @forelse (($myUpcomingMaintenance ?? collect()) as $item)
            @php
                $myAsset = data_get($item, 'asset');
                $myDate = data_get($item, 'maintenance_date')
                    ?? data_get($item, 'scheduled_date')
                    ?? data_get($item, 'next_maintenance_date')
                    ?? data_get($item, 'date');

                $dateParts = null;
                if ($myDate) {
                    try {
                        $dateParts = \Illuminate\Support\Carbon::parse($myDate);
                    } catch (\Throwable $e) {
                        $dateParts = null;
                    }
                }

                $myTitle = data_get($item, 'title')
                    ?? data_get($item, 'maintenance_type')
                    ?? data_get($item, 'description')
                    ?? 'Maintenance';
            @endphp

            <div class="vas-schedule-item">
                <div class="vas-schedule-date">
                    @if ($dateParts)
                        <span style="font-size:20px;line-height:1.1">{{ $dateParts->format('d') }}</span>
                        <small>{{ $dateParts->locale('id')->translatedFormat('M') }}</small>
                    @else
                        <span style="font-size:20px">—</span>
                        <small>Tanggal</small>
                    @endif
                </div>
                <div class="flex-grow-1">
                    <div class="vas-asset-name">
                        {{ $myAsset ? $assetName($myAsset) : ($assetName($item) !== '-' ? $assetName($item) : 'Maintenance Aset') }}
                    </div>
                    <div class="vas-muted mt-1">
                        {{ $myTitle }}
                        @if ($dateParts)
                            · {{ $dateParts->locale('id')->translatedFormat('l, d F Y') }}
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="vas-empty">
                <div style="font-size:28px;margin-bottom:8px">📅</div>
                Belum ada jadwal maintenance untuk aset Anda.
            </div>
        @endforelse
    </div>

</div>

{{-- Chart.js: tidak perlu npm run dev --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.8/dist/chart.umd.min.js"></script>

<script>
(function () {
    function initVasetraCharts() {
        if (typeof Chart === 'undefined') {
            console.warn('Vasetra Dashboard: Chart.js belum berhasil dimuat.');
            return;
        }

        const conditionCanvas = document.getElementById('vasAssetConditionChart');
        const maintenanceCanvas = document.getElementById('vasMaintenanceChart');

        if (conditionCanvas && !conditionCanvas.dataset.initialized) {
            conditionCanvas.dataset.initialized = '1';

            const conditionValues = @json($conditionValues);
            const conditionLabels = @json(array_values($conditionLabels));
            const conditionColors = @json($conditionColors);

            new Chart(conditionCanvas, {
                type: 'doughnut',
                data: {
                    labels: conditionLabels,
                    datasets: [{
                        data: conditionValues,
                        backgroundColor: conditionColors,
                        borderWidth: 0,
                        hoverOffset: 7,
                        spacing: 3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '72%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                pointStyle: 'circle',
                                padding: 16,
                                boxWidth: 8,
                                font: { size: 11 }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    return ' ' + context.label + ': ' +
                                        Number(context.raw || 0).toLocaleString('id-ID');
                                }
                            }
                        }
                    }
                }
            });
        }

        if (maintenanceCanvas && !maintenanceCanvas.dataset.initialized) {
            maintenanceCanvas.dataset.initialized = '1';

            const maintenanceLabels = @json($maintenanceLabels);
            const maintenanceValues = @json($maintenanceValues);

            new Chart(maintenanceCanvas, {
                type: 'bar',
                data: {
                    labels: maintenanceLabels,
                    datasets: [{
                        label: 'Jumlah maintenance',
                        data: maintenanceValues,
                        backgroundColor: [
                            'rgba(239, 68, 68, 0.85)',
                            'rgba(37, 99, 235, 0.85)',
                            'rgba(22, 163, 74, 0.85)'
                        ],
                        borderRadius: 8,
                        maxBarThickness: 48
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    return ' ' + Number(context.raw || 0).toLocaleString('id-ID') +
                                        ' maintenance';
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            border: { display: false },
                            ticks: { font: { size: 11 } }
                        },
                        y: {
                            beginAtZero: true,
                            border: { display: false },
                            grid: { color: '#edf1f7' },
                            ticks: {
                                precision: 0,
                                font: { size: 11 }
                            }
                        }
                    }
                }
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initVasetraCharts);
    } else {
        initVasetraCharts();
    }
})();
</script>
@endsection
