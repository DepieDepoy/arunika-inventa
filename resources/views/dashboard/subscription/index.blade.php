 
@extends('dashboard.layouts.wrapper')

@section('title', 'Subscription')

@section('content')

@php
    /*
    |--------------------------------------------------------------------------
    | Storage Add-on Settings dari Database
    |--------------------------------------------------------------------------
    |
    | Diambil dari SubscriptionService::storageRate() (tabel storage_addon_rates),
    | sumber yang sama dengan perhitungan harga di server.
    |
    */
    $storageAddonUnitBytes = (int) $storageRate['unit_bytes'];
    $storageAddonPriceMonthly = (int) $storageRate['price_monthly'];
    $storageAddonPriceYearly = (int) $storageRate['price_yearly'];
    $storageAddonMaxSteps = (int) $storageRate['max_units'];

    $storageAddonMaxBytes =
        $storageAddonUnitBytes * $storageAddonMaxSteps;

    $resolvePlanStorage = function ($plan) {
        if ($plan->max_storage_bytes !== null) {
            return (int) $plan->max_storage_bytes;
        }

        return match (strtoupper((string) $plan->plan_code)) {
            'FREE' => 500 * 1024 * 1024,
            'STARTER' => 1 * 1024 * 1024 * 1024,
            'PROFESSIONAL' => 5 * 1024 * 1024 * 1024,
            'ENTERPRISE' => 20 * 1024 * 1024 * 1024,
            default => null,
        };
    };

    $formatStorageServer = function ($bytes) {
        if ($bytes === null) {
            return 'Unlimited';
        }

        $bytes = max(0, (int) $bytes);
        $gib = 1024 * 1024 * 1024;
        $mib = 1024 * 1024;

        if ($bytes >= $gib) {
            $value = number_format(
                $bytes / $gib,
                2,
                ',',
                '.'
            );

            $value = rtrim(rtrim($value, '0'), ',');

            return $value . ' GB';
        }

        return number_format(
            $bytes / $mib,
            0,
            ',',
            '.'
        ) . ' MB';
    };
@endphp

