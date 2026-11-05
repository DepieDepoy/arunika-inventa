<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Pencatat audit trail.
 *
 * Contoh pemakaian manual:
 *
 *   AuditLogger::log(
 *       event: 'exported',
 *       module: 'asset',
 *       description: 'Export data asset',
 *   );
 *
 * Pencatatan TIDAK PERNAH menggagalkan proses bisnis:
 * semua error ditangkap dan hanya dilaporkan ke log.
 */
class AuditLogger
{
    /**
     * Penanda untuk menonaktifkan audit otomatis
     * (dipakai saat import massal, diganti log ringkasan).
     */
    protected static int $disabledDepth = 0;

    /**
     * Field yang tidak pernah disimpan nilainya.
     */
    public const SENSITIVE_FIELDS = [
        'password',
        'remember_token',
        'token',
        'api_key',
        'secret',
        'server_key',
        'client_secret',
    ];

    /**
     * Label modul (dipakai di keterangan & halaman Audit Trail).
     */
    public const MODULE_LABELS = [
        'auth' => 'Autentikasi',
        'company' => 'Perusahaan',
        'user' => 'User',
        'role' => 'Role & Permission',
        'category' => 'Kategori',
        'sub_category' => 'Sub Kategori',
        'vendor' => 'Vendor',
        'asset' => 'Asset',
        'asset_photo' => 'Foto Asset',
        'asset_document' => 'Dokumen Asset',
        'asset_assignment' => 'Penugasan Asset',
        'maintenance_request' => 'Request Maintenance',
        'maintenance' => 'Maintenance',
        'calendar' => 'Kalender',
        'subscription' => 'Subscription',
        'payment' => 'Pembayaran',
    ];

    public static function moduleLabel(string $module): string
    {
        return static::MODULE_LABELS[$module]
            ?? Str::of($module)->replace('_', ' ')->title()->toString();
    }

    public static function isEnabled(): bool
    {
        return static::$disabledDepth === 0;
    }

    /**
     * Jalankan callback tanpa audit otomatis dari model.
     */
    public static function withoutAuditing(callable $callback): mixed
    {
        static::$disabledDepth++;

        try {
            return $callback();
        } finally {
            static::$disabledDepth--;
        }
    }

    /**
     * Simpan satu catatan audit.
     *
     * @param  int|null  $companyId  Default: company user yang login.
     * @param  User|null $actor      Default: user yang login.
     */
    public static function log(
        string $event,
        string $module,
        string $description,
        ?Model $subject = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $companyId = null,
        ?User $actor = null,
    ): ?AuditLog {
        try {
            $actor ??= static::currentUser();

            $companyId ??= $actor?->company_id
                ? (int) $actor->company_id
                : null;

            // Audit selalu terikat ke satu PT.
            if (!$companyId) {
                return null;
            }

            $context = static::requestContext();

            return AuditLog::create([
                'company_id' => $companyId,
                'user_id' => $actor?->getKey(),
                'user_name' => $actor?->name
                    ? Str::limit((string) $actor->name, 147)
                    : null,
                'event' => Str::limit($event, 50, ''),
                'module' => Str::limit($module, 50, ''),
                'description' => Str::limit($description, 252),
                'auditable_type' => $subject ? $subject->getMorphClass() : null,
                'auditable_id' => $subject?->getKey(),
                'old_values' => $oldValues ? static::sanitize($oldValues) : null,
                'new_values' => $newValues ? static::sanitize($newValues) : null,
                ...$context,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    /**
     * User yang sedang login (web session atau Sanctum token).
     */
    public static function currentUser(): ?User
    {
        try {
            $user = Auth::user() ?? Auth::guard('sanctum')->user();
        } catch (\Throwable) {
            return null;
        }

        return $user instanceof User ? $user : null;
    }

    /**
     * Sembunyikan field sensitif & potong nilai yang terlalu panjang.
     */
    public static function sanitize(array $values): array
    {
        $clean = [];

        foreach ($values as $key => $value) {
            if (in_array(Str::lower((string) $key), static::SENSITIVE_FIELDS, true)) {
                $clean[$key] = '[hidden]';
                continue;
            }

            if (is_string($value) && mb_strlen($value) > 1000) {
                $value = mb_substr($value, 0, 1000) . '…';
            }

            if ($value instanceof \DateTimeInterface) {
                $value = $value->format('Y-m-d H:i:s');
            }

            if (is_object($value) && !($value instanceof \JsonSerializable)) {
                $value = method_exists($value, '__toString')
                    ? (string) $value
                    : get_class($value);
            }

            $clean[$key] = $value;
        }

        return $clean;
    }

    /**
     * Informasi request (IP, browser, URL).
     */
    protected static function requestContext(): array
    {
        if (app()->runningInConsole()) {
            return [
                'source' => 'system',
                'ip_address' => null,
                'user_agent' => null,
                'method' => null,
                'url' => null,
            ];
        }

        $request = request();

        /*
         * URL tanpa query string, dan token di path (mis. reset password)
         * disamarkan agar audit log tidak menyimpan rahasia.
         */
        $url = $request->url();
        $token = $request->route('token');

        if (is_string($token) && $token !== '') {
            $url = str_replace($token, '***', $url);
        }

        return [
            'source' => $request->is('api/*') ? 'api' : 'web',
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 497),
            'method' => $request->method(),
            'url' => Str::limit($url, 497),
        ];
    }
}
