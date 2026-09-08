@extends('dashboard.layouts.wrapper')

@section('title', 'Subscription')

@section('content')

<div class="container-fluid">

    <div class="row justify-content-center">
        <div class="col-md-7">

            <div class="card">
                <div class="card-body text-center py-5">

                    <div class="mb-4">
                        <i
                            class="fa-solid fa-triangle-exclamation text-warning"
                            style="font-size: 60px;"
                        ></i>
                    </div>

                    <h3 class="fw-bold mb-3">
                        Subscription Telah Berakhir
                    </h3>

                    <p class="text-muted mb-4">
                        Subscription perusahaan Anda telah berakhir.
                        Silakan perpanjang subscription untuk kembali
                        menggunakan seluruh fitur Inventa.
                    </p>

                    @if($subscription)

                        <div class="mb-4">
                            <div>
                                <strong>Paket:</strong>
                                {{ $subscription->plan?->plan_name ?? '-' }}
                            </div>

                            <div>
                                <strong>Berakhir:</strong>
                                {{ $subscription->end_date?->format('d F Y') ?? '-' }}
                            </div>
                        </div>

                    @endif

                    <button
                        type="button"
                        class="btn btn-primary"
                        disabled
                    >
                        <i class="fa-solid fa-credit-card me-1"></i>
                        Perpanjang Subscription
                    </button>

                </div>
            </div>

        </div>
    </div>

</div>

@endsection