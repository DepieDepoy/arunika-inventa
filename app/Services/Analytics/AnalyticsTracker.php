<?php

namespace App\Services\Analytics;

use App\Models\AnalyticsHit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Throwable;

/**
 * Mencatat kunjungan halaman publik & event funnel ke analytics_hits.
 * Tidak pernah melempar error ke pengunjung: gagal mencatat = diabaikan.
 */
class AnalyticsTracker
{
    /**
     * Kunjungan halaman (dipanggil dari middleware "track").
     */
    public function pageView(Request $request, string $page): void
    {
        if (!$this->shouldTrack($request)) {
            return;
        }

        $this->store($request, [
            'event' => AnalyticsHit::PAGE_VIEW,
            'page' => $page,
            'path' => mb_substr('/' . ltrim($request->path(), '/'), 0, 191),
        ] + $this->sourceFromRequest($request));
    }

    /**
     * Event funnel (signup / login). Sumber diambil dari kunjungan pertama pengunjung ini
     * (first-touch), karena referer saat submit form pasti halaman kita sendiri.
     */
    public function event(Request $request, string $event, ?int $userId = null, ?int $companyId = null): void
    {
        if (!config('analytics.enabled') || UserAgentParser::isBot($request->userAgent())) {
            return;
        }

        try {
            $visitorId = $this->visitorId($request);

            $first = AnalyticsHit::query()
                ->where('visitor_id', $visitorId)
                ->where('event', AnalyticsHit::PAGE_VIEW)
                ->where('source', '!=', TrafficSource::INTERNAL)
                ->orderBy('id')
                ->first(['source', 'referrer_host', 'utm_source', 'utm_medium', 'utm_campaign']);

            $this->store($request, [
                'event' => $event,
                'page' => null,
                'path' => mb_substr('/' . ltrim($request->path(), '/'), 0, 191),
                'user_id' => $userId,
                'company_id' => $companyId,
                'source' => $first?->source ?? TrafficSource::DIRECT,
                'referrer_host' => $first?->referrer_host,
                'utm_source' => $first?->utm_source,
                'utm_medium' => $first?->utm_medium,
                'utm_campaign' => $first?->utm_campaign,
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    public function shouldTrack(Request $request): bool
    {
        if (!config('analytics.enabled') || !$request->isMethod('GET')) {
            return false;
        }

        // Prefetch browser bukan kunjungan nyata.
        $purpose = strtolower((string) ($request->header('Sec-Purpose') ?: $request->header('Purpose') ?: $request->header('X-Moz')));
        if (str_contains($purpose, 'prefetch') || str_contains($purpose, 'prerender')) {
            return false;
        }

        if (UserAgentParser::isBot($request->userAgent())) {
            return false;
        }

        // Pegawai Vasetra yang sedang login backoffice tidak ikut dihitung.
        try {
            if (auth('backoffice')->check()) {
                return false;
            }
        } catch (Throwable) {
            // guard tidak tersedia -> tetap catat
        }

        return true;
    }

    /**
     * ID pengunjung anonim dari cookie first-party; dibuat bila belum ada.
     */
    public function visitorId(Request $request): string
    {
        if ($id = $request->attributes->get('analytics_visitor_id')) {
            return $id;
        }

        $name = config('analytics.cookie', 'vs_vid');
        $id = (string) $request->cookie($name);

        if (!preg_match('/^[a-zA-Z0-9-]{20,40}$/', $id)) {
            $id = (string) Str::uuid();
        }

        // Perpanjang masa berlaku setiap kunjungan.
        Cookie::queue($name, $id, (int) config('analytics.cookie_days', 365) * 24 * 60, null, null, null, true, false, 'Lax');
        $request->attributes->set('analytics_visitor_id', $id);

        return $id;
    }

    /**
     * @return array<string, ?string>
     */
    private function sourceFromRequest(Request $request): array
    {
        $utm = fn (string $key) => ($v = trim((string) $request->query($key))) !== '' ? mb_substr(strip_tags($v), 0, 100) : null;

        $source = TrafficSource::resolve(
            $request->headers->get('referer'),
            $utm('utm_source'),
            $request->getHost(),
        );

        return $source + [
            'utm_source' => $utm('utm_source'),
            'utm_medium' => $utm('utm_medium'),
            'utm_campaign' => $utm('utm_campaign'),
        ];
    }

    private function store(Request $request, array $data): void
    {
        try {
            AnalyticsHit::query()->create($data + UserAgentParser::parse($request->userAgent()) + [
                'visitor_id' => $this->visitorId($request),
                'ip' => $request->ip(),
                'locale' => app()->getLocale() === 'en' ? 'en' : 'id',
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
