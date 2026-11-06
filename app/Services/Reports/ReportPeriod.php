<?php

namespace App\Services\Reports;

use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;

/**
 * Periode laporan: preset (minggu / bulan / kuartal / tahun) atau rentang bebas.
 */
class ReportPeriod
{
    public const PRESETS = [
        'this_week' => 'Minggu ini',
        'last_week' => 'Minggu lalu',
        'this_month' => 'Bulan ini',
        'last_month' => 'Bulan lalu',
        'this_quarter' => 'Kuartal ini',
        'this_year' => 'Tahun ini',
        'last_year' => 'Tahun lalu',
        'custom' => 'Pilih tanggal',
    ];

    public const MAX_DAYS = 1100; // ~3 tahun

    public function __construct(
        public readonly string $preset,
        public readonly CarbonImmutable $from,
        public readonly CarbonImmutable $to,
    ) {
    }

    public static function make(?string $preset, ?string $from = null, ?string $to = null): self
    {
        $preset = array_key_exists((string) $preset, self::PRESETS) ? $preset : 'this_month';
        $today = CarbonImmutable::today();

        [$start, $end] = match ($preset) {
            'this_week' => [$today->startOfWeek(), $today->endOfWeek()],
            'last_week' => [$today->subWeek()->startOfWeek(), $today->subWeek()->endOfWeek()],
            'last_month' => [$today->subMonthNoOverflow()->startOfMonth(), $today->subMonthNoOverflow()->endOfMonth()],
            'this_quarter' => [$today->firstOfQuarter(), $today->lastOfQuarter()->endOfDay()],
            'this_year' => [$today->startOfYear(), $today->endOfYear()],
            'last_year' => [$today->subYear()->startOfYear(), $today->subYear()->endOfYear()],
            'custom' => self::parseCustom($from, $to, $today),
            default => [$today->startOfMonth(), $today->endOfMonth()],
        };

        return new self($preset, $start->startOfDay(), $end->endOfDay());
    }

    private static function parseCustom(?string $from, ?string $to, CarbonImmutable $today): array
    {
        $start = rescue(fn () => CarbonImmutable::parse((string) $from), null, false) ?? $today->startOfMonth();
        $end = rescue(fn () => CarbonImmutable::parse((string) $to), null, false) ?? $today;

        if ($end->lt($start)) {
            [$start, $end] = [$end, $start];
        }

        if ($start->diffInDays($end) > self::MAX_DAYS) {
            $start = $end->subDays(self::MAX_DAYS);
        }

        return [$start, $end];
    }

    /** Periode sebanding sebelumnya (untuk perbandingan naik/turun). */
    public function previous(): self
    {
        $days = (int) $this->from->diffInDays($this->to->startOfDay()) + 1;

        return match ($this->preset) {
            'this_month', 'last_month' => new self('custom', $this->from->subMonthNoOverflow()->startOfMonth(), $this->from->subMonthNoOverflow()->endOfMonth()),
            'this_year', 'last_year' => new self('custom', $this->from->subYear()->startOfYear(), $this->from->subYear()->endOfYear()),
            default => new self('custom', $this->from->subDays($days), $this->from->subDay()->endOfDay()),
        };
    }

    public function days(): int
    {
        return (int) $this->from->diffInDays($this->to->startOfDay()) + 1;
    }

    /** day | week | month */
    public function granularity(): string
    {
        return match (true) {
            $this->days() <= 31 => 'day',
            $this->days() <= 92 => 'week',
            default => 'month',
        };
    }

    /**
     * Bucket waktu: key SQL => label tampilan.
     *
     * @return array<string, string>
     */
    public function buckets(): array
    {
        $out = [];
        $unit = $this->granularity();

        $period = CarbonPeriod::create(
            $unit === 'month' ? $this->from->startOfMonth() : ($unit === 'week' ? $this->from->startOfWeek() : $this->from),
            '1 ' . $unit,
            $this->to
        );

        foreach ($period as $d) {
            $d = CarbonImmutable::instance($d)->locale('id');
            $key = $this->bucketKey($d);
            $out[$key] = match ($unit) {
                'day', 'week' => $d->translatedFormat('d M'),
                default => $this->from->isSameYear($this->to) ? $d->translatedFormat('M') : $d->translatedFormat("M 'y"),
            };
        }

        return $out;
    }

    public function bucketKey(\DateTimeInterface $date): string
    {
        $d = CarbonImmutable::instance($date);

        return match ($this->granularity()) {
            'day' => $d->format('Y-m-d'),
            'week' => $d->startOfWeek()->format('Y-m-d'),
            default => $d->format('Y-m'),
        };
    }

    /** Ekspresi SQL untuk mengelompokkan kolom tanggal sesuai granularity. */
    public function bucketSql(string $column): string
    {
        return match ($this->granularity()) {
            'day' => "DATE({$column})",
            'week' => "DATE(DATE_SUB({$column}, INTERVAL WEEKDAY({$column}) DAY))",
            default => "DATE_FORMAT({$column}, '%Y-%m')",
        };
    }

    public function label(): string
    {
        $f = $this->from->locale('id');
        $t = $this->to->locale('id');

        $range = $f->isSameDay($t)
            ? $f->translatedFormat('d F Y')
            : ($f->isSameYear($t)
                ? $f->translatedFormat('d M') . ' – ' . $t->translatedFormat('d M Y')
                : $f->translatedFormat('d M Y') . ' – ' . $t->translatedFormat('d M Y'));

        return $this->preset === 'custom' ? $range : self::PRESETS[$this->preset] . ' · ' . $range;
    }

    public function title(): string
    {
        return match ($this->preset) {
            'this_week', 'last_week' => 'Laporan Mingguan',
            'this_month', 'last_month' => 'Laporan Bulanan',
            'this_quarter' => 'Laporan Kuartalan',
            'this_year', 'last_year' => 'Laporan Tahunan',
            default => 'Laporan Aset',
        };
    }

    public function slug(): string
    {
        return $this->from->format('Ymd') . '-' . $this->to->format('Ymd');
    }

    public function toQuery(): array
    {
        return $this->preset === 'custom'
            ? ['range' => 'custom', 'from' => $this->from->toDateString(), 'to' => $this->to->toDateString()]
            : ['range' => $this->preset];
    }
}
