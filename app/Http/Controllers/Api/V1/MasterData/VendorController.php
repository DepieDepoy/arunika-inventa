<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Controllers\Dashboard\VendorController as WebVendorController;
use App\Models\Vendor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Kelola vendor. Simpan/ubah/hapus memakai controller web (kode VD-01, dst.).
 * Untuk dropdown form cukup gunakan GET /lookups/vendors (hanya yang aktif).
 */
class VendorController extends ApiController
{
    /**
     * Query: search (nama/kode/PIC/telepon), status (0|1), per_page.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Vendor::query()
            ->where('company_id', $request->user()->company_id)
            ->withCount('assets')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->integer('status')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%' . trim((string) $request->query('search')) . '%';
                $q->where(fn ($w) => $w
                    ->where('vendor_name', 'like', $term)
                    ->orWhere('vendor_code', 'like', $term)
                    ->orWhere('pic_name', 'like', $term)
                    ->orWhere('phone', 'like', $term));
            })
            ->orderBy('vendor_name');

        return $this->paginated('vendors', $query->paginate($this->perPage($request)), fn (Vendor $v) => $this->format($v));
    }

    public function show(Request $request, int $id): JsonResponse
    {
        return $this->ok(['vendor' => $this->format($this->findVendor($request, $id)->loadCount('assets'))]);
    }

    /**
     * Body: vendor_name, address, email, status (0|1), phone, pic_name, description.
     */
    public function store(Request $request): JsonResponse
    {
        $response = $this->delegate(fn () => app(WebVendorController::class)->store($request), 'VENDOR_CREATE_FAILED');

        if (!$this->succeeded($response)) {
            return $response;
        }

        $vendor = Vendor::query()
            ->where('company_id', $request->user()->company_id)
            ->latest('id')
            ->first()
            ?->loadCount('assets');

        return $this->ok(['vendor' => $vendor ? $this->format($vendor) : null], 'Vendor berhasil ditambahkan.', 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $vendor = $this->findVendor($request, $id);
        $request->merge(['id' => $vendor->id]);

        $response = $this->delegate(fn () => app(WebVendorController::class)->update($request), 'VENDOR_UPDATE_FAILED');

        if (!$this->succeeded($response)) {
            return $response;
        }

        return $this->ok(['vendor' => $this->format($vendor->refresh()->loadCount('assets'))], 'Vendor berhasil diperbarui.');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $vendor = $this->findVendor($request, $id);

        $response = $this->delegate(fn () => app(WebVendorController::class)->destroy($vendor->id), 'VENDOR_DELETE_FAILED');

        if (!$this->succeeded($response)) {
            return $response;
        }

        return $this->ok(null, 'Vendor berhasil dihapus.');
    }

    protected function findVendor(Request $request, int $id): Vendor
    {
        return Vendor::query()->where('company_id', $request->user()->company_id)->findOrFail($id);
    }

    protected function format(Vendor $v): array
    {
        return [
            'id' => $v->id,
            'code' => $v->vendor_code,
            'name' => $v->vendor_name,
            'address' => $v->address,
            'phone' => $v->phone,
            'pic_name' => $v->pic_name,
            'email' => $v->email,
            'description' => $v->description,
            'status' => (int) $v->status,
            'assets_count' => $v->assets_count ?? null,
        ];
    }
}
