@extends('dashboard.layouts.wrapper')

@section('title', 'Subscription History')

@section('content')

<style>
    .subscription-page {
        --sub-primary: #3157d5;
        --sub-primary-soft: rgba(49, 87, 213, .09);
        --sub-border: #e8edf5;
        --sub-muted: #778198;
    }

    .subscription-page .page-header-card,
    .subscription-page .summary-card,
    .subscription-page .history-card {
        border: 1px solid var(--sub-border);
        border-radius: 16px;
        box-shadow: 0 4px 18px rgba(28, 39, 70, .035);
        overflow: hidden;
    }

    .subscription-page .page-header-card .card-body {
        padding: 24px;
    }

    .subscription-page .page-title {
        font-size: 1.2rem;
        font-weight: 750;
        letter-spacing: -.35px;
        margin-bottom: 6px;
        color: #26334d;
    }

    .subscription-page .page-subtitle {
        color: var(--sub-muted);
        font-size: .875rem;
    }

    .subscription-page .subscribe-btn {
        border-radius: 10px;
        padding: 10px 17px;
        font-weight: 600;
        box-shadow: 0 5px 12px rgba(49, 87, 213, .14);
    }

    .subscription-page .summary-card {
        height: 100%;
        transition: transform .2s ease, box-shadow .2s ease;
    }

    .subscription-page .summary-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(28, 39, 70, .07);
    }

    .subscription-page .summary-card .card-body {
        padding: 22px;
    }

    .subscription-page .summary-icon {
        width: 50px;
        height: 50px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 22px;
    }

    .subscription-page .icon-blue {
        color: #3157d5;
        background: rgba(49, 87, 213, .10);
    }

    .subscription-page .icon-purple {
        color: #805ad5;
        background: rgba(128, 90, 213, .11);
    }

    .subscription-page .icon-orange {
        color: #d97706;
        background: rgba(245, 158, 11, .13);
    }

    .subscription-page .summary-label {
        color: var(--sub-muted);
        font-size: .8rem;
        font-weight: 600;
        margin-bottom: 6px;
    }

    .subscription-page .summary-value {
        color: #26334d;
        font-size: 1.35rem;
        line-height: 1.3;
        font-weight: 750;
        margin: 0;
        overflow-wrap: anywhere;
    }

    .subscription-page .summary-note {
        color: var(--sub-muted);
        font-size: .75rem;
        margin-top: 5px;
    }

    .subscription-page .history-card .card-header {
        padding: 22px 24px;
        background: #fff;
        border-bottom: 1px solid var(--sub-border);
    }

    .subscription-page .history-card .card-body {
        padding: 22px 24px 24px;
    }

    .subscription-page .section-title {
        color: #26334d;
        font-size: 1rem;
        font-weight: 750;
        margin-bottom: 5px;
    }

    .subscription-page .section-subtitle {
        color: var(--sub-muted);
        font-size: .82rem;
        margin: 0;
    }

    .subscription-page .table-responsive {
        border: 1px solid var(--sub-border);
        border-radius: 12px;
    }

    .subscription-page #subscriptionHistoryTable {
        margin-bottom: 0 !important;
        min-width: 1250px;
        border-collapse: separate !important;
        border-spacing: 0;
    }

    .subscription-page #subscriptionHistoryTable thead th {
        background: #f7f9fd;
        color: #59657c;
        font-size: .74rem;
        font-weight: 750;
        text-transform: uppercase;
        letter-spacing: .35px;
        white-space: nowrap;
        padding: 15px 14px;
        border-bottom: 1px solid var(--sub-border);
        vertical-align: middle;
    }

    .subscription-page #subscriptionHistoryTable tbody td {
        color: #46516a;
        font-size: .84rem;
        padding: 15px 14px;
        vertical-align: middle;
        border-bottom: 1px solid #edf0f6;
    }

    .subscription-page #subscriptionHistoryTable tbody tr:last-child td {
        border-bottom: 0;
    }

    .subscription-page #subscriptionHistoryTable tbody tr:hover {
        background: #f9fbff;
    }

    .subscription-page .invoice-code {
        color: var(--sub-primary);
        font-weight: 750;
        white-space: nowrap;
    }

    .subscription-page .money-value {
        color: #26334d;
        font-weight: 650;
        white-space: nowrap;
    }

    .subscription-page .date-value {
        white-space: nowrap;
    }

    .subscription-page .storage-value {
        min-width: 90px;
        line-height: 1.5;
    }

    .subscription-page .storage-value small {
        color: var(--sub-muted);
        font-size: .74rem;
    }

    .subscription-page .status-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
        border-radius: 7px;
        padding: 6px 9px;
        font-size: .72rem;
        font-weight: 700;
        white-space: nowrap;
        border: 1px solid transparent;
    }

    .subscription-page .status-active,
    .subscription-page .status-paid,
    .subscription-page .status-settlement,
    .subscription-page .status-success {
        color: #16804a;
        background: #eaf8f0;
        border-color: #d4f0df;
    }

    .subscription-page .status-pending,
    .subscription-page .status-unpaid {
        color: #996b00;
        background: #fff6d9;
        border-color: #f6e8ae;
    }

    .subscription-page .status-expired,
    .subscription-page .status-unknown {
        color: #667085;
        background: #f0f2f5;
        border-color: #e4e7ec;
    }

    .subscription-page .status-cancelled,
    .subscription-page .status-failed,
    .subscription-page .status-failure,
    .subscription-page .status-denied {
        color: #c13232;
        background: #fff0f0;
        border-color: #ffdcdc;
    }

    .subscription-page .status-inactive {
        color: #59657c;
        background: #eef2f7;
        border-color: #e0e6ef;
    }

    .subscription-page .btn-print-billing {
        border-radius: 8px;
        padding: 7px 10px;
        font-size: .77rem;
        font-weight: 650;
        white-space: nowrap;
    }

    /* Search dan jumlah data */
    .subscription-page .dataTables_wrapper .dataTables_length,
    .subscription-page .dataTables_wrapper .dataTables_filter {
        margin-bottom: 18px;
        color: #667085;
        font-size: .83rem;
    }

    .subscription-page .dataTables_wrapper .dataTables_length select,
    .subscription-page .dataTables_wrapper .dataTables_filter input {
        border: 1px solid #dfe5ef;
        border-radius: 9px;
        padding: 8px 11px;
        color: #344054;
        background: #fff;
        outline: none;
        box-shadow: none;
    }

    .subscription-page .dataTables_wrapper .dataTables_length select {
        margin: 0 6px;
        min-width: 68px;
    }

    .subscription-page .dataTables_wrapper .dataTables_filter input {
        margin-left: 8px;
        min-width: 190px;
    }

    .subscription-page .dataTables_wrapper .dataTables_filter input:focus,
    .subscription-page .dataTables_wrapper .dataTables_length select:focus {
        border-color: #91a8f5;
        box-shadow: 0 0 0 3px rgba(49, 87, 213, .08);
    }

    .subscription-page .dataTables_wrapper .dataTables_info {
        color: #778198;
        font-size: .82rem;
        padding-top: 17px;
    }

    /* Pagination: Sebelumnya, nomor halaman, Berikutnya */
    .subscription-page .dataTables_wrapper .dataTables_paginate {
        padding-top: 12px;
        display: flex;
        justify-content: flex-end;
        align-items: center;
        flex-wrap: wrap;
        gap: 5px;
    }

    .subscription-page .dataTables_wrapper .dataTables_paginate .paginate_button {
        min-width: 36px;
        height: 36px;
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
        padding: 0 11px !important;
        margin: 0 !important;
        border: 1px solid #e2e7f0 !important;
        border-radius: 9px !important;
        color: #59657c !important;
        background: #fff !important;
        font-size: .82rem;
        font-weight: 650;
        transition: all .15s ease;
    }

    .subscription-page .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
        border-color: #cbd6fb !important;
        color: var(--sub-primary) !important;
        background: #f4f6ff !important;
    }

    .subscription-page .dataTables_wrapper .dataTables_paginate .paginate_button.current,
    .subscription-page .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
        border-color: var(--sub-primary) !important;
        color: #fff !important;
        background: var(--sub-primary) !important;
        box-shadow: 0 4px 10px rgba(49, 87, 213, .18);
    }

    .subscription-page .dataTables_wrapper .dataTables_paginate .paginate_button.disabled,
    .subscription-page .dataTables_wrapper .dataTables_paginate .paginate_button.disabled:hover {
        color: #b8c0ce !important;
        background: #f8f9fb !important;
        border-color: #edf0f5 !important;
        cursor: not-allowed !important;
    }

    .subscription-page .dataTables_wrapper .dataTables_processing {
        border: 1px solid var(--sub-border);
        border-radius: 10px;
        background: rgba(255, 255, 255, .96);
        color: #344054;
        box-shadow: 0 5px 18px rgba(28, 39, 70, .08);
    }

    .subscription-page .dataTables_wrapper .dataTables_empty {
        padding: 45px 15px !important;
        color: #778198 !important;
        text-align: center;
    }

    .subscription-page .dataTables_wrapper .row {
        row-gap: 10px;
    }

    @media (max-width: 767.98px) {
        .subscription-page .page-header-card .card-body {
            padding: 20px;
        }

        .subscription-page .subscription-header {
            align-items: flex-start;
            flex-direction: column;
            gap: 16px;
        }

        .subscription-page .subscription-header-action,
        .subscription-page .subscribe-btn {
            width: 100%;
        }

        .subscription-page .summary-card .card-body {
            padding: 18px;
        }

        .subscription-page .history-card .card-header,
        .subscription-page .history-card .card-body {
            padding: 17px;
        }

        .subscription-page .dataTables_wrapper .dataTables_filter,
        .subscription-page .dataTables_wrapper .dataTables_filter label {
            width: 100%;
            text-align: left;
        }

        .subscription-page .dataTables_wrapper .dataTables_filter input {
            display: block;
            width: 100%;
            margin: 8px 0 0;
        }

        .subscription-page .dataTables_wrapper .dataTables_paginate {
            justify-content: flex-start;
        }
    }
