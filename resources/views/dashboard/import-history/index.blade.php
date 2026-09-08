@extends('dashboard.layouts.wrapper')

@section('title', 'Import History')

@section('content')

<div class="container-fluid">

    {{-- =========================================================
         PAGE HEADER
    ========================================================== --}}
    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h4 class="mb-1">
                Import History
            </h4>

            <p class="text-muted mb-0">
                Riwayat proses import data ke sistem.
            </p>
        </div>

        <a
            href="{{ url()->previous() }}"
            class="btn btn-light"
        >
            <i class="fas fa-arrow-left me-1"></i>
            Kembali
        </a>

    </div>


    {{-- =========================================================
         INFO
    ========================================================== --}}
    <div class="alert alert-info d-flex align-items-start mb-4">

        <i class="fas fa-info-circle me-2 mt-1"></i>

        <div>
            <strong>Import History</strong>

            <div class="small mt-1">
                Halaman ini menampilkan seluruh riwayat import
                User, Asset dan modul lainnya.
            </div>
        </div>

    </div>


    {{-- =========================================================
         TABLE CARD
    ========================================================== --}}
    <div class="card">

        <div class="card-header">

            <div class="d-flex justify-content-between align-items-center">

                <h5 class="mb-0">
                    Riwayat Import
                </h5>

               <!-- <button
                    type="button"
                    class="btn btn-sm btn-outline-secondary"
                    onclick="loadImportHistory()"
                >
                    <i class="fas fa-sync-alt me-1"></i>
                    Refresh
                </button>-->

            </div>

        </div>


        <div class="card-body">

            <div class="table-responsive">

                <table
                    class="table table-hover align-middle"
                    id="importHistoryTable"
                >

                    <thead>

                        <tr>

                            <th width="50">
                                #
                            </th>

                            <th width="100">
                                Module
                            </th>

                            <th>
                                File
                            </th>

                            <th width="180">
                                Progress
                            </th>

                            <th width="80"
                                class="text-center">
                                Total
                            </th>

                            <th width="90"
                                class="text-center">
                                Berhasil
                            </th>

                            <th width="80"
                                class="text-center">
                                Gagal
                            </th>

                            <th width="150">
                                Status
                            </th>

                            <th width="170">
                                Waktu
                            </th>

                            <th width="80">
                                Action
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                        <tr>

                            <td
                                colspan="10"
                                class="text-center text-muted py-5"
                            >
                                <i class="fas fa-spinner fa-spin me-1"></i>
                                Memuat data...
                            </td>

                        </tr>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>


