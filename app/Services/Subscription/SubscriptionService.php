<?php

namespace App\Services\Subscription;

use App\Models\AddonRate;
use App\Models\Asset;
use App\Models\PaymentTransaction;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionAddon;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Aturan paket, upgrade, add-on, dan kuota.
 *
 * JENIS PEMBELIAN (payment_transactions.purchase_type)
 * - subscription : beli / perpanjang / ganti paket.
 *                  Masih ada paket berbayar aktif -> masuk antrean.
 *                  Trial / tidak ada paket -> langsung aktif (trial ditutup).
 * - upgrade      : upgrade SEKARANG ke paket lebih tinggi untuk 1..N bulan
 *                  atau sampai akhir paket. Bayar selisih harga harian.
 *                  Setelah selesai, otomatis kembali ke paket regular.
 * - addon        : tambah storage / asset / user untuk 1 bulan atau sampai
 *                  akhir paket, harga prorata. Langsung aktif.
 * - storage_addon: (lama) tambah storage langsung ke snapshot paket.
 *
 * KUOTA EFEKTIF HARI INI
 *   paket yang berlaku (upgrade menimpa regular) + add-on aktif.
 *   Data tidak pernah dihapus saat kuota turun; hanya penambahan yang diblokir.
 */
class SubscriptionService
{
    public const TYPE_SUBSCRIPTION = 'subscription';
    public const TYPE_UPGRADE = 'upgrade';
    public const TYPE_ADDON = 'addon';
    public const TYPE_STORAGE_ADDON = 'storage_addon';

    /** Pilihan durasi upgrade (bulan). NULL = sampai akhir paket. */
    public const UPGRADE_DURATIONS = [1, 2, 3, 6, null];

    /** Pilihan durasi add-on (bulan). NULL = sampai akhir paket. */
    public const ADDON_DURATIONS = [1, null];

    /*
    |--------------------------------------------------------------------------
    | TARIF ADD-ON (dari database: addon_rates)
    |--------------------------------------------------------------------------
    */

    /**
     * @return array<string, array{type:string,name:string,unit_value:int,unit_label:string,price_monthly:int,price_yearly:int,max_units:int}>
     */
    public function rates(): array
    {
        $rates = [];

        foreach (
            AddonRate::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get() as $rate
        ) {
            $rates[$rate->type] = [
                'type' => $rate->type,
                'name' => $rate->name,
                'unit_value' => max(1, (int) $rate->unit_value),
                'unit_label' => $rate->unit_label,
                'price_monthly' => (int) $rate->price_monthly,
                'price_yearly' => (int) $rate->price_yearly,
                'max_units' => max(0, (int) $rate->max_units),
            ];
        }

        return $rates;
    }

