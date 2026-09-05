@extends('dashboard.layouts.wrapper')

@section('title', 'Preview Import Assets')

@section('content')

<style>
    .import-page {
        padding-bottom: 30px;
    }

    .import-header {
        margin-bottom: 1.5rem;
    }

    .import-header h4 {
        font-weight: 700;
        color: #1f2937;
    }

    .import-header p {
        font-size: 14px;
    }

    .import-card {
        border: 0;
        border-radius: 14px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
        overflow: hidden;
    }

    .import-card .card-header {
        background: #fff;
        border-bottom: 1px solid #eef0f3;
        padding: 18px 22px;
    }

    .import-card .card-body {
        padding: 22px;
    }

    /* SUMMARY */
    .summary-card {
        border: 1px solid #eef0f3;
        border-radius: 12px;
        padding: 16px 18px;
        height: 100%;
        background: #fff;
    }

    .summary-icon {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f3f4f6;
        color: #374151;
        font-size: 18px;
    }

    .summary-label {
        font-size: 12px;
        color: #6b7280;
        margin-bottom: 3px;
    }

    .summary-value {
        font-size: 22px;
        font-weight: 700;
        color: #1f2937;
    }

    /* TABLE */
    .import-table-wrapper {
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        overflow: auto;
        max-height: 600px;
    }

    .import-table {
        margin-bottom: 0;
        white-space: nowrap;
    }

    .import-table thead th {
        position: sticky;
        top: 0;
        z-index: 2;
        background: #f8f9fa;
        color: #374151;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .3px;
        padding: 12px 10px;
        border-bottom: 2px solid #dee2e6;
    }

    .import-table tbody td {
        font-size: 13px;
        vertical-align: middle;
        padding: 10px;
        color: #374151;
    }

    .import-table tbody tr:hover {
        background: #f8fafc;
    }

    .row-number {
        width: 50px;
        text-align: center;
        color: #6b7280;
        font-weight: 600;
    }

    /* INFO */
    .import-info {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 14px 16px;
        border-radius: 10px;
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        margin-top: 20px;
    }

    .import-info i {
        margin-top: 2px;
        color: #6b7280;
    }

    .import-info-text {
        font-size: 13px;
        color: #6b7280;
        line-height: 1.6;
    }

    .import-info-text strong {
        color: #374151;
    }

    /* ACTION */
    .import-action {
        margin-top: 22px;
        padding-top: 20px;
        border-top: 1px solid #eef0f3;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 15px;
    }

    .btn-import {
        min-width: 190px;
        font-weight: 600;
        border-radius: 8px;
    }

    .btn-back {
        border-radius: 8px;
    }

    /* EMPTY */
    .empty-import {
        text-align: center;
        padding: 60px 20px;
    }

    .empty-import-icon {
        width: 80px;
        height: 80px;
        margin: 0 auto 20px;
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f3f4f6;
        color: #9ca3af;
        font-size: 34px;
    }

    .empty-import h5 {
        font-weight: 700;
        color: #374151;
    }
</style>

