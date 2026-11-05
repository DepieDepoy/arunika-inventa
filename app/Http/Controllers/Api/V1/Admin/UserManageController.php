<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Controllers\Dashboard\UserController as WebUserController;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Tambah / ubah / hapus user dari aplikasi mobile.
 *
 * Aturan bisnis (kuota paket, duplikat email, email verifikasi, password
 * default) memakai controller web yang sama. Lapisan ini menambahkan
 * pengaman yang wajib untuk akses mobile:
 *  - hanya Administrator yang boleh memberi role Administrator atau
 *    mengubah/menghapus akun Administrator;
 *  - user tidak boleh menonaktifkan / menghapus / mengganti role dirinya sendiri;
 *  - nomor telepon tetap unik per perusahaan saat diubah.
 */
class UserManageController extends ApiController
{
    /**
     * Body: name, nik, email, phone, role_id, status (0|1),
     *       send_verification_email (bool, opsional).
     */
    public function store(Request $request): JsonResponse
    {
        $actor = $request->user();

        if ($denied = $this->guardRole($actor, $request->input('role_id'))) {
            return $denied;
        }

        $response = $this->delegate(fn () => app(WebUserController::class)->store($request), 'USER_CREATE_FAILED');

        if (!$this->succeeded($response)) {
            return $response;
        }

        $payload = $response->getData(true);

        $user = User::query()
            ->where('company_id', $actor->company_id)
            ->where('email', $request->input('email'))
            ->with('role:id,role_name,role_code')
            ->first();

        return $this->ok([
            'user' => $user ? $this->format($user) : null,
            // Password awal ditampilkan sekali agar admin bisa menginformasikannya
            // bila email verifikasi tidak dikirim. User wajib menggantinya.
            'default_password' => $payload['password'] ?? null,
            'email_sent' => (bool) ($payload['email_sent'] ?? false),
        ], $payload['message'] ?? 'User berhasil ditambahkan.', 201);
    }

    /**
     * Body sama dengan store; password + password_confirmation opsional.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $actor = $request->user();
        $target = $this->findUser($actor, $id);

        if ($denied = $this->guardTarget($actor, $target)) {
            return $denied;
        }

        if ($denied = $this->guardRole($actor, $request->input('role_id'))) {
            return $denied;
        }

        if ((int) $target->id === (int) $actor->id) {
            if ((int) $request->input('role_id') !== (int) $target->role_id) {
                return $this->fail('Anda tidak dapat mengganti role akun sendiri.', 'CANNOT_MODIFY_SELF', 403);
            }

            if ((string) $request->input('status') !== '1') {
                return $this->fail('Anda tidak dapat menonaktifkan akun sendiri.', 'CANNOT_MODIFY_SELF', 403);
            }
        }

        if (
            $request->filled('phone')
            && User::query()
                ->where('company_id', $actor->company_id)
                ->where('phone', $request->input('phone'))
                ->whereKeyNot($target->id)
                ->exists()
        ) {
            return $this->fail('No. telepon sudah digunakan user lain.', 'VALIDATION_ERROR', 422, [
                'phone' => ['No. telepon sudah digunakan user lain.'],
            ]);
        }

        $request->merge(['id' => $target->id]);

        $response = $this->delegate(fn () => app(WebUserController::class)->update($request), 'USER_UPDATE_FAILED');

        if (!$this->succeeded($response)) {
            return $response;
        }

        $target->refresh()->load('role:id,role_name,role_code');

        return $this->ok(['user' => $this->format($target)], 'User berhasil diperbarui.');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $actor = $request->user();
        $target = $this->findUser($actor, $id);

        if ((int) $target->id === (int) $actor->id) {
            return $this->fail('Anda tidak dapat menghapus akun sendiri.', 'CANNOT_MODIFY_SELF', 403);
        }

        if ($denied = $this->guardTarget($actor, $target)) {
            return $denied;
        }

        if ($target->assets()->exists()) {
            return $this->fail(
                'User masih memegang aset. Kembalikan / pindahkan asetnya terlebih dahulu.',
                'USER_HAS_ASSETS'
            );
        }

        return $this->delegate(fn () => app(WebUserController::class)->destroy($target->id), 'USER_DELETE_FAILED');
    }

    /**
     * Role yang boleh dipilih user login saat menambah / mengubah user.
     */
    public function roles(Request $request): JsonResponse
    {
        $actor = $request->user();

        $roles = Role::query()
            ->where('company_id', $actor->company_id)
            ->where('status', 1)
            ->when(!$actor->isAdministrator(), fn ($q) => $q->where('role_code', '!=', 'administrator'))
            ->orderBy('role_name')
            ->get(['id', 'role_name', 'role_code'])
            ->map(fn (Role $r) => ['id' => $r->id, 'name' => $r->role_name, 'code' => $r->role_code]);

        return $this->ok(['roles' => $roles]);
    }

    protected function findUser(User $actor, int $id): User
    {
        return User::query()
            ->where('company_id', $actor->company_id)
            ->with('role')
            ->findOrFail($id);
    }

    protected function guardRole(User $actor, mixed $roleId): ?JsonResponse
    {
        if (!$roleId || $actor->isAdministrator()) {
            return null;
        }

        $isAdminRole = Role::query()
            ->where('company_id', $actor->company_id)
            ->whereKey((int) $roleId)
            ->where('role_code', 'administrator')
            ->exists();

        return $isAdminRole
            ? $this->fail('Hanya Administrator yang dapat memberikan role Administrator.', 'FORBIDDEN', 403)
            : null;
    }

    protected function guardTarget(User $actor, User $target): ?JsonResponse
    {
        if ($target->role?->role_code === 'administrator' && !$actor->isAdministrator()) {
            return $this->fail('Hanya Administrator yang dapat mengubah akun Administrator.', 'FORBIDDEN', 403);
        }

        return null;
    }

    protected function format(User $u): array
    {
        return [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'phone' => $u->phone,
            'nik' => $u->nik,
            'status' => (int) $u->status,
            'email_verified' => $u->email_verified_at !== null,
            'role' => $u->role ? [
                'id' => $u->role->id,
                'name' => $u->role->role_name,
                'code' => $u->role->role_code,
            ] : null,
        ];
    }
}
