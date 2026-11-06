<?php

namespace App\Services\Landing;

use App\Models\SiteContent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Konten landing page yang bisa diedit pengelola platform lewat CMS.
 *
 * - schema()   : struktur setiap bagian (dipakai form CMS & validasi).
 * - defaults() : teks bawaan. Bila bagian belum pernah diedit, nilai ini yang tampil.
 * - all()      : konten akhir = bawaan ditimpa isi tabel site_contents (di-cache).
 *
 * Token {hari_trial} di teks diganti otomatis dengan masa coba paket gratis.
 */
class LandingContent
{
    public const CACHE_KEY = 'landing_content_v2';

    public const DEFAULT_LOCALE = 'id';

    public const LOCALES = [
        'id' => 'Bahasa Indonesia',
        'en' => 'English',
    ];

    public const IMAGE_HELP = 'Opsional. JPG/PNG/WEBP/GIF maks. 2 MB. GIF animasi juga bisa.';

    /**
     * Ikon Bootstrap Icons yang sering dipakai (bantuan pilihan di form).
     */
    public const ICON_SUGGESTIONS = [
        'bi-qr-code', 'bi-box-seam', 'bi-arrow-left-right', 'bi-calendar2-check', 'bi-calendar3', 'bi-tools',
        'bi-wrench-adjustable-circle', 'bi-graph-up-arrow', 'bi-shield-lock', 'bi-shield-check', 'bi-file-earmark-spreadsheet',
        'bi-stars', 'bi-bag-check', 'bi-upc-scan', 'bi-person-badge', 'bi-cash-stack', 'bi-clipboard2-data', 'bi-people',
        'bi-buildings', 'bi-mortarboard', 'bi-hospital', 'bi-gear-wide-connected', 'bi-house-door', 'bi-megaphone',
        'bi-camera', 'bi-geo-alt', 'bi-clock-history', 'bi-bell', 'bi-truck', 'bi-activity', 'bi-door-open', 'bi-lightning-charge',
        'bi-hand-index-thumb', 'bi-check2-circle', 'bi-phone', 'bi-laptop', 'bi-printer', 'bi-graph-down-arrow',
    ];

