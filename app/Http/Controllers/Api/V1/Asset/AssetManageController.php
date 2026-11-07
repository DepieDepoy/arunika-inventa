<?php

namespace App\Http\Controllers\Api\V1\Asset;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Controllers\Dashboard\AssetController as WebAssetController;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\AssetLocationHistory;
use App\Models\User;
use App\Services\Asset\AssetLifecycleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Tambah / ubah / hapus aset dan penugasan (assign, transfer, return) dari mobile.
 *
 * Tambah/ubah/hapus memakai controller web (multipart/form-data) sehingga
 * validasi, kuota paket, kuota storage, kode aset, upload foto & invoice,
 * serta jadwal maintenance identik dengan dashboard.
 */
class AssetManageController extends ApiController
{
    /**
     * multipart/form-data — field sama dengan form web:
     * asset_name, category_id, sub_category_id?, vendor_id, responsible_user_id?,
     * brand?, model?, serial_number?, description?, asset_condition?, location?,
     * purchase_date?, purchase_price?, purchase_invoice?, warranty_start?, warranty_end?,
     * depreciation_method?, useful_life?, residual_value?, depreciation_start_date?,
     * maintenance_required (0/1) + maintenance_* bila 1, status (0/1),
     * asset_photos[] (maks 3, jpg/png ≤5MB), invoice_documents[] (maks 5, pdf/jpg/png ≤10MB).
     */
    public function store(Request $request, WebAssetController $web, AssetController $api): JsonResponse
    {
        $response = $this->delegate(fn () => $web->store($request), 'ASSET_INVALID');

        $payload = $response->getData(true);

        if (!$payload['success']) {
            return $response;
        }

        $detail = $api->show($request, (int) $payload['data']['id'])->getData(true);

        return $this->ok($detail['data'], 'Aset berhasil ditambahkan.', 201);
    }

    /**
     * multipart/form-data dengan seluruh field seperti store (kirim ulang nilai lama),
     * ditambah delete_images[] (id foto) & delete_invoice_documents[] (id dokumen).
     * Field jadwal maintenance tidak diubah lewat endpoint ini.
     *
     * Catatan: Flutter memakai POST + `_method=PUT` untuk multipart.
     */
    public function update(Request $request, WebAssetController $web, AssetController $api, int $id): JsonResponse
    {
        $this->findActiveAsset($request, $id);

        $request->merge(['id' => $id]);

        $response = $this->delegate(fn () => $web->update($request), 'ASSET_INVALID');

        if (!$response->getData(true)['success']) {
            return $response;
        }

        $detail = $api->show($request, $id)->getData(true);

        return $this->ok($detail['data'], 'Aset berhasil diperbarui.');
    }

    public function destroy(Request $request, WebAssetController $web, int $id): JsonResponse
    {
        $this->findAsset($request, $id);

        $request->merge(['id' => $id]);

        return $this->delegate(fn () => $web->destroy($request), 'ASSET_DELETE_FAILED');
    }

    /*
    |--------------------------------------------------------------------------
    | PENUGASAN ASET
    |--------------------------------------------------------------------------
    */

    public function assignments(Request $request, int $id): JsonResponse
    {
        $asset = $this->findAsset($request, $id)->load('responsibleUser:id,name,email');

        $history = AssetAssignment::query()
            ->where('asset_id', $asset->id)
            ->with(['user:id,name,email', 'creator:id,name'])
            ->orderByDesc('start_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (AssetAssignment $a) => [
                'id' => $a->id,
                'type' => $a->assignment_type,
                'user' => $a->user ? ['id' => $a->user->id, 'name' => $a->user->name, 'email' => $a->user->email] : null,
                'start_at' => $this->dateTime($a->start_at),
                'end_at' => $this->dateTime($a->end_at),
                'is_current' => $a->end_at === null && $a->assignment_type !== 'return',
                'reason' => $a->reason,
                'notes' => $a->notes,
                'created_by' => $a->creator?->name,
            ]);

