<?php

namespace App\Services\Asset;

use App\Models\Asset;
use App\Models\AssetAudit;
use App\Models\AssetAuditAssignee;
use App\Models\AssetAuditItem;
use App\Models\Location;
use App\Models\Scopes\BranchScope;
use App\Models\User;
use App\Notifications\GlobalNotification;
use App\Support\ActionFailed;
use App\Support\BranchAccess;
use App\Support\NotificationText;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Aturan Audit Fisik Aset (dipakai web & API mobile).
 *
 *  1. Pengelola audit (izin asset_audit.create) membuat sesi: cabang + lokasi
 *     (opsional) + PETUGAS (opsional, masing-masing boleh punya area lokasi).
 *     Aset sesuai cakupan masuk daftar sebagai "belum dicek"; petugas diberi notifikasi.
 *  2. Petugas scan dengan akunnya masing-masing (checked_by tercatat).
 *     - Lokasi petugas dipilih dari master lokasi; kosong = lokasi tidak dicek.
 *     - Status: ditemukan, salah lokasi (lokasi berbeda di cabang yang sama),
 *       beda cabang (aset milik cabang lain), di luar daftar, tidak ditemukan.
 *  3. Selesaikan -> yang belum dicek = tidak ditemukan; lokasi salah bisa diperbarui.
 */
class AssetAuditService
{
    public const FILTERS = ['all', 'pending', 'found', 'misplaced', 'damaged', 'missing', 'extra', 'other_branch', 'mine'];

    public const TYPE = 'asset_audit';

    public function __construct(
        protected AssetLifecycleService $lifecycle
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | AKSES
    |--------------------------------------------------------------------------
    */

    /**
     * Audit yang boleh dilihat user: semua (kantor pusat), audit cabangnya,
     * atau audit tempat ia ditugaskan sebagai petugas.
     */
    public function visibleQuery(User $user): Builder
    {
        $restricted = BranchAccess::restrictedBranchId($user);

        return AssetAudit::query()
            ->where('company_id', $user->company_id)
            ->when($restricted, fn ($q) => $q->where(fn ($w) => $w
                ->where('branch_id', $restricted)
                ->orWhereHas('assignees', fn ($a) => $a->where('user_id', $user->id))));
    }

    public function find(User $user, int $id): AssetAudit
    {
        $audit = $this->visibleQuery($user)->find($id);

        if (!$audit) {
            throw new ActionFailed('Audit tidak ditemukan.', 'NOT_FOUND', 404);
        }

        return $audit;
    }

    public function isAssignee(User $user, AssetAudit $audit): bool
    {
        return $audit->assignees()->where('user_id', $user->id)->exists();
    }

    /**
     * Boleh scan? Bila audit punya petugas, hanya petugas & pengelola audit.
     */
    public function canScan(User $user, AssetAudit $audit): bool
    {
        if (!$audit->isOpen() || !$user->hasPermission('asset_audit.scan')) {
            return false;
        }

        return !$audit->assignees()->exists()
            || $this->isAssignee($user, $audit)
            || $user->hasPermission('asset_audit.create');
    }

    public function canManage(User $user, AssetAudit $audit): bool
    {
        return $audit->isOpen() && $user->hasPermission('asset_audit.create');
    }

    /**
     * Calon petugas: user aktif berizin scan (Administrator otomatis),
     * dalam cakupan cabang pembuat audit.
     */
    public function assigneeCandidates(User $user): Collection
    {
        return BranchAccess::scopeUsers(User::query()->where('company_id', $user->company_id), $user)
            ->where('status', 1)
            ->whereHas('role', fn ($r) => $r->where('status', 1)->where(fn ($q) => $q
                ->where('role_code', 'administrator')
                ->orWhereHas('permissions', fn ($p) => $p->where('permission_code', 'asset_audit.scan'))))
            ->with(['role:id,role_name', 'branch:id,branch_name,is_head_office'])
            ->orderBy('name')
            ->get(['id', 'name', 'role_id', 'branch_id']);
    }

    /*
    |--------------------------------------------------------------------------
    | DAFTAR ITEM
    |--------------------------------------------------------------------------
    */

    /**
     * Item audit dengan filter: all|pending|found|misplaced|damaged|missing|extra|other_branch|mine.
     * "mine" = item di area lokasi petugas yang sedang login.
     */
    public function itemsQuery(AssetAudit $audit, string $filter = 'all', ?string $term = null, ?User $user = null)
    {
        $term = trim((string) $term);

        $mine = null;

        if ($filter === 'mine' && $user) {
            $mine = $audit->assignees()->where('user_id', $user->id)->pluck('location_id');
        }

        return $audit->items()
            ->when($filter === 'pending', fn ($q) => $q->where('in_scope', true)->whereNull('result'))
            ->when($filter === 'found', fn ($q) => $q->where('result', 'found')->where('in_scope', true)->where('location_mismatch', false))
            ->when($filter === 'misplaced', fn ($q) => $q->where('result', 'found')->where('in_scope', true)->where('location_mismatch', true))
            ->when($filter === 'damaged', fn ($q) => $q->where('result', 'found')->whereIn('condition', ['minor_damage', 'major_damage']))
            ->when($filter === 'missing', fn ($q) => $q->where('result', 'missing'))
            ->when($filter === 'extra', fn ($q) => $q->where('in_scope', false)->where('branch_mismatch', false))
            ->when($filter === 'other_branch', fn ($q) => $q->where('in_scope', false)->where('branch_mismatch', true))
            ->when($mine !== null, function ($q) use ($mine) {
                // Petugas tanpa area (NULL) = semua lokasi.
                if ($mine->contains(null)) {
                    return $q->where('in_scope', true);
                }

                return $q->where('in_scope', true)->whereIn('expected_location_id', $mine->filter()->all() ?: [0]);
            })
            ->when($term !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('asset_code', 'like', "%{$term}%")
                ->orWhere('asset_name', 'like', "%{$term}%")
                ->orWhere('expected_location', 'like', "%{$term}%")))
            ->orderByRaw('result IS NULL DESC')
            ->orderByDesc('checked_at')
            ->orderBy('expected_location')
            ->orderBy('asset_code');
    }

