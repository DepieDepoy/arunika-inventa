<?php

namespace App\Http\Controllers;

use App\Models\PaymentTransaction;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\MidtransService;
use App\Services\DokuService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
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
        $company = $user->company;

        $isAdministrator =
            $user->role &&
            $user->role->role_code === 'administrator';

        if (!$isAdministrator) {
            abort(403, 'Only Administrator can manage subscription.');
        }

        // Ambil transaksi pending milik perusahaan.
        $pendingTransactions = $company
            ?->paymentTransactions()
            ->with('subscription')
            ->where('transaction_status', 'pending')
            ->get();

        // Periksa transaksi yang sudah melewati waktu kedaluwarsa.
        if ($pendingTransactions) {
            foreach ($pendingTransactions as $transaction) {
                if (
                    $transaction->expiry_time &&
                    \Carbon\Carbon::parse($transaction->expiry_time)->isPast()
                ) {
                    // Ubah transaksi hanya jika masih pending.
                    if ($transaction->transaction_status === 'pending') {
                        $transaction->update([
                            'transaction_status' => 'expired',
                        ]);

                        // Perbarui subscription hanya jika masih pending.
                        $subscription = $transaction->subscription;

                        if (
                            $subscription &&
                            $subscription->status === 'pending' &&
                            $subscription->payment_status === 'pending'
                        ) {
                            $subscription->update([
                                'status' => 'cancelled',
                                'payment_status' => 'failed',
                            ]);
                        }
                    }
                }
            }
        }

        $currentSubscription = $company
            ?->subscriptions()
            ->with('plan')
            ->where('status', 'active')
            ->whereDate('start_date', '<=', today())
            ->whereDate('end_date', '>=', today())
            ->latest('end_date')
            ->first();

        if (!$currentSubscription) {
            $currentSubscription = $company
                ?->subscriptions()
                ->with('plan')
                ->latest('end_date')
                ->first();
        }

        $plans = Plan::query()
            ->where('status', 1)
            ->where('plan_code', '!=', 'FREE')
            ->orderBy('price_monthly')
            ->get();

        // Ambil hanya transaksi yang masih pending dan belum kedaluwarsa.
        $pendingPayment = $company
            ?->paymentTransactions()
            ->with('plan')
            ->where('transaction_status', 'pending')
            ->where(function ($query) {
                $query->whereNull('expiry_time')
                    ->orWhere('expiry_time', '>', now());
            })
            ->latest()
            ->first();

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

        // Pertahankan pengecekan Administrator asli Vasetra.
        $isAdministrator =
            $user &&
            $user->role &&
            $user->role->role_code === 'administrator';

        if (!$isAdministrator) {
            abort(403, 'Only Administrator can manage subscription.');
        }

        // =========================================================
        // VALIDASI
        // =========================================================
        $validated = $request->validate([
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
            'billing_cycle' => ['required', 'in:monthly,yearly'],
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

        // =========================================================
        // AMBIL PAKET
        // =========================================================
        $plan = Plan::query()
            ->where('id', $validated['plan_id'])
            ->where('status', 1)
            ->where('plan_code', '!=', 'FREE')
            ->first();

        if (!$plan) {
            return back()->withErrors([
                'subscription' => 'Plan tidak tersedia.',
            ]);
        }

        $price = $validated['billing_cycle'] === 'yearly'
            ? $plan->price_yearly
            : $plan->price_monthly;

        if ($price === null || $price <= 0) {
            return back()->withErrors([
                'subscription' => 'Harga subscription tidak tersedia.',
            ]);
        }

        // =========================================================
        // HITUNG TAMBAHAN STORAGE
        // =========================================================
        $bytesPerStorageStep = 512 * 1024 * 1024;
        $maxExtraStorageBytes = 20 * 1024 * 1024 * 1024;

        $extraStorageBytes = (int) (
            $validated['extra_storage_bytes'] ?? 0
        );

        if (
            $extraStorageBytes > $maxExtraStorageBytes ||
            $extraStorageBytes % $bytesPerStorageStep !== 0
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

        // Belum menerapkan diskon; tetap nol seperti alur sebelumnya.
        $discountAmount = 0;

        $totalPrice = (int) round(
            (float) $price + $storageAddonPrice - $discountAmount
        );

        // =========================================================
        // CEK EXISTING PENDING PAYMENT
        // =========================================================
        $existingPending = $company
            ->paymentTransactions()
            ->with('subscription')
            ->where('transaction_status', 'pending')
            ->latest()
            ->first();

        if ($existingPending) {
            $samePlan =
                (int) $existingPending->plan_id === (int) $plan->id;

            $sameCycle =
                $existingPending->subscription &&
                $existingPending->subscription->billing_cycle
                    === $validated['billing_cycle'];

            $sameAmount =
                (int) round((float) $existingPending->gross_amount)
                    === $totalPrice;

            $sameStorage = abs(
                (float) $existingPending->storage_addon_gb
                    - $storageAddonGb
            ) < 0.001;

            if (
                $samePlan &&
                $sameCycle &&
                $sameAmount &&
                $sameStorage &&
                $existingPending->payment_url
            ) {
                return back()->with(
                    'doku_checkout_url',
                    $existingPending->payment_url
                );
            }

            DB::transaction(function () use ($existingPending) {
                $existingPending->update([
                    'transaction_status' => 'cancel',
                ]);

                if (
                    $existingPending->subscription &&
                    $existingPending->subscription->payment_status === 'pending'
                ) {
                    $existingPending->subscription->update([
                        'status' => 'cancelled',
                        'payment_status' => 'failed',
                    ]);
                }
            });
        }

        // =========================================================
        // GENERATE DOKU ORDER ID: V-0001, V-0002, DST.
        // =========================================================
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

        // =========================================================
        // CREATE SUBSCRIPTION AND DOKU PAYMENT
        // =========================================================
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
                $doku
            ) {
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
                    ? Carbon::parse(
                        $previousSubscription->end_date
                    )->addDay()
                    : $today;

                $endDate = $validated['billing_cycle'] === 'yearly'
                    ? $startDate->copy()->addYear()->subDay()
                    : $startDate->copy()->addMonth()->subDay();

                // Simpan rincian harga ke tabel subscriptions.
                $subscription = Subscription::create([
                    'company_id' => $company->id,
                    'plan_id' => $plan->id,
                    'billing_cycle' => $validated['billing_cycle'],

                    'price' => $price,
                    'storage_addon_gb' => $storageAddonGb,
                    'storage_addon_price' => $storageAddonPrice,
                    'discount_amount' => $discountAmount,
                    'total_price' => $totalPrice,

                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'status' => 'pending',
                    'payment_status' => 'pending',
                ]);

                // Simpan rincian invoice ke tabel payment_transactions.
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

                // Pertahankan struktur payload DOKU asli.
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

                // Tambahkan item storage bila dipilih.
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
                            'subscription.payment.finish'
                        ),
                        'line_items' => $lineItems,
                    ],
                    'payment' => [
                        'payment_due_date' => 1,
                    ],
                    'customer' => [
                        'id' => 'COMPANY-' . $company->id,
                        'name' => $user->name
                            ?: $company->company_name,
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
                    'Gagal membuat pembayaran DOKU. Silakan periksa log aplikasi.',
            ]);
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

        $isAdministrator =
            $user->role &&
            $user->role->role_code === 'administrator';

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
            return back()
                ->withErrors([
                    'subscription' => 'Company tidak ditemukan.',
                ])
                ->withInput();
        }

        $plan = Plan::query()
            ->where('id', $validated['plan_id'])
            ->where('status', 1)
            ->where('plan_code', '!=', 'FREE')
            ->first();

        if (!$plan) {
            return back()
                ->withErrors([
                    'subscription' =>
                        'Plan tidak tersedia atau tidak dapat dipilih.',
                ])
                ->withInput();
        }

        $existingPendingPayment = $company
            ->paymentTransactions()
            ->with('subscription')
            ->where('transaction_status', 'pending')
            ->latest()
            ->first();

        if ($existingPendingPayment) {
            $samePlan =
                (int) $existingPendingPayment->plan_id ===
                (int) $plan->id;

            $sameBillingCycle =
                $existingPendingPayment->subscription &&
                $existingPendingPayment->subscription->billing_cycle ===
                $validated['billing_cycle'];

            if ($samePlan && $sameBillingCycle) {
                if ($existingPendingPayment->payment_url) {
                    return redirect()->away(
                        $existingPendingPayment->payment_url
                    );
                }

                return back()
                    ->withErrors([
                        'subscription' =>
                            'Pembayaran sebelumnya masih tersedia. '
                            . 'Silakan lanjutkan pembayaran tersebut.',
                    ]);
            }

            DB::beginTransaction();

            try {
                $existingPendingPayment->update([
                    'transaction_status' => 'cancel',
                ]);

                if ($existingPendingPayment->subscription) {
                    $existingPendingPayment->subscription->update([
                        'status' => 'cancelled',
                        'payment_status' => 'failed',
                    ]);
                }

                DB::commit();

            } catch (\Throwable $e) {
                DB::rollBack();

                report($e);

                return back()
                    ->withErrors([
                        'subscription' =>
                            'Gagal mengganti transaksi subscription sebelumnya.',
                    ])
                    ->withInput();
            }
        }

        $price = $validated['billing_cycle'] === 'yearly'
            ? $plan->price_yearly
            : $plan->price_monthly;

        if ($price === null || $price <= 0) {
            return back()
                ->withErrors([
                    'subscription' =>
                        'Harga subscription tidak tersedia.',
                ])
                ->withInput();
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
                $midtransParams['customer_details']['phone'] =
                    $customerPhone;
            }

            $midtransResult = $midtrans->createTransaction(
                $midtransParams
            );

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

            return back()
                ->withErrors([
                    'subscription' =>
                        'Gagal membuat pembayaran subscription. '
                        . 'Silakan coba lagi.',
                ])
                ->withInput();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PAYMENT FINISH / RETURN PAGE
    |--------------------------------------------------------------------------
    |
    | Halaman ini tidak mengaktifkan subscription.
    | Aktivasi harus dilakukan setelah notifikasi pembayaran diverifikasi.
    |--------------------------------------------------------------------------
    */

    public function paymentFinish(Request $request): RedirectResponse
    {
        $orderId = $request->input('order_id');
        $transactionStatus = $request->input('transaction_status');

        $paymentTransaction = null;

        if ($orderId) {
            $paymentTransaction = PaymentTransaction::query()
                ->where('order_id', $orderId)
                ->first();
        }

        if (!$paymentTransaction) {
            return redirect()
                ->route('subscription.index')
                ->withErrors([
                    'subscription' =>
                        'Transaksi pembayaran tidak ditemukan. '
                        . 'Silakan cek status pembayaran Anda.',
                ]);
        }

        if (in_array(
            $transactionStatus,
            ['settlement', 'capture'],
            true
        )) {
            return redirect()
                ->route('subscription.index')
                ->with(
                    'success',
                    'Pembayaran telah diterima oleh Midtrans. '
                    . 'Subscription sedang diproses.'
                );
        }

        if (in_array(
            $transactionStatus,
            ['deny', 'cancel', 'expire', 'failure'],
            true
        )) {
            return redirect()
                ->route('subscription.index')
                ->withErrors([
                    'subscription' =>
                        'Pembayaran subscription tidak berhasil.',
                ]);
        }

        return redirect()
            ->route('subscription.index')
            ->with(
                'success',
                'Silakan selesaikan pembayaran subscription Anda.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | SUBSCRIPTION HISTORY
    |--------------------------------------------------------------------------
    */

    public function history(\Illuminate\Http\Request $request)
    {
        $user = \Illuminate\Support\Facades\Auth::user();

        abort_unless($user && $user->company_id, 403);

        $company = $user->company;

        // Ringkasan menghitung semua subscription, termasuk FREE.
        $totalSubscriptions = \App\Models\Subscription::query()
            ->where('company_id', $user->company_id)
            ->count();

        $activeSubscription = \App\Models\Subscription::query()
            ->with('plan')
            ->where('company_id', $user->company_id)
            ->where('status', 'active')
            ->latest('id')
            ->first();

        /*
        * Ambil ID transaksi terbaru untuk setiap subscription.
        * LEFT JOIN memastikan subscription FREE tetap tampil
        * walaupun tidak memiliki payment_transactions.
        */
        $latestPaymentIds = \App\Models\PaymentTransaction::query()
            ->selectRaw('MAX(id) AS latest_id, subscription_id')
            ->where('company_id', $user->company_id)
            ->whereNotNull('subscription_id')
            ->groupBy('subscription_id');

        $query = \App\Models\Subscription::query()
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
            return \Yajra\DataTables\Facades\DataTables::of($query)
                ->addIndexColumn()

                ->addColumn('invoice', function ($row) {
                    $invoice = $row->history_order_id;

                    if (!$invoice && strtoupper(
                        (string) ($row->plan?->plan_code ?? '')
                    ) === 'FREE') {
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
                        . e(\Carbon\Carbon::parse(
                            $row->start_date
                        )->format('d-m-Y'))
                        . '</span>';
                })

                ->addColumn('end_date', function ($row) {
                    if (!$row->end_date) {
                        return '-';
                    }

                    return '<span class="date-value">'
                        . e(\Carbon\Carbon::parse(
                            $row->end_date
                        )->format('d-m-Y'))
                        . '</span>';
                })

                ->addColumn('status', function ($row) {
                    $status = strtolower(
                        (string) ($row->status ?? 'unknown')
                    );

                    $labels = [
                        'active'    => 'Active',
                        'pending'   => 'Pending',
                        'expired'   => 'Expired',
                        'cancelled' => 'Cancelled',
                        'canceled'  => 'Cancelled',
                        'failed'    => 'Failed',
                        'inactive'  => 'Inactive',
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

                    // FREE tidak memerlukan transaksi pembayaran.
                    if (!$row->history_payment_id && $planCode === 'FREE') {
                        return '<span class="status-badge status-success">'
                            . 'FREE</span>';
                    }

                    $status = $transactionStatus
                        ?: strtolower(
                            (string) ($row->payment_status ?? 'unknown')
                        );

                    $labels = [
                        'pending'   => 'Pending',
                        'settlement'=> 'Settlement',
                        'paid'      => 'Paid',
                        'success'   => 'Success',
                        'completed' => 'Completed',
                        'unpaid'    => 'Unpaid',
                        'expired'   => 'Expired',
                        'cancelled' => 'Cancelled',
                        'canceled'  => 'Cancelled',
                        'failed'    => 'Failed',
                        'failure'   => 'Failure',
                        'denied'    => 'Denied',
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
                    // Tidak membuat tombol billing untuk FREE
                    // atau subscription yang belum memiliki transaksi.
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
                    $query->orderBy('subscriptions.start_date', $order);
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

        $subscription = $user->company
            ?->subscriptions()
            ->with('plan')
            ->latest('end_date')
            ->first();

        return view(
            'dashboard.subscription.expired',
            compact('subscription')
        );
    }

    public function usage()
    {
        $user = Auth::user();

        if (!$user) {
            abort(401);
        }

        // Ambil company pengguna yang sedang login.
        $companyId = $user->company_id;

        abort_unless($companyId, 403, 'Company tidak ditemukan.');

        /*
        |--------------------------------------------------------------------------
        | Ambil subscription aktif
        |--------------------------------------------------------------------------
        | Bagian ini perlu disesuaikan dengan relasi dan struktur tabel
        | subscription yang digunakan oleh project Vasetra.
        */

        $subscription = \App\Models\Subscription::where('company_id', $companyId)
            ->where('status', 'active')
            ->whereDate('end_date', '>=', now()->toDateString())
            ->latest('id')
            ->first();

        /*
        |--------------------------------------------------------------------------
        | Data paket
        |--------------------------------------------------------------------------
        */

        
        $plan = $subscription
            ? \App\Models\Plan::find($subscription->plan_id)
            : null;


        $planName = $plan->name ?? 'Free';
        $subscriptionStatus = $subscription->status ?? 'inactive';
        $subscriptionEndsAt = $subscription->end_date ?? null;

        /*
        |--------------------------------------------------------------------------
        | Hitung jumlah user
        |--------------------------------------------------------------------------
        */

        $usersUsed = \App\Models\User::where('company_id', $companyId)->count();

        /*
        |--------------------------------------------------------------------------
        | Hitung jumlah aset
        |--------------------------------------------------------------------------
        | Menggunakan withTrashed() agar aset yang soft-deleted tetap
        | dapat diperhitungkan jika tabel Asset memakai SoftDeletes.
        | Jika kuota harus hanya menghitung aset aktif, hapus withTrashed().
        */

        $assetsQuery = \App\Models\Asset::where('company_id', $companyId);

        if (method_exists(\App\Models\Asset::class, 'bootSoftDeletes')) {
            $assetsQuery->withTrashed();
        }

        $assetsUsed = $assetsQuery->count();

        /*
        |--------------------------------------------------------------------------
        | Batas paket
        |--------------------------------------------------------------------------
        | Nama kolom di bawah adalah contoh umum. Sesuaikan dengan kolom
        | yang benar pada tabel subscription_plans Vasetra.
        */

        $usersLimit = (int) ($plan->max_users ?? 5);
        $assetsLimit = (int) ($plan->max_assets ?? 5);

        /*
        |--------------------------------------------------------------------------
        | Hitung storage
        |--------------------------------------------------------------------------
        | File fisik dihitung dari:
        | storage/app/public/documents/{company_code}
        |
        | File temp import Excel tidak ikut dihitung.
        */

        $company = \App\Models\Company::find($companyId);

        $companyCode = $company->company_code ?? null;

        $storageUsed = 0;

        if ($companyCode) {
            $storagePath = storage_path(
                'app/public/documents/' . $companyCode
            );

            if (\Illuminate\Support\Facades\File::isDirectory($storagePath)) {
                $files = \Illuminate\Support\Facades\File::allFiles($storagePath);

                foreach ($files as $file) {
                    $storageUsed += $file->getSize();
                }
            }
        }

        // Contoh kuota default 1 GB jika kolom paket belum tersedia.
        $storageLimit = (int) ($plan->max_storage_bytes ?? 1073741824);

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

    public function printBilling(PaymentTransaction $paymentTransaction)
    {
        $user = Auth::user();

        abort_unless($user && $user->company_id, 403);

        // Batasi billing hanya untuk perusahaan milik pengguna login.
        $paymentTransaction = PaymentTransaction::query()
            ->with(['company', 'subscription.plan', 'plan'])
            ->where('id', $paymentTransaction->id)
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
