<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\PaymentTransaction;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\CompanyStorage;
use App\Services\DokuService;
use App\Services\MidtransService;
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

    public function index(): View
    {
        $user = Auth::user();

        abort_unless($user, 401);

        $company = $user->company;

        $isAdministrator = $user->role
            && $user->role->role_code === 'administrator';

        if (!$isAdministrator) {
            abort(403, 'Only Administrator can manage subscription.');
        }

        /*
         * Periksa transaksi pending yang sudah kedaluwarsa.
         * Transaksi yang sudah paid tidak disentuh.
         */
        $pendingTransactions = $company
            ? $company->paymentTransactions()
                ->with('subscription')
                ->where('transaction_status', 'pending')
                ->get()
            : collect();

        foreach ($pendingTransactions as $transaction) {
            if (
                !$transaction->expiry_time
                || !Carbon::parse($transaction->expiry_time)->isPast()
            ) {
                continue;
            }

            DB::transaction(function () use ($transaction) {
                $lockedTransaction = PaymentTransaction::query()
                    ->whereKey($transaction->id)
                    ->lockForUpdate()
                    ->first();

                if (
                    !$lockedTransaction
                    || $lockedTransaction->transaction_status !== 'pending'
                    || $lockedTransaction->isPaid()
                ) {
                    return;
                }

                $lockedTransaction->transaction_status = 'expired';
                $lockedTransaction->save();

                $subscription = Subscription::query()
                    ->whereKey($lockedTransaction->subscription_id)
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

        /*
         * Paket saat ini adalah paket yang benar-benar aktif hari ini.
         */
        $currentSubscription = $company
            ? $company->subscriptions()
                ->with('plan')
                ->where('status', 'active')
                ->whereDate('start_date', '<=', today())
                ->whereDate('end_date', '>=', today())
                ->orderByDesc('start_date')
                ->orderByDesc('id')
                ->first()
            : null;

        /*
         * Jika belum ada paket aktif, tampilkan riwayat terakhir
         * sebagai informasi, tetapi jangan menganggapnya aktif.
         */
        if (!$currentSubscription && $company) {
            $currentSubscription = $company
                ->subscriptions()
                ->with('plan')
                ->orderByDesc('id')
                ->first();
        }

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

        return view(
            'dashboard.subscription.index',
            compact(
                'company',
                'currentSubscription',
                'plans',
                'pendingPayment'
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
            'extra_storage_bytes' => [
                'nullable',
                'integer',
                'min:0',
                'max:21474836480',
                function ($attribute, $value, $fail) {
                    $step = 512 * 1024 * 1024;

                    if ((int) $value % $step !== 0) {
                        $fail(
                            'Tambahan storage harus kelipatan 512 MB.'
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
         * Ambil paket yang tersedia dan bukan paket FREE.
         */
        $plan = Plan::query()
            ->whereKey($validated['plan_id'])
            ->where('status', 1)
            ->where('plan_code', '!=', 'FREE')
            ->first();

        if (!$plan) {
            return back()->withErrors([
                'subscription' => 'Plan tidak tersedia.',
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

        /*
         * Hitung storage tambahan.
         * Satu langkah = 512 MiB.
         */
        $bytesPerStorageStep = 512 * 1024 * 1024;
        $maxExtraStorageBytes = 20 * 1024 * 1024 * 1024;

        $extraStorageBytes = (int) (
            $validated['extra_storage_bytes'] ?? 0
        );

        if (
            $extraStorageBytes > $maxExtraStorageBytes
            || $extraStorageBytes % $bytesPerStorageStep !== 0
        ) {
            return back()->withErrors([
                'subscription' =>
                    'Tambahan storage maksimal 20 GB dan harus kelipatan 512 MB.',
            ])->withInput();
        }

        $storageSteps = (int) (
            $extraStorageBytes / $bytesPerStorageStep
        );

        $storageAddonGb = $storageSteps * 0.5;

        $pricePer500MbMonthly = (int) config(
            'subscription.storage_addon.price_per_500mb_monthly',
            10000
        );

        $billingMultiplier = $validated['billing_cycle'] === 'yearly'
            ? 12
            : 1;

        $storageUnitPrice = $pricePer500MbMonthly * $billingMultiplier;
        $storageAddonPrice = $storageSteps * $storageUnitPrice;

        // Diskon tetap mengikuti perilaku sebelumnya: belum diterapkan.
        $discountAmount = 0;

        $totalPrice = (int) round(
            (float) $price + $storageAddonPrice - $discountAmount
        );

        /*
         * Periksa transaksi pending milik perusahaan ini.
         * Jika masih cocok, gunakan kembali URL pembayaran.
         */
        $existingPending = $company
            ->paymentTransactions()
            ->with('subscription')
            ->where('transaction_status', 'pending')
            ->latest('id')
            ->first();

        if ($existingPending) {
            $samePlan = (int) $existingPending->plan_id === (int) $plan->id;

            $sameCycle = $existingPending->subscription
                && $existingPending->subscription->billing_cycle
                    === $validated['billing_cycle'];

            $sameAmount = (int) round(
                (float) $existingPending->gross_amount
            ) === $totalPrice;

            $sameStorage = abs(
                (float) $existingPending->storage_addon_gb
                - $storageAddonGb
            ) < 0.001;

            $notExpired = !$existingPending->expiry_time
                || Carbon::parse($existingPending->expiry_time)->isFuture();

            if (
                $samePlan
                && $sameCycle
                && $sameAmount
                && $sameStorage
                && $notExpired
                && $existingPending->payment_url
            ) {
                return back()->with(
                    'doku_checkout_url',
                    $existingPending->payment_url
                );
            }

            /*
             * Batalkan hanya transaksi lama yang masih pending.
             * Jangan mengubah transaksi yang sudah dibayar.
             */
            DB::transaction(function () use ($existingPending) {
                $transaction = PaymentTransaction::query()
                    ->whereKey($existingPending->id)
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

        /*
         * Generate order ID V-0001, V-0002, dan seterusnya.
         */
        $lastSequence = PaymentTransaction::query()
            ->where('order_id', 'like', 'V-%')
            ->pluck('order_id')
            ->map(function ($existingOrderId) {
                if (
                    preg_match(
                        '/^V-(\d+)$/',
                        (string) $existingOrderId,
                        $matches
                    )
                ) {
                    return (int) $matches[1];
                }

                return 0;
            })
            ->max() ?? 0;

        $orderId = 'V-' . str_pad(
            (string) ($lastSequence + 1),
            4,
            '0',
            STR_PAD_LEFT
        );

        try {
            $result = DB::transaction(function () use (
                $company,
                $plan,
                $validated,
                $price,
                $storageAddonGb,
                $storageAddonPrice,
                $storageSteps,
                $storageUnitPrice,
                $discountAmount,
                $totalPrice,
                $orderId,
                $user,
                $doku,
                $extraStorageBytes
            ) {
                $today = today();

                /*
                 * Ambil paket yang saat ini aktif.
                 * Lock mengurangi risiko dua proses membaca data bersamaan.
                 */
                $previousSubscription = $company
                    ->subscriptions()
                    ->where('status', 'active')
                    ->whereDate('start_date', '<=', $today)
                    ->whereDate('end_date', '>=', $today)
                    ->orderByDesc('end_date')
                    ->lockForUpdate()
                    ->first();

                /*
                 * Paket baru akan aktif saat pembayaran terverifikasi.
                 * Tanggal mulai disimpan sebagai hari ini.
                 *
                 * Masa akhir menghitung sisa paket lama:
                 * Free berakhir 10 November + Starter bulanan
                 * = Starter berakhir 10 Desember.
                 */
                $startDate = $today;

                $billingBaseDate = $previousSubscription
                    ? Carbon::parse($previousSubscription->end_date)
                    : $today->copy();

                $endDate = $validated['billing_cycle'] === 'yearly'
                    ? $billingBaseDate->copy()->addYearNoOverflow()
                    : $billingBaseDate->copy()->addMonthNoOverflow();

                /*
                 * Kuota dasar harus berasal dari konfigurasi plan.
                 * Tidak mengubah data plan secara otomatis.
                 */
                $baseStorageBytes = (int) ($plan->max_storage_bytes ?? 0);

                if ($baseStorageBytes <= 0) {
                    throw new \RuntimeException(
                        'Kuota storage dasar paket tidak valid. '
                        . 'Periksa kolom max_storage_bytes pada tabel plans.'
                    );
                }

                $storageAddonBytes = $extraStorageBytes;
                $totalStorageBytes = $baseStorageBytes + $storageAddonBytes;

                $subscription = Subscription::create([
                    'company_id' => $company->id,
                    'plan_id' => $plan->id,
                    'billing_cycle' => $validated['billing_cycle'],

                    'price' => $price,
                    'storage_addon_gb' => $storageAddonGb,
                    'storage_addon_price' => $storageAddonPrice,

                    'base_storage_bytes' => $baseStorageBytes,
                    'storage_addon_bytes' => $storageAddonBytes,
                    'total_storage_bytes' => $totalStorageBytes,

                    'discount_amount' => $discountAmount,
                    'total_price' => $totalPrice,

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
                    'plan_name' => $plan->plan_name,
                    'billing_cycle' => $validated['billing_cycle'],

                    'package_price' => $price,
                    'storage_addon_gb' => $storageAddonGb,
                    'storage_addon_price' => $storageAddonPrice,
                    'discount_amount' => $discountAmount,

                    'gross_amount' => $totalPrice,
                    'transaction_status' => 'pending',
                    'payment_url' => null,
                    'expiry_time' => null,
                ]);

                $lineItems = [
                    [
                        'id' => (string) $plan->id,
                        'name' => 'Subscription '
                            . $plan->plan_name . ' - '
                            . ucfirst($validated['billing_cycle']),
                        'quantity' => 1,
                        'price' => (int) $price,
                    ],
                ];

                if ($storageSteps > 0) {
                    $lineItems[] = [
                        'id' => 'STORAGE-' . $storageSteps,
                        'name' => 'Tambahan Storage '
                            . number_format(
                                $storageAddonGb,
                                1,
                                '.',
                                ''
                            )
                            . ' GB - '
                            . ucfirst($validated['billing_cycle']),
                        'quantity' => $storageSteps,
                        'price' => $storageUnitPrice,
                    ];
                }

                $payload = [
                    'order' => [
                        'amount' => $totalPrice,
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

                return [
                    'payment_url' => $dokuResult['payment_url'],
                ];
            });

            return back()->with(
                'doku_checkout_url',
                $result['payment_url']
            );
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors([
                'subscription' =>
                    'Gagal membuat pembayaran DOKU. '
                    . 'Silakan periksa log aplikasi.',
            ])->withInput();
        }
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
            $result = $doku->checkStatus($paymentTransaction->order_id);
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

        $gatewayInvoice = data_get($result, 'order.invoice_number');
        $gatewayAmount = data_get($result, 'order.amount');
        $gatewayStatus = strtoupper(
            (string) data_get($result, 'transaction.status', '')
        );

        /*
         * Pastikan invoice dan nominal yang dikonfirmasi DOKU cocok.
         */
        if (
            (string) $gatewayInvoice !== (string) $paymentTransaction->order_id
            || !is_numeric($gatewayAmount)
            || (int) $gatewayAmount !== (int) $paymentTransaction->gross_amount
        ) {
            Log::warning('Invoice atau nominal DOKU tidak cocok.', [
                'order_id' => $paymentTransaction->order_id,
                'gateway_invoice' => $gatewayInvoice,
                'gateway_amount' => $gatewayAmount,
                'local_amount' => $paymentTransaction->gross_amount,
            ]);

            return redirect()
                ->route('subscription.index')
                ->withErrors([
                    'subscription' =>
                        'Validasi invoice atau nominal pembayaran gagal.',
                ]);
        }

        /*
         * PEMBAYARAN SUKSES
         *
         * Subscription baru langsung aktif.
         * Subscription aktif lain, termasuk Free, dinonaktifkan.
         * Data lama tidak dihapus; statusnya menjadi cancelled.
         */
        if ($gatewayStatus === 'SUCCESS') {
            DB::transaction(function () use ($paymentTransaction, $result) {
                $transaction = PaymentTransaction::query()
                    ->whereKey($paymentTransaction->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                /*
                 * Callback berulang tidak boleh memproses transaksi sukses
                 * untuk kedua kalinya.
                 */
                if ($transaction->isPaid()) {
                    return;
                }

                $subscription = Subscription::query()
                    ->whereKey($transaction->subscription_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                /*
                 * Simpan status transaksi terlebih dahulu.
                 */
                $transaction->transaction_status = 'success';

                $gatewayDate = data_get($result, 'transaction.date');

                $transaction->transaction_time = $gatewayDate
                    ? Carbon::parse($gatewayDate)
                    : now();

                $transaction->settlement_time = now();
                $transaction->midtrans_response = $result;
                $transaction->save();

                /*
                 * Aktifkan subscription yang pembayarannya terverifikasi.
                 */
                $subscription->payment_status = 'paid';
                $subscription->status = 'active';
                $subscription->save();

                /*
                 * Nonaktifkan subscription lain yang masih aktif.
                 * Baris lama tetap ada untuk keperluan riwayat.
                 */
                Subscription::query()
                    ->where('company_id', $transaction->company_id)
                    ->where('id', '!=', $subscription->id)
                    ->where('status', 'active')
                    ->update([
                        'status' => 'cancelled',
                    ]);
            });

            return redirect()
                ->route('subscription.index')
                ->with(
                    'success',
                    'Pembayaran berhasil diverifikasi oleh DOKU. '
                    . 'Subscription telah diperbarui.'
                );
        }

        /*
         * PEMBAYARAN GAGAL ATAU KEDALUWARSA
         */
        if (in_array($gatewayStatus, ['FAILED', 'EXPIRED'], true)) {
            DB::transaction(function () use (
                $paymentTransaction,
                $gatewayStatus,
                $result
            ) {
                $transaction = PaymentTransaction::query()
                    ->whereKey($paymentTransaction->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                // Jangan mengubah transaksi yang sudah berhasil dibayar.
                if ($transaction->isPaid()) {
                    return;
                }

                $transaction->transaction_status = strtolower($gatewayStatus);
                $transaction->midtrans_response = $result;
                $transaction->save();

                $subscription = Subscription::query()
                    ->whereKey($transaction->subscription_id)
                    ->lockForUpdate()
                    ->first();

                if (
                    $subscription
                    && $subscription->payment_status !== 'paid'
                ) {
                    $subscription->status = 'cancelled';
                    $subscription->payment_status = 'failed';
                    $subscription->save();
                }
            });

            return redirect()
                ->route('subscription.index')
                ->withErrors([
                    'subscription' => $gatewayStatus === 'EXPIRED'
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

                    return '<span class="fw-bold text-dark">'
                        . e($planName)
                        . '</span>';
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
            compact(
                'company',
                'totalSubscriptions',
                'activeSubscription'
            )
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
         * Ambil paket yang aktif pada tanggal sekarang.
         * Paket pending atau paket yang belum dimulai tidak dihitung.
         */
        $subscription = Subscription::query()
            ->where('company_id', $companyId)
            ->where('status', 'active')
            ->whereDate('start_date', '<=', today())
            ->whereDate('end_date', '>=', today())
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->first();

        $plan = $subscription
            ? Plan::find($subscription->plan_id)
            : null;

        $planName = $plan
            ? ($plan->plan_name ?? $plan->name ?? $plan->plan_code ?? 'Free')
            : 'Free';

        $subscriptionStatus = $subscription
            ? $subscription->status
            : 'inactive';

        $subscriptionEndsAt = $subscription?->end_date;

        /*
         * Jumlah pengguna perusahaan.
         */
        $usersUsed = User::query()
            ->where('company_id', $companyId)
            ->count();

        /*
         * Jumlah aset perusahaan.
         */
        $assetsQuery = Asset::query()
            ->where('company_id', $companyId);

        /*
         * Pertahankan perhitungan aset yang digunakan saat ini.
         * Jika model menggunakan SoftDeletes, withTrashed() turut
         * menghitung aset yang sudah dihapus secara lunak.
         */
        if (method_exists(Asset::class, 'bootSoftDeletes')) {
            $assetsQuery->withTrashed();
        }

        $assetsUsed = $assetsQuery->count();

        /*
         * Batas user dan aset berasal dari paket aktif.
         */
        $usersLimit = (int) ($plan?->max_users ?? 5);
        $assetsLimit = (int) ($plan?->max_assets ?? 5);

        /*
         * CompanyStorage menjadi sumber tunggal perhitungan storage.
         */
        $storageUsed = CompanyStorage::usedBytes($companyId);
        $storageLimit = CompanyStorage::limitBytes($companyId);

        return view('dashboard.subscription.usage', [
            'planName' => $planName,
            'subscriptionStatus' => $subscriptionStatus,
            'subscriptionEndsAt' => $subscriptionEndsAt,

            'usage' => [
                'users' => [
                    'used' => $usersUsed,
                    'limit' => $usersLimit,
                ],
                'assets' => [
                    'used' => $assetsUsed,
                    'limit' => $assetsLimit,
                ],
                'storage' => [
                    'used' => $storageUsed,
                    'limit' => $storageLimit,
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
