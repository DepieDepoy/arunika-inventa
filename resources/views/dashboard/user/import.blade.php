@extends('dashboard.layouts.wrapper')

@section('title', 'Import User')

@section('content')

<div class="container-fluid">

    {{-- =========================================================
         PAGE HEADER
    ========================================================== --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Import User</h4>
            <p class="text-muted mb-0">
                Import data user secara massal menggunakan file Excel.
            </p>
        </div>

        <a href="{{ route('users.index') }}"
           class="btn btn-light">
            <i class="fas fa-arrow-left me-1"></i>
            Kembali
        </a>
    </div>


    {{-- =========================================================
         ALERT
    ========================================================== --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            {{ session('success') }}

            <button type="button"
                    class="btn-close"
                    data-bs-dismiss="alert">
            </button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            {{ session('error') }}

            <button type="button"
                    class="btn-close"
                    data-bs-dismiss="alert">
            </button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>
                <i class="fas fa-exclamation-triangle me-2"></i>
                Validasi gagal
            </strong>

            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>

            <button type="button"
                    class="btn-close"
                    data-bs-dismiss="alert">
            </button>
        </div>
    @endif


    {{-- =========================================================
         IMPORT CARD
    ========================================================== --}}
    <div class="card border-0 shadow-sm">

        <div class="card-header bg-white border-bottom py-3">
            <div class="d-flex align-items-center">

                <div class="me-3"
                     style="
                        width:42px;
                        height:42px;
                        border-radius:10px;
                        background:#f0fdf4;
                        display:flex;
                        align-items:center;
                        justify-content:center;
                     ">
                    <i class="fas fa-file-excel text-success"></i>
                </div>

                <div>
                    <h5 class="mb-1">Import Data User</h5>
                    <small class="text-muted">
                        Upload file Excel untuk menambahkan banyak user sekaligus.
                    </small>
                </div>

            </div>
        </div>


        <div class="card-body p-4">

            {{-- =================================================
                 INFORMATION
            ================================================== --}}
            <div class="alert alert-info border-0 mb-4">

                <div class="d-flex">

                    <div class="me-3">
                        <i class="fas fa-info-circle fa-lg"></i>
                    </div>

                    <div>
                        <strong>Perhatian</strong>

                        <ul class="mb-0 mt-2">
                            <li>
                                Gunakan template Excel yang telah disediakan.
                            </li>
                            <li>
                                Kolom yang diperlukan:
                                <strong>Name, ID Person (NIK), Email, Phone</strong>.
                            </li>
                            <li>
                                Data akan diperiksa terlebih dahulu melalui halaman
                                <strong>Preview</strong>.
                            </li>
                            <li>
                                User hasil import akan dibuat dengan status
                                <strong>Active</strong>.
                            </li>
                            <li>
                                User hasil import akan menggunakan password default
                                dan diwajibkan mengganti password saat login pertama.
                            </li>
                        </ul>
                    </div>

                </div>

            </div>


            {{-- =================================================
                 DOWNLOAD TEMPLATE
            ================================================== --}}
            <div class="border rounded-3 p-4 mb-4">

                <div class="row align-items-center">

                    <div class="col-md-8">

                        <div class="d-flex align-items-center">

                            <div class="me-3"
                                 style="
                                    width:48px;
                                    height:48px;
                                    border-radius:10px;
                                    background:#ecfdf5;
                                    display:flex;
                                    align-items:center;
                                    justify-content:center;
                                 ">
                                <i class="fas fa-file-download text-success fa-lg"></i>
                            </div>

                            <div>
                                <h6 class="mb-1">
                                    Download Template Excel
                                </h6>

                                <p class="text-muted mb-0 small">
                                    Gunakan template ini agar format data sesuai
                                    dengan sistem Inventa.
                                </p>
                            </div>

                        </div>

                    </div>

                    <div class="col-md-4 text-md-end mt-3 mt-md-0">

                        <a href="{{ route('users.import.template') }}"
                           class="btn btn-outline-success">

                            <i class="fas fa-download me-1"></i>

                            Download Template

                        </a>

                    </div>

                </div>

            </div>


            {{-- =================================================
                 UPLOAD FORM
            ================================================== --}}
            <form action="{{ route('users.import.preview') }}"
                  method="POST"
                  enctype="multipart/form-data"
                  id="userImportForm">

                @csrf

                <div class="mb-3">

                    <label for="excel_file"
                           class="form-label fw-semibold">

                        File Excel

                        <span class="text-danger">*</span>

                    </label>


                    {{-- Upload Area --}}
                    <div id="uploadArea"
                         class="border border-2 border-dashed rounded-3 p-5 text-center"
                         style="
                            cursor:pointer;
                            transition:all .2s ease;
                         ">

                        <input type="file"
                               name="excel_file"
                               id="excel_file"
                               class="d-none"
                               accept=".xlsx,.xls">


                        <div id="uploadPlaceholder">

                            <div class="mb-3">

                                <i class="fas fa-cloud-upload-alt"
                                   style="
                                      font-size:42px;
                                      color:#6c757d;
                                   ">
                                </i>

                            </div>

                            <h6 class="mb-2">
                                Pilih File Excel
                            </h6>

                            <p class="text-muted mb-2">
                                Klik area ini untuk memilih file
                            </p>

                            <small class="text-muted">
                                Format yang diperbolehkan:
                                <strong>.xlsx</strong> atau
                                <strong>.xls</strong>
                                <br>
                                Maksimal ukuran file: <strong>10 MB</strong>
                            </small>

                        </div>


                        {{-- Selected File --}}
                        <div id="selectedFile"
                             class="d-none">

                            <div class="mb-3">

                                <i class="fas fa-file-excel text-success"
                                   style="font-size:42px;">
                                </i>

                            </div>

                            <h6 id="fileName"
                                class="mb-1">
                            </h6>

                            <small id="fileSize"
                                   class="text-muted">
                            </small>

                            <div class="mt-3">

                                <button type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        id="removeFile">

                                    <i class="fas fa-times me-1"></i>
                                    Ganti File

                                </button>

                            </div>

                        </div>

                    </div>

                    <div class="form-text mt-2">
                        Pastikan data Excel sudah sesuai sebelum melakukan preview.
                    </div>

                </div>


                {{-- =================================================
                     ACTION BUTTON
                ================================================== --}}
                <div class="d-flex justify-content-end gap-2 mt-4">

                    <a href="{{ route('users.index') }}"
                       class="btn btn-light">

                        <i class="fas fa-times me-1"></i>
                        Batal

                    </a>


                    <button type="submit"
                            class="btn btn-primary"
                            id="previewButton"
                            disabled>

                        <i class="fas fa-eye me-1"></i>
                        Preview Data

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


{{-- =============================================================
     STYLE
============================================================= --}}
<style>

    .border-dashed {
        border-style: dashed !important;
    }

    #uploadArea:hover {
        background-color: #f8f9fa;
        border-color: #198754 !important;
    }

    #uploadArea.drag-over {
        background-color: #f0fdf4;
        border-color: #198754 !important;
    }

    #previewButton:disabled {
        cursor: not-allowed;
        opacity: .65;
    }

</style>


{{-- =============================================================
     SCRIPT
============================================================= --}}
<script>

document.addEventListener('DOMContentLoaded', function () {

    const uploadArea = document.getElementById('uploadArea');
    const fileInput = document.getElementById('excel_file');

    const uploadPlaceholder =
        document.getElementById('uploadPlaceholder');

    const selectedFile =
        document.getElementById('selectedFile');

    const fileName =
        document.getElementById('fileName');

    const fileSize =
        document.getElementById('fileSize');

    const removeFile =
        document.getElementById('removeFile');

    const previewButton =
        document.getElementById('previewButton');

    const form =
        document.getElementById('userImportForm');


    /*
     * ==========================================================
     * CLICK UPLOAD AREA
     * ==========================================================
     */

    uploadArea.addEventListener('click', function (e) {

        if (e.target.closest('#removeFile')) {
            return;
        }

        fileInput.click();

    });


    /*
     * ==========================================================
     * FILE SELECTED
     * ==========================================================
     */

    fileInput.addEventListener('change', function () {

        if (this.files.length > 0) {

            handleFile(this.files[0]);

        }

    });


    /*
     * ==========================================================
     * HANDLE FILE
     * ==========================================================
     */

    function handleFile(file) {

        const allowedExtensions = [
            'xlsx',
            'xls'
        ];

        const extension =
            file.name.split('.').pop().toLowerCase();


        /*
         * Check extension
         */

        if (!allowedExtensions.includes(extension)) {

            alert(
                'Format file tidak valid. Silakan pilih file Excel (.xlsx atau .xls).'
            );

            resetFile();

            return;

        }


        /*
         * Check size
         * 10 MB
         */

        const maxSize =
            10 * 1024 * 1024;

        if (file.size > maxSize) {

            alert(
                'Ukuran file terlalu besar. Maksimal 10 MB.'
            );

            resetFile();

            return;

        }


        /*
         * Show selected file
         */

        uploadPlaceholder.classList.add('d-none');

        selectedFile.classList.remove('d-none');

        fileName.textContent =
            file.name;

        fileSize.textContent =
            formatFileSize(file.size);

        previewButton.disabled = false;

    }


    /*
     * ==========================================================
     * REMOVE / CHANGE FILE
     * ==========================================================
     */

    removeFile.addEventListener('click', function (e) {

        e.preventDefault();
        e.stopPropagation();

        resetFile();

    });


    function resetFile() {

        fileInput.value = '';

        uploadPlaceholder.classList.remove('d-none');

        selectedFile.classList.add('d-none');

        fileName.textContent = '';

        fileSize.textContent = '';

        previewButton.disabled = true;

    }


    /*
     * ==========================================================
     * FORMAT FILE SIZE
     * ==========================================================
     */

    function formatFileSize(bytes) {

        if (bytes === 0) {
            return '0 Bytes';
        }

        const sizes = [
            'Bytes',
            'KB',
            'MB',
            'GB'
        ];

        const i =
            Math.floor(
                Math.log(bytes) /
                Math.log(1024)
            );

        return (
            Math.round(
                bytes /
                Math.pow(1024, i) *
                100
            ) / 100
        ) + ' ' + sizes[i];

    }


    /*
     * ==========================================================
     * DRAG & DROP
     * ==========================================================
     */

    uploadArea.addEventListener(
        'dragover',
        function (e) {

            e.preventDefault();

            uploadArea.classList.add(
                'drag-over'
            );

        }
    );


    uploadArea.addEventListener(
        'dragleave',
        function () {

            uploadArea.classList.remove(
                'drag-over'
            );

        }
    );


    uploadArea.addEventListener(
        'drop',
        function (e) {

            e.preventDefault();

            uploadArea.classList.remove(
                'drag-over'
            );

            const files =
                e.dataTransfer.files;

            if (files.length > 0) {

                const file =
                    files[0];

                /*
                 * Karena input file tidak selalu bisa
                 * langsung diisi dari DataTransfer,
                 * kita tetap validasi file terlebih dahulu.
                 */

                try {

                    const dataTransfer =
                        new DataTransfer();

                    dataTransfer.items.add(file);

                    fileInput.files =
                        dataTransfer.files;

                    handleFile(file);

                } catch (error) {

                    handleFile(file);

                }

            }

        }
    );


    /*
     * ==========================================================
     * SUBMIT
     * ==========================================================
     */

    form.addEventListener('submit', function () {

        previewButton.disabled = true;

        previewButton.innerHTML =
            '<i class="fas fa-spinner fa-spin me-1"></i> Membaca Excel...';

    });

});

</script>

@endsection