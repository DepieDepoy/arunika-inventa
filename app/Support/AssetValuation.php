<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Nilai buku aset metode garis lurus (straight line).
 * Aset tanpa data penyusutan dinilai sebesar harga perolehannya.
 */
class AssetValuation
{
    /**
     * @return array{value: float, depreciated: float, percent: float}|null  null = tidak disusutkan
     */
    public static function bookValue(object $asset, ?CarbonInterface $at = null): ?array
    {
        $price = (float) ($asset->purchase_price ?? 0);
        $years = (int) ($asset->useful_life ?? 0);
        $start = $asset->depreciation_start_date ?? $asset->purchase_date ?? null;

        if (($asset->depreciation_method ?? null) !== 'straight_line' || $price <= 0 || $years <= 0 || !$start) {
            return null;
        }

        $start = $start instanceof CarbonInterface ? $start : Carbon::parse($start);
        $residual = min((float) ($asset->residual_value ?? 0), $price);
        $elapsedMonths = max(0, $start->diffInMonths($at ?? now()));
        $ratio = min(1, $elapsedMonths / ($years * 12));
        $depreciated = ($price - $residual) * $ratio;

        return [
            'value' => round($price - $depreciated),
            'depreciated' => round($depreciated),
            'percent' => round($ratio * 100, 1),
        ];
    }

    /**
     * Ekspresi SQL (MySQL) nilai buku per baris aset pada tanggal ? (binding).
     */
    public static function sqlBookValue(string $table = 'assets'): string
    {
        $t = $table;
        $start = "COALESCE({$t}.depreciation_start_date, {$t}.purchase_date)";

        return "CASE WHEN {$t}.depreciation_method = 'straight_line' AND {$t}.purchase_price > 0 AND {$t}.useful_life > 0 AND {$start} IS NOT NULL "
            . "THEN {$t}.purchase_price - ({$t}.purchase_price - LEAST(COALESCE({$t}.residual_value, 0), {$t}.purchase_price)) "
            . "* LEAST(1, GREATEST(0, TIMESTAMPDIFF(MONTH, {$start}, ?)) / ({$t}.useful_life * 12)) "
            . "ELSE COALESCE({$t}.purchase_price, 0) END";
    }
}
