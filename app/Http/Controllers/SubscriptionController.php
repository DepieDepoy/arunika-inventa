<?php

namespace App\Http\Controllers;

use App\Models\PaymentTransaction;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\MidtransService;
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

        /*
        |--------------------------------------------------------------------------
        | ONLY ADMINISTRATOR
        |--------------------------------------------------------------------------
        */

        $isAdministrator =
            $user->role &&
            $user->role->role_code === 'administrator';

        if (!$isAdministrator) {
            abort(
                403,
                'Only Administrator can manage subscription.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | CURRENT ACTIVE SUBSCRIPTION
        |--------------------------------------------------------------------------
        */

        $currentSubscription = $company
            ?->subscriptions()
            ->with('plan')
            ->where('status', 'active')
            ->whereDate('start_date', '<=', today())
            ->whereDate('end_date', '>=', today())
            ->latest('end_date')
            ->first();

        /*
        |--------------------------------------------------------------------------
        | FALLBACK
        |--------------------------------------------------------------------------
        |
        | Kalau tidak ada subscription aktif, tampilkan subscription terakhir.
        |
        */

        if (!$currentSubscription) {
            $currentSubscription = $company
                ?->subscriptions()
                ->with('plan')
                ->latest('end_date')
                ->first();
        }

        /*
        |--------------------------------------------------------------------------
        | AVAILABLE PLANS
        |--------------------------------------------------------------------------
        |
        | FREE hanya untuk trial awal.
        |
        */

        $plans = Plan::query()
            ->where('status', 1)
            ->where('plan_code', '!=', 'FREE')
            ->orderBy('price_monthly')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | PENDING PAYMENT
        |--------------------------------------------------------------------------
        |
        | Jika Administrator sudah membuat transaksi tetapi belum membayar,
        | tampilkan transaksi tersebut.
        |
        */

        $pendingPayment = $company
            ?->paymentTransactions()
            ->with('plan')
            ->where('transaction_status', 'pending')
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
    | RENEW / CHANGE SUBSCRIPTION
    |--------------------------------------------------------------------------
    |
    | Alur:
    |
    | Administrator klik Renew
    |        ↓
    | Subscription = pending
    |        ↓
    | PaymentTransaction = pending
    |        ↓
    | Midtrans Snap
    |        ↓
    | Redirect ke halaman pembayaran Midtrans
    |
    | Subscription TIDAK langsung menjadi active.
    |
    |--------------------------------------------------------------------------
    */

    public function renew(
        Request $request,
        MidtransService $midtrans
    ): RedirectResponse {

        $user = Auth::user();

        /*
        |--------------------------------------------------------------------------
        | ONLY ADMINISTRATOR
        |--------------------------------------------------------------------------
        */

        $isAdministrator =
            $user->role &&
            $user->role->role_code === 'administrator';

        if (!$isAdministrator) {
            abort(
                403,
                'Only Administrator can manage subscription.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | VALIDATION
        |--------------------------------------------------------------------------
        */

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

        /*
        |--------------------------------------------------------------------------
        | COMPANY
        |--------------------------------------------------------------------------
        */

        $company = $user->company;

        if (!$company) {
            return back()
                ->withErrors([
                    'subscription' => 'Company tidak ditemukan.',
                ])
                ->withInput();
        }

        /*
        |--------------------------------------------------------------------------
        | FIND PLAN
        |--------------------------------------------------------------------------
        */

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

        /*
        |--------------------------------------------------------------------------
        | CHECK EXISTING PENDING PAYMENT
        |--------------------------------------------------------------------------
        |
        | Jika pending payment memiliki plan + billing cycle yang sama,
        | lanjutkan pembayaran yang lama.
        |
        | Jika berbeda, batalkan pending payment lama dan buat
        | transaksi baru sesuai pilihan Administrator.
        |
        */

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

            /*
            |--------------------------------------------------------------------------
            | SAME PLAN + SAME BILLING CYCLE
            |--------------------------------------------------------------------------
            |
            | Lanjutkan pembayaran yang sebelumnya.
            |
            */

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

            /*
            |--------------------------------------------------------------------------
            | DIFFERENT PLAN / BILLING CYCLE
            |--------------------------------------------------------------------------
            |
            | User mengganti pilihan subscription.
            |
            | Pending lama tidak lagi digunakan.
            |
            */

            DB::beginTransaction();

            try {

                /*
                |----------------------------------------------------------------------
                | Cancel old payment
                |----------------------------------------------------------------------
                */

                $existingPendingPayment->update([
                    'transaction_status' => 'cancel',
                ]);

                /*
                |----------------------------------------------------------------------
                | Cancel old pending subscription
                |----------------------------------------------------------------------
                */

                if ($existingPendingPayment->subscription) {

                    $existingPendingPayment
                        ->subscription
                        ->update([
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

        /*
        |--------------------------------------------------------------------------
        | PRICE
        |--------------------------------------------------------------------------
        */

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

        /*
        |--------------------------------------------------------------------------
        | CUSTOMER DATA
        |--------------------------------------------------------------------------
        */

        $customerName = $user->name ?: $company->company_name;

        $customerEmail = $user->email;

        $customerPhone = $user->phone;

        /*
        |--------------------------------------------------------------------------
        | ORDER ID
        |--------------------------------------------------------------------------
        */

        $orderId =
            'SUB-'
            . $company->id
            . '-'
            . now()->format('YmdHis')
            . '-'
            . strtoupper(Str::random(6));

        /*
        |--------------------------------------------------------------------------
        | DATABASE TRANSACTION
        |--------------------------------------------------------------------------
        */

        DB::beginTransaction();

        try {

            $today = today();

            /*
            |--------------------------------------------------------------------------
            | FIND CURRENT ACTIVE SUBSCRIPTION
            |--------------------------------------------------------------------------
            |
            | Subscription lama TIDAK kita ubah dulu.
            |
            | Ini penting:
            |
            | User baru benar-benar membeli subscription setelah Midtrans
            | mengkonfirmasi pembayaran.
            |
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
            |--------------------------------------------------------------------------
            | DETERMINE START DATE
            |--------------------------------------------------------------------------
            */

            if ($previousSubscription) {

                $startDate = Carbon::parse(
                    $previousSubscription->end_date
                )->addDay();

            } else {

                $startDate = $today;
            }

            /*
            |--------------------------------------------------------------------------
            | CALCULATE END DATE
            |--------------------------------------------------------------------------
            */

            if ($validated['billing_cycle'] === 'yearly') {

                $endDate = $startDate
                    ->copy()
                    ->addYear()
                    ->subDay();

            } else {

                $endDate = $startDate
                    ->copy()
                    ->addMonth()
                    ->subDay();
            }

            /*
            |--------------------------------------------------------------------------
            | CREATE PENDING SUBSCRIPTION
            |--------------------------------------------------------------------------
            */

            $subscription = Subscription::create([
                'company_id' => $company->id,

                'plan_id' => $plan->id,

                'billing_cycle' => $validated['billing_cycle'],

                'price' => $price,

                'start_date' => $startDate,

                'end_date' => $endDate,

                /*
                |--------------------------------------------------------------
                | BELUM DIBAYAR
                |--------------------------------------------------------------
                */

                'status' => 'pending',

                'payment_status' => 'pending',
            ]);

            /*
            |--------------------------------------------------------------------------
            | CREATE PAYMENT TRANSACTION
            |--------------------------------------------------------------------------
            */

            $paymentTransaction = PaymentTransaction::create([
                'company_id' => $company->id,

                'subscription_id' => $subscription->id,

                'plan_id' => $plan->id,

                'order_id' => $orderId,

                'transaction_status' => 'pending',

                'gross_amount' => $price,
            ]);

            /*
            |--------------------------------------------------------------------------
            | MIDTRANS PARAMETER
            |--------------------------------------------------------------------------
            */

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

                        'name' =>
                            'Subscription '
                            . $plan->plan_name
                            . ' - '
                            . ucfirst(
                                $validated['billing_cycle']
                            ),
                    ],
                ],

                'customer_details' => [
                    'first_name' => $customerName,

                    'email' => $customerEmail,
                ],

                'callbacks' => [
                    'finish' =>
                        route(
                            'subscription.payment.finish'
                        ),
                ],
            ];

            /*
            |--------------------------------------------------------------------------
            | PHONE
            |--------------------------------------------------------------------------
            |
            | Hanya dikirim kalau user memang memiliki nomor telepon.
            |
            */

            if ($customerPhone) {
                $midtransParams['customer_details']['phone']
                    = $customerPhone;
            }

            /*
            |--------------------------------------------------------------------------
            | CREATE MIDTRANS TRANSACTION
            |--------------------------------------------------------------------------
            */

            $midtransResult =
                $midtrans->createTransaction(
                    $midtransParams
                );

            $redirectUrl =
                $midtransResult['redirect_url'] ?? null;

            if (!$redirectUrl) {

                throw new \RuntimeException(
                    'Midtrans tidak mengembalikan payment URL.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | SAVE MIDTRANS DATA
            |--------------------------------------------------------------------------
            */

            $paymentTransaction->update([
                'payment_url' => $redirectUrl,

                'midtrans_response' => $midtransResult,
            ]);

            /*
            |--------------------------------------------------------------------------
            | COMMIT
            |--------------------------------------------------------------------------
            */

            DB::commit();

            /*
            |--------------------------------------------------------------------------
            | REDIRECT TO MIDTRANS
            |--------------------------------------------------------------------------
            */

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
    | MIDTRANS FINISH / RETURN PAGE
    |--------------------------------------------------------------------------
    |
    | IMPORTANT:
    |
    | Method ini hanya menangani user yang kembali dari Midtrans.
    |
    | Method ini TIDAK mengaktifkan subscription.
    |
    | Aktivasi subscription nanti dilakukan melalui Webhook Midtrans.
    |
    |--------------------------------------------------------------------------
    */

    public function paymentFinish(
        Request $request
    ): RedirectResponse {

        $orderId = $request->input('order_id');

        $transactionStatus =
            $request->input('transaction_status');

        /*
        |--------------------------------------------------------------------------
        | FIND PAYMENT
        |--------------------------------------------------------------------------
        */

        $paymentTransaction = null;

        if ($orderId) {

            $paymentTransaction =
                PaymentTransaction::query()
                    ->where('order_id', $orderId)
                    ->first();
        }

        /*
        |--------------------------------------------------------------------------
        | PAYMENT NOT FOUND
        |--------------------------------------------------------------------------
        */

        if (!$paymentTransaction) {

            return redirect()
                ->route('subscription.index')
                ->withErrors([
                    'subscription' =>
                        'Transaksi pembayaran tidak ditemukan. '
                        . 'Silakan cek status pembayaran Anda.',
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | SUCCESS MESSAGE
        |--------------------------------------------------------------------------
        |
        | Hanya memberi informasi.
        |
        | Subscription belum dianggap paid hanya karena user kembali
        | dari halaman Midtrans.
        |
        */

        if (
            in_array(
                $transactionStatus,
                [
                    'settlement',
                    'capture',
                ],
                true
            )
        ) {

            return redirect()
                ->route('subscription.index')
                ->with(
                    'success',
                    'Pembayaran telah diterima oleh Midtrans. '
                    . 'Subscription sedang diproses.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | FAILED
        |--------------------------------------------------------------------------
        */

        if (
            in_array(
                $transactionStatus,
                [
                    'deny',
                    'cancel',
                    'expire',
                    'failure',
                ],
                true
            )
        ) {

            return redirect()
                ->route('subscription.index')
                ->withErrors([
                    'subscription' =>
                        'Pembayaran subscription tidak berhasil.',
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | PENDING / OTHER
        |--------------------------------------------------------------------------
        */

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
    |
    | Menampilkan seluruh riwayat subscription milik company
    |
    */

    public function history(Request $request): View
    {
        $user = Auth::user();

        /*
        |--------------------------------------------------------------------------
        | ONLY ADMINISTRATOR
        |--------------------------------------------------------------------------
        */

        $isAdministrator =
            $user->role &&
            $user->role->role_code === 'administrator';

        if (!$isAdministrator) {
            abort(
                403,
                'Only Administrator can view subscription history.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | COMPANY
        |--------------------------------------------------------------------------
        */

        $company = $user->company;

        if (!$company) {
            abort(
                404,
                'Company tidak ditemukan.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | SUBSCRIPTION HISTORY
        |--------------------------------------------------------------------------
        |
        | Ambil seluruh subscription milik company.
        |
        | Tidak mengambil subscription milik company lain.
        |
        */

        $subscriptions = $company
            ->subscriptions()
            ->with('plan')
            ->latest('start_date')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | SUMMARY
        |--------------------------------------------------------------------------
        */

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