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
        $query = ImportHistory::query()
            ->where('company_id', Auth::user()->company_id)
            ->select([
                'id',
                'company_id',
                'module',
                'file_name',
                'total_rows',
                'success_rows',
                'failed_rows',
                'status',
                'started_at',
                'finished_at',
                'created_at',
            ]);

        return DataTables::of($query)

            ->addIndexColumn()

            ->addColumn('encrypted_id', function ($history) {
                return encryptId((string) $history->id);
            })

            ->addColumn('processed_rows', function ($history) {
                return (int) $history->success_rows
                    + (int) $history->failed_rows;
            })

            ->addColumn('progress', function ($history) {
                $total = (int) $history->total_rows;

                if ($total <= 0) {
                    return 0;
                }

                $processed = (int) $history->success_rows
                    + (int) $history->failed_rows;

                return min(
                    (int) round(($processed / $total) * 100),
                    100
                );
            })

            ->addColumn('duration', function ($history) {
                if (!$history->started_at) {
                    return null;
                }

                $endTime = $history->finished_at ?? now();

                $seconds = (int) $history->started_at
                    ->diffInSeconds($endTime);

                $hours = intdiv($seconds, 3600);
                $minutes = intdiv($seconds % 3600, 60);
                $remainingSeconds = $seconds % 60;

                $parts = [];

                if ($hours > 0) {
                    $parts[] = $hours . ' jam';
                }

                if ($minutes > 0) {
                    $parts[] = $minutes . ' menit';
                }

                if ($remainingSeconds > 0 || empty($parts)) {
                    $parts[] = $remainingSeconds . ' detik';
                }

                $duration = implode(' ', $parts);

                if ($history->status === 'processing') {
                    $duration .= ' (berjalan)';
                }

                return $duration;
            })

            ->editColumn('started_at', function ($history) {
                return $history->started_at
                    ? $history->started_at->format('d M Y H:i:s')
                    : null;
            })

            ->orderColumn('started_at', 'started_at $1')

            ->orderColumn('module', 'module $1')

            ->orderColumn('file_name', 'file_name $1')

            ->orderColumn('status', 'status $1')

            ->orderColumn('total_rows', 'total_rows $1')

            ->orderColumn('success_rows', 'success_rows $1')

            ->orderColumn('failed_rows', 'failed_rows $1')

            ->toJson();
    }

    /**
     * =========================================================
     * IMPORT HISTORY DETAIL
     * =========================================================
     */
    
    public function detail(string $id)
    {
        try {
            $id = decryptId($id);
        } catch (\Throwable $e) {
            abort(404);
        }

        if (!is_numeric($id)) {
            abort(404);
        }

        $history = ImportHistory::where(
            'company_id',
            Auth::user()->company_id
        )->findOrFail((int) $id);

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