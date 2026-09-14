@extends('dashboard.layouts.wrapper')

@section('title', 'My Assets')

@section('content')

<style>

    /* =========================================================
       SWEETALERT
    ========================================================== */

    .swal2-container {
        z-index: 99999 !important;
    }


    /* =========================================================
       DATATABLE
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
       ASSET
    ========================================================== */

    .my-asset-name {
        font-weight: 600;
        white-space: normal;
    }


    /* =========================================================
       CATEGORY
    ========================================================== */

    .my-asset-category {
        white-space: normal;
    }


    /* =========================================================
       RESPONSIVE
    ========================================================== */

    @media (max-width: 767.98px) {

        .my-asset-name {
            min-width: 160px;
        }

        .my-asset-category {
            min-width: 140px;
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
                                My Assets
                            </h5>

                            <small class="text-muted">
                                Daftar aset yang menjadi tanggung jawab Anda.
                            </small>

                        </div>

                    </div>


                    {{-- =================================================
                         SUMMARY
                    ================================================== --}}

                    <div class="card-body pb-0">

                        <div class="row">

                            <div class="col-xl-3 col-md-6 mb-3">

                                <div class="card border shadow-none h-100">

                                    <div class="card-body">

                                        <div class="d-flex align-items-center">

                                            <div
                                                class="rounded-circle bg-primary-subtle p-3 me-3"
                                            >

                                                <i
                                                    class="fa-solid fa-box text-primary fs-4"
                                                ></i>

                                            </div>


                                            <div>

                                                <small class="text-muted">
                                                    My Assets
                                                </small>

                                                <h4 class="mb-0 fw-bold">
                                                    {{ $assets->count() }}
                                                </h4>

                                            </div>

                                        </div>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>


                    {{-- =================================================
                         ASSET LIST
                    ================================================== --}}

                    <div class="card-body">

                        <div class="mb-3">

                            <h6 class="mb-1 fw-semibold">
                                Asset List
                            </h6>

                            <small class="text-muted">
                                Ajukan maintenance, repair, atau laporan masalah
                                untuk aset yang menjadi tanggung jawab Anda.
                            </small>

                        </div>


                        <div class="table-responsive">

                            <table
                                class="table table-hover align-middle"
                                id="myAssetsTable"
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
                                            Category
                                        </th>

                                        <th>
                                            Condition
                                        </th>

                                        <th>
                                            Request Status
                                        </th>

                                        <th width="15%">
                                            Action
                                        </th>

                                    </tr>

                                </thead>


                                <tbody>

                                    @foreach($assets as $asset)

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

                                                <div class="my-asset-name">

                                                    {{ $asset->asset_name }}

                                                </div>

                                                <small class="text-muted">

                                                    {{ $asset->asset_code }}

                                                </small>

                                            </td>


                                            {{-- =====================================
                                                 CATEGORY
                                            ====================================== --}}

                                            <td>

                                                <div class="my-asset-category">

                                                    {{ $asset->category->category_name ?? '-' }}

                                                    @if($asset->subcategory)

                                                        <br>

                                                        <small class="text-muted">

                                                            {{ $asset->subcategory->subcategory_name }}

                                                        </small>

                                                    @endif

                                                </div>

                                            </td>


                                            {{-- =====================================
                                                 CONDITION
                                            ====================================== --}}

                                            <td>

                                                @php

                                                    $conditionClass = match(
                                                        $asset->condition
                                                    ) {

                                                        'new' =>
                                                            'bg-success',

                                                        'used' =>
                                                            'bg-info',

                                                        'damaged' =>
                                                            'bg-warning text-dark',

                                                        'broken' =>
                                                            'bg-danger',

                                                        default =>
                                                            'bg-secondary',

                                                    };


                                                    $conditionLabel = match(
                                                        $asset->condition
                                                    ) {

                                                        'new' =>
                                                            'New',

                                                        'used' =>
                                                            'Used',

                                                        'damaged' =>
                                                            'Damaged',

                                                        'broken' =>
                                                            'Broken',

                                                        default =>
                                                            ucfirst(
                                                                $asset->condition ?? '-'
                                                            ),

                                                    };

                                                @endphp


                                                <span
                                                    class="badge {{ $conditionClass }}"
                                                >

                                                    {{ $conditionLabel }}

                                                </span>

                                            </td>


                                            {{-- =====================================
                                                 REQUEST STATUS
                                            ====================================== --}}

                                            <td>

                                                @if($asset->activeMaintenanceRequest)

                                                    @php

                                                        $requestStatus =
                                                            $asset->activeMaintenanceRequest->status;


                                                        $requestStatusClass =
                                                            match($requestStatus) {

                                                                'pending' =>
                                                                    'bg-warning text-dark',

                                                                'approved' =>
                                                                    'bg-info',

                                                                'in_progress' =>
                                                                    'bg-primary',

                                                                default =>
                                                                    'bg-secondary',

                                                            };


                                                        $requestStatusLabel =
                                                            match($requestStatus) {

                                                                'pending' =>
                                                                    'Pending',

                                                                'approved' =>
                                                                    'Approved',

                                                                'in_progress' =>
                                                                    'In Progress',

                                                                default =>
                                                                    ucfirst(
                                                                        str_replace(
                                                                            '_',
                                                                            ' ',
                                                                            $requestStatus
                                                                        )
                                                                    ),

                                                            };

                                                    @endphp


                                                    <span
                                                        class="badge {{ $requestStatusClass }}"
                                                    >

                                                        {{ $requestStatusLabel }}

                                                    </span>

                                                @else

                                                    <span
                                                        class="badge bg-light text-dark"
                                                    >

                                                        No Active Request

                                                    </span>

                                                @endif

                                            </td>


                                            {{-- =====================================
                                                 ACTION
                                            ====================================== --}}

                                            <td>

                                                <div class="d-flex gap-1 flex-wrap">


                                                    {{-- =================================
                                                         NO ACTIVE REQUEST
                                                    ================================== --}}

                                                    @if(!$asset->activeMaintenanceRequest)

                                                        <button
                                                            type="button"
                                                            class="btn btn-primary btn-sm btn-request"

                                                            data-bs-toggle="modal"
                                                            data-bs-target="#requestMaintenanceModal"

                                                            data-id="{{ $asset->id }}"
                                                            data-name="{{ $asset->asset_name }}"
                                                            data-code="{{ $asset->asset_code }}"

                                                            title="Create Request"
                                                        >

                                                            <i class="fa-solid fa-wrench me-1"></i>

                                                            Request

                                                        </button>


                                                    {{-- =================================
                                                         ACTIVE REQUEST
                                                    ================================== --}}

                                                    @else

                                                        @php

                                                            $activeRequest =
                                                                $asset->activeMaintenanceRequest;

                                                        @endphp


                                                        {{-- =============================
                                                             PENDING
                                                        ============================== --}}

                                                        @if($activeRequest->status === 'pending')

                                                            <button
                                                                type="button"
                                                                class="btn btn-warning btn-sm btn-edit-request"

                                                                data-bs-toggle="modal"
                                                                data-bs-target="#requestMaintenanceModal"

                                                                data-id="{{ $activeRequest->id }}"
                                                                data-asset-id="{{ $asset->id }}"
                                                                data-name="{{ $asset->asset_name }}"
                                                                data-code="{{ $asset->asset_code }}"
                                                                data-type="{{ $activeRequest->request_type }}"
                                                                data-description="{{ $activeRequest->description }}"

                                                                title="Edit Request"
                                                            >

                                                                <i class="fa-solid fa-pen-to-square me-1"></i>

                                                                Edit Request

                                                            </button>


                                                        {{-- =============================
                                                             IN PROGRESS
                                                        ============================== --}}

                                                        @else

                                                            <button
                                                                type="button"
                                                                class="btn btn-secondary btn-sm"
                                                                disabled
                                                            >

                                                                <i class="fa-solid fa-hourglass-half me-1"></i>

                                                                Processing

                                                            </button>

                                                        @endif

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



{{-- ============================================================
     REQUEST / EDIT REQUEST MODAL
============================================================= --}}

<div
    class="modal fade"
    id="requestMaintenanceModal"
    tabindex="-1"
    aria-hidden="true"
>

    <div class="modal-dialog modal-lg modal-dialog-centered">

        <form id="formAssetRequest">

            @csrf


            {{-- METHOD --}}

            <input
                type="hidden"
                name="_method"
                id="request_method"
                value="POST"
            >


            {{-- ASSET ID --}}

            <input
                type="hidden"
                name="asset_id"
                id="request_asset_id"
            >


            {{-- REQUEST ID --}}

            <input
                type="hidden"
                name="request_id"
                id="request_id"
            >


            <div class="modal-content">


                {{-- =================================================
                     HEADER
                ================================================== --}}

                <div class="modal-header">

                    <h5
                        class="modal-title"
                        id="requestModalTitle"
                    >
                        Asset Request
                    </h5>


                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                    ></button>

                </div>


                {{-- =================================================
                     BODY
                ================================================== --}}

                <div class="modal-body">

                    <div class="row">


                        {{-- =========================================
                             ASSET
                        ========================================== --}}

                        <div class="col-12 mb-3">

                            <label class="form-label fw-semibold">
                                Asset
                            </label>


                            <div class="border rounded p-3 bg-light">

                                <div
                                    id="requestAssetName"
                                    class="fw-semibold"
                                >
                                    -
                                </div>


                                <small
                                    id="requestAssetCode"
                                    class="text-muted"
                                >
                                    -
                                </small>

                            </div>

                        </div>


                        {{-- =========================================
                             REQUEST TYPE
                        ========================================== --}}

                        <div class="col-md-6 mb-3">

                            <label
                                for="requestType"
                                class="form-label fw-semibold"
                            >

                                Request Type

                                <span class="text-danger">
                                    *
                                </span>

                            </label>


                            <select
                                name="request_type"
                                id="requestType"
                                class="form-select"
                                required
                            >

                                <option value="">
                                    Select Request Type
                                </option>

                                <option value="maintenance">
                                    Maintenance
                                </option>

                                <option value="repair">
                                    Repair
                                </option>

                                <option value="problem">
                                    Problem / Issue
                                </option>

                                <option value="other">
                                    Other
                                </option>

                            </select>

                        </div>


                        {{-- =========================================
                             DESCRIPTION
                        ========================================== --}}

                        <div class="col-12 mb-3">

                            <label
                                for="requestDescription"
                                class="form-label fw-semibold"
                            >

                                Description

                                <span class="text-danger">
                                    *
                                </span>

                            </label>


                            <textarea
                                name="description"
                                id="requestDescription"
                                class="form-control"
                                rows="5"
                                placeholder="Jelaskan kebutuhan atau masalah pada aset..."
                                minlength="5"
                                required
                            ></textarea>


                            <small class="text-muted">

                                Jelaskan masalah atau kebutuhan
                                maintenance dengan jelas.

                            </small>

                        </div>

                    </div>

                </div>


                {{-- =================================================
                     FOOTER
                ================================================== --}}

                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-label-secondary"
                        data-bs-dismiss="modal"
                    >
                        Close
                    </button>


                    <button
                        type="submit"
                        class="btn btn-primary"
                        id="btnSubmitRequest"
                    >

                        <i class="fa-solid fa-paper-plane me-1"></i>

                        Submit Request

                    </button>

                </div>

            </div>

        </form>

    </div>

</div>


@endsection



@section('js')

<script>

$(function () {


    /* =========================================================
       DATATABLE
    ========================================================== */

    $('#myAssetsTable').DataTable({

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
            [1, 'asc']
        ],

        columnDefs: [

            {
                orderable: false,
                searchable: false,
                targets: [0, 5]
            }

        ],

        language: {

            search: "Search:",

            lengthMenu:
                "Show _MENU_ entries",

            info:
                "Showing _START_ to _END_ of _TOTAL_ assets",

            infoEmpty:
                "No assets available",

            zeroRecords:
                "No matching assets found",

            emptyTable:
                "No assets assigned to you",

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
       NEW REQUEST
    ========================================================== */

    $(document).on(
        'click',
        '.btn-request',
        function () {

            let button =
                $(this);


            let assetId =
                button.data('id');


            let assetName =
                button.data('name');


            let assetCode =
                button.data('code');


            /*
            |--------------------------------------------------------------------------
            | MODE
            |--------------------------------------------------------------------------
            */

            $('#requestModalTitle')
                .text('Asset Request');


            $('#request_method')
                .val('POST');


            $('#request_id')
                .val('');


            /*
            |--------------------------------------------------------------------------
            | ASSET
            |--------------------------------------------------------------------------
            */

            $('#request_asset_id')
                .val(assetId);


            $('#requestAssetName')
                .text(assetName);


            $('#requestAssetCode')
                .text(assetCode);


            /*
            |--------------------------------------------------------------------------
            | RESET INPUT
            |--------------------------------------------------------------------------
            */

            $('#requestType')
                .val('');


            $('#requestDescription')
                .val('');


            /*
            |--------------------------------------------------------------------------
            | BUTTON
            |--------------------------------------------------------------------------
            */

            $('#btnSubmitRequest')
                .html(`
                    <i class="fa-solid fa-paper-plane me-1"></i>
                    Submit Request
                `);

        }

    );



    /* =========================================================
       EDIT REQUEST
    ========================================================== */

    $(document).on(
        'click',
        '.btn-edit-request',
        function () {

            let button =
                $(this);


            let requestId =
                button.data('id');


            let assetId =
                button.data('asset-id');


            let assetName =
                button.data('name');


            let assetCode =
                button.data('code');


            let requestType =
                button.data('type');


            let description =
                button.data('description');


            /*
            |--------------------------------------------------------------------------
            | MODE
            |--------------------------------------------------------------------------
            */

            $('#requestModalTitle')
                .text('Edit Request');


            $('#request_method')
                .val('PUT');


            $('#request_id')
                .val(requestId);


            /*
            |--------------------------------------------------------------------------
            | ASSET
            |--------------------------------------------------------------------------
            */

            $('#request_asset_id')
                .val(assetId);


            $('#requestAssetName')
                .text(assetName);


            $('#requestAssetCode')
                .text(assetCode);


            /*
            |--------------------------------------------------------------------------
            | REQUEST DATA
            |--------------------------------------------------------------------------
            */

            $('#requestType')
                .val(requestType);


            $('#requestDescription')
                .val(description);


            /*
            |--------------------------------------------------------------------------
            | BUTTON
            |--------------------------------------------------------------------------
            */

            $('#btnSubmitRequest')
                .html(`
                    <i class="fa-solid fa-save me-1"></i>
                    Update Request
                `);

        }

    );



    /* =========================================================
       SUBMIT / UPDATE REQUEST
    ========================================================== */

    $('#formAssetRequest').on(
        'submit',
        function (e) {

            e.preventDefault();


            let form =
                this;


            /*
            |--------------------------------------------------------------------------
            | HTML VALIDATION
            |--------------------------------------------------------------------------
            */

            if (!form.checkValidity()) {

                form.reportValidity();

                return;

            }


            let requestType =
                $('#requestType')
                    .val();


            let description =
                $('#requestDescription')
                    .val()
                    .trim();


            /*
            |--------------------------------------------------------------------------
            | REQUEST TYPE VALIDATION
            |--------------------------------------------------------------------------
            */

            if (!requestType) {

                Swal.fire({

                    icon:
                        'warning',

                    title:
                        'Request Type Required',

                    text:
                        'Silakan pilih jenis request terlebih dahulu.'

                });

                return;

            }


            /*
            |--------------------------------------------------------------------------
            | DESCRIPTION VALIDATION
            |--------------------------------------------------------------------------
            */

            if (description.length < 5) {

                Swal.fire({

                    icon:
                        'warning',

                    title:
                        'Description Required',

                    text:
                        'Description minimal 5 karakter.'

                });

                return;

            }


            /*
            |--------------------------------------------------------------------------
            | REQUEST ID / METHOD
            |--------------------------------------------------------------------------
            */

            let requestId =
                $('#request_id')
                    .val();


            let method =
                $('#request_method')
                    .val();


            /*
            |--------------------------------------------------------------------------
            | URL
            |--------------------------------------------------------------------------
            */

            let url;


            if (requestId) {

                /*
                | Update menggunakan route Laravel.
                | Route:
                | PUT /dashboard/maintenance-requests/{id}
                */

                url =
                    "{{ url('/dashboard/maintenance-requests') }}/"
                    + requestId;

            } else {

                url =
                    "{{ route('maintenance.requests.store') }}";

            }


            /*
            |--------------------------------------------------------------------------
            | FORM DATA
            |--------------------------------------------------------------------------
            */

            let formData =
                new FormData(form);


            /*
            |--------------------------------------------------------------------------
            | METHOD SPOOFING
            |--------------------------------------------------------------------------
            */

            formData.set(
                '_method',
                method === 'PUT'
                    ? 'PUT'
                    : 'POST'
            );


            /*
            |--------------------------------------------------------------------------
            | BUTTON
            |--------------------------------------------------------------------------
            */

            let submitButton =
                $('#btnSubmitRequest');


            submitButton
                .prop('disabled', true)
                .html(`
                    <span class="spinner-border spinner-border-sm me-1"></span>
                    Processing...
                `);


            /*
            |--------------------------------------------------------------------------
            | AJAX
            |--------------------------------------------------------------------------
            */

            $.ajax({

                url:
                    url,

                type:
                    'POST',

                data:
                    formData,

                processData:
                    false,

                contentType:
                    false,

                headers: {

                    'Accept':
                        'application/json'

                },


                /*
                |--------------------------------------------------------------------------
                | SUCCESS
                |--------------------------------------------------------------------------
                */

                success: function (response) {


                    if (!response.success) {

                        Swal.fire({

                            icon:
                                'error',

                            title:
                                'Failed',

                            text:
                                response.message ??
                                'Request gagal diproses.'

                        });

                        return;

                    }


                    /*
                    |--------------------------------------------------------------------------
                    | CLOSE MODAL
                    |--------------------------------------------------------------------------
                    */

                    let modalElement =
                        document.getElementById(
                            'requestMaintenanceModal'
                        );


                    let modalInstance =
                        bootstrap.Modal.getInstance(
                            modalElement
                        );


                    if (modalInstance) {

                        modalInstance.hide();

                    }


                    /*
                    |--------------------------------------------------------------------------
                    | SUCCESS MESSAGE
                    |--------------------------------------------------------------------------
                    */

                    Swal.fire({

                        icon:
                            'success',

                        title:
                            'Success',

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


                /*
                |--------------------------------------------------------------------------
                | ERROR
                |--------------------------------------------------------------------------
                */

                error: function (xhr) {

                    let message =
                        'Terjadi kesalahan saat memproses request.';


                    if (xhr.responseJSON) {


                        if (
                            xhr.responseJSON.message
                        ) {

                            message =
                                xhr.responseJSON.message;

                        }


                        /*
                        |--------------------------------------------------------------------------
                        | VALIDATION
                        |--------------------------------------------------------------------------
                        */

                        if (
                            xhr.responseJSON.errors
                        ) {

                            let errors =
                                xhr.responseJSON.errors;


                            let firstError =
                                Object.values(errors)[0];


                            if (
                                Array.isArray(firstError) &&
                                firstError.length
                            ) {

                                message =
                                    firstError[0];

                            }

                        }

                    }


                    Swal.fire({

                        icon:
                            'error',

                        title:
                            'Request Failed',

                        text:
                            message

                    });

                },


                /*
                |--------------------------------------------------------------------------
                | COMPLETE
                |--------------------------------------------------------------------------
                */

                complete: function () {

                    submitButton
                        .prop('disabled', false);


                    if (requestId) {

                        submitButton.html(`
                            <i class="fa-solid fa-save me-1"></i>
                            Update Request
                        `);

                    } else {

                        submitButton.html(`
                            <i class="fa-solid fa-paper-plane me-1"></i>
                            Submit Request
                        `);

                    }

                }

            });

        }

    );



    /* =========================================================
       RESET MODAL
    ========================================================== */

    $('#requestMaintenanceModal').on(
        'hidden.bs.modal',
        function () {


            $('#formAssetRequest')[0]
                .reset();


            $('#request_method')
                .val('POST');


            $('#request_id')
                .val('');


            $('#request_asset_id')
                .val('');


            $('#requestAssetName')
                .text('-');


            $('#requestAssetCode')
                .text('-');


            $('#requestModalTitle')
                .text('Asset Request');


            $('#btnSubmitRequest')
                .html(`
                    <i class="fa-solid fa-paper-plane me-1"></i>
                    Submit Request
                `);

        }

    );



    /* =========================================================
       FOCUS
    ========================================================== */

    $('#requestMaintenanceModal').on(
        'shown.bs.modal',
        function () {

            $('#requestType')
                .trigger('focus');

        }

    );

});

</script>

@endsection