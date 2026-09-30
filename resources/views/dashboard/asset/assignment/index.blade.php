@extends('dashboard.layouts.wrapper')

@section('title', 'Asset Assignment')

@section('content')

<style>

    /* =========================================================
       HEADER
    ========================================================= */

    .assignment-header {
        border-radius: 12px;
        background: linear-gradient(
            135deg,
            #696cff 0%,
            #8592ff 100%
        );
        color: #fff;
        padding: 24px;
    }

    .assignment-header .asset-code {
        font-size: 13px;
        font-weight: 600;
        opacity: .85;
        letter-spacing: .5px;
    }

    .assignment-header .asset-name {
        font-size: 21px;
        font-weight: 600;
        margin-top: 4px;
    }

    .assignment-header .asset-meta {
        font-size: 13px;
        opacity: .9;
    }


    /* =========================================================
       CARD
    ========================================================= */

    .section-card {
        border-radius: 12px;
        border: 1px solid #e7e7e7;
        box-shadow: 0 3px 12px rgba(0, 0, 0, .04);
    }

    .section-title {
        font-size: 15px;
        font-weight: 600;
        color: #566a7f;
    }

    .section-description {
        font-size: 12px;
        color: #a1acb8;
        margin-bottom: 0;
    }


    /* =========================================================
       CURRENT USER
    ========================================================= */

    .current-user-box {
        background: #f8f9fc;
        border: 1px solid #e7e7e7;
        border-radius: 10px;
        padding: 18px;
    }

    .current-user-icon {
        width: 48px;
        height: 48px;
        min-width: 48px;
        border-radius: 50%;
        background: rgba(105, 108, 255, .12);
        color: #696cff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }

    .current-user-name {
        font-size: 16px;
        font-weight: 600;
        color: #566a7f;
    }

    .current-user-meta {
        font-size: 12px;
        color: #a1acb8;
    }


    /* =========================================================
       ACTION CARD
    ========================================================= */

    .action-card {
        border-radius: 10px;
        border: 1px solid #e7e7e7;
        padding: 18px;
        height: 100%;
    }

    .action-icon {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        margin-bottom: 12px;
    }

    .action-icon.assign {
        background: rgba(105, 108, 255, .12);
        color: #696cff;
    }

    .action-icon.return {
        background: rgba(113, 221, 55, .12);
        color: #71dd37;
    }

    .action-title {
        font-weight: 600;
        color: #566a7f;
        margin-bottom: 4px;
    }

    .action-description {
        font-size: 12px;
        color: #a1acb8;
        margin-bottom: 15px;
    }


    /* =========================================================
       USER SEARCH
    ========================================================= */

    .user-search-wrapper {
        position: relative;
    }

    .user-search-input {
        height: 42px;
        width: 100%;
        border: 1px solid #d9dee3;
        border-radius: 6px;
        padding: 8px 40px 8px 40px;
        font-size: 14px;
        color: #566a7f;
        background-color: #fff;
        outline: none;
        transition:
            border-color .15s ease,
            box-shadow .15s ease;
    }

    .user-search-input:focus {
        border-color: #696cff;
        box-shadow: 0 0 0 .15rem rgba(105, 108, 255, .12);
    }

    .user-search-input:disabled {
        background-color: #f8f9fa;
        cursor: not-allowed;
    }

    .user-search-input::placeholder {
        color: #a1acb8;
    }

    .user-search-icon {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #a1acb8;
        pointer-events: none;
        z-index: 2;
    }

    .user-search-clear {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        border: 0;
        background: transparent;
        color: #a1acb8;
        padding: 0;
        width: 24px;
        height: 24px;
        display: none;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        z-index: 3;
    }

    .user-search-clear:hover {
        color: #566a7f;
    }


    /* =========================================================
       USER SEARCH RESULTS
    ========================================================= */

    .user-search-results {
        position: absolute;
        top: calc(100% + 5px);
        left: 0;
        right: 0;
        background: #fff;
        border: 1px solid #e1e5e9;
        border-radius: 8px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, .12);
        max-height: 280px;
        overflow-y: auto;
        z-index: 1080;
        display: none;
    }

    .user-search-result {
        padding: 11px 13px;
        cursor: pointer;
        border-bottom: 1px solid #f0f1f2;
        transition: background-color .12s ease;
    }

    .user-search-result:last-child {
        border-bottom: 0;
    }

    .user-search-result:hover {
        background-color: #f5f6ff;
    }

    .user-search-result-name {
        font-size: 13px;
        font-weight: 600;
        color: #566a7f;
    }

    .user-search-result-meta {
        font-size: 11px;
        color: #a1acb8;
        margin-top: 3px;
    }

    .user-search-result-icon {
        width: 34px;
        height: 34px;
        min-width: 34px;
        border-radius: 50%;
        background: rgba(105, 108, 255, .10);
        color: #696cff;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 10px;
    }

    .user-search-loading,
    .user-search-empty,
    .user-search-minimum {
        padding: 18px;
        text-align: center;
        color: #a1acb8;
        font-size: 12px;
    }


    /* =========================================================
       SELECTED USER
    ========================================================= */

    .selected-user-box {
        display: none;
        margin-top: 8px;
        padding: 10px 12px;
        border: 1px solid rgba(105, 108, 255, .18);
        background: rgba(105, 108, 255, .05);
        border-radius: 7px;
    }

    .selected-user-box .selected-user-name {
        font-size: 13px;
        font-weight: 600;
        color: #566a7f;
    }

    .selected-user-box .selected-user-meta {
        font-size: 11px;
        color: #a1acb8;
        margin-top: 2px;
    }

    .selected-user-clear {
        border: 0;
        background: transparent;
        color: #a1acb8;
        cursor: pointer;
        padding: 4px;
    }

    .selected-user-clear:hover {
        color: #ff3e1d;
    }


    /* =========================================================
       HISTORY TABLE
    ========================================================= */

    .history-table th {
        font-size: 12px;
        font-weight: 600;
        color: #566a7f;
        white-space: nowrap;
    }

    .history-table td {
        font-size: 13px;
        vertical-align: middle;
    }

    .user-name {
        font-weight: 600;
        color: #566a7f;
    }

    .user-email {
        font-size: 11px;
        color: #a1acb8;
    }

    .type-badge {
        font-size: 11px;
        padding: 5px 9px;
        border-radius: 20px;
    }

    .empty-assignment {
        padding: 30px 20px;
        text-align: center;
        color: #a1acb8;
    }

    .empty-assignment i {
        font-size: 34px;
        margin-bottom: 10px;
    }


    /* =========================================================
       HISTORY TIMELINE
    ========================================================= */

    .history-user-cell {
        min-width: 160px;
    }

    .history-action-cell {
        min-width: 110px;
    }

    .history-date-cell {
        min-width: 145px;
        white-space: nowrap;
    }

    .history-status-cell {
        min-width: 90px;
    }

    .history-reason {
        max-width: 260px;
        white-space: normal;
        color: #566a7f;
    }


    /* =========================================================
       MODAL
    ========================================================= */

    #assignModal .modal-body,
    #returnModal .modal-body {
        overflow: visible;
    }

