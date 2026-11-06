<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\AnalyticsHit;
use App\Models\Company;
use App\Services\Analytics\AnalyticsSettings;
use App\Services\Analytics\TrafficSource;
use App\Services\Backoffice\BackofficeAudit;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Dashboard analitik pengunjung landing / daftar / login (khusus Super Admin).
 */
class AnalyticsController extends Controller
{
    public const RANGES = [7 => '7 hari', 30 => '30 hari', 90 => '90 hari'];

    public function index(Request $request): View
    {
        $range = array_key_exists((int) $request->query('range'), self::RANGES) ? (int) $request->query('range') : 30;

        $from = today()->subDays($range - 1);
        $to = now();
        $prevFrom = $from->copy()->subDays($range);
        $prevTo = $from->copy()->subSecond();

        $kpi = [
            'visitors' => $this->kpi($this->visitorCount($from, $to), $this->visitorCount($prevFrom, $prevTo)),
            'pageviews' => $this->kpi($this->views($from, $to)->count(), $this->views($prevFrom, $prevTo)->count()),
            'signups' => $this->kpi($this->signups($from, $to)->count(), $this->signups($prevFrom, $prevTo)->count()),
            'active' => $this->views(now()->subMinutes(30), $to)->distinct()->count('visitor_id'),
        ];

        $landingVisitors = $this->visitorCount($from, $to, 'landing');
        $kpi['conversion'] = [
            'value' => $landingVisitors ? round($kpi['signups']['value'] / $landingVisitors * 100, 1) : 0,
            'prev' => null,
        ];

        return view('backoffice.analytics.index', [
            'range' => $range,
            'ranges' => self::RANGES,
            'from' => $from,
            'kpi' => $kpi,
            'daily' => $this->daily($from, $to),
            'funnel' => $this->funnel($from, $to, $landingVisitors),
            'breakdowns' => [
                'device' => $this->breakdown($from, $to, 'device'),
                'os' => $this->breakdown($from, $to, 'os'),
                'browser' => $this->breakdown($from, $to, 'browser'),
                'source' => $this->breakdown($from, $to, 'source', fn (Builder $q) => $q->where('source', '!=', TrafficSource::INTERNAL)),
                'locale' => $this->breakdown($from, $to, 'locale', fn (Builder $q) => $q->where('page', 'landing')),
                'campaign' => $this->breakdown($from, $to, 'utm_campaign', fn (Builder $q) => $q->whereNotNull('utm_campaign')),
            ],
            'pages' => $this->pages($from, $to),
            'visits' => $this->visits($request, $from, $to),
            'signupList' => $this->signupList($from, $to),
            'ga4Id' => AnalyticsSettings::ga4Id(),
            'ipDays' => (int) config('analytics.ip_retention_days', 60),
            'enabled' => (bool) config('analytics.enabled'),
        ]);
    }

    /**
     * Simpan ID Google Analytics 4 (opsional). Kosong = GA tidak dipasang.
     */
    public function settings(Request $request): RedirectResponse
    {
        $request->merge(['ga4_id' => strtoupper(trim((string) $request->input('ga4_id')))]);

        $data = $request->validate([
            'ga4_id' => ['nullable', 'string', 'max:20', 'regex:' . AnalyticsSettings::GA4_PATTERN],
        ], [
            'ga4_id.regex' => 'Format Measurement ID tidak valid. Contoh: G-AB12CD34EF',
        ]);

        $old = AnalyticsSettings::all();
        $new = ['ga4_id' => strtoupper(trim((string) ($data['ga4_id'] ?? ''))) ?: null];

        AnalyticsSettings::save($new, auth('backoffice')->id());

        BackofficeAudit::log('analytics.settings', $new['ga4_id'] ? 'Mengatur Google Analytics: ' . $new['ga4_id'] : 'Menonaktifkan Google Analytics', $old, $new);

        return back()->with('success', $new['ga4_id'] ? 'Google Analytics aktif di landing, halaman daftar & login.' : 'Google Analytics dinonaktifkan.');
    }

