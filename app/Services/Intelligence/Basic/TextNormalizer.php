<?php

namespace App\Services\Intelligence\Basic;

/**
 * Merapikan bahasa chat sehari-hari agar bisa dipahami mesin aturan:
 * singkatan ("brp", "yg", "mtc"), bahasa gaul ("gk", "udh"), huruf berulang
 * ("brppp"), dan salah ketik ringan ("maintenence" -> "maintenance").
 */
class TextNormalizer
{
    /** Singkatan / sinonim -> bentuk baku. */
    public const DICTIONARY = [
        // kata tanya & jumlah
        'brp' => 'berapa', 'brapa' => 'berapa', 'berapah' => 'berapa', 'brpa' => 'berapa', 'how' => 'berapa',
        'jml' => 'jumlah', 'jmlh' => 'jumlah', 'jmlah' => 'jumlah', 'total' => 'total', 'ttl' => 'total', 'tot' => 'total',
        'byk' => 'banyak', 'bnyk' => 'banyak', 'bnyak' => 'banyak',
        'dmn' => 'dimana', 'dmna' => 'dimana', 'dmana' => 'dimana', 'mana' => 'dimana', 'where' => 'dimana',
        'sp' => 'siapa', 'spa' => 'siapa', 'sapa' => 'siapa', 'who' => 'siapa',
        'list' => 'daftar', 'tampilkan' => 'daftar', 'tunjukkan' => 'daftar', 'lihat' => 'daftar', 'liat' => 'daftar', 'show' => 'daftar', 'cek' => 'daftar', 'check' => 'daftar',
        'cari' => 'cari', 'carikan' => 'cari', 'search' => 'cari', 'find' => 'cari',
        // kata umum
        'yg' => 'yang', 'yng' => 'yang', 'dg' => 'dengan', 'dgn' => 'dengan', 'utk' => 'untuk', 'untk' => 'untuk', 'u/' => 'untuk',
        'sdh' => 'sudah', 'udh' => 'sudah', 'udah' => 'sudah', 'dah' => 'sudah', 'blm' => 'belum', 'belom' => 'belum', 'blom' => 'belum',
        'tdk' => 'tidak', 'gak' => 'tidak', 'ga' => 'tidak', 'gk' => 'tidak', 'nggak' => 'tidak', 'ngga' => 'tidak', 'enggak' => 'tidak', 'engga' => 'tidak',
        'skrg' => 'sekarang', 'skr' => 'sekarang', 'now' => 'sekarang', 'dlm' => 'dalam', 'kpn' => 'kapan', 'when' => 'kapan',
        'sy' => 'saya', 'aku' => 'saya', 'gw' => 'saya', 'gue' => 'saya', 'gua' => 'saya', 'ane' => 'saya', 'my' => 'saya', 'me' => 'saya',
        'krn' => 'karena', 'jg' => 'juga', 'aja' => 'saja', 'ajah' => 'saja', 'smua' => 'semua', 'sma' => 'semua', 'all' => 'semua',
        // aset
        'asset' => 'aset', 'asset2' => 'aset', 'aset2' => 'aset', 'asetnya' => 'aset', 'assetnya' => 'aset', 'assets' => 'aset',
        'barang' => 'aset', 'brg' => 'aset', 'inventaris' => 'aset', 'inventory' => 'aset', 'perangkat' => 'aset',
        'pegang' => 'pegang', 'megang' => 'pegang', 'dipegang' => 'pegang', 'pemegang' => 'pegang', 'pakai' => 'pegang', 'dipakai' => 'pegang', 'pake' => 'pegang',
        'milik' => 'pegang', 'punya' => 'pegang', 'tanggung' => 'pegang',
        'nilai' => 'nilai', 'harga' => 'nilai', 'value' => 'nilai', 'worth' => 'nilai',
        'rsk' => 'rusak', 'rusak2' => 'rusak', 'broken' => 'rusak', 'error' => 'rusak', 'mati' => 'rusak', 'eror' => 'rusak',
        'baru' => 'baru', 'new' => 'baru', 'bekas' => 'bekas', 'second' => 'bekas', 'used' => 'bekas',
        'aktif' => 'aktif', 'active' => 'aktif', 'nonaktif' => 'nonaktif', 'inactive' => 'nonaktif',
        'garansi' => 'garansi', 'warranty' => 'garansi', 'garansinya' => 'garansi',
        // maintenance & request
        'mtc' => 'maintenance', 'mt' => 'maintenance', 'maint' => 'maintenance', 'maintain' => 'maintenance', 'maintenance' => 'maintenance',
        'servis' => 'maintenance', 'service' => 'maintenance', 'perawatan' => 'maintenance', 'pemeliharaan' => 'maintenance', 'pm' => 'maintenance',
        'perbaikan' => 'perbaikan', 'repair' => 'perbaikan', 'benerin' => 'perbaikan', 'betulin' => 'perbaikan', 'fix' => 'perbaikan',
        'req' => 'request', 'rekues' => 'request', 'requests' => 'request', 'permintaan' => 'request', 'tiket' => 'request', 'ticket' => 'request', 'pengajuan' => 'request', 'report' => 'laporan', 'lap' => 'laporan',
        'jadwal' => 'jadwal', 'schedule' => 'jadwal', 'jdwl' => 'jadwal', 'agenda' => 'jadwal',
        'biaya' => 'biaya', 'ongkos' => 'biaya', 'cost' => 'biaya', 'pengeluaran' => 'biaya', 'budget' => 'biaya',
        'telat' => 'terlambat', 'lewat' => 'terlambat', 'overdue' => 'terlambat', 'terlewat' => 'terlambat',
        'pending' => 'menunggu', 'nunggu' => 'menunggu', 'antri' => 'menunggu', 'open' => 'terbuka', 'proses' => 'dikerjakan', 'progress' => 'dikerjakan',
        'selesai' => 'selesai', 'done' => 'selesai', 'beres' => 'selesai', 'kelar' => 'selesai',
        // waktu
        'hr' => 'hari', 'hri' => 'hari', 'today' => 'hari ini', 'kmrn' => 'kemarin', 'yesterday' => 'kemarin',
        'mgg' => 'minggu', 'mg' => 'minggu', 'week' => 'minggu', 'pekan' => 'minggu',
        'bln' => 'bulan', 'month' => 'bulan', 'thn' => 'tahun', 'th' => 'tahun', 'year' => 'tahun',
        'dpn' => 'depan', 'next' => 'depan', 'lalu' => 'lalu', 'kemaren' => 'kemarin', 'last' => 'lalu', 'this' => 'ini',
        // lokasi
        'lt' => 'lantai', 'lnt' => 'lantai', 'floor' => 'lantai', 'gd' => 'gudang', 'gdg' => 'gudang', 'warehouse' => 'gudang',
        'kntr' => 'kantor', 'ktr' => 'kantor', 'office' => 'kantor', 'rg' => 'ruang', 'rng' => 'ruang', 'ruangan' => 'ruang', 'room' => 'ruang',
        'cab' => 'cabang', 'cbg' => 'cabang', 'branch' => 'cabang',
        // perangkat umum
        'lappy' => 'laptop', 'leptop' => 'laptop', 'notebook' => 'laptop', 'kompi' => 'komputer', 'pc' => 'komputer', 'computer' => 'komputer',
        'printer' => 'printer', 'prnter' => 'printer', 'aircon' => 'ac', 'airconditioner' => 'ac',
        // perintah
        'tambah' => 'tambah', 'tambahkan' => 'tambah', 'add' => 'tambah', 'input' => 'tambah', 'catat' => 'tambah', 'daftarkan' => 'tambah', 'buat' => 'tambah', 'bikin' => 'tambah', 'create' => 'tambah',
        'halo' => 'halo', 'hallo' => 'halo', 'hai' => 'halo', 'hi' => 'halo', 'hello' => 'halo', 'pagi' => 'halo', 'siang' => 'halo', 'sore' => 'halo', 'malam' => 'halo',
        'bantuan' => 'bantuan', 'help' => 'bantuan', 'tolong' => 'bantuan', 'bisa' => 'bisa',
    ];

