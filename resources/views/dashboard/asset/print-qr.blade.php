@extends('dashboard.layouts.wrapper')

@section('title', 'Print QR Asset')

@section('content')

<style>

    /*
    |--------------------------------------------------------------------------
    | PAGE
    |--------------------------------------------------------------------------
    */

    .qr-print-page {
        padding-bottom: 40px;
    }

    /*
    |--------------------------------------------------------------------------
    | HEADER
    |--------------------------------------------------------------------------
    */

    .qr-page-header {
        margin-bottom: 1.5rem;
    }

    .qr-page-title {
        font-size: 24px;
        font-weight: 700;
        color: #566a7f;
        margin-bottom: 4px;
    }

    .qr-page-description {
        color: #8592a3;
        font-size: 13px;
        margin: 0;
    }

    /*
    |--------------------------------------------------------------------------
    | SETTINGS CARD
    |--------------------------------------------------------------------------
    */

    .qr-settings-card {
        border: 0;
        box-shadow: 0 4px 18px rgba(0,0,0,.05);
        border-radius: 12px;
        margin-bottom: 1.5rem;
    }

    .qr-settings-title {
        font-size: 15px;
        font-weight: 600;
        color: #566a7f;
    }

    /*
    |--------------------------------------------------------------------------
    | OPTION BUTTON
    |--------------------------------------------------------------------------
    */

    .qr-option {
        cursor: pointer;
    }

    .qr-option .btn {
        min-height: 65px;
        border-radius: 10px;
        font-size: 13px;
    }

    .qr-option small {
        font-size: 11px;
    }

    /*
    |--------------------------------------------------------------------------
    | PREVIEW CARD
    |--------------------------------------------------------------------------
    */

    .qr-preview-card {
        border: 0;
        border-radius: 12px;
        box-shadow: 0 4px 18px rgba(0,0,0,.05);
    }

    .qr-preview-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-bottom: 15px;
        border-bottom: 1px solid #e9ecef;
        margin-bottom: 20px;
    }

    .qr-preview-title {
        font-size: 15px;
        font-weight: 600;
        color: #566a7f;
        margin: 0;
    }

    .qr-count-badge {
        background: #f0f0ff;
        color: #696cff;
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }

    /*
    |--------------------------------------------------------------------------
    | QR GRID
    |--------------------------------------------------------------------------
    */

    #qrPreviewGrid {
        display: grid;
        gap: 20px;
        justify-content: center;
        align-items: start;
    }

    /*
    | Layout
    */

    #qrPreviewGrid.layout-1 {
        grid-template-columns: repeat(1, minmax(0, 1fr));
    }

    #qrPreviewGrid.layout-2 {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    #qrPreviewGrid.layout-4 {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    #qrPreviewGrid.layout-6 {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    #qrPreviewGrid.layout-8 {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    /*
    |--------------------------------------------------------------------------
    | QR LABEL
    |--------------------------------------------------------------------------
    */

    .qr-label {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;

        padding: 18px;

        text-align: center;

        box-shadow: 0 3px 12px rgba(0,0,0,.04);

        break-inside: avoid;
        page-break-inside: avoid;

        transition: .2s ease;
    }

    .qr-label:hover {
        box-shadow: 0 6px 20px rgba(0,0,0,.08);
    }

    /*
    |--------------------------------------------------------------------------
    | COMPANY
    |--------------------------------------------------------------------------
    */

    .qr-company {
        font-size: 12px;
        font-weight: 600;
        color: #566a7f;
        margin-bottom: 10px;
        text-transform: uppercase;
    }

    /*
    |--------------------------------------------------------------------------
    | QR CONTAINER
    |--------------------------------------------------------------------------
    */

    .qr-code-container {
        display: flex;
        justify-content: center;
        align-items: center;

        margin: 0 auto 12px;
    }

    /*
    |--------------------------------------------------------------------------
    | ASSET CODE
    |--------------------------------------------------------------------------
    */

    .qr-asset-code {
        font-size: 15px;
        font-weight: 700;
        color: #696cff;

        margin-top: 8px;
    }

    /*
    |--------------------------------------------------------------------------
    | ASSET NAME
    |--------------------------------------------------------------------------
    */

    .qr-asset-name {
        font-size: 12px;
        color: #566a7f;

        margin-top: 3px;

        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /*
    |--------------------------------------------------------------------------
    | EMPTY
    |--------------------------------------------------------------------------
    */

    .qr-empty {
        padding: 70px 20px;
        text-align: center;
        color: #8592a3;
    }

    .qr-empty i {
        font-size: 50px;
        margin-bottom: 15px;
        opacity: .5;
    }

    /*
    |--------------------------------------------------------------------------
    | ACTION
    |--------------------------------------------------------------------------
    */

    .qr-action-bar {
        position: sticky;
        bottom: 15px;

        z-index: 100;

        background: rgba(255,255,255,.95);

        backdrop-filter: blur(8px);

        border: 1px solid #e5e7eb;

        border-radius: 12px;

        padding: 12px 15px;

        box-shadow: 0 5px 25px rgba(0,0,0,.08);
    }


    /*
    |--------------------------------------------------------------------------
    | PRINT
    |--------------------------------------------------------------------------
    */

    @media print {

        body {
            background: #fff !important;
        }

        .content-wrapper {
            padding: 0 !important;
            margin: 0 !important;
        }

        .qr-page-header,
        .qr-settings-card,
        .qr-preview-header,
        .qr-action-bar {
            display: none !important;
        }

        .qr-preview-card {
            box-shadow: none !important;
            border: 0 !important;
        }

        .card-body {
            padding: 0 !important;
        }

        #qrPreviewGrid {
            display: grid !important;
            gap: 10mm !important;
        }

        .qr-label {
            box-shadow: none !important;
            border: 1px solid #ddd !important;
        }

        /*
        |----------------------------------------------------------------------
        | PRINT LAYOUT
        |----------------------------------------------------------------------
        */

        #qrPreviewGrid.layout-1 {
            grid-template-columns: repeat(1, 1fr);
        }

        #qrPreviewGrid.layout-2 {
            grid-template-columns: repeat(2, 1fr);
        }

        #qrPreviewGrid.layout-4 {
            grid-template-columns: repeat(2, 1fr);
        }

        #qrPreviewGrid.layout-6 {
            grid-template-columns: repeat(3, 1fr);
        }

        #qrPreviewGrid.layout-8 {
            grid-template-columns: repeat(4, 1fr);
        }

        .qr-label {
            break-inside: avoid;
            page-break-inside: avoid;
        }

    }

