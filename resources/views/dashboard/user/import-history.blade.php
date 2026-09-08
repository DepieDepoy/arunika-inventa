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
                Riwayat proses import data user
            </p>

        </div>

        <a
            href="{{ route('users.import') }}"
            class="btn btn-primary"
        >
            <i class="fas fa-file-import me-1"></i>
            Import User
        </a>

    </div>


    {{-- =========================================================
         HISTORY TABLE
    ========================================================== --}}

    <div class="card">

        <div class="card-body p-0">

            <div class="table-responsive">

                <table
                    class="table table-hover align-middle mb-0"
                    id="importHistoryTable"
                >

                    <thead>

                        <tr>

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

                            <th style="min-width: 190px;">
                                Waktu
                            </th>

                            <th class="text-end">
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                    @forelse($histories as $history)

                        @php

                            $total =
                                (int) $history->total_rows;

                            $success =
                                (int) $history->success_rows;

                            $failed =
                                (int) $history->failed_rows;

                            $processed =
                                $success + $failed;

                            $progress =
                                $total > 0
                                    ? min(
                                        round(
                                            (
                                                $processed /
                                                $total
                                            ) * 100
                                        ),
                                        100
                                    )
                                    : 0;

                        @endphp


                        <tr
                            data-history-id="{{ $history->id }}"
                        >

                            {{-- =================================================
                                 FILE
                            ================================================== --}}

                            <td>

                                <div class="d-flex align-items-center">

                                    <div
                                        class="rounded bg-success bg-opacity-10 p-2 me-2"
                                    >

                                        <i
                                            class="fas fa-file-excel text-success"
                                        ></i>

                                    </div>

                                    <div>

                                        <div class="fw-semibold">
                                            {{ $history->file_name }}
                                        </div>

                                        <small class="text-muted">
                                            Import User
                                        </small>

                                    </div>

                                </div>

                            </td>


                            {{-- =================================================
                                 PROGRESS
                            ================================================== --}}

                            <td>

                                <div class="progress-wrapper">

                                    <div
                                        class="d-flex justify-content-between align-items-center mb-1"
                                    >

                                        <small
                                            class="text-muted progress-text"
                                        >
                                            {{ number_format($processed) }}
                                            /
                                            {{ number_format($total) }}
                                        </small>

                                        <strong
                                            class="progress-percent"
                                        >
                                            {{ $progress }}%
                                        </strong>

                                    </div>


                                    <div
                                        class="progress"
                                        style="height: 8px;"
                                    >

                                        <div
                                            class="progress-bar progress-bar-striped progress-bar-animated"
                                            role="progressbar"
                                            style="width: {{ $progress }}%"
                                            aria-valuenow="{{ $progress }}"
                                            aria-valuemin="0"
                                            aria-valuemax="100"
                                        ></div>

                                    </div>

                                </div>

                            </td>


                            {{-- =================================================
                                 TOTAL
                            ================================================== --}}

                            <td>

                                <span class="fw-semibold">

                                    {{ number_format($total) }}

                                </span>

                            </td>


                            {{-- =================================================
                                 SUCCESS
                            ================================================== --}}

                            <td>

                                <span
                                    class="badge bg-success success-count"
                                >

                                    {{ number_format($success) }}

                                </span>

                            </td>


                            {{-- =================================================
                                 FAILED
                            ================================================== --}}

                            <td>

                                <span
                                    class="badge bg-danger failed-count"
                                >

                                    {{ number_format($failed) }}

                                </span>

                            </td>


                            {{-- =================================================
                                 STATUS
                            ================================================== --}}

                            <td>

                                <span class="status-container">

                                    @if($history->status === 'processing')

                                        <span
                                            class="badge bg-warning text-dark status-badge"
                                        >

                                            <i
                                                class="fas fa-spinner fa-spin me-1"
                                            ></i>

                                            Processing

                                        </span>

                                    @elseif($history->status === 'completed')

                                        <span
                                            class="badge bg-success status-badge"
                                        >

                                            <i
                                                class="fas fa-check me-1"
                                            ></i>

                                            Completed

                                        </span>

                                    @elseif($history->status === 'completed_with_errors')

                                        <span
                                            class="badge bg-warning text-dark status-badge"
                                        >

                                            <i
                                                class="fas fa-exclamation-triangle me-1"
                                            ></i>

                                            Completed with Errors

                                        </span>

                                    @else

                                        <span
                                            class="badge bg-danger status-badge"
                                        >

                                            <i
                                                class="fas fa-times me-1"
                                            ></i>

                                            Failed

                                        </span>

                                    @endif

                                </span>

                            </td>


                            {{-- =================================================
                                 WAKTU
                            ================================================== --}}

                            <td>

                                {{-- CREATED AT --}}

                                <div class="created-time">

                                    {{ $history->created_at?->format('d M Y H:i') }}

                                </div>


                                {{-- START --}}

                                <div class="small text-muted">

                                    <span class="fw-semibold">
                                        Mulai:
                                    </span>

                                    <span class="started-time">

                                        @if($history->started_at)

                                            {{ $history->started_at->format('H:i:s') }}

                                        @else

                                            -

                                        @endif

                                    </span>

                                </div>


                                {{-- FINISHED --}}

                                <div class="small text-muted finished-wrapper">

                                    <span class="fw-semibold">
                                        Selesai:
                                    </span>

                                    <span class="finished-time">

                                        @if($history->finished_at)

                                            {{ $history->finished_at->format('H:i:s') }}

                                        @else

                                            -

                                        @endif

                                    </span>

                                </div>


                                {{-- DURATION --}}

                                <div class="small duration-wrapper">

                                    <span class="fw-semibold">
                                        Durasi:
                                    </span>

                                    <span class="duration-text">

                                        @if($history->started_at)

                                            @php

                                                $endTime =
                                                    $history->finished_at
                                                        ?? now();

                                                $durationSeconds =
                                                    $history->started_at
                                                        ->diffInSeconds(
                                                            $endTime
                                                        );

                                                $hours =
                                                    intdiv(
                                                        $durationSeconds,
                                                        3600
                                                    );

                                                $minutes =
                                                    intdiv(
                                                        $durationSeconds % 3600,
                                                        60
                                                    );

                                                $seconds =
                                                    $durationSeconds % 60;

                                            @endphp


                                            @if($hours > 0)

                                                {{ $hours }} jam

                                                @if($minutes > 0)
                                                    {{ $minutes }} menit
                                                @endif

                                                @if($seconds > 0)
                                                    {{ $seconds }} detik
                                                @endif

                                            @elseif($minutes > 0)

                                                {{ $minutes }} menit

                                                @if($seconds > 0)
                                                    {{ $seconds }} detik
                                                @endif

                                            @else

                                                {{ $seconds }} detik

                                            @endif


                                            @if($history->status === 'processing')

                                                <span class="text-warning">
                                                    (berjalan)
                                                </span>

                                            @endif

                                        @else

                                            -

                                        @endif

                                    </span>

                                </div>

                            </td>


                            {{-- =================================================
                                 ACTION
                            ================================================== --}}

                            <td class="text-end">

                                <a
                                    href="{{ route(
                                        'users.import.history.detail',
                                        $history->id
                                    ) }}"
                                    class="btn btn-sm btn-outline-primary"
                                >

                                    <i class="fas fa-eye me-1"></i>

                                    Detail

                                </a>

                            </td>

                        </tr>


                    @empty

                        <tr>

                            <td
                                colspan="8"
                                class="text-center py-5 text-muted"
                            >

                                <i
                                    class="fas fa-history fa-2x mb-3"
                                ></i>

                                <div>
                                    Belum ada riwayat import.
                                </div>

                            </td>

                        </tr>

                    @endforelse

                    </tbody>

                </table>

            </div>

        </div>


        {{-- =========================================================
             PAGINATION
        ========================================================== --}}

        @if($histories->hasPages())

            <div class="card-footer">

                {{ $histories->links() }}

            </div>

        @endif

    </div>

