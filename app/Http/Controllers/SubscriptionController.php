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

        $isAdministrator =
            $user->role &&
            $user->role->role_code === 'administrator';

        if (!$isAdministrator) {
            abort(403, 'Only Administrator can manage subscription.');
        }

        $validated = $request->validate([
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
            'billing_cycle' => ['required', 'in:monthly,yearly'],
        ]);

        $company = $user->company;

        if (!$company) {
            return back()->withErrors([
                'subscription' => 'Company tidak ditemukan.',
            ]);
        }

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

        /*
        |--------------------------------------------------------------------------
        | CHECK EXISTING PENDING PAYMENT
        |--------------------------------------------------------------------------
        */

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

            if (
                $samePlan &&
                $sameCycle &&
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

                if ($existingPending->subscription) {
                    $existingPending->subscription->update([
                        'status' => 'cancelled',
                        'payment_status' => 'failed',
                    ]);
                }
            });
        }

        /*
        |--------------------------------------------------------------------------
        | GENERATE DOKU ORDER ID
        |--------------------------------------------------------------------------
        |
        | Format baru:
        | V-0001
        | V-0002
        | V-0003
        |
        | Format lama seperti SUB-... dan V-1-... tetap dibiarkan.
        | Hanya order_id dengan format tepat V-angka yang dihitung.
        |--------------------------------------------------------------------------
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

        /*
        |--------------------------------------------------------------------------
        | CREATE SUBSCRIPTION AND DOKU PAYMENT
        |--------------------------------------------------------------------------
        */

        try {
            $result = DB::transaction(function () use (
                $company,
                $plan,
                $validated,
                $price,
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

                $payload = [
                    'order' => [
                        'amount' => (int) $price,
                        'invoice_number' => $orderId,
                        'currency' => 'IDR',
                        'callback_url' => route(
                            'subscription.payment.finish'
                        ),
                        'line_items' => [
                            [
                                'id' => (string) $plan->id,
                                'name' => 'Subscription '
                                    . $plan->plan_name . ' - '
                                    . ucfirst($validated['billing_cycle']),
                                'quantity' => 1,
                                'price' => (int) $price,
                            ],
                        ],
                    ],
                    'payment' => [
                        'payment_due_date' => 1, //timeout payment
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

    public function history(Request $request): View
    {
        $user = Auth::user();

        $isAdministrator =
            $user->role &&
            $user->role->role_code === 'administrator';

        if (!$isAdministrator) {
            abort(
                403,
                'Only Administrator can view subscription history.'
            );
        }

        $company = $user->company;

        if (!$company) {
            abort(404, 'Company tidak ditemukan.');
        }

        $subscriptions = $company
            ->subscriptions()
            ->with('plan')
            ->latest('start_date')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        $totalSubscriptions = $company
            ->subscriptions()
            ->count();

        $activeSubscription = $company
            ->subscriptions()
            ->where('status', 'active')
            ->whereDate('start_date', '<=', today())
            ->whereDate('end_date', '>=', today())
            ->latest('end_date')
            ->first();

        return view(
            'dashboard.subscription.history',
            compact(
                'company',
                'subscriptions',
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
}