</style>


<div class="content-wrapper">

    <div class="container-xxl flex-grow-1 container-p-y">


        {{-- ========================================================= --}}
        {{-- HEADER --}}
        {{-- ========================================================= --}}

        <div class="assignment-header mb-4">

            <div class="d-flex justify-content-between align-items-start">

                <div>

                    <div class="asset-code">
                        {{ $asset->asset_code }}
                    </div>

                    <div class="asset-name">
                        {{ $asset->asset_name }}
                    </div>

                    <div class="asset-meta mt-1">

                        {{ $asset->category?->category_name ?? '-' }}

                        @if($asset->subCategory)
                            • {{ $asset->subCategory->sub_category_name }}
                        @endif

                        @if($asset->brand)
                            • {{ $asset->brand }}
                        @endif

                    </div>

                </div>


                <div>

                    <a
                        href="{{ route('assets.show', $encryptedId) }}"
                        class="btn btn-light btn-sm"
                    >

                        <i class="fa-solid fa-arrow-left me-1"></i>

                        Asset Detail

                    </a>

                </div>

            </div>

        </div>


        {{-- ========================================================= --}}
        {{-- CURRENT ASSIGNMENT --}}
        {{-- ========================================================= --}}

        <div class="card section-card mb-4">

            <div class="card-body">

                <div class="d-flex justify-content-between align-items-center mb-3">

                    <div>

                        <div class="section-title">

                            <i class="fa-solid fa-user-check me-1"></i>

                            Current Assignment

                        </div>

                        <p class="section-description">

                            User yang saat ini bertanggung jawab atas asset.

                        </p>

                    </div>


                    @if($asset->responsibleUser)

                        <span class="badge bg-success">
                            Active
                        </span>

                    @else

                        <span class="badge bg-secondary">
                            Unassigned
                        </span>

                    @endif

                </div>


                @if($asset->responsibleUser)

                    <div class="current-user-box">

                        <div class="d-flex align-items-center">

                            <div class="current-user-icon">

                                <i class="fa-solid fa-user"></i>

                            </div>


                            <div class="ms-3">

                                <div class="current-user-name">

                                    {{ $asset->responsibleUser->name }}

                                </div>


                                @if($asset->responsibleUser->nik)

                                    <div class="current-user-meta">

                                        NIK:
                                        {{ $asset->responsibleUser->nik }}

                                    </div>

                                @endif


                                @if($asset->responsibleUser->email)

                                    <div class="current-user-meta">

                                        {{ $asset->responsibleUser->email }}

                                    </div>

                                @endif

                            </div>

                        </div>

                    </div>

                @else

                    <div class="alert alert-secondary mb-0">

                        <div class="d-flex align-items-center">

                            <i class="fa-solid fa-box-open me-2"></i>

                            <div>

                                <strong>
                                    Asset belum diberikan kepada user.
                                </strong>

                                <div class="small">

                                    Asset saat ini berstatus
                                    unassigned / stock.

                                </div>

                            </div>

                        </div>

                    </div>

                @endif

            </div>

        </div>


        {{-- ========================================================= --}}
        {{-- ACTION --}}
        {{-- ========================================================= --}}

        <div class="card section-card mb-4">

            <div class="card-body">

                <div class="mb-3">

                    <div class="section-title">

                        <i class="fa-solid fa-gears me-1"></i>

                        Assignment Action

                    </div>

                    <p class="section-description">

                        Kelola perpindahan tanggung jawab asset.

                    </p>

                </div>


                <div class="row g-3">


                    {{-- ================================================= --}}
                    {{-- ASSIGN / TRANSFER --}}
                    {{-- ================================================= --}}

                    <div class="col-md-6">

                        <div class="action-card">

                            <div class="action-icon assign">

                                @if($asset->responsibleUser)

                                    <i class="fa-solid fa-right-left"></i>

                                @else

                                    <i class="fa-solid fa-user-plus"></i>

                                @endif

                            </div>


                            <div class="action-title">

                                @if($asset->responsibleUser)

                                    Transfer Asset

                                @else

                                    Assign Asset

                                @endif

                            </div>


                            <div class="action-description">

                                @if($asset->responsibleUser)

                                    Pindahkan asset dari user saat ini
                                    kepada user lain.

                                @else

                                    Berikan asset kepada user yang
                                    bertanggung jawab.

                                @endif

                            </div>


                            <button
                                type="button"
                                class="btn btn-primary"
                                data-bs-toggle="modal"
                                data-bs-target="#assignModal"
                            >

                                @if($asset->responsibleUser)

                                    <i class="fa-solid fa-right-left me-1"></i>

                                    Transfer Asset

                                @else

                                    <i class="fa-solid fa-user-plus me-1"></i>

                                    Assign Asset

                                @endif

                            </button>

                        </div>

                    </div>


                    {{-- ================================================= --}}
                    {{-- RETURN --}}
                    {{-- ================================================= --}}

                    <div class="col-md-6">

                        <div class="action-card">

                            <div class="action-icon return">

                                <i class="fa-solid fa-rotate-left"></i>

                            </div>


                            <div class="action-title">

                                Return Asset

                            </div>


                            <div class="action-description">

                                Kembalikan asset dari user sehingga
                                menjadi unassigned / stock.

                            </div>


                            @if($asset->responsibleUser)

                                <button
                                    type="button"
                                    class="btn btn-success"
                                    data-bs-toggle="modal"
                                    data-bs-target="#returnModal"
                                >

                                    <i class="fa-solid fa-rotate-left me-1"></i>

                                    Return Asset

                                </button>

                            @else

                                <button
                                    type="button"
                                    class="btn btn-secondary"
                                    disabled
                                >

                                    <i class="fa-solid fa-ban me-1"></i>

                                    Already Unassigned

                                </button>

                            @endif

                        </div>

                    </div>

                </div>

            </div>

        </div>


        {{-- ========================================================= --}}
        {{-- HISTORY --}}
        {{-- ========================================================= --}}

        <div class="card section-card">

            <div class="card-body">

                <div class="d-flex justify-content-between align-items-center mb-3">

                    <div>

                        <div class="section-title">

                            <i class="fa-solid fa-clock-rotate-left me-1"></i>

                            Assignment History

                        </div>

                        <p class="section-description">

                            Riwayat lengkap perpindahan dan pengembalian asset.

                        </p>

                    </div>


                    <span class="badge bg-label-primary">

                        {{ $assignments->count() }} Record

                    </span>

                </div>


                @if($assignments->count() > 0)

                    <div class="table-responsive">

                        <table class="table table-hover history-table">

                            <thead>

                                <tr>

                                    <th>
                                        User
                                    </th>

                                    <th>
                                        Action
                                    </th>

                                    <th>
                                        Start
                                    </th>

                                    <th>
                                        End
                                    </th>

                                    <th>
                                        Status
                                    </th>

                                    <th>
                                        Reason
                                    </th>

                                    <th>
                                        Notes
                                    </th>

                                    <th>
                                        Created By
                                    </th>

                                </tr>

                            </thead>


                            <tbody>

                                @foreach($assignments as $assignment)

                                    @php

                                        $type =
                                            strtolower(
                                                (string) $assignment->assignment_type
                                            );

                                        /*
                                        |--------------------------------------------------------------------------
                                        | Action label
                                        |--------------------------------------------------------------------------
                                        */

                                        $typeLabel = match($type) {

                                            'initial' =>
                                                'Initial',

                                            'assign' =>
                                                'Assign',

                                            'transfer' =>
                                                'Transfer',

                                            'return' =>
                                                'Return',

                                            default =>
                                                ucfirst($type),

                                        };


                                        /*
                                        |--------------------------------------------------------------------------
                                        | Action badge
                                        |--------------------------------------------------------------------------
                                        */

                                        $typeClass = match($type) {

                                            'initial' =>
                                                'bg-label-info',

                                            'assign' =>
                                                'bg-label-primary',

                                            'transfer' =>
                                                'bg-label-warning',

                                            'return' =>
                                                'bg-label-success',

                                            default =>
                                                'bg-label-secondary',

                                        };

                                    @endphp


                                    <tr>


                                        {{-- ================================================= --}}
                                        {{-- USER --}}
                                        {{-- ================================================= --}}

                                        <td class="history-user-cell">

                                            <div class="d-flex align-items-center">

                                                <div class="user-search-result-icon">

                                                    <i class="fa-solid fa-user"></i>

                                                </div>


                                                <div>

                                                    <div class="user-name">

                                                        {{ $assignment->user?->name ?? '-' }}

                                                    </div>


                                                    @if($assignment->user?->nik)

                                                        <div class="user-email">

                                                            NIK:
                                                            {{ $assignment->user->nik }}

                                                        </div>

                                                    @endif


                                                    @if($assignment->user?->email)

                                                        <div class="user-email">

                                                            {{ $assignment->user->email }}

                                                        </div>

                                                    @endif

                                                </div>

                                            </div>

                                        </td>


                                        {{-- ================================================= --}}
                                        {{-- ACTION --}}
                                        {{-- ================================================= --}}

                                        <td class="history-action-cell">

                                            <span
                                                class="badge {{ $typeClass }} type-badge"
                                            >

                                                @if($type === 'initial')

                                                    <i class="fa-solid fa-box-open me-1"></i>

                                                @elseif($type === 'assign')

                                                    <i class="fa-solid fa-user-plus me-1"></i>

                                                @elseif($type === 'transfer')

                                                    <i class="fa-solid fa-right-left me-1"></i>

                                                @elseif($type === 'return')

                                                    <i class="fa-solid fa-rotate-left me-1"></i>

                                                @endif

                                                {{ $typeLabel }}

                                            </span>

                                        </td>


                                        {{-- ================================================= --}}
                                        {{-- START --}}
                                        {{-- ================================================= --}}

                                        <td class="history-date-cell">

                                            @if($assignment->start_at)

                                                {{ $assignment->start_at->format('d M Y') }}

                                                <div class="text-muted small">

                                                    {{ $assignment->start_at->format('H:i') }}

                                                </div>

                                            @else

                                                -

                                            @endif

                                        </td>


                                        {{-- ================================================= --}}
                                        {{-- END --}}
                                        {{-- ================================================= --}}

                                        <td class="history-date-cell">

                                            @if($assignment->end_at)

                                                {{ $assignment->end_at->format('d M Y') }}

                                                <div class="text-muted small">

                                                    {{ $assignment->end_at->format('H:i') }}

                                                </div>

                                            @else

                                                -

                                            @endif

                                        </td>


                                        {{-- ================================================= --}}
                                        {{-- STATUS --}}
                                        {{-- ================================================= --}}

                                        <td class="history-status-cell">
                                            @if($type === 'return')
                                                <span class="badge bg-success">
                                                    Returned
                                                </span>
                                            @elseif(is_null($assignment->end_at))
                                                <span class="badge bg-primary">
                                                    Active
                                                </span>
                                            @else
                                                <span class="badge bg-secondary">
                                                    Finished
                                                </span>
                                            @endif
                                        </td>
                                        {{-- ================================================= --}}
                                        {{-- REASON --}}
                                        {{-- ================================================= --}}

                                        <td>

                                            <div class="history-reason">

                                                {{ $assignment->reason ?: '-' }}

                                            </div>

                                        </td>


                                        {{-- ================================================= --}}
                                        {{-- NOTES --}}
                                        {{-- ================================================= --}}

                                        <td>

                                            <div class="history-reason">

                                                {{ $assignment->notes ?: '-' }}

                                            </div>

                                        </td>


                                        {{-- ================================================= --}}
                                        {{-- CREATED BY --}}
                                        {{-- ================================================= --}}

                                        <td>

                                            <div class="user-name">

                                                {{ $assignment->creator?->name ?? '-' }}

                                            </div>

                                        </td>

                                    </tr>

                                @endforeach

                            </tbody>

                        </table>

                    </div>

                @else

                    <div class="empty-assignment">

                        <i class="fa-solid fa-clock-rotate-left d-block"></i>

                        <div>

                            Belum ada history assignment.

                        </div>

                    </div>

                @endif

            </div>

        </div>

    </div>

