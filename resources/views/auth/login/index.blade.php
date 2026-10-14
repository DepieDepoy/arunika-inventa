<!doctype html>

<html lang="en" dir="ltr">

<head>
    <meta charset="utf-8">

<meta name="viewport"
    content="width=device-width, initial-scale=1, shrink-to-fit=no">

<title>Asset Management System - By Arunika Solusi Inovasi</title>

<!-- Favicon -->
<link rel="shortcut icon"
    href="{{ asset('assets/images/favicon.ico') }}">

<!-- Library / Plugin Css Build -->
<link rel="stylesheet"
    href="{{ asset('assets/css/core/libs.min.css') }}">

<!-- Hope Ui -->
<link rel="stylesheet"
    href="{{ asset('assets/css/hope-ui.min.css?v=4.0.0') }}">

<!-- Custom -->
<link rel="stylesheet"
    href="{{ asset('assets/css/custom.min.css?v=4.0.0') }}">

<link rel="stylesheet"
    href="{{ asset('assets/css/custom.css') }}">

<!-- Bootstrap Icons -->
<link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<!-- Laravel Vite -->
<!-- @vite(['resources/css/app.css', 'resources/js/app.js']) -->

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    /*
    |--------------------------------------------------------------------------
    | LOGIN BUTTON
    |--------------------------------------------------------------------------
    */

    #loginBtn {
        min-width: 130px;
    }

    #loginBtnLoading {
        display: none;
        align-items: center;
        justify-content: center;
    }

    /*
    |--------------------------------------------------------------------------
    | PASSWORD BUTTON
    |--------------------------------------------------------------------------
    */

    #togglePassword {
        min-width: 48px;
    }

    /*
    |--------------------------------------------------------------------------
    | MOBILE
    |--------------------------------------------------------------------------
    */

    @media (max-width: 576px) {

        #loginBtn {
            width: 100%;
        }

    }
</style>


</head>

<body class=" "
    data-bs-spy="scroll"
    data-bs-target="#elements-section"
    data-bs-offset="0"
    tabindex="0">


<!-- loader Start -->
<div id="loading">
    <div class="loader simple-loader">
        <div class="loader-body">
        </div>
    </div>
</div>
<!-- loader END -->


