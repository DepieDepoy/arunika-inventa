<?php

namespace App\Services\Intelligence\Basic;

use App\Models\Asset;
use App\Models\User;
use App\Support\AssetValuation;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Tera mode Dasar (gratis, tanpa layanan AI luar):
 * memahami pertanyaan seputar aset / maintenance / request dengan aturan + kamus
 * singkatan + pencocokan nama kategori, lokasi, dan karyawan perusahaan,
 * lalu menjawab LANGSUNG dari database sesuai hak akses user.
 */
class TeraBasicEngine
{
    /** Intent yang dipahami (juga dipakai sebagai daftar pilihan untuk Gemini). */
    public const INTENTS = [
        'asset_count' => 'hitung jumlah aset (boleh dengan filter)',
        'asset_list' => 'tampilkan / cari daftar aset (boleh dengan filter)',
        'asset_detail' => 'detail satu aset: lokasi, pemegang, kondisi (butuh kode / nama aset)',
        'asset_value' => 'total nilai / harga aset',
        'my_assets' => 'aset yang dipegang user yang bertanya',
        'maintenance_schedule' => 'jadwal maintenance yang akan datang / terlambat',
        'maintenance_count' => 'jumlah pekerjaan maintenance dalam periode',
        'maintenance_cost' => 'biaya maintenance dalam periode',
        'request_list' => 'daftar request perbaikan',
        'request_count' => 'jumlah request perbaikan',
        'warranty' => 'aset yang garansinya akan / sudah habis',
        'report' => 'membuka laporan mingguan / bulanan / tahunan',
        'create_hint' => 'cara menambah aset',
        'help' => 'sapaan / minta bantuan / tanya Tera bisa apa',
    ];

    private const COUNT_WORDS = ['berapa', 'jumlah', 'total', 'banyak'];
    private const LIST_LIMIT = 10;

    /* ================================================================== */
    /* MEMAHAMI                                                           */
    /* ================================================================== */

    /**
     * @return array{intent:string, params:array}|null  null = tidak dipahami
     */
    public function understand(User $user, string $question): ?array
    {
        $n = TextNormalizer::normalize($question);
        if ($n === '') {
            return null;
        }

        $has = fn (string ...$words) => (bool) array_filter($words, fn ($w) => preg_match('/(^|\s)' . preg_quote($w, '/') . '(\s|$)/u', $n));
        $lookup = $this->lookup((int) $user->company_id);
        $params = $this->entities($n, $lookup, $user);
        $isCount = $has(...self::COUNT_WORDS);

        $intent = match (true) {
            $has('tambah') && $has('aset') => 'create_hint',
            $has('laporan') => 'report',
            $has('garansi') => 'warranty',
            $has('biaya') && ($has('maintenance', 'perbaikan') || !$has('aset')) => 'maintenance_cost',
            $has('request') || ($has('perbaikan') && !$has('jadwal')) => $isCount ? 'request_count' : 'request_list',
            $has('maintenance', 'jadwal') => $isCount && !$has('jadwal') ? 'maintenance_count' : 'maintenance_schedule',
            !empty($params['code']) => 'asset_detail',
            $has('nilai') => 'asset_value',
            $has('saya') && $has('aset', 'pegang') && empty($params['holder_id']) => 'my_assets',
            $isCount && ($has('aset') || $this->hasFilter($params)) => 'asset_count',
            $has('dimana', 'siapa') && !empty($params['search']) => 'asset_detail',
            $has('daftar', 'cari', 'aset', 'pegang', 'rusak') || $this->hasFilter($params) => 'asset_list',
            $has('halo', 'bantuan') || ($has('bisa') && $has('apa')) => 'help',
            default => null,
        };

        // Hanya kata benda (mis. "laptop dell") -> cari aset bila memang ada.
        if ($intent === null && !empty($params['search'])
            && $this->assetQuery($user, ['search' => $params['search']])->exists()) {
            $intent = 'asset_list';
        }

        if ($intent === null) {
            return null;
        }

        // "dimana laptop dell" tanpa kode: detail bila hasilnya satu, selain itu daftar.
        if ($intent === 'asset_detail' && empty($params['code'])
            && $this->assetQuery($user, $params)->count() !== 1) {
            $intent = 'asset_list';
        }

        return ['intent' => $intent, 'params' => $params + ['_normalized' => $n]];
    }

