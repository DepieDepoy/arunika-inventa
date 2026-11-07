<?php

namespace App\Services\Landing;

/**
 * Teks bawaan Kebijakan Privasi & Syarat Layanan (bisa diubah lewat CMS backoffice),
 * serta pengubah format sederhana (## judul, - poin, **tebal**) menjadi HTML aman.
 *
 * Catatan: teks ini titik awal yang wajar untuk SaaS di Indonesia (UU No. 27/2022
 * tentang Pelindungan Data Pribadi). Tetap minta tinjauan konsultan hukum.
 */
class LegalContent
{
    public static function schema(): array
    {
        $fields = [
            'title' => ['type' => 'text', 'label' => 'Judul halaman'],
            'updated' => ['type' => 'text', 'label' => 'Tanggal berlaku', 'help' => 'Contoh: 7 November 2026'],
            'intro' => ['type' => 'textarea', 'label' => 'Pengantar'],
            'body' => ['type' => 'longtext', 'label' => 'Isi'],
        ];

        return [
            'privacy' => [
                'label' => 'Kebijakan Privasi',
                'icon' => 'bi-shield-check',
                'description' => 'Halaman /kebijakan-privasi — wajib untuk Play Store & App Store.',
                'fields' => $fields,
            ],
            'terms' => [
                'label' => 'Syarat Layanan',
                'icon' => 'bi-file-earmark-text',
                'description' => 'Halaman /syarat-layanan — aturan pemakaian Vasetra.',
                'fields' => $fields,
            ],
        ];
    }

    public static function defaults(string $locale): array
    {
        return $locale === 'en' ? self::en() : self::id();
    }

    /**
     * Ubah teks berformat sederhana menjadi HTML. Semua teks di-escape lebih dulu.
     */
    public static function render(string $text): string
    {
        $html = '';
        $para = [];
        $list = [];

        $inline = function (string $s): string {
            $s = e($s);
            $s = preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', $s);

            return preg_replace('/\b([A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,})\b/', '<a href="mailto:$1">$1</a>', $s);
        };
        $flushPara = function () use (&$para, &$html, $inline) {
            if ($para) {
                $html .= '<p>' . $inline(implode(' ', $para)) . '</p>';
                $para = [];
            }
        };
        $flushList = function () use (&$list, &$html, $inline) {
            if ($list) {
                $html .= '<ul>' . implode('', array_map(fn ($li) => '<li>' . $inline($li) . '</li>', $list)) . '</ul>';
                $list = [];
            }
        };

        foreach (preg_split('/\R/u', $text) as $line) {
            $line = rtrim($line);
            if ($line === '') {
                $flushPara();
                $flushList();
            } elseif (str_starts_with($line, '## ')) {
                $flushPara();
                $flushList();
                $html .= '<h2>' . $inline(substr($line, 3)) . '</h2>';
            } elseif (preg_match('/^\s*[-•]\s+(.*)$/u', $line, $m)) {
                $flushPara();
                $list[] = $m[1];
            } else {
                $flushList();
                $para[] = trim($line);
            }
        }
        $flushPara();
        $flushList();

        return $html;
    }

    /* ------------------------------------------------------------------ */

