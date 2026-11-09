<?php

namespace App\Services\Asset;

use App\Models\Asset;
use App\Models\AssetAudit;
use App\Models\AssetAuditItem;
use App\Models\Scopes\BranchScope;
use App\Models\User;
use App\Support\ActionFailed;
use App\Support\BranchAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Aturan Audit Fisik Aset (dipakai web & API mobile).
 *  1. Buat sesi -> aset sesuai cakupan (cabang + lokasi) masuk daftar "belum dicek".
 *  2. Scan QR / kode aset -> ditemukan, salah lokasi, kondisi, atau di luar daftar.
 *  3. Selesaikan -> yang belum dicek menjadi "tidak ditemukan"; lokasi salah bisa diperbarui.
 */
class AssetAuditService
{
    public const FILTERS = ['all', 'pending', 'found', 'misplaced', 'damaged', 'missing', 'extra'];

    public function __construct(
        protected AssetLifecycleService $lifecycle
    ) {
    }

    /**
     * Audit yang boleh dilihat user (user cabang: hanya audit cabangnya).
     */
    public function visibleQuery(User $user): Builder
    {
        $restricted = BranchAccess::restrictedBranchId($user);

        return AssetAudit::query()
            ->where('company_id', $user->company_id)
            ->when($restricted, fn ($q) => $q->where('branch_id', $restricted));
    }

    public function find(User $user, int $id): AssetAudit
    {
        $audit = $this->visibleQuery($user)->find($id);

        if (!$audit) {
            throw new ActionFailed('Audit tidak ditemukan.', 'NOT_FOUND', 404);
        }

        return $audit;
    }