    /** Filter yang dikenali dari data perusahaan (kata pencarian bebas tidak dihitung). */
    private function hasFilter(array $p): bool
    {
        return !empty($p['category_id']) || !empty($p['sub_category_id']) || !empty($p['location'])
            || !empty($p['holder_id']) || !empty($p['condition']) || isset($p['status']) || !empty($p['broken']);
    }

    /**
     * Ambil kategori, lokasi, karyawan, kode, kondisi, periode & kata pencarian dari kalimat.
     */
    private function entities(string $n, array $lookup, User $user): array
    {
        $p = [];
        $used = [];

        // Kode aset, mis. AST-000001 / ast000001.
        if (preg_match('/\b([a-z]{2,6}-?\d{2,})\b/u', $n, $m)) {
            $code = Asset::query()->where('company_id', $user->company_id)
                ->whereRaw("REPLACE(LOWER(asset_code), '-', '') = ?", [str_replace('-', '', $m[1])])
                ->value('asset_code');
            if ($code) {
                $p['code'] = $code;
                $used[] = $m[1];
            }
        }

        foreach (['category' => 'category_id', 'sub' => 'sub_category_id'] as $key => $field) {
            if ($hit = $this->matchName($n, $lookup[$key])) {
                $p[$field] = $hit[0];
                $p[$field . '_label'] = $hit[1];
                $used = array_merge($used, explode(' ', $hit[2]));
            }
        }

        if ($hit = $this->matchName($n, $lookup['locations'])) {
            $p['location'] = $hit[1];
            $used = array_merge($used, explode(' ', $hit[2]));
        } elseif (preg_match('/\b(lantai|gudang|ruang|cabang|gedung)\s+([\p{L}\p{N}\-]+)/u', $n, $m)) {
            $p['location'] = $m[1] . ' ' . $m[2];
            $used = array_merge($used, [$m[1], $m[2]]);
        }

        if ($hit = $this->matchName($n, $lookup['users'])) {
            $p['holder_id'] = $hit[0];
            $p['holder_label'] = $hit[1];
            $used = array_merge($used, explode(' ', $hit[2]));
        }

        if (preg_match('/(^|\s)(baru|bekas)(\s|$)/u', $n, $m) && !preg_match('/(dicatat|ditambah|masuk)/u', $n)) {
            $p['condition'] = $m[2] === 'baru' ? 'new' : 'used';
        }
        if (preg_match('/(^|\s)nonaktif(\s|$)/u', $n)) {
            $p['status'] = 0;
        } elseif (preg_match('/(^|\s)aktif(\s|$)/u', $n)) {
            $p['status'] = 1;
        }
        if (preg_match('/(^|\s)rusak(\s|$)/u', $n)) {
            $p['broken'] = true;
        }
        foreach (['menunggu' => 'pending', 'terbuka' => 'open', 'dikerjakan' => 'in_progress', 'selesai' => 'completed'] as $w => $s) {
            if (preg_match('/(^|\s)' . $w . '(\s|$)/u', $n)) {
                $p['request_status'] = $s;
            }
        }
        // "belum dikerjakan / belum diambil" = menunggu; "belum selesai" = masih terbuka.
        if (preg_match('/belum (dikerjakan|diambil|ditangani)/u', $n)) {
            $p['request_status'] = 'pending';
        } elseif (str_contains($n, 'belum selesai') || preg_match('/tidak selesai/u', $n)) {
            $p['request_status'] = 'open';
        }
        if (preg_match('/(^|\s)terlambat(\s|$)/u', $n)) {
            $p['overdue'] = true;
        }
        if ($period = $this->period($n)) {
            $p['period'] = $period;
        }
        if (preg_match('/(^|\s)saya(\s|$)/u', $n)) {
            $p['mine'] = true;
        }

        // Sisa kata bermakna -> kata pencarian.
        $words = array_filter(explode(' ', $n), fn ($w) => mb_strlen($w) >= 2
            && !in_array($w, TextNormalizer::STOPWORDS, true)
            && !in_array($w, $used, true)
            && !preg_match('/^\d+$/', $w));
        if ($words) {
            $p['search'] = implode(' ', array_slice(array_values($words), 0, 4));
        }

        return $p;
    }

