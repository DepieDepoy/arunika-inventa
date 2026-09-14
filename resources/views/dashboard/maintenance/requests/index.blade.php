@extends('dashboard.layouts.wrapper')

@section('title', 'Maintenance Requests')

@section('content')

<style>

    /* =========================================================
       SWEETALERT
    ========================================================== */

    .swal2-container {
        z-index: 99999 !important;
    }


    /* =========================================================
       DATATABLE RESPONSIVE
    ========================================================== */

    .table-responsive {
        overflow: visible !important;
    }


    /* =========================================================
       DROPDOWN
    ========================================================== */

    .dropdown-menu {
        z-index: 99999 !important;
    }


    /* =========================================================
       REQUEST DESCRIPTION
    ========================================================== */

    .maintenance-description {
        min-width: 220px;
        max-width: 350px;
        white-space: normal;
        word-break: break-word;
    }


    /* =========================================================
       ASSET NAME
    ========================================================== */

    .maintenance-asset-name {
        font-weight: 600;
        white-space: normal;
    }


    /* =========================================================
       RESPONSIVE
    ========================================================== */

    @media (max-width: 767.98px) {

        .maintenance-description {
            min-width: 180px;
            max-width: 250px;
        }

    }

</style>


<div class="content-wrapper">

    <!-- Content -->

    <div class="container-xxl flex-grow-1 container-p-y">

        {{-- =====================================================
             PAGE HEADER
        ====================================================== --}}

        <div class="row">

            <div class="col-xxl-12 mb-4">

                <div class="card">

                    <div class="card-header">

                        <div>

                            <h5 class="mb-1">
                                Maintenance Requests
                            </h5>

                            <small class="text-muted">
                                Daftar permintaan maintenance, repair, dan laporan masalah asset.
                            </small>

                        </div>

                    </div>


                    {{-- =================================================
                         SUMMARY
                    ================================================== --}}

                    <div class="card-body pb-0">

                        <div class="row">

                            {{-- TOTAL --}}
                            <div class="col-xl-3 col-md-6 mb-3">

                                <div class="card border shadow-none h-100">

                                    <div class="card-body">

                                        <small class="text-muted">
                                            Total Request
                                        </small>

                                        <h3 class="fw-bold mb-0">
                                            {{ $requests->count() }}
                                        </h3>

                                    </div>

                                </div>

                            </div>


                            {{-- PENDING --}}
                            <div class="col-xl-3 col-md-6 mb-3">

                                <div class="card border shadow-none h-100">

                                    <div class="card-body">

                                        <small class="text-muted">
                                            Pending
                                        </small>

                                        <h3 class="fw-bold text-warning mb-0">
                                            {{ $requests->where('status', 'pending')->count() }}
                                        </h3>

                                    </div>

                                </div>

                            </div>


                            {{-- IN PROGRESS --}}
                            <div class="col-xl-3 col-md-6 mb-3">

                                <div class="card border shadow-none h-100">

                                    <div class="card-body">

                                        <small class="text-muted">
                                            In Progress
                                        </small>

                                        <h3 class="fw-bold text-primary mb-0">
                                            {{ $requests->where('status', 'in_progress')->count() }}
                                        </h3>

                                    </div>

                                </div>

                            </div>


                            {{-- ACTIVE --}}
                            <div class="col-xl-3 col-md-6 mb-3">

                                <div class="card border shadow-none h-100">

                                    <div class="card-body">

                                        <small class="text-muted">
                                            Active Requests
                                        </small>

                                        <h3 class="fw-bold text-info mb-0">
                                            {{ $requests->count() }}
                                        </h3>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>


                    {{-- =================================================
                         REQUEST LIST
                    ================================================== --}}

                    <div class="card-body">

                        <div class="mb-3">

                            <h6 class="mb-1 fw-semibold">
                                Request List
                            </h6>

                            <small class="text-muted">
                                Request yang sedang menunggu atau sedang dalam proses.
                            </small>

                        </div>


                        <div class="table-responsive">

                            <table
                                class="table table-hover align-middle"
                                id="maintenanceRequestsTable"
                                width="100%"
                            >

                                <thead>

                                    <tr>

                                        <th width="5%">
                                            No
                                        </th>

                                        <th>
                                            Asset
                                        </th>

                                        <th>
                                            Request Type
                                        </th>

                                        <th>
                                            Description
                                        </th>

                                        <th>
                                            Requester
                                        </th>

                                        <th>
                                            Handler
                                        </th>

                                        <th>
                                            Status
                                        </th>

                                        <th width="12%">
                                            Action
                                        </th>

                                    </tr>

                                </thead>


                                <tbody>

                                    @foreach($requests as $item)

                                        <tr>

                                            {{-- =====================================
                                                 NO
                                            ====================================== --}}

                                            <td>
                                                {{ $loop->iteration }}
                                            </td>


                                            {{-- =====================================
                                                 ASSET
                                            ====================================== --}}

                                            <td>

                                                @if($item->asset)

                                                    <div class="maintenance-asset-name">

                                                        {{ $item->asset->asset_name }}

                                                    </div>

                                                    <small class="text-muted">

                                                        {{ $item->asset->asset_code }}

                                                    </small>

                                                @else

                                                    <span class="text-muted">
                                                        -
                                                    </span>

                                                @endif

                                            </td>


                                            {{-- =====================================
                                                 REQUEST TYPE
                                            ====================================== --}}

                                            <td>

                                                @php

                                                    $typeLabel = match($item->request_type) {

                                                        'maintenance' =>
                                                            'Maintenance',

                                                        'repair' =>
                                                            'Repair',

                                                        'problem' =>
                                                            'Problem / Issue',

                                                        'other' =>
                                                            'Other',

                                                        default =>
                                                            ucfirst(
                                                                $item->request_type
                                                            ),

                                                    };

                                                @endphp


                                                <span class="badge bg-secondary">

                                                    {{ $typeLabel }}

                                                </span>

                                            </td>


                                            {{-- =====================================
                                                 DESCRIPTION
                                            ====================================== --}}

                                            <td>

                                                <div class="maintenance-description">

                                                    {{ $item->description }}

                                                </div>

                                            </td>


                                            {{-- =====================================
                                                 REQUESTER
                                            ====================================== --}}

                                            <td>

                                                {{ $item->requester->name ?? '-' }}

                                            </td>


                                            {{-- =====================================
                                                 HANDLER
                                            ====================================== --}}

                                            <td>

                                                {{ $item->handler->name ?? '-' }}

                                            </td>


                                            {{-- =====================================
                                                 STATUS
                                            ====================================== --}}

                                            <td>

                                                @php

                                                    $statusClass = match($item->status) {

                                                        'pending' =>
                                                            'bg-warning text-dark',

                                                        'approved' =>
                                                            'bg-info',

                                                        'in_progress' =>
                                                            'bg-primary',

                                                        'completed' =>
                                                            'bg-success',

                                                        'rejected' =>
                                                            'bg-danger',

                                                        'cancelled' =>
                                                            'bg-dark',

                                                        default =>
                                                            'bg-secondary',

                                                    };


                                                    $statusLabel = match($item->status) {

                                                        'pending' =>
                                                            'Pending',

                                                        'approved' =>
                                                            'Approved',

                                                        'in_progress' =>
                                                            'In Progress',

                                                        'completed' =>
                                                            'Completed',

                                                        'rejected' =>
                                                            'Rejected',

                                                        'cancelled' =>
                                                            'Cancelled',

                                                        default =>
                                                            ucfirst(
                                                                str_replace(
                                                                    '_',
                                                                    ' ',
                                                                    $item->status
                                                                )
                                                            ),

                                                    };

                                                @endphp


                                                <span class="badge {{ $statusClass }}">

                                                    {{ $statusLabel }}

                                                </span>

                                            </td>


                                            {{-- =====================================
                                                 ACTION
                                            ====================================== --}}

                                            <td>

                                                <div class="d-flex gap-1 flex-wrap">

                                                    {{-- =================================
                                                         PENDING
                                                    ================================== --}}

                                                    @if($item->status === 'pending')

                                                        @if(auth()->user()->hasPermission('maintenance.edit'))

                                                            <button
                                                                type="button"
                                                                class="btn btn-primary btn-sm btn-take-request"
                                                                data-id="{{ $item->id }}"
                                                            >

                                                                <i class="fa-solid fa-hand-pointer me-1"></i>

                                                                Take

                                                            </button>

                                                        @else

                                                            <span class="text-warning small">

                                                                <i class="fa-solid fa-clock me-1"></i>

                                                                Waiting

                                                            </span>

                                                        @endif


                                                    {{-- =================================
                                                         IN PROGRESS
                                                    ================================== --}}

                                                    @elseif($item->status === 'in_progress')

                                                        @if(auth()->user()->hasPermission('maintenance.edit'))

                                                            <a
                                                                href="{{ route('maintenance.requests.work', $item->id) }}"
                                                                class="btn btn-primary btn-sm"
                                                            >

                                                                <i class="fa-solid fa-screwdriver-wrench me-1"></i>

                                                                Work

                                                            </a>

                                                        @else

                                                            <span class="text-primary small">

                                                                <i class="fa-solid fa-spinner me-1"></i>

                                                                In Progress

                                                            </span>

                                                        @endif


                                                    {{-- =================================
                                                         OTHER
                                                    ================================== --}}

                                                    @else

                                                        <span class="text-muted small">
                                                            -
                                                        </span>

                                                    @endif

                                                </div>

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

