<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\CalendarEvent;
use App\Models\CalendarEventAttendee;
use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Notifications\CalendarMeetingCancellation;
use App\Notifications\CalendarMeetingInvitation;
use App\Notifications\GlobalNotification;
use App\Services\Calendar\CalendarFeed;
use App\Support\NotificationText;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class CalendarController extends Controller
{
    /**
     * Tampilkan agenda kalender perusahaan:
     * - Meeting
     * - Company Announcement
     * - Jadwal Maintenance Aset
     * - Permintaan Maintenance
     * - Permintaan Repair
     */
    public function index()
    {
        $user = Auth::user();

        // Agenda dibangun oleh CalendarFeed (dipakai juga oleh API mobile).
        $events = app(CalendarFeed::class)->events($user);

        /*
        |--------------------------------------------------------------------------
        | 5. Daftar peserta untuk form Meeting
        |--------------------------------------------------------------------------
        */

        $users = User::query()
            ->where('company_id', $user->company_id)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view(
            'dashboard.calendar.index',
            compact('events', 'users')
        );
    }

    /**
     * Buat agenda baru.
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validated = $this->validateEvent($request);

        $attendeeIds = collect($validated['attendees'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $this->validateCompanyAttendees(
            $validated['event_type'],
            $attendeeIds,
            $user
        );

        $event = DB::transaction(function () use (
            $validated,
            $attendeeIds,
            $user
        ) {
            $event = CalendarEvent::create([
                'company_id' => $user->company_id,
                'created_by' => $user->id,
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'location' => $validated['location'] ?? null,
                'event_type' => $validated['event_type'],
                'start_at' => $validated['start_at'],
                'end_at' => $validated['end_at'],
                'status' => 'scheduled',
            ]);

            if ($validated['event_type'] === 'meeting') {
                $participants = $attendeeIds
                    ->push((int) $user->id)
                    ->unique()
                    ->values();

                foreach ($participants as $participantId) {
                    $isOrganizer =
                        (int) $participantId === (int) $user->id;

                    CalendarEventAttendee::create([
                        'calendar_event_id' => $event->id,
                        'user_id' => $participantId,
                        'invited_by' => $user->id,
                        'response_status' => $isOrganizer
                            ? 'accepted'
                            : 'pending',
                        'responded_at' => $isOrganizer
                            ? now()
                            : null,
                    ]);
                }
            }

            return $event;
        });

        $this->notifyEventRecipients(
            (int) $event->id,
            $attendeeIds->all(),
            (int) $user->id,
            $validated['event_type'],
            'created'
        );

        return response()->json([
            'success' => true,
            'message' => $validated['event_type'] === 'announcement'
                ? 'Pengumuman berhasil dibuat.'
                : 'Meeting berhasil dibuat.',
        ], 201);
    }

    /**
     * Perbarui agenda milik pembuat.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();

        $event = CalendarEvent::query()
            ->where('company_id', $user->company_id)
            ->where('created_by', $user->id)
            ->where('status', 'scheduled')
            ->findOrFail($id);

        $validated = $this->validateEvent($request);

        $attendeeIds = collect($validated['attendees'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $this->validateCompanyAttendees(
            $validated['event_type'],
            $attendeeIds,
            $user
        );

        DB::transaction(function () use (
            $event,
            $validated,
            $attendeeIds,
            $user
        ) {
            $event->update([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'location' => $validated['location'] ?? null,
                'event_type' => $validated['event_type'],
                'start_at' => $validated['start_at'],
                'end_at' => $validated['end_at'],
            ]);

            // Pertahankan perilaku sinkronisasi peserta yang lama.
            $event->attendees()->detach();

            if ($validated['event_type'] === 'meeting') {
                $participants = $attendeeIds
                    ->push((int) $user->id)
                    ->unique()
                    ->values();

                foreach ($participants as $participantId) {
                    $isOrganizer =
                        (int) $participantId === (int) $user->id;

                    CalendarEventAttendee::create([
                        'calendar_event_id' => $event->id,
                        'user_id' => $participantId,
                        'invited_by' => $user->id,
                        'response_status' => $isOrganizer
                            ? 'accepted'
                            : 'pending',
                        'responded_at' => $isOrganizer
                            ? now()
                            : null,
                    ]);
                }
            }
        });

        $this->notifyEventRecipients(
            (int) $event->id,
            $attendeeIds->all(),
            (int) $user->id,
            $validated['event_type'],
            'updated'
        );

        return response()->json([
            'success' => true,
            'message' => 'Agenda berhasil diperbarui.',
        ]);
    }

    /**
     * Batalkan agenda tanpa menghapus riwayat.
     */
    public function destroy(int $id): JsonResponse
    {
        $user = Auth::user();

        $event = CalendarEvent::query()
            ->where('company_id', $user->company_id)
            ->where('created_by', $user->id)
            ->where('status', 'scheduled')
            ->with('attendees:id,name,email')
            ->findOrFail($id);

        $recipientIds = $event->attendees
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $event->update([
            'status' => 'cancelled',
        ]);

        $this->notifyEventRecipients(
            (int) $event->id,
            $recipientIds,
            (int) $user->id,
            $event->event_type,
            'cancelled'
        );

        return response()->json([
            'success' => true,
            'message' => 'Agenda dibatalkan. Riwayat tetap tersimpan.',
        ]);
    }

    /**
     * Kirim notifikasi kalender.
     *
     * Menggunakan Eloquent User agar method notify() tersedia.
     * Kegagalan pengiriman notifikasi tidak membatalkan penyimpanan agenda.
     */
    private function notifyEventRecipients(
        int $eventId,
        array $recipientIds,
        int $organizerId,
        string $eventType,
        string $action
    ): void {
        try {
            $currentUser = Auth::user();

            if (!$currentUser) {
                return;
            }

            $event = CalendarEvent::query()
                ->where('company_id', $currentUser->company_id)
                ->find($eventId);

            $organizer = User::query()
                ->where('company_id', $currentUser->company_id)
                ->find($organizerId);

            if (!$event || !$organizer) {
                return;
            }

            $recipientIds = collect($recipientIds)
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->reject(fn ($id) => $id === $organizerId)
                ->values();

            /*
            |--------------------------------------------------------------------------
            | Company Announcement
            |--------------------------------------------------------------------------
            */

            if ($eventType === 'announcement') {
                // Perilaku lama: pembatalan pengumuman tidak mengirim
                // notifikasi tambahan melalui mekanisme ini.
                if ($action === 'cancelled') {
                    return;
                }

                User::query()
                    ->where('company_id', $event->company_id)
                    ->where('id', '!=', $organizerId)
                    ->get()
                    ->each(function (User $recipient) use (
                        $event,
                        $organizer,
                        $action
                    ) {
                        try {
                            $recipient->notify(
                                new GlobalNotification(
                                    type: 'company_announcement',
                                    title: NotificationText::make($action === 'updated'
                                        ? 'Info perusahaan diperbarui'
                                        : 'Info perusahaan'),
                                    message: NotificationText::make(':name membuat pengumuman: :event', [
                                        'name' => $organizer->name,
                                        'event' => $event->title,
                                    ]),
                                    url: '/dashboard/calendar',
                                    extra: [
                                        'event_id' => $event->id,
                                        'event_title' => $event->title,
                                    ]
                                )
                            );
                        } catch (Throwable $e) {
                            Log::error(
                                'Gagal membuat notifikasi pengumuman kalender.',
                                [
                                    'event_id' => $event->id,
                                    'recipient_id' => $recipient->id,
                                    'error' => $e->getMessage(),
                                ]
                            );
                        }
                    });

                return;
            }

            /*
            |--------------------------------------------------------------------------
            | Meeting
            |--------------------------------------------------------------------------
            */

            $recipients = User::query()
                ->where('company_id', $event->company_id)
                ->whereIn('id', $recipientIds)
                ->get();

            foreach ($recipients as $recipient) {
                try {
                    if ($action === 'cancelled') {
                        if ($recipient->email) {
                            $recipient->notify(
                                new CalendarMeetingCancellation(
                                    $event,
                                    $organizer
                                )
                            );
                        }

                        $recipient->notify(
                            new GlobalNotification(
                                type: 'calendar_invitation',
                                title: NotificationText::make('Rapat dibatalkan'),
                                message: NotificationText::make(':name membatalkan rapat: :event', [
                                    'name' => $organizer->name,
                                    'event' => $event->title,
                                ]),
                                url: '/dashboard/calendar',
                                extra: [
                                    'event_id' => $event->id,
                                    'event_title' => $event->title,
                                ]
                            )
                        );

                        continue;
                    }

                    $recipient->notify(
                        new GlobalNotification(
                            type: 'calendar_invitation',
                            title: NotificationText::make($action === 'updated'
                                ? 'Rapat diperbarui'
                                : 'Undangan rapat'),
                            message: NotificationText::make($action === 'updated'
                                ? ':name memperbarui rapat: :event'
                                : ':name mengundang Anda ke rapat: :event', [
                                    'name' => $organizer->name,
                                    'event' => $event->title,
                                ]),
                            url: '/dashboard/calendar',
                            extra: [
                                'event_id' => $event->id,
                                'event_title' => $event->title,
                                'start_at' => Carbon::parse(
                                    $event->start_at
                                )->toDateTimeString(),
                                'end_at' => Carbon::parse(
                                    $event->end_at
                                )->toDateTimeString(),
                            ]
                        )
                    );

                    if ($recipient->email) {
                        $recipient->notify(
                            new CalendarMeetingInvitation(
                                $event,
                                $organizer
                            )
                        );
                    }
                } catch (Throwable $e) {
                    Log::error(
                        'Gagal mengirim notifikasi kalender.',
                        [
                            'event_id' => $event->id,
                            'recipient_id' => $recipient->id,
                            'action' => $action,
                            'error' => $e->getMessage(),
                        ]
                    );
                }
            }
        } catch (Throwable $e) {
            Log::error('Gagal memproses notifikasi kalender.', [
                'event_id' => $eventId,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Validasi data agenda.
     */
    private function validateEvent(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'location' => ['nullable', 'string', 'max:255'],

            'event_type' => [
                'required',
                Rule::in(['meeting', 'announcement']),
            ],

            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after:start_at'],

            'attendees' => ['nullable', 'array'],

            'attendees.*' => [
                'integer',
                'distinct',
                'exists:users,id',
            ],
        ], [
            'title.required' => 'Judul acara wajib diisi.',
            'event_type.required' => 'Jenis acara wajib dipilih.',
            'start_at.required' => 'Tanggal dan jam mulai wajib diisi.',
            'start_at.date' => 'Format tanggal dan jam mulai tidak valid.',
            'end_at.required' => 'Tanggal dan jam selesai wajib diisi.',
            'end_at.date' => 'Format tanggal dan jam selesai tidak valid.',
            'end_at.after' => 'Waktu selesai harus setelah waktu mulai.',
            'attendees.*.exists' => 'Salah satu peserta tidak ditemukan.',
            'attendees.*.distinct' =>
                'Peserta yang sama tidak boleh dipilih berulang kali.',
        ]);
    }

    /**
     * Pastikan peserta meeting berasal dari perusahaan yang sama.
     *
     * @param \Illuminate\Support\Collection<int, int> $attendeeIds
     */
    private function validateCompanyAttendees(
        string $eventType,
        \Illuminate\Support\Collection $attendeeIds,
        User $user
    ): void {
        if ($eventType !== 'meeting' || $attendeeIds->isEmpty()) {
            return;
        }

        $validCount = User::query()
            ->where('company_id', $user->company_id)
            ->whereIn('id', $attendeeIds)
            ->count();

        if ($validCount !== $attendeeIds->count()) {
            throw ValidationException::withMessages([
                'attendees' => 'Peserta harus berasal dari perusahaan Anda.',
            ]);
        }
    }
}