    /**
     * Cocokkan nama dari daftar (kategori/lokasi/karyawan) di dalam kalimat.
     *
     * @param array<int|string, string> $names id => nama
     * @return array{0:int|string,1:string,2:string}|null  [id, nama asli, nama ternormalisasi]
     */
    private function matchName(string $n, array $names): ?array
    {
        $best = null;
        $padded = ' ' . $n . ' ';

        foreach ($names as $id => $name) {
            $norm = TextNormalizer::normalize($name);
            if ($norm === '' || mb_strlen($norm) < 2) {
                continue;
            }

            $score = 0;
            if (str_contains($padded, ' ' . $norm . ' ')) {
                $score = 100 + mb_strlen($norm);
            } else {
                // Salah ketik ringan pada nama satu kata (>= 5 huruf).
                if (!str_contains($norm, ' ') && mb_strlen($norm) >= 5) {
                    foreach (explode(' ', $n) as $w) {
                        if (mb_strlen($w) >= 5 && levenshtein($w, $norm) <= 1) {
                            $score = 50;
                        }
                    }
                }
                // Nama karyawan: cukup nama depan yang unik (>= 4 huruf).
                $first = explode(' ', $norm)[0];
                if (!$score && str_contains($norm, ' ') && mb_strlen($first) >= 4 && str_contains($padded, ' ' . $first . ' ')) {
                    $score = 40;
                    $norm = $first;
                }
            }

            if ($score && (!$best || $score > $best[3])) {
                $best = [$id, $name, $norm, $score];
            }
        }

        return $best ? array_slice($best, 0, 3) : null;
    }

    /** @return array{0:string,1:string,2:string}|null [dari, sampai, label] */
    private function period(string $n): ?array
    {
        $t = CarbonImmutable::today();
        $map = [
            'hari ini' => [$t, $t, 'hari ini'],
            'kemarin' => [$t->subDay(), $t->subDay(), 'kemarin'],
            'minggu ini' => [$t->startOfWeek(), $t->endOfWeek(), 'minggu ini'],
            'minggu lalu' => [$t->subWeek()->startOfWeek(), $t->subWeek()->endOfWeek(), 'minggu lalu'],
            'minggu depan' => [$t->addWeek()->startOfWeek(), $t->addWeek()->endOfWeek(), 'minggu depan'],
            'bulan ini' => [$t->startOfMonth(), $t->endOfMonth(), 'bulan ini'],
            'bulan lalu' => [$t->subMonthNoOverflow()->startOfMonth(), $t->subMonthNoOverflow()->endOfMonth(), 'bulan lalu'],
            'bulan depan' => [$t->addMonthNoOverflow()->startOfMonth(), $t->addMonthNoOverflow()->endOfMonth(), 'bulan depan'],
            'tahun ini' => [$t->startOfYear(), $t->endOfYear(), 'tahun ini'],
            'tahun lalu' => [$t->subYear()->startOfYear(), $t->subYear()->endOfYear(), 'tahun lalu'],
        ];
        foreach ($map as $phrase => [$from, $to, $label]) {
            if (str_contains(' ' . $n . ' ', ' ' . $phrase . ' ')) {
                return [$from->toDateString(), $to->toDateString(), $label];
            }
        }
        if (preg_match('/(\d{1,3})\s+hari/u', $n, $m)) {
            return [$t->toDateString(), $t->addDays((int) $m[1])->toDateString(), $m[1] . ' hari ke depan'];
        }

        return null;
    }

    /** Daftar nama milik perusahaan (cache 5 menit). */
    private function lookup(int $companyId): array
    {
        return Cache::remember("tera_lookup_{$companyId}", now()->addMinutes(5), function () use ($companyId) {
            return [
                'category' => DB::table('categories')->where('company_id', $companyId)->whereNull('deleted_at')->pluck('category_name', 'id')->all(),
                'sub' => DB::table('sub_categories')->where('company_id', $companyId)->whereNull('deleted_at')->pluck('sub_category_name', 'id')->all(),
                'locations' => DB::table('assets')->where('company_id', $companyId)->whereNull('deleted_at')->whereNotNull('location')
                    ->distinct()->limit(500)->pluck('location')->filter()->mapWithKeys(fn ($l) => [$l => $l])->all(),
                'users' => DB::table('users')->where('company_id', $companyId)->where('status', 1)->pluck('name', 'id')->all(),
            ];
        });
    }

    /* ================================================================== */
    /* MENJAWAB                                                           */
    /* ================================================================== */