    public function schema(): array
    {
        $head = fn (string $label) => [
            'kicker' => ['type' => 'text', 'label' => 'Label kecil di atas judul'],
            'title' => ['type' => 'text', 'label' => $label],
            'highlight' => ['type' => 'text', 'label' => 'Judul — bagian berwarna (opsional)'],
            'subtitle' => ['type' => 'textarea', 'label' => 'Deskripsi singkat'],
        ];

        return [
            'seo' => [
                'label' => 'SEO & Bagikan',
                'icon' => 'bi-search',
                'description' => 'Judul tab browser, deskripsi di Google, dan gambar saat link dibagikan (WhatsApp, LinkedIn).',
                'fields' => [
                    'title' => ['type' => 'text', 'label' => 'Judul halaman'],
                    'description' => ['type' => 'textarea', 'label' => 'Meta description'],
                    'og_image' => ['type' => 'image', 'label' => 'Gambar saat dibagikan', 'help' => 'Disarankan 1200×630 px.'],
                ],
            ],
            'hero' => [
                'label' => 'Hero (bagian paling atas)',
                'icon' => 'bi-window',
                'description' => 'Kesan pertama pengunjung.',
                'fields' => [
                    'badge' => ['type' => 'text', 'label' => 'Label di samping "Gratis {hari_trial} hari"'],
                    'title' => ['type' => 'text', 'label' => 'Judul baris 1'],
                    'highlight' => ['type' => 'text', 'label' => 'Judul baris 2 (berwarna)'],
                    'lead' => ['type' => 'textarea', 'label' => 'Paragraf pembuka'],
                    'secondary_cta' => ['type' => 'text', 'label' => 'Tombol kedua'],
                    'checks' => ['type' => 'lines', 'label' => 'Poin di bawah tombol (satu per baris)', 'max' => 4],
                    'image' => ['type' => 'image', 'label' => 'Gambar / GIF hero', 'help' => 'Opsional. Jika diisi, menggantikan ilustrasi dashboard bawaan. ' . self::IMAGE_HELP],
                ],
            ],
            'compare' => [
                'label' => 'Sebelum & Sesudah',
                'icon' => 'bi-layout-split',
                'description' => 'Judul bagian perbandingan (ilustrasinya bawaan sistem).',
                'fields' => $head('Judul baris 1') + [
                    'footer_title' => ['type' => 'text', 'label' => 'Judul kotak di bawah'],
                    'footer_text' => ['type' => 'text', 'label' => 'Teks kotak di bawah'],
                ],
            ],
            'features' => [
                'label' => 'Fitur',
                'icon' => 'bi-grid-3x3-gap',
                'description' => 'Kartu-kartu fitur.',
                'fields' => $head('Judul') + [
                    'items' => ['type' => 'list', 'label' => 'Kartu fitur', 'max' => 12, 'fields' => [
                        'icon' => ['type' => 'icon', 'label' => 'Ikon'],
                        'title' => ['type' => 'text', 'label' => 'Judul'],
                        'text' => ['type' => 'textarea', 'label' => 'Deskripsi'],
                    ]],
                ],
            ],
            'tour' => [
                'label' => 'Tur Produk',
                'icon' => 'bi-display',
                'description' => 'Lima tab tampilan aplikasi. Unggah screenshot/GIF untuk mengganti ilustrasi bawaan per tab.',
                'fields' => $head('Judul baris 1') + [
                    'shot_aset' => ['type' => 'image', 'label' => 'Tab "Daftar Aset"', 'help' => self::IMAGE_HELP],
                    'shot_detail' => ['type' => 'image', 'label' => 'Tab "Detail & QR"', 'help' => self::IMAGE_HELP],
                    'shot_jadwal' => ['type' => 'image', 'label' => 'Tab "Jadwal Maintenance"', 'help' => self::IMAGE_HELP],
                    'shot_request' => ['type' => 'image', 'label' => 'Tab "Request Tim"', 'help' => self::IMAGE_HELP],
                    'shot_kalender' => ['type' => 'image', 'label' => 'Tab "Kalender"', 'help' => self::IMAGE_HELP],
                ],
            ],
            'lifecycle' => [
                'label' => 'Alur Kerja Aset',
                'icon' => 'bi-diagram-3',
                'description' => 'Tahapan siklus hidup aset (bagian berlatar gelap).',
                'fields' => $head('Judul') + [
                    'steps' => ['type' => 'list', 'label' => 'Tahapan', 'max' => 8, 'fields' => [
                        'icon' => ['type' => 'icon', 'label' => 'Ikon'],
                        'title' => ['type' => 'text', 'label' => 'Judul'],
                        'text' => ['type' => 'text', 'label' => 'Keterangan'],
                    ]],
                ],
            ],
            'request' => [
                'label' => 'Alur Request Perbaikan',
                'icon' => 'bi-tools',
                'description' => 'Penjelasan langkah lapor kerusakan.',
                'fields' => $head('Judul') + [
                    'steps' => ['type' => 'list', 'label' => 'Langkah', 'max' => 6, 'fields' => [
                        'title' => ['type' => 'text', 'label' => 'Judul'],
                        'text' => ['type' => 'textarea', 'label' => 'Keterangan'],
                    ]],
                    'image' => ['type' => 'image', 'label' => 'Gambar / GIF', 'help' => 'Opsional. Menggantikan ilustrasi tiket. ' . self::IMAGE_HELP],
                ],
            ],
            'roles' => [
                'label' => 'Peran Tim',
                'icon' => 'bi-people',
                'description' => 'Tiga peran bawaan.',
                'fields' => $head('Judul') + [
                    'items' => ['type' => 'list', 'label' => 'Peran', 'max' => 4, 'fields' => [
                        'icon' => ['type' => 'icon', 'label' => 'Ikon'],
                        'title' => ['type' => 'text', 'label' => 'Nama peran'],
                        'sub' => ['type' => 'text', 'label' => 'Keterangan singkat'],
                        'points' => ['type' => 'lines', 'label' => 'Poin (satu per baris)'],
                    ]],
                    'note' => ['type' => 'text', 'label' => 'Catatan di bawah kartu'],
                ],
            ],
            'solutions' => [
                'label' => 'Solusi per Industri',
                'icon' => 'bi-buildings',
                'description' => 'Tab solusi untuk tiap jenis organisasi.',
                'fields' => $head('Judul') + [
                    'items' => ['type' => 'list', 'label' => 'Industri', 'max' => 8, 'fields' => [
                        'icon' => ['type' => 'icon', 'label' => 'Ikon tab'],
                        'label' => ['type' => 'text', 'label' => 'Nama tab'],
                        'title' => ['type' => 'text', 'label' => 'Judul'],
                        'text' => ['type' => 'textarea', 'label' => 'Deskripsi'],
                        'checks' => ['type' => 'lines', 'label' => 'Poin utama (satu per baris)'],
                        'features' => ['type' => 'lines', 'label' => 'Daftar fitur kanan — format "ikon | teks" per baris'],
                    ]],
                ],
            ],
            'mobile' => [
                'label' => 'Aplikasi Mobile',
                'icon' => 'bi-phone',
                'description' => 'Banner aplikasi Android & iOS.',
                'fields' => [
                    'badge' => ['type' => 'text', 'label' => 'Label'],
                    'title' => ['type' => 'text', 'label' => 'Judul'],
                    'text' => ['type' => 'textarea', 'label' => 'Deskripsi'],
                    'points' => ['type' => 'lines', 'label' => 'Poin — format "ikon | teks" per baris'],
                    'play_store_url' => ['type' => 'text', 'label' => 'Link Google Play (kosongkan bila belum rilis)'],
                    'app_store_url' => ['type' => 'text', 'label' => 'Link App Store (kosongkan bila belum rilis)'],
                    'image' => ['type' => 'image', 'label' => 'Gambar / GIF aplikasi', 'help' => 'Opsional. Menggantikan ilustrasi HP. ' . self::IMAGE_HELP],
                ],
            ],
            'pricing' => [
                'label' => 'Harga — teks',
                'icon' => 'bi-tags',
                'description' => 'Teks bagian harga. Angka harga & kuota diatur di menu "Paket & Harga".',
                'fields' => $head('Judul') + [
                    'steps' => ['type' => 'list', 'label' => 'Langkah coba gratis', 'max' => 4, 'fields' => [
                        'title' => ['type' => 'text', 'label' => 'Judul'],
                        'text' => ['type' => 'text', 'label' => 'Keterangan'],
                    ]],
                    'plan_features' => ['type' => 'lines', 'label' => 'Fitur yang tampil di semua kartu paket (satu per baris)'],
                    'notes' => ['type' => 'list', 'label' => 'Kotak catatan di bawah harga', 'max' => 3, 'fields' => [
                        'icon' => ['type' => 'icon', 'label' => 'Ikon'],
                        'title' => ['type' => 'text', 'label' => 'Judul'],
                        'text' => ['type' => 'textarea', 'label' => 'Keterangan'],
                    ]],
                ],
            ],
            'faq' => [
                'label' => 'FAQ',
                'icon' => 'bi-question-circle',
                'description' => 'Pertanyaan yang sering diajukan.',
                'fields' => $head('Judul') + [
                    'items' => ['type' => 'list', 'label' => 'Pertanyaan', 'max' => 15, 'fields' => [
                        'q' => ['type' => 'text', 'label' => 'Pertanyaan'],
                        'a' => ['type' => 'textarea', 'label' => 'Jawaban'],
                    ]],
                ],
            ],
            'cta' => [
                'label' => 'Ajakan Penutup',
                'icon' => 'bi-megaphone',
                'description' => 'Kotak ajakan sebelum footer.',
                'fields' => [
                    'title' => ['type' => 'text', 'label' => 'Judul'],
                    'text' => ['type' => 'textarea', 'label' => 'Teks'],
                ],
            ],
            'footer' => [
                'label' => 'Footer & Kontak',
                'icon' => 'bi-telephone',
                'description' => 'Info perusahaan dan kontak yang tampil di footer.',
                'fields' => [
                    'tagline' => ['type' => 'textarea', 'label' => 'Deskripsi singkat'],
                    'company' => ['type' => 'text', 'label' => 'Nama perusahaan'],
                    'email' => ['type' => 'text', 'label' => 'Email kontak'],
                    'whatsapp' => ['type' => 'text', 'label' => 'Nomor WhatsApp (mis. 6281234567890)'],
                    'address' => ['type' => 'textarea', 'label' => 'Alamat'],
                ],
            ],
        ];
    }