    /**
     * Daftar item audit dengan filter: all|pending|found|misplaced|damaged|missing|extra.
     */
    public function itemsQuery(AssetAudit $audit, string $filter = 'all', ?string $term = null)
    {
        $term = trim((string) $term);

        return $audit->items()
            ->when($filter === 'pending', fn ($q) => $q->where('in_scope', true)->whereNull('result'))
            ->when($filter === 'found', fn ($q) => $q->where('result', 'found')->where('in_scope', true)->where('location_mismatch', false))
            ->when($filter === 'misplaced', fn ($q) => $q->where('result', 'found')->where('in_scope', true)->where('location_mismatch', true))
            ->when($filter === 'damaged', fn ($q) => $q->where('result', 'found')->whereIn('condition', ['minor_damage', 'major_damage']))
            ->when($filter === 'missing', fn ($q) => $q->where('result', 'missing'))
            ->when($filter === 'extra', fn ($q) => $q->where('in_scope', false))
            ->when($term !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('asset_code', 'like', "%{$term}%")
                ->orWhere('asset_name', 'like', "%{$term}%")
                ->orWhere('expected_location', 'like', "%{$term}%")))
            ->orderByRaw('result IS NULL DESC')
            ->orderByDesc('checked_at')
            ->orderBy('asset_code');
    }

    public function create(User $user, string $title, mixed $branchId = null, ?string $location = null, ?string $notes = null): AssetAudit
    {
        $companyId = (int) $user->company_id;
        $restricted = BranchAccess::restrictedBranchId($user);
        $branchId = $restricted ?? ($branchId ? (int) $branchId : null);

        if ($branchId && !BranchAccess::find($companyId, $branchId, $user)) {
            throw new ActionFailed('Cabang tidak valid.');
        }

        $location = trim((string) $location);

        $assets = Asset::query()
            ->withoutGlobalScope(BranchScope::class)
            ->where('company_id', $companyId)
            ->inService()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($location !== '', fn ($q) => $q->where('location', $location))
            ->orderBy('asset_code')
            ->get(['id', 'asset_code', 'asset_name', 'location']);

        if ($assets->isEmpty()) {
            throw new ActionFailed('Tidak ada aset pada cakupan audit tersebut.');
        }

        return DB::transaction(function () use ($companyId, $branchId, $location, $title, $notes, $user, $assets) {
            $audit = AssetAudit::create([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'audit_no' => $this->nextNumber($companyId),
                'title' => trim($title),
                'location_filter' => $location !== '' ? $location : null,
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
                    'in_scope' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all());
            }

            return $audit;
        });
    }

    /**
     * Scan QR (isi label = URL .../{qr_token}) atau kode aset.
     *
     * @return array{item: AssetAuditItem, already: bool, message: string}
     */
    public function scan(User $user, AssetAudit $audit, string $code, ?string $location = null, ?string $condition = null, ?string $notes = null): array
    {
        $this->ensureOpen($audit);

        $code = $this->parseCode($code);

        $asset = Asset::query()
            ->withoutGlobalScope(BranchScope::class)
            ->where('company_id', $audit->company_id)
            ->where(fn ($q) => $q->where('qr_token', $code)->orWhere('asset_code', $code))
            ->first();

        if (!$asset) {
            throw new ActionFailed('QR / kode "' . $code . '" tidak dikenali sebagai aset perusahaan ini.', 'ASSET_NOT_FOUND', 404);
        }

        $location = trim((string) $location);
        $condition = array_key_exists((string) $condition, AssetAuditItem::CONDITIONS) ? $condition : 'good';

        $item = $audit->items()->where('asset_id', $asset->id)->first();
        $already = $item && $item->result === AssetAuditItem::RESULT_FOUND;

        if (!$item) {
            // Ditemukan tetapi tidak ada di daftar audit (cabang/lokasi lain, atau aset baru).
            $item = new AssetAuditItem([
                'asset_audit_id' => $audit->id,
                'asset_id' => $asset->id,
                'asset_code' => $asset->asset_code,
                'asset_name' => $asset->asset_name,
                'expected_location' => $asset->location,
                'in_scope' => false,
            ]);
        }

        $expected = $item->expected_location;

        $item->fill([
            'result' => AssetAuditItem::RESULT_FOUND,
            'found_location' => $location !== '' ? $location : $expected,
            'condition' => $condition,
            'location_mismatch' => $location !== '' && $this->differs($expected, $location),
            'notes' => $notes !== null && trim($notes) !== '' ? trim($notes) : ($asset->isDisposed() ? 'Aset berstatus dihapuskan' : $item->notes),
            'checked_at' => now(),
            'checked_by' => $user->id,
        ])->save();

        $message = match (true) {
            !$item->in_scope => 'Ditemukan, tetapi aset ini tidak ada di daftar audit.',
            $already => 'Sudah di-scan sebelumnya; data diperbarui.',
            $item->location_mismatch => 'Ditemukan — lokasi berbeda dari catatan (' . ($expected ?: 'kosong') . ').',
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

        $item = $audit->items()->find($itemId);

        if (!$item) {
            throw new ActionFailed('Item audit tidak ditemukan.', 'NOT_FOUND', 404);
        }

        if ($action === 'reset') {
            if (!$item->in_scope) {
                $item->delete();

                return null;
            }

            $item->update(['result' => null, 'found_location' => null, 'condition' => null, 'location_mismatch' => false, 'checked_at' => null, 'checked_by' => null]);

            return $item->fresh();
        }

        if (!in_array($action, ['found', 'missing'], true)) {
            throw new ActionFailed('Aksi harus found, missing, atau reset.');
        }

        $item->update([
            'result' => $action,
            'found_location' => $action === 'found' ? $item->expected_location : null,
            'condition' => $action === 'found' ? ($item->condition ?? 'good') : null,
            'location_mismatch' => false,
            'checked_at' => now(),
            'checked_by' => $user->id,
        ]);

        return $item->fresh();
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
                    ->get()
                    ->each(function (AssetAuditItem $item) use ($audit, $user, &$updated) {
                        $asset = Asset::withoutGlobalScope(BranchScope::class)->find($item->asset_id);

                        if (!$asset || !$item->found_location) {
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

    public function itemPayload(AssetAuditItem $item): array
    {
        return [
            'id' => $item->id,
            'asset_id' => $item->asset_id,
            'code' => $item->asset_code,
            'name' => $item->asset_name,
            'state' => $item->state(),
            'in_scope' => (bool) $item->in_scope,
            'expected_location' => $item->expected_location,
            'found_location' => $item->found_location,
            'location_mismatch' => (bool) $item->location_mismatch,
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

    protected function differs(?string $a, ?string $b): bool
    {
        $norm = fn ($v) => mb_strtolower(trim(preg_replace('/\s+/', ' ', (string) $v)));

        return $norm($a) !== $norm($b);
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
