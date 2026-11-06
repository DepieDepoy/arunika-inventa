<?php

namespace App\Services\Backoffice;

use App\Models\BackofficeAdmin;
use App\Models\BackofficeAuditLog;
use Illuminate\Support\Str;
use Throwable;

/**
 * Jejak aktivitas pegawai Vasetra di backoffice.
 * Terpisah dari audit trail pelanggan (yang selalu terikat ke satu perusahaan).
 */
class BackofficeAudit
{
    public static function log(
        string $event,
        string $description,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?BackofficeAdmin $admin = null,
    ): void {
        try {
            $admin ??= auth('backoffice')->user();
            $request = request();

            BackofficeAuditLog::create([
                'backoffice_admin_id' => $admin?->id,
                'admin_name' => $admin?->name ?? 'console',
                'event' => Str::limit($event, 50, ''),
                'description' => Str::limit($description, 252),
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'ip_address' => app()->runningInConsole() ? null : $request?->ip(),
                'user_agent' => app()->runningInConsole() ? 'console' : Str::limit((string) $request?->userAgent(), 252),
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