</div>

@endsection


@section('js')

<script>

$(function () {


    /* =========================================================
       DATATABLE
    ========================================================== */

    $('#maintenanceRequestsTable').DataTable({

        processing: true,

        serverSide: false,

        responsive: true,

        autoWidth: false,

        pageLength: 10,

        order: [
            [0, 'asc']
        ],

        columnDefs: [

            {
                orderable: false,
                searchable: false,
                targets: [0, 7]
            }

        ],

        language: {

            search: "Search:",

            lengthMenu:
                "Show _MENU_ entries",

            info:
                "Showing _START_ to _END_ of _TOTAL_ requests",

            infoEmpty:
                "No requests available",

            zeroRecords:
                "No matching requests found",

            emptyTable:
                "No maintenance requests",

            paginate: {

                first:
                    "First",

                last:
                    "Last",

                next:
                    "Next",

                previous:
                    "Previous"

            }

        }

    });


    /* =========================================================
       TAKE REQUEST
    ========================================================== */

    $(document).on(
        'click',
        '.btn-take-request',
        function () {

            let button =
                $(this);

            let requestId =
                button.data('id');


            /* =================================================
               CONFIRMATION
            ================================================= */

            Swal.fire({

                icon:
                    'question',

                title:
                    'Take Request?',

                text:
                    'Request ini akan menjadi tanggung jawab Anda.',

                showCancelButton:
                    true,

                confirmButtonText:
                    'Yes, Take',

                cancelButtonText:
                    'Cancel',

                reverseButtons:
                    true

            }).then(function (result) {

                if (!result.isConfirmed) {

                    return;

                }


                /* =============================================
                   BUTTON LOADING
                ============================================== */

                button
                    .prop('disabled', true)
                    .html(`
                        <span class="spinner-border spinner-border-sm me-1"></span>
                        Processing...
                    `);


                /* =============================================
                   AJAX
                ============================================== */

                $.ajax({

                    url:
                        "{{ url('/dashboard/maintenance-requests') }}/"
                        + requestId
                        + "/take",

                    type:
                        'POST',

                    data: {

                        _token:
                            $('meta[name="csrf-token"]').attr('content')

                    },

                    headers: {

                        'Accept':
                            'application/json'

                    },


                    /* =========================================
                       SUCCESS
                    ========================================== */

                    success: function (response) {

                        Swal.fire({

                            icon:
                                'success',

                            title:
                                'Berhasil',

                            text:
                                response.message,

                            timer:
                                1500,

                            showConfirmButton:
                                false

                        }).then(function () {

                            window.location.reload();

                        });

                    },


                    /* =========================================
                       ERROR
                    ========================================== */

                    error: function (xhr) {

                        let message =
                            'Request gagal diambil.';


                        if (
                            xhr.responseJSON &&
                            xhr.responseJSON.message
                        ) {

                            message =
                                xhr.responseJSON.message;

                        }


                        Swal.fire({

                            icon:
                                'error',

                            title:
                                'Gagal',

                            text:
                                message

                        });


                        button
                            .prop('disabled', false)
                            .html(`
                                <i class="fa-solid fa-hand-pointer me-1"></i>
                                Take
                            `);

                    }

                });

            });

        }

    );

});

</script>

@endsection