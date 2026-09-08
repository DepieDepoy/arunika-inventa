@extends('dashboard.layouts.wrapper')

@section('title', 'Import History Detail')

@section('content')

@php
/*
|--------------------------------------------------------------------------
| Import Summary
|--------------------------------------------------------------------------
*/

$module = strtolower(
    (string) ($history->module ?? '')
);

$moduleIcon = match ($module) {
    'user' => 'fa-users',
    'asset' => 'fa-boxes',
    'vendor' => 'fa-building',
    'category' => 'fa-tags',
    'subcategory' => 'fa-tag',
    default => 'fa-file-import',
};

$total = (int) ($history->total_rows ?? 0);

$success = (int) ($history->success_rows ?? 0);

$failed = (int) ($history->failed_rows ?? 0);

$processed = $success + $failed;

$progress = $total > 0
    ? min(
        round(
            ($processed / $total) * 100
        ),
        100
    )
    : 0;

/*
|--------------------------------------------------------------------------
| Duration
|--------------------------------------------------------------------------
*/

$durationText = '-';

if ($history->started_at) {

    $endTime =
        $history->finished_at
        ?? now();

    $durationSeconds =
        $history->started_at
            ->diffInSeconds($endTime);

    $hours =
        intdiv(
            $durationSeconds,
            3600
        );

    $minutes =
        intdiv(
            $durationSeconds % 3600,
            60
        );

    $seconds =
        $durationSeconds % 60;

    $durationParts = [];

    if ($hours > 0) {
        $durationParts[] =
            $hours . ' jam';
    }

    if ($minutes > 0) {
        $durationParts[] =
            $minutes . ' menit';
    }

    if ($seconds > 0 || empty($durationParts)) {
        $durationParts[] =
            $seconds . ' detik';
    }

    $durationText =
        implode(
            ' ',
            $durationParts
        );
}

@endphp

<style>
    /*
    |--------------------------------------------------------------------------
    | Import History Detail
    |--------------------------------------------------------------------------
    */

    .import-detail-header {
        margin-bottom: 1.5rem;
    }

    .import-detail-header h4 {
        font-weight: 600;
        margin-bottom: 0.35rem;
    }

    .import-detail-header p {
        margin-bottom: 0;
    }

    .import-summary-card {
        height: 100%;
        border: 1px solid #e9ecef;
        border-radius: 12px;
        transition: all 0.2s ease;
    }

    .import-summary-card:hover {
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.06);
    }

    .import-summary-card .card-body {
        padding: 1.25rem;
    }

    .import-summary-icon {
        width: 48px;
        height: 48px;
        min-width: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        background: rgba(25, 135, 84, 0.10);
    }

    .import-summary-label {
        display: block;
        font-size: 0.78rem;
        color: #6c757d;
        margin-bottom: 0.25rem;
    }

    .import-summary-value {
        font-size: 1.25rem;
        font-weight: 600;
        line-height: 1.3;
    }

    .import-file-name {
        font-size: 0.95rem;
        font-weight: 600;
    }

    .import-module-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 0.45rem 0.65rem;
        border-radius: 7px;
        font-weight: 500;
    }

    .import-status-card,
    .import-result-card,
    .import-error-card {
        border: 1px solid #e9ecef;
        border-radius: 12px;
        overflow: hidden;
    }

    .import-status-card .card-header,
    .import-result-card .card-header,
    .import-error-card .card-header {
        background: #fff;
        border-bottom: 1px solid #e9ecef;
        padding: 1rem 1.25rem;
    }

    .import-status-card .card-body,
    .import-result-card .card-body,
    .import-error-card .card-body {
        padding: 1.25rem;
    }

    .import-section-title {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 1rem;
        font-weight: 600;
        margin: 0;
    }

    .import-info-label {
        display: block;
        font-size: 0.78rem;
        color: #6c757d;
        margin-bottom: 0.25rem;
    }

    .import-info-value {
        font-weight: 600;
    }

    .import-progress {
        height: 10px;
        border-radius: 10px;
        overflow: hidden;
        background: #e9ecef;
    }

    .import-progress-bar {
        height: 100%;
        border-radius: 10px;
        transition: width 0.3s ease;
    }

    .import-result-box {
        border: 1px solid #e9ecef;
        border-radius: 10px;
        padding: 1rem;
        background: #fff;
    }

    .import-result-box-label {
        display: block;
        font-size: 0.78rem;
        color: #6c757d;
        margin-bottom: 0.25rem;
    }

    .import-result-box-value {
        font-size: 1.2rem;
        font-weight: 600;
    }

    .import-empty-state {
        padding: 3rem 1rem;
        text-align: center;
    }

    .import-empty-state i {
        margin-bottom: 1rem;
    }

    .import-error-table-wrap {
        width: 100%;
        overflow-x: auto;
    }

    #import-error-table {
        width: 100% !important;
    }

    #import-error-table thead th {
        white-space: nowrap;
        vertical-align: middle;
    }

    #import-error-table tbody td {
        vertical-align: top;
    }

    .import-error-data {
        max-width: 500px;
        max-height: 160px;
        overflow: auto;
        white-space: pre-wrap;
        word-break: break-word;
        margin: 0;
        padding: 0.75rem;
        border-radius: 7px;
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        font-size: 0.78rem;
        line-height: 1.45;
    }

    .import-error-message {
        color: #dc3545;
        line-height: 1.5;
    }

    .import-row-badge {
        display: inline-block;
        min-width: 42px;
        padding: 0.35rem 0.5rem;
        border-radius: 6px;
        font-size: 0.78rem;
        font-weight: 600;
        text-align: center;
        background: #f8f9fa;
        color: #212529;
        border: 1px solid #dee2e6;
    }

    .import-back-button {
        white-space: nowrap;
    }

    @media (max-width: 767.98px) {

        .import-detail-header {
            align-items: flex-start !important;
            gap: 1rem;
        }

        .import-back-button {
            flex-shrink: 0;
        }

        .import-summary-value {
            font-size: 1.1rem;
        }

    }
