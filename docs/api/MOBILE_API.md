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
| 409 | `ALREADY_TAKEN` | Request sudah diambil teknisi lain (`message` menyebut namanya) → muat ulang daftar |
| 403 | `NOT_HANDLER` | Request sedang ditangani teknisi lain |
| 422 | `INVALID_STATUS` | Status request tidak sesuai (mis. ubah/batal setelah diambil) |
| 422 | `ACTIVE_REQUEST_EXISTS` | Aset masih punya request aktif (`data.request` berisi request tersebut) |
| 403 | `CANNOT_MODIFY_SELF` | Tidak boleh menonaktifkan/menghapus/mengganti role akun sendiri |
| 403 | `SYSTEM_ROLE` | Role Administrator tidak dapat diubah |
| 422 | `ROLE_IN_USE`, `IN_USE`, `USER_HAS_ASSETS` | Data masih dipakai, tidak bisa dihapus |
| 403 | `PASSWORD_CHANGE_REQUIRED` | Password awal (dibuat admin / hasil import) belum diganti → paksa layar ganti password. Yang tetap boleh: `/profile*`, `/account/delete`, `/devices`, `/notifications*`, `/logout`, `/app-info` |
| 403 | `COMPANY_SUSPENDED` | Perusahaan ditangguhkan tim Vasetra → hapus token, tampilkan pesan, kembali ke login |
| 426 | `UPGRADE_REQUIRED` | Versi aplikasi di bawah minimal → layar wajib update; `data.store_url` berisi link toko |
| 503 | `MAINTENANCE` | Mode pemeliharaan aplikasi aktif → layar "sedang pemeliharaan" + tombol coba lagi |
| 422 | `INVALID_PASSWORD` | Password salah saat hapus akun |
| 429 | `TOO_MANY_LOGIN_ATTEMPTS` | Login dikunci; tampilkan hitung mundur dari `retry_after` (detik) |
| 429 | `RATE_LIMITED` | Terlalu banyak request; coba lagi nanti |
| 500 | `SERVER_ERROR` | Tampilkan pesan umum + tombol coba lagi |

### Header wajib dari aplikasi

Kirim di **setiap** request agar server bisa memaksa update & mode pemeliharaan:

```
Accept: application/json
X-App-Platform: android        # android | ios | web
X-App-Version: 1.0.0           # versi aplikasi (pubspec.yaml)
```

Versi minimal, versi terbaru, link toko, dan mode pemeliharaan diatur di **Backoffice › Aplikasi mobile** (bukan .env).

**Saat dites di browser (Flutter web)** kirim `X-App-Platform: web` (`kIsWeb`). Server tidak menolaknya:
pengecekan versi & pemeliharaan memakai aturan **android**. Yang berbeda di web hanya pendaftaran
token push: `POST /devices` hanya menerima `android`/`ios`, jadi **lewati pendaftaran FCM saat `kIsWeb`**.

`X-App-Version` sebaiknya diambil otomatis dari `package_info_plus` (`PackageInfo.version`) agar selalu sama dengan `pubspec.yaml`.

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
| Lihat semua aset | `asset.view` |
| Scan QR | `asset.view` / `asset.my` / `maintenance.create` (detail lengkap hanya untuk administrator & pemegang aset) |
| Menu **Aset Saya**, request saya, foto aset yang dipegang | `asset.my` (server juga menerima `asset.view` / `maintenance.create`) |
| Riwayat maintenance aset sendiri | `asset.my` → `GET /my-assets/{id}/maintenances` |
| Tambah aset | `asset.create` |
| Ubah aset, assign/transfer/return | `asset.edit` |
| Hapus aset | `asset.delete` |
| Buat / ubah / batalkan request maintenance | `maintenance.create` |
| Lihat jadwal maintenance & request tim | `maintenance.view` |
| Ambil/progres/selesaikan request (teknisi) | `maintenance.edit` |
| Daftar user / tambah / ubah / hapus | `user.view` / `user.create` / `user.edit` / `user.delete` |
| Role: lihat / tambah / ubah / hapus / atur permission | `role.view` / `role.create` / `role.edit` / `role.delete` / `role.permission` |
| Kategori & subkategori | `category.*`, `subcategory.*` (`view`/`create`/`edit`/`delete`) |
| Vendor | `vendor.*` (`view`/`create`/`edit`/`delete`) |
| Audit trail, status langganan | hanya `user.is_administrator = true` |

