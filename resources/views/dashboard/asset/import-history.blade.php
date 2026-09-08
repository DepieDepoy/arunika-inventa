@extends('dashboard.layouts.wrapper')

@section('title', 'Asset Import History')

@section('content')

<div class="container-fluid">

    <!-- =========================================================
         PAGE HEADER
    ========================================================== -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">

        <div>
            <h4 class="mb-1">
                Asset Import History
            </h4>

            <p class="text-muted mb-0">
                Monitor proses import asset dari file Excel.
            </p>
        </div>

        <div class="d-flex gap-2">

            <a
                href="{{ route('assets.import') }}"
                class="btn btn-outline-secondary"
            >
                <i class="fas fa-arrow-left me-1"></i>
                Kembali Import
            </a>

        </div>

    </div>


    <!-- =========================================================
         INFO
    ========================================================== -->
    <div class="alert alert-info d-flex align-items-start gap-2 mb-4">

        <i class="fas fa-info-circle mt-1"></i>

        <div>
            <strong>Import berjalan di background.</strong>

            <div class="small mt-1">
                Halaman ini akan memperbarui progress secara otomatis.
                Anda tidak perlu menunggu halaman tetap terbuka untuk proses import.
            </div>
        </div>

    </div>


    <!-- =========================================================
         HISTORY TABLE
    ========================================================== -->
    <div class="card">

        <div class="card-header d-flex justify-content-between align-items-center">

            <div>
                <h5 class="mb-0">
                    Riwayat Import
                </h5>
            </div>

            <div>
                <span
                    id="lastUpdated"
                    class="text-muted small"
                >
                    -
                </span>
            </div>

        </div>


        <div class="card-body p-0">

            <div class="table-responsive">

                <table
                    class="table table-hover align-middle mb-0"
                    id="importHistoryTable"
                >

                    <thead class="table-light">

                        <tr>

                            <th style="width: 60px;">
                                #
                            </th>

                            <th>
                                File
                            </th>

                            <th style="min-width: 220px;">
                                Progress
                            </th>

                            <th>
                                Total
                            </th>

                            <th>
                                Berhasil
                            </th>

                            <th>
                                Gagal
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Waktu
                            </th>

                            <th style="width: 100px;">
                                Action
                            </th>

                        </tr>

                    </thead>

                    <tbody id="importHistoryBody">

                        <tr>

                            <td
                                colspan="9"
                                class="text-center py-5"
                            >

                                <div class="spinner-border spinner-border-sm me-2"></div>

                                Memuat riwayat import...

                            </td>

                        </tr>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>


<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script>

document.addEventListener('DOMContentLoaded', function () {

    loadImportHistory();

    /*
    |--------------------------------------------------------------------------
    | Polling setiap 2 detik
    |--------------------------------------------------------------------------
    */

    setInterval(function () {

        loadImportHistory();

    }, 2000);

});


/*
|--------------------------------------------------------------------------
| Load Import History
|--------------------------------------------------------------------------
*/

function loadImportHistory()
{
    fetch(
        "{{ route('assets.import.history.progress') }}",
        {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        }
    )

    .then(response => {

        if (!response.ok) {
            throw new Error(
                'Gagal mengambil data import history.'
            );
        }

        return response.json();

    })

    .then(data => {

        renderImportHistory(data);

        document.getElementById('lastUpdated').innerText =
            'Update: ' +
            new Date().toLocaleTimeString('id-ID');

    })

    .catch(error => {

        console.error(
            'Import history error:',
            error
        );

    });
}


/*
|--------------------------------------------------------------------------
| Render Table
|--------------------------------------------------------------------------
*/

