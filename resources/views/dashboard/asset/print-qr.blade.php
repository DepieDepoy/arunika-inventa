@extends('dashboard.layouts.wrapper')

@section('title', 'Print QR Asset')

@section('content')

<style>
    /* =========================================================
       SCREEN
    ========================================================= */

    .qr-print-wrapper {
        width: 100%;
    }

    .qr-toolbar {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 16px;
        margin-bottom: 20px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    }

    .qr-toolbar-title {
        font-size: 18px;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 4px;
    }

    .qr-toolbar-subtitle {
        font-size: 13px;
        color: #6b7280;
        margin-bottom: 15px;
    }

    .qr-settings {
        display: flex;
        flex-wrap: wrap;
        align-items: end;
        gap: 12px;
    }

    .qr-setting-item {
        min-width: 150px;
    }

    .qr-setting-item label {
        display: block;
        font-size: 12px;
        font-weight: 600;
        color: #4b5563;
        margin-bottom: 5px;
    }

    .qr-setting-item select {
        width: 100%;
        height: 38px;
        border: 1px solid #d1d5db;
        border-radius: 7px;
        padding: 0 10px;
        background: #fff;
        font-size: 13px;
    }

    .qr-actions {
        display: flex;
        gap: 8px;
        margin-left: auto;
    }

    .qr-actions .btn {
        height: 38px;
    }

    /* =========================================================
       QR GRID
    ========================================================= */

    .qr-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 16px;
    }

    .qr-label {
        background: #fff;
        border: 1px solid #dfe3e8;
        border-radius: 10px;
        padding: 14px;
        min-height: 220px;

        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;

        text-align: center;

        break-inside: avoid;
        page-break-inside: avoid;
    }

    .qr-code-container {
        width: 150px;
        height: 150px;

        display: flex;
        align-items: center;
        justify-content: center;

        margin-bottom: 10px;
    }

    .qr-code-container img,
    .qr-code-container canvas {
        display: block;
        max-width: 100%;
        max-height: 100%;
    }

    .qr-asset-code {
        font-size: 14px;
        font-weight: 700;
        color: #111827;
        line-height: 1.25;
        word-break: break-word;
    }

    .qr-asset-name {
        font-size: 11px;
        color: #6b7280;
        line-height: 1.3;
        margin-top: 3px;
        max-width: 95%;
        word-break: break-word;
    }

    .qr-loading {
        font-size: 12px;
        color: #9ca3af;
    }

    .qr-empty {
        background: #fff;
        border: 1px dashed #d1d5db;
        border-radius: 10px;
        padding: 40px 20px;
        text-align: center;
        color: #6b7280;
    }

    /* =========================================================
       RESPONSIVE
    ========================================================= */

    @media (max-width: 1200px) {
        .qr-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }

    @media (max-width: 900px) {
        .qr-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .qr-actions {
            width: 100%;
            margin-left: 0;
        }
    }

    @media (max-width: 576px) {
        .qr-grid {
            grid-template-columns: 1fr;
        }

        .qr-settings {
            display: block;
        }

        .qr-setting-item {
            width: 100%;
            margin-bottom: 10px;
        }

        .qr-actions {
            display: flex;
        }

        .qr-actions .btn {
            flex: 1;
        }
    }

    /* =========================================================
       PRINT
    ========================================================= */

    @media print {

        @page {
            margin: 8mm;
        }

        html,
        body {
            background: #fff !important;
        }

        body {
            margin: 0 !important;
            padding: 0 !important;
        }

        /*
         * Sembunyikan semua elemen dashboard.
         */
        body > * {
            visibility: hidden !important;
        }

        .qr-print-wrapper,
        .qr-print-wrapper * {
            visibility: visible !important;
        }

        .qr-print-wrapper {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }

        .qr-toolbar {
            display: none !important;
        }

        .qr-grid {
            display: grid !important;
            gap: 5mm !important;
        }

        .qr-label {
            border: 1px solid #000 !important;
            border-radius: 0 !important;
            box-shadow: none !important;
            background: #fff !important;

            padding: 4mm !important;

            break-inside: avoid !important;
            page-break-inside: avoid !important;
        }

        .qr-code-container {
            margin-bottom: 2mm !important;
        }

        .qr-asset-code {
            color: #000 !important;
        }

        .qr-asset-name {
            color: #000 !important;
        }

        /*
         * Page break antar halaman.
         */
        .qr-page-break {
            page-break-after: always;
            break-after: page;
        }
    }
</style>