<div class="wrapper">

    <section class="login-content">

        <div class="row m-0 align-items-center bg-white vh-100">

            <!-- =====================================================
                 LEFT SIDE - LOGIN
            ====================================================== -->

            <div class="col-md-6">

                <div class="row justify-content-center">

                    <div class="col-md-10">

                        <div class="card card-transparent shadow-none d-flex justify-content-center mb-0 auth-card">

                            <div class="card-body">

                                <!-- =====================================================
                                     LOGO
                                ====================================================== -->

                                <a href="{{ route('login') }}"
                                    class="navbar-brand d-flex align-items-center mb-3">

                                    <img
                                        src="{{ asset('assets/images/auth/vasetra.png') }}"
                                        alt="VASETRA - Asset Lifecycle Platform"
                                        style="max-width: 280px; height: auto;">

                                </a>


                                <!-- =====================================================
                                     TITLE
                                ====================================================== -->

                                <h2 class="mb-2 text-center">
                                    Sign In
                                </h2>

                                <p class="text-center">
                                    Login to stay connected.
                                </p>


                                <!-- =====================================================
                                     LOGIN FORM
                                ====================================================== -->

                                <form
                                    id="loginForm"
                                    method="POST"
                                    action="{{ route('login') }}">

                                    @csrf

                                    <div class="row">

                                        <!-- =====================================================
                                             EMAIL / PHONE
                                        ====================================================== -->

                                        <div class="col-lg-12">

                                            <div class="form-group">

                                                <label class="form-label">
                                                    Email / Nomor WhatsApp
                                                </label>

                                                <input
                                                    type="text"
                                                    name="login"
                                                    value="{{ old('login') }}"
                                                    class="form-control @error('login') is-invalid @enderror"
                                                    placeholder="Masukkan Email atau Nomor WhatsApp"
                                                    autofocus>

                                                @error('login')
                                                    <div class="invalid-feedback">
                                                        {{ $message }}
                                                    </div>
                                                @enderror

                                            </div>

                                        </div>


                                        <!-- =====================================================
                                             PASSWORD
                                        ====================================================== -->

                                        <div class="col-lg-12">

                                            <div class="form-group">

                                                <label class="form-label">
                                                    Password
                                                </label>

                                                <div class="input-group">

                                                    <input
                                                        type="password"
                                                        id="password"
                                                        name="password"
                                                        class="form-control @error('password') is-invalid @enderror">

                                                    <button
                                                        class="btn btn-outline-secondary"
                                                        type="button"
                                                        id="togglePassword"
                                                        aria-label="Show password">

                                                        <i class="bi bi-eye"></i>

                                                    </button>

                                                    @error('password')
                                                        <div class="invalid-feedback">
                                                            {{ $message }}
                                                        </div>
                                                    @enderror

                                                </div>

                                            </div>

                                        </div>


                                        <!-- =====================================================
                                             REMEMBER + FORGOT PASSWORD
                                        ====================================================== -->

                                        <div class="col-lg-12 d-flex justify-content-between">

                                            <div class="form-check mb-3">

                                                <input
                                                    type="checkbox"
                                                    class="form-check-input"
                                                    id="remember"
                                                    name="remember">

                                                <label
                                                    class="form-check-label"
                                                    for="remember">

                                                    Remember Me

                                                </label>

                                            </div>

                                            <a href="{{ route('password.request') }}">
                                                Forgot Password?
                                            </a>

                                        </div>

                                    </div>


                                    <!-- =====================================================
                                         LOGIN BUTTON
                                    ====================================================== -->

                                    <div class="d-flex justify-content-center">

                                        <button
                                            type="submit"
                                            class="btn btn-primary"
                                            id="loginBtn">

                                            <!-- Normal -->
                                            <span id="loginBtnText">
                                                Sign In
                                            </span>

                                            <!-- Loading -->
                                            <span id="loginBtnLoading">

                                                <span
                                                    class="spinner-border spinner-border-sm me-2"
                                                    role="status"
                                                    aria-hidden="true">
                                                </span>

                                                Signing in...

                                            </span>

                                        </button>

                                    </div>


                                    <!-- =====================================================
                                         REGISTER
                                    ====================================================== -->

                                    <p class="mt-3 text-center">

                                        Don’t have an account?

                                        <a
                                            href="{{ route('register') }}"
                                            class="text-underline">

                                            Click here to sign up.

                                        </a>

                                    </p>

                                </form>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- =====================================================
                     BACKGROUND SVG
                ====================================================== -->

                <div class="sign-bg">

                    <svg
                        width="280"
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


            <!-- =====================================================
                 RIGHT SIDE IMAGE
            ====================================================== -->

            <div class="col-md-6 d-md-block d-none bg-primary p-0 mt-n1 vh-100 overflow-hidden">

                <img
                    src="{{ asset('assets/images/auth/01.png') }}"
                    class="img-fluid gradient-main animated-scaleX"
                    alt="VASETRA">

            </div>

        </div>

    </section>

</div>


<!-- =====================================================
     LIBRARY BUNDLE SCRIPT
====================================================== -->

<script src="{{ asset('assets/js/core/libs.min.js') }}"></script>

<!-- External Library Bundle Script -->
<script src="{{ asset('assets/js/core/external.min.js') }}"></script>

<!-- Widgetchart Script -->
<script src="{{ asset('assets/js/charts/widgetcharts.js') }}"></script>

<!-- mapchart Script -->
<script src="{{ asset('assets/js/charts/vectore-chart.js') }}"></script>

<script src="{{ asset('assets/js/charts/dashboard.js') }}"></script>

