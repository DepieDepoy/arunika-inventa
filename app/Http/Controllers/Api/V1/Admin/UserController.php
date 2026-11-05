<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\Asset;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Daftar & detail user perusahaan (permission user.view).
 * Tambah/ubah user tetap lewat dashboard web untuk saat ini.
 */
class UserController extends ApiController
{
    /**
     * Query: search (nama/email/telepon/NIK), status (0|1), role_id, per_page.
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::query()
            ->where('company_id', $request->user()->company_id)
            ->with('role:id,role_name,role_code')
            ->withCount('assets')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->integer('status')))
            ->when($request->filled('role_id'), fn ($q) => $q->where('role_id', $request->integer('role_id')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%' . trim((string) $request->query('search')) . '%';
                $q->where(fn ($w) => $w
                    ->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('phone', 'like', $term)
                    ->orWhere('nik', 'like', $term));
            })
            ->orderBy('name');

        return $this->paginated('users', $query->paginate($this->perPage($request)), fn (User $u) => $this->format($u));
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $user = User::query()
            ->where('company_id', $companyId)
            ->with('role:id,role_name,role_code')
            ->findOrFail($id);

        $assets = Asset::query()
            ->where('company_id', $companyId)
            ->where('responsible_user_id', $user->id)
            ->with('category:id,category_name')
            ->orderBy('asset_name')
            ->limit(100)
            ->get(['id', 'asset_code', 'asset_name', 'category_id', 'location'])
            ->map(fn ($a) => [
                'id' => $a->id,
                'asset_code' => $a->asset_code,
                'asset_name' => $a->asset_name,
                'category' => $a->category?->category_name,
                'location' => $a->location,
            ]);

        return $this->ok([
            'user' => $this->format($user) + [
                'last_login_at' => $this->dateTime($user->last_login_at),
                'created_at' => $this->dateTime($user->created_at),
            ],
            'assets' => $assets,
        ]);
    }

    protected function format(User $u): array
    {
        return [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'phone' => $u->phone,
            'nik' => $u->nik,
            'photo_url' => $u->photo ? asset('storage/' . ltrim($u->photo, '/')) : null,
            'status' => (int) $u->status,
            'email_verified' => $u->email_verified_at !== null,
            'role' => $u->role ? [
                'id' => $u->role->id,
                'name' => $u->role->role_name,
                'code' => $u->role->role_code,
            ] : null,
            'assets_count' => $u->assets_count ?? null,
        ];
    }
}
