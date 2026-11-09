<?php

namespace App\Services\Branch;

use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\AssetLocationHistory;
use App\Models\Branch;
use App\Models\BranchTransfer;
use App\Models\BranchTransferItem;
use App\Models\Scopes\BranchScope;
use App\Models\User;
use App\Support\ActionFailed;
use App\Support\BranchAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Aturan mutasi aset antar-cabang (dipakai web & API mobile).
 *  1. Cabang asal membuat mutasi (status sent); aset masih milik cabang asal.
 *  2. Cabang tujuan menerima; aset pindah cabang & tercatat di riwayat lokasi.
 *  3. Selama belum diterima, cabang asal bisa membatalkan.
 */
class BranchTransferService
{
    public function __construct(
        protected BranchTransferNotifier $notifier
    ) {
    }

    /**
     * Mutasi yang boleh dilihat user (semua untuk kantor pusat; dari/ke cabangnya untuk user cabang).
     */
    public function visibleQuery(User $user): Builder
    {
        $restricted = BranchAccess::restrictedBranchId($user);

        return BranchTransfer::query()
            ->where('company_id', $user->company_id)
            ->when($restricted, fn ($q) => $q->where(fn ($w) => $w->where('from_branch_id', $restricted)->orWhere('to_branch_id', $restricted)));
    }

    public function find(User $user, int $id): BranchTransfer
    {
        $transfer = $this->visibleQuery($user)->find($id);

        if (!$transfer) {
            throw new ActionFailed('Mutasi tidak ditemukan.', 'NOT_FOUND', 404);
        }

        return $transfer;
    }

    /**
     * Aset cabang asal yang boleh dimutasi: belum dihapuskan & tidak sedang dalam mutasi lain.
     */
    public function movableAssets(int $companyId, int $branchId): Builder
    {
        $inTransit = BranchTransferItem::query()
            ->whereHas('transfer', fn ($q) => $q->where('company_id', $companyId)->where('status', BranchTransfer::STATUS_SENT))
            ->whereNotNull('asset_id')
            ->pluck('asset_id');

        return Asset::query()
            ->withoutGlobalScope(BranchScope::class)
            ->where('company_id', $companyId)
            ->where('branch_id', $branchId)
            ->inService()
            ->whereNotIn('id', $inTransit);
    }

    public function canReceive(User $user, BranchTransfer $transfer): bool
    {
        $restricted = BranchAccess::restrictedBranchId($user);

        return $transfer->isPending()
            && $user->hasPermission('branch.receive')
            && ($restricted === null || $restricted === (int) $transfer->to_branch_id);
    }

    public function canCancel(User $user, BranchTransfer $transfer): bool
    {
        $restricted = BranchAccess::restrictedBranchId($user);

        return $transfer->isPending()
            && $user->hasPermission('branch.transfer')
            && ($restricted === null || $restricted === (int) $transfer->from_branch_id);
    }

    /**
     * @param array<int> $assetIds
     */
    public function create(User $user, mixed $fromBranchId, mixed $toBranchId, array $assetIds, ?string $notes = null): BranchTransfer
    {
        $companyId = (int) $user->company_id;
        $assetIds = array_values(array_unique(array_map('intval', $assetIds)));

        if (!$assetIds) {
            throw new ActionFailed('Pilih minimal satu aset yang akan dikirim.');
        }

        if (count($assetIds) > 200) {
            throw new ActionFailed('Maksimal 200 aset per mutasi.');
        }

        $from = BranchAccess::find($companyId, $fromBranchId, $user);
        $to = Branch::query()->where('company_id', $companyId)->where('status', 1)->find((int) $toBranchId);

        if (!$from || !$to) {
            throw new ActionFailed('Cabang asal atau tujuan tidak valid.');
        }

        if ((int) $from->id === (int) $to->id) {
            throw new ActionFailed('Cabang tujuan harus berbeda dengan cabang asal.');
        }

        $assets = $this->movableAssets($companyId, $from->id)->whereIn('id', $assetIds)->get();

        if ($assets->count() !== count($assetIds)) {
            throw new ActionFailed('Sebagian aset tidak bisa dikirim (sudah pindah cabang, dihapuskan, atau sedang dalam mutasi lain). Muat ulang lalu pilih lagi.');
        }

        $transfer = DB::transaction(function () use ($companyId, $from, $to, $assets, $notes, $user) {
            $transfer = BranchTransfer::create([
                'company_id' => $companyId,
                'transfer_no' => $this->nextNumber($companyId),
                'from_branch_id' => $from->id,
                'to_branch_id' => $to->id,
                'status' => BranchTransfer::STATUS_SENT,
                'notes' => $notes !== null && trim($notes) !== '' ? trim($notes) : null,
                'sent_at' => now(),
                'sent_by' => $user->id,
            ]);

            foreach ($assets as $asset) {
                $transfer->items()->create([
                    'asset_id' => $asset->id,
                    'asset_code' => $asset->asset_code,
                    'asset_name' => $asset->asset_name,
                    'from_location' => $asset->location,
                ]);
            }

            return $transfer;
        });

        $this->notifier->sent($transfer);

        return $transfer;
    }

