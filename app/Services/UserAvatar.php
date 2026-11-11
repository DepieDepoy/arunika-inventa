<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Foto profil user (web & aplikasi mobile).
 *
 * Disimpan di disk "public": storage/app/public/documents/{kode}/users/{id}-xxx.jpg
 * sehingga ikut terhitung kuota storage perusahaan (sama seperti assets/img & inv).
 * Gambar selalu diproses ulang menjadi JPEG persegi maks 512px: ringan,
 * dan isi file selain gambar ikut terbuang.
 */
class UserAvatar
{
    public const SIZE = 512;

    /** Aturan validasi upload (dipakai web & API). */
    public const RULES = ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'];

    /** Path lokal foto, null bila belum ada / file hilang / foto eksternal. */
    public static function path(?User $user): ?string
    {
        $relative = ltrim((string) $user?->photo, '/');

        if ($relative === '' || str_contains($relative, '..') || preg_match('#^https?://#i', $relative)) {
            return null;
        }

        $path = storage_path('app/public/' . $relative);

        return is_file($path) ? $path : null;
    }

    public static function url(?User $user): ?string
    {
        $photo = trim((string) $user?->photo);

        if (preg_match('#^https?://#i', $photo)) {
            return $photo;
        }

        return self::path($user) ? asset('storage/' . ltrim($photo, '/')) : null;
    }

    /**
     * Simpan foto baru (kuota storage dicek lebih dulu), hapus yang lama.
     *
     * @throws RuntimeException pesan siap ditampilkan ke user
     */
    public static function store(User $user, UploadedFile $file): string
    {
        $company = $user->company;

        if (!$company || !$company->company_code) {
            throw new RuntimeException('Perusahaan user tidak ditemukan.');
        }

        $quota = CompanyStorage::checkUpload((int) $company->id, [$file]);
        if (!$quota['allowed']) {
            throw new RuntimeException($quota['message']);
        }

        $image = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));
        if (!$image) {
            throw new RuntimeException('File bukan gambar yang valid.');
        }

        $image = self::orient($image, $file);

        // Potong tengah menjadi persegi, lalu perkecil.
        $w = imagesx($image);
        $h = imagesy($image);
        $side = min($w, $h);
        $target = min(self::SIZE, $side);

        $canvas = imagecreatetruecolor($target, $target);
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
        imagecopyresampled(
            $canvas, $image, 0, 0,
            (int) (($w - $side) / 2), (int) (($h - $side) / 2),
            $target, $target, $side, $side
        );

        $relative = 'documents/' . $company->company_code . '/users/'
            . $user->id . '-' . Str::lower(Str::random(10)) . '.jpg';
        $absolute = storage_path('app/public/' . $relative);
        File::ensureDirectoryExists(dirname($absolute), 0755, true);

        $saved = imagejpeg($canvas, $absolute, 85);
        imagedestroy($image);
        imagedestroy($canvas);

        if (!$saved) {
            throw new RuntimeException('Foto gagal disimpan.');
        }

        $old = self::path($user);
        $user->forceFill(['photo' => $relative])->save();

        if ($old && $old !== $absolute) {
            @unlink($old);
        }

        return $relative;
    }

    public static function remove(User $user): void
    {
        if ($old = self::path($user)) {
            @unlink($old);
        }

        $user->forceFill(['photo' => null])->save();
    }

    /** Foto dari kamera HP sering berorientasi EXIF; putar agar tegak. */
    private static function orient(\GdImage $image, UploadedFile $file): \GdImage
    {
        if (!function_exists('exif_read_data') || $file->getMimeType() !== 'image/jpeg') {
            return $image;
        }

        $orientation = (int) (@exif_read_data($file->getRealPath())['Orientation'] ?? 1);
        $angle = match ($orientation) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        if ($angle === 0) {
            return $image;
        }

        $rotated = imagerotate($image, $angle, 0);
        if ($rotated) {
            imagedestroy($image);

            return $rotated;
        }

        return $image;
    }
}
