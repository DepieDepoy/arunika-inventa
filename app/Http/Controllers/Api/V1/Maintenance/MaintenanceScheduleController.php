<?php

namespace App\Http\Controllers\Api\V1\Maintenance;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\Maintenance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Jadwal & riwayat maintenance (tabel `maintenances`), sumber yang sama
 * dengan menu Maintenance di dashboard.
 */
class MaintenanceScheduleController extends ApiController
{
    /**
     * Query:
     *   status   = scheduled|in_progress|completed|cancelled (boleh dipisah koma)
     *   scope    = open (scheduled+in_progress) | overdue | today | week
     *   from,to  = rentang maintenance_date (YYYY-MM-DD)
     *   asset_id, search (kode maintenance / kode & nama aset), per_page
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'asset_id' => ['nullable', 'integer'],
            'scope' => ['nullable', 'in:open,overdue,today,week'],
        ]);

        $today = today();

        $query = Maintenance::query()
            ->where('company_id', $request->user()->company_id)
            ->with(['asset:id,asset_code,asset_name,location,category_id', 'asset.category:id,category_name', 'vendor:id,vendor_name'])
            ->when($request->filled('status'), fn ($q) => $q->whereIn(
                'status',
                array_filter(explode(',', (string) $request->query('status')))
            ))
            ->when($request->query('scope') === 'open', fn ($q) => $q->whereIn('status', ['scheduled', 'in_progress']))
            ->when($request->query('scope') === 'overdue', fn ($q) => $q
                ->whereIn('status', ['scheduled', 'in_progress'])
                ->whereDate('maintenance_date', '<', $today))
            ->when($request->query('scope') === 'today', fn ($q) => $q
                ->whereIn('status', ['scheduled', 'in_progress'])
                ->whereDate('maintenance_date', $today))
            ->when($request->query('scope') === 'week', fn ($q) => $q
                ->whereIn('status', ['scheduled', 'in_progress'])
                ->whereDate('maintenance_date', '>=', $today)
                ->whereDate('maintenance_date', '<=', $today->copy()->endOfWeek()))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('maintenance_date', '>=', $request->query('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('maintenance_date', '<=', $request->query('to')))
            ->when($request->filled('asset_id'), fn ($q) => $q->where('asset_id', $request->integer('asset_id')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%' . trim((string) $request->query('search')) . '%';
                $q->where(fn ($w) => $w
                    ->where('maintenance_code', 'like', $term)
                    ->orWhereHas('asset', fn ($a) => $a
                        ->where('asset_code', 'like', $term)
                        ->orWhere('asset_name', 'like', $term)));
            });

        // Yang terbuka: terdekat dulu. Riwayat: terbaru dulu.
        $openOnly = in_array($request->query('scope'), ['open', 'overdue', 'today', 'week'], true);
        $query->orderBy('maintenance_date', $openOnly ? 'asc' : 'desc')->orderByDesc('id');

        return $this->paginated(
            'maintenances',
            $query->paginate($this->perPage($request)),
            fn (Maintenance $m) => self::format($m)
        );
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $maintenance = Maintenance::query()
            ->where('company_id', $request->user()->company_id)
            ->with([
                'asset:id,asset_code,asset_name,location,category_id',
                'asset.category:id,category_name',
                'vendor:id,vendor_name',
                'creator:id,name',
                'photos',
            ])
            ->findOrFail($id);

        return $this->ok(['maintenance' => self::format($maintenance, true)]);
    }

    /**
     * Dipakai juga oleh riwayat maintenance per aset (Api\V1\Asset\AssetController).
     */
    public static function format(Maintenance $m, bool $detail = false): array
    {
        $isOpen = in_array($m->status, ['scheduled', 'in_progress'], true);

        $data = [
            'id' => $m->id,
            'maintenance_code' => $m->maintenance_code,
            'maintenance_type' => $m->maintenance_type,
            'maintenance_date' => $m->maintenance_date?->toDateString(),
            'next_maintenance_date' => $m->next_maintenance_date?->toDateString(),
            'status' => $m->status,
            'is_overdue' => $isOpen && $m->maintenance_date && $m->maintenance_date->lt(today()),
            'technician_name' => $m->technician_name,
            'cost' => $m->cost !== null ? (float) $m->cost : null,
            'vendor' => $m->relationLoaded('vendor') && $m->vendor
                ? ['id' => $m->vendor->id, 'name' => $m->vendor->vendor_name]
                : null,
            'maintenance_request_id' => $m->maintenance_request_id,
            'asset' => $m->relationLoaded('asset') && $m->asset ? [
                'id' => $m->asset->id,
                'asset_code' => $m->asset->asset_code,
                'asset_name' => $m->asset->asset_name,
                'location' => $m->asset->location,
                'category' => $m->asset->category?->category_name,
            ] : null,
        ];

        if ($detail) {
            $data += [
                'problem_description' => $m->problem_description,
                'action_taken' => $m->action_taken,
                'notes' => $m->notes,
                'created_by' => $m->creator?->name,
                'created_at' => $m->created_at?->toIso8601String(),
                'photos' => $m->photos->map(fn ($p) => [
                    'id' => $p->id,
                    'type' => $p->photo_type,
                    'caption' => $p->caption,
                    'url' => $p->file_path ? asset('storage/' . ltrim($p->file_path, '/')) : null,
                ])->values(),
            ];
        }

        return $data;
    }
}
