<div class="iq-navbar-header" style="height: 215px;">

    <div class="container-fluid iq-container">

        <div class="row">
            <div class="col-md-12">

                @php
                    $company = auth()->user()->company;

                    $expiredAt = $company?->expired_at
                        ? \Carbon\Carbon::parse($company->expired_at)
                        : null;

                    $daysLeft = $expiredAt
                        ? now()->startOfDay()->diffInDays(
                            $expiredAt->startOfDay(),
                            false
                        )
                        : null;

                    $isAdministrator =
                        auth()->user()->role?->role_code === 'administrator';
                @endphp

                <div class="flex-wrap d-flex justify-content-between align-items-center">

                    <div>

                        {{-- =====================================================
                             SUBSCRIPTION EXPIRED
                        ====================================================== --}}

                        @if ($expiredAt && $daysLeft < 0)

                            <h1>
                                Subscription Telah Berakhir ⚠️
                            </h1>

                            <p class="mb-2">

                                Masa berlangganan
                                <strong>
                                    {{ $company->company_name }}
                                </strong>
                                telah berakhir.

                                Silakan lakukan perpanjangan untuk
                                melanjutkan penggunaan
                                Assets Management System.

                            </p>


                            {{-- PERPANJANG - ADMINISTRATOR ONLY --}}

                            @if ($isAdministrator)

                                <a
                                    href="{{ route('subscription.index') }}"
                                    class="btn btn-danger"
                                >
                                    <i class="bi bi-arrow-repeat me-1"></i>
                                    Perpanjang Subscription
                                </a>

                            @endif


                        {{-- =====================================================
                             EXPIRED HARI INI
                        ====================================================== --}}

                        @elseif ($expiredAt && $daysLeft === 0)

                            <h1>
                                Subscription Berakhir Hari Ini ⚠️
                            </h1>

                            <p class="mb-2">

                                Subscription
                                <strong>
                                    {{ $company->company_name }}
                                </strong>
                                akan berakhir
                                <strong>hari ini</strong>.

                                Silakan lakukan perpanjangan agar
                                layanan tetap dapat digunakan.

                            </p>


                            {{-- PERPANJANG - ADMINISTRATOR ONLY --}}

                            @if ($isAdministrator)

                                <a
                                    href="{{ route('subscription.index') }}"
                                    class="btn btn-warning"
                                >
                                    <i class="bi bi-arrow-repeat me-1"></i>
                                    Perpanjang Subscription
                                </a>

                            @endif


                        {{-- =====================================================
                             AKAN EXPIRED <= 7 HARI
                        ====================================================== --}}

                        @elseif ($expiredAt && $daysLeft <= 7)

                            <h1>
                                Selamat Datang di Assets Management System 👋
                            </h1>

                            <p class="mb-2">

                                Subscription
                                <strong>
                                    {{ $company->company_name }}
                                </strong>

                                akan berakhir dalam

                                <strong>
                                    {{ $daysLeft }} hari
                                </strong>.

                                Silakan lakukan perpanjangan agar
                                layanan tetap dapat digunakan.

                            </p>


                            {{-- PERPANJANG - ADMINISTRATOR ONLY --}}

                            @if ($isAdministrator)

                                <a
                                    href="{{ route('subscription.index') }}"
                                    class="btn btn-primary"
                                >
                                    <i class="bi bi-arrow-repeat me-1"></i>
                                    Perpanjang Subscription
                                </a>

                            @endif


                        {{-- =====================================================
                             SUBSCRIPTION AKTIF > 7 HARI
                        ====================================================== --}}

                        @else

                            <h1>
                                Selamat Datang di Assets Management System 👋
                            </h1>

                            <p>

                                Kelola kebutuhan
                                <strong>
                                    {{ $company->company_name }}
                                </strong>
                                dengan mudah dan terintegrasi.

                                @if ($expiredAt)

                                    Subscription aktif sampai

                                    <strong>
                                        {{ $expiredAt->translatedFormat('d F Y') }}
                                    </strong>.

                                @endif

                            </p>

                        @endif

                    </div>

                </div>

            </div>
        </div>

    </div>


    {{-- =========================================================
         HEADER IMAGE
    ========================================================== --}}

    <div class="iq-header-img">

        @if ($daysLeft !== null && $daysLeft <= 7 && $daysLeft > 0)

            <img
                src="{{ asset('assets/images/dashboard/top-header5.png') }}"
                alt="header"
                class="img-fluid w-100 h-100 animated-scaleX"
            >

        @elseif ($daysLeft === 0)

            <img
                src="{{ asset('assets/images/dashboard/top-header5.png') }}"
                alt="header"
                class="img-fluid w-100 h-100 animated-scaleX"
            >

        @elseif ($daysLeft !== null && $daysLeft < 0)

            <img
                src="{{ asset('assets/images/dashboard/top-header2.png') }}"
                alt="header"
                class="img-fluid w-100 h-100 animated-scaleX"
            >

        @else

            <img
                src="{{ asset('assets/images/dashboard/top-header.png') }}"
                alt="header"
                class="img-fluid w-100 h-100 animated-scaleX"
            >

        @endif

    </div>

</div>
