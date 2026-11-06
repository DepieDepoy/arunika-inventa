<?php

namespace App\Services\Reports;

use App\Services\Reports\Pptx\PptxBuilder;
use App\Services\Reports\Pptx\PptxSlide;
use Illuminate\Support\Carbon;

/**
 * Menyusun laporan aset menjadi presentasi PowerPoint siap pakai rapat.
 */
class ReportPresentation
{
    private const NAVY = '0B2447';
    private const BLUE = '1F5FBF';
    private const TEAL = '14B8A6';
    private const ORANGE = 'EB6834';
    private const INK = '24364D';
    private const MUTED = '8592A6';
    private const SOFT = 'F4F7FB';
    private const GREEN = '15803D';
    private const RED = 'B91C1C';

    private int $page = 0;
    private array $r;
    private ?string $logoPath = null;

    /**
     * @param string|null $logoPath  logo perusahaan; null = logo Vasetra
     */
    public function build(array $report, string $path, ?string $logoPath = null): void
    {
        $this->r = $report;
        $this->logoPath = $logoPath;
        $meta = $report['meta'];
        $deck = new PptxBuilder($meta['title'] . ' — ' . $meta['company'], 'Vasetra');

        $this->cover($deck->slide(self::NAVY));
        $this->summary($deck->slide());
        $this->kpis($deck->slide(self::SOFT));
        $this->trend($deck->slide());
        $this->composition($deck->slide());
        $this->maintenance($deck->slide());
        $this->requests($deck->slide());
        $this->outlook($deck->slide());
        $this->closing($deck->slide(self::NAVY));

        $deck->save($path);
    }

    /* ------------------------------------------------------------------ */
    /* Slide                                                               */
    /* ------------------------------------------------------------------ */

    private function cover(PptxSlide $s): void
    {
        $m = $this->r['meta'];
        $s->rect(0, 0, 1280, 720, self::NAVY)
            ->rect(820, -160, 620, 620, self::BLUE, 0.5, null, 0.35)
            ->rect(980, 380, 420, 420, self::TEAL, 0.5, null, 0.25)
            ->rect(80, 70, 280, 92, 'FFFFFF', 0.2);
        $this->logo($s, 96, 82, 248, 68);

        $s->text(80, 250, 900, 40, strtoupper($m['title']), ['size' => 16, 'bold' => true, 'color' => '22D3EE'])
            ->text(80, 292, 1000, 110, (string) $m['company'], ['size' => 44, 'bold' => true, 'color' => 'FFFFFF'])
            ->text(80, 410, 1000, 40, $m['period'], ['size' => 20, 'color' => 'D6E2F5'])
            ->rect(80, 470, 90, 5, self::TEAL)
            ->text(80, 640, 900, 30, 'Dibuat ' . Carbon::parse($m['generated_at'])->locale('id')->translatedFormat('d F Y H:i') . ' · Vasetra Asset Lifecycle Platform', ['size' => 11, 'color' => '9FB3D1']);
    }

    private function summary(PptxSlide $s): void
    {
        $this->frame($s, 'Ringkasan', 'Poin penting periode ini');
        $items = array_slice($this->r['highlights'], 0, 8) ?: ['Belum ada aktivitas pada periode ini.'];

        $s->rect(60, 140, 760, 520, self::SOFT, 0.04)
            ->text(90, 165, 700, 480, $items, ['size' => 16, 'bullet' => true, 'spacing' => 10, 'color' => self::INK]);

        $k = $this->r['kpi'];
        $tiles = [
            ['Total aset', $this->num($k['total_assets']), self::BLUE],
            ['Nilai perolehan', $this->rpShort($k['total_value']), self::NAVY],
            ['Request selesai', $this->num($k['requests_completed']) . ' / ' . $this->num($k['requests_created']), self::TEAL],
        ];
        foreach ($tiles as $i => [$label, $value, $color]) {
            $y = 140 + $i * 178;
            $s->rect(850, $y, 370, 160, $color, 0.08)
                ->text(878, $y + 26, 320, 24, $label, ['size' => 13, 'color' => 'D6E2F5'])
                ->text(878, $y + 60, 320, 70, $value, ['size' => 34, 'bold' => true, 'color' => 'FFFFFF']);
        }
    }

