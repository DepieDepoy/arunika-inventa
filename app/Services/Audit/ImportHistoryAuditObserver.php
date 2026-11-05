<?php

namespace App\Services\Audit;

use App\Models\ImportHistory;
use App\Models\User;

/**
 * Ringkasan import ke audit trail.
 *
 * Baris-baris hasil import tidak dicatat satu per satu
 * (lihat AuditLogger::withoutAuditing di job import),
 * cukup ringkasan: mulai & selesai.
 */
class ImportHistoryAuditObserver
{
    protected const FINISHED_STATUSES = [
        'completed' => 'selesai',
        'completed_with_errors' => 'selesai dengan error',
        'failed' => 'gagal',
    ];

    public function created(ImportHistory $history): void
    {
        AuditLogger::log(
            event: 'import_started',
            module: $this->module($history),
            description: 'Memulai import ' . $this->moduleLabel($history)
                . ' dari file "' . $history->file_name . '"',
            subject: $history,
            newValues: [
                'file_name' => $history->file_name,
                'total_rows' => (int) $history->total_rows,
            ],
            companyId: (int) $history->company_id,
            actor: $this->actor($history),
        );
    }

    public function updated(ImportHistory $history): void
    {
        if (!$history->wasChanged('status')) {
            return;
        }

        $status = (string) $history->status;

        if (!array_key_exists($status, self::FINISHED_STATUSES)) {
            return;
        }

        AuditLogger::log(
            event: 'import_finished',
            module: $this->module($history),
            description: 'Import ' . $this->moduleLabel($history)
                . ' ' . self::FINISHED_STATUSES[$status]
                . ': ' . (int) $history->success_rows . ' berhasil, '
                . (int) $history->failed_rows . ' gagal',
            subject: $history,
            newValues: [
                'file_name' => $history->file_name,
                'status' => $status,
                'total_rows' => (int) $history->total_rows,
                'success_rows' => (int) $history->success_rows,
                'failed_rows' => (int) $history->failed_rows,
            ],
            companyId: (int) $history->company_id,
            actor: $this->actor($history),
        );
    }

    protected function module(ImportHistory $history): string
    {
        return strtolower((string) $history->module) ?: 'import';
    }

    protected function moduleLabel(ImportHistory $history): string
    {
        return ucfirst($this->module($history));
    }

    protected function actor(ImportHistory $history): ?User
    {
        return $history->user_id
            ? User::find($history->user_id)
            : null;
    }
}