    /**
     * Tarif storage (dipakai juga saat beli paket + storage).
     *
     * @return array{unit_bytes:int, price_monthly:int, price_yearly:int, max_units:int}
     */
    public function storageRate(): array
    {
        $rate = $this->rates()['storage'] ?? null;

        return [
            'unit_bytes' => (int) ($rate['unit_value'] ?? 536870912),
            'price_monthly' => (int) ($rate['price_monthly'] ?? 10000),
            'price_yearly' => (int) ($rate['price_yearly'] ?? 120000),
            'max_units' => (int) ($rate['max_units'] ?? 40),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | STATUS PAKET
    |--------------------------------------------------------------------------
    */

    public function isTrial(?Subscription $subscription): bool
    {
        if (!$subscription) {
            return false;
        }

        return $subscription->billing_cycle === 'trial'
            || strtoupper((string) $subscription->plan?->plan_code) === 'FREE';
    }

    /**
     * Paket yang berlaku hari ini. Upgrade aktif menimpa paket regular.
     */
    public function current(int $companyId): ?Subscription
    {
        return Subscription::query()
            ->with(['plan', 'parent.plan'])
            ->where('company_id', $companyId)
            ->where('status', 'active')
            ->whereDate('start_date', '<=', today())
            ->whereDate('end_date', '>=', today())
            ->orderByDesc('priority')
            ->orderByDesc('end_date')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Paket REGULAR berbayar yang berlaku hari ini (dasar upgrade & add-on).
     */
    public function currentPaid(int $companyId): ?Subscription
    {
        return Subscription::query()
            ->with('plan')
            ->where('company_id', $companyId)
            ->where('kind', 'regular')
            ->where('status', 'active')
            ->where('payment_status', 'paid')
            ->where('billing_cycle', '!=', 'trial')
            ->whereDate('start_date', '<=', today())
            ->whereDate('end_date', '>=', today())
            ->whereHas('plan', fn ($q) => $q->where('plan_code', '!=', 'FREE'))
            ->orderByDesc('end_date')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Upgrade yang sedang berjalan hari ini.
     */
    public function activeUpgrade(int $companyId): ?Subscription
    {
        return Subscription::query()
            ->with(['plan', 'parent.plan'])
            ->where('company_id', $companyId)
            ->where('kind', 'upgrade')
            ->where('status', 'active')
            ->whereDate('start_date', '<=', today())
            ->whereDate('end_date', '>=', today())
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Paket regular yang sudah dibayar dan menunggu giliran aktif.
     */
    public function queued(int $companyId): ?Subscription
    {
        return Subscription::query()
            ->with('plan')
            ->where('company_id', $companyId)
            ->where('kind', 'regular')
            ->where('status', 'active')
            ->where('payment_status', 'paid')
            ->whereDate('start_date', '>', today())
            ->orderBy('start_date')
            ->first();
    }

    /**
     * Add-on yang berlaku hari ini.
     */
    public function activeAddons(int $companyId)
    {
        return SubscriptionAddon::query()
            ->where('company_id', $companyId)
            ->activeOn(today())
            ->orderBy('end_date')
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | KUOTA EFEKTIF
    |--------------------------------------------------------------------------
    */

    /**
     * Kuota yang berlaku hari ini.
     * users / assets: NULL = unlimited, 0 = tidak boleh menambah.
     *
     * @return array{subscription:?Subscription, plan:?Plan, users:?int, assets:?int, storage:int, addons:array{storage:int, asset:int, user:int}}
     */
    public function quota(int $companyId): array
    {
        $subscription = $this->current($companyId);
        $plan = $subscription?->plan;

        $addons = ['storage' => 0, 'asset' => 0, 'user' => 0];

        foreach ($this->activeAddons($companyId) as $addon) {
            $addons[$addon->type] = ($addons[$addon->type] ?? 0) + (int) $addon->total_value;
        }

        if (!$subscription || !$plan) {
            return [
                'subscription' => null,
                'plan' => null,
                'users' => 0,
                'assets' => 0,
                'branches' => 1,
                'storage' => 536870912, // 512 MB default
                'addons' => $addons,
            ];
        }

        // Kuota dikunci di langganan saat pembelian (Subscription::limit()),
        // sehingga perubahan paket di backoffice tidak mengubah pelanggan berjalan.
        $maxUsers = $subscription->limit('users');
        $maxAssets = $subscription->limit('assets');
        $maxBranches = $subscription->limit('branches');

        $users = $maxUsers === null ? null : $maxUsers + $addons['user'];
        $assets = $maxAssets === null ? null : $maxAssets + $addons['asset'];

        $baseStorage = (int) $subscription->total_storage_bytes;

        if ($baseStorage <= 0) {
            $baseStorage = (int) $subscription->base_storage_bytes + (int) $subscription->storage_addon_bytes;
        }

        if ($baseStorage <= 0) {
            $baseStorage = (int) ($plan->max_storage_bytes ?? 536870912);
        }

        return [
            'subscription' => $subscription,
            'plan' => $plan,
            'users' => $users,
            'assets' => $assets,
            // Batas cabang (Kantor Pusat ikut dihitung). NULL = tanpa batas.
            'branches' => $maxBranches === null ? null : max(1, $maxBranches),
            'storage' => $baseStorage + $addons['storage'],
            'addons' => $addons,
        ];
    }

    /**
     * Pemakaian saat ini.
     *
     * @return array{users:int, assets:int, storage:int}
     */
    public function usage(int $companyId): array
    {
        return [
            'users' => User::query()->where('company_id', $companyId)->count(),
            // Kuota dihitung untuk seluruh perusahaan, bukan per cabang.
            'assets' => (int) \App\Services\Asset\AssetStats::remember($companyId, 'quota-assets', fn () => Asset::query()->withoutGlobalScope(\App\Models\Scopes\BranchScope::class)->where('company_id', $companyId)->count()),
            'branches' => \App\Models\Branch::query()->where('company_id', $companyId)->count(),
            'storage' => \App\Services\CompanyStorage::usedBytes($companyId),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | PERIODE & TARIF HARIAN
    |--------------------------------------------------------------------------
    */

    public function endDateFor(CarbonInterface $start, string $billingCycle): Carbon
    {
        $start = Carbon::parse($start)->startOfDay();

        return $billingCycle === 'yearly'
            ? $start->copy()->addYearNoOverflow()->subDay()
            : $start->copy()->addMonthNoOverflow()->subDay();
    }

    /**
     * @return array{start: Carbon, end: Carbon, queued: bool}
     */
    public function previewPeriod(int $companyId, string $billingCycle): array
    {
        $paid = $this->currentPaid($companyId);

        $start = $paid
            ? Carbon::parse($paid->end_date)->addDay()->startOfDay()
            : today();

        return [
            'start' => $start,
            'end' => $this->endDateFor($start, $billingCycle),
            'queued' => $start->gt(today()),
        ];
    }

    /**
     * Tanggal akhir untuk upgrade / add-on mulai hari ini.
     * NULL bulan = sampai akhir paket dasar. Tidak pernah melewati akhir paket dasar.
     */
    public function durationEnd(Subscription $base, ?int $months): Carbon
    {
        $baseEnd = Carbon::parse($base->end_date)->startOfDay();

        if ($months === null) {
            return $baseEnd;
        }

        $end = today()->addMonthsNoOverflow($months)->subDay();

        return $end->gt($baseEnd) ? $baseEnd : $end;
    }

    public function daysBetween(CarbonInterface $start, CarbonInterface $end): int
    {
        if (Carbon::parse($end)->lt(Carbon::parse($start))) {
            return 0;
        }

        return (int) Carbon::parse($start)->startOfDay()->diffInDays(Carbon::parse($end)->startOfDay()) + 1;
    }

    public function remainingDays(Subscription $subscription): int
    {
        return $this->daysBetween(today(), $subscription->end_date);
    }

    /**
     * Tarif harian. Tahunan dipakai bila $useYearly (durasi sampai akhir
     * paket tahunan) agar pelanggan mendapat harga tahunan.
     */
    protected function dailyRate(float $monthly, float $yearly, bool $useYearly): float
    {
        return $useYearly && $yearly > 0
            ? $yearly / 365
            : $monthly / 30;
    }

    /**
     * Tarif harian yang benar-benar dibayar untuk paket regular.
     */
    protected function paidDailyRate(Subscription $base): float
    {
        $price = (float) $base->price;

        if ($price <= 0) {
            $price = $base->billing_cycle === 'yearly'
                ? (float) $base->plan?->price_yearly
                : (float) $base->plan?->price_monthly;
        }

        return $base->billing_cycle === 'yearly'
            ? $price / 365
            : $price / 30;
    }

    protected function roundUp100(float $value): int
    {
        return (int) (ceil(max(0, $value) / 100) * 100);
    }

    /*
    |--------------------------------------------------------------------------
    | PENAWARAN UPGRADE
    |--------------------------------------------------------------------------
    */

    /**
     * Paket yang lebih tinggi dari paket regular saat ini.
     */
    public function upgradeablePlans(Subscription $base)
    {
        $basePrice = (float) ($base->plan?->price_monthly ?? 0);

        return Plan::query()
            ->where('status', 1)
            ->where('plan_code', '!=', 'FREE')
            ->where('price_monthly', '>', $basePrice)
            ->orderBy('price_monthly')
            ->get();
    }

    /**
     * Hitung harga upgrade sekarang.
     *
     * @return array{plan_id:int, plan_name:string, months:?int, start:Carbon, end:Carbon, revert_date:?Carbon, days:int, new_price:int, credit:int, price:int}
     */
    public function upgradeQuote(Subscription $base, Plan $plan, ?int $months): array
    {
        $start = today();
        $end = $this->durationEnd($base, $months);
        $days = $this->daysBetween($start, $end);
        $untilEnd = $end->equalTo(Carbon::parse($base->end_date)->startOfDay());

        $useYearly = $untilEnd && $base->billing_cycle === 'yearly';

        $newDaily = $this->dailyRate(
            (float) $plan->price_monthly,
            (float) $plan->price_yearly,
            $useYearly
        );

        $newPrice = $this->roundUp100($newDaily * $days);
        $credit = min($newPrice, (int) floor($this->paidDailyRate($base) * $days / 100) * 100);

        return [
            'plan_id' => (int) $plan->id,
            'plan_name' => (string) $plan->plan_name,
            'months' => $months,
            'start' => $start,
            'end' => $end,
            'revert_date' => $untilEnd ? null : $end->copy()->addDay(),
            'days' => $days,
            'new_price' => $newPrice,
            'credit' => $credit,
            'price' => max(0, $newPrice - $credit),
        ];
    }

    /**
     * Semua kombinasi paket x durasi untuk ditampilkan.
     */
    public function upgradeOptions(Subscription $base): array
    {
        $options = [];
        $remaining = $this->remainingDays($base);

        foreach ($this->upgradeablePlans($base) as $plan) {
            foreach (self::UPGRADE_DURATIONS as $months) {
                if ($months !== null) {
                    $end = $this->durationEnd($base, $months);

                    // Durasi yang sama dengan "sampai akhir paket" disembunyikan.
                    if ($end->equalTo(Carbon::parse($base->end_date)->startOfDay())) {
                        continue;
                    }
                }

                if ($remaining < 1) {
                    continue;
                }

                $quote = $this->upgradeQuote($base, $plan, $months);

                $options[] = [
                    'plan_id' => $quote['plan_id'],
                    'plan_name' => $quote['plan_name'],
                    'max_assets' => $plan->max_assets,
                    'max_users' => $plan->max_users,
                    'max_branches' => $plan->max_branches,
                    'max_storage_bytes' => (int) $plan->max_storage_bytes,
                    'months' => $months,
                    'start' => $quote['start']->translatedFormat('d M Y'),
                    'end' => $quote['end']->translatedFormat('d M Y'),
                    'revert_date' => $quote['revert_date']?->translatedFormat('d M Y'),
                    'days' => $quote['days'],
                    'new_price' => $quote['new_price'],
                    'credit' => $quote['credit'],
                    'price' => $quote['price'],
                ];
            }
        }

        return $options;
    }

    /*
    |--------------------------------------------------------------------------
    | PENAWARAN ADD-ON
    |--------------------------------------------------------------------------
    */

    /**
     * Unit add-on yang masih aktif (per jenis) pada paket dasar.
     */
    public function activeAddonUnits(int $companyId): array
    {
        $units = ['storage' => 0, 'asset' => 0, 'user' => 0];

        foreach ($this->activeAddons($companyId) as $addon) {
            $units[$addon->type] = ($units[$addon->type] ?? 0) + (int) $addon->units;
        }

        return $units;
    }

    /**
     * Hitung harga add-on.
     *
     * @param  array<string,int> $units  ['storage' => 2, 'asset' => 1, 'user' => 0]
     * @return array{start:Carbon, end:Carbon, days:int, months:?int, items:array, total:int}
     */
    public function addonQuote(Subscription $base, array $units, ?int $months): array
    {
        $rates = $this->rates();
        $start = today();
        $end = $this->durationEnd($base, $months);
        $days = $this->daysBetween($start, $end);
        $untilEnd = $end->equalTo(Carbon::parse($base->end_date)->startOfDay());
        $useYearly = $untilEnd && $base->billing_cycle === 'yearly';

        $items = [];
        $total = 0;

        foreach ($units as $type => $qty) {
            $qty = (int) $qty;

            if ($qty <= 0 || !isset($rates[$type])) {
                continue;
            }

            $rate = $rates[$type];

            $unitPrice = $this->roundUp100(
                $this->dailyRate($rate['price_monthly'], $rate['price_yearly'], $useYearly) * $days
            );

            $items[] = [
                'type' => $type,
                'name' => $rate['name'],
                'units' => $qty,
                'unit_value' => $rate['unit_value'],
                'unit_label' => $rate['unit_label'],
                'total_value' => $qty * $rate['unit_value'],
                'unit_price' => $unitPrice,
                'price' => $unitPrice * $qty,
            ];

            $total += $unitPrice * $qty;
        }

        return [
            'start' => $start,
            'end' => $end,
            'days' => $days,
            'months' => $months,
            'items' => $items,
            'total' => $total,
        ];
    }

    /**
     * Harga per unit untuk setiap jenis x durasi (dipakai tampilan).
     */
    public function addonUnitPrices(Subscription $base): array
    {
        $prices = [];

        foreach (self::ADDON_DURATIONS as $months) {
            $key = $months === null ? 'end' : (string) $months;
            $units = array_fill_keys(array_keys($this->rates()), 1);
            $quote = $this->addonQuote($base, $units, $months);

            $prices[$key] = [
                'end' => $quote['end']->translatedFormat('d M Y'),
                'days' => $quote['days'],
                'unit_price' => collect($quote['items'])->pluck('unit_price', 'type')->all(),
            ];
        }

        return $prices;
    }

    /*
    |--------------------------------------------------------------------------
    | AKTIVASI PEMBAYARAN (idempoten)
    |--------------------------------------------------------------------------
    */

    /**
     * @return string already_paid | activated | queued | upgraded | addon_applied
     */
    public function activatePayment(PaymentTransaction $payment, array $gatewayResult = []): string
    {
        return DB::transaction(function () use ($payment, $gatewayResult) {
            $transaction = PaymentTransaction::query()
                ->whereKey($payment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($transaction->isPaid()) {
                return 'already_paid';
            }

            $gatewayDate = data_get($gatewayResult, 'transaction.date');

            $transaction->transaction_status = 'success';
            $transaction->transaction_time = $gatewayDate ? Carbon::parse($gatewayDate) : now();
            $transaction->settlement_time = now();

            if ($gatewayResult !== []) {
                $transaction->midtrans_response = $gatewayResult;
            }

            $transaction->save();

            $subscription = Subscription::query()
                ->whereKey($transaction->subscription_id)
                ->lockForUpdate()
                ->firstOrFail();

            return match ($transaction->purchase_type) {
                self::TYPE_STORAGE_ADDON => $this->applyLegacyStorageAddon($transaction, $subscription),
                self::TYPE_ADDON => $this->activateAddons($transaction, $subscription),
                self::TYPE_UPGRADE => $this->activateUpgrade($transaction, $subscription),
                default => $this->activateSubscription($transaction, $subscription),
            };
        });
    }

    protected function activateSubscription(
        PaymentTransaction $transaction,
        Subscription $subscription
    ): string {
        $companyId = (int) $transaction->company_id;

        Subscription::query()
            ->where('company_id', $companyId)
            ->where('status', 'active')
            ->lockForUpdate()
            ->get();

        $queued = Subscription::query()
            ->where('company_id', $companyId)
            ->where('kind', 'regular')
            ->whereKeyNot($subscription->id)
            ->where('status', 'active')
            ->where('payment_status', 'paid')
            ->whereDate('start_date', '>', today())
            ->orderByDesc('end_date')
            ->first();

        $currentPaid = $this->currentPaid($companyId);

        if ($currentPaid && (int) $currentPaid->id === (int) $subscription->id) {
            $currentPaid = null;
        }

        if ($queued) {
            $start = Carbon::parse($queued->end_date)->addDay()->startOfDay();
        } elseif ($currentPaid) {
            $start = Carbon::parse($currentPaid->end_date)->addDay()->startOfDay();
        } else {
            $start = today();

            // Trial / paket gratis yang masih berjalan ditutup.
            Subscription::query()
                ->where('company_id', $companyId)
                ->whereKeyNot($subscription->id)
                ->where('status', 'active')
                ->whereDate('end_date', '>=', today())
                ->get()
                ->each(function (Subscription $old) {
                    $old->status = 'replaced';
                    $old->save();
                });
        }

        $subscription->kind = 'regular';
        $subscription->priority = 0;
        $subscription->start_date = $start;
        $subscription->end_date = $this->endDateFor($start, (string) $subscription->billing_cycle);
        $subscription->status = 'active';
        $subscription->payment_status = 'paid';
        $subscription->save();

        $isQueued = $start->gt(today());

        AuditLogger::log(
            event: $isQueued ? 'subscription_queued' : 'subscription_activated',
            module: 'subscription',
            description: ($isQueued ? 'Paket dijadwalkan: ' : 'Paket diaktifkan: ')
                . ($subscription->plan?->plan_name ?? 'Paket')
                . ' (' . $subscription->billing_cycle . ') '
                . $start->format('d/m/Y') . ' s/d '
                . Carbon::parse($subscription->end_date)->format('d/m/Y'),
            subject: $subscription,
            newValues: [
                'order_id' => $transaction->order_id,
                'start_date' => $start->toDateString(),
                'end_date' => Carbon::parse($subscription->end_date)->toDateString(),
                'gross_amount' => (int) $transaction->gross_amount,
            ],
            companyId: $companyId,
        );

        return $isQueued ? 'queued' : 'activated';
    }

    protected function activateUpgrade(
        PaymentTransaction $transaction,
        Subscription $upgrade
    ): string {
        $companyId = (int) $transaction->company_id;

        $base = Subscription::query()
            ->whereKey($upgrade->parent_subscription_id)
            ->lockForUpdate()
            ->first();

        if (!$base) {
            throw new RuntimeException('Paket dasar untuk upgrade tidak ditemukan.');
        }

        $months = $transaction->duration_months !== null
            ? (int) $transaction->duration_months
            : null;

        // Tanggal final dihitung saat lunas.
        $start = today();
        $end = $this->durationEnd($base, $months);

        if ($end->lt($start)) {
            // Paket dasar sudah berakhir saat pembayaran diterima.
            $end = $start->copy();
        }

        // Upgrade lain yang masih berjalan digantikan.
        Subscription::query()
            ->where('company_id', $companyId)
            ->where('kind', 'upgrade')
            ->whereKeyNot($upgrade->id)
            ->where('status', 'active')
            ->whereDate('end_date', '>=', today())
            ->get()
            ->each(function (Subscription $old) {
                $old->status = 'replaced';
                $old->save();
            });

        $upgrade->kind = 'upgrade';
        $upgrade->priority = 1;
        $upgrade->start_date = $start;
        $upgrade->end_date = $end;
        $upgrade->status = 'active';
        $upgrade->payment_status = 'paid';
        $upgrade->save();

        $revert = $end->lt(Carbon::parse($base->end_date))
            ? ', kembali ke ' . ($base->plan?->plan_name ?? 'paket dasar') . ' mulai ' . $end->copy()->addDay()->format('d/m/Y')
            : '';

        AuditLogger::log(
            event: 'subscription_upgraded',
            module: 'subscription',
            description: 'Upgrade ke ' . ($upgrade->plan?->plan_name ?? 'paket')
                . ' ' . $start->format('d/m/Y') . ' s/d ' . $end->format('d/m/Y') . $revert,
            subject: $upgrade,
            newValues: [
                'order_id' => $transaction->order_id,
                'base_subscription_id' => $base->id,
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'gross_amount' => (int) $transaction->gross_amount,
            ],
            companyId: $companyId,
        );

        return 'upgraded';
    }

    protected function activateAddons(
        PaymentTransaction $transaction,
        Subscription $base
    ): string {
        $months = $transaction->duration_months !== null
            ? (int) $transaction->duration_months
            : null;

        $start = today();
        $end = $this->durationEnd($base, $months);

        if ($end->lt($start)) {
            $end = $start->copy();
        }

        $addons = SubscriptionAddon::query()
            ->where('payment_transaction_id', $transaction->id)
            ->lockForUpdate()
            ->get();

        $summary = [];

        foreach ($addons as $addon) {
            $addon->start_date = $start;
            $addon->end_date = $end;
            $addon->status = 'active';
            $addon->save();

            $summary[] = $this->describeAddon($addon->type, (int) $addon->total_value);
        }

        AuditLogger::log(
            event: 'addon_purchased',
            module: 'subscription',
            description: 'Tambah kapasitas: ' . implode(', ', $summary)
                . ' (' . $start->format('d/m/Y') . ' s/d ' . $end->format('d/m/Y') . ')',
            subject: $base,
            newValues: [
                'order_id' => $transaction->order_id,
                'items' => $summary,
                'end_date' => $end->toDateString(),
                'gross_amount' => (int) $transaction->gross_amount,
            ],
            companyId: (int) $transaction->company_id,
        );

        return 'addon_applied';
    }

    /**
     * Pembelian storage model lama (langsung ke snapshot paket).
     */
    protected function applyLegacyStorageAddon(
        PaymentTransaction $transaction,
        Subscription $subscription
    ): string {
        $bytes = (int) $transaction->storage_addon_bytes;

        $subscription->storage_addon_bytes = (int) $subscription->storage_addon_bytes + $bytes;
        $subscription->total_storage_bytes = (int) $subscription->base_storage_bytes
            + (int) $subscription->storage_addon_bytes;
        $subscription->storage_addon_gb = round((int) $subscription->storage_addon_bytes / (1024 ** 3), 2);
        $subscription->storage_addon_price = (float) $subscription->storage_addon_price
            + (float) $transaction->storage_addon_price;
        $subscription->save();

        AuditLogger::log(
            event: 'addon_purchased',
            module: 'subscription',
            description: 'Tambah kapasitas: ' . $this->describeAddon('storage', $bytes),
            subject: $subscription,
            newValues: ['order_id' => $transaction->order_id, 'storage_addon_bytes' => $bytes],
            companyId: (int) $transaction->company_id,
        );

        return 'addon_applied';
    }

    public function describeAddon(string $type, int $totalValue): string
    {
        return match ($type) {
            'storage' => ($totalValue >= 1024 ** 3
                ? rtrim(rtrim(number_format($totalValue / (1024 ** 3), 2, ',', '.'), '0'), ',') . ' GB'
                : round($totalValue / (1024 ** 2)) . ' MB') . ' storage',
            'asset' => $totalValue . ' asset',
            'user' => $totalValue . ' user',
            default => $totalValue . ' ' . $type,
        };
    }

    /**
     * Tandai pembayaran gagal / kedaluwarsa / dibatalkan.
     */
    public function failPayment(
        PaymentTransaction $payment,
        string $status,
        array $gatewayResult = []
    ): void {
        DB::transaction(function () use ($payment, $status, $gatewayResult) {
            $transaction = PaymentTransaction::query()
                ->whereKey($payment->id)
                ->lockForUpdate()
                ->first();

            if (!$transaction || $transaction->isPaid()) {
                return;
            }

            $transaction->transaction_status = strtolower($status);

            if ($gatewayResult !== []) {
                $transaction->midtrans_response = $gatewayResult;
            }

            $transaction->save();

            if (in_array($transaction->purchase_type, [self::TYPE_ADDON, self::TYPE_STORAGE_ADDON], true)) {
                SubscriptionAddon::query()
                    ->where('payment_transaction_id', $transaction->id)
                    ->where('status', 'pending')
                    ->update(['status' => 'cancelled']);

                return;
            }

            $subscription = Subscription::query()
                ->whereKey($transaction->subscription_id)
                ->lockForUpdate()
                ->first();

            if (
                $subscription
                && $subscription->status === 'pending'
                && $subscription->payment_status !== 'paid'
            ) {
                $subscription->status = 'cancelled';
                $subscription->payment_status = 'failed';
                $subscription->save();
            }
        });
    }
}