<!-- fslightbox Script -->
<script src="{{ asset('assets/js/plugins/fslightbox.js') }}"></script>

<!-- Settings Script -->
<script src="{{ asset('assets/js/plugins/setting.js') }}"></script>

<!-- Slider-tab Script -->
<script src="{{ asset('assets/js/plugins/slider-tabs.js') }}"></script>

<!-- Form Wizard Script -->
<script src="{{ asset('assets/js/plugins/form-wizard.js') }}"></script>

<!-- App Script -->
<script src="{{ asset('assets/js/hope-ui.js') }}" defer></script>


<!-- =====================================================
     PASSWORD TOGGLE
====================================================== -->

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

                button.setAttribute(
                    'aria-label',
                    'Hide password'
                );

            } else {

                input.type = 'password';

                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');

                button.setAttribute(
                    'aria-label',
                    'Show password'
                );
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

    });
</script>


<!-- =====================================================
     LOGIN LOADING
====================================================== -->

<script>
    document.addEventListener('DOMContentLoaded', function () {

        const form =
            document.getElementById('loginForm');

        const button =
            document.getElementById('loginBtn');

        const text =
            document.getElementById('loginBtnText');

        const loading =
            document.getElementById('loginBtnLoading');


        if (!form || !button) {
            return;
        }


        form.addEventListener('submit', function () {

            /*
            |--------------------------------------------------------------------------
            | Prevent double submit
            |--------------------------------------------------------------------------
            */

            if (form.dataset.submitting === 'true') {
                return;
            }


            form.dataset.submitting = 'true';


            /*
            |--------------------------------------------------------------------------
            | Disable button
            |--------------------------------------------------------------------------
            */

            button.disabled = true;


            /*
            |--------------------------------------------------------------------------
            | Show loading
            |--------------------------------------------------------------------------
            */

            if (text) {
                text.style.display = 'none';
            }

            if (loading) {
                loading.style.display = 'inline-flex';
            }

        });

    });
</script>


<!-- =====================================================
     SWEETALERT SESSION
====================================================== -->

<div
    id="sweetAlertData"
    data-success="{{ session('success') }}"
    data-error="{{ session('error') }}"
    data-warning="{{ session('warning') }}"
    data-errors="{{ $errors->any() ? implode(' | ', $errors->all()) : '' }}"
    style="display:none;">
</div>


<script>
    document.addEventListener('DOMContentLoaded', function () {

        const alertData =
            document.getElementById('sweetAlertData');


        if (!alertData || typeof Swal === 'undefined') {
            return;
        }


        const success =
            alertData.dataset.success;

        const error =
            alertData.dataset.error;

        const warning =
            alertData.dataset.warning;

        const errors =
            alertData.dataset.errors;


        /*
        |--------------------------------------------------------------------------
        | SUCCESS
        |--------------------------------------------------------------------------
        */

        if (success) {

            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: success,
                confirmButtonColor: '#3a57e8'
            });

        }


        /*
        |--------------------------------------------------------------------------
        | WARNING
        |--------------------------------------------------------------------------
        */

        if (warning) {

            Swal.fire({
                icon: 'warning',
                title: 'Perhatian',
                text: warning,
                confirmButtonColor: '#f59e0b'
            });

        }


        /*
        |--------------------------------------------------------------------------
        | EMAIL NOT VERIFIED
        |--------------------------------------------------------------------------
        */

        if (error) {

            Swal.fire({
                icon: 'warning',
                title: 'Verifikasi Email',
                text: error,
                confirmButtonColor: '#f59e0b'
            });

        }


        /*
        |--------------------------------------------------------------------------
        | VALIDATION ERROR
        |--------------------------------------------------------------------------
        */

        if (errors) {

            Swal.fire({
                icon: 'error',
                title: 'Validasi Gagal',
                html: errors.replace(/\|/g, '<br>'),
                confirmButtonColor: '#d33'
            });

        }

    });
</script>


</body>

</html>
