<!doctype html>
<html lang="id" dir="ltr">

<head>

    <meta charset="utf-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title>Lupa Password - Vasetra</title>


    <!-- =====================================================
         FAVICON
    ====================================================== -->

    <link rel="shortcut icon"
          href="{{ asset('assets/images/favicon.ico') }}">


    <!-- =====================================================
         HOPE UI CSS
    ====================================================== -->

    <link rel="stylesheet"
          href="{{ asset('assets/css/core/libs.min.css') }}">

    <link rel="stylesheet"
          href="{{ asset('assets/css/hope-ui.min.css?v=4.0.0') }}">

    <link rel="stylesheet"
          href="{{ asset('assets/css/custom.min.css?v=4.0.0') }}">

    <link rel="stylesheet"
          href="{{ asset('assets/css/custom.css') }}">


    <!-- =====================================================
         BOOTSTRAP ICONS
    ====================================================== -->

    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">


    <!-- =====================================================
         SWEETALERT 2
    ====================================================== -->

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


    <!-- =====================================================
         CUSTOM STYLE
    ====================================================== -->

    <style>

        /*
        |--------------------------------------------------------------------------
        | SweetAlert
        |--------------------------------------------------------------------------
        */

        .swal2-container {
            z-index: 99999 !important;
        }


        /*
        |--------------------------------------------------------------------------
        | Reset Password Button
        |--------------------------------------------------------------------------
        */

        #resetPasswordBtn {
            min-width: 220px;
            transition: all 0.2s ease;
        }


        #resetPasswordBtn:disabled {
            cursor: not-allowed;
            opacity: 0.75;
        }


        /*
        |--------------------------------------------------------------------------
        | Loading Spinner
        |--------------------------------------------------------------------------
        */

        #resetPasswordBtnLoading {
            align-items: center;
            justify-content: center;
        }


        /*
        |--------------------------------------------------------------------------
        | Logo
        |--------------------------------------------------------------------------
        */

        .vasetra-logo {
            max-width: 280px;
            height: auto;
        }


        /*
        |--------------------------------------------------------------------------
        | Mobile
        |--------------------------------------------------------------------------
        */

        @media (max-width: 767.98px) {

            .vasetra-logo {
                max-width: 240px;
            }

            #resetPasswordBtn {
                width: 100%;
            }

        }

    </style>

</head>