function renderImportHistory(data)
{
    const tbody =
        document.getElementById(
            'importHistoryBody'
        );

    if (!data || data.length === 0) {

        tbody.innerHTML = `
            <tr>
                <td
                    colspan="9"
                    class="text-center text-muted py-5"
                >
                    <i class="fas fa-history fa-2x mb-3 d-block"></i>

                    Belum ada riwayat import asset.
                </td>
            </tr>
        `;

        return;
    }


    let html = '';


    data.forEach(function (item, index) {

        /*
        |--------------------------------------------------------------------------
        | Status Badge
        |--------------------------------------------------------------------------
        */

        let statusBadge = '';

        switch (item.status) {

            case 'processing':

                statusBadge = `
                    <span class="badge bg-primary">
                        <i class="fas fa-spinner fa-spin me-1"></i>
                        Processing
                    </span>
                `;

                break;


            case 'completed':

                statusBadge = `
                    <span class="badge bg-success">
                        <i class="fas fa-check me-1"></i>
                        Completed
                    </span>
                `;

                break;


            case 'completed_with_errors':

                statusBadge = `
                    <span class="badge bg-warning text-dark">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        Selesai dengan Error
                    </span>
                `;

                break;


            case 'failed':

                statusBadge = `
                    <span class="badge bg-danger">
                        <i class="fas fa-times me-1"></i>
                        Failed
                    </span>
                `;

                break;


            default:

                statusBadge = `
                    <span class="badge bg-secondary">
                        ${escapeHtml(item.status ?? '-')}
                    </span>
                `;

        }


        /*
        |--------------------------------------------------------------------------
        | Progress
        |--------------------------------------------------------------------------
        */

        const progress =
            Number(item.progress ?? 0);

        const processed =
            Number(item.processed_rows ?? 0);

        const total =
            Number(item.total_rows ?? 0);


        /*
        |--------------------------------------------------------------------------
        | Action
        |--------------------------------------------------------------------------
        */

        let action = `
            <a
                href="/assets/import/history/${item.id}"
                class="btn btn-sm btn-outline-primary"
                title="Detail"
            >
                <i class="fas fa-eye"></i>
            </a>
        `;


        /*
        |--------------------------------------------------------------------------
        | Row
        |--------------------------------------------------------------------------
        */

        html += `

            <tr>

                <td>
                    ${index + 1}
                </td>


                <td>

                    <div class="d-flex align-items-center">

                        <div
                            class="rounded bg-light d-flex align-items-center justify-content-center me-2"
                            style="
                                width: 38px;
                                height: 38px;
                            "
                        >

                            <i class="fas fa-file-excel text-success"></i>

                        </div>


                        <div>

                            <div class="fw-semibold">
                                ${escapeHtml(item.file_name ?? '-')}
                            </div>

                            <div class="small text-muted">

                                ${item.started_at
                                    ? 'Mulai: ' +
                                      escapeHtml(item.started_at)
                                    : 'Menunggu proses...'}

                            </div>

                        </div>

                    </div>

                </td>


                <td>

                    <div class="d-flex justify-content-between mb-1">

                        <span class="small">
                            ${processed.toLocaleString('id-ID')}
                            /
                            ${total.toLocaleString('id-ID')}
                        </span>

                        <span class="small fw-semibold">
                            ${progress}%
                        </span>

                    </div>


                    <div
                        class="progress"
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

                    ${
                        item.duration
                            ? `
                                <div class="small text-muted mt-1">
                                    <i class="far fa-clock me-1"></i>
                                    ${escapeHtml(item.duration)}
                                </div>
                              `
                            : ''
                    }

                </td>


                <td>
                    ${total.toLocaleString('id-ID')}
                </td>


                <td>

                    <span class="text-success fw-semibold">
                        ${Number(item.success_rows ?? 0)
                            .toLocaleString('id-ID')}
                    </span>

                </td>


                <td>

                    ${
                        Number(item.failed_rows ?? 0) > 0

                            ? `
                                <span class="text-danger fw-semibold">
                                    ${Number(item.failed_rows)
                                        .toLocaleString('id-ID')}
                                </span>
                              `

                            : `
                                <span class="text-muted">
                                    0
                                </span>
                              `
                    }

                </td>


                <td>
                    ${statusBadge}
                </td>


                <td>

                    <div class="small">

                        ${
                            item.finished_at

                                ? `
                                    <div>
                                        ${escapeHtml(item.finished_at)}
                                    </div>
                                  `

                                : `
                                    <div class="text-muted">
                                        -
                                    </div>
                                  `
                        }

                    </div>

                </td>


                <td>
                    ${action}
                </td>

            </tr>

        `;

    });


    tbody.innerHTML = html;
}


/*
|--------------------------------------------------------------------------
| Escape HTML
|--------------------------------------------------------------------------
*/

function escapeHtml(value)
{
    const div =
        document.createElement('div');

    div.textContent =
        value ?? '';

    return div.innerHTML;
}

</script>

@endsection