    private static function id(): array
    {
        return [
            'privacy' => [
                'title' => 'Kebijakan Privasi',
                'updated' => '7 November 2026',
                'intro' => 'Kebijakan ini menjelaskan data apa yang kami kumpulkan saat Anda memakai Vasetra (website dan aplikasi mobile), untuk apa data itu dipakai, dan hak Anda atas data tersebut, sesuai Undang-Undang No. 27 Tahun 2022 tentang Pelindungan Data Pribadi.',
                'body' => <<<'TXT'
## 1. Siapa kami
Vasetra adalah platform pengelolaan siklus hidup aset yang dioperasikan oleh **PT Arunika Solusi Inovasi** ("kami"). Untuk pertanyaan terkait privasi, hubungi info@vasetra.com.

## 2. Peran kami atas data
- Untuk data akun pendaftar, pembayaran, dan kunjungan website, kami bertindak sebagai **pengendali data**.
- Untuk data yang dimasukkan perusahaan pelanggan ke dalam Vasetra (data aset, karyawan, maintenance, foto, dan dokumen), **perusahaan pelanggan adalah pengendali data** dan kami bertindak sebagai **prosesor** yang mengolah data sesuai instruksi pelanggan.

## 3. Data yang kami kumpulkan
- **Data akun:** nama, email, nomor WhatsApp, ID/NIK karyawan, nama perusahaan, dan password (disimpan dalam bentuk terenkripsi/hash).
- **Data operasional pelanggan:** data aset, kategori, vendor, penugasan, jadwal dan riwayat maintenance, request perbaikan, foto, dan dokumen yang diunggah.
- **Data perangkat & penggunaan:** alamat IP, jenis perangkat, sistem operasi, browser, waktu login, dan halaman yang dibuka, untuk keamanan dan statistik.
- **Token notifikasi:** token perangkat (aplikasi mobile) dan langganan notifikasi browser, hanya bila Anda mengizinkan notifikasi.
- **Data pembayaran:** diproses oleh mitra pembayaran (DOKU). Kami **tidak** menyimpan nomor kartu atau data rekening Anda.

## 4. Tujuan penggunaan data
- Menyediakan layanan Vasetra, termasuk login, pengelolaan aset, notifikasi, dan laporan.
- Mengelola langganan, tagihan, dan dukungan pelanggan.
- Menjaga keamanan akun, misalnya mendeteksi login mencurigakan dan mencatat jejak audit.
- Mengukur dan meningkatkan layanan dari data statistik agregat.
- Memenuhi kewajiban hukum.

Kami **tidak menjual** data pribadi Anda dan tidak memakai data operasional pelanggan untuk iklan.

## 5. Cookie
Kami memakai cookie yang diperlukan agar layanan berjalan (sesi login dan keamanan) serta cookie analitik pihak pertama untuk menghitung kunjungan website. Kami tidak memakai cookie iklan pihak ketiga.

## 6. Pihak yang membantu kami
Data dapat diproses oleh penyedia layanan yang membantu kami, terbatas sesuai keperluan:
- penyedia server/hosting,
- layanan pengiriman email,
- mitra pembayaran (DOKU),
- layanan notifikasi (Google Firebase dan layanan push browser).

Semua pihak tersebut wajib menjaga kerahasiaan data.

## 7. Penyimpanan & retensi
- Data akun dan data operasional disimpan selama langganan aktif.
- Alamat IP pengunjung website dihapus otomatis setelah **60 hari**.
- Setelah akun atau perusahaan dihapus, data dihapus atau dianonimkan paling lambat **30 hari**, kecuali yang wajib disimpan menurut hukum (misalnya catatan transaksi). Cadangan (backup) terhapus mengikuti siklus cadangan, paling lama 90 hari.

## 8. Keamanan
Kami memakai koneksi terenkripsi (HTTPS), password ter-hash, pembatasan percobaan login, pemisahan data antarperusahaan, hak akses berbasis peran, dan jejak audit. Tidak ada sistem yang 100% aman. Bila terjadi kegagalan pelindungan data, kami akan memberi tahu sesuai ketentuan hukum.

## 9. Hak Anda
Anda berhak:
- mengakses dan memperbaiki data pribadi,
- meminta salinan data,
- menarik persetujuan,
- meminta penghapusan akun.

Akun dapat dihapus dari menu **Profil** di website, dari aplikasi mobile, atau lewat halaman **Hapus Akun**. Untuk data karyawan yang dikelola perusahaan Anda, ajukan permintaan melalui administrator perusahaan.

## 10. Anak-anak
Vasetra ditujukan untuk perusahaan dan tidak ditujukan bagi anak di bawah 17 tahun.

## 11. Perubahan kebijakan
Kami dapat memperbarui kebijakan ini. Perubahan penting akan kami umumkan melalui website atau email.

## 12. Kontak
PT Arunika Solusi Inovasi · info@vasetra.com
TXT,
            ],
            'terms' => [
                'title' => 'Syarat Layanan',
                'updated' => '7 November 2026',
                'intro' => 'Dengan mendaftar atau memakai Vasetra, Anda menyetujui syarat berikut. Mohon dibaca dengan saksama.',
                'body' => <<<'TXT'
## 1. Layanan
Vasetra adalah perangkat lunak berbasis langganan (SaaS) untuk mencatat aset, menugaskan aset ke karyawan, menjadwalkan maintenance, menangani request perbaikan, dan membuat laporan, melalui website dan aplikasi mobile. Layanan dioperasikan oleh PT Arunika Solusi Inovasi.

## 2. Akun
- Pendaftar menyatakan berwenang mewakili perusahaannya.
- Anda bertanggung jawab menjaga kerahasiaan password dan semua aktivitas di akun Anda.
- Administrator perusahaan mengatur user dan hak akses di perusahaannya.

## 3. Masa coba & langganan
- Akun baru mendapat masa coba gratis sesuai yang tercantum di website, tanpa kartu kredit.
- Setelah masa coba atau masa langganan berakhir, akses dibatasi sampai langganan diperpanjang. Data tidak langsung dihapus.
- Harga, kuota (aset, user, storage), dan add-on mengikuti paket yang tertera saat pembelian.
- Pembayaran diproses melalui mitra pembayaran resmi. Biaya yang sudah dibayar tidak dapat dikembalikan, kecuali diatur lain secara tertulis.

## 4. Data pelanggan
- Data yang Anda masukkan tetap **milik perusahaan Anda**.
- Kami hanya memproses data tersebut untuk menyediakan layanan, sesuai Kebijakan Privasi.
- Anda dapat mengekspor data Anda (misalnya ke Excel) selama langganan aktif.

## 5. Penggunaan yang dilarang
- Melanggar hukum atau hak pihak lain.
- Mengunggah konten berbahaya (virus/malware) atau konten ilegal.
- Mencoba membobol, membebani, atau mengakses data perusahaan lain.
- Menjual kembali layanan tanpa persetujuan tertulis.

## 6. Ketersediaan layanan
Kami berupaya menjaga layanan tersedia setiap saat, namun bisa ada pemeliharaan terjadwal atau gangguan di luar kendali kami. Kami tidak menjamin layanan bebas gangguan sepenuhnya.

## 7. Penangguhan & pengakhiran
- Kami dapat menangguhkan akun yang melanggar syarat ini atau menunggak pembayaran.
- Anda dapat berhenti berlangganan atau meminta penghapusan akun kapan saja.

## 8. Batasan tanggung jawab
Sejauh diizinkan hukum, kami tidak bertanggung jawab atas kerugian tidak langsung, termasuk kehilangan keuntungan, yang timbul dari penggunaan layanan. Tanggung jawab kami paling banyak sebesar biaya langganan yang Anda bayar dalam 3 bulan terakhir.

## 9. Hak kekayaan intelektual
Perangkat lunak, merek, dan tampilan Vasetra adalah milik PT Arunika Solusi Inovasi. Anda mendapat hak pakai selama berlangganan.

## 10. Perubahan syarat
Kami dapat memperbarui syarat ini. Penggunaan layanan setelah perubahan berarti Anda menyetujui syarat yang baru.

## 11. Hukum yang berlaku
Syarat ini tunduk pada hukum Republik Indonesia. Perselisihan diselesaikan secara musyawarah terlebih dahulu.

## 12. Kontak
info@vasetra.com
TXT,
            ],
        ];
    }

