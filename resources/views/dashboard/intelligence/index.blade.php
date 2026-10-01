@extends('dashboard.layouts.wrapper')

@section('title', 'Vasetra Intelligence')

@section('content')

<style>
    /* =========================================================
       VASETRA INTELLIGENCE
    ========================================================= */

    .vi-page {
        background: #f7f9fc;
        min-height: calc(100vh - 140px);
        padding: 24px;
    }

    .vi-container {
        max-width: 1500px;
        margin: 0 auto;
    }


    /* =========================================================
       HEADER
    ========================================================= */

    .vi-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        margin-bottom: 22px;
    }

    .vi-header-left {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .vi-ai-icon {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #eef5ff;
        color: #2563eb;
        font-size: 23px;
        flex-shrink: 0;
    }

    .vi-title {
        margin: 0;
        font-size: 24px;
        font-weight: 700;
        color: #172033;
        letter-spacing: -0.3px;
    }

    .vi-subtitle {
        margin: 3px 0 0;
        font-size: 13px;
        color: #7a8497;
    }

    .vi-header-actions {
        display: flex;
        gap: 8px;
        align-items: center;
    }

    .vi-btn {
        border: 1px solid #e4e8ef;
        background: #ffffff;
        color: #344054;
        padding: 9px 14px;
        border-radius: 9px;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        transition: all .2s ease;
    }

    .vi-btn:hover {
        color: #2563eb;
        border-color: #cbdaf7;
        background: #f8fbff;
    }

    .vi-btn-primary {
        background: #2563eb;
        border-color: #2563eb;
        color: #ffffff;
    }

    .vi-btn-primary:hover {
        color: #ffffff;
        background: #1d4ed8;
        border-color: #1d4ed8;
    }


    /* =========================================================
       AI STATUS
    ========================================================= */

    .vi-status {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 6px 10px;
        border-radius: 30px;
        background: #ecfdf3;
        color: #15803d;
        font-size: 11px;
        font-weight: 700;
    }

    .vi-status-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: #22c55e;
    }


    /* =========================================================
       KPI
    ========================================================= */

    .vi-kpi-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 14px;
        margin-bottom: 18px;
    }

    .vi-kpi {
        background: #ffffff;
        border: 1px solid #e9edf3;
        border-radius: 14px;
        padding: 17px;
        box-shadow: 0 2px 8px rgba(15, 23, 42, .035);
        display: flex;
        align-items: center;
        justify-content: space-between;
        min-height: 94px;
    }

    .vi-kpi-info {
        min-width: 0;
    }

    .vi-kpi-label {
        font-size: 12px;
        color: #7b8495;
        margin-bottom: 5px;
    }

    .vi-kpi-value {
        font-size: 25px;
        line-height: 1;
        font-weight: 700;
        color: #172033;
    }

    .vi-kpi-icon {
        width: 40px;
        height: 40px;
        border-radius: 11px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        background: #f2f6ff;
        color: #2563eb;
    }

    .vi-kpi-icon.warning {
        background: #fff7ed;
        color: #ea580c;
    }

    .vi-kpi-icon.success {
        background: #ecfdf3;
        color: #16a34a;
    }

    .vi-kpi-icon.danger {
        background: #fef2f2;
        color: #dc2626;
    }


    /* =========================================================
       AI WORKSPACE
    ========================================================= */

    .vi-ai-workspace {
        background: #ffffff;
        border: 1px solid #e5eaf1;
        border-radius: 16px;
        box-shadow: 0 3px 12px rgba(15, 23, 42, .045);
        margin-bottom: 18px;
        overflow: hidden;
    }

    .vi-ai-top {
        padding: 18px 20px;
        border-bottom: 1px solid #edf0f4;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
    }

    .vi-ai-heading {
        display: flex;
        align-items: center;
        gap: 11px;
    }

    .vi-ai-heading-icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        background: #eff6ff;
        color: #2563eb;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
    }

    .vi-ai-heading h3 {
        margin: 0;
        font-size: 16px;
        color: #172033;
        font-weight: 700;
    }

    .vi-ai-heading p {
        margin: 2px 0 0;
        color: #8992a3;
        font-size: 12px;
    }

    .vi-ai-body {
        padding: 20px;
    }

    .vi-ai-input-wrapper {
        display: flex;
        align-items: center;
        gap: 10px;
        border: 1px solid #dfe5ee;
        background: #fbfcfe;
        border-radius: 11px;
        padding: 6px;
        transition: all .2s ease;
    }

    .vi-ai-input-wrapper:focus-within {
        border-color: #93b4f7;
        background: #ffffff;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, .07);
    }

    .vi-ai-input-icon {
        width: 38px;
        height: 38px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #98a2b3;
        flex-shrink: 0;
    }

    .vi-ai-input {
        border: 0 !important;
        outline: none !important;
        box-shadow: none !important;
        background: transparent !important;
        flex: 1;
        height: 42px;
        font-size: 13px;
        color: #344054;
        min-width: 0;
    }

    .vi-ai-input::placeholder {
        color: #a0a8b6;
    }

    .vi-ai-send {
        border: 0;
        background: #2563eb;
        color: #ffffff;
        height: 42px;
        padding: 0 17px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        cursor: pointer;
        transition: background .2s ease;
    }

    .vi-ai-send:hover {
        background: #1d4ed8;
    }

    .vi-ai-send:disabled {
        opacity: .6;
        cursor: not-allowed;
    }

    .vi-ai-suggestions {
        display: flex;
        flex-wrap: wrap;
        gap: 7px;
        margin-top: 12px;
    }

    .vi-ai-suggestion {
        border: 1px solid #e4e8ef;
        background: #ffffff;
        color: #667085;
        border-radius: 30px;
        padding: 7px 11px;
        font-size: 11px;
        cursor: pointer;
        transition: all .2s ease;
    }

    .vi-ai-suggestion:hover {
        border-color: #bfd2f7;
        color: #2563eb;
        background: #f8fbff;
    }


    /* =========================================================
       AI RESPONSE
    ========================================================= */

    .vi-ai-response {
        margin-top: 15px;
        display: none;
    }

    .vi-ai-response.show {
        display: block;
    }

    .vi-response-box {
        border: 1px solid #e4e9f0;
        border-radius: 12px;
        background: #fbfcfe;
        padding: 15px;
    }

    .vi-response-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 10px;
    }

    .vi-response-title {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #344054;
        font-size: 12px;
        font-weight: 700;
    }

    .vi-response-title i {
        color: #2563eb;
    }

    .vi-response-content {
        font-size: 13px;
        line-height: 1.7;
        color: #475467;
    }

    .vi-response-loading {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #667085;
        font-size: 12px;
    }

    .vi-spinner {
        width: 15px;
        height: 15px;
        border: 2px solid #dbe5f5;
        border-top-color: #2563eb;
        border-radius: 50%;
        animation: viSpin .7s linear infinite;
    }

    @keyframes viSpin {
        to {
            transform: rotate(360deg);
        }
    }


    /* =========================================================
       CONFIRMATION
    ========================================================= */

    .vi-confirm-card {
        margin-top: 12px;
        border: 1px solid #dbe5f5;
        background: #f8fbff;
        border-radius: 12px;
        padding: 14px;
    }

    .vi-confirm-title {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        font-weight: 700;
        color: #1e3a8a;
        margin-bottom: 10px;
    }

    .vi-confirm-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px;
    }

    .vi-confirm-item {
        background: #ffffff;
        border: 1px solid #e7edf5;
        border-radius: 8px;
        padding: 9px 10px;
    }

    .vi-confirm-label {
        font-size: 10px;
        color: #98a2b3;
        margin-bottom: 2px;
    }

    .vi-confirm-value {
        font-size: 12px;
        color: #344054;
        font-weight: 600;
        word-break: break-word;
    }

    .vi-confirm-actions {
        display: flex;
        justify-content: flex-end;
        gap: 7px;
        margin-top: 12px;
    }

    .vi-confirm-btn {
        border-radius: 8px;
        border: 1px solid #dfe4eb;
        background: #ffffff;
        color: #475467;
        padding: 8px 13px;
        font-size: 11px;
        font-weight: 600;
        cursor: pointer;
    }

    .vi-confirm-btn.primary {
        background: #2563eb;
        border-color: #2563eb;
        color: #ffffff;
    }


    /* =========================================================
       CONTENT GRID
    ========================================================= */

    .vi-content-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.4fr) minmax(320px, .8fr);
        gap: 18px;
    }

    .vi-card {
        background: #ffffff;
        border: 1px solid #e7ebf1;
        border-radius: 14px;
        box-shadow: 0 2px 8px rgba(15, 23, 42, .03);
        overflow: hidden;
    }

    .vi-card-header {
        padding: 15px 17px;
        border-bottom: 1px solid #edf0f4;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }

    .vi-card-title {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        font-weight: 700;
        color: #344054;
        margin: 0;
    }

    .vi-card-title i {
        color: #2563eb;
    }

    .vi-card-body {
        padding: 17px;
    }


    /* =========================================================
       HEALTH
    ========================================================= */

    .vi-health-item {
        margin-bottom: 16px;
    }

    .vi-health-item:last-child {
        margin-bottom: 0;
    }

    .vi-health-head {
        display: flex;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 6px;
        font-size: 11px;
    }

    .vi-health-label {
        color: #667085;
    }

    .vi-health-value {
        color: #344054;
        font-weight: 700;
    }

    .vi-progress {
        width: 100%;
        height: 7px;
        background: #edf1f6;
        border-radius: 20px;
        overflow: hidden;
    }

    .vi-progress-bar {
        height: 100%;
        width: 0;
        background: #2563eb;
        border-radius: 20px;
        transition: width .8s ease;
    }


    /* =========================================================
       CATEGORY
    ========================================================= */

    .vi-category-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .vi-category-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }

    .vi-category-left {
        display: flex;
        align-items: center;
        gap: 9px;
        min-width: 0;
    }

    .vi-category-icon {
        width: 30px;
        height: 30px;
        border-radius: 8px;
        background: #f2f6ff;
        color: #2563eb;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 13px;
    }

    .vi-category-name {
        font-size: 12px;
        color: #475467;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .vi-category-count {
        min-width: 26px;
        text-align: center;
        padding: 4px 7px;
        border-radius: 20px;
        background: #f2f4f7;
        color: #475467;
        font-size: 10px;
        font-weight: 700;
    }


    /* =========================================================
       MAINTENANCE
    ========================================================= */

    .vi-maint-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 8px;
        margin-bottom: 14px;
    }

    .vi-maint-stat {
        border: 1px solid #edf0f4;
        border-radius: 10px;
        padding: 11px;
        text-align: center;
    }

    .vi-maint-number {
        font-size: 20px;
        font-weight: 700;
        color: #172033;
    }

    .vi-maint-label {
        font-size: 10px;
        color: #98a2b3;
        margin-top: 2px;
    }

    .vi-maint-stat.warning .vi-maint-number {
        color: #ea580c;
    }

    .vi-maint-stat.danger .vi-maint-number {
        color: #dc2626;
    }

    .vi-maint-stat.success .vi-maint-number {
        color: #16a34a;
    }


    /* =========================================================
       UPCOMING TABLE
    ========================================================= */

    .vi-table {
        width: 100%;
        border-collapse: collapse;
    }

    .vi-table th {
        text-align: left;
        font-size: 10px;
        font-weight: 700;
        color: #98a2b3;
        padding: 9px 10px;
        border-bottom: 1px solid #edf0f4;
        white-space: nowrap;
    }

    .vi-table td {
        font-size: 11px;
        color: #475467;
        padding: 10px;
        border-bottom: 1px solid #f0f2f5;
        vertical-align: middle;
    }

    .vi-table tr:last-child td {
        border-bottom: 0;
    }

    .vi-asset-name {
        color: #344054;
        font-weight: 600;
    }

    .vi-code {
        font-size: 10px;
        color: #98a2b3;
        margin-top: 2px;
    }

    .vi-date {
        white-space: nowrap;
        color: #667085;
    }

    .vi-empty {
        padding: 28px 15px;
        text-align: center;
        color: #98a2b3;
        font-size: 12px;
    }


    /* =========================================================
       QUICK LINKS
    ========================================================= */

    .vi-quick-links {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 8px;
    }

    .vi-quick-link {
        border: 1px solid #e8ecf2;
        background: #ffffff;
        border-radius: 10px;
        padding: 11px;
        display: flex;
        align-items: center;
        gap: 9px;
        text-decoration: none;
        transition: all .2s ease;
    }

    .vi-quick-link:hover {
        border-color: #cbdaf7;
        background: #f8fbff;
    }

    .vi-quick-icon {
        width: 31px;
        height: 31px;
        border-radius: 8px;
        background: #f2f6ff;
        color: #2563eb;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        flex-shrink: 0;
    }

    .vi-quick-text {
        min-width: 0;
    }

    .vi-quick-title {
        font-size: 11px;
        color: #344054;
        font-weight: 700;
    }

    .vi-quick-desc {
        font-size: 9px;
        color: #98a2b3;
        margin-top: 1px;
    }


    /* =========================================================
       RESPONSIVE
    ========================================================= */

    @media (max-width: 1200px) {

        .vi-kpi-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .vi-content-grid {
            grid-template-columns: 1fr;
        }
    }


    @media (max-width: 768px) {

        .vi-page {
            padding: 15px;
        }

        .vi-header {
            align-items: flex-start;
            flex-direction: column;
        }

        .vi-header-actions {
            width: 100%;
        }

        .vi-header-actions .vi-btn {
            flex: 1;
            justify-content: center;
        }

        .vi-kpi-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 9px;
        }

        .vi-kpi {
            padding: 13px;
            min-height: 82px;
        }

        .vi-kpi-value {
            font-size: 21px;
        }

        .vi-kpi-icon {
            width: 34px;
            height: 34px;
            font-size: 15px;
        }

        .vi-ai-body {
            padding: 14px;
        }

        .vi-ai-top {
            padding: 14px;
        }

        .vi-ai-input-wrapper {
            align-items: stretch;
        }

        .vi-ai-input-icon {
            display: none;
        }

        .vi-ai-send {
            padding: 0 12px;
        }

        .vi-confirm-grid {
            grid-template-columns: 1fr;
        }

        .vi-maint-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }


    @media (max-width: 480px) {

        .vi-title {
            font-size: 20px;
        }

        .vi-subtitle {
            font-size: 11px;
        }

        .vi-kpi-grid {
            grid-template-columns: 1fr 1fr;
        }

        .vi-kpi {
            min-height: 75px;
        }

        .vi-kpi-label {
            font-size: 10px;
        }

        .vi-kpi-value {
            font-size: 19px;
        }

        .vi-ai-input-wrapper {
            flex-wrap: wrap;
        }

        .vi-ai-input {
            flex-basis: calc(100% - 45px);
        }

        .vi-ai-send {
            width: 100%;
            justify-content: center;
        }

        .vi-table-wrapper {
            overflow-x: auto;
        }
    }
