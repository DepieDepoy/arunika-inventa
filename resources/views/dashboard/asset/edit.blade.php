@extends('dashboard.layouts.wrapper')

@section('title', 'Edit Asset')

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
    }

    .asset-section-description {
        margin: 2px 0 0;
        font-size: 12px;
        color: #8592a3;
    }

    .required {
        color: #ff3e1d;
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

    .select2-container {
        width: 100% !important;
    }

    .select2-container .select2-selection--single {
        height: 42px !important;
        border: 1px solid #d9dee3 !important;
        border-radius: 6px !important;
    }

    .select2-container .select2-selection--single .select2-selection__rendered {
        line-height: 40px !important;
        padding-left: 12px !important;
    }

    .select2-container .select2-selection--single .select2-selection__arrow {
        height: 40px !important;
    }

    .select2-dropdown {
        border-color: #d9dee3 !important;
    }

    .select2-results__option {
        padding: 8px 12px;
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
        gap: 15px;
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
        flex: 1;
    }

    .preview-item-left > i {
        font-size: 20px;
        color: #696cff;
        flex-shrink: 0;
    }

    .preview-file-name {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 500px;
    }

    .asset-photo-preview {
        width: 80px;
        height: 80px;
        object-fit: cover;
        border-radius: 8px;
        border: 1px solid #ddd;
        flex-shrink: 0;
    }

    .preview-remove {
        border: none;
        background: transparent;
        color: #ff3e1d;
        font-size: 16px;
        cursor: pointer;
        padding: 6px 8px;
        border-radius: 5px;
        flex-shrink: 0;
    }

    .preview-remove:hover {
        background: #fff0ed;
        color: #d92d20;
    }

    .old-file-badge {
        display: inline-block;
        font-size: 10px;
        padding: 2px 6px;
        border-radius: 4px;
        background: #eef0f2;
        color: #69707a;
        margin-left: 5px;
    }

    .sticky-footer {
        position: sticky;
        bottom: 0;
        z-index: 10;
        background: rgba(255, 255, 255, .95);
        border-top: 1px solid #e5e7eb;
        padding: 15px 0;
        margin-top: 10px;
    }
</style>

<div class="content-wrapper">

```
<div class="container-xxl flex-grow-1 container-p-y">

    <!-- ===================================================== -->
    <!-- HEADER -->
    <!-- ===================================================== -->

    <div class="asset-page-header">

        <div class="d-flex justify-content-between align-items-center">

            <div>
                <h4 class="fw-bold mb-1">
                    Edit Asset
                </h4>

                <p class="text-muted mb-0">
                    Update company asset information
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

    <form
        id="formEditAsset"
        action="{{ route('assets.update') }}"
        method="POST"
        enctype="multipart/form-data"
        data-existing-photo-count="{{ $asset->photos->count() }}"
        data-existing-document-count="{{ $asset->documents->count() }}"
    >

        @csrf

        <input
            type="hidden"
            name="id"
            value="{{ $asset->id }}"
        >


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

                    <!-- ASSET CODE -->

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Asset Code
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            value="{{ $asset->asset_code }}"
                            readonly
                        >

                        <small class="text-muted">
                            Asset code cannot be changed.
                        </small>

                    </div>


                    <!-- ASSET NAME -->

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
                            value="{{ old('asset_name', $asset->asset_name) }}"
                            placeholder="Enter asset name"
                            required
                        >

                    </div>

                    <!-- ASSET CONDITION -->

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Asset Condition
                            <span class="required">*</span>
                        </label>

                        <select
                            name="asset_condition"
                            id="asset_condition"
                            class="form-select"
                            required
                        >

                            <option value="">
                                Select Condition
                            </option>

                            <option
                                value="new"
                                {{ old('asset_condition', $asset->asset_condition) == 'new' ? 'selected' : '' }}
                            >
                                New
                            </option>

                            <option
                                value="used"
                                {{ old('asset_condition', $asset->asset_condition) == 'used' ? 'selected' : '' }}
                            >
                                Used
                            </option>

                        </select>

                        <small class="text-muted">
                            Indicates whether the asset was new or previously used when acquired.
                        </small>

                    </div>
                    <!-- CATEGORY -->

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
                                    {{ $asset->category_id == $category->id ? 'selected' : '' }}
                                >
                                    {{ $category->category_name }}
                                </option>

                            @endforeach

                        </select>

                        <small class="text-muted">
                            Type a new category if it is not registered yet.
                        </small>

                    </div>


                    <!-- SUB CATEGORY -->

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

                            @foreach($subCategories as $subCategory)

                                <option
                                    value="{{ $subCategory->id }}"
                                    data-category-id="{{ $subCategory->category_id }}"
                                    {{ $asset->sub_category_id == $subCategory->id ? 'selected' : '' }}
                                >
                                    {{ $subCategory->sub_category_name }}
                                </option>

                            @endforeach

                        </select>

                        <small class="text-muted">
                            Sub category will follow the selected category.
                        </small>

                    </div>


                    <!-- VENDOR -->

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

                                <option
                                    value="{{ $vendor->id }}"
                                    {{ $asset->vendor_id == $vendor->id ? 'selected' : '' }}
                                >
                                    {{ $vendor->vendor_name }}
                                </option>

                            @endforeach

                        </select>

                    </div>


                    <!-- BRAND -->

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Brand
                        </label>

                        <input
                            type="text"
                            name="brand"
                            class="form-control"
                            value="{{ old('brand', $asset->brand) }}"
                            placeholder="Example: Dell, HP, Lenovo"
                        >

                    </div>


                    <!-- MODEL -->

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Model
                        </label>

                        <input
                            type="text"
                            name="model"
                            class="form-control"
                            value="{{ old('model', $asset->model) }}"
                            placeholder="Enter asset model"
                        >

                    </div>


                    <!-- SERIAL -->

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Serial Number
                        </label>

                        <input
                            type="text"
                            name="serial_number"
                            class="form-control"
                            value="{{ old('serial_number', $asset->serial_number) }}"
                            placeholder="Enter serial number"
                        >

                    </div>


                    <!-- STATUS -->

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

                            <option
                                value="1"
                                {{ $asset->status == 1 ? 'selected' : '' }}
                            >
                                Active
                            </option>

                            <option
                                value="0"
                                {{ $asset->status == 0 ? 'selected' : '' }}
                            >
                                Inactive
                            </option>

                        </select>

                    </div>


                    <!-- DESCRIPTION -->

                    <div class="col-md-12 mb-3">

                        <label class="form-label">
                            Description
                        </label>

                        <textarea
                            name="description"
                            class="form-control"
                            rows="4"
                            placeholder="Enter asset description"
                        >{{ old('description', $asset->description) }}</textarea>

                    </div>

                </div>

            </div>

        </div>


        <!-- ================================================= -->
        <!-- 2. PURCHASE -->
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

                    <div class="col-md-4 mb-3">

                        <label class="form-label">
                            Purchase Date
                            <span class="required">*</span>
                        </label>

                        <input
                            type="date"
                            name="purchase_date"
                            class="form-control"
                            value="{{ old('purchase_date', $asset->purchase_date ? \Carbon\Carbon::parse($asset->purchase_date)->format('Y-m-d') : '') }}"
                            required
                        >

                    </div>


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
                                value="{{ old('purchase_price', $asset->purchase_price) }}"
                                placeholder="0"
                                required
                            >

                        </div>

                    </div>


                    <div class="col-md-4 mb-3">

                        <label class="form-label">
                            Purchase Number
                            <span class="required">*</span>
                        </label>

                        <input
                            type="text"
                            name="purchase_invoice"
                            class="form-control"
                            value="{{ old('purchase_invoice', $asset->purchase_invoice) }}"
                            placeholder="Enter invoice number"
                            required
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

                    <div class="col-md-4 mb-3">

                        <label class="form-label">
                            Depreciation Method
                        </label>

                        <select
                            name="depreciation_method"
                            class="form-select"
                        >

                            <option value="">
                                Select Method
                            </option>

                            <option
                                value="straight_line"
                                {{ $asset->depreciation_method == 'straight_line' ? 'selected' : '' }}
                            >
                                Straight Line
                            </option>

                        </select>

                    </div>


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
                                value="{{ old('useful_life', $asset->useful_life) }}"
                                placeholder="5"
                            >

                            <span class="input-group-text">
                                Years
                            </span>

                        </div>

                    </div>


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
                                value="{{ old('residual_value', $asset->residual_value) }}"
                                placeholder="0"
                            >

                        </div>

                    </div>


                    <div class="col-md-4 mb-3">

                        <label class="form-label">
                            Depreciation Start Date
                        </label>

                        <input
                            type="date"
                            name="depreciation_start_date"
                            class="form-control"
                            value="{{ old('depreciation_start_date', $asset->depreciation_start_date ? \Carbon\Carbon::parse($asset->depreciation_start_date)->format('Y-m-d') : '') }}"
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

                    <!-- MAINTENANCE REQUIRED -->

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Maintenance Required
                        </label>

                        {{-- Important: send 0 when checkbox is unchecked --}}
                        <input
                            type="hidden"
                            name="maintenance_required"
                            value="0"
                        >

                        <div class="form-check form-switch mt-2">

                            <input
                                class="form-check-input"
                                type="checkbox"
                                name="maintenance_required"
                                id="maintenance_required"
                                value="1"
                                {{ old('maintenance_required', $asset->maintenance_required) ? 'checked' : '' }}
                            >

                            <label
                                class="form-check-label"
                                for="maintenance_required"
                                id="maintenance_required_label"
                            >
                                {{ old('maintenance_required', $asset->maintenance_required) ? 'Yes' : 'No' }}
                            </label>

                        </div>

                        <small class="text-muted">
                            Enable maintenance scheduling for this asset.
                        </small>

                    </div>

                </div>


                <!-- MAINTENANCE CONFIGURATION -->
                <div
                    id="maintenance_config"
                    style="{{ old('maintenance_required', $asset->maintenance_required) ? '' : 'display: none;' }}"
                >
                    <div class="row">
                        <!-- MAINTENANCE TYPE -->
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
                                <option
                                    value="preventive"
                                    {{ old('maintenance_type', $asset->maintenance_type) == 'preventive' ? 'selected' : '' }}
                                >
                                    Preventive Maintenance
                                </option>
                                <option
                                    value="corrective"
                                    {{ old('maintenance_type', $asset->maintenance_type) == 'corrective' ? 'selected' : '' }}
                                >
                                    Corrective Maintenance
                                </option>
                            </select>
                        </div>
                        <!-- MAINTENANCE TRIGGER -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Maintenance Trigger
                            </label>
                            <select
                                name="maintenance_trigger"
                                id="maintenance_trigger"
                                class="form-select"
                            >
                                <option
                                    value="calendar"
                                    {{ old('maintenance_trigger', $asset->maintenance_trigger ?? 'calendar') == 'calendar' ? 'selected' : '' }}
                                >
                                    Calendar
                                </option>
                            </select>
                            <small class="text-muted">
                                Currently maintenance is scheduled based on calendar date.
                            </small>
                        </div>
                        <!-- MAINTENANCE INTERVAL -->
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
                                    value="{{ old('maintenance_interval', $asset->maintenance_interval) }}"
                                    placeholder="3"
                                >
                                <select
                                    name="maintenance_interval_unit"
                                    id="maintenance_interval_unit"
                                    class="form-select"
                                    style="max-width: 140px;"
                                >
                                    <option
                                        value="day"
                                        {{ old('maintenance_interval_unit', $asset->maintenance_interval_unit ?? 'month') == 'day' ? 'selected' : '' }}
                                    >
                                        Days
                                    </option>
                                    <option
                                        value="week"
                                        {{ old('maintenance_interval_unit', $asset->maintenance_interval_unit ?? 'month') == 'week' ? 'selected' : '' }}
                                    >
                                        Weeks
                                    </option>
                                    <option
                                        value="month"
                                        {{ old('maintenance_interval_unit', $asset->maintenance_interval_unit ?? 'month') == 'month' ? 'selected' : '' }}
                                    >
                                        Months
                                    </option>
                                    <option
                                        value="year"
                                        {{ old('maintenance_interval_unit', $asset->maintenance_interval_unit ?? 'month') == 'year' ? 'selected' : '' }}
                                    >
                                        Years
                                    </option>
                                </select>
                            </div>
                            <small class="text-muted">
                                Example: every 3 months.
                            </small>
                        </div>
                        <!-- START DATE -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Maintenance Start Date
                            </label>
                            <input
                                type="date"
                                name="maintenance_start_date"
                                id="maintenance_start_date"
                                class="form-control"
                                value="{{ old('maintenance_start_date', $asset->maintenance_start_date ? \Carbon\Carbon::parse($asset->maintenance_start_date)->format('Y-m-d') : '') }}"
                            >
                            <small class="text-muted">
                                Starting date for the maintenance schedule.
                            </small>
                        </div>
                        <!-- LAST MAINTENANCE -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Last Maintenance
                            </label>
                            <input
                                type="date"
                                name="last_maintenance_date"
                                id="last_maintenance_date"
                                class="form-control"
                                value="{{ old('last_maintenance_date', $asset->last_maintenance_date ? \Carbon\Carbon::parse($asset->last_maintenance_date)->format('Y-m-d') : '') }}"
                            >
                            <small class="text-muted">
                                Useful when the asset was previously used or already maintained.
                            </small>
                        </div>
                        <!-- NEXT MAINTENANCE -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Next Maintenance
                            </label>
                            <input
                                type="date"
                                name="next_maintenance_date"
                                id="next_maintenance_date"
                                class="form-control"
                                value="{{ old('next_maintenance_date', $asset->next_maintenance_date ? \Carbon\Carbon::parse($asset->next_maintenance_date)->format('Y-m-d') : '') }}"
                            >
                            <small class="text-muted">
                                Automatically calculated, but can be adjusted manually.
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

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Responsible
                        </label>

                        <select
                            name="responsible_user_id"
                            id="responsible_user_id"
                            class="form-select"
                        >

                            <option value="">
                                Select Responsible Person
                            </option>

                            @foreach($users as $user)

                                <option
                                    value="{{ $user->id }}"
                                    {{ $asset->responsible_user_id == $user->id ? 'selected' : '' }}
                                >
                                    {{ $user->name }}
                                </option>

                            @endforeach

                        </select>

                    </div>


                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Location
                        </label>

                        <input
                            type="text"
                            name="location"
                            class="form-control"
                            value="{{ old('location', $asset->location) }}"
                            placeholder="Example: Warehouse, Office, Room 01"
                        >

                    </div>

                </div>

            </div>

        </div>


        <!-- ================================================= -->
        <!-- 6. PHOTOS -->
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
                            Maximum 3 photos total
                        </p>

                    </div>

                </div>


                <!-- EXISTING PHOTOS -->

                <div id="existing_photo_preview">

                    @forelse($asset->photos as $photo)

                        @php
                            $photoUrl = asset('storage/' .$photo->file_path);
                        @endphp

                        <div
                            class="preview-item existing-photo-item"
                            data-id="{{ $photo->id }}"
                        >

                            <div class="preview-item-left">

                                <img
                                    src="{{ $photoUrl }}"
                                    class="asset-photo-preview"
                                    alt="{{ $photo->original_name ?? 'Asset Photo' }}"
                                >

                                <div class="min-width-0">

                                    <div class="fw-semibold preview-file-name">

                                        {{ $photo->original_name ?? basename($photo->file_path) }}

                                        <span class="old-file-badge">
                                            Existing
                                        </span>

                                    </div>

                                </div>

                            </div>


                            <div class="d-flex align-items-center gap-1">

                                <a
                                    href="{{ $photoUrl }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="btn btn-sm btn-outline-primary"
                                    title="View"
                                >
                                    <i class="fa-solid fa-eye"></i>
                                </a>

                                <button
                                    type="button"
                                    class="preview-remove btn-remove-existing-photo"
                                    data-id="{{ $photo->id }}"
                                >
                                    <i class="fa-solid fa-trash"></i>
                                </button>

                            </div>

                        </div>

                    @empty

                        <div
                            class="alert alert-warning"
                            id="no-existing-photo"
                        >
                            <i class="fa-solid fa-image me-1"></i>
                            Belum ada foto asset.
                        </div>

                    @endforelse

                </div>


                <!-- UPLOAD -->

                <label
                    for="asset_photos"
                    class="upload-box w-100"
                >

                    <i class="fa-solid fa-cloud-arrow-up d-block"></i>

                    <strong>
                        Click to add asset photos
                    </strong>

                    <div class="text-muted small mt-1">

                        Maximum 3 photos total

                        <br>

                        JPG, JPEG, PNG — Maximum 5 MB each

                    </div>

                </label>


                <input
                    type="file"
                    name="asset_photos[]"
                    id="asset_photos"
                    class="d-none"
                    accept="image/jpeg,image/png"
                    multiple
                >


                <div
                    id="photo_preview"
                    class="upload-preview"
                ></div>


                <!-- DELETE EXISTING PHOTOS -->

                <div id="deleted_photos_container"></div>

            </div>

        </div>


        <!-- ================================================= -->
        <!-- 7. DOCUMENTS -->
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
                            Maximum 5 invoice or supporting documents
                        </p>

                    </div>

                </div>


                <!-- EXISTING DOCUMENTS -->

                <div id="existing_invoice_preview">

                    @forelse($asset->documents as $document)

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

                            $documentUrl = asset(
                                'storage/' .$document->file_path
                            );

                        @endphp


                        <div
                            class="preview-item existing-document-item"
                            data-id="{{ $document->id }}"
                        >

                            <div class="preview-item-left">

                                <i
                                    class="fa-solid {{ $icon }}"
                                ></i>

                                <div class="min-width-0">

                                    <div class="fw-semibold preview-file-name">

                                        {{ $document->original_name ?? basename($document->file_path) }}

                                        <span class="old-file-badge">
                                            Existing
                                        </span>

                                    </div>

                                </div>

                            </div>


                            <div class="d-flex align-items-center gap-1">

                                <a
                                    href="{{ $documentUrl }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="btn btn-sm btn-outline-primary"
                                    title="View"
                                >
                                    <i class="fa-solid fa-eye"></i>
                                </a>

                                <button
                                    type="button"
                                    class="preview-remove btn-remove-existing-document"
                                    data-id="{{ $document->id }}"
                                >
                                    <i class="fa-solid fa-trash"></i>
                                </button>

                            </div>

                        </div>

                    @empty

                        <div
                            class="alert alert-warning"
                            id="no-existing-document"
                        >
                            <i class="fa-solid fa-file me-1"></i>
                            Belum ada invoice/document.
                        </div>

                    @endforelse

                </div>


                <!-- UPLOAD -->

                <label
                    for="invoice_documents"
                    class="upload-box w-100"
                >

                    <i class="fa-solid fa-file-invoice d-block"></i>

                    <strong>
                        Click to add invoice documents
                    </strong>

                    <div class="text-muted small mt-1">

                        Maximum 5 documents total

                        <br>

                        PDF, JPG, JPEG, PNG — Maximum 5 MB each

                    </div>

                </label>


                <input
                    type="file"
                    name="invoice_documents[]"
                    id="invoice_documents"
                    class="d-none"
                    accept=".pdf,.jpg,.jpeg,.png"
                    multiple
                >


                <div
                    id="invoice_preview"
                    class="upload-preview"
                ></div>


                <!-- DELETE EXISTING DOCUMENTS -->

                <div id="deleted_documents_container"></div>

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
                        id="btnUpdateAsset"
                    >
                        <i class="fa-solid fa-save me-1"></i>
                        Update Asset
                    </button>

                </div>

            </div>

        </div>

    </form>

</div>
```

</div>

@endsection

@section('js')

<link
    href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css"
    rel="stylesheet"
/>

<script
    src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"
></script>

<script>

$(function () {

    'use strict';


    // ============================================================
    // ELEMENTS
    // ============================================================

    const form = $('#formEditAsset');

    const photoInput =
        document.getElementById('asset_photos');

    const documentInput =
        document.getElementById('invoice_documents');

    const updateButton =
        $('#btnUpdateAsset');


    // ============================================================
    // CONFIGURATION
    // ============================================================

    const MAX_PHOTOS = 3;

    const MAX_DOCUMENTS = 5;

    const MAX_PHOTO_SIZE =
        5 * 1024 * 1024;

    const MAX_DOCUMENT_SIZE =
        5 * 1024 * 1024;


    // ============================================================
    // EXISTING FILE COUNT
    // ============================================================

    let existingPhotoCount =
        parseInt(
            form.attr('data-existing-photo-count'),
            10
        ) || 0;


    let existingDocumentCount =
        parseInt(
            form.attr('data-existing-document-count'),
            10
        ) || 0;


    // ============================================================
    // NEW FILE STORAGE
    // ============================================================

    let photoTransfer =
        new DataTransfer();

    let documentTransfer =
        new DataTransfer();


    // ============================================================
    // DELETED IDS
    // ============================================================

    let deletedPhotos = [];

    let deletedDocuments = [];


    // ============================================================
    // SELECT2
    // ============================================================

    $('#category_id').select2({

        width: '100%',

        placeholder: 'Select or type category',

        allowClear: true,

        tags: true,

        createTag: function (params) {

            const term =
                $.trim(params.term);

            if (!term) {
                return null;
            }

            return {
                id: 'new:' + term,
                text: term,
                newTag: true
            };
        }

    });


    $('#sub_category_id').select2({

        width: '100%',

        placeholder: 'Select or type sub category',

        allowClear: true,

        tags: true,

        createTag: function (params) {

            const term =
                $.trim(params.term);

            if (!term) {
                return null;
            }

            if (!$('#category_id').val()) {

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

        }

    });


    $('#vendor_id').select2({

        width: '100%',

        placeholder: 'Select vendor',

        allowClear: true

    });


    $('#responsible_user_id').select2({

        width: '100%',

        placeholder: 'Select Responsible Person',

        allowClear: true

    });

    // ============================================================
    // MAINTENANCE
    // ============================================================

    function calculateNextMaintenanceDate() {

        if (!$('#maintenance_required').is(':checked')) {
            $('#next_maintenance_date').val('');
            return;
        }

        const interval =
            parseInt($('#maintenance_interval').val(), 10);

        const unit =
            $('#maintenance_interval_unit').val();

        const lastMaintenance =
            $('#last_maintenance_date').val();

        const startDate =
            $('#maintenance_start_date').val();

        /*
        * If Last Maintenance exists,
        * use it as the base date.
        * Otherwise use Maintenance Start Date.
        */
        const baseDate =
            lastMaintenance || startDate;

        if (
            !baseDate ||
            !interval ||
            interval < 1 ||
            !unit
        ) {
            return;
        }

        const date =
            new Date(baseDate + 'T00:00:00');

        if (isNaN(date.getTime())) {
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
            String(
                date.getMonth() + 1
            ).padStart(2, '0');

        const day =
            String(
                date.getDate()
            ).padStart(2, '0');

        $('#next_maintenance_date').val(
            `${year}-${month}-${day}`
        );
    }


    function updateMaintenanceUI() {

        const required =
            $('#maintenance_required').is(':checked');

        $('#maintenance_config').toggle(required);

        $('#maintenance_required_label').text(
            required ? 'Yes' : 'No'
        );

        if (!required) {

            $('#maintenance_type').val('');

            $('#maintenance_trigger')
                .val('calendar');

            $('#maintenance_interval').val('');

            $('#maintenance_interval_unit')
                .val('month');

            $('#maintenance_start_date').val('');

            $('#last_maintenance_date').val('');

            $('#next_maintenance_date').val('');

        } else {

            calculateNextMaintenanceDate();

        }

    }


    $('#maintenance_required').on(
        'change',
        function () {

            updateMaintenanceUI();

        }
    );


    $(
        '#maintenance_interval, ' +
        '#maintenance_interval_unit, ' +
        '#maintenance_start_date, ' +
        '#last_maintenance_date'
    ).on(
        'change input',
        function () {

            calculateNextMaintenanceDate();

        }
    );


    // Initial maintenance state
    updateMaintenanceUI();

    // ============================================================
    // CATEGORY CHANGE
    // ============================================================

    $('#category_id').on(
        'change',
        function () {

            const categoryId =
                $(this).val();

            const subCategory =
                $('#sub_category_id');

            const currentValue =
                subCategory.val();


            const options = [];


            subCategory.find('option').each(
                function () {

                    const option =
                        $(this);

                    if (
                        option.val() !== ''
                    ) {

                        options.push({

                            value:
                                option.val(),

                            text:
                                option.text(),

                            categoryId:
                                option.attr(
                                    'data-category-id'
                                )

                        });

                    }

                }
            );


            subCategory.empty();


            subCategory.append(
                $('<option>', {
                    value: '',
                    text: 'Select or type sub category'
                })
            );


            if (!categoryId) {

                subCategory
                    .val(null)
                    .trigger('change');

                return;
            }


            if (
                typeof categoryId === 'string' &&
                categoryId.indexOf('new:') === 0
            ) {

                subCategory
                    .val(null)
                    .trigger('change');

                return;
            }


            options.forEach(
                function (item) {

                    if (
                        String(item.categoryId) ===
                        String(categoryId)
                    ) {

                        subCategory.append(

                            $('<option>', {

                                value:
                                    item.value,

                                text:
                                    item.text,

                                'data-category-id':
                                    item.categoryId

                            })

                        );

                    }

                }
            );


            if (
                currentValue &&
                subCategory.find(
                    'option[value="' +
                    currentValue +
                    '"]'
                ).length
            ) {

                subCategory
                    .val(currentValue)
                    .trigger('change');

            } else {

                subCategory
                    .val(null)
                    .trigger('change');

            }

        }
    );


    // ============================================================
    // REMOVE EXISTING PHOTO
    // ============================================================

    $(document).on(
        'click',
        '.btn-remove-existing-photo',
        function (event) {

            event.preventDefault();

            event.stopPropagation();


            const button =
                $(this);

            const id =
                String(
                    button.attr('data-id')
                );


            if (!id) {
                return;
            }


            Swal.fire({

                icon: 'warning',

                title: 'Remove Photo?',

                text:
                    'Photo akan dihapus ketika asset disimpan.',

                showCancelButton: true,

                confirmButtonText:
                    'Yes, Remove',

                cancelButtonText:
                    'Cancel'

            }).then(
                function (result) {

                    if (!result.isConfirmed) {
                        return;
                    }


                    if (
                        deletedPhotos.indexOf(id) === -1
                    ) {

                        deletedPhotos.push(id);

                    }


                    button
                        .closest(
                            '.existing-photo-item'
                        )
                        .remove();


                    existingPhotoCount =
                        Math.max(
                            0,
                            existingPhotoCount - 1
                        );


                    $('#deleted_photos_container')
                        .append(

                            $('<input>', {

                                type:
                                    'hidden',

                                name:
                                    'delete_images[]',

                                value:
                                    id

                            })

                        );


                    updatePhotoMessage();

                    updatePhotoRequired();

                }
            );

        }
    );


    // ============================================================
    // PHOTO MESSAGE
    // ============================================================

    function updatePhotoMessage() {

        const container =
            $('#existing_photo_preview');


        if (
            existingPhotoCount === 0 &&
            photoTransfer.files.length === 0
        ) {

            if (
                $('#no-existing-photo').length === 0
            ) {

                container.prepend(`

                    <div
                        class="alert alert-warning"
                        id="no-existing-photo"
                    >

                        <i
                            class="fa-solid fa-image me-1"
                        ></i>

                        Belum ada foto asset.

                    </div>

                `);

            }

        } else {

            $('#no-existing-photo').remove();

        }

    }


    // ============================================================
    // ADD NEW PHOTOS
    // ============================================================

    $('#asset_photos').on(
        'change',
        function () {

            const files =
                Array.from(this.files);


            let currentTotal =
                existingPhotoCount +
                photoTransfer.files.length;


            files.forEach(
                function (file) {

                    if (
                        currentTotal >=
                        MAX_PHOTOS
                    ) {

                        Swal.fire({

                            icon: 'warning',

                            title:
                                'Maximum 3 Photos',

                            text:
                                'Asset hanya dapat memiliki maksimal 3 foto.'

                        });

                        return;
                    }


                    const duplicate =
                        Array.from(
                            photoTransfer.files
                        ).some(
                            function (oldFile) {

                                return (
                                    oldFile.name ===
                                        file.name &&
                                    oldFile.size ===
                                        file.size
                                );

                            }
                        );


                    if (duplicate) {
                        return;
                    }


                    if (
                        ![
                            'image/jpeg',
                            'image/png'
                        ].includes(file.type)
                    ) {

                        Swal.fire({

                            icon: 'error',

                            title:
                                'Format Tidak Valid',

                            text:
                                'Foto harus JPG, JPEG atau PNG.'

                        });

                        return;
                    }


                    if (
                        file.size >
                        MAX_PHOTO_SIZE
                    ) {

                        Swal.fire({

                            icon: 'error',

                            title:
                                'Ukuran Foto Terlalu Besar',

                            text:
                                file.name +
                                ' maksimal 5 MB.'

                        });

                        return;
                    }


                    photoTransfer.items.add(file);

                    currentTotal++;

                }
            );


            photoInput.files =
                photoTransfer.files;


            renderPhotoPreview();

        }
    );


    // ============================================================
    // RENDER NEW PHOTOS
    // ============================================================

    function renderPhotoPreview() {

        const preview =
            $('#photo_preview');


        preview.empty();


        Array.from(
            photoTransfer.files
        ).forEach(
            function (file, index) {

                const reader =
                    new FileReader();


                reader.onload =
                    function (event) {

                        const html = `

                            <div
                                class="preview-item new-photo-item"
                            >

                                <div
                                    class="preview-item-left"
                                >

                                    <img
                                        src="${event.target.result}"
                                        class="asset-photo-preview"
                                        alt="New Photo"
                                    >

                                    <div
                                        class="min-width-0"
                                    >

                                        <div
                                            class="fw-semibold preview-file-name"
                                        >
                                            ${escapeHtml(file.name)}
                                        </div>

                                        <small
                                            class="text-muted"
                                        >

                                            ${(
                                                file.size /
                                                1024 /
                                                1024
                                            ).toFixed(2)} MB

                                            <span
                                                class="old-file-badge"
                                            >
                                                New
                                            </span>

                                        </small>

                                    </div>

                                </div>


                                <button
                                    type="button"
                                    class="preview-remove btn-remove-new-photo"
                                    data-index="${index}"
                                >

                                    <i
                                        class="fa-solid fa-trash"
                                    ></i>

                                </button>

                            </div>

                        `;


                        preview.append(html);

                    };


                reader.readAsDataURL(file);

            }
        );


        updatePhotoRequired();

        updatePhotoMessage();

    }


    // ============================================================
    // REMOVE NEW PHOTO
    // ============================================================

    $(document).on(
        'click',
        '.btn-remove-new-photo',
        function (event) {

            event.preventDefault();

            event.stopPropagation();


            const index =
                parseInt(
                    $(this).attr('data-index'),
                    10
                );


            const newTransfer =
                new DataTransfer();


            Array.from(
                photoTransfer.files
            ).forEach(
                function (file, fileIndex) {

                    if (
                        fileIndex !== index
                    ) {

                        newTransfer.items.add(file);

                    }

                }
            );


            photoTransfer =
                newTransfer;


            photoInput.files =
                photoTransfer.files;


            renderPhotoPreview();

        }
    );


    // ============================================================
    // PHOTO REQUIRED
    // ============================================================

    function updatePhotoRequired() {

        const total =
            existingPhotoCount +
            photoTransfer.files.length;


        $(photoInput).prop(
            'required',
            total === 0
        );

    }


    // ============================================================
    // REMOVE EXISTING DOCUMENT
    // ============================================================

    $(document).on(
        'click',
        '.btn-remove-existing-document',
        function (event) {

            event.preventDefault();

            event.stopPropagation();


            const button =
                $(this);

            const id =
                String(
                    button.attr('data-id')
                );


            if (!id) {
                return;
            }


            Swal.fire({

                icon: 'warning',

                title:
                    'Remove Document?',

                text:
                    'Document akan dihapus ketika asset disimpan.',

                showCancelButton: true,

                confirmButtonText:
                    'Yes, Remove',

                cancelButtonText:
                    'Cancel'

            }).then(
                function (result) {

                    if (!result.isConfirmed) {
                        return;
                    }


                    if (
                        deletedDocuments.indexOf(id) === -1
                    ) {

                        deletedDocuments.push(id);

                    }


                    button
                        .closest(
                            '.existing-document-item'
                        )
                        .remove();


                    existingDocumentCount =
                        Math.max(
                            0,
                            existingDocumentCount - 1
                        );


                    $('#deleted_documents_container')
                        .append(

                            $('<input>', {

                                type:
                                    'hidden',

                                name:
                                    'delete_invoice_documents[]',

                                value:
                                    id

                            })

                        );


                    updateDocumentMessage();

                    updateDocumentRequired();

                }
            );

        }
    );


    // ============================================================
    // DOCUMENT MESSAGE
    // ============================================================

    function updateDocumentMessage() {

        const container =
            $('#existing_invoice_preview');


        if (
            existingDocumentCount === 0 &&
            documentTransfer.files.length === 0
        ) {

            if (
                $('#no-existing-document').length === 0
            ) {

                container.prepend(`

                    <div
                        class="alert alert-warning"
                        id="no-existing-document"
                    >

                        <i
                            class="fa-solid fa-file me-1"
                        ></i>

                        Belum ada invoice/document.

                    </div>

                `);

            }

        } else {

            $('#no-existing-document').remove();

        }

    }


    // ============================================================
    // ADD NEW DOCUMENTS
    // ============================================================

    $('#invoice_documents').on(
        'change',
        function () {

            const files =
                Array.from(this.files);


            let currentTotal =
                existingDocumentCount +
                documentTransfer.files.length;


            files.forEach(
                function (file) {

                    if (
                        currentTotal >=
                        MAX_DOCUMENTS
                    ) {

                        Swal.fire({

                            icon: 'warning',

                            title:
                                'Maximum 5 Documents',

                            text:
                                'Invoice/document hanya dapat maksimal 5 file.'

                        });

                        return;
                    }


                    const duplicate =
                        Array.from(
                            documentTransfer.files
                        ).some(
                            function (oldFile) {

                                return (
                                    oldFile.name ===
                                        file.name &&
                                    oldFile.size ===
                                        file.size
                                );

                            }
                        );


                    if (duplicate) {
                        return;
                    }


                    const extension =
                        file.name
                            .split('.')
                            .pop()
                            .toLowerCase();


                    if (
                        ![
                            'pdf',
                            'jpg',
                            'jpeg',
                            'png'
                        ].includes(extension)
                    ) {

                        Swal.fire({

                            icon: 'error',

                            title:
                                'Format Tidak Valid',

                            text:
                                'Document hanya PDF, JPG, JPEG atau PNG.'

                        });

                        return;
                    }


                    if (
                        file.size >
                        MAX_DOCUMENT_SIZE
                    ) {

                        Swal.fire({

                            icon: 'error',

                            title:
                                'Ukuran Dokumen Terlalu Besar',

                            text:
                                file.name +
                                ' maksimal 5 MB.'

                        });

                        return;
                    }


                    documentTransfer.items.add(file);

                    currentTotal++;

                }
            );


            documentInput.files =
                documentTransfer.files;


            renderDocumentPreview();

        }
    );


    // ============================================================
    // RENDER NEW DOCUMENTS
    // ============================================================

    function renderDocumentPreview() {

        const preview =
            $('#invoice_preview');


        preview.empty();


        Array.from(
            documentTransfer.files
        ).forEach(
            function (file, index) {

                const extension =
                    file.name
                        .split('.')
                        .pop()
                        .toLowerCase();


                const icon =
                    extension === 'pdf'
                        ? 'fa-file-pdf'
                        : 'fa-file-image';


                preview.append(`

                    <div
                        class="preview-item new-document-item"
                    >

                        <div
                            class="preview-item-left"
                        >

                            <i
                                class="fa-solid ${icon}"
                            ></i>

                            <div
                                class="min-width-0"
                            >

                                <div
                                    class="preview-file-name fw-semibold"
                                >
                                    ${escapeHtml(file.name)}
                                </div>

                                <small
                                    class="text-muted"
                                >

                                    ${(
                                        file.size /
                                        1024 /
                                        1024
                                    ).toFixed(2)} MB

                                    <span
                                        class="old-file-badge"
                                    >
                                        New
                                    </span>

                                </small>

                            </div>

                        </div>


                        <button
                            type="button"
                            class="preview-remove btn-remove-new-document"
                            data-index="${index}"
                        >

                            <i
                                class="fa-solid fa-trash"
                            ></i>

                        </button>

                    </div>

                `);

            }
        );


        updateDocumentRequired();

        updateDocumentMessage();

    }


    // ============================================================
    // REMOVE NEW DOCUMENT
    // ============================================================

    $(document).on(
        'click',
        '.btn-remove-new-document',
        function (event) {

            event.preventDefault();

            event.stopPropagation();


            const index =
                parseInt(
                    $(this).attr('data-index'),
                    10
                );


            const newTransfer =
                new DataTransfer();


            Array.from(
                documentTransfer.files
            ).forEach(
                function (file, fileIndex) {

                    if (
                        fileIndex !== index
                    ) {

                        newTransfer.items.add(file);

                    }

                }
            );


            documentTransfer =
                newTransfer;


            documentInput.files =
                documentTransfer.files;


            renderDocumentPreview();

        }
    );


    // ============================================================
    // DOCUMENT REQUIRED
    // ============================================================

    function updateDocumentRequired() {

        const total =
            existingDocumentCount +
            documentTransfer.files.length;


        $(documentInput).prop(
            'required',
            total === 0
        );

    }


    // ============================================================
    // ESCAPE HTML
    // ============================================================

    function escapeHtml(value) {

        return $('<div>')
            .text(value)
            .html();

    }


    // ============================================================
    // FORM SUBMIT
    // ============================================================

    form.on(
        'submit',
        function (event) {

            event.preventDefault();


            // ----------------------------------------------------
            // BROWSER VALIDATION
            // ----------------------------------------------------

            if (
                !this.checkValidity()
            ) {

                this.reportValidity();

                return;

            }


            // ----------------------------------------------------
            // TOTAL FILES
            // ----------------------------------------------------

            const totalPhotos =
                existingPhotoCount +
                photoTransfer.files.length;


            const totalDocuments =
                existingDocumentCount +
                documentTransfer.files.length;


            // ----------------------------------------------------
            // PHOTO REQUIRED
            // ----------------------------------------------------

            if (
                totalPhotos < 1
            ) {

                Swal.fire({

                    icon: 'warning',

                    title:
                        'Photo Required',

                    text:
                        'Asset harus memiliki minimal 1 foto.'

                });

                return;

            }


            // ----------------------------------------------------
            // DOCUMENT REQUIRED
            // ----------------------------------------------------

            if (
                totalDocuments < 1
            ) {

                Swal.fire({

                    icon: 'warning',

                    title:
                        'Document Required',

                    text:
                        'Asset harus memiliki minimal 1 document.'

                });

                return;

            }


            // ----------------------------------------------------
            // MAX PHOTOS
            // ----------------------------------------------------

            if (
                totalPhotos >
                MAX_PHOTOS
            ) {

                Swal.fire({

                    icon: 'warning',

                    title:
                        'Maximum 3 Photos',

                    text:
                        'Asset hanya dapat memiliki maksimal 3 foto.'

                });

                return;

            }


            // ----------------------------------------------------
            // MAX DOCUMENTS
            // ----------------------------------------------------

            if (
                totalDocuments >
                MAX_DOCUMENTS
            ) {

                Swal.fire({

                    icon: 'warning',

                    title:
                        'Maximum 5 Documents',

                    text:
                        'Invoice/document hanya dapat maksimal 5 file.'

                });

                return;

            }


            // ----------------------------------------------------
            // CREATE FORM DATA
            // ----------------------------------------------------

            const formData =
                new FormData(this);


            // ----------------------------------------------------
            // REMOVE DEFAULT FILE INPUT
            // ----------------------------------------------------

            formData.delete(
                'asset_photos[]'
            );

            formData.delete(
                'invoice_documents[]'
            );


            // ----------------------------------------------------
            // ADD NEW PHOTOS
            // ----------------------------------------------------

            Array.from(
                photoTransfer.files
            ).forEach(
                function (file) {

                    formData.append(
                        'asset_photos[]',
                        file
                    );

                }
            );


            // ----------------------------------------------------
            // ADD NEW DOCUMENTS
            // ----------------------------------------------------

            Array.from(
                documentTransfer.files
            ).forEach(
                function (file) {

                    formData.append(
                        'invoice_documents[]',
                        file
                    );

                }
            );


            // ----------------------------------------------------
            // ADD DELETED PHOTOS
            // ----------------------------------------------------

            deletedPhotos.forEach(
                function (id) {

                    formData.append(
                        'delete_images[]',
                        id
                    );

                }
            );


            // ----------------------------------------------------
            // ADD DELETED DOCUMENTS
            // ----------------------------------------------------

            deletedDocuments.forEach(
                function (id) {

                    formData.append(
                        'delete_invoice_documents[]',
                        id
                    );

                }
            );


            // ----------------------------------------------------
            // BUTTON LOADING
            // ----------------------------------------------------

            updateButton
                .prop(
                    'disabled',
                    true
                )
                .html(`

                    <span
                        class="spinner-border spinner-border-sm me-1"
                    ></span>

                    Updating...

                `);


            // ----------------------------------------------------
            // AJAX
            // ----------------------------------------------------

            $.ajax({

                url:
                    form.attr('action'),

                type:
                    'POST',

                data:
                    formData,

                processData:
                    false,

                contentType:
                    false,

                headers: {

                    Accept:
                        'application/json'

                },


                success:
                    function (response) {

                        console.log(
                            'UPDATE SUCCESS:',
                            response
                        );


                        const isSuccess =
                            response.success === true ||
                            response.status === true ||
                            response.status === 'success';


                        if (
                            isSuccess
                        ) {

                            Swal.fire({

                                icon:
                                    'success',

                                title:
                                    'Berhasil',

                                text:
                                    response.message ||
                                    'Asset berhasil diperbarui.',

                                timer:
                                    1500,

                                showConfirmButton:
                                    false

                            }).then(
                                function () {

                                    window.location.href =
                                        "{{ route('assets.index') }}";

                                }
                            );


                            return;

                        }


                        resetUpdateButton();


                        Swal.fire({

                            icon:
                                'error',

                            title:
                                'Gagal',

                            text:
                                response.message ||
                                'Asset gagal diperbarui.'

                        });

                    },


                error:
                    function (xhr) {

                        console.error(
                            'UPDATE ERROR:',
                            xhr.status
                        );


                        console.error(
                            xhr.responseJSON
                        );


                        resetUpdateButton();


                        const response =
                            xhr.responseJSON || {};


                        // ----------------------------------------
                        // VALIDATION ERROR
                        // ----------------------------------------

                        if (
                            xhr.status === 422
                        ) {

                            let message =
                                'Data yang dimasukkan tidak valid.<br><br>';


                            if (
                                response.errors
                            ) {

                                $.each(
                                    response.errors,
                                    function (
                                        field,
                                        messages
                                    ) {

                                        message +=
                                            '<b>' +
                                            escapeHtml(field) +
                                            '</b>: ';


                                        if (
                                            Array.isArray(
                                                messages
                                            )
                                        ) {

                                            message +=
                                                messages
                                                    .map(
                                                        function (
                                                            item
                                                        ) {

                                                            return escapeHtml(
                                                                item
                                                            );

                                                        }
                                                    )
                                                    .join(
                                                        ', '
                                                    );

                                        } else {

                                            message +=
                                                escapeHtml(
                                                    messages
                                                );

                                        }


                                        message +=
                                            '<br>';

                                    }
                                );

                            } else if (
                                response.message
                            ) {

                                message =
                                    escapeHtml(
                                        response.message
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


                        // ----------------------------------------
                        // OTHER ERROR
                        // ----------------------------------------

                        Swal.fire({

                            icon:
                                'error',

                            title:
                                'Gagal',

                            text:
                                response.message ||
                                'Terjadi kesalahan pada server.'

                        });

                    }

            });

        }
    );


    // ============================================================
    // RESET BUTTON
    // ============================================================

    function resetUpdateButton() {

        updateButton
            .prop(
                'disabled',
                false
            )
            .html(`

                <i
                    class="fa-solid fa-save me-1"
                ></i>

                Update Asset

            `);

    }


    // ============================================================
    // INITIAL STATE
    // ============================================================

    updatePhotoRequired();

    updateDocumentRequired();

});

</script>

@endsection