Server tetap memeriksa izin; aplikasi hanya menyesuaikan tampilan.

**Role bawaan** setiap perusahaan (role = hak akses, bukan jabatan):

| Role (`role_code`) | Untuk | Permission bawaan |
|---|---|---|
| Administrator (`administrator`) | Pengelola aplikasi | Semua (tidak dapat diubah) |
| Teknisi (`teknisi`) | Mengambil & mengerjakan request | `asset.view`, `asset.my`, `maintenance.view`, `maintenance.create`, `maintenance.edit`, `maintenance.history` |
| Karyawan (`karyawan`) | Memegang aset & membuat request | `asset.my`, `maintenance.create`, `maintenance.history` |

Admin dapat mengubah permission Teknisi/Karyawan atau menambah role sendiri. Karena itu aplikasi harus selalu
membaca `permissions`, bukan menebak dari nama role.

---

## 2. Alur aplikasi yang disarankan

1. **Splash** → `GET /app-info?platform=android&version=1.0.0`.
   - `update.required = true` → layar wajib update (tombol ke `store_url`).
   - `update.available = true` → tawarkan update (boleh dilewati).
   - `maintenance.active = true` → layar pemeliharaan.
2. Ada token tersimpan? → `GET /me`. Jika 401 / `COMPANY_SUSPENDED` → ke login.
3. **Login** → `POST /login` dan simpan `token` di secure storage (`flutter_secure_storage`).
4. Jika `user.must_change_password = true` → paksa ke layar ganti password (`PUT /profile/password`).
5. Jika `subscription.is_active = false` → layar "Langganan berakhir". Admin diarahkan ke web.
6. **Beranda** → `GET /dashboard`.
7. Setelah login & saat token FCM berubah → `POST /devices`. Saat logout → `POST /logout` dengan `fcm_token`.
8. Menu **Profil** wajib punya: link Kebijakan Privasi & Syarat Layanan (`app-info.links`) dan tombol **Hapus akun** (`POST /account/delete`) — syarat Google Play & App Store.
9. Logo perusahaan: `company.logo_url` di `/me` (null = tampilkan logo Vasetra).

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
| POST | `/account/delete` | `password`, `reason`? — hapus akun sendiri |

**POST /account/delete** — respons `data.status`:
- `deleted` → akun sudah dihapus & semua token dicabut. Hapus data lokal, kembali ke login.
- `company_pending` → user adalah **administrator terakhir**; dicatat sebagai permintaan penutupan perusahaan (diproses tim Vasetra ≤ 30 hari). Tampilkan `message`, akun masih bisa dipakai.

**app-info** (publik) juga berisi `links.privacy_policy`, `links.terms_of_service`, `links.delete_account`,
`features.push_notification`, `features.tera_ai`, dan `support_email`.

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
| GET | `/assets/qr/{qrToken}` | asset.view / asset.my / maintenance.create | Hasil scan QR. `data.access` = `full` (administrator / pemegang: `asset` berisi detail lengkap) atau `limited` (user lain: `asset` hanya kode, nama, kategori, merek, model, kondisi, lokasi, status, `responsible_user.name`, `has_active_request`). `data.is_mine` = aset dipegang user. |
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

**Request maintenance — alur kerja**