    public function defaults(string $locale = self::DEFAULT_LOCALE): array
    {
        return $locale === 'en' ? LandingContentEn::defaults() : $this->defaultsId();
    }

    /**
     * Teks bawaan bahasa Indonesia.
     */
    protected function defaultsId(): array
    {
        return [
            'seo' => [
                'title' => 'Vasetra — Asset Lifecycle Platform | Kelola Aset Perusahaan dalam Satu Platform',
                'description' => 'Vasetra membantu perusahaan mencatat aset, menugaskan ke karyawan, menjadwalkan maintenance, dan menangani request perbaikan dalam satu platform. Coba gratis {hari_trial} hari tanpa kartu kredit.',
                'og_image' => null,
            ],
            'hero' => [
                'badge' => 'Tanpa kartu kredit · Langsung aktif',
                'title' => 'Semua aset perusahaan,',
                'highlight' => 'terkendali dalam satu platform.',
                'lead' => 'Vasetra mencatat setiap aset dari pembelian, penugasan ke karyawan, jadwal maintenance, hingga perbaikan — lengkap dengan label QR, notifikasi ke tim teknisi, dan jejak audit yang rapi.',
                'secondary_cta' => 'Lihat alur kerja',
                'checks' => ['Semua fitur terbuka saat masa coba', 'Upgrade kapan saja', 'Import data dari Excel'],
                'image' => null,
            ],
            'compare' => [
                'kicker' => 'Sebelum & Sesudah',
                'title' => 'Data aset tercecer,',
                'highlight' => 'tim kewalahan.',
                'subtitle' => 'File Excel beda versi, laporan kerusakan tenggelam di grup chat, jadwal servis terlupa. Geser garis di tengah untuk melihat bedanya.',
                'footer_title' => 'Lebih sedikit mencari, lebih banyak bekerja.',
                'footer_text' => 'Aset, maintenance, dan laporan kerusakan terhubung di satu tempat.',
            ],
            'features' => [
                'kicker' => 'Fitur',
                'title' => 'Satu tempat untuk seluruh siklus hidup aset',
                'highlight' => '',
                'subtitle' => 'Tinggalkan spreadsheet yang tercecer dan grup chat untuk lapor kerusakan. Semua tercatat, terjadwal, dan bisa ditelusuri.',
                'items' => [
                    ['icon' => 'bi-qr-code', 'title' => 'Registrasi aset & label QR', 'text' => 'Kode aset otomatis, foto, invoice, garansi, lokasi, dan vendor. Cetak label QR, cukup scan untuk melihat detail aset.'],
                    ['icon' => 'bi-arrow-left-right', 'title' => 'Penugasan & serah terima', 'text' => 'Serahkan aset ke karyawan, pindahkan, atau tarik kembali — setiap perpindahan tercatat lengkap dengan alasannya.'],
                    ['icon' => 'bi-calendar2-check', 'title' => 'Maintenance terjadwal', 'text' => 'Atur interval perawatan per aset. Jadwal terlambat, hari ini, dan minggu ini langsung terlihat di beranda & kalender.'],
                    ['icon' => 'bi-tools', 'title' => 'Request & perbaikan', 'text' => 'Karyawan melapor, teknisi mengambil, progres dicatat dengan foto. Pemohon mendapat notifikasi di setiap tahap.'],
                    ['icon' => 'bi-graph-up-arrow', 'title' => 'Nilai & penyusutan', 'text' => 'Hitung penyusutan garis lurus dan pantau nilai buku serta biaya maintenance setiap aset.'],
                    ['icon' => 'bi-calendar3', 'title' => 'Kalender & meeting', 'text' => 'Agenda maintenance, request, dan meeting tim dalam satu kalender. Undangan meeting terkirim lewat email.'],
                    ['icon' => 'bi-shield-lock', 'title' => 'Hak akses & audit trail', 'text' => 'Atur hak akses per peran. Setiap perubahan, login, dan ekspor data tercatat: siapa, kapan, dan apa yang berubah.'],
                    ['icon' => 'bi-file-earmark-spreadsheet', 'title' => 'Import & export Excel', 'text' => 'Pindahkan data aset dan user lama sekaligus lewat Excel, lengkap dengan pratinjau dan laporan baris yang gagal.'],
                    ['icon' => 'bi-stars', 'title' => 'Vasetra AI', 'text' => 'Tanyakan data aset dengan bahasa sehari-hari, misalnya "aset apa saja yang maintenance-nya terlambat?"'],
                ],
            ],
            'tour' => [
                'kicker' => 'Tur Produk',
                'title' => 'Lebih sedikit mencatat ulang.',
                'highlight' => 'Lebih banyak yang selesai.',
                'subtitle' => 'Ini tampilan yang dipakai tim Anda setiap hari — rapi, cepat, dan mudah dipahami tanpa pelatihan panjang.',
                'shot_aset' => null, 'shot_detail' => null, 'shot_jadwal' => null, 'shot_request' => null, 'shot_kalender' => null,
            ],
            'lifecycle' => [
                'kicker' => 'Alur Kerja',
                'title' => 'Dari aset dibeli sampai diaudit, semuanya tercatat',
                'highlight' => '',
                'subtitle' => 'Setiap tahap saling terhubung, jadi riwayat aset selalu lengkap tanpa input ulang.',
                'steps' => [
                    ['icon' => 'bi-bag-check', 'title' => 'Pengadaan', 'text' => 'Catat pembelian, vendor, harga, invoice, dan garansi.'],
                    ['icon' => 'bi-upc-scan', 'title' => 'Registrasi', 'text' => 'Kode aset otomatis dan label QR siap cetak.'],
                    ['icon' => 'bi-person-badge', 'title' => 'Penugasan', 'text' => 'Serahkan ke karyawan, pindah tangan, atau kembalikan.'],
                    ['icon' => 'bi-wrench-adjustable-circle', 'title' => 'Pemeliharaan', 'text' => 'Jadwal rutin dan request perbaikan oleh teknisi.'],
                    ['icon' => 'bi-cash-stack', 'title' => 'Evaluasi', 'text' => 'Nilai buku, penyusutan, dan total biaya perawatan.'],
                    ['icon' => 'bi-clipboard2-data', 'title' => 'Audit & laporan', 'text' => 'Jejak lengkap setiap perubahan, siap diekspor.'],
                ],
            ],
            'request' => [
                'kicker' => 'Request Perbaikan',
                'title' => 'Lapor kerusakan tanpa grup chat yang berantakan',
                'highlight' => '',
                'subtitle' => 'Setiap laporan langsung sampai ke tim teknisi, dikerjakan oleh satu orang, dan pemohon tahu perkembangannya tanpa harus bertanya.',
                'steps' => [
                    ['title' => 'Karyawan membuat request', 'text' => 'Pilih aset yang dipegang, tulis keluhannya. Seluruh tim teknisi menerima notifikasi.'],
                    ['title' => 'Teknisi mengambil', 'text' => 'Siapa cepat dia dapat. Request langsung hilang dari antrean teknisi lain dan tertulis "dikerjakan oleh Budi".'],
                    ['title' => 'Progres dengan foto', 'text' => 'Setiap perkembangan dicatat beserta foto, dan pemohon diberi tahu.'],
                    ['title' => 'Selesai & masuk riwayat', 'text' => 'Tindakan, biaya, dan foto hasil tersimpan di riwayat maintenance aset.'],
                ],
                'image' => null,
            ],
            'roles' => [
                'kicker' => 'Peran Tim',
                'title' => 'Setiap orang melihat yang perlu saja',
                'highlight' => '',
                'subtitle' => 'Tiga peran bawaan sudah siap pakai sejak hari pertama. Tidak perlu mengatur hak akses dari nol.',
                'items' => [
                    ['icon' => 'bi-person-gear', 'title' => 'Administrator', 'sub' => 'Pengelola aplikasi', 'points' => ['Kelola aset, kategori, vendor, dan user', 'Atur hak akses setiap peran', 'Pantau kuota, langganan, dan audit trail']],
                    ['icon' => 'bi-tools', 'title' => 'Teknisi', 'sub' => 'Tim maintenance', 'points' => ['Menerima notifikasi request baru', 'Mengambil & mengerjakan perbaikan', 'Melihat jadwal & riwayat maintenance']],
                    ['icon' => 'bi-person-badge', 'title' => 'Karyawan', 'sub' => 'Pemegang aset', 'points' => ['Melihat aset yang menjadi tanggung jawabnya', 'Melaporkan kerusakan atau minta servis', 'Memantau status request miliknya']],
                ],
                'note' => 'Butuh peran lain, misalnya Staf Gudang? Buat role sendiri dan pilih hak aksesnya.',
            ],
            'solutions' => [
                'kicker' => 'Solusi',
                'title' => 'Dibuat untuk cara kerja',
                'highlight' => 'organisasi Anda.',
                'subtitle' => 'Mulai dari yang Anda butuhkan hari ini, dan tetap cocok saat organisasi bertumbuh.',
                'items' => [
                    ['icon' => 'bi-buildings', 'label' => 'Kantor & Korporasi', 'title' => 'Laptop & inventaris kantor terkendali',
                        'text' => 'Perangkat berpindah tangan setiap kali ada karyawan baru, mutasi, atau resign. Vasetra memastikan setiap perangkat jelas pemegangnya.',
                        'checks' => ['Serah terima laptop saat onboarding & resign', 'Tahu siapa memegang perangkat apa', 'Servis AC & printer terjadwal'],
                        'features' => ['bi-arrow-left-right | Penugasan & pengembalian aset', 'bi-qr-code | Label QR di setiap perangkat', 'bi-megaphone | Karyawan lapor kerusakan sendiri', 'bi-file-earmark-spreadsheet | Export data untuk tim finance']],
                    ['icon' => 'bi-mortarboard', 'label' => 'Sekolah & Kampus', 'title' => 'Fasilitas kelas & lab selalu siap pakai',
                        'text' => 'Proyektor, komputer lab, dan perlengkapan kelas tersebar di banyak ruangan. Catat per lokasi dan rawat sebelum rusak.',
                        'checks' => ['Inventaris per ruang kelas & laboratorium', 'Jadwal servis proyektor & komputer lab', 'Laporan kerusakan langsung dari guru'],
                        'features' => ['bi-geo-alt | Lokasi aset per ruangan', 'bi-calendar2-check | Pemeliharaan berkala', 'bi-clock-history | Riwayat perbaikan tiap fasilitas', 'bi-clipboard2-data | Data inventaris siap untuk audit']],
                    ['icon' => 'bi-hospital', 'label' => 'Klinik & Rumah Sakit', 'title' => 'Alat medis terawat, jadwal kalibrasi tidak terlewat',
                        'text' => 'Peralatan medis wajib dirawat dan dikalibrasi tepat waktu. Simpan jadwal, vendor, garansi, dan riwayatnya dalam satu tempat.',
                        'checks' => ['Jadwal kalibrasi & inspeksi alat', 'Riwayat servis siap ditunjukkan saat audit', 'Garansi & vendor setiap alat tercatat'],
                        'features' => ['bi-activity | Interval perawatan per alat', 'bi-shield-check | Audit trail setiap perubahan', 'bi-truck | Data vendor & kontak servis', 'bi-camera | Foto bukti hasil perbaikan']],
                    ['icon' => 'bi-gear-wide-connected', 'label' => 'Pabrik & Gudang', 'title' => 'Mesin produksi terawat, downtime berkurang',
                        'text' => 'Kerusakan mesin yang terlambat ditangani berarti produksi berhenti. Rawat secara preventif dan percepat respons teknisi.',
                        'checks' => ['Preventive maintenance berbasis interval', 'Teknisi langsung diberi tahu saat ada kerusakan', 'Biaya perawatan per mesin terlihat jelas'],
                        'features' => ['bi-tools | Antrean request untuk tim teknisi', 'bi-hand-index-thumb | Satu request, satu penanggung jawab', 'bi-cash-stack | Total biaya maintenance per aset', 'bi-graph-down-arrow | Penyusutan & nilai buku mesin']],
                    ['icon' => 'bi-house-door', 'label' => 'Hotel & Properti', 'title' => 'Kamar & fasilitas tamu selalu prima',
                        'text' => 'Setiap kamar dan area punya puluhan aset. Staf cukup melapor, teknisi mengambil, dan tamu tidak sempat merasakan masalahnya.',
                        'checks' => ['Aset tercatat per kamar & area', 'Laporan kerusakan dari staf housekeeping', 'Perbaikan selesai dengan foto bukti'],
                        'features' => ['bi-door-open | Lokasi aset per kamar', 'bi-bell | Notifikasi ke teknisi yang bertugas', 'bi-camera | Progres & foto hasil perbaikan', 'bi-calendar3 | Jadwal perawatan rutin fasilitas']],
                ],
            ],
            'mobile' => [
                'badge' => 'Aplikasi Android & iOS segera hadir',
                'title' => 'Kelola aset langsung dari lapangan',
                'text' => 'Vasetra bisa diakses dari browser di laptop maupun HP. Aplikasi mobile sedang disiapkan agar tim bisa bekerja lebih cepat tanpa membuka laptop.',
                'points' => ['bi-qr-code-scan | Scan QR untuk detail aset', 'bi-megaphone | Lapor kerusakan dari HP', 'bi-hand-index-thumb | Teknisi ambil request', 'bi-camera | Foto progres & hasil'],
                'play_store_url' => '',
                'app_store_url' => '',
                'image' => null,
            ],
            'pricing' => [
                'kicker' => 'Harga',
                'title' => 'Coba gratis dulu, bayar setelah yakin',
                'highlight' => '',
                'subtitle' => 'Mulai dengan masa coba {hari_trial} hari tanpa kartu kredit. Pilih paket kapan saja — bahkan di hari pertama.',
                'steps' => [
                    ['title' => 'Daftar dalam hitungan menit', 'text' => 'Isi nama perusahaan & akun admin, verifikasi email.'],
                    ['title' => 'Pakai gratis {hari_trial} hari', 'text' => 'Coba semua fitur bersama tim Anda.'],
                    ['title' => 'Upgrade kapan saja', 'text' => 'Paket baru langsung aktif, data trial tetap tersimpan.'],
                ],
                // Diatur dari halaman "Paket & Harga". Kosong = paket berbayar kedua.
                'recommended_plan' => '',
                'plan_features' => ['QR, penugasan & maintenance', 'Request perbaikan & notifikasi tim', 'Kalender, audit trail & Vasetra AI', 'Import & export Excel'],
                'notes' => [
                    ['icon' => 'bi-lightning-charge-fill', 'title' => 'Upgrade langsung aktif', 'text' => 'Masih masa coba lalu ingin paket bisnis besok? Bisa — paket baru aktif saat itu juga.'],
                    ['icon' => 'bi-plus-square-fill', 'title' => 'Tambah storage fleksibel', 'text' => 'Ruang penyimpanan kurang? Tambah storage tanpa ganti paket. Butuh lebih banyak user atau aset? Cukup naik paket.'],
                    ['icon' => 'bi-credit-card-2-front-fill', 'title' => 'Pembayaran aman', 'text' => 'Diproses lewat payment gateway DOKU. Harga dalam Rupiah.'],
                ],
            ],
            'faq' => [
                'kicker' => 'FAQ',
                'title' => 'Pertanyaan yang sering diajukan',
                'highlight' => '',
                'subtitle' => '',
                'items' => [
                    ['q' => 'Apakah masa coba benar-benar gratis?', 'a' => 'Ya. Anda bisa memakai Vasetra gratis selama {hari_trial} hari tanpa memasukkan kartu kredit atau data pembayaran. Tidak ada tagihan otomatis — Anda sendiri yang memilih paket saat siap.'],
                    ['q' => 'Bisakah upgrade sebelum masa coba berakhir?', 'a' => 'Bisa, kapan saja dari menu Langganan di dashboard. Begitu pembayaran berhasil, masa coba ditutup dan paket baru langsung aktif dengan kuota yang lebih besar.'],
                    ['q' => 'Apa yang terjadi pada data saat masa coba habis?', 'a' => 'Data Anda tetap tersimpan. Akses fitur dibatasi sampai Anda memilih paket, dan setelah berlangganan semuanya bisa langsung dipakai kembali tanpa input ulang.'],
                    ['q' => 'Apakah data perusahaan saya terpisah dari perusahaan lain?', 'a' => 'Ya. Setiap perusahaan memiliki ruang data sendiri, akses dibatasi sesuai peran, dan setiap perubahan penting tercatat di audit trail yang hanya bisa dilihat Administrator.'],
                    ['q' => 'Data aset kami masih di Excel. Bisa dipindahkan?', 'a' => 'Bisa. Unduh template, isi data aset atau user, lalu upload. Vasetra menampilkan pratinjau dan memberi tahu baris yang perlu diperbaiki sebelum disimpan.'],
                    ['q' => 'Bagaimana cara pembayarannya?', 'a' => 'Pembayaran dilakukan dari dashboard melalui payment gateway DOKU, dengan pilihan metode pembayaran yang tersedia di halaman checkout DOKU. Riwayat dan bukti pembayaran tersimpan di menu Langganan.'],
                ],
            ],
            'cta' => [
                'title' => 'Mulai kelola aset dengan lebih rapi hari ini',
                'text' => 'Daftar sekarang, ajak tim Anda, dan rasakan bedanya dalam {hari_trial} hari — gratis.',
            ],
            'footer' => [
                'tagline' => 'Platform pengelolaan siklus hidup aset untuk perusahaan di Indonesia — dari pengadaan hingga audit.',
                'company' => 'PT Arunika Solusi Inovasi',
                'email' => '',
                'whatsapp' => '',
                'address' => '',
            ],
        ];
    }

