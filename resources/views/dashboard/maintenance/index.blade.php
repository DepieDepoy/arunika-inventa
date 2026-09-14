@extends('dashboard.layouts.wrapper')

@section('title', 'All Maintenance')

@section('content')

<style>
    /* =====================================================
       MAINTENANCE PAGE
    ====================================================== */

    .maintenance-page {
        width: 100%;
    }

    /* =====================================================
       MODULE NAVIGATION
    ====================================================== */

    .maintenance-nav-card {
        border: 1px solid #e9ecef;
        border-radius: 12px;
        padding: 8px;
        margin-bottom: 24px;
        background: #fff;
    }

    .maintenance-nav {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .maintenance-nav a {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 9px 15px;
        border-radius: 8px;
        text-decoration: none;
        font-size: 13px;
        font-weight: 500;
        color: #6c757d;
        transition: all .2s ease;
    }

    .maintenance-nav a:hover {
        background: #f8f9fa;
        color: #435ebe;
    }

    .maintenance-nav a.active {
        background: #435ebe;
        color: #fff;
    }

    /* =====================================================
       HERO
    ====================================================== */

    .maintenance-hero {
        position: relative;
        overflow: hidden;
        border-radius: 14px;
        padding: 24px;
        margin-bottom: 24px;
        background: linear-gradient(
            135deg,
            #435ebe 0%,
            #6777ef 100%
        );
        color: #fff;
    }

    .maintenance-hero-content {
        position: relative;
        z-index: 2;
    }

    .maintenance-hero h5 {
        margin-bottom: 6px;
        font-weight: 700;
    }

    .maintenance-hero p {
        margin-bottom: 0;
        opacity: .9;
        font-size: 13px;
    }

    .maintenance-hero-bg {
        position: absolute;
        right: -30px;
        top: -40px;
        width: 180px;
        height: 180px;
        border-radius: 50%;
        background: rgba(255, 255, 255, .08);
    }

    .maintenance-hero-bg::after {
        content: "";
        position: absolute;
        width: 120px;
        height: 120px;
        right: 40px;
        top: 80px;
        border-radius: 50%;
        background: rgba(255, 255, 255, .06);
    }

    /* =====================================================
       STATISTICS
    ====================================================== */

    .maintenance-stat-card {
        height: 100%;
        border: 1px solid #e9ecef;
        border-radius: 12px;
        background: #fff;
        padding: 18px;
        transition: all .2s ease;
    }

    .maintenance-stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 18px rgba(0, 0, 0, .06);
    }

    .maintenance-stat-icon {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 12px;
        font-size: 17px;
    }

    .maintenance-stat-title {
        font-size: 12px;
        color: #8a8a8a;
        margin-bottom: 4px;
    }

    .maintenance-stat-value {
        font-size: 24px;
        font-weight: 700;
        line-height: 1.2;
    }

    /* =====================================================
       CARDS
    ====================================================== */

    .maintenance-card {
        border: 1px solid #e9ecef;
        border-radius: 12px;
        background: #fff;
        margin-bottom: 24px;
        overflow: hidden;
    }

    .maintenance-card-header {
        padding: 16px 20px;
        border-bottom: 1px solid #e9ecef;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 15px;
    }

    .maintenance-card-title {
        margin: 0;
        font-size: 15px;
        font-weight: 700;
    }

    .maintenance-card-subtitle {
        margin: 3px 0 0;
        color: #8a8a8a;
        font-size: 12px;
    }

    .maintenance-card-body {
        padding: 20px;
    }

    /* =====================================================
       FILTER
    ====================================================== */

    .maintenance-filter {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-bottom: 20px;
    }

    .maintenance-filter button {
        border: 1px solid #dee2e6;
        background: #fff;
        color: #6c757d;
        border-radius: 7px;
        padding: 7px 12px;
        font-size: 12px;
        cursor: pointer;
        transition: all .2s ease;
    }

    .maintenance-filter button:hover {
        border-color: #435ebe;
        color: #435ebe;
    }

    .maintenance-filter button.active {
        background: #435ebe;
        border-color: #435ebe;
        color: #fff;
    }

    /* =====================================================
       TABLE
    ====================================================== */

    .maintenance-table {
        width: 100%;
        margin-bottom: 0;
    }

    .maintenance-table thead th {
        background: #f8f9fa;
        border-bottom: 1px solid #e9ecef;
        font-size: 11px;
        font-weight: 700;
        color: #6c757d;
        text-transform: uppercase;
        white-space: nowrap;
        padding: 12px;
    }

    .maintenance-table tbody td {
        padding: 13px 12px;
        vertical-align: middle;
        font-size: 13px;
        border-bottom: 1px solid #f1f1f1;
    }

    .maintenance-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .maintenance-table tbody tr:hover {
        background: #fafbfc;
    }

    /* =====================================================
       STATUS
    ====================================================== */

    .maintenance-status {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 9px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 600;
        white-space: nowrap;
    }

    .maintenance-status.overdue {
        background: #fff1f2;
        color: #dc3545;
    }

    .maintenance-status.today {
        background: #fff8e1;
        color: #d39e00;
    }

    .maintenance-status.week {
        background: #eef4ff;
        color: #435ebe;
    }

    .maintenance-status.upcoming {
        background: #edf9f0;
        color: #198754;
    }

    /* =====================================================
       INFO
    ====================================================== */

    .maintenance-info {
        border-radius: 10px;
        padding: 15px;
        background: #f8f9fa;
        height: 100%;
    }

    .maintenance-info-title {
        font-size: 13px;
        font-weight: 700;
        margin-bottom: 5px;
    }

    .maintenance-info-text {
        font-size: 12px;
        color: #777;
        margin-bottom: 0;
        line-height: 1.6;
    }

    /* =====================================================
       EMPTY STATE
    ====================================================== */

    .maintenance-empty {
        text-align: center;
        padding: 45px 20px;
        color: #999;
    }

    .maintenance-empty i {
        font-size: 38px;
        margin-bottom: 12px;
        opacity: .45;
    }

    .maintenance-empty h6 {
        margin-bottom: 5px;
        font-weight: 600;
        color: #777;
    }

    .maintenance-empty p {
        margin-bottom: 0;
        font-size: 12px;
    }

    /* =====================================================
       RESPONSIVE
    ====================================================== */

    @media (max-width: 768px) {

        .maintenance-card-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .maintenance-hero {
            padding: 20px;
        }

        .maintenance-table {
            min-width: 850px;
        }

        .maintenance-stat-value {
            font-size: 20px;
        }

    }