</style>

<div class="content-wrapper subscription-page">
    <div class="container-xxl flex-grow-1 container-p-y">

        {{-- PAGE HEADER --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="card page-header-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between subscription-header">
                            <div>
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="summary-icon icon-blue"
                                          style="width:42px;height:42px;border-radius:12px;">
                                        <i class="bi bi-receipt"></i>
                                    </span>
                                    <h5 class="page-title mb-0">Subscription History</h5>
                                </div>

                                <p class="page-subtitle mb-0">
                                    Riwayat paket, pembayaran, dan billing
                                    {{ $company->company_name ?? 'perusahaan' }}.
                                </p>
                            </div>

                            <div class="subscription-header-action">
                                <a href="{{ route('subscription.index') }}"
                                   class="btn btn-primary subscribe-btn">
                                    <i class="bi bi-plus-circle me-1"></i>
                                    Subscribe / Perpanjang
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- SUMMARY CARDS --}}
        <div class="row g-3 mb-4">

            <div class="col-12 col-md-4">
                <div class="card summary-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3">
                            <div class="summary-icon icon-blue">
                                <i class="bi bi-clock-history"></i>
                            </div>
                            <div>
                                <div class="summary-label">Total Subscription</div>
                                <h4 class="summary-value">
                                    {{ number_format((int) ($totalSubscriptions ?? 0), 0, ',', '.') }}
                                </h4>
                                <div class="summary-note">Jumlah riwayat paket perusahaan</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-4">
                <div class="card summary-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3">
                            <div class="summary-icon icon-purple">
                                <i class="bi bi-stars"></i>
                            </div>
                            <div>
                                <div class="summary-label">Current Plan</div>
                                <h4 class="summary-value">
                                    {{ $activeSubscription?->plan?->plan_name ?? '-' }}
                                </h4>
                                <div class="summary-note">Paket subscription saat ini</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-4">
                <div class="card summary-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3">
                            <div class="summary-icon icon-orange">
                                <i class="bi bi-calendar2-check"></i>
                            </div>
                            <div>
                                <div class="summary-label">Current Expiration</div>
                                <h4 class="summary-value">
                                    @if($activeSubscription?->end_date)
                                        {{ \Carbon\Carbon::parse($activeSubscription->end_date)->format('d M Y') }}
                                    @else
                                        -
                                    @endif
                                </h4>
                                <div class="summary-note">Tanggal akhir paket aktif</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- HISTORY TABLE --}}
        <div class="row">
            <div class="col-12">
                <div class="card history-card">

                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <h5 class="section-title">
                                    <i class="bi bi-list-ul me-1"></i>
                                    Riwayat Billing
                                </h5>
                                <p class="section-subtitle">
                                    Rincian harga paket, tambahan storage, diskon, dan status pembayaran.
                                </p>
                            </div>

                            <span class="badge rounded-pill text-bg-light border px-3 py-2">
                                <i class="bi bi-shield-check me-1"></i>
                                Data perusahaan
                            </span>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="table-responsive">
                            <table
                                id="subscriptionHistoryTable"
                                data-url="{{ route('subscription.history') }}"
                                class="table table-hover align-middle subscription-table w-100">

                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Invoice</th>
                                        <th>Plan</th>
                                        <th>Billing</th>
                                        <th>Package Price</th>
                                        <th>Storage</th>
                                        <th>Diskon</th>
                                        <th>Total</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Subscription</th>
                                        <th>Payment</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>

                                <tbody></tbody>
                            </table>
                        </div>

                        <div class="small text-muted mt-3">
                            <i class="bi bi-info-circle me-1"></i>
                            Pastikan status pembayaran sudah berhasil sebelum menggunakan billing sebagai dokumen klaim kantor.
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>
</div>

