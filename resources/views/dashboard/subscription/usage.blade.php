
@extends('dashboard.layouts.wrapper')

@section('title', 'Usage & Limits')

@section('content')

@php
    $planName = $planName ?? 'Subscription';
    $subscriptionStatus = $subscriptionStatus ?? 'unknown';
    $subscriptionEndsAt = $subscriptionEndsAt ?? null;
    $usage = $usage ?? [];

    $metrics = [
        [
            'key' => 'users',
            'title' => 'Users',
            'description' => 'Jumlah user perusahaan',
            'used' => $usage['users']['used'] ?? 0,
            'limit' => $usage['users']['limit'] ?? null,
            'icon' => 'bi-people-fill',
            'icon_style' => 'users',
            'accent' => '#696cff',
        ],
        [
            'key' => 'assets',
            'title' => 'Assets',
            'description' => 'Jumlah aset terdaftar',
            'used' => $usage['assets']['used'] ?? 0,
            'limit' => $usage['assets']['limit'] ?? null,
            'icon' => 'bi-buildings-fill',
            'icon_style' => 'assets',
            'accent' => '#198754',
        ],
        [
            'key' => 'storage',
            'title' => 'Storage',
            'description' => 'Penyimpanan dokumen perusahaan',
            'used' => $usage['storage']['used'] ?? 0,
            'limit' => $usage['storage']['limit'] ?? null,
            'icon' => 'bi-database-fill',
            'icon_style' => 'storage',
            'accent' => '#f0ad32',
        ],
    ];

    $formatBytes = function ($bytes) {
        $bytes = max(0, (float) $bytes);

        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2, ',', '.') . ' GB';
        }

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2, ',', '.') . ' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2, ',', '.') . ' KB';
        }

        return number_format($bytes, 0, ',', '.') . ' B';
    };

    $formatMetric = function ($key, $value) use ($formatBytes) {
        return $key === 'storage'
            ? $formatBytes($value ?? 0)
            : number_format((float) ($value ?? 0), 0, ',', '.');
    };

    $getPercentage = function ($used, $limit) {
        if ($limit === null || (float) $limit <= 0) {
            return null;
        }

        return min(100, max(0, (int) round(
            ((float) $used / (float) $limit) * 100
        )));
    };

    $statusConfig = match (strtolower((string) $subscriptionStatus)) {
        'active' => [
            'label' => 'Active',
            'class' => 'status-active',
            'icon' => 'bi-check-circle-fill',
        ],
        'expired' => [
            'label' => 'Expired',
            'class' => 'status-expired',
            'icon' => 'bi-exclamation-circle-fill',
        ],
        'pending' => [
            'label' => 'Pending',
            'class' => 'status-pending',
            'icon' => 'bi-clock-fill',
        ],
        'cancelled', 'canceled' => [
            'label' => 'Cancelled',
            'class' => 'status-expired',
            'icon' => 'bi-x-circle-fill',
        ],
        default => [
            'label' => ucfirst((string) $subscriptionStatus),
            'class' => 'status-neutral',
            'icon' => 'bi-info-circle-fill',
        ],
    };
@endphp

