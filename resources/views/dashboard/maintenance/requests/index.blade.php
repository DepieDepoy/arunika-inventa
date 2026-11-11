@extends('dashboard.layouts.wrapper')

@section('title', __('Maintenance Requests'))

@section('content')

@php
    /** @var \App\Models\User $authUser */
    $authUser = auth()->user();
    $canHandle = $authUser->hasPermission('maintenance.edit');

    $pendingCount = $requests->where('status', 'pending')->count();
    $progressCount = $requests->where('status', 'in_progress')->count();
    $mineCount = $requests
        ->where('status', 'in_progress')
        ->where('handled_by', $authUser->id)
        ->count();

    $typeLabels = \App\Services\Maintenance\MaintenanceRequestNotifier::TYPE_LABELS;
    $typeIcons = [
        'maintenance' => 'bi-tools',
        'repair' => 'bi-wrench-adjustable',
        'problem' => 'bi-exclamation-triangle',
        'other' => 'bi-three-dots',
    ];
@endphp

<style>
    .swal2-container { z-index: 99999 !important; }

    .mr-stat {
        border: 1px solid var(--bs-border-color);
        border-radius: .75rem;
        padding: 1rem 1.25rem;
        display: flex;
        align-items: center;
        gap: .9rem;
        height: 100%;
        background: var(--bs-body-bg);
        transition: border-color .15s, box-shadow .15s;
        cursor: pointer;
        width: 100%;
        text-align: left;
    }
    .mr-stat:hover { border-color: var(--bs-primary); }
    .mr-stat.active { border-color: var(--bs-primary); box-shadow: 0 0 0 .2rem rgba(var(--bs-primary-rgb), .12); }
    .mr-stat-icon {
        width: 44px; height: 44px; border-radius: .65rem;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 1.25rem; flex-shrink: 0;
    }
    .mr-stat-value { font-size: 1.5rem; font-weight: 700; line-height: 1.1; }
    .mr-stat-label { font-size: .8rem; color: var(--bs-secondary-color); }

    .mr-asset-name { font-weight: 600; white-space: normal; }
    .mr-description {
        min-width: 200px; max-width: 340px;
        white-space: normal; word-break: break-word;
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
    }
    .mr-handler {
        display: inline-flex; align-items: center; gap: .4rem;
        background: rgba(var(--bs-primary-rgb), .08);
        color: var(--bs-primary);
        border-radius: 999px; padding: .2rem .65rem; font-size: .8rem; font-weight: 600;
        white-space: nowrap;
    }
    .mr-handler.is-me { background: rgba(var(--bs-success-rgb), .12); color: var(--bs-success); }
    .mr-waiting {
        display: inline-flex; align-items: center; gap: .4rem;
        background: rgba(var(--bs-warning-rgb), .15); color: #9a6700;
        border-radius: 999px; padding: .2rem .65rem; font-size: .8rem; font-weight: 600;
        white-space: nowrap;
    }
    .mr-meta { font-size: .75rem; color: var(--bs-secondary-color); }

    @media (max-width: 767.98px) {
        .mr-description { min-width: 160px; max-width: 240px; }
    }
</style>

