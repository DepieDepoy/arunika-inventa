<nav class="nav navbar navbar-expand-lg navbar-light iq-navbar">
    <div class="container-fluid navbar-inner">

        <a href="{{ url('/dashboard') }}" class="navbar-brand">

            {{-- Logo --}}
            <div class="logo-main">
                <div class="logo-normal">
                    <svg class="text-primary icon-30" viewBox="0 0 30 30"
                        fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect x="-0.757324" y="19.2427" width="28" height="4"
                            rx="2" transform="rotate(-45 -0.757324 19.2427)"
                            fill="currentColor"/>
                        <rect x="7.72803" y="27.728" width="28" height="4"
                            rx="2" transform="rotate(-45 7.72803 27.728)"
                            fill="currentColor"/>
                        <rect x="10.5366" y="16.3945" width="16" height="4"
                            rx="2" transform="rotate(45 10.5366 16.3945)"
                            fill="currentColor"/>
                        <rect x="10.5562" y="-0.556152" width="28" height="4"
                            rx="2" transform="rotate(45 10.5562 -0.556152)"
                            fill="currentColor"/>
                    </svg>
                </div>

                <div class="logo-mini">
                    <svg class="text-primary icon-30" viewBox="0 0 30 30"
                        fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect x="-0.757324" y="19.2427" width="28" height="4"
                            rx="2" transform="rotate(-45 -0.757324 19.2427)"
                            fill="currentColor"/>
                        <rect x="7.72803" y="27.728" width="28" height="4"
                            rx="2" transform="rotate(-45 7.72803 27.728)"
                            fill="currentColor"/>
                        <rect x="10.5366" y="16.3945" width="16" height="4"
                            rx="2" transform="rotate(45 10.5366 16.3945)"
                            fill="currentColor"/>
                        <rect x="10.5562" y="-0.556152" width="28" height="4"
                            rx="2" transform="rotate(45 10.5562 -0.556152)"
                            fill="currentColor"/>
                    </svg>
                </div>
            </div>

            <h4 class="logo-title">Vasetra</h4>
        </a>

        {{-- Sidebar Toggle --}}
        <div class="sidebar-toggle" data-toggle="sidebar" data-active="true">
            <i class="icon">
                <svg width="20px" class="icon-20" viewBox="0 0 24 24">
                    <path fill="currentColor"
                        d="M4,11V13H16L10.5,18.5L11.92,19.92L19.84,12L11.92,4.08L10.5,5.5L16,11H4Z"/>
                </svg>
            </i>
        </div>

        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#navbarSupportedContent"
            aria-controls="navbarSupportedContent"
            aria-expanded="false"
            aria-label="Toggle navigation"
        >
            <span class="navbar-toggler-icon">
                <span class="mt-2 navbar-toggler-bar bar1"></span>
                <span class="navbar-toggler-bar bar2"></span>
                <span class="navbar-toggler-bar bar3"></span>
            </span>
        </button>

        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="mb-2 navbar-nav ms-auto align-items-center navbar-list mb-lg-0">

                {{-- =====================================================
                    NOTIFICATION DROPDOWN
                ====================================================== --}}

                @php
                    $navbarUser = auth()->user();

                    $unreadNotificationCount = $navbarUser
                        ? $navbarUser->unreadNotifications()->count()
                        : 0;

                    $navbarNotifications = $navbarUser
                        ? $navbarUser->notifications()
                            ->latest()
                            ->take(5)
                            ->get()
                        : collect();
                @endphp

                <li class="nav-item dropdown">

                    <a
                        href="#"
                        class="nav-link position-relative"
                        id="notification-drop"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                        aria-label="Notifikasi"
                    >
                        <svg
                            class="icon-24"
                            width="24"
                            viewBox="0 0 24 24"
                            fill="none"
                            xmlns="http://www.w3.org/2000/svg"
                        >
                            <path
                                d="M19.7695 11.6453C19.039 10.7923 18.7071 10.0531 18.7071 8.79716V8.37013C18.7071 6.73354 18.3304 5.67907 17.5115 4.62459C16.2493 2.98699 14.1244 2 12.0442 2H11.9558C9.91935 2 7.86106 2.94167 6.577 4.5128C5.71333 5.58842 5.29293 6.68822 5.29293 8.37013V8.79716C5.29293 10.0531 4.98284 10.7923 4.23049 11.6453C3.67691 12.2738 3.5 13.0815 3.5 13.9557C3.5 14.8309 3.78723 15.6598 4.36367 16.3336C5.11602 17.1413 6.17846 17.6569 7.26375 17.7466C8.83505 17.9258 10.4063 17.9933 12.0005 17.9933C13.5937 17.9933 15.165 17.8805 16.7372 17.7466C17.8215 17.6569 18.884 17.1413 19.6363 16.3336C20.2118 15.6598 20.5 14.8309 20.5 13.9557C20.5 13.0815 20.3231 12.2738 19.7695 11.6453Z"
                                fill="currentColor"
                            />
                            <path
                                opacity="0.4"
                                d="M14.0088 19.2283C13.5088 19.1215 10.4627 19.1215 9.96275 19.2283C9.53539 19.327 9.07324 19.5566 9.07324 20.0602C9.09809 20.5406 9.37935 20.9646 9.76895 21.2335L9.76795 21.2345C10.2718 21.6273 10.8632 21.877 11.4824 21.9667C11.8123 22.012 12.1482 22.01 12.4901 21.9667C13.1082 21.877 13.6997 21.6273 14.2036 21.2345L14.2026 21.2335C14.5922 20.9646 14.8983 20.0602 14.8983 20.0602Z"
                                fill="currentColor"
                            />
                        </svg>

                        @if ($unreadNotificationCount > 0)
                            <span
                                class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                                style="font-size: 10px;"
                            >
                                {{ $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount }}
                            </span>
                        @endif
                    </a>

                    <div
                        class="p-0 sub-drop dropdown-menu dropdown-menu-end"
                        aria-labelledby="notification-drop"
                        style="width: 350px; max-width: 90vw;"
                    >
                        <div class="m-0 shadow-none card">

                            {{-- Header --}}
                            <div class="py-3 card-header d-flex justify-content-between align-items-center bg-primary">
                                <div class="header-title">
                                    <h5 class="mb-0 text-white">
                                        Notifikasi
                                    </h5>

                                    <small class="text-white">
                                        {{ $unreadNotificationCount }} belum dibaca
                                    </small>
                                </div>
                            </div>

                            {{-- Notification List --}}
                            <div class="p-0 card-body">

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

                            </div>
                        </div>
                    </div>
                </li>

                {{-- =====================================================
                    USER PROFILE DROPDOWN
                ====================================================== --}}

                <li class="nav-item dropdown">

                    <a
                        class="py-0 nav-link d-flex align-items-center"
                        href="#"
                        id="navbarDropdown"
                        role="button"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                    >
                        <img
                            src="{{ asset('assets/images/avatars/01.png') }}"
                            alt="User Profile"
                            class="theme-color-default-img img-fluid avatar avatar-50 avatar-rounded"
                        >

                        <img
                            src="{{ asset('assets/images/avatars/avtar_1.png') }}"
                            alt="User Profile"
                            class="theme-color-purple-img img-fluid avatar avatar-50 avatar-rounded"
                        >

                        <img
                            src="{{ asset('assets/images/avatars/avtar_2.png') }}"
                            alt="User Profile"
                            class="theme-color-blue-img img-fluid avatar avatar-50 avatar-rounded"
                        >

                        <img
                            src="{{ asset('assets/images/avatars/avtar_4.png') }}"
                            alt="User Profile"
                            class="theme-color-green-img img-fluid avatar avatar-50 avatar-rounded"
                        >

                        <img
                            src="{{ asset('assets/images/avatars/avtar_5.png') }}"
                            alt="User Profile"
                            class="theme-color-yellow-img img-fluid avatar avatar-50 avatar-rounded"
                        >

                        <img
                            src="{{ asset('assets/images/avatars/avtar_3.png') }}"
                            alt="User Profile"
                            class="theme-color-pink-img img-fluid avatar avatar-50 avatar-rounded"
                        >

                        <div class="caption ms-3 d-none d-md-block">
                            <h6 class="mb-0 caption-title">
                                {{ auth()->user()->name }}
                            </h6>

                            <p class="mb-0 caption-sub-title">
                                {{ auth()->user()->company->company_name ?? 'Vasetra' }}
                            </p>
                        </div>
                    </a>

                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">

                        <li>
                            <a class="dropdown-item" href="{{ route('profile.index') }}">
                                <i class="fa-solid fa-user me-2"></i>
                                Profile
                            </a>
                        </li>

                        <li>
                            <hr class="dropdown-divider">
                        </li>

                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf

                                <button type="submit" class="dropdown-item">
                                    <i class="fa-solid fa-right-from-bracket me-2"></i>
                                    Logout
                                </button>
                            </form>
                        </li>

                    </ul>
                </li>

            </ul>
        </div>
    </div>
</nav>
