<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\PaymentTransaction;
use App\Models\Subscription;
use App\Models\SubscriptionAddon;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Status langganan untuk Administrator (read-only).
 *
 * Pembelian/perpanjangan/upgrade/add-on sengaja TIDAK disediakan di mobile:
 * dilakukan lewat dashboard web (lihat `manage_url`). Aturan Google Play &
 * App Store membatasi penjualan langganan digital di dalam aplikasi di luar
 * sistem pembayaran mereka.
 */
class SubscriptionController extends ApiController
{
    public function show(Request $request, SubscriptionService $subscriptions): JsonResponse
    {
        $companyId = (int) $request->user()->company_id;

        $current = $subscriptions->current($companyId);
        $paid = $subscriptions->currentPaid($companyId);
        $upgrade = $subscriptions->activeUpgrade($companyId);
        $queued = $subscriptions->queued($companyId);
        $quota = $subscriptions->quota($companyId);
        $usage = $subscriptions->usage($companyId);

        return $this->ok([
            'current' => $this->subscription($current, $subscriptions),
            'base' => $upgrade ? $this->subscription($upgrade->parent ?? $paid, $subscriptions) : null,
            'queued' => $this->subscription($queued, $subscriptions),
            'addons' => $subscriptions->activeAddons($companyId)->map(fn (SubscriptionAddon $a) => [
                'id' => $a->id,
                'type' => $a->type,
                'description' => $subscriptions->describeAddon($a->type, (int) $a->total_value),
                'total_value' => (int) $a->total_value,
                'start_date' => $this->date($a->start_date),
                'end_date' => $this->date($a->end_date),
            ])->values(),
            'quota' => collect(['users', 'assets', 'storage'])->mapWithKeys(fn ($key) => [$key => [
                'used' => (int) ($usage[$key] ?? 0),
                'limit' => $quota[$key] ?? null, // null = tanpa batas
                'exceeded' => isset($quota[$key]) && ($usage[$key] ?? 0) > $quota[$key],
                'percent' => isset($quota[$key]) && $quota[$key] > 0
                    ? round(($usage[$key] ?? 0) / $quota[$key] * 100, 1)
                    : null,
            ]])->all(),
            'manage_url' => route('subscription.index'),
        ]);
    }

    /**
     * Riwayat pembayaran (tanpa data sensitif gateway).
     */
    public function payments(Request $request): JsonResponse
    {
        $page = PaymentTransaction::query()
            ->where('company_id', $request->user()->company_id)
            ->latest('id')
            ->paginate($this->perPage($request));

        return $this->paginated('payments', $page, fn (PaymentTransaction $p) => [
            'id' => $p->id,
            'order_id' => $p->order_id,
            'purchase_type' => $p->purchase_type ?? 'subscription',
            'plan_name' => $p->plan_name,
            'billing_cycle' => $p->billing_cycle,
            'duration_months' => $p->duration_months,
            'gross_amount' => (float) $p->gross_amount,
            'status' => $p->transaction_status,
            'payment_type' => $p->payment_type,
            'created_at' => $this->dateTime($p->created_at),
            'paid_at' => $this->dateTime($p->settlement_time),
        ]);
    }

    protected function subscription(?Subscription $s, SubscriptionService $service): ?array
    {
        if (!$s) {
            return null;
        }

        $s->loadMissing('plan');

        return [
            'id' => $s->id,
            'kind' => $s->kind ?? 'regular',
            'plan' => $s->plan?->plan_name,
            'plan_code' => $s->plan?->plan_code,
            'billing_cycle' => $s->billing_cycle,
            'is_trial' => $service->isTrial($s),
            'start_date' => $this->date($s->start_date),
            'end_date' => $this->date($s->end_date),
            'status' => $s->status,
            'limits' => [
                'users' => $s->plan?->max_users,
                'assets' => $s->plan?->max_assets,
                'storage' => $s->total_storage_bytes ?: $s->plan?->max_storage_bytes,
            ],
        ];
    }
}
