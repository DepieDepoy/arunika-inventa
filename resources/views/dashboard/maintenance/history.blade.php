@extends('dashboard.layouts.wrapper')

@section('title', 'Maintenance History')

@section('content')

<style>

    /* =====================================================
       SWEETALERT / DROPDOWN
    ====================================================== */

    .swal2-container {
        z-index: 99999 !important;
    }

    .table-responsive {
        overflow: visible !important;
    }

    .dropdown-menu {
        z-index: 99999 !important;
    }


    /* =====================================================
       HISTORY SUMMARY
    ====================================================== */

    .history-summary-card {
        height: 100%;
    }

    .history-summary-icon {
        width: 45px;
        height: 45px;

        display: flex;
        align-items: center;
        justify-content: center;

        border-radius: 10px;

        flex-shrink: 0;
    }

    .history-summary-icon i {
        font-size: 18px;
    }


    /* =====================================================
       TABLE
    ====================================================== */

    #maintenanceHistoryTable {
        width: 100% !important;
    }

    #maintenanceHistoryTable th,
    #maintenanceHistoryTable td {
        vertical-align: middle;
    }

    #maintenanceHistoryTable th {
        white-space: nowrap;
    }

    .asset-info {
        min-width: 0;
    }

    .asset-code {
        display: block;

        font-weight: 600;

        white-space: nowrap;
    }

    .asset-name {
        display: block;

        max-width: 220px;

        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;

        color: #6c757d;

        font-size: 12px;
    }

    .maintenance-code {
        white-space: nowrap;
        font-weight: 600;
    }

    .maintenance-date {
        white-space: nowrap;
    }

    .technician-name {
        white-space: nowrap;
    }

    .vendor-name {
        max-width: 160px;

        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }


    /* =====================================================
       ACTION BUTTON
    ====================================================== */

    .btn-history-detail {
        width: 34px;
        height: 34px;

        padding: 0;

        display: inline-flex;
        align-items: center;
        justify-content: center;

        border-radius: 7px;
    }


    /* =====================================================
       MODAL
    ====================================================== */

    .maintenance-detail-modal .modal-content {
        border: 0;
        border-radius: 10px;
        overflow: hidden;
    }

    .maintenance-detail-modal .modal-header {
        padding: 18px 20px;
    }

    .maintenance-detail-modal .modal-body {
        padding: 20px;
    }

    .maintenance-detail-modal .modal-footer {
        padding: 15px 20px;
    }

    .detail-section-title {
        font-size: 15px;
        font-weight: 700;

        margin-bottom: 15px;
    }

    .detail-item {
        height: 100%;
    }

    .detail-item label,
    .detail-label {
        display: block;

        margin-bottom: 5px;

        font-size: 12px;
        font-weight: 600;

        color: #6c757d;
    }

    .detail-value {
        font-weight: 500;

        word-break: break-word;
    }

    .detail-box {
        padding: 12px 14px;

        background: #f8f9fa;

        border-radius: 7px;

        line-height: 1.6;

        word-break: break-word;
    }


    /* =====================================================
       PROGRESS HISTORY
    ====================================================== */

    .progress-history-item {
        padding: 15px;

        margin-bottom: 15px;

        border: 1px solid #e9ecef;

        border-radius: 8px;

        background: #fff;
    }

    .progress-history-item:last-child {
        margin-bottom: 0;
    }

    .progress-user {
        font-weight: 600;
    }

    .progress-date {
        font-size: 12px;
        color: #6c757d;
    }


    /* =====================================================
       PHOTO
    ====================================================== */

    .maintenance-photo {
        width: 100%;
        height: 180px;

        object-fit: cover;

        display: block;

        border-radius: 7px;
    }

    .photo-wrapper {
        overflow: hidden;

        border-radius: 7px;

        border: 1px solid #e9ecef;

        background: #fff;
    }

    .photo-wrapper a {
        display: block;
    }

    .photo-wrapper img {
        transition: transform .2s ease;
    }

    .photo-wrapper a:hover img {
        transform: scale(1.03);
    }

    .empty-photo {
        padding: 25px;

        text-align: center;

        color: #6c757d;

        border: 1px dashed #dee2e6;

        border-radius: 8px;
    }

    .empty-photo i {
        font-size: 28px;

        margin-bottom: 8px;
    }


    /* =====================================================
       RESPONSIVE
    ====================================================== */

    @media (max-width: 767.98px) {

        .history-summary-card {
            margin-bottom: 0;
        }

        .maintenance-detail-modal .modal-dialog {
            margin: .5rem;
        }

        .maintenance-detail-modal .modal-body {
            padding: 15px;
        }

        .maintenance-detail-modal .modal-header {
            padding: 15px;
        }

        .maintenance-detail-modal .modal-footer {
            padding: 12px 15px;
        }

        .maintenance-photo {
            height: 160px;
        }

        .asset-name {
            max-width: 160px;
        }

    }

