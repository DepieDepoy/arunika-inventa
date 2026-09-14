@extends('dashboard.layouts.wrapper')

@section('title', 'Maintenance')

@section('content')

@php
/** @var \App\Models\User $user */
$user = auth()->user();
@endphp

<style>
/* =========================================================
   MAINTENANCE DASHBOARD
========================================================= */

.maintenance-page {
    padding-bottom: 30px;
}

/* =========================================================
   HEADER
========================================================= */

.maintenance-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 20px;
}

.maintenance-header-left {
    display: flex;
    align-items: center;
    gap: 14px;
}

.maintenance-header-icon {
    width: 54px;
    height: 54px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(13, 110, 253, .10);
    color: #0d6efd;
    font-size: 22px;
}

.maintenance-header h4 {
    margin: 0;
    font-size: 22px;
    font-weight: 750;
    color: #1f2937;
}

.maintenance-header p {
    margin: 4px 0 0;
    color: #8b95a5;
    font-size: 13px;
}

/* =========================================================
   NAVIGATION
========================================================= */

.maintenance-nav-card {
    background: #fff;
    border-radius: 18px;
    padding: 7px;
    margin-bottom: 20px;
    box-shadow: 0 5px 22px rgba(0, 0, 0, .05);
}

.maintenance-nav {
    display: flex;
    gap: 5px;
}

.maintenance-nav-item {
    flex: 1;
    min-width: 150px;
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 13px 15px;
    border-radius: 13px;
    color: #6b7280;
    text-decoration: none;
    transition: .18s ease;
}

.maintenance-nav-item:hover {
    background: #f4f7fb;
    color: #0d6efd;
    text-decoration: none;
}

.maintenance-nav-item.active {
    background: #0d6efd;
    color: #fff;
    box-shadow: 0 6px 16px rgba(13, 110, 253, .18);
}

.maintenance-nav-icon {
    width: 35px;
    height: 35px;
    min-width: 35px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(13, 110, 253, .08);
    color: #0d6efd;
}

.maintenance-nav-item.active .maintenance-nav-icon {
    background: rgba(255, 255, 255, .18);
    color: #fff;
}

.maintenance-nav-title {
    font-size: 12px;
    font-weight: 750;
}

.maintenance-nav-desc {
    margin-top: 2px;
    font-size: 10px;
    opacity: .65;
}

/* =========================================================
   HERO
========================================================= */

.maintenance-hero {
    position: relative;
    overflow: hidden;
    border-radius: 20px;
    padding: 24px 27px;
    margin-bottom: 20px;
    background: linear-gradient(
        135deg,
        #0d6efd,
        #1559b7
    );
    color: #fff;
    box-shadow: 0 10px 28px rgba(13, 110, 253, .14);
}

.maintenance-hero::before {
    content: "";
    position: absolute;
    width: 220px;
    height: 220px;
    border-radius: 50%;
    right: -80px;
    top: -120px;
    background: rgba(255, 255, 255, .08);
}

.maintenance-hero::after {
    content: "";
    position: absolute;
    width: 120px;
    height: 120px;
    border-radius: 50%;
    right: 130px;
    bottom: -90px;
    background: rgba(255, 255, 255, .06);
}

.maintenance-hero-content {
    position: relative;
    z-index: 2;
}

.maintenance-hero-title {
    font-size: 20px;
    font-weight: 750;
    margin-bottom: 5px;
}

.maintenance-hero-text {
    max-width: 700px;
    margin: 0;
    font-size: 13px;
    line-height: 1.6;
    opacity: .82;
}

.maintenance-hero-action {
    position: relative;
    z-index: 2;
}

/* =========================================================
   STATISTICS
========================================================= */

.maintenance-stat-card {
    height: 100%;
    border-radius: 17px;
    background: #fff;
    box-shadow: 0 5px 22px rgba(0, 0, 0, .05);
    transition: .18s ease;
}

.maintenance-stat-card:hover {
    transform: translateY(-2px);
}