</style>


<div class="vi-page">

    <div class="vi-container">


        {{-- =====================================================
            PAGE HEADER
        ====================================================== --}}

        <div class="vi-header">

            <div class="vi-header-left">

                <div class="vi-ai-icon">
                    <i class="bi bi-stars"></i>
                </div>

                <div>
                    <h1 class="vi-title">
                        Vasetra Intelligence
                    </h1>

                    <p class="vi-subtitle">
                        Workspace untuk memahami dan mengelola data aset perusahaan.
                    </p>
                </div>

            </div>


            <div class="vi-header-actions">

                <span class="vi-status">
                    <span class="vi-status-dot"></span>
                    AI Ready
                </span>

                <a
                    href="{{ route('dashboard.home') }}"
                    class="vi-btn"
                >
                    <i class="bi bi-grid"></i>
                    Dashboard
                </a>

            </div>

        </div>



        {{-- =====================================================
            KPI
        ====================================================== --}}

        <div class="vi-kpi-grid">

            <div class="vi-kpi">

                <div class="vi-kpi-info">

                    <div class="vi-kpi-label">
                        Total Asset
                    </div>

                    <div class="vi-kpi-value">
                        {{ number_format($totalAssets) }}
                    </div>

                </div>

                <div class="vi-kpi-icon">
                    <i class="bi bi-box-seam"></i>
                </div>

            </div>


            <div class="vi-kpi">

                <div class="vi-kpi-info">

                    <div class="vi-kpi-label">
                        Asset Aktif
                    </div>

                    <div class="vi-kpi-value">
                        {{ number_format($activeAssets) }}
                    </div>

                </div>

                <div class="vi-kpi-icon success">
                    <i class="bi bi-check-circle"></i>
                </div>

            </div>


            <div class="vi-kpi">

                <div class="vi-kpi-info">

                    <div class="vi-kpi-label">
                        Asset Saya
                    </div>

                    <div class="vi-kpi-value">
                        {{ number_format($myAssets) }}
                    </div>

                </div>

                <div class="vi-kpi-icon">
                    <i class="bi bi-person-badge"></i>
                </div>

            </div>


            <div class="vi-kpi">

                <div class="vi-kpi-info">

                    <div class="vi-kpi-label">
                        Maintenance Due
                    </div>

                    <div class="vi-kpi-value">
                        {{ number_format($maintenanceDue) }}
                    </div>

                </div>

                <div class="vi-kpi-icon warning">
                    <i class="bi bi-tools"></i>
                </div>

            </div>

        </div>



        {{-- =====================================================
            AI WORKSPACE
        ====================================================== --}}

        <div class="vi-ai-workspace">

            <div class="vi-ai-top">

                <div class="vi-ai-heading">

                    <div class="vi-ai-heading-icon">
                        <i class="bi bi-stars"></i>
                    </div>

                    <div>

                        <h3>
                            Ask Vasetra AI
                        </h3>

                        <p>
                            Tanyakan data atau minta bantuan melakukan tindakan.
                        </p>

                    </div>

                </div>

                <span class="vi-status">
                    <span class="vi-status-dot"></span>
                    Online
                </span>

            </div>


            <div class="vi-ai-body">

                <form id="vasetraAiForm">

                    <div class="vi-ai-input-wrapper">

                        <div class="vi-ai-input-icon">
                            <i class="bi bi-chat-dots"></i>
                        </div>

                        <input
                            type="text"
                            id="vasetraQuestion"
                            class="vi-ai-input"
                            autocomplete="off"
                            placeholder="Contoh: Berapa total asset perusahaan saya?"
                        >

                        <button
                            type="submit"
                            class="vi-ai-send"
                            id="vasetraAiButton"
                        >
                            <i class="bi bi-send"></i>
                            Tanya AI
                        </button>

                    </div>

                </form>


                {{-- Suggestions --}}

                <div class="vi-ai-suggestions">

                    <button
                        type="button"
                        class="vi-ai-suggestion"
                        data-question="Berapa total asset perusahaan saya?"
                    >
                        Total asset
                    </button>

                    <button
                        type="button"
                        class="vi-ai-suggestion"
                        data-question="Berapa asset saya?"
                    >
                        Asset saya
                    </button>

                    <button
                        type="button"
                        class="vi-ai-suggestion"
                        data-question="Berapa asset yang belum memiliki PIC?"
                    >
                        Asset tanpa PIC
                    </button>

                    <button
                        type="button"
                        class="vi-ai-suggestion"
                        data-question="Apakah ada maintenance yang terlambat?"
                    >
                        Maintenance terlambat
                    </button>

                    <button
                        type="button"
                        class="vi-ai-suggestion"
                        data-question="Tampilkan maintenance minggu ini."
                    >
                        Maintenance minggu ini
                    </button>

                    <button
                        type="button"
                        class="vi-ai-suggestion"
                        data-question="Tambahkan asset laptop Dell Latitude 5420, kategori Laptop, subkategori Notebook, vendor Dell, harga 12 juta"
                    >
                        + Tambah Asset
                    </button>

                </div>


                {{-- Response --}}

                <div
                    id="vasetraAiResponse"
                    class="vi-ai-response"
                ></div>

            </div>

        </div>



        {{-- =====================================================
            MAIN CONTENT
        ====================================================== --}}

        <div class="vi-content-grid">


            {{-- =================================================
                LEFT
            ================================================== --}}

            <div>


                {{-- Asset Health --}}

                <div class="vi-card mb-3">

                    <div class="vi-card-header">

                        <h3 class="vi-card-title">
                            <i class="bi bi-heart-pulse"></i>
                            Asset Health
                        </h3>

                    </div>


                    <div class="vi-card-body">

                        @php

                            $conditionLabels = [
                                'new' => 'Baru',
                                'good' => 'Baik',
                                'fair' => 'Cukup',
                                'damaged' => 'Rusak',
                                'lost' => 'Hilang',
                            ];

                            $conditionTotal = max(
                                1,
                                $assetConditions->sum()
                            );

                        @endphp


                        @forelse($assetConditions as $condition => $total)

                            @php

                                $percentage = round(
                                    ($total / $conditionTotal) * 100
                                );

                            @endphp

                            <div class="vi-health-item">

                                <div class="vi-health-head">

                                    <span class="vi-health-label">
                                        {{ $conditionLabels[$condition] ?? ucfirst($condition) }}
                                    </span>

                                    <span class="vi-health-value">
                                        {{ $total }}
                                        ({{ $percentage }}%)
                                    </span>

                                </div>

                                <div class="vi-progress">

                                    <div
                                        class="vi-progress-bar"
                                        data-width="{{ $percentage }}"
                                    ></div>

                                </div>

                            </div>

                        @empty

                            <div class="vi-empty">
                                Belum ada data kondisi asset.
                            </div>

                        @endforelse

                    </div>

                </div>



                {{-- Asset Categories --}}

                <div class="vi-card">

                    <div class="vi-card-header">

                        <h3 class="vi-card-title">
                            <i class="bi bi-diagram-3"></i>
                            Distribusi Kategori
                        </h3>

                    </div>


                    <div class="vi-card-body">

                        @if($assetCategories->count())

                            <div class="vi-category-list">

                                @foreach($assetCategories as $item)

                                    <div class="vi-category-item">

                                        <div class="vi-category-left">

                                            <div class="vi-category-icon">
                                                <i class="bi bi-box"></i>
                                            </div>

                                            <div class="vi-category-name">

                                                {{ $item->category?->category_name ?? 'Tanpa kategori' }}

                                            </div>

                                        </div>


                                        <div class="vi-category-count">
                                            {{ $item->total }}
                                        </div>

                                    </div>

                                @endforeach

                            </div>

                        @else

                            <div class="vi-empty">
                                Belum ada data kategori.
                            </div>

                        @endif

                    </div>

                </div>

            </div>



            {{-- =================================================
                RIGHT
            ================================================== --}}

            <div>


                {{-- Maintenance Monitor --}}

                <div class="vi-card mb-3">

                    <div class="vi-card-header">

                        <h3 class="vi-card-title">
                            <i class="bi bi-calendar-check"></i>
                            Maintenance Monitor
                        </h3>

                    </div>


                    <div class="vi-card-body">

                        <div class="vi-maint-grid">

                            <div class="vi-maint-stat danger">

                                <div class="vi-maint-number">
                                    {{ $maintenanceOverdue }}
                                </div>

                                <div class="vi-maint-label">
                                    Terlambat
                                </div>

                            </div>


                            <div class="vi-maint-stat warning">

                                <div class="vi-maint-number">
                                    {{ $maintenanceToday }}
                                </div>

                                <div class="vi-maint-label">
                                    Hari Ini
                                </div>

                            </div>


                            <div class="vi-maint-stat success">

                                <div class="vi-maint-number">
                                    {{ $maintenanceThisWeek }}
                                </div>

                                <div class="vi-maint-label">
                                    Minggu Ini
                                </div>

                            </div>

                        </div>

                        <div class="vi-health-head">

                            <span class="vi-health-label">
                                Asset tanpa PIC
                            </span>

                            <span class="vi-health-value">
                                {{ $unassignedAssets }}
                            </span>

                        </div>

                    </div>

                </div>



                {{-- =================================================
    Quick Access
================================================== --}}

