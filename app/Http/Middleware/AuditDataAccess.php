<?php

namespace App\Http\Middleware;

use App\Services\Audit\AuditLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mencatat aktivitas pengambilan data keluar sistem
 * (export, cetak QR, cetak billing) ke audit trail.
 *
 * Perubahan data (tambah/ubah/hapus) sudah dicatat otomatis
 * oleh trait Auditable pada model, jadi tidak dicatat di sini.
 */
class AuditDataAccess
{
    /**
     * route name => [event, module, deskripsi]
     */
    protected const ROUTES = [
        'assets.export' => ['exported', 'asset', 'Export data asset'],
        'category.export' => ['exported', 'category', 'Export data kategori'],
        'subcategory.export' => ['exported', 'sub_category', 'Export data sub kategori'],
        'vendor.export' => ['exported', 'vendor', 'Export data vendor'],
        'roles.export' => ['exported', 'role', 'Export data role'],
        'users.export' => ['exported', 'user', 'Export data user'],
        'assets.print-qr' => ['printed', 'asset', 'Cetak QR code asset'],
        'subscription.billing.print' => ['printed', 'payment', 'Cetak billing / invoice'],
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $routeName = $request->route()?->getName();

        if (
            $routeName
            && isset(self::ROUTES[$routeName])
            && $response->isSuccessful()
        ) {
            [$event, $module, $description] = self::ROUTES[$routeName];

            if ($routeName === 'assets.print-qr') {
                $count = count((array) $request->input('ids', []));

                if ($count > 0) {
                    $description .= ' (' . $count . ' asset)';
                }
            }

            AuditLogger::log(
                event: $event,
                module: $module,
                description: $description,
            );
        }

        return $response;
    }
}
