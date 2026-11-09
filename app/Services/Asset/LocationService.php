<?php

namespace App\Services\Asset;

use App\Models\Asset;
use App\Models\Location;
use App\Models\Scopes\BranchScope;
use App\Models\User;
use App\Support\ActionFailed;
use App\Support\BranchAccess;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Master lokasi per cabang.
 *
 * Kolom teks assets.location tetap ada (dipakai laporan, AI, API) dan selalu
 * berisi nama lokasi. Saat teks lokasi diisi dari mana pun (form, API, import,
 * pindah lokasi), nama itu dicocokkan ke master lokasi cabang aset — bila
 * belum ada, lokasi baru dibuat otomatis. Jadi tidak ada lokasi "liar".
 */
class LocationService
{
    /**
     * ID lokasi bernama $name di cabang $branchId (dibuat bila belum ada).
     */
    public static function resolveId(int $companyId, ?int $branchId, ?string $name): ?int
    {
        $name = trim(preg_replace('/\s+/', ' ', (string) $name));

        if ($name === '' || !$branchId) {
            return null;
        }

        $existing = Location::withTrashed()
            ->where('company_id', $companyId)
            ->where('branch_id', $branchId)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->orderByRaw('deleted_at IS NULL DESC')
            ->first();

        if ($existing) {
            if ($existing->trashed()) {
                $existing->restore();
            }

            return (int) $existing->id;
        }

        return (int) Location::create([
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'name' => $name,
            'status' => 1,
        ])->id;
    }

    /**
     * Lokasi aktif per cabang yang boleh dilihat user (untuk dropdown).
     *
     * @return Collection<int, Location>
     */
    public function options(User $user, ?int $branchId = null): Collection
    {
        $restricted = BranchAccess::restrictedBranchId($user);

        return Location::query()
            ->where('company_id', $user->company_id)
            ->where('status', 1)
            ->when($restricted, fn ($q) => $q->where('branch_id', $restricted))
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->with('branch:id,branch_name,is_head_office')
            ->orderBy('branch_id')
            ->orderBy('name')
            ->get();
    }

    /**
     * Daftar lokasi dikelompokkan per cabang: [branch_id => [{id, name}]] (untuk JS).
     */
    public function groupedForJs(User $user): array
    {
        return $this->options($user)
            ->groupBy('branch_id')
            ->map(fn ($items) => $items->map(fn (Location $l) => ['id' => $l->id, 'name' => $l->name])->values())
            ->all();
    }

    public function create(User $user, array $data): Location
    {
        $companyId = (int) $user->company_id;
        $branch = BranchAccess::find($companyId, $data['branch_id'] ?? BranchAccess::restrictedBranchId($user), $user);

        if (!$branch) {
            throw new ActionFailed('Cabang tidak valid.');
        }

        $this->ensureNameFree($companyId, $branch->id, $data['name'] ?? '');

        return Location::create([
            'company_id' => $companyId,
            'branch_id' => $branch->id,
            'name' => trim((string) $data['name']),
            'description' => $data['description'] ?? null,
            'status' => (int) ($data['status'] ?? 1),
        ]);
    }

    public function update(User $user, Location $location, array $data): Location
    {
        $this->authorize($user, $location);
        $this->ensureNameFree((int) $location->company_id, (int) $location->branch_id, $data['name'] ?? '', $location->id);

        $newName = trim((string) $data['name']);

        DB::transaction(function () use ($location, $newName, $data) {
            $location->update([
                'name' => $newName,
                'description' => $data['description'] ?? null,
                'status' => (int) ($data['status'] ?? $location->status),
            ]);

            // Nama tampilan aset ikut berubah.
            Asset::withoutGlobalScope(BranchScope::class)
                ->where('location_id', $location->id)
                ->update(['location' => $newName]);
        });

        return $location;
    }

    public function delete(User $user, Location $location): void
    {
        $this->authorize($user, $location);

        $count = Asset::withoutGlobalScope(BranchScope::class)->where('location_id', $location->id)->count();

        if ($count > 0) {
            throw new ActionFailed("Lokasi masih dipakai {$count} aset. Pindahkan asetnya terlebih dahulu.", 'IN_USE');
        }

        $location->delete();
    }

    public function find(User $user, int $id): Location
    {
        $location = Location::query()->where('company_id', $user->company_id)->find($id);

        if (!$location) {
            throw new ActionFailed('Lokasi tidak ditemukan.', 'NOT_FOUND', 404);
        }

        $this->authorize($user, $location);

        return $location;
    }

    /**
     * Sinkronkan aset yang punya teks lokasi tetapi belum terhubung ke master
     * (mis. hasil import massal).
     */
    public static function syncMissing(int $companyId): int
    {
        $count = 0;

        Asset::withoutGlobalScope(BranchScope::class)
            ->where('company_id', $companyId)
            ->whereNull('location_id')
            ->whereNotNull('location')
            ->whereNotNull('branch_id')
            ->select(['id', 'company_id', 'branch_id', 'location'])
            ->chunkById(500, function ($assets) use (&$count) {
                foreach ($assets as $asset) {
                    $id = self::resolveId((int) $asset->company_id, (int) $asset->branch_id, $asset->location);

                    if ($id) {
                        DB::table('assets')->where('id', $asset->id)->update(['location_id' => $id]);
                        $count++;
                    }
                }
            });

        return $count;
    }

    protected function authorize(User $user, Location $location): void
    {
        if ((int) $location->company_id !== (int) $user->company_id || !BranchAccess::canAccessBranch((int) $location->branch_id, $user)) {
            throw new ActionFailed('Lokasi tidak ditemukan.', 'NOT_FOUND', 404);
        }
    }

    protected function ensureNameFree(int $companyId, int $branchId, string $name, ?int $ignoreId = null): void
    {
        if (trim($name) === '') {
            throw new ActionFailed('Nama lokasi wajib diisi.');
        }

        $taken = Location::query()
            ->where('company_id', $companyId)
            ->where('branch_id', $branchId)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($name))])
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->exists();

        if ($taken) {
            throw new ActionFailed('Nama lokasi sudah ada di cabang ini.');
        }
    }
}