    /*
    |--------------------------------------------------------------------------
    | BUAT SESI
    |--------------------------------------------------------------------------
    */

    /**
     * @param array<int, array{user_id:int, location_id?:?int}> $assignees
     */
    public function create(User $user, string $title, mixed $branchId = null, mixed $locationId = null, ?string $notes = null, array $assignees = []): AssetAudit
    {
        $companyId = (int) $user->company_id;
        $restricted = BranchAccess::restrictedBranchId($user);
        $branchId = $restricted ?? ($branchId ? (int) $branchId : null);

        if ($branchId && !BranchAccess::find($companyId, $branchId, $user)) {
            throw new ActionFailed('Cabang tidak valid.');
        }

        $location = null;

        if ($locationId) {
            $location = Location::query()->where('company_id', $companyId)->find((int) $locationId);

            if (!$location || ($branchId && (int) $location->branch_id !== $branchId)) {
                throw new ActionFailed('Lokasi tidak valid untuk cabang yang dipilih.');
            }

            $branchId ??= (int) $location->branch_id;
        }

        // Petugas: harus user yang boleh dipilih pembuat audit.
        $candidates = $this->assigneeCandidates($user)->keyBy('id');
        $team = collect($assignees)
            ->filter(fn ($a) => !empty($a['user_id']))
            ->map(fn ($a) => ['user_id' => (int) $a['user_id'], 'location_id' => !empty($a['location_id']) ? (int) $a['location_id'] : null])
            ->unique(fn ($a) => $a['user_id'] . '|' . $a['location_id'])
            ->values();

        foreach ($team as $a) {
            if (!$candidates->has($a['user_id'])) {
                throw new ActionFailed('Petugas audit tidak valid atau tidak memiliki izin scan audit.');
            }

            if ($a['location_id']) {
                $ok = Location::query()->where('company_id', $companyId)
                    ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                    ->whereKey($a['location_id'])->exists();

                if (!$ok) {
                    throw new ActionFailed('Area lokasi petugas tidak valid.');
                }
            }
        }

        $assets = Asset::query()
            ->withoutGlobalScope(BranchScope::class)
            ->where('company_id', $companyId)
            ->inService()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($location, fn ($q) => $q->where('location_id', $location->id))
            ->orderBy('asset_code')
            ->get(['id', 'asset_code', 'asset_name', 'branch_id', 'location', 'location_id']);

        if ($assets->isEmpty()) {
            throw new ActionFailed('Tidak ada aset pada cakupan audit tersebut.');
        }

        $audit = DB::transaction(function () use ($companyId, $branchId, $location, $title, $notes, $user, $assets, $team) {
            $audit = AssetAudit::create([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'audit_no' => $this->nextNumber($companyId),
                'title' => trim($title),
                'location_filter' => $location?->name,
                'location_id' => $location?->id,
                'status' => AssetAudit::STATUS_IN_PROGRESS,
                'notes' => $notes !== null && trim($notes) !== '' ? trim($notes) : null,
                'started_by' => $user->id,
            ]);

            $now = now();

            foreach ($assets->chunk(500) as $chunk) {
                AssetAuditItem::insert($chunk->map(fn (Asset $a) => [
                    'asset_audit_id' => $audit->id,
                    'asset_id' => $a->id,
                    'asset_code' => $a->asset_code,
                    'asset_name' => $a->asset_name,
                    'expected_location' => $a->location,
                    'expected_branch_id' => $a->branch_id,
                    'expected_location_id' => $a->location_id,
                    'in_scope' => true,
                    'branch_mismatch' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all());
            }

            foreach ($team as $a) {
                AssetAuditAssignee::create(['asset_audit_id' => $audit->id] + $a);
            }

            return $audit;
        });

        $this->notifyAssignees($audit, $user);

        return $audit;
    }

    /*
    |--------------------------------------------------------------------------
    | SCAN
    |--------------------------------------------------------------------------
    */

    /**
     * Scan QR (isi label = URL .../{qr_token}) atau kode aset.
     * $locationId = lokasi petugas saat ini (master lokasi); NULL = lokasi tidak dicek.
     *
     * @return array{item: AssetAuditItem, already: bool, message: string}
     */
    public function scan(User $user, AssetAudit $audit, string $code, mixed $locationId = null, ?string $condition = null, ?string $notes = null): array
    {
        $this->ensureOpen($audit);

        if (!$this->canScan($user, $audit)) {
            throw new ActionFailed('Anda bukan petugas audit ini.', 'FORBIDDEN', 403);
        }

        $code = $this->parseCode($code);

        $asset = Asset::query()
            ->withoutGlobalScope(BranchScope::class)
            ->where('company_id', $audit->company_id)
            ->where(fn ($q) => $q->where('qr_token', $code)->orWhere('asset_code', $code))
            ->with('branch:id,branch_name')
            ->first();

        if (!$asset) {
            throw new ActionFailed('QR / kode "' . $code . '" tidak dikenali sebagai aset perusahaan ini.', 'ASSET_NOT_FOUND', 404);
        }

        $location = $locationId
            ? Location::query()->where('company_id', $audit->company_id)->find((int) $locationId)
            : null;

        if ($locationId && !$location) {
            throw new ActionFailed('Lokasi petugas tidak valid.');
        }

        $condition = array_key_exists((string) $condition, AssetAuditItem::CONDITIONS) ? $condition : 'good';

        $item = $audit->items()->where('asset_id', $asset->id)->first();
        $already = $item && $item->result === AssetAuditItem::RESULT_FOUND;

        if (!$item) {
            // Terbaca tetapi tidak ada di daftar audit.
            $item = new AssetAuditItem([
                'asset_audit_id' => $audit->id,
                'asset_id' => $asset->id,
                'asset_code' => $asset->asset_code,
                'asset_name' => $asset->asset_name,
                'expected_location' => $asset->location,
                'expected_branch_id' => $asset->branch_id,
                'expected_location_id' => $asset->location_id,
                'in_scope' => false,
                // Aset milik cabang lain terbaca di audit cabang ini.
                'branch_mismatch' => $audit->branch_id !== null && (int) $asset->branch_id !== (int) $audit->branch_id,
            ]);
        }

        // Salah lokasi: hanya bila petugas memilih lokasinya & aset ada di daftar.
        $mismatch = $location !== null
            && $item->in_scope
            && (int) $item->expected_location_id !== (int) $location->id;

        $item->fill([
            'result' => AssetAuditItem::RESULT_FOUND,
            'found_location' => $location?->name ?? $item->expected_location,
            'found_location_id' => $location?->id ?? $item->expected_location_id,
            'condition' => $condition,
            'location_mismatch' => $mismatch,
            'notes' => $notes !== null && trim($notes) !== '' ? trim($notes) : ($asset->isDisposed() ? 'Aset berstatus dihapuskan' : $item->notes),
            'checked_at' => now(),
            'checked_by' => $user->id,
        ])->save();

        $expected = $item->expected_location ?: 'belum diisi';

        $message = match (true) {
            (bool) $item->branch_mismatch => 'Aset milik cabang ' . ($asset->branch?->branch_name ?? 'lain') . ' — bukan aset cabang ini. Perlu mutasi bila memang dipindahkan.',
            !$item->in_scope => 'Aset ini tidak ada di daftar audit (lokasi tercatat: ' . $expected . ').',
            $already => 'Sudah di-scan sebelumnya; data diperbarui.',
            $mismatch => 'Salah lokasi — tercatat di ' . $expected . ', ditemukan di ' . $location->name . '.',
            default => 'Ditemukan.',
        };

        return ['item' => $item->fresh(), 'already' => $already, 'message' => $message];
    }

    /**
     * Tandai manual (aset tanpa label QR): found | missing | reset.
     * Mengembalikan NULL bila item "di luar daftar" dihapus (reset).
     */
    public function mark(User $user, AssetAudit $audit, int $itemId, string $action): ?AssetAuditItem
    {
        $this->ensureOpen($audit);

        if (!$this->canScan($user, $audit)) {
            throw new ActionFailed('Anda bukan petugas audit ini.', 'FORBIDDEN', 403);
        }

        $item = $audit->items()->find($itemId);

        if (!$item) {
            throw new ActionFailed('Item audit tidak ditemukan.', 'NOT_FOUND', 404);
        }

        if ($action === 'reset') {
            if (!$item->in_scope) {
                $item->delete();

                return null;
            }

            $item->update(['result' => null, 'found_location' => null, 'found_location_id' => null, 'condition' => null, 'location_mismatch' => false, 'checked_at' => null, 'checked_by' => null]);

            return $item->fresh();
        }

        if (!in_array($action, ['found', 'missing'], true)) {
            throw new ActionFailed('Aksi harus found, missing, atau reset.');
        }

        $item->update([
            'result' => $action,
            'found_location' => $action === 'found' ? $item->expected_location : null,
            'found_location_id' => $action === 'found' ? $item->expected_location_id : null,
            'condition' => $action === 'found' ? ($item->condition ?? 'good') : null,
            'location_mismatch' => false,
            'checked_at' => now(),
            'checked_by' => $user->id,
        ]);

        return $item->fresh();
    }

    /**
     * Tandai SEMUA aset yang belum dicek di satu lokasi sebagai ditemukan
     * (untuk aset tanpa label QR, mis. meja/kursi di satu ruangan).
     */
    public function markLocationFound(User $user, AssetAudit $audit, mixed $locationId): int
    {
        $this->ensureOpen($audit);

        if (!$this->canScan($user, $audit)) {
            throw new ActionFailed('Anda bukan petugas audit ini.', 'FORBIDDEN', 403);
        }

        if (!$locationId) {
            throw new ActionFailed('Pilih lokasi yang akan ditandai.');
        }

        return $audit->items()
            ->where('in_scope', true)
            ->whereNull('result')
            ->where('expected_location_id', (int) $locationId)
            ->update([
                'result' => AssetAuditItem::RESULT_FOUND,
                'found_location' => DB::raw('expected_location'),
                'found_location_id' => DB::raw('expected_location_id'),
                'condition' => 'good',
                'location_mismatch' => false,
                'notes' => 'Ditandai massal per lokasi',
                'checked_at' => now(),
                'checked_by' => $user->id,
                'updated_at' => now(),
            ]);
    }

    /**
     * @return array{summary: array, locations_updated: int}
     */
    public function complete(User $user, AssetAudit $audit, bool $applyLocations): array
    {
        $this->ensureOpen($audit);

        $updated = 0;

        DB::transaction(function () use ($audit, $applyLocations, $user, &$updated) {
            $audit->items()
                ->where('in_scope', true)
                ->whereNull('result')
                ->update(['result' => AssetAuditItem::RESULT_MISSING, 'checked_at' => now(), 'checked_by' => $user->id]);

            if ($applyLocations) {
                $audit->items()
                    ->where('in_scope', true)
                    ->where('result', AssetAuditItem::RESULT_FOUND)
                    ->where('location_mismatch', true)
                    ->whereNotNull('asset_id')
                    ->whereNotNull('found_location')
                    ->get()
                    ->each(function (AssetAuditItem $item) use ($audit, $user, &$updated) {
                        $asset = Asset::withoutGlobalScope(BranchScope::class)->find($item->asset_id);

                        if (!$asset) {
                            return;
                        }

                        try {
                            $this->lifecycle->moveLocation($asset, $item->found_location, today(), 'Hasil audit fisik ' . $audit->audit_no, $user);
                            $updated++;
                        } catch (Throwable) {
                            // Lokasi sama / aset dihapuskan: lewati.
                        }
                    });
            }

            $audit->update([
                'status' => AssetAudit::STATUS_COMPLETED,
                'completed_at' => now(),
                'completed_by' => $user->id,
                'locations_applied' => $applyLocations,
            ]);
        });

        return ['summary' => $audit->summary(), 'locations_updated' => $updated];
    }

    public function cancel(User $user, AssetAudit $audit): void
    {
        $this->ensureOpen($audit);

        $audit->update(['status' => AssetAudit::STATUS_CANCELLED, 'completed_at' => now(), 'completed_by' => $user->id]);
    }

    /*
    |--------------------------------------------------------------------------
    | TIM
    |--------------------------------------------------------------------------
    */

    /**
     * Progres tiap petugas: area, jumlah dicek, sisa di areanya.
     *
     * @return array<int, array{user_id:int, name:string, areas:array, checked:int, pending:int}>
     */
    public function teamProgress(AssetAudit $audit): array
    {
        $assignees = $audit->assignees()->with(['user:id,name', 'location:id,name'])->get()->groupBy('user_id');
        $checked = $audit->items()->whereNotNull('checked_by')->whereNotNull('result')
            ->selectRaw('checked_by, COUNT(*) AS c')->groupBy('checked_by')->pluck('c', 'checked_by');

        $rows = [];

        foreach ($assignees as $userId => $list) {
            $locationIds = $list->pluck('location_id');
            $allAreas = $locationIds->contains(null);

            $pending = $audit->items()->where('in_scope', true)->whereNull('result')
                ->when(!$allAreas, fn ($q) => $q->whereIn('expected_location_id', $locationIds->filter()->all() ?: [0]))
                ->count();

            $rows[] = [
                'user_id' => (int) $userId,
                'name' => $list->first()->user?->name ?? '—',
                'areas' => $allAreas ? [] : $list->pluck('location.name')->filter()->values()->all(),
                'checked' => (int) ($checked[$userId] ?? 0),
                'pending' => $pending,
            ];
        }

        // Pemindai lain yang bukan petugas (mis. pengelola audit).
        foreach ($checked as $userId => $count) {
            if (!$assignees->has($userId)) {
                $rows[] = [
                    'user_id' => (int) $userId,
                    'name' => User::query()->whereKey($userId)->value('name') ?? '—',
                    'areas' => null,
                    'checked' => (int) $count,
                    'pending' => null,
                ];
            }
        }

        return $rows;
    }

    protected function notifyAssignees(AssetAudit $audit, User $by): void
    {
        try {
            $audit->loadMissing('branch');

            foreach ($audit->assignees()->with('user', 'location')->get()->groupBy('user_id') as $list) {
                $user = $list->first()->user;

                if (!$user || (int) $user->id === (int) $by->id) {
                    continue;
                }

                $areas = $list->pluck('location.name')->filter()->implode(', ');

                $user->notify(new GlobalNotification(
                    type: self::TYPE,
                    title: NotificationText::make('Tugas audit fisik: :title', ['title' => $audit->title]),
                    message: NotificationText::make(':name menugaskan Anda audit fisik :no (:area). Buka lalu scan QR aset.', [
                        'name' => $by->name,
                        'no' => $audit->audit_no,
                        'area' => $areas !== '' ? $areas : ($audit->branch?->branch_name ?? 'semua cabang'),
                    ]),
                    url: route('asset-audits.show', $audit->id, false),
                    extra: ['action' => 'assigned', 'asset_audit_id' => $audit->id, 'audit_no' => $audit->audit_no],
                ));
            }
        } catch (Throwable $e) {
            report($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | HELPER
    |--------------------------------------------------------------------------
    */

    public function itemPayload(AssetAuditItem $item): array
    {
        return [
            'id' => $item->id,
            'asset_id' => $item->asset_id,
            'code' => $item->asset_code,
            'name' => $item->asset_name,
            'state' => $item->state(),
            'in_scope' => (bool) $item->in_scope,
            'expected_branch_id' => $item->expected_branch_id,
            'expected_branch' => $item->expectedBranch?->branch_name,
            'expected_location_id' => $item->expected_location_id,
            'expected_location' => $item->expected_location,
            'found_location_id' => $item->found_location_id,
            'found_location' => $item->found_location,
            'location_mismatch' => (bool) $item->location_mismatch,
            'branch_mismatch' => (bool) $item->branch_mismatch,
            'condition' => $item->condition,
            'condition_label' => $item->conditionLabel(),
            'notes' => $item->notes,
            'checked_at' => $item->checked_at?->toIso8601String(),
            'checked_by' => $item->checker?->name,
        ];
    }

    protected function ensureOpen(AssetAudit $audit): void
    {
        if (!$audit->isOpen()) {
            throw new ActionFailed('Audit ini sudah ' . strtolower($audit->statusLabel()) . '.', 'INVALID_STATUS');
        }
    }

    protected function parseCode(string $raw): string
    {
        $raw = trim($raw);

        if (str_contains($raw, '/')) {
            $path = parse_url($raw, PHP_URL_PATH) ?: $raw;
            $raw = basename(rtrim($path, '/'));
        }

        return trim($raw);
    }

    public function nextNumber(int $companyId): string
    {
        $prefix = 'AUD-' . now()->format('ym') . '-';

        $last = AssetAudit::query()
            ->where('company_id', $companyId)
            ->where('audit_no', 'like', $prefix . '%')
            ->orderByDesc('audit_no')
            ->value('audit_no');

        $n = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix . str_pad((string) $n, 3, '0', STR_PAD_LEFT);
    }
}