</style>

<div class="content-wrapper">

```
<div class="container-xxl flex-grow-1 container-p-y">

    <div class="row">

        <div class="col-xxl-12 mb-12 order-0">

            <div class="card">

                {{-- =====================================================
                     CARD HEADER
                ====================================================== --}}

                <div class="card-header d-flex justify-content-between align-items-center">

                    <div>

                        <h5 class="mb-0">
                            All Maintenance
                        </h5>

                        <small class="text-muted">
                            Pantau seluruh jadwal maintenance asset perusahaan
                        </small>

                    </div>

                    <div class="d-flex gap-2">

                        <a
                            href="{{ route('my-assets.index') }}"
                            class="btn btn-primary"
                        >
                            <i class="fa-solid fa-user-check me-1"></i>
                            My Assets
                        </a>

                    </div>

                </div>


                {{-- =====================================================
                     CARD BODY
                ====================================================== --}}

                <div class="card-body">

                    <div class="maintenance-page">


                        {{-- =================================================
                             MODULE NAVIGATION
                        ================================================== --}}

                        <div class="maintenance-nav-card">

                            <div class="maintenance-nav">

                                <a
                                    href="{{ route('maintenance.index') }}"
                                    class="active"
                                >
                                    <i class="fa-solid fa-screwdriver-wrench"></i>
                                    All Maintenance
                                </a>


                                @can('maintenance.request.create')

                                    <a
                                        href="{{ route('my-assets.index') }}"
                                    >
                                        <i class="fa-solid fa-user-check"></i>
                                        My Assets
                                    </a>

                                @endcan


                                @can('maintenance.request.view')

                                    <a
                                        href="{{ route('maintenance.requests.index') }}"
                                    >
                                        <i class="fa-solid fa-clipboard-list"></i>
                                        Requests
                                    </a>

                                @endcan


                                @can('maintenance.history.view')

                                    <a
                                        href="{{ route('maintenance.history') }}"
                                    >
                                        <i class="fa-solid fa-clock-rotate-left"></i>
                                        History
                                    </a>

                                @endcan

                            </div>

                        </div>


                        {{-- =================================================
                             HERO
                        ================================================== --}}

                        <div class="maintenance-hero">

                            <div class="maintenance-hero-bg"></div>

                            <div class="maintenance-hero-content">

                                <h5>
                                    Maintenance Overview
                                </h5>

                                <p>
                                    Pantau asset yang membutuhkan maintenance
                                    berdasarkan jadwal yang telah ditentukan.
                                </p>

                            </div>

                        </div>


                        {{-- =================================================
                             STATISTICS
                        ================================================== --}}

                        <div class="row g-3 mb-4">

                            {{-- TOTAL --}}

                            <div class="col-6 col-md">

                                <div class="maintenance-stat-card">

                                    <div
                                        class="maintenance-stat-icon"
                                        style="background:#eef4ff;color:#435ebe;"
                                    >
                                        <i class="fa-solid fa-screwdriver-wrench"></i>
                                    </div>

                                    <div class="maintenance-stat-title">
                                        Total Scheduled
                                    </div>

                                    <div class="maintenance-stat-value">
                                        {{ $summary['total'] ?? 0 }}
                                    </div>

                                </div>

                            </div>


                            {{-- OVERDUE --}}

                            <div class="col-6 col-md">

                                <div class="maintenance-stat-card">

                                    <div
                                        class="maintenance-stat-icon"
                                        style="background:#fff1f2;color:#dc3545;"
                                    >
                                        <i class="fa-solid fa-triangle-exclamation"></i>
                                    </div>

                                    <div class="maintenance-stat-title">
                                        Overdue
                                    </div>

                                    <div class="maintenance-stat-value text-danger">
                                        {{ $summary['overdue'] ?? 0 }}
                                    </div>

                                </div>

                            </div>


                            {{-- TODAY --}}

                            <div class="col-6 col-md">

                                <div class="maintenance-stat-card">

                                    <div
                                        class="maintenance-stat-icon"
                                        style="background:#fff8e1;color:#d39e00;"
                                    >
                                        <i class="fa-solid fa-calendar-day"></i>
                                    </div>

                                    <div class="maintenance-stat-title">
                                        Today
                                    </div>

                                    <div class="maintenance-stat-value">
                                        {{ $summary['today'] ?? 0 }}
                                    </div>

                                </div>

                            </div>


                            {{-- THIS WEEK --}}

                            <div class="col-6 col-md">

                                <div class="maintenance-stat-card">

                                    <div
                                        class="maintenance-stat-icon"
                                        style="background:#eef4ff;color:#435ebe;"
                                    >
                                        <i class="fa-solid fa-calendar-week"></i>
                                    </div>

                                    <div class="maintenance-stat-title">
                                        This Week
                                    </div>

                                    <div class="maintenance-stat-value">
                                        {{ $summary['this_week'] ?? 0 }}
                                    </div>

                                </div>

                            </div>


                            {{-- UPCOMING --}}

                            <div class="col-6 col-md">

                                <div class="maintenance-stat-card">

                                    <div
                                        class="maintenance-stat-icon"
                                        style="background:#edf9f0;color:#198754;"
                                    >
                                        <i class="fa-solid fa-calendar-plus"></i>
                                    </div>

                                    <div class="maintenance-stat-title">
                                        Upcoming
                                    </div>

                                    <div class="maintenance-stat-value text-success">
                                        {{ $summary['upcoming'] ?? 0 }}
                                    </div>

                                </div>

                            </div>

                        </div>


                        {{-- =================================================
                             MAINTENANCE SCHEDULE
                        ================================================== --}}

                        <div class="maintenance-card">

                            <div class="maintenance-card-header">

                                <div>

                                    <h6 class="maintenance-card-title">
                                        Maintenance Schedule
                                    </h6>

                                    <p class="maintenance-card-subtitle">
                                        Daftar asset berdasarkan jadwal maintenance.
                                    </p>

                                </div>

                            </div>


                            <div class="maintenance-card-body">


                                {{-- FILTER --}}

                                <div class="maintenance-filter">

                                    <button
                                        type="button"
                                        class="maintenance-filter-btn active"
                                        data-filter="all"
                                    >
                                        All
                                    </button>

                                    <button
                                        type="button"
                                        class="maintenance-filter-btn"
                                        data-filter="overdue"
                                    >
                                        Overdue
                                    </button>

                                    <button
                                        type="button"
                                        class="maintenance-filter-btn"
                                        data-filter="today"
                                    >
                                        Today
                                    </button>

                                    <button
                                        type="button"
                                        class="maintenance-filter-btn"
                                        data-filter="week"
                                    >
                                        This Week
                                    </button>

                                    <button
                                        type="button"
                                        class="maintenance-filter-btn"
                                        data-filter="upcoming"
                                    >
                                        Upcoming
                                    </button>

                                </div>


                                {{-- TABLE RESPONSIVE --}}

                                <div class="table-responsive">

                                    <table
                                        class="table maintenance-table"
                                        id="maintenanceScheduleTable"
                                    >

                                        <thead>

                                            <tr>

                                                <th width="50">
                                                    #
                                                </th>

                                                <th>
                                                    Asset
                                                </th>

                                                <th>
                                                    Category
                                                </th>

                                                <th>
                                                    Responsible User
                                                </th>

                                                <th>
                                                    Maintenance Date
                                                </th>

                                                <th>
                                                    Status
                                                </th>

                                            </tr>

                                        </thead>


                                        <tbody>

                                            @php

                                                /*
                                                 * Gabungkan semua kategori.
                                                 *
                                                 * Penting:
                                                 * Data sekarang berasal dari model Maintenance,
                                                 * bukan langsung dari Asset.
                                                 */

                                                $allMaintenance = collect()
                                                    ->merge($overdue ?? [])
                                                    ->merge($todayMaintenance ?? [])
                                                    ->merge($thisWeek ?? [])
                                                    ->merge($upcoming ?? [])
                                                    ->unique('id')
                                                    ->sortBy('maintenance_date');

                                            @endphp


                                            @forelse($allMaintenance as $maintenance)

                                                @php

                                                    /*
                                                     |--------------------------------------------------------------------------
                                                     | MAINTENANCE DATE
                                                     |--------------------------------------------------------------------------
                                                     */

                                                    $maintenanceDate = $maintenance->maintenance_date
                                                        ? \Carbon\Carbon::parse(
                                                            $maintenance->maintenance_date
                                                        )
                                                        : null;


                                                    /*
                                                     |--------------------------------------------------------------------------
                                                     | TODAY / WEEK
                                                     |--------------------------------------------------------------------------
                                                     */

                                                    $today = now()->startOfDay();

                                                    $endOfWeek = now()->endOfWeek();


                                                    /*
                                                     |--------------------------------------------------------------------------
                                                     | DISPLAY STATUS
                                                     |--------------------------------------------------------------------------
                                                     */

                                                    if (!$maintenanceDate) {

                                                        $displayStatus = 'upcoming';

                                                        $statusLabel = 'Scheduled';

                                                    } elseif ($maintenanceDate->lt($today)) {

                                                        $displayStatus = 'overdue';

                                                        $statusLabel = 'Overdue';

                                                    } elseif ($maintenanceDate->isToday()) {

                                                        $displayStatus = 'today';

                                                        $statusLabel = 'Today';

                                                    } elseif ($maintenanceDate->lte($endOfWeek)) {

                                                        $displayStatus = 'week';

                                                        $statusLabel = 'This Week';

                                                    } else {

                                                        $displayStatus = 'upcoming';

                                                        $statusLabel = 'Upcoming';

                                                    }


                                                    /*
                                                     |--------------------------------------------------------------------------
                                                     | ASSET
                                                     |--------------------------------------------------------------------------
                                                     */

                                                    $asset = $maintenance->asset;

                                                @endphp


                                                <tr
                                                    data-maintenance-status="{{ $displayStatus }}"
                                                >

                                                    {{-- # --}}

                                                    <td>
                                                        {{ $loop->iteration }}
                                                    </td>


                                                    {{-- ASSET --}}

                                                    <td>

                                                        @if($asset)

                                                            <div>

                                                                <div class="fw-semibold">
                                                                    {{ $asset->asset_name }}
                                                                </div>

                                                                <small class="text-muted">
                                                                    {{ $asset->asset_code }}
                                                                </small>

                                                            </div>

                                                        @else

                                                            <span class="text-muted">
                                                                Asset tidak ditemukan
                                                            </span>

                                                        @endif

                                                    </td>


                                                    {{-- CATEGORY --}}

                                                    <td>

                                                        @if($asset)

                                                            {{ $asset->category?->name
                                                                ?? $asset->category?->category_name
                                                                ?? '-' }}

                                                        @else

                                                            -

                                                        @endif

                                                    </td>


                                                    {{-- RESPONSIBLE USER --}}

                                                    <td>

                                                        @if($asset?->responsibleUser)

                                                            <div class="fw-semibold">
                                                                {{ $asset->responsibleUser->name }}
                                                            </div>

                                                            @if($asset->responsibleUser->email)

                                                                <small class="text-muted">
                                                                    {{ $asset->responsibleUser->email }}
                                                                </small>

                                                            @endif

                                                        @else

                                                            <span class="text-muted">
                                                                Not Assigned
                                                            </span>

                                                        @endif

                                                    </td>


                                                    {{-- MAINTENANCE DATE --}}

                                                    <td>

                                                        @if($maintenanceDate)

                                                            <div class="fw-semibold">

                                                                {{ $maintenanceDate->format('d M Y') }}

                                                            </div>

                                                            <small class="text-muted">

                                                                {{ $maintenanceDate->diffForHumans() }}

                                                            </small>

                                                        @else

                                                            -

                                                        @endif

                                                    </td>


                                                    {{-- STATUS --}}

                                                    <td>

                                                        <span
                                                            class="maintenance-status {{ $displayStatus }}"
                                                        >

                                                            @if($displayStatus === 'overdue')

                                                                <i class="fa-solid fa-circle-exclamation"></i>

                                                            @elseif($displayStatus === 'today')

                                                                <i class="fa-solid fa-calendar-day"></i>

                                                            @elseif($displayStatus === 'week')

                                                                <i class="fa-solid fa-calendar-week"></i>

                                                            @else

                                                                <i class="fa-solid fa-calendar-check"></i>

                                                            @endif

                                                            {{ $statusLabel }}

                                                        </span>

                                                    </td>

                                                </tr>


                                            @empty

                                                <tr>

                                                    <td colspan="6">

                                                        <div class="maintenance-empty">

                                                            <i class="fa-solid fa-screwdriver-wrench"></i>

                                                            <h6>
                                                                Belum ada jadwal maintenance
                                                            </h6>

                                                            <p>
                                                                Belum ada asset yang memiliki
                                                                jadwal maintenance aktif.
                                                            </p>

                                                        </div>

                                                    </td>

                                                </tr>

                                            @endforelse

                                        </tbody>

                                    </table>

                                </div>

                            </div>

                        </div>


                        {{-- =================================================
                             INFORMATION
                        ================================================== --}}

                        <div class="row g-3">

                            {{-- SCHEDULED --}}

                            <div class="col-md-4">

                                <div class="maintenance-info">

                                    <div class="maintenance-info-title">

                                        <i class="fa-solid fa-calendar-check me-1"></i>

                                        Scheduled Maintenance

                                    </div>

                                    <p class="maintenance-info-text">

                                        Menampilkan asset yang memiliki
                                        jadwal maintenance berdasarkan
                                        <strong>Maintenance Date</strong>.

                                    </p>

                                </div>

                            </div>


                            {{-- MY ASSETS --}}

                            <div class="col-md-4">

                                <div class="maintenance-info">

                                    <div class="maintenance-info-title">

                                        <i class="fa-solid fa-user-check me-1"></i>

                                        My Assets

                                    </div>

                                    <p class="maintenance-info-text">

                                        Asset yang menjadi tanggung jawab
                                        user login dapat diajukan
                                        maintenance atau repair meskipun
                                        tidak memiliki jadwal.

                                    </p>

                                </div>

                            </div>


                            {{-- HISTORY --}}

                            <div class="col-md-4">

                                <div class="maintenance-info">

                                    <div class="maintenance-info-title">

                                        <i class="fa-solid fa-clock-rotate-left me-1"></i>

                                        Maintenance History

                                    </div>

                                    <p class="maintenance-info-text">

                                        Request yang sudah selesai akan
                                        tersimpan di History lengkap dengan
                                        hasil pekerjaan dan dokumentasi.

                                    </p>

                                </div>

                            </div>

                        </div>


                    </div>

                </div>

            </div>

        </div>

    </div>

</div>
```

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const filterButtons = document.querySelectorAll(
        '.maintenance-filter-btn'
    );

    const rows = document.querySelectorAll(
        '#maintenanceScheduleTable tbody tr[data-maintenance-status]'
    );


    filterButtons.forEach(function (button) {

        button.addEventListener('click', function () {

            /*
             * Reset active button
             */

            filterButtons.forEach(function (btn) {

                btn.classList.remove('active');

            });


            /*
             * Set active button
             */

            this.classList.add('active');


            /*
             * Get selected filter
             */

            const filter = this.dataset.filter;


            /*
             * Filter table rows
             */

            rows.forEach(function (row) {

                const status = row.dataset.maintenanceStatus;


                if (
                    filter === 'all' ||
                    status === filter
                ) {

                    row.style.display = '';

                } else {

                    row.style.display = 'none';

                }

            });

        });

    });

});
</script>

@endsection