</style>


<div class="content-wrapper">

    <div class="container-xxl flex-grow-1 container-p-y qr-print-page">


        <!-- ========================================================= -->
        <!-- HEADER -->
        <!-- ========================================================= -->

        <div class="qr-page-header">

            <div class="d-flex justify-content-between align-items-center">

                <div>

                    <h4 class="qr-page-title">
                        <i class="fa-solid fa-qrcode text-primary me-2"></i>
                        Print QR Asset
                    </h4>

                    <p class="qr-page-description">
                        Configure QR label and print multiple assets at once.
                    </p>

                </div>


                <div>

                    <a
                        href="{{ route('assets.index') }}"
                        class="btn btn-label-secondary"
                    >

                        <i class="fa-solid fa-arrow-left me-1"></i>

                        Back to Assets

                    </a>

                </div>

            </div>

        </div>


        <!-- ========================================================= -->
        <!-- SETTINGS -->
        <!-- ========================================================= -->
        <div class="card qr-settings-card">
            <div class="card-body">
                <div class="row g-4">
                    <!-- ================================================= -->
                    <!-- QR SIZE -->
                    <!-- ================================================= -->
                    <div class="col-lg-6">
                        <div class="qr-settings-title mb-3">
                            <i class="fa-solid fa-expand me-2 text-primary"></i>
                            QR Size
                        </div>
                        <div class="row g-2">
                            <!-- SMALL -->
                            <div class="col-3 qr-option">
                                <input
                                    type="radio"
                                    class="btn-check"
                                    name="qrSize"
                                    id="qrSizeSmall"
                                    value="40"
                                >
                                <label
                                    class="btn btn-outline-primary w-100"
                                    for="qrSizeSmall"
                                >
                                    <strong>
                                        Small
                                    </strong>
                                    <small class="d-block">
                                        1 × 1 cm
                                    </small>
                                </label>
                            </div>
                            <!-- MEDIUM -->
                            <div class="col-3 qr-option">
                                <input
                                    type="radio"
                                    class="btn-check"
                                    name="qrSize"
                                    id="qrSizeMedium"
                                    value="50"
                                    checked
                                >
                                <label
                                    class="btn btn-outline-primary w-100"
                                    for="qrSizeMedium"
                                >
                                    <strong>
                                        Medium
                                    </strong>
                                    <small class="d-block">
                                        1.3 × 1.3 cm
                                    </small>
                                </label>
                            </div>
                            <!-- LARGE -->
                            <div class="col-3 qr-option">
                                <input
                                    type="radio"
                                    class="btn-check"
                                    name="qrSize"
                                    id="qrSizeLarge"
                                    value="60"
                                >
                                <label
                                    class="btn btn-outline-primary w-100"
                                    for="qrSizeLarge"
                                >
                                    <strong>
                                        Large
                                    </strong>
                                    <small class="d-block">
                                        1.6 × 1.6 cm
                                    </small>
                                </label>
                            </div>
                            <!-- XLARGE -->
                            <div class="col-3 qr-option">
                                <input
                                    type="radio"
                                    class="btn-check"
                                    name="qrSize"
                                    id="qrSizeXL"
                                    value="80"
                                >
                                <label
                                    class="btn btn-outline-primary w-100"
                                    for="qrSizeXL"
                                >
                                    <strong>
                                        XL
                                    </strong>
                                    <small class="d-block">
                                        2.1 × 2.1 cm
                                    </small>
                                </label>
                            </div>
                        </div>
                    </div>
                    <!-- ================================================= -->
                    <!-- LAYOUT -->
                    <!-- ================================================= -->
                    <div class="col-lg-6">
                        <div class="qr-settings-title mb-3">
                            <i class="fa-solid fa-table-cells me-2 text-primary"></i>
                            Print Layout
                        </div>
                        <div class="row g-2">
                            <!-- 1 -->
                            <div class="col">
                                <input
                                    type="radio"
                                    class="btn-check"
                                    name="qrLayout"
                                    id="layout1"
                                    value="1"
                                >
                                <label
                                    class="btn btn-outline-primary w-100"
                                    for="layout1"
                                >
                                    <strong>1</strong>
                                    <small class="d-block">
                                        / Page
                                    </small>
                                </label>
                            </div>
                            <!-- 2 -->
                            <div class="col">
                                <input
                                    type="radio"
                                    class="btn-check"
                                    name="qrLayout"
                                    id="layout2"
                                    value="2"
                                >
                                <label
                                    class="btn btn-outline-primary w-100"
                                    for="layout2"
                                >
                                    <strong>2</strong>
                                    <small class="d-block">
                                        / Page
                                    </small>
                                </label>
                            </div>
                            <!-- 4 -->
                            <div class="col">
                                <input
                                    type="radio"
                                    class="btn-check"
                                    name="qrLayout"
                                    id="layout4"
                                    value="4"
                                    checked
                                >
                                <label
                                    class="btn btn-outline-primary w-100"
                                    for="layout4"
                                >
                                    <strong>4</strong>
                                    <small class="d-block">
                                        / Page
                                    </small>
                                </label>
                            </div>
                            <!-- 6 -->
                            <div class="col">
                                <input
                                    type="radio"
                                    class="btn-check"
                                    name="qrLayout"
                                    id="layout6"
                                    value="6"
                                >
                                <label
                                    class="btn btn-outline-primary w-100"
                                    for="layout6"
                                >
                                    <strong>6</strong>
                                    <small class="d-block">
                                        / Page
                                    </small>
                                </label>
                            </div>
                            <!-- 8 -->
                            <div class="col">
                                <input
                                    type="radio"
                                    class="btn-check"
                                    name="qrLayout"
                                    id="layout8"
                                    value="8"
                                >
                                <label
                                    class="btn btn-outline-primary w-100"
                                    for="layout8"
                                >
                                    <strong>8</strong>
                                    <small class="d-block">
                                        / Page
                                    </small>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- ========================================================= -->
        <!-- PREVIEW -->
        <!-- ========================================================= -->

        <div class="card qr-preview-card">

            <div class="card-body">


                <!-- PREVIEW HEADER -->

                <div class="qr-preview-header">

                    <div>

                        <h6 class="qr-preview-title">

                            <i class="fa-solid fa-eye me-2 text-primary"></i>

                            QR Preview

                        </h6>

                    </div>


                    <div>

                        <span class="qr-count-badge">

                            <span id="qrAssetCount">
                                {{ $assets->count() }}
                            </span>

                            Asset

                        </span>

                    </div>

                </div>


                <!-- GRID -->

                <div
                    id="qrPreviewGrid"
                    class="layout-4"
                >

                    @forelse($assets as $asset)

                        <div
                            class="qr-label"
                            data-asset-id="{{ $asset->id }}"
                        >

                            <!-- COMPANY -->

                            <div class="qr-company">

                                {{ $asset->company->company_name ?? 'COMPANY' }}

                            </div>


                            <!-- QR -->

                            <div
                                class="qr-code-container"
                                id="qr-{{ $asset->id }}"
                            >

                                <div class="text-muted">

                                    <i class="fa-solid fa-qrcode fa-2x"></i>

                                </div>

                            </div>


                            <!-- ASSET CODE -->

                            <div class="qr-asset-code">

                                {{ $asset->asset_code }}

                            </div>


                            <!-- ASSET NAME -->

                            <div
                                class="qr-asset-name"
                                title="{{ $asset->asset_name }}"
                            >

                                {{ $asset->asset_name }}

                            </div>

                        </div>

                    @empty

                        <div class="qr-empty">

                            <i class="fa-solid fa-qrcode d-block"></i>

                            <strong>
                                No asset selected
                            </strong>

                            <div class="mt-1">
                                Please select asset from Asset List.
                            </div>

                        </div>

                    @endforelse

                </div>

            </div>

        </div>


        <!-- ========================================================= -->
        <!-- ACTION BAR -->
        <!-- ========================================================= -->

        <div class="qr-action-bar mt-3">

            <div class="d-flex justify-content-between align-items-center">


                <div class="text-muted small">

                    <i class="fa-solid fa-circle-info me-1"></i>

                    <span id="printInfo">
                        {{ $assets->count() }} asset
                        will be printed.
                    </span>

                </div>


                <div class="d-flex gap-2">


                    <button
                        type="button"
                        class="btn btn-label-secondary"
                        onclick="window.history.back()"
                    >

                        <i class="fa-solid fa-arrow-left me-1"></i>

                        Back

                    </button>


                    <button
                        type="button"
                        class="btn btn-primary"
                        onclick="printAllQr()"
                    >

                        <i class="fa-solid fa-print me-1"></i>

                        Print QR

                    </button>

                </div>

            </div>

        </div>


    </div>

