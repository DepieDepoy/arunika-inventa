<?php

namespace App\Services\Reports;

use App\Models\Company;
use App\Support\AssetValuation;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Laporan aset perusahaan untuk satu periode (mingguan / bulanan / tahunan / bebas).
 *
 * Semua angka dihitung dengan query agregat (COUNT / SUM / GROUP BY) sehingga
 * laporan tahunan tetap ringan, lalu di-cache singkat agar tampilan web, Excel,
 * PDF, dan PowerPoint memakai hasil yang sama tanpa menghitung ulang.
 */
class AssetReport
{
    public const VERSION = 'v1';
    public const DETAIL_LIMIT = 5000;

    public const MAINTENANCE_TYPES = ['preventive' => 'Preventif', 'corrective' => 'Korektif', 'inspection' => 'Inspeksi'];
    public const MAINTENANCE_STATUS = ['scheduled' => 'Terjadwal', 'in_progress' => 'Dikerjakan', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan', 'pending' => 'Menunggu'];
    public const REQUEST_TYPES = ['maintenance' => 'Maintenance', 'repair' => 'Perbaikan', 'problem' => 'Laporan masalah', 'other' => 'Lainnya'];
    public const REQUEST_STATUS = ['pending' => 'Menunggu', 'in_progress' => 'Dikerjakan', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan'];

    /** Cabang yang dilaporkan; NULL = seluruh cabang perusahaan. */
    protected ?int $branchId = null;

    /**
     * Laporan untuk satu cabang (tetap di dalam perusahaan yang sama).
     */
    public function forBranch(?int $branchId): static
    {
        $clone = clone $this;
        $clone->branchId = $branchId ?: null;

        return $clone;
    }

    public function branchId(): ?int
    {
        return $this->branchId;
    }

    protected function cacheKey(int $companyId, ReportPeriod $period): string
    {
        return sprintf('report:%s:%d:b%d:%s', self::VERSION, $companyId, (int) $this->branchId, $period->slug());
    }

    /** Filter tabel assets (atau query yang join assets) ke cabang terpilih. */
    protected function onAssets(Builder $q, string $table = 'assets'): Builder
    {
        return $this->branchId ? $q->where("{$table}.branch_id", $this->branchId) : $q;
    }

    /** Filter tabel turunan aset (punya asset_id) ke cabang terpilih. */
    protected function viaAsset(Builder $q, string $table): Builder
    {
        if (!$this->branchId) {
            return $q;
        }

        $branchId = $this->branchId;

        return $q->whereIn("{$table}.asset_id", fn ($s) => $s->select('id')->from('assets')->where('branch_id', $branchId));
    }

    public function build(int $companyId, ReportPeriod $period): array
    {
        $key = $this->cacheKey($companyId, $period);
        // Periode yang sudah lewat tidak berubah -> cache lebih lama.
        $ttl = $period->to->lt(CarbonImmutable::today()) ? now()->addHours(6) : now()->addMinutes(10);

        return Cache::remember($key, $ttl, fn () => $this->compute($companyId, $period));
    }

    public function forget(int $companyId, ReportPeriod $period): void
    {
        Cache::forget($this->cacheKey($companyId, $period));
    }

    protected function compute(int $companyId, ReportPeriod $period): array
    {
        // Hanya saat cache kosong: rekap jutaan aset bisa lebih dari 30 detik.
        @set_time_limit(300);

        $at = $period->to->min(CarbonImmutable::now());
        $prev = $period->previous();

        $kpi = $this->kpis($companyId, $period, $at);
        $prevKpi = $this->periodKpis($companyId, $prev);

        $report = [
            'meta' => [
                // Laporan cabang: nama cabang ikut tampil di judul web, PDF, Excel, PPT.
                'company' => trim(Company::query()->whereKey($companyId)->value('company_name')
                    . ($this->branchId ? ' · ' . DB::table('branches')->where('company_id', $companyId)->where('id', $this->branchId)->value('branch_name') : '')),
                'branch' => $this->branchId ? DB::table('branches')->where('company_id', $companyId)->where('id', $this->branchId)->value('branch_name') : null,
                'branch_id' => $this->branchId,
                'title' => $period->title(),
                'period' => $period->label(),
                'from' => $period->from->toDateString(),
                'to' => $period->to->toDateString(),
                'granularity' => $period->granularity(),
                'previous' => $prev->label(),
                'generated_at' => now()->toIso8601String(),
            ],
            'kpi' => $kpi,
            'previous' => $prevKpi,
            'trend' => $this->trend($companyId, $period),
            'by_category' => $this->byCategory($companyId, $at),
            'by_location' => $this->byLocation($companyId, $at),
            'maintenance_by_type' => $this->maintenanceBy($companyId, $period, 'maintenance_type', self::MAINTENANCE_TYPES),
            'maintenance_by_status' => $this->maintenanceBy($companyId, $period, 'status', self::MAINTENANCE_STATUS),
            'requests_by_type' => $this->requestsBy($companyId, $period, 'request_type', self::REQUEST_TYPES),
            'requests_by_status' => $this->requestsBy($companyId, $period, 'status', self::REQUEST_STATUS),
            'top_cost_assets' => $this->topCostAssets($companyId, $period),
            'top_technicians' => $this->topTechnicians($companyId, $period),
            'upcoming_maintenance' => $this->upcomingMaintenance($companyId, $at),
            'warranty_expiring' => $this->warrantyExpiring($companyId, $at),
        ];

        $report['highlights'] = $this->highlights($report);

        return $report;
    }

    /* ------------------------------------------------------------------ */
    /* KPI                                                                 */
    /* ------------------------------------------------------------------ */

    protected function aliveAssets(int $companyId, CarbonImmutable $at): Builder
    {
        return $this->onAssets(DB::table('assets'))
            ->where('assets.company_id', $companyId)
            ->where('assets.created_at', '<=', $at)
            ->where(fn ($q) => $q->whereNull('assets.deleted_at')->orWhere('assets.deleted_at', '>', $at))
            // Aset yang sudah dihapuskan pada tanggal laporan tidak dihitung.
            ->where(fn ($q) => $q->whereNull('assets.disposed_at')->orWhere('assets.disposed_at', '>', $at->toDateString()));
    }

    protected function kpis(int $companyId, ReportPeriod $period, CarbonImmutable $at): array
    {
        $portfolio = $this->aliveAssets($companyId, $at)
            ->selectRaw('COUNT(*) as total, SUM(CASE WHEN assets.status = 1 THEN 1 ELSE 0 END) as active, COALESCE(SUM(assets.purchase_price), 0) as value')
            ->selectRaw('COALESCE(SUM(' . AssetValuation::sqlBookValue() . '), 0) as book', [$at->toDateString()])
            ->selectRaw('SUM(CASE WHEN assets.status = 1 AND assets.responsible_user_id IS NULL THEN 1 ELSE 0 END) as unassigned')
            ->first();

        return [
            'total_assets' => (int) $portfolio->total,
            'active_assets' => (int) $portfolio->active,
            'total_value' => (float) $portfolio->value,
            'book_value' => round((float) $portfolio->book),
            'unassigned' => (int) $portfolio->unassigned,
        ] + $this->periodKpis($companyId, $period) + [
            'requests_open' => $this->viaAsset(DB::table('maintenance_requests'), 'maintenance_requests')
                ->where('company_id', $companyId)
                ->where('created_at', '<=', $at)
                ->where('status', '!=', 'cancelled')
                ->where(fn ($q) => $q->whereNull('completed_at')->orWhere('completed_at', '>', $at))
                ->count(),
        ];
    }

    /**
     * Angka aktivitas dalam periode (juga dipakai untuk periode pembanding).
     */
    protected function periodKpis(int $companyId, ReportPeriod $period): array
    {
        $range = [$period->from, $period->to];

        $new = $this->onAssets(DB::table('assets'))->where('company_id', $companyId)->whereBetween('created_at', $range)
            ->selectRaw('COUNT(*) as n, COALESCE(SUM(purchase_price), 0) as v')->first();

        $maint = $this->viaAsset(DB::table('maintenances'), 'maintenances')->where('company_id', $companyId)->whereNull('deleted_at')
            ->whereBetween('maintenance_date', [$period->from->toDateString(), $period->to->toDateString()])
            ->where('status', '!=', 'cancelled')
            ->selectRaw("COUNT(*) as n, SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as done, COALESCE(SUM(CASE WHEN status = 'completed' THEN cost ELSE 0 END), 0) as cost")
            ->first();

        $completed = $this->viaAsset(DB::table('maintenance_requests'), 'maintenance_requests')->where('company_id', $companyId)->whereBetween('completed_at', $range)
            ->selectRaw('COUNT(*) as n, AVG(TIMESTAMPDIFF(MINUTE, created_at, completed_at)) as mins')->first();

        return [
            'new_assets' => (int) $new->n,
            'new_assets_value' => (float) $new->v,
            'disposed_assets' => $this->onAssets(DB::table('assets'))->where('company_id', $companyId)->whereBetween('deleted_at', $range)->count()
                + $this->viaAsset(DB::table('asset_disposals'), 'asset_disposals')->where('company_id', $companyId)->whereNull('cancelled_at')
                    ->whereBetween('disposal_date', [$period->from->toDateString(), $period->to->toDateString()])->count(),
            'maintenance_count' => (int) $maint->n,
            'maintenance_done' => (int) $maint->done,
            'maintenance_cost' => (float) $maint->cost,
            'requests_created' => $this->viaAsset(DB::table('maintenance_requests'), 'maintenance_requests')->where('company_id', $companyId)->whereBetween('created_at', $range)->count(),
            'requests_completed' => (int) $completed->n,
            'avg_resolution_hours' => $completed->mins !== null ? round($completed->mins / 60, 1) : null,
            'assignments' => $this->onAssets(DB::table('asset_assignments')
                ->join('assets', 'assets.id', '=', 'asset_assignments.asset_id'))
                ->where('assets.company_id', $companyId)
                ->whereBetween('asset_assignments.start_at', $range)
                ->count(),
        ];
    }

    /* ------------------------------------------------------------------ */
    /* Tren & rincian                                                      */
    /* ------------------------------------------------------------------ */

    protected function trend(int $companyId, ReportPeriod $period): array
    {
        $range = [$period->from, $period->to];
        $dateRange = [$period->from->toDateString(), $period->to->toDateString()];

        $series = [
            'new_assets' => $this->onAssets(DB::table('assets'))->where('company_id', $companyId)->whereBetween('created_at', $range)
                ->selectRaw($period->bucketSql('created_at') . ' as b, COUNT(*) as n')->groupBy('b')->pluck('n', 'b'),
            'maintenance_count' => $this->viaAsset(DB::table('maintenances'), 'maintenances')->where('company_id', $companyId)->whereNull('deleted_at')->where('status', '!=', 'cancelled')
                ->whereBetween('maintenance_date', $dateRange)
                ->selectRaw($period->bucketSql('maintenance_date') . ' as b, COUNT(*) as n')->groupBy('b')->pluck('n', 'b'),
            'maintenance_cost' => $this->viaAsset(DB::table('maintenances'), 'maintenances')->where('company_id', $companyId)->whereNull('deleted_at')->where('status', 'completed')
                ->whereBetween('maintenance_date', $dateRange)
                ->selectRaw($period->bucketSql('maintenance_date') . ' as b, SUM(cost) as n')->groupBy('b')->pluck('n', 'b'),
            'requests_created' => $this->viaAsset(DB::table('maintenance_requests'), 'maintenance_requests')->where('company_id', $companyId)->whereBetween('created_at', $range)
                ->selectRaw($period->bucketSql('created_at') . ' as b, COUNT(*) as n')->groupBy('b')->pluck('n', 'b'),
            'requests_completed' => $this->viaAsset(DB::table('maintenance_requests'), 'maintenance_requests')->where('company_id', $companyId)->whereBetween('completed_at', $range)
                ->selectRaw($period->bucketSql('completed_at') . ' as b, COUNT(*) as n')->groupBy('b')->pluck('n', 'b'),
        ];

        $rows = [];
        foreach ($period->buckets() as $key => $label) {
            $row = ['key' => $key, 'label' => $label];
            foreach ($series as $name => $values) {
                $row[$name] = (float) ($values[$key] ?? 0);
            }
            $rows[] = $row;
        }

        return $rows;
    }

    protected function byCategory(int $companyId, CarbonImmutable $at): array
    {
        $rows = $this->aliveAssets($companyId, $at)
            ->leftJoin('categories', 'categories.id', '=', 'assets.category_id')
            ->selectRaw("COALESCE(categories.category_name, 'Tanpa kategori') as label, COUNT(*) as total, COALESCE(SUM(assets.purchase_price), 0) as value")
            ->groupBy('label')
            ->orderByDesc('value')
            ->get();

        return $this->topWithOther($rows, 8);
    }

    protected function byLocation(int $companyId, CarbonImmutable $at): array
    {
        // Dikelompokkan per location_id (index), nama diambil dari master lokasi.
        $grouped = $this->aliveAssets($companyId, $at)
            ->selectRaw('assets.location_id AS lid, COUNT(*) AS total, COALESCE(SUM(assets.purchase_price), 0) AS value')
            ->groupBy('assets.location_id')
            ->get();

        $names = DB::table('locations')->whereIn('id', $grouped->pluck('lid')->filter())->pluck('name', 'id');

        $rows = $grouped
            ->groupBy(fn ($r) => $r->lid ? ($names[$r->lid] ?? 'Belum diisi') : 'Belum diisi')
            ->map(fn ($items, $label) => (object) ['label' => $label, 'total' => $items->sum('total'), 'value' => $items->sum('value')])
            ->sortByDesc('total')
            ->values();

        return $this->topWithOther($rows, 8);
    }

    protected function topWithOther(Collection $rows, int $limit): array
    {
        $out = $rows->take($limit)->map(fn ($r) => ['label' => (string) $r->label, 'total' => (int) $r->total, 'value' => (float) $r->value])->values();
        $rest = $rows->slice($limit);
        if ($rest->isNotEmpty()) {
            $out->push(['label' => 'Lainnya', 'total' => (int) $rest->sum('total'), 'value' => (float) $rest->sum('value')]);
        }

        return $out->all();
    }

    protected function maintenanceBy(int $companyId, ReportPeriod $period, string $column, array $labels): array
    {
        return $this->viaAsset(DB::table('maintenances'), 'maintenances')->where('company_id', $companyId)->whereNull('deleted_at')
            ->whereBetween('maintenance_date', [$period->from->toDateString(), $period->to->toDateString()])
            ->selectRaw("{$column} as k, COUNT(*) as total, COALESCE(SUM(cost), 0) as cost")
            ->groupBy('k')->orderByDesc('total')->get()
            ->map(fn ($r) => ['label' => $labels[$r->k] ?? ucfirst((string) $r->k), 'total' => (int) $r->total, 'cost' => (float) $r->cost])
            ->all();
    }

    protected function requestsBy(int $companyId, ReportPeriod $period, string $column, array $labels): array
    {
        return $this->viaAsset(DB::table('maintenance_requests'), 'maintenance_requests')->where('company_id', $companyId)
            ->whereBetween('created_at', [$period->from, $period->to])
            ->selectRaw("{$column} as k, COUNT(*) as total")
            ->groupBy('k')->orderByDesc('total')->get()
            ->map(fn ($r) => ['label' => $labels[$r->k] ?? ucfirst((string) $r->k), 'total' => (int) $r->total])
            ->all();
    }

    protected function topCostAssets(int $companyId, ReportPeriod $period): array
    {
        return $this->onAssets(DB::table('maintenances')
            ->join('assets', 'assets.id', '=', 'maintenances.asset_id'))
            ->where('maintenances.company_id', $companyId)->whereNull('maintenances.deleted_at')
            ->where('maintenances.status', '!=', 'cancelled')
            ->whereBetween('maintenances.maintenance_date', [$period->from->toDateString(), $period->to->toDateString()])
            ->selectRaw('assets.asset_code as code, assets.asset_name as name, COUNT(*) as total, COALESCE(SUM(maintenances.cost), 0) as cost')
            ->groupBy('assets.id', 'assets.asset_code', 'assets.asset_name')
            ->orderByDesc('cost')->orderByDesc('total')
            ->limit(5)->get()
            ->map(fn ($r) => ['code' => $r->code, 'name' => $r->name, 'total' => (int) $r->total, 'cost' => (float) $r->cost])
            ->all();
    }

    protected function topTechnicians(int $companyId, ReportPeriod $period): array
    {
        return $this->viaAsset(DB::table('maintenance_requests'), 'maintenance_requests')
            ->join('users', 'users.id', '=', 'maintenance_requests.handled_by')
            ->where('maintenance_requests.company_id', $companyId)
            ->whereBetween('maintenance_requests.completed_at', [$period->from, $period->to])
            ->selectRaw('users.name as name, COUNT(*) as total, AVG(TIMESTAMPDIFF(MINUTE, maintenance_requests.created_at, maintenance_requests.completed_at)) as mins')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total')
            ->limit(5)->get()
            ->map(fn ($r) => ['name' => $r->name, 'total' => (int) $r->total, 'avg_hours' => $r->mins !== null ? round($r->mins / 60, 1) : null])
            ->all();
    }

    protected function upcomingMaintenance(int $companyId, CarbonImmutable $at): array
    {
        $q = $this->withIndex($this->aliveAssets($companyId, $at), 'assets_next_maintenance_index')
            ->where('assets.status', 1)
            ->whereBetween('assets.next_maintenance_date', [$at->toDateString(), $at->addDays(30)->toDateString()]);

        return [
            'total' => (clone $q)->count(),
            'items' => $q->orderBy('assets.next_maintenance_date')->limit(8)
                ->get(['assets.asset_code as code', 'assets.asset_name as name', 'assets.next_maintenance_date as date'])
                ->map(fn ($r) => (array) $r)->all(),
        ];
    }

    protected function warrantyExpiring(int $companyId, CarbonImmutable $at): array
    {
        $q = $this->withIndex($this->aliveAssets($companyId, $at), 'assets_warranty_end_index')
            ->where('assets.status', 1)
            ->whereBetween('assets.warranty_end', [$at->toDateString(), $at->addDays(60)->toDateString()]);

        return [
            'total' => (clone $q)->count(),
            'items' => $q->orderBy('assets.warranty_end')->limit(8)
                ->get(['assets.asset_code as code', 'assets.asset_name as name', 'assets.warranty_end as date'])
                ->map(fn ($r) => (array) $r)->all(),
        ];
    }

    /**
     * Paksa index tanggal (MySQL/MariaDB) agar rekap jadwal & garansi tidak
     * memindai seluruh aset. Database lain: tanpa perubahan.
     */
    protected function withIndex(Builder $query, string $index): Builder
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)
            && collect(\Illuminate\Support\Facades\Schema::getIndexes('assets'))->contains(fn ($i) => $i['name'] === $index)) {
            $query->forceIndex($index);
        }

