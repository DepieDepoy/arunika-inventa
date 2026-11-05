<?php

namespace App\Services\Maintenance;

use App\Models\Maintenance;
use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Notifications\GlobalNotification;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Notifikasi alur request maintenance/repair (dipakai web & API mobile).
 *
 * Alur:
 *  1. created   : pemohon membuat request → seluruh tim maintenance diberi tahu.
 *  2. taken     : teknisi A mengambil request → pemohon diberi tahu, dan
 *                 notifikasi "request baru" milik teknisi lain diubah menjadi
 *                 "sedang dikerjakan oleh A" + ditandai terbaca (hilang dari
 *                 hitungan belum dibaca).
 *  3. progress  : teknisi menambah progress → pemohon diberi tahu.
 *  4. completed : selesai → pemohon diberi tahu.
 *  5. cancelled : pemohon membatalkan → notifikasi tim ditutup.
 *
 * Tim maintenance = user aktif di perusahaan yang role-nya (aktif) memiliki
 * permission `maintenance.edit`. Bila perusahaan belum punya role teknisi,
 * notifikasi dikirim ke Administrator agar request tidak terlewat.
 *
 * Kegagalan notifikasi tidak pernah membatalkan proses utama.
 */
class MaintenanceRequestNotifier
{
    public const TYPE = 'maintenance_request';

    public const TYPE_LABELS = [
        'maintenance' => 'Maintenance',
        'repair' => 'Perbaikan',
        'problem' => 'Laporan Masalah',
        'other' => 'Permintaan Lain',
    ];

    public function created(MaintenanceRequest $request): void
    {
        $this->safely(function () use ($request) {
            $request->loadMissing(['asset', 'requester']);

            $recipients = $this->team($request->company_id)
                ->reject(fn (User $u) => (int) $u->id === (int) $request->requested_by);

            foreach ($recipients as $recipient) {
                $recipient->notify($this->notification(
                    $request,
                    'created',
                    'Request ' . $this->typeLabel($request) . ' baru',
                    sprintf(
                        '%s mengajukan %s untuk %s. Ambil request untuk mulai mengerjakan.',
                        $request->requester?->name ?? 'User',
                        strtolower($this->typeLabel($request)),
                        $this->assetLabel($request)
                    ),
                    $this->teamUrl()
                ));
            }
        });
    }

    public function taken(MaintenanceRequest $request, User $handler): void
    {
        $this->safely(function () use ($request, $handler) {
            $request->loadMissing(['asset', 'requester']);

            if ((int) $request->requested_by !== (int) $handler->id && $request->requester) {
                $request->requester->notify($this->notification(
                    $request,
                    'taken',
                    'Request sedang dikerjakan',
                    sprintf(
                        'Request %s untuk %s sedang dikerjakan oleh %s.',
                        strtolower($this->typeLabel($request)),
                        $this->assetLabel($request),
                        $handler->name
                    ),
                    $this->requesterUrl()
                ));
            }

            $this->closeTeamNotifications(
                $request,
                'Request ' . $this->typeLabel($request) . ' sudah diambil',
                sprintf('%s — sedang dikerjakan oleh %s.', $this->assetLabel($request), $handler->name),
                ['status' => 'in_progress', 'handler_id' => $handler->id, 'handler_name' => $handler->name]
            );
        });
    }

    public function progress(MaintenanceRequest $request, User $handler, string $note): void
    {
        $this->safely(function () use ($request, $handler, $note) {
            $request->loadMissing(['asset', 'requester']);

            if (!$request->requester || (int) $request->requested_by === (int) $handler->id) {
                return;
            }

            $request->requester->notify($this->notification(
                $request,
                'progress',
                'Update progress ' . $this->assetLabel($request),
                sprintf('%s: %s', $handler->name, \Illuminate\Support\Str::limit($note, 140)),
                $this->requesterUrl()
            ));
        });
    }

