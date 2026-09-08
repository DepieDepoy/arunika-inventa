@extends('dashboard.layouts.wrapper')

@section('title', 'Import History Detail')

@section('content')

<div class="container-fluid">

```
{{-- =========================================================
     HEADER
========================================================== --}}

<div class="d-flex justify-content-between align-items-center mb-4">

    <div>
        <h4 class="mb-1">
            Import History Detail
        </h4>

        <p class="text-muted mb-0">
            Detail hasil proses import user
        </p>
    </div>

    <a
        href="{{ route('users.import.history') }}"
        class="btn btn-outline-secondary"
    >
        <i class="fas fa-arrow-left me-1"></i>
        Kembali
    </a>

</div>


{{-- =========================================================
     IMPORT SUMMARY
========================================================== --}}

<div class="row g-3 mb-4">

    {{-- FILE --}}

    <div class="col-md-4">

        <div class="card h-100">

            <div class="card-body">

                <div class="d-flex align-items-center">

                    <div
                        class="rounded bg-success bg-opacity-10 p-3 me-3"
                    >
                        <i
                            class="fas fa-file-excel fa-lg text-success"
                        ></i>
                    </div>

                    <div class="overflow-hidden">

                        <small class="text-muted">
                            File Import
                        </small>

                        <div
                            class="fw-semibold text-truncate"
                            title="{{ $history->file_name }}"
                        >
                            {{ $history->file_name }}
                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- TOTAL --}}

    <div class="col-md-2">

        <div class="card h-100">

            <div class="card-body">

                <small class="text-muted">
                    Total
                </small>

                <h4 class="mb-0 mt-1">
                    {{ number_format($history->total_rows) }}
                </h4>

            </div>

        </div>

    </div>


    {{-- SUCCESS --}}

    <div class="col-md-2">

        <div class="card h-100">

            <div class="card-body">

                <small class="text-muted">
                    Berhasil
                </small>

                <h4 class="mb-0 mt-1 text-success">
                    {{ number_format($history->success_rows) }}
                </h4>

            </div>

        </div>

    </div>


    {{-- FAILED --}}

    <div class="col-md-2">

        <div class="card h-100">

            <div class="card-body">

                <small class="text-muted">
                    Gagal
                </small>

                <h4 class="mb-0 mt-1 text-danger">
                    {{ number_format($history->failed_rows) }}
                </h4>

            </div>

        </div>

    </div>


    {{-- STATUS --}}

    <div class="col-md-2">

        <div class="card h-100">

            <div class="card-body">

                <small class="text-muted">
                    Status
                </small>

                <div class="mt-2">

                    @if($history->status === 'processing')

                        <span class="badge bg-warning text-dark">

                            <i
                                class="fas fa-spinner fa-spin me-1"
                            ></i>

                            Processing

                        </span>

                    @elseif($history->status === 'completed')

                        <span class="badge bg-success">

                            <i class="fas fa-check me-1"></i>

                            Completed

                        </span>

                    @elseif($history->status === 'completed_with_errors')

                        <span class="badge bg-warning text-dark">

                            <i
                                class="
                                    fas
                                    fa-exclamation-triangle
                                    me-1
                                "
                            ></i>

                            Completed with Errors

                        </span>

                    @else

                        <span class="badge bg-danger">

                            <i class="fas fa-times me-1"></i>

                            Failed

                        </span>

                    @endif

                </div>

            </div>

        </div>

    </div>

</div>


{{-- =========================================================
     PROCESS INFORMATION
========================================================== --}}

<div class="card mb-4">

    <div class="card-header">

        <h5 class="mb-0">

            <i class="fas fa-clock me-2"></i>

            Informasi Proses

        </h5>

    </div>

    <div class="card-body">

        <div class="row">

            {{-- CREATED --}}

            <div class="col-md-3 mb-3">

                <small class="text-muted d-block">
                    Dibuat
                </small>

                <strong>
                    {{ $history->created_at?->format('d M Y H:i:s') ?? '-' }}
                </strong>

            </div>


            {{-- STARTED --}}

            <div class="col-md-3 mb-3">

                <small class="text-muted d-block">
                    Mulai
                </small>

                <strong>
                    {{ $history->started_at?->format('d M Y H:i:s') ?? '-' }}
                </strong>

            </div>


            {{-- FINISHED --}}

            <div class="col-md-3 mb-3">

                <small class="text-muted d-block">
                    Selesai
                </small>

                <strong>
                    {{ $history->finished_at?->format('d M Y H:i:s') ?? '-' }}
                </strong>

            </div>


            {{-- DURATION --}}

            <div class="col-md-3 mb-3">

                <small class="text-muted d-block">
                    Durasi
                </small>

                <strong>

                    @if($history->started_at)

                        @php

                            $endTime =
                                $history->finished_at ?? now();

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

                        @endphp


                        @if($hours > 0)

                            {{ $hours }} jam

                            @if($minutes > 0)
                                {{ $minutes }} menit
                            @endif

                            @if($seconds > 0)
                                {{ $seconds }} detik
                            @endif

                        @elseif($minutes > 0)

                            {{ $minutes }} menit

                            @if($seconds > 0)
                                {{ $seconds }} detik
                            @endif

                        @else

                            {{ $seconds }} detik

                        @endif


                        @if($history->status === 'processing')

                            <span class="text-warning">
                                (berjalan)
                            </span>

                        @endif

                    @else

                        -

                    @endif

                </strong>

            </div>

        </div>

    </div>

</div>


{{-- =========================================================
     DETAIL ERROR
========================================================== --}}

<div class="card">

    <div class="card-header">

        <div
            class="
                d-flex
                justify-content-between
                align-items-center
            "
        >

            <div>

                <h5 class="mb-1">

                    <i
                        class="
                            fas
                            fa-exclamation-circle
                            text-danger
                            me-2
                        "
                    ></i>

                    Detail Error

                </h5>

                <small class="text-muted">
                    Daftar row yang gagal diimport
                </small>

            </div>


            {{-- TOTAL ERROR --}}

            @if((int) $history->failed_rows > 0)

                <span class="badge bg-danger">

                    {{ number_format($history->failed_rows) }}

                    Error

                </span>

            @endif

        </div>

    </div>


    <div class="card-body">

        {{-- =====================================================
             ADA ERROR
        ====================================================== --}}

        @if((int) $history->failed_rows > 0)

            <div class="table-responsive">

                <table
                    id="import-error-table"
                    class="
                        table
                        table-hover
                        align-middle
                        w-100
                    "
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

            {{-- =================================================
                 NO ERROR
            ================================================== --}}

            <div class="text-center py-5">

                <i
                    class="
                        fas
                        fa-check-circle
                        fa-3x
                        text-success
                        mb-3
                    "
                ></i>

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
```

</div>

{{-- =============================================================
SERVER-SIDE DATATABLE
============================================================= --}}

@if((int) $history->failed_rows > 0)

<script>

document.addEventListener('DOMContentLoaded', function () {

    if (!document.getElementById('import-error-table')) {
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
            url: "{{ route('users.import.history.errors', $history->id) }}",
            type: 'GET'
        },

        columns: [

            {
                data: 'row_display',
                name: 'row_number',
                className: 'text-center',
                searchable: false,
                orderable: true,

                render: function (data) {

                    if (!data) {
                        return '<span class="text-muted">-</span>';
                    }

                    return '<span class="badge bg-light text-dark">'
                        + data
                        + '</span>';
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

            processing: 'Memuat data...',

            search: 'Cari:',

            lengthMenu: 'Tampilkan _MENU_ data',

            info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ error',

            infoEmpty: 'Menampilkan 0 sampai 0 dari 0 error',

            infoFiltered: '(difilter dari _MAX_ total error)',

            zeroRecords: 'Data error tidak ditemukan',

            emptyTable: 'Tidak ada data error',

            paginate: {

                first: 'Pertama',

                last: 'Terakhir',

                next: '›',

                previous: '‹'

            }

        }

    });

});

</script>

@endif

@endsection
