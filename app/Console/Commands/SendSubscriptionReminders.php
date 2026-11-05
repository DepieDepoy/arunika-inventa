<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Models\SubscriptionAddon;
use App\Models\User;
use App\Notifications\GlobalNotification;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Console\Command;

/**
 * Pengingat ke Administrator tiap company:
 * - upgrade sementara akan berakhir (kuota kembali ke paket dasar)
 * - add-on kapasitas akan berakhir
 * - paket berbayar akan berakhir dan belum ada paket berikutnya
 *
 * Dijalankan harian (routes/console.php). Dikirim tepat H-7 dan H-1,
 * sehingga tidak ada notifikasi ganda.
 */
class SendSubscriptionReminders extends Command
{
    protected $signature = 'subscription:reminders';

    protected $description = 'Kirim pengingat upgrade / add-on / paket yang akan berakhir';

    protected const DAYS_BEFORE = [7, 1];

    public function handle(SubscriptionService $subscriptions): int
    {
        $sent = 0;

        foreach (self::DAYS_BEFORE as $days) {
            $date = today()->addDays($days)->toDateString();

            // 1. Upgrade sementara berakhir.
            Subscription::query()
                ->with(['plan', 'parent.plan'])
                ->where('kind', 'upgrade')
                ->where('status', 'active')
                ->whereDate('end_date', $date)
                ->get()
                ->each(function (Subscription $upgrade) use ($days, &$sent) {
                    $parent = $upgrade->parent;

                    if (!$parent || !$upgrade->end_date->lt($parent->end_date)) {
                        return; // berlaku sampai akhir paket: ditangani pengingat paket.
                    }

                    $sent += $this->notifyAdmins(
                        (int) $upgrade->company_id,
                        'Upgrade ' . ($upgrade->plan?->plan_name ?? '') . ' berakhir ' . $this->when($days),
                        'Mulai ' . $upgrade->end_date->copy()->addDay()->format('d M Y')
                            . ' paket kembali ke ' . ($parent->plan?->plan_name ?? 'paket dasar')
                            . '. Perpanjang upgrade bila kapasitas masih dibutuhkan.'
                    );
                });

            // 2. Add-on berakhir (dikelompokkan per transaksi).
            SubscriptionAddon::query()
                ->where('status', 'active')
                ->whereDate('end_date', $date)
                ->get()
                ->groupBy('payment_transaction_id')
                ->each(function ($addons) use ($days, $subscriptions, &$sent) {
                    $items = $addons
                        ->map(fn ($a) => $subscriptions->describeAddon($a->type, (int) $a->total_value))
                        ->implode(', ');

                    $sent += $this->notifyAdmins(
                        (int) $addons->first()->company_id,
                        'Kapasitas tambahan berakhir ' . $this->when($days),
                        'Add-on ' . $items . ' berakhir ' . $addons->first()->end_date->format('d M Y')
                            . '. Data tetap aman, tetapi penambahan baru diblokir bila melebihi kuota.'
                    );
                });

            // 3. Paket berbayar berakhir & belum ada paket berikutnya.
            Subscription::query()
                ->with('plan')
                ->where('kind', 'regular')
                ->where('status', 'active')
                ->where('payment_status', 'paid')
                ->where('billing_cycle', '!=', 'trial')
                ->whereDate('end_date', $date)
                ->get()
                ->each(function (Subscription $subscription) use ($days, $subscriptions, &$sent) {
                    if ($subscriptions->queued((int) $subscription->company_id)) {
                        return;
                    }

                    $sent += $this->notifyAdmins(
                        (int) $subscription->company_id,
                        'Paket ' . ($subscription->plan?->plan_name ?? '') . ' berakhir ' . $this->when($days),
                        'Paket Anda berakhir ' . $subscription->end_date->format('d M Y')
                            . '. Perpanjang sekarang agar layanan tidak terhenti.'
                    );
                });
        }

        $this->info("Pengingat terkirim: {$sent}");

        return self::SUCCESS;
    }

    protected function when(int $days): string
    {
        return $days === 1 ? 'besok' : "dalam {$days} hari";
    }

    protected function notifyAdmins(int $companyId, string $title, string $message): int
    {
        $admins = User::query()
            ->where('company_id', $companyId)
            ->where('status', 1)
            ->whereHas('role', fn ($q) => $q->where('role_code', 'administrator'))
            ->get();

        foreach ($admins as $admin) {
            $admin->notify(new GlobalNotification(
                type: 'subscription',
                title: $title,
                message: $message,
                url: '/dashboard/subscription',
            ));
        }

        return $admins->count();
    }
}