</div>



{{-- =========================================================
     LIVE IMPORT PROGRESS
========================================================= --}}

<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const progressUrl = "{{ route('users.import.history.progress') }}";


        let polling = null;


        /*
         * ======================================================
         * UPDATE HISTORY
         * ======================================================
         */

        function updateHistory() {

            fetch(
                progressUrl,
                {
                    method: 'GET',

                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }
            )

            .then(response => {

                if (!response.ok) {

                    throw new Error(
                        'Gagal mengambil progress import.'
                    );

                }

                return response.json();

            })


            .then(histories => {

                let stillProcessing = false;


                histories.forEach(history => {

                    const row =
                        document.querySelector(
                            `tr[data-history-id="${history.id}"]`
                        );


                    /*
                     * Row tidak ada di halaman saat ini
                     */
                    if (!row) {

                        return;

                    }


                    /*
                     * ==================================================
                     * PROGRESS
                     * ==================================================
                     */

                    const progressBar =
                        row.querySelector(
                            '.progress-bar'
                        );


                    const progressPercent =
                        row.querySelector(
                            '.progress-percent'
                        );


                    const progressText =
                        row.querySelector(
                            '.progress-text'
                        );


                    if (progressBar) {

                        progressBar.style.width =
                            history.progress + '%';


                        progressBar.setAttribute(
                            'aria-valuenow',
                            history.progress
                        );

                    }


                    if (progressPercent) {

                        progressPercent.textContent =
                            history.progress + '%';

                    }


                    if (progressText) {

                        progressText.textContent =
                            Number(
                                history.processed_rows
                            ).toLocaleString(
                                'id-ID'
                            )
                            +
                            ' / '
                            +
                            Number(
                                history.total_rows
                            ).toLocaleString(
                                'id-ID'
                            );

                    }


                    /*
                     * ==================================================
                     * SUCCESS
                     * ==================================================
                     */

                    const successCount =
                        row.querySelector(
                            '.success-count'
                        );


                    if (successCount) {

                        successCount.textContent =
                            Number(
                                history.success_rows
                            ).toLocaleString(
                                'id-ID'
                            );

                    }


                    /*
                     * ==================================================
                     * FAILED
                     * ==================================================
                     */

                    const failedCount =
                        row.querySelector(
                            '.failed-count'
                        );


                    if (failedCount) {

                        failedCount.textContent =
                            Number(
                                history.failed_rows
                            ).toLocaleString(
                                'id-ID'
                            );

                    }


                    /*
                     * ==================================================
                     * STATUS
                     * ==================================================
                     */

                    const statusContainer =
                        row.querySelector(
                            '.status-container'
                        );


                    if (statusContainer) {

                        if (
                            history.status ===
                            'processing'
                        ) {

                            statusContainer.innerHTML = `

                                <span
                                    class="badge bg-warning text-dark status-badge"
                                >

                                    <i
                                        class="fas fa-spinner fa-spin me-1"
                                    ></i>

                                    Processing

                                </span>

                            `;


                            stillProcessing = true;

                        }


                        else if (
                            history.status ===
                            'completed'
                        ) {

                            statusContainer.innerHTML = `

                                <span
                                    class="badge bg-success status-badge"
                                >

                                    <i
                                        class="fas fa-check me-1"
                                    ></i>

                                    Completed

                                </span>

                            `;

                        }


                        else if (
                            history.status ===
                            'completed_with_errors'
                        ) {

                            statusContainer.innerHTML = `

                                <span
                                    class="badge bg-warning text-dark status-badge"
                                >

                                    <i
                                        class="fas fa-exclamation-triangle me-1"
                                    ></i>

                                    Completed with Errors

                                </span>

                            `;

                        }


                        else if (
                            history.status ===
                            'failed'
                        ) {

                            statusContainer.innerHTML = `

                                <span
                                    class="badge bg-danger status-badge"
                                >

                                    <i
                                        class="fas fa-times me-1"
                                    ></i>

                                    Failed

                                </span>

                            `;

                        }

                    }


                    /*
                     * ==================================================
                     * WAKTU
                     * ==================================================
                     */

                    const startedTime =
                        row.querySelector(
                            '.started-time'
                        );


                    const finishedTime =
                        row.querySelector(
                            '.finished-time'
                        );


                    const durationText =
                        row.querySelector(
                            '.duration-text'
                        );


                    /*
                     * STARTED
                     */

                    if (startedTime) {

                        startedTime.textContent =
                            history.started_at
                                ? history.started_at.substring(11)
                                : '-';

                    }


                    /*
                     * FINISHED
                     */

                    if (finishedTime) {

                        finishedTime.textContent =
                            history.finished_at
                                ? history.finished_at.substring(11)
                                : '-';

                    }


                    /*
                     * DURATION
                     */

                    if (durationText) {

                        durationText.innerHTML =
                            history.duration ?? '-';

                    }


                    /*
                     * ==================================================
                     * PROGRESS BAR ANIMATION
                     * ==================================================
                     */

                    if (
                        history.status !==
                        'processing'
                    ) {

                        if (progressBar) {

                            progressBar.classList.remove(
                                'progress-bar-animated'
                            );


                            progressBar.classList.remove(
                                'progress-bar-striped'
                            );

                        }

                    }


                    /*
                     * ==================================================
                     * PROCESSING CHECK
                     * ==================================================
                     */

                    if (
                        history.status ===
                        'processing'
                    ) {

                        stillProcessing = true;

                    }

                });


                /*
                 * ==================================================
                 * STOP POLLING
                 * ==================================================
                 */

                if (!stillProcessing) {

                    clearInterval(
                        polling
                    );

                    polling = null;

                }

            })


            .catch(error => {

                console.error(
                    'Import progress error:',
                    error
                );

            });

        }


        /*
         * ======================================================
         * RUN IMMEDIATELY
         * ======================================================
         */

        updateHistory();


        /*
         * ======================================================
         * POLLING EVERY 2 SECONDS
         * ======================================================
 */

        polling =
            setInterval(
                updateHistory,
                2000
            );

    }
);

</script>

@endsection