</style>

<div class="content-wrapper">

```
<!-- Content -->

<div class="container-xxl flex-grow-1 container-p-y">

    {{-- =====================================================
         PAGE HEADER
    ====================================================== --}}
    <div class="row">

        <div class="col-12">

            <div class="card">

                <div class="card-header d-flex justify-content-between align-items-center">

                    <div>

                        <h5 class="mb-0">
                            Maintenance History
                        </h5>

                        <small class="text-muted">
                            Riwayat maintenance yang sudah selesai
                        </small>

                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- =====================================================
         SUMMARY
    ====================================================== --}}
    <div class="row mt-3 g-3">

        {{-- TOTAL HISTORY --}}
        <div class="col-12 col-md-4">

            <div class="card history-summary-card">

                <div class="card-body">

                    <div class="d-flex align-items-center">

                        <div class="history-summary-icon bg-primary-subtle me-3">

                            <i class="fa-solid fa-clock-rotate-left text-primary"></i>

                        </div>

                        <div>

                            <small class="text-muted">
                                Total History
                            </small>

                            <h4 class="mb-0 fw-bold">
                                {{ $requests->count() }}
                            </h4>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        {{-- COMPLETED --}}
        <div class="col-12 col-md-4">

            <div class="card history-summary-card">

                <div class="card-body">

                    <div class="d-flex align-items-center">

                        <div class="history-summary-icon bg-success-subtle me-3">

                            <i class="fa-solid fa-circle-check text-success"></i>

                        </div>

                        <div>

                            <small class="text-muted">
                                Completed
                            </small>

                            <h4 class="mb-0 fw-bold">
                                {{ $requests->where('status', 'completed')->count() }}
                            </h4>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        {{-- TOTAL COST --}}
        <div class="col-12 col-md-4">

            <div class="card history-summary-card">

                <div class="card-body">

                    <div class="d-flex align-items-center">

                        <div class="history-summary-icon bg-warning-subtle me-3">

                            <i class="fa-solid fa-money-bill-wave text-warning"></i>

                        </div>

                        <div>

                            <small class="text-muted">
                                Total Cost
                            </small>

                            <h4 class="mb-0 fw-bold">

                                Rp {{ number_format(
                                    $requests->sum(
                                        fn ($request) =>
                                            (float) optional($request->maintenance)->cost
                                    ),
                                    0,
                                    ',',
                                    '.'
                                ) }}

                            </h4>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- =====================================================
         HISTORY TABLE
    ====================================================== --}}
    <div class="row mt-3">

        <div class="col-12">

            <div class="card">

                <div class="card-header">

                    <div>

                        <h5 class="mb-0">
                            Maintenance History
                        </h5>

                        <small class="text-muted">
                            Daftar maintenance yang telah selesai
                        </small>

                    </div>

                </div>


                <div class="card-body">

                    <div class="table-responsive">

                        <table
                            class="table table-hover align-middle"
                            id="maintenanceHistoryTable"
                            width="100%"
                        >

                            <thead>

                                <tr>

                                    <th width="5%">
                                        No
                                    </th>

                                    <th>
                                        Maintenance Code
                                    </th>

                                    <th>
                                        Asset
                                    </th>

                                    <th>
                                        Type
                                    </th>

                                    <th>
                                        Maintenance Date
                                    </th>

                                    <th>
                                        Technician
                                    </th>

                                    <th>
                                        Vendor
                                    </th>

                                    <th>
                                        Cost
                                    </th>

                                    <th>
                                        Status
                                    </th>

                                    <th width="10%">
                                        Action
                                    </th>

                                </tr>

                            </thead>


                            <tbody>

                                @foreach($requests as $request)

                                    @php

                                        $maintenance = $request->maintenance;

                                        $typeLabel = match(
                                            $maintenance?->maintenance_type
                                        ) {

                                            'preventive' =>
                                                'Preventive',

                                            'corrective' =>
                                                'Corrective',

                                            'inspection' =>
                                                'Inspection',

                                            'calibration' =>
                                                'Calibration',

                                            default =>
                                                ucfirst(
                                                    $maintenance?->maintenance_type ?? '-'
                                                ),

                                        };

                                    @endphp


                                    <tr>

                                        {{-- NO --}}
                                        <td>
                                            {{ $loop->iteration }}
                                        </td>


                                        {{-- MAINTENANCE CODE --}}
                                        <td>

                                            <span class="maintenance-code">

                                                {{ $maintenance?->maintenance_code ?? '-' }}

                                            </span>

                                        </td>


                                        {{-- ASSET --}}
                                        <td>

                                            @if($request->asset)

                                                <div class="asset-info">

                                                    <span class="asset-code">

                                                        {{ $request->asset->asset_code ?? '-' }}

                                                    </span>

                                                    <span
                                                        class="asset-name"
                                                        title="{{ $request->asset->name ?? '' }}"
                                                    >

                                                        {{ $request->asset->name ?? '-' }}

                                                    </span>

                                                </div>

                                            @else

                                                <span class="text-muted">
                                                    -
                                                </span>

                                            @endif

                                        </td>


                                        {{-- TYPE --}}
                                        <td>

                                            @if($maintenance)

                                                <span class="badge bg-info-subtle text-info">

                                                    {{ $typeLabel }}

                                                </span>

                                            @else

                                                <span class="text-muted">
                                                    -
                                                </span>

                                            @endif

                                        </td>


                                        {{-- DATE --}}
                                        <td>

                                            @if($maintenance?->maintenance_date)

                                                <span class="maintenance-date">

                                                    {{ \Carbon\Carbon::parse(
                                                        $maintenance->maintenance_date
                                                    )->format('d M Y') }}

                                                </span>

                                            @else

                                                <span class="text-muted">
                                                    -
                                                </span>

                                            @endif

                                        </td>


                                        {{-- TECHNICIAN --}}
                                        <td>

                                            <span class="technician-name">

                                                {{ $maintenance?->technician_name ?? '-' }}

                                            </span>

                                        </td>


                                        {{-- VENDOR --}}
                                        <td>

                                            @if($maintenance?->vendor)

                                                <span
                                                    class="vendor-name d-block"
                                                    title="{{ $maintenance->vendor->name }}"
                                                >

                                                    {{ $maintenance->vendor->name }}

                                                </span>

                                            @else

                                                <span class="text-muted">
                                                    -
                                                </span>

                                            @endif

                                        </td>


                                        {{-- COST --}}
                                        <td>

                                            <span class="text-nowrap">

                                                Rp {{ number_format(
                                                    $maintenance?->cost ?? 0,
                                                    0,
                                                    ',',
                                                    '.'
                                                ) }}

                                            </span>

                                        </td>


                                        {{-- STATUS --}}
                                        <td>

                                            <span class="badge bg-success">

                                                Completed

                                            </span>

                                        </td>


                                        {{-- ACTION --}}
                                        <td>

                                            <button
                                                type="button"
                                                class="btn btn-sm btn-light btn-history-detail"
                                                data-bs-toggle="modal"
                                                data-bs-target="#maintenanceDetailModal{{ $request->id }}"
                                                title="View Detail"
                                                aria-label="View maintenance detail"
                                            >

                                                <i class="fa-solid fa-eye"></i>

                                            </button>

                                        </td>

                                    </tr>


                                @endforeach

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>
```

