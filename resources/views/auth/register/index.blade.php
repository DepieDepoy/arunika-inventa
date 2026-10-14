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

<!-- Hope Ui Design System Css -->
<link rel="stylesheet"
    href="{{ asset('assets/css/hope-ui.min.css?v=4.0.0') }}">

<!-- Custom Css -->
<link rel="stylesheet"
    href="{{ asset('assets/css/custom.min.css?v=4.0.0') }}">

<link rel="stylesheet"
    href="{{ asset('assets/css/custom.css') }}">

<!-- Bootstrap Icons -->
<link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    /*
    |--------------------------------------------------------------------------
    | REGISTER BUTTON
    |--------------------------------------------------------------------------
    */

    #registerBtn {
        min-height: 45px;
    }

    #registerBtnLoading {
        display: none;
        align-items: center;
        justify-content: center;
    }

    /*
    |--------------------------------------------------------------------------
    | PASSWORD BUTTON
    |--------------------------------------------------------------------------
    */

    #togglePassword,
    #toggleConfirmPassword {
        min-width: 48px;
    }

    /*
    |--------------------------------------------------------------------------
    | MOBILE
    |--------------------------------------------------------------------------
    */

    @media (max-width: 576px) {

        #registerBtn {
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


<!-- =====================================================
     LOADER START
====================================================== -->

<div id="loading">

    <div class="loader simple-loader">

        <div class="loader-body">
        </div>

    </div>

</div>

<!-- LOADER END -->


<div class="wrapper">

    <section class="login-content">

        <div class="row m-0 bg-white min-vh-100">


            <!-- =====================================================
                 LEFT IMAGE
            ====================================================== -->

            <div class="col-lg-6 d-none d-lg-block bg-primary p-0 position-sticky top-0 min-vh-100 overflow-hidden">

                <img
                    src="{{ asset('assets/images/auth/05.png') }}"
                    class="img-fluid gradient-main animated-scaleX"
                    alt="VASETRA">

            </div>


            <!-- =====================================================
                 REGISTER FORM
            ====================================================== -->

            <div class="col-md-6">

                <div class="row justify-content-center">

                    <div class="col-md-10">

                        <div class="card card-transparent auth-card shadow-none d-flex justify-content-center mb-0">

                            <div class="card-body">


                                <!-- =====================================================
                                     LOGO
                                ====================================================== -->

                                <a
                                    href="{{ route('login') }}"
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
                                    Register
                                </h2>

                                <p class="text-center">
                                    Create your account.
                                </p>


                                <!-- =====================================================
                                     REGISTER FORM
                                ====================================================== -->

                                <form
                                    id="registerForm"
                                    method="POST"
                                    action="{{ route('register') }}">

                                    @csrf

                                    <div class="row">


                                        <!-- =====================================================
                                             FULL NAME
                                        ====================================================== -->

                                        <div class="col-lg-6">

                                            <div class="form-group">

                                                <label class="form-label">
                                                    Full Name
                                                </label>

                                                <input
                                                    type="text"
                                                    name="name"
                                                    class="form-control @error('name') is-invalid @enderror"
                                                    value="{{ old('name') }}"
                                                    placeholder="Enter your full name"
                                                    autofocus>

                                                @error('name')

                                                    <div class="invalid-feedback">
                                                        {{ $message }}
                                                    </div>

                                                @enderror

                                            </div>

                                        </div>


                                        <!-- =====================================================
                                             ID PERSON
                                        ====================================================== -->

                                        <div class="col-lg-6">

                                            <div class="form-group">

                                                <label class="form-label">
                                                    ID Person
                                                </label>

                                                <input
                                                    type="text"
                                                    name="nik"
                                                    class="form-control @error('nik') is-invalid @enderror"
                                                    value="{{ old('nik') }}"
                                                    placeholder="ID Person">

                                                @error('nik')

                                                    <div class="invalid-feedback">
                                                        {{ $message }}
                                                    </div>

                                                @enderror

                                            </div>

                                        </div>


                                        <!-- =====================================================
                                             COMPANY
                                        ====================================================== -->

                                        <div class="col-lg-12">

                                            <div class="form-group">

                                                <label class="form-label">
                                                    Company / Organization Name
                                                </label>

                                                <input
                                                    type="text"
                                                    name="company_name"
                                                    class="form-control @error('company_name') is-invalid @enderror"
                                                    value="{{ old('company_name') }}"
                                                    placeholder="Ex : PT Arunika Solusi Inovasi">

                                                @error('company_name')

                                                    <div class="invalid-feedback">
                                                        {{ $message }}
                                                    </div>

                                                @enderror

                                            </div>

                                        </div>


                                        <!-- =====================================================
                                             WHATSAPP
                                        ====================================================== -->

                                        <div class="col-lg-12">

                                            <div class="form-group">

                                                <label class="form-label">
                                                    WhatsApp Number
                                                </label>

                                                <input
                                                    type="number"
                                                    name="phone"
                                                    class="form-control @error('phone') is-invalid @enderror"
                                                    value="{{ old('phone') }}"
                                                    placeholder="0812xxxxx">

                                                @error('phone')

                                                    <div class="invalid-feedback">
                                                        {{ $message }}
                                                    </div>

                                                @enderror

                                            </div>

                                        </div>


                                        <!-- =====================================================
                                             EMAIL
                                        ====================================================== -->

                                        <div class="col-lg-12">

                                            <div class="form-group">

                                                <label class="form-label">
                                                    Email
                                                </label>

                                                <input
                                                    type="email"
                                                    name="email"
                                                    class="form-control @error('email') is-invalid @enderror"
                                                    value="{{ old('email') }}"
                                                    placeholder="email@gmail.com">

                                                @error('email')

                                                    <div class="invalid-feedback">
                                                        {{ $message }}
                                                    </div>

                                                @enderror

                                            </div>

                                        </div>


                                        <!-- =====================================================
                                             PASSWORD
                                        ====================================================== -->

                                        <div class="col-lg-6">

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
                                             CONFIRM PASSWORD
                                        ====================================================== -->

                                        <div class="col-lg-6">

                                            <div class="form-group">

                                                <label class="form-label">
                                                    Confirm Password
                                                </label>

                                                <div class="input-group">

                                                    <input
                                                        type="password"
                                                        id="password_confirmation"
                                                        name="password_confirmation"
                                                        class="form-control
                                                            @error('password_confirmation') is-invalid @enderror
                                                            @if($errors->has('password') && str_contains($errors->first('password'), 'confirmation')) is-invalid @endif">

                                                    <button
                                                        class="btn btn-outline-secondary"
                                                        type="button"
                                                        id="toggleConfirmPassword"
                                                        aria-label="Show password">

                                                        <i class="bi bi-eye"></i>

                                                    </button>

                                                </div>


                                                @error('password_confirmation')

                                                    <div class="invalid-feedback d-block">
                                                        {{ $message }}
                                                    </div>

                                                @enderror


                                                @if($errors->has('password') && str_contains($errors->first('password'), 'confirmation'))

                                                    <div class="invalid-feedback d-block">
                                                        {{ $errors->first('password') }}
                                                    </div>

                                                @endif

                                            </div>

                                        </div>


                                        <!-- =====================================================
                                             TERMS
                                        ====================================================== -->

                                        <div class="col-lg-12 d-flex justify-content-center">

                                            <div class="form-check mb-3">

                                                <input
                                                    type="checkbox"
                                                    class="form-check-input"
                                                    id="agree"
                                                    required>

                                                <label
                                                    class="form-check-label"
                                                    for="agree">

                                                    I agree to the Terms & Conditions.

                                                </label>

                                            </div>

                                        </div>


                                        <!-- =====================================================
                                             REGISTER BUTTON
                                        ====================================================== -->

                                        <div class="col-lg-12">

                                            <button
                                                type="submit"
                                                class="btn btn-primary w-100"
                                                id="registerBtn">

                                                <!-- Normal -->
                                                <span id="registerBtnText">
                                                    Register
                                                </span>


                                                <!-- Loading -->
                                                <span id="registerBtnLoading">

                                                    <span
                                                        class="spinner-border spinner-border-sm me-2"
                                                        role="status"
                                                        aria-hidden="true">
                                                    </span>

                                                    Processing...

                                                </span>

                                            </button>

                                        </div>


                                        <!-- =====================================================
                                             LOGIN LINK
                                        ====================================================== -->

                                        <p class="mt-3 text-center">

                                            Already have an account?

                                            <a
                                                href="{{ route('login') }}"
                                                class="text-underline">

                                                Sign In

                                            </a>

                                        </p>

                                    </div>

                                </form>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- =====================================================
                     BACKGROUND
                ====================================================== -->

                <div class="sign-bg sign-bg-right">

                    <svg
                        width="280"
                        height="230"
                        viewBox="0 0 421 359"
                        fill="none"
                        xmlns="http://www.w3.org/2000/svg">

                        <g opacity="0.05">

                            <rect
                                x="-15.0845"
                                y="154.773"
                                width="543"
                                height="77.5714"
                                rx="38.7857"
                                transform="rotate(-45 -15.0845 154.773)"
                                fill="#3A57E8"/>

                            <rect
                                x="149.47"
                                y="319.328"
                                width="543"
                                height="77.5714"
                                rx="38.7857"
                                transform="rotate(-45 149.47 319.328)"
                                fill="#3A57E8"/>

                            <rect
                                x="203.936"
                                y="99.543"
                                width="310.286"
                                height="77.5714"
                                rx="38.7857"
                                transform="rotate(45 203.936 99.543)"
                                fill="#3A57E8"/>

                            <rect
                                x="204.316"
                                y="-229.172"
                                width="543"
                                height="77.5714"
                                rx="38.7857"
                                transform="rotate(45 204.316 -229.172)"
                                fill="#3A57E8"/>

                        </g>

                    </svg>

                </div>

            </div>

        </div>

    </section>

</div>


<!-- =====================================================
     JAVASCRIPT LIBRARIES
====================================================== -->

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


<!-- =====================================================
     PASSWORD TOGGLE
====================================================== -->

<script>

    document.addEventListener('DOMContentLoaded', function () {

        function togglePassword(inputId, buttonId) {

            const input =
                document.getElementById(inputId);

            const button =
                document.getElementById(buttonId);

            if (!input || !button) {
                return;
            }

            const icon =
                button.querySelector('i');

            if (!icon) {
                return;
            }


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

            btnPassword.addEventListener(
                'click',
                function () {

                    togglePassword(
                        'password',
                        'togglePassword'
                    );

                }
            );

        }


        const btnConfirmPassword =
            document.getElementById('toggleConfirmPassword');


        if (btnConfirmPassword) {

            btnConfirmPassword.addEventListener(
                'click',
                function () {

                    togglePassword(
                        'password_confirmation',
                        'toggleConfirmPassword'
                    );

                }
            );

        }

    });

</script>


<!-- =====================================================
     REGISTER LOADING
====================================================== -->

<script>

    document.addEventListener('DOMContentLoaded', function () {

        const form =
            document.getElementById('registerForm');

        const button =
            document.getElementById('registerBtn');

        const text =
            document.getElementById('registerBtnText');

        const loading =
            document.getElementById('registerBtnLoading');


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


            /*
            |--------------------------------------------------------------------------
            | Mark as submitting
            |--------------------------------------------------------------------------
            */

            form.dataset.submitting = 'true';


            /*
            |--------------------------------------------------------------------------
            | Disable button
            |--------------------------------------------------------------------------
            */

            button.disabled = true;


            /*
            |--------------------------------------------------------------------------
            | Change button text
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
     SWEETALERT - SUCCESS
====================================================== -->

@if(session('success'))

    <script>

        document.addEventListener('DOMContentLoaded', function () {

            Swal.fire({

                icon: 'success',

                title: 'Success',

                text: "{{ session('success') }}",

                confirmButtonColor: '#3a57e8'

            });

        });

    </script>

@endif


<!-- =====================================================
     SWEETALERT - ERROR
====================================================== -->

@if(session('error'))

    <script>

        document.addEventListener('DOMContentLoaded', function () {

            Swal.fire({

                icon: 'error',

                title: 'Oops...',

                text: "{{ session('error') }}",

                confirmButtonColor: '#d33'

            });

        });

    </script>

@endif


<!-- =====================================================
     SWEETALERT - VALIDATION
====================================================== -->

@if($errors->any())

    <script>

        document.addEventListener('DOMContentLoaded', function () {

            Swal.fire({

                icon: 'error',

                title: 'Validation Failed',

                html: `{!! implode('<br>', $errors->all()) !!}`,

                confirmButtonColor: '#d33'

            });

        });

    </script>

@endif


</body>

</html>