    public function execute(User $user, array $plan): array
    {
        $p = $plan['params'] ?? [];
        // Parameter dari Gemini bisa berupa nama, bukan id -> cocokkan lagi.
        $p = $this->resolveNames($user, $p);

        return match ($plan['intent']) {
            'asset_count' => $this->assetCount($user, $p),
            'asset_list' => $this->assetList($user, $p),
            'asset_detail' => $this->assetDetail($user, $p),
            'asset_value' => $this->assetValue($user, $p),
            'my_assets' => $this->assetList($user, ['holder_id' => $user->id, 'holder_label' => 'Anda']),
            'maintenance_schedule' => $this->maintenanceSchedule($user, $p),
            'maintenance_count', 'maintenance_cost' => $this->maintenanceSummary($user, $p, $plan['intent'] === 'maintenance_cost'),
            'request_list' => $this->requests($user, $p, false),
            'request_count' => $this->requests($user, $p, true),
            'warranty' => $this->warranty($user, $p),
            'report' => $this->reportHint($user),
            'create_hint' => $this->reply('Untuk menambah aset, buka menu **Asset › Data Aset** lalu klik **Tambah**. Bila asetnya banyak, pakai **Import Excel** agar sekaligus.', 'create_hint', [], $this->can($user, 'asset.create') ? route('assets.index') : null),
            default => $this->help($user),
        };
    }

    public function help(User $user, ?string $prefix = null): array
    {
        $examples = [
            'brp aset di gudang?',
            'laptop yg dipegang budi',
            'aset rsk apa aja',
            'jadwal mtc minggu ini',
            'biaya maintenance bln ini',
            'request yg blm dikerjakan',
            'garansi yg mau habis',
            'total nilai aset IT',
        ];

        return $this->reply(
            ($prefix ? $prefix . "\n\n" : 'Halo! Saya Tera, asisten aset Anda. ')
            . "Coba tanya seperti ini:\n• " . implode("\n• ", $examples),
            'help'
        );
    }

    private function resolveNames(User $user, array $p): array
    {
        $lookup = $this->lookup((int) $user->company_id);
        foreach (['category' => ['category', 'category_id'], 'sub_category' => ['sub', 'sub_category_id'], 'holder' => ['users', 'holder_id']] as $key => [$list, $field]) {
            if (!empty($p[$key]) && empty($p[$field]) && ($hit = $this->matchName(TextNormalizer::normalize((string) $p[$key]), $lookup[$list]))) {
                $p[$field] = $hit[0];
                $p[$field === 'holder_id' ? 'holder_label' : $field . '_label'] = $hit[1];
            }
        }
        if (!empty($p['code']) && !preg_match('/\d/', (string) $p['code'])) {
            unset($p['code']);
        }
        if (!empty($p['code'])) {
            $p['code'] = Asset::query()->where('company_id', $user->company_id)
                ->whereRaw("REPLACE(LOWER(asset_code), '-', '') = ?", [str_replace('-', '', mb_strtolower((string) $p['code']))])
                ->value('asset_code') ?? $p['code'];
        }
        // Periode berupa frasa ("bulan ini") -> [dari, sampai, label].
        if (isset($p['period']) && is_string($p['period'])) {
            $period = $this->period(TextNormalizer::normalize($p['period']));
            $period ? $p['period'] = $period : $p = array_diff_key($p, ['period' => 1]);
        }
        if (isset($p['search']) && is_string($p['search'])) {
            $p['search'] = TextNormalizer::normalize($p['search']);
        }

        return $p;
    }

    private function can(User $user, string $permission): bool
    {
        return $user->hasPermission($permission);
    }

    /** Query aset sesuai filter & hak akses (tanpa asset.view: hanya aset yang dipegang sendiri). */
    private function assetQuery(User $user, array $p): Builder
    {
        $q = Asset::query()->where('assets.company_id', $user->company_id);

        if (!$this->can($user, 'asset.view')) {
            $q->where('assets.responsible_user_id', $user->id);
        }
        if (!empty($p['code'])) {
            $q->where('assets.asset_code', $p['code']);
        }
        if (!empty($p['category_id'])) {
            $q->where('assets.category_id', $p['category_id']);
        }
        if (!empty($p['sub_category_id'])) {
            $q->where('assets.sub_category_id', $p['sub_category_id']);
        }
        if (!empty($p['location'])) {
            $q->where('assets.location', 'like', '%' . $p['location'] . '%');
        }
        if (!empty($p['holder_id'])) {
            $q->where('assets.responsible_user_id', $p['holder_id']);
        }
        if (!empty($p['condition'])) {
            $q->where('assets.asset_condition', $p['condition']);
        }
        if (isset($p['status']) && $p['status'] !== '') {
            $q->where('assets.status', (int) $p['status']);
        }
        if (!empty($p['broken'])) {
            $q->whereIn('assets.id', DB::table('maintenance_requests')->where('company_id', $user->company_id)
                ->whereIn('status', ['pending', 'in_progress'])->select('asset_id'));
        }
        if (!empty($p['search'])) {
            foreach (array_slice(explode(' ', (string) $p['search']), 0, 4) as $word) {
                $like = '%' . $word . '%';
                $q->where(fn ($w) => $w->where('assets.asset_name', 'like', $like)
                    ->orWhere('assets.brand', 'like', $like)
                    ->orWhere('assets.model', 'like', $like)
                    ->orWhere('assets.serial_number', 'like', $like)
                    ->orWhere('assets.asset_code', 'like', $like)
                    ->orWhere('assets.location', 'like', $like));
            }
        }

        return $q;
    }

