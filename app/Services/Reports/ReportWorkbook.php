<?php

namespace App\Services\Reports;

/**
 * Laporan aset dalam bentuk Excel multi-sheet.
 */
class ReportWorkbook
{
    public function __construct(protected AssetReport $report)
    {
    }

    public function build(array $r, int $companyId, ReportPeriod $period, ?string $exportedBy = null): SpreadsheetExporter
    {
        $k = $r['kpi'];
        $p = $r['previous'];
        $excel = new SpreadsheetExporter($r['meta']['company'], $exportedBy);
        $sub = $r['meta']['period'];

        $vs = function ($now, $before) {
            $c = AssetReport::change($now, $before);

            return 'Periode sebelumnya: ' . number_format((float) $before, 0, ',', '.') . ($c === null ? '' : ' (' . ($c >= 0 ? '+' : '') . number_format($c, 1, ',', '.') . '%)');
        };

        $excel->summary('Ringkasan', $r['meta']['title'], [
            'Portofolio aset (akhir periode)' => [
                ['Total aset', $k['total_assets'], 'number'],
                ['Aset aktif', $k['active_assets'], 'number'],
                ['Nilai perolehan', $k['total_value'], 'money'],
                ['Nilai buku (estimasi garis lurus)', $k['book_value'], 'money'],
                ['Aset aktif tanpa pemegang', $k['unassigned'], 'number'],
            ],
            'Aktivitas aset' => [
                ['Aset baru dicatat', $k['new_assets'], 'number', $vs($k['new_assets'], $p['new_assets'])],
                ['Nilai aset baru', $k['new_assets_value'], 'money'],
                ['Aset dihapus', $k['disposed_assets'], 'number'],
                ['Penugasan / serah terima', $k['assignments'], 'number', $vs($k['assignments'], $p['assignments'])],
            ],
            'Maintenance' => [
                ['Pekerjaan maintenance', $k['maintenance_count'], 'number', $vs($k['maintenance_count'], $p['maintenance_count'])],
                ['Selesai', $k['maintenance_done'], 'number'],
                ['Biaya (realisasi)', $k['maintenance_cost'], 'money', $vs($k['maintenance_cost'], $p['maintenance_cost'])],
            ],
            'Request perbaikan' => [
                ['Request masuk', $k['requests_created'], 'number', $vs($k['requests_created'], $p['requests_created'])],
                ['Request selesai', $k['requests_completed'], 'number', $vs($k['requests_completed'], $p['requests_completed'])],
                ['Rata-rata waktu penyelesaian (jam)', $k['avg_resolution_hours'], 'decimal'],
                ['Masih terbuka di akhir periode', $k['requests_open'], 'number'],
            ],
            'Poin penting' => array_map(fn ($h) => [$h, null], $r['highlights']),
        ], $sub);

        $excel->table('Tren', 'Tren aktivitas', [
            ['key' => 'label', 'label' => 'Periode', 'width' => 14],
            ['key' => 'new_assets', 'label' => 'Aset baru', 'type' => 'number', 'width' => 12],
            ['key' => 'maintenance_count', 'label' => 'Maintenance', 'type' => 'number', 'width' => 13],
            ['key' => 'maintenance_cost', 'label' => 'Biaya maintenance', 'type' => 'money', 'width' => 18],
            ['key' => 'requests_created', 'label' => 'Request masuk', 'type' => 'number', 'width' => 14],
            ['key' => 'requests_completed', 'label' => 'Request selesai', 'type' => 'number', 'width' => 15],
        ], $r['trend'], $sub);

        $excel->table('Per Kategori', 'Aset per kategori', [
            ['key' => 'label', 'label' => 'Kategori', 'width' => 30],
            ['key' => 'total', 'label' => 'Jumlah aset', 'type' => 'number', 'width' => 13],
            ['key' => 'value', 'label' => 'Nilai perolehan', 'type' => 'money', 'width' => 20],
            ['label' => 'Porsi nilai', 'type' => 'percent', 'width' => 12, 'value' => fn ($row) => $k['total_value'] > 0 ? round($row['value'] / $k['total_value'] * 100, 1) : 0],
        ], $r['by_category'], 'Posisi akhir periode');

        $excel->table('Per Lokasi', 'Aset per lokasi', [
            ['key' => 'label', 'label' => 'Lokasi', 'width' => 30],
            ['key' => 'total', 'label' => 'Jumlah aset', 'type' => 'number', 'width' => 13],
            ['key' => 'value', 'label' => 'Nilai perolehan', 'type' => 'money', 'width' => 20],
        ], $r['by_location'], 'Posisi akhir periode');

        $cid = $companyId;
        $limitNote = ' · maks. ' . number_format(AssetReport::DETAIL_LIMIT, 0, ',', '.') . ' baris';

        $excel->table('Aset Baru', 'Aset baru dicatat', [
            ['key' => 'created_at', 'label' => 'Dicatat', 'type' => 'date', 'width' => 12],
            ['key' => 'asset_code', 'label' => 'Kode', 'width' => 15],
            ['key' => 'asset_name', 'label' => 'Nama aset', 'width' => 30],
            ['key' => 'category_name', 'label' => 'Kategori', 'width' => 18],
            ['key' => 'location', 'label' => 'Lokasi', 'width' => 18],
            ['key' => 'holder', 'label' => 'Pemegang', 'width' => 20],
            ['key' => 'purchase_date', 'label' => 'Tgl. beli', 'type' => 'date', 'width' => 12],
            ['key' => 'purchase_price', 'label' => 'Harga beli', 'type' => 'money', 'width' => 16],
        ], $this->report->newAssetRows($cid, $period), $sub . $limitNote);

        $excel->table('Maintenance', 'Pekerjaan maintenance', [
            ['key' => 'maintenance_date', 'label' => 'Tanggal', 'type' => 'date', 'width' => 12],
            ['key' => 'maintenance_code', 'label' => 'No.', 'width' => 16],
            ['key' => 'asset_code', 'label' => 'Kode aset', 'width' => 15],
            ['key' => 'asset_name', 'label' => 'Nama aset', 'width' => 26],
            ['label' => 'Jenis', 'width' => 12, 'value' => fn ($m) => AssetReport::MAINTENANCE_TYPES[$m->maintenance_type] ?? $m->maintenance_type],
            ['label' => 'Status', 'width' => 12, 'value' => fn ($m) => AssetReport::MAINTENANCE_STATUS[$m->status] ?? $m->status],
            ['key' => 'technician_name', 'label' => 'Teknisi', 'width' => 18],
            ['key' => 'vendor_name', 'label' => 'Vendor', 'width' => 18],
            ['key' => 'cost', 'label' => 'Biaya', 'type' => 'money', 'width' => 15],
            ['key' => 'problem_description', 'label' => 'Masalah', 'width' => 32],
            ['key' => 'action_taken', 'label' => 'Tindakan', 'width' => 32],
        ], $this->report->maintenanceRows($cid, $period), $sub . $limitNote);

        $excel->table('Request', 'Request perbaikan', [
            ['key' => 'created_at', 'label' => 'Dibuat', 'type' => 'datetime', 'width' => 16],
            ['key' => 'asset_code', 'label' => 'Kode aset', 'width' => 15],
            ['key' => 'asset_name', 'label' => 'Nama aset', 'width' => 26],
            ['label' => 'Jenis', 'width' => 15, 'value' => fn ($q) => AssetReport::REQUEST_TYPES[$q->request_type] ?? $q->request_type],
            ['label' => 'Status', 'width' => 12, 'value' => fn ($q) => AssetReport::REQUEST_STATUS[$q->status] ?? $q->status],
            ['key' => 'requester', 'label' => 'Pemohon', 'width' => 20],
            ['key' => 'handler', 'label' => 'Dikerjakan oleh', 'width' => 20],
            ['key' => 'completed_at', 'label' => 'Selesai', 'type' => 'datetime', 'width' => 16],
            ['key' => 'description', 'label' => 'Keterangan', 'width' => 40],
        ], $this->report->requestRows($cid, $period), $sub . $limitNote);

        return $excel;
    }
}
