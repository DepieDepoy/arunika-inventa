<div class="iq-navbar-header" style="height: 215px;">

    <div class="container-fluid iq-container">

        <div class="row">
            <div class="col-md-12">

                @php
                    $company = auth()->user()->company;

                    /*
                     * Masa aktif diambil dari paket yang benar-benar berlaku
                     * (bukan companies.expired_at yang hanya diisi saat registrasi).
                     * Bila tidak ada paket aktif, pakai paket terakhir yang sudah berakhir.
                     */
                    $headerSubscription = $company
                        ? (app(\App\Services\Subscription\SubscriptionService::class)->current($company->id)
                            ?? $company->subscriptions()
                                ->whereIn('status', ['active', 'replaced'])
                                ->whereDate('start_date', '<=', today())
                                ->orderByDesc('end_date')
                                ->first())
                        : null;

                    // Jika ada paket berikutnya di antrean, masa aktif tersambung.
                    $headerQueued = $company
                        ? app(\App\Services\Subscription\SubscriptionService::class)->queued($company->id)
                        : null;

                    $expiredAt = $headerQueued?->end_date
                        ?? ($headerSubscription?->isUpgrade()
                            ? $headerSubscription->parent?->end_date
                            : $headerSubscription?->end_date)
                        ?? ($company?->expired_at ? \Carbon\Carbon::parse($company->expired_at) : null);

                    $expiredAt = $expiredAt ? \Carbon\Carbon::parse($expiredAt) : null;

                    $daysLeft = $expiredAt
                        ? now()->startOfDay()->diffInDays(
                            $expiredAt->startOfDay(),
                            false
                        )
                        : null;

                    $isAdministrator =
                        auth()->user()->role?->role_code === 'administrator';

                    $hour = (int) now()->format('H');
                    $greeting = match (true) {
                        $hour < 11 => 'Selamat pagi',
                        $hour < 15 => 'Selamat siang',
                        $hour < 18 => 'Selamat sore',
                        default => 'Selamat malam',
                    };
                    $firstName = \Illuminate\Support\Str::of(auth()->user()->name)->explode(' ')->first();
                @endphp

                <div class="flex-wrap d-flex justify-content-between align-items-center">

                    <div>

                        <div class="vx-header-eyebrow">
                            {{ now()->translatedFormat('l, d F Y') }} · {{ $company?->company_name }}
                        </div>

                        {{-- =====================================================
                             SUBSCRIPTION EXPIRED
                        ====================================================== --}}

                        @if ($expiredAt && $daysLeft < 0)

                            <h1>
                                {{ __('Subscription Telah Berakhir ⚠️') }}
                            </h1>

                            <p class="mb-2">

                                {{ __('Masa berlangganan') }}
                                <strong>
                                    {{ $company->company_name }}
                                </strong>
                                {{ __('telah berakhir. Silakan lakukan perpanjangan untuk melanjutkan penggunaan Assets Management System.') }}

                            </p>


                            {{-- PERPANJANG - ADMINISTRATOR ONLY --}}

                            @if ($isAdministrator)

                                <a
                                    href="{{ route('subscription.index') }}"
                                    class="btn btn-danger"
                                >
                                    <i class="bi bi-arrow-repeat me-1"></i>
                                    {{ __('Perpanjang Subscription') }}
                                </a>

                            @endif


                        {{-- =====================================================
                             EXPIRED HARI INI
                        ====================================================== --}}

                        @elseif ($expiredAt && $daysLeft === 0)

                            <h1>
                                {{ __('Subscription Berakhir Hari Ini ⚠️') }}
                            </h1>

                            <p class="mb-2">

                                {{ __('Subscription') }}
                                <strong>
                                    {{ $company->company_name }}
                                </strong>
                                {{ __('akan berakhir') }}
                                <strong>{{ __('hari ini') }}</strong>{{ __('. Silakan lakukan perpanjangan agar layanan tetap dapat digunakan.') }}

                            </p>


                            {{-- PERPANJANG - ADMINISTRATOR ONLY --}}

                            @if ($isAdministrator)

                                <a
                                    href="{{ route('subscription.index') }}"
                                    class="btn btn-warning"
                                >
                                    <i class="bi bi-arrow-repeat me-1"></i>
                                    {{ __('Perpanjang Subscription') }}
                                </a>

                            @endif


                        {{-- =====================================================
                             AKAN EXPIRED <= 7 HARI
                        ====================================================== --}}

                        @elseif ($expiredAt && $daysLeft <= 7)

                            <h1>
                                {{ $greeting }}, {{ $firstName }} 👋
                            </h1>

                            <p class="mb-2">

                                {{ __('Subscription') }}
                                <strong>
                                    {{ $company->company_name }}
                                </strong>

                                {{ __('akan berakhir dalam') }}

                                <strong>
                                    {{ __(':p1 hari', ['p1' => $daysLeft]) }}
                                </strong>{{ __('. Silakan lakukan perpanjangan agar layanan tetap dapat digunakan.') }}

                            </p>


                            {{-- PERPANJANG - ADMINISTRATOR ONLY --}}

                            @if ($isAdministrator)

                                <a
                                    href="{{ route('subscription.index') }}"
                                    class="btn btn-light"
                                >
                                    <i class="bi bi-arrow-repeat me-1"></i>
                                    {{ __('Perpanjang Subscription') }}
                                </a>

                            @endif


                        {{-- =====================================================
                             SUBSCRIPTION AKTIF > 7 HARI
                        ====================================================== --}}

                        @else

                            <h1>
                                {{ $greeting }}, {{ $firstName }} 👋
                            </h1>

                            <p>

                                {{ __('Pantau aset, maintenance, dan kapasitas') }}
                                <strong>
                                    {{ $company->company_name }}
                                </strong>
                                dalam satu tempat.

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