```
Pemohon (maintenance.create)           Tim maintenance (maintenance.edit)
────────────────────────────           ─────────────────────────────────
buat request ──── notifikasi ────────▶ semua anggota tim
(ubah / batal selama pending)          "Ambil" (siapa cepat dia dapat)
                                         ├─ yang berhasil → status in_progress
◀── notifikasi "dikerjakan oleh A" ──────┤
                                         └─ anggota lain: hilang dari antrean,
                                            notifikasinya berubah menjadi
                                            "sudah diambil — dikerjakan oleh A"
◀── notifikasi tiap progress ─────────── A menambah progress (+ foto)
◀── notifikasi "selesai" ─────────────── A menyelesaikan (+ foto hasil)
```

Tim maintenance = user dengan role aktif yang memiliki `maintenance.edit` (selain Administrator).
Bila perusahaan belum punya role teknisi, notifikasi request baru dikirim ke Administrator.

| Method | Path | Izin | Body / Query |
|---|---|---|---|
| POST | `/assets/{assetId}/requests` | maintenance.create | `request_type` (`maintenance`/`repair`/`problem`/`other`), `description` (5–5000). Jenis `problem` (laporan masalah) boleh untuk aset perusahaan mana pun; jenis lain hanya untuk aset yang dipegang user. |
| POST | `/assets/{assetId}/maintenance-request` | maintenance.create | `description` (pintasan jenis `maintenance`) |
| POST | `/assets/{assetId}/repair-request` | maintenance.create | `description` (pintasan jenis `repair`) |
| PUT | `/maintenance-requests/{id}` | maintenance.create | `description`, `request_type`? — hanya pemohon & selama `pending` |
| POST | `/maintenance-requests/{id}/cancel` | maintenance.create | Batalkan — hanya pemohon & selama `pending` |
| GET | `/maintenance-requests?status=&request_type=` | asset.view | Request milik user |
| GET | `/maintenance-requests/{id}` | asset.view | Detail + progress + hasil |
| GET | `/maintenance/team/requests?scope=&search=&request_type=` | maintenance.view | Lihat `scope` di bawah. Respons memuat `summary` |
| GET | `/maintenance/team/requests/{id}` | maintenance.view | |
| POST | `/maintenance/team/requests/{id}/take` | maintenance.edit | pending → in_progress. Gagal: 409 `ALREADY_TAKEN` |
| POST | `/maintenance/team/requests/{id}/progress` | maintenance.edit | `note` (5–5000), `photos[]` (maks. 10, ≤ 10 MB) |
| POST | `/maintenance/team/requests/{id}/complete` | maintenance.edit | `action_taken`, `maintenance_date`, `after_photos[]` (1–10), `technician_name`?, `vendor_id`?, `cost`?, `next_maintenance_date`?, `notes`? |

`scope` untuk daftar tim (cocok untuk tab di aplikasi):

| scope | Isi |
|---|---|
| `available` | Menunggu diambil (`pending`) |
| `in_progress` | Sedang dikerjakan siapa pun |
| `mine` | Sedang saya kerjakan |
| `history` | Selesai / dibatalkan |
| *(kosong)* | `pending` + `in_progress` (atau filter `status=`) |

`summary` = `{ "available": 3, "in_progress": 2, "mine": 1 }` untuk angka badge tab.

Setiap item request memuat penanda tampilan, jadi aplikasi tidak perlu menghitung sendiri:

```json
{
  "id": 21, "request_type": "repair", "request_type_label": "Perbaikan",
  "status": "in_progress", "status_label": "Sedang dikerjakan oleh Budi",
  "is_mine": false, "can_take": false, "can_work": false, "can_edit": false,
  "handler": { "id": 8, "name": "Budi" }, "...": "..."
}
```

