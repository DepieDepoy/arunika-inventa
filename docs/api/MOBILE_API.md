# Vasetra Mobile API v1

Panduan integrasi aplikasi Flutter (Android & iOS) dengan backend Vasetra.

- **Base URL**: `{APP_URL}/api/v1`, contoh lokal `http://localhost/arunika-inventa/public/api/v1`
- **Format**: JSON (`Accept: application/json` wajib di setiap request)
- **Autentikasi**: Bearer token (Laravel Sanctum), header `Authorization: Bearer <token>`
- **Koleksi Postman**: [`Vasetra-Mobile-API.postman_collection.json`](Vasetra-Mobile-API.postman_collection.json)

---

## 1. Aturan umum

### Format respons

Sukses:

```json
{ "success": true, "message": "opsional", "data": { ... } }
```

Daftar ber-halaman menambahkan `pagination`:

```json
{
  "success": true,
  "data": { "assets": [ ... ] },
  "pagination": { "current_page": 1, "last_page": 3, "per_page": 20, "total": 47, "from": 1, "to": 20 }
}
```

Parameter `per_page` (1–100, default 20) dan `page` berlaku untuk semua endpoint ber-halaman.

Gagal:

```json
{ "success": false, "message": "Pesan untuk ditampilkan ke user", "code": "VALIDATION_ERROR", "errors": { "field": ["pesan"] } }
```

Tampilkan `message` ke user. Pakai `code` untuk logika aplikasi. `errors` hanya ada untuk kesalahan per-field.

### Kode error yang perlu ditangani

| HTTP | `code` | Tindakan di aplikasi |
|---|---|---|
| 401 | `UNAUTHENTICATED` | Token tidak valid/kedaluwarsa → hapus token, buka halaman login |
| 403 | `FORBIDDEN` | Tidak punya izin → sembunyikan fitur / tampilkan pesan |
| 403 | `SUBSCRIPTION_EXPIRED` | Langganan perusahaan berakhir → layar "Langganan berakhir" |
| 403 | `ACCOUNT_INACTIVE` | Akun dinonaktifkan admin |
| 403 | `EMAIL_NOT_VERIFIED` | Minta user verifikasi email (link dikirim via email) |
| 404 | `NOT_FOUND` | Data tidak ada / milik perusahaan lain |
| 422 | `VALIDATION_ERROR` dan kode khusus modul | Tampilkan `errors` di field form |
| 422 | `PLAN_LIMIT_REACHED` | Kuota aset/user paket penuh → info hubungi admin |
| 422 | `STORAGE_QUOTA_EXCEEDED` | Kuota penyimpanan penuh |
| 429 | `TOO_MANY_LOGIN_ATTEMPTS` | Login dikunci; tampilkan hitung mundur dari `retry_after` (detik) |
| 429 | `RATE_LIMITED` | Terlalu banyak request; coba lagi nanti |
| 500 | `SERVER_ERROR` | Tampilkan pesan umum + tombol coba lagi |

### Tanggal & angka

- Tanggal saja: `YYYY-MM-DD`. Tanggal+jam: ISO 8601 dengan zona waktu (`2026-11-05T13:35:57+07:00`).
- Uang dalam Rupiah (angka, tanpa format). Ukuran storage dalam **byte**.
- Kuota `limit: null` artinya **tanpa batas**.

### Upload file

Endpoint dengan file memakai `multipart/form-data`. Array file dikirim sebagai `asset_photos[]`, `photos[]`, dll.
Untuk endpoint `PUT` yang membawa file, kirim **POST** dengan field tambahan `_method=PUT`.

### Izin (permission)

`GET /me` mengembalikan `permissions` (daftar kode). Tampilkan menu/tombol hanya jika kode terkait ada:

| Fitur | Permission |
|---|---|
| Lihat aset, scan QR, aset saya, request saya | `asset.view` |
| Tambah aset | `asset.create` |
| Ubah aset, assign/transfer/return | `asset.edit` |
| Hapus aset | `asset.delete` |
| Buat request maintenance/repair | `maintenance.create` |
| Lihat jadwal maintenance & request tim | `maintenance.view` |
| Ambil/progres/selesaikan request (teknisi) | `maintenance.edit` |
| Daftar user | `user.view` |
| Audit trail, status langganan | hanya `user.is_administrator = true` |

Server tetap memeriksa izin; aplikasi hanya menyesuaikan tampilan.

---

## 2. Alur aplikasi yang disarankan

