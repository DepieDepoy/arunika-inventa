<?php

namespace App\Http\Middleware;

use App\Services\Analytics\AnalyticsTracker;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Catat kunjungan halaman publik. Pemakaian: ->middleware('track:landing').
 */
class TrackPageView
{
    public function __construct(protected AnalyticsTracker $tracker)
    {
    }

    public function handle(Request $request, Closure $next, string $page = 'other'): Response
    {
        $response = $next($request);

        if ($response->getStatusCode() === 200) {
            $this->tracker->pageView($request, $page);
        }

        return $response;
    }
}