    /**
     * @return array{moved:int, skipped:int}
     */
    public function receive(User $user, BranchTransfer $transfer, ?string $location = null, ?string $notes = null): array
    {
        if (!$transfer->isPending()) {
            throw new ActionFailed('Mutasi ini sudah ' . strtolower($transfer->statusLabel()) . '.', 'INVALID_STATUS');
        }

        if (!$this->canReceive($user, $transfer)) {
            throw new ActionFailed('Hanya cabang tujuan yang dapat menerima mutasi ini.', 'FORBIDDEN', 403);
        }

        $newLocation = trim((string) $location);
        $transfer->load(['fromBranch', 'toBranch', 'items']);

        $moved = DB::transaction(function () use ($transfer, $user, $newLocation, $notes) {
            $moved = 0;

            foreach ($transfer->items as $item) {
                $asset = Asset::withoutGlobalScope(BranchScope::class)
                    ->where('company_id', $transfer->company_id)
                    ->lockForUpdate()
                    ->find($item->asset_id);

                // Aset yang sudah dihapus / dihapuskan / pindah cabang lain dilewati.
                if (!$asset || $asset->isDisposed() || (int) $asset->branch_id !== (int) $transfer->from_branch_id) {
                    continue;
                }

                $fromLoc = $asset->location;
                $toLoc = $newLocation !== '' ? $newLocation : $fromLoc;

                AssetLocationHistory::create([
                    'company_id' => $asset->company_id,
                    'asset_id' => $asset->id,
                    'from_location' => $this->branchLocation($transfer->fromBranch, $fromLoc),
                    'to_location' => $this->branchLocation($transfer->toBranch, $toLoc),
                    'moved_at' => today(),
                    'notes' => 'Mutasi cabang ' . $transfer->transfer_no,
                    'created_by' => $user->id,
                ]);

                $changes = ['branch_id' => $transfer->to_branch_id, 'location' => $toLoc];

                // Pemegang lama dari cabang lain otomatis dilepas (aset hanya
                // boleh dipegang user di cabang aset). Tercatat sebagai pengembalian.
                if ($asset->responsible_user_id) {
                    $holderBranch = User::query()->whereKey($asset->responsible_user_id)->value('branch_id');

                    if ((int) $holderBranch !== (int) $transfer->to_branch_id) {
                        $this->releaseHolder($asset, $user, $transfer);
                        $changes['responsible_user_id'] = null;
                    }
                }

                $asset->update($changes);
                $moved++;
            }

            $transfer->update([
                'status' => BranchTransfer::STATUS_RECEIVED,
                'received_at' => now(),
                'received_by' => $user->id,
                'receive_notes' => $notes !== null && trim($notes) !== '' ? trim($notes) : null,
            ]);

            return $moved;
        });

        $this->notifier->received($transfer->fresh());

        return ['moved' => $moved, 'skipped' => $transfer->items->count() - $moved];
    }

    public function cancel(User $user, BranchTransfer $transfer): void
    {
        if (!$transfer->isPending()) {
            throw new ActionFailed('Mutasi yang sudah diterima atau dibatalkan tidak bisa dibatalkan lagi.', 'INVALID_STATUS');
        }

        if (!$this->canCancel($user, $transfer)) {
            throw new ActionFailed('Hanya cabang pengirim yang dapat membatalkan mutasi ini.', 'FORBIDDEN', 403);
        }

        $transfer->update([
            'status' => BranchTransfer::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'cancelled_by' => $user->id,
        ]);

        $this->notifier->cancelled($transfer);
    }

    public function nextNumber(int $companyId): string
    {
        $prefix = 'MUT-' . now()->format('ym') . '-';

        $last = BranchTransfer::query()
            ->where('company_id', $companyId)
            ->where('transfer_no', 'like', $prefix . '%')
            ->orderByDesc('transfer_no')
            ->value('transfer_no');

        $n = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix . str_pad((string) $n, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Tutup serah terima aktif & catat pengembalian (sama seperti tombol "Kembalikan").
     */
    protected function releaseHolder(Asset $asset, User $by, BranchTransfer $transfer): void
    {
        $now = now();

        $active = AssetAssignment::query()
            ->where('asset_id', $asset->id)
            ->whereNull('end_at')
            ->latest('start_at')
            ->first();

        $active?->update(['end_at' => $now]);

        AssetAssignment::create([
            'asset_id' => $asset->id,
            'user_id' => $active?->user_id ?? $asset->responsible_user_id,
            'start_at' => $now,
            'end_at' => null,
            'assignment_type' => 'return',
            'reason' => 'Mutasi cabang ' . $transfer->transfer_no,
            'notes' => 'Otomatis dikembalikan karena aset pindah ke ' . ($transfer->toBranch?->branch_name ?? 'cabang lain') . '.',
            'created_by' => $by->id,
        ]);
    }

    protected function branchLocation(?Branch $branch, ?string $location): string
    {
        $name = $branch?->branch_name ?? 'Cabang';

        return $location ? $name . ' · ' . $location : $name;
    }
}