    private function describe(array $p): string
    {
        $parts = [];
        if (!empty($p['search'])) {
            $parts[] = '"' . $p['search'] . '"';
        }
        if (!empty($p['category_id_label'])) {
            $parts[] = 'kategori ' . $p['category_id_label'];
        }
        if (!empty($p['sub_category_id_label'])) {
            $parts[] = 'sub kategori ' . $p['sub_category_id_label'];
        }
        if (!empty($p['location'])) {
            $parts[] = 'di ' . $p['location'];
        }
        if (!empty($p['holder_label'])) {
            $parts[] = 'dipegang ' . $p['holder_label'];
        }
        if (!empty($p['condition'])) {
            $parts[] = 'kondisi ' . ($p['condition'] === 'new' ? 'baru' : 'bekas');
        }
        if (isset($p['status']) && $p['status'] !== '') {
            $parts[] = (int) $p['status'] === 1 ? 'aktif' : 'tidak aktif';
        }
        if (!empty($p['broken'])) {
            $parts[] = 'yang sedang rusak / dalam perbaikan';
        }

        return $parts ? ' ' . implode(', ', $parts) : '';
    }

    private function assetCount(User $user, array $p): array
    {
        $total = $this->assetQuery($user, $p)->count();
        $msg = "Ada **{$this->num($total)} aset**{$this->describe($p)}"
            . ($this->can($user, 'asset.view') ? '.' : ' yang Anda pegang (Anda hanya bisa melihat aset milik sendiri).');

        if (empty($p['category_id']) && $total > 0) {
            $rows = $this->assetQuery($user, $p)
                ->leftJoin('categories', 'categories.id', '=', 'assets.category_id')
                ->selectRaw("COALESCE(categories.category_name, 'Tanpa kategori') as label, COUNT(*) as n")
                ->groupBy('label')->orderByDesc('n')->limit(5)->get();
            if ($rows->count() > 1) {
                $msg .= "\n\nPer kategori:\n" . $rows->map(fn ($r) => "• {$r->label}: {$this->num($r->n)}")->implode("\n");
            }
        }

        return $this->reply($msg, 'asset_count', ['total' => $total], $this->can($user, 'asset.view') ? route('assets.index') : null);
    }

    private function assetList(User $user, array $p): array
    {
        $q = $this->assetQuery($user, $p);
        $total = (clone $q)->count();
        $who = !empty($p['holder_label']) && $p['holder_label'] === 'Anda';

        if ($total === 0) {
            return $this->reply(($who ? 'Saat ini tidak ada aset yang Anda pegang.' : 'Tidak ada aset' . $this->describe($p) . '.')
                . ($this->can($user, 'asset.view') ? '' : "\n(Anda hanya bisa melihat aset yang Anda pegang.)"), 'asset_list', ['total' => 0]);
        }

        $items = $q->with(['responsibleUser:id,name'])->orderBy('assets.asset_code')->limit(self::LIST_LIMIT)
            ->get(['assets.id', 'assets.asset_code', 'assets.asset_name', 'assets.location', 'assets.responsible_user_id']);

        $lines = $items->map(fn ($a) => '• ' . $a->asset_code . ' · ' . $a->asset_name
            . ($a->location ? ' · ' . $a->location : '')
            . (!$who && $a->responsibleUser ? ' · ' . $a->responsibleUser->name : ''))->implode("\n");

        $head = $who ? "Anda memegang **{$this->num($total)} aset**:" : "Ditemukan **{$this->num($total)} aset**{$this->describe($p)}:";
        $more = $total > self::LIST_LIMIT ? "\n…dan " . $this->num($total - self::LIST_LIMIT) . ' aset lainnya.' : '';

        return $this->reply("{$head}\n{$lines}{$more}", 'asset_list', [
            'total' => $total,
            'items' => $items->map(fn ($a) => ['code' => $a->asset_code, 'name' => $a->asset_name, 'location' => $a->location, 'holder' => $a->responsibleUser?->name])->all(),
        ], $this->can($user, 'asset.view') ? route('assets.index') : route('my-assets.index'));
    }