    private function kpis(PptxSlide $s): void
    {
        $this->frame($s, 'Angka utama', 'Dibanding ' . $this->r['meta']['previous']);
        $k = $this->r['kpi'];
        $p = $this->r['previous'];

        $tiles = [
            ['Total aset', $this->num($k['total_assets']), $this->num($k['active_assets']) . ' aktif', null],
            ['Nilai perolehan', $this->rpShort($k['total_value']), 'Nilai buku ' . $this->rpShort($k['book_value']), null],
            ['Aset baru', $this->num($k['new_assets']), $this->rpShort($k['new_assets_value']), AssetReport::change($k['new_assets'], $p['new_assets'])],
            ['Penugasan aset', $this->num($k['assignments']), 'serah terima / pindah', AssetReport::change($k['assignments'], $p['assignments'])],
            ['Maintenance', $this->num($k['maintenance_count']), $this->num($k['maintenance_done']) . ' selesai', AssetReport::change($k['maintenance_count'], $p['maintenance_count'])],
            ['Biaya maintenance', $this->rpShort($k['maintenance_cost']), 'realisasi (selesai)', AssetReport::change($k['maintenance_cost'], $p['maintenance_cost'])],
            ['Request masuk', $this->num($k['requests_created']), $this->num($k['requests_open']) . ' masih terbuka', AssetReport::change($k['requests_created'], $p['requests_created'])],
            ['Waktu penyelesaian', app(AssetReport::class)->duration($k['avg_resolution_hours']), $this->num($k['requests_completed']) . ' request selesai', null],
        ];

        foreach ($tiles as $i => [$label, $value, $sub, $chg]) {
            $x = 60 + ($i % 4) * 295;
            $y = 150 + intdiv($i, 4) * 255;
            $s->rect($x, $y, 275, 230, 'FFFFFF', 0.06, 'DCE3EC')
                ->rect($x, $y, 6, 230, $i < 4 ? self::BLUE : self::TEAL)
                ->text($x + 26, $y + 26, 230, 24, $label, ['size' => 13, 'bold' => true, 'color' => self::MUTED])
                ->text($x + 26, $y + 62, 235, 60, $value, ['size' => 30, 'bold' => true, 'color' => self::NAVY])
                ->text($x + 26, $y + 132, 235, 24, $sub, ['size' => 12, 'color' => self::MUTED]);

            if ($chg !== null) {
                $up = $chg >= 0;
                $s->rect($x + 26, $y + 172, 120, 30, $up ? 'DCFCE7' : 'FEE2E2', 0.5)
                    ->text($x + 26, $y + 172, 120, 30, ($up ? '▲ ' : '▼ ') . number_format(abs($chg), 0, ',', '.') . '%', ['size' => 12, 'bold' => true, 'align' => 'ctr', 'valign' => 'ctr', 'color' => $up ? self::GREEN : self::RED]);
            }
        }
    }

    private function trend(PptxSlide $s): void
    {
        $unit = ['day' => 'per hari', 'week' => 'per minggu', 'month' => 'per bulan'][$this->r['meta']['granularity']] ?? '';
        $this->frame($s, 'Tren aktivitas', 'Request perbaikan & maintenance ' . $unit);
        $rows = $this->r['trend'];
        $labels = array_column($rows, 'label');

        $s->text(60, 140, 560, 26, 'Request perbaikan', ['size' => 15, 'bold' => true, 'color' => self::NAVY]);
        $this->legend($s, 60, 170, [['Masuk', self::BLUE], ['Selesai', self::TEAL]]);
        $this->bars($s, 60, 205, 560, 420, $labels, [
            array_column($rows, 'requests_created'),
            array_column($rows, 'requests_completed'),
        ], [self::BLUE, self::TEAL], fn ($v) => $this->num($v));

        $s->text(680, 140, 540, 26, 'Biaya maintenance (selesai)', ['size' => 15, 'bold' => true, 'color' => self::NAVY]);
        $this->legend($s, 680, 170, [['Biaya', self::ORANGE]]);
        $this->bars($s, 680, 205, 540, 420, $labels, [array_column($rows, 'maintenance_cost')], [self::ORANGE], fn ($v) => $this->rpShort($v));
    }

