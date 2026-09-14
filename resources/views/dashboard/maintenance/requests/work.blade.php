@extends('dashboard.layouts.wrapper')

@section('title', 'Maintenance Work')

@section('content')

<style>
.swal2-container {
    z-index: 99999 !important;
}

.table-responsive {
    overflow: visible !important;
}

.dropdown-menu {
    z-index: 99999 !important;
}

.maintenance-photo-preview img {
    width: 100%;
    height: 120px;
    object-fit: cover;
}

.after-photo-preview img {
    width: 100%;
    height: 140px;
    object-fit: cover;
}
</style>


<div class="content-wrapper">

    <!-- Content -->
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
                                Maintenance Work
                            </h5>

                            <small class="text-muted">
                                Kelola pekerjaan maintenance dan progress asset.
                            </small>

                        </div>

                        <div class="d-flex gap-2">

                            <a
                                href="{{ route('maintenance.requests.index') }}"
                                class="btn btn-light"
                            >
                                <i class="fa-solid fa-arrow-left me-1"></i>
                                Back
                            </a>

                        </div>

                    </div>


                    {{-- =====================================================
                         CARD BODY
                    ====================================================== --}}
                    <div class="card-body">


                        {{-- =================================================
                             REQUEST INFORMATION + WORK SUMMARY
                        ================================================== --}}
                        <div class="row">


                            {{-- =================================================
                                 REQUEST INFORMATION
                            ================================================== --}}
                            <div class="col-lg-8 mb-4">

                                <div class="card h-100">

                                    <div class="card-header">

                                        <h5 class="mb-1">
                                            Request Information
                                        </h5>

                                        <small class="text-muted">
                                            Informasi maintenance request.
                                        </small>

                                    </div>


                                    <div class="card-body">

                                        <div class="row">


                                            {{-- ASSET --}}
                                            <div class="col-md-6 mb-3">

                                                <span class="text-muted small d-block mb-1">
                                                    Asset
                                                </span>

                                                @if($maintenanceRequest->asset)

                                                    <div class="fw-semibold">
                                                        {{ $maintenanceRequest->asset->asset_name }}
                                                    </div>

                                                    <small class="text-muted">
                                                        {{ $maintenanceRequest->asset->asset_code }}
                                                    </small>

                                                @else

                                                    <span class="text-muted">
                                                        -
                                                    </span>

                                                @endif

                                            </div>


                                            {{-- REQUEST TYPE --}}
                                            <div class="col-md-6 mb-3">

                                                <span class="text-muted small d-block mb-1">
                                                    Request Type
                                                </span>

                                                @php

                                                    $typeLabel = match(
                                                        $maintenanceRequest->request_type
                                                    ) {

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
                                                                $maintenanceRequest->request_type
                                                            ),

                                                    };

                                                @endphp

                                                <span class="badge bg-secondary">
                                                    {{ $typeLabel }}
                                                </span>

                                            </div>


                                            {{-- REQUESTER --}}
                                            <div class="col-md-6 mb-3">

                                                <span class="text-muted small d-block mb-1">
                                                    Requested By
                                                </span>

                                                <div class="fw-semibold">
                                                    {{ $maintenanceRequest->requester->name ?? '-' }}
                                                </div>

                                            </div>


                                            {{-- HANDLER --}}
                                            <div class="col-md-6 mb-3">

                                                <span class="text-muted small d-block mb-1">
                                                    Handler
                                                </span>

                                                <div class="fw-semibold">
                                                    {{ $maintenanceRequest->handler->name ?? '-' }}
                                                </div>

                                            </div>


                                            {{-- STATUS --}}
                                            <div class="col-md-6 mb-3">

                                                <span class="text-muted small d-block mb-1">
                                                    Status
                                                </span>

                                                <span class="badge bg-primary">
                                                    In Progress
                                                </span>

                                            </div>


                                            {{-- REQUEST DATE --}}
                                            <div class="col-md-6 mb-3">

                                                <span class="text-muted small d-block mb-1">
                                                    Request Date
                                                </span>

                                                <div>
                                                    {{ $maintenanceRequest->created_at?->format('d M Y H:i') ?? '-' }}
                                                </div>

                                            </div>


                                            {{-- DESCRIPTION --}}
                                            <div class="col-12">

                                                <span class="text-muted small d-block mb-1">
                                                    Problem / Description
                                                </span>

                                                <div class="bg-light rounded p-3">

                                                    {{ $maintenanceRequest->description }}

                                                </div>

                                            </div>

                                        </div>

                                    </div>

                                </div>

                            </div>


                            {{-- =================================================
                                 WORK SUMMARY
                            ================================================== --}}
                            <div class="col-lg-4 mb-4">

                                <div class="card h-100">

                                    <div class="card-header">

                                        <h5 class="mb-1">
                                            Work Summary
                                        </h5>

                                        <small class="text-muted">
                                            Status pekerjaan saat ini.
                                        </small>

                                    </div>


                                    <div class="card-body">


                                        <div class="mb-4">

                                            <span class="text-muted small d-block mb-1">
                                                Started At
                                            </span>

                                            <div class="fw-semibold">

                                                @if($maintenanceRequest->handled_at)

                                                    {{ \Carbon\Carbon::parse(
                                                        $maintenanceRequest->handled_at
                                                    )->format('d M Y H:i') }}

                                                @else

                                                    -

                                                @endif

                                            </div>

                                        </div>


                                        <div class="mb-4">

                                            <span class="text-muted small d-block mb-1">
                                                Handler
                                            </span>

                                            <div class="fw-semibold">
                                                {{ $maintenanceRequest->handler->name ?? '-' }}
                                            </div>

                                        </div>


                                        <div class="mb-4">

                                            <span class="text-muted small d-block mb-1">
                                                Progress
                                            </span>

                                            <div class="fw-semibold">

                                                {{ $maintenanceRequest->logs->count() }}

                                                update

                                            </div>

                                        </div>


                                        <div class="alert alert-primary mb-0">

                                            <i class="fa-solid fa-screwdriver-wrench me-1"></i>

                                            Maintenance sedang dikerjakan.

                                        </div>

                                    </div>

                                </div>

                            </div>

                        </div>


                        {{-- =================================================
                             WORK PROGRESS
                        ================================================== --}}
                        <div class="row">

                            <div class="col-12">

                                <div class="card mb-4">


                                    <div class="card-header d-flex justify-content-between align-items-center">

                                        <div>

                                            <h5 class="mb-1">
                                                Work Progress
                                            </h5>

                                            <small class="text-muted">
                                                Catatan pekerjaan maintenance.
                                            </small>

                                        </div>


                                        <div>

                                            <button
                                                type="button"
                                                class="btn btn-primary btn-sm"
                                                id="btnAddProgress"
                                            >

                                                <i class="fa-solid fa-plus me-1"></i>

                                                Add Progress

                                            </button>

                                        </div>

                                    </div>


                                    <div class="card-body">

                                        @forelse($maintenanceRequest->logs as $log)

                                            <div class="row mb-4">

                                                {{-- ICON --}}
                                                <div class="col-auto">

                                                    <div
                                                        class="d-flex align-items-center justify-content-center rounded-circle bg-primary text-white"
                                                        style="
                                                            width:42px;
                                                            height:42px;
                                                        "
                                                    >

                                                        <i class="fa-solid fa-wrench"></i>

                                                    </div>

                                                </div>


                                                {{-- CONTENT --}}
                                                <div class="col">

                                                    <div class="mb-2">

                                                        <div class="fw-semibold">
                                                            {{ $log->user->name ?? 'User' }}
                                                        </div>

                                                        <small class="text-muted">
                                                            {{ $log->created_at?->format('d M Y H:i') }}
                                                        </small>

                                                    </div>


                                                    <div class="bg-light rounded p-3">

                                                        {{ $log->note }}

                                                    </div>


                                                    {{-- PROGRESS PHOTOS --}}
                                                    @if(
                                                        $log->photos &&
                                                        $log->photos->count()
                                                    )

                                                        <div class="row g-2 mt-2">

                                                            @foreach($log->photos as $photo)

                                                                <div class="col-lg-3 col-md-4 col-sm-4 col-6">

                                                                    <a
                                                                        href="{{ asset('storage/' . $photo->file_path) }}"
                                                                        target="_blank"
                                                                    >

                                                                        <img
                                                                            src="{{ asset('storage/' . $photo->file_path) }}"
                                                                            class="img-fluid rounded border"
                                                                            style="
                                                                                width:100%;
                                                                                height:120px;
                                                                                object-fit:cover;
                                                                            "
                                                                            alt="Progress Photo"
                                                                        >

                                                                    </a>

                                                                </div>

                                                            @endforeach

                                                        </div>

                                                    @endif

                                                </div>

                                            </div>

                                        @empty

                                            <div class="text-center py-5 text-muted">

                                                <i class="fa-solid fa-clock-rotate-left fa-2x mb-2"></i>

                                                <div>
                                                    Belum ada progress pekerjaan.
                                                </div>

                                                <small>
                                                    Klik Add Progress untuk menambahkan update pekerjaan.
                                                </small>

                                            </div>

                                        @endforelse

                                    </div>

                                </div>

                            </div>

                        </div>


                        {{-- =================================================
                             COMPLETE AREA
                        ================================================== --}}
                        <div class="row">

                            <div class="col-12">

                                <div class="card mb-4">

                                    <div class="card-body">

                                        <div class="d-flex justify-content-between align-items-center">

                                            <div>

                                                <h5 class="mb-1">
                                                    Finish Maintenance
                                                </h5>

                                                <small class="text-muted">
                                                    Setelah pekerjaan benar-benar selesai,
                                                    lanjutkan proses Complete Maintenance.
                                                </small>

                                            </div>


                                            <div>

                                                <button
                                                    type="button"
                                                    class="btn btn-success"
                                                    id="btnComplete"
                                                >

                                                    <i class="fa-solid fa-check me-1"></i>

                                                    Complete Maintenance

                                                </button>

                                            </div>

                                        </div>

                                    </div>

                                </div>

                            </div>

                        </div>


                    </div>

                </div>

            </div>

        </div>

    </div>