    /* ------------------------------------------------------------------ */

    private function views(Carbon $from, Carbon $to): Builder
    {
        return AnalyticsHit::query()
            ->where('event', AnalyticsHit::PAGE_VIEW)
            ->whereBetween('created_at', [$from, $to]);
    }

    private function visitorCount(Carbon $from, Carbon $to, ?string $page = null): int
    {
        return $this->views($from, $to)
            ->when($page, fn ($q) => $q->where('page', $page))
            ->distinct()
            ->count('visitor_id');
    }

    private function signups(Carbon $from, Carbon $to): Builder
    {
        return Company::query()->whereBetween('companies.created_at', [$from, $to]);
    }

    private function kpi(int|float $value, int|float $prev): array
    {
        return [
            'value' => $value,
            'prev' => $prev,
            'delta' => $prev > 0 ? round(($value - $prev) / $prev * 100) : null,
        ];
    }

    private function daily(Carbon $from, Carbon $to): array
    {
        $hits = $this->views($from, $to)
            ->selectRaw('DATE(created_at) as d, COUNT(*) as pv, COUNT(DISTINCT visitor_id) as v')
            ->groupBy('d')
            ->get()
            ->keyBy('d');

        $signups = $this->signups($from, $to)
            ->selectRaw('DATE(created_at) as d, COUNT(*) as n')
            ->groupBy('d')
            ->pluck('n', 'd');

        $rows = [];
        foreach (CarbonPeriod::create($from, today()) as $day) {
            $key = $day->toDateString();
            $rows[] = [
                'date' => $key,
                'label' => $day->locale('id')->translatedFormat('d M'),
                'visitors' => (int) ($hits[$key]->v ?? 0),
                'pageviews' => (int) ($hits[$key]->pv ?? 0),
                'signups' => (int) ($signups[$key] ?? 0),
            ];
        }

        return $rows;
    }

    private function funnel(Carbon $from, Carbon $to, int $landingVisitors): array
    {
        $companyIds = $this->signups($from, $to)->pluck('id');

        $loggedIn = $companyIds->isEmpty() ? 0 : DB::table('users')
            ->whereIn('company_id', $companyIds)
            ->whereNotNull('last_login_at')
            ->distinct()
            ->count('company_id');

        $paid = $companyIds->isEmpty() ? 0 : DB::table('subscriptions')
            ->whereIn('company_id', $companyIds)
            ->where('payment_status', 'paid')
            ->where('total_price', '>', 0)
            ->distinct()
            ->count('company_id');

        $steps = [
            ['label' => 'Mengunjungi landing page', 'icon' => 'bi-globe2', 'value' => $landingVisitors],
            ['label' => 'Membuka halaman daftar', 'icon' => 'bi-ui-checks', 'value' => $this->visitorCount($from, $to, 'register')],
            ['label' => 'Daftar berhasil (trial)', 'icon' => 'bi-person-check', 'value' => $companyIds->count()],
            ['label' => 'Login ke dashboard', 'icon' => 'bi-box-arrow-in-right', 'value' => $loggedIn],
            ['label' => 'Upgrade ke paket berbayar', 'icon' => 'bi-credit-card', 'value' => $paid],
        ];

        $top = max(1, $steps[0]['value']);
        foreach ($steps as $i => &$step) {
            $step['pct_total'] = round($step['value'] / $top * 100, 1);
            $prev = $i > 0 ? $steps[$i - 1]['value'] : null;
            $step['pct_prev'] = $prev ? round($step['value'] / $prev * 100, 1) : null;
        }

        return $steps;
    }