    private function assetDetail(User $user, array $p): array
    {
        $a = $this->assetQuery($user, $p)->with(['category:id,category_name', 'responsibleUser:id,name'])->first();
        if (!$a) {
            return $this->reply('Aset tersebut tidak ditemukan' . ($this->can($user, 'asset.view') ? '.' : ' di antara aset yang Anda pegang.'), 'asset_detail');
        }

        $open = DB::table('maintenance_requests')->where('asset_id', $a->id)->whereIn('status', ['pending', 'in_progress'])->latest('id')->first(['status', 'created_at']);
        $book = AssetValuation::bookValue($a);

        $lines = array_filter([
            "**{$a->asset_code} · {$a->asset_name}**",
            $a->category ? '• Kategori: ' . $a->category->category_name : null,
            '• Lokasi: ' . ($a->location ?: 'belum diisi'),
            '• Pemegang: ' . ($a->responsibleUser?->name ?? 'belum ada'),
            '• Status: ' . ((int) $a->status === 1 ? 'Aktif' : 'Tidak aktif') . ($a->asset_condition ? ' · ' . ($a->asset_condition === 'new' ? 'Baru' : 'Bekas') : ''),
            $a->purchase_price ? '• Harga beli: ' . $this->rp($a->purchase_price) . ($book ? ' (nilai buku ±' . $this->rp($book['value']) . ')' : '') : null,
            $a->warranty_end ? '• Garansi s/d: ' . $a->warranty_end->locale('id')->translatedFormat('d M Y') : null,
            $a->next_maintenance_date ? '• Maintenance berikutnya: ' . CarbonImmutable::parse($a->next_maintenance_date)->locale('id')->translatedFormat('d M Y') : null,
            $open ? '• ⚠️ Ada request perbaikan ' . ($open->status === 'pending' ? 'menunggu diambil' : 'sedang dikerjakan') : null,
        ]);

        return $this->reply(implode("\n", $lines), 'asset_detail', ['code' => $a->asset_code],
            $this->can($user, 'asset.view') ? route('assets.show', encryptId($a->id)) : route('my-assets.index'));
    }

    private function assetValue(User $user, array $p): array
    {
        $row = $this->assetQuery($user, $p)
            ->selectRaw('COUNT(*) as n, COALESCE(SUM(assets.purchase_price), 0) as v')
            ->selectRaw('COALESCE(SUM(' . AssetValuation::sqlBookValue() . '), 0) as b', [now()->toDateString()])
            ->first();

        return $this->reply(
            "Total nilai perolehan **{$this->rp($row->v)}** dari {$this->num($row->n)} aset{$this->describe($p)}."
            . ((float) $row->b < (float) $row->v ? "\nPerkiraan nilai buku saat ini: {$this->rp($row->b)}." : ''),
            'asset_value', ['total' => (int) $row->n, 'value' => (float) $row->v, 'book_value' => round((float) $row->b)]
        );
    }

    private function maintenanceSchedule(User $user, array $p): array
    {
        if (!$this->can($user, 'maintenance.view') && !$this->can($user, 'maintenance.edit')) {
            return $this->reply('Anda belum punya akses untuk melihat jadwal maintenance. Hubungi administrator perusahaan.', 'maintenance_schedule');
        }

        $today = CarbonImmutable::today();
        [$from, $to, $label] = !empty($p['overdue'])
            ? ['2000-01-01', $today->subDay()->toDateString(), 'yang terlambat']
            : ($p['period'] ?? [$today->toDateString(), $today->addDays(30)->toDateString(), '30 hari ke depan']);

        $assets = $this->assetQuery($user, array_intersect_key($p, array_flip(['category_id', 'location', 'search'])))
            ->where('assets.status', 1)
            ->whereBetween('assets.next_maintenance_date', [$from, $to])
            ->orderBy('assets.next_maintenance_date')
            ->get(['assets.asset_code', 'assets.asset_name', 'assets.next_maintenance_date']);

        if ($assets->isEmpty()) {
            return $this->reply("Tidak ada jadwal maintenance {$label}. 👍", 'maintenance_schedule', ['total' => 0], route('maintenance.index'));
        }

        $lines = $assets->take(self::LIST_LIMIT)->map(fn ($a) => '• ' . CarbonImmutable::parse($a->next_maintenance_date)->locale('id')->translatedFormat('d M') . ' · ' . $a->asset_code . ' ' . $a->asset_name)->implode("\n");
        $more = $assets->count() > self::LIST_LIMIT ? "\n…dan " . ($assets->count() - self::LIST_LIMIT) . ' lainnya.' : '';

        return $this->reply("Ada **{$this->num($assets->count())} aset** dengan jadwal maintenance {$label}:\n{$lines}{$more}", 'maintenance_schedule', ['total' => $assets->count()], route('maintenance.index'));
    }