    private function composition(PptxSlide $s): void
    {
        $this->frame($s, 'Komposisi aset', 'Per kategori (nilai perolehan) dan lokasi (jumlah aset)');

        $s->text(60, 140, 560, 26, 'Nilai aset per kategori', ['size' => 15, 'bold' => true, 'color' => self::NAVY]);
        $this->hbars($s, 60, 185, 560, $this->r['by_category'], 'value', fn ($v) => $this->rpShort($v), self::BLUE);

        $s->text(680, 140, 540, 26, 'Jumlah aset per lokasi', ['size' => 15, 'bold' => true, 'color' => self::NAVY]);
        $this->hbars($s, 680, 185, 540, $this->r['by_location'], 'total', fn ($v) => $this->num($v) . ' aset', self::TEAL);
    }

    private function maintenance(PptxSlide $s): void
    {
        $k = $this->r['kpi'];
        $this->frame($s, 'Maintenance', $this->num($k['maintenance_count']) . ' pekerjaan · biaya ' . $this->rp($k['maintenance_cost']));

        $s->text(60, 140, 460, 26, 'Berdasarkan jenis', ['size' => 15, 'bold' => true, 'color' => self::NAVY]);
        $rows = [['Jenis', 'Jumlah', 'Biaya']];
        foreach ($this->r['maintenance_by_type'] as $t) {
            $rows[] = [$t['label'], $this->num($t['total']), $this->rp($t['cost'])];
        }
        $this->tableOrEmpty($s, 60, 180, 460, $rows, [190, 100, 170], ['l', 'r', 'r']);

        $s->text(580, 140, 640, 26, '5 aset dengan biaya maintenance terbesar', ['size' => 15, 'bold' => true, 'color' => self::NAVY]);
        $rows = [['Kode', 'Nama aset', 'Kali', 'Biaya']];
        foreach ($this->r['top_cost_assets'] as $a) {
            $rows[] = [$a['code'], $this->cut($a['name'], 30), $this->num($a['total']), $this->rp($a['cost'])];
        }
        $this->tableOrEmpty($s, 580, 180, 640, $rows, [120, 290, 70, 160], ['l', 'l', 'r', 'r']);
    }

    private function requests(PptxSlide $s): void
    {
        $k = $this->r['kpi'];
        $this->frame($s, 'Request perbaikan', $this->num($k['requests_created']) . ' masuk · ' . $this->num($k['requests_completed']) . ' selesai · rata-rata ' . app(AssetReport::class)->duration($k['avg_resolution_hours']));

        $s->text(60, 140, 520, 26, 'Status request yang masuk', ['size' => 15, 'bold' => true, 'color' => self::NAVY]);
        $this->hbars($s, 60, 185, 520, $this->r['requests_by_status'], 'total', fn ($v) => $this->num($v), self::BLUE);

        $s->text(640, 140, 580, 26, 'Teknisi paling aktif', ['size' => 15, 'bold' => true, 'color' => self::NAVY]);
        $rows = [['Teknisi', 'Selesai', 'Rata-rata']];
        foreach ($this->r['top_technicians'] as $t) {
            $rows[] = [$t['name'], $this->num($t['total']), app(AssetReport::class)->duration($t['avg_hours'])];
        }
        $this->tableOrEmpty($s, 640, 180, 580, $rows, [300, 120, 160], ['l', 'r', 'r']);
    }

    private function outlook(PptxSlide $s): void
    {
        $up = $this->r['upcoming_maintenance'];
        $wr = $this->r['warranty_expiring'];
        $this->frame($s, 'Yang perlu diperhatikan', 'Agenda setelah periode laporan');

        $s->text(60, 140, 560, 26, 'Maintenance 30 hari ke depan (' . $this->num($up['total']) . ')', ['size' => 15, 'bold' => true, 'color' => self::NAVY]);
        $rows = [['Tanggal', 'Kode', 'Aset']];
        foreach ($up['items'] as $i) {
            $rows[] = [Carbon::parse($i['date'])->format('d/m/Y'), $i['code'], $this->cut($i['name'], 28)];
        }
        $this->tableOrEmpty($s, 60, 180, 560, $rows, [120, 130, 310], ['l', 'l', 'l']);

        $s->text(680, 140, 540, 26, 'Garansi habis 60 hari ke depan (' . $this->num($wr['total']) . ')', ['size' => 15, 'bold' => true, 'color' => self::NAVY]);
        $rows = [['Tanggal', 'Kode', 'Aset']];
        foreach ($wr['items'] as $i) {
            $rows[] = [Carbon::parse($i['date'])->format('d/m/Y'), $i['code'], $this->cut($i['name'], 26)];
        }
        $this->tableOrEmpty($s, 680, 180, 540, $rows, [120, 130, 290], ['l', 'l', 'l']);
    }

