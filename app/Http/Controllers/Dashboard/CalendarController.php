<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\CalendarEvent;
use App\Models\CalendarEventAttendee;
use App\Models\User;
use App\Notifications\CalendarMeetingInvitation;
use App\Notifications\CalendarMeetingCancellation;
use App\Notifications\GlobalNotification;
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
     * Tampilkan agenda perusahaan yang boleh dilihat user.
     */
    public function index()
    {
        $user = Auth::user();

        $events = CalendarEvent::query()
            ->where('company_id', $user->company_id)
            ->whereIn('status', ['scheduled', 'cancelled'])
            ->where(function ($query) use ($user) {
                $query->where('event_type', 'announcement')
                    ->orWhere('created_by', $user->id)
                    ->orWhereHas('attendees', function ($attendees) use ($user) {
                        $attendees->where('users.id', $user->id);
                    });
            })
            ->with('attendees:id,name')
            ->orderBy('start_at')
            ->get()
            ->map(function ($event) {
                $attendees = $event->attendees;

                // Pastikan pembuat selalu terlihat dalam daftar peserta.
                $creator = User::query()
                    ->where('company_id', $event->company_id)
                    ->find($event->created_by);

                if (
                    $creator &&
                    !$attendees->contains('id', $creator->id)
                ) {
                    $attendees->push($creator);
                }

                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'start' => $event->start_at->format('Y-m-d\TH:i'),
                    'end' => $event->end_at->format('Y-m-d\TH:i'),
                    'type' => $event->event_type,
                    'description' => $event->description,
                    'location' => $event->location,
                    'created_by' => $event->created_by,
                    'status' => $event->status,

                    'attendee_ids' => $attendees
                        ->pluck('id')
                        ->unique()
                        ->values()
                        ->all(),

                    'attendees' => $attendees
                        ->pluck('name')
                        ->unique()
                        ->values()
                        ->all(),
                ];
            })
            ->values();

        $users = User::query()
            ->where('company_id', $user->company_id)
            //->where('id', '!=', $user->id)
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

        // Proses simpan tetap sederhana seperti controller lama.
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
                    CalendarEventAttendee::create([
                        'calendar_event_id' => $event->id,
                        'user_id' => $participantId,
                        'invited_by' => $user->id,
                        'response_status' =>
                            (int) $participantId === (int) $user->id
                                ? 'accepted'
                                : 'pending',
                        'responded_at' =>
                            (int) $participantId === (int) $user->id
                                ? now()
                                : null,
                    ]);
                }
            }

            return $event;
        });

        // Notifikasi dijalankan setelah transaksi simpan berhasil.
        // Kegagalan notifikasi tidak membuat proses simpan dilaporkan gagal.
        $this->notifyEventRecipients(
            $event->id,
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

            // Sinkronisasi peserta hanya untuk meeting.
            $event->attendees()->detach();

            if ($validated['event_type'] === 'meeting') {
                $participants = $attendeeIds
                    ->push((int) $user->id)
                    ->unique()
                    ->values();

                foreach ($participants as $participantId) {
                    CalendarEventAttendee::create([
                        'calendar_event_id' => $event->id,
                        'user_id' => $participantId,
                        'invited_by' => $user->id,
                        'response_status' =>
                            (int) $participantId === (int) $user->id
                                ? 'accepted'
                                : 'pending',
                        'responded_at' =>
                            (int) $participantId === (int) $user->id
                                ? now()
                                : null,
                    ]);
                }
            }
        });

        // Notifikasi dilakukan sesudah transaksi berhasil.
        $this->notifyEventRecipients(
            $event->id,
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

        // Simpan daftar peserta sebelum mengubah status.
        $recipientIds = $event->attendees
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $event->update([
            'status' => 'cancelled',
        ]);

        // Email pembatalan dikirim terpisah dari proses update status.
        $this->notifyEventRecipients(
            $event->id,
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
     * Kirim notifikasi tanpa membiarkan error notifikasi
     * membatalkan respons proses simpan/update.
     */
    private function notifyEventRecipients(
        int $eventId,
        array $recipientIds,
        int $organizerId,
        string $eventType,
        string $action
    ): void {
        try {
            $event = CalendarEvent::query()
                ->where('company_id', Auth::user()->company_id)
                ->find($eventId);

            $organizer = User::query()
                ->where('id', $organizerId)
                ->where('company_id', Auth::user()->company_id)
                ->first();

            if (!$event || !$organizer) {
                return;
            }

            $recipientIds = collect($recipientIds)
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->reject(fn ($id) => $id === $organizerId)
                ->values();

            // Pengumuman perusahaan: notifikasi database ke pengguna lain.
            if ($eventType === 'announcement') {
                if ($action === 'cancelled') {
                    return;
                }

                User::query()
                    ->where('company_id', $event->company_id)
                    ->where('id', '!=', $organizerId)
                    ->get()
                    ->each(function ($recipient) use (
                        $event,
                        $organizer,
                        $action
                    ) {
                        try {
                            $recipient->notify(new GlobalNotification(
                                type: 'company_announcement',
                                title: $action === 'updated'
                                    ? 'Info Perusahaan Diperbarui'
                                    : 'Info Perusahaan',
                                message: $organizer->name
                                    . ' membuat pengumuman: '
                                    . $event->title,
                                url: '/dashboard/calendar',
                                extra: [
                                    'event_id' => $event->id,
                                    'event_title' => $event->title,
                                ]
                            ));
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

            // Meeting: peserta yang dipilih, email tetap diproses
            // menggunakan notifikasi undangan yang sudah ada.
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

                        $recipient->notify(new GlobalNotification(
                            type: 'calendar_invitation',
                            title: 'Meeting Dibatalkan',
                            message: $organizer->name
                                . ' membatalkan meeting: '
                                . $event->title,
                            url: '/dashboard/calendar',
                            extra: [
                                'event_id' => $event->id,
                                'event_title' => $event->title,
                            ]
                        ));

                        continue;
                    }

                    $recipient->notify(new GlobalNotification(
                        type: 'calendar_invitation',
                        title: $action === 'updated'
                            ? 'Meeting Diperbarui'
                            : 'Undangan Meeting',
                        message: $organizer->name
                            . (
                                $action === 'updated'
                                    ? ' memperbarui meeting: '
                                    : ' mengundang Anda ke meeting: '
                            )
                            . $event->title,
                        url: '/dashboard/calendar',
                        extra: [
                            'event_id' => $event->id,
                            'event_title' => $event->title,
                            'start_at' => $event->start_at->toDateTimeString(),
                            'end_at' => $event->end_at->toDateTimeString(),
                        ]
                    ));

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
     * Validasi agenda.
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
     */
    private function validateCompanyAttendees(
        string $eventType,
        $attendeeIds,
        $user
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