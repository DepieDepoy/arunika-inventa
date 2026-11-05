<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Basis controller API mobile v1.
 *
 * Format respons seragam:
 *   sukses : { success: true,  message?, data, pagination? }
 *   gagal  : { success: false, message, code, errors? }
 */
abstract class ApiController extends Controller
{
    protected function ok(mixed $data = null, ?string $message = null, int $status = 200): JsonResponse
    {
        $payload = ['success' => true];

        if ($message !== null) {
            $payload['message'] = $message;
        }

        if ($data !== null) {
            $payload['data'] = $data;
        }

        return response()->json($payload, $status);
    }

    protected function fail(string $message, string $code, int $status = 422, array $errors = []): JsonResponse
    {
        $payload = [
            'success' => false,
            'message' => $message,
            'code' => $code,
        ];

        if ($errors) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }

    protected function paginated(string $key, LengthAwarePaginator $paginator, callable $map): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                $key => collect($paginator->items())->map($map)->values(),
            ],
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }

    protected function perPage(Request $request, int $default = 20): int
    {
        return min(max($request->integer('per_page', $default), 1), 100);
    }

    protected function date(mixed $value, string $format = 'Y-m-d'): ?string
    {
        if (!$value) {
            return null;
        }

        return \Carbon\Carbon::parse($value)->format($format);
    }

    protected function dateTime(mixed $value): ?string
    {
        return $value ? \Carbon\Carbon::parse($value)->toIso8601String() : null;
    }

    /**
     * Apakah respons hasil delegate() sukses (beberapa controller web
     * memakai kunci `status` alih-alih `success`).
     */
    protected function succeeded(JsonResponse $response): bool
    {
        return $response->getStatusCode() < 400
            && (($response->getData(true)['success'] ?? true) !== false);
    }

    /**
     * Menjalankan aksi controller dashboard (yang sudah mengembalikan JSON)
     * agar aturan bisnis web & mobile tetap satu sumber.
     *
     * Respons dinormalisasi ke format API: detail error teknis tidak
     * dibocorkan dan setiap kegagalan diberi `code`.
     */
    protected function delegate(callable $action, string $defaultErrorCode = 'REQUEST_FAILED'): JsonResponse
    {
        $response = $action();

        if (!$response instanceof JsonResponse) {
            return $response instanceof Response && $response->isRedirection()
                ? $this->fail('Permintaan tidak dapat diproses.', $defaultErrorCode)
                : $this->fail('Respons tidak dikenali.', 'SERVER_ERROR', 500);
        }

        $status = $response->getStatusCode();
        $payload = (array) $response->getData(true);

        if ($status < 400 && ($payload['success'] ?? true) !== false) {
            return $response;
        }

        // Jangan kirim pesan exception mentah ke aplikasi.
        unset($payload['error'], $payload['trace'], $payload['exception']);

        $payload['success'] = false;
        $payload['code'] ??= match (true) {
            $status === 404 => 'NOT_FOUND',
            $status === 403 => 'FORBIDDEN',
            $status >= 500 => 'SERVER_ERROR',
            isset($payload['errors']['plan_limit']) => 'PLAN_LIMIT_REACHED',
            isset($payload['errors']['storage_limit']) => 'STORAGE_QUOTA_EXCEEDED',
            default => $defaultErrorCode,
        };

        if (empty($payload['message'])) {
            $firstError = collect($payload['errors'] ?? [])->flatten()->first();
            $payload['message'] = $firstError ?: 'Permintaan tidak dapat diproses.';
        }

        return response()->json($payload, $status >= 400 ? $status : 422);
    }
}