<style>
    .subscription-page { padding-bottom: 30px; }

    .subscription-hero {
        position: relative;
        overflow: hidden;
        border: 0;
        border-radius: 20px;
        color: #fff;
        background: linear-gradient(135deg,#4f46e5 0%,#6366f1 45%,#8b5cf6 100%);
        box-shadow: 0 12px 30px rgba(79,70,229,.22);
    }

    .subscription-hero::before {
        content: "";
        position: absolute;
        width: 240px;
        height: 240px;
        right: -80px;
        top: -100px;
        border-radius: 50%;
        background: rgba(255,255,255,.12);
    }

    .subscription-hero::after {
        content: "";
        position: absolute;
        width: 180px;
        height: 180px;
        right: 100px;
        bottom: -130px;
        border-radius: 50%;
        background: rgba(255,255,255,.08);
    }

    .subscription-hero-content { position: relative; z-index: 2; }

    .subscription-hero-icon {
        width: 65px;
        height: 65px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 18px;
        font-size: 30px;
        background: rgba(255,255,255,.18);
        backdrop-filter: blur(5px);
    }

    .current-subscription-card {
        border: 0;
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 5px 20px rgba(0,0,0,.06);
    }

    .current-subscription-header {
        padding: 18px 22px;
        color: #fff;
        background: linear-gradient(135deg,#0f766e,#14b8a6);
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

    .billing-wrapper {
        display: inline-flex;
        padding: 5px;
        border-radius: 14px;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
    }

    .billing-wrapper .form-check { margin: 0; padding: 0; }
    .billing-wrapper .form-check-input { display: none; }

    .billing-wrapper .form-check-label {
        cursor: pointer;
        display: block;
        padding: 10px 20px;
        border-radius: 10px;
        color: #64748b;
        font-weight: 600;
        transition: all .2s ease;
    }

    .billing-wrapper .form-check-input:checked + .form-check-label {
        color: #fff;
        background: linear-gradient(135deg,#4f46e5,#7c3aed);
        box-shadow: 0 4px 10px rgba(79,70,229,.25);
    }

    .subscription-card {
        position: relative;
        height: 100%;
        overflow: hidden;
        border: 2px solid transparent !important;
        border-radius: 20px !important;
        background: #fff;
        transition: all .25s ease;
        box-shadow: 0 5px 18px rgba(15,23,42,.06);
    }

    .subscription-card:hover {
        transform: translateY(-7px);
        box-shadow: 0 14px 30px rgba(15,23,42,.12);
    }

    .subscription-card.selected {
        border-color: #6366f1 !important;
        box-shadow: 0 12px 28px rgba(99,102,241,.22);
    }

    .subscription-card.selected::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 5px;
        background: linear-gradient(90deg,#4f46e5,#a855f7);
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

    .plan-starter .plan-top-icon { color: #0369a1; background: #e0f2fe; }
    .plan-professional .plan-top-icon { color: #6d28d9; background: #ede9fe; }
    .plan-enterprise .plan-top-icon { color: #b45309; background: #fef3c7; }

    .plan-price { min-height: 78px; }

    .plan-price .price-value {
        font-size: 30px;
        font-weight: 800;
        letter-spacing: -1px;
    }

    .plan-price .price-period { color: #64748b; font-size: 13px; }

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

    .plan-feature i { color: #10b981; font-size: 17px; }

    .popular-badge {
        position: absolute;
        top: 18px;
        right: -34px;
        width: 135px;
        padding: 6px 0;
        text-align: center;
        color: #fff;
        background: linear-gradient(135deg,#7c3aed,#a855f7);
        font-size: 11px;
        font-weight: 700;
        transform: rotate(45deg);
        box-shadow: 0 3px 8px rgba(124,58,237,.25);
    }

    .select-plan-button {
        border-radius: 12px;
        font-weight: 600;
        transition: all .2s ease;
    }

    .subscription-card.selected .select-plan-button {
        color: #fff;
        border-color: #6366f1;
        background: linear-gradient(135deg,#4f46e5,#7c3aed);
    }

    .btn-subscription-submit {
        border: 0;
        border-radius: 13px;
        padding: 13px 25px;
        font-weight: 700;
        background: linear-gradient(135deg,#4f46e5,#7c3aed);
        box-shadow: 0 7px 15px rgba(79,70,229,.22);
        transition: all .2s ease;
    }

    .btn-subscription-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(79,70,229,.30);
    }

    .pending-payment-alert {
        border: 0;
        border-left: 5px solid #f59e0b;
        border-radius: 14px;
        background: #fffbeb;
        color: #92400e;
    }

    .storage-addon-card {
        border: 1px solid #e2e8f0;
        border-radius: 18px;
        background: #fff;
        box-shadow: 0 5px 18px rgba(15,23,42,.04);
    }

    .storage-value {
        font-size: 28px;
        font-weight: 800;
        letter-spacing: -.5px;
    }

    /* ---------- Upgrade & Add-on ---------- */
    .sub-highlight-card { border-left: 4px solid var(--bs-primary) !important; }
    .sub-highlight-icon {
        width: 52px; height: 52px; border-radius: 14px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center; font-size: 1.5rem;
        background: var(--bs-primary-bg-subtle, #e7efff); color: var(--bs-primary);
    }
    .sub-price-box {
        background: var(--bs-tertiary-bg, #f8f9fb);
        border: 1px solid var(--bs-border-color-translucent, rgba(0,0,0,.08));
        border-radius: 14px; padding: 1.1rem 1.25rem;
    }
    .sub-timeline {
        position: relative; padding: 1rem 1.25rem 1rem 2.25rem;
        border: 1px dashed var(--bs-border-color, #dee2e6); border-radius: 14px;
    }
    .sub-timeline-item { position: relative; padding-bottom: 1rem; }
    .sub-timeline-item:last-child { padding-bottom: 0; }
    .sub-timeline-item::before {
        content: ""; position: absolute; left: -1.05rem; top: 1.1rem; bottom: -.1rem;
        width: 2px; background: var(--bs-border-color, #dee2e6);
    }
    .sub-timeline-item:last-child::before { display: none; }
    .sub-timeline-dot {
        position: absolute; left: -1.4rem; top: .3rem; width: 12px; height: 12px;
        border-radius: 50%; background: var(--bs-secondary-bg, #e9ecef);
        border: 2px solid var(--bs-secondary-color, #adb5bd);
    }
    .sub-timeline-item.active .sub-timeline-dot {
        background: var(--bs-primary); border-color: var(--bs-primary);
        box-shadow: 0 0 0 4px rgba(var(--bs-primary-rgb, 13,110,253), .15);
    }
    .upgrade-plan-btn, .upgrade-duration-btn { min-width: 120px; text-align: left; border-radius: 12px; }
    .addon-tile {
        border: 1px solid var(--bs-border-color-translucent, rgba(0,0,0,.08));
        border-radius: 14px; padding: 1rem 1.1rem; transition: border-color .2s, box-shadow .2s;
    }
    .addon-tile.is-selected {
        border-color: var(--bs-success);
        box-shadow: 0 0 0 3px rgba(var(--bs-success-rgb, 25,135,84), .12);
    }
    .addon-tile-icon {
        width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center;
        background: var(--bs-success-bg-subtle, #e8f5ee); color: var(--bs-success);
    }

    .queued-subscription-card {
        border-left: 4px solid var(--bs-info) !important;
    }

    .queued-subscription-card .queued-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        background: var(--bs-info-bg-subtle, #e7f6fd);
        color: var(--bs-info-text-emphasis, #087990);
        flex-shrink: 0;
    }

    .storage-summary-box {
        padding: 16px;
        height: 100%;
        border-radius: 13px;
        background: #f8fafc;
        border: 1px solid #eef2f7;
    }

    .storage-range { height: 9px; cursor: pointer; }

    .subscription-total-card {
        border-radius: 16px;
        background: linear-gradient(135deg,#eef2ff,#f5f3ff);
        border: 1px solid #e0e7ff;
    }

    @media (max-width: 767px) {
        .subscription-hero { border-radius: 15px; }
        .subscription-hero-icon { width: 52px; height: 52px; font-size: 24px; }
        .plan-price .price-value { font-size: 26px; }
        .billing-wrapper .form-check-label { padding: 9px 13px; }
        .storage-value { font-size: 23px; }
    }
</style>

<div class="content-wrapper subscription-page">
    <div class="container-xxl flex-grow-1 container-p-y">

        {{-- HERO HEADER --}}
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
                                    <h3 class="mb-1 fw-bold text-white">Subscription</h3>
                                    <p class="mb-0 text-white opacity-75">
                                        Kelola paket dan tingkatkan kemampuan
                                        {{ $company->company_name }}.
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 text-md-end mt-4 mt-md-0">
                            <span class="badge rounded-pill bg-white text-primary px-3 py-2">
                                <i class="bi bi-shield-check me-1"></i> Secure Payment
                            </span>
                            <div class="small text-white opacity-75 mt-2">Powered by DOKU</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- FLASH MESSAGE --}}
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4">
                <i class="bi bi-check-circle-fill me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
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
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- CURRENT SUBSCRIPTION --}}
        @if($currentSubscription)
            <div class="card current-subscription-card mb-4">
                <div class="current-subscription-header">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h5 class="mb-1 text-white fw-bold">
                                <i class="bi bi-award me-2"></i> Current Subscription
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
                        <div class="col-12 col-sm-6 col-lg-3">
                            <div class="current-subscription-item">
                                <div class="item-icon"><i class="bi bi-box-seam"></i></div>
                                <small class="text-muted d-block">Plan</small>
                                <div class="fw-bold fs-5">
                                    {{ $currentSubscription->plan->plan_name ?? '-' }}
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-sm-6 col-lg-3">
                            <div class="current-subscription-item">
                                <div class="item-icon"><i class="bi bi-activity"></i></div>
                                <small class="text-muted d-block">Status</small>
                                <div class="mt-1">
                                    @if($currentSubscription->status === 'active')
                                        <span class="badge rounded-pill bg-success px-3 py-2">
                                            <i class="bi bi-check-circle me-1"></i> Active
                                        </span>
                                    @elseif($currentSubscription->status === 'expired')
                                        <span class="badge rounded-pill bg-danger px-3 py-2">
                                            <i class="bi bi-x-circle me-1"></i> Expired
                                        </span>
                                    @elseif($currentSubscription->status === 'replaced')
                                        <span class="badge rounded-pill bg-warning text-dark px-3 py-2">
                                            <i class="bi bi-arrow-left-right me-1"></i> Replaced
                                        </span>
                                    @elseif($currentSubscription->status === 'cancelled')
                                        <span class="badge rounded-pill bg-secondary px-3 py-2">
                                            <i class="bi bi-slash-circle me-1"></i> Cancelled
                                        </span>
                                    @elseif($currentSubscription->status === 'pending')
                                        <span class="badge rounded-pill bg-warning text-dark px-3 py-2">
                                            <i class="bi bi-hourglass-split me-1"></i> Pending
                                        </span>
                                    @else
                                        <span class="badge rounded-pill bg-secondary px-3 py-2">
                                            {{ ucfirst($currentSubscription->status) }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-sm-6 col-lg-3">
                            <div class="current-subscription-item">
                                <div class="item-icon"><i class="bi bi-calendar-check"></i></div>
                                <small class="text-muted d-block">Start Date</small>
                                <div class="fw-semibold">
                                    {{ $currentSubscription->start_date
                                        ? $currentSubscription->start_date->translatedFormat('d F Y')
                                        : '-' }}
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-sm-6 col-lg-3">
                            <div class="current-subscription-item">
                                <div class="item-icon"><i class="bi bi-calendar-x"></i></div>
                                <small class="text-muted d-block">Expired Date</small>
                                <div class="fw-semibold">
                                    {{ $currentSubscription->end_date
                                        ? $currentSubscription->end_date->translatedFormat('d F Y')
                                        : '-' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- NEXT (QUEUED) SUBSCRIPTION --}}
        @if($queuedSubscription)
            <div class="card border-0 shadow-sm rounded-4 mb-4 queued-subscription-card">
                <div class="card-body p-4">
                    <div class="d-flex align-items-start gap-3 flex-wrap">
                        <div class="queued-icon">
                            <i class="bi bi-calendar2-week"></i>
                        </div>
                        <div class="flex-grow-1">
                            <span class="badge bg-info-subtle text-info-emphasis mb-2">
                                <i class="bi bi-clock-history me-1"></i> Paket Berikutnya
                            </span>
                            <h5 class="fw-bold mb-1">
                                {{ $queuedSubscription->plan->plan_name ?? '-' }}
                                <small class="text-muted fw-normal">
                                    ({{ $queuedSubscription->billing_cycle === 'yearly' ? 'Tahunan' : 'Bulanan' }})
                                </small>
                            </h5>
                            <div class="text-muted small">
                                Sudah dibayar. Aktif otomatis mulai
                                <strong>{{ $queuedSubscription->start_date->translatedFormat('d F Y') }}</strong>
                                s/d
                                <strong>{{ $queuedSubscription->end_date->translatedFormat('d F Y') }}</strong>,
                                setelah paket saat ini berakhir.
                            </div>
                        </div>
                        <div class="text-md-end">
                            <small class="text-muted d-block">Kapasitas storage</small>
                            <strong>{{ $formatStorageServer($queuedSubscription->total_storage_bytes) }}</strong>
                            @if($queuedSubscription->plan?->max_assets !== null)
                                <small class="text-muted d-block mt-1">Maks. asset</small>
                                <strong>{{ number_format($queuedSubscription->plan->max_assets, 0, ',', '.') }}</strong>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- PENDING PAYMENT --}}
        @if($pendingPayment)
            <div class="alert pending-payment-alert shadow-sm mb-4">
                <div class="d-flex align-items-start gap-3">
                    <i class="bi bi-hourglass-split fs-3"></i>
                    <div class="flex-grow-1">
                        <h6 class="fw-bold mb-1">Pembayaran Belum Selesai</h6>
                        <div class="small mb-2">
                            Masih terdapat transaksi pembayaran yang belum selesai.
                            @if($pendingPayment->purchase_type === 'storage_addon')
                                Tambah storage:
                                <strong>{{ $formatStorageServer($pendingPayment->storage_addon_bytes) }}</strong>
                            @elseif($pendingPayment->plan)
                                Paket: <strong>{{ $pendingPayment->plan->plan_name }}</strong>
                            @endif
                            @if($pendingPayment->gross_amount)
                                — Rp{{ number_format($pendingPayment->gross_amount, 0, ',', '.') }}
                            @endif
                        </div>

                        @if($pendingPayment->payment_url)
                            <a href="{{ $pendingPayment->payment_url }}"
                               class="btn btn-warning btn-sm fw-semibold">
                                <i class="bi bi-credit-card me-1"></i> Lanjutkan Pembayaran
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        @php
            $svc = app(\App\Services\Subscription\SubscriptionService::class);
            $addonIcons = ['storage' => 'bi-hdd-stack', 'asset' => 'bi-box-seam', 'user' => 'bi-people'];
            $formatQuota = function ($type, $value) use ($formatStorageServer) {
                if ($value === null) {
                    return 'Unlimited';
                }
                return $type === 'storage'
                    ? $formatStorageServer($value)
                    : number_format((int) $value, 0, ',', '.');
            };
        @endphp

        {{-- UPGRADE YANG SEDANG BERJALAN --}}
        @if($activeUpgrade)
            <div class="card border-0 shadow-sm rounded-4 mb-4 sub-highlight-card sub-highlight-primary">
                <div class="card-body p-4 d-flex flex-wrap gap-3 align-items-center">
                    <div class="sub-highlight-icon"><i class="bi bi-rocket-takeoff"></i></div>
                    <div class="flex-grow-1">
                        <span class="badge bg-primary-subtle text-primary mb-1">Upgrade Aktif</span>
                        <h5 class="fw-bold mb-1">
                            {{ $activeUpgrade->plan->plan_name ?? '-' }}
                            <small class="text-muted fw-normal">
                                s/d {{ $activeUpgrade->end_date->translatedFormat('d F Y') }}
                            </small>
                        </h5>
                        <div class="small text-muted">
                            @if($activeUpgrade->parent && $activeUpgrade->end_date->lt($activeUpgrade->parent->end_date))
                                Otomatis kembali ke <strong>{{ $activeUpgrade->parent->plan?->plan_name }}</strong>
                                mulai <strong>{{ $activeUpgrade->end_date->copy()->addDay()->translatedFormat('d F Y') }}</strong>
                                s/d {{ $activeUpgrade->parent->end_date->translatedFormat('d F Y') }}.
                                Data tetap aman; jika pemakaian melebihi kuota paket tersebut, penambahan data baru diblokir.
                            @else
                                Berlaku sampai akhir masa paket Anda.
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- UPGRADE SEKARANG --}}
        @if($paidSubscription && count($upgradeOptions))
            <div class="card border-0 shadow-sm rounded-4 mb-4" id="upgrade-card">
                <div class="card-body p-4 p-lg-5">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                        <div>
                            <span class="badge bg-primary-subtle text-primary mb-2">
                                <i class="bi bi-lightning-charge-fill me-1"></i> Upgrade Sekarang
                            </span>
                            <h5 class="fw-bold mb-1">Butuh kapasitas lebih besar sekarang?</h5>
                            <p class="text-muted small mb-0">
                                Upgrade langsung aktif hari ini. Anda hanya membayar <strong>selisih harga</strong>;
                                sisa nilai paket <strong>{{ $paidSubscription->plan->plan_name ?? '' }}</strong>
                                dihitung sebagai kredit.
                            </p>
                        </div>
                        <div class="text-end small">
                            <div class="text-muted">Paket saat ini</div>
                            <strong>{{ $paidSubscription->plan->plan_name ?? '-' }}</strong>
                            <div class="text-muted">s/d {{ $paidSubscription->end_date->translatedFormat('d M Y') }}</div>
                        </div>
                    </div>

                    <form action="{{ route('subscription.upgrade') }}" method="POST" id="upgradeForm">
                        @csrf
                        <input type="hidden" name="plan_id" id="upgrade_plan_id">
                        <input type="hidden" name="duration" id="upgrade_duration">

                        <div class="mb-3">
                            <div class="small fw-semibold text-muted mb-2">1. Pilih paket tujuan</div>
                            <div class="d-flex flex-wrap gap-2" id="upgradePlanChoices"></div>
                        </div>

                        <div class="mb-4">
                            <div class="small fw-semibold text-muted mb-2">2. Pilih durasi upgrade</div>
                            <div class="d-flex flex-wrap gap-2" id="upgradeDurationChoices"></div>
                        </div>

                        <div class="row g-3 align-items-stretch">
                            <div class="col-lg-7">
                                <div class="sub-timeline h-100" id="upgradeTimeline"></div>
                            </div>
                            <div class="col-lg-5">
                                <div class="sub-price-box h-100">
                                    <div class="d-flex justify-content-between small mb-1">
                                        <span class="text-muted" id="upgradeNewLabel">Harga paket baru</span>
                                        <strong id="upgradeNewPrice">Rp0</strong>
                                    </div>
                                    <div class="d-flex justify-content-between small mb-2">
                                        <span class="text-muted">Kredit sisa paket saat ini</span>
                                        <strong class="text-success" id="upgradeCredit">−Rp0</strong>
                                    </div>
                                    <hr class="my-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="fw-bold">Bayar selisih</span>
                                        <span class="fs-4 fw-bold text-primary" id="upgradeTotal">Rp0</span>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100 mt-3" id="btnUpgrade">
                                        <i class="bi bi-arrow-up-circle me-1"></i> Upgrade Sekarang
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        @elseif($paidSubscription && $queuedSubscription && !$activeUpgrade)
            <div class="alert alert-light border small mb-4" id="upgrade-card">
                <i class="bi bi-info-circle me-1"></i>
                Upgrade sekarang tidak tersedia karena sudah ada paket berikutnya di antrean.
            </div>
        @endif

        {{-- TAMBAH KAPASITAS --}}
        @if($addonOffer)
            <div class="card border-0 shadow-sm rounded-4 mb-4" id="addon-card">
                <div class="card-body p-4 p-lg-5">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                        <div>
                            <span class="badge bg-success-subtle text-success mb-2">
                                <i class="bi bi-sliders me-1"></i> Tambah Storage
                            </span>
                            <h5 class="fw-bold mb-1">Butuh ruang penyimpanan lebih?</h5>
                            <p class="text-muted small mb-0">
                                Tambah storage tanpa ganti paket. Langsung aktif setelah pembayaran, harga dihitung prorata.
                                Untuk menambah kuota user atau aset, gunakan <a href="#upgrade-card">upgrade paket</a>.
                            </p>
                        </div>
                        <div class="btn-group" role="group" aria-label="Durasi add-on" id="addonDurationGroup">
                            <input type="radio" class="btn-check" name="addon_duration_display" id="addonDur1" value="1" checked>
                            <label class="btn btn-outline-success btn-sm" for="addonDur1">1 bulan</label>
                            <input type="radio" class="btn-check" name="addon_duration_display" id="addonDurEnd" value="end">
                            <label class="btn btn-outline-success btn-sm" for="addonDurEnd">
                                Sampai {{ $addonOffer['base']->end_date->translatedFormat('d M Y') }}
                            </label>
                        </div>
                    </div>

                    <form action="{{ route('subscription.addon') }}" method="POST" id="addonForm">
                        @csrf
                        <input type="hidden" name="duration" id="addon_duration" value="1">

                        <div class="row g-3">
                            @foreach($addonOffer['rates'] as $type => $rate)
                                @php
                                    $remainingUnits = $addonOffer['remaining_units'][$type] ?? 0;
                                    $quotaKey = ['storage' => 'storage', 'asset' => 'assets', 'user' => 'users'][$type];
                                    $currentLimit = $quota[$quotaKey] ?? null;
                                    $currentUsed = $usage[$quotaKey] ?? 0;
                                @endphp
                                <div class="{{ count($addonOffer['rates']) === 1 ? 'col-12' : 'col-md-4' }}">
                                    <div class="addon-tile h-100" data-type="{{ $type }}">
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <span class="addon-tile-icon"><i class="bi {{ $addonIcons[$type] ?? 'bi-plus' }}"></i></span>
                                            <div>
                                                <div class="fw-semibold">{{ $rate['name'] }}</div>
                                                <small class="text-muted">per {{ $rate['unit_label'] }}</small>
                                            </div>
                                        </div>

                                        <div class="small text-muted mb-2">
                                            Terpakai {{ $formatQuota($type, $currentUsed) }}
                                            / {{ $formatQuota($type, $currentLimit) }}
                                        </div>

                                        @if($currentLimit === null && $type !== 'storage')
                                            <div class="small text-success"><i class="bi bi-infinity me-1"></i>Paket Anda sudah unlimited</div>
                                        @elseif($remainingUnits > 0)
                                            <input type="hidden" name="{{ $type }}_units" value="0" class="addon-units-input">
                                            <input type="range" class="form-range addon-slider" min="0"
                                                   max="{{ $remainingUnits }}" step="1" value="0"
                                                   aria-label="Jumlah {{ $rate['name'] }}">
                                            <div class="d-flex justify-content-between align-items-baseline">
                                                <strong class="addon-amount">+0</strong>
                                                <small class="text-muted addon-price">Rp0</small>
                                            </div>
                                        @else
                                            <div class="small text-muted">Batas maksimal tambahan sudah tercapai.</div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="sub-price-box d-flex flex-wrap justify-content-between align-items-center gap-3 mt-4">
                            <div>
                                <div class="small text-muted" id="addonPeriodLabel">-</div>
                                <div class="fs-4 fw-bold text-success" id="addonTotal">Rp0</div>
                            </div>
                            <button type="submit" class="btn btn-success px-4" id="btnAddon" disabled>
                                <i class="bi bi-plus-circle me-1"></i> Beli Kapasitas
                            </button>
                        </div>
                    </form>

                    @if(count($activeAddons))
                        <div class="mt-4">
                            <div class="small fw-semibold text-muted mb-2">Kapasitas tambahan aktif</div>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($activeAddons as $addon)
                                    <span class="badge rounded-pill bg-light text-dark border px-3 py-2">
                                        <i class="bi {{ $addonIcons[$addon->type] ?? 'bi-plus' }} me-1"></i>
                                        +{{ $svc->describeAddon($addon->type, (int) $addon->total_value) }}
                                        <span class="text-muted">s/d {{ $addon->end_date->format('d M Y') }}</span>
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- AVAILABLE PLANS --}}
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4 p-lg-5">

                <div class="text-center mb-4">
                    <span class="badge rounded-pill bg-primary-subtle text-primary px-3 py-2 mb-2">
                        <i class="bi bi-lightning-charge-fill me-1"></i> Upgrade Your Experience
                    </span>
                    <h4 class="fw-bold mb-2">Pilih Paket Terbaik untuk Perusahaan Anda</h4>
                    <p class="text-muted mb-0">
                        Sesuaikan paket dengan kebutuhan user, asset, dan kapasitas penyimpanan perusahaan.
                    </p>
                </div>

                {{-- BILLING CYCLE --}}
                <div class="d-flex justify-content-center mb-5">
                    <div class="billing-wrapper">
                        <div class="form-check">
                            <input class="form-check-input"
                                   type="radio"
                                   name="billing_cycle_display"
                                   id="billing_monthly"
                                   value="monthly"
                                   checked>
                            <label class="form-check-label" for="billing_monthly">
                                <i class="bi bi-calendar-month me-1"></i> Monthly
                            </label>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input"
                                   type="radio"
                                   name="billing_cycle_display"
                                   id="billing_yearly"
                                   value="yearly">
                            <label class="form-check-label" for="billing_yearly">
                                <i class="bi bi-calendar-check me-1"></i> Yearly
                                <span class="badge bg-success ms-1">Save</span>
                            </label>
                        </div>
                    </div>
                </div>

                <form action="{{ route('subscription.renewdoku') }}"
                      method="POST"
                      id="subscriptionForm">
                    @csrf

                    <input type="hidden" name="billing_cycle" id="billing_cycle" value="monthly">
                    <input type="hidden" name="extra_storage_bytes" id="extra_storage_bytes" value="0">

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
                                    $baseStorageBytes = $resolvePlanStorage($plan);
                                @endphp

                                <div class="col-12 col-md-6 col-xl-4">
                                    <div class="card subscription-card {{ $planClass }}"
                                         data-plan-card="{{ $plan->id }}">
                                        @if($isPopular)
                                            <div class="popular-badge">POPULAR</div>
                                        @endif

                                        <div class="card-body p-4 d-flex flex-column">
                                            <div class="plan-top-icon">
                                                <i class="bi {{ $planIcon }}"></i>
                                            </div>

                                            <div class="form-check mb-3">
                                                <input class="form-check-input plan-radio"
                                                       type="radio"
                                                       name="plan_id"
                                                       value="{{ $plan->id }}"
                                                       id="plan_{{ $plan->id }}"
                                                       data-plan-name="{{ $plan->plan_name }}"
                                                       data-monthly="{{ (float) $plan->price_monthly }}"
                                                       data-yearly="{{ (float) $plan->price_yearly }}"
                                                       data-base-storage="{{ $baseStorageBytes === null ? 'unlimited' : (int) $baseStorageBytes }}">

                                                <label class="form-check-label fw-bold fs-5"
                                                       for="plan_{{ $plan->id }}">
                                                    {{ $plan->plan_name }}
                                                </label>
                                            </div>

                                            <div class="plan-description mb-3">
                                                {{ $plan->description ?: 'Paket subscription untuk mendukung kebutuhan perusahaan Anda.' }}
                                            </div>

                                            <div class="plan-price mb-3">
                                                <div class="monthly-price">
                                                    <span class="price-value">
                                                        Rp{{ number_format($plan->price_monthly, 0, ',', '.') }}
                                                    </span>
                                                    <span class="price-period">/ bulan</span>
                                                </div>

                                                <div class="yearly-price d-none">
                                                    <span class="price-value">
                                                        Rp{{ number_format($plan->price_yearly, 0, ',', '.') }}
                                                    </span>
                                                    <span class="price-period">/ tahun</span>
                                                </div>
                                            </div>

                                            <hr>

                                            <div class="mb-4">
                                                <div class="plan-feature">
                                                    <i class="bi bi-check-circle-fill"></i>
                                                    <span>
                                                        @if($plan->max_users !== null)
                                                            Maksimal {{ $plan->max_users }} Users
                                                        @else
                                                            Unlimited Users
                                                        @endif
                                                    </span>
                                                </div>

                                                <div class="plan-feature">
                                                    <i class="bi bi-check-circle-fill"></i>
                                                    <span>
                                                        @if($plan->max_assets !== null)
                                                            Maksimal {{ $plan->max_assets }} Assets
                                                        @else
                                                            Unlimited Assets
                                                        @endif
                                                    </span>
                                                </div>

                                                <div class="plan-feature">
                                                    <i class="bi bi-check-circle-fill"></i>
                                                    <span>
                                                        Storage dasar:
                                                        {{ $formatStorageServer($baseStorageBytes) }}
                                                    </span>
                                                </div>

                                                <div class="plan-feature">
                                                    <i class="bi bi-check-circle-fill"></i>
                                                    <span>Asset Management</span>
                                                </div>

                                                <div class="plan-feature">
                                                    <i class="bi bi-check-circle-fill"></i>
                                                    <span>Maintenance Management</span>
                                                </div>
                                            </div>

                                            <div class="mt-auto">
                                                <label class="btn btn-outline-primary select-plan-button w-100"
                                                       for="plan_{{ $plan->id }}">
                                                    <i class="bi bi-check2-circle me-1"></i> Pilih Paket
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

                    {{-- CUSTOM STORAGE --}}
                    <div class="storage-addon-card p-4 mt-4">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                            <div>
                                <span class="badge bg-primary-subtle text-primary mb-2">
                                    <i class="bi bi-hdd-stack me-1"></i> Custom Storage
                                </span>
                                <h5 class="fw-bold mb-1">Tambah Kapasitas Penyimpanan</h5>
                                <p class="text-muted small mb-0">
                                    Storage bawaan paket tetap tersedia. Pilih tambahan sesuai kebutuhan.
                                </p>
                            </div>

                            <div class="text-end">
                                <div class="small text-muted">Storage tambahan</div>
                                <div class="storage-value text-primary" id="extraStorageLabel">0 MB</div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <input type="range"
                                   class="form-range storage-range"
                                   id="storageSlider"
                                   min="0"
                                   max="{{ $storageAddonMaxSteps }}"
                                   step="1"
                                   value="0"
                                   aria-label="Pilih tambahan storage">

                            <div class="d-flex justify-content-between small text-muted">
                                <span>0 MB</span>
                                <span>{{ $formatStorageServer($storageAddonMaxBytes) }}</span>
                            </div>

                            <div class="small text-muted mt-2">
                                Setiap langkah menambah
                                {{ number_format($storageAddonUnitBytes / (1024 * 1024), 0, ',', '.') }} MB.
                                Maksimal tambahan {{ $formatStorageServer($storageAddonMaxBytes) }}.
                            </div>
                        </div>

                        <div class="row g-3 mt-2">
                            <div class="col-12 col-md-4">
                                <div class="storage-summary-box">
                                    <small class="text-muted d-block mb-1">Storage dasar paket</small>
                                    <strong id="baseStorageLabel">-</strong>
                                </div>
                            </div>

                            <div class="col-12 col-md-4">
                                <div class="storage-summary-box">
                                    <small class="text-muted d-block mb-1">Storage tambahan</small>
                                    <strong id="extraStorageSummary">0 MB</strong>
                                </div>
                            </div>

                            <div class="col-12 col-md-4">
                                <div class="storage-summary-box">
                                    <small class="text-muted d-block mb-1">Total kapasitas</small>
                                    <strong id="totalStorageLabel">-</strong>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-4 pt-3 border-top">
                            <div>
                                <div class="text-muted small">Biaya tambahan storage</div>
                                <strong class="fs-5 text-primary" id="storageAddonPriceLabel">Rp0</strong>
                                <div class="small text-muted" id="storageAddonPeriod">Per bulan</div>
                            </div>

                            <div class="text-md-end">
                                <div class="small text-muted">
                                    Rp{{ number_format($storageAddonPriceMonthly, 0, ',', '.') }}
                                    per {{ $formatStorageServer($storageAddonUnitBytes) }} per bulan
                                </div>
                                <div class="small text-muted">
                                    Harga tahunan:
                                    Rp{{ number_format($storageAddonPriceYearly, 0, ',', '.') }}
                                    per unit
                                </div>
                                <div class="small text-muted">
                                    Maksimal tambahan {{ $formatStorageServer($storageAddonMaxBytes) }}
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- TOTAL PAYMENT --}}
                    <div class="subscription-total-card p-4 mt-3">
                        <div class="row align-items-center g-3">
                            <div class="col">
                                <div class="fw-bold fs-5">Total Pembayaran</div>
                                <div class="small text-muted" id="subscriptionTotalPeriod">
                                    Harga paket + storage tambahan per bulan
                                </div>
                                <div class="small text-muted mt-1" id="subscriptionTotalBreakdown">
                                    Pilih paket untuk melihat rincian harga.
                                </div>
                            </div>
                            <div class="col-auto text-end">
                                <div class="fs-3 fw-bold text-primary" id="subscriptionTotalPrice">
                                    Rp0
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- PERIODE PAKET BARU --}}
                    @if($queuedSubscription)
                        <div class="alert alert-warning d-flex gap-2 mt-3 mb-0">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            <div class="small">
                                Anda sudah memiliki paket berikutnya
                                (<strong>{{ $queuedSubscription->plan->plan_name ?? '-' }}</strong>,
                                mulai {{ $queuedSubscription->start_date->translatedFormat('d F Y') }}).
                                Pembelian paket baru dapat dilakukan setelah paket tersebut mulai berjalan.
                            </div>
                        </div>
                    @elseif($periodPreview)
                        <div class="alert alert-info d-flex gap-2 mt-3 mb-0" id="periodPreview"
                             data-monthly-start="{{ $periodPreview['monthly']['start']->translatedFormat('d F Y') }}"
                             data-monthly-end="{{ $periodPreview['monthly']['end']->translatedFormat('d F Y') }}"
                             data-yearly-start="{{ $periodPreview['yearly']['start']->translatedFormat('d F Y') }}"
                             data-yearly-end="{{ $periodPreview['yearly']['end']->translatedFormat('d F Y') }}"
                             data-queued="{{ $periodPreview['monthly']['queued'] ? '1' : '0' }}">
                            <i class="bi bi-calendar-event"></i>
                            <div class="small">
                                @if($periodPreview['monthly']['queued'])
                                    Paket Anda saat ini masih aktif sampai
                                    <strong>{{ $paidSubscription?->end_date?->translatedFormat('d F Y') }}</strong>.
                                    Paket baru akan <strong>masuk antrean</strong> dan aktif mulai
                                    <strong id="periodPreviewStart">{{ $periodPreview['monthly']['start']->translatedFormat('d F Y') }}</strong>
                                    s/d <strong id="periodPreviewEnd">{{ $periodPreview['monthly']['end']->translatedFormat('d F Y') }}</strong>.
                                    Kuota asset &amp; storage paket baru berlaku mulai tanggal tersebut.
                                @else
                                    Paket baru <strong>langsung aktif</strong> setelah pembayaran berhasil,
                                    mulai <strong id="periodPreviewStart">{{ $periodPreview['monthly']['start']->translatedFormat('d F Y') }}</strong>
                                    s/d <strong id="periodPreviewEnd">{{ $periodPreview['monthly']['end']->translatedFormat('d F Y') }}</strong>.
                                    @if($currentSubscription && $currentSubscription->status === 'active' && $currentSubscription->billing_cycle === 'trial')
                                        Masa trial akan ditutup.
                                    @endif
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- SUBMIT --}}
                    <div class="d-flex flex-column flex-md-row align-items-center justify-content-between gap-3 mt-5 pt-4 border-top">
                        <div class="text-muted small text-center text-md-start">
                            <i class="bi bi-lock-fill me-1"></i>
                            Pembayaran aman melalui DOKU
                            <br>
                            <i class="bi bi-info-circle me-1"></i>
                            Tanggal final paket dihitung saat pembayaran dikonfirmasi.
                        </div>

                        <button type="submit"
                                class="btn btn-primary btn-subscription-submit"
                                id="btnSubmitSubscription"
                                @disabled($queuedSubscription)>
                            <i class="bi bi-arrow-right-circle me-1"></i>
                            Lanjutkan Pembayaran
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="{{ config('services.doku.checkout_js') }}"></script>

@if(session('doku_checkout_url'))
    <div id="dokuCheckoutConfig"
         data-payment-url="{{ session('doku_checkout_url') }}"
         hidden></div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const config = document.getElementById('dokuCheckoutConfig');
            const paymentUrl = config ? config.dataset.paymentUrl : null;

            if (paymentUrl && typeof loadJokulCheckout === 'function') {
                loadJokulCheckout(paymentUrl);
            }
        });
    </script>
@endif

<script>
document.addEventListener('DOMContentLoaded', function () {
    const billingMonthly = document.getElementById('billing_monthly');
    const billingYearly = document.getElementById('billing_yearly');
    const billingCycleInput = document.getElementById('billing_cycle');
    const subscriptionForm = document.getElementById('subscriptionForm');
    const submitButton = document.getElementById('btnSubmitSubscription');

    const planCards = [...document.querySelectorAll('.subscription-card')];
    const planRadios = [...document.querySelectorAll('input[name="plan_id"]')];

    const storageSlider = document.getElementById('storageSlider');
    const extraStorageInput = document.getElementById('extra_storage_bytes');

    // Semua konfigurasi storage berasal dari database.
    const storageUnitBytes = Number('{{ $storageAddonUnitBytes }}');
    const storagePriceMonthly = Number('{{ $storageAddonPriceMonthly }}');
    const storagePriceYearly = Number('{{ $storageAddonPriceYearly }}');
    const maxExtraSteps = Number('{{ $storageAddonMaxSteps }}');


    const bytesPerGb = 1024 * 1024 * 1024;
    const bytesPerMb = 1024 * 1024;

    const rupiah = value =>
        'Rp' + Math.round(value).toLocaleString('id-ID');

    function formatNumber(value, digits = 2) {
        return Number(value).toLocaleString('id-ID', {
            minimumFractionDigits: 0,
            maximumFractionDigits: digits
        });
    }

    function formatStorage(bytes) {
        if (bytes === null) return 'Unlimited';

        bytes = Math.max(0, Number(bytes) || 0);

        if (bytes === 0) return '0 MB';

        if (bytes >= bytesPerGb) {
            return formatNumber(bytes / bytesPerGb, 2) + ' GB';
        }

        return formatNumber(bytes / bytesPerMb, 0) + ' MB';
    }

    function selectedPlan() {
        return planRadios.find(radio => radio.checked) || null;
    }

    function isYearly() {
        return Boolean(billingYearly && billingYearly.checked);
    }

    function getPlanPrice(plan, yearly) {
        if (!plan) return 0;

        return Math.max(
            0,
            Number(yearly ? plan.dataset.yearly : plan.dataset.monthly) || 0
        );
    }

    function getStorageSteps() {
        return Math.max(
            0,
            Math.min(
                maxExtraSteps,
                Math.round(Number(storageSlider?.value || 0))
            )
        );
    }

    function getStoragePrice(steps, yearly) {
        return steps * (yearly ? storagePriceYearly : storagePriceMonthly);
    }

    function updatePrices() {
        const yearly = isYearly();

        if (billingCycleInput) {
            billingCycleInput.value = yearly ? 'yearly' : 'monthly';
        }

        document.querySelectorAll('.monthly-price').forEach(element => {
            element.classList.toggle('d-none', yearly);
        });

        document.querySelectorAll('.yearly-price').forEach(element => {
            element.classList.toggle('d-none', !yearly);
        });

        // Perkiraan periode paket baru mengikuti billing cycle.
        const preview = document.getElementById('periodPreview');
        const previewStart = document.getElementById('periodPreviewStart');
        const previewEnd = document.getElementById('periodPreviewEnd');

        if (preview && previewStart && previewEnd) {
            const key = yearly ? 'yearly' : 'monthly';
            previewStart.textContent = preview.dataset[key + 'Start'] || '-';
            previewEnd.textContent = preview.dataset[key + 'End'] || '-';
        }

        updateStorageAndTotal();
    }

    function updateSelectedPlan() {
        planCards.forEach(card => {
            const radio = card.querySelector('input[name="plan_id"]');

            card.classList.toggle(
                'selected',
                Boolean(radio && radio.checked)
            );
        });

        updateStorageAndTotal();
    }

    function updateStorageAndTotal() {
        const plan = selectedPlan();
        const yearly = isYearly();

        const steps = getStorageSteps();
        const extraBytes = steps * storageUnitBytes;
        const addonPrice = getStoragePrice(steps, yearly);

        if (extraStorageInput) {
            extraStorageInput.value = String(extraBytes);
        }

        document.getElementById('extraStorageLabel').textContent =
            formatStorage(extraBytes);

        document.getElementById('extraStorageSummary').textContent =
            formatStorage(extraBytes);

        document.getElementById('storageAddonPriceLabel').textContent =
            rupiah(addonPrice);

        document.getElementById('storageAddonPeriod').textContent =
            yearly ? 'Untuk 1 tahun' : 'Per bulan';

        let baseStorage = null;
        let planPrice = 0;
        let planName = 'Belum dipilih';

        if (plan) {
            const rawBase = plan.dataset.baseStorage;

            baseStorage = rawBase === 'unlimited'
                ? null
                : Math.max(0, Number(rawBase) || 0);

            planPrice = getPlanPrice(plan, yearly);
            planName = plan.dataset.planName || 'Subscription';
        }

        document.getElementById('baseStorageLabel').textContent =
            !plan ? '-' : formatStorage(baseStorage);

        const totalStorage = baseStorage === null
            ? null
            : baseStorage + extraBytes;

        document.getElementById('totalStorageLabel').textContent =
            !plan ? '-' : formatStorage(totalStorage);

        const totalPrice = planPrice + addonPrice;

        document.getElementById('subscriptionTotalPrice').textContent =
            rupiah(totalPrice);

        document.getElementById('subscriptionTotalPeriod').textContent =
            yearly
                ? 'Harga paket + storage tambahan per tahun'
                : 'Harga paket + storage tambahan per bulan';

        document.getElementById('subscriptionTotalBreakdown').textContent =
            plan
                ? planName + ': ' + rupiah(planPrice) +
                  ' + storage: ' + rupiah(addonPrice)
                : 'Pilih paket untuk melihat rincian harga.';
    }

    if (billingMonthly) {
        billingMonthly.addEventListener('change', updatePrices);
    }

    if (billingYearly) {
        billingYearly.addEventListener('change', updatePrices);
    }

    planRadios.forEach(radio => {
        radio.addEventListener('change', updateSelectedPlan);
    });

    planCards.forEach(card => {
        card.addEventListener('click', function (event) {
            if (
                event.target.closest('label') ||
                event.target.tagName === 'INPUT'
            ) {
                return;
            }

            const radio = card.querySelector('input[name="plan_id"]');

            if (radio) {
                radio.checked = true;
                radio.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
    });

    if (storageSlider) {
        storageSlider.min = '0';
        storageSlider.max = String(maxExtraSteps);
        storageSlider.step = '1';
        storageSlider.addEventListener('input', updateStorageAndTotal);
    }

    if (subscriptionForm) {
        subscriptionForm.addEventListener('submit', function (event) {
            event.preventDefault();

            const plan = selectedPlan();

            if (!plan) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Pilih Paket',
                    text: 'Silakan pilih subscription plan terlebih dahulu.'
                });
                return;
            }

            const yearly = isYearly();
            const cycle = yearly ? 'Yearly' : 'Monthly';
            const planPrice = getPlanPrice(plan, yearly);
            const steps = getStorageSteps();
            const addonPrice = getStoragePrice(steps, yearly);
            const totalPrice = planPrice + addonPrice;
            const extraBytes = steps * storageUnitBytes;

            if (extraStorageInput) {
                extraStorageInput.value = String(extraBytes);
            }

            Swal.fire({
                title: 'Konfirmasi Subscription',
                html:
                    '<div class="text-start">' +
                        '<p class="mb-2">Paket yang dipilih:</p>' +
                        '<div class="fw-bold fs-5">' +
                            (plan.dataset.planName || 'Subscription') +
                        '</div>' +
                        '<div class="text-muted mb-1">Billing Cycle: ' + cycle + '</div>' +
                        (function () {
                            const p = document.getElementById('periodPreview');
                            if (!p) {
                                return '<div class="mb-3"></div>';
                            }
                            const key = yearly ? 'yearly' : 'monthly';
                            const queued = p.dataset.queued === '1';
                            return '<div class="small mb-3 ' + (queued ? 'text-warning-emphasis' : 'text-success') + '">' +
                                (queued ? 'Masuk antrean, aktif ' : 'Aktif ') +
                                (p.dataset[key + 'Start'] || '') + ' s/d ' + (p.dataset[key + 'End'] || '') +
                                '</div>';
                        })() +
                        '<div class="d-flex justify-content-between mb-2">' +
                            '<span>Harga paket</span><strong>' + rupiah(planPrice) + '</strong>' +
                        '</div>' +
                        '<div class="d-flex justify-content-between mb-2">' +
                            '<span>Storage tambahan</span><strong>' +
                                formatStorage(extraBytes) + '</strong>' +
                        '</div>' +
                        '<div class="d-flex justify-content-between mb-2">' +
                            '<span>Biaya storage</span><strong>' + rupiah(addonPrice) + '</strong>' +
                        '</div>' +
                        '<hr>' +
                        '<div class="d-flex justify-content-between align-items-center">' +
                            '<span class="fw-bold">Total</span>' +
                            '<strong class="text-primary fs-4">' +
                                rupiah(totalPrice) + '</strong>' +
                        '</div>' +
                        '<hr>' +
                        '<small class="text-muted">' +
                            'Nominal final akan divalidasi oleh server sebelum pembayaran DOKU.' +
                        '</small>' +
                    '</div>',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: '<i class="bi bi-credit-card me-1"></i> Lanjutkan',
                cancelButtonText: 'Batal',
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
                        'Menghubungkan ke DOKU...';

                    subscriptionForm.submit();
                }
            });
        });
    }

    if (!selectedPlan() && planRadios.length > 0) {
        planRadios[0].checked = true;
    }

    updatePrices();
    updateSelectedPlan();
    /*
    |--------------------------------------------------------------------------
    | UPGRADE SEKARANG
    |--------------------------------------------------------------------------
    | Angka di sini untuk tampilan; nominal final dihitung ulang server.
    */
    const upgradeForm = document.getElementById('upgradeForm');

    if (upgradeForm) {
        const options = @json($upgradeOptions);
        const basePlanName = @json($paidSubscription?->plan?->plan_name ?? '');
        const baseEnd = @json($paidSubscription?->end_date?->translatedFormat('d M Y') ?? '');
        const planBox = document.getElementById('upgradePlanChoices');
        const durationBox = document.getElementById('upgradeDurationChoices');
        const timeline = document.getElementById('upgradeTimeline');
        const planInput = document.getElementById('upgrade_plan_id');
        const durationInput = document.getElementById('upgrade_duration');
        const upgradeButton = document.getElementById('btnUpgrade');

        const esc = value => String(value ?? '').replace(/[&<>"']/g, c => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        }[c]));

        const durationLabel = months => months === null ? 'Sampai akhir paket' : months + ' bulan';
        const plansSeen = [...new Map(options.map(o => [o.plan_id, o])).values()];

        let selectedPlan = plansSeen.length ? plansSeen[0].plan_id : null;
        let selectedMonths = null;

        function currentOption() {
            return options.find(o => o.plan_id === selectedPlan && o.months === selectedMonths) || null;
        }

        function renderPlans() {
            planBox.innerHTML = plansSeen.map(p => `
                <button type="button" class="btn btn-sm ${p.plan_id === selectedPlan ? 'btn-primary' : 'btn-outline-primary'} upgrade-plan-btn"
                        data-plan="${p.plan_id}">
                    ${esc(p.plan_name)}
                    <small class="d-block opacity-75">
                        ${p.max_assets === null ? 'Unlimited' : esc(p.max_assets)} asset ·
                        ${formatStorage(p.max_storage_bytes)}
                    </small>
                </button>
            `).join('');
        }

        function renderDurations() {
            const durations = options.filter(o => o.plan_id === selectedPlan);

            if (!durations.some(o => o.months === selectedMonths)) {
                selectedMonths = durations.length ? durations[0].months : null;
            }

            durationBox.innerHTML = durations.map(o => `
                <button type="button" class="btn btn-sm ${o.months === selectedMonths ? 'btn-dark' : 'btn-outline-secondary'} upgrade-duration-btn"
                        data-months="${o.months === null ? 'end' : o.months}">
                    ${esc(durationLabel(o.months))}
                    <small class="d-block opacity-75">${rupiah(o.price)}</small>
                </button>
            `).join('');
        }

        function renderSummary() {
            const o = currentOption();

            if (!o) {
                upgradeButton.disabled = true;
                return;
            }

            planInput.value = o.plan_id;
            durationInput.value = o.months === null ? 'end' : String(o.months);
            upgradeButton.disabled = false;

            document.getElementById('upgradeNewLabel').textContent =
                'Harga ' + o.plan_name + ' (' + o.days + ' hari)';
            document.getElementById('upgradeNewPrice').textContent = rupiah(o.new_price);
            document.getElementById('upgradeCredit').textContent = '−' + rupiah(o.credit);
            document.getElementById('upgradeTotal').textContent = rupiah(o.price);

            timeline.innerHTML = `
                <div class="sub-timeline-item active">
                    <div class="sub-timeline-dot"></div>
                    <div>
                        <div class="fw-semibold">${esc(o.plan_name)}</div>
                        <small class="text-muted">${esc(o.start)} s/d ${esc(o.end)} · aktif setelah pembayaran</small>
                    </div>
                </div>
                ${o.revert_date ? `
                <div class="sub-timeline-item">
                    <div class="sub-timeline-dot"></div>
                    <div>
                        <div class="fw-semibold">Kembali ke ${esc(basePlanName)}</div>
                        <small class="text-muted">${esc(o.revert_date)} s/d ${esc(baseEnd)} · otomatis</small>
                        <div class="small text-warning-emphasis mt-1">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            Kuota kembali ke paket ${esc(basePlanName)}. Data tetap aman, penambahan diblokir jika melebihi kuota.
                        </div>
                    </div>
                </div>` : `
                <div class="sub-timeline-item">
                    <div class="sub-timeline-dot"></div>
                    <div>
                        <div class="fw-semibold">Berlaku sampai akhir paket</div>
                        <small class="text-muted">Tidak kembali ke ${esc(basePlanName)}</small>
                    </div>
                </div>`}
            `;
        }

        function renderAll() {
            renderPlans();
            renderDurations();
            renderSummary();
        }

        planBox.addEventListener('click', function (event) {
            const btn = event.target.closest('.upgrade-plan-btn');
            if (!btn) return;
            selectedPlan = Number(btn.dataset.plan);
            renderAll();
        });

        durationBox.addEventListener('click', function (event) {
            const btn = event.target.closest('.upgrade-duration-btn');
            if (!btn) return;
            selectedMonths = btn.dataset.months === 'end' ? null : Number(btn.dataset.months);
            renderAll();
        });

        upgradeForm.addEventListener('submit', function (event) {
            event.preventDefault();
            const o = currentOption();
            if (!o) return;

            Swal.fire({
                title: 'Konfirmasi Upgrade',
                html:
                    '<div class="text-start">' +
                        '<div class="fw-bold fs-5 mb-1">' + esc(basePlanName) + ' → ' + esc(o.plan_name) + '</div>' +
                        '<div class="small text-muted mb-3">' + esc(o.start) + ' s/d ' + esc(o.end) + ' (' + o.days + ' hari)</div>' +
                        (o.revert_date
                            ? '<div class="small mb-3">Kembali ke ' + esc(basePlanName) + ' mulai <strong>' + esc(o.revert_date) + '</strong>.</div>'
                            : '') +
                        '<div class="d-flex justify-content-between"><span>Harga paket baru</span><strong>' + rupiah(o.new_price) + '</strong></div>' +
                        '<div class="d-flex justify-content-between"><span>Kredit</span><strong class="text-success">−' + rupiah(o.credit) + '</strong></div>' +
                        '<hr><div class="d-flex justify-content-between align-items-center"><span class="fw-bold">Bayar</span>' +
                        '<strong class="fs-4 text-primary">' + rupiah(o.price) + '</strong></div>' +
                    '</div>',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: '<i class="bi bi-credit-card me-1"></i> Bayar',
                cancelButtonText: 'Batal',
                reverseButtons: true,
                buttonsStyling: false,
                customClass: { confirmButton: 'btn btn-primary px-4 ms-2', cancelButton: 'btn btn-secondary px-4' }
            }).then(function (result) {
                if (result.isConfirmed) {
                    upgradeButton.disabled = true;
                    upgradeButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menghubungkan ke DOKU...';
                    upgradeForm.submit();
                }
            });
        });

        renderAll();
    }

    /*
    |--------------------------------------------------------------------------
    | TAMBAH KAPASITAS
    |--------------------------------------------------------------------------
    */
    const addonForm = document.getElementById('addonForm');

    if (addonForm) {
        const rates = @json($addonOffer['rates'] ?? []);
        const prices = @json($addonOffer['prices'] ?? []);
        const durationInput = document.getElementById('addon_duration');
        const totalLabel = document.getElementById('addonTotal');
        const periodLabel = document.getElementById('addonPeriodLabel');
        const addonButton = document.getElementById('btnAddon');

        const durationKey = () => durationInput.value === 'end' ? 'end' : '1';

        function describe(type, units) {
            const rate = rates[type];
            const total = units * rate.unit_value;
            if (type === 'storage') return '+' + formatStorage(total);
            return '+' + total.toLocaleString('id-ID') + (type === 'asset' ? ' asset' : ' user');
        }

        function update() {
            const key = durationKey();
            const info = prices[key] || { unit_price: {}, days: 0, end: '' };
            let total = 0;
            const lines = [];

            addonForm.querySelectorAll('.addon-tile').forEach(function (tile) {
                const type = tile.dataset.type;
                const slider = tile.querySelector('.addon-slider');
                if (!slider) return;

                const units = Number(slider.value || 0);
                const price = units * Number(info.unit_price[type] || 0);

                tile.querySelector('.addon-units-input').value = String(units);
                tile.querySelector('.addon-amount').textContent = describe(type, units);
                tile.querySelector('.addon-price').textContent = rupiah(price);
                tile.classList.toggle('is-selected', units > 0);

                if (units > 0) lines.push(describe(type, units));
                total += price;
            });

            totalLabel.textContent = rupiah(total);
            periodLabel.textContent = 'Aktif hari ini s/d ' + info.end + ' (' + info.days + ' hari)';
            addonButton.disabled = total <= 0;
            addonForm.dataset.summary = lines.join(', ');
            addonForm.dataset.total = String(total);
        }

        addonForm.querySelectorAll('.addon-slider').forEach(s => s.addEventListener('input', update));

        document.querySelectorAll('input[name="addon_duration_display"]').forEach(function (radio) {
            radio.addEventListener('change', function () {
                durationInput.value = this.value;
                update();
            });
        });

        addonForm.addEventListener('submit', function (event) {
            event.preventDefault();

            Swal.fire({
                title: 'Konfirmasi Tambah Kapasitas',
                html:
                    '<div class="text-start">' +
                        '<div class="mb-2">' + (addonForm.dataset.summary || '-') + '</div>' +
                        '<div class="small text-muted mb-3">' + periodLabel.textContent + '</div>' +
                        '<div class="d-flex justify-content-between align-items-center"><span class="fw-bold">Total</span>' +
                        '<strong class="fs-4 text-success">' + rupiah(Number(addonForm.dataset.total || 0)) + '</strong></div>' +
                    '</div>',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: '<i class="bi bi-credit-card me-1"></i> Bayar',
                cancelButtonText: 'Batal',
                reverseButtons: true,
                buttonsStyling: false,
                customClass: { confirmButton: 'btn btn-success px-4 ms-2', cancelButton: 'btn btn-secondary px-4' }
            }).then(function (result) {
                if (result.isConfirmed) {
                    addonButton.disabled = true;
                    addonButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menghubungkan ke DOKU...';
                    addonForm.submit();
                }
            });
        });

        update();
    }
});
</script>

@endsection
