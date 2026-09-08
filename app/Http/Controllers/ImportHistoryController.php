<?php

namespace App\Http\Controllers;

use App\Models\ImportHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class ImportHistoryController extends Controller
{
    /**
     * =========================================================
     * IMPORT HISTORY INDEX
     * =========================================================
     */
    public function index()
    {
        return view(
            'dashboard.import-history.index'
        );
    }


    /**
     * =========================================================
     * IMPORT HISTORY PROGRESS
     * =========================================================
     */
    public function progress()
    {
        $histories = ImportHistory::where(
            'company_id',
            Auth::user()->company_id
        )
            ->latest()
            ->get();

        $data = $histories->map(function ($history) {

            $total = (int) $history->total_rows;
            $success = (int) $history->success_rows;
            $failed = (int) $history->failed_rows;

            $processed = $success + $failed;

            $progress = $total > 0
                ? min(
                    round(
                        ($processed / $total) * 100
                    ),
                    100
                )
                : 0;

            $duration = null;

            if ($history->started_at) {

                $endTime =
                    $history->finished_at ?? now();

                $seconds =
                    $history->started_at
                        ->diffInSeconds($endTime);

                $hours =
                    intdiv(
                        $seconds,
                        3600
                    );

                $minutes =
                    intdiv(
                        $seconds % 3600,
                        60
                    );

                $remainingSeconds =
                    $seconds % 60;

                if ($hours > 0) {

                    $duration =
                        $hours . ' jam';

                    if ($minutes > 0) {

                        $duration .=
                            ' ' .
                            $minutes .
                            ' menit';
                    }

                    if ($remainingSeconds > 0) {

                        $duration .=
                            ' ' .
                            $remainingSeconds .
                            ' detik';
                    }

                } elseif ($minutes > 0) {

                    $duration =
                        $minutes . ' menit';

                    if ($remainingSeconds > 0) {

                        $duration .=
                            ' ' .
                            $remainingSeconds .
                            ' detik';
                    }

                } else {

                    $duration =
                        $remainingSeconds .
                        ' detik';
                }

                if (
                    $history->status ===
                    'processing'
                ) {

                    $duration .=
                        ' (berjalan)';
                }
            }

            return [
                'id' =>
                    $history->id,

                'module' =>
                    $history->module,

                'file_name' =>
                    $history->file_name,

                'total_rows' =>
                    $total,

                'success_rows' =>
                    $success,

                'failed_rows' =>
                    $failed,

                'processed_rows' =>
                    $processed,

                'progress' =>
                    $progress,

                'status' =>
                    $history->status,

                'started_at' =>
                    $history->started_at
                        ? $history->started_at
                            ->format(
                                'd M Y H:i:s'
                            )
                        : null,

                'finished_at' =>
                    $history->finished_at
                        ? $history->finished_at
                            ->format(
                                'd M Y H:i:s'
                            )
                        : null,

                'duration' =>
                    $duration,
            ];
        });

        return response()->json(
            $data
        );
    }


    /**
     * =========================================================
     * IMPORT HISTORY DETAIL
     * =========================================================
     */
    public function detail(int $id)
    {
        $history = ImportHistory::where(
            'company_id',
            Auth::user()->company_id
        )
            ->findOrFail($id);

        return view(
            'dashboard.import-history.detail',
            compact('history')
        );
    }


    /**
     * =========================================================
     * IMPORT HISTORY DETAIL ERRORS
     * =========================================================
     */
    public function detailErrors(
        Request $request,
        int $id
    ) {
        $history = ImportHistory::where(
            'company_id',
            Auth::user()->company_id
        )
            ->findOrFail($id);

        $query = $history->errors()
            ->select([
                'id',
                //'row_number',
                'data',
                'error_message',
                'created_at',
            ]);

        return DataTables::of($query)

            /**
             * ROW
             */

            /**
             * DATA
             */
            ->addColumn(
                'data_display',
                function ($error) {

                    if (empty($error->data)) {

                        return
                            '<span class="text-muted">'
                            . '-'
                            . '</span>';
                    }

                    $data =
                        is_array($error->data)
                            ? $error->data
                            : json_decode(
                                $error->data,
                                true
                            );

                    if (!is_array($data)) {

                        return e(
                            $error->data
                        );
                    }

                    return
                        '<pre class="mb-0 small" style="
                            max-width: 500px;
                            max-height: 160px;
                            overflow: auto;
                            white-space: pre-wrap;
                        ">' .

                        e(
                            json_encode(
                                $data,
                                JSON_PRETTY_PRINT |
                                JSON_UNESCAPED_UNICODE |
                                JSON_UNESCAPED_SLASHES
                            )
                        ) .

                        '</pre>';
                }
            )

            /**
             * ERROR MESSAGE
             */
            ->addColumn(
                'error_display',
                function ($error) {

                    return
                        '<span class="text-danger">'
                        .
                        e(
                            $error->error_message
                        )
                        .
                        '</span>';
                }
            )

            /**
             * RAW HTML
             */
            ->rawColumns([
                'data_display',
                'error_display',
            ])

            /**
             * ORDER ROW
             */
            ->orderColumn(
                'row_display',
                'row_number $1'
            )

            ->make(true);
    }
}