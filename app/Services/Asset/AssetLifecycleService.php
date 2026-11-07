<?php

namespace App\Services\Asset;

use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\AssetDisposal;
use App\Models\AssetLocationHistory;
use App\Models\Maintenance;
use App\Models\MaintenanceRequest;
use App\Models\MaintenanceRequestLog;
use App\Models\User;
use App\Services\CompanyStorage;
use App\Support\AssetValuation;
use Carbon\CarbonInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Pindah lokasi & penghapusan aset. Dipakai dashboard web dan API mobile
 * agar aturan dan riwayatnya selalu sama.
 */
class AssetLifecycleService
{
    /*
    |--------------------------------------------------------------------------
    | PINDAH LOKASI
    |--------------------------------------------------------------------------
    */

    public function moveLocation(Asset $asset, string $to, CarbonInterface|string|null $movedAt, ?string $notes, User $by): AssetLocationHistory
    {
        $this->ensureNotDisposed($asset);

        $to = trim(preg_replace('/\s+/', ' ', $to));
        $from = $asset->location !== null ? trim($asset->location) : null;

        if ($to === '') {
            throw ValidationException::withMessages(['location' => 'Lokasi baru wajib diisi.']);
        }

        if ($from !== null && mb_strtolower($from) === mb_strtolower($to)) {
            throw ValidationException::withMessages(['location' => 'Lokasi baru sama dengan lokasi saat ini.']);
        }

        $date = $movedAt ? Carbon::parse($movedAt)->startOfDay() : today();

        if ($date->isFuture()) {
            throw ValidationException::withMessages(['moved_at' => 'Tanggal pindah tidak boleh di masa depan.']);
        }

        return DB::transaction(function () use ($asset, $from, $to, $date, $notes, $by) {
            $history = AssetLocationHistory::create([
                'company_id' => $asset->company_id,
                'asset_id' => $asset->id,
                'from_location' => $from !== '' ? $from : null,
                'to_location' => $to,
                'moved_at' => $date,
                'notes' => $notes !== null && trim($notes) !== '' ? trim($notes) : null,
                'created_by' => $by->id,
            ]);

            $asset->update(['location' => $to]);

            return $history;
        });
    }