<style>
    .usage-page-header {
        border: 0;
        border-radius: 16px;
        background: linear-gradient(120deg, #ffffff 0%, #f5f8ff 100%);
    }

    .usage-page-title {
        font-size: 1.35rem;
        font-weight: 750;
        letter-spacing: -.35px;
    }

    .usage-page-subtitle {
        font-size: .875rem;
        line-height: 1.7;
    }

    .usage-plan-card,
    .usage-summary-card,
    .usage-info-card {
        border: 0;
        border-radius: 16px;
        box-shadow: 0 5px 22px rgba(15, 23, 42, .055);
        transition: transform .22s ease, box-shadow .22s ease;
    }

    .usage-summary-card:hover,
    .usage-plan-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 28px rgba(15, 23, 42, .09);
    }

    .usage-summary-card {
        min-height: 265px;
        overflow: hidden;
        position: relative;
    }

    .usage-summary-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: var(--usage-accent, #696cff);
    }

    .usage-summary-card.users-card {
        --usage-accent: #696cff;
    }

    .usage-summary-card.assets-card {
        --usage-accent: #198754;
    }

    .usage-summary-card.storage-card {
        --usage-accent: #f0ad32;
    }

    .usage-icon {
        width: 58px;
        height: 58px;
        border-radius: 17px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 26px;
    }

    .usage-icon.users {
        color: #696cff;
        background: rgba(105, 108, 255, .12);
    }

    .usage-icon.assets {
        color: #239b56;
        background: rgba(40, 167, 69, .12);
    }

    .usage-icon.storage {
        color: #c98908;
        background: rgba(240, 173, 50, .17);
    }

    .usage-icon.plan {
        color: #696cff;
        background: rgba(105, 108, 255, .12);
    }

    .usage-icon.info {
        color: #0891b2;
        background: rgba(13, 202, 240, .12);
    }

    .usage-metric-label {
        color: #566a7f;
        font-size: .875rem;
        font-weight: 600;
    }

    .usage-metric-value {
        font-size: 1.9rem;
        line-height: 1.35;
        font-weight: 800;
        letter-spacing: -.7px;
        overflow-wrap: anywhere;
        color: #233446;
    }

    .usage-metric-limit {
        font-size: .9rem;
        color: #8592a3;
    }

    /* Progress bar */
    .usage-progress {
        display: block !important;
        width: 100%;
        height: 10px !important;
        padding: 0 !important;
        margin-top: 4px;
        border: 0;
        border-radius: 999px !important;
        overflow: hidden !important;
        background-color: #e9edf3 !important;
        box-shadow: inset 0 1px 2px rgba(15, 23, 42, .06);
    }

    .usage-progress-fill {
        display: block !important;
        width: 0;
        height: 100% !important;
        min-width: 0;
        max-width: 100%;
        padding: 0 !important;
        border: 0 !important;
        border-radius: 999px !important;
        opacity: 1 !important;
        transition: width .45s ease;
    }

    .usage-fill-success {
        background-color: #16a34a !important;
    }

    .usage-fill-warning {
        background-color: #f59e0b !important;
    }

    .usage-fill-danger {
        background-color: #dc2626 !important;
    }

    .usage-status {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 11px;
        border-radius: 8px;
        font-size: .78rem;
        font-weight: 700;
    }

    .status-active {
        background: rgba(40, 167, 69, .11);
        color: #198754;
    }

    .status-expired {
        background: rgba(220, 53, 69, .10);
        color: #dc3545;
    }

    .status-pending {
        background: rgba(255, 193, 7, .17);
        color: #946c00;
    }

    .status-neutral {
        background: rgba(108, 117, 125, .11);
        color: #6c757d;
    }

    .usage-plan-name {
        font-size: 1.55rem;
        font-weight: 800;
        letter-spacing: -.4px;
        overflow-wrap: anywhere;
    }

    .usage-info-card {
        background: linear-gradient(120deg, #f2fbff 0%, #f7f8ff 100%);
    }

    .usage-caption {
        font-size: .8rem;
        line-height: 1.6;
    }

    .usage-header-action .btn {
        border-radius: 10px;
        font-weight: 600;
        padding: 10px 16px;
    }

    @media (max-width: 767.98px) {
        .usage-page-header {
            align-items: flex-start !important;
            gap: 14px;
        }

        .usage-header-action,
        .usage-header-action .btn {
            width: 100%;
        }

        .usage-metric-value {
            font-size: 1.55rem;
        }

        .usage-summary-card {
            min-height: auto;
        }
    }
</style>

<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">

        {{-- PAGE HEADER --}}
        <div class="row">
            <div class="col-12 mb-4">
                <div class="card usage-page-header">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center usage-page-header">
                            <div>
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <i class="bi bi-speedometer2 text-primary fs-4"></i>
                                    <h5 class="usage-page-title mb-0">Usage &amp; Limits</h5>
                                </div>

                                <p class="usage-page-subtitle text-muted mb-0">
                                    Pantau pemakaian resource dan kapasitas paket perusahaan Anda.
                                </p>
                            </div>

                            <div class="usage-header-action">
                                <a href="{{ route('subscription.index') }}" class="btn btn-primary">
                                    <i class="bi bi-box-seam me-1"></i>
                                    Lihat Paket
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- CURRENT SUBSCRIPTION --}}
        <div class="row">
            <div class="col-12 mb-4">
                <div class="card usage-plan-card">
                    <div class="card-body p-4">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="usage-icon plan me-3">
                                        <i class="bi bi-award-fill"></i>
                                    </div>

                                    <div>
                                        <div class="text-muted small mb-1">
                                            Paket Subscription Saat Ini
                                        </div>
                                        <h4 class="usage-plan-name mb-0">{{ $planName }}</h4>
                                    </div>
                                </div>

                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <span class="usage-status {{ $statusConfig['class'] }}">
                                        <i class="bi {{ $statusConfig['icon'] }}"></i>
                                        {{ $statusConfig['label'] }}
                                    </span>

                                    @if($subscriptionEndsAt)
                                        <span class="usage-status status-neutral">
                                            <i class="bi bi-calendar-event"></i>
                                            Berakhir
                                            {{ \Carbon\Carbon::parse($subscriptionEndsAt)->format('d M Y') }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                                <a href="{{ route('subscription.index') }}" class="btn btn-outline-primary">
                                    <i class="bi bi-gear me-1"></i>
                                    Kelola Subscription
                                    <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- USAGE METRICS --}}
        <div class="row">
            @foreach($metrics as $metric)
                @php
                    $used = max(0, (float) $metric['used']);
                    $limit = $metric['limit'];

                    $isUnlimited = $limit === null;
                    $percentage = $getPercentage($used, $limit);

                    $isExceeded = !$isUnlimited
                        && (float) $limit > 0
                        && $used > (float) $limit;

                    $isNearLimit = !$isUnlimited
                        && !$isExceeded
                        && $percentage !== null
                        && $percentage >= 80;

                    $remaining = !$isUnlimited && (float) $limit > 0
                        ? max(0, (float) $limit - $used)
                        : null;

                    if ($isExceeded || ($percentage !== null && $percentage >= 95)) {
                        $barClass = 'usage-fill-danger';
                    } elseif ($percentage !== null && $percentage >= 80) {
                        $barClass = 'usage-fill-warning';
                    } else {
                        $barClass = 'usage-fill-success';
                    }
                @endphp

                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card usage-summary-card h-100 {{ $metric['key'] }}-card">
                        <div class="card-body p-4">

                            {{-- CARD TITLE + ICON --}}
                            <div class="d-flex align-items-start justify-content-between mb-4">
                                <div class="pe-2">
                                    <h5 class="usage-metric-label mb-2">
                                        {{ $metric['title'] }}
                                    </h5>
                                    <small class="text-muted">
                                        {{ $metric['description'] }}
                                    </small>
                                </div>

                                <div class="usage-icon {{ $metric['icon_style'] }}">
                                    <i class="bi {{ $metric['icon'] }}"></i>
                                </div>
                            </div>

                            {{-- USAGE NUMBER --}}
                            <div class="mb-3">
                                <div class="d-flex align-items-baseline flex-wrap gap-2">
                                    <span class="usage-metric-value">
                                        {{ $formatMetric($metric['key'], $used) }}
                                    </span>

                                    <span class="usage-metric-limit">
                                        /
                                        @if($isUnlimited)
                                            Unlimited
                                        @elseif((float) $limit > 0)
                                            {{ $formatMetric($metric['key'], $limit) }}
                                        @else
                                            Tidak tersedia
                                        @endif
                                    </span>
                                </div>

                                <small class="text-muted">
                                    @if($isUnlimited)
                                        Tanpa batas pemakaian
                                    @elseif($percentage !== null)
                                        {{ $percentage }}% dari kapasitas paket
                                    @else
                                        Batas paket belum valid
                                    @endif
                                </small>
                            </div>

                            {{-- PROGRESS BAR --}}
                            @if($isUnlimited)
                                <div class="d-flex align-items-center gap-2 mt-4">
                                    <i class="bi bi-infinity text-success fs-4"></i>
                                    <small class="text-success fw-semibold">
                                        Tidak ada batas yang ditentukan
                                    </small>
                                </div>

                            @elseif($percentage !== null)
                                <div
                                    class="usage-progress mb-3"
                                    role="progressbar"
                                    aria-label="{{ $metric['title'] }} usage"
                                    aria-valuenow="{{ $percentage }}"
                                    aria-valuemin="0"
                                    aria-valuemax="100"
                                >
                                    <div
                                        class="usage-progress-fill {{ $barClass }}"
                                        data-width="{{ $percentage }}"
                                    ></div>
                                </div>

                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    @if($isExceeded)
                                        <small class="text-danger fw-semibold">
                                            <i class="bi bi-exclamation-circle-fill me-1"></i>
                                            Melebihi batas
                                        </small>
                                    @elseif($percentage == 100)
                                        <small class="text-danger fw-semibold">
                                            <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                            Batas tercapai
                                        </small>
                                    @elseif($percentage >= 95)
                                        <small class="text-danger fw-semibold">
                                            <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                            Hampir mencapai batas
                                        </small>
                                    @elseif($isNearLimit)
                                        <small class="fw-semibold" style="color: #b77908;">
                                            <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                            Hampir mencapai batas
                                        </small>
                                    @else
                                        <small class="text-success fw-semibold">
                                            <i class="bi bi-check-circle me-1"></i>
                                            Masih tersedia
                                        </small>
                                    @endif

                                    <small class="text-muted text-end">
                                        Sisa:
                                        <strong>{{ $formatMetric($metric['key'], $remaining) }}</strong>
                                    </small>
                                </div>

                                @if($metric['key'] === 'storage' && $percentage >= 95)
                                    <div class="alert alert-danger py-2 px-3 mt-3 mb-0 small">
                                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                        Penyimpanan hampir penuh. Kelola file atau tingkatkan paket.
                                    </div>
                                @elseif($metric['key'] === 'storage' && $percentage >= 80)
                                    <div class="alert alert-warning py-2 px-3 mt-3 mb-0 small">
                                        <i class="bi bi-exclamation-circle-fill me-1"></i>
                                        Penggunaan storage sudah mencapai {{ $percentage }}%.
                                        Pertimbangkan untuk mengelola file atau meningkatkan paket.
                                    </div>
                                @endif

                            @else
                                <div class="mt-4">
                                    <small class="text-muted">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Batas paket tidak tersedia untuk ditampilkan.
                                    </small>
                                </div>
                            @endif

                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- INFORMATION --}}
        <div class="row">
            <div class="col-12 mb-4">
                <div class="card usage-info-card">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-start">
                            <div class="usage-icon info me-3">
                                <i class="bi bi-lightbulb-fill"></i>
                            </div>

                            <div>
                                <h6 class="fw-bold mb-2">Informasi Penggunaan</h6>
                                <p class="text-muted mb-0" style="line-height: 1.8;">
                                    Data pemakaian mengikuti resource perusahaan Anda.
                                    Batas Users, Assets, dan Storage bergantung pada konfigurasi
                                    paket subscription. Peringatan akan ditampilkan ketika storage
                                    mencapai 80% dan 95% dari kapasitas paket. Jika pemakaian
                                    mendekati kapasitas maksimum, Anda dapat meninjau paket yang
                                    tersedia melalui menu Subscribe.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

{{-- SET PROGRESS WIDTH FROM ACTUAL PERCENTAGE --}}
<script>
(function () {
    function updateUsageProgressBars() {
        document.querySelectorAll('.usage-progress-fill[data-width]').forEach(function (bar) {
            var percentage = parseFloat(bar.getAttribute('data-width'));

            if (!Number.isFinite(percentage)) {
                percentage = 0;
            }

            percentage = Math.max(0, Math.min(100, percentage));

            // Set lebar sesuai persentase pemakaian, bukan selalu 100%.
            bar.style.width = percentage + '%';
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', updateUsageProgressBars);
    } else {
        updateUsageProgressBars();
    }
})();
</script>

@endsection