<div class="vi-card">

    <div class="vi-card-header">

        <h3 class="vi-card-title">
            <i class="bi bi-lightning"></i>
            Quick Access
        </h3>

    </div>


    <div class="vi-card-body">

        <div class="vi-quick-links">

            <a
                href="{{ route('assets.index') }}"
                class="vi-quick-link"
            >

                <div class="vi-quick-icon">
                    <i class="bi bi-box-seam"></i>
                </div>

                <div class="vi-quick-text">

                    <div class="vi-quick-title">
                        Assets
                    </div>

                    <div class="vi-quick-desc">
                        Kelola asset
                    </div>

                </div>

            </a>


            <a
                href="{{ route('maintenance.index') }}"
                class="vi-quick-link"
            >

                <div class="vi-quick-icon">
                    <i class="bi bi-tools"></i>
                </div>

                <div class="vi-quick-text">

                    <div class="vi-quick-title">
                        Maintenance
                    </div>

                    <div class="vi-quick-desc">
                        Jadwal maintenance
                    </div>

                </div>

            </a>

        </div>

    </div>

</div>

            </div>

        </div>



        {{-- =====================================================
            UPCOMING MAINTENANCE
        ====================================================== --}}

        <div class="vi-card mt-3">

            <div class="vi-card-header">

                <h3 class="vi-card-title">
                    <i class="bi bi-calendar3"></i>
                    Upcoming Maintenance
                </h3>

                <span
                    style="font-size:10px;color:#98a2b3;"
                >
                    5 jadwal berikutnya
                </span>

            </div>


            <div class="vi-table-wrapper">

                @if($upcomingMaintenance->count())

                    <table class="vi-table">

                        <thead>

                            <tr>

                                <th>
                                    Asset
                                </th>

                                <th>
                                    Category
                                </th>

                                <th>
                                    PIC
                                </th>

                                <th>
                                    Jadwal
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            @foreach($upcomingMaintenance as $maintenance)

                                <tr>

                                    <td>

                                        <div class="vi-asset-name">

                                            {{ $maintenance->asset?->asset_name ?? '-' }}

                                        </div>

                                        <div class="vi-code">

                                            {{ $maintenance->asset?->asset_code ?? '-' }}

                                        </div>

                                    </td>


                                    <td>

                                        {{ $maintenance->asset?->category?->category_name ?? '-' }}

                                    </td>


                                    <td>

                                        {{ $maintenance->asset?->responsibleUser?->name ?? 'Belum ada PIC' }}

                                    </td>


                                    <td>

                                        <span class="vi-date">

                                            {{ optional($maintenance->maintenance_date)->format('d M Y') }}

                                        </span>

                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                @else

                    <div class="vi-empty">
                        Tidak ada maintenance terjadwal.
                    </div>

                @endif

            </div>

        </div>


    </div>