{{-- =========================================================
     SCRIPT
========================================================= --}}
<script>

    function escapeHtml(value) {

        if (value === null ||
            value === undefined) {

            return '';
        }

        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }


    function getModuleBadge(module) {

        const value =
            String(module || '')
                .toLowerCase();

        let label =
            value
                ? value.charAt(0).toUpperCase() +
                  value.slice(1)
                : '-';

        let icon =
            'fa-file-import';

        if (value === 'user') {

            icon =
                'fa-users';

        } else if (value === 'asset') {

            icon =
                'fa-boxes';

        } else if (value === 'vendor') {

            icon =
                'fa-building';

        } else if (
            value === 'category'
        ) {

            icon =
                'fa-tags';
        }

        return `
            <span class="badge bg-light text-dark border">
                <i class="fas ${icon} me-1"></i>
                ${escapeHtml(label)}
            </span>
        `;
    }


    function getStatusBadge(status) {

        const value =
            String(status || '')
                .toLowerCase();

        let label =
            status || '-';

        let className =
            'bg-secondary';

        let icon =
            'fa-clock';

        switch (value) {

            case 'processing':

                label =
                    'Processing';

                className =
                    'bg-warning text-dark';

                icon =
                    'fa-spinner fa-spin';

                break;


            case 'completed':

                label =
                    'Completed';

                className =
                    'bg-success';

                icon =
                    'fa-check';

                break;


            case 'completed_with_errors':

                label =
                    'Completed with Errors';

                className =
                    'bg-warning text-dark';

                icon =
                    'fa-exclamation-triangle';

                break;


            case 'failed':

                label =
                    'Failed';

                className =
                    'bg-danger';

                icon =
                    'fa-times';

                break;
        }

        return `
            <span class="badge ${className}">
                <i class="fas ${icon} me-1"></i>
                ${escapeHtml(label)}
            </span>
        `;
    }


    function getProgressBar(item) {

        const progress =
            Number(item.progress || 0);

        const processed =
            Number(item.processed_rows || 0);

        const total =
            Number(item.total_rows || 0);

        return `
            <div class="d-flex align-items-center gap-2">

                <div
                    class="progress flex-grow-1"
                    style="height: 8px;"
                >
                    <div
                        class="progress-bar"
                        role="progressbar"
                        style="width: ${progress}%"
                        aria-valuenow="${progress}"
                        aria-valuemin="0"
                        aria-valuemax="100"
                    ></div>
                </div>

                <small
                    class="text-muted"
                    style="min-width: 42px;"
                >
                    ${progress}%
                </small>

            </div>

            <small class="text-muted">
                ${processed.toLocaleString('id-ID')}
                /
                ${total.toLocaleString('id-ID')}
            </small>
        `;
    }


    function loadImportHistory() {

        fetch(
            "{{ route('import.history.progress') }}",
            {
                headers: {
                    'Accept':
                        'application/json'
                }
            }
        )

        .then(function(response) {

            if (!response.ok) {

                throw new Error(
                    'HTTP ' +
                    response.status
                );
            }

            return response.json();
        })

        .then(function(data) {

            const tbody =
                document.querySelector(
                    '#importHistoryTable tbody'
                );

            if (!data.length) {

                tbody.innerHTML = `
                    <tr>
                        <td
                            colspan="10"
                            class="text-center text-muted py-5"
                        >
                            <i class="fas fa-inbox fa-2x mb-2"></i>

                            <div>
                                Belum ada riwayat import.
                            </div>
                        </td>
                    </tr>
                `;

                return;
            }


            tbody.innerHTML =
                data.map(function(item, index) {

                    const isFinished =
                        [
                            'completed',
                            'completed_with_errors',
                            'failed'
                        ].includes(
                            String(
                                item.status || ''
                            ).toLowerCase()
                        );


                    return `
                        <tr>

                            <td>
                                ${index + 1}
                            </td>


                            <td>
                                ${getModuleBadge(
                                    item.module
                                )}
                            </td>


                            <td>

                                <div
                                    class="fw-semibold text-truncate"
                                    style="max-width: 280px;"
                                    title="${escapeHtml(
                                        item.file_name
                                    )}"
                                >
                                    ${escapeHtml(
                                        item.file_name
                                    )}
                                </div>

                            </td>


                            <td>
                                ${getProgressBar(item)}
                            </td>


                            <td class="text-center">
                                ${Number(
                                    item.total_rows || 0
                                ).toLocaleString('id-ID')}
                            </td>


                            <td
                                class="text-center text-success fw-semibold"
                            >
                                ${Number(
                                    item.success_rows || 0
                                ).toLocaleString('id-ID')}
                            </td>


                            <td
                                class="text-center text-danger fw-semibold"
                            >
                                ${Number(
                                    item.failed_rows || 0
                                ).toLocaleString('id-ID')}
                            </td>


                            <td>
                                ${getStatusBadge(
                                    item.status
                                )}
                            </td>


                            <td>

                                <div>
                                    ${
                                        item.started_at
                                            ? escapeHtml(
                                                item.started_at
                                            )
                                            : '-'
                                    }
                                </div>

                                ${
                                    item.duration
                                        ? `
                                            <small class="text-muted">
                                                ${escapeHtml(
                                                    item.duration
                                                )}
                                            </small>
                                          `
                                        : ''
                                }

                            </td>


                            <td class="text-center">

                                ${
                                    isFinished
                                        ? `
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-primary"
                                                onclick="showImportDetail(${item.id})"
                                                title="Lihat Detail Import"
                                            >
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        `
                                        : `
                                            <span
                                                class="text-muted"
                                                title="Import masih berjalan"
                                            >
                                                <i class="fas fa-spinner fa-spin"></i>
                                            </span>
                                        `
                                }

                            </td>

                        </tr>
                    `;

                }).join('');

        })

        .catch(function(error) {

            console.error(
                'Import history error:',
                error
            );

            const tbody =
                document.querySelector(
                    '#importHistoryTable tbody'
                );

            tbody.innerHTML = `
                <tr>
                    <td
                        colspan="10"
                        class="text-center text-danger py-5"
                    >
                        <i class="fas fa-exclamation-circle me-1"></i>

                        Gagal memuat import history.
                    </td>
                </tr>
            `;
        });
    }


    function showImportDetail(id) {

        if (!id) {
            return;
        }

        window.location.href =
            "{{ url('/dashboard/import-history-detail') }}/" + id;
    }


    /*
    |--------------------------------------------------------------------------
    | Load pertama
    |--------------------------------------------------------------------------
    */

    loadImportHistory();


    /*
    |--------------------------------------------------------------------------
    | Auto refresh setiap 2 detik
    |--------------------------------------------------------------------------
    */

    setInterval(
        loadImportHistory,
        2000
    );

</script>

@endsection