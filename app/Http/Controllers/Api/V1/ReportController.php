<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\Reports\AssetReport;
use App\Support\BranchAccess;
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
 * Opsional: &branch_id= (kantor pusat). User cabang otomatis hanya cabangnya.
 */
class ReportController extends ApiController
{
    public function __construct(protected AssetReport $reports)
    {
    }

    public function show(Request $request): JsonResponse
    {
        $period = $this->period($request);
        $user = $request->user();
        $report = $this->reports->forBranch($this->branchId($request))->build((int) $user->company_id, $period);

        return $this->ok([
            'presets' => collect(ReportPeriod::PRESETS)->map(fn ($label, $key) => ['key' => $key, 'label' => $label])->values(),
            // Pilihan cabang (kosong bila user terikat cabang / perusahaan 1 kantor).
            'branches' => !BranchAccess::isRestricted($user) && BranchAccess::hasMultiple((int) $user->company_id)
                ? BranchAccess::options((int) $user->company_id, $user, false)->map(fn ($b) => ['id' => $b->id, 'name' => $b->branch_name, 'is_head_office' => $b->is_head_office])->values()
                : [],
            'branch_id' => $report['meta']['branch_id'],
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
        $report = $this->reports->forBranch($this->branchId($request))->build((int) $user->company_id, $period);
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

    /** User cabang: cabangnya sendiri. Kantor pusat: ?branch_id= opsional. */
    protected function branchId(Request $request): ?int
    {
        $user = $request->user();

        return BranchAccess::restrictedBranchId($user)
            ?? BranchAccess::find((int) $user->company_id, $request->query('branch_id'), $user)?->id;
    }

    protected function period(Request $request): ReportPeriod
    {
        return ReportPeriod::make($request->query('range'), $request->query('from'), $request->query('to'));
    }
}
