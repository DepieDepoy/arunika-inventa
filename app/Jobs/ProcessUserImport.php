<?php

namespace App\Jobs;

use App\Imports\UserImport;
use App\Models\ImportHistory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ProcessUserImport implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    */

    public int $timeout = 3600;

    public int $tries = 1;

    protected int $importHistoryId;

    protected string $filePath;

    public function __construct(
        int $importHistoryId,
        string $filePath
    ) {
        $this->importHistoryId = $importHistoryId;
        $this->filePath = $filePath;
    }

    public function handle(): void
    {
        $history = ImportHistory::findOrFail(
            $this->importHistoryId
        );

        try {

            /*
            |--------------------------------------------------------------------------
            | Processing
            |--------------------------------------------------------------------------
            */

            $history->update([
                'status' =>
                    'processing',

                'started_at' =>
                    now(),
            ]);

            /*
            |--------------------------------------------------------------------------
            | File
            |--------------------------------------------------------------------------
            */

            $fullPath = Storage::path(
                $this->filePath
            );

            if (!file_exists($fullPath)) {

                throw new \RuntimeException(
                    'File Excel import tidak ditemukan.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Import
            |--------------------------------------------------------------------------
            */

            Excel::import(
                new UserImport($history),
                $fullPath
            );

            /*
            |--------------------------------------------------------------------------
            | Calculate Result
            |--------------------------------------------------------------------------
            */

            $history->refresh();

            $successRows =
                (int) $history->success_rows;

            $failedRows =
                (int) $history->failed_rows;

            /*
            |--------------------------------------------------------------------------
            | Update History
            |--------------------------------------------------------------------------
            */

            $history->update([
                'success_rows' =>
                    $successRows,

                'failed_rows' =>
                    $failedRows,

                'status' =>
                    $failedRows > 0
                        ? 'completed_with_errors'
                        : 'completed',

                'finished_at' =>
                    now(),
            ]);

            /*
            |--------------------------------------------------------------------------
            | Delete temporary file
            |--------------------------------------------------------------------------
            */

            Storage::delete(
                $this->filePath
            );

        } catch (\Throwable $e) {

            Log::error(
                'Process User Import gagal',
                [
                    'import_history_id' =>
                        $this->importHistoryId,

                    'file' =>
                        $this->filePath,

                    'error' =>
                        $e->getMessage(),

                    'trace' =>
                        $e->getTraceAsString(),
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | Mark Failed
            |--------------------------------------------------------------------------
            */

            $history->update([
                'status' =>
                    'failed',

                'finished_at' =>
                    now(),
            ]);

            /*
            |--------------------------------------------------------------------------
            | Delete Temporary File
            |--------------------------------------------------------------------------
            */

            Storage::delete(
                $this->filePath
            );

            throw $e;
        }
    }
}