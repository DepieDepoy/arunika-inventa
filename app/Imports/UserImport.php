<?php

namespace App\Imports;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithBatchInserts;

class UserImport implements
    ToModel,
    WithHeadingRow,
    SkipsEmptyRows,
    WithChunkReading,
    WithBatchInserts,
    ShouldQueue
{
    protected int $companyId;

    protected int $staffRoleId;

    protected string $defaultPasswordHash;

    public function __construct(
        int $companyId,
        int $staffRoleId
    ) {
        /*
         * Company dan Role dikirim dari controller.
         *
         * Jangan menggunakan Auth::user() di sini
         * karena import dijalankan oleh Queue Worker.
         */
        $this->companyId = $companyId;

        $this->staffRoleId = $staffRoleId;

        /*
         * Hash password default hanya SEKALI.
         */
        $defaultPassword = env(
            'DEFAULT_USER_PASSWORD',
            'Inventa@123'
        );

        $this->defaultPasswordHash = Hash::make(
            $defaultPassword
        );
    }

    public function model(array $row): Model|null
    {
        return new User([
            'company_id' => $this->companyId,

            'role_id' => $this->staffRoleId,

            'name' => $row['name'] ?? null,

            'nik' => $row['id_person_nik'] ?? null,

            'email' => $row['email'] ?? null,

            'phone' => $row['phone'] ?? null,

            /*
             * Menggunakan hash yang sudah dibuat sekali.
             */
            'password' => $this->defaultPasswordHash,

            'status' => 1,

            /*
             * User hasil import wajib mengganti password.
             */
            'must_change_password' => 1,
        ]);
    }

    /**
     * Excel dibaca per 1.000 baris.
     */
    public function chunkSize(): int
    {
        return 1000;
    }

    /**
     * Insert database per 1.000 data.
     */
    public function batchSize(): int
    {
        return 1000;
    }
}