    private function breakdown(Carbon $from, Carbon $to, string $column, ?callable $scope = null): array
    {
        $query = $this->views($from, $to)->whereNotNull($column);
        if ($scope) {
            $scope($query);
        }

        $rows = $query
            ->selectRaw("$column as label, COUNT(DISTINCT visitor_id) as total")
            ->groupBy($column)
            ->orderByDesc('total')
            ->get();

        $sum = max(1, (int) $rows->sum('total'));

        // Lebih dari 6 baris digabung ke "Lainnya".
        $top = $rows->take(6)->map(fn ($r) => ['label' => (string) $r->label, 'total' => (int) $r->total]);
        $rest = (int) $rows->slice(6)->sum('total');
        if ($rest > 0) {
            $top->push(['label' => 'Lainnya', 'total' => $rest]);
        }

        return $top->map(fn ($r) => $r + ['pct' => round($r['total'] / $sum * 100, 1)])->values()->all();
    }

    private function pages(Carbon $from, Carbon $to): array
    {
        $rows = $this->views($from, $to)
            ->selectRaw('page, locale, COUNT(*) as pv, COUNT(DISTINCT visitor_id) as v')
            ->groupBy('page', 'locale')
            ->get();

        return $rows->map(fn ($r) => [
            'label' => (AnalyticsHit::PAGES[$r->page] ?? $r->page) . ($r->page === 'landing' ? ' (' . strtoupper((string) $r->locale) . ')' : ''),
            'pageviews' => (int) $r->pv,
            'visitors' => (int) $r->v,
        ])->sortByDesc('pageviews')->values()->all();
    }

    private function visits(Request $request, Carbon $from, Carbon $to)
    {
        $q = trim((string) $request->query('q'));
        $page = (string) $request->query('page_type');
        $device = (string) $request->query('device');

        $visits = $this->views($from, $to)
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('ip', 'like', '%' . $q . '%')
                ->orWhere('source', 'like', '%' . $q . '%')
                ->orWhere('utm_campaign', 'like', '%' . $q . '%')))
            ->when(array_key_exists($page, AnalyticsHit::PAGES), fn ($w) => $w->where('page', $page))
            ->when(in_array($device, ['desktop', 'mobile', 'tablet'], true), fn ($w) => $w->where('device', $device))
            ->latest('id')
            ->paginate(20, ['*'], 'p')
            ->withQueryString();

        // Pengunjung yang akhirnya mendaftar -> tampilkan nama perusahaannya.
        $signedUp = AnalyticsHit::query()
            ->where('event', AnalyticsHit::SIGNUP)
            ->whereIn('visitor_id', $visits->pluck('visitor_id')->unique())
            ->with('company:id,company_name')
            ->get()
            ->keyBy('visitor_id');

        $visits->getCollection()->transform(function (AnalyticsHit $hit) use ($signedUp) {
            $hit->signed_up_company = $signedUp->get($hit->visitor_id)?->company?->company_name;

            return $hit;
        });

        return $visits;
    }

    private function signupList(Carbon $from, Carbon $to)
    {
        $companies = $this->signups($from, $to)
            ->with(['users' => fn ($q) => $q->orderBy('id')->select('id', 'company_id', 'name', 'email', 'last_login_at')])
            ->latest('companies.created_at')
            ->limit(10)
            ->get(['id', 'company_name', 'subscription_plan', 'created_at']);

        $hits = AnalyticsHit::query()
            ->where('event', AnalyticsHit::SIGNUP)
            ->whereIn('company_id', $companies->pluck('id'))
            ->get()
            ->keyBy('company_id');

        $paid = DB::table('subscriptions')
            ->whereIn('company_id', $companies->pluck('id'))
            ->where('payment_status', 'paid')
            ->where('total_price', '>', 0)
            ->pluck('company_id')
            ->flip();

        return $companies->map(function (Company $c) use ($hits, $paid) {
            $owner = $c->users->first();

            return [
                'company' => $c->company_name,
                'name' => $owner?->name,
                'email' => $owner?->email,
                'created_at' => $c->created_at,
                'logged_in' => (bool) $owner?->last_login_at,
                'paid' => $paid->has($c->id),
                'source' => $hits[$c->id]->source ?? null,
                'device' => $hits[$c->id]->device ?? null,
                'ip' => $hits[$c->id]->ip ?? null,
            ];
        });
    }
}
