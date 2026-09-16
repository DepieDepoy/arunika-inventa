@extends('dashboard.layouts.wrapper')

@section('title', 'Subscription')

@section('content')

<style>
    /*
    |--------------------------------------------------------------------------
    | SUBSCRIPTION PAGE
    |--------------------------------------------------------------------------
    */

    .subscription-page {
        padding-bottom: 30px;
    }

    /*
    |--------------------------------------------------------------------------
    | HERO HEADER
    |--------------------------------------------------------------------------
    */

    .subscription-hero {
        position: relative;
        overflow: hidden;
        border: 0;
        border-radius: 20px;
        color: #fff;
        background:
            linear-gradient(
                135deg,
                #4f46e5 0%,
                #6366f1 45%,
                #8b5cf6 100%
            );
        box-shadow: 0 12px 30px rgba(79, 70, 229, 0.22);
    }

    .subscription-hero::before {
        content: "";
        position: absolute;
        width: 240px;
        height: 240px;
        right: -80px;
        top: -100px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.12);
    }

    .subscription-hero::after {
        content: "";
        position: absolute;
        width: 180px;
        height: 180px;
        right: 100px;
        bottom: -130px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.08);
    }

    .subscription-hero-content {
        position: relative;
        z-index: 2;
    }

    .subscription-hero-icon {
        width: 65px;
        height: 65px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 18px;
        font-size: 30px;
        background: rgba(255, 255, 255, 0.18);
        backdrop-filter: blur(5px);
    }

    /*
    |--------------------------------------------------------------------------
    | CURRENT SUBSCRIPTION
    |--------------------------------------------------------------------------
    */

    .current-subscription-card {
        border: 0;
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.06);
    }

    .current-subscription-header {
        padding: 18px 22px;
        color: #fff;
        background: linear-gradient(
            135deg,
            #0f766e,
            #14b8a6
        );
    }

    .current-subscription-item {
        min-height: 80px;
        padding: 15px;
        border-radius: 14px;
        background: #f8fafc;
        border: 1px solid #eef2f7;
    }

    .current-subscription-item .item-icon {
        width: 36px;
        height: 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        margin-bottom: 8px;
        background: #e0f2fe;
        color: #0284c7;
    }

    /*
    |--------------------------------------------------------------------------
    | BILLING TOGGLE
    |--------------------------------------------------------------------------
    */

    .billing-wrapper {
        display: inline-flex;
        padding: 5px;
        border-radius: 14px;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
    }

    .billing-wrapper .form-check {
        margin: 0;
        padding: 0;
    }

    .billing-wrapper .form-check-input {
        display: none;
    }

    .billing-wrapper .form-check-label {
        cursor: pointer;
        display: block;
        padding: 10px 20px;
        border-radius: 10px;
        color: #64748b;
        font-weight: 600;
        transition: all 0.2s ease;
    }

    .billing-wrapper .form-check-input:checked + .form-check-label {
        color: #fff;
        background: linear-gradient(
            135deg,
            #4f46e5,
            #7c3aed
        );
        box-shadow: 0 4px 10px rgba(79, 70, 229, 0.25);
    }

    /*
    |--------------------------------------------------------------------------
    | PLAN CARD
    |--------------------------------------------------------------------------
    */

    .subscription-card {
        position: relative;
        height: 100%;
        overflow: hidden;
        border: 2px solid transparent !important;
        border-radius: 20px !important;
        background: #fff;
        transition: all 0.25s ease;
        box-shadow: 0 5px 18px rgba(15, 23, 42, 0.06);
    }

    .subscription-card:hover {
        transform: translateY(-7px);
        box-shadow: 0 14px 30px rgba(15, 23, 42, 0.12);
    }

    .subscription-card.selected {
        border-color: #6366f1 !important;
        box-shadow: 0 12px 28px rgba(99, 102, 241, 0.22);
    }

    .subscription-card.selected::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 5px;
        background: linear-gradient(
            90deg,
            #4f46e5,
            #a855f7
        );
    }

    .plan-top-icon {
        width: 58px;
        height: 58px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 16px;
        font-size: 27px;
        margin-bottom: 15px;
    }

    .plan-starter .plan-top-icon {
        color: #0369a1;
        background: #e0f2fe;
    }

    .plan-professional .plan-top-icon {
        color: #6d28d9;
        background: #ede9fe;
    }

    .plan-enterprise .plan-top-icon {
        color: #b45309;
        background: #fef3c7;
    }

    .plan-price {
        min-height: 78px;
    }

    .plan-price .price-value {
        font-size: 30px;
        font-weight: 800;
        letter-spacing: -1px;
    }

    .plan-price .price-period {
        color: #64748b;
        font-size: 13px;
    }

    .plan-description {
        min-height: 50px;
        color: #64748b;
        font-size: 13px;
    }

    .plan-feature {
        display: flex;
        align-items: center;
        gap: 9px;
        margin-bottom: 12px;
        color: #475569;
        font-size: 14px;
    }

    .plan-feature i {
        color: #10b981;
        font-size: 17px;
    }

    .popular-badge {
        position: absolute;
        top: 18px;
        right: -34px;
        width: 135px;
        padding: 6px 0;
        text-align: center;
        color: #fff;
        background: linear-gradient(
            135deg,
            #7c3aed,
            #a855f7
        );
        font-size: 11px;
        font-weight: 700;
        transform: rotate(45deg);
        box-shadow: 0 3px 8px rgba(124, 58, 237, 0.25);
    }

    .plan-radio {
        width: 19px;
        height: 19px;
        cursor: pointer;
    }

    .select-plan-button {
        border-radius: 12px;
        font-weight: 600;
        transition: all 0.2s ease;
    }

    .subscription-card.selected .select-plan-button {
        color: #fff;
        border-color: #6366f1;
        background: linear-gradient(
            135deg,
            #4f46e5,
            #7c3aed
        );
    }

    /*
    |--------------------------------------------------------------------------
    | PAYMENT BUTTON
    |--------------------------------------------------------------------------
    */

    .btn-subscription-submit {
        border: 0;
        border-radius: 13px;
        padding: 13px 25px;
        font-weight: 700;
        background: linear-gradient(
            135deg,
            #4f46e5,
            #7c3aed
        );
        box-shadow: 0 7px 15px rgba(79, 70, 229, 0.22);
        transition: all 0.2s ease;
    }

    .btn-subscription-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(79, 70, 229, 0.30);
    }

    /*
    |--------------------------------------------------------------------------
    | PENDING PAYMENT
    |--------------------------------------------------------------------------
    */

    .pending-payment-alert {
        border: 0;
        border-left: 5px solid #f59e0b;
        border-radius: 14px;
        background: #fffbeb;
        color: #92400e;
    }

    /*
    |--------------------------------------------------------------------------
    | RESPONSIVE
    |--------------------------------------------------------------------------
    */

    @media (max-width: 767px) {
        .subscription-hero {
            border-radius: 15px;
        }

        .subscription-hero-icon {
            width: 52px;
            height: 52px;
            font-size: 24px;
        }

        .plan-price .price-value {
            font-size: 26px;
        }

        .billing-wrapper .form-check-label {
            padding: 9px 13px;
        }
    }
