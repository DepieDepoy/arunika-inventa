<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\Reports\AssetReport;
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

        return view('dashboard.report.index', [
            'period' => $period,
            'presets' => ReportPeriod::PRESETS,
            'r' => $this->reports->build((int) Auth::user()->company_id, $period),
            'query' => $period->toQuery(),
        ]);
    }

    /** Versi cetak (Simpan sebagai PDF dari browser). */
    public function print(Request $request): View
    {
        $period = $this->period($request);

        return view('dashboard.report.print', [
            'period' => $period,
            'r' => $this->reports->build((int) Auth::user()->company_id, $period),
        ]);
    }

    public function excel(Request $request, ReportWorkbook $workbook): StreamedResponse
    {
        $period = $this->period($request);
        $companyId = (int) Auth::user()->company_id;
        $report = $this->reports->build($companyId, $period);

        return $workbook->build($report, $companyId, $period, Auth::user()->name)
            ->download($this->filename($report, $period, 'xlsx'));
    }

    public function pptx(Request $request, ReportPresentation $presentation): BinaryFileResponse
    {
        $period = $this->period($request);
        $report = $this->reports->build((int) Auth::user()->company_id, $period);

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
        @mkdir($dir, 0755, true);

        foreach (glob($dir . '/report-*.pptx') ?: [] as $old) {
            if (@filemtime($old) < time() - 3600) {
                @unlink($old);
            }
        }

        return $dir . '/report-' . Str::uuid() . '.pptx';
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
