<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\Location;
use App\Services\Asset\LocationService;
use App\Support\ActionFailed;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Master lokasi (ruangan / area) per cabang untuk aplikasi mobile.
 *
 * GET    /locations?branch_id=   daftar lokasi aktif (dropdown form aset, pindah lokasi, audit)
 * POST   /locations              tambah (location.create | asset.create | asset.move)
 * PUT    /locations/{id}         ubah (location.edit)
 * DELETE /locations/{id}         hapus (location.delete) — 422 IN_USE bila dipakai aset
 */
class LocationController extends ApiController
{
    public function __construct(
        protected LocationService $locations
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $rows = $this->locations->options($request->user(), $request->filled('branch_id') ? $request->integer('branch_id') : null);

        return $this->ok([
            'locations' => $rows->map(fn (Location $l) => $this->present($l))->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);

        try {
            $location = $this->locations->create($request->user(), $data);
        } catch (ActionFailed $e) {
            return $this->fail($e->getMessage(), $e->errorCode, $e->status);
        }

        return $this->ok(['location' => $this->present($location->load('branch'))], 'Lokasi ditambahkan.', 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $data = $this->validated($request);

        try {
            $location = $this->locations->find($request->user(), $id);
            $this->locations->update($request->user(), $location, $data);
        } catch (ActionFailed $e) {
            return $this->fail($e->getMessage(), $e->errorCode, $e->status);
        }

        return $this->ok(['location' => $this->present($location->fresh('branch'))], 'Lokasi diperbarui.');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            $location = $this->locations->find($request->user(), $id);
            $this->locations->delete($request->user(), $location);
        } catch (ActionFailed $e) {
            return $this->fail($e->getMessage(), $e->errorCode, $e->status);
        }

        return $this->ok(null, 'Lokasi dihapus.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'branch_id' => ['nullable', 'integer'],
            'description' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:0,1'],
        ], [
            'name.required' => 'Nama lokasi wajib diisi.',
        ]);
    }

    protected function present(Location $l): array
    {
        return [
            'id' => $l->id,
            'name' => $l->name,
            'description' => $l->description,
            'status' => (int) $l->status,
            'branch_id' => $l->branch_id,
            'branch_name' => $l->branch?->branch_name,
        ];
    }
}