</style>

<div class="container-fluid">


{{-- =========================================================
     HEADER
========================================================== --}}
<div class="import-detail-header d-flex justify-content-between align-items-center">

    <div>

        <h4>
            Import History Detail
        </h4>

        <p class="text-muted">
            Detail hasil proses import
            {{ ucfirst($module ?: '-') }}
        </p>

    </div>


    <a
        href="{{ route('import.history') }}"
        class="btn btn-outline-secondary import-back-button"
    >
        <i class="fas fa-arrow-left me-1"></i>
        Kembali
    </a>

</div>


{{-- =========================================================
     SUMMARY CARDS
========================================================== --}}
<div class="row g-3 mb-4">

    {{-- FILE --}}
    <div class="col-lg-4 col-md-6">

        <div class="card import-summary-card">

            <div class="card-body">

                <div class="d-flex align-items-center">

                    <div class="import-summary-icon me-3">

                        <i class="fas fa-file-excel fa-lg text-success"></i>

                    </div>


                    <div class="overflow-hidden">

                        <span class="import-summary-label">
                            File Import
                        </span>

                        <div
                            class="import-file-name text-truncate"
                            title="{{ $history->file_name }}"
                        >
                            {{ $history->file_name }}
                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- MODULE --}}
    <div class="col-lg-2 col-md-6">

        <div class="card import-summary-card">

            <div class="card-body">

                <span class="import-summary-label">
                    Module
                </span>

                <div class="mt-2">

                    <span class="badge bg-light text-dark border import-module-badge">

                        <i class="fas {{ $moduleIcon }}"></i>

                        {{ ucfirst($module ?: '-') }}

                    </span>

                </div>

            </div>

        </div>

    </div>


    {{-- TOTAL --}}
    <div class="col-lg-2 col-md-4 col-6">

        <div class="card import-summary-card">

            <div class="card-body">

                <span class="import-summary-label">
                    Total
                </span>

                <div class="import-summary-value">
                    {{ number_format($total) }}
                </div>

            </div>

        </div>

    </div>


    {{-- SUCCESS --}}
    <div class="col-lg-2 col-md-4 col-6">

        <div class="card import-summary-card">

            <div class="card-body">

                <span class="import-summary-label">
                    Berhasil
                </span>

                <div class="import-summary-value text-success">
                    {{ number_format($success) }}
                </div>

            </div>

        </div>

    </div>


    {{-- FAILED --}}
    <div class="col-lg-2 col-md-4 col-6">

        <div class="card import-summary-card">

            <div class="card-body">

                <span class="import-summary-label">
                    Gagal
                </span>

                <div class="import-summary-value text-danger">
                    {{ number_format($failed) }}
                </div>

            </div>

        </div>

    </div>