    public static function normalizeLocale(?string $locale): string
    {
        return array_key_exists((string) $locale, self::LOCALES) ? (string) $locale : self::DEFAULT_LOCALE;
    }

    /**
     * Nama field bertipe gambar per bagian. Gambar dipakai bersama semua bahasa
     * dan selalu disimpan di baris bahasa utama (id).
     *
     * @return array<string, string[]>
     */
    public function imageFields(): array
    {
        return collect($this->schema())
            ->map(fn ($s) => collect($s['fields'])->filter(fn ($f) => $f['type'] === 'image')->keys()->all())
            ->filter()
            ->all();
    }

    /**
     * Konten final (bawaan + isi CMS) untuk satu bahasa, token {hari_trial} sudah diganti.
     */
    public function all(int $trialDays = 7, string $locale = self::DEFAULT_LOCALE): array
    {
        $locale = self::normalizeLocale($locale);

        $stored = Cache::rememberForever(self::CACHE_KEY, function () {
            try {
                if (!Schema::hasTable('site_contents')) {
                    return [];
                }

                $rows = [];
                foreach (SiteContent::query()->get(['key', 'locale', 'value']) as $row) {
                    $rows[$row->locale][$row->key] = $row->value;
                }

                return $rows;
            } catch (Throwable $e) {
                report($e);

                return [];
            }
        });

        $images = $this->imageFields();
        $content = [];

        foreach ($this->defaults($locale) as $key => $default) {
            $saved = is_array($stored[$locale][$key] ?? null) ? $stored[$locale][$key] : [];
            $content[$key] = array_merge($default, array_intersect_key($saved, $default));

            // Gambar: selalu dari bahasa utama.
            foreach ($images[$key] ?? [] as $field) {
                $content[$key][$field] = $stored[self::DEFAULT_LOCALE][$key][$field] ?? null;
            }
        }

        return $this->replaceTokens($content, ['{hari_trial}' => (string) $trialDays]);
    }