</div>


{{-- ========================================================= --}}
{{-- ASSIGN / TRANSFER MODAL --}}
{{-- ========================================================= --}}

<div
    class="modal fade"
    id="assignModal"
    tabindex="-1"
    aria-hidden="true"
>

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">

            <form
                id="assignForm"
                method="POST"
                action="{{ route('assets.assignment.assign', $encryptedId) }}"
            >

                @csrf


                {{-- ================================================= --}}
                {{-- MODAL HEADER --}}
                {{-- ================================================= --}}

                <div class="modal-header">

                    <h5 class="modal-title">

                        @if($asset->responsibleUser)

                            Transfer Asset

                        @else

                            Assign Asset

                        @endif

                    </h5>


                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                    ></button>

                </div>


                {{-- ================================================= --}}
                {{-- MODAL BODY --}}
                {{-- ================================================= --}}

                <div class="modal-body">


                    {{-- CURRENT USER --}}

                    @if($asset->responsibleUser)

                        <div class="alert alert-light border">

                            <div class="small text-muted mb-1">

                                Current Responsible

                            </div>


                            <div class="fw-semibold">

                                <i class="fa-solid fa-user me-1"></i>

                                {{ $asset->responsibleUser->name }}

                            </div>

                        </div>

                    @endif


                    {{-- ================================================= --}}
                    {{-- ASSIGN TO --}}
                    {{-- ================================================= --}}

                    <div class="mb-3">

                        <label
                            for="assignment_user_search"
                            class="form-label"
                        >

                            Assign To

                            <span class="text-danger">*</span>

                        </label>


                        <div class="user-search-wrapper">

                            <i
                                class="fa-solid fa-magnifying-glass user-search-icon"
                            ></i>


                            <input
                                type="text"
                                id="assignment_user_search"
                                class="user-search-input"
                                autocomplete="off"
                                placeholder="Ketik nama, NIK, atau email..."
                            >


                            <button
                                type="button"
                                id="assignment_user_clear"
                                class="user-search-clear"
                                aria-label="Clear"
                            >

                                <i class="fa-solid fa-xmark"></i>

                            </button>


                            <div
                                id="assignment_user_results"
                                class="user-search-results"
                            ></div>

                        </div>


                        {{-- Hidden user ID --}}

                        <input
                            type="hidden"
                            id="assignment_user_id"
                            name="user_id"
                            value=""
                        >


                        {{-- Selected user --}}

                        <div
                            id="selected_user_box"
                            class="selected-user-box"
                        >

                            <div class="d-flex align-items-center">

                                <div class="user-search-result-icon">

                                    <i class="fa-solid fa-user"></i>

                                </div>


                                <div class="flex-grow-1">

                                    <div
                                        id="selected_user_name"
                                        class="selected-user-name"
                                    ></div>


                                    <div
                                        id="selected_user_meta"
                                        class="selected-user-meta"
                                    ></div>

                                </div>


                                <button
                                    type="button"
                                    id="selected_user_clear"
                                    class="selected-user-clear"
                                    title="Ganti user"
                                >

                                    <i class="fa-solid fa-xmark"></i>

                                </button>

                            </div>

                        </div>


                        <small class="text-muted">

                            Ketik minimal 2 karakter untuk mencari
                            nama, NIK, atau email.

                        </small>

                    </div>


                    {{-- ================================================= --}}
                    {{-- START DATE --}}
                    {{-- ================================================= --}}

                    <div class="mb-3">

                        <label class="form-label">

                            Start Date & Time

                            <span class="text-danger">*</span>

                        </label>


                        <input
                            type="datetime-local"
                            name="start_at"
                            class="form-control"
                            value="{{ now()->format('Y-m-d\TH:i') }}"
                            required
                        >


                        <small class="text-muted">

                            Waktu mulai user menerima tanggung jawab
                            asset.

                        </small>

                    </div>


                    {{-- ================================================= --}}
                    {{-- REASON --}}
                    {{-- ================================================= --}}

                    <div class="mb-3">

                        <label class="form-label">

                            Reason

                        </label>


                        <input
                            type="text"
                            name="reason"
                            class="form-control"
                            maxlength="255"
                            placeholder="Contoh: Laptop baru / Tukeran dengan user B"
                        >

                    </div>


                    {{-- ================================================= --}}
                    {{-- NOTES --}}
                    {{-- ================================================= --}}

                    <div class="mb-0">

                        <label class="form-label">

                            Notes

                        </label>


                        <textarea
                            name="notes"
                            class="form-control"
                            rows="3"
                            placeholder="Catatan tambahan..."
                        ></textarea>

                    </div>

                </div>


                {{-- ================================================= --}}
                {{-- MODAL FOOTER --}}
                {{-- ================================================= --}}

                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-label-secondary"
                        data-bs-dismiss="modal"
                    >

                        Batal

                    </button>


                    <button
                        type="submit"
                        class="btn btn-primary"
                    >

                        @if($asset->responsibleUser)

                            <i class="fa-solid fa-right-left me-1"></i>

                            Transfer Asset

                        @else

                            <i class="fa-solid fa-user-plus me-1"></i>

                            Assign Asset

                        @endif

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


