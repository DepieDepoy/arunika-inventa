<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Controllers\Dashboard\AuditTrailController;
use App\Models\AuditLog;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Audit trail perusahaan — khusus Administrator.
 */
class AuditLogController extends ApiController
{
    /**
     * Query: module, event, user_id, from, to, search, per_page.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'user_id' => ['nullable', 'integer'],
        ]);

        $query = AuditLog::query()
            ->where('company_id', $request->user()->company_id)
            ->when($request->filled('module'), fn ($q) => $q->where('module', $request->query('module')))
            ->when($request->filled('event'), fn ($q) => $q->where('event', $request->query('event')))
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->integer('user_id')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->query('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->query('to')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%' . trim((string) $request->query('search')) . '%';
                $q->where(fn ($w) => $w->where('description', 'like', $term)->orWhere('user_name', 'like', $term));
            })
            ->latest('id');

        return $this->paginated('logs', $query->paginate($this->perPage($request, 30)), fn (AuditLog $log) => [
            'id' => $log->id,
            'user_id' => $log->user_id,
            'user_name' => $log->user_name,
            'event' => $log->event,
            'event_label' => AuditTrailController::EVENTS[$log->event] ?? ucfirst(str_replace('_', ' ', $log->event)),
            'module' => $log->module,
            'module_label' => AuditLogger::moduleLabel((string) $log->module),
            'description' => $log->description,
            'source' => $log->source,
            'ip_address' => $log->ip_address,
            'created_at' => $this->dateTime($log->created_at),
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $log = AuditLog::query()
            ->where('company_id', $request->user()->company_id)
            ->findOrFail($id);

        return $this->ok(['log' => [
            'id' => $log->id,
            'user_id' => $log->user_id,
            'user_name' => $log->user_name,
            'event' => $log->event,
            'event_label' => AuditTrailController::EVENTS[$log->event] ?? ucfirst(str_replace('_', ' ', $log->event)),
            'module' => $log->module,
            'module_label' => AuditLogger::moduleLabel((string) $log->module),
            'description' => $log->description,
            'old_values' => $log->old_values,
            'new_values' => $log->new_values,
            'source' => $log->source,
            'ip_address' => $log->ip_address,
            'user_agent' => $log->user_agent,
            'method' => $log->method,
            'created_at' => $this->dateTime($log->created_at),
        ]]);
    }

    /**
     * Pilihan filter (modul & jenis aktivitas).
     */
    public function filters(): JsonResponse
    {
        return $this->ok([
            'modules' => collect(AuditLogger::MODULE_LABELS)
                ->map(fn ($label, $value) => ['value' => $value, 'label' => $label])
                ->values(),
            'events' => collect(AuditTrailController::EVENTS)
                ->map(fn ($label, $value) => ['value' => $value, 'label' => $label])
                ->values(),
        ]);
    }
}