    /** Kata kunci baku untuk koreksi salah ketik. */
    private const VOCAB = [
        'berapa', 'jumlah', 'banyak', 'dimana', 'siapa', 'daftar', 'aset', 'maintenance', 'perbaikan', 'request',
        'jadwal', 'biaya', 'garansi', 'terlambat', 'rusak', 'nilai', 'laptop', 'printer', 'komputer', 'gudang',
        'lantai', 'kantor', 'selesai', 'menunggu', 'dikerjakan', 'minggu', 'bulan', 'tahun', 'kemarin', 'pegang',
    ];

    public static function normalize(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = str_replace(['’', '‘', '`'], "'", $text);
        // Huruf berulang > 2 (brppppa -> brpa) kecuali angka.
        $text = preg_replace('/([a-z])\1{2,}/u', '$1', $text) ?? $text;
        // Pisahkan "aset2" -> "aset", "di-gudang" tetap; buang tanda baca selain - / . pada kode.
        $text = preg_replace('/[^\p{L}\p{N}\s\-\/\.]/u', ' ', $text) ?? $text;
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        $out = [];
        foreach (explode(' ', trim($text)) as $word) {
            if ($word === '') {
                continue;
            }
            $clean = trim($word, '.');
            $out[] = self::DICTIONARY[$clean] ?? self::fixTypo($clean);
        }

        // Frasa dua kata.
        $joined = ' ' . implode(' ', $out) . ' ';
        $joined = str_replace([' apa saja ', ' apa aja ', ' dimana saja ', ' apa apa '], ' daftar ', $joined);
        $joined = str_replace([' laporan masalah ', ' request masalah '], ' request ', $joined);

        return trim(preg_replace('/\s+/u', ' ', $joined) ?? $joined);
    }

