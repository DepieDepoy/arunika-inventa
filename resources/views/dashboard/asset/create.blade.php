@extends('dashboard.layouts.wrapper')
@section('title', 'Add Asset')
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
        color: white;
        font-weight: 600;
        font-size: 14px;
    }

    .asset-section-title {
        margin: 0;
        font-size: 16px;
        font-weight: 600;
    }

    .asset-section-description {
        margin: 2px 0 0;
        font-size: 12px;
        color: #8592a3;
    }

    .required {
        color: #ff3e1d;
    }

    .upload-box {
        border: 2px dashed #d9dee3;
        border-radius: 10px;
        padding: 25px;
        text-align: center;
        cursor: pointer;
        transition: all .2s ease;
    }

    .upload-box:hover {
        border-color: #696cff;
        background: #f8f8ff;
    }

    .upload-box i {
        font-size: 35px;
        color: #696cff;
        margin-bottom: 10px;
    }

    .upload-preview {
        margin-top: 15px;
    }

    .preview-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 10px 12px;
        margin-bottom: 8px;
        background: #fff;
    }

    .preview-item-left {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 0;
    }

    .preview-item-left i {
        font-size: 20px;
        color: #696cff;
    }
    .preview-remove {
        border: none;
        background: transparent;
        color: #ff3e1d;
        font-size: 16px;
        cursor: pointer;
        padding: 5px 8px;
        border-radius: 5px;
    }

    .preview-remove:hover {
        background: #fff0ed;
    }

    .preview-item-left {
        flex: 1;
        min-width: 0;
    }

    .preview-file-name {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 400px;
    }

    .asset-photo-preview {
        width: 80px;
        height: 80px;
        object-fit: cover;
        border-radius: 8px;
        border: 1px solid #ddd;
    }

    .form-control,
    .form-select {
        min-height: 42px;
        border: 1px solid #d9dee3 !important;
        border-radius: 6px;
        background-color: #fff;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #696cff !important;
        box-shadow: 0 0 0 0.15rem rgba(105, 108, 255, 0.15);
    }

    .sticky-footer {
        position: sticky;
        bottom: 0;
        z-index: 10;
        background: rgba(255,255,255,.95);
        border-top: 1px solid #e5e7eb;
        padding: 15px 0;
        margin-top: 10px;
    }

    /*
    |--------------------------------------------------------------------------
    | SELECT2
    |--------------------------------------------------------------------------
    */

    .select2-container {
        width: 100% !important;
    }

    .select2-container .select2-selection--single {
        height: 42px !important;
        border: 1px solid #d9dee3 !important;
        border-radius: 6px !important;
    }

    .select2-container .select2-selection--single
    .select2-selection__rendered {
        line-height: 40px !important;
        padding-left: 12px !important;
    }

    .select2-container .select2-selection--single
    .select2-selection__arrow {
        height: 40px !important;
    }

    .select2-container--default
    .select2-selection--single:focus {
        border-color: #696cff !important;
    }

    .select2-dropdown {
        border-color: #d9dee3 !important;
    }

    .select2-results__option {
        padding: 8px 12px;
    }
</style>