    private function closing(PptxSlide $s): void
    {
        $m = $this->r['meta'];
        $s->rect(-120, 420, 520, 520, self::BLUE, 0.5, null, 0.35)
            ->rect(1000, -140, 400, 400, self::TEAL, 0.5, null, 0.25)
            ->text(0, 250, 1280, 70, 'Terima kasih', ['size' => 44, 'bold' => true, 'color' => 'FFFFFF', 'align' => 'ctr'])
            ->text(0, 330, 1280, 30, $m['company'] . ' · ' . $m['period'], ['size' => 16, 'color' => 'D6E2F5', 'align' => 'ctr'])
            ->rect(500, 392, 280, 92, 'FFFFFF', 0.2);
        $this->logo($s, 516, 404, 248, 68);
        $s->text(0, 500, 1280, 24, 'Data diambil langsung dari Vasetra Asset Lifecycle Platform', ['size' => 12, 'color' => '9FB3D1', 'align' => 'ctr']);
    }

    /* ------------------------------------------------------------------ */
    /* Komponen                                                            */
    /* ------------------------------------------------------------------ */

    private function frame(PptxSlide $s, string $title, string $subtitle): void
    {
        $this->page++;
        $m = $this->r['meta'];
        $s->rect(0, 0, 1280, 6, self::BLUE)
            ->text(60, 40, 800, 44, $title, ['size' => 28, 'bold' => true, 'color' => self::NAVY])
            ->text(60, 90, 900, 26, $subtitle, ['size' => 14, 'color' => self::MUTED])
            ->text(820, 46, 400, 22, (string) $m['company'], ['size' => 12, 'bold' => true, 'color' => self::INK, 'align' => 'r'])
            ->text(820, 68, 400, 22, $m['period'], ['size' => 11, 'color' => self::MUTED, 'align' => 'r'])
            ->rect(60, 680, 1160, 1, 'DCE3EC')
            ->text(60, 688, 600, 20, 'Vasetra · ' . $m['title'], ['size' => 10, 'color' => self::MUTED])
            ->text(1020, 688, 200, 20, (string) ($this->page + 1), ['size' => 10, 'color' => self::MUTED, 'align' => 'r']);
    }

    /**
     * Logo di dalam kotak putih (x, y, w, h): diperkecil proporsional & ditengahkan,
     * sehingga logo mendatar maupun kotak tetap rapi.
     */
    private function logo(PptxSlide $s, float $x, float $y, float $w, float $h): void
    {
        $path = $this->logoPath && is_file($this->logoPath) ? $this->logoPath : \App\Services\CompanyBranding::vasetraLogoPath();
        if (!is_file($path)) {
            return;
        }

        [$iw, $ih] = getimagesize($path) ?: [900, 185];
        $scale = min($w / max(1, $iw), $h / max(1, $ih));
        $lw = $iw * $scale;
        $lh = $ih * $scale;
        $s->image($path, $x + ($w - $lw) / 2, $y + ($h - $lh) / 2, $lw, $lh);
    }

    private function legend(PptxSlide $s, float $x, float $y, array $items): void
    {
        foreach ($items as [$label, $color]) {
            $s->rect($x, $y + 6, 14, 14, $color, 0.2)->text($x + 20, $y + 3, 120, 20, $label, ['size' => 11, 'color' => self::INK]);
            $x += 30 + mb_strlen($label) * 8;
        }
    }