1. **Splash** → `GET /app-info`. Bila versi aplikasi < `min_app_version`, tampilkan layar wajib update.
2. Ada token tersimpan? → `GET /me`. Jika 401 → ke login.
3. **Login** → `POST /login` dan simpan `token` di secure storage (`flutter_secure_storage`).
4. Jika `user.must_change_password = true` → paksa ke layar ganti password (`PUT /profile/password`).
5. Jika `subscription.is_active = false` → layar "Langganan berakhir". Admin diarahkan ke web.
6. **Beranda** → `GET /dashboard`.
7. Setelah login & saat token FCM berubah → `POST /devices`. Saat logout → `POST /logout` dengan `fcm_token`.

Token berlaku **60 hari** (`expires_at` pada respons login). Login di perangkat baru akan mengeluarkan sesi di perangkat lama (satu sesi aktif per user).

---

## 3. Endpoint

Keterangan: 🔓 publik · 🔐 perlu token · 💳 perlu langganan aktif · 👑 khusus administrator

### 3.1 Publik & autentikasi

| Method | Path | Keterangan |
|---|---|---|
| GET | `/app-info` 🔓 | Versi minimal aplikasi, link store, waktu server |
| POST | `/login` 🔓 | Login (maks. 5 gagal → dikunci 15 menit) |
| POST | `/forgot-password` 🔓 | Kirim link reset password ke email |
| GET | `/me` 🔐 | Profil, perusahaan, permission, status langganan |
| POST | `/logout` 🔐 | Cabut token (dan token FCM perangkat bila dikirim) |

**POST /login**

```json
{
  "login": "email@perusahaan.com atau 0812xxxx",
  "password": "rahasia",
  "device_name": "Samsung A55",
  "fcm_token": "opsional",
  "platform": "android"
}
```

Respons `data`: `token`, `token_type`, `expires_at`, dan isi yang sama dengan `GET /me`:

```json
{
  "token": "12|xxxxxxxx",
  "token_type": "Bearer",
  "expires_at": "2027-01-04T16:32:41+07:00",
  "user": {
    "id": 1, "name": "Depoy Dasril", "email": "…", "phone": "…", "nik": "…",
    "photo_url": null, "status": 1, "role": "Administrator", "role_code": "administrator",
    "is_administrator": true, "must_change_password": false, "email_verified": true,
    "last_login_at": "2026-11-05T13:35:57+07:00"
  },
  "company": { "id": 1, "name": "PT Arunika Solusi Inovasi", "code": "…", "logo_url": null },
  "permissions": ["asset.create", "asset.view", "…"],
  "subscription": {
    "is_active": true, "plan": "Free Trial", "plan_code": "FREE", "is_trial": true,
    "is_upgrade": false, "active_until": "2026-11-11", "days_left": 6, "queued_plan": null
  }
}
```

**POST /forgot-password**: body `{ "email": "…" }`. Respons selalu sukses (untuk keamanan). Halaman reset password ada di web.

### 3.2 Profil & perangkat 🔐

| Method | Path | Body / Query |
|---|---|---|
| GET | `/profile` | sama dengan `/me` |
| PUT | `/profile` | `name`, `nik`, `phone` (email tidak bisa diubah) |
| PUT | `/profile/password` | `current_password`, `password` (min. 8), `password_confirmation`, `logout_other_devices` (bool, opsional) |
| POST | `/devices` | `fcm_token`, `platform` (`android`/`ios`), `device_name`?, `app_version`? |
| DELETE | `/devices` | `fcm_token` |

### 3.3 Notifikasi 🔐

| Method | Path | Keterangan |
|---|---|---|
| GET | `/notifications?filter=unread` | Daftar (ber-halaman) + `unread_count` |
| GET | `/notifications/unread-count` | Untuk badge lonceng |
| POST | `/notifications/{id}/read` | Tandai dibaca |
| POST | `/notifications/read-all` | Tandai semua dibaca |
| DELETE | `/notifications/{id}` | Hapus |

Setiap notifikasi punya `target` untuk navigasi:

```json
{ "id": "uuid", "type": "calendar_invitation", "title": "Undangan Meeting", "message": "…",
  "target": { "screen": "calendar_event", "id": 12 }, "data": { … }, "is_read": false,
  "read_at": null, "created_at": "…" }
```

Nilai `target.screen`: `calendar_event`, `calendar`, `maintenance_request`, `maintenance`, `asset`, `subscription`, atau `target: null`.

### 3.4 Beranda 🔐💳