</style>


<div class="content-wrapper subscription-page">

    <div class="container-xxl flex-grow-1 container-p-y">

        {{-- =====================================================
             HERO HEADER
        ====================================================== --}}

        <div class="subscription-hero mb-4">

            <div class="card-body p-4 p-lg-5">

                <div class="subscription-hero-content">

                    <div class="row align-items-center">

                        <div class="col-md-8">

                            <div class="d-flex align-items-center gap-3">

                                <div class="subscription-hero-icon">
                                    <i class="bi bi-stars"></i>
                                </div>

                                <div>

                                    <h3 class="mb-1 fw-bold text-white">
                                        Subscription
                                    </h3>

                                    <p class="mb-0 text-white opacity-75">
                                        Kelola paket dan tingkatkan kemampuan
                                        {{ $company->company_name }}.
                                    </p>

                                </div>

                            </div>

                        </div>

                        <div class="col-md-4 text-md-end mt-4 mt-md-0">

                            <span class="badge rounded-pill bg-white text-primary px-3 py-2">
                                <i class="bi bi-shield-check me-1"></i>
                                Secure Payment
                            </span>

                            <div class="small text-white opacity-75 mt-2">
                                Powered by Midtrans
                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        {{-- =====================================================
             FLASH MESSAGE
        ====================================================== --}}

        @if(session('success'))

            <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4">

                <i class="bi bi-check-circle-fill me-2"></i>

                {{ session('success') }}

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                ></button>

            </div>

        @endif


        @if($errors->any())

            <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-4">

                <i class="bi bi-exclamation-triangle-fill me-2"></i>

                <strong>Terjadi kesalahan:</strong>

                <ul class="mb-0 mt-2">

                    @foreach($errors->all() as $error)

                        <li>{{ $error }}</li>

                    @endforeach

                </ul>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                ></button>

            </div>

        @endif


        {{-- =====================================================
             CURRENT SUBSCRIPTION
        ====================================================== --}}

        @if($currentSubscription)

            <div class="card current-subscription-card mb-4">

                <div class="current-subscription-header">

                    <div class="d-flex align-items-center justify-content-between">

                        <div>

                            <h5 class="mb-1 text-white fw-bold">
                                <i class="bi bi-award me-2"></i>
                                Current Subscription
                            </h5>

                            <small class="text-white opacity-75">
                                Informasi paket yang sedang digunakan
                            </small>

                        </div>

                        <i class="bi bi-patch-check-fill fs-2"></i>

                    </div>

                </div>

                <div class="card-body p-4">

                    <div class="row g-3">

                        {{-- PLAN --}}

                        <div class="col-12 col-sm-6 col-lg-3">

                            <div class="current-subscription-item">

                                <div class="item-icon">
                                    <i class="bi bi-box-seam"></i>
                                </div>

                                <small class="text-muted d-block">
                                    Plan
                                </small>

                                <div class="fw-bold fs-5">

                                    {{ $currentSubscription->plan->plan_name ?? '-' }}

                                </div>

                            </div>

                        </div>


                        {{-- STATUS --}}

                        <div class="col-12 col-sm-6 col-lg-3">

                            <div class="current-subscription-item">

                                <div class="item-icon">
                                    <i class="bi bi-activity"></i>
                                </div>

                                <small class="text-muted d-block">
                                    Status
                                </small>

                                <div class="mt-1">

                                    @if($currentSubscription->status === 'active')

                                        <span class="badge rounded-pill bg-success px-3 py-2">
                                            <i class="bi bi-check-circle me-1"></i>
                                            Active
                                        </span>

                                    @elseif($currentSubscription->status === 'expired')

                                        <span class="badge rounded-pill bg-danger px-3 py-2">
                                            <i class="bi bi-x-circle me-1"></i>
                                            Expired
                                        </span>

                                    @elseif($currentSubscription->status === 'replaced')

                                        <span class="badge rounded-pill bg-warning text-dark px-3 py-2">
                                            <i class="bi bi-arrow-left-right me-1"></i>
                                            Replaced
                                        </span>

                                    @elseif($currentSubscription->status === 'cancelled')

                                        <span class="badge rounded-pill bg-secondary px-3 py-2">
                                            <i class="bi bi-slash-circle me-1"></i>
                                            Cancelled
                                        </span>

                                    @elseif($currentSubscription->status === 'pending')

                                        <span class="badge rounded-pill bg-warning text-dark px-3 py-2">
                                            <i class="bi bi-hourglass-split me-1"></i>
                                            Pending
                                        </span>

                                    @else

                                        <span class="badge rounded-pill bg-secondary px-3 py-2">
                                            {{ ucfirst($currentSubscription->status) }}
                                        </span>

                                    @endif

                                </div>

                            </div>

                        </div>


                        {{-- START DATE --}}

                        <div class="col-12 col-sm-6 col-lg-3">

                            <div class="current-subscription-item">

                                <div class="item-icon">
                                    <i class="bi bi-calendar-check"></i>
                                </div>

                                <small class="text-muted d-block">
                                    Start Date
                                </small>

                                <div class="fw-semibold">

                                    {{ $currentSubscription->start_date
                                        ? $currentSubscription->start_date->translatedFormat('d F Y')
                                        : '-'
                                    }}

                                </div>

                            </div>

                        </div>


                        {{-- END DATE --}}

                        <div class="col-12 col-sm-6 col-lg-3">

                            <div class="current-subscription-item">

                                <div class="item-icon">
                                    <i class="bi bi-calendar-x"></i>
                                </div>

                                <small class="text-muted d-block">
                                    Expired Date
                                </small>

                                <div class="fw-semibold">

                                    {{ $currentSubscription->end_date
                                        ? $currentSubscription->end_date->translatedFormat('d F Y')
                                        : '-'
                                    }}

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        @endif


        {{-- =====================================================
             PENDING PAYMENT
        ====================================================== --}}

        @if($pendingPayment)

            <div class="alert pending-payment-alert shadow-sm mb-4">

                <div class="d-flex align-items-start gap-3">

                    <i class="bi bi-hourglass-split fs-3"></i>

                    <div class="flex-grow-1">

                        <h6 class="fw-bold mb-1">
                            Pembayaran Belum Selesai
                        </h6>

                        <div class="small mb-2">

                            Masih terdapat transaksi pembayaran yang belum selesai.

                            @if($pendingPayment->plan)

                                Paket:
                                <strong>
                                    {{ $pendingPayment->plan->plan_name }}
                                </strong>

                            @endif

                            @if($pendingPayment->gross_amount)

                                — Rp
                                {{ number_format($pendingPayment->gross_amount, 0, ',', '.') }}

                            @endif

                        </div>

                        @if($pendingPayment->payment_url)

                            <a
                                href="{{ $pendingPayment->payment_url }}"
                                class="btn btn-warning btn-sm fw-semibold"
                            >
                                <i class="bi bi-credit-card me-1"></i>
                                Lanjutkan Pembayaran
                            </a>

                        @endif

                    </div>

                </div>

            </div>

        @endif


        {{-- =====================================================
             AVAILABLE PLANS
        ====================================================== --}}

        <div class="card border-0 shadow-sm rounded-4">

            <div class="card-body p-4 p-lg-5">

                <div class="text-center mb-4">

                    <span class="badge rounded-pill bg-primary-subtle text-primary px-3 py-2 mb-2">
                        <i class="bi bi-lightning-charge-fill me-1"></i>
                        Upgrade Your Experience
                    </span>

                    <h4 class="fw-bold mb-2">
                        Pilih Paket Terbaik untuk Perusahaan Anda
                    </h4>

                    <p class="text-muted mb-0">
                        Sesuaikan paket dengan kebutuhan user dan asset perusahaan.
                    </p>

                </div>


                {{-- =================================================
                     BILLING CYCLE
                ================================================== --}}

                <div class="d-flex justify-content-center mb-5">

                    <div class="billing-wrapper">

                        <div class="form-check">

                            <input
                                class="form-check-input"
                                type="radio"
                                name="billing_cycle_display"
                                id="billing_monthly"
                                value="monthly"
                                checked
                            >

                            <label
                                class="form-check-label"
                                for="billing_monthly"
                            >
                                <i class="bi bi-calendar-month me-1"></i>
                                Monthly
                            </label>

                        </div>

                        <div class="form-check">

                            <input
                                class="form-check-input"
                                type="radio"
                                name="billing_cycle_display"
                                id="billing_yearly"
                                value="yearly"
                            >

                            <label
                                class="form-check-label"
                                for="billing_yearly"
                            >
                                <i class="bi bi-calendar-check me-1"></i>
                                Yearly
                                <span class="badge bg-success ms-1">
                                    Save
                                </span>
                            </label>

                        </div>

                    </div>

                </div>


                <form
                    action="{{ route('subscription.renew') }}"
                    method="POST"
                    id="subscriptionForm"
                >

                    @csrf

                    {{-- Hidden billing cycle untuk dikirim ke backend --}}

                    <input
                        type="hidden"
                        name="billing_cycle"
                        id="billing_cycle"
                        value="monthly"
                    >


                    <div class="row g-4">

                        @forelse($plans as $plan)

                            @if(strtoupper($plan->plan_code) !== 'FREE')

                                @php
                                    $planClass = match(strtoupper($plan->plan_code)) {
                                        'STARTER' => 'plan-starter',
                                        'PROFESSIONAL' => 'plan-professional',
                                        'ENTERPRISE' => 'plan-enterprise',
                                        default => 'plan-starter',
                                    };

                                    $planIcon = match(strtoupper($plan->plan_code)) {
                                        'STARTER' => 'bi-rocket-takeoff',
                                        'PROFESSIONAL' => 'bi-gem',
                                        'ENTERPRISE' => 'bi-building-check',
                                        default => 'bi-box-seam',
                                    };

                                    $isPopular = strtoupper($plan->plan_code) === 'PROFESSIONAL';
                                @endphp

                                <div class="col-12 col-md-6 col-xl-4">

                                    <div
                                        class="card subscription-card {{ $planClass }}"
                                        data-plan-card="{{ $plan->id }}"
                                    >

                                        @if($isPopular)

                                            <div class="popular-badge">
                                                POPULAR
                                            </div>

                                        @endif

                                        <div class="card-body p-4 d-flex flex-column">

                                            {{-- PLAN HEADER --}}

                                            <div class="plan-top-icon">

                                                <i class="bi {{ $planIcon }}"></i>

                                            </div>

                                            <div class="form-check mb-3">

                                                <input
                                                    class="form-check-input plan-radio"
                                                    type="radio"
                                                    name="plan_id"
                                                    value="{{ $plan->id }}"
                                                    id="plan_{{ $plan->id }}"
                                                    {{ $loop->first ? 'checked' : '' }}
                                                >

                                                <label
                                                    class="form-check-label fw-bold fs-5"
                                                    for="plan_{{ $plan->id }}"
                                                >
                                                    {{ $plan->plan_name }}
                                                </label>

                                            </div>


                                            {{-- DESCRIPTION --}}

                                            <div class="plan-description mb-3">

                                                {{ $plan->description ?: 'Paket subscription untuk mendukung kebutuhan perusahaan Anda.' }}

                                            </div>


                                            {{-- PRICE --}}

                                            <div class="plan-price mb-3">

                                                <div class="monthly-price">

                                                    <span class="price-value">

                                                        Rp{{ number_format($plan->price_monthly, 0, ',', '.') }}

                                                    </span>

                                                    <span class="price-period">
                                                        / bulan
                                                    </span>

                                                </div>

                                                <div class="yearly-price d-none">

                                                    <span class="price-value">

                                                        Rp{{ number_format($plan->price_yearly, 0, ',', '.') }}

                                                    </span>

                                                    <span class="price-period">
                                                        / tahun
                                                    </span>

                                                </div>

                                            </div>


                                            <hr>


                                            {{-- FEATURES --}}

                                            <div class="mb-4">

                                                <div class="plan-feature">

                                                    <i class="bi bi-check-circle-fill"></i>

                                                    <span>

                                                        @if($plan->max_users)
                                                            Maksimal {{ $plan->max_users }} Users
                                                        @else
                                                            Unlimited Users
                                                        @endif

                                                    </span>

                                                </div>

                                                <div class="plan-feature">

                                                    <i class="bi bi-check-circle-fill"></i>

                                                    <span>

                                                        @if($plan->max_assets)
                                                            Maksimal {{ $plan->max_assets }} Assets
                                                        @else
                                                            Unlimited Assets
                                                        @endif

                                                    </span>

                                                </div>

                                                <div class="plan-feature">

                                                    <i class="bi bi-check-circle-fill"></i>

                                                    <span>
                                                        Asset Management
                                                    </span>

                                                </div>

                                                <div class="plan-feature">

                                                    <i class="bi bi-check-circle-fill"></i>

                                                    <span>
                                                        Maintenance Management
                                                    </span>

                                                </div>

                                            </div>


                                            {{-- SELECT BUTTON --}}

                                            <div class="mt-auto">

                                                <label
                                                    class="btn btn-outline-primary select-plan-button w-100"
                                                    for="plan_{{ $plan->id }}"
                                                >

                                                    <i class="bi bi-check2-circle me-1"></i>

                                                    Pilih Paket

                                                </label>

                                            </div>

                                        </div>

                                    </div>

                                </div>

                            @endif

                        @empty

                            <div class="col-12">

                                <div class="alert alert-warning">

                                    <i class="bi bi-exclamation-circle me-2"></i>

                                    Belum ada paket subscription yang tersedia.

                                </div>

                            </div>

                        @endforelse

                    </div>


                    {{-- =================================================
                         SUBMIT
                    ================================================== --}}

                    <div class="d-flex flex-column flex-md-row align-items-center justify-content-between gap-3 mt-5 pt-4 border-top">

                        <div class="text-muted small text-center text-md-start">

                            <i class="bi bi-lock-fill me-1"></i>

                            Pembayaran aman melalui Midtrans

                            <br>

                            <i class="bi bi-info-circle me-1"></i>

                            Subscription aktif setelah pembayaran dikonfirmasi.

                        </div>

                        <button
                            type="submit"
                            class="btn btn-primary btn-subscription-submit"
                            id="btnSubmitSubscription"
                        >

                            <i class="bi bi-arrow-right-circle me-1"></i>

                            Lanjutkan Pembayaran

                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

