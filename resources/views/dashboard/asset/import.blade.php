@extends('dashboard.layouts.wrapper')

@section('title', 'Import Assets')

@section('content')

@if(session('error'))
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle me-1"></i>
        {{ session('error') }}
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger">
        <strong>Terjadi error:</strong>
        <ul class="mb-0 mt-2">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
<style>
    .import-page-header {
        margin-bottom: 1.5rem;
    }

    .import-page-header h4 {
        margin-bottom: .35rem;
        font-weight: 600;
    }

    .import-page-header p {
        margin-bottom: 0;
        color: #6c757d;
    }

    .import-card {
        background: #fff;
        border: 1px solid #e9ecef;
        border-radius: 12px;
        overflow: hidden;
        margin-bottom: 1.5rem;
    }

    .import-card-header {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid #e9ecef;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }

    .import-card-header h5 {
        margin: 0;
        font-size: 1rem;
        font-weight: 600;
    }

    .import-card-body {
        padding: 1.5rem;
    }

    /* Upload */
    .upload-area {
        border: 2px dashed #d9dee3;
        border-radius: 12px;
        padding: 3rem 1.5rem;
        text-align: center;
        background: #fafbfc;
        cursor: pointer;
        transition: all .2s ease;
    }

    .upload-area:hover {
        border-color: #adb5bd;
        background: #f8f9fa;
    }

    .upload-icon {
        width: 64px;
        height: 64px;
        margin: 0 auto 1rem;
        border-radius: 50%;
        background: #f1f3f5;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 26px;
        color: #6c757d;
    }

    .upload-area h6 {
        margin-bottom: .4rem;
        font-weight: 600;
    }

    .upload-area p {
        color: #6c757d;
        font-size: .875rem;
        margin-bottom: 1rem;
    }

    .selected-file {
        display: none;
        margin-top: 1rem;
        padding: .75rem 1rem;
        background: #f1f3f5;
        border-radius: 8px;
        text-align: left;
        font-size: .875rem;
    }

    .selected-file i {
        margin-right: .5rem;
    }

    /* Steps */
    .import-steps {
        display: flex;
        flex-direction: column;
        gap: 1.15rem;
    }

    .import-step {
        display: flex;
        gap: .8rem;
        align-items: flex-start;
    }

    .step-number {
        width: 30px;
        min-width: 30px;
        height: 30px;
        border-radius: 50%;
        background: #f1f3f5;
        color: #495057;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .8rem;
        font-weight: 600;
    }

    .import-step strong {
        display: block;
        font-size: .875rem;
        margin-bottom: .15rem;
    }

    .import-step span {
        display: block;
        color: #6c757d;
        font-size: .8rem;
        line-height: 1.5;
    }

    /* Information */
    .import-info {
        padding: 1rem;
        border-radius: 10px;
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        font-size: .85rem;
        color: #495057;
    }

    .import-info strong {
        display: block;
        margin-bottom: .4rem;
    }

    .format-list {
        margin: 0;
        padding-left: 1.1rem;
        color: #6c757d;
        font-size: .83rem;
    }

    .format-list li {
        margin-bottom: .4rem;
    }

    .action-buttons {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 1.25rem;
        gap: 1rem;
    }

    @media (max-width: 767px) {
        .import-card-header,
        .action-buttons {
            flex-direction: column;
            align-items: stretch;
        }
    }
</style>

<div class="content-wrapper">
<!-- Content -->
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="row">
    <div class="col-xxl-12 mb-12 order-0">
    <div class="card">

    <div class="card-header d-flex justify-content-between align-items-center">

        <div>
            <h5 class="mb-0">Import Assets</h5>
            <small class="text-muted">
                Import data aset dalam jumlah banyak menggunakan file Excel.
            </small>
        </div>

        <div class="d-flex gap-2">

        </div>
    </div>
    <div class="card-body">
        <div class="card permission-role-card">
            <div class="card-body">
                <div class="row">

    {{-- =====================================================
         LEFT : UPLOAD
    ====================================================== --}}
    <div class="col-lg-8">

        <div class="import-card">

            <div class="import-card-header">

                <div>
                    <h5>Upload File Excel</h5>
                </div>

                {{-- NANTI ROUTE INI KITA BUAT --}}
                <a
                    href="{{ route('assets.import.template') }}"
                    class="btn btn-outline-primary"
                >
                    <i class="fas fa-download me-1"></i>
                    Download Template
                </a>

            </div>


            <div class="import-card-body">
            
                {{-- NANTI ACTION INI KITA BUAT --}}
                <form action="{{ route('assets.import.preview') }}"
                      method="POST"
                      enctype="multipart/form-data">
                    @csrf
                    <div class="upload-area"
                         onclick="document.getElementById('excel_file').click()">
                        <div class="upload-icon">
                            <i class="fas fa-file-excel"></i>
                        </div>
                        <h6>Pilih File Excel</h6>
                        <p>
                            Upload file Excel yang berisi data aset
                            <br>
                            Format yang didukung: <strong>.xlsx</strong> dan <strong>.xls</strong>
                        </p>
                        <button type="button"
                                class="btn btn-outline-primary btn-sm"
                                onclick="event.stopPropagation(); document.getElementById('excel_file').click()">
                            <i class="fas fa-folder-open me-1"></i>
                            Pilih File
                        </button>
                        <input type="file"
                               id="excel_file"
                               name="excel_file"
                               accept=".xlsx,.xls"
                               hidden>
                        <div class="selected-file"
                             id="selectedFile">

                            <i class="fas fa-file-excel"></i>

                            <span id="fileName"></span>

                        </div>

                    </div>


                    {{-- ERROR --}}
                    @error('excel_file')

                        <div class="text-danger small mt-2">
                            {{ $message }}
                        </div>

                    @enderror


                    <div class="import-info mt-4">

                        <strong>
                            <i class="fas fa-info-circle me-1"></i>
                            Perhatian
                        </strong>

                        Pastikan data Excel mengikuti format template.
                        Data akan divalidasi terlebih dahulu sebelum disimpan
                        ke database.

                    </div>


                    <div class="action-buttons">

                        <a href="{{ route('assets.index') }}"
                           class="btn btn-light">

                            <i class="fas fa-arrow-left me-1"></i>
                            Kembali

                        </a>


                        <button type="submit"
                                id="previewButton"
                                class="btn btn-primary"
                                disabled>

                            <i class="fas fa-eye me-1"></i>
                            Preview Data

                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>



    {{-- =====================================================
         RIGHT : INSTRUCTIONS
    ====================================================== --}}
    <div class="col-lg-4">

        <div class="import-card">

            <div class="import-card-header">

                <h5>Cara Import</h5>

            </div>


            <div class="import-card-body">

                <div class="import-steps">

                    <div class="import-step">

                        <div class="step-number">
                            1
                        </div>

                        <div>
                            <strong>Download Template</strong>

                            <span>
                                Gunakan template Excel yang sudah
                                disediakan.
                            </span>
                        </div>

                    </div>


                    <div class="import-step">

                        <div class="step-number">
                            2
                        </div>

                        <div>
                            <strong>Isi Data Asset</strong>

                            <span>
                                Isi data asset sesuai dengan
                                kolom yang tersedia.
                            </span>
                        </div>

                    </div>


                    <div class="import-step">

                        <div class="step-number">
                            3
                        </div>

                        <div>
                            <strong>Upload Excel</strong>

                            <span>
                                Upload file Excel yang sudah
                                selesai diisi.
                            </span>
                        </div>

                    </div>


                    <div class="import-step">

                        <div class="step-number">
                            4
                        </div>

                        <div>
                            <strong>Preview & Import</strong>

                            <span>
                                Periksa data terlebih dahulu
                                sebelum dimasukkan ke sistem.
                            </span>
                        </div>

                    </div>

                </div>

            </div>

        </div>



        <div class="import-card">

            <div class="import-card-header">

                <h5>Ketentuan File</h5>

            </div>


            <div class="import-card-body">

                <ul class="format-list">

                    <li>
                        Format file <strong>.xlsx</strong> atau <strong>.xls</strong>
                    </li>

                    <li>
                        Header Excel harus mengikuti template.
                    </li>

                    <li>
                        Asset Code harus unik.
                    </li>

                    <li>
                        Purchase Date menggunakan format tanggal.
                    </li>

                    <li>
                        Purchase Price diisi dalam angka.
                    </li>

                    <li>
                        Maksimal ukuran file akan ditentukan
                        pada proses validasi.
                    </li>

                </ul>

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

<script>
document.addEventListener('DOMContentLoaded', function () {

    const fileInput = document.getElementById('excel_file');
    const selectedFile = document.getElementById('selectedFile');
    const fileName = document.getElementById('fileName');
    const previewButton = document.getElementById('previewButton');

    /*
     * Saat file Excel dipilih
     */
    fileInput.addEventListener('change', function () {

        if (this.files.length > 0) {

            fileName.textContent = this.files[0].name;

            selectedFile.style.display = 'block';

            previewButton.disabled = false;

        } else {

            fileName.textContent = '';

            selectedFile.style.display = 'none';

            previewButton.disabled = true;

        }

    });


    /*
     * Saat tombol Preview Data ditekan
     */
    const form = fileInput.closest('form');

    form.addEventListener('submit', function () {

        /*
         * Cegah submit ulang / double click
         */
        previewButton.disabled = true;

        /*
         * Ubah isi tombol
         */
        previewButton.innerHTML = `
            <span class="spinner-border spinner-border-sm me-1"
                  role="status"
                  aria-hidden="true"></span>
            Membaca Excel...
        `;

    });

});
</script>

@endsection