</div>

{{-- =========================================================
DETAIL MODALS
========================================================= --}}

@foreach($requests as $request)

```
@php

    $maintenance = $request->maintenance;

    $typeLabel = match(
        $maintenance?->maintenance_type
    ) {

        'preventive' =>
            'Preventive',

        'corrective' =>
            'Corrective',

        'inspection' =>
            'Inspection',

        'calibration' =>
            'Calibration',

        default =>
            ucfirst(
                $maintenance?->maintenance_type ?? '-'
            ),

    };

@endphp


<div
    class="modal fade maintenance-detail-modal"
    id="maintenanceDetailModal{{ $request->id }}"
    tabindex="-1"
    aria-labelledby="maintenanceDetailModalLabel{{ $request->id }}"
    aria-hidden="true"
    role="dialog"
>

    <div
        class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"
        role="document"
    >

        <div class="modal-content">


            {{-- MODAL HEADER --}}
            <div class="modal-header">

                <div>

                    <h5
                        class="modal-title fw-bold"
                        id="maintenanceDetailModalLabel{{ $request->id }}"
                    >

                        Maintenance Detail

                    </h5>

                    <small class="text-muted">

                        {{ $maintenance?->maintenance_code ?? '-' }}

                    </small>

                </div>


                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Close"
                ></button>

            </div>


            {{-- MODAL BODY --}}
            <div class="modal-body">


                {{-- =====================================================
                     MAINTENANCE INFORMATION
                ====================================================== --}}
                <div class="mb-4">

                    <h6 class="detail-section-title">
                        Maintenance Information
                    </h6>


                    <div class="row g-3">

                        {{-- CODE --}}
                        <div class="col-12 col-md-6">

                            <div class="detail-item">

                                <label>
                                    Maintenance Code
                                </label>

                                <div class="detail-value">

                                    {{ $maintenance?->maintenance_code ?? '-' }}

                                </div>

                            </div>

                        </div>


                        {{-- STATUS --}}
                        <div class="col-12 col-md-6">

                            <div class="detail-item">

                                <label>
                                    Status
                                </label>

                                <div>

                                    <span class="badge bg-success">
                                        Completed
                                    </span>

                                </div>

                            </div>

                        </div>


                        {{-- ASSET --}}
                        <div class="col-12 col-md-6">

                            <div class="detail-item">

                                <label>
                                    Asset
                                </label>

                                @if($request->asset)

                                    <div class="detail-value">

                                        {{ $request->asset->asset_code }}

                                    </div>

                                    <small class="text-muted">

                                        {{ $request->asset->name }}

                                    </small>

                                @else

                                    <span class="text-muted">
                                        -
                                    </span>

                                @endif

                            </div>

                        </div>


                        {{-- TYPE --}}
                        <div class="col-12 col-md-6">

                            <div class="detail-item">

                                <label>
                                    Maintenance Type
                                </label>

                                <div class="detail-value">

                                    {{ $typeLabel }}

                                </div>

                            </div>

                        </div>


                        {{-- DATE --}}
                        <div class="col-12 col-md-6">

                            <div class="detail-item">

                                <label>
                                    Maintenance Date
                                </label>

                                <div class="detail-value">

                                    @if($maintenance?->maintenance_date)

                                        {{ \Carbon\Carbon::parse(
                                            $maintenance->maintenance_date
                                        )->format('d F Y') }}

                                    @else

                                        -

                                    @endif

                                </div>

                            </div>

                        </div>


                        {{-- TECHNICIAN --}}
                        <div class="col-12 col-md-6">

                            <div class="detail-item">

                                <label>
                                    Technician
                                </label>

                                <div class="detail-value">

                                    {{ $maintenance?->technician_name ?? '-' }}

                                </div>

                            </div>

                        </div>


                        {{-- VENDOR --}}
                        <div class="col-12 col-md-6">

                            <div class="detail-item">

                                <label>
                                    Vendor
                                </label>

                                <div class="detail-value">

                                    {{ $maintenance?->vendor?->name ?? '-' }}

                                </div>

                            </div>

                        </div>


                        {{-- COST --}}
                        <div class="col-12 col-md-6">

                            <div class="detail-item">

                                <label>
                                    Cost
                                </label>

                                <div class="detail-value">

                                    Rp {{ number_format(
                                        $maintenance?->cost ?? 0,
                                        0,
                                        ',',
                                        '.'
                                    ) }}

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                <hr>


                {{-- =====================================================
                     REQUEST INFORMATION
                ====================================================== --}}
                <div class="mb-4">

                    <h6 class="detail-section-title">
                        Request Information
                    </h6>


                    <div class="row g-3">


                        {{-- REQUEST TYPE --}}
                        <div class="col-12 col-md-6">

                            <div class="detail-item">

                                <label>
                                    Request Type
                                </label>

                                <div class="detail-value">

                                    {{
                                        ucfirst(
                                            str_replace(
                                                '_',
                                                ' ',
                                                $request->request_type ?? '-'
                                            )
                                        )
                                    }}

                                </div>

                            </div>

                        </div>


                        {{-- REQUESTED BY --}}
                        <div class="col-12 col-md-6">

                            <div class="detail-item">

                                <label>
                                    Requested By
                                </label>

                                <div class="detail-value">

                                    {{ $request->requester?->name ?? '-' }}

                                </div>

                            </div>

                        </div>


                        {{-- HANDLED BY --}}
                        <div class="col-12 col-md-6">

                            <div class="detail-item">

                                <label>
                                    Handled By
                                </label>

                                <div class="detail-value">

                                    {{ $request->handler?->name ?? '-' }}

                                </div>

                            </div>

                        </div>


                        {{-- COMPLETED AT --}}
                        <div class="col-12 col-md-6">

                            <div class="detail-item">

                                <label>
                                    Completed At
                                </label>

                                <div class="detail-value">

                                    @if($request->completed_at)

                                        {{ \Carbon\Carbon::parse(
                                            $request->completed_at
                                        )->format('d F Y H:i')
                                        }}

                                    @else

                                        -

                                    @endif

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                {{-- =====================================================
                     PROBLEM / DESCRIPTION
                ====================================================== --}}
                <div class="mb-4">

                    <label class="detail-label">
                        Problem / Description
                    </label>

                    <div class="detail-box">

                        {!! nl2br(
                            e(
                                $maintenance?->problem_description
                                ?? $request->description
                                ?? '-'
                            )
                        ) !!}

                    </div>

                </div>


                {{-- =====================================================
                     ACTION TAKEN
                ====================================================== --}}
                <div class="mb-4">

                    <label class="detail-label">
                        Action Taken
                    </label>

                    <div class="detail-box">

                        {!! nl2br(
                            e(
                                $maintenance?->action_taken ?? '-'
                            )
                        ) !!}

                    </div>

                </div>


                {{-- =====================================================
                     NOTES
                ====================================================== --}}
                @if($maintenance?->notes)

                    <div class="mb-4">

                        <label class="detail-label">
                            Notes
                        </label>

                        <div class="detail-box">

                            {!! nl2br(
                                e(
                                    $maintenance->notes
                                )
                            ) !!}

                        </div>

                    </div>

                @endif


                {{-- =====================================================
                     NEXT MAINTENANCE
                ====================================================== --}}
                @if($maintenance?->next_maintenance_date)

                    <div class="alert alert-info">

                        <div class="d-flex align-items-start gap-2">

                            <i class="fa-solid fa-calendar-days mt-1"></i>

                            <div>

                                <strong>
                                    Next Maintenance
                                </strong>

                                <div class="mt-1">

                                    {{
                                        \Carbon\Carbon::parse(
                                            $maintenance->next_maintenance_date
                                        )->format('d F Y')
                                    }}

                                </div>

                            </div>

                        </div>

                    </div>

                @endif


                {{-- =====================================================
                     WORK PROGRESS
                ====================================================== --}}
                @if(
                    $request->logs &&
                    $request->logs->count()
                )

                    <hr>

                    <div class="mb-4">

                        <h6 class="detail-section-title">
                            Work Progress
                        </h6>


                        @foreach($request->logs as $log)

                            <div class="progress-history-item">

                                <div>

                                    <div class="progress-user">

                                        {{ $log->user?->name ?? 'Unknown User' }}

                                    </div>


                                    @if($log->created_at)

                                        <div class="progress-date">

                                            {{ $log->created_at->format('d M Y H:i') }}

                                        </div>

                                    @endif

                                </div>


                                <div class="mt-3">

                                    {!! nl2br(
                                        e(
                                            $log->note
                                        )
                                    ) !!}

                                </div>


                                {{-- PROGRESS PHOTOS --}}
                                @if(
                                    $log->photos &&
                                    $log->photos->count()
                                )

                                    <div class="row g-3 mt-1">

                                        @foreach($log->photos as $photo)

                                            <div class="col-12 col-sm-6 col-md-4">

                                                <div class="photo-wrapper">

                                                    <a
                                                        href="{{ asset('storage/' . $photo->file_path) }}"
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                    >

                                                        <img
                                                            src="{{ asset('storage/' . $photo->file_path) }}"
                                                            class="maintenance-photo"
                                                            alt="Progress Photo"
                                                            loading="lazy"
                                                        >

                                                    </a>

                                                </div>

                                                @if($photo->caption)

                                                    <small class="text-muted d-block mt-1">

                                                        {{ $photo->caption }}

                                                    </small>

                                                @endif

                                            </div>

                                        @endforeach

                                    </div>

                                @endif

                            </div>

                        @endforeach

                    </div>

                @endif


                {{-- =====================================================
                     MAINTENANCE RESULT PHOTOS
                ====================================================== --}}
                <hr>

                <div>

                    <h6 class="detail-section-title">
                        Maintenance Result Photos
                    </h6>


                    @if(
                        $maintenance &&
                        $maintenance->photos &&
                        $maintenance->photos->count()
                    )

                        <div class="row g-3">

                            @foreach($maintenance->photos as $photo)

                                <div class="col-12 col-sm-6 col-md-4">

                                    <div class="photo-wrapper">

                                        <a
                                            href="{{ asset('storage/' . $photo->file_path) }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                        >

                                            <img
                                                src="{{ asset('storage/' . $photo->file_path) }}"
                                                class="maintenance-photo"
                                                alt="Maintenance Result Photo"
                                                loading="lazy"
                                            >

                                        </a>

                                    </div>


                                    <div class="mt-1">

                                        <small class="text-muted">

                                            {{
                                                ucfirst(
                                                    $photo->photo_type ?? 'Photo'
                                                )
                                            }}

                                        </small>

                                    </div>

                                </div>

                            @endforeach

                        </div>

                    @else

                        <div class="empty-photo">

                            <i class="fa-regular fa-image d-block"></i>

                            <div>
                                No maintenance photos.
                            </div>

                        </div>

                    @endif

                </div>


            </div>


            {{-- MODAL FOOTER --}}
            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-label-secondary"
                    data-bs-dismiss="modal"
                >

                    Close

                </button>

            </div>


        </div>

    </div>

</div>
```

@endforeach

@endsection

@section('js')

<script>

$(function () {

    $('#maintenanceHistoryTable').DataTable({

        processing: true,

        serverSide: false,

        responsive: true,

        autoWidth: false,

        pageLength: 10,

        lengthMenu: [
            [10, 25, 50, 100],
            [10, 25, 50, 100]
        ],

        order: [
            [4, 'desc']
        ],

        columnDefs: [

            {
                orderable: false,
                searchable: false,
                targets: [0, 9]
            }

        ],

        language: {

            search: 'Search:',

            searchPlaceholder:
                'Search maintenance history...',

            lengthMenu:
                'Show _MENU_ entries',

            info:
                'Showing _START_ to _END_ of _TOTAL_ entries',

            infoEmpty:
                'Showing 0 to 0 of 0 entries',

            zeroRecords:
                'No maintenance history found',

            emptyTable:
                'No maintenance history available',

            paginate: {

                first: 'First',

                last: 'Last',

                next: 'Next',

                previous: 'Previous'

            }

        }

    });

});

</script>

@endsection
