<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Dashboard\CalendarController as WebCalendarController;
use App\Models\CalendarEvent;
use App\Models\User;
use App\Services\Calendar\CalendarFeed;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Kalender: meeting, pengumuman perusahaan, jadwal maintenance,
 * dan permintaan maintenance/repair.
 *
 * Pembuatan/ubah/batal meeting memakai controller web agar notifikasi
 * & email undangan tetap berjalan sama persis.
 */
class CalendarController extends ApiController
{
    /**
     * Query: from=YYYY-MM-DD, to=YYYY-MM-DD (maks. 120 hari; default bulan berjalan),
     *        type=meeting|announcement|maintenance|maintenance_request|repair_request (opsional).
     */
    public function index(Request $request, CalendarFeed $feed): JsonResponse
    {
        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'type' => ['nullable', 'string'],
        ]);

        $from = $request->filled('from')
            ? Carbon::parse($request->query('from'))->startOfDay()
            : today()->startOfMonth();

        $to = $request->filled('to')
            ? Carbon::parse($request->query('to'))->endOfDay()
            : $from->copy()->endOfMonth();

        if ($from->diffInDays($to) > 120) {
            return $this->fail('Rentang tanggal maksimal 120 hari.', 'RANGE_TOO_LARGE');
        }

        $user = $request->user();

        $events = $feed->events($user, $from, $to)
            ->when($request->filled('type'), fn ($c) => $c->where('type', $request->query('type')))
            ->map(fn (array $event) => $this->format($event, $user))
            ->values();

        return $this->ok([
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'events' => $events,
        ]);
    }

    /**
     * Detail meeting / pengumuman (hanya agenda buatan pengguna).
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $event = $this->visibleEvents($user)
            ->with(['attendees:id,name,email', 'creator:id,name'])
            ->findOrFail($id);

        return $this->ok([
            'event' => [
                'id' => $event->id,
                'title' => $event->title,
                'type' => $event->event_type,
                'description' => $event->description,
                'location' => $event->location,
                'status' => $event->status,
                'start_at' => $this->dateTime($event->start_at),
                'end_at' => $this->dateTime($event->end_at),
                'created_by' => $event->creator ? [
                    'id' => $event->creator->id,
                    'name' => $event->creator->name,
                ] : null,
                'attendees' => $event->attendees->map(fn ($a) => [
                    'id' => $a->id,
                    'name' => $a->name,
                    'email' => $a->email,
                    'response_status' => $a->pivot->response_status ?? null,
                ])->values(),
                'can_edit' => (int) $event->created_by === (int) $user->id && $event->status === 'scheduled',
            ],
        ]);
    }

    /**
     * Body: title, event_type (meeting|announcement), start_at, end_at,
     *       description?, location?, attendees[] (user id; untuk meeting).
     */
    public function store(Request $request, WebCalendarController $web): JsonResponse
    {
        return $this->delegate(fn () => $web->store($request), 'EVENT_INVALID');
    }

    public function update(Request $request, WebCalendarController $web, int $id): JsonResponse
    {
        return $this->delegate(fn () => $web->update($request, $id), 'EVENT_INVALID');
    }

    /**
     * Membatalkan agenda (riwayat tetap tersimpan, peserta diberi tahu).
     */
    public function destroy(WebCalendarController $web, int $id): JsonResponse
    {
        return $this->delegate(fn () => $web->destroy($id), 'EVENT_INVALID');
    }

    /**
     * Agenda buatan pengguna yang boleh dilihat user (sama dengan aturan web).
     */
    protected function visibleEvents(User $user)
    {
        return CalendarEvent::query()
            ->where('company_id', $user->company_id)
            ->where(function ($query) use ($user) {
                $query->where('event_type', 'announcement')
                    ->orWhere('created_by', $user->id)
                    ->orWhereHas('attendees', fn ($q) => $q->where('users.id', $user->id));
            });
    }

    protected function format(array $event, User $user): array
    {
        return [
            'id' => (string) $event['id'],
            'source' => $event['source'] ?? 'event',
            'type' => $event['type'],
            'title' => $event['title'],
            'description' => $event['description'],
            'location' => $event['location'],
            'status' => $event['status'],
            'start_at' => $this->dateTime($event['start']),
            'end_at' => $this->dateTime($event['end']),
            'event_id' => $event['event_id'] ?? null,
            'asset_id' => $event['asset_id'] ?? null,
            'request_id' => $event['request_id'] ?? null,
            'attendees' => $event['attendees'],
            'is_system' => (bool) $event['is_system'],
            'can_edit' => !$event['is_system']
                && (int) $event['created_by'] === (int) $user->id
                && $event['status'] === 'scheduled',
        ];
    }
}
