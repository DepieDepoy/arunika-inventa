@extends('dashboard.layouts.wrapper')

@section('title', 'Dashboard')

@section('content')

<div class="container-fluid">


{{-- =====================================================
     HEADER
====================================================== --}}
<div class="row">
    <div class="col-12">

        <div class="card overflow-hidden">

            <div class="card-body position-relative">

                <div class="d-flex flex-wrap justify-content-between align-items-center">

                    <div>

                        <h3 class="mb-1 fw-bold">
                            Hello, {{ Auth::user()->name }} 👋
                        </h3>

                        <p class="mb-0 text-muted">
                            Welcome back to your asset management dashboard.
                        </p>

                    </div>


                    <div class="text-end mt-3 mt-md-0">

                        <div class="fw-semibold">
                            {{ now()->format('d F Y') }}
                        </div>

                        <small class="text-muted">
                            {{ Auth::user()->company->name ?? 'Company' }}
                        </small>

                    </div>

                </div>

            </div>

        </div>

    </div>
</div>


{{-- =====================================================
     SUMMARY CARDS
====================================================== --}}
<div class="row">

    {{-- TOTAL ASSETS --}}
    <div class="col-md-6 col-lg-3">

        <div class="card card-slide">

            <div class="card-body">

                <div class="d-flex justify-content-between align-items-center">

                    <div>

                        <p class="mb-2 text-muted">
                            Total Assets
                        </p>

                        <h3 class="mb-0 fw-bold">
                            {{ number_format($totalAssets) }}
                        </h3>

                    </div>

                    <div class="rounded-circle bg-soft-primary p-3">

                        <i class="iconoir-box fs-4 text-primary"></i>

                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- ACTIVE ASSETS --}}
    <div class="col-md-6 col-lg-3">

        <div class="card card-slide">

            <div class="card-body">

                <div class="d-flex justify-content-between align-items-center">

                    <div>

                        <p class="mb-2 text-muted">
                            Active Assets
                        </p>

                        <h3 class="mb-0 fw-bold">
                            {{ number_format($activeAssets) }}
                        </h3>

                    </div>

                    <div class="rounded-circle bg-soft-success p-3">

                        <i class="iconoir-check-circle fs-4 text-success"></i>

                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- MAINTENANCE --}}
    <div class="col-md-6 col-lg-3">

        <div class="card card-slide">

            <div class="card-body">

                <div class="d-flex justify-content-between align-items-center">

                    <div>

                        <p class="mb-2 text-muted">
                            Maintenance Due
                        </p>

                        <h3 class="mb-0 fw-bold">
                            {{ number_format($maintenanceDue) }}
                        </h3>

                    </div>

                    <div class="rounded-circle bg-soft-warning p-3">

                        <i class="iconoir-wrench fs-4 text-warning"></i>

                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- OVERDUE --}}
    <div class="col-md-6 col-lg-3">

        <div class="card card-slide">

            <div class="card-body">

                <div class="d-flex justify-content-between align-items-center">

                    <div>

                        <p class="mb-2 text-muted">
                            Maintenance Overdue
                        </p>

                        <h3 class="mb-0 fw-bold text-danger">
                            {{ number_format($maintenanceOverdue) }}
                        </h3>

                    </div>

                    <div class="rounded-circle bg-soft-danger p-3">

                        <i class="iconoir-warning-triangle fs-4 text-danger"></i>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>