        return $this->ok([
            'asset' => [
                'id' => $asset->id,
                'asset_code' => $asset->asset_code,
                'asset_name' => $asset->asset_name,
            ],
            'current_user' => $asset->responsibleUser ? [
                'id' => $asset->responsibleUser->id,
                'name' => $asset->responsibleUser->name,
                'email' => $asset->responsibleUser->email,
            ] : null,
            'history' => $history,
        ]);
    }

    /**
     * Berikan / pindahkan aset ke user. Body: user_id, start_at?, reason?, notes?
     */
    public function assign(Request $request, int $id): JsonResponse
    {
        $authUser = $request->user();
        $asset = $this->findActiveAsset($request, $id);

        $validated = $request->validate([
            'user_id' => ['required', 'integer'],
            'start_at' => ['nullable', 'date'],
            'reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'user_id.required' => 'User penerima wajib dipilih.',
        ]);

        $target = User::query()
            ->where('company_id', $authUser->company_id)
            ->where('status', 1)
            ->find($validated['user_id']);

        if (!$target) {
            return $this->fail('User penerima tidak ditemukan atau tidak aktif.', 'USER_NOT_FOUND', 422, [
                'user_id' => ['User penerima tidak ditemukan atau tidak aktif.'],
            ]);
        }

        if ((int) $asset->responsible_user_id === (int) $target->id) {
            return $this->fail('Aset sudah dipegang oleh user tersebut.', 'ALREADY_ASSIGNED');
        }

        $startAt = $validated['start_at'] ?? now();

        $type = DB::transaction(function () use ($asset, $target, $validated, $authUser, $startAt) {
            $hasCurrent = AssetAssignment::query()
                ->where('asset_id', $asset->id)
                ->whereNull('end_at')
                ->exists();

            if ($hasCurrent) {
                AssetAssignment::query()
                    ->where('asset_id', $asset->id)
                    ->whereNull('end_at')
                    ->update(['end_at' => $startAt, 'updated_at' => now()]);
            }

            $type = $hasCurrent ? 'transfer' : 'assign';

            AssetAssignment::create([
                'asset_id' => $asset->id,
                'user_id' => $target->id,
                'start_at' => $startAt,
                'end_at' => null,
                'assignment_type' => $type,
                'reason' => $validated['reason'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'created_by' => $authUser->id,
            ]);

            $asset->update(['responsible_user_id' => $target->id]);

            return $type;
        });

        return $this->ok(
            ['assignment_type' => $type, 'user' => ['id' => $target->id, 'name' => $target->name]],
            $type === 'transfer'
                ? 'Aset berhasil dipindahkan ke ' . $target->name . '.'
                : 'Aset berhasil diberikan ke ' . $target->name . '.'
        );
    }

    /**
     * Kembalikan aset (menjadi tanpa penanggung jawab). Body: end_at?, reason?, notes?
     */
    public function returnAsset(Request $request, int $id): JsonResponse
    {
        $authUser = $request->user();
        $asset = $this->findActiveAsset($request, $id);

        $validated = $request->validate([
            'end_at' => ['nullable', 'date'],
            'reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if (!$asset->responsible_user_id) {
            return $this->fail('Aset saat ini tidak memiliki penanggung jawab.', 'NOT_ASSIGNED');
        }

        $endAt = $validated['end_at'] ?? now();

        DB::transaction(function () use ($asset, $validated, $authUser, $endAt) {
            $active = AssetAssignment::query()
                ->where('asset_id', $asset->id)
                ->whereNull('end_at')
                ->latest('start_at')
                ->first()
                ?? AssetAssignment::query()
                    ->where('asset_id', $asset->id)
                    ->where('user_id', $asset->responsible_user_id)
                    ->latest('start_at')
                    ->first();

            if ($active) {
                $active->update(['end_at' => $endAt]);

                // Sama dengan web: catatan pengembalian sebagai baris riwayat.
                AssetAssignment::create([
                    'asset_id' => $asset->id,
                    'user_id' => $active->user_id,
                    'start_at' => $endAt,
                    'end_at' => null,
                    'assignment_type' => 'return',
                    'reason' => $validated['reason'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                    'created_by' => $authUser->id,
                ]);
            }

            $asset->update(['responsible_user_id' => null]);
        });

        return $this->ok(null, 'Aset berhasil dikembalikan.');
    }

    /*
    |--------------------------------------------------------------------------
    | LOKASI ASET
    |--------------------------------------------------------------------------
    */

    /** Riwayat pindah lokasi + daftar lokasi yang pernah dipakai (untuk saran isian). */
    public function locationHistory(Request $request, AssetLifecycleService $lifecycle, int $id): JsonResponse
    {
        $asset = $this->findAsset($request, $id);

        $history = AssetLocationHistory::query()
            ->where('asset_id', $asset->id)
            ->with('creator:id,name')
            ->orderByDesc('moved_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (AssetLocationHistory $h) => [
                'id' => $h->id,
                'from' => $h->from_location,
                'to' => $h->to_location,
                'moved_at' => $h->moved_at->toDateString(),
                'notes' => $h->notes,
                'created_by' => $h->creator?->name,
            ]);

        return $this->ok([
            'asset' => [
                'id' => $asset->id,
                'asset_code' => $asset->asset_code,
                'asset_name' => $asset->asset_name,
                'location' => $asset->location,
                'is_disposed' => $asset->isDisposed(),
            ],
            'history' => $history,
            'known_locations' => $lifecycle->knownLocations((int) $asset->company_id),
        ]);
    }

    /** Pindah lokasi. Body: location, moved_at? (Y-m-d, default hari ini), notes? */
    public function moveLocation(Request $request, AssetLifecycleService $lifecycle, int $id): JsonResponse
    {
        $asset = $this->findActiveAsset($request, $id);

        $validated = $request->validate([
            'location' => ['required', 'string', 'max:255'],
            'moved_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'location.required' => 'Lokasi baru wajib diisi.',
        ]);

        $history = $lifecycle->moveLocation(
            $asset,
            $validated['location'],
            $validated['moved_at'] ?? null,
            $validated['notes'] ?? null,
            $request->user()
        );

        return $this->ok([
            'location' => $history->to_location,
            'history' => [
                'id' => $history->id,
                'from' => $history->from_location,
                'to' => $history->to_location,
                'moved_at' => $history->moved_at->toDateString(),
                'notes' => $history->notes,
            ],
        ], 'Lokasi aset dipindahkan ke "' . $history->to_location . '".');
    }

    protected function findAsset(Request $request, int $id): Asset
    {
        return Asset::query()
            ->where('company_id', $request->user()->company_id)
            ->findOrFail($id);
    }

    /** Aset dihapuskan tidak boleh diubah, diserahkan, atau dikembalikan. */
    protected function findActiveAsset(Request $request, int $id): Asset
    {
        $asset = $this->findAsset($request, $id);

        if ($asset->isDisposed()) {
            abort(response()->json([
                'success' => false,
                'message' => 'Aset ini sudah dihapuskan sehingga tidak dapat diubah.',
                'code' => 'ASSET_DISPOSED',
            ], 422));
        }

        return $asset;
    }
}
