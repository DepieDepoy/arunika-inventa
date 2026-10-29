<?php

namespace App\Imports;

use App\Models\ImportHistory;
use App\Models\Role;
use App\Models\User;
use App\Notifications\UserImportedNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UserImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    protected int $companyId;
    protected int $staffRoleId;
    protected string $defaultPassword;
    protected string $defaultPasswordHash;
    protected ImportHistory $history;
    protected bool $sendVerificationEmail;

    /*
     * Cache lintas chunk agar duplikat dalam satu file
     * tetap terdeteksi meskipun barisnya berjauhan.
     */
    protected array $seenNiks = [];
    protected array $seenEmails = [];
    protected array $seenPhones = [];

    public function __construct(
        ImportHistory $history,
        bool $sendVerificationEmail = false
    ) {
        $this->history = $history;
        $this->sendVerificationEmail = $sendVerificationEmail;
        $this->companyId = (int) $history->company_id;

        $staffRole = Role::where('company_id', $this->companyId)
            ->where('role_name', 'Staff')
            ->firstOrFail();

        $this->staffRoleId = (int) $staffRole->id;

        $this->defaultPassword = env(
            'DEFAULT_USER_PASSWORD',
            'Vasetra@123'
        );

        $this->defaultPasswordHash = Hash::make(
            $this->defaultPassword
        );
    }

    /**
     * Normalisasi nomor telepon untuk perbandingan.
     * Spasi dan tanda pemisah diabaikan, tetapi + dan angka
     * tetap dipertahankan agar format internasional tidak
     * diubah secara sembarangan.
     */
    protected function normalizePhone(string $phone): string
    {
        return preg_replace('/[\s().-]+/', '', trim($phone)) ?? trim($phone);
    }

    public function collection(Collection $rows): void
    {
        if ($rows->isEmpty()) {
            return;
        }

        /*
         * 1. NORMALISASI DATA CHUNK
         */
        $preparedRows = [];

        foreach ($rows as $row) {
            $nik = trim((string) ($row['id_person_nik'] ?? ''));
            $name = trim((string) ($row['name'] ?? ''));
            $email = trim((string) ($row['email'] ?? ''));
            $phone = trim((string) ($row['phone'] ?? ''));

            if (
                $nik === '' &&
                $name === '' &&
                $email === '' &&
                $phone === ''
            ) {
                continue;
            }

            $preparedRows[] = [
                'nik' => $nik,
                'name' => $name,
                'email' => $email !== '' ? strtolower($email) : null,
                'phone' => $phone !== '' ? $phone : null,
                'phone_key' => $phone !== ''
                    ? $this->normalizePhone($phone)
                    : null,
                'original_data' => $row->toArray(),
            ];
        }

        if (empty($preparedRows)) {
            return;
        }

        /*
         * 2. AMBIL DATA UNTUK PENGECEKAN DATABASE
         */
        $niks = [];
        $emails = [];
        $phones = [];

        foreach ($preparedRows as $row) {
            if ($row['nik'] !== '') {
                $niks[] = $row['nik'];
            }

            if ($row['email'] !== null) {
                $emails[] = $row['email'];
            }

            if ($row['phone'] !== null) {
                $phones[] = $row['phone'];
            }
        }

        $niks = array_values(array_unique($niks));
        $emails = array_values(array_unique($emails));
        $phones = array_values(array_unique($phones));

        /*
         * 3. CEK NIK EXISTING
         */
        $existingNiks = [];

        if ($niks !== []) {
            $existingNiks = User::query()
                ->where('company_id', $this->companyId)
                ->whereIn('nik', $niks)
                ->pluck('nik')
                ->map(fn ($value) => trim((string) $value))
                ->flip()
                ->toArray();
        }

        /*
         * 4. CEK EMAIL EXISTING
         */
        $existingEmails = [];

        if ($emails !== []) {
            $existingEmails = User::query()
                ->where('company_id', $this->companyId)
                ->whereIn('email', $emails)
                ->pluck('email')
                ->map(fn ($value) => strtolower(trim((string) $value)))
                ->flip()
                ->toArray();
        }

        /*
         * 5. CEK TELEPON EXISTING
         *
         * Unique index users_phone_unique berlaku global.
         * Ambil semua nomor yang cocok dengan nilai Excel.
         */
        $existingPhones = [];

        if ($phones !== []) {
            $existingPhoneValues = User::query()
                ->whereIn('phone', $phones)
                ->pluck('phone');

            foreach ($existingPhoneValues as $existingPhone) {
                $key = $this->normalizePhone((string) $existingPhone);

                if ($key !== '') {
                    $existingPhones[$key] = true;
                }
            }
        }

        /*
         * 6. VALIDASI PER BARIS
         */
        $insertRows = [];
        $errorRows = [];

        foreach ($preparedRows as $row) {
            $errors = [];

            $nik = $row['nik'];
            $email = $row['email'];
            $phone = $row['phone'];
            $phoneKey = $row['phone_key'];

            if ($nik === '') {
                $errors[] = 'NIK wajib diisi.';
            }

            if ($row['name'] === '') {
                $errors[] = 'Nama wajib diisi.';
            }

            if (
                $email !== null &&
                !filter_var($email, FILTER_VALIDATE_EMAIL)
            ) {
                $errors[] = 'Format email tidak valid.';
            }

            /*
             * NIK: file Excel lebih dahulu, lalu database.
             */
            if ($nik !== '') {
                if (isset($this->seenNiks[$nik])) {
                    $errors[] = 'NIK duplikat di dalam file Excel.';
                } elseif (isset($existingNiks[$nik])) {
                    $errors[] = 'NIK sudah terdaftar di perusahaan ini.';
                }
            }

            /*
             * Email: file Excel lebih dahulu, lalu database.
             */
            if ($email !== null) {
                if (isset($this->seenEmails[$email])) {
                    $errors[] = 'Email duplikat di dalam file Excel.';
                } elseif (isset($existingEmails[$email])) {
                    $errors[] = 'Email sudah terdaftar di perusahaan ini.';
                }
            }

            /*
             * TELEPON:
             * Periksa duplikat di Excel menggunakan nilai normalisasi.
             */
            if ($phoneKey !== null && $phoneKey !== '') {
                if (isset($this->seenPhones[$phoneKey])) {
                    $errors[] = 'Nomor telepon duplikat di dalam file Excel.';
                } elseif (isset($existingPhones[$phoneKey])) {
                    $errors[] = 'Nomor telepon sudah terdaftar di database.';
                }
            }

            /*
             * Tandai nilai yang sudah ditemukan agar baris berikutnya
             * terdeteksi sebagai duplikat, termasuk antar-chunk.
             */
            if ($nik !== '') {
                $this->seenNiks[$nik] = true;
            }

            if ($email !== null) {
                $this->seenEmails[$email] = true;
            }

            if ($phoneKey !== null && $phoneKey !== '') {
                $this->seenPhones[$phoneKey] = true;
            }

            /*
             * Baris gagal: catat ke tabel import_errors.
             */
            if ($errors !== []) {
                $errorRows[] = [
                    'import_history_id' => $this->history->id,
                    'row_number' => 0,
                    'data' => json_encode(
                        $row['original_data'],
                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                    ),
                    'error_message' => implode(' ', $errors),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                continue;
            }

            /*
             * Baris valid: masukkan ke batch insert.
             */
            $insertRows[] = [
                'company_id' => $this->companyId,
                'role_id' => $this->staffRoleId,
                'name' => $row['name'],
                'nik' => $nik,
                'email' => $email,
                'phone' => $phone,
                'password' => $this->defaultPasswordHash,
                'status' => 1,
                'must_change_password' => 1,
                'email_verified_at' => $this->sendVerificationEmail
                    ? null
                    : now(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        /*
         * 7. SIMPAN ERROR VALIDASI TERLEBIH DAHULU
         *
         * Supaya detail error sudah tercatat sebelum proses insert.
         */
        if ($errorRows !== []) {
            DB::table('import_errors')->insert($errorRows);

            $this->history->increment(
                'failed_rows',
                count($errorRows)
            );
        }

        /*
         * 8. BULK INSERT USER VALID
         */
        if ($insertRows !== []) {
            DB::table('users')->insert($insertRows);

            $this->history->increment(
                'success_rows',
                count($insertRows)
            );

            /*
             * 9. KIRIM NOTIFIKASI JIKA DIMINTA
             */
            if ($this->sendVerificationEmail) {
                $insertedNiks = collect($insertRows)
                    ->pluck('nik')
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                if ($insertedNiks !== []) {
                    $insertedUsers = User::query()
                        ->where('company_id', $this->companyId)
                        ->whereIn('nik', $insertedNiks)
                        ->get();

                    foreach ($insertedUsers as $user) {
                        if (empty($user->email)) {
                            continue;
                        }

                        $user->notify(
                            new UserImportedNotification(
                                $this->defaultPassword
                            )
                        );
                    }
                }
            }
        }
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}