{{-- ========================================================= --}}
{{-- RETURN MODAL --}}
{{-- ========================================================= --}}

<div
    class="modal fade"
    id="returnModal"
    tabindex="-1"
    aria-hidden="true"
>

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">

            <form
                method="POST"
                action="{{ route('assets.assignment.return', $encryptedId) }}"
            >

                @csrf


                {{-- ================================================= --}}
                {{-- HEADER --}}
                {{-- ================================================= --}}

                <div class="modal-header">

                    <h5 class="modal-title">

                        <i class="fa-solid fa-rotate-left me-1"></i>

                        Return Asset

                    </h5>


                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                    ></button>

                </div>


                {{-- ================================================= --}}
                {{-- BODY --}}
                {{-- ================================================= --}}

                <div class="modal-body">


                    <div class="alert alert-warning">

                        <div class="d-flex">

                            <i
                                class="fa-solid fa-triangle-exclamation me-2 mt-1"
                            ></i>


                            <div>

                                <strong>
                                    Return asset?
                                </strong>


                                <div class="small mt-1">

                                    Asset akan dikembalikan dari

                                    <strong>
                                        {{ $asset->responsibleUser?->name }}
                                    </strong>

                                    ke kantor / stock.

                                </div>

                            </div>

                        </div>

                    </div>


                    {{-- ================================================= --}}
                    {{-- RETURN DATE --}}
                    {{-- ================================================= --}}

                    <div class="mb-3">

                        <label class="form-label">

                            Return Date & Time

                            <span class="text-danger">*</span>

                        </label>


                        <input
                            type="datetime-local"
                            name="end_at"
                            class="form-control"
                            value="{{ now()->format('Y-m-d\TH:i') }}"
                            required
                        >


                        <small class="text-muted">

                            Waktu asset dikembalikan ke kantor.

                        </small>

                    </div>


                    {{-- ================================================= --}}
                    {{-- REASON --}}
                    {{-- ================================================= --}}

                    <div class="mb-3">

                        <label class="form-label">

                            Reason

                        </label>


                        <input
                            type="text"
                            name="reason"
                            class="form-control"
                            maxlength="255"
                            placeholder="Contoh: Ganti laptop"
                        >

                    </div>


                    {{-- ================================================= --}}
                    {{-- NOTES --}}
                    {{-- ================================================= --}}

                    <div class="mb-0">

                        <label class="form-label">

                            Notes

                        </label>


                        <textarea
                            name="notes"
                            class="form-control"
                            rows="3"
                            placeholder="Catatan tambahan..."
                        ></textarea>

                    </div>

                </div>


                {{-- ================================================= --}}
                {{-- FOOTER --}}
                {{-- ================================================= --}}

                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-label-secondary"
                        data-bs-dismiss="modal"
                    >

                        Batal

                    </button>


                    <button
                        type="submit"
                        class="btn btn-success"
                    >

                        <i class="fa-solid fa-rotate-left me-1"></i>

                        Return Asset

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