<div class="qr-print-wrapper">

    {{-- =====================================================
         HIDDEN CONFIG
         Blade hanya digunakan di HTML attribute.
         Tidak ada Blade expression di dalam JavaScript.
    ====================================================== --}}

    <div
        id="qrPrintConfig"
        data-qr-base-url="{{ e(url('/assets/qr')) }}"
        style="display:none;"
    ></div>

    {{-- =====================================================
         TOOLBAR
    ====================================================== --}}

    <div class="qr-toolbar">

        <div class="qr-toolbar-title">
            Print QR Asset
        </div>

        <div class="qr-toolbar-subtitle">
            Total {{ $assets->count() }} asset dipilih untuk dicetak.
        </div>

        <div class="qr-settings">

            {{-- Ukuran QR --}}
            <div class="qr-setting-item">

                <label for="qrSize">
                    Ukuran QR
                </label>

                <select id="qrSize">
                    <option value="40">
                        Kecil
                    </option>

                    <option value="50" selected>
                        Sedang
                    </option>

                    <option value="60">
                        Besar
                    </option>
                </select>

            </div>

            {{-- Jumlah kolom --}}
            <div class="qr-setting-item">

                <label for="qrColumns">
                    Kolom
                </label>

                <select id="qrColumns">

                    <option value="2">
                        2 Kolom
                    </option>

                    <option value="3">
                        3 Kolom
                    </option>

                    <option value="4" selected>
                        4 Kolom
                    </option>

                </select>

            </div>

            {{-- Tampilkan nama --}}
            <div class="qr-setting-item">

                <label for="showAssetName">
                    Nama Asset
                </label>

                <select id="showAssetName">

                    <option value="yes" selected>
                        Tampilkan
                    </option>

                    <option value="no">
                        Sembunyikan
                    </option>

                </select>

            </div>

            {{-- Action --}}
            <div class="qr-actions">

                <button
                    type="button"
                    class="btn btn-primary"
                    id="btnPrintQr"
                >
                    <i class="fa fa-print me-1"></i>
                    Print QR
                </button>

                <button
                    type="button"
                    class="btn btn-secondary"
                    id="btnBack"
                >
                    <i class="fa fa-arrow-left me-1"></i>
                    Kembali
                </button>

            </div>

        </div>

    </div>

    {{-- =====================================================
         QR GRID
    ====================================================== --}}

    @if($assets->count() > 0)

        <div
            class="qr-grid"
            id="qrGrid"
        >

            @foreach($assets as $asset)

                <div
                    class="qr-label"
                    data-asset-id="{{ $asset->id }}"
                    data-qr-token="{{ e($asset->qr_token) }}"
                >

                    <div class="qr-code-container">

                        <span class="qr-loading">
                            Generating...
                        </span>

                    </div>

                    <div class="qr-asset-code">
                        {{ $asset->asset_code }}
                    </div>

                    <div class="qr-asset-name">
                        {{ $asset->asset_name }}
                    </div>

                </div>

            @endforeach

        </div>

    @else

        <div class="qr-empty">

            <i
                class="fa fa-qrcode"
                style="font-size:40px;margin-bottom:10px;"
            ></i>

            <div>
                Tidak ada asset yang dipilih.
            </div>

        </div>

    @endif

</div>

