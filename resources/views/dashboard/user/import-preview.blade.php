@extends('dashboard.layouts.wrapper')

@section('title', 'Preview Import User')

@section('content')

<style>
    .import-page-header {
        margin-bottom: 1.5rem;
    }

    .import-section {
        margin-bottom: 1.5rem;
    }

    .import-section-header {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 1rem;
        padding-bottom: 10px;
        border-bottom: 1px solid #e9ecef;
    }

    .import-section-header i {
        font-size: 20px;
    }

    .import-section-header h5 {
        margin: 0;
        font-weight: 600;
    }

    .preview-info {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
        margin-bottom: 1.5rem;
    }

    .preview-info-item {
        flex: 1;
        min-width: 180px;
        padding: 15px 18px;
        border: 1px solid #e9ecef;
        border-radius: 10px;
        background: #fff;
    }

    .preview-info-label {
        font-size: 13px;
        color: #6c757d;
        margin-bottom: 5px;
    }

    .preview-info-value {
        font-size: 20px;
        font-weight: 700;
    }

    /* =========================================================
    PREVIEW TABLE
    ========================================================= */

    .preview-table-wrapper {
        width: 100%;
        max-height: 600px;

        overflow-x: auto;
        overflow-y: auto;

        border: 1px solid #e9ecef;
        border-radius: 10px;
    }

    /* TABLE */
    .preview-table {
        width: max-content;
        min-width: 100%;
        margin-bottom: 0;

        white-space: nowrap;
        border-collapse: separate;
        border-spacing: 0;
    }

    /* HEADER */
    .preview-table thead th {
        position: sticky;
        top: 0;
        z-index: 10;

        background: #4472C4;
        color: #fff;

        font-weight: 600;
        font-size: 13px;

        padding: 12px 14px;

        vertical-align: middle;
        white-space: nowrap;

        border: none;
        border-bottom: 1px solid #3b64ad;
    }

    /* BODY */
    .preview-table tbody td {
        padding: 11px 14px;

        vertical-align: middle;

        font-size: 13px;
        color: #374151;

        white-space: nowrap;

        border-bottom: 1px solid #e9ecef;
    }

    /* HOVER */
    .preview-table tbody tr:hover td {
        background-color: #f8f9fa;
    }

    /* NOMOR */
    .preview-table .row-number {
        width: 55px;
        min-width: 55px;

        text-align: center;

        color: #6c757d;
        font-weight: 600;
    }

    .import-note {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 15px 18px;
        border-radius: 10px;
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        margin-bottom: 1.5rem;
    }

    .import-note i {
        font-size: 20px;
        margin-top: 1px;
    }

    .import-note-content {
        font-size: 13px;
        line-height: 1.6;
    }

    .import-note-content strong {
        font-weight: 600;
    }

    .empty-preview {
        text-align: center;
        padding: 50px 20px;
        color: #6c757d;
    }

    .empty-preview i {
        font-size: 45px;
        margin-bottom: 15px;
    }

    .action-buttons {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
        margin-top: 1.5rem;
    }

    .action-buttons-right {
        display: flex;
        gap: 10px;
    }

    .badge-staff {
        display: inline-block;
        padding: 5px 10px;
        border-radius: 20px;
        background: #e8f5e9;
        color: #2e7d32;
        font-size: 12px;
        font-weight: 600;
    }

    @media (max-width: 768px) {

        .preview-info {
            flex-direction: column;
        }

        .action-buttons {
            flex-direction: column-reverse;
            align-items: stretch;
        }

        .action-buttons-right {
            flex-direction: column;
        }

        .action-buttons .btn {
            width: 100%;
        }

        .preview-table-wrapper {
            overflow-x: auto;
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
            <h5 class="mb-0">
                Preview Import User
            </h5>
            <small class="text-muted">
                Periksa data sebelum disimpan ke database.
            </small>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('users.import') }}"
                class="btn btn-outline-secondary">

                <i class="fas fa-arrow-left me-1"></i>
                Back to Import

            </a>
        </div>
    </div>

    <!-- =========================================================
         ALERT
    ========================================================== -->

    @if(session('error'))

        <div class="alert alert-danger alert-dismissible fade show"
             role="alert">

            <i class="fas fa-exclamation-circle me-2"></i>

            {{ session('error') }}

            <button type="button"
                    class="btn-close"
                    data-bs-dismiss="alert">
            </button>

        </div>

    @endif


    @if(session('success'))

        <div class="alert alert-success alert-dismissible fade show"
             role="alert">

            <i class="fas fa-check-circle me-2"></i>

            {{ session('success') }}

            <button type="button"
                    class="btn-close"
                    data-bs-dismiss="alert">
            </button>

        </div>

    @endif

    <div class="card-body">
        <div class="card permission-role-card">
            <div class="card-body">
                <div class="row">
                    <!-- =========================================================
         SUMMARY
    ========================================================== -->

    <div class="preview-info">

        <div class="preview-info-item">

            <div class="preview-info-label">
                Total Data
            </div>

            <div class="preview-info-value">
                {{ number_format($totalRows) }}
            </div>

        </div>


        <div class="preview-info-item">

            <div class="preview-info-label">
                Data Ditampilkan
            </div>

            <div class="preview-info-value">
                {{ number_format($previewRows) }}
            </div>

        </div>


        <div class="preview-info-item">

            <div class="preview-info-label">
                Default Role
            </div>

            <div class="preview-info-value">
                <span class="badge-staff">
                    Staff
                </span>
            </div>

        </div>

    </div>


    <!-- =========================================================
         INFORMATION
    ========================================================== -->

    <div class="import-note">

        <i class="fas fa-info-circle text-primary"></i>

        <div class="import-note-content">

            <strong>Informasi Import</strong>

            <br>

            Semua user yang diimport akan otomatis:

            <ul class="mb-0 mt-2">

                <li>
                    Mengikuti <strong>Company</strong> dari user yang melakukan import.
                </li>

                <li>
                    Mendapatkan role default
                    <strong>Staff</strong>.
                </li>

                <li>
                    Mendapatkan password default dari
                    <strong>DEFAULT_USER_PASSWORD</strong>.
                </li>

                <li>
                    Wajib mengganti password saat login pertama.
                </li>

            </ul>

        </div>

    </div>


    <!-- =========================================================
         PREVIEW DATA
    ========================================================== -->

    <div class="card shadow-sm">

        <div class="card-body">

            <div class="import-section">

                <div class="import-section-header">

                    <i class="fas fa-table text-primary"></i>

                    <h5>
                        Data User
                    </h5>

                </div>


                @if(!empty($data) && count($data) > 1)

                    <div class="preview-table-wrapper">

                        <table class="table table-bordered preview-table">

                            <thead>

                                <tr>

                                    <th style="width: 70px;">
                                        No
                                    </th>

                                    <th>
                                        Name
                                    </th>

                                    <th>
                                        NIK
                                    </th>

                                    <th>
                                        Email
                                    </th>

                                    <th>
                                        Phone
                                    </th>

                                    <th style="width: 100px;">
                                        Role
                                    </th>

                                </tr>

                            </thead>


                            <tbody>

                                @foreach(array_slice($data, 1) as $index => $row)

                                    @php
                                        $name = $row[1] ?? '';
                                        $nik = $row[2] ?? '';
                                        $email = $row[3] ?? '';
                                        $phone = $row[4] ?? '';
                                    @endphp

                                    <tr>

                                        <td>
                                            {{ $index + 1 }}
                                        </td>

                                        <td>
                                            {{ $name }}
                                        </td>

                                        <td>
                                            {{ $nik }}
                                        </td>

                                        <td>
                                            {{ $email }}
                                        </td>

                                        <td>
                                            {{ $phone }}
                                        </td>

                                        <td>
                                            <span class="badge-staff">
                                                Staff
                                            </span>
                                        </td>

                                    </tr>

                                @endforeach

                            </tbody>

                        </table>

                    </div>


                    @if($totalRows > 100)

                        <div class="text-muted small mt-3">

                            <i class="fas fa-info-circle me-1"></i>

                            Hanya 100 data pertama yang ditampilkan pada preview.
                            Total data yang akan diimport:
                            <strong>{{ number_format($totalRows) }}</strong>.

                        </div>

                    @endif

                @else

                    <div class="empty-preview">

                        <i class="fas fa-file-excel"></i>

                        <h5>
                            Tidak ada data
                        </h5>

                        <p class="mb-0">
                            Tidak ditemukan data user yang dapat diimport.
                        </p>

                    </div>

                @endif

            </div>


            <!-- =================================================
                 ACTION BUTTONS
            ================================================== -->

            <div class="action-buttons">

                <a href="{{ route('users.import') }}"
                   class="btn btn-outline-secondary">

                    <i class="fas fa-times me-1"></i>

                    Cancel

                </a>


                <div class="action-buttons-right">

                    <a href="{{ route('users.import') }}"
                       class="btn btn-outline-primary">

                        <i class="fas fa-file-upload me-1"></i>

                        Upload Ulang

                    </a>


                    @if(!empty($data) && count($data) > 1 && $totalRows > 0)

                        <form action="{{ route('users.import.store') }}"
                              method="POST"
                              id="importSubmitForm">

                            @csrf

                            <button type="submit"
                                    class="btn btn-primary"
                                    id="submitImportBtn">

                                <i class="fas fa-cloud-upload-alt me-1"></i>

                                Submit Import

                            </button>

                        </form>

                    @endif

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

<!-- =========================================================
     SUBMIT LOADING
========================================================== -->

<script>
document.addEventListener('DOMContentLoaded', function () {

    const form = document.getElementById('importSubmitForm');
    const button = document.getElementById('submitImportBtn');

    if (!form || !button) {
        return;
    }

    form.addEventListener('submit', function () {

        /*
        |--------------------------------------------------------------------------
        | Disable semua tombol/link action
        |--------------------------------------------------------------------------
        */

        const actionButtons = document.querySelectorAll(
            '.action-buttons a, .action-buttons button'
        );

        actionButtons.forEach(function (element) {

            element.style.pointerEvents = 'none';
            element.style.opacity = '0.6';

            if (element.tagName === 'BUTTON') {
                element.disabled = true;
            }

        });

        /*
        |--------------------------------------------------------------------------
        | Ubah tombol Submit
        |--------------------------------------------------------------------------
        */

        button.disabled = true;

        button.innerHTML = `
            <span class="spinner-border spinner-border-sm me-1"
                  role="status"
                  aria-hidden="true">
            </span>

            Sedang mengimport...
        `;

    });

});
</script>

@endsection