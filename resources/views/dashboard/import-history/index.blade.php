
@extends('dashboard.layouts.wrapper')

@section('title', 'Import History')

@section('content')
<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">Import History</h5>
                            <small class="text-muted">
                                Riwayat proses import data ke sistem.
                            </small>
                        </div>

                        <a href="{{ url()->previous() }}" class="btn btn-light">
                            <i class="fas fa-arrow-left me-1"></i>
                            Kembali
                        </a>
                    </div>

                    <div class="card-body">
                        <div class="alert alert-info d-flex align-items-start mb-4">
                            <i class="fas fa-info-circle me-2 mt-1"></i>
                            <div>
                                <strong>Import History</strong>
                                <div class="small mt-1">
                                    Riwayat import User, Asset, dan modul lainnya.
                                    Data ditampilkan menggunakan server-side pagination.
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle"
                                   id="table-import-history"
                                   width="100%">
                                <thead>
                                    <tr>
                                        <th width="5%">No</th>
                                        <th>Module</th>
                                        <th>File</th>
                                        <th width="180">Progress</th>
                                        <th>Total</th>
                                        <th>Berhasil</th>
                                        <th>Gagal</th>
                                        <th>Status</th>
                                        <th>Waktu</th>
                                        <th width="80">Action</th>
                                    </tr>
                                </thead>
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

    function formatNumber(value) {
        return Number(value || 0).toLocaleString('id-ID');
    }

    function moduleBadge(module) {
        const value = String(module || '').toLowerCase();

        const icons = {
            user: 'fa-users',
            asset: 'fa-boxes',
            vendor: 'fa-building',
            category: 'fa-tags',
            maintenance: 'fa-tools'
        };

        const label = value
            ? value.charAt(0).toUpperCase() + value.slice(1)
            : '-';

        return `
            <span class="badge bg-light text-dark border">
                <i class="fas ${icons[value] || 'fa-file-import'} me-1"></i>
                ${escapeHtml(label)}
            </span>
        `;
    }

    function statusBadge(status) {
        const config = {
            processing: {
                label: 'Processing',
                css: 'bg-warning text-dark',
                icon: 'fa-spinner fa-spin'
            },
            completed: {
                label: 'Completed',
                css: 'bg-success',
                icon: 'fa-check'
            },
            completed_with_errors: {
                label: 'Completed with Errors',
                css: 'bg-warning text-dark',
                icon: 'fa-exclamation-triangle'
            },
            failed: {
                label: 'Failed',
                css: 'bg-danger',
                icon: 'fa-times'
            }
        };

        const value = String(status || '').toLowerCase();
        const item = config[value] || {
            label: status || '-',
            css: 'bg-secondary',
            icon: 'fa-clock'
        };

        return `
            <span class="badge ${item.css}">
                <i class="fas ${item.icon} me-1"></i>
                ${escapeHtml(item.label)}
            </span>
        `;
    }

    function progressBar(row) {
        const progress = Math.max(
            0,
            Math.min(100, Number(row.progress || 0))
        );

        return `
            <div class="d-flex align-items-center gap-2">
                <div class="progress flex-grow-1" style="height:8px">
                    <div class="progress-bar"
                         role="progressbar"
                         style="width:${progress}%"
                         aria-valuenow="${progress}"
                         aria-valuemin="0"
                         aria-valuemax="100">
                    </div>
                </div>
                <small class="text-muted">${progress}%</small>
            </div>
            <small class="text-muted">
                ${formatNumber(row.processed_rows)}
                /
                ${formatNumber(row.total_rows)}
            </small>
        `;
    }

    const detailUrlTemplate =
        "{{ route('import.history.detail', ['id' => '__ID__']) }}";

    const table = $('#table-import-history').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        autoWidth: false,
        searchDelay: 400,
        pageLength: 10,
        lengthMenu: [
            [10, 25, 50, 100],
            [10, 25, 50, 100]
        ],
        ajax: "{{ route('import.history.progress') }}",

        columns: [
            {
                data: 'DT_RowIndex',
                name: 'DT_RowIndex',
                orderable: false,
                searchable: false
            },
            {
                data: 'module',
                name: 'module',
                render: function (data, type) {
                    return type === 'display'
                        ? moduleBadge(data)
                        : (data || '');
                }
            },
            {
                data: 'file_name',
                name: 'file_name',
                render: function (data, type) {
                    if (type !== 'display') {
                        return data || '';
                    }

                    return `
                        <div class="fw-semibold text-truncate"
                             style="max-width:280px"
                             title="${escapeHtml(data || '')}">
                            ${escapeHtml(data || '-')}
                        </div>
                    `;
                }
            },
            {
                data: 'progress',
                name: 'progress',
                orderable: false,
                searchable: false,
                render: function (data, type, row) {
                    return type === 'display'
                        ? progressBar(row)
                        : Number(data || 0);
                }
            },
            {
                data: 'total_rows',
                name: 'total_rows',
                className: 'text-center',
                render: function (data, type) {
                    return type === 'display'
                        ? formatNumber(data)
                        : Number(data || 0);
                }
            },
            {
                data: 'success_rows',
                name: 'success_rows',
                className: 'text-center text-success fw-semibold',
                render: function (data, type) {
                    return type === 'display'
                        ? formatNumber(data)
                        : Number(data || 0);
                }
            },
            {
                data: 'failed_rows',
                name: 'failed_rows',
                className: 'text-center text-danger fw-semibold',
                render: function (data, type) {
                    return type === 'display'
                        ? formatNumber(data)
                        : Number(data || 0);
                }
            },
            {
                data: 'status',
                name: 'status',
                render: function (data, type) {
                    return type === 'display'
                        ? statusBadge(data)
                        : (data || '');
                }
            },
            {
                data: 'started_at',
                name: 'started_at',
                render: function (data, type, row) {
                    if (type !== 'display') {
                        return data || '';
                    }

                    return `
                        <div>${escapeHtml(data || '-')}</div>
                        ${row.duration
                            ? `<small class="text-muted">
                                   ${escapeHtml(row.duration)}
                               </small>`
                            : ''}
                    `;
                }
            },
            {
                data: 'encrypted_id',
                name: 'encrypted_id',
                orderable: false,
                searchable: false,
                className: 'text-center',
                render: function (data, type) {
                    if (type !== 'display') {
                        return '';
                    }

                    if (!data) {
                        return '-';
                    }

                    return `
                        <button type="button"
                                class="btn btn-sm btn-outline-primary btn-import-detail"
                                data-id="${escapeHtml(data)}"
                                title="Lihat Detail Import">
                            <i class="fas fa-eye"></i>
                        </button>
                    `;
                }
            }
        ],

        order: [[8, 'desc']],

        /*language: {
            processing: 'Memuat data...',
            search: 'Cari:',
            lengthMenu: 'Tampilkan _MENU_ data',
            info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
            infoEmpty: 'Tidak ada data',
            infoFiltered: '(difilter dari _MAX_ total data)',
            zeroRecords: 'Data tidak ditemukan',
            emptyTable: 'Belum ada riwayat import',
            paginate: {
                first: 'Pertama',
                last: 'Terakhir',
                next: 'Berikutnya',
                previous: 'Sebelumnya'
            }
        }*/
    });

    $('#table-import-history').on(
        'click',
        '.btn-import-detail',
        function () {
            const encryptedId = $(this).attr('data-id');

            if (!encryptedId) {
                return;
            }

            window.location.href = detailUrlTemplate.replace(
                '__ID__',
                encodeURIComponent(encryptedId)
            );
        }
    );

    // Refresh progress tanpa kembali ke halaman pertama.
    const refreshTimer = setInterval(function () {
        if (document.visibilityState === 'visible') {
            table.ajax.reload(null, false);
        }
    }, 5000);

    // Tidak perlu menghentikan timer secara manual;
    // browser men-throttle tab yang tidak aktif.
});
</script>
@endsection