</div>


{{-- =========================================================
     SCRIPT
========================================================= --}}

<script>
document.addEventListener('DOMContentLoaded', function () {

    const billingMonthly =
        document.getElementById('billing_monthly');

    const billingYearly =
        document.getElementById('billing_yearly');

    const billingCycleInput =
        document.getElementById('billing_cycle');

    const subscriptionForm =
        document.getElementById('subscriptionForm');

    const submitButton =
        document.getElementById('btnSubmitSubscription');

    const planCards =
        document.querySelectorAll('.subscription-card');

    const planRadios =
        document.querySelectorAll('input[name="plan_id"]');


    /*
    |--------------------------------------------------------------------------
    | UPDATE BILLING CYCLE
    |--------------------------------------------------------------------------
    */

    function updatePrices() {

        const isYearly =
            billingYearly && billingYearly.checked;

        if (billingCycleInput) {
            billingCycleInput.value =
                isYearly ? 'yearly' : 'monthly';
        }

        document
            .querySelectorAll('.monthly-price')
            .forEach(function (element) {

                element.classList.toggle(
                    'd-none',
                    isYearly
                );

            });

        document
            .querySelectorAll('.yearly-price')
            .forEach(function (element) {

                element.classList.toggle(
                    'd-none',
                    !isYearly
                );

            });

    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE SELECTED PLAN
    |--------------------------------------------------------------------------
    */

    function updateSelectedPlan() {

        planCards.forEach(function (card) {

            const radio =
                card.querySelector('input[name="plan_id"]');

            if (radio && radio.checked) {

                card.classList.add('selected');

            } else {

                card.classList.remove('selected');

            }

        });

    }


    /*
    |--------------------------------------------------------------------------
    | BILLING EVENTS
    |--------------------------------------------------------------------------
    */

    if (billingMonthly) {

        billingMonthly.addEventListener(
            'change',
            updatePrices
        );

    }

    if (billingYearly) {

        billingYearly.addEventListener(
            'change',
            updatePrices
        );

    }


    /*
    |--------------------------------------------------------------------------
    | PLAN RADIO EVENTS
    |--------------------------------------------------------------------------
    */

    planRadios.forEach(function (radio) {

        radio.addEventListener('change', function () {

            updateSelectedPlan();

        });

    });


    /*
    |--------------------------------------------------------------------------
    | PLAN CARD CLICK
    |--------------------------------------------------------------------------
    */

    planCards.forEach(function (card) {

        card.addEventListener('click', function (event) {

            if (
                event.target.closest('label') ||
                event.target.tagName === 'INPUT'
            ) {
                return;
            }

            const radio =
                card.querySelector('input[name="plan_id"]');

            if (radio) {

                radio.checked = true;

                radio.dispatchEvent(
                    new Event('change')
                );

            }

        });

    });


    /*
    |--------------------------------------------------------------------------
    | FORM SUBMIT CONFIRMATION
    |--------------------------------------------------------------------------
    */

    if (subscriptionForm) {

        subscriptionForm.addEventListener(
            'submit',
            function (event) {

                event.preventDefault();

                const selectedPlan =
                    document.querySelector(
                        'input[name="plan_id"]:checked'
                    );

                const selectedCycle =
                    billingCycleInput
                        ? billingCycleInput.value
                        : 'monthly';

                if (!selectedPlan) {

                    Swal.fire({
                        icon: 'warning',
                        title: 'Pilih Paket',
                        text: 'Silakan pilih subscription plan terlebih dahulu.'
                    });

                    return;

                }

                const selectedCard =
                    document.querySelector(
                        '.subscription-card.selected'
                    );

                const planName =
                    selectedCard
                        ? selectedCard.querySelector(
                            'label[for^="plan_"]'
                        )?.innerText.trim()
                        : 'Subscription';

                const selectedPriceElement =
                    selectedCard
                        ? selectedCard.querySelector(
                            selectedCycle === 'yearly'
                                ? '.yearly-price .price-value'
                                : '.monthly-price .price-value'
                        )
                        : null;

                const selectedPrice =
                    selectedPriceElement
                        ? selectedPriceElement.innerText.trim()
                        : '';

                Swal.fire({

                    title: 'Konfirmasi Subscription',

                    html:
                        '<div class="text-start">' +
                        '<p class="mb-2">Anda memilih:</p>' +
                        '<div class="fw-bold fs-5">' +
                        planName +
                        '</div>' +
                        '<div class="text-muted mb-2">' +
                        (selectedCycle === 'yearly'
                            ? 'Billing Cycle: Yearly'
                            : 'Billing Cycle: Monthly') +
                        '</div>' +
                        '<div class="fw-bold text-primary fs-4">' +
                        selectedPrice +
                        '</div>' +
                        '<hr>' +
                        '<small class="text-muted">' +
                        'Anda akan diarahkan ke halaman pembayaran Midtrans.' +
                        '</small>' +
                        '</div>',

                    icon: 'question',

                    showCancelButton: true,

                    confirmButtonText:
                        '<i class="bi bi-credit-card me-1"></i> Lanjutkan',

                    cancelButtonText:
                        'Batal',

                    reverseButtons: true,

                    buttonsStyling: false,

                    customClass: {
                        confirmButton: 'btn btn-primary px-4 ms-2',
                        cancelButton: 'btn btn-secondary px-4'
                    }

                }).then(function (result) {

                    if (result.isConfirmed) {

                        submitButton.disabled = true;

                        submitButton.innerHTML =
                            '<span class="spinner-border spinner-border-sm me-2"></span>' +
                            'Menghubungkan ke Midtrans...';

                        subscriptionForm.submit();

                    }

                });

            }
        );

    }


    /*
    |--------------------------------------------------------------------------
    | INITIAL STATE
    |--------------------------------------------------------------------------
    */

    updatePrices();

    updateSelectedPlan();

});
</script>

@endsection