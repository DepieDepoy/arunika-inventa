<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\AnalyticsHit;
use App\Models\Company;
use App\Models\PaymentTransaction;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Backoffice\BackofficeAudit;
use App\Services\Backoffice\CustomerDirectory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Dashboard bisnis & daftar pelanggan (perusahaan) Vasetra. Khusus Super Admin.
 */
class CustomerController extends Controller
{
    public function __construct(protected CustomerDirectory $directory)
    {
    }

    /**
     * Ringkasan bisnis: jumlah PT, status langganan, MRR, pemakaian.
     */
    public function dashboard(): View
    {
        $companies = $this->directory->all();
        $summary = $this->directory->summary($companies);

        $visitors30 = AnalyticsHit::query()
            ->where('event', AnalyticsHit::PAGE_VIEW)
            ->where('created_at', '>=', today()->subDays(29))
            ->distinct()
            ->count('visitor_id');

        $planMix = $companies
            ->whereIn('customer_status', ['trial', 'paid'])
            ->groupBy('plan_label')
            ->map(fn ($g, $label) => ['label' => $label, 'total' => $g->count()])
            ->sortByDesc('total')
            ->values()
            ->all();

        return view('backoffice.customers.dashboard', [
            'summary' => $summary,
            'monthly' => $this->directory->monthlySignups($companies),
            'planMix' => $planMix,
            'expiring' => $companies
                ->filter(fn ($c) => in_array($c->customer_status, ['trial', 'paid'], true) && $c->days_left !== null && $c->days_left <= 7)
                ->sortBy('days_left')
                ->take(8),
            'recent' => $companies->take(6),
            'topAssets' => $companies->sortByDesc('assets_count')->take(6),
            'storageAlerts' => $companies
                ->filter(fn ($c) => $c->storage_limit > 0 && $c->storage_used / $c->storage_limit >= 0.8)
                ->sortByDesc(fn ($c) => $c->storage_used / $c->storage_limit)
                ->take(6),
            'visitors30' => $visitors30,
            'signups30' => $companies->filter(fn ($c) => $c->created_at?->gte(today()->subDays(29)))->count(),
        ]);
    }

    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q'));
        $status = (string) $request->query('status');
        $plan = (string) $request->query('plan');
        $sort = (string) $request->query('sort', 'newest');

        $all = $this->directory->all();
        $counts = $all->countBy('customer_status');

        $rows = $all
            ->when($q !== '', function ($c) use ($q) {
                $needle = mb_strtolower($q);
                $ids = User::query()
                    ->where(fn ($w) => $w->where('email', 'like', "%{$q}%")->orWhere('phone', 'like', "%{$q}%")->orWhere('name', 'like', "%{$q}%"))
                    ->pluck('company_id')
                    ->flip();

                return $c->filter(fn ($x) => str_contains(mb_strtolower($x->company_name . ' ' . $x->company_code), $needle) || $ids->has($x->id));
            })
            ->when(array_key_exists($status, CustomerDirectory::STATUS), fn ($c) => $c->where('customer_status', $status))
            ->when($plan !== '', fn ($c) => $c->where('plan_label', $plan));

        $rows = match ($sort) {
            'assets' => $rows->sortByDesc('assets_count'),
            'users' => $rows->sortByDesc('users_count'),
            'storage' => $rows->sortByDesc('storage_used'),
            'ending' => $rows->sortBy(fn ($c) => $c->days_left ?? PHP_INT_MAX),
            'activity' => $rows->sortByDesc(fn ($c) => (string) $c->last_activity_at),
            default => $rows,
        };

