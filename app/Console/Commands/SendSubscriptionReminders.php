<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Models\SubscriptionAddon;
use App\Models\User;
use App\Notifications\GlobalNotification;
use App\Services\Subscription\SubscriptionService;
use App\Support\NotificationText;
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
                        $this->endsTitle($days, 'Upgrade :plan berakhir', ['plan' => $upgrade->plan?->plan_name ?? '']),
                        NotificationText::make(
                            'Mulai :date paket kembali ke :base. Perpanjang upgrade bila kapasitas masih dibutuhkan.',
                            [
                                'date' => ['date' => $upgrade->end_date->copy()->addDay()->toDateString()],
                                'base' => $parent->plan?->plan_name ?? ['t' => 'paket dasar'],
                            ]
                        )
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
                        $this->endsTitle($days, 'Kapasitas tambahan berakhir'),
                        NotificationText::make(
                            'Kapasitas tambahan :items berakhir :date. Data tetap aman, tetapi penambahan baru diblokir bila melebihi kuota.',
                            ['items' => $items, 'date' => ['date' => $addons->first()->end_date->toDateString()]]
                        )
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
                        $this->endsTitle($days, 'Paket :plan berakhir', ['plan' => $subscription->plan?->plan_name ?? '']),
                        NotificationText::make(
                            'Paket Anda berakhir :date. Perpanjang sekarang agar layanan tidak terhenti.',
                            ['date' => ['date' => $subscription->end_date->toDateString()]]
                        )
                    );
                });
        }

        $this->info("Pengingat terkirim: {$sent}");

        return self::SUCCESS;
    }

    /**
     * Judul "... berakhir besok" / "... berakhir dalam N hari" (dua bahasa).
     * Kunci terjemahan: "<awal> besok" dan "<awal> dalam :days hari".
     */
    protected function endsTitle(int $days, string $start, array $params = []): array
    {
        return $days === 1
            ? NotificationText::make($start . ' besok', $params)
            : NotificationText::make($start . ' dalam :days hari', $params + ['days' => $days]);
    }

    protected function notifyAdmins(int $companyId, array $title, array $message): int
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