</div>


{{-- =========================================================
     STATUS IMPORT
========================================================== --}}
<div class="card import-status-card mb-4">

    <div class="card-header">

        <div class="d-flex justify-content-between align-items-center">

            <h5 class="import-section-title">

                <i class="fas fa-info-circle"></i>

                Status Import

            </h5>


            @if($history->status === 'processing')

                <span class="badge bg-warning text-dark">

                    <i class="fas fa-spinner fa-spin me-1"></i>

                    Processing

                </span>

            @elseif($history->status === 'completed')

                <span class="badge bg-success">

                    <i class="fas fa-check me-1"></i>

                    Completed

                </span>

            @elseif($history->status === 'completed_with_errors')

                <span class="badge bg-warning text-dark">

                    <i class="fas fa-exclamation-triangle me-1"></i>

                    Completed with Errors

                </span>

            @elseif($history->status === 'failed')

                <span class="badge bg-danger">

                    <i class="fas fa-times me-1"></i>

                    Failed

                </span>

            @else

                <span class="badge bg-secondary">

                    {{ ucfirst(
                        str_replace(
                            '_',
                            ' ',
                            $history->status
                        )
                    ) }}

                </span>

            @endif

        </div>

    </div>


    <div class="card-body">

        <div class="row">

            {{-- CREATED --}}
            <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">

                <span class="import-info-label">
                    Dibuat
                </span>

                <div class="import-info-value">

                    {{ $history->created_at
                        ? $history->created_at->format('d M Y H:i:s')
                        : '-'
                    }}

                </div>

            </div>


            {{-- STARTED --}}
            <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">

                <span class="import-info-label">
                    Mulai
                </span>

                <div class="import-info-value">

                    {{ $history->started_at
                        ? $history->started_at->format('d M Y H:i:s')
                        : '-'
                    }}

                </div>

            </div>


            {{-- FINISHED --}}
            <div class="col-lg-3 col-md-6">

                <span class="import-info-label">
                    Selesai
                </span>

                <div class="import-info-value">

                    {{ $history->finished_at
                        ? $history->finished_at->format('d M Y H:i:s')
                        : '-'
                    }}

                </div>

            </div>


            {{-- DURATION --}}
            <div class="col-lg-3 col-md-6">

                <span class="import-info-label">
                    Durasi
                </span>

                <div class="import-info-value">

                    {{ $durationText }}

                    @if($history->status === 'processing' && $history->started_at)

                        <span class="text-warning">
                            (berjalan)
                        </span>

                    @endif

                </div>

            </div>

        </div>

    </div>

</div>


{{-- =========================================================
     RESULT SUMMARY
========================================================== --}}
<div class="card import-result-card mb-4">

    <div class="card-header">

        <h5 class="import-section-title">

            <i class="fas fa-chart-bar"></i>

            Ringkasan Hasil

        </h5>

    </div>


    <div class="card-body">

        <div class="mb-2 d-flex justify-content-between">

            <span class="text-muted">
                Progress
            </span>

            <strong>
                {{ $progress }}%
            </strong>

        </div>


        <div
            class="import-progress mb-4"
            role="progressbar"
            aria-valuenow="{{ $progress }}"
            aria-valuemin="0"
            aria-valuemax="100"
        >

            <div
                id="import-progress-bar"
                class="import-progress-bar"
                data-progress="{{ $progress }}"
            ></div>

        </div>


        <div class="row g-3 text-center">

            {{-- TOTAL --}}
            <div class="col-md-4">

                <div class="import-result-box">

                    <span class="import-result-box-label">
                        Total
                    </span>

                    <div class="import-result-box-value">
                        {{ number_format($total) }}
                    </div>

                </div>

            </div>


            {{-- SUCCESS --}}
            <div class="col-md-4">

                <div class="import-result-box">

                    <span class="import-result-box-label">
                        Berhasil
                    </span>

                    <div class="import-result-box-value text-success">
                        {{ number_format($success) }}
                    </div>

                </div>

            </div>


            {{-- FAILED --}}
            <div class="col-md-4">

                <div class="import-result-box">

                    <span class="import-result-box-label">
                        Gagal
                    </span>

                    <div class="import-result-box-value text-danger">
                        {{ number_format($failed) }}
                    </div>

                </div>

            </div>

        </div>

    </div>