`GET /dashboard` berisi:
- `kpi`: total/aktif/aset saya/tanpa penanggung jawab, nilai aset, aset baru bulan ini & lalu, maintenance terlambat/hari ini/minggu ini/terbuka, biaya maintenance tahun ini, request pending/in progress.
- `trend`: 12 bulan (`label`, `new_assets`, `total_assets`, `maintenance`).
- `asset_conditions`, `asset_categories` (6 teratas).
- `upcoming_maintenance` (6), `recent_assets` (5), `subscription`, `unread_notifications`.
- 👑 Tambahan untuk admin: `quota` (`users`/`assets`/`storage` → `used`, `limit`, `exceeded`) dan `recent_activity`.

### 3.5 Aset 🔐💳

| Method | Path | Izin | Keterangan |
|---|---|---|---|
| GET | `/assets?search=` | asset.view | Semua aset perusahaan (cari kode/nama/merek/model/serial) |
| GET | `/assets/{id}` | asset.view | Detail + foto + riwayat maintenance |
| GET | `/assets/qr/{qrToken}` | asset.view | Hasil scan QR |
| GET | `/assets/photos/{photoId}` | asset.view | File foto (kirim header Authorization) |
| GET | `/assets/{id}/maintenances` | asset.view | Riwayat maintenance aset |
| GET | `/my-assets`, `/my-assets/{id}`, `/my-assets/{id}/maintenances` | asset.view | Aset yang dipegang user |
| POST | `/assets` | asset.create | Tambah aset (multipart) |
| PUT | `/assets/{id}` | asset.edit | Ubah aset (multipart → POST + `_method=PUT`) |
| DELETE | `/assets/{id}` | asset.delete | Hapus aset |
| GET | `/assets/{id}/assignments` | asset.view | Pemegang saat ini + riwayat penugasan |
| POST | `/assets/{id}/assign` | asset.edit | `user_id`, `start_at`?, `reason`?, `notes`? (otomatis transfer bila sudah dipegang orang lain) |
| POST | `/assets/{id}/return` | asset.edit | `end_at`?, `reason`?, `notes`? |

**Field tambah/ubah aset** (sama dengan form web):

| Field | Wajib | Catatan |
|---|---|---|
| `asset_name` | ✔ | maks. 255 |
| `category_id` | ✔ | dari `/lookups/categories` |
| `sub_category_id` | | harus milik kategori terpilih |
| `vendor_id` | ✔ | dari `/lookups/vendors` |
| `responsible_user_id` | | dari `/lookups/users` (hanya saat tambah) |
| `status` | ✔ | `1` aktif / `0` tidak aktif |
| `maintenance_required` | ✔ (tambah) | `0`/`1`. Jika `1`: `maintenance_type`, `maintenance_trigger`, `maintenance_interval`, `maintenance_interval_unit`, `maintenance_start_date` wajib |
| `asset_condition` | | `new`/`used` |
| `brand`, `model`, `serial_number`, `location`, `description` | | |
| `purchase_date`, `purchase_price`, `purchase_invoice` | | |
| `warranty_start`, `warranty_end`, `warranty_note` | | `warranty_end` ≥ `warranty_start` |
| `depreciation_method`, `useful_life`, `residual_value`, `depreciation_start_date` | | |
| `asset_photos[]` | | maks. 3, jpg/png, ≤ 5 MB |
| `invoice_documents[]` | | maks. 5, pdf/jpg/png, ≤ 10 MB |
| `delete_images[]`, `delete_invoice_documents[]` | | hanya saat ubah: id foto/dokumen yang dihapus |

Saat **ubah**, kirim ulang semua nilai lama (form terisi dari `GET /assets/{id}`). Jadwal maintenance tidak diubah lewat endpoint ini.
Respons tambah/ubah berisi detail aset (format sama dengan `GET /assets/{id}`).

### 3.6 Maintenance 🔐💳

**Jadwal & riwayat** (`maintenance.view`)

| Method | Path | Query |
|---|---|---|
| GET | `/maintenances` | `scope` = `open`/`overdue`/`today`/`week`, `status` (pisah koma), `from`, `to`, `asset_id`, `search` |
| GET | `/maintenances/{id}` | detail + foto + tindakan |

**Request maintenance/repair** (alur pengguna → teknisi)

| Method | Path | Izin | Body |
|---|---|---|---|
| POST | `/assets/{assetId}/maintenance-request` | maintenance.create | `description` (5–5000) |
| POST | `/assets/{assetId}/repair-request` | maintenance.create | `description` (5–5000) |
| GET | `/maintenance-requests?status=` | asset.view | Request milik user |
| GET | `/maintenance-requests/{id}` | asset.view | |
| GET | `/maintenance/team/requests?status=&request_type=` | maintenance.view | Default: `pending` + `in_progress` |
| GET | `/maintenance/team/requests/{id}` | maintenance.view | |
| POST | `/maintenance/team/requests/{id}/take` | maintenance.edit | pending → in_progress |
| POST | `/maintenance/team/requests/{id}/progress` | maintenance.edit | `note` (5–5000), `photos[]` (maks. 10, ≤ 10 MB) |
| POST | `/maintenance/team/requests/{id}/complete` | maintenance.edit | `action_taken`, `maintenance_date`, `after_photos[]` (1–10), `technician_name`?, `vendor_id`?, `cost`?, `next_maintenance_date`?, `notes`? |