{{-- =========================================================
     QR CODE LIBRARY
========================================================= --}}

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<script>
(function () {

    'use strict';

    /*
     * ========================================================
     * ELEMENT
     * ========================================================
     */

    var qrConfigElement = document.getElementById('qrPrintConfig');
    var qrGrid = document.getElementById('qrGrid');

    var qrSizeElement = document.getElementById('qrSize');
    var qrColumnsElement = document.getElementById('qrColumns');
    var showAssetNameElement = document.getElementById('showAssetName');

    var btnPrintQr = document.getElementById('btnPrintQr');
    var btnBack = document.getElementById('btnBack');


    /*
     * ========================================================
     * QR BASE URL
     * ========================================================
     *
     * Contoh:
     *
     * http://10.241.240.135:8001/assets/qr
     *
     * atau production:
     *
     * https://vasetra.arunikasolusiinovasi.co.id/assets/qr
     *
     */

    var qrBaseUrl = '';

    if (qrConfigElement) {

        qrBaseUrl =
            qrConfigElement.getAttribute('data-qr-base-url') || '';

    }


    /*
     * ========================================================
     * GET QR SIZE
     * ========================================================
     */

    function getQrSize() {

        if (!qrSizeElement) {
            return 50;
        }

        var value =
            parseInt(qrSizeElement.value, 10);

        if (isNaN(value)) {
            return 50;
        }

        return value;
    }


    /*
     * ========================================================
     * GET COLUMNS
     * ========================================================
     */

    function getColumns() {

        if (!qrColumnsElement) {
            return 4;
        }

        var value =
            parseInt(qrColumnsElement.value, 10);

        if (isNaN(value)) {
            return 4;
        }

        return value;
    }


    /*
     * ========================================================
     * GET FONT SIZE
     * ========================================================
     */

    function getFontSizes() {

        var qrSize = getQrSize();

        var result = {
            code: 14,
            name: 11
        };

        if (qrSize === 40) {

            result.code = 12;
            result.name = 10;

        } else if (qrSize === 50) {

            result.code = 14;
            result.name = 11;

        } else if (qrSize === 60) {

            result.code = 16;
            result.name = 12;

        }

        return result;
    }


    /*
     * ========================================================
     * UPDATE GRID
     * ========================================================
     */

    function updateGrid() {

        if (!qrGrid) {
            return;
        }

        var columns = getColumns();

        qrGrid.style.gridTemplateColumns =
            'repeat(' +
            columns +
            ', minmax(0, 1fr))';

    }


    /*
     * ========================================================
     * UPDATE NAME
     * ========================================================
     */

    function updateAssetNameVisibility() {

        var labels =
            document.querySelectorAll('.qr-label');

        var showName = true;

        if (showAssetNameElement) {

            showName =
                showAssetNameElement.value === 'yes';

        }

        labels.forEach(function (label) {

            var nameElement =
                label.querySelector('.qr-asset-name');

            if (!nameElement) {
                return;
            }

            if (showName) {

                nameElement.style.display = 'block';

            } else {

                nameElement.style.display = 'none';

            }

        });

    }


    /*
     * ========================================================
     * UPDATE FONT
     * ========================================================
     */

    function updateFontSizes() {

        var sizes =
            getFontSizes();

        var codes =
            document.querySelectorAll('.qr-asset-code');

        var names =
            document.querySelectorAll('.qr-asset-name');

        codes.forEach(function (element) {

            element.style.fontSize =
                sizes.code + 'px';

        });

        names.forEach(function (element) {

            element.style.fontSize =
                sizes.name + 'px';

        });

    }


    /*
     * ========================================================
     * GENERATE ONE QR
     * ========================================================
     */

    function generateQr(label) {

        if (!label) {
            return;
        }

        var container =
            label.querySelector('.qr-code-container');

        if (!container) {
            return;
        }

        /*
         * PENTING:
         *
         * QR menggunakan qr_token.
         * BUKAN asset ID.
         */

        var qrToken =
            label.getAttribute('data-qr-token') || '';

        if (!qrToken) {

            container.innerHTML =
                '<span class="qr-loading">QR token tidak tersedia</span>';

            return;

        }

        if (!qrBaseUrl) {

            container.innerHTML =
                '<span class="qr-loading">QR URL tidak tersedia</span>';

            return;

        }

        /*
         * Bersihkan container.
         */

        container.innerHTML = '';

        /*
         * Bentuk URL QR.
         *
         * Tidak menggunakan template literal.
         */

        var qrUrl =
            qrBaseUrl + '/' + qrToken;

        /*
         * Ukuran QR.
         */

        var size =
            getQrSize();

        /*
         * Generate QR.
         */

        try {

            new QRCode(
                container,
                {
                    text: qrUrl,
                    width: size,
                    height: size,
                    correctLevel: QRCode.CorrectLevel.H
                }
            );

        } catch (error) {

            console.error(
                'QR generation error:',
                error
            );

            container.innerHTML =
                '<span class="qr-loading">Gagal membuat QR</span>';

        }

    }


    /*
     * ========================================================
     * GENERATE ALL QR
     * ========================================================
     */

    function generateAllQr() {

        var labels =
            document.querySelectorAll('.qr-label');

        labels.forEach(function (label) {

            generateQr(label);

        });

        updateGrid();
        updateAssetNameVisibility();
        updateFontSizes();

    }


    /*
     * ========================================================
     * REFRESH QR
     * ========================================================
     */

    function refreshQr() {

        var labels =
            document.querySelectorAll('.qr-label');

        labels.forEach(function (label) {

            generateQr(label);

        });

        updateGrid();
        updateAssetNameVisibility();
        updateFontSizes();

    }


    /*
     * ========================================================
     * PRINT
     * ========================================================
     */

    function printQr() {

        /*
         * Pastikan QR sudah dibuat.
         */

        generateAllQr();

        /*
         * Tunggu sebentar agar canvas QR selesai dirender.
         */

        setTimeout(function () {

            window.print();

        }, 500);

    }


    /*
     * ========================================================
     * BACK
     * ========================================================
     */

    function goBack() {

        if (
            document.referrer &&
            document.referrer !== window.location.href
        ) {

            window.history.back();

            return;

        }

        window.location.href =
            '/dashboard/assets';

    }


    /*
     * ========================================================
     * EVENT
     * ========================================================
     */

    if (qrSizeElement) {

        qrSizeElement.addEventListener(
            'change',
            function () {

                refreshQr();

            }
        );

    }


    if (qrColumnsElement) {

        qrColumnsElement.addEventListener(
            'change',
            function () {

                updateGrid();

            }
        );

    }


    if (showAssetNameElement) {

        showAssetNameElement.addEventListener(
            'change',
            function () {

                updateAssetNameVisibility();

            }
        );

    }


    if (btnPrintQr) {

        btnPrintQr.addEventListener(
            'click',
            function () {

                printQr();

            }
        );

    }


    if (btnBack) {

        btnBack.addEventListener(
            'click',
            function () {

                goBack();

            }
        );

    }


    /*
     * ========================================================
     * INITIALIZE
     * ========================================================
     */

    document.addEventListener(
        'DOMContentLoaded',
        function () {

            updateGrid();
            updateAssetNameVisibility();
            updateFontSizes();

            /*
             * QRCode library mungkin belum selesai dimuat.
             * Beri sedikit waktu sebelum generate.
             */

            setTimeout(
                function () {

                    generateAllQr();

                },
                300
            );

        }
    );

})();
</script>

@endsection

