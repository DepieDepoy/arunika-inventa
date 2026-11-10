<?php

namespace App\Services\Asset;

use App\Models\Asset;
use App\Models\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Pencarian aset yang tetap cepat pada jutaan baris.
 *
 * LIKE '%kata%' harus membaca seluruh tabel. Sebagai gantinya:
 *  1. kode aset diawali kata kunci   -> index (company_id, asset_code)
 *  2. kata di nama / kode / merek / nomor seri (awalan kata) -> FULLTEXT index
 * ID hasil keduanya (maks. MAX_IDS) lalu dipakai sebagai filter query utama,
 * sehingga filter lain, cabang, urutan, dan paginasi tetap berjalan normal.
 *
 * Catatan: pencarian berdasarkan AWAL kata/kode ("lapt" menemukan "Laptop",
 * "AST-00" menemukan "AST-0012"), bukan potongan di tengah kata.
 */
class AssetSearch
{
    public const MAX_IDS = 5000;

    public static function apply(Builder $query, int $companyId, ?string $term, string $idColumn = 'assets.id'): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        if (!in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            // Database lain (mis. sqlite saat tes): LIKE biasa.
            return $query->where(fn ($q) => $q->where('assets.asset_code', 'like', "%{$term}%")->orWhere('assets.asset_name', 'like', "%{$term}%"));
        }

        return $query->whereIn($idColumn, self::ids($companyId, $term) ?: [0]);
    }

    /**
     * @return array<int>
     */
    public static function ids(int $companyId, string $term): array
    {
        $base = fn () => Asset::query()->withoutGlobalScope(BranchScope::class)->where('company_id', $companyId);
        $like = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term);

        // 1. Kode aset diawali kata kunci (index unik company_id + asset_code).
        $ids = $base()->where('asset_code', 'like', $like . '%')->limit(self::MAX_IDS)->pluck('id')->all();

        // 2. FULLTEXT: setiap kata harus ada (awalan kata), mis. "+laptop* +dell*".
        $words = array_values(array_filter(preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($term)), fn ($w) => mb_strlen($w) >= 2));

        if ($words) {
            $boolean = implode(' ', array_map(fn ($w) => '+' . $w . '*', $words));

            try {
                $ids = array_merge($ids, $base()
                    ->whereRaw('MATCH(asset_name, asset_code, brand, serial_number) AGAINST (? IN BOOLEAN MODE)', [$boolean])
                    ->limit(self::MAX_IDS)
                    ->pluck('id')
                    ->all());
            } catch (Throwable) {
                // Index FULLTEXT belum ada: awalan nama aset.
                $ids = array_merge($ids, $base()->where('asset_name', 'like', $like . '%')->limit(self::MAX_IDS)->pluck('id')->all());
            }
        }

        return array_slice(array_values(array_unique($ids)), 0, self::MAX_IDS);
    }
}