{{-- =====================================================
     MAINTENANCE OVERVIEW + ASSET CONDITION
====================================================== --}}
<div class="row">

    {{-- MAINTENANCE OVERVIEW --}}
    <div class="col-lg-8">

        <div class="card">

            <div class="card-header d-flex justify-content-between align-items-center">

                <div>

                    <h4 class="card-title mb-1">
                        Maintenance Overview
                    </h4>

                    <p class="mb-0 text-muted">
                        Current maintenance schedule
                    </p>

                </div>


                <a
                    href="{{ route('maintenance.index') }}"
                    class="btn btn-sm btn-primary"
                >
                    View All
                </a>

            </div>


            <div class="card-body">

                <div class="row">

                    {{-- OVERDUE --}}
                    <div class="col-6 col-md-3 mb-3 mb-md-0">

                        <div class="p-3 rounded bg-soft-danger">

                            <div class="d-flex align-items-center mb-2">

                                <span class="badge bg-danger rounded-circle p-2 me-2">

                                    <i class="iconoir-warning-triangle"></i>

                                </span>

                                <span class="text-muted">
                                    Overdue
                                </span>

                            </div>

                            <h3 class="mb-0">
                                {{ $maintenanceOverdue }}
                            </h3>

                        </div>

                    </div>


                    {{-- TODAY --}}
                    <div class="col-6 col-md-3 mb-3 mb-md-0">

                        <div class="p-3 rounded bg-soft-warning">

                            <div class="d-flex align-items-center mb-2">

                                <span class="badge bg-warning rounded-circle p-2 me-2">

                                    <i class="iconoir-calendar"></i>

                                </span>

                                <span class="text-muted">
                                    Today
                                </span>

                            </div>

                            <h3 class="mb-0">
                                {{ $maintenanceToday }}
                            </h3>

                        </div>

                    </div>


                    {{-- THIS WEEK --}}
                    <div class="col-6 col-md-3">

                        <div class="p-3 rounded bg-soft-info">

                            <div class="d-flex align-items-center mb-2">

                                <span class="badge bg-info rounded-circle p-2 me-2">

                                    <i class="iconoir-calendar"></i>

                                </span>

                                <span class="text-muted">
                                    This Week
                                </span>

                            </div>

                            <h3 class="mb-0">
                                {{ $maintenanceThisWeek }}
                            </h3>

                        </div>

                    </div>


                    {{-- MY ASSETS --}}
                    <div class="col-6 col-md-3">

                        <div class="p-3 rounded bg-soft-primary">

                            <div class="d-flex align-items-center mb-2">

                                <span class="badge bg-primary rounded-circle p-2 me-2">

                                    <i class="iconoir-user"></i>

                                </span>

                                <span class="text-muted">
                                    My Assets
                                </span>

                            </div>

                            <h3 class="mb-0">
                                {{ $myAssets }}
                            </h3>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- ASSET CONDITION --}}
    <div class="col-lg-4">

        <div class="card h-100">

            <div class="card-header">

                <h4 class="card-title mb-1">
                    Asset Condition
                </h4>

                <p class="mb-0 text-muted">
                    Current asset condition
                </p>

            </div>


            <div class="card-body">

                @php

                    $conditionLabels = [
                        'new' => 'New',
                        'good' => 'Good',
                        'used' => 'Used',
                        'damaged' => 'Damaged',
                        'poor' => 'Poor',
                    ];

                @endphp


                @foreach($conditionLabels as $key => $label)

                    @php

                        $total = $assetConditions[$key] ?? 0;

                        $percentage = $totalAssets > 0
                            ? round(($total / $totalAssets) * 100)
                            : 0;

                    @endphp


                    <div class="mb-3">

                        <div class="d-flex justify-content-between mb-1">

                            <span>
                                {{ $label }}
                            </span>

                            <span class="fw-semibold">
                                {{ $total }}
                            </span>

                        </div>


                        <div
                            class="progress"
                            style="height: 6px;"
                        >

                            <div
                                class="progress-bar"
                                role="progressbar"
                                style="width: {{ $percentage }}%;"
                                aria-valuenow="{{ $percentage }}"
                                aria-valuemin="0"
                                aria-valuemax="100"
                            >
                            </div>

                        </div>

                    </div>

                @endforeach

            </div>

        </div>

    </div>

</div>


