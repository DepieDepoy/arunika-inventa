<!doctype html>
<html lang="id" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title>Reset Password - Vasetra</title>

    <!-- Favicon -->
    <link rel="shortcut icon" href="{{ asset('assets/images/favicon.ico') }}">

    <!-- Library / Plugin Css Build -->
    <link rel="stylesheet" href="{{ asset('assets/css/core/libs.min.css') }}">

    <!-- Hope UI -->
    <link rel="stylesheet" href="{{ asset('assets/css/hope-ui.min.css?v=4.0.0') }}">

    <!-- Custom -->
    <link rel="stylesheet" href="{{ asset('assets/css/custom.min.css?v=4.0.0') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- SweetAlert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>

    <!-- Loader -->
    <div id="loading">
        <div class="loader simple-loader">
            <div class="loader-body"></div>
        </div>
    </div>

    <div class="wrapper">

        <section class="login-content">

            <div class="row m-0 align-items-center bg-white vh-100">

                <!-- LEFT -->
                <div class="col-md-6">

                    <div class="row justify-content-center">

                        <div class="col-md-10">

                            <div class="card card-transparent shadow-none d-flex justify-content-center mb-0 auth-card">

                                <div class="card-body">

                                    <!-- Logo -->
                                    <a href="{{ route('login') }}"
                                       class="navbar-brand d-flex align-items-center mb-4">

                                        <img
                                            src="{{ asset('assets/images/auth/vasetra.png') }}"
                                            alt="VASETRA - Asset Lifecycle Platform"
                                            style="max-width: 280px; height: auto;"
                                        >

                                    </a>

                                    <!-- Title -->
                                    <h2 class="mb-2 text-center">
                                        Reset Password
                                    </h2>

                                    <p class="text-center text-muted mb-4">
                                        Silakan masukkan password baru untuk akun Anda.
                                    </p>

                                    <form method="POST"
                                          action="{{ route('password.store') }}">

                                        @csrf

                                        <!-- Token -->
                                        <input
                                            type="hidden"
                                            name="token"
                                            value="{{ $request->route('token') }}">

                                        <!-- Email -->
                                        <div class="form-group mb-3">

                                            <label class="form-label">
                                                Email
                                            </label>

                                            <input
                                                type="email"
                                                name="email"
                                                value="{{ old('email', $request->email) }}"
                                                class="form-control @error('email') is-invalid @enderror"
                                                placeholder="Masukkan email Anda"
                                                autocomplete="username"
                                                required
                                                autofocus
                                            >

                                            @error('email')
                                                <div class="invalid-feedback">
                                                    {{ $message }}
                                                </div>
                                            @enderror

                                        </div>

                                        <!-- Password -->
                                        <div class="form-group mb-3">

                                            <label class="form-label">
                                                Password Baru
                                            </label>

                                            <div class="input-group">

                                                <input
                                                    type="password"
                                                    id="password"
                                                    name="password"
                                                    class="form-control @error('password') is-invalid @enderror"
                                                    placeholder="Masukkan password baru"
                                                    autocomplete="new-password"
                                                    required
                                                >

                                                <button
                                                    class="btn btn-outline-secondary"
                                                    type="button"
                                                    id="togglePassword">

                                                    <i class="bi bi-eye"></i>

                                                </button>

                                            </div>

                                            @error('password')
                                                <div class="text-danger small mt-1">
                                                    {{ $message }}
                                                </div>
                                            @enderror

                                        </div>

                                        <!-- Confirm Password -->
                                        <div class="form-group mb-4">

                                            <label class="form-label">
                                                Konfirmasi Password Baru
                                            </label>

                                            <div class="input-group">

                                                <input
                                                    type="password"
                                                    id="password_confirmation"
                                                    name="password_confirmation"
                                                    class="form-control"
                                                    placeholder="Ulangi password baru"
                                                    autocomplete="new-password"
                                                    required
                                                >

                                                <button
                                                    class="btn btn-outline-secondary"
                                                    type="button"
                                                    id="toggleConfirmPassword">

                                                    <i class="bi bi-eye"></i>

                                                </button>

                                            </div>

                                        </div>

                                        <!-- Button -->
                                        <div class="d-flex justify-content-center">

                                            <button
                                                type="submit"
                                                class="btn btn-primary">

                                                <i class="bi bi-shield-check me-1"></i>
                                                Reset Password

                                            </button>

                                        </div>

                                        <!-- Back Login -->
                                        <p class="mt-4 text-center">

                                            <a href="{{ route('login') }}"
                                               class="text-underline">

                                                <i class="bi bi-arrow-left me-1"></i>
                                                Kembali ke Login

                                            </a>

                                        </p>

                                    </form>

                                </div>

                            </div>

                        </div>

                    </div>

                    <!-- Background SVG -->
                    <div class="sign-bg">

                        <svg width="280"
                             height="230"
                             viewBox="0 0 431 398"
                             fill="none"
                             xmlns="http://www.w3.org/2000/svg">

                            <g opacity="0.05">

                                <rect
                                    x="-157.085"
                                    y="193.773"
                                    width="543"
                                    height="77.5714"
                                    rx="38.7857"
                                    transform="rotate(-45 -157.085 193.773)"
                                    fill="#3B8AFF"/>

                                <rect
                                    x="7.46875"
                                    y="358.327"
                                    width="543"
                                    height="77.5714"
                                    rx="38.7857"
                                    transform="rotate(-45 7.46875 358.327)"
                                    fill="#3B8AFF"/>

                                <rect
                                    x="61.9355"
                                    y="138.545"
                                    width="310.286"
                                    height="77.5714"
                                    rx="38.7857"
                                    transform="rotate(45 61.9355 138.545)"
                                    fill="#3B8AFF"/>

                                <rect
                                    x="62.3154"
                                    y="-190.173"
                                    width="543"
                                    height="77.5714"
                                    rx="38.7857"
                                    transform="rotate(45 62.3154 -190.173)"
                                    fill="#3B8AFF"/>

                            </g>

                        </svg>

                    </div>

                </div>

                <!-- RIGHT -->
                <div class="col-md-6 d-md-block d-none bg-primary p-0 mt-n1 vh-100 overflow-hidden">

                    <img
                        src="{{ asset('assets/images/auth/01.png') }}"
                        class="img-fluid gradient-main animated-scaleX"
                        alt="Vasetra">

                </div>

            </div>

        </section>

    </div>

    <!-- Scripts -->
    <script src="{{ asset('assets/js/core/libs.min.js') }}"></script>
    <script src="{{ asset('assets/js/core/external.min.js') }}"></script>
    <script src="{{ asset('assets/js/charts/widgetcharts.js') }}"></script>
    <script src="{{ asset('assets/js/charts/vectore-chart.js') }}"></script>
    <script src="{{ asset('assets/js/charts/dashboard.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/fslightbox.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/setting.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/slider-tabs.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/form-wizard.js') }}"></script>
    <script src="{{ asset('assets/js/hope-ui.js') }}" defer></script>

    <!-- Password Toggle -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {

            function togglePassword(inputId, buttonId) {

                const input = document.getElementById(inputId);
                const button = document.getElementById(buttonId);

                if (!input || !button) {
                    return;
                }

                const icon = button.querySelector('i');

                if (input.type === 'password') {

                    input.type = 'text';

                    icon.classList.remove('bi-eye');
                    icon.classList.add('bi-eye-slash');

                } else {

                    input.type = 'password';

                    icon.classList.remove('bi-eye-slash');
                    icon.classList.add('bi-eye');

                }
            }

            const btnPassword =
                document.getElementById('togglePassword');

            if (btnPassword) {

                btnPassword.addEventListener('click', function () {

                    togglePassword(
                        'password',
                        'togglePassword'
                    );

                });

            }

            const btnConfirm =
                document.getElementById('toggleConfirmPassword');

            if (btnConfirm) {

                btnConfirm.addEventListener('click', function () {

                    togglePassword(
                        'password_confirmation',
                        'toggleConfirmPassword'
                    );

                });

            }

        });
    </script>

    <!-- SweetAlert Error -->
    @if ($errors->any())
        <script>
            document.addEventListener('DOMContentLoaded', function () {

                Swal.fire({
                    icon: 'error',
                    title: 'Validasi Gagal',
                    html: `{!! implode('<br>', $errors->all()) !!}`,
                    confirmButtonColor: '#3a57e8'
                });

            });
        </script>
    @endif

</body>
</html>