- `can_take` → tampilkan tombol **Ambil**.
- `can_work` → tampilkan tombol **Progress** & **Selesaikan** (hanya teknisi yang mengambil).
- `can_edit` → pemohon boleh ubah / batalkan.
- Notifikasi request memiliki `target.screen = "maintenance_request"` dan `data.action` (`created`, `taken`, `progress`, `completed`, `closed`).

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
| GET | `/users/roles` | 💳 user.view | Pilihan role untuk form user (non-admin tidak melihat Administrator) |
| POST | `/users` | 💳 user.create | `name`, `nik`, `email`, `phone`, `role_id`, `status`, `send_verification_email` (bool) |
| PUT | `/users/{id}` | 💳 user.edit | Sama dengan POST; `password` + `password_confirmation` opsional |
| DELETE | `/users/{id}` | 💳 user.delete | Gagal `USER_HAS_ASSETS` bila masih memegang aset |
| GET | `/admin/subscription` | 👑 | Paket berjalan, paket dasar (saat upgrade), antrean, add-on, kuota & pemakaian, `manage_url` |
| GET | `/admin/subscription/payments` | 👑 | Riwayat pembayaran |
| GET | `/admin/audit-logs?module=&event=&user_id=&from=&to=&search=` | 💳👑 | Audit trail |
| GET | `/admin/audit-logs/{id}` | 💳👑 | Detail (nilai lama & baru) |
| GET | `/admin/audit-logs/filters` | 💳👑 | Daftar modul & jenis aktivitas |

`/admin/subscription` tetap bisa diakses walau langganan sudah berakhir.

**Tambah user**: respons `data` = `user`, `default_password`, `email_sent`. Bila `email_sent = false`, tampilkan
`default_password` sekali ke admin agar bisa diinformasikan ke user (user wajib mengganti password saat login).
Kuota paket berlaku (`PLAN_LIMIT_REACHED`).

Pengaman yang berlaku untuk akses mobile:
- Hanya Administrator yang dapat memberi role Administrator atau mengubah/menghapus akun Administrator.
- User tidak dapat menonaktifkan, menghapus, atau mengganti role akunnya sendiri (`CANNOT_MODIFY_SELF`).

### 3.10 Role & permission 💳

| Method | Path | Izin | Keterangan |
|---|---|---|---|
| GET | `/roles?search=&status=` | role.view | Daftar role + `users_count`, `permissions_count`, `is_system` |
| GET | `/roles/permissions` | role.view | Katalog permission per modul (untuk layar checklist) |
| GET | `/roles/{id}` | role.view | Detail + `permission_ids` |
| POST | `/roles` | role.create | `role_name`, `status` |
| PUT | `/roles/{id}` | role.edit | `role_name`, `status` |
| DELETE | `/roles/{id}` | role.delete | Gagal `ROLE_IN_USE` bila masih dipakai user |
| PUT | `/roles/{id}/permissions` | role.permission | `permission_ids: [..]` — daftar **lengkap**; yang tidak dikirim dicabut |

Role Administrator (`is_system: true`) tidak dapat diubah (`SYSTEM_ROLE`). Selain Administrator, user tidak dapat
mengubah role yang sedang dipakainya sendiri.

### 3.11 Kategori, subkategori & vendor 💳

| Method | Path | Izin | Body / Query |
|---|---|---|---|
| GET | `/categories?search=&status=` | category.view | Ber-halaman, + jumlah subkategori & aset |
| GET | `/categories/{id}` | category.view | Detail + `sub_categories` |
| POST | `/categories` | category.create | `category_name`, `status`, `description`? — kode `CAT-01` dibuat otomatis |
| PUT | `/categories/{id}` | category.edit | sama dengan POST |
| DELETE | `/categories/{id}` | category.delete | Gagal `IN_USE` bila masih dipakai aset |
| POST | `/categories/{id}/sub-categories` | subcategory.create | `sub_category_name`, `status`, `description`? |
| PUT | `/sub-categories/{id}` | subcategory.edit | sama + `category_id`? (pindah kategori) |
| DELETE | `/sub-categories/{id}` | subcategory.delete | Gagal `IN_USE` bila masih dipakai aset |
| GET | `/vendors?search=&status=` | vendor.view | Ber-halaman |
| GET | `/vendors/{id}` | vendor.view | |
| POST | `/vendors` | vendor.create | `vendor_name`, `address`, `email`, `status`, `phone`?, `pic_name`?, `description`? |
| PUT | `/vendors/{id}` | vendor.edit | sama dengan POST |
| DELETE | `/vendors/{id}` | vendor.delete | |