<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row">
            <div class="col-xxl-12 mb-4">
                <div class="card">

                    <div class="card-header d-flex flex-wrap justify-content-between align-items-start gap-2">
                        <div>
                            <h5 class="mb-1">{{ __('Maintenance Requests') }}</h5>
                            <small class="text-muted">
                                Permintaan maintenance, perbaikan, dan laporan masalah aset.
                                @if($canHandle)
                                    Ambil request yang menunggu — setelah diambil, request tidak lagi muncul di antrean tim lain.
                                @endif
                            </small>
                        </div>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.location.reload()">
                            <i class="bi bi-arrow-clockwise me-1"></i> {{ __('Muat ulang') }}
                        </button>
                    </div>

                    {{-- SUMMARY / FILTER --}}
                    <div class="card-body pb-0">
                        <div class="row g-3">
                            <div class="col-xl-3 col-md-6">
                                <button type="button" class="mr-stat {{ $pendingCount > 0 ? 'active' : '' }}" data-filter="pending">
                                    <span class="mr-stat-icon bg-warning-subtle text-warning"><i class="bi bi-hourglass-split"></i></span>
                                    <span>
                                        <span class="mr-stat-value d-block">{{ $pendingCount }}</span>
                                        <span class="mr-stat-label">{{ __('Menunggu diambil') }}</span>
                                    </span>
                                </button>
                            </div>
                            <div class="col-xl-3 col-md-6">
                                <button type="button" class="mr-stat" data-filter="in_progress">
                                    <span class="mr-stat-icon bg-primary-subtle text-primary"><i class="bi bi-gear-wide-connected"></i></span>
                                    <span>
                                        <span class="mr-stat-value d-block">{{ $progressCount }}</span>
                                        <span class="mr-stat-label">{{ __('Sedang dikerjakan') }}</span>
                                    </span>
                                </button>
                            </div>
                            @if($canHandle)
                                <div class="col-xl-3 col-md-6">
                                    <button type="button" class="mr-stat" data-filter="mine">
                                        <span class="mr-stat-icon bg-success-subtle text-success"><i class="bi bi-person-workspace"></i></span>
                                        <span>
                                            <span class="mr-stat-value d-block">{{ $mineCount }}</span>
                                            <span class="mr-stat-label">{{ __('Tugas saya') }}</span>
                                        </span>
                                    </button>
                                </div>
                            @endif
                            <div class="col-xl-3 col-md-6">
                                <button type="button" class="mr-stat {{ $pendingCount === 0 ? 'active' : '' }}" data-filter="all">
                                    <span class="mr-stat-icon bg-info-subtle text-info"><i class="bi bi-list-task"></i></span>
                                    <span>
                                        <span class="mr-stat-value d-block">{{ $requests->count() }}</span>
                                        <span class="mr-stat-label">{{ __('Semua request aktif') }}</span>
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- LIST --}}
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle" id="maintenanceRequestsTable" width="100%">
                                <thead>
                                    <tr>
                                        <th width="4%">{{ __('No') }}</th>
                                        <th>{{ __('Aset') }}</th>
                                        <th>{{ __('Jenis') }}</th>
                                        <th>{{ __('Keterangan') }}</th>
                                        <th>{{ __('Pemohon') }}</th>
                                        <th>{{ __('Status') }}</th>
                                        <th width="14%">{{ __('Aksi') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($requests as $item)
                                        @php
                                            $isMine = (int) $item->handled_by === (int) $authUser->id;
                                            $group = $item->status === 'pending'
                                                ? 'pending'
                                                : ($isMine ? 'in_progress mine' : 'in_progress');
                                        @endphp
                                        <tr data-group="{{ $group }}">
                                            <td>{{ $loop->iteration }}</td>

                                            <td>
                                                @if($item->asset)
                                                    <div class="mr-asset-name">{{ $item->asset->asset_name }}</div>
                                                    <small class="text-muted">{{ $item->asset->asset_code }}</small>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>

                                            <td>
                                                <span class="badge bg-secondary-subtle text-secondary-emphasis">
                                                    <i class="bi {{ $typeIcons[$item->request_type] ?? 'bi-tag' }} me-1"></i>
                                                    {{ $typeLabels[$item->request_type] ?? ucfirst($item->request_type) }}
                                                </span>
                                            </td>

                                            <td>
                                                <div class="mr-description" title="{{ $item->description }}">{{ $item->description }}</div>
                                            </td>

                                            <td>
                                                <div>{{ $item->requester->name ?? '-' }}</div>
                                                <div class="mr-meta" title="{{ $item->created_at?->format('d M Y H:i') }}">
                                                    {{ $item->created_at?->diffForHumans() }}
                                                </div>
                                            </td>

                                            <td data-order="{{ $item->status === 'pending' ? 0 : 1 }}">
                                                @if($item->status === 'pending')
                                                    <span class="mr-waiting"><i class="bi bi-hourglass-split"></i> {{ __('Menunggu diambil') }}</span>
                                                @elseif($item->status === 'in_progress')
                                                    <span class="mr-handler {{ $isMine ? 'is-me' : '' }}">
                                                        <i class="bi bi-person-gear"></i>
                                                        {{ $isMine ? 'Dikerjakan oleh Anda' : 'Dikerjakan oleh ' . ($item->handler->name ?? '-') }}
                                                    </span>
                                                    @if($item->handled_at)
                                                        <div class="mr-meta mt-1">{{ __('sejak :p1', ['p1' => $item->handled_at->diffForHumans()]) }}</div>
                                                    @endif
                                                @else
                                                    <span class="badge bg-secondary">{{ ucfirst(str_replace('_', ' ', $item->status)) }}</span>
                                                @endif
                                            </td>

                                            <td>
                                                <div class="d-flex gap-1 flex-wrap">
                                                    @if($item->status === 'pending' && $canHandle)
                                                        <button type="button" class="btn btn-primary btn-sm btn-take-request" data-id="{{ $item->id }}">
                                                            <i class="bi bi-hand-index-thumb me-1"></i> {{ __('Ambil') }}
                                                        </button>
                                                    @elseif($item->status === 'in_progress' && $isMine)
                                                        <a href="{{ route('maintenance.requests.work', $item->id) }}" class="btn btn-success btn-sm">
                                                            <i class="bi bi-tools me-1"></i> {{ __('Kerjakan') }}
                                                        </a>
                                                    @endif

                                                    <a href="{{ route('maintenance.requests.show', $item->id) }}" class="btn btn-outline-secondary btn-sm" title="{{ __('Detail') }}">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
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
    let activeFilter = $('.mr-stat.active').data('filter') || 'all';

    // Filter baris berdasarkan kartu ringkasan yang dipilih.
    $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
        if (settings.nTable.id !== 'maintenanceRequestsTable' || activeFilter === 'all') {
            return true;
        }

        const row = settings.aoData[dataIndex].nTr;
        const groups = (row.getAttribute('data-group') || '').split(' ');

        return groups.includes(activeFilter);
    });

    const table = $('#maintenanceRequestsTable').DataTable({
        responsive: true,
        autoWidth: false,
        pageLength: 10,
        order: [[5, 'asc']],
        columnDefs: [{ orderable: false, searchable: false, targets: [0, 6] }],
        language: {
            search: @json(__('Cari:')),
            lengthMenu: @json(__('Tampilkan _MENU_')),
            info: @json(__('_START_–_END_ dari _TOTAL_ request')),
            infoEmpty: @json(__('Tidak ada request')),
            infoFiltered: @json(__('(disaring dari _MAX_)')),
            zeroRecords: @json(__('Tidak ada request yang cocok')),
            emptyTable: @json(__('Belum ada request aktif')),
            paginate: { first: @json(__('Awal')), last: @json(__('Akhir')), next: '›', previous: '‹' }
        }
    });

    $('.mr-stat').on('click', function () {
        $('.mr-stat').removeClass('active');
        $(this).addClass('active');
        activeFilter = $(this).data('filter');
        table.draw();
    });

    table.draw();

    /* TAKE REQUEST */
    $(document).on('click', '.btn-take-request', function () {
        const button = $(this);
        const requestId = button.data('id');

        Swal.fire({
            icon: 'question',
            title: @json(__('Ambil request ini?')),
            text: @json(__('Request akan menjadi tanggung jawab Anda dan hilang dari antrean anggota tim lainnya.')),
            showCancelButton: true,
            confirmButtonText: @json(__('Ya, ambil')),
            cancelButtonText: @json(__('Batal')),
            reverseButtons: true
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }

            button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Memproses...');

            $.ajax({
                url: "{{ url('/dashboard/maintenance-requests') }}/" + requestId + "/take",
                type: 'POST',
                data: { _token: $('meta[name="csrf-token"]').attr('content') },
                headers: { 'Accept': 'application/json' },

                success: function (response) {
                    Swal.fire({
                        icon: 'success',
                        title: @json(__('Request diambil')),
                        text: response.message,
                        showCancelButton: true,
                        confirmButtonText: @json(__('Mulai kerjakan')),
                        cancelButtonText: @json(__('Nanti')),
                        reverseButtons: true
                    }).then(function (r) {
                        if (r.isConfirmed && response.redirect) {
                            window.location.href = response.redirect;
                        } else {
                            window.location.reload();
                        }
                    });
                },

                error: function (xhr) {
                    const json = xhr.responseJSON || {};

                    Swal.fire({
                        icon: xhr.status === 409 ? 'info' : 'error',
                        title: xhr.status === 409 ? 'Sudah diambil' : 'Gagal',
                        text: json.message || 'Request gagal diambil.'
                    }).then(function () {
                        if (xhr.status === 409) {
                            window.location.reload();
                        }
                    });

                    button.prop('disabled', false).html('<i class="bi bi-hand-index-thumb me-1"></i> Ambil');
                }
            });
        });
    });
});
</script>
@endsection
