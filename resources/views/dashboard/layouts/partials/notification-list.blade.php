{{-- Daftar notifikasi lonceng (dipakai navbar & endpoint /dashboard/notifications-summary) --}}
@forelse ($navbarNotifications as $notification)

    @php
        $notificationType = data_get(
            $notification->data,
            'type',
            'general'
        );

        $notificationTitle = data_get(
            $notification->data,
            'title',
            'Notifikasi Vasetra'
        );

        $notificationMessage = data_get(
            $notification->data,
            'message',
            'Anda memiliki notifikasi baru.'
        );

        $isUnread = is_null($notification->read_at);

        switch ($notificationType) {
            case 'calendar_invitation':
                $notificationIcon = 'fa-regular fa-calendar';
                $notificationIconColor = 'text-primary';
                break;

            case 'company_announcement':
                $notificationIcon = 'fa-solid fa-bullhorn';
                $notificationIconColor = 'text-warning';
                break;

            case 'maintenance':
            case 'maintenance_reminder':
                $notificationIcon = 'fa-solid fa-screwdriver-wrench';
                $notificationIconColor = 'text-danger';
                break;

            default:
                $notificationIcon = 'fa-regular fa-bell';
                $notificationIconColor = 'text-primary';
                break;
        }
    @endphp

    <a
        href="{{ route('dashboard.notifications.read', [
            'id' => $notification->id
        ]) }}"
        class="iq-sub-card d-block text-decoration-none"
        @if ($isUnread)
            style="background-color: rgba(13, 110, 253, 0.06);"
        @endif
    >
        <div class="d-flex align-items-start p-2">

            {{-- Notification Icon --}}
            <div class="me-3">
                <span
                    class="d-flex align-items-center justify-content-center rounded-circle bg-soft-primary"
                    style="width: 40px; height: 40px; flex-shrink: 0;"
                >
                    <i class="{{ $notificationIcon }} {{ $notificationIconColor }}"></i>
                </span>
            </div>

            {{-- Notification Content --}}
            <div class="w-100">

                <div class="d-flex justify-content-between align-items-start">
                    <h6 class="mb-1 text-dark">
                        {{ $notificationTitle }}
                    </h6>

                    @if ($isUnread)
                        <span
                            class="rounded-circle bg-primary flex-shrink-0 ms-2 mt-1"
                            style="width: 7px; height: 7px;"
                            title="Belum dibaca"
                        ></span>
                    @endif
                </div>

                <p class="mb-1 text-muted small">
                    {{ $notificationMessage }}
                </p>

                <small class="text-muted">
                    {{ $notification->created_at?->diffForHumans() }}
                </small>

            </div>
        </div>
    </a>

@empty

    <div class="text-center py-4 px-3">
        <i class="fa-regular fa-bell-slash fs-2 text-muted"></i>

        <p class="mb-1 mt-2 fw-semibold">
            Belum ada notifikasi
        </p>

        <small class="text-muted">
            Notifikasi meeting, Info Perusahaan,
            dan pemberitahuan lainnya akan tampil di sini.
        </small>
    </div>

@endforelse