    private function maintenanceSummary(User $user, array $p, bool $cost): array
    {
        if (!$this->can($user, 'maintenance.view') && !$this->can($user, 'maintenance.edit')) {
            return $this->reply('Anda belum punya akses untuk melihat data maintenance. Hubungi administrator perusahaan.', 'maintenance_summary');
        }

        $t = CarbonImmutable::today();
        [$from, $to, $label] = $p['period'] ?? [$t->startOfMonth()->toDateString(), $t->endOfMonth()->toDateString(), 'bulan ini'];

        $row = DB::table('maintenances')->where('company_id', $user->company_id)->whereNull('deleted_at')
            ->whereBetween('maintenance_date', [$from, $to])->where('status', '!=', 'cancelled')
            ->selectRaw("COUNT(*) as n, SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as done, COALESCE(SUM(CASE WHEN status = 'completed' THEN cost ELSE 0 END), 0) as cost")
            ->first();

        $msg = $cost
            ? "Biaya maintenance {$label}: **{$this->rp($row->cost)}** dari {$this->num($row->done)} pekerjaan yang selesai."
            : "Ada **{$this->num($row->n)} pekerjaan maintenance** {$label}, {$this->num($row->done)} sudah selesai.";

        if ($cost && (float) $row->cost > 0) {
            $top = DB::table('maintenances')->join('assets', 'assets.id', '=', 'maintenances.asset_id')
                ->where('maintenances.company_id', $user->company_id)->whereNull('maintenances.deleted_at')
                ->where('maintenances.status', 'completed')->whereBetween('maintenances.maintenance_date', [$from, $to])
                ->selectRaw('assets.asset_code, assets.asset_name, SUM(maintenances.cost) as c')
                ->groupBy('assets.id', 'assets.asset_code', 'assets.asset_name')->orderByDesc('c')->limit(3)->get();
            if ($top->isNotEmpty()) {
                $msg .= "\n\nTerbesar:\n" . $top->map(fn ($r) => "• {$r->asset_code} {$r->asset_name}: {$this->rp($r->c)}")->implode("\n");
            }
        }

        return $this->reply($msg, $cost ? 'maintenance_cost' : 'maintenance_count', ['total' => (int) $row->n, 'cost' => (float) $row->cost],
            $this->can($user, 'report.view') ? route('reports.index') : route('maintenance.index'));
    }