</div>


{{-- =========================================================
     DETAIL ERROR
========================================================== --}}
<div class="card import-error-card">

    <div class="card-header">

        <div class="d-flex justify-content-between align-items-center">

            <div>

                <h5 class="import-section-title mb-1">

                    <i class="fas fa-exclamation-circle text-danger"></i>

                    Detail Error

                </h5>

                <small class="text-muted">
                    Daftar row yang gagal diimport
                </small>

            </div>


            @if($failed > 0)

                <span class="badge bg-danger">

                    {{ number_format($failed) }}

                    Error

                </span>

            @endif

        </div>

    </div>


    <div class="card-body">

        @if($failed > 0)

            <div class="import-error-table-wrap">

                <table
                    id="import-error-table"
                    class="table table-hover align-middle w-100"
                    data-url="{{ route('import.history.detail.errors', $history->id) }}"
                >

                    <thead>

                        <tr>

                            <th
                                class="text-center"
                                style="width: 80px;"
                            >
                                Row
                            </th>

                            <th>
                                Data
                            </th>

                            <th>
                                Error
                            </th>

                        </tr>

                    </thead>

                    <tbody>
                    </tbody>

                </table>

            </div>

        @else

            <div class="import-empty-state">

                <i class="fas fa-check-circle fa-3x text-success"></i>

                <h5>
                    Tidak ada error
                </h5>

                <p class="text-muted mb-0">
                    Semua data berhasil diproses.
                </p>

            </div>

        @endif

    </div>

</div>


</div>

{{-- =========================================================
DATATABLE ERROR
========================================================== --}}
@if($failed > 0)

<script>

    document.addEventListener('DOMContentLoaded', function () {

        const tableElement =
            document.getElementById(
                'import-error-table'
            );

        if (!tableElement) {
            return;
        }


        const errorDataUrl =
            tableElement.getAttribute(
                'data-url'
            );


        if (
            !errorDataUrl ||
            typeof $ === 'undefined' ||
            typeof $.fn.DataTable === 'undefined'
        ) {
            return;
        }


        $('#import-error-table').DataTable({

            processing: true,

            serverSide: true,

            responsive: false,

            autoWidth: false,

            pageLength: 10,

            lengthMenu: [
                [10, 25, 50, 100, 200],
                [10, 25, 50, 100, 200]
            ],

            ajax: {
                url: errorDataUrl,
                type: 'GET'
            },

            columns: [

                {
    data: null,
    name: 'id',
    className: 'text-center',
    searchable: false,
    orderable: false,

    render: function (data, type, row, meta) {
        return (
            '<span class="import-row-badge">'
            + (meta.row + meta.settings._iDisplayStart + 1) +
            '</span>'
        );
    }
},


                {
                    data: 'data_display',
                    name: 'data',
                    orderable: false,
                    searchable: true
                },


                {
                    data: 'error_display',
                    name: 'error_message',
                    orderable: true,
                    searchable: true
                }

            ],


            order: [
                [0, 'asc']
            ],


            language: {

                processing:
                    'Memuat data...',

                search:
                    'Cari:',

                lengthMenu:
                    'Tampilkan _MENU_ data',

                info:
                    'Menampilkan _START_ sampai _END_ dari _TOTAL_ error',

                infoEmpty:
                    'Menampilkan 0 sampai 0 dari 0 error',

                infoFiltered:
                    '(difilter dari _MAX_ total error)',

                zeroRecords:
                    'Data error tidak ditemukan',

                emptyTable:
                    'Tidak ada data error',

                paginate: {

                    first:
                        'Pertama',

                    last:
                        'Terakhir',

                    next:
                        '›',

                    previous:
                        '‹'

                }

            }

        });

    });

</script>

@endif

{{-- =========================================================
PROGRESS BAR INITIALIZATION
========================================================== --}}

<script>

    document.addEventListener('DOMContentLoaded', function () {

        const progressBar =
            document.getElementById(
                'import-progress-bar'
            );

        if (!progressBar) {
            return;
        }


        const progress =
            progressBar.getAttribute(
                'data-progress'
            );


        if (!progress) {
            progressBar.style.width = '0%';
            return;
        }


        progressBar.style.width =
            progress + '%';

    });

</script>

@endsection
