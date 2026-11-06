<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\Reports\AssetReport;
use App\Services\Reports\ReportPeriod;
use App\Services\Reports\ReportPresentation;
use App\Services\Reports\ReportWorkbook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Laporan aset untuk aplikasi mobile.
 *
 * GET /reports?range=this_week|last_week|this_month|last_month|this_quarter|this_year|last_year|custom&from=&to=
 * GET /reports/download?format=xlsx|pptx&range=...
 */
class ReportController extends ApiController
{
    public function __construct(protected AssetReport $reports)
    {
    }

    public function show(Request $request): JsonResponse
    {
        $period = $this->period($request);
        $report = $this->reports->build((int) $request->user()->company_id, $period);

        return $this->ok([
            'presets' => collect(ReportPeriod::PRESETS)->map(fn ($label, $key) => ['key' => $key, 'label' => $label])->values(),
            'report' => $report,
        ]);
    }

    public function download(Request $request): Response
    {
        $format = strtolower((string) $request->query('format', 'xlsx'));
        if (!in_array($format, ['xlsx', 'pptx'], true)) {
            return $this->fail('Format harus xlsx atau pptx.', 'VALIDATION_ERROR', 422);
        }

        $user = $request->user();
        $period = $this->period($request);
        $report = $this->reports->build((int) $user->company_id, $period);
        $name = 'Vasetra-' . Str::slug($report['meta']['title']) . '-' . $period->slug() . '.' . $format;

        if ($format === 'xlsx') {
            return app(ReportWorkbook::class)->build($report, (int) $user->company_id, $period, $user->name)->download($name);
        }

        $path = \App\Http\Controllers\Dashboard\ReportController::tempPath();
        app(ReportPresentation::class)->build($report, $path, \App\Services\CompanyBranding::logoPath($user->company));

        return response()->download($path, $name, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'Cache-Control' => 'no-store, private',
        ])->deleteFileAfterSend();
    }

    protected function period(Request $request): ReportPeriod
    {
        return ReportPeriod::make($request->query('range'), $request->query('from'), $request->query('to'));
    }
}