</div>


{{-- =========================================================
     ADD PROGRESS MODAL
========================================================= --}}
<div
    class="modal fade"
    id="addProgressModal"
    tabindex="-1"
    aria-hidden="true"
>

    <div class="modal-dialog modal-lg modal-dialog-centered">

        <div class="modal-content">

            <div class="modal-header">

                <div>

                    <h5 class="modal-title fw-bold mb-1">
                        Add Work Progress
                    </h5>

                    <small class="text-muted">
                        Tambahkan perkembangan pekerjaan maintenance.
                    </small>

                </div>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                ></button>

            </div>


            <form
                id="addProgressForm"
                enctype="multipart/form-data"
            >

                <div class="modal-body">

                    <div class="mb-3">

                        <label class="form-label fw-semibold">

                            Progress / Note

                            <span class="text-danger">*</span>

                        </label>

                        <textarea
                            name="note"
                            id="progressNote"
                            class="form-control"
                            rows="5"
                            placeholder="Contoh: Pemeriksaan AC menemukan kebocoran pada pipa..."
                            required
                        ></textarea>

                    </div>


                    {{-- =================================================
                         PROGRESS PHOTOS
                    ================================================== --}}
                    <div class="mb-3">

                        <label class="form-label fw-semibold">
                            Progress Photos
                        </label>

                        <input
                            type="file"
                            name="photos[]"
                            id="progressPhotos"
                            class="form-control"
                            accept=".jpg,.jpeg,.png,image/jpeg,image/png"
                            multiple
                        >

                        <small class="text-muted d-block">
                            Maksimal 10 foto dalam satu update.
                            JPG, JPEG, PNG. Maksimal 10 MB per foto.
                        </small>

                    </div>


                    {{-- PREVIEW --}}
                    <div
                        id="photoPreview"
                        class="row g-2 mt-2 maintenance-photo-preview"
                    ></div>

                </div>


                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="btn btn-primary"
                        id="btnSaveProgress"
                    >

                        <i class="fa-solid fa-save me-1"></i>

                        Save Progress

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