</div>


<!-- =========================================================
     QR CODE LIBRARY
========================================================= -->

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>


<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        generateAllQr();

    }
);


/*
|--------------------------------------------------------------------------
| GENERATE ALL QR
|--------------------------------------------------------------------------
*/

function generateAllQr() {

    const size =
        parseInt(
            document.querySelector(
                'input[name="qrSize"]:checked'
            ).value
        );


    const containers =
        document.querySelectorAll(
            '.qr-code-container'
        );


    containers.forEach(
        function (container) {

            const assetId =
                container
                    .closest('.qr-label')
                    .dataset
                    .assetId;


            /*
            |--------------------------------------------------------------------------
            | CLEAR
            |--------------------------------------------------------------------------
            */

            container.innerHTML = '';


            /*
            |--------------------------------------------------------------------------
            | QR URL
            |--------------------------------------------------------------------------
            */

            const qrUrl =
                "{{ url('/assets/qr') }}/" +
                assetId;


            /*
            |--------------------------------------------------------------------------
            | GENERATE
            |--------------------------------------------------------------------------
            */

            new QRCode(
                container,
                {

                    text: qrUrl,

                    width: size,

                    height: size,

                    correctLevel:
                        QRCode.CorrectLevel.H

                }
            );

        }
    );

}


/*
|--------------------------------------------------------------------------
| QR SIZE CHANGE
|--------------------------------------------------------------------------
*/

