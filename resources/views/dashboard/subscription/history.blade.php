@extends('dashboard.layouts.wrapper')

@section('title', 'Subscription History')

@section('content')

<style>

    .subscription-summary-card {
        min-height: 120px;
    }

    .subscription-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(0, 0, 0, 0.05);
        flex-shrink: 0;
    }

    .subscription-icon i {
        font-size: 22px;
    }

    .subscription-table th {
        white-space: nowrap;
        vertical-align: middle;
    }

    .subscription-table td {
        vertical-align: middle;
    }

    .subscription-plan {
        font-weight: 600;
    }

    .subscription-price {
        white-space: nowrap;
    }

    .subscription-date {
        white-space: nowrap;
    }

    .status-badge {
        font-size: 12px;
        padding: 6px 10px;
        border-radius: 6px;
        font-weight: 600;
    }

    .status-active {
        background: rgba(40, 167, 69, 0.12);
        color: #198754;
    }

    .status-pending {
        background: rgba(255, 193, 7, 0.15);
        color: #997404;
    }

    .status-expired {
        background: rgba(108, 117, 125, 0.12);
        color: #6c757d;
    }

    .status-cancelled {
        background: rgba(220, 53, 69, 0.12);
        color: #dc3545;
    }

    .status-failed {
        background: rgba(220, 53, 69, 0.12);
        color: #dc3545;
    }

    .status-paid {
        background: rgba(40, 167, 69, 0.12);
        color: #198754;
    }

    .status-unpaid {
        background: rgba(255, 193, 7, 0.15);
        color: #997404;
    }

    .status-unknown {
        background: rgba(108, 117, 125, 0.12);
        color: #6c757d;
    }

    .empty-history {
        padding: 60px 20px;
        text-align: center;
    }

    .empty-history i {
        font-size: 48px;
        opacity: 0.35;
    }

    @media (max-width: 767.98px) {

        .subscription-header {
            align-items: flex-start !important;
            gap: 15px;
        }

        .subscription-header .btn {
            width: 100%;
        }

        .subscription-header-action {
            width: 100%;
        }

        .subscription-table {
            min-width: 850px;
        }

    }

</style>