{{-- =========================================================
     COMPLETE REQUEST MODAL
========================================================= --}}
<div
    class="modal fade"
    id="completeRequestModal"
    tabindex="-1"
    aria-hidden="true"
>

    <div class="modal-dialog modal-lg modal-dialog-centered">

        <div class="modal-content">

            <div class="modal-header">

                <div>

                    <h5 class="modal-title fw-bold mb-1">
                        Complete Maintenance Request
                    </h5>

                    <small class="text-muted">
                        Selesaikan pekerjaan maintenance untuk asset.
                    </small>

                </div>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                ></button>

            </div>


            <form
                id="completeRequestForm"
                enctype="multipart/form-data"
            >

                <div class="modal-body">


                    <div class="mb-3">

                        <label class="form-label fw-semibold">
                            Asset
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            value="{{ $maintenanceRequest->asset->asset_name ?? '-' }}"
                            readonly
                        >

                    </div>


                    <div class="row">


                        <div class="col-md-6 mb-3">

                            <label class="form-label fw-semibold">

                                Maintenance Date

                                <span class="text-danger">*</span>

                            </label>

                            <input
                                type="date"
                                name="maintenance_date"
                                id="maintenanceDate"
                                class="form-control"
                                value="{{ now()->format('Y-m-d') }}"
                                required
                            >

                        </div>


                        <div class="col-md-6 mb-3">

                            <label class="form-label fw-semibold">
                                Technician
                            </label>

                            <input
                                type="text"
                                name="technician_name"
                                class="form-control"
                                value="{{ auth()->user()->name }}"
                                placeholder="Nama teknisi"
                            >

                        </div>

                    </div>


                    <div class="mb-3">

                        <label class="form-label fw-semibold">

                            Action Taken

                            <span class="text-danger">*</span>

                        </label>

                        <textarea
                            name="action_taken"
                            id="actionTaken"
                            class="form-control"
                            rows="4"
                            placeholder="Jelaskan tindakan/perbaikan yang dilakukan..."
                            required
                        ></textarea>

                    </div>


                    <div class="row">


                        <div class="col-md-6 mb-3">

                            <label class="form-label fw-semibold">
                                Cost
                            </label>

                            <input
                                type="number"
                                name="cost"
                                id="maintenanceCost"
                                class="form-control"
                                min="0"
                                step="0.01"
                                value="0"
                            >

                        </div>


                        <div class="col-md-6 mb-3">

                            <label class="form-label fw-semibold">
                                Next Maintenance Date
                            </label>

                            <input
                                type="date"
                                name="next_maintenance_date"
                                class="form-control"
                            >

                            <small class="text-muted">
                                Kosongkan jika tidak ada jadwal maintenance berikutnya.
                            </small>

                        </div>

                    </div>


                    <div class="mb-3">

                        <label class="form-label fw-semibold">
                            Notes
                        </label>

                        <textarea
                            name="notes"
                            class="form-control"
                            rows="3"
                            placeholder="Catatan tambahan..."
                        ></textarea>

                    </div>


                    {{-- =================================================
                         MULTIPLE RESULT PHOTOS
                    ================================================== --}}
                    <div class="mb-3">

                        <label class="form-label fw-semibold">

                            Foto Setelah Maintenance

                            <span class="text-danger">*</span>

                        </label>


                        <input
                            type="file"
                            name="after_photos[]"
                            id="afterPhotos"
                            class="form-control"
                            accept=".jpg,.jpeg,.png,image/jpeg,image/png"
                            multiple
                            required
                        >


                        <small class="text-muted d-block">
                            Upload foto kondisi asset setelah diperbaiki.
                            Bisa memilih beberapa foto sekaligus.
                            Maksimal 10 foto, 10 MB per foto.
                        </small>


                        {{-- RESULT PHOTO PREVIEW --}}
                        <div
                            id="afterPhotoPreview"
                            class="row g-2 mt-2 after-photo-preview"
                        ></div>

                    </div>

                </div>


                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="btn btn-success"
                        id="btnSubmitComplete"
                    >

                        <i class="fa-solid fa-check me-1"></i>

                        Complete Maintenance

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


