<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

/**
 * Audit Trail - hanya untuk Administrator,
 * data selalu dibatasi ke PT (company) user yang login.
 */
class AuditTrailController extends Controller
{
    /**
     * Label modul untuk tampilan.
     */
    public const MODULES = AuditLogger::MODULE_LABELS;

    /**
     * Label event untuk tampilan.
     */
    public const EVENTS = [
        'created' => 'Tambah',
        'updated' => 'Ubah',
        'deleted' => 'Hapus',
        'restored' => 'Pulihkan',
        'login' => 'Login',
        'logout' => 'Logout',
        'login_failed' => 'Login Gagal',
        'login_locked' => 'Login Dikunci',
        'registered' => 'Registrasi',
        'password_reset' => 'Reset Password',
        'email_verified' => 'Verifikasi Email',
        'permission_updated' => 'Ubah Permission',
        'exported' => 'Export',
        'printed' => 'Cetak',
        'import_started' => 'Import Dimulai',
        'import_finished' => 'Import Selesai',
    ];

    protected function companyId(): int
    {
        return (int) Auth::user()->company_id;
    }

    public function index(): View
    {
        $users = User::query()
            ->where('company_id', $this->companyId())
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('dashboard.audit-trail.index', [
            'modules' => self::MODULES,
            'events' => self::EVENTS,
            'users' => $users,
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d'],
            'module' => ['nullable', 'string', 'max:50'],
            'event' => ['nullable', 'string', 'max:50'],
            'user_id' => ['nullable', 'integer'],
            'source' => ['nullable', 'in:web,api,system'],
        ]);

        $query = AuditLog::query()
            ->where('company_id', $this->companyId())
            ->select([
                'id',
                'user_id',
                'user_name',
                'event',
                'module',
                'description',
                'source',
                'ip_address',
                'created_at',
            ]);

        if (!empty($validated['date_from'])) {
            $query->where(
                'created_at',
                '>=',
                Carbon::parse($validated['date_from'])->startOfDay()
            );
        }

        if (!empty($validated['date_to'])) {
            $query->where(
                'created_at',
                '<=',
                Carbon::parse($validated['date_to'])->endOfDay()
            );
        }

        foreach (['module', 'event', 'user_id', 'source'] as $field) {
            if (!empty($validated[$field])) {
                $query->where($field, $validated[$field]);
            }
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('encrypted_id', fn (AuditLog $log) => encryptId($log->id))
            ->addColumn('module_label', fn (AuditLog $log) => self::MODULES[$log->module] ?? ucfirst(str_replace('_', ' ', $log->module)))
            ->addColumn('event_label', fn (AuditLog $log) => self::EVENTS[$log->event] ?? ucfirst(str_replace('_', ' ', $log->event)))
            ->editColumn('created_at', fn (AuditLog $log) => $log->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i:s'))
            ->filterColumn('user_name', function ($query, $keyword) {
                $query->where('user_name', 'like', '%' . $keyword . '%');
            })
            ->orderColumn('created_at', 'id $1')
            ->toJson();
    }

    public function show(string $id): JsonResponse
    {
        $logId = decryptId($id);

        abort_unless($logId, 404);

        $log = AuditLog::query()
            ->where('company_id', $this->companyId())
            ->whereKey($logId)
            ->firstOrFail();

        $old = $log->old_values ?? [];
        $new = $log->new_values ?? [];

        $fields = [];

        foreach (array_unique(array_merge(array_keys($old), array_keys($new))) as $key) {
            $fields[] = [
                'field' => (string) $key,
                'old' => array_key_exists($key, $old) ? $old[$key] : null,
                'new' => array_key_exists($key, $new) ? $new[$key] : null,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'created_at' => $log->created_at?->format('d/m/Y H:i:s'),
                'user_name' => $log->user_name ?? 'Sistem',
                'event' => self::EVENTS[$log->event] ?? $log->event,
                'module' => self::MODULES[$log->module] ?? $log->module,
                'description' => $log->description,
                'source' => $log->source,
                'ip_address' => $log->ip_address,
                'user_agent' => $log->user_agent,
                'method' => $log->method,
                'url' => $log->url,
                'record' => $log->auditable_type
                    ? class_basename($log->auditable_type) . ' #' . $log->auditable_id
                    : null,
                'fields' => $fields,
            ],
        ]);
    }
}