.maintenance-stat-body {
    display: flex;
    align-items: center;
    gap: 13px;
    padding: 18px;
}

.maintenance-stat-icon {
    width: 44px;
    height: 44px;
    min-width: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
}

.stat-total .maintenance-stat-icon {
    background: rgba(13, 110, 253, .10);
    color: #0d6efd;
}

.stat-scheduled .maintenance-stat-icon {
    background: rgba(13, 202, 240, .10);
    color: #087990;
}

.stat-progress .maintenance-stat-icon {
    background: rgba(255, 193, 7, .13);
    color: #997404;
}

.stat-completed .maintenance-stat-icon {
    background: rgba(25, 135, 84, .10);
    color: #198754;
}

.stat-overdue .maintenance-stat-icon {
    background: rgba(220, 53, 69, .10);
    color: #dc3545;
}

.maintenance-stat-label {
    color: #8b95a5;
    font-size: 10px;
    margin-bottom: 4px;
}

.maintenance-stat-value {
    color: #1f2937;
    font-size: 21px;
    line-height: 1;
    font-weight: 750;
}

/* =========================================================
   CARDS
========================================================= */

.maintenance-card {
    height: 100%;
    background: #fff;
    border-radius: 18px;
    box-shadow: 0 5px 22px rgba(0, 0, 0, .05);
    overflow: hidden;
}

.maintenance-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    padding: 17px 20px;
    border-bottom: 1px solid #eef0f3;
}

.maintenance-card-title {
    display: flex;
    align-items: center;
    gap: 10px;
}

.maintenance-card-icon {
    width: 37px;
    height: 37px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f3f7ff;
    color: #0d6efd;
}

.maintenance-card-title h5 {
    margin: 0;
    color: #1f2937;
    font-size: 14px;
    font-weight: 750;
}

.maintenance-card-title span {
    display: block;
    margin-top: 2px;
    color: #9ca3af;
    font-size: 10px;
}

/* =========================================================
   UPCOMING
========================================================= */

.maintenance-upcoming {
    padding: 7px 20px 15px;
}

.upcoming-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 13px 0;
    border-bottom: 1px solid #f0f2f5;
}

.upcoming-item:last-child {
    border-bottom: 0;
}

.upcoming-date {
    width: 43px;
    min-width: 43px;
    height: 45px;
    border-radius: 10px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: #f3f7ff;
    color: #0d6efd;
}

.upcoming-day {
    font-size: 16px;
    line-height: 1;
    font-weight: 750;
}

.upcoming-month {
    margin-top: 3px;
    font-size: 8px;
    font-weight: 700;
    text-transform: uppercase;
}

.upcoming-info {
    flex: 1;
    min-width: 0;
}

.upcoming-name {
    color: #374151;
    font-size: 12px;
    font-weight: 650;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.upcoming-code {
    margin-top: 3px;
    color: #9ca3af;
    font-size: 10px;
}

/* =========================================================
   QUICK ACTIONS
========================================================= */

.quick-actions {
    padding: 10px 20px 20px;
}

.quick-action {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    margin-top: 8px;
    border-radius: 11px;
    background: #f8fafc;
    color: #374151;
    text-decoration: none;
    transition: .18s ease;
}

.quick-action:hover {
    background: #f1f6ff;
    color: #0d6efd;
    text-decoration: none;
}

.quick-action-icon {
    width: 36px;
    height: 36px;
    min-width: 36px;
    border-radius: 10px;
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #0d6efd;
    box-shadow: 0 2px 7px rgba(0, 0, 0, .04);
}

.quick-action-title {
    font-size: 12px;
    font-weight: 700;
}

.quick-action-desc {
    margin-top: 2px;
    color: #9ca3af;
    font-size: 10px;
}

/* =========================================================
   INFO PANEL
========================================================= */

.maintenance-info {
    padding: 20px;
}

.maintenance-info-box {
    display: flex;
    align-items: flex-start;
    gap: 13px;
    padding: 15px;
    border-radius: 12px;
    background: #f8fafc;
}

.maintenance-info-icon {
    width: 38px;
    height: 38px;
    min-width: 38px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #eaf2ff;
    color: #0d6efd;
}

.maintenance-info-title {
    margin-bottom: 4px;
    color: #374151;
    font-size: 12px;
    font-weight: 700;
}

.maintenance-info-text {
    margin: 0;
    color: #8b95a5;
    font-size: 11px;
    line-height: 1.6;
}

/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 991.98px) {

    .maintenance-nav {
        overflow-x: auto;
    }

    .maintenance-nav-item {
        min-width: 140px;
    }

}