        $page = max(1, (int) $request->query('page', 1));
        $perPage = 20;
        $paginator = new LengthAwarePaginator(
            $rows->slice(($page - 1) * $perPage, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // Admin utama tiap perusahaan (user pertama).
        $owners = User::query()
            ->whereIn('company_id', $paginator->getCollection()->pluck('id'))
            ->orderBy('id')
            ->get(['id', 'company_id', 'name', 'email', 'phone'])
            ->unique('company_id')
            ->keyBy('company_id');

        return view('backoffice.customers.index', [
            'companies' => $paginator,
            'owners' => $owners,
            'counts' => $counts,
            'total' => $all->count(),
            'plans' => Plan::query()->orderBy('id')->pluck('plan_name'),
            'statuses' => CustomerDirectory::STATUS,
        ]);
    }

    public function show(Company $company): View
    {
        $company = $this->directory->all()->firstWhere('id', $company->id) ?? $this->directory->decorate($company);

        $users = User::query()
            ->where('company_id', $company->id)
            ->with('role:id,role_name,role_code')
            ->orderBy('id')
            ->get(['id', 'company_id', 'role_id', 'name', 'email', 'phone', 'status', 'email_verified_at', 'last_login_at', 'last_login_ip', 'created_at']);

        $subscriptions = Subscription::query()
            ->where('company_id', $company->id)
            ->with('plan:id,plan_name,plan_code')
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        $payments = PaymentTransaction::query()
            ->where('company_id', $company->id)
            ->orderByDesc('id')
            ->limit(20)
            ->get(['id', 'order_id', 'purchase_type', 'plan_name', 'billing_cycle', 'gross_amount', 'transaction_status', 'payment_type', 'created_at', 'settlement_time']);

        $activity = [
            'maintenance_requests' => DB::table('maintenance_requests')->where('company_id', $company->id)->count(),
            'maintenance_open' => DB::table('maintenance_requests')->where('company_id', $company->id)->whereNotIn('status', ['completed', 'cancelled', 'rejected'])->count(),
            'maintenances' => DB::table('maintenances')->where('company_id', $company->id)->count(),
            'categories' => DB::table('categories')->where('company_id', $company->id)->count(),
            'assets_value' => (float) DB::table('assets')->where('company_id', $company->id)->sum('purchase_price'),
            'audit_30d' => DB::table('audit_logs')->where('company_id', $company->id)->where('created_at', '>=', now()->subDays(30))->count(),
        ];

        $signup = AnalyticsHit::query()->where('event', AnalyticsHit::SIGNUP)->where('company_id', $company->id)->first();

        return view('backoffice.customers.show', compact('company', 'users', 'subscriptions', 'payments', 'activity', 'signup'));
    }

    /**
     * Perpanjang masa trial (mis. calon pelanggan butuh waktu evaluasi lebih lama).
     */
    public function extendTrial(Request $request, Company $company): RedirectResponse
    {
        $data = $request->validate([
            'days' => ['required', 'integer', 'min:1', 'max:30'],
            'note' => ['nullable', 'string', 'max:200'],
        ]);

        $trial = Subscription::query()
            ->where('company_id', $company->id)
            ->where('billing_cycle', 'trial')
            ->orderByDesc('id')
            ->first();

        if (!$trial) {
            return back()->withErrors(['days' => 'Perusahaan ini tidak punya langganan trial.']);
        }

        // Jangan perpanjang trial bila sudah ada paket berbayar yang berjalan.
        $current = $company->activeSubscription()->with('plan')->first();
        if ($current && $current->id !== $trial->id) {
            return back()->withErrors(['days' => 'Perusahaan sudah memakai paket berbayar; trial tidak perlu diperpanjang.']);
        }

        $old = ['end_date' => $trial->end_date?->toDateString(), 'status' => $trial->status];
        $base = $trial->end_date && $trial->end_date->gte(today()) ? $trial->end_date : today();
        $newEnd = $base->copy()->addDays((int) $data['days']);

        DB::transaction(function () use ($trial, $company, $newEnd) {
            $trial->forceFill(['end_date' => $newEnd, 'status' => 'active'])->save();
            $company->forceFill(['expired_at' => $newEnd, 'subscription_status' => 'active'])->save();
        });

        BackofficeAudit::log(
            'customer.trial_extended',
            "Perpanjang trial {$company->company_name} +{$data['days']} hari s/d {$newEnd->format('d M Y')}" . ($data['note'] ? " ({$data['note']})" : ''),
            $old,
            ['end_date' => $newEnd->toDateString(), 'status' => 'active']
        );

        return back()->with('success', "Trial diperpanjang sampai {$newEnd->locale('id')->translatedFormat('d M Y')}.");
    }

    /**
     * Tangguhkan / aktifkan kembali perusahaan.
     */
    public function toggleStatus(Request $request, Company $company): RedirectResponse
    {
        $suspend = (int) $company->status === 1;

        $data = $request->validate([
            'reason' => [$suspend ? 'required' : 'nullable', 'string', 'max:200'],
        ], ['reason.required' => 'Alasan penangguhan wajib diisi.']);

        $company->forceFill(['status' => $suspend ? 0 : 1])->save();

        if ($suspend) {
            // Keluarkan semua sesi aplikasi mobile perusahaan ini.
            DB::table('personal_access_tokens')
                ->where('tokenable_type', User::class)
                ->whereIn('tokenable_id', User::query()->where('company_id', $company->id)->pluck('id'))
                ->delete();
        }

        BackofficeAudit::log(
            $suspend ? 'customer.suspended' : 'customer.activated',
            ($suspend ? 'Menangguhkan ' : 'Mengaktifkan kembali ') . $company->company_name . (!empty($data['reason']) ? ': ' . $data['reason'] : ''),
            ['status' => $suspend ? 1 : 0],
            ['status' => $suspend ? 0 : 1]
        );

        return back()->with('success', $suspend
            ? "{$company->company_name} ditangguhkan. Semua user tidak bisa login sampai diaktifkan kembali."
            : "{$company->company_name} aktif kembali.");
    }

    public function refreshStorage(Company $company): RedirectResponse
    {
        $this->directory->storageUsed($company->id, true);

        return back()->with('success', 'Pemakaian storage dihitung ulang.');
    }
}