    public function completed(MaintenanceRequest $request, User $handler, ?Maintenance $maintenance = null): void
    {
        $this->safely(function () use ($request, $handler, $maintenance) {
            $request->loadMissing(['asset', 'requester']);

            if (!$request->requester || (int) $request->requested_by === (int) $handler->id) {
                return;
            }

            $request->requester->notify($this->notification(
                $request,
                'completed',
                'Request selesai',
                sprintf(
                    'Request %s untuk %s telah diselesaikan oleh %s.',
                    strtolower($this->typeLabel($request)),
                    $this->assetLabel($request),
                    $handler->name
                ),
                $this->requesterUrl(),
                ['maintenance_id' => $maintenance?->id]
            ));
        });
    }

    public function cancelled(MaintenanceRequest $request): void
    {
        $this->safely(function () use ($request) {
            $request->loadMissing(['asset', 'requester']);

            $this->closeTeamNotifications(
                $request,
                'Request ' . $this->typeLabel($request) . ' dibatalkan',
                sprintf('%s — request dibatalkan oleh pemohon.', $this->assetLabel($request)),
                ['status' => 'cancelled']
            );
        });
    }

    /**
     * Anggota tim maintenance yang menerima request baru.
     *
     * @return Collection<int, User>
     */
    public function team(int $companyId): Collection
    {
        $technicians = User::query()
            ->where('company_id', $companyId)
            ->where('status', 1)
            ->whereHas('role', fn ($role) => $role
                ->where('status', 1)
                // Administrator hanya cadangan, agar tidak dibanjiri notifikasi.
                ->where('role_code', '!=', 'administrator')
                ->whereHas('permissions', fn ($p) => $p->where('permission_code', 'maintenance.edit')))
            ->get();

        if ($technicians->isNotEmpty()) {
            return $technicians;
        }

        return User::query()
            ->where('company_id', $companyId)
            ->where('status', 1)
            ->whereHas('role', fn ($role) => $role->where('role_code', 'administrator'))
            ->get();
    }

    public function typeLabel(MaintenanceRequest $request): string
    {
        return self::TYPE_LABELS[$request->request_type] ?? ucfirst((string) $request->request_type);
    }

    /**
     * Notifikasi "request baru" yang masih terbuka di tim diperbarui
     * keterangannya lalu ditandai terbaca agar tidak lagi menunggu diambil.
     */
    protected function closeTeamNotifications(MaintenanceRequest $request, string $title, string $message, array $extra): void
    {
        DatabaseNotification::query()
            ->where('notifiable_type', User::class)
            ->where('data->type', self::TYPE)
            ->where('data->action', 'created')
            ->where('data->maintenance_request_id', $request->id)
            ->get()
            ->each(function (DatabaseNotification $notification) use ($title, $message, $extra) {
                $data = (array) $notification->data;
                $data['title'] = $title;
                $data['message'] = $message;
                $data['action'] = 'closed';

                $notification->forceFill([
                    'data' => array_merge($data, $extra),
                    'read_at' => $notification->read_at ?? now(),
                ])->save();
            });
    }

    protected function notification(
        MaintenanceRequest $request,
        string $action,
        string $title,
        string $message,
        string $url,
        array $extra = []
    ): GlobalNotification {
        return new GlobalNotification(
            type: self::TYPE,
            title: $title,
            message: $message,
            url: $url,
            extra: array_merge([
                'action' => $action,
                'maintenance_request_id' => $request->id,
                'request_type' => $request->request_type,
                'asset_id' => $request->asset_id,
                'asset_name' => $request->asset?->asset_name,
            ], $extra),
        );
    }

    protected function assetLabel(MaintenanceRequest $request): string
    {
        $asset = $request->asset;

        if (!$asset) {
            return 'aset #' . $request->asset_id;
        }

        return trim($asset->asset_name . ' (' . $asset->asset_code . ')');
    }

    protected function teamUrl(): string
    {
        return route('maintenance.requests.index', [], false);
    }

    protected function requesterUrl(): string
    {
        return route('my-assets.index', [], false);
    }

    protected function safely(callable $callback): void
    {
        try {
            $callback();
        } catch (Throwable $e) {
            report($e);
        }
    }
}