Untuk dropdown di form aset tetap gunakan `/lookups/*` (lebih ringan, hanya data aktif).

### 3.12 Vasetra AI 💳

| Method | Path | Body |
|---|---|---|
| POST | `/ai/ask` | `question` (maks. 500) |
| POST | `/ai/confirm` | `token` (64 karakter) |

Respons `ask` sama dengan panel AI di web. Bila jawaban berisi aksi yang perlu persetujuan (mis. membuat jadwal),
tampilkan ringkasannya lalu kirim `token`-nya ke `/ai/confirm` jika user setuju. Dibatasi 20 permintaan/menit.

- `message` berisi teks polos dengan baris baru (`\n`) dan poin `•` — tampilkan apa adanya (multi-line).
- `data.url` (opsional) = halaman web terkait; `data.items` (opsional) = daftar aset `{code, name, location, holder}`.
- Tera bisa dimatikan dari Backoffice (global / per perusahaan) → `ask` membalas **403** dengan `type = "disabled"`.
  Sembunyikan tombol Tera bila `app-info.features.tera_ai = false`.

### 3.13 Laporan 🔐💳

| Method | Path | Permission | Keterangan |
|---|---|---|---|
| GET | `/reports?range=this_month` | report.view | Data laporan lengkap (KPI, tren, kategori, lokasi, maintenance, ringkasan otomatis `highlights`) + daftar `presets` |
| GET | `/reports/download?format=xlsx&range=this_year` | report.export | Unduh file **Excel** (`xlsx`) atau **PowerPoint** (`pptx`) |

`range`: `this_week`, `last_week`, `this_month`, `last_month`, `this_quarter`, `this_year`, `last_year`, atau `custom` + `from` & `to` (`YYYY-MM-DD`).
Unduhan berupa file biner (bukan JSON) — simpan lalu buka dengan `open_filex` / share sheet.

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
MOBILE_SUPPORT_EMAIL=
FIREBASE_CREDENTIALS=storage/app/private/firebase.json   # service account Firebase (push ke HP)
```

Versi minimal/terbaru, link toko, mode pemeliharaan, serta saklar notifikasi browser & aplikasi
diatur di **Backoffice › Aplikasi mobile** (nilai `MOBILE_*` di .env hanya bawaan awal).

Cron server wajib aktif: `* * * * * php artisan schedule:run` (pengingat langganan & pembersihan token kedaluwarsa).

---

## 6. Push notification (FCM)

Server mengirim lewat **FCM HTTP v1** setiap kali ada notifikasi baru (isi sama dengan `GET /notifications`):

```json
{
  "notification": { "title": "Request perbaikan baru", "body": "Laptop Dell · Budi" },
  "data": { "type": "maintenance", "url": "https://…/dashboard/maintenance-requests", "notification_id": "uuid", "...": "..." },
  "android": { "priority": "high", "notification": { "channel_id": "vasetra_default" } },
  "apns": { "payload": { "aps": { "sound": "default", "badge": 1 } } }
}
```

Di aplikasi:
- Buat Android notification channel `vasetra_default` (importance high).
- Saat notifikasi diketuk: tandai dibaca (`POST /notifications/{notification_id}/read`) lalu navigasi sesuai `type` / muat `GET /notifications`.
- Token yang tidak berlaku (aplikasi di-uninstall) otomatis dihapus server.
- Push bisa dimatikan dari Backoffice; notifikasi tetap tersedia di `GET /notifications`.

Persiapan Firebase (sekali): buat project di console.firebase.google.com → tambah app Android (& iOS) →
`google-services.json` / `GoogleService-Info.plist` untuk aplikasi → *Service accounts › Generate new private key*
untuk server (`FIREBASE_CREDENTIALS`).

## 7. Khusus web

Import/export Excel, cetak label QR, profil & logo perusahaan, dan pembelian/upgrade paket hanya di dashboard web.