@media (max-width: 767.98px) {

    .maintenance-header {
        align-items: flex-start;
        flex-direction: column;
    }

    .maintenance-header-actions {
        width: 100%;
    }

    .maintenance-header-actions .btn {
        width: 100%;
    }

    .maintenance-nav-item {
        min-width: 125px;
    }

    .maintenance-nav-desc {
        display: none;
    }

    .maintenance-hero {
        padding: 20px;
    }

    .maintenance-hero-action {
        margin-top: 15px;
    }

    .maintenance-hero-action .btn {
        width: 100%;
    }

}
</style>

<div class="maintenance-page">

```
{{-- =====================================================
     HEADER
====================================================== --}}
<div class="maintenance-header">

    <div class="maintenance-header-left">

        <div class="maintenance-header-icon">
            <i class="fa-solid fa-screwdriver-wrench"></i>
        </div>

        <div>

            <h4>
                Maintenance
            </h4>

            <p>
                Kelola perawatan dan pemeliharaan asset perusahaan
            </p>

        </div>

    </div>


    <div class="maintenance-header-actions">

        @if($user->hasPermission('maintenance.create'))

            <a
                href="{{ route('maintenance.create') }}"
                class="btn btn-primary"
            >
                <i class="fa-solid fa-plus me-1"></i>
                Add Maintenance
            </a>

        @endif

    </div>

</div>


{{-- =====================================================
     MODULE NAVIGATION
====================================================== --}}
<div class="maintenance-nav-card">

    <div class="maintenance-nav">

        <a
            href="{{ route('maintenance.index') }}"
            class="maintenance-nav-item active"
        >

            <div class="maintenance-nav-icon">
                <i class="fa-solid fa-chart-pie"></i>
            </div>

            <div>

                <div class="maintenance-nav-title">
                    Dashboard
                </div>

                <div class="maintenance-nav-desc">
                    Overview
                </div>

            </div>

        </a>


        <a
            href="{{ route('maintenance.schedule') }}"
            class="maintenance-nav-item"
        >

            <div class="maintenance-nav-icon">
                <i class="fa-regular fa-calendar-days"></i>
            </div>

            <div>

                <div class="maintenance-nav-title">
                    Schedule
                </div>

                <div class="maintenance-nav-desc">
                    Jadwal maintenance
                </div>

            </div>

        </a>


        <a
            href="{{ route('maintenance.request') }}"
            class="maintenance-nav-item"
        >

            <div class="maintenance-nav-icon">
                <i class="fa-solid fa-file-circle-plus"></i>
            </div>

            <div>

                <div class="maintenance-nav-title">
                    Request
                </div>

                <div class="maintenance-nav-desc">
                    Pengajuan
                </div>

            </div>

        </a>


        <a
            href="{{ route('maintenance.history') }}"
            class="maintenance-nav-item"
        >

            <div class="maintenance-nav-icon">
                <i class="fa-solid fa-clock-rotate-left"></i>
            </div>

            <div>

                <div class="maintenance-nav-title">
                    History
                </div>

                <div class="maintenance-nav-desc">
                    Riwayat pekerjaan
                </div>

            </div>

        </a>

    </div>

</div>


{{-- =====================================================
     HERO
====================================================== --}}
<div class="maintenance-hero">

    <div class="row align-items-center">

        <div class="col-lg-8">

            <div class="maintenance-hero-content">

                <div class="maintenance-hero-title">
                    Maintenance Overview
                </div>

                <p class="maintenance-hero-text">
                    Pantau jadwal dan aktivitas maintenance asset
                    secara cepat dari satu dashboard.
                </p>

            </div>

        </div>


        <div class="col-lg-4 text-lg-end">

            <div class="maintenance-hero-action">

                @if($user->hasPermission('maintenance.create'))

                    <a
                        href="{{ route('maintenance.create') }}"
                        class="btn btn-light"
                    >
                        <i class="fa-solid fa-plus me-1"></i>
                        New Maintenance
                    </a>

                @endif

            </div>

        </div>

    </div>

</div>


{{-- =====================================================
     STATISTICS
====================================================== --}}
<div class="row g-3 mb-3">

    {{-- Total --}}
    <div class="col-xl col-lg-4 col-md-6 col-sm-6">

        <div class="maintenance-stat-card stat-total">

            <div class="maintenance-stat-body">

                <div class="maintenance-stat-icon">
                    <i class="fa-solid fa-screwdriver-wrench"></i>
                </div>

                <div>

                    <div class="maintenance-stat-label">
                        Total Maintenance
                    </div>

                    <div
                        id="statTotal"
                        class="maintenance-stat-value"
                    >
                        -
                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- Scheduled --}}
    <div class="col-xl col-lg-4 col-md-6 col-sm-6">

        <div class="maintenance-stat-card stat-scheduled">

            <div class="maintenance-stat-body">

                <div class="maintenance-stat-icon">
                    <i class="fa-regular fa-calendar-check"></i>
                </div>

                <div>

                    <div class="maintenance-stat-label">
                        Scheduled
                    </div>

                    <div
                        id="statScheduled"
                        class="maintenance-stat-value"
                    >
                        -
                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- In Progress --}}
    <div class="col-xl col-lg-4 col-md-6 col-sm-6">

        <div class="maintenance-stat-card stat-progress">

            <div class="maintenance-stat-body">

                <div class="maintenance-stat-icon">
                    <i class="fa-solid fa-spinner"></i>
                </div>

                <div>

                    <div class="maintenance-stat-label">
                        In Progress
                    </div>

                    <div
                        id="statProgress"
                        class="maintenance-stat-value"
                    >
                        -
                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- Completed --}}
    <div class="col-xl col-lg-4 col-md-6 col-sm-6">

        <div class="maintenance-stat-card stat-completed">

            <div class="maintenance-stat-body">

                <div class="maintenance-stat-icon">
                    <i class="fa-solid fa-circle-check"></i>
                </div>

                <div>

                    <div class="maintenance-stat-label">
                        Completed
                    </div>

                    <div
                        id="statCompleted"
                        class="maintenance-stat-value"
                    >
                        -
                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- Overdue --}}
    <div class="col-xl col-lg-4 col-md-6 col-sm-6">

        <div class="maintenance-stat-card stat-overdue">

            <div class="maintenance-stat-body">

                <div class="maintenance-stat-icon">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>

                <div>

                    <div class="maintenance-stat-label">
                        Overdue
                    </div>

                    <div
                        id="statOverdue"
                        class="maintenance-stat-value"
                    >
                        -
                    </div>

                </div>

            </div>

        </div>

    </div>

</div>


{{-- =====================================================
     MAIN DASHBOARD CONTENT
====================================================== --}}
<div class="row g-3">

    {{-- Upcoming --}}
    <div class="col-xl-7">

        <div class="maintenance-card">

            <div class="maintenance-card-header">

                <div class="maintenance-card-title">

                    <div class="maintenance-card-icon">
                        <i class="fa-regular fa-calendar"></i>
                    </div>

                    <div>

                        <h5>
                            Upcoming Maintenance
                        </h5>

                        <span>
                            Jadwal maintenance terdekat
                        </span>

                    </div>

                </div>


                <a
                    href="{{ route('maintenance.schedule') }}"
                    class="btn btn-sm btn-light border"
                >
                    View Schedule
                </a>

            </div>


            <div
                id="upcomingMaintenance"
                class="maintenance-upcoming"
            >

                <div class="text-center py-4">

                    <div class="spinner-border spinner-border-sm text-primary"></div>

                    <div class="mt-2 text-muted small">
                        Loading schedule...
                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- Quick Actions --}}
    <div class="col-xl-5">

        <div class="maintenance-card">

            <div class="maintenance-card-header">

                <div class="maintenance-card-title">

                    <div class="maintenance-card-icon">
                        <i class="fa-solid fa-bolt"></i>
                    </div>

                    <div>

                        <h5>
                            Quick Actions
                        </h5>

                        <span>
                            Akses cepat maintenance
                        </span>

                    </div>

                </div>

            </div>


            <div class="quick-actions">

                @if($user->hasPermission('maintenance.create'))

                    <a
                        href="{{ route('maintenance.create') }}"
                        class="quick-action"
                    >

                        <div class="quick-action-icon">
                            <i class="fa-solid fa-plus"></i>
                        </div>

                        <div>

                            <div class="quick-action-title">
                                Add Maintenance
                            </div>

                            <div class="quick-action-desc">
                                Buat record maintenance baru
                            </div>

                        </div>

                        <i class="fa-solid fa-chevron-right ms-auto text-muted"></i>

                    </a>

                @endif


                <a
                    href="{{ route('maintenance.schedule') }}"
                    class="quick-action"
                >

                    <div class="quick-action-icon">
                        <i class="fa-regular fa-calendar-days"></i>
                    </div>

                    <div>

                        <div class="quick-action-title">
                            Schedule
                        </div>

                        <div class="quick-action-desc">
                            Kelola jadwal maintenance
                        </div>

                    </div>

                    <i class="fa-solid fa-chevron-right ms-auto text-muted"></i>

                </a>


                <a
                    href="{{ route('maintenance.request') }}"
                    class="quick-action"
                >

                    <div class="quick-action-icon">
                        <i class="fa-solid fa-file-circle-plus"></i>
                    </div>

                    <div>

                        <div class="quick-action-title">
                            Request
                        </div>

                        <div class="quick-action-desc">
                            Kelola request maintenance
                        </div>

                    </div>

                    <i class="fa-solid fa-chevron-right ms-auto text-muted"></i>

                </a>


                <a
                    href="{{ route('maintenance.history') }}"
                    class="quick-action"
                >

                    <div class="quick-action-icon">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                    </div>

                    <div>

                        <div class="quick-action-title">
                            History
                        </div>

                        <div class="quick-action-desc">
                            Lihat histori maintenance
                        </div>

                    </div>

                    <i class="fa-solid fa-chevron-right ms-auto text-muted"></i>

                </a>

            </div>

        </div>

    </div>


    {{-- Information --}}
    <div class="col-12">

        <div class="maintenance-card">

            <div class="maintenance-card-header">

                <div class="maintenance-card-title">

                    <div class="maintenance-card-icon">
                        <i class="fa-solid fa-circle-info"></i>
                    </div>

                    <div>

                        <h5>
                            Maintenance Management
                        </h5>

                        <span>
                            Alur pengelolaan maintenance
                        </span>

                    </div>

                </div>

            </div>


            <div class="maintenance-info">

                <div class="row g-3">

                    <div class="col-lg-4">

                        <div class="maintenance-info-box">

                            <div class="maintenance-info-icon">
                                <i class="fa-regular fa-calendar-check"></i>
                            </div>

                            <div>

                                <div class="maintenance-info-title">
                                    Schedule
                                </div>

                                <p class="maintenance-info-text">
                                    Atur dan pantau jadwal preventive,
                                    corrective, inspection, dan calibration.
                                </p>

                            </div>

                        </div>

                    </div>


                    <div class="col-lg-4">

                        <div class="maintenance-info-box">

                            <div class="maintenance-info-icon">
                                <i class="fa-solid fa-file-circle-plus"></i>
                            </div>

                            <div>

                                <div class="maintenance-info-title">
                                    Request
                                </div>

                                <p class="maintenance-info-text">
                                    Kelola pengajuan maintenance dari
                                    user atau PIC asset.
                                </p>

                            </div>

                        </div>

                    </div>


                    <div class="col-lg-4">

                        <div class="maintenance-info-box">

                            <div class="maintenance-info-icon">
                                <i class="fa-solid fa-clock-rotate-left"></i>
                            </div>

                            <div>

                                <div class="maintenance-info-title">
                                    History
                                </div>

                                <p class="maintenance-info-text">
                                    Simpan dan telusuri histori pekerjaan
                                    maintenance setiap asset.
                                </p>

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

@endsection

@push('scripts')

<script>

$(document).ready(function () {

    /* =====================================================
       LOAD SUMMARY
       HANYA 1 REQUEST SAAT PAGE LOAD
    ====================================================== */

    function loadSummary() {

        $.ajax({

            url: "{{ route('maintenance.summary') }}",

            type: "GET",

            dataType: "json",

            success: function (response) {

                $('#statTotal')
                    .text(response.total ?? 0);

                $('#statScheduled')
                    .text(response.scheduled ?? 0);

                $('#statProgress')
                    .text(response.progress ?? 0);

                $('#statCompleted')
                    .text(response.completed ?? 0);

                $('#statOverdue')
                    .text(response.overdue ?? 0);

            },

            error: function () {

                $('#statTotal').text('0');
                $('#statScheduled').text('0');
                $('#statProgress').text('0');
                $('#statCompleted').text('0');
                $('#statOverdue').text('0');

            }

        });

    }


    /* =====================================================
       LOAD UPCOMING
       MAKSIMAL 5 DATA
       TIDAK ADA DATATABLE
    ====================================================== */

    function loadUpcoming() {

        $.ajax({

            url: "{{ route('maintenance.data') }}",

            type: "GET",

            dataType: "json",

            data: {

                draw: 1,

                start: 0,

                length: 5,

                status: 'scheduled',

                order: [{
                    column: 3,
                    dir: 'asc'
                }]

            },

            success: function (response) {

                let rows =
                    response.data || [];

                let container =
                    $('#upcomingMaintenance');

                container.empty();


                if (!rows.length) {

                    container.html(`

                        <div class="text-center py-4">

                            <div
                                class="mb-2"
                                style="font-size:24px;color:#cbd5e1;"
                            >
                                <i class="fa-regular fa-calendar-xmark"></i>
                            </div>

                            <div
                                class="text-muted"
                                style="font-size:12px;"
                            >
                                Belum ada maintenance terjadwal.
                            </div>

                        </div>

                    `);

                    return;

                }


                rows.forEach(function (row) {

                    let rawDate =
                        row.maintenance_date || '-';

                    let dateParts =
                        rawDate.split(' ');

                    let day =
                        dateParts[0] || '-';

                    let month =
                        dateParts[1] || '';


                    let assetHtml =
                        row.asset_info || '-';


                    container.append(`

                        <div class="upcoming-item">

                            <div class="upcoming-date">

                                <div class="upcoming-day">
                                    ${day}
                                </div>

                                <div class="upcoming-month">
                                    ${month}
                                </div>

                            </div>


                            <div class="upcoming-info">

                                <div class="upcoming-name">
                                    ${assetHtml}
                                </div>

                                <div class="upcoming-code">
                                    ${row.maintenance_code ?? '-'}
                                </div>

                            </div>

                        </div>

                    `);

                });

            },

            error: function () {

                $('#upcomingMaintenance').html(`

                    <div class="text-center py-4">

                        <div
                            class="text-danger mb-2"
                            style="font-size:20px;"
                        >
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </div>

                        <div
                            class="text-muted"
                            style="font-size:12px;"
                        >
                            Jadwal gagal dimuat.
                        </div>

                    </div>

                `);

            }

        });

    }


    /* =====================================================
       INITIAL LOAD
       HANYA SEKALI
    ====================================================== */

    loadSummary();

    loadUpcoming();

});

</script>

@endpush