<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">
        <!-- ===================================================== -->
        <!-- PAGE HEADER -->
        <!-- ===================================================== -->
        <div class="asset-page-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="fw-bold mb-1">
                        Add Asset
                    </h4>
                    <p class="text-muted mb-0">
                        Add new company asset information
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
        <!-- ===================================================== -->
        <!-- FORM -->
        <!-- ===================================================== -->
        <form id="formAddAsset" enctype="multipart/form-data">
            @csrf
            <!-- ================================================= -->
            <!-- 1. ASSET INFORMATION -->
            <!-- ================================================= -->
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
                                Basic information about the asset
                            </p>
                        </div>
                    </div>
                    <div class="row">
                        <!-- Asset Name -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Asset Name
                                <span class="required">*</span>
                            </label>
                            <input
                                type="text"
                                name="asset_name"
                                id="asset_name"
                                class="form-control"
                                placeholder="Enter asset name"
                                required
                            >
                        </div>
                        <!-- Category -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Category
                                <span class="required">*</span>
                            </label>
                            <select
                                name="category_id"
                                id="category_id"
                                class="form-select"
                                required
                            >
                                <option value="">
                                    Select or type category
                                </option>
                                @foreach($categories as $category)
                                    <option
                                        value="{{ $category->id }}"
                                        data-name="{{ $category->category_name }}"
                                    >
                                        {{ $category->category_name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">
                                Type a new category if it is not registered yet.
                            </small>
                        </div>
                        <!-- Sub Category -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Sub Category
                            </label>
                            <select
                                name="sub_category_id"
                                id="sub_category_id"
                                class="form-select"
                            >
                                <option value="">
                                    Select or type sub category
                                </option>
                            </select>
                            <small class="text-muted">
                                Sub category will follow the selected category.
                            </small>
                        </div>
                        <!-- Vendor -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Vendor
                                <span class="required">*</span>
                            </label>
                            <select
                                name="vendor_id"
                                id="vendor_id"
                                class="form-select"
                                required
                            >
                                <option value="">
                                    Select vendor
                                </option>
                                @foreach($vendors as $vendor)
                                    <option value="{{ $vendor->id }}">
                                        {{ $vendor->vendor_name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">
                                Vendor must be registered first.
                            </small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="asset_condition" class="form-label">
                                Asset Condition <span class="required">*</span>
                            </label>

                            <select
                                name="asset_condition"
                                id="asset_condition"
                                class="form-select"
                                required
                            >
                                <option value="">Select Condition</option>
                                <option value="new" selected>New</option>
                                <option value="used">Used</option>
                            </select>

                            <small class="text-muted">
                                Select whether the asset is new or previously used.
                            </small>
                        </div>
                        <!-- Brand -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Brand
                            </label>
                            <input
                                type="text"
                                name="brand"
                                class="form-control"
                                placeholder="Example: Dell, HP, Lenovo"
                            >
                        </div>
                        <!-- Model -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Model
                            </label>
                            <input
                                type="text"
                                name="model"
                                class="form-control"
                                placeholder="Enter asset model"
                            >
                        </div>
                        <!-- Serial Number -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Serial Number
                            </label>
                            <input
                                type="text"
                                name="serial_number"
                                class="form-control"
                                placeholder="Enter serial number"
                            >
                        </div>
                        <!-- Status -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Status
                                <span class="required">*</span>
                            </label>
                            <select
                                name="status"
                                class="form-select"
                                required
                            >
                                <option value="1" selected>
                                    Active
                                </option>
                                <option value="0">
                                    Inactive
                                </option>
                            </select>
                        </div>
                        <!-- Description -->
                        <div class="col-md-12 mb-3">
                            <label class="form-label">
                                Description
                            </label>
                            <textarea
                                name="description"
                                class="form-control"
                                rows="4"
                                placeholder="Enter asset description"
                            ></textarea>
                        </div>
                    </div>
                </div>
            </div>
            <!-- ================================================= -->
            <!-- 2. PURCHASE INFORMATION -->
            <!-- ================================================= -->
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
                        <!-- Purchase Date -->
                        <div class="col-md-4 mb-3">
                            <label class="form-label">
                                Purchase Date
                                <span class="required">*</span>
                            </label>
                            <input
                                type="date"
                                name="purchase_date"
                                class="form-control"required
                            >
                        </div>
                        <!-- Purchase Price -->
                        <div class="col-md-4 mb-3">
                            <label class="form-label">
                                Purchase Price
                                <span class="required">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    Rp
                                </span>
                                <input
                                    type="number"
                                    name="purchase_price"
                                    class="form-control"
                                    min="0"
                                    placeholder="0" required
                                >
                            </div>
                        </div>
                        <!-- Invoice Number -->
                        <div class="col-md-4 mb-3">
                            <label class="form-label">
                                Purchase Number
                                <span class="required">*</span>
                            </label>
                            <input
                                type="text"
                                name="purchase_invoice"
                                class="form-control"
                                placeholder="Enter invoice number" required
                            >
                        </div>
                    </div>
                </div>
            </div>
            <!-- ================================================= -->
            <!-- 3. DEPRECIATION -->
            <!-- ================================================= -->
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
                    <div class="alert alert-info">
                        <div class="d-flex">
                            <i class="fa-solid fa-circle-info me-2 mt-1"></i>
                            <div>
                                Depreciation will be calculated based on
                                purchase price, useful life and depreciation
                                method.
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <!-- Depreciation Method -->
                        <div class="col-md-4 mb-3">
                            <label class="form-label">
                                Depreciation Method
                            </label>
                            <select
                                name="depreciation_method"
                                id="depreciation_method"
                                class="form-select"
                            >
                                <option value="">
                                    Select Method
                                </option>
                                <option value="straight_line">
                                    Straight Line
                                </option>
                            </select>
                        </div>
                        <!-- Useful Life -->
                        <div class="col-md-4 mb-3">
                            <label class="form-label">
                                Useful Life
                            </label>
                            <div class="input-group">
                                <input
                                    type="number"
                                    name="useful_life"
                                    class="form-control"
                                    min="1"
                                    placeholder="5"
                                >
                                <span class="input-group-text">
                                    Years
                                </span>
                            </div>
                        </div>
                        <!-- Residual Value -->
                        <div class="col-md-4 mb-3">
                            <label class="form-label">
                                Residual Value
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    Rp
                                </span>
                                <input
                                    type="number"
                                    name="residual_value"
                                    class="form-control"
                                    min="0"
                                    placeholder="0"
                                >
                            </div>
                        </div>
                        <!-- Depreciation Start -->
                        <div class="col-md-4 mb-3">
                            <label class="form-label">
                                Depreciation Start Date
                            </label>
                            <input
                                type="date"
                                name="depreciation_start_date"
                                class="form-control"
                            >
                        </div>
                    </div>
                </div>
            </div>
                        <!-- ================================================= -->
            <!-- 4. MAINTENANCE -->
            <!-- ================================================= -->
            <div class="card asset-section">
                <div class="card-body">

                    <div class="asset-section-header">
                        <div class="asset-section-number">
                            4
                        </div>

                        <div>
                            <h6 class="asset-section-title">
                                Maintenance
                            </h6>

                            <p class="asset-section-description">
                                Maintenance schedule and configuration
                            </p>
                        </div>
                    </div>

                    <div class="row">

                        <!-- Maintenance Required -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Maintenance Required
                            </label>

                            <div class="form-check form-switch mt-2">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    name="maintenance_required"
                                    id="maintenance_required"
                                    value="1"
                                >

                                <label
                                    class="form-check-label"
                                    for="maintenance_required"
                                    id="maintenance_required_label"
                                >
                                    No
                                </label>
                            </div>

                            <small class="text-muted">
                                Enable maintenance scheduling for this asset.
                            </small>
                        </div>

                    </div>

                    <!-- MAINTENANCE CONFIGURATION -->
                    <div id="maintenance_config" style="display: none;">

                        <div class="row">

                            <!-- Maintenance Type -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    Maintenance Type
                                </label>

                                <select
                                    name="maintenance_type"
                                    id="maintenance_type"
                                    class="form-select"
                                >
                                    <option value="">
                                        Select Maintenance Type
                                    </option>

                                    <option value="preventive">
                                        Preventive Maintenance
                                    </option>

                                    <option value="corrective">
                                        Corrective Maintenance
                                    </option>
                                </select>
                            </div>

                            <!-- Maintenance Trigger -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    Maintenance Trigger
                                </label>

                                <select
                                    name="maintenance_trigger"
                                    id="maintenance_trigger"
                                    class="form-select"
                                >
                                    <option value="">
                                        Select Trigger
                                    </option>

                                    <option value="calendar" selected>
                                        Calendar
                                    </option>

                                    <option value="usage">
                                        Usage / Meter
                                    </option>
                                </select>
                            </div>

                            <!-- Maintenance Interval -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    Maintenance Interval
                                </label>

                                <div class="input-group">

                                    <input
                                        type="number"
                                        name="maintenance_interval"
                                        id="maintenance_interval"
                                        class="form-control"
                                        min="1"
                                        placeholder="3"
                                    >

                                    <select
                                        name="maintenance_interval_unit"
                                        id="maintenance_interval_unit"
                                        class="form-select"
                                        style="max-width: 140px;"
                                    >
                                        <option value="day">
                                            Days
                                        </option>

                                        <option value="week">
                                            Weeks
                                        </option>

                                        <option value="month" selected>
                                            Months
                                        </option>

                                        <option value="year">
                                            Years
                                        </option>
                                    </select>

                                </div>

                                <small class="text-muted">
                                    Example: every 3 months.
                                </small>
                            </div>

                            <!-- Maintenance Start Date -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    Maintenance Start Date
                                </label>

                                <input
                                    type="date"
                                    name="maintenance_start_date"
                                    id="maintenance_start_date"
                                    class="form-control"
                                >

                                <small class="text-muted">
                                    Starting date for the maintenance schedule.
                                </small>
                            </div>

                            <!-- Last Maintenance -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    Last Maintenance
                                </label>

                                <input
                                    type="date"
                                    name="last_maintenance_date"
                                    id="last_maintenance_date"
                                    class="form-control"
                                >

                                <small class="text-muted">
                                    Leave empty if this asset has never been maintained.
                                </small>
                            </div>

                            <!-- Next Maintenance -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    Next Maintenance
                                </label>

                                <input
                                    type="date"
                                    id="next_maintenance_date_display"
                                    class="form-control"
                                    readonly
                                >

                                <small class="text-muted">
                                    Automatically calculated from the maintenance schedule.
                                </small>
                            </div>

                        </div>

                    </div>

                </div>
            </div>

            <!-- ================================================= -->
            <!-- 5. ASSIGNMENT -->
            <!-- ================================================= -->
            <div class="card asset-section">
                <div class="card-body">
                    <div class="asset-section-header">
                        <div class="asset-section-number">
                            5
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
                        <!-- Responsible -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Responsible
                            </label>
                            <select
                                name="responsible_user_id"
                                class="form-select"
                            >
                                <option value="">
                                    Select Responsible Person
                                </option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}">
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <!-- Location -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Location
                            </label>
                            <input
                                type="text"
                                name="location"
                                class="form-control"
                                placeholder="Example: Warehouse, Office, Room 01"
                            >
                        </div>
                    </div>
                </div>
            </div>
            <!-- ================================================= -->
            <!-- 6. ASSET PHOTOS -->
            <!-- ================================================= -->
            <div class="card asset-section">
                <div class="card-body">
                    <div class="asset-section-header">
                        <div class="asset-section-number">
                            6
                        </div>
                        <div>
                            <h6 class="asset-section-title">
                                Asset Photos
                                <span class="required">*</span>
                            </h6>
                            <p class="asset-section-description">
                                Upload maximum 3 photos of the asset
                            </p>
                        </div>
                    </div>
                    <label
                        for="asset_photos"
                        class="upload-box w-100"
                    >
                        <i class="fa-solid fa-cloud-arrow-up d-block"></i>
                        <strong>
                            Click to upload asset photos
                        </strong>
                        <div class="text-muted small mt-1">
                            Maximum 3 files
                            <br>
                            JPG, JPEG, PNG
                        </div>
                    </label>
                    <input
                        type="file"
                        name="asset_photos[]"
                        id="asset_photos"
                        class="d-none"
                        accept="image/jpeg,image/png"
                        multiple
                        required
                    >
                    <div
                        id="photo_preview"
                        class="upload-preview"
                    ></div>
                </div>
            </div>
            <!-- ================================================= -->
            <!-- 7. INVOICE DOCUMENT -->
            <!-- ================================================= -->
            <div class="card asset-section">
                <div class="card-body">
                    <div class="asset-section-header">
                        <div class="asset-section-number">
                            7
                        </div>
                        <div>
                            <h6 class="asset-section-title">
                                Invoice / Documents
                                <span class="required">*</span>
                            </h6>
                            <p class="asset-section-description">
                                Upload maximum 5 invoice or supporting documents
                            </p>
                        </div>
                    </div>
                    <label
                        for="invoice_documents"
                        class="upload-box w-100"
                    >
                        <i class="fa-solid fa-file-invoice d-block"></i>
                        <strong>
                            Click to upload invoice documents
                        </strong>
                        <div class="text-muted small mt-1">
                            Maximum 5 files
                            <br>
                            PDF, JPG, JPEG, PNG
                        </div>
                    </label>
                    <input
                        type="file"
                        name="invoice_documents[]"
                        id="invoice_documents"
                        class="d-none"
                        accept=".pdf,image/jpeg,image/png"
                        multiple
                        required
                    >
                    <div
                        id="invoice_preview"
                        class="upload-preview"
                    ></div>

                </div>

            </div>


            <!-- ================================================= -->
            <!-- FOOTER -->
            <!-- ================================================= -->

            <div class="sticky-footer">

                <div class="container-xxl px-0">

                    <div class="d-flex justify-content-end gap-2">

                        <a
                            href="{{ route('assets.index') }}"
                            class="btn btn-label-secondary"
                        >
                            Cancel
                        </a>


                        <button
                            type="submit"
                            class="btn btn-primary"
                            id="btnSaveAsset"
                        >

                            <i class="fa-solid fa-save me-1"></i>

                            Save Asset

                        </button>

                    </div>

                </div>

            </div>

        </form>

    </div>

</div>

@endsection


@section('js')

<!-- ============================================================ -->
<!-- SELECT2 -->
<!-- ============================================================ -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>

$(document).ready(function () {


    /*
    |--------------------------------------------------------------------------
    | CATEGORY SELECT2
    |--------------------------------------------------------------------------
    */

    $('#category_id').select2({

        width: '100%',

        placeholder: 'Select or type category',

        allowClear: true,

        tags: true,

        createTag: function (params) {

            let term = $.trim(params.term);

            if (term === '') {
                return null;
            }

            return {
                id: 'new:' + term,
                text: term,
                newTag: true
            };
        },

        templateResult: function (data) {

            if (data.newTag) {

                return $(
                    '<span>' +
                    '<i class="fa-solid fa-plus me-1"></i>' +
                    'Add "' + data.text + '"' +
                    '</span>'
                );

            }

            return data.text;
        }

    });


    /*
    |--------------------------------------------------------------------------
    | SUB CATEGORY SELECT2
    |--------------------------------------------------------------------------
    */

    $('#sub_category_id').select2({

        width: '100%',

        placeholder: 'Select or type sub category',

        allowClear: true,

        tags: true,

        createTag: function (params) {

            let term = $.trim(params.term);

            if (term === '') {
                return null;
            }


            let categoryId =
                $('#category_id').val();


            if (!categoryId) {

                Swal.fire({

                    icon: 'warning',

                    title: 'Select Category First',

                    text:
                        'Please select or create a category first.'

                });

                return null;
            }


            return {

                id: 'new:' + term,

                text: term,

                newTag: true

            };

        },


        templateResult: function (data) {

            if (data.newTag) {

                return $(
                    '<span>' +
                    '<i class="fa-solid fa-plus me-1"></i>' +
                    'Add "' + data.text + '"' +
                    '</span>'
                );

            }

            return data.text;

        }

    });


    /*
    |--------------------------------------------------------------------------
    | CATEGORY CHANGE
    |--------------------------------------------------------------------------
    */

    $('#category_id').on('change', function () {

        let categoryValue =
            $(this).val();


        let subCategory =
            $('#sub_category_id');


        /*
        | Reset sub category
        */

        subCategory
            .empty()
            .append(
                '<option value="">Select or type sub category</option>'
            )
            .trigger('change');


        if (!categoryValue) {
            return;
        }


        /*
        | Category baru
        |
        | Contoh:
        | new:ATK
        |
        | Belum memiliki category_id.
        */

        if (
            typeof categoryValue === 'string' &&
            categoryValue.startsWith('new:')
        ) {

            return;

        }


        /*
        |--------------------------------------------------------------------------
        | Load existing sub categories
        |--------------------------------------------------------------------------
        */

        @foreach($subCategories as $subCategory)

            if (
                "{{ $subCategory->category_id }}"
                ==
                categoryValue
            ) {

                subCategory.append(

                    new Option(

                        "{{ $subCategory->sub_category_name }}",

                        "{{ $subCategory->id }}",

                        false,

                        false

                    )

                );

            }

        @endforeach


        subCategory.trigger('change');

    });


    /*
    |--------------------------------------------------------------------------
    | VENDOR SELECT2
    |--------------------------------------------------------------------------
    |
    | Vendor TIDAK menggunakan tags.
    |
    | User hanya dapat memilih vendor yang sudah terdaftar.
    |
    */

    $('#vendor_id').select2({

        width: '100%',

        placeholder: 'Select vendor',

        allowClear: true

    });


    /*
    |--------------------------------------------------------------------------
    | SUB CATEGORY MUST HAVE CATEGORY
    |--------------------------------------------------------------------------
    */

    $('#sub_category_id').on(
        'select2:opening',
        function (e) {

            let category =
                $('#category_id').val();


            if (!category) {

                e.preventDefault();


                Swal.fire({

                    icon: 'warning',

                    title: 'Select Category First',

                    text:
                        'Please select or create a category first.'

                });

            }

        }
    );


    
   /*
|--------------------------------------------------------------------------
| PHOTO UPLOAD
|--------------------------------------------------------------------------
*/

let photoDataTransfer = new DataTransfer();

$('#asset_photos').on('change', function () {

    const input = this;
    const preview = $('#photo_preview');

    const MAX_PHOTOS = 3;
    const MAX_PHOTO_SIZE = 5 * 1024 * 1024;

    const newFiles = Array.from(input.files);

    /*
    |--------------------------------------------------------------------------
    | Add new files
    |--------------------------------------------------------------------------
    */

    for (let file of newFiles) {

        /*
        | Check duplicate file
        */

        let duplicate = Array.from(photoDataTransfer.files).some(
            existingFile =>
                existingFile.name === file.name &&
                existingFile.size === file.size
        );

        if (duplicate) {
            continue;
        }

        /*
        | Maximum 3 photos
        */

        if (photoDataTransfer.files.length >= MAX_PHOTOS) {

            Swal.fire({
                icon: 'warning',
                title: 'Maximum 3 Photos',
                text: 'Asset hanya dapat memiliki maksimal 3 foto.'
            });

            break;
        }

        /*
        | Validate MIME
        */

        if (!/^image\/(jpeg|png)$/.test(file.type)) {

            Swal.fire({
                icon: 'error',
                title: 'Format Tidak Valid',
                text: 'Foto harus JPG, JPEG atau PNG.'
            });

            continue;
        }

        /*
        | Validate size
        */

        if (file.size > MAX_PHOTO_SIZE) {

            Swal.fire({
                icon: 'error',
                title: 'Ukuran Foto Terlalu Besar',
                text:
                    `"${file.name}" memiliki ukuran ` +
                    `${(file.size / 1024 / 1024).toFixed(5)} MB. ` +
                    `Maksimal ukuran setiap foto adalah 5 MB.`
            });

            continue;
        }

        photoDataTransfer.items.add(file);
    }

    /*
    |--------------------------------------------------------------------------
    | Update input files
    |--------------------------------------------------------------------------
    */

    input.files = photoDataTransfer.files;

    /*
    |--------------------------------------------------------------------------
    | Render preview
    |--------------------------------------------------------------------------
    */

    renderPhotoPreview();

});


function renderPhotoPreview() {

    const preview = $('#photo_preview');

    preview.empty();

    Array.from(photoDataTransfer.files).forEach(
        function (file, index) {

            const reader = new FileReader();

            reader.onload = function (e) {

                preview.append(`
                    <div class="preview-item">

                        <div class="preview-item-left">

                            <img
                                src="${e.target.result}"
                                class="asset-photo-preview"
                            >

                            <div class="min-width-0">

                                <div class="fw-semibold preview-file-name">
                                    ${file.name}
                                </div>

                                <small class="text-muted">
                                    ${(file.size / 1024 / 1024).toFixed(5)} MB
                                </small>

                            </div>

                        </div>

                        <button
                            type="button"
                            class="preview-remove btn-remove-photo"
                            data-index="${index}"
                            title="Remove photo"
                        >
                            <i class="fa-solid fa-trash"></i>
                        </button>

                    </div>
                `);

            };

            reader.readAsDataURL(file);

        }
    );

    /*
    |--------------------------------------------------------------------------
    | Required validation
    |--------------------------------------------------------------------------
    */

    $('#asset_photos').prop(
        'required',
        photoDataTransfer.files.length === 0
    );

}


/*
|--------------------------------------------------------------------------
| REMOVE PHOTO
|--------------------------------------------------------------------------
*/

$(document).on(
    'click',
    '.btn-remove-photo',
    function () {

        const index = parseInt(
            $(this).data('index')
        );

        /*
        | Create new DataTransfer
        */

        const newDataTransfer = new DataTransfer();

        /*
        | Copy all files except selected file
        */

        Array.from(photoDataTransfer.files).forEach(
            function (file, fileIndex) {

                if (fileIndex !== index) {

                    newDataTransfer.items.add(file);

                }

            }
        );

        /*
        | Replace DataTransfer
        */

        photoDataTransfer = newDataTransfer;

        /*
        | Update input
        */

        $('#asset_photos')[0].files =
            photoDataTransfer.files;

        /*
        | Re-render preview
        */

        renderPhotoPreview();

    }
);


/*
|--------------------------------------------------------------------------
| INVOICE DOCUMENT UPLOAD
|--------------------------------------------------------------------------
*/

let documentDataTransfer = new DataTransfer();

$('#invoice_documents').on('change', function () {

    const input = this;

    const MAX_DOCUMENTS = 5;
    const MAX_DOCUMENT_SIZE = 5 * 1024 * 1024;

    const allowedExtensions = [
        'pdf',
        'jpg',
        'jpeg',
        'png'
    ];

    const newFiles = Array.from(input.files);

    /*
    |--------------------------------------------------------------------------
    | Add new files
    |--------------------------------------------------------------------------
    */

    for (let file of newFiles) {

        /*
        | Check duplicate
        */

        let duplicate =
            Array.from(
                documentDataTransfer.files
            ).some(
                existingFile =>
                    existingFile.name === file.name &&
                    existingFile.size === file.size
            );

        if (duplicate) {
            continue;
        }

        /*
        | Maximum documents
        */

        if (
            documentDataTransfer.files.length >=
            MAX_DOCUMENTS
        ) {

            Swal.fire({
                icon: 'warning',
                title: 'Maximum 5 Documents',
                text:
                    'Invoice/document hanya dapat maksimal 5 file.'
            });

            break;
        }

        /*
        | Extension
        */

        let extension =
            file.name
                .split('.')
                .pop()
                .toLowerCase();

        if (!allowedExtensions.includes(extension)) {

            Swal.fire({
                icon: 'error',
                title: 'Format Tidak Valid',
                text:
                    'Document hanya PDF, JPG, JPEG atau PNG.'
            });

            continue;
        }

        /*
        | File size
        */

        if (file.size > MAX_DOCUMENT_SIZE) {

            Swal.fire({
                icon: 'error',
                title: 'Ukuran Dokumen Terlalu Besar',
                text:
                    `"${file.name}" memiliki ukuran ` +
                    `${(file.size / 1024 / 1024).toFixed(5)} MB. ` +
                    `Maksimal ukuran setiap dokumen adalah 5 MB.`
            });

            continue;
        }

        documentDataTransfer.items.add(file);

    }

    /*
    |--------------------------------------------------------------------------
    | Update input
    |--------------------------------------------------------------------------
    */

    input.files =
        documentDataTransfer.files;

    /*
    |--------------------------------------------------------------------------
    | Render
    |--------------------------------------------------------------------------
    */

    renderDocumentPreview();

});


function renderDocumentPreview() {

    const preview =
        $('#invoice_preview');

    preview.empty();

    Array.from(
        documentDataTransfer.files
    ).forEach(
        function (file, index) {

            let extension =
                file.name
                    .split('.')
                    .pop()
                    .toLowerCase();

            let icon =
                extension === 'pdf'
                    ? 'fa-file-pdf'
                    : 'fa-file-image';

            preview.append(`

                <div class="preview-item">

                    <div class="preview-item-left">

                        <i
                            class="fa-solid ${icon}"
                        ></i>

                        <div class="min-width-0">

                            <div
                                class="preview-file-name fw-semibold"
                            >
                                ${file.name}
                            </div>

                            <small class="text-muted">

                                ${(
                                    file.size /
                                    1024 /
                                    1024
                                ).toFixed(5)} MB

                            </small>

                        </div>

                    </div>

                    <button
                        type="button"
                        class="preview-remove btn-remove-document"
                        data-index="${index}"
                        title="Remove document"
                    >
                        <i class="fa-solid fa-trash"></i>
                    </button>

                </div>

            `);

        }
    );

    /*
    |--------------------------------------------------------------------------
    | Required validation
    |--------------------------------------------------------------------------
    */

    $('#invoice_documents').prop(
        'required',
        documentDataTransfer.files.length === 0
    );

}


/*
|--------------------------------------------------------------------------
| REMOVE DOCUMENT
|--------------------------------------------------------------------------
*/

$(document).on(
    'click',
    '.btn-remove-document',
    function () {

        const index =
            parseInt(
                $(this).data('index')
            );

        const newDataTransfer =
            new DataTransfer();

        Array.from(
            documentDataTransfer.files
        ).forEach(
            function (file, fileIndex) {

                if (fileIndex !== index) {

                    newDataTransfer.items.add(file);

                }

            }
        );

        documentDataTransfer =
            newDataTransfer;

        $('#invoice_documents')[0].files =
            documentDataTransfer.files;

        renderDocumentPreview();

    }
);
    /*
|--------------------------------------------------------------------------
| MAINTENANCE
|--------------------------------------------------------------------------
*/

function updateMaintenanceUI() {

    const required = $('#maintenance_required').is(':checked');

    $('#maintenance_config').toggle(required);

    $('#maintenance_required_label').text(
        required ? 'Yes' : 'No'
    );

    if (!required) {

        $('#maintenance_type').val('');
        $('#maintenance_trigger').val('calendar');
        $('#maintenance_interval').val('');
        $('#maintenance_interval_unit').val('month');
        $('#maintenance_start_date').val('');
        $('#last_maintenance_date').val('');
        $('#next_maintenance_date_display').val('');
    }

    calculateNextMaintenanceDate();
}


/*
|--------------------------------------------------------------------------
| MAINTENANCE TOGGLE
|--------------------------------------------------------------------------
*/

$('#maintenance_required').on('change', function () {

    updateMaintenanceUI();

});


/*
|--------------------------------------------------------------------------
| CALCULATE NEXT MAINTENANCE
|--------------------------------------------------------------------------
*/

function calculateNextMaintenanceDate() {

    if (!$('#maintenance_required').is(':checked')) {

        $('#next_maintenance_date_display').val('');

        return;
    }

    const interval = parseInt(
        $('#maintenance_interval').val()
    );

    const unit = $('#maintenance_interval_unit').val();

    const lastMaintenance =
        $('#last_maintenance_date').val();

    const startDate =
        $('#maintenance_start_date').val();

    /*
    |--------------------------------------------------------------------------
    | Base date
    |--------------------------------------------------------------------------
    | Jika sudah pernah maintenance:
    | gunakan Last Maintenance.
    |
    | Jika belum pernah:
    | gunakan Maintenance Start Date.
    |--------------------------------------------------------------------------
    */

    const baseDate = lastMaintenance || startDate;

    if (
        !baseDate ||
        !interval ||
        interval < 1 ||
        !unit
    ) {

        $('#next_maintenance_date_display').val('');

        return;
    }

    const date = new Date(baseDate + 'T00:00:00');

    if (isNaN(date.getTime())) {

        $('#next_maintenance_date_display').val('');

        return;
    }

    switch (unit) {

        case 'day':
            date.setDate(
                date.getDate() + interval
            );
            break;

        case 'week':
            date.setDate(
                date.getDate() + (interval * 7)
            );
            break;

        case 'month':
            date.setMonth(
                date.getMonth() + interval
            );
            break;

        case 'year':
            date.setFullYear(
                date.getFullYear() + interval
            );
            break;
    }

    const year =
        date.getFullYear();

    const month =
        String(date.getMonth() + 1)
            .padStart(2, '0');

    const day =
        String(date.getDate())
            .padStart(2, '0');

    $('#next_maintenance_date_display').val(
        `${year}-${month}-${day}`
    );
}


/*
|--------------------------------------------------------------------------
| MAINTENANCE FIELD CHANGE
|--------------------------------------------------------------------------
*/

$(
    '#maintenance_interval, ' +
    '#maintenance_interval_unit, ' +
    '#maintenance_start_date, ' +
    '#last_maintenance_date'
).on('change input', function () {

    calculateNextMaintenanceDate();

});


/*
|--------------------------------------------------------------------------
| INITIAL STATE
|--------------------------------------------------------------------------
*/

updateMaintenanceUI();

    /*
    |--------------------------------------------------------------------------
    | SUBMIT
    |--------------------------------------------------------------------------
    */

    $('#formAddAsset').submit(function (e) {

        e.preventDefault();


        /*
        | Browser validation
        */

        if (!this.checkValidity()) {

            this.reportValidity();

            return;

        }


        let form = this;


        let formData =
            new FormData(form);


        let button =
            $('#btnSaveAsset');


        button

            .prop('disabled', true)

            .html(`

                <span
                    class="spinner-border spinner-border-sm me-1"
                ></span>

                Saving...

            `);


        $.ajax({

            url:
                "{{ route('assets.store') }}",

            type:
                "POST",

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


            success:
                function (response) {


                    if (
                        response.success ||
                        response.status
                    ) {


                        Swal.fire({

                            icon:
                                'success',

                            title:
                                'Berhasil',

                            text:
                                response.message ||
                                'Asset berhasil ditambahkan.',

                            timer:
                                1500,

                            showConfirmButton:
                                false

                        }).then(function () {


                            window.location.href =
                                "{{ route('assets.index') }}";

                        });

                    }

                },


            error:
                function (xhr) {


                    button

                        .prop(
                            'disabled',
                            false
                        )

                        .html(`

                            <i
                                class="fa-solid fa-save me-1"
                            ></i>

                            Save Asset

                        `);


                    /*
                    |--------------------------------------------------------------------------
                    | VALIDATION ERROR
                    |--------------------------------------------------------------------------
                    */

                    if (
                        xhr.status === 422
                    ) {


                        let errors =
                            xhr.responseJSON?.errors;


                        let message =
                            '';


                        if (errors) {


                            $.each(

                                errors,

                                function (
                                    key,
                                    value
                                ) {


                                    message +=

                                        value[0] +

                                        '<br>';

                                }

                            );

                        }


                        Swal.fire({

                            icon:
                                'error',

                            title:
                                'Validasi Gagal',

                            html:
                                message

                        });


                        return;

                    }


                    /*
                    |--------------------------------------------------------------------------
                    | GENERAL ERROR
                    |--------------------------------------------------------------------------
                    */

                    Swal.fire({

                        icon:
                            'error',

                        title:
                            'Gagal',

                        text:
                            xhr.responseJSON?.message ||
                            'Asset gagal disimpan.'

                    });

                }

        });

    });


    /*
    |--------------------------------------------------------------------------
    | FOCUS
    |--------------------------------------------------------------------------
    */

    $('#asset_name').trigger('focus');

});
</script>

@endsection