    private function requests(User $user, array $p, bool $count): array
    {
        $all = $this->can($user, 'maintenance.view') || $this->can($user, 'maintenance.edit');
        $mine = !$all || !empty($p['mine']);

        $q = DB::table('maintenance_requests')
            ->join('assets', 'assets.id', '=', 'maintenance_requests.asset_id')
            ->leftJoin('users as h', 'h.id', '=', 'maintenance_requests.handled_by')
            ->where('maintenance_requests.company_id', $user->company_id)
            ->when($mine, fn ($w) => $w->where(fn ($x) => $x->where('maintenance_requests.requested_by', $user->id)->orWhere('maintenance_requests.handled_by', $user->id)));

        $status = $p['request_status'] ?? ($count ? null : 'open');
        if ($status === 'open') {
            $q->whereIn('maintenance_requests.status', ['pending', 'in_progress']);
        } elseif ($status) {
            $q->where('maintenance_requests.status', $status);
        }
        if (!empty($p['period'])) {
            $q->whereBetween('maintenance_requests.created_at', [$p['period'][0] . ' 00:00:00', $p['period'][1] . ' 23:59:59']);
        }

        $label = ['open' => 'yang masih terbuka', 'pending' => 'yang menunggu diambil', 'in_progress' => 'yang sedang dikerjakan', 'completed' => 'yang sudah selesai'][$status] ?? '';
        $label = trim($label . (!empty($p['period']) ? ' ' . $p['period'][2] : '') . ($mine ? ' milik Anda' : ''));
        $total = (clone $q)->count();
        $url = $all ? route('maintenance.requests.index') : route('my-assets.index');

        if ($count || $total === 0) {
            $by = $count && !$status ? (clone $q)->selectRaw('maintenance_requests.status as s, COUNT(*) as n')->groupBy('s')->pluck('n', 's') : collect();
            $names = ['pending' => 'menunggu', 'in_progress' => 'dikerjakan', 'completed' => 'selesai', 'cancelled' => 'dibatalkan'];
            $detail = $by->isNotEmpty() ? "\n" . $by->map(fn ($n, $s) => '• ' . ($names[$s] ?? $s) . ': ' . $this->num($n))->implode("\n") : '';

            return $this->reply("Ada **{$this->num($total)} request perbaikan** {$label}.{$detail}", $count ? 'request_count' : 'request_list', ['total' => $total], $url);
        }

        $items = $q->orderByDesc('maintenance_requests.id')->limit(self::LIST_LIMIT)
            ->get(['maintenance_requests.status', 'maintenance_requests.created_at', 'assets.asset_code', 'assets.asset_name', 'h.name as handler']);
        $lines = $items->map(fn ($r) => '• ' . $r->asset_code . ' ' . $r->asset_name . ' · '
            . ($r->status === 'pending' ? 'menunggu' : ($r->status === 'in_progress' ? 'dikerjakan ' . ($r->handler ?? '') : ($r->status === 'completed' ? 'selesai' : $r->status))))->implode("\n");
        $more = $total > self::LIST_LIMIT ? "\n…dan " . ($total - self::LIST_LIMIT) . ' lainnya.' : '';

        return $this->reply("Ada **{$this->num($total)} request** {$label}:\n{$lines}{$more}", 'request_list', ['total' => $total], $url);
    }

    private function warranty(User $user, array $p): array
    {
        $today = CarbonImmutable::today();
        $expired = (bool) preg_match('/sudah|lewat|terlambat/u', (string) ($p['_normalized'] ?? ''));
        [$from, $to, $label] = $expired
            ? ['2000-01-01', $today->subDay()->toDateString(), 'yang garansinya sudah habis']
            : [$today->toDateString(), ($p['period'][1] ?? $today->addDays(60)->toDateString()), 'yang garansinya habis ' . (!empty($p['period']) ? $p['period'][2] : 'dalam 60 hari')];

        $assets = $this->assetQuery($user, array_intersect_key($p, array_flip(['category_id', 'location'])))
            ->where('assets.status', 1)->whereBetween('assets.warranty_end', [$from, $to])
            ->orderBy('assets.warranty_end')->get(['assets.asset_code', 'assets.asset_name', 'assets.warranty_end']);

        if ($assets->isEmpty()) {
            return $this->reply("Tidak ada aset {$label}.", 'warranty', ['total' => 0]);
        }

        $lines = $assets->take(self::LIST_LIMIT)->map(fn ($a) => '• ' . $a->warranty_end->locale('id')->translatedFormat('d M Y') . ' · ' . $a->asset_code . ' ' . $a->asset_name)->implode("\n");

        return $this->reply("Ada **{$this->num($assets->count())} aset** {$label}:\n{$lines}", 'warranty', ['total' => $assets->count()]);
    }

    private function reportHint(User $user): array
    {
        if (!$this->can($user, 'report.view')) {
            return $this->reply('Laporan mingguan, bulanan, dan tahunan tersedia di menu **Laporan** untuk user yang punya akses. Hubungi administrator perusahaan bila Anda membutuhkannya.', 'report');
        }

        return $this->reply('Laporan mingguan, bulanan, kuartalan, atau tahunan ada di menu **Laporan**. Pilih periodenya, lalu unduh sebagai PDF, Excel, atau PowerPoint.', 'report', [], route('reports.index'));
    }

    /* ------------------------------------------------------------------ */

    private function reply(string $message, string $intent, array $data = [], ?string $url = null): array
    {
        // Teks polos untuk widget (tanpa penanda **tebal**).
        $plain = str_replace('**', '', $message);

        return [
            'success' => true,
            'type' => 'answer',
            'engine' => 'basic',
            'intent' => 'basic.' . $intent,
            'message' => $plain,
            'data' => $data + ($url ? ['url' => $url] : []),
        ];
    }

    private function num(int|float|string|null $v): string
    {
        return number_format((float) $v, 0, ',', '.');
    }

    private function rp(int|float|string|null $v): string
    {
        return 'Rp' . number_format((float) $v, 0, ',', '.');
    }
}