{{-- =====================================================
     UPCOMING MAINTENANCE
====================================================== --}}
<div class="row">

    <div class="col-12">

        <div class="card">

            <div class="card-header d-flex justify-content-between align-items-center">

                <div>

                    <h4 class="card-title mb-1">
                        Upcoming Maintenance
                    </h4>

                    <p class="mb-0 text-muted">
                        Assets that require scheduled maintenance
                    </p>

                </div>


                <a
                    href="{{ route('maintenance.index') }}"
                    class="btn btn-sm btn-soft-primary"
                >
                    View Maintenance
                </a>

            </div>


            <div class="card-body p-0">

                <div class="table-responsive">

                    <table class="table table-hover mb-0">

                        <thead>

                            <tr>

                                <th class="ps-4">
                                    Asset
                                </th>

                                <th>
                                    Category
                                </th>

                                <th>
                                    Responsible
                                </th>

                                <th>
                                    Maintenance Date
                                </th>

                                <th>
                                    Status
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            @forelse($upcomingMaintenance as $maintenance)

                                @php

                                    $asset = $maintenance->asset;

                                    $maintenanceDate = $maintenance->maintenance_date
                                        ? \Carbon\Carbon::parse(
                                            $maintenance->maintenance_date
                                        )
                                        : null;

                                    $days = $maintenanceDate
                                        ? now()->startOfDay()->diffInDays(
                                            $maintenanceDate,
                                            false
                                        )
                                        : null;

                                @endphp


                                <tr>

                                    {{-- ASSET --}}

                                    <td class="ps-4">

                                        @if($asset)

                                            <div class="fw-semibold">
                                                {{ $asset->asset_code }}
                                            </div>

                                            <small class="text-muted">
                                                {{ $asset->asset_name }}
                                            </small>

                                        @else

                                            <span class="text-muted">
                                                Asset tidak ditemukan
                                            </span>

                                        @endif

                                    </td>


                                    {{-- CATEGORY --}}

                                    <td>

                                        {{ $asset?->category?->name
                                            ?? $asset?->category?->category_name
                                            ?? '-' }}

                                    </td>


                                    {{-- RESPONSIBLE --}}

                                    <td>

                                        {{ $asset?->responsibleUser?->name
                                            ?? '-' }}

                                    </td>


                                    {{-- MAINTENANCE DATE --}}

                                    <td>

                                        @if($maintenanceDate)

                                            <div class="fw-semibold">

                                                {{ $maintenanceDate->format('d M Y') }}

                                            </div>

                                            <small class="text-muted">

                                                {{ $maintenanceDate->diffForHumans() }}

                                            </small>

                                        @else

                                            -

                                        @endif

                                    </td>


                                    {{-- STATUS --}}

                                    <td>

                                        @if($days === null)

                                            <span class="badge bg-secondary">
                                                Scheduled
                                            </span>

                                        @elseif($days < 0)

                                            <span class="badge bg-danger">
                                                Overdue
                                            </span>

                                        @elseif($days === 0)

                                            <span class="badge bg-warning">
                                                Today
                                            </span>

                                        @elseif($days === 1)

                                            <span class="badge bg-info">
                                                Tomorrow
                                            </span>

                                        @elseif($days > 1)

                                            <span class="badge bg-soft-primary text-primary">
                                                {{ $days }} days
                                            </span>

                                        @endif

                                    </td>

                                </tr>


                            @empty

                                <tr>

                                    <td
                                        colspan="5"
                                        class="text-center py-5"
                                    >

                                        <div class="text-muted">

                                            <i class="iconoir-check-circle fs-1 d-block mb-2"></i>

                                            No upcoming maintenance.

                                        </div>

                                    </td>

                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>

</div>