        return $query;
    }

    /* ------------------------------------------------------------------ */
    /* Insight otomatis                                                    */
    /* ------------------------------------------------------------------ */

    public static function change(float|int|null $now, float|int|null $before): ?float
    {
        if ($now === null || $before === null || (float) $before == 0.0) {
            return null;
        }

        return round(($now - $before) / $before * 100, 1);
    }

    protected function highlights(array $r): array
    {
        $k = $r['kpi'];
        $p = $r['previous'];
        $rp = fn ($v) => 'Rp' . number_format((float) $v, 0, ',', '.');
        $out = [];

        $out[] = "Total {$this->n($k['total_assets'])} aset tercatat dengan nilai perolehan {$rp($k['total_value'])}"
            . ($k['book_value'] < $k['total_value'] ? " (nilai buku ±{$rp($k['book_value'])})." : '.');

        if ($k['new_assets'] > 0) {
            $out[] = "{$this->n($k['new_assets'])} aset baru dicatat senilai {$rp($k['new_assets_value'])}" . $this->vs($k['new_assets'], $p['new_assets']) . '.';
        }

        if ($k['maintenance_count'] > 0) {
            $out[] = "{$this->n($k['maintenance_count'])} pekerjaan maintenance, {$this->n($k['maintenance_done'])} selesai dengan biaya {$rp($k['maintenance_cost'])}" . $this->vs($k['maintenance_cost'], $p['maintenance_cost'], 'biaya') . '.';
        }

        if ($k['requests_created'] > 0 || $k['requests_completed'] > 0) {
            $line = "{$this->n($k['requests_created'])} request perbaikan masuk, {$this->n($k['requests_completed'])} diselesaikan";
            if ($k['avg_resolution_hours'] !== null) {
                $line .= ' (rata-rata ' . $this->duration($k['avg_resolution_hours']) . ')';
            }
            $out[] = $line . '.';
        }

        if ($top = ($r['by_category'][0] ?? null)) {
            $share = $k['total_value'] > 0 ? round($top['value'] / $k['total_value'] * 100) : 0;
            if ($share > 0) {
                $out[] = "Kategori bernilai terbesar: {$top['label']} ({$share}% dari total nilai aset).";
            }
        }

        if ($top = ($r['top_cost_assets'][0] ?? null)) {
            if ($top['cost'] > 0) {
                $out[] = "Biaya maintenance terbesar: {$top['code']} {$top['name']} ({$rp($top['cost'])}).";
            }
        }

        if ($r['upcoming_maintenance']['total'] > 0) {
            $out[] = "{$this->n($r['upcoming_maintenance']['total'])} aset dijadwalkan maintenance dalam 30 hari ke depan.";
        }
        if ($r['warranty_expiring']['total'] > 0) {
            $out[] = "{$this->n($r['warranty_expiring']['total'])} aset habis garansi dalam 60 hari — pertimbangkan perpanjangan.";
        }
        if ($k['unassigned'] > 0) {
            $out[] = "{$this->n($k['unassigned'])} aset aktif belum memiliki pemegang.";
        }
        if ($k['requests_open'] > 0) {
            $out[] = "{$this->n($k['requests_open'])} request perbaikan masih terbuka di akhir periode.";
        }

        return $out;
    }

    protected function vs(float|int $now, float|int $before, string $what = ''): string
    {
        $c = self::change($now, $before);
        if ($c === null || abs($c) < 1) {
            return '';
        }

        return ', ' . ($what ? $what . ' ' : '') . ($c > 0 ? 'naik ' : 'turun ') . number_format(abs($c), 0, ',', '.') . '% dari periode sebelumnya';
    }

    protected function n(int|float $v): string
    {
        return number_format((float) $v, 0, ',', '.');
    }

    public function duration(?float $hours): string
    {
        if ($hours === null) {
            return '—';
        }

        return $hours >= 48 ? number_format($hours / 24, 1, ',', '.') . ' hari' : number_format($hours, 1, ',', '.') . ' jam';
    }

    /* ------------------------------------------------------------------ */
    /* Data rinci (untuk Excel)                                            */
    /* ------------------------------------------------------------------ */

    public function newAssetRows(int $companyId, ReportPeriod $period): Collection
    {
        return $this->onAssets(DB::table('assets'))
            ->leftJoin('categories', 'categories.id', '=', 'assets.category_id')
            ->leftJoin('users', 'users.id', '=', 'assets.responsible_user_id')
            ->where('assets.company_id', $companyId)
            ->whereBetween('assets.created_at', [$period->from, $period->to])
            ->orderBy('assets.created_at')
            ->limit(self::DETAIL_LIMIT)
            ->get(['assets.created_at', 'assets.asset_code', 'assets.asset_name', 'categories.category_name', 'assets.location', 'users.name as holder', 'assets.purchase_date', 'assets.purchase_price']);
    }

    public function maintenanceRows(int $companyId, ReportPeriod $period): Collection
    {
        return $this->onAssets(DB::table('maintenances')
            ->join('assets', 'assets.id', '=', 'maintenances.asset_id'))
            ->leftJoin('vendors', 'vendors.id', '=', 'maintenances.vendor_id')
            ->where('maintenances.company_id', $companyId)->whereNull('maintenances.deleted_at')
            ->whereBetween('maintenances.maintenance_date', [$period->from->toDateString(), $period->to->toDateString()])
            ->orderBy('maintenances.maintenance_date')
            ->limit(self::DETAIL_LIMIT)
            ->get(['maintenances.maintenance_date', 'maintenances.maintenance_code', 'assets.asset_code', 'assets.asset_name', 'maintenances.maintenance_type', 'maintenances.status', 'maintenances.technician_name', 'vendors.vendor_name', 'maintenances.cost', 'maintenances.problem_description', 'maintenances.action_taken']);
    }

    public function requestRows(int $companyId, ReportPeriod $period): Collection
    {
        return $this->onAssets(DB::table('maintenance_requests')
            ->join('assets', 'assets.id', '=', 'maintenance_requests.asset_id'))
            ->leftJoin('users as req', 'req.id', '=', 'maintenance_requests.requested_by')
            ->leftJoin('users as tech', 'tech.id', '=', 'maintenance_requests.handled_by')
            ->where('maintenance_requests.company_id', $companyId)
            ->whereBetween('maintenance_requests.created_at', [$period->from, $period->to])
            ->orderBy('maintenance_requests.created_at')
            ->limit(self::DETAIL_LIMIT)
            ->get(['maintenance_requests.created_at', 'assets.asset_code', 'assets.asset_name', 'maintenance_requests.request_type', 'maintenance_requests.status', 'req.name as requester', 'tech.name as handler', 'maintenance_requests.completed_at', 'maintenance_requests.description']);
    }
}
