<?php

namespace App\Services\Maintenance;

use App\Models\Maintenance;
use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Notifications\GlobalNotification;
use App\Support\NotificationText;
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

    /**
     * Nama jenis permintaan untuk teks notifikasi (diterjemahkan lewat
     * lang/en.json): [huruf besar untuk judul, huruf kecil untuk kalimat].
     */
    public const TYPE_TEXT = [
        'maintenance' => ['Perawatan', 'perawatan'],
        'repair' => ['Perbaikan', 'perbaikan'],
        'problem' => ['Laporan masalah', 'laporan masalah'],
        'other' => ['Lainnya', 'lainnya'],
    ];

    public function created(MaintenanceRequest $request): void
    {
        $this->safely(function () use ($request) {
            $request->loadMissing(['asset', 'requester']);

            $recipients = $this->team($request->company_id, $request->asset?->branch_id ? (int) $request->asset->branch_id : null)
                ->reject(fn (User $u) => (int) $u->id === (int) $request->requested_by);

            foreach ($recipients as $recipient) {
                $recipient->notify($this->notification(
                    $request,
                    'created',
                    NotificationText::make('Permintaan baru: :type', ['type' => $this->typeText($request)]),
                    NotificationText::make(
                        ':name mengajukan permintaan :type untuk :asset. Ambil permintaan untuk mulai mengerjakan.',
                        [
                            'name' => $request->requester?->name ?? 'Pengguna',
                            'type' => $this->typeText($request, lower: true),
                            'asset' => $this->assetLabel($request),
                        ]
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
                    NotificationText::make('Permintaan sedang dikerjakan'),
                    NotificationText::make('Permintaan :type untuk :asset sedang dikerjakan oleh :handler.', [
                        'type' => $this->typeText($request, lower: true),
                        'asset' => $this->assetLabel($request),
                        'handler' => $handler->name,
                    ]),
                    $this->requesterUrl()
                ));
            }

            $this->closeTeamNotifications(
                $request,
                NotificationText::make('Permintaan :type sudah diambil', ['type' => $this->typeText($request, lower: true)]),
                NotificationText::make(':asset — sedang dikerjakan oleh :handler.', [
                    'asset' => $this->assetLabel($request),
                    'handler' => $handler->name,
                ]),
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
                NotificationText::make('Progres baru: :asset', ['asset' => $this->assetLabel($request)]),
                // Catatan teknisi ditampilkan apa adanya (tidak diterjemahkan).
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
                NotificationText::make('Permintaan selesai'),
                NotificationText::make('Permintaan :type untuk :asset telah diselesaikan oleh :handler.', [
                    'type' => $this->typeText($request, lower: true),
                    'asset' => $this->assetLabel($request),
                    'handler' => $handler->name,
                ]),
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
                NotificationText::make('Permintaan :type dibatalkan', ['type' => $this->typeText($request, lower: true)]),
                NotificationText::make(':asset — permintaan dibatalkan oleh pemohon.', [
                    'asset' => $this->assetLabel($request),
                ]),
                ['status' => 'cancelled']
            );
        });
    }

    /** Parameter jenis permintaan yang ikut diterjemahkan. */
    protected function typeText(MaintenanceRequest $request, bool $lower = false): array
    {
        $text = self::TYPE_TEXT[$request->request_type] ?? self::TYPE_TEXT['other'];

        return ['t' => $text[$lower ? 1 : 0]];
    }

    /**
     * Anggota tim maintenance yang menerima request baru.
     *
     * @return Collection<int, User>
     */
    public function team(int $companyId, ?int $assetBranchId = null): Collection
    {
        $technicians = fn (?int $branchId) => User::query()
            ->where('company_id', $companyId)
            ->where('status', 1)
            // Multi-cabang: hanya teknisi cabang yang melayani aset tersebut.
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereHas('role', fn ($role) => $role
                ->where('status', 1)
                // Administrator hanya cadangan, agar tidak dibanjiri notifikasi.
                ->where('role_code', '!=', 'administrator')
                ->whereHas('permissions', fn ($p) => $p->where('permission_code', 'maintenance.edit')))
            ->get();

        /*
         * Urutan penerima:
         *  1. teknisi cabang pelayan aset (cabang sendiri / "Maintenance ditangani oleh");
         *  2. teknisi Kantor Pusat (cabang tanpa teknisi);
         *  3. Administrator.
         */
        $serviceBranchId = \App\Support\BranchAccess::serviceBranchId($companyId, $assetBranchId);
        $headOfficeId = (int) \App\Support\BranchAccess::headOffice($companyId)->id;

        foreach (array_unique([$serviceBranchId, $headOfficeId]) as $branchId) {
            $found = $technicians($branchId);

            if ($found->isNotEmpty()) {
                return $found;
            }
        }

        // Tidak ada teknisi sama sekali: Administrator cabang aset (atau Kantor Pusat).
        return \App\Support\BranchAccess::administrators($companyId, $assetBranchId);
    }

    public function typeLabel(MaintenanceRequest $request): string
    {
        return self::TYPE_LABELS[$request->request_type] ?? ucfirst((string) $request->request_type);
    }

    /**
     * Notifikasi "request baru" yang masih terbuka di tim diperbarui
     * keterangannya lalu ditandai terbaca agar tidak lagi menunggu diambil.
     */
    protected function closeTeamNotifications(MaintenanceRequest $request, array $title, array $message, array $extra): void
    {
        DatabaseNotification::query()
            ->where('notifiable_type', User::class)
            ->where('data->type', self::TYPE)
            ->where('data->action', 'created')
            ->where('data->maintenance_request_id', $request->id)
            ->get()
            ->each(function (DatabaseNotification $notification) use ($title, $message, $extra) {
                $data = (array) $notification->data;
                $data['title'] = NotificationText::render($title, NotificationText::DEFAULT_LOCALE);
                $data['message'] = NotificationText::render($message, NotificationText::DEFAULT_LOCALE);
                $data['i18n'] = ['title' => $title, 'message' => $message];
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
        string|array $title,
        string|array $message,
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
            return '#' . $request->asset_id;
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