document
    .querySelectorAll(
        'input[name="qrSize"]'
    )
    .forEach(
        function (radio) {

            radio.addEventListener(
                'change',
                function () {

                    generateAllQr();

                }
            );

        }
    );


/*
|--------------------------------------------------------------------------
| LAYOUT CHANGE
|--------------------------------------------------------------------------
*/

document
    .querySelectorAll(
        'input[name="qrLayout"]'
    )
    .forEach(
        function (radio) {

            radio.addEventListener(
                'change',
                function () {

                    const layout =
                        this.value;


                    const grid =
                        document.getElementById(
                            'qrPreviewGrid'
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | REMOVE OLD CLASS
                    |--------------------------------------------------------------------------
                    */

                    grid.classList.remove(
                        'layout-1',
                        'layout-2',
                        'layout-4',
                        'layout-6',
                        'layout-8'
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | ADD NEW CLASS
                    |--------------------------------------------------------------------------
                    */

                    grid.classList.add(
                        'layout-' + layout
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | INFO
                    |--------------------------------------------------------------------------
                    */

                    updatePrintInfo();

                }
            );

        }
    );


/*
|--------------------------------------------------------------------------
| UPDATE INFO
|--------------------------------------------------------------------------
*/

function updatePrintInfo() {

    const total =
        document.querySelectorAll(
            '.qr-label'
        ).length;


    const layout =
        parseInt(
            document.querySelector(
                'input[name="qrLayout"]:checked'
            ).value
        );


    const pages =
        total > 0
            ? Math.ceil(total / layout)
            : 0;


    document.getElementById(
        'qrAssetCount'
    ).innerText = total;


    document.getElementById(
        'printInfo'
    ).innerText =
        total +
        ' asset • approximately ' +
        pages +
        ' page' +
        (pages > 1 ? 's' : '') +
        ' with ' +
        layout +
        ' QR / page';

}


/*
|--------------------------------------------------------------------------
| PRINT
|--------------------------------------------------------------------------
*/

function printAllQr() {

    const labels = document.querySelectorAll('.qr-label');

    if (labels.length === 0) {

        Swal.fire({
            icon: 'warning',
            title: 'Tidak ada asset',
            text: 'Silakan pilih asset terlebih dahulu.'
        });

        return;
    }

    const layout = parseInt(
        document.querySelector(
            'input[name="qrLayout"]:checked'
        ).value
    );

    const size = parseInt(
        document.querySelector(
            'input[name="qrSize"]:checked'
        ).value
    );

    /*
    |--------------------------------------------------------------------------
    | COLS
    |--------------------------------------------------------------------------
    */

    let columns = 1;

    if (layout === 2) {
        columns = 2;
    }

    if (layout === 4) {
        columns = 2;
    }

    if (layout === 6) {
        columns = 3;
    }

    if (layout === 8) {
        columns = 4;
    }


    /*
    |--------------------------------------------------------------------------
    | CREATE PRINT WINDOW
    |--------------------------------------------------------------------------
    */

    const printWindow = window.open(
        '',
        '_blank',
        'width=1000,height=900'
    );


    /*
    |--------------------------------------------------------------------------
    | BUILD QR HTML
    |--------------------------------------------------------------------------
    */

    let qrHtml = '';

    labels.forEach(function (label) {

        qrHtml += `
            <div class="qr-label">
                ${label.innerHTML}
            </div>
        `;

    });


    /*
    |--------------------------------------------------------------------------
    | PRINT DOCUMENT
    |--------------------------------------------------------------------------
    */
    // Ukuran tulisan mengikuti ukuran QR
    let companyFontSize = 14;
    let assetCodeFontSize = 20;
    let assetNameFontSize = 14;

    if (qrSize = 40) {
        companyFontSize = 6;
        assetCodeFontSize = 8;
        assetNameFontSize = 6;
    } else if (qrSize = 50) {
        companyFontSize = 7;
        assetCodeFontSize = 10;
        assetNameFontSize = 7;
    } else if (qrSize = 60) {
        companyFontSize = 9;
        assetCodeFontSize = 12;
        assetNameFontSize = 8;
    } else {
        companyFontSize = 11;
        assetCodeFontSize = 15;
        assetNameFontSize = 10;
    }
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>
                Print Asset QR
            </title>
            <style>
                @page {
                    size: A4;
                    margin: 10mm;
                }
                * {
                    box-sizing: border-box;
                }
                html,
                body {
                    margin: 0;
                    padding: 0;
                    background: #fff;
                    font-family:
                        Arial,
                        Helvetica,
                        sans-serif;
                }
                .qr-grid {
                    display: grid;
                    grid-template-columns:
                        repeat(${columns}, 1fr);
                    gap: 8mm;
                    width: 100%;
                }
                .qr-label {
                    border: 1px solid #d9d9d9;
                    border-radius: 8px;
                    padding: 5mm;
                    text-align: center;
                    background: #fff;
                    break-inside: avoid;
                    page-break-inside: avoid;
                    min-height: 50mm;
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                }
                .qr-company {
                    font-size: ${companyFontSize}px;
                    font-weight: 600;
                    margin-bottom: 3mm;
                    text-transform: uppercase;
                }
                .qr-code-container {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin-bottom: 3mm;
                }
                .qr-code-container img,
                .qr-code-container canvas {
                    max-width: ${size}px;
                    max-height: ${size}px;
                }
                .qr-asset-code {
                    font-size: ${assetCodeFontSize}px;
                    font-weight: bold;
                    margin-top: 2mm;
                }
                .qr-asset-name {
                    font-size: ${assetNameFontSize}px;
                    margin-top: 1mm;
                    max-width: 100%;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    white-space: nowrap;
                }
                /*
                |--------------------------------------------------------------------------
                | PAGE BREAK
                |--------------------------------------------------------------------------
                */
                .qr-label:nth-child(${layout}n) {
                    page-break-after: always;
                }
                /*
                |--------------------------------------------------------------------------
                | SPECIAL CASE
                |--------------------------------------------------------------------------
                */
                .qr-grid {
                    page-break-inside: auto;
                }
            </style>
        </head>
        <body>
            <div class="qr-grid">
                ${qrHtml}
            </div>
            <script>
                window.onload = function () {
                    setTimeout(function () {
                        window.print();
                    }, 500);
                };
            <\/script>
        </body>
        </html>
    `);
    printWindow.document.close();

}


/*
|--------------------------------------------------------------------------
| INITIAL INFO
|--------------------------------------------------------------------------
*/

updatePrintInfo();

</script>

@endsection