    /** Koreksi salah ketik ringan ke kosakata kunci (jarak Levenshtein kecil). */
    public static function fixTypo(string $word): string
    {
        $len = mb_strlen($word);
        if ($len < 5 || preg_match('/\d/', $word)) {
            return $word;
        }

        $best = null;
        $bestDist = $len >= 8 ? 2 : 1;
        foreach (self::VOCAB as $v) {
            if (abs(mb_strlen($v) - $len) > $bestDist) {
                continue;
            }
            $d = levenshtein($word, $v);
            if ($d <= $bestDist && ($best === null || $d < levenshtein($word, $best))) {
                $best = $v;
            }
        }

        return $best ?? $word;
    }

    /** Kata yang tidak dipakai sebagai kata pencarian. */
    public const STOPWORDS = [
        'berapa', 'jumlah', 'total', 'banyak', 'ada', 'adakah', 'apakah', 'apa', 'yang', 'di', 'ke', 'dari', 'dan', 'atau', 'dengan',
        'untuk', 'saya', 'kami', 'kita', 'tolong', 'mohon', 'dong', 'donk', 'ya', 'nih', 'sih', 'kah', 'deh', 'aja', 'saja', 'semua',
        'aset', 'daftar', 'cari', 'dimana', 'siapa', 'pegang', 'sekarang', 'ini', 'itu', 'nya', 'lagi', 'sudah', 'belum', 'tidak',
        'kapan', 'hari', 'minggu', 'bulan', 'tahun', 'lalu', 'depan', 'kemarin', 'nilai', 'biaya', 'maintenance', 'perbaikan',
        'request', 'jadwal', 'garansi', 'terlambat', 'rusak', 'baru', 'bekas', 'aktif', 'nonaktif', 'menunggu', 'dikerjakan',
        'selesai', 'terbuka', 'halo', 'bantuan', 'bisa', 'info', 'informasi', 'data', 'punya', 'oleh', 'buah', 'unit', 'pcs',
        'tambah', 'habis', 'mau', 'akan', 'segera', 'yg', 'perusahaan', 'kantor', 'lokasi', 'kategori', 'per', 'tiap', 'setiap',
        'tera', 'vasetra', 'cara', 'gimana', 'gmn', 'bagaimana', 'diambil', 'ditangani', 'abis', 'mana', 'tau', 'tahu',
    ];
}