<div class="container-fluid import-page">

    {{-- =========================================================
         HEADER
    ========================================================== --}}
    <div class="d-flex justify-content-between align-items-center import-header">

        <div>
            <h4 class="mb-1">
                <i class="fas fa-file-import me-2"></i>
                Preview Import Assets
            </h4>

            <p class="text-muted mb-0">
                Periksa kembali data Excel sebelum disimpan ke database.
            </p>
        </div>

        <a href="{{ route('assets.import') }}"
           class="btn btn-light btn-back">

            <i class="fas fa-arrow-left me-1"></i>
            Kembali
        </a>

    </div>


    @if(count($data) > 0)

        @php
            $totalRows = max(count($data) - 1, 0);
            $totalColumns = count($data[0] ?? []);
        @endphp


        {{-- =====================================================
             SUMMARY
        ====================================================== --}}
        <div class="row g-3 mb-4">

            <div class="col-md-4">
                <div class="summary-card">

                    <div class="d-flex align-items-center gap-3">

                        <div class="summary-icon">
                            <i class="fas fa-list"></i>
                        </div>

                        <div>
                            <div class="summary-label">
                                Total Data
                            </div>

                            <div class="summary-value">
                                {{ $totalRows }}
                            </div>
                        </div>

                    </div>

                </div>
            </div>


            <div class="col-md-4">
                <div class="summary-card">

                    <div class="d-flex align-items-center gap-3">

                        <div class="summary-icon">
                            <i class="fas fa-columns"></i>
                        </div>

                        <div>
                            <div class="summary-label">
                                Kolom Excel
                            </div>

                            <div class="summary-value">
                                {{ $totalColumns }}
                            </div>
                        </div>

                    </div>

                </div>
            </div>


            <div class="col-md-4">
                <div class="summary-card">

                    <div class="d-flex align-items-center gap-3">

                        <div class="summary-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>

                        <div>
                            <div class="summary-label">
                                Status Preview
                            </div>

                            <div class="summary-value"
                                 style="font-size: 16px;">
                                Siap Diimport
                            </div>
                        </div>

                    </div>

                </div>
            </div>

        </div>


        {{-- =====================================================
             MAIN CARD
        ====================================================== --}}
        <div class="card import-card">

            <div class="card-header">

                <div class="d-flex justify-content-between align-items-center">

                    <div>
                        <strong>
                            <i class="fas fa-table me-2"></i>
                            Data Excel
                        </strong>

                        <div class="text-muted mt-1"
                             style="font-size: 12px;">

                            Pastikan data sudah sesuai dengan format import asset.

                        </div>
                    </div>

                    <span class="badge bg-light text-dark">
                        {{ $totalRows }} Data
                    </span>

                </div>

            </div>


            <div class="card-body">

                {{-- =================================================
                     TABLE
                ================================================== --}}
                <div class="import-table-wrapper">

                    <table class="table table-bordered table-hover import-table">

                        <thead>

                            <tr>

                                <th class="row-number">
                                    #
                                </th>

                                @foreach($data[0] as $header)

                                    <th>
                                        {{ $header }}
                                    </th>

                                @endforeach

                            </tr>

                        </thead>


                        <tbody>

                            @foreach(array_slice($data, 1) as $index => $row)

                                <tr>

                                    <td class="row-number">
                                        {{ $index + 1 }}
                                    </td>

                                    @foreach($row as $value)

                                        <td>
                                            {{ $value ?? '-' }}
                                        </td>

                                    @endforeach

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>


                {{-- =================================================
                     INFORMATION
                ================================================== --}}
                <div class="import-info">

                    <i class="fas fa-info-circle"></i>

                    <div class="import-info-text">

                        <strong>Informasi Import</strong><br>

                        Data di atas masih dalam tahap preview dan
                        <strong>belum disimpan ke database</strong>.

                        Saat tombol <strong>Submit & Import</strong>
                        ditekan, sistem akan memproses seluruh data.

                        <br>

                        Category dan Sub Category yang belum tersedia
                        akan <strong>dibuat otomatis</strong> berdasarkan
                        data Excel.

                    </div>

                </div>


                {{-- =================================================
                     ACTION
                ================================================== --}}
                <div class="import-action">

                    <div class="text-muted"
                         style="font-size: 13px;">

                        <i class="fas fa-shield-alt me-1"></i>

                        Pastikan data sudah benar sebelum melanjutkan.

                    </div>


                    <div class="d-flex gap-2">

                        <a href="{{ route('assets.import') }}"
                           class="btn btn-light">

                            <i class="fas fa-times me-1"></i>
                            Batal

                        </a>


                        {{-- =========================================
                             SUBMIT IMPORT
                        ========================================== --}}
                        <form action="{{ route('assets.import.store') }}"
                              method="POST"
                              id="importForm">

                            @csrf
                            <button type="submit"
                                    class="btn btn-primary btn-import"
                                    id="btnSubmitImport">

                                <i class="fas fa-cloud-upload-alt me-1"></i>

                                Submit & Import

                            </button>

                        </form>

                    </div>

                </div>

            </div>

        </div>


    @else

        {{-- =====================================================
             EMPTY STATE
        ====================================================== --}}
        <div class="card import-card">

            <div class="card-body">

                <div class="empty-import">

                    <div class="empty-import-icon">
                        <i class="fas fa-file-excel"></i>
                    </div>

                    <h5>
                        Data Excel Kosong
                    </h5>

                    <p class="text-muted mb-4">
                        Tidak ditemukan data yang dapat ditampilkan
                        dari file Excel.
                    </p>

                    <a href="{{ route('assets.import') }}"
                       class="btn btn-primary">

                        <i class="fas fa-upload me-1"></i>

                        Upload File Lain

                    </a>

                </div>

            </div>

        </div>

    @endif

</div>


{{-- =============================================================
     SUBMIT LOADING
============================================================= --}}
<script>
document.addEventListener('DOMContentLoaded', function () {

    const form = document.getElementById('importForm');
    const button = document.getElementById('btnSubmitImport');

    if (form && button) {

        form.addEventListener('submit', function () {

            button.disabled = true;

            button.innerHTML = `
                <span class="spinner-border spinner-border-sm me-1"
                      role="status"
                      aria-hidden="true"></span>
                Sedang Mengimport...
            `;

        });

    }

});
</script>

@endsection
