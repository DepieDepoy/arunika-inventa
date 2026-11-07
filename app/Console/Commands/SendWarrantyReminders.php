<?php

namespace App\Console\Commands;

use App\Models\Asset;
use App\Models\User;
use App\Notifications\GlobalNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

/**
 * Pengingat garansi aset: 30 hari, 7 hari, dan pada hari garansi berakhir.
 * Dikirim ke administrator & user yang boleh mengubah aset (lonceng + push).
 * Dijalankan sekali sehari oleh scheduler, sehingga tiap ambang hanya terkirim sekali.
 */
class SendWarrantyReminders extends Command
{
    public const DAYS = [30, 7, 0];

    protected $signature = 'assets:warranty-reminders';

    protected $description = 'Kirim pengingat garansi aset yang akan / sudah berakhir';

    public function handle(): int
    {
        $dates = collect(self::DAYS)->mapWithKeys(fn ($d) => [today()->addDays($d)->toDateString() => $d]);

        $assets = Asset::query()
            ->where('status', Asset::STATUS_ACTIVE)
            ->whereIn('warranty_end', $dates->keys())
            ->with('responsibleUser:id,name')
            ->get(['id', 'company_id', 'asset_code', 'asset_name', 'warranty_end', 'responsible_user_id']);

        $sent = 0;

        foreach ($assets->groupBy('company_id') as $companyId => $items) {
            $recipients = $this->recipients((int) $companyId);

            if ($recipients->isEmpty()) {
                continue;
            }

            foreach ($items as $asset) {
                $days = $dates[$asset->warranty_end->toDateString()];
                $notification = $this->notification($asset, $days);

                foreach ($recipients as $user) {
                    try {
                        $user->notify($notification);
                        $sent++;
                    } catch (\Throwable $e) {
                        report($e);
                    }
                }
            }
        }

        $this->info("Pengingat garansi: {$assets->count()} aset, {$sent} notifikasi.");

        return self::SUCCESS;
    }

    /** @return Collection<int, User> */
    protected function recipients(int $companyId): Collection
    {
        return User::query()
            ->where('company_id', $companyId)
            ->where('status', 1)
            ->whereHas('role', fn ($role) => $role->where('status', 1)->where(fn ($q) => $q
                ->where('role_code', 'administrator')
                ->orWhereHas('permissions', fn ($p) => $p->where('permission_code', 'asset.edit'))))
            ->get();
    }

    protected function notification(Asset $asset, int $days): GlobalNotification
    {
        $label = trim($asset->asset_name . ' (' . $asset->asset_code . ')');
        $date = $asset->warranty_end->translatedFormat('d M Y');

        [$title, $message] = $days === 0
            ? ['Garansi aset berakhir hari ini', "Garansi {$label} berakhir hari ini ({$date}). Perpanjang garansi atau catat bila sudah tidak berlaku."]
            : ["Garansi aset habis {$days} hari lagi", "Garansi {$label} berakhir pada {$date}. Klaim kerusakan atau perpanjang sebelum tanggal tersebut."];

        return new GlobalNotification(
            type: 'asset_warranty',
            title: $title,
            message: $message,
            url: route('assets.show', encryptId($asset->id), false),
            extra: [
                'asset_id' => $asset->id,
                'asset_name' => $asset->asset_name,
                'days_left' => $days,
                'warranty_end' => $asset->warranty_end->toDateString(),
            ],
        );
    }
}