{{-- =====================================================
     RECENT ASSETS + MY MAINTENANCE
====================================================== --}}
<div class="row">

    {{-- RECENT ASSETS --}}
    <div class="col-lg-7">

        <div class="card">

            <div class="card-header d-flex justify-content-between align-items-center">

                <div>

                    <h4 class="card-title mb-1">
                        Recent Assets
                    </h4>

                    <p class="mb-0 text-muted">
                        Recently added assets
                    </p>

                </div>


                <a
                    href="{{ route('assets.index') }}"
                    class="btn btn-sm btn-soft-primary"
                >
                    View All
                </a>

            </div>


            <div class="card-body p-0">

                <div class="table-responsive">

                    <table class="table table-hover mb-0">

                        <thead>

                            <tr>

                                <th class="ps-4">
                                    Asset
                                </th>

                                <th>
                                    Category
                                </th>

                                <th>
                                    Responsible
                                </th>

                                <th>
                                    Condition
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            @forelse($recentAssets as $asset)

                                <tr>

                                    <td class="ps-4">

                                        <div class="fw-semibold">
                                            {{ $asset->asset_code }}
                                        </div>

                                        <small class="text-muted">
                                            {{ $asset->asset_name }}
                                        </small>

                                    </td>


                                    <td>

                                        {{ $asset->category->name ?? '-' }}

                                    </td>


                                    <td>

                                        {{ $asset->responsibleUser->name ?? '-' }}

                                    </td>


                                    <td>

                                        @php

                                            $condition = strtolower(
                                                $asset->asset_condition ?? ''
                                            );

                                        @endphp


                                        @if($condition === 'new')

                                            <span class="badge bg-soft-success text-success">
                                                New
                                            </span>

                                        @elseif($condition === 'good')

                                            <span class="badge bg-soft-primary text-primary">
                                                Good
                                            </span>

                                        @elseif($condition === 'used')

                                            <span class="badge bg-soft-info text-info">
                                                Used
                                            </span>

                                        @elseif($condition === 'damaged')

                                            <span class="badge bg-soft-danger text-danger">
                                                Damaged
                                            </span>

                                        @else

                                            <span class="badge bg-soft-secondary text-secondary">
                                                {{ ucfirst($asset->asset_condition ?? '-') }}
                                            </span>

                                        @endif

                                    </td>

                                </tr>


                            @empty

                                <tr>

                                    <td
                                        colspan="4"
                                        class="text-center py-4 text-muted"
                                    >

                                        No assets available.

                                    </td>

                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>


    {{-- MY UPCOMING MAINTENANCE --}}
    <div class="col-lg-5">

        <div class="card">

            <div class="card-header">

                <h4 class="card-title mb-1">
                    My Upcoming Maintenance
                </h4>

                <p class="mb-0 text-muted">
                    Maintenance for assets assigned to you
                </p>

            </div>


            <div class="card-body">

                @forelse($myUpcomingMaintenance as $maintenance)

                    @php

                        $asset = $maintenance->asset;

                        $date = $maintenance->maintenance_date
                            ? \Carbon\Carbon::parse(
                                $maintenance->maintenance_date
                            )
                            : null;

                        $days = $date
                            ? now()->startOfDay()->diffInDays(
                                $date,
                                false
                            )
                            : null;

                    @endphp


                    <div class="d-flex align-items-center mb-4">

                        <div class="rounded bg-soft-primary p-3">

                            <i class="iconoir-wrench fs-4 text-primary"></i>

                        </div>


                        <div class="ms-3 flex-grow-1">

                            @if($asset)

                                <h6 class="mb-1">
                                    {{ $asset->asset_name }}
                                </h6>

                                <small class="text-muted">
                                    {{ $asset->asset_code }}
                                </small>

                            @else

                                <h6 class="mb-1">
                                    Asset tidak ditemukan
                                </h6>

                            @endif


                            <div class="mt-1">

                                @if($days === null)

                                    <span class="badge bg-secondary">
                                        Scheduled
                                    </span>

                                @elseif($days < 0)

                                    <span class="badge bg-danger">
                                        Overdue
                                    </span>

                                @elseif($days === 0)

                                    <span class="badge bg-warning">
                                        Today
                                    </span>

                                @elseif($days === 1)

                                    <span class="badge bg-info">
                                        Tomorrow
                                    </span>

                                @else

                                    <span class="badge bg-soft-primary text-primary">
                                        {{ $date->format('d M Y') }}
                                    </span>

                                @endif

                            </div>

                        </div>

                    </div>


                @empty

                    <div class="text-center py-4">

                        <i class="iconoir-check-circle fs-1 text-success"></i>

                        <p class="mt-2 mb-0 text-muted">
                            No upcoming maintenance for your assets.
                        </p>

                    </div>

                @endforelse

            </div>

        </div>

    </div>

</div>


</div>

@endsection
