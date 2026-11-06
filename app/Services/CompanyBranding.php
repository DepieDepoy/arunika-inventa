<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Logo perusahaan pelanggan (dipakai di laporan PDF / PowerPoint & aplikasi mobile).
 * Bila perusahaan belum mengunggah logo, dipakai logo Vasetra.
 *
 * Disimpan di disk "public": storage/app/public/documents/{kode}/company/logo-xxx.png
 * (ikut terhitung kuota storage perusahaan, ukurannya kecil).
 */
class CompanyBranding
{
    public const MAX_SIDE = 800;

    public static function vasetraLogoPath(): string
    {
        return public_path('assets/images/brand/vasetra-logo.png');
    }

    /** Path lokal logo perusahaan, null bila belum ada / file hilang. */
    public static function logoPath(?Company $company): ?string
    {
        $relative = ltrim((string) $company?->logo, '/');
        if ($relative === '' || str_contains($relative, '..')) {
            return null;
        }

        $path = storage_path('app/public/' . $relative);

        return is_file($path) ? $path : null;
    }

    public static function logoUrl(?Company $company): ?string
    {
        return self::logoPath($company) ? asset('storage/' . ltrim($company->logo, '/')) : null;
    }

    /** Logo untuk laporan: logo perusahaan, atau logo Vasetra sebagai cadangan. */
    public static function reportLogoPath(?Company $company): string
    {
        return self::logoPath($company) ?? self::vasetraLogoPath();
    }

    public static function reportLogoUrl(?Company $company): string
    {
        return self::logoUrl($company) ?? asset('assets/images/brand/vasetra-logo.png');
    }

    /**
     * Simpan logo baru. Gambar selalu diproses ulang menjadi PNG (maks 800px)
     * sehingga ringan di laporan dan isi file selain gambar ikut terbuang.
     */
    public static function store(Company $company, UploadedFile $file): string
    {
        $image = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));
        if (!$image) {
            throw new RuntimeException('File bukan gambar yang valid.');
        }

        $w = imagesx($image);
        $h = imagesy($image);
        $scale = min(1, self::MAX_SIDE / max($w, $h));
        $nw = max(1, (int) round($w * $scale));
        $nh = max(1, (int) round($h * $scale));

        $canvas = imagecreatetruecolor($nw, $nh);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 0, 0, 0, 127));
        imagecopyresampled($canvas, $image, 0, 0, 0, 0, $nw, $nh, $w, $h);

        $relative = 'documents/' . $company->company_code . '/company/logo-' . Str::lower(Str::random(10)) . '.png';
        $absolute = storage_path('app/public/' . $relative);
        File::ensureDirectoryExists(dirname($absolute), 0755, true);

        if (!imagepng($canvas, $absolute, 7)) {
            throw new RuntimeException('Logo gagal disimpan.');
        }

        imagedestroy($image);
        imagedestroy($canvas);

        $old = self::logoPath($company);
        $company->forceFill(['logo' => $relative])->save();

        if ($old && $old !== $absolute) {
            @unlink($old);
        }

        return $relative;
    }

    public static function remove(Company $company): void
    {
        if ($old = self::logoPath($company)) {
            @unlink($old);
        }

        $company->forceFill(['logo' => null])->save();
    }
}