    /**
     * Konten satu bagian apa adanya (tanpa penggantian token) untuk form CMS.
     */
    public function section(string $key, string $locale = self::DEFAULT_LOCALE): array
    {
        $locale = self::normalizeLocale($locale);
        $default = $this->defaults($locale)[$key] ?? [];
        $saved = SiteContent::query()->where('key', $key)->where('locale', $locale)->value('value');
        $value = array_merge($default, array_intersect_key(is_array($saved) ? $saved : [], $default));

        if ($locale !== self::DEFAULT_LOCALE) {
            $base = SiteContent::query()->where('key', $key)->where('locale', self::DEFAULT_LOCALE)->value('value');
            foreach ($this->imageFields()[$key] ?? [] as $field) {
                $value[$field] = is_array($base) ? ($base[$field] ?? null) : null;
            }
        }

        return $value;
    }

    public function isCustomized(string $key, string $locale = self::DEFAULT_LOCALE): bool
    {
        return SiteContent::query()->where('key', $key)->where('locale', self::normalizeLocale($locale))->exists();
    }

    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Pecah baris "ikon | teks" menjadi [ikon, teks].
     *
     * @return array{0:string,1:string}
     */
    public static function iconLine(string $line, string $fallbackIcon = 'bi-check2-circle'): array
    {
        $parts = array_map('trim', explode('|', $line, 2));

        if (count($parts) === 2 && str_starts_with($parts[0], 'bi-')) {
            return [$parts[0], $parts[1]];
        }

        return [$fallbackIcon, trim($line)];
    }

    public static function imageUrl(?string $path): ?string
    {
        return $path ? asset('storage/' . ltrim($path, '/')) : null;
    }

    protected function replaceTokens(mixed $value, array $tokens): mixed
    {
        if (is_array($value)) {
            return array_map(fn ($v) => $this->replaceTokens($v, $tokens), $value);
        }

        return is_string($value) ? strtr($value, $tokens) : $value;
    }
}