<div class="content-wrapper">

    <div class="container-xxl flex-grow-1 container-p-y">


        {{-- =====================================================
             PAGE HEADER
        ====================================================== --}}

        <div class="row">

            <div class="col-xxl-12 mb-4 order-0">

                <div class="card">

                    <div class="card-header d-flex justify-content-between align-items-center subscription-header">

                        <div>

                            <h5 class="mb-1 fw-bold">
                                Subscription History
                            </h5>

                            <small class="text-muted">
                                Riwayat subscription {{ $company->company_name }}
                            </small>

                        </div>


                        <div class="subscription-header-action">

                            <a href="{{ route('subscription.index') }}"
                               class="btn btn-primary">

                                <i class="bi bi-credit-card me-1"></i>

                                Subscribe

                            </a>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        {{-- =====================================================
             SUMMARY
        ====================================================== --}}

        <div class="row">


            {{-- TOTAL SUBSCRIPTION --}}

            <div class="col-xxl-4 col-xl-4 col-lg-4 col-md-12 mb-4">

                <div class="card subscription-summary-card">

                    <div class="card-body">

                        <div class="d-flex align-items-center">

                            <div class="subscription-icon me-3">

                                <i class="bi bi-clock-history"></i>

                            </div>

                            <div>

                                <small class="text-muted">
                                    Total Subscription
                                </small>

                                <h4 class="mb-0 fw-bold">
                                    {{ $totalSubscriptions }}
                                </h4>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            {{-- CURRENT PLAN --}}

            <div class="col-xxl-4 col-xl-4 col-lg-4 col-md-12 mb-4">

                <div class="card subscription-summary-card">

                    <div class="card-body">

                        <div class="d-flex align-items-center">

                            <div class="subscription-icon me-3">

                                <i class="bi bi-star"></i>

                            </div>

                            <div>

                                <small class="text-muted">
                                    Current Plan
                                </small>

                                <h5 class="mb-0 fw-bold">

                                    @if($activeSubscription?->plan)

                                        {{ $activeSubscription->plan->plan_name }}

                                    @else

                                        -

                                    @endif

                                </h5>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            {{-- EXPIRATION --}}

            <div class="col-xxl-4 col-xl-4 col-lg-4 col-md-12 mb-4">

                <div class="card subscription-summary-card">

                    <div class="card-body">

                        <div class="d-flex align-items-center">

                            <div class="subscription-icon me-3">

                                <i class="bi bi-calendar-event"></i>

                            </div>

                            <div>

                                <small class="text-muted">
                                    Current Expiration
                                </small>

                                <h5 class="mb-0 fw-bold">

                                    @if($activeSubscription)

                                        {{ \Carbon\Carbon::parse($activeSubscription->end_date)->format('d M Y') }}

                                    @else

                                        -

                                    @endif

                                </h5>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        {{-- =====================================================
             HISTORY TABLE
        ====================================================== --}}

        <div class="row">

            <div class="col-xxl-12 mb-4 order-0">

                <div class="card">


                    {{-- CARD HEADER --}}

                    <div class="card-header">

                        <h5 class="mb-1 fw-bold">
                            Subscription History
                        </h5>

                        <small class="text-muted">
                            Daftar seluruh subscription perusahaan
                        </small>

                    </div>


                    {{-- CARD BODY --}}

                    <div class="card-body">


                        @if($subscriptions->count())

                            <div class="table-responsive">

                                <table class="table table-hover align-middle subscription-table">

                                    <thead>

                                        <tr>

                                            <th>
                                                #
                                            </th>

                                            <th>
                                                Plan
                                            </th>

                                            <th>
                                                Billing
                                            </th>

                                            <th>
                                                Price
                                            </th>

                                            <th>
                                                Start Date
                                            </th>

                                            <th>
                                                End Date
                                            </th>

                                            <th>
                                                Subscription
                                            </th>

                                            <th>
                                                Payment
                                            </th>

                                        </tr>

                                    </thead>


                                    <tbody>

                                        @foreach($subscriptions as $subscription)

                                            @php

                                                $status = strtolower(
                                                    $subscription->status ?? ''
                                                );

                                                $paymentStatus = strtolower(
                                                    $subscription->payment_status ?? ''
                                                );

                                            @endphp

                                            <tr>


                                                {{-- NUMBER --}}

                                                <td>

                                                    {{ $subscriptions->firstItem() + $loop->index }}

                                                </td>


                                                {{-- PLAN --}}

                                                <td>

                                                    <div class="subscription-plan">

                                                        {{ $subscription->plan?->plan_name ?? '-' }}

                                                    </div>

                                                    @if($subscription->plan?->plan_code)

                                                        <small class="text-muted">

                                                            {{ $subscription->plan->plan_code }}

                                                        </small>

                                                    @endif

                                                </td>


                                                {{-- BILLING --}}

                                                <td>

                                                    @if($subscription->billing_cycle === 'yearly')

                                                        <span class="badge bg-info">
                                                            Yearly
                                                        </span>

                                                    @elseif($subscription->billing_cycle === 'monthly')

                                                        <span class="badge bg-secondary">
                                                            Monthly
                                                        </span>

                                                    @else

                                                        <span class="text-muted">
                                                            -
                                                        </span>

                                                    @endif

                                                </td>


                                                {{-- PRICE --}}

                                                <td class="subscription-price">

                                                    Rp
                                                    {{ number_format(
                                                        (float) $subscription->price,
                                                        0,
                                                        ',',
                                                        '.'
                                                    ) }}

                                                </td>


                                                {{-- START DATE --}}

                                                <td class="subscription-date">

                                                    {{ \Carbon\Carbon::parse($subscription->start_date)->format('d M Y') }}

                                                </td>


                                                {{-- END DATE --}}

                                                <td class="subscription-date">

                                                    {{ \Carbon\Carbon::parse($subscription->end_date)->format('d M Y') }}

                                                </td>


                                                {{-- SUBSCRIPTION STATUS --}}

                                                <td>

                                                    @switch($status)

                                                        @case('active')

                                                            <span class="status-badge status-active">
                                                                Active
                                                            </span>

                                                        @break


                                                        @case('pending')

                                                            <span class="status-badge status-pending">
                                                                Pending
                                                            </span>

                                                        @break


                                                        @case('cancelled')

                                                            <span class="status-badge status-cancelled">
                                                                Cancelled
                                                            </span>

                                                        @break


                                                        @case('expired')

                                                            <span class="status-badge status-expired">
                                                                Expired
                                                            </span>

                                                        @break


                                                        @default

                                                            <span class="status-badge status-unknown">
                                                                {{ ucfirst($subscription->status ?? '-') }}
                                                            </span>

                                                    @endswitch

                                                </td>


                                                {{-- PAYMENT STATUS --}}

                                                <td>

                                                    @switch($paymentStatus)

                                                        @case('paid')

                                                        @case('settlement')

                                                        @case('capture')

                                                            <span class="status-badge status-paid">
                                                                Paid
                                                            </span>

                                                        @break


                                                        @case('pending')

                                                            <span class="status-badge status-pending">
                                                                Pending
                                                            </span>

                                                        @break


                                                        @case('failed')

                                                        @case('deny')

                                                        @case('cancel')

                                                        @case('expire')

                                                            <span class="status-badge status-failed">
                                                                Failed
                                                            </span>

                                                        @break


                                                        @default

                                                            <span class="status-badge status-unknown">
                                                                {{ ucfirst($subscription->payment_status ?? '-') }}
                                                            </span>

                                                    @endswitch

                                                </td>


                                            </tr>

                                        @endforeach

                                    </tbody>

                                </table>

                            </div>


                            {{-- =================================================
                                 PAGINATION
                            ================================================== --}}

                            <div class="d-flex justify-content-end mt-4">

                                {{ $subscriptions->links() }}

                            </div>


                        @else


                            {{-- =================================================
                                 EMPTY
                            ================================================== --}}

                            <div class="empty-history">

                                <i class="bi bi-clock-history"></i>

                                <h5 class="mt-3">
                                    Belum Ada Riwayat Subscription
                                </h5>

                                <p class="text-muted mb-4">
                                    Perusahaan ini belum memiliki riwayat subscription.
                                </p>

                                <a href="{{ route('subscription.index') }}"
                                   class="btn btn-primary">

                                    <i class="bi bi-credit-card me-1"></i>

                                    Subscribe Sekarang

                                </a>

                            </div>

                        @endif


                    </div>

                </div>

            </div>

        </div>


    </div>

</div>

@endsection