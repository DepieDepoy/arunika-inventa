@extends('dashboard.layouts.wrapper')

@section('title', 'Asset Detail')

@section('content')

<style>

    .asset-page-header {
        margin-bottom: 1.5rem;
    }

    .asset-section {
        margin-bottom: 1.5rem;
    }

    .asset-section-header {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 1.25rem;
        padding-bottom: 10px;
        border-bottom: 1px solid #e9ecef;
    }

    .asset-section-number {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;

        background: #696cff;
        color: #fff;

        font-weight: 600;
        font-size: 14px;

        flex-shrink: 0;
    }

    .asset-section-title {
        margin: 0;
        font-size: 16px;
        font-weight: 600;
        color: #566a7f;
    }

    .asset-section-description {
        margin: 2px 0 0;
        font-size: 12px;
        color: #8592a3;
    }

    /*
    |--------------------------------------------------------------------------
    | DETAIL ITEM
    |--------------------------------------------------------------------------
    */

    .detail-item {
        margin-bottom: 1.25rem;
    }

    .detail-label {
        display: block;
        font-size: 12px;
        color: #8592a3;
        margin-bottom: 5px;
        font-weight: 500;
    }

    .detail-value {
        font-size: 14px;
        color: #495057;
        font-weight: 400;
        word-break: break-word;
    }

    .detail-value.empty {
        color: #adb5bd;
        font-style: italic;
    }

    /*
    |--------------------------------------------------------------------------
    | ASSET CODE
    |--------------------------------------------------------------------------
    */

    .asset-code-box {
        background: #f8f8ff;
        border: 1px solid #e7e7ff;
        border-radius: 8px;
        padding: 15px 18px;
    }

    .asset-code-label {
        font-size: 11px;
        color: #8592a3;
        margin-bottom: 3px;
    }

    .asset-code {
        font-size: 20px;
        font-weight: 600;
        color: #696cff;
        letter-spacing: .5px;
    }

    /*
    |--------------------------------------------------------------------------
    | STATUS
    |--------------------------------------------------------------------------
    */

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;

        padding: 5px 10px;

        border-radius: 6px;

        font-size: 12px;
        font-weight: 500;
    }

    .status-active {
        background: #e8fadf;
        color: #71dd37;
    }

    .status-inactive {
        background: #ffe8e6;
        color: #ff3e1d;
    }

    /*
    |--------------------------------------------------------------------------
    | PHOTO
    |--------------------------------------------------------------------------
    */

    .asset-photo-card {
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        overflow: hidden;
        background: #fff;
        height: 100%;
    }

    .asset-photo {
        width: 100%;
        height: 220px;
        object-fit: cover;
        display: block;
        background: #f8f9fa;
    }

    .asset-photo-footer {
        padding: 10px 12px;
    }

    .asset-photo-name {
        font-size: 12px;
        color: #566a7f;

        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /*
    |--------------------------------------------------------------------------
    | DOCUMENT
    |--------------------------------------------------------------------------
    */

    .document-item {
        display: flex;
        align-items: center;
        justify-content: space-between;

        gap: 15px;

        border: 1px solid #e5e7eb;
        border-radius: 8px;

        padding: 12px 15px;

        margin-bottom: 10px;

        background: #fff;
    }

    .document-left {
        display: flex;
        align-items: center;
        gap: 12px;

        min-width: 0;
    }

    .document-icon {
        font-size: 25px;
        color: #696cff;
        flex-shrink: 0;
    }

    .document-name {
        font-size: 13px;
        color: #495057;

        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /*
    |--------------------------------------------------------------------------
    | EMPTY
    |--------------------------------------------------------------------------
    */

    .empty-data {
        padding: 25px;
        text-align: center;

        border: 1px dashed #d9dee3;
        border-radius: 8px;

        color: #8592a3;
        font-size: 13px;
    }

    /*
    |--------------------------------------------------------------------------
    | DESCRIPTION
    |--------------------------------------------------------------------------
    */

    .description-box {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 15px;

        color: #495057;
        font-size: 14px;

        line-height: 1.7;

        white-space: pre-line;
    }

</style>


<div class="content-wrapper">

    <div class="container-xxl flex-grow-1 container-p-y">

        <!-- ========================================================= -->
        <!-- HEADER -->
        <!-- ========================================================= -->

        <div class="asset-page-header">

            <div class="d-flex justify-content-between align-items-center">

                <div>

                    <h4 class="fw-bold mb-1">
                        Asset Detail
                    </h4>

                    <p class="text-muted mb-0">
                        View asset information
                    </p>

                </div>


                <div class="d-flex gap-2">

                    <a
                        href="{{ route('assets.index') }}"
                        class="btn btn-label-secondary"
                    >

                        <i class="fa-solid fa-arrow-left me-1"></i>

                        Back to Assets

                    </a>


                    <a
                        href="{{ route('assets.edit', $asset->id) }}"
                        class="btn btn-warning"
                    >

                        <i class="fa-solid fa-edit me-1"></i>

                        Edit

                    </a>


                    <button
                        type="button"
                        class="btn btn-primary"
                        data-bs-toggle="modal"
                        data-bs-target="#printQrModal"
                        onclick="generateAssetQr()"
                    >
                        <i class="fa-solid fa-qrcode me-1"></i>
                        Print QR
                    </button>

                </div>

            </div>

        </div>


        <!-- ========================================================= -->
        <!-- 1. ASSET INFORMATION -->
        <!-- ========================================================= -->

        <div class="card asset-section">

            <div class="card-body">

                <div class="asset-section-header">

                    <div class="asset-section-number">
                        1
                    </div>

                    <div>

                        <h6 class="asset-section-title">
                            Asset Information
                        </h6>

                        <p class="asset-section-description">
                            Basic information about this asset
                        </p>

                    </div>

                </div>


                <div class="row">

                    <!-- CODE -->

                    <div class="col-md-6 detail-item">

                        <div class="asset-code-box">

                            <div class="asset-code-label">
                                ASSET CODE
                            </div>

                            <div class="asset-code">
                                {{ $asset->asset_code }}
                            </div>

                        </div>

                    </div>


                    <!-- STATUS -->

                    <div class="col-md-6 detail-item">

                        <span class="detail-label">
                            Status
                        </span>

                        @if($asset->status == 1)

                            <span class="status-badge status-active">

                                <i class="fa-solid fa-circle"></i>

                                Active

                            </span>

                        @else

                            <span class="status-badge status-inactive">

                                <i class="fa-solid fa-circle"></i>

                                Inactive

                            </span>

                        @endif

                    </div>


                    <!-- NAME -->

                    <div class="col-md-6 detail-item">

                        <span class="detail-label">
                            Asset Name
                        </span>

                        <div class="detail-value">
                            {{ $asset->asset_name }}
                        </div>

                    </div>


                    <!-- CATEGORY -->

                    <div class="col-md-6 detail-item">

                        <span class="detail-label">
                            Category
                        </span>

                        <div class="detail-value">

                            {{ $asset->category->category_name ?? '-' }}

                            @if($asset->category && $asset->category->category_code)

                                <small class="text-muted">
                                    ({{ $asset->category->category_code }})
                                </small>

                            @endif

                        </div>

                    </div>


                    <!-- SUB CATEGORY -->

                    <div class="col-md-6 detail-item">

                        <span class="detail-label">
                            Sub Category
                        </span>

                        <div class="detail-value">

                            {{ $asset->subCategory->sub_category_name ?? '-' }}

                            @if($asset->subCategory && $asset->subCategory->sub_category_code)

                                <small class="text-muted">
                                    ({{ $asset->subCategory->sub_category_code }})
                                </small>

                            @endif

                        </div>

                    </div>


                    <!-- VENDOR -->

                    <div class="col-md-6 detail-item">

                        <span class="detail-label">
                            Vendor
                        </span>

                        <div class="detail-value">
                            {{ $asset->vendor->vendor_name ?? '-' }}
                        </div>

                    </div>


                    <!-- BRAND -->

                    <div class="col-md-6 detail-item">

                        <span class="detail-label">
                            Brand
                        </span>

                        <div class="detail-value">

                            @if($asset->brand)

                                {{ $asset->brand }}

                            @else

                                <span class="empty">
                                    Not specified
                                </span>

                            @endif

                        </div>

                    </div>


                    <!-- MODEL -->

                    <div class="col-md-6 detail-item">

                        <span class="detail-label">
                            Model
                        </span>

                        <div class="detail-value">

                            {{ $asset->model ?: 'Not specified' }}

                        </div>

                    </div>


                    <!-- SERIAL -->

                    <div class="col-md-6 detail-item">

                        <span class="detail-label">
                            Serial Number
                        </span>

                        <div class="detail-value">

                            {{ $asset->serial_number ?: 'Not specified' }}

                        </div>

                    </div>


                    <!-- DESCRIPTION -->

                    <div class="col-md-12 detail-item">

                        <span class="detail-label">
                            Description
                        </span>

                        @if($asset->description)

                            <div class="description-box">

                                {{ $asset->description }}

                            </div>

                        @else

                            <div class="detail-value empty">
                                No description available.
                            </div>

                        @endif

                    </div>

                </div>

            </div>

        </div>


        <!-- ========================================================= -->
        <!-- 2. PURCHASE -->
        <!-- ========================================================= -->

        <div class="card asset-section">

            <div class="card-body">

                <div class="asset-section-header">

                    <div class="asset-section-number">
                        2
                    </div>

                    <div>

                        <h6 class="asset-section-title">
                            Purchase Information
                        </h6>

                        <p class="asset-section-description">
                            Purchase and invoice information
                        </p>

                    </div>

                </div>


                <div class="row">

                    <!-- PURCHASE DATE -->

                    <div class="col-md-4 detail-item">

                        <span class="detail-label">
                            Purchase Date
                        </span>

                        <div class="detail-value">

                            @if($asset->purchase_date)

                                {{ \Carbon\Carbon::parse($asset->purchase_date)->format('d M Y') }}

                            @else

                                Not specified

                            @endif

                        </div>

                    </div>


                    <!-- PURCHASE PRICE -->

                    <div class="col-md-4 detail-item">

                        <span class="detail-label">
                            Purchase Price
                        </span>

                        <div class="detail-value">

                            Rp
                            {{ number_format($asset->purchase_price ?? 0, 0, ',', '.') }}

                        </div>

                    </div>


                    <!-- PURCHASE INVOICE -->

                    <div class="col-md-4 detail-item">

                        <span class="detail-label">
                            Purchase Number
                        </span>

                        <div class="detail-value">

                            {{ $asset->purchase_invoice ?: 'Not specified' }}

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- ========================================================= -->
        <!-- 3. DEPRECIATION -->
        <!-- ========================================================= -->

        <div class="card asset-section">

            <div class="card-body">

                <div class="asset-section-header">

                    <div class="asset-section-number">
                        3
                    </div>

                    <div>

                        <h6 class="asset-section-title">
                            Depreciation
                        </h6>

                        <p class="asset-section-description">
                            Asset depreciation configuration
                        </p>

                    </div>

                </div>


                <div class="row">

                    <!-- METHOD -->

                    <div class="col-md-4 detail-item">

                        <span class="detail-label">
                            Depreciation Method
                        </span>

                        <div class="detail-value">

                            @if($asset->depreciation_method === 'straight_line')

                                Straight Line

                            @else

                                Not specified

                            @endif

                        </div>

                    </div>


                    <!-- USEFUL LIFE -->

                    <div class="col-md-4 detail-item">

                        <span class="detail-label">
                            Useful Life
                        </span>

                        <div class="detail-value">

                            @if($asset->useful_life)

                                {{ $asset->useful_life }} Years

                            @else

                                Not specified

                            @endif

                        </div>

                    </div>


                    <!-- RESIDUAL -->

                    <div class="col-md-4 detail-item">

                        <span class="detail-label">
                            Residual Value
                        </span>

                        <div class="detail-value">

                            Rp
                            {{ number_format($asset->residual_value ?? 0, 0, ',', '.') }}

                        </div>

                    </div>


                    <!-- START DATE -->

                    <div class="col-md-4 detail-item">

                        <span class="detail-label">
                            Depreciation Start Date
                        </span>

                        <div class="detail-value">

                            @if($asset->depreciation_start_date)

                                {{ \Carbon\Carbon::parse($asset->depreciation_start_date)->format('d M Y') }}

                            @else

                                Not specified

                            @endif

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- ========================================================= -->
        <!-- 4. ASSIGNMENT -->
        <!-- ========================================================= -->

        <div class="card asset-section">

            <div class="card-body">

                <div class="asset-section-header">

                    <div class="asset-section-number">
                        4
                    </div>

                    <div>

                        <h6 class="asset-section-title">
                            Asset Assignment
                        </h6>

                        <p class="asset-section-description">
                            Person responsible for this asset
                        </p>

                    </div>

                </div>
                <div class="row">
                    <!-- RESPONSIBLE -->
                    <div class="col-md-6 detail-item">
                        <span class="detail-label">
                            Responsible
                        </span>
                        <div class="detail-value">
                            {{ $asset->responsibleUser->name ?? 'Not assigned' }}
                        </div>
                    </div>
                    <!-- LOCATION -->
                    <div class="col-md-6 detail-item">
                        <span class="detail-label">
                            Location
                        </span>
                        <div class="detail-value">
                            {{ $asset->location ?: 'Not specified' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- ========================================================= -->
        <!-- 5. PHOTOS -->
        <!-- ========================================================= -->
        <div class="card asset-section">
            <div class="card-body">
                <div class="asset-section-header">
                    <div class="asset-section-number">
                        5
                    </div>
                    <div>
                        <h6 class="asset-section-title">
                            Asset Photos
                        </h6>
                        <p class="asset-section-description">
                            Photos attached to this asset
                        </p>
                    </div>
                </div>
                @if($asset->photos->count())
                    <div class="row g-3">
                        @foreach($asset->photos as $photo)
                            @php
                                $photoUrl = asset('storage/' .$photo->file_path);
                            @endphp
                            <div class="col-md-4">
                                <div class="asset-photo-card">
                                    <a
                                        href="{{ $photoUrl }}"
                                        target="_blank"
                                    >
                                        <img
                                            src="{{ $photoUrl }}"
                                            class="asset-photo"
                                            alt="{{ $photo->original_name ?? 'Asset Photo' }}"
                                            onerror="this.style.opacity='0.4';"
                                        >
                                    </a>
                                    <div class="asset-photo-footer">
                                        <div
                                            class="asset-photo-name"
                                            title="{{ $photo->original_name ?? basename($photo->file_path) }}"
                                        >
                                            {{ $photo->original_name ?? basename($photo->file_path) }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-data">
                        <i class="fa-solid fa-image mb-2"></i>
                        <div>
                            No photos available.
                        </div>
                    </div>
                @endif
            </div>
        </div>
        <!-- ========================================================= -->
        <!-- 6. DOCUMENTS -->
        <!-- ========================================================= -->
        <div class="card asset-section">
            <div class="card-body">
                <div class="asset-section-header">
                    <div class="asset-section-number">
                        6
                    </div>
                    <div>
                        <h6 class="asset-section-title">
                            Invoice / Documents
                        </h6>
                        <p class="asset-section-description">
                            Invoice and supporting documents
                        </p>
                    </div>
                </div>
                @if($asset->documents->count())
                    @foreach($asset->documents as $document)
                        @php
                            $extension = strtolower(
                                pathinfo(
                                    $document->file_path,
                                    PATHINFO_EXTENSION
                                )
                            );
                            $icon = $extension === 'pdf'
                                ? 'fa-file-pdf'
                                : 'fa-file-image';
                            $documentUrl =
                                asset('storage/' .$document->file_path);
                        @endphp
                        <div class="document-item">
                            <div class="document-left">
                                <i
                                    class="fa-solid {{ $icon }} document-icon"
                                ></i>
                                <div style="min-width:0;">
                                    <div
                                        class="document-name"
                                        title="{{ $document->original_name ?? basename($document->file_path) }}"
                                    >
                                        {{ $document->original_name ?? basename($document->file_path) }}
                                    </div>
                                    <small class="text-muted">
                                        {{ strtoupper($extension) }}
                                    </small>
                                </div>
                            </div>
                            <a
                                href="{{ $documentUrl }}"
                                target="_blank"
                                class="btn btn-sm btn-outline-primary"
                            >
                                <i class="fa-solid fa-eye me-1"></i>
                                View
                            </a>
                        </div>
                    @endforeach
                @else
                    <div class="empty-data">
                        <i class="fa-solid fa-file mb-2"></i>
                        <div>
                            No documents available.
                        </div>
                    </div>
                @endif
            </div>
        </div>
        <!-- ========================================================= -->
        <!-- BOTTOM ACTION -->
        <!-- ========================================================= -->
        <div class="d-flex justify-content-end gap-2 mb-4">
            <a
                href="{{ route('assets.index') }}"
                class="btn btn-label-secondary"
            >
                <i class="fa-solid fa-arrow-left me-1"></i>
                Back
            </a>
            <a
                href="{{ route('assets.edit', $asset->id) }}"
                class="btn btn-warning"
            >
                <i class="fa-solid fa-edit me-1"></i>
                Edit Asset
            </a>

            <button
                type="button"
                class="btn btn-primary"
                data-bs-toggle="modal"
                data-bs-target="#printQrModal"
                onclick="generateAssetQr()"
            >
                <i class="fa-solid fa-qrcode me-1"></i>
                Print QR
            </button>
        </div>
    </div>
</div>

<!-- =========================================================
     PRINT QR MODAL
========================================================= -->
<div
    class="modal fade"
    id="printQrModal"
    tabindex="-1"
    aria-labelledby="printQrModalLabel"
    aria-hidden="true"
>
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <!-- HEADER -->
            <div class="modal-header">
                <div>
                    <h5 class="modal-title fw-bold" id="printQrModalLabel">
                        <i class="fa-solid fa-qrcode text-primary me-2"></i>
                        Print QR Asset
                    </h5>
                    <small class="text-muted">
                        Generate dan cetak QR Code asset
                    </small>
                </div>
                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Close"
                ></button>
            </div>
            <!-- BODY -->
            <div class="modal-body">
                <div class="row g-4">
                    <!-- ==========================================
                         QR PREVIEW
                    =========================================== -->
                    <div class="col-md-5">
                        <div
                            id="qrPrintArea"
                            class="border rounded-4 bg-white p-4 text-center"
                        >
                            <div class="mb-3">
                                <small class="text-muted">
                                    {{ $asset->company->company_name ?? 'COMPANY' }}
                                </small>
                            </div>
                            <!-- QR -->
                            <div
                                id="assetQrCode"
                                class="d-flex justify-content-center align-items-center"
                                style="min-height: 220px;"
                            >
                                <div class="text-muted">
                                    <i class="fa-solid fa-qrcode fa-2x mb-2"></i>
                                    <br>
                                    Generating QR...
                                </div>
                            </div>
                            <!-- ASSET INFO -->
                            <div class="mt-3">
                                <div
                                    class="fw-bold fs-5"
                                    id="qrAssetCode"
                                >
                                    {{ $asset->asset_code }}
                                </div>
                                <div
                                    class="text-muted"
                                    id="qrAssetName"
                                >
                                    {{ $asset->asset_name }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- ==========================================
                         SETTINGS
                    =========================================== -->
                    <div class="col-md-7">
                        <!-- SIZE -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold">
                                QR Size
                            </label>
                            <div class="row g-2">
                                <div class="col-3">
                                    <input
                                        type="radio"
                                        class="btn-check"
                                        name="qrSize"
                                        id="qrSizeSmall"
                                        value="40"
                                        checked
                                    >
                                    <label
                                        class="btn btn-outline-primary w-100"
                                        for="qrSizeSmall"
                                    >
                                        Small
                                        <small class="d-block">
                                            1 × 1 cm<br>
                                        </small>
                                    </label>
                                </div>
                                <div class="col-3">
                                    <input
                                        type="radio"
                                        class="btn-check"
                                        name="qrSize"
                                        id="qrSizeMedium"
                                        value="50"
                                    >
                                    <label
                                        class="btn btn-outline-primary w-100"
                                        for="qrSizeMedium"
                                    >
                                        Medium
                                        <small class="d-block">
                                            1.3 × 1.3 cm
                                        </small>
                                    </label>
                                </div>
                                <div class="col-3">
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
                                        Large
                                        <small class="d-block">
                                            1.6 × 1.6 cm
                                        </small>
                                    </label>
                                </div>
                                <div class="col-3">
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
                                        XL
                                        <small class="d-block">
                                            2.1 × 2.1 cm
                                        </small>
                                    </label>
                                </div>

                            </div>

                        </div>


                        <!-- LAYOUT -->
                        <!--<div class="mb-4">
                            <label class="form-label fw-semibold">
                                Print Layout
                            </label>

                            <select
                                class="form-select"
                                id="qrLayout"
                            >
                                <option value="1">
                                    1 QR / Page
                                </option>

                                <option value="2">
                                    2 QR / Page
                                </option>

                                <option value="4" selected>
                                    4 QR / Page
                                </option>

                                <option value="6">
                                    6 QR / Page
                                </option>

                                <option value="8">
                                    8 QR / Page
                                </option>
                            </select>
                        </div>-->
                        <!-- INFO -->
                        <div class="alert alert-light border mb-4">
                            <div class="d-flex">
                                <div class="me-3">
                                    <i class="fa-solid fa-circle-info text-primary"></i>
                                </div>
                                <div>
                                    <small>
                                        QR Code akan berisi informasi asset
                                        dan dapat digunakan untuk proses
                                        scanning inventory.
                                    </small>
                                </div>
                            </div>
                        </div>
                        <!-- ACTION -->
                        <div class="d-grid gap-2">
                            <button
                                type="button"
                                class="btn btn-primary"
                                onclick="printAssetQr()"
                            >
                                <i class="fa-solid fa-print me-2"></i>
                                Print QR
                            </button>
                            <button
                                type="button"
                                class="btn btn-outline-secondary"
                                onclick="downloadAssetQr()"
                            >
                                <i class="fa-solid fa-download me-2"></i>
                                Download QR
                            </button>
                        </div>
                        <!-- SHARE -->
                        <div class="mt-4">
                            <label class="form-label fw-semibold">
                                Share
                            </label>
                            <div class="d-flex gap-2">
                                <button
                                    type="button"
                                    class="btn btn-outline-success flex-fill"
                                    onclick="shareAssetWhatsApp()"
                                >
                                    <i class="fa-brands fa-whatsapp me-1"></i>
                                    WhatsApp
                                </button>
                                <button
                                    type="button"
                                    class="btn btn-outline-primary flex-fill"
                                    onclick="shareAssetEmail()"
                                >
                                    <i class="fa-solid fa-envelope me-1"></i>
                                    Email
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FOOTER -->
            <div class="modal-footer bg-light">

                <small class="text-muted me-auto">
                    Asset:
                    <strong>{{ $asset->asset_code }}</strong>
                </small>

                <button
                    type="button"
                    class="btn btn-secondary"
                    data-bs-dismiss="modal"
                >
                    Close
                </button>

            </div>

        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    /*
    |--------------------------------------------------------------------------
    | ASSET DATA
    |--------------------------------------------------------------------------
    */

    const assetId = @json($asset->id);

    const assetCode = @json($asset->asset_code);

    const assetName = @json($asset->asset_name);

    /*
    |--------------------------------------------------------------------------
    | QR URL
    |--------------------------------------------------------------------------
    |
    | Untuk sekarang masih menggunakan ID biasa.
    | Nanti ketika encryption sudah diterapkan,
    | bagian ini tinggal kita ubah.
    |
    */

    const assetQrUrl =
        "{{ url('/assets/qr') }}/" + assetId;


    /*
    |--------------------------------------------------------------------------
    | GENERATE QR
    |--------------------------------------------------------------------------
    */

    function generateAssetQr() {

        const qrContainer =
            document.getElementById('assetQrCode');

        const size =
            parseInt(
                document.querySelector(
                    'input[name="qrSize"]:checked'
                ).value
            );
        /*
        |--------------------------------------------------------------------------
        | CLEAR PREVIOUS QR
        |--------------------------------------------------------------------------
        */
        qrContainer.innerHTML = '';
        /*
        |--------------------------------------------------------------------------
        | GENERATE
        |--------------------------------------------------------------------------
        */
        new QRCode(qrContainer, {
            text: assetQrUrl,
            width: size,
            height: size,
            correctLevel: QRCode.CorrectLevel.H
        });
    }

    /*
    |--------------------------------------------------------------------------
    | CHANGE QR SIZE
    |--------------------------------------------------------------------------
    */
    document
        .querySelectorAll('input[name="qrSize"]')
        .forEach(function (radio) {
            radio.addEventListener('change', function () {
                generateAssetQr();
            });
        });
    /*
    |--------------------------------------------------------------------------
    | PRINT
    |--------------------------------------------------------------------------
    */
    function printAssetQr() {
        const printArea =
            document.getElementById('qrPrintArea');
        const printWindow =
            window.open('', '_blank', 'width=900,height=900');
        
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
                    QR Asset - ${assetCode}
                </title>
                <style>
                    @page {
                        size: A4;
                        margin: 15mm;
                    }
                    body {
                        font-family:
                            Arial,
                            Helvetica,
                            sans-serif;
                        margin: 0;
                        display: flex;
                        justify-content: center;
                        align-items: flex-start;
                        padding-top: 20mm;
                    }
                    .qr-print {
                        text-align: center;
                    }
                    .company {
                        font-size: ${companyFontSize}px;
                        margin-bottom: 8px;
                    }

                    .asset-code {
                        font-size: ${assetCodeFontSize}px;
                        font-weight: bold;
                        margin-top: 8px;
                    }

                    .asset-name {
                        font-size: ${assetNameFontSize}px;
                        margin-top: 4px;
                    }
                    img {
                        display: block;
                        margin: auto;
                    }
                </style>
            </head>
            <body>
                <div class="qr-print">
                    <div class="company">
                        {{ $asset->company->company_name ?? 'COMPANY' }}
                    </div>
                    ${printArea
                        .querySelector('#assetQrCode')
                        .innerHTML}
                    <div class="asset-code">
                        ${assetCode}
                    </div>
                    <div class="asset-name">
                        ${assetName}
                    </div>
                </div>
            </body>
            </html>
        `);
        printWindow.document.close();
        printWindow.focus();
        setTimeout(function () {
            printWindow.print();
        }, 500);
    }


    /*
    |--------------------------------------------------------------------------
    | DOWNLOAD QR
    |--------------------------------------------------------------------------
    */

    function downloadAssetQr() {

        const qrContainer =
            document.getElementById('assetQrCode');

        const canvas =
            qrContainer.querySelector('canvas');

        if (!canvas) {

            alert('QR Code belum selesai dibuat.');

            return;

        }

        const link =
            document.createElement('a');

        link.download =
            'QR-' + assetCode + '.png';

        link.href =
            canvas.toDataURL('image/png');

        link.click();

    }


    /*
    |--------------------------------------------------------------------------
    | WHATSAPP
    |--------------------------------------------------------------------------
    */

    function shareAssetWhatsApp() {

        const message =
            `Asset Information\n\n` +
            `Asset Code: ${assetCode}\n` +
            `Asset Name: ${assetName}\n\n` +
            `QR / Asset Link:\n${assetQrUrl}`;

        const whatsappUrl =
            'https://wa.me/?text=' +
            encodeURIComponent(message);

        window.open(
            whatsappUrl,
            '_blank'
        );

    }


    /*
    |--------------------------------------------------------------------------
    | EMAIL
    |--------------------------------------------------------------------------
    */

    function shareAssetEmail() {

        const subject =
            `Asset QR - ${assetCode}`;

        const body =
            `Asset Information\n\n` +
            `Asset Code: ${assetCode}\n` +
            `Asset Name: ${assetName}\n\n` +
            `Asset Link:\n${assetQrUrl}`;

        window.location.href =
            'mailto:?subject=' +
            encodeURIComponent(subject) +
            '&body=' +
            encodeURIComponent(body);

    }

</script>
@endsection