@endsection


@section('js')

<script>

$(document).ready(function () {

    /*
    |--------------------------------------------------------------------------
    | URL
    |--------------------------------------------------------------------------
    */

    const assignmentUsersUrl =
        "{{ route('assets.assignment.users', $encryptedId) }}";


    /*
    |--------------------------------------------------------------------------
    | Variables
    |--------------------------------------------------------------------------
    */

    let searchTimer = null;

    let currentRequest = null;


    /*
    |--------------------------------------------------------------------------
    | Elements
    |--------------------------------------------------------------------------
    */

    const searchInput =
        $('#assignment_user_search');

    const hiddenUserId =
        $('#assignment_user_id');

    const resultsBox =
        $('#assignment_user_results');

    const clearButton =
        $('#assignment_user_clear');

    const selectedBox =
        $('#selected_user_box');

    const selectedName =
        $('#selected_user_name');

    const selectedMeta =
        $('#selected_user_meta');

    const selectedClear =
        $('#selected_user_clear');


    /*
    |--------------------------------------------------------------------------
    | Show results
    |--------------------------------------------------------------------------
    */

    function showResults()
    {
        resultsBox.show();
    }


    /*
    |--------------------------------------------------------------------------
    | Hide results
    |--------------------------------------------------------------------------
    */

    function hideResults()
    {
        resultsBox.hide();
    }


    /*
    |--------------------------------------------------------------------------
    | Clear selected user
    |--------------------------------------------------------------------------
    */

    function clearUserSelection()
    {
        hiddenUserId.val('');

        searchInput
            .val('')
            .prop('disabled', false);

        selectedName.text('');

        selectedMeta.text('');

        selectedBox.hide();

        clearButton.hide();

        resultsBox.empty();

        hideResults();
    }


    /*
    |--------------------------------------------------------------------------
    | Select user
    |--------------------------------------------------------------------------
    */

    function selectUser(user)
    {
        hiddenUserId.val(user.id);

        searchInput
            .val(user.name || user.text || '')
            .prop('disabled', true);

        let meta = '';


        if (user.nik) {

            meta =
                'NIK: ' +
                user.nik;

        }


        if (user.email) {

            if (meta) {

                meta += ' • ';

            }

            meta += user.email;

        }


        selectedName.text(
            user.name || user.text || ''
        );

        selectedMeta.text(meta);

        selectedBox.show();

        clearButton.hide();

        resultsBox.empty();

        hideResults();
    }


    /*
    |--------------------------------------------------------------------------
    | Loading
    |--------------------------------------------------------------------------
    */

    function showLoading()
    {
        resultsBox.html(
            '<div class="user-search-loading">' +

                '<i class="fa-solid fa-spinner fa-spin me-1"></i>' +

                'Mencari user...' +

            '</div>'
        );

        showResults();
    }


    /*
    |--------------------------------------------------------------------------
    | Minimum
    |--------------------------------------------------------------------------
    */

    function showMinimumMessage()
    {
        resultsBox.html(
            '<div class="user-search-minimum">' +

                '<i class="fa-solid fa-keyboard me-1"></i>' +

                'Ketik minimal 2 karakter.' +

            '</div>'
        );

        showResults();
    }


    /*
    |--------------------------------------------------------------------------
    | Empty
    |--------------------------------------------------------------------------
    */

    function showEmpty()
    {
        resultsBox.html(
            '<div class="user-search-empty">' +

                '<i class="fa-solid fa-user-slash d-block mb-1"></i>' +

                'User tidak ditemukan.' +

            '</div>'
        );

        showResults();
    }


    /*
    |--------------------------------------------------------------------------
    | Search users
    |--------------------------------------------------------------------------
    */

    function searchUsers(keyword)
    {
        keyword =
            $.trim(keyword);


        if (keyword.length < 2) {

            showMinimumMessage();

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Abort previous request
        |--------------------------------------------------------------------------
        */

        if (currentRequest) {

            currentRequest.abort();

        }


        showLoading();


        currentRequest = $.ajax({

            url: assignmentUsersUrl,

            type: 'GET',

            dataType: 'json',

            data: {

                q: keyword,

                page: 1

            },


            success: function (response) {

                resultsBox.empty();


                const results =
                    response.results || [];


                if (!results.length) {

                    showEmpty();

                    return;
                }


                results.forEach(function (user) {

                    const item =
                        $('<div>');


                    item
                        .addClass('user-search-result')
                        .attr(
                            'data-user-id',
                            user.id
                        );


                    const row =
                        $('<div>')
                            .addClass(
                                'd-flex align-items-center'
                            );


                    const icon =
                        $('<div>')
                            .addClass(
                                'user-search-result-icon'
                            )
                            .html(
                                '<i class="fa-solid fa-user"></i>'
                            );


                    const content =
                        $('<div>')
                            .addClass(
                                'flex-grow-1'
                            );


                    const name =
                        $('<div>')
                            .addClass(
                                'user-search-result-name'
                            )
                            .text(
                                user.name ||
                                user.text ||
                                '-'
                            );


                    let metaText = '';


                    if (user.nik) {

                        metaText =
                            'NIK: ' +
                            user.nik;

                    }


                    if (user.email) {

                        if (metaText) {

                            metaText +=
                                ' • ';

                        }

                        metaText +=
                            user.email;

                    }


                    const meta =
                        $('<div>')
                            .addClass(
                                'user-search-result-meta'
                            )
                            .text(metaText);


                    content.append(name);


                    if (metaText) {

                        content.append(meta);

                    }


                    row.append(icon);

                    row.append(content);

                    item.append(row);


                    /*
                    |--------------------------------------------------------------------------
                    | Use mousedown
                    |--------------------------------------------------------------------------
                    |
                    | Prevent input blur from hiding the result before
                    | the selected user is processed.
                    |
                    */

                    item.on(
                        'mousedown',
                        function (event) {

                            event.preventDefault();

                            selectUser(user);

                        }
                    );


                    resultsBox.append(item);

                });


                showResults();

            },


            error: function (xhr, status) {

                if (status === 'abort') {

                    return;
                }


                resultsBox.html(
                    '<div class="user-search-empty">' +

                        '<i class="fa-solid fa-triangle-exclamation d-block mb-1"></i>' +

                        'Gagal mengambil data user.' +

                    '</div>'
                );

                showResults();

            },


            complete: function () {

                currentRequest = null;

            }

        });
    }


    /*
    |--------------------------------------------------------------------------
    | Input
    |--------------------------------------------------------------------------
    */

    searchInput.on(
        'input',
        function () {

            const keyword =
                $(this).val();


            /*
            |--------------------------------------------------------------------------
            | Because user started typing again,
            | selected user is no longer valid.
            |--------------------------------------------------------------------------
            */

            hiddenUserId.val('');

            selectedBox.hide();


            clearButton.toggle(
                keyword.length > 0
            );


            clearTimeout(searchTimer);


            searchTimer =
                setTimeout(
                    function () {

                        searchUsers(keyword);

                    },
                    300
                );
        }
    );


    /*
    |--------------------------------------------------------------------------
    | Focus
    |--------------------------------------------------------------------------
    */

    searchInput.on(
        'focus',
        function () {

            const keyword =
                $.trim(
                    $(this).val()
                );


            if (!keyword) {

                showMinimumMessage();

            }

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Clear button
    |--------------------------------------------------------------------------
    */

    clearButton.on(
        'click',
        function () {

            clearUserSelection();

            searchInput.focus();

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Selected user clear
    |--------------------------------------------------------------------------
    */

    selectedClear.on(
        'click',
        function () {

            clearUserSelection();

            searchInput.focus();

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Click outside
    |--------------------------------------------------------------------------
    */

    $(document).on(
        'mousedown',
        function (event) {

            if (
                !$(event.target).closest(
                    '.user-search-wrapper'
                ).length
            ) {

                hideResults();

            }

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Modal shown
    |--------------------------------------------------------------------------
    */

    $('#assignModal').on(
        'shown.bs.modal',
        function () {

            clearUserSelection();


            setTimeout(
                function () {

                    searchInput.focus();

                },
                150
            );

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Modal hidden
    |--------------------------------------------------------------------------
    */

    $('#assignModal').on(
        'hidden.bs.modal',
        function () {

            if (currentRequest) {

                currentRequest.abort();

                currentRequest = null;

            }


            clearTimeout(searchTimer);


            clearUserSelection();

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Submit validation
    |--------------------------------------------------------------------------
    */

    $('#assignForm').on(
        'submit',
        function (event) {

            const userId =
                hiddenUserId.val();


            if (!userId) {

                event.preventDefault();


                searchInput.focus();


                resultsBox.html(
                    '<div class="user-search-empty text-danger">' +

                        '<i class="fa-solid fa-circle-exclamation me-1"></i>' +

                        'Silakan pilih user terlebih dahulu.' +

                    '</div>'
                );


                showResults();


                return false;
            }

        }
    );

});

</script>

@endsection