### 3.7 Kalender 🔐💳

| Method | Path | Keterangan |
|---|---|---|
| GET | `/calendar/events?from=&to=&type=` | Default bulan berjalan, maks. 120 hari |
| GET | `/calendar/events/{id}` | Detail meeting/pengumuman + peserta |
| POST | `/calendar/events` | `title`, `event_type` (`meeting`/`announcement`), `start_at`, `end_at`, `description`?, `location`?, `attendees[]` (id user) |
| PUT | `/calendar/events/{id}` | Sama dengan POST (hanya pembuat) |
| DELETE | `/calendar/events/{id}` | Batalkan agenda (peserta diberi tahu) |

Agenda dalam daftar memiliki `source`:
- `event` → buka detail via `event_id`.
- `maintenance_schedule` → buka aset via `asset_id`.
- `maintenance_request` → buka request via `request_id`.

`can_edit` menandai agenda yang boleh diubah user.

### 3.8 Data pilihan form 🔐💳

| Method | Path | Isi |
|---|---|---|
| GET | `/lookups/categories` | Kategori aktif + `sub_categories` |
| GET | `/lookups/vendors?search=` | Vendor aktif (maks. 200) |
| GET | `/lookups/users?search=&limit=` | User aktif (penanggung jawab, peserta meeting) |
| GET | `/lookups/options` | Enum (kondisi aset, tipe/interval maintenance, jenis agenda) + batas upload |

Simpan `/lookups/options` di cache. Data ini jarang berubah.

### 3.9 User & administrasi

| Method | Path | Akses | Keterangan |
|---|---|---|---|
| GET | `/users?search=&status=&role_id=` | 💳 user.view | Daftar user + jumlah aset |
| GET | `/users/{id}` | 💳 user.view | Detail + aset yang dipegang |
| GET | `/admin/subscription` | 👑 | Paket berjalan, paket dasar (saat upgrade), antrean, add-on, kuota & pemakaian, `manage_url` |
| GET | `/admin/subscription/payments` | 👑 | Riwayat pembayaran |
| GET | `/admin/audit-logs?module=&event=&user_id=&from=&to=&search=` | 💳👑 | Audit trail |
| GET | `/admin/audit-logs/{id}` | 💳👑 | Detail (nilai lama & baru) |
| GET | `/admin/audit-logs/filters` | 💳👑 | Daftar modul & jenis aktivitas |

`/admin/subscription` tetap bisa diakses walau langganan sudah berakhir.

---

## 4. Langganan & pembayaran di aplikasi

Pembelian paket, perpanjangan, upgrade, dan add-on **tidak disediakan di aplikasi mobile**. Google Play dan App Store mewajibkan penjualan langganan digital di dalam aplikasi memakai sistem pembayaran mereka (dengan potongan komisi). Yang disarankan:

- Tampilkan status & kuota dari `/admin/subscription`.
- **Android**: boleh menampilkan teks "Kelola langganan di dashboard web" beserta `manage_url`.
- **iOS**: tampilkan informasi saja **tanpa tombol/tautan ke halaman pembelian**, agar aplikasi tidak ditolak saat review App Store.

---

## 5. Konfigurasi server (.env)

```
SANCTUM_EXPIRATION=86400          # menit (60 hari); kosongkan = tanpa batas
MOBILE_MIN_APP_VERSION=1.0.0      # naikkan untuk memaksa update
MOBILE_LATEST_APP_VERSION=1.0.0
MOBILE_PLAY_STORE_URL=
MOBILE_APP_STORE_URL=
MOBILE_SUPPORT_EMAIL=
```

Cron server wajib aktif: `* * * * * php artisan schedule:run` (pengingat langganan & pembersihan token kedaluwarsa).

---

## 6. Belum tersedia (rencana berikutnya)

- **Pengiriman push notification (FCM)**: token perangkat sudah disimpan (`device_tokens`), pengirimnya belum dibuat. Saat ini notifikasi dibaca lewat `GET /notifications` (polling / pull-to-refresh).
- **Notifikasi untuk alur request maintenance**: request baru → teknisi, dan diambil/selesai → pemohon.
- Tambah/ubah user, role, kategori, dan vendor dari mobile (masih lewat web).