    private static function en(): array
    {
        return [
            'privacy' => [
                'title' => 'Privacy Policy',
                'updated' => 'November 7, 2026',
                'intro' => 'This policy explains what data we collect when you use Vasetra (website and mobile app), how we use it, and your rights, in line with Indonesia\'s Personal Data Protection Law (Law No. 27 of 2022).',
                'body' => <<<'TXT'
## 1. Who we are
Vasetra is an asset lifecycle management platform operated by **PT Arunika Solusi Inovasi** ("we"). For privacy questions, contact info@vasetra.com.

## 2. Our role
- For sign-up account data, payments, and website visits, we act as the **data controller**.
- For data that customer companies enter into Vasetra (assets, employees, maintenance, photos, and documents), **the customer company is the data controller** and we act as a **processor** on its instructions.

## 3. Data we collect
- **Account data:** name, email, WhatsApp number, employee ID, company name, and password (stored hashed).
- **Customer operational data:** assets, categories, vendors, assignments, maintenance schedules and history, repair requests, photos, and uploaded documents.
- **Device & usage data:** IP address, device type, operating system, browser, login times, and pages visited, for security and statistics.
- **Notification tokens:** mobile device tokens and browser notification subscriptions, only if you allow notifications.
- **Payment data:** processed by our payment partner (DOKU). We do **not** store your card or bank account details.

## 4. How we use data
- To provide Vasetra, including sign-in, asset management, notifications, and reports.
- To manage subscriptions, billing, and customer support.
- To keep accounts secure, for example by detecting suspicious sign-ins and keeping audit trails.
- To measure and improve the service using aggregated statistics.
- To meet legal obligations.

We do **not sell** personal data and do not use customer operational data for advertising.

## 5. Cookies
We use cookies required to run the service (login session and security) and first-party analytics cookies to count website visits. We do not use third-party advertising cookies.

## 6. Service providers
Data may be processed by providers that help us, limited to what is needed:
- hosting/servers,
- email delivery,
- payment partner (DOKU),
- notification services (Google Firebase and browser push services).

All of them must keep the data confidential.

## 7. Retention
- Account and operational data are kept while the subscription is active.
- Website visitor IP addresses are deleted automatically after **60 days**.
- After an account or company is deleted, data is deleted or anonymised within **30 days**, except records we must keep by law (such as transaction records). Backups expire within 90 days.

## 8. Security
We use encrypted connections (HTTPS), hashed passwords, login rate limiting, per-company data isolation, role-based access, and audit trails. No system is 100% secure. If a personal data breach occurs, we will notify you as required by law.

## 9. Your rights
You have the right to:
- access and correct your personal data,
- request a copy of your data,
- withdraw consent,
- request account deletion.

You can delete your account from **Profile** on the website, from the mobile app, or via the **Delete Account** page. For employee data managed by your company, contact your company administrator.

## 10. Children
Vasetra is intended for businesses and is not directed at children under 17.

## 11. Changes
We may update this policy. Material changes will be announced on our website or by email.

## 12. Contact
PT Arunika Solusi Inovasi · info@vasetra.com
TXT,
            ],
            'terms' => [
                'title' => 'Terms of Service',
                'updated' => 'November 7, 2026',
                'intro' => 'By signing up for or using Vasetra, you agree to the following terms. Please read them carefully.',
                'body' => <<<'TXT'
## 1. The service
Vasetra is subscription software (SaaS) for recording assets, assigning them to employees, scheduling maintenance, handling repair requests, and producing reports, via the website and mobile app. It is operated by PT Arunika Solusi Inovasi.

## 2. Accounts
- Whoever signs up confirms they are authorised to act for their company.
- You are responsible for keeping your password confidential and for all activity on your account.
- Company administrators manage users and access rights in their company.

## 3. Trial & subscriptions
- New accounts get a free trial as stated on our website, with no credit card required.
- When a trial or subscription ends, access is limited until it is renewed. Data is not deleted immediately.
- Prices, quotas (assets, users, storage), and add-ons follow the plan shown at purchase.
- Payments are processed by an official payment partner. Fees paid are non-refundable unless agreed otherwise in writing.

## 4. Customer data
- The data you enter remains **your company's property**.
- We process it only to provide the service, as described in the Privacy Policy.
- You can export your data (for example to Excel) while your subscription is active.

## 5. Prohibited use
- Breaking the law or infringing others' rights.
- Uploading malicious content (viruses/malware) or illegal content.
- Attempting to breach or overload the service, or to access other companies' data.
- Reselling the service without written consent.

## 6. Availability
We aim to keep the service available at all times, but there may be scheduled maintenance or outages beyond our control. We do not guarantee uninterrupted service.

## 7. Suspension & termination
- We may suspend accounts that breach these terms or have unpaid fees.
- You may stop your subscription or request account deletion at any time.

## 8. Limitation of liability
To the extent permitted by law, we are not liable for indirect losses, including lost profits, arising from use of the service. Our total liability is limited to the subscription fees you paid in the previous 3 months.

## 9. Intellectual property
The Vasetra software, brand, and design belong to PT Arunika Solusi Inovasi. You receive a right to use them while subscribed.

## 10. Changes
We may update these terms. Continuing to use the service after changes means you accept the updated terms.

## 11. Governing law
These terms are governed by the laws of the Republic of Indonesia. Disputes are first settled amicably.

## 12. Contact
info@vasetra.com
TXT,
            ],
        ];
    }
}