@endsection

@section('js')
<script>
$(function () {
    const tableSelector = '#subscriptionHistoryTable';

    if (typeof $.fn.DataTable === 'undefined') {
        console.error('DataTables belum dimuat. Periksa layout dashboard.');
        return;
    }

    if ($.fn.DataTable.isDataTable(tableSelector)) {
        return;
    }

    $(tableSelector).DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        autoWidth: false,

        ajax: {
            url: $(tableSelector).data('url'),
            type: 'GET',
            error: function (xhr) {
                console.error(
                    'Gagal mengambil subscription history:',
                    xhr.status,
                    xhr.responseText
                );
            }
        },

        columns: [
            {
                data: 'DT_RowIndex',
                name: 'DT_RowIndex',
                orderable: false,
                searchable: false
            },
            {
                data: 'invoice',
                name: 'invoice',
                orderable: false,
                searchable: false,
                defaultContent: '-'
            },
            {
                data: 'plan',
                name: 'plan',
                orderable: false,
                searchable: false,
                defaultContent: '-'
            },
            {
                data: 'billing_cycle',
                name: 'billing_cycle',
                orderable: false,
                searchable: false,
                defaultContent: '-'
            },
            {
                data: 'package_price',
                name: 'package_price',
                orderable: false,
                searchable: false,
                defaultContent: '-'
            },
            {
                data: 'storage',
                name: 'storage',
                orderable: false,
                searchable: false,
                defaultContent: '-'
            },
            {
                data: 'discount',
                name: 'discount',
                orderable: false,
                searchable: false,
                defaultContent: '-'
            },
            {
                data: 'total_price',
                name: 'total_price',
                orderable: false,
                searchable: false,
                defaultContent: '-'
            },
            {
                data: 'start_date',
                name: 'start_date',
                orderable: true,
                searchable: false,
                defaultContent: '-'
            },
            {
                data: 'end_date',
                name: 'end_date',
                orderable: false,
                searchable: false,
                defaultContent: '-'
            },
            {
                data: 'status',
                name: 'status',
                orderable: false,
                searchable: false,
                defaultContent: '-'
            },
            {
                data: 'payment_status',
                name: 'payment_status',
                orderable: false,
                searchable: false,
                defaultContent: '-'
            },
            {
                data: 'action',
                name: 'action',
                orderable: false,
                searchable: false,
                defaultContent: '-'
            }
        ],

        order: [[8, 'desc']],

    });
});
</script>
@endsection