<body>


    <!-- =====================================================
         LOADER
    ====================================================== -->

    <div id="loading">

        <div class="loader simple-loader">

            <div class="loader-body"></div>

        </div>

    </div>


    <!-- =====================================================
         WRAPPER
    ====================================================== -->

    <div class="wrapper">

        <section class="login-content">

            <div class="row m-0 align-items-center bg-white vh-100">


                <!-- =====================================================
                     LEFT SIDE
                ====================================================== -->

                <div class="col-md-6">

                    <div class="row justify-content-center">

                        <div class="col-md-10">

                            <div class="card card-transparent shadow-none d-flex justify-content-center mb-0 auth-card">

                                <div class="card-body">


                                    <!-- =====================================================
                                         VASETRA LOGO
                                    ====================================================== -->

                                    <a href="{{ route('login') }}"
                                       class="navbar-brand d-flex align-items-center mb-4">

                                        <img
                                            src="{{ asset('assets/images/auth/vasetra.png') }}"
                                            alt="VASETRA - Asset Lifecycle Platform"
                                            class="vasetra-logo"
                                        >

                                    </a>


                                    <!-- =====================================================
                                         TITLE
                                    ====================================================== -->

                                    <h2 class="mb-2 text-center">

                                        Lupa Password?

                                    </h2>


                                    <p class="text-center text-muted mb-4">

                                        Jangan khawatir. Masukkan email akun Anda,
                                        kami akan mengirimkan link untuk membuat
                                        password baru.

                                    </p>


                                    <!-- =====================================================
                                         FORM
                                    ====================================================== -->

                                    <form
                                        id="forgotPasswordForm"
                                        method="POST"
                                        action="{{ route('password.email') }}"
                                    >

                                        @csrf


                                        <!-- =====================================================
                                             EMAIL
                                        ====================================================== -->

                                        <div class="form-group mb-4">

                                            <label
                                                for="email"
                                                class="form-label"
                                            >
                                                Email
                                            </label>


                                            <input
                                                id="email"
                                                type="email"
                                                name="email"
                                                value="{{ old('email') }}"
                                                class="form-control @error('email') is-invalid @enderror"
                                                placeholder="Masukkan email Anda"
                                                autocomplete="email"
                                                autofocus
                                                required
                                            >


                                            @error('email')

                                                <div class="invalid-feedback">

                                                    {{ $message }}

                                                </div>

                                            @enderror

                                        </div>


                                        <!-- =====================================================
                                             BUTTON
                                        ====================================================== -->

                                        <div class="d-flex justify-content-center">

                                            <button
                                                type="submit"
                                                id="resetPasswordBtn"
                                                class="btn btn-primary"
                                            >

                                                <!-- NORMAL -->

                                                <span id="resetPasswordBtnText">

                                                    <i class="bi bi-envelope me-1"></i>

                                                    Kirim Link Reset Password

                                                </span>


                                                <!-- LOADING -->

                                                <span
                                                    id="resetPasswordBtnLoading"
                                                    style="display: none;"
                                                >

                                                    <span
                                                        class="spinner-border spinner-border-sm me-2"
                                                        role="status"
                                                        aria-hidden="true"
                                                    ></span>

                                                    Mengirim...

                                                </span>

                                            </button>

                                        </div>


                                        <!-- =====================================================
                                             BACK TO LOGIN
                                        ====================================================== -->

                                        <p class="mt-4 text-center mb-0">

                                            <a
                                                href="{{ route('login') }}"
                                                class="text-underline"
                                            >

                                                <i class="bi bi-arrow-left me-1"></i>

                                                Kembali ke Login

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
                            xmlns="http://www.w3.org/2000/svg"
                        >

                            <g opacity="0.05">

                                <rect
                                    x="-157.085"
                                    y="193.773"
                                    width="543"
                                    height="77.5714"
                                    rx="38.7857"
                                    transform="rotate(-45 -157.085 193.773)"
                                    fill="#3B8AFF"
                                />

                                <rect
                                    x="7.46875"
                                    y="358.327"
                                    width="543"
                                    height="77.5714"
                                    rx="38.7857"
                                    transform="rotate(-45 7.46875 358.327)"
                                    fill="#3B8AFF"
                                />

                                <rect
                                    x="61.9355"
                                    y="138.545"
                                    width="310.286"
                                    height="77.5714"
                                    rx="38.7857"
                                    transform="rotate(45 61.9355 138.545)"
                                    fill="#3B8AFF"
                                />

                                <rect
                                    x="62.3154"
                                    y="-190.173"
                                    width="543"
                                    height="77.5714"
                                    rx="38.7857"
                                    transform="rotate(45 62.3154 -190.173)"
                                    fill="#3B8AFF"
                                />

                            </g>

                        </svg>

                    </div>

                </div>


                <!-- =====================================================
                     RIGHT SIDE
                ====================================================== -->

                <div
                    class="col-md-6 d-md-block d-none bg-primary p-0 mt-n1 vh-100 overflow-hidden"
                >

                    <img
                        src="{{ asset('assets/images/auth/01.png') }}"
                        class="img-fluid gradient-main animated-scaleX"
                        alt="Vasetra"
                    >

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
         FORM LOADING
    ====================================================== -->

    <script>

        document.addEventListener('DOMContentLoaded', function () {

            const form = document.getElementById('forgotPasswordForm');

            const button = document.getElementById('resetPasswordBtn');

            const buttonText = document.getElementById('resetPasswordBtnText');

            const buttonLoading = document.getElementById('resetPasswordBtnLoading');


            if (!form || !button) {
                return;
            }


            form.addEventListener('submit', function (event) {

                /*
                |--------------------------------------------------------------------------
                | Prevent double submit
                |--------------------------------------------------------------------------
                */

                if (button.disabled) {

                    event.preventDefault();

                    return;

                }


                /*
                |--------------------------------------------------------------------------
                | Disable button
                |--------------------------------------------------------------------------
                */

                button.disabled = true;


                /*
                |--------------------------------------------------------------------------
                | Hide normal button
                |--------------------------------------------------------------------------
                */

                if (buttonText) {

                    buttonText.style.display = 'none';

                }


                /*
                |--------------------------------------------------------------------------
                | Show loading
                |--------------------------------------------------------------------------
                */

                if (buttonLoading) {

                    buttonLoading.style.display = 'inline-flex';

                }

            });

        });

    </script>


    <!-- =====================================================
         SWEETALERT DATA
    ====================================================== -->

    <div
        id="sweetAlertData"
        data-status="{{ session('status') }}"
        data-errors="{{ $errors->any() ? implode(' | ', $errors->all()) : '' }}"
        style="display: none;"
    ></div>


    <!-- =====================================================
         SWEETALERT
    ====================================================== -->

    <script>

        document.addEventListener('DOMContentLoaded', function () {

            const alertData = document.getElementById('sweetAlertData');

            if (!alertData) {
                return;
            }


            /*
            |--------------------------------------------------------------------------
            | Get data from HTML
            |--------------------------------------------------------------------------
            */

            const status = alertData.dataset.status || '';

            const errors = alertData.dataset.errors || '';


            /*
            |--------------------------------------------------------------------------
            | SUCCESS
            |--------------------------------------------------------------------------
            */

            if (status !== '') {

                Swal.fire({

                    icon: 'success',

                    title: 'Berhasil!',

                    text: status,

                    confirmButtonColor: '#3a57e8',

                    confirmButtonText: 'OK',

                    allowOutsideClick: false,

                    allowEscapeKey: false

                });

            }


            /*
            |--------------------------------------------------------------------------
            | ERROR
            |--------------------------------------------------------------------------
            */

            if (errors !== '') {

                Swal.fire({

                    icon: 'error',

                    title: 'Oops...',

                    html: errors.replace(/\|/g, '<br>'),

                    confirmButtonColor: '#3a57e8',

                    confirmButtonText: 'OK',

                    allowOutsideClick: false,

                    allowEscapeKey: false

                });

            }

        });

    </script>


</body>

</html>