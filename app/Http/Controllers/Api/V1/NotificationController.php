<?php

namespace App\Http\Controllers\Api\V1;

use App\Support\NotificationText;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

/**
 * Notifikasi in-app (sama dengan lonceng di navbar web).
 */
class NotificationController extends ApiController
{
    /**
     * Query: filter=all|unread (default all), per_page.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = $request->query('filter') === 'unread'
            ? $user->unreadNotifications()
            : $user->notifications();

        $page = $query->paginate($this->perPage($request));
        $locale = $user->locale;

        $response = $this->paginated('notifications', $page, fn (DatabaseNotification $n) => $this->format($n, $locale));

        $payload = $response->getData(true);
        $payload['data']['unread_count'] = $user->unreadNotifications()->count();

        return response()->json($payload);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return $this->ok([
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()->notifications()->where('id', $id)->firstOrFail();
        $notification->markAsRead();

        return $this->ok([
            'notification' => $this->format($notification, $request->user()->locale),
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return $this->ok(['unread_count' => 0], 'Semua notifikasi ditandai sudah dibaca.');
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $request->user()->notifications()->where('id', $id)->firstOrFail()->delete();

        return $this->ok(null, 'Notifikasi dihapus.');
    }

    /**
     * `target` membantu Flutter membuka layar yang tepat
     * (mis. detail request / kalender) tanpa mem-parsing URL web.
     */
    protected function format(DatabaseNotification $notification, ?string $locale = null): array
    {
        $data = (array) $notification->data;
        $extra = collect($data)->except(['type', 'title', 'message', 'url', 'i18n'])->all();

        // Judul & pesan mengikuti bahasa pilihan user di aplikasi (users.locale).
        return [
            'id' => $notification->id,
            'type' => $data['type'] ?? class_basename($notification->type),
            'title' => NotificationText::fromData(
                $data,
                'title',
                $locale,
                NotificationText::render(NotificationText::make('Notifikasi'), $locale)
            ),
            'message' => NotificationText::fromData($data, 'message', $locale),
            'target' => $this->target($data),
            'data' => $extra,
            'is_read' => $notification->read_at !== null,
            'read_at' => $this->dateTime($notification->read_at),
            'created_at' => $this->dateTime($notification->created_at),
        ];
    }

    protected function target(array $data): ?array
    {
        if (!empty($data['event_id'])) {
            return ['screen' => 'calendar_event', 'id' => (int) $data['event_id']];
        }

        foreach (['maintenance_request_id', 'request_id'] as $key) {
            if (!empty($data[$key])) {
                return ['screen' => 'maintenance_request', 'id' => (int) $data[$key]];
            }
        }

        // Audit fisik & mutasi antar-cabang (dicek sebelum asset_id).
        if (!empty($data['asset_audit_id'])) {
            return ['screen' => 'asset_audit', 'id' => (int) $data['asset_audit_id']];
        }

        if (!empty($data['branch_transfer_id'])) {
            return ['screen' => 'branch_transfer', 'id' => (int) $data['branch_transfer_id']];
        }

        if (!empty($data['asset_id'])) {
            return ['screen' => 'asset', 'id' => (int) $data['asset_id']];
        }

        $url = (string) ($data['url'] ?? '');

        return match (true) {
            str_contains($url, '/subscription') => ['screen' => 'subscription', 'id' => null],
            str_contains($url, '/calendar') => ['screen' => 'calendar', 'id' => null],
            str_contains($url, '/maintenance') => ['screen' => 'maintenance', 'id' => null],
            default => null,
        };
    }
}
