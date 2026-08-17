<div class="iq-navbar-header" style="height: 215px;">

    <div class="container-fluid iq-container">

        <div class="row">
            <div class="col-md-12">

                <div class="flex-wrap d-flex justify-content-between align-items-center">
                @php
                    $company = auth()->user()->company;

                    $expiredAt = $company->expired_at
                        ? \Carbon\Carbon::parse($company->expired_at)
                        : null;

                    $daysLeft = $expiredAt
                        ? now()->startOfDay()->diffInDays($expiredAt->startOfDay(), false)
                        : null;
                @endphp

                <div>

                    @if ($expiredAt && $daysLeft < 0)

                        {{-- EXPIRED --}}
                        <h1>
                            Subscription Telah Berakhir ⚠️
                        </h1>

                        <p>
                            Masa berlangganan
                            <strong>{{ $company->company_name }}</strong>
                            telah berakhir.
                            Silakan lakukan perpanjangan untuk melanjutkan penggunaan AMS.
                        </p>

                    @elseif ($expiredAt && $daysLeft <= 7)

                        {{-- AKAN EXPIRED --}}
                        <h1>
                            Selamat Datang di AMS 👋
                        </h1>

                        <p>
                            Subscription
                            <strong>{{ $company->company_name }}</strong>
                            akan berakhir dalam
                            <strong>{{ $daysLeft }} hari</strong>.
                            Silakan lakukan perpanjangan agar layanan tetap dapat digunakan.
                        </p>

                    @else

                        {{-- AKTIF --}}
                        <h1>
                            Selamat Datang di AMS 👋
                        </h1>

                        <p>
                            Kelola kebutuhan
                            <strong>{{ $company->company_name }}</strong>
                            dengan mudah dan terintegrasi.

                            @if ($expiredAt)
                                Subscription aktif sampai
                                <strong>{{ $expiredAt->translatedFormat('d F Y') }}</strong>.
                            @endif
                        </p>

                    @endif

                </div>
                </div>
            </div>
        </div>
    </div>
    <div class="iq-header-img">
        @if($daysLeft <= 7 && $daysLeft > 0)
            <img src="{{ asset('assets/images/dashboard/top-header5.png') }}"
                alt="header"
                class="img-fluid w-100 h-100 animated-scaleX">
        @elseif($daysLeft == 0)
            <img src="{{ asset('assets/images/dashboard/top-header5.png') }}"
                alt="header"
                class="img-fluid w-100 h-100 animated-scaleX">
        @elseif($daysLeft < 0)
            <img src="{{ asset('assets/images/dashboard/top-header2.png') }}"
                alt="header"
                class="img-fluid w-100 h-100 animated-scaleX">
        @else
            <img src="{{ asset('assets/images/dashboard/top-header.png') }}"
                alt="header"
                class="img-fluid w-100 h-100 animated-scaleX">
        @endif
    </div>
    <!--<div class="iq-header-img">
        
        <img src="{{ asset('assets/images/dashboard/top-header.png') }}"
             alt="header"
             class="theme-color-default-img img-fluid w-100 h-100 animated-scaleX">

        <img src="{{ asset('assets/images/dashboard/top-header1.png') }}"
             alt="header"
             class="theme-color-purple-img img-fluid w-100 h-100 animated-scaleX">

        <img src="{{ asset('assets/images/dashboard/top-header2.png') }}"
             alt="header"
             class="theme-color-blue-img img-fluid w-100 h-100 animated-scaleX">

        <img src="{{ asset('assets/images/dashboard/top-header3.png') }}"
             alt="header"
             class="theme-color-green-img img-fluid w-100 h-100 animated-scaleX">

        <img src="{{ asset('assets/images/dashboard/top-header4.png') }}"
             alt="header"
             class="theme-color-yellow-img img-fluid w-100 h-100 animated-scaleX">

        <img src="{{ asset('assets/images/dashboard/top-header5.png') }}"
             alt="header"
             class="theme-color-pink-img img-fluid w-100 h-100 animated-scaleX">

    </div>-->

</div>