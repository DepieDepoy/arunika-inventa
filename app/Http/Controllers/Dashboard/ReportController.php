<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\Reports\AssetReport;
use App\Support\BranchAccess;
use App\Services\Reports\ReportPeriod;
use App\Services\Reports\ReportPresentation;
use App\Services\Reports\ReportWorkbook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Laporan aset mingguan / bulanan / tahunan: tampilan web, Excel, PDF (cetak), PowerPoint.
 */
class ReportController extends Controller
{
    public function __construct(protected AssetReport $reports)
    {
    }

    public function index(Request $request): View
    {
        $period = $this->period($request);

        $branchId = $this->branchId($request);
        $companyId = (int) Auth::user()->company_id;

        return view('dashboard.report.index', [
            'period' => $period,
            'presets' => ReportPeriod::PRESETS,
            'r' => $this->reports->forBranch($branchId)->build($companyId, $period),
            'query' => $period->toQuery() + array_filter(['branch_id' => BranchAccess::isRestricted() ? null : $branchId]),
            // Pilihan cabang hanya untuk user kantor pusat di perusahaan multi-cabang.
            'branches' => !BranchAccess::isRestricted() && BranchAccess::hasMultiple($companyId) ? BranchAccess::options($companyId, null, false) : collect(),
            'branchId' => $branchId,
        ]);
    }

    /** Versi cetak (Simpan sebagai PDF dari browser). */
    public function print(Request $request): View
    {
        $period = $this->period($request);

        return view('dashboard.report.print', [
            'period' => $period,
            'r' => $this->reports->forBranch($this->branchId($request))->build((int) Auth::user()->company_id, $period),
        ]);
    }

    public function excel(Request $request, ReportWorkbook $workbook): StreamedResponse
    {
        $period = $this->period($request);
        $companyId = (int) Auth::user()->company_id;
        $report = $this->reports->forBranch($this->branchId($request))->build($companyId, $period);

        return $workbook->build($report, $companyId, $period, Auth::user()->name)
            ->download($this->filename($report, $period, 'xlsx'));
    }

    public function pptx(Request $request, ReportPresentation $presentation): BinaryFileResponse
    {
        $period = $this->period($request);
        $report = $this->reports->forBranch($this->branchId($request))->build((int) Auth::user()->company_id, $period);

        $path = self::tempPath();
        $presentation->build($report, $path, \App\Services\CompanyBranding::logoPath(Auth::user()->company));

        return response()
            ->download($path, $this->filename($report, $period, 'pptx'), [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'Cache-Control' => 'no-store, private',
            ])
            ->deleteFileAfterSend();
    }

    /**
     * Lokasi file PowerPoint sementara (dihapus setelah dikirim). File yatim
     * > 1 jam (mis. unduhan terputus) dibersihkan sekalian.
     */
    public static function tempPath(): string
    {
        $dir = storage_path('app/private/tmp');

        // Buat folder hanya bila belum ada (hindari peringatan "File exists").
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new \RuntimeException('Folder sementara laporan tidak dapat dibuat: ' . $dir);
        }

        foreach (glob($dir . '/report-*.pptx') ?: [] as $old) {
            if (@filemtime($old) < time() - 3600) {
                @unlink($old);
            }
        }

        return $dir . '/report-' . Str::uuid() . '.pptx';
    }

    /**
     * Cabang laporan. User cabang selalu cabangnya sendiri; user kantor pusat
     * boleh memilih cabang (?branch_id=) atau semua cabang. Selalu di dalam PT sendiri.
     */
    protected function branchId(Request $request): ?int
    {
        $user = Auth::user();

        return BranchAccess::restrictedBranchId($user)
            ?? BranchAccess::find((int) $user->company_id, $request->query('branch_id'), $user)?->id;
    }

    protected function period(Request $request): ReportPeriod
    {
        return ReportPeriod::make($request->query('range'), $request->query('from'), $request->query('to'));
    }

    protected function filename(array $report, ReportPeriod $period, string $ext): string
    {
        $title = Str::slug($report['meta']['title']);
        $company = Str::slug(Str::limit((string) $report['meta']['company'], 30, ''));

        return "Vasetra-{$title}-{$company}-{$period->slug()}.{$ext}";
    }
}
