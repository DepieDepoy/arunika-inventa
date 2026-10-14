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

        /*
        |--------------------------------------------------------------------------
        | 1. Meeting dan Company Announcement
        |--------------------------------------------------------------------------
        */

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

                // Pastikan pembuat meeting ikut ditampilkan sebagai peserta.
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
                    'start' => Carbon::parse($event->start_at)
                        ->format('Y-m-d\TH:i'),
                    'end' => Carbon::parse($event->end_at)
                        ->format('Y-m-d\TH:i'),
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

                    // Agenda yang dibuat manual oleh pengguna.
                    'is_system' => false,
                    'source_url' => null,
                ];
            })
            ->values();

        /*
        |--------------------------------------------------------------------------
        | 2. Jadwal Maintenance Aset
        |--------------------------------------------------------------------------
        |
        | Menggunakan next_maintenance_date dari tabel assets.
        | Tidak membuat atau menggandakan data maintenance.
        |
        */

        $maintenanceEvents = Asset::query()
            ->where('company_id', $user->company_id)
            ->where('maintenance_required', 1)
            ->whereNotNull('next_maintenance_date')
            ->get([
                'id',
                'company_id',
                'asset_code',
                'asset_name',
                'location',
                'maintenance_type',
                'maintenance_trigger',
                'maintenance_interval',
                'maintenance_interval_unit',
                'next_maintenance_date',
            ])
            ->map(function ($asset) {
                $date = Carbon::parse($asset->next_maintenance_date);

                return [
                    'id' => 'asset-' . $asset->id,

                    'title' => 'Scheduled Maintenance - '
                        . $asset->asset_name,

                    // Jam 09.00–10.00 hanya penanda tampilan kalender,
                    // bukan jadwal pengerjaan yang telah dikonfirmasi.
                    'start' => $date->copy()
                        ->setTime(9, 0)
                        ->format('Y-m-d\TH:i'),

                    'end' => $date->copy()
                        ->setTime(18, 0)
                        ->format('Y-m-d\TH:i'),

                    'type' => 'maintenance',

                    'description' => implode("\n", array_filter([
                        'Kode aset: ' . $asset->asset_code,
                        'Nama aset: ' . $asset->asset_name,
                        $asset->maintenance_type
                            ? 'Tipe maintenance: ' . $asset->maintenance_type
                            : null,
                        $asset->maintenance_trigger
                            ? 'Pemicu maintenance: ' . $asset->maintenance_trigger
                            : null,
                        $asset->maintenance_interval
                            ? 'Interval: ' . $asset->maintenance_interval
                                . ' '
                                . ($asset->maintenance_interval_unit ?? '')
                            : null,
                    ])),

                    'location' => $asset->location,
                    'created_by' => null,
                    'status' => 'scheduled',
                    'attendee_ids' => [],
                    'attendees' => [],

                    'is_system' => true,
                    'source_url' => route('maintenance.index'),
                ];
            })
            ->values();

        /*
        |--------------------------------------------------------------------------
        | 3. Permintaan Maintenance dan Repair
        |--------------------------------------------------------------------------
        |
        | Karena model MaintenanceRequest belum memiliki kolom tanggal
        | pengerjaan terjadwal, kalender menggunakan created_at sebagai
        | tanggal permintaan dibuat, bukan tanggal janji pengerjaan.
        |
        */

        $requestEvents = MaintenanceRequest::query()
            ->where('company_id', $user->company_id)
            ->with([
                'asset:id,asset_code,asset_name,location',
            ])
            ->latest('created_at')
            ->get()
            ->map(function ($maintenanceRequest) {
                $createdAt = Carbon::parse(
                    $maintenanceRequest->created_at
                );

                $asset = $maintenanceRequest->asset;

                $requestType = strtolower(trim(
    (string) $maintenanceRequest->request_type
));

$type = match ($requestType) {
    'repair' => 'repair_request',
    'maintenance' => 'maintenance_request',
    default => 'maintenance_request',
};

$title = match ($requestType) {
    'repair' => 'Repair Request',
    'maintenance' => 'Maintenance Request',
    default => 'Other Request',
};

if ($asset) {
    $title .= ' - ' . $asset->asset_name;
}

$status = strtolower(trim(
    (string) $maintenanceRequest->status
));

$statusNormalized = str_replace(
    [' ', '-'],
    '_',
    $status
);

// Request yang dibatalkan tidak ditampilkan,
// apa pun jenis request-nya.
if (in_array(
    $statusNormalized,
    ['cancelled', 'canceled'],
    true
)) {
    return null;
}

// Tampilkan status untuk semua jenis request.
$statusLabel = match ($statusNormalized) {
    'pending' => 'Menunggu',
    'approved' => 'Disetujui',
    'in_progress', 'inprogress' => 'In Progress',
    'completed', 'complete' => 'Selesai',
    'rejected' => 'Ditolak',
    default => null,
};

if ($statusLabel) {
    $title .= ' — ' . $statusLabel;
}


                $descriptionParts = [
                    'Nomor permintaan: #' . $maintenanceRequest->id,
                    'Jenis: ' . ucfirst($requestType),
                    'Status: ' . $status,
                ];

                if ($asset) {
                    $descriptionParts[] = 'Kode aset: '
                        . $asset->asset_code;

                    $descriptionParts[] = 'Nama aset: '
                        . $asset->asset_name;
                }

                if ($maintenanceRequest->description) {
                    $descriptionParts[] = 'Keterangan: '
                        . $maintenanceRequest->description;
                }

                return [
                    'id' => 'request-' . $maintenanceRequest->id,
                    'title' => $title,

                    'start' => $createdAt->format('Y-m-d\TH:i'),
                    'end' => $createdAt->copy()
                        ->addMinutes(30)
                        ->format('Y-m-d\TH:i'),

                    'type' => $type,
                    'description' => implode("\n", $descriptionParts),
                    'location' => $asset?->location,
                    'created_by' => $maintenanceRequest->requested_by,
                    'status' => $status,

                    'attendee_ids' => [],
                    'attendees' => [],

                    'is_system' => true,
                    'source_url' => route(
                        'maintenance.requests.show',
                        $maintenanceRequest->id
                    ),
                ];
            })
            ->filter()
            ->values();

        /*
        |--------------------------------------------------------------------------
        | 4. Gabungkan seluruh agenda
        |--------------------------------------------------------------------------
        */

        $events = $events
            ->concat($maintenanceEvents)
            ->concat($requestEvents)
            ->sortBy('start')
            ->values();

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
                                title: 'Meeting Dibatalkan',
                                message: $organizer->name
                                    . ' membatalkan meeting: '
                                    . $event->title,
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
