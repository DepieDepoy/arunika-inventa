<?php

namespace App\Services\Calendar;

use App\Models\Asset;
use App\Models\CalendarEvent;
use App\Models\MaintenanceRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Sumber agenda kalender yang dipakai bersama oleh web
 * (Dashboard\CalendarController) dan API mobile (Api\V1\CalendarController):
 *
 * - Meeting & Company Announcement
 * - Jadwal Maintenance Aset
 * - Permintaan Maintenance / Repair
 */
class CalendarFeed
{
    /**
     * @param  Carbon|null  $from  batas awal (opsional, untuk API)
     * @param  Carbon|null  $to    batas akhir (opsional, untuk API)
     */
    public function events(User $user, ?Carbon $from = null, ?Carbon $to = null): Collection
    {
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
                    'source' => 'event',
                    'event_id' => $event->id,
                    'asset_id' => null,
                    'request_id' => null,
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
                    'source' => 'maintenance_schedule',
                    'event_id' => null,
                    'asset_id' => $asset->id,
                    'request_id' => null,
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
                    'source' => 'maintenance_request',
                    'event_id' => null,
                    'asset_id' => $maintenanceRequest->asset_id,
                    'request_id' => $maintenanceRequest->id,
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

        if ($from || $to) {
            $events = $events->filter(function (array $event) use ($from, $to) {
                $start = Carbon::parse($event['start']);
                $end = Carbon::parse($event['end']);

                return (!$to || $start->lte($to)) && (!$from || $end->gte($from));
            })->values();
        }

        return $events;
    }
}