</div>



<script>

document.addEventListener('DOMContentLoaded', function () {


    /* =========================================================
       PROGRESS BAR
    ========================================================= */

    document
        .querySelectorAll('.vi-progress-bar')
        .forEach(function (bar) {

            const width = bar.dataset.width || 0;

            setTimeout(function () {

                bar.style.width = width + '%';

            }, 100);

        });



    /* =========================================================
       AI ELEMENTS
    ========================================================= */

    const form =
        document.getElementById('vasetraAiForm');

    const input =
        document.getElementById('vasetraQuestion');

    const button =
        document.getElementById('vasetraAiButton');

    const response =
        document.getElementById('vasetraAiResponse');


    if (!form || !input || !button || !response) {
        return;
    }


    const askUrl =
        @json(route('dashboard.intelligence.ask'));


    const confirmUrl =
        @json(route('dashboard.intelligence.confirm'));


    const csrf =
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content');


    /* =========================================================
       ESCAPE HTML
    ========================================================= */

    function escapeHtml(value) {

        if (value === null || value === undefined) {
            return '';
        }

        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }


    /* =========================================================
       FORMAT PRICE
    ========================================================= */

    function formatPrice(value) {

        if (
            value === null ||
            value === undefined ||
            value === ''
        ) {
            return '-';
        }

        const number =
            Number(value);

        if (Number.isNaN(number)) {
            return escapeHtml(value);
        }

        return new Intl.NumberFormat(
            'id-ID',
            {
                style: 'currency',
                currency: 'IDR',
                maximumFractionDigits: 0
            }
        ).format(number);

    }


    /* =========================================================
       CONFIRMATION
    ========================================================= */

    function renderConfirmation(data) {

        const payload =
            data.data || {};

        const fields = [
            ['Asset', payload.asset_name],
            ['Category', payload.category_name || payload.category],
            ['Subcategory', payload.subcategory_name || payload.subcategory],
            ['Vendor', payload.vendor_name || payload.vendor],
            ['Harga', formatPrice(payload.purchase_price)]
        ];


        let html = `
            <div class="vi-confirm-card">

                <div class="vi-confirm-title">

                    <i class="bi bi-shield-check"></i>

                    Konfirmasi Tambah Asset

                </div>

                <div class="vi-confirm-grid">
        `;


        fields.forEach(function (field) {

            html += `
                <div class="vi-confirm-item">

                    <div class="vi-confirm-label">
                        ${escapeHtml(field[0])}
                    </div>

                    <div class="vi-confirm-value">
                        ${escapeHtml(field[1] ?? '-')}
                    </div>

                </div>
            `;

        });


        html += `
                </div>

                <div class="vi-confirm-actions">

                    <button
                        type="button"
                        class="vi-confirm-btn"
                        id="viCancelConfirm"
                    >
                        Batal
                    </button>

                    <button
                        type="button"
                        class="vi-confirm-btn primary"
                        id="viConfirmAction"
                        data-token="${escapeHtml(data.token || '')}"
                    >
                        <i class="bi bi-check2"></i>
                        Konfirmasi & Tambahkan
                    </button>

                </div>

            </div>
        `;


        return html;
    }


    /* =========================================================
       ASK AI
    ========================================================= */

    async function askAI(question) {

        question =
            String(question || '').trim();


        if (!question) {
            return;
        }


        button.disabled = true;

        response.classList.add('show');


        response.innerHTML = `
            <div class="vi-response-box">

                <div class="vi-response-head">

                    <div class="vi-response-title">

                        <i class="bi bi-stars"></i>

                        Vasetra AI

                    </div>

                </div>

                <div class="vi-response-loading">

                    <span class="vi-spinner"></span>

                    Sedang memproses pertanyaan...

                </div>

            </div>
        `;


        try {

            const result =
                await fetch(
                    askUrl,
                    {
                        method: 'POST',

                        headers: {
                            'Content-Type':
                                'application/json',

                            'Accept':
                                'application/json',

                            'X-CSRF-TOKEN':
                                csrf,

                            'X-Requested-With':
                                'XMLHttpRequest'
                        },

                        body:
                            JSON.stringify({
                                question: question
                            })
                    }
                );


            const data =
                await result.json();


            if (!result.ok || data.success === false) {

                throw new Error(
                    data.message ||
                    'Terjadi kesalahan saat memproses AI.'
                );

            }


            /*
             * Backend Vasetra menggunakan:
             *
             * data.message
             *
             * bukan:
             *
             * data.answer
             */

            const aiMessage =
                data.message ||
                data.answer ||
                data.data?.message ||
                'Tidak ada jawaban.';


            let html = `
                <div class="vi-response-box">

                    <div class="vi-response-head">

                        <div class="vi-response-title">

                            <i class="bi bi-stars"></i>

                            Vasetra AI

                        </div>

                    </div>

                    <div class="vi-response-content">

                        ${escapeHtml(aiMessage)}

                    </div>
            `;


            /* =================================================
               CONFIRMATION REQUIRED
            ================================================== */

            if (
                data.type ===
                'confirmation_required'
            ) {

                html +=
                    renderConfirmation(data);

            }


            html += `
                </div>
            `;


            response.innerHTML =
                html;


            /* =================================================
               CONFIRM
            ================================================== */

            const confirmButton =
                document.getElementById(
                    'viConfirmAction'
                );


            if (confirmButton) {

                confirmButton.addEventListener(
                    'click',
                    function () {

                        confirmAction(
                            confirmButton.dataset.token
                        );

                    }
                );

            }


            /* =================================================
               CANCEL
            ================================================== */

            const cancelButton =
                document.getElementById(
                    'viCancelConfirm'
                );


            if (cancelButton) {

                cancelButton.addEventListener(
                    'click',
                    function () {

                        response.innerHTML = `
                            <div class="vi-response-box">

                                <div class="vi-response-content">

                                    Tindakan dibatalkan.

                                </div>

                            </div>
                        `;

                    }
                );

            }

        } catch (error) {

            response.innerHTML = `
                <div class="vi-response-box">

                    <div class="vi-response-head">

                        <div class="vi-response-title">

                            <i
                                class="bi bi-exclamation-circle"
                                style="color:#dc2626;"
                            ></i>

                            Vasetra AI

                        </div>

                    </div>

                    <div class="vi-response-content">

                        ${escapeHtml(
                            error.message ||
                            'Terjadi kesalahan.'
                        )}

                    </div>

                </div>
            `;

        } finally {

            button.disabled = false;

        }

    }


    /* =========================================================
       CONFIRM ACTION
    ========================================================= */

    async function confirmAction(token) {

        if (!token) {

            response.innerHTML = `
                <div class="vi-response-box">

                    <div class="vi-response-content">

                        Token konfirmasi tidak ditemukan.

                    </div>

                </div>
            `;

            return;
        }


        const confirmButton =
            document.getElementById(
                'viConfirmAction'
            );


        if (confirmButton) {

            confirmButton.disabled = true;

            confirmButton.innerHTML = `
                <span class="vi-spinner"></span>
                Memproses...
            `;

        }


        try {

            const result =
                await fetch(
                    confirmUrl,
                    {
                        method: 'POST',

                        headers: {
                            'Content-Type':
                                'application/json',

                            'Accept':
                                'application/json',

                            'X-CSRF-TOKEN':
                                csrf,

                            'X-Requested-With':
                                'XMLHttpRequest'
                        },

                        body:
                            JSON.stringify({
                                token: token
                            })
                    }
                );


            const data =
                await result.json();


            if (
                !result.ok ||
                data.success === false
            ) {

                throw new Error(
                    data.message ||
                    'Tindakan gagal.'
                );

            }


            const asset =
                data.data || {};


            response.innerHTML = `
                <div class="vi-response-box">

                    <div class="vi-response-head">

                        <div class="vi-response-title">

                            <i
                                class="bi bi-check-circle"
                                style="color:#16a34a;"
                            ></i>

                            Berhasil

                        </div>

                    </div>

                    <div class="vi-response-content">

                        ${escapeHtml(
                            data.message ||
                            'Asset berhasil ditambahkan.'
                        )}

                        <br><br>

                        <strong>
                            Asset Code:
                        </strong>

                        ${escapeHtml(
                            asset.asset_code || '-'
                        )}

                    </div>

                </div>
            `;


        } catch (error) {

            response.innerHTML = `
                <div class="vi-response-box">

                    <div class="vi-response-content">

                        ${escapeHtml(
                            error.message ||
                            'Gagal menjalankan tindakan.'
                        )}

                    </div>

                </div>
            `;

        }

    }


    /* =========================================================
       FORM SUBMIT
    ========================================================= */

    form.addEventListener(
        'submit',
        function (event) {

            event.preventDefault();

            askAI(input.value);

        }
    );


    /* =========================================================
       SUGGESTIONS
    ========================================================= */

    document
        .querySelectorAll('.vi-ai-suggestion')
        .forEach(function (suggestion) {

            suggestion.addEventListener(
                'click',
                function () {

                    const question =
                        suggestion.dataset.question || '';

                    input.value =
                        question;

                    input.focus();

                }
            );

        });


});

</script>

@endsection