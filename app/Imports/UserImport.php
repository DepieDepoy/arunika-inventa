<?php

namespace App\Imports;

use App\Models\ImportError;
use App\Models\ImportHistory;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UserImport implements
    ToCollection,
    WithHeadingRow,
    WithChunkReading
{
    protected int $companyId;

    protected int $staffRoleId;

    protected string $defaultPasswordHash;

    protected ImportHistory $history;

    public function __construct(
        ImportHistory $history
    ) {
        $this->history = $history;

        $this->companyId = $history->company_id;

        $staffRole = Role::where(
            'company_id',
            $this->companyId
        )
            ->where(
                'role_name',
                'Staff'
            )
            ->firstOrFail();

        $this->staffRoleId = $staffRole->id;

        /*
         * Hash password SATU KALI saja.
         */
        $defaultPassword = env(
            'DEFAULT_USER_PASSWORD',
            'Inventa@123'
        );

        $this->defaultPasswordHash =
            Hash::make($defaultPassword);
    }

    /**
     * Process setiap chunk Excel.
     */
    public function collection(Collection $rows): void
    {
        if ($rows->isEmpty()) {
            return;
        }

        /*
         * =====================================================
         * 1. NORMALISASI DATA CHUNK
         * =====================================================
         */

        $preparedRows = [];

        foreach ($rows as $row) {

            $nik = trim(
                (string) (
                    $row['id_person_nik'] ?? ''
                )
            );

            $name = trim(
                (string) (
                    $row['name'] ?? ''
                )
            );

            $email = trim(
                (string) (
                    $row['email'] ?? ''
                )
            );

            $phone = trim(
                (string) (
                    $row['phone'] ?? ''
                )
            );

            /*
             * Abaikan baris kosong.
             */
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
                'email' => $email !== ''
                    ? strtolower($email)
                    : null,
                'phone' => $phone !== ''
                    ? $phone
                    : null,
                'original_data' =>
                    $row->toArray(),
            ];
        }

        if (empty($preparedRows)) {
            return;
        }

        /*
         * =====================================================
         * 2. KUMPULKAN NIK & EMAIL
         * =====================================================
         */

        $niks = [];

        $emails = [];

        foreach ($preparedRows as $row) {

            if ($row['nik'] !== '') {
                $niks[] = $row['nik'];
            }

            if (!empty($row['email'])) {
                $emails[] = $row['email'];
            }
        }

        $niks = array_values(
            array_unique($niks)
        );

        $emails = array_values(
            array_unique($emails)
        );

        /*
         * =====================================================
         * 3. CEK NIK EXISTING SEKALIGUS
         * =====================================================
         */

        $existingNiks = [];

        if (!empty($niks)) {

            $existingNiks = User::where(
                'company_id',
                $this->companyId
            )
                ->whereIn(
                    'nik',
                    $niks
                )
                ->pluck('nik')
                ->map(
                    fn ($value) => (string) $value
                )
                ->flip()
                ->toArray();
        }

        /*
         * =====================================================
         * 4. CEK EMAIL EXISTING SEKALIGUS
         * =====================================================
         */

        $existingEmails = [];

        if (!empty($emails)) {

            $existingEmails = User::where(
                'company_id',
                $this->companyId
            )
                ->whereIn(
                    'email',
                    $emails
                )
                ->pluck('email')
                ->map(
                    fn ($value) => strtolower(
                        (string) $value
                    )
                )
                ->flip()
                ->toArray();
        }

        /*
         * =====================================================
         * 5. CEK DUPLICATE DI DALAM FILE
         * =====================================================
         */

        $seenNiks = [];

        $seenEmails = [];

        $insertRows = [];

        $errorRows = [];

        foreach ($preparedRows as $row) {

            $errors = [];

            $nik = $row['nik'];

            $email = $row['email'];

            /*
             * NIK wajib.
             */
            if ($nik === '') {

                $errors[] =
                    'NIK wajib diisi.';
            }

            /*
             * Nama wajib.
             */
            if ($row['name'] === '') {

                $errors[] =
                    'Nama wajib diisi.';
            }

            /*
             * Validasi email.
             */
            if (
                $email !== null &&
                !filter_var(
                    $email,
                    FILTER_VALIDATE_EMAIL
                )
            ) {

                $errors[] =
                    'Format email tidak valid.';
            }

            /*
             * Cek NIK database.
             */
            if (
                $nik !== '' &&
                isset($existingNiks[$nik])
            ) {

                $errors[] =
                    'NIK sudah terdaftar di perusahaan ini.';
            }

            /*
             * Cek duplicate NIK dalam file.
             */
            if (
                $nik !== '' &&
                isset($seenNiks[$nik])
            ) {

                $errors[] =
                    'NIK duplicate di dalam file Excel.';
            }

            /*
             * Cek email database.
             */
            if (
                $email !== null &&
                isset($existingEmails[$email])
            ) {

                $errors[] =
                    'Email sudah terdaftar di perusahaan ini.';
            }

            /*
             * Cek duplicate email dalam file.
             */
            if (
                $email !== null &&
                isset($seenEmails[$email])
            ) {

                $errors[] =
                    'Email duplicate di dalam file Excel.';
            }

            /*
             * Tandai sebagai sudah pernah ditemukan.
             */
            if ($nik !== '') {
                $seenNiks[$nik] = true;
            }

            if ($email !== null) {
                $seenEmails[$email] = true;
            }

            /*
             * =================================================
             * ERROR
             * =================================================
             */

            if (!empty($errors)) {

                $errorRows[] = [
                    'import_history_id' =>
                        $this->history->id,

                    'row_number' =>
                        0,

                    'data' =>
                        json_encode(
                            $row['original_data'],
                            JSON_UNESCAPED_UNICODE
                        ),

                    'error_message' =>
                        implode(
                            ' ',
                            $errors
                        ),

                    'created_at' =>
                        now(),

                    'updated_at' =>
                        now(),
                ];

                continue;
            }

            /*
             * =================================================
             * VALID → SIAP BULK INSERT
             * =================================================
             */

            $insertRows[] = [
                'company_id' =>
                    $this->companyId,

                'role_id' =>
                    $this->staffRoleId,

                'name' =>
                    $row['name'],

                'nik' =>
                    $nik,

                'email' =>
                    $email,

                'phone' =>
                    $row['phone'],

                'password' =>
                    $this->defaultPasswordHash,

                'status' =>
                    1,

                'must_change_password' =>
                    1,

                'created_at' =>
                    now(),

                'updated_at' =>
                    now(),
            ];
        }

        /*
         * =====================================================
         * 6. BULK INSERT USER
         * =====================================================
         */

        if (!empty($insertRows)) {

            DB::table('users')->insert(
                $insertRows
            );
        }

        /*
         * =====================================================
         * 7. BULK INSERT ERROR
         * =====================================================
         */

        if (!empty($errorRows)) {

            DB::table('import_errors')->insert(
                $errorRows
            );
        }

        /*
         * =====================================================
         * 8. UPDATE COUNTER HISTORY
         * =====================================================
         */

        $successCount =
            count($insertRows);

        $failedCount =
            count($errorRows);

        $this->history->increment(
            'success_rows',
            $successCount
        );

        $this->history->increment(
            'failed_rows',
            $failedCount
        );
    }

    /**
     * Ukuran chunk.
     */
    public function chunkSize(): int
    {
        return 1000;
    }
}