    /**
     * Daftar lokasi yang pernah dipakai perusahaan (untuk saran isian).
     *
     * @return array<int, string>
     */
    public function knownLocations(int $companyId, int $limit = 200): array
    {
        return Asset::query()
            ->where('company_id', $companyId)
            ->whereNotNull('location')
            ->where('location', '!=', '')
            ->select('location')
            ->groupBy('location')
            ->orderByRaw('COUNT(*) DESC')
            ->limit($limit)
            ->pluck('location')
            ->map(fn ($l) => trim($l))
            ->unique(fn ($l) => mb_strtolower($l))
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    /*
    |--------------------------------------------------------------------------
    | PENGHAPUSAN ASET
    |--------------------------------------------------------------------------
    */

    /**
     * Perkiraan nilai saat penghapusan (untuk pratinjau & penyimpanan).
     *
     * @return array{purchase_price: ?float, book_value: ?float, depreciated: ?float, proceeds: float, gain_loss: ?float}
     */
    public function valuation(Asset $asset, CarbonInterface|string|null $date, float $proceeds = 0): array
    {
        $at = $date ? Carbon::parse($date) : now();
        $price = $asset->purchase_price !== null ? (float) $asset->purchase_price : null;
        $book = AssetValuation::bookValue($asset, $at);

        // Tanpa data penyusutan, nilai buku = harga perolehan (sama seperti laporan).
        $bookValue = $book['value'] ?? $price;

        return [
            'purchase_price' => $price,
            'book_value' => $bookValue,
            'depreciated' => $book['depreciated'] ?? null,
            'proceeds' => $proceeds,
            'gain_loss' => $bookValue !== null ? round($proceeds - $bookValue, 2) : null,
        ];
    }

    /**
     * @param  array{disposal_type: string, disposal_date: string, proceeds?: float|int|string|null, notes?: ?string}  $data
     */
    public function dispose(Asset $asset, array $data, ?UploadedFile $document, User $by): AssetDisposal
    {
        $this->ensureNotDisposed($asset);

        $type = $data['disposal_type'];

        if (!array_key_exists($type, AssetDisposal::TYPES)) {
            throw ValidationException::withMessages(['disposal_type' => 'Alasan penghapusan tidak valid.']);
        }

        $date = Carbon::parse($data['disposal_date'])->startOfDay();

        if ($date->isFuture()) {
            throw ValidationException::withMessages(['disposal_date' => 'Tanggal penghapusan tidak boleh di masa depan.']);
        }

        if ($asset->purchase_date && $date->lt($asset->purchase_date->copy()->startOfDay())) {
            throw ValidationException::withMessages(['disposal_date' => 'Tanggal penghapusan tidak boleh sebelum tanggal pembelian (' . $asset->purchase_date->translatedFormat('d M Y') . ').']);
        }

        $proceeds = max(0, (float) ($data['proceeds'] ?? 0));
        $notes = isset($data['notes']) && trim((string) $data['notes']) !== '' ? trim((string) $data['notes']) : null;

        if ($type === 'other' && $notes === null) {
            throw ValidationException::withMessages(['notes' => 'Jelaskan alasan penghapusan pada catatan.']);
        }

        $value = $this->valuation($asset, $date, $proceeds);
        [$documentPath, $documentName] = $this->storeDocument($asset, $document);

        try {
            return DB::transaction(function () use ($asset, $type, $date, $notes, $value, $documentPath, $documentName, $by) {
                $asset = Asset::query()->whereKey($asset->id)->lockForUpdate()->firstOrFail();
                $this->ensureNotDisposed($asset);

                $disposal = AssetDisposal::create([
                    'company_id' => $asset->company_id,
                    'asset_id' => $asset->id,
                    'disposal_type' => $type,
                    'disposal_date' => $date,
                    'purchase_price' => $value['purchase_price'],
                    'book_value' => $value['book_value'],
                    'proceeds' => $value['proceeds'],
                    'gain_loss' => $value['gain_loss'],
                    'holder_user_id' => $asset->responsible_user_id,
                    'previous_location' => $asset->location,
                    'previous_status' => in_array((int) $asset->status, [Asset::STATUS_INACTIVE, Asset::STATUS_ACTIVE], true) ? (int) $asset->status : Asset::STATUS_ACTIVE,
                    'notes' => $notes,
                    'document_path' => $documentPath,
                    'document_name' => $documentName,
                    'created_by' => $by->id,
                ]);

                $reason = 'Aset dihapuskan: ' . $disposal->typeLabel();

                $this->closeAssignment($asset, $date, $reason, $by);
                $this->closeMaintenance($asset, $reason, $by);

                $asset->update([
                    'status' => Asset::STATUS_DISPOSED,
                    'disposed_at' => $date,
                    'responsible_user_id' => null,
                    'next_maintenance_date' => null,
                ]);

                return $disposal;
            });
        } catch (\Throwable $e) {
            // Transaksi gagal: jangan tinggalkan file berita acara tanpa data.
            if ($documentPath) {
                @unlink(storage_path('app/public/' . $documentPath));
            }

            throw $e;
        }
    }

    /**
     * Batalkan penghapusan (mis. salah input). Aset kembali ke status sebelumnya.
     * Pemegang & jadwal maintenance tidak dipulihkan otomatis (atur ulang manual).
     */
    public function cancelDisposal(AssetDisposal $disposal, string $reason, User $by): Asset
    {
        if ($disposal->isCancelled()) {
            throw ValidationException::withMessages(['disposal' => 'Penghapusan ini sudah dibatalkan sebelumnya.']);
        }

        return DB::transaction(function () use ($disposal, $reason, $by) {
            $asset = Asset::query()->whereKey($disposal->asset_id)->lockForUpdate()->firstOrFail();

            $disposal->update([
                'cancelled_at' => now(),
                'cancelled_by' => $by->id,
                'cancel_reason' => Str::limit(trim($reason), 490, ''),
            ]);

            $asset->update([
                'status' => $disposal->previous_status ?? Asset::STATUS_ACTIVE,
                'disposed_at' => null,
            ]);

            return $asset;
        });
    }

    public function ensureNotDisposed(Asset $asset): void
    {
        if ($asset->isDisposed()) {
            throw ValidationException::withMessages([
                'asset' => 'Aset ini sudah dihapuskan sehingga tidak dapat diubah. Batalkan penghapusan terlebih dahulu bila diperlukan.',
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | INTERNAL
    |--------------------------------------------------------------------------
    */

    /** Pemegang terakhir dicatat mengembalikan aset (riwayat tidak ditimpa). */
    protected function closeAssignment(Asset $asset, CarbonInterface $date, string $reason, User $by): void
    {
        if (!$asset->responsible_user_id) {
            return;
        }

        $active = AssetAssignment::query()
            ->where('asset_id', $asset->id)
            ->whereNull('end_at')
            ->where('assignment_type', '!=', 'return')
            ->latest('start_at')
            ->first();

        $endAt = $date->copy()->setTimeFrom(now());

        if ($active) {
            $active->update(['end_at' => $endAt]);
        }

        AssetAssignment::create([
            'asset_id' => $asset->id,
            'user_id' => $active?->user_id ?? $asset->responsible_user_id,
            'start_at' => $endAt,
            'end_at' => null,
            'assignment_type' => 'return',
            'reason' => Str::limit($reason, 250, ''),
            'notes' => null,
            'created_by' => $by->id,
        ]);
    }

    /** Jadwal maintenance & request yang masih terbuka dibatalkan. */
    protected function closeMaintenance(Asset $asset, string $reason, User $by): void
    {
        Maintenance::query()
            ->where('asset_id', $asset->id)
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->update(['status' => 'cancelled', 'updated_at' => now()]);

        MaintenanceRequest::query()
            ->where('asset_id', $asset->id)
            ->whereIn('status', ['pending', 'in_progress'])
            ->get()
            ->each(function (MaintenanceRequest $request) use ($reason, $by) {
                $request->update(['status' => 'cancelled']);

                MaintenanceRequestLog::create([
                    'maintenance_request_id' => $request->id,
                    'user_id' => $by->id,
                    'note' => 'Dibatalkan otomatis. ' . $reason . '.',
                ]);
            });
    }

    /**
     * Berita acara / bukti (PDF/JPG/PNG) disimpan bersama dokumen perusahaan
     * sehingga ikut dihitung dalam kuota storage.
     *
     * @return array{0: ?string, 1: ?string}
     */
    protected function storeDocument(Asset $asset, ?UploadedFile $file): array
    {
        if (!$file) {
            return [null, null];
        }

        $check = CompanyStorage::checkUpload((int) $asset->company_id, [$file]);

        if (!$check['allowed']) {
            throw ValidationException::withMessages(['document' => $check['message']]);
        }

        $companyCode = $asset->company?->company_code ?: 'company-' . $asset->company_id;
        $folder = 'documents/' . $companyCode . '/assets/disposal/' . now()->format('Y/m');
        $name = Str::slug($asset->asset_code ?: 'asset') . '-' . Str::uuid() . '.' . strtolower($file->getClientOriginalExtension() ?: 'pdf');

        $target = storage_path('app/public/' . $folder);

        if (!is_dir($target)) {
            mkdir($target, 0755, true);
        }

        $file->move($target, $name);

        return [$folder . '/' . $name, Str::limit($file->getClientOriginalName(), 250, '')];
    }
}
