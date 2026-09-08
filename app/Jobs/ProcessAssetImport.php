<?php

namespace App\Jobs;

use App\Imports\AssetImport;
use App\Models\ImportHistory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ProcessAssetImport implements ShouldQueue
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

    /*
    |--------------------------------------------------------------------------
    | Data
    |--------------------------------------------------------------------------
    */

    protected int $importHistoryId;

    protected string $filePath;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    */

    public function __construct(
        int $importHistoryId,
        string $filePath
    ) {
        $this->importHistoryId = $importHistoryId;
        $this->filePath = $filePath;
    }

    /*
    |--------------------------------------------------------------------------
    | Handle
    |--------------------------------------------------------------------------
    */

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
                'status' => 'processing',
                'started_at' => now(),
                'finished_at' => null,
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
                new AssetImport($history),
                $fullPath
            );

            /*
            |--------------------------------------------------------------------------
            | Calculate Result
            |--------------------------------------------------------------------------
            */

            $history->refresh();

            $successRows = (int) $history->success_rows;

            $failedRows = (int) $history->failed_rows;

            /*
            |--------------------------------------------------------------------------
            | Update History
            |--------------------------------------------------------------------------
            */

            $history->update([
                'success_rows' => $successRows,
                'failed_rows' => $failedRows,

                'status' => $failedRows > 0
                    ? 'completed_with_errors'
                    : 'completed',

                'finished_at' => now(),
            ]);

            /*
            |--------------------------------------------------------------------------
            | Delete Temporary File
            |--------------------------------------------------------------------------
            */

            Storage::delete(
                $this->filePath
            );

        } catch (\Throwable $e) {

            /*
            |--------------------------------------------------------------------------
            | Log Error
            |--------------------------------------------------------------------------
            */

            Log::error(
                'Process Asset Import gagal',
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
                'status' => 'failed',
                'finished_at' => now(),
            ]);

            /*
            |--------------------------------------------------------------------------
            | Delete Temporary File
            |--------------------------------------------------------------------------
            */

            Storage::delete(
                $this->filePath
            );

            /*
            |--------------------------------------------------------------------------
            | Throw Again
            |--------------------------------------------------------------------------
            */

            throw $e;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Failed
    |--------------------------------------------------------------------------
    */

    public function failed(\Throwable $exception): void
    {
        try {

            $history = ImportHistory::find(
                $this->importHistoryId
            );

            if ($history) {

                $history->update([
                    'status' => 'failed',
                    'finished_at' => now(),
                ]);
            }

            Log::error(
                'Asset import job failed',
                [
                    'import_history_id' =>
                        $this->importHistoryId,

                    'file' =>
                        $this->filePath,

                    'error' =>
                        $exception->getMessage(),
                ]
            );

        } catch (\Throwable $e) {

            Log::error(
                'Gagal update ImportHistory setelah Asset Import failed',
                [
                    'import_history_id' =>
                        $this->importHistoryId,

                    'error' =>
                        $e->getMessage(),
                ]
            );
        }
    }
}
