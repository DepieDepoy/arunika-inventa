
@extends('dashboard.layouts.wrapper')
@section('title', 'Detail Maintenance Request')

@section('content')

<style>
.swal2-container {
    z-index: 99999 !important;
}

.table-responsive {
    overflow: visible !important;
}

.dropdown-menu {
    z-index: 99999 !important;
}
</style>


<div class="content-wrapper">

<!-- Content -->
<div class="container-xxl flex-grow-1 container-p-y">

    <div class="row">

        <div class="col-xxl-12 mb-12 order-0">

            <div class="card">


                {{-- =====================================================
                     CARD HEADER
                ====================================================== --}}
                <div class="card-header d-flex justify-content-between align-items-center">

                    <div>

                        <h5 class="mb-0">
                            Detail Maintenance Request
                        </h5>

                        <small class="text-muted">
                            Informasi lengkap pengajuan dan riwayat pengerjaan aset.
                        </small>

                    </div>


                    <div class="d-flex gap-2">

                        <a
                            href="{{ route('maintenance.requests.index') }}"
                            class="btn btn-secondary"
                        >

                            <i class="fa fa-arrow-left"></i>

                            Kembali

                        </a>

                    </div>

                </div>


                {{-- =====================================================
                     CARD BODY
                ====================================================== --}}
                <div class="card-body">


                    {{-- =================================================
                         INFORMASI REQUEST
                    ================================================== --}}
                    <div class="mb-4">

                        <h6 class="fw-bold mb-1">
                            Informasi Request
                        </h6>

                        <small class="text-muted">
                            Informasi pengajuan maintenance asset.
                        </small>

                    </div>


                    <div class="row g-3 mb-4">


                        {{-- Kode Aset --}}
                        <div class="col-xl-3 col-lg-4 col-md-6">

                            <label class="text-muted small d-block">
                                Kode Aset
                            </label>

                            <div class="fw-bold">
                                {{ $maintenanceRequest->asset->asset_code ?? '-' }}
                            </div>

                        </div>


                        {{-- Nama Aset --}}
                        <div class="col-xl-3 col-lg-4 col-md-6">

                            <label class="text-muted small d-block">
                                Nama Aset
                            </label>

                            <div class="fw-bold">
                                {{ $maintenanceRequest->asset->name ?? '-' }}
                            </div>

                        </div>


                        {{-- Jenis Request --}}
                        <div class="col-xl-3 col-lg-4 col-md-6">

                            <label class="text-muted small d-block">
                                Jenis Request
                            </label>

                            @php

                                $requestTypeLabel = match (
                                    $maintenanceRequest->request_type
                                ) {

                                    'maintenance' => 'Maintenance',

                                    'repair' => 'Repair',

                                    'problem' => 'Problem / Issue',

                                    'other' => 'Other',

                                    default => ucfirst(
                                        $maintenanceRequest->request_type ?? '-'
                                    ),

                                };

                            @endphp

                            <span class="badge bg-secondary">
                                {{ $requestTypeLabel }}
                            </span>

                        </div>


                        {{-- Status --}}
                        <div class="col-xl-3 col-lg-4 col-md-6">

                            <label class="text-muted small d-block">
                                Status
                            </label>

                            @php

                                $statusClass = match (
                                    $maintenanceRequest->status
                                ) {

                                    'pending' => 'warning',

                                    'approved' => 'info',

                                    'in_progress' => 'primary',

                                    'completed' => 'success',

                                    'rejected',
                                    'cancelled' => 'danger',

                                    default => 'secondary',

                                };

                            @endphp

                            <span class="badge bg-{{ $statusClass }}">

                                {{
                                    ucwords(
                                        str_replace(
                                            '_',
                                            ' ',
                                            $maintenanceRequest->status
                                        )
                                    )
                                }}

                            </span>

                        </div>


                        {{-- Pemohon --}}
                        <div class="col-xl-3 col-lg-4 col-md-6">

                            <label class="text-muted small d-block">
                                Pemohon
                            </label>

                            <div>
                                {{ $maintenanceRequest->requester->name ?? '-' }}
                            </div>

                        </div>


                        {{-- Petugas --}}
                        <div class="col-xl-3 col-lg-4 col-md-6">

                            <label class="text-muted small d-block">
                                Petugas
                            </label>

                            <div>
                                {{ $maintenanceRequest->handler->name ?? 'Belum ditangani' }}
                            </div>

                        </div>


                        {{-- Deskripsi --}}
                        <div class="col-12">

                            <label class="text-muted small d-block mb-1">
                                Deskripsi Masalah
                            </label>

                            <div class="border rounded p-3 bg-light">

                                {!! nl2br(
                                    e(
                                        $maintenanceRequest->description ?? '-'
                                    )
                                ) !!}

                            </div>

                        </div>

                    </div>


                    <hr class="my-4">


                    {{-- =================================================
                         TIMELINE REQUEST
                    ================================================== --}}
                    <div class="mb-4">

                        <h6 class="fw-bold mb-1">
                            Timeline Request
                        </h6>

                        <small class="text-muted">
                            Riwayat waktu proses maintenance request.
                        </small>

                    </div>


                    <div class="row g-3 mb-4">


                        {{-- Pengajuan --}}
                        <div class="col-lg-4 col-md-4">

                            <div class="border rounded p-3 h-100">

                                <label class="text-muted small d-block">
                                    Waktu Pengajuan
                                </label>

                                <strong>

                                    {{
                                        optional(
                                            $maintenanceRequest->created_at
                                        )->format('d/m/Y H:i')
                                        ?? '-'
                                    }}

                                </strong>

                            </div>

                        </div>


                        {{-- Ditangani --}}
                        <div class="col-lg-4 col-md-4">

                            <div class="border rounded p-3 h-100">

                                <label class="text-muted small d-block">
                                    Mulai Ditangani
                                </label>

                                <strong>

                                    {{
                                        optional(
                                            $maintenanceRequest->handled_at
                                        )->format('d/m/Y H:i')
                                        ?? '-'
                                    }}

                                </strong>

                            </div>

                        </div>


                        {{-- Selesai --}}
                        <div class="col-lg-4 col-md-4">

                            <div class="border rounded p-3 h-100">

                                <label class="text-muted small d-block">
                                    Waktu Selesai
                                </label>

                                <strong>

                                    {{
                                        optional(
                                            $maintenanceRequest->completed_at
                                        )->format('d/m/Y H:i')
                                        ?? '-'
                                    }}

                                </strong>

                            </div>

                        </div>

                    </div>


                    <hr class="my-4">


                    {{-- =================================================
                         RIWAYAT PROGRESS
                    ================================================== --}}
                    <div class="mb-4">

                        <h6 class="fw-bold mb-1">
                            Riwayat Progress
                        </h6>

                        <small class="text-muted">
                            Catatan dan dokumentasi selama pekerjaan berlangsung.
                        </small>

                    </div>


                    @forelse($maintenanceRequest->logs as $log)

                        <div class="border rounded p-3 mb-3">


                            {{-- Header --}}
                            <div class="row align-items-center mb-3">

                                <div class="col-md-8">

                                    <strong>
                                        {{ $log->user->name ?? 'User' }}
                                    </strong>

                                    <div>
                                        <small class="text-muted">
                                            Progress Update
                                        </small>
                                    </div>

                                </div>


                                <div class="col-md-4 text-md-end">

                                    <small class="text-muted">

                                        {{
                                            optional(
                                                $log->created_at
                                            )->format('d/m/Y H:i')
                                        }}

                                    </small>

                                </div>

                            </div>


                            {{-- Note --}}
                            <div class="mb-3">

                                {!! nl2br(
                                    e(
                                        $log->note
                                    )
                                ) !!}

                            </div>


                            {{-- Foto --}}
                            @if(
                                $log->photos &&
                                $log->photos->count()
                            )

                                <div>

                                    <label class="text-muted small d-block mb-2">
                                        Foto Progress
                                    </label>


                                    <div class="row g-3">

                                        @foreach(
                                            $log->photos as $photo
                                        )

                                            <div class="col-xl-3 col-lg-4 col-md-4 col-sm-6 col-6">

                                                <a
                                                    href="{{ asset(
                                                        'storage/' .
                                                        $photo->file_path
                                                    ) }}"
                                                    target="_blank"
                                                >

                                                    <img
                                                        src="{{ asset(
                                                            'storage/' .
                                                            $photo->file_path
                                                        ) }}"
                                                        class="img-fluid rounded border"
                                                        style="
                                                            width:100%;
                                                            height:180px;
                                                            object-fit:cover;
                                                        "
                                                        alt="Foto progress"
                                                    >

                                                </a>


                                                @if($photo->caption)

                                                    <small class="text-muted d-block mt-1">

                                                        {{ $photo->caption }}

                                                    </small>

                                                @endif

                                            </div>

                                        @endforeach

                                    </div>

                                </div>

                            @endif

                        </div>

                    @empty

                        <div class="text-center text-muted py-4">

                            <i class="fa fa-info-circle me-1"></i>

                            Belum ada progress yang tercatat.

                        </div>

                    @endforelse


                    <hr class="my-4">


                    {{-- =================================================
                         HASIL MAINTENANCE
                    ================================================== --}}
                    @if(isset($maintenance) && $maintenance)


                        <div class="mb-4">

                            <div class="d-flex justify-content-between align-items-center">

                                <div>

                                    <h6 class="fw-bold mb-1">
                                        Hasil Maintenance
                                    </h6>

                                    <small class="text-muted">
                                        Detail hasil pekerjaan maintenance asset.
                                    </small>

                                </div>


                                @if($maintenance->status)

                                    @php

                                        $maintenanceStatusClass = match (
                                            $maintenance->status
                                        ) {

                                            'scheduled' => 'warning',

                                            'in_progress' => 'primary',

                                            'completed' => 'success',

                                            'cancelled' => 'danger',

                                            default => 'secondary',

                                        };

                                    @endphp

                                    <span class="badge bg-{{ $maintenanceStatusClass }}">

                                        {{
                                            ucwords(
                                                str_replace(
                                                    '_',
                                                    ' ',
                                                    $maintenance->status
                                                )
                                            )
                                        }}

                                    </span>

                                @endif

                            </div>

                        </div>


                        <div class="row g-3">


                            {{-- Maintenance Code --}}
                            <div class="col-xl-4 col-lg-4 col-md-6">

                                <label class="text-muted small d-block">
                                    Maintenance Code
                                </label>

                                <strong>
                                    {{ $maintenance->maintenance_code ?? '-' }}
                                </strong>

                            </div>


                            {{-- Type --}}
                            <div class="col-xl-4 col-lg-4 col-md-6">

                                <label class="text-muted small d-block">
                                    Maintenance Type
                                </label>

                                <strong>
                                    {{ ucfirst($maintenance->maintenance_type ?? '-') }}
                                </strong>

                            </div>


                            {{-- Date --}}
                            <div class="col-xl-4 col-lg-4 col-md-6">

                                <label class="text-muted small d-block">
                                    Maintenance Date
                                </label>

                                <strong>

                                    {{
                                        optional(
                                            $maintenance->maintenance_date
                                        )->format('d/m/Y')
                                        ?? '-'
                                    }}

                                </strong>

                            </div>


                            {{-- Technician --}}
                            <div class="col-xl-4 col-lg-4 col-md-6">

                                <label class="text-muted small d-block">
                                    Technician
                                </label>

                                <strong>
                                    {{ $maintenance->technician_name ?? '-' }}
                                </strong>

                            </div>


                            {{-- Vendor --}}
                            <div class="col-xl-4 col-lg-4 col-md-6">

                                <label class="text-muted small d-block">
                                    Vendor
                                </label>

                                <strong>
                                    {{ $maintenance->vendor->name ?? '-' }}
                                </strong>

                            </div>


                            {{-- Cost --}}
                            <div class="col-xl-4 col-lg-4 col-md-6">

                                <label class="text-muted small d-block">
                                    Cost
                                </label>

                                <strong>

                                    Rp {{
                                        number_format(
                                            $maintenance->cost ?? 0,
                                            0,
                                            ',',
                                            '.'
                                        )
                                    }}

                                </strong>

                            </div>


                            {{-- Action Taken --}}
                            <div class="col-12">

                                <label class="text-muted small d-block mb-1">
                                    Action Taken
                                </label>

                                <div class="border rounded p-3 bg-light">

                                    {!! nl2br(
                                        e(
                                            $maintenance->action_taken ?? '-'
                                        )
                                    ) !!}

                                </div>

                            </div>


                            {{-- Problem Description --}}
                            @if($maintenance->problem_description)

                                <div class="col-12">

                                    <label class="text-muted small d-block mb-1">
                                        Problem Description
                                    </label>

                                    <div class="border rounded p-3 bg-light">

                                        {!! nl2br(
                                            e(
                                                $maintenance->problem_description
                                            )
                                        ) !!}

                                    </div>

                                </div>

                            @endif


                            {{-- Next Maintenance --}}
                            <div class="col-xl-4 col-lg-4 col-md-6">

                                <label class="text-muted small d-block">
                                    Next Maintenance
                                </label>

                                <strong>

                                    {{
                                        optional(
                                            $maintenance->next_maintenance_date
                                        )->format('d/m/Y')
                                        ?? '-'
                                    }}

                                </strong>

                            </div>


                            {{-- Created By --}}
                            <div class="col-xl-4 col-lg-4 col-md-6">

                                <label class="text-muted small d-block">
                                    Created By
                                </label>

                                <strong>
                                    {{ $maintenance->creator->name ?? '-' }}
                                </strong>

                            </div>


                            {{-- Notes --}}
                            @if($maintenance->notes)

                                <div class="col-12">

                                    <label class="text-muted small d-block mb-1">
                                        Notes
                                    </label>

                                    <div class="border rounded p-3 bg-light">

                                        {!! nl2br(
                                            e(
                                                $maintenance->notes
                                            )
                                        ) !!}

                                    </div>

                                </div>

                            @endif

                        </div>


                        {{-- =================================================
                             FOTO AFTER MAINTENANCE
                        ================================================== --}}
                        @php

                            $afterPhotos = $maintenance->photos
                                ? $maintenance->photos->where(
                                    'photo_type',
                                    'after'
                                )
                                : collect();

                        @endphp


                        <hr class="my-4">


                        <div class="d-flex justify-content-between align-items-center mb-3">

                            <div>

                                <h6 class="fw-bold mb-1">
                                    Foto Setelah Maintenance
                                </h6>

                                <small class="text-muted">
                                    Dokumentasi kondisi asset setelah pekerjaan selesai.
                                </small>

                            </div>


                            @if($afterPhotos->count())

                                <span class="badge bg-success">

                                    {{ $afterPhotos->count() }} Foto

                                </span>

                            @endif

                        </div>


                        @if($afterPhotos->count())

                            <div class="row g-3">

                                @foreach($afterPhotos as $photo)

                                    <div class="col-xl-3 col-lg-4 col-md-4 col-sm-6 col-6">

                                        <a
                                            href="{{ asset(
                                                'storage/' .
                                                $photo->file_path
                                            ) }}"
                                            target="_blank"
                                        >

                                            <img
                                                src="{{ asset(
                                                    'storage/' .
                                                    $photo->file_path
                                                ) }}"
                                                class="img-fluid rounded border"
                                                style="
                                                    width:100%;
                                                    height:200px;
                                                    object-fit:cover;
                                                "
                                                alt="Foto setelah maintenance"
                                            >

                                        </a>


                                        @if($photo->caption)

                                            <small class="text-muted d-block mt-1">
                                                {{ $photo->caption }}
                                            </small>

                                        @endif

                                    </div>

                                @endforeach

                            </div>

                        @else

                            <div class="text-center text-muted py-4">

                                <i class="fa fa-image me-1"></i>

                                Tidak ada foto setelah maintenance.

                            </div>

                        @endif


                    @endif

                </div>

            </div>

        </div>

    </div>

</div>

@endsection
