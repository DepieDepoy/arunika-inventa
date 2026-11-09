<?php

namespace App\Services\Branch;

use App\Models\BranchTransfer;
use App\Models\User;
use App\Notifications\GlobalNotification;
use App\Support\NotificationText;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Notifikasi mutasi aset antar-cabang (lonceng web + push HP).
 *  - dikirim  -> penerima di cabang tujuan (izin branch.receive)
 *  - diterima -> pengirim
 *  - dibatalkan -> penerima di cabang tujuan
 */
class BranchTransferNotifier
{
    public const TYPE = 'branch_transfer';

    public function sent(BranchTransfer $transfer): void
    {
        $this->safely(function () use ($transfer) {
            $transfer->loadMissing(['fromBranch', 'toBranch', 'sender']);
            $count = $transfer->items()->count();

            foreach ($this->receivers($transfer) as $user) {
                $user->notify($this->make(
                    $transfer,
                    'sent',
                    NotificationText::make('Mutasi aset masuk: :no', ['no' => $transfer->transfer_no]),
                    NotificationText::make(':name mengirim :count aset dari :from ke :to. Konfirmasi setelah aset diterima.', [
                        'name' => $transfer->sender?->name ?? 'Pengguna',
                        'count' => $count,
                        'from' => $transfer->fromBranch?->branch_name,
                        'to' => $transfer->toBranch?->branch_name,
                    ])
                ));
            }
        });
    }

    public function received(BranchTransfer $transfer): void
    {
        $this->safely(function () use ($transfer) {
            $transfer->loadMissing(['toBranch', 'sender', 'receiver']);

            if (!$transfer->sender || (int) $transfer->sent_by === (int) $transfer->received_by) {
                return;
            }

            $transfer->sender->notify($this->make(
                $transfer,
                'received',
                NotificationText::make('Mutasi :no sudah diterima', ['no' => $transfer->transfer_no]),
                NotificationText::make(':name di :to sudah menerima aset yang Anda kirim.', [
                    'name' => $transfer->receiver?->name ?? 'Pengguna',
                    'to' => $transfer->toBranch?->branch_name,
                ])
            ));
        });
    }

    public function cancelled(BranchTransfer $transfer): void
    {
        $this->safely(function () use ($transfer) {
            $transfer->loadMissing(['fromBranch']);

            foreach ($this->receivers($transfer) as $user) {
                $user->notify($this->make(
                    $transfer,
                    'cancelled',
                    NotificationText::make('Mutasi :no dibatalkan', ['no' => $transfer->transfer_no]),
                    NotificationText::make('Pengiriman aset dari :from dibatalkan oleh pengirim.', [
                        'from' => $transfer->fromBranch?->branch_name,
                    ])
                ));
            }
        });
    }

    /**
     * User cabang tujuan yang berhak menerima mutasi. Bila tidak ada, Administrator Kantor Pusat.
     */
    public function receivers(BranchTransfer $transfer): Collection
    {
        $base = User::query()
            ->where('company_id', $transfer->company_id)
            ->where('status', 1)
            ->where('id', '!=', (int) $transfer->sent_by);

        // User di cabang tujuan yang boleh menerima (Administrator cabang tsb atau izin branch.receive).
        $receivers = (clone $base)
            ->where('branch_id', $transfer->to_branch_id)
            ->whereHas('role', fn ($role) => $role
                ->where('status', 1)
                ->where(fn ($q) => $q->where('role_code', 'administrator')
                    ->orWhereHas('permissions', fn ($p) => $p->where('permission_code', 'branch.receive'))))
            ->get();

        if ($receivers->isNotEmpty()) {
            return $receivers;
        }

        return \App\Support\BranchAccess::administrators((int) $transfer->company_id)
            ->reject(fn (User $u) => (int) $u->id === (int) $transfer->sent_by)
            ->values();
    }

    protected function make(BranchTransfer $transfer, string $action, array $title, array $message): GlobalNotification
    {
        return new GlobalNotification(
            type: self::TYPE,
            title: $title,
            message: $message,
            url: route('branch-transfers.show', $transfer->id, false),
            extra: [
                'action' => $action,
                'branch_transfer_id' => $transfer->id,
                'transfer_no' => $transfer->transfer_no,
            ],
        );
    }

    protected function safely(callable $callback): void
    {
        try {
            $callback();
        } catch (Throwable $e) {
            report($e);
        }
    }
}