    /**
     * Grafik batang vertikal (satu atau beberapa seri), digambar sebagai shape.
     */
    private function bars(PptxSlide $s, float $x, float $y, float $w, float $h, array $labels, array $series, array $colors, callable $fmt): void
    {
        $max = 0;
        foreach ($series as $vals) {
            $max = max($max, ...array_map('floatval', $vals ?: [0]));
        }

        if ($max <= 0) {
            $s->rect($x, $y, $w, $h, self::SOFT, 0.04)->text($x, $y, $w, $h, 'Tidak ada data pada periode ini', ['size' => 13, 'color' => self::MUTED, 'align' => 'ctr', 'valign' => 'ctr']);

            return;
        }

        $chartH = $h - 40;
        $n = max(1, count($labels));
        $slot = $w / $n;
        $groupW = $slot * 0.7;
        $barW = max(2, $groupW / count($series) - 2);

        // Garis dasar & nilai maksimum.
        $s->rect($x, $y + $chartH, $w, 1.5, 'B6C2D2')
            ->rect($x, $y, $w, 0.75, 'EEF2F7')
            ->text($x, $y - 20, $w, 18, 'maks ' . $fmt($max), ['size' => 9, 'color' => self::MUTED, 'align' => 'r']);

        $every = max(1, (int) ceil($n / 12));
        foreach ($labels as $i => $label) {
            $gx = $x + $i * $slot + ($slot - $groupW) / 2;
            foreach ($series as $si => $vals) {
                $v = (float) ($vals[$i] ?? 0);
                $bh = $v > 0 ? max(2, $v / $max * ($chartH - 10)) : 0;
                if ($bh > 0) {
                    $s->rect($gx + $si * ($barW + 2), $y + $chartH - $bh, $barW, $bh, $colors[$si] ?? self::BLUE);
                }
            }
            if ($i % $every === 0) {
                // Label di tengah slot batang; lebar maks 1 slot x jarak label.
                $lw = min($slot * $every, 90);
                $s->text($x + $i * $slot + $slot / 2 - $lw / 2, $y + $chartH + 8, $lw, 18, $label, ['size' => 9, 'color' => self::MUTED, 'align' => 'ctr']);
            }
        }
    }

    /**
     * Grafik batang horizontal dengan label & nilai.
     */
    private function hbars(PptxSlide $s, float $x, float $y, float $w, array $rows, string $key, callable $fmt, string $color): void
    {
        $rows = array_slice($rows, 0, 9);
        if (!$rows) {
            $s->rect($x, $y, $w, 200, self::SOFT, 0.04)->text($x, $y, $w, 200, 'Tidak ada data pada periode ini', ['size' => 13, 'color' => self::MUTED, 'align' => 'ctr', 'valign' => 'ctr']);

            return;
        }

        $max = max(1, max(array_map(fn ($r) => (float) $r[$key], $rows)));
        foreach ($rows as $i => $row) {
            $ry = $y + $i * 52;
            $s->text($x, $ry, $w * 0.62, 22, $this->cut($row['label'], 40), ['size' => 12, 'bold' => true, 'color' => self::INK])
                ->text($x + $w * 0.62, $ry, $w * 0.38, 22, $fmt($row[$key]), ['size' => 12, 'color' => self::INK, 'align' => 'r'])
                ->rect($x, $ry + 26, $w, 12, 'EEF2F7', 0.5)
                ->rect($x, $ry + 26, max(6, $w * (float) $row[$key] / $max), 12, $color, 0.5);
        }
    }

    private function tableOrEmpty(PptxSlide $s, float $x, float $y, float $w, array $rows, array $widths, array $align): void
    {
        if (count($rows) <= 1) {
            $s->rect($x, $y, $w, 160, self::SOFT, 0.04)->text($x, $y, $w, 160, 'Tidak ada data pada periode ini', ['size' => 13, 'color' => self::MUTED, 'align' => 'ctr', 'valign' => 'ctr']);

            return;
        }

        $s->table($x, $y, $w, $rows, ['widths' => $widths, 'align' => $align, 'size' => 12, 'rowHeight' => 40]);
    }

    private function num(float|int|null $v): string
    {
        return number_format((float) $v, 0, ',', '.');
    }

    private function rp(float|int|null $v): string
    {
        return 'Rp' . number_format((float) $v, 0, ',', '.');
    }

    /** Rupiah ringkas: Rp1,2 M / Rp850 jt / Rp12 rb. */
    private function rpShort(float|int|null $v): string
    {
        $v = (float) $v;

        return match (true) {
            $v >= 1e12 => 'Rp' . number_format($v / 1e12, 1, ',', '.') . ' T',
            $v >= 1e9 => 'Rp' . number_format($v / 1e9, 1, ',', '.') . ' M',
            $v >= 1e6 => 'Rp' . number_format($v / 1e6, 1, ',', '.') . ' jt',
            $v >= 1e3 => 'Rp' . number_format($v / 1e3, 0, ',', '.') . ' rb',
            default => 'Rp' . number_format($v, 0, ',', '.'),
        };
    }

    private function cut(?string $s, int $n): string
    {
        return mb_strimwidth((string) $s, 0, $n, '…');
    }
}