@endsection


@section('js')

<script>

$(function () {


    // =========================================================
    // ADD PROGRESS MODAL
    // =========================================================

    $(document).on('click', '#btnAddProgress', function () {

        $('#addProgressForm')[0].reset();

        $('#photoPreview').html('');

        $('#addProgressModal').modal('show');

    });


    // =========================================================
    // PROGRESS PHOTO PREVIEW
    // =========================================================

    $('#progressPhotos').on('change', function () {

        let input = this;

        let preview = $('#photoPreview');

        preview.html('');


        if (!input.files || input.files.length === 0) {
            return;
        }


        // MAX 10 FILES
        if (input.files.length > 10) {

            Swal.fire({
                icon: 'warning',
                title: 'Terlalu Banyak Foto',
                text: 'Maksimal 10 foto progress dalam satu update.'
            });

            input.value = '';

            return;
        }


        // VALIDATE + PREVIEW
        for (
            let i = 0;
            i < input.files.length;
            i++
        ) {

            let file = input.files[i];


            // FORMAT
            if (
                file.type !== 'image/jpeg' &&
                file.type !== 'image/png'
            ) {

                Swal.fire({
                    icon: 'error',
                    title: 'Format Tidak Valid',
                    text: 'Foto harus JPG, JPEG, atau PNG.'
                });

                input.value = '';

                preview.html('');

                return;
            }


            // SIZE
            if (
                file.size >
                10 * 1024 * 1024
            ) {

                Swal.fire({
                    icon: 'error',
                    title: 'File Terlalu Besar',
                    text: 'Maksimal ukuran foto adalah 10 MB per file.'
                });

                input.value = '';

                preview.html('');

                return;
            }


            let reader = new FileReader();


            reader.onload = function (e) {

                let wrapper =
                    $('<div class="col-lg-3 col-md-4 col-sm-4 col-6"></div>');

                let image =
                    $('<img>');

                image
                    .attr('src', e.target.result)
                    .attr('alt', 'Progress Photo')
                    .addClass('img-fluid rounded border');

                wrapper.append(image);

                preview.append(wrapper);

            };


            reader.readAsDataURL(file);

        }

    });


    // =========================================================
    // SUBMIT ADD PROGRESS
    // =========================================================

    $('#addProgressForm').on('submit', function (e) {

        e.preventDefault();


        let form = this;

        let button = $('#btnSaveProgress');

        let note =
            $('#progressNote')
                .val()
                .trim();


        if (!note) {

            Swal.fire({
                icon: 'warning',
                title: 'Progress Wajib Diisi',
                text: 'Silakan isi catatan progress pekerjaan.'
            });

            return;
        }


        button
            .prop('disabled', true)
            .html(
                '<span class="spinner-border spinner-border-sm me-1"></span> Saving...'
            );


        let formData = new FormData(form);


        formData.append(
            '_token',
            "{{ csrf_token() }}"
        );


        $.ajax({

            url:
                "{{ url('/dashboard/maintenance-requests') }}/{{ $maintenanceRequest->id }}/progress",

            type: 'POST',

            data: formData,

            processData: false,

            contentType: false,


            success: function (response) {

                $('#addProgressModal')
                    .modal('hide');


                Swal.fire({

                    icon: 'success',

                    title: 'Success',

                    text:
                        response.message ||
                        'Progress berhasil ditambahkan.',

                    timer: 1500,

                    showConfirmButton: false

                }).then(function () {

                    window.location.reload();

                });

            },


            error: function (xhr) {

                let message =
                    'Progress gagal disimpan.';


                if (
                    xhr.responseJSON &&
                    xhr.responseJSON.message
                ) {

                    message =
                        xhr.responseJSON.message;

                }


                if (
                    xhr.status === 422 &&
                    xhr.responseJSON &&
                    xhr.responseJSON.errors
                ) {

                    let errors =
                        xhr.responseJSON.errors;

                    let errorMessages = [];


                    $.each(
                        errors,
                        function (field, messages) {

                            $.each(
                                messages,
                                function (index, text) {

                                    errorMessages.push(text);

                                }
                            );

                        }
                    );


                    if (errorMessages.length) {

                        message =
                            errorMessages.join('<br>');

                    }

                }


                Swal.fire({

                    icon: 'error',

                    title: 'Failed',

                    html: message

                });

            },


            complete: function () {

                button
                    .prop('disabled', false)
                    .html(
                        '<i class="fa-solid fa-save me-1"></i> Save Progress'
                    );

            }

        });

    });


    // =========================================================
    // OPEN COMPLETE MODAL
    // =========================================================

    $(document).on('click', '#btnComplete', function () {

        $('#completeRequestForm')[0].reset();


        $('#maintenanceDate')
            .val("{{ now()->format('Y-m-d') }}");


        $('input[name="technician_name"]')
            .val("{{ auth()->user()->name }}");


        $('#maintenanceCost')
            .val('0');


        $('#afterPhotos')
            .val('');


        $('#afterPhotoPreview')
            .html('');


        $('#completeRequestModal')
            .modal('show');

    });


    // =========================================================
    // MULTIPLE AFTER PHOTO PREVIEW
    // =========================================================

    $('#afterPhotos').on('change', function () {

        let input = this;

        let preview =
            $('#afterPhotoPreview');

        preview.html('');


        if (
            !input.files ||
            input.files.length === 0
        ) {

            return;
        }


        // MAX 10 FILES
        if (
            input.files.length > 10
        ) {

            Swal.fire({

                icon: 'warning',

                title: 'Terlalu Banyak Foto',

                text:
                    'Maksimal 10 foto hasil maintenance.'

            });


            input.value = '';

            return;
        }


        // VALIDATE + PREVIEW
        for (
            let i = 0;
            i < input.files.length;
            i++
        ) {

            let file =
                input.files[i];


            // FORMAT
            if (
                file.type !== 'image/jpeg' &&
                file.type !== 'image/png'
            ) {

                Swal.fire({

                    icon: 'error',

                    title: 'Format Tidak Didukung',

                    text:
                        'Foto harus berformat JPG, JPEG, atau PNG.'

                });


                input.value = '';

                preview.html('');

                return;
            }


            // SIZE
            if (
                file.size >
                10 * 1024 * 1024
            ) {

                Swal.fire({

                    icon: 'error',

                    title: 'File Terlalu Besar',

                    text:
                        'Ukuran foto maksimal 10 MB per file.'

                });


                input.value = '';

                preview.html('');

                return;
            }


            let reader =
                new FileReader();


            reader.onload = function (e) {

                let wrapper =
                    $('<div class="col-lg-3 col-md-4 col-sm-6 col-6"></div>');

                let image =
                    $('<img>');


                image
                    .attr(
                        'src',
                        e.target.result
                    )
                    .attr(
                        'alt',
                        'After Maintenance Photo'
                    )
                    .addClass(
                        'img-fluid rounded border'
                    );


                wrapper.append(image);

                preview.append(wrapper);

            };


            reader.readAsDataURL(file);

        }

    });


    // =========================================================
    // SUBMIT COMPLETE
    // =========================================================

    $('#completeRequestForm').on('submit', function (e) {

        e.preventDefault();


        let form = this;

        let submitButton =
            $('#btnSubmitComplete');


        let actionTaken =
            $('#actionTaken')
                .val()
                .trim();


        let photoInput =
            document.getElementById(
                'afterPhotos'
            );


        if (!actionTaken) {

            Swal.fire({

                icon: 'warning',

                title: 'Action Taken Wajib Diisi',

                text:
                    'Jelaskan tindakan atau perbaikan yang telah dilakukan.'

            });

            return;
        }


        // PHOTO REQUIRED
        if (
            !photoInput.files ||
            !photoInput.files.length
        ) {

            Swal.fire({

                icon: 'warning',

                title: 'Foto Wajib',

                text:
                    'Silakan upload minimal 1 foto kondisi asset setelah maintenance.'

            });

            return;
        }


        // MAX 10
        if (
            photoInput.files.length > 10
        ) {

            Swal.fire({

                icon: 'warning',

                title: 'Terlalu Banyak Foto',

                text:
                    'Maksimal 10 foto hasil maintenance.'

            });

            return;
        }


        Swal.fire({

            icon: 'question',

            title: 'Complete Maintenance?',

            text:
                'Pastikan pekerjaan maintenance sudah benar-benar selesai.',

            showCancelButton: true,

            confirmButtonText: 'Yes, Complete',

            cancelButtonText: 'Cancel',

            reverseButtons: true

        }).then(function (result) {

            if (!result.isConfirmed) {
                return;
            }


            submitButton
                .prop('disabled', true)
                .html(
                    '<span class="spinner-border spinner-border-sm me-1"></span> Saving...'
                );


            let formData =
                new FormData(form);


            formData.append(
                '_token',
                "{{ csrf_token() }}"
            );


            $.ajax({

                url:
                    "{{ url('/dashboard/maintenance-requests') }}/{{ $maintenanceRequest->id }}/complete",

                type: 'POST',

                data: formData,

                processData: false,

                contentType: false,


                success: function (response) {

                    $('#completeRequestModal')
                        .modal('hide');


                    Swal.fire({

                        icon: 'success',

                        title: 'Success',

                        text:
                            response.message ||
                            'Maintenance berhasil diselesaikan.',

                        timer: 1800,

                        showConfirmButton: false

                    }).then(function () {

                        window.location.href =
                            "{{ route('maintenance.requests.index') }}";

                    });

                },


                error: function (xhr) {

                    let message =
                        'Maintenance gagal diselesaikan.';


                    if (
                        xhr.responseJSON &&
                        xhr.responseJSON.message
                    ) {

                        message =
                            xhr.responseJSON.message;

                    }


                    if (
                        xhr.status === 422 &&
                        xhr.responseJSON &&
                        xhr.responseJSON.errors
                    ) {

                        let errors =
                            xhr.responseJSON.errors;

                        let errorMessages = [];


                        $.each(
                            errors,
                            function (field, messages) {

                                $.each(
                                    messages,
                                    function (index, text) {

                                        errorMessages.push(text);

                                    }
                                );

                            }
                        );


                        if (errorMessages.length) {

                            message =
                                errorMessages.join('<br>');

                        }

                    }


                    Swal.fire({

                        icon: 'error',

                        title: 'Failed',

                        html: message

                    });

                },


                complete: function () {

                    submitButton
                        .prop('disabled', false)
                        .html(
                            '<i class="fa-solid fa-check me-1"></i> Complete Maintenance'
                        );

                }

            });

        });

    });


    // =========================================================
    // RESET ADD PROGRESS MODAL
    // =========================================================

    $('#addProgressModal').on(
        'hidden.bs.modal',
        function () {

            $('#addProgressForm')[0].reset();

            $('#photoPreview').html('');

        }
    );


    // =========================================================
    // RESET COMPLETE MODAL
    // =========================================================

    $('#completeRequestModal').on(
        'hidden.bs.modal',
        function () {

            $('#completeRequestForm')[0].reset();


            $('#maintenanceDate')
                .val(
                    "{{ now()->format('Y-m-d') }}"
                );


            $('input[name="technician_name"]')
                .val(
                    "{{ auth()->user()->name }}"
                );


            $('#maintenanceCost')
                .val('0');


            $('#afterPhotos')
                .val('');


            $('#afterPhotoPreview')
                .html('');

        }
    );

});

</script>

@endsection
