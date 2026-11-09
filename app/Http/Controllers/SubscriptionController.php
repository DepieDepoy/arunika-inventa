<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\PaymentTransaction;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionAddon;
use App\Models\User;
use App\Services\CompanyStorage;
use App\Services\DokuService;
use App\Services\MidtransService;
use App\Services\Subscription\SubscriptionService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class SubscriptionController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | SUBSCRIPTION INDEX
    |--------------------------------------------------------------------------
    */

    public function __construct(
        protected SubscriptionService $subscriptions
    ) {
    }

    public function index(DokuService $doku): View
    {
        $user = Auth::user();

        abort_unless($user, 401);

        $this->requireAdministrator();

        $company = $user->company;

        /*
         * Transaksi pending yang sudah lewat batas waktu dicek ulang
         * ke DOKU dulu: jika ternyata sudah dibayar, paket diaktifkan.
         */
        if ($company) {
            $this->syncExpiredPendingPayments($company->id, $doku);
        }

        /*
         * Paket yang berlaku hari ini.
         */
        $currentSubscription = $company
            ? $this->subscriptions->current($company->id)
            : null;

        /*
         * Jika belum ada paket aktif, tampilkan riwayat terakhir
         * sebagai informasi, tetapi jangan menganggapnya aktif.
         */
        if (!$currentSubscription && $company) {
            $currentSubscription = $company
                ->subscriptions()
                ->with('plan')
                ->whereDate('start_date', '<=', today())
                ->orderByDesc('end_date')
                ->orderByDesc('id')
                ->first();
        }

        $queuedSubscription = $company
            ? $this->subscriptions->queued($company->id)
            : null;

        $paidSubscription = $company
            ? $this->subscriptions->currentPaid($company->id)
            : null;

        $plans = Plan::query()
            ->where('status', 1)
            ->where('plan_code', '!=', 'FREE')
            ->orderBy('price_monthly')
            ->get();

        /*
         * Jangan tampilkan link pembayaran yang sudah kedaluwarsa.
         */
        $pendingPayment = $company
            ? $company->paymentTransactions()
                ->with('plan')
                ->where('transaction_status', 'pending')
                ->where(function ($query) {
                    $query->whereNull('expiry_time')
                        ->orWhere('expiry_time', '>', now());
                })
                ->latest('id')
                ->first()
            : null;

        /*
         * Perkiraan periode paket baru jika dibayar hari ini.
         */
        $periodPreview = $company
            ? [
                'monthly' => $this->subscriptions->previewPeriod($company->id, 'monthly'),
                'yearly' => $this->subscriptions->previewPeriod($company->id, 'yearly'),
            ]
            : null;

        $storageRate = $this->subscriptions->storageRate();

        /*
         * Upgrade sekarang & tambah kapasitas (butuh paket berbayar aktif).
         */
        $activeUpgrade = $company ? $this->subscriptions->activeUpgrade($company->id) : null;
        $activeAddons = $company ? $this->subscriptions->activeAddons($company->id) : collect();
        $quota = $company ? $this->subscriptions->quota($company->id) : null;
        $usage = $company ? $this->subscriptions->usage($company->id) : null;

        $upgradeOptions = [];
        $addonOffer = null;

        if ($paidSubscription) {
            if (!$activeUpgrade && !$queuedSubscription) {
                $upgradeOptions = $this->subscriptions->upgradeOptions($paidSubscription);
            }

            $rates = $this->subscriptions->rates();
            $activeUnits = $this->subscriptions->activeAddonUnits($company->id);

            $addonOffer = [
                'base' => $paidSubscription,
                'rates' => $rates,
                'remaining_units' => collect($rates)->mapWithKeys(fn ($rate, $type) => [
                    $type => max(0, $rate['max_units'] - (int) ($activeUnits[$type] ?? 0)),
                ])->all(),
                'prices' => $this->subscriptions->addonUnitPrices($paidSubscription),
            ];
        }

        return view(
            'dashboard.subscription.index',
            compact(
                'company',
                'currentSubscription',
                'queuedSubscription',
                'paidSubscription',
                'plans',
                'pendingPayment',
                'periodPreview',
                'storageRate',
                'activeUpgrade',
                'activeAddons',
                'quota',
                'usage',
                'upgradeOptions',
                'addonOffer'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | RENEW / CHANGE SUBSCRIPTION WITH DOKU
    |--------------------------------------------------------------------------
    */

    public function renewdoku(
        Request $request,
        DokuService $doku
    ): RedirectResponse {
        $user = Auth::user();

        $this->requireAdministrator();

        $rate = $this->subscriptions->storageRate();
        $unitBytes = $rate['unit_bytes'];
        $maxExtraBytes = $unitBytes * $rate['max_units'];

        $validated = $request->validate([
            'plan_id' => [
                'required',
                'integer',
                'exists:plans,id',
            ],
            'billing_cycle' => [
                'required',
                'in:monthly,yearly',
            ],
            'extra_storage_bytes' => [
                'nullable',
                'integer',
                'min:0',
                'max:' . $maxExtraBytes,
                function ($attribute, $value, $fail) use ($unitBytes) {
                    if ((int) $value % $unitBytes !== 0) {
                        $fail(
                            'Tambahan storage harus kelipatan '
                            . round($unitBytes / (1024 * 1024)) . ' MB.'
                        );
                    }
                },
            ],
        ]);

        $company = $user->company;

        if (!$company) {
            return back()->withErrors([
                'subscription' => 'Company tidak ditemukan.',
            ]);
        }

        /*
         * Maksimal 1 paket dalam antrean.
         */
        $queued = $this->subscriptions->queued($company->id);

        if ($queued) {
            return back()->withErrors([
                'subscription' =>
                    'Anda sudah memiliki paket berikutnya ('
                    . ($queued->plan?->plan_name ?? 'Paket')
                    . ', mulai ' . $queued->start_date->format('d/m/Y') . '). '
                    . 'Pembelian paket baru dapat dilakukan setelah paket tersebut berjalan.',
            ])->withInput();
        }

        $plan = Plan::query()
            ->whereKey($validated['plan_id'])
            ->where('status', 1)
            ->where('plan_code', '!=', 'FREE')
            ->first();

        if (!$plan) {
            return back()->withErrors([
                'subscription' =>
                    'Plan tidak tersedia atau tidak dapat dipilih.',
            ])->withInput();
        }

        $billingCycle = $validated['billing_cycle'];
        $yearly = $billingCycle === 'yearly';

        $price = $yearly
            ? $plan->price_yearly
            : $plan->price_monthly;

        if ($price === null || (float) $price <= 0) {
            return back()->withErrors([
                'subscription' =>
                    'Harga paket belum diatur. Silakan hubungi admin.',
            ])->withInput();
        }

        $baseStorageBytes = (int) ($plan->max_storage_bytes ?? 0);

        if ($baseStorageBytes <= 0) {
            report(new \RuntimeException(
                'max_storage_bytes plan ' . $plan->plan_code . ' belum diatur.'
            ));

            return back()->withErrors([
                'subscription' =>
                    'Kuota storage paket belum diatur. Silakan hubungi admin.',
            ])->withInput();
        }

        /*
         * Harga storage tambahan dari database (storage_addon_rates).
         */
        $extraStorageBytes = (int) ($validated['extra_storage_bytes'] ?? 0);
        $storageUnits = intdiv($extraStorageBytes, $unitBytes);
        $storageUnitPrice = $yearly ? $rate['price_yearly'] : $rate['price_monthly'];
        $storageAddonPrice = $storageUnits * $storageUnitPrice;
        $storageAddonGb = round($extraStorageBytes / (1024 ** 3), 2);

        $discountAmount = 0;

        $totalPrice = (int) round(
            (float) $price + $storageAddonPrice - $discountAmount
        );

        /*
         * Pakai ulang link pembayaran yang sama bila pilihan tidak berubah.
         */
        $reuseUrl = $this->reuseOrCancelPending(
            $company->id,
            fn (PaymentTransaction $pending) =>
                $pending->purchase_type === SubscriptionService::TYPE_SUBSCRIPTION
                && (int) $pending->plan_id === (int) $plan->id
                && $pending->billing_cycle === $billingCycle
                && (int) $pending->storage_addon_bytes === $extraStorageBytes
                && (int) round((float) $pending->gross_amount) === $totalPrice
        );

        if ($reuseUrl) {
            return back()->with('doku_checkout_url', $reuseUrl);
        }

        /*
         * Tanggal di bawah hanya perkiraan. Tanggal final dihitung
         * ulang saat pembayaran lunas (SubscriptionService).
         */
        $period = $this->subscriptions->previewPeriod($company->id, $billingCycle);

        try {
            $paymentUrl = DB::transaction(function () use (
                $company,
                $plan,
                $user,
                $doku,
                $billingCycle,
                $price,
                $period,
                $baseStorageBytes,
                $extraStorageBytes,
                $storageUnits,
                $storageUnitPrice,
                $storageAddonGb,
                $storageAddonPrice,
                $discountAmount,
                $totalPrice
            ) {
                $subscription = Subscription::create([
                    'company_id' => $company->id,
                    'plan_id' => $plan->id,
                    'billing_cycle' => $billingCycle,
                    'price' => $price,
                    'storage_addon_gb' => $storageAddonGb,
                    'storage_addon_price' => $storageAddonPrice,
                    'base_storage_bytes' => $baseStorageBytes,
                    'storage_addon_bytes' => $extraStorageBytes,
                    'total_storage_bytes' => $baseStorageBytes + $extraStorageBytes,
                    'discount_amount' => $discountAmount,
                    'total_price' => $totalPrice,
                    'start_date' => $period['start'],
                    'end_date' => $period['end'],
                    'status' => 'pending',
                    'payment_status' => 'pending',
                ]);

                $paymentTransaction = PaymentTransaction::create([
                    'company_id' => $company->id,
                    'subscription_id' => $subscription->id,
                    'plan_id' => $plan->id,
                    'order_id' => $this->nextOrderId(),
                    'purchase_type' => SubscriptionService::TYPE_SUBSCRIPTION,
                    'plan_name' => $plan->plan_name,
                    'billing_cycle' => $billingCycle,
                    'package_price' => $price,
                    'storage_addon_gb' => $storageAddonGb,
                    'storage_addon_bytes' => $extraStorageBytes,
                    'storage_addon_price' => $storageAddonPrice,
                    'discount_amount' => $discountAmount,
                    'gross_amount' => $totalPrice,
                    'transaction_status' => 'pending',
                ]);

                $lineItems = [
                    [
                        'id' => (string) $plan->id,
                        'name' => 'Subscription ' . $plan->plan_name
                            . ' - ' . ucfirst($billingCycle),
                        'quantity' => 1,
                        'price' => (int) $price,
                    ],
                ];

                if ($storageUnits > 0) {
                    $lineItems[] = [
                        'id' => 'STORAGE-' . $storageUnits,
                        'name' => 'Tambahan Storage '
                            . $this->formatBytes($extraStorageBytes)
                            . ' - ' . ucfirst($billingCycle),
                        'quantity' => $storageUnits,
                        'price' => $storageUnitPrice,
                    ];
                }

                return $this->createDokuCheckout(
                    $paymentTransaction,
                    $lineItems,
                    $user,
                    $company,
                    $doku
                );
            });

            return back()->with('doku_checkout_url', $paymentUrl);
        } catch (\Throwable $e) {
            return $this->paymentFailedResponse($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | UPGRADE SEKARANG (1..N BULAN / SAMPAI AKHIR PAKET)
    |--------------------------------------------------------------------------
    |
    | Bayar selisih harga harian paket baru vs paket saat ini.
    | Setelah durasi habis, otomatis kembali ke paket regular.
    |
    */

    public function upgradeNow(
        Request $request,
        DokuService $doku
    ): RedirectResponse {
        $user = Auth::user();

        $this->requireAdministrator();

        $validated = $request->validate([
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
            'duration' => ['required', 'in:1,2,3,6,end'],
        ]);

        $company = $user->company;
        abort_unless($company, 403, 'Company tidak ditemukan.');

        $base = $this->subscriptions->currentPaid($company->id);

        if (!$base) {
            return back()->withErrors([
                'subscription' => 'Upgrade sekarang hanya tersedia untuk paket berbayar yang sedang aktif.',
            ]);
        }

        if ($this->subscriptions->queued($company->id)) {
            return back()->withErrors([
                'subscription' => 'Upgrade sekarang tidak tersedia karena sudah ada paket berikutnya di antrean.',
            ]);
        }

        if ($active = $this->subscriptions->activeUpgrade($company->id)) {
            return back()->withErrors([
                'subscription' => 'Upgrade ke ' . ($active->plan?->plan_name ?? 'paket lain')
                    . ' masih berjalan sampai ' . $active->end_date->format('d/m/Y') . '.',
            ]);
        }

        $plan = $this->subscriptions->upgradeablePlans($base)
            ->firstWhere('id', (int) $validated['plan_id']);

        if (!$plan) {
            return back()->withErrors([
                'subscription' => 'Paket tujuan harus lebih tinggi dari paket saat ini.',
            ]);
        }

        $months = $validated['duration'] === 'end' ? null : (int) $validated['duration'];
        $quote = $this->subscriptions->upgradeQuote($base, $plan, $months);

        if ($quote['days'] < 1 || $quote['price'] <= 0) {
            return back()->withErrors([
                'subscription' => 'Upgrade tidak dapat diproses untuk durasi tersebut.',
            ]);
        }

        $reuseUrl = $this->reuseOrCancelPending(
            $company->id,
            fn (PaymentTransaction $pending) =>
                $pending->purchase_type === SubscriptionService::TYPE_UPGRADE
                && (int) $pending->plan_id === (int) $plan->id
                && $pending->duration_months === $months
                && (int) round((float) $pending->gross_amount) === $quote['price']
        );

        if ($reuseUrl) {
            return back()->with('doku_checkout_url', $reuseUrl);
        }

        // Storage paket baru + storage tambahan yang sudah melekat di paket dasar.
        $baseStorage = (int) ($plan->max_storage_bytes ?? 0);
        $carriedStorage = (int) $base->storage_addon_bytes;

        try {
            $paymentUrl = DB::transaction(function () use (
                $company, $user, $doku, $base, $plan, $months, $quote, $baseStorage, $carriedStorage
            ) {
                $upgrade = Subscription::create([
                    'company_id' => $company->id,
                    'plan_id' => $plan->id,
                    'kind' => 'upgrade',
                    'priority' => 1,
                    'parent_subscription_id' => $base->id,
                    'billing_cycle' => $base->billing_cycle,
                    'price' => $quote['new_price'],
                    'storage_addon_gb' => round($carriedStorage / (1024 ** 3), 2),
                    'storage_addon_price' => 0,
                    'base_storage_bytes' => $baseStorage,
                    'storage_addon_bytes' => $carriedStorage,
                    'total_storage_bytes' => $baseStorage + $carriedStorage,
                    'discount_amount' => $quote['credit'],
                    'total_price' => $quote['price'],
                    'start_date' => $quote['start'],
                    'end_date' => $quote['end'],
                    'status' => 'pending',
                    'payment_status' => 'pending',
                ]);

                $paymentTransaction = PaymentTransaction::create([
                    'company_id' => $company->id,
                    'subscription_id' => $upgrade->id,
                    'plan_id' => $plan->id,
                    'order_id' => $this->nextOrderId(),
                    'purchase_type' => SubscriptionService::TYPE_UPGRADE,
                    'plan_name' => $plan->plan_name,
                    'billing_cycle' => $base->billing_cycle,
                    'package_price' => $quote['new_price'],
                    'storage_addon_gb' => 0,
                    'storage_addon_price' => 0,
                    'prorated_days' => $quote['days'],
                    'duration_months' => $months,
                    'discount_amount' => $quote['credit'],
                    'gross_amount' => $quote['price'],
                    'transaction_status' => 'pending',
                ]);

                // DOKU: line item harus berjumlah = amount, jadi kirim satu baris selisih.
                return $this->createDokuCheckout(
                    $paymentTransaction,
                    [[
                        'id' => 'UPGRADE-' . $plan->id,
                        'name' => 'Upgrade ' . ($base->plan?->plan_name ?? '') . ' ke ' . $plan->plan_name
                            . ' (' . $quote['days'] . ' hari)',
                        'quantity' => 1,
                        'price' => $quote['price'],
                    ]],
                    $user,
                    $company,
                    $doku
                );
            });

            return back()->with('doku_checkout_url', $paymentUrl);
        } catch (\Throwable $e) {
            return $this->paymentFailedResponse($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | TAMBAH KAPASITAS (STORAGE / ASSET / USER) - LANGSUNG AKTIF
    |--------------------------------------------------------------------------
    */

    public function buyAddon(
        Request $request,
        DokuService $doku
    ): RedirectResponse {
        $user = Auth::user();

        $this->requireAdministrator();

        $validated = $request->validate([
            'storage_units' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'asset_units' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'user_units' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'duration' => ['required', 'in:1,end'],
        ]);

        $company = $user->company;
        abort_unless($company, 403, 'Company tidak ditemukan.');

        $base = $this->subscriptions->currentPaid($company->id);

        if (!$base) {
            return back()->withErrors([
                'subscription' => 'Tambah kapasitas hanya tersedia untuk paket berbayar yang sedang aktif. '
                    . 'Silakan pilih paket berbayar terlebih dahulu.',
            ]);
        }

        $rates = $this->subscriptions->rates();
        $activeUnits = $this->subscriptions->activeAddonUnits($company->id);

        $units = [];

        foreach (['storage', 'asset', 'user'] as $type) {
            $qty = (int) ($validated[$type . '_units'] ?? 0);

            if ($qty <= 0) {
                continue;
            }

            if (!isset($rates[$type])) {
                return back()->withErrors(['subscription' => 'Add-on ' . $type . ' sedang tidak tersedia.']);
            }

            $remaining = max(0, $rates[$type]['max_units'] - (int) ($activeUnits[$type] ?? 0));

            if ($qty > $remaining) {
                return back()->withErrors([
                    'subscription' => $rates[$type]['name'] . ' melebihi batas. Sisa yang dapat ditambahkan: '
                        . $remaining . ' x ' . $rates[$type]['unit_label'] . '.',
                ]);
            }

            $units[$type] = $qty;
        }

        if ($units === []) {
            return back()->withErrors(['subscription' => 'Pilih minimal satu kapasitas yang ingin ditambahkan.']);
        }

        $months = $validated['duration'] === 'end' ? null : 1;
        $quote = $this->subscriptions->addonQuote($base, $units, $months);

        if ($quote['days'] < 1 || $quote['total'] <= 0) {
            return back()->withErrors(['subscription' => 'Paket Anda sudah berakhir. Silakan perpanjang paket.']);
        }

        $signature = json_encode($units);

        $reuseUrl = $this->reuseOrCancelPending(
            $company->id,
            fn (PaymentTransaction $pending) =>
                $pending->purchase_type === SubscriptionService::TYPE_ADDON
                && (int) $pending->subscription_id === (int) $base->id
                && $pending->duration_months === $months
                && (int) round((float) $pending->gross_amount) === $quote['total']
                && data_get($pending->midtrans_response, 'addon_units') === $signature
        );

        if ($reuseUrl) {
            return back()->with('doku_checkout_url', $reuseUrl);
        }

        $storageItem = collect($quote['items'])->firstWhere('type', 'storage');

        try {
            $paymentUrl = DB::transaction(function () use (
                $company, $user, $doku, $base, $months, $quote, $storageItem, $signature
            ) {
                $paymentTransaction = PaymentTransaction::create([
                    'company_id' => $company->id,
                    'subscription_id' => $base->id,
                    'plan_id' => $base->plan_id,
                    'order_id' => $this->nextOrderId(),
                    'purchase_type' => SubscriptionService::TYPE_ADDON,
                    'plan_name' => $base->plan?->plan_name ?? 'Add-on',
                    'billing_cycle' => $base->billing_cycle,
                    'package_price' => 0,
                    'storage_addon_gb' => $storageItem ? round($storageItem['total_value'] / (1024 ** 3), 2) : 0,
                    'storage_addon_bytes' => $storageItem['total_value'] ?? 0,
                    'storage_addon_price' => $storageItem['price'] ?? 0,
                    'prorated_days' => $quote['days'],
                    'duration_months' => $months,
                    'discount_amount' => 0,
                    'gross_amount' => $quote['total'],
                    'transaction_status' => 'pending',
                    // Penanda isi keranjang (untuk pakai ulang link pembayaran).
                    'midtrans_response' => ['addon_units' => $signature],
                ]);

                $lineItems = [];

                foreach ($quote['items'] as $item) {
                    SubscriptionAddon::create([
                        'company_id' => $company->id,
                        'subscription_id' => $base->id,
                        'payment_transaction_id' => $paymentTransaction->id,
                        'type' => $item['type'],
                        'units' => $item['units'],
                        'unit_value' => $item['unit_value'],
                        'total_value' => $item['total_value'],
                        'price' => $item['price'],
                        'duration_months' => $months,
                        'start_date' => $quote['start'],
                        'end_date' => $quote['end'],
                        'status' => 'pending',
                    ]);

                    $lineItems[] = [
                        'id' => 'ADDON-' . strtoupper($item['type']),
                        'name' => $item['name'] . ' ' . $item['unit_label']
                            . ' (' . $quote['days'] . ' hari)',
                        'quantity' => $item['units'],
                        'price' => $item['unit_price'],
                    ];
                }

                return $this->createDokuCheckout($paymentTransaction, $lineItems, $user, $company, $doku);
            });

            return back()->with('doku_checkout_url', $paymentUrl);
        } catch (\Throwable $e) {
            return $this->paymentFailedResponse($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    protected function requireAdministrator(): void
    {
        $user = Auth::user();

        if (!$user instanceof User || !$user->isCompanyAdmin()) {
            abort(403, 'Only Administrator can manage subscription.');
        }
    }

    /**
     * Pesan yang jelas saat checkout DOKU gagal dibuat.
     * Detail teknis tetap tercatat di log (report()).
     */
    protected function paymentFailedResponse(\Throwable $e): RedirectResponse
    {
        report($e);

        $message = strtolower($e->getMessage());

        [$title, $text] = match (true) {
            // Server DOKU tidak dapat dihubungi (timeout, DNS, koneksi ditolak).
            $e instanceof \Illuminate\Http\Client\ConnectionException,
            str_contains($message, 'curl error'),
            str_contains($message, 'timed out') => [
                'Server Pembayaran Tidak Merespons',
                'Server pembayaran DOKU sedang tidak dapat dihubungi. '
                    . 'Tidak ada tagihan yang dibuat. Silakan coba beberapa saat lagi.',
            ],

            // Konfigurasi .env belum lengkap.
            str_contains($message, 'konfigurasi doku') => [
                'Pembayaran Belum Dikonfigurasi',
                'Konfigurasi pembayaran DOKU belum lengkap. Silakan hubungi tim teknis.',
            ],

            // DOKU menjawab, tetapi menolak permintaan.
            $e instanceof \Illuminate\Http\Client\RequestException,
            str_contains($message, 'doku') => [
                'Pembayaran Ditolak DOKU',
                'DOKU menolak permintaan pembayaran. Silakan coba lagi; '
                    . 'jika berulang, hubungi tim teknis.',
            ],

            default => [
                'Pembayaran Belum Dapat Diproses',
                'Terjadi kendala saat membuat pembayaran. Tidak ada tagihan yang dibuat. '
                    . 'Silakan coba lagi beberapa saat lagi.',
            ],
        };

        return back()
            ->withInput()
            ->with('payment_error', [
                'title' => $title,
                'message' => $text,
            ]);
    }

    /**
     * Jika ada transaksi pending yang sama persis dan link-nya masih
     * berlaku, kembalikan link tersebut. Selain itu batalkan pending lama.
     */
    protected function reuseOrCancelPending(int $companyId, callable $isSame): ?string
    {
        $pending = PaymentTransaction::query()
            ->where('company_id', $companyId)
            ->where('transaction_status', 'pending')
            ->latest('id')
            ->first();

        if (!$pending) {
            return null;
        }

        $notExpired = !$pending->expiry_time
            || Carbon::parse($pending->expiry_time)->isFuture();

        if ($notExpired && $pending->payment_url && $isSame($pending)) {
            return $pending->payment_url;
        }

        $this->subscriptions->failPayment($pending, 'cancel');

        return null;
    }

    protected function nextOrderId(): string
    {
        $lastSequence = PaymentTransaction::query()
            ->where('order_id', 'like', 'V-%')
            ->pluck('order_id')
            ->map(function ($existingOrderId) {
                return preg_match('/^V-(\d+)$/', (string) $existingOrderId, $matches)
                    ? (int) $matches[1]
                    : 0;
            })
            ->max() ?? 0;

        return 'V-' . str_pad((string) ($lastSequence + 1), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Buat checkout DOKU dan simpan payment URL ke transaksi.
     */
    protected function createDokuCheckout(
        PaymentTransaction $paymentTransaction,
        array $lineItems,
        User $user,
        $company,
        DokuService $doku
    ): string {
        $orderId = $paymentTransaction->order_id;

        $payload = [
            'order' => [
                'amount' => (int) round((float) $paymentTransaction->gross_amount),
                'invoice_number' => $orderId,
                'currency' => 'IDR',
                'callback_url' => route(
                    'subscription.payment.finish',
                    ['order_id' => $orderId]
                ),
                'line_items' => $lineItems,
            ],
            'payment' => [
                'payment_due_date' => 5,
            ],
            'customer' => [
                'id' => 'COMPANY-' . $company->id,
                'name' => $user->name ?: $company->company_name,
                'email' => $user->email,
            ],
        ];

        if ($user->phone) {
            $payload['customer']['phone'] = $user->phone;
        }

        $dokuResult = $doku->createPayment($payload);

        if (empty($dokuResult['payment_url'])) {
            throw new \RuntimeException(
                'DOKU tidak mengembalikan payment_url.'
            );
        }

        $paymentTransaction->update([
            'payment_url' => $dokuResult['payment_url'],
            'expiry_time' => $dokuResult['expiry_time'] ?? null,
        ]);

        return $dokuResult['payment_url'];
    }

    /**
     * Cek status ke DOKU dan validasi invoice + nominal.
     *
     * @return array{valid: bool, status: string, result: array}
     */
    protected function verifyWithDoku(
        PaymentTransaction $paymentTransaction,
        DokuService $doku
    ): array {
        $result = $doku->checkStatus($paymentTransaction->order_id);

        $gatewayInvoice = data_get($result, 'order.invoice_number');
        $gatewayAmount = data_get($result, 'order.amount');

        $valid = (string) $gatewayInvoice === (string) $paymentTransaction->order_id
            && is_numeric($gatewayAmount)
            && (int) $gatewayAmount === (int) $paymentTransaction->gross_amount;

        if (!$valid) {
            Log::warning('Invoice atau nominal DOKU tidak cocok.', [
                'order_id' => $paymentTransaction->order_id,
                'gateway_invoice' => $gatewayInvoice,
                'gateway_amount' => $gatewayAmount,
                'local_amount' => $paymentTransaction->gross_amount,
            ]);
        }

        return [
            'valid' => $valid,
            'status' => strtoupper((string) data_get($result, 'transaction.status', '')),
            'result' => $result,
        ];
    }

    /**
     * Transaksi pending yang sudah lewat batas waktu: cek ulang ke DOKU.
     * Sudah dibayar -> aktifkan. Belum -> tandai expired.
     * Dibatasi 3 transaksi per pemanggilan agar halaman tetap cepat.
     */
    protected function syncExpiredPendingPayments(int $companyId, DokuService $doku): void
    {
        $expired = PaymentTransaction::query()
            ->where('company_id', $companyId)
            ->where('transaction_status', 'pending')
            ->whereNotNull('expiry_time')
            ->where('expiry_time', '<', now())
            ->orderBy('id')
            ->limit(3)
            ->get();

        foreach ($expired as $transaction) {
            try {
                $check = $this->verifyWithDoku($transaction, $doku);

                if ($check['valid'] && $check['status'] === 'SUCCESS') {
                    $this->subscriptions->activatePayment($transaction, $check['result']);
                    continue;
                }

                $this->subscriptions->failPayment($transaction, 'expired', $check['result']);
            } catch (\Throwable $e) {
                /*
                 * DOKU tidak dapat dihubungi: biarkan pending agar
                 * pembayaran yang mungkin sudah lunas tidak hangus.
                 * Setelah 1 hari lewat batas, baru dianggap expired.
                 */
                if (Carbon::parse($transaction->expiry_time)->lt(now()->subDay())) {
                    $this->subscriptions->failPayment($transaction, 'expired');
                }

                Log::warning('Gagal cek ulang transaksi pending ke DOKU.', [
                    'order_id' => $transaction->order_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    protected function formatBytes(int|float $bytes): string
    {
        $bytes = (int) $bytes;

        if ($bytes >= 1024 ** 3) {
            return rtrim(rtrim(number_format($bytes / (1024 ** 3), 2, ',', '.'), '0'), ',') . ' GB';
        }

        return number_format($bytes / (1024 ** 2), 0, ',', '.') . ' MB';
    }

    /*
    |--------------------------------------------------------------------------
    | RENEW / CHANGE SUBSCRIPTION WITH MIDTRANS
    |--------------------------------------------------------------------------
    */

    public function renewmidtrans(
        Request $request,
        MidtransService $midtrans
    ): RedirectResponse {
        $user = Auth::user();

        $isAdministrator = $user
            && $user->role
            && $user->role->role_code === 'administrator';

        if (!$isAdministrator) {
            abort(403, 'Only Administrator can manage subscription.');
        }

        $validated = $request->validate([
            'plan_id' => [
                'required',
                'integer',
                'exists:plans,id',
            ],
            'billing_cycle' => [
                'required',
                'in:monthly,yearly',
            ],
        ]);

        $company = $user->company;

        if (!$company) {
            return back()->withErrors([
                'subscription' => 'Company tidak ditemukan.',
            ])->withInput();
        }

        $plan = Plan::query()
            ->whereKey($validated['plan_id'])
            ->where('status', 1)
            ->where('plan_code', '!=', 'FREE')
            ->first();

        if (!$plan) {
            return back()->withErrors([
                'subscription' =>
                    'Plan tidak tersedia atau tidak dapat dipilih.',
            ])->withInput();
        }

        $price = $validated['billing_cycle'] === 'yearly'
            ? $plan->price_yearly
            : $plan->price_monthly;

        if ($price === null || (float) $price <= 0) {
            return back()->withErrors([
                'subscription' => 'Harga subscription tidak tersedia.',
            ])->withInput();
        }

        $existingPendingPayment = $company
            ->paymentTransactions()
            ->with('subscription')
            ->where('transaction_status', 'pending')
            ->latest('id')
            ->first();

        if ($existingPendingPayment) {
            $samePlan = (int) $existingPendingPayment->plan_id
                === (int) $plan->id;

            $sameBillingCycle = $existingPendingPayment->subscription
                && $existingPendingPayment->subscription->billing_cycle
                    === $validated['billing_cycle'];

            if ($samePlan && $sameBillingCycle) {
                if ($existingPendingPayment->payment_url) {
                    return redirect()->away(
                        $existingPendingPayment->payment_url
                    );
                }

                return back()->withErrors([
                    'subscription' =>
                        'Pembayaran sebelumnya masih tersedia. '
                        . 'Silakan lanjutkan pembayaran tersebut.',
                ]);
            }

            DB::transaction(function () use ($existingPendingPayment) {
                $transaction = PaymentTransaction::query()
                    ->whereKey($existingPendingPayment->id)
                    ->lockForUpdate()
                    ->first();

                if (
                    !$transaction
                    || $transaction->transaction_status !== 'pending'
                    || $transaction->isPaid()
                ) {
                    return;
                }

                $transaction->transaction_status = 'cancel';
                $transaction->save();

                $subscription = Subscription::query()
                    ->whereKey($transaction->subscription_id)
                    ->lockForUpdate()
                    ->first();

                if (
                    $subscription
                    && $subscription->payment_status !== 'paid'
                    && $subscription->status === 'pending'
                ) {
                    $subscription->status = 'cancelled';
                    $subscription->payment_status = 'failed';
                    $subscription->save();
                }
            });
        }

        $customerName = $user->name ?: $company->company_name;
        $customerEmail = $user->email;
        $customerPhone = $user->phone;

        $orderId = 'SUB-'
            . $company->id
            . '-'
            . now()->format('YmdHis')
            . '-'
            . strtoupper(Str::random(6));

        DB::beginTransaction();

        try {
            $today = today();

            $previousSubscription = $company
                ->subscriptions()
                ->where('status', 'active')
                ->whereDate('start_date', '<=', $today)
                ->whereDate('end_date', '>=', $today)
                ->orderByDesc('end_date')
                ->lockForUpdate()
                ->first();

            $startDate = $previousSubscription
                ? Carbon::parse($previousSubscription->end_date)->addDay()
                : $today;

            $endDate = $validated['billing_cycle'] === 'yearly'
                ? $startDate->copy()->addYear()->subDay()
                : $startDate->copy()->addMonth()->subDay();

            $subscription = Subscription::create([
                'company_id' => $company->id,
                'plan_id' => $plan->id,
                'billing_cycle' => $validated['billing_cycle'],
                'price' => $price,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => 'pending',
                'payment_status' => 'pending',
            ]);

            $paymentTransaction = PaymentTransaction::create([
                'company_id' => $company->id,
                'subscription_id' => $subscription->id,
                'plan_id' => $plan->id,
                'order_id' => $orderId,
                'transaction_status' => 'pending',
                'gross_amount' => $price,
            ]);

            $midtransParams = [
                'transaction_details' => [
                    'order_id' => $orderId,
                    'gross_amount' => (int) $price,
                ],
                'item_details' => [
                    [
                        'id' => (string) $plan->id,
                        'price' => (int) $price,
                        'quantity' => 1,
                        'name' => 'Subscription '
                            . $plan->plan_name . ' - '
                            . ucfirst($validated['billing_cycle']),
                    ],
                ],
                'customer_details' => [
                    'first_name' => $customerName,
                    'email' => $customerEmail,
                ],
                'callbacks' => [
                    'finish' => route('subscription.payment.finish'),
                ],
            ];

            if ($customerPhone) {
                $midtransParams['customer_details']['phone'] = $customerPhone;
            }

            $midtransResult = $midtrans->createTransaction($midtransParams);
            $redirectUrl = $midtransResult['redirect_url'] ?? null;

            if (!$redirectUrl) {
                throw new \RuntimeException(
                    'Midtrans tidak mengembalikan payment URL.'
                );
            }

            $paymentTransaction->update([
                'payment_url' => $redirectUrl,
                'midtrans_response' => $midtransResult,
            ]);

            DB::commit();

            return redirect()->away($redirectUrl);
        } catch (\Throwable $e) {
            DB::rollBack();

            report($e);

            return back()->withErrors([
                'subscription' =>
                    'Gagal membuat pembayaran subscription. '
                    . 'Silakan coba lagi.',
            ])->withInput();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PAYMENT FINISH / DOKU STATUS VERIFICATION
    |--------------------------------------------------------------------------
    */

    public function paymentFinish(
        Request $request,
        DokuService $doku
    ): RedirectResponse {
        $user = Auth::user();

        abort_unless($user, 401);

        $orderId = $request->query('order_id')
            ?? $request->query('invoice_number');

        if (!$orderId) {
            return redirect()
                ->route('subscription.index')
                ->withErrors([
                    'subscription' =>
                        'Nomor invoice tidak ditemukan. '
                        . 'Silakan cek riwayat pembayaran.',
                ]);
        }

        $paymentTransaction = PaymentTransaction::query()
            ->where('order_id', $orderId)
            ->where('company_id', $user->company_id)
            ->first();

        if (!$paymentTransaction) {
            return redirect()
                ->route('subscription.index')
                ->withErrors([
                    'subscription' => 'Transaksi pembayaran tidak ditemukan.',
                ]);
        }

        try {
            // Status diambil langsung dari DOKU, bukan dipercaya dari URL.
            $check = $this->verifyWithDoku($paymentTransaction, $doku);
        } catch (\Throwable $e) {
            Log::error('Gagal memverifikasi pembayaran DOKU.', [
                'order_id' => $paymentTransaction->order_id,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('subscription.index')
                ->with(
                    'warning',
                    'Status pembayaran belum dapat diverifikasi. '
                    . 'Silakan cek kembali beberapa saat lagi.'
                );
        }

        if (!$check['valid']) {
            return redirect()
                ->route('subscription.index')
                ->withErrors([
                    'subscription' =>
                        'Validasi invoice atau nominal pembayaran gagal.',
                ]);
        }

        /*
         * PEMBAYARAN SUKSES
         */
        if ($check['status'] === 'SUCCESS') {
            $outcome = $this->subscriptions->activatePayment(
                $paymentTransaction,
                $check['result']
            );

            $subscription = Subscription::query()
                ->with('plan')
                ->find($paymentTransaction->subscription_id);

            $planName = $subscription?->plan?->plan_name ?? 'Paket';
            $start = $subscription?->start_date?->format('d/m/Y');
            $end = $subscription?->end_date?->format('d/m/Y');

            $message = match ($outcome) {
                'queued' => 'Pembayaran berhasil. Paket ' . $planName
                    . ' dijadwalkan aktif mulai ' . $start . ' s/d ' . $end
                    . ', setelah paket Anda saat ini berakhir.',
                'activated' => 'Pembayaran berhasil. Paket ' . $planName
                    . ' aktif mulai ' . $start . ' s/d ' . $end . '.',
                'upgraded' => 'Pembayaran berhasil. Paket ' . $planName
                    . ' aktif mulai hari ini s/d ' . $end
                    . ($subscription?->parent && $subscription->end_date->lt($subscription->parent->end_date)
                        ? ', lalu otomatis kembali ke ' . ($subscription->parent->plan?->plan_name ?? 'paket sebelumnya') . '.'
                        : '.'),
                'addon_applied' => 'Pembayaran berhasil. Kapasitas tambahan sudah aktif'
                    . ' sampai ' . (SubscriptionAddon::query()
                        ->where('payment_transaction_id', $paymentTransaction->id)
                        ->value('end_date')?->format('d/m/Y') ?? $end) . '.',
                default => 'Pembayaran ini sudah diproses sebelumnya.',
            };

            return redirect()
                ->route('subscription.index')
                ->with('success', $message);
        }

        /*
         * PEMBAYARAN GAGAL ATAU KEDALUWARSA
         */
        if (in_array($check['status'], ['FAILED', 'EXPIRED'], true)) {
            $this->subscriptions->failPayment(
                $paymentTransaction,
                $check['status'],
                $check['result']
            );

            return redirect()
                ->route('subscription.index')
                ->withErrors([
                    'subscription' => $check['status'] === 'EXPIRED'
                        ? 'Pembayaran telah kedaluwarsa. Silakan buat pembayaran baru.'
                        : 'Pembayaran dinyatakan gagal oleh DOKU.',
                ]);
        }

        /*
         * Status lain, termasuk PENDING, tidak mengaktifkan paket.
         */
        return redirect()
            ->route('subscription.index')
            ->with(
                'warning',
                'Pembayaran masih menunggu konfirmasi DOKU. '
                . 'Silakan cek kembali beberapa saat lagi.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | SUBSCRIPTION HISTORY
    |--------------------------------------------------------------------------
    */

    public function history(Request $request)
    {
        $user = Auth::user();

        abort_unless($user && $user->company_id, 403);

        $company = $user->company;

        $totalSubscriptions = Subscription::query()
            ->where('company_id', $user->company_id)
            ->count();

        $activeSubscription = Subscription::query()
            ->with('plan')
            ->where('company_id', $user->company_id)
            ->where('status', 'active')
            ->whereDate('start_date', '<=', today())
            ->whereDate('end_date', '>=', today())
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->first();

        /*
         * Ambil transaksi terbaru untuk setiap subscription.
         * LEFT JOIN membuat subscription Free tetap muncul.
         */
        $latestPaymentIds = PaymentTransaction::query()
            ->selectRaw('MAX(id) AS latest_id, subscription_id')
            ->where('company_id', $user->company_id)
            ->whereNotNull('subscription_id')
            // Add-on punya riwayat sendiri; jangan menimpa transaksi paket.
            ->whereIn('purchase_type', [
                SubscriptionService::TYPE_SUBSCRIPTION,
                SubscriptionService::TYPE_UPGRADE,
            ])
            ->groupBy('subscription_id');

        $query = Subscription::query()
            ->with('plan')
            ->leftJoinSub(
                $latestPaymentIds,
                'latest_payment_ids',
                function ($join) {
                    $join->on(
                        'subscriptions.id',
                        '=',
                        'latest_payment_ids.subscription_id'
                    );
                }
            )
            ->leftJoin(
                'payment_transactions as pt',
                'pt.id',
                '=',
                'latest_payment_ids.latest_id'
            )
            ->where('subscriptions.company_id', $user->company_id)
            ->select([
                'subscriptions.*',
                'pt.id as history_payment_id',
                'pt.order_id as history_order_id',
                'pt.plan_name as history_plan_name',
                'pt.billing_cycle as history_billing_cycle',
                'pt.package_price as history_package_price',
                'pt.storage_addon_gb as history_storage_gb',
                'pt.storage_addon_price as history_storage_price',
                'pt.discount_amount as history_discount',
                'pt.gross_amount as history_gross_amount',
                'pt.transaction_status as history_transaction_status',
            ])
            ->orderByDesc('subscriptions.id');

        if ($request->ajax()) {
            return DataTables::of($query)
                ->addIndexColumn()

                ->addColumn('invoice', function ($row) {
                    $invoice = $row->history_order_id;

                    if (
                        !$invoice
                        && strtoupper(
                            (string) ($row->plan?->plan_code ?? '')
                        ) === 'FREE'
                    ) {
                        $invoice = 'FREE';
                    }

                    return '<span class="invoice-code">'
                        . e($invoice ?: '-')
                        . '</span>';
                })

                ->addColumn('plan', function ($row) {
                    $planName = $row->history_plan_name
                        ?? $row->plan?->plan_name
                        ?? $row->plan?->name
                        ?? $row->plan?->plan_code
                        ?? 'Subscription';

                    $badge = match (true) {
                        $row->kind === 'upgrade' => '<span class="badge bg-primary-subtle text-primary ms-1">Upgrade</span>',
                        $row->billing_cycle === 'trial' => '<span class="badge bg-secondary-subtle text-secondary ms-1">Trial</span>',
                        $row->start_date && $row->start_date->isFuture() && $row->status === 'active'
                            => '<span class="badge bg-info-subtle text-info-emphasis ms-1">Terjadwal</span>',
                        default => '',
                    };

                    return '<span class="fw-bold text-dark">'
                        . e($planName)
                        . '</span>' . $badge;
                })

                ->addColumn('billing_cycle', function ($row) {
                    $cycle = $row->history_billing_cycle
                        ?? $row->billing_cycle
                        ?? '-';

                    return '<span class="text-dark">'
                        . e(ucfirst((string) $cycle))
                        . '</span>';
                })

                ->addColumn('package_price', function ($row) {
                    $price = $row->history_package_price
                        ?? $row->price
                        ?? 0;

                    return '<span class="money-value">Rp '
                        . number_format((float) $price, 0, ',', '.')
                        . '</span>';
                })

                ->addColumn('storage', function ($row) {
                    $storageGb = (float) (
                        $row->history_storage_gb ?? 0
                    );

                    $storagePrice = (float) (
                        $row->history_storage_price ?? 0
                    );

                    return '<div class="storage-value">'
                        . '<span class="fw-semibold">'
                        . number_format($storageGb, 2, ',', '.')
                        . ' GB</span><br>'
                        . '<small>Rp '
                        . number_format($storagePrice, 0, ',', '.')
                        . '</small></div>';
                })

                ->addColumn('discount', function ($row) {
                    return '<span class="money-value">Rp '
                        . number_format(
                            (float) ($row->history_discount ?? 0),
                            0,
                            ',',
                            '.'
                        )
                        . '</span>';
                })

                ->addColumn('total_price', function ($row) {
                    $total = $row->history_gross_amount
                        ?? $row->total_price
                        ?? $row->price
                        ?? 0;

                    return '<span class="money-value">Rp '
                        . number_format((float) $total, 0, ',', '.')
                        . '</span>';
                })

                ->addColumn('start_date', function ($row) {
                    if (!$row->start_date) {
                        return '-';
                    }

                    return '<span class="date-value">'
                        . e(Carbon::parse($row->start_date)->format('d-m-Y'))
                        . '</span>';
                })

                ->addColumn('end_date', function ($row) {
                    if (!$row->end_date) {
                        return '-';
                    }

                    return '<span class="date-value">'
                        . e(Carbon::parse($row->end_date)->format('d-m-Y'))
                        . '</span>';
                })

                ->addColumn('status', function ($row) {
                    $status = strtolower(
                        (string) ($row->status ?? 'unknown')
                    );

                    $labels = [
                        'active' => 'Active',
                        'pending' => 'Pending',
                        'expired' => 'Expired',
                        'cancelled' => 'Cancelled',
                        'canceled' => 'Cancelled',
                        'failed' => 'Failed',
                        'inactive' => 'Inactive',
                    ];

                    $allowed = [
                        'active',
                        'pending',
                        'expired',
                        'cancelled',
                        'failed',
                        'inactive',
                    ];

                    $class = in_array($status, $allowed, true)
                        ? 'status-' . $status
                        : 'status-unknown';

                    return '<span class="status-badge '
                        . e($class) . '">'
                        . e($labels[$status] ?? ucfirst($status))
                        . '</span>';
                })

                ->addColumn('payment_status', function ($row) {
                    $planCode = strtoupper(
                        (string) ($row->plan?->plan_code ?? '')
                    );

                    $transactionStatus = strtolower(
                        (string) ($row->history_transaction_status ?? '')
                    );

                    if (
                        !$row->history_payment_id
                        && $planCode === 'FREE'
                    ) {
                        return '<span class="status-badge status-success">'
                            . 'FREE</span>';
                    }

                    $status = $transactionStatus
                        ?: strtolower(
                            (string) ($row->payment_status ?? 'unknown')
                        );

                    $labels = [
                        'pending' => 'Pending',
                        'settlement' => 'Settlement',
                        'paid' => 'Paid',
                        'success' => 'Success',
                        'completed' => 'Completed',
                        'unpaid' => 'Unpaid',
                        'expired' => 'Expired',
                        'cancelled' => 'Cancelled',
                        'canceled' => 'Cancelled',
                        'failed' => 'Failed',
                        'failure' => 'Failure',
                        'denied' => 'Denied',
                        'cancel' => 'Cancelled',
                    ];

                    $allowed = [
                        'pending',
                        'settlement',
                        'paid',
                        'success',
                        'completed',
                        'unpaid',
                        'expired',
                        'cancelled',
                        'failed',
                        'failure',
                        'denied',
                        'cancel',
                    ];

                    $class = in_array($status, $allowed, true)
                        ? 'status-' . $status
                        : 'status-unknown';

                    return '<span class="status-badge '
                        . e($class) . '">'
                        . e($labels[$status] ?? ucfirst($status))
                        . '</span>';
                })

                ->addColumn('action', function ($row) {
                    if (!$row->history_payment_id) {
                        return '<span class="text-muted">-</span>';
                    }

                    return '<a href="'
                        . e(route(
                            'subscription.billing.print',
                            $row->history_payment_id
                        ))
                        . '" target="_blank" rel="noopener noreferrer" '
                        . 'class="btn btn-outline-primary btn-print-billing">'
                        . '<i class="bi bi-printer me-1"></i>'
                        . 'Print Billing</a>';
                })

                ->orderColumn('start_date', function ($query, $order) {
                    $query->orderBy(
                        'subscriptions.start_date',
                        $order
                    );
                })

                ->orderColumn('end_date', function ($query, $order) {
                    $query->orderBy(
                        'subscriptions.end_date',
                        $order
                    );
                })

                ->rawColumns([
                    'invoice',
                    'plan',
                    'billing_cycle',
                    'package_price',
                    'storage',
                    'discount',
                    'total_price',
                    'start_date',
                    'end_date',
                    'status',
                    'payment_status',
                    'action',
                ])
                ->toJson();
        }

        return view(
            'dashboard.subscription.history',
            [
                'company' => $company,
                'totalSubscriptions' => $totalSubscriptions,
                'activeSubscription' => $activeSubscription,
                // Riwayat pembelian kapasitas tambahan (add-on).
                'addonPurchases' => PaymentTransaction::query()
                    ->where('company_id', $user->company_id)
                    ->whereIn('purchase_type', [
                        SubscriptionService::TYPE_ADDON,
                        SubscriptionService::TYPE_STORAGE_ADDON,
                    ])
                    ->with('addons')
                    ->latest('id')
                    ->limit(50)
                    ->get(),
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | EXPIRED PAGE
    |--------------------------------------------------------------------------
    */

    public function expired(): View
    {
        $user = Auth::user();

        abort_unless($user, 401);

        $subscription = $user->company
            ? $user->company->subscriptions()
                ->with('plan')
                ->orderByDesc('end_date')
                ->first()
            : null;

        return view(
            'dashboard.subscription.expired',
            compact('subscription')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | USAGE & LIMITS
    |--------------------------------------------------------------------------
    */

    public function usage(): View
    {
        $user = Auth::user();

        abort_unless($user, 401);

        $companyId = $user->company_id;

        abort_unless($companyId, 403, 'Company tidak ditemukan.');

        /*
         * Kuota efektif hari ini = paket berlaku (upgrade menimpa regular)
         * + add-on aktif. Sumber yang sama dipakai pengecekan upload/tambah data.
         */
        $quota = $this->subscriptions->quota((int) $companyId);
        $used = $this->subscriptions->usage((int) $companyId);
        $subscription = $quota['subscription'];

        return view('dashboard.subscription.usage', [
            'planName' => $quota['plan']?->plan_name ?? 'Tidak ada paket aktif',
            'subscriptionStatus' => $subscription ? $subscription->status : 'inactive',
            'subscriptionEndsAt' => $subscription?->end_date,
            'activeUpgrade' => $this->subscriptions->activeUpgrade((int) $companyId),
            'activeAddons' => $this->subscriptions->activeAddons((int) $companyId),
            'addonTotals' => $quota['addons'],
            'canManage' => $user->isCompanyAdmin(),
            'usage' => [
                'users' => [
                    'used' => $used['users'],
                    'limit' => $quota['users'],
                ],
                'assets' => [
                    'used' => $used['assets'],
                    'limit' => $quota['assets'],
                ],
                'storage' => [
                    'used' => $used['storage'],
                    'limit' => $quota['storage'],
                ],
                'branches' => [
                    'used' => $used['branches'],
                    'limit' => $quota['branches'],
                ],
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | PRINT BILLING
    |--------------------------------------------------------------------------
    */

    public function printBilling(PaymentTransaction $paymentTransaction): View
    {
        $user = Auth::user();

        abort_unless($user && $user->company_id, 403);

        /*
         * Pengguna hanya boleh mencetak billing perusahaan sendiri.
         */
        $paymentTransaction = PaymentTransaction::query()
            ->with(['company', 'subscription.plan', 'plan'])
            ->whereKey($paymentTransaction->id)
            ->where('company_id', $user->company_id)
            ->firstOrFail();

        return view('dashboard.subscription.billing-print', [
            'payment' => $paymentTransaction,
            'company' => $paymentTransaction->company,
            'subscription' => $paymentTransaction->subscription,
            'plan' => $paymentTransaction->plan
                ?? $paymentTransaction->subscription?->plan,
        ]);
    }
}
