<!doctype html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
      <title>Asset Management System - By Arunika Solusi Inovasi</title>
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="{{ asset('assets/images/favicon.ico') }}">
    <!-- Library / Plugin Css Build -->
    <link rel="stylesheet" href="{{ asset('assets/css/core/libs.min.css') }}">
    <!-- Hope Ui Design System Css -->
    <link rel="stylesheet" href="{{ asset('assets/css/hope-ui.min.css?v=4.0.0') }}">
    <!-- Custom Css -->
    <link rel="stylesheet" href="{{ asset('assets/css/custom.min.css?v=4.0.0') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  </head>
  <body class=" " data-bs-spy="scroll" data-bs-target="#elements-section" data-bs-offset="0" tabindex="0">
    <!-- loader Start -->
    <div id="loading">
      <div class="loader simple-loader">
          <div class="loader-body">
          </div>
      </div>    </div>
    <!-- loader END -->
    
      <div class="wrapper">
      <section class="login-content">
         <div class="row m-0 bg-white min-vh-100">            
               <div class="col-lg-6 d-none d-lg-block bg-primary p-0 position-sticky top-0 min-vh-100 overflow-hidden">
               <img src="{{ asset('assets/images/auth/05.png') }}" class="img-fluid gradient-main animated-scaleX" alt="images">
            </div>
            <div class="col-md-6">               
               <div class="row justify-content-center">
                  <div class="col-md-10">
                     <div class="card card-transparent auth-card shadow-none d-flex justify-content-center mb-0">
                        <div class="card-body">
                           <a href="{{ route('login') }}"
                            class="navbar-brand d-flex align-items-center mb-3">

                                <img
                                    src="{{ asset('assets/images/auth/vasetra.png') }}"
                                    alt="VASETRA - Asset Lifecycle Platform"
                                    style="max-width: 280px; height: auto;"
                                >

                            </a>
                           <h2 class="mb-2 text-center">Register</h2>
                           <p class="text-center">Create your account.</p>
                    <form method="POST" action="{{ route('register') }}">
                        @csrf
                        <div class="row">
                            {{-- Nama --}}
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label class="form-label">Full Name</label>
                                    <input
                                        type="text"
                                        name="name"
                                        class="form-control @error('name') is-invalid @enderror"
                                        value="{{ old('name') }}"
                                        placeholder="Enter your full name" autofocus>
                                    @error('name')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label class="form-label">ID Person</label>
                                    <input
                                        type="text"
                                        name="nik"
                                        class="form-control @error('nik') is-invalid @enderror"
                                        value="{{ old('nik') }}"
                                        placeholder="ID Person" >
                                    @error('nik')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>

                            {{-- Perusahaan --}}
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

                            {{-- Whatsapp --}}
                            <div class="col-lg-12">
                                <div class="form-group">
                                    <label class="form-label">No WhatsApp</label>
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

                            {{-- Email --}}
                            <div class="col-lg-12">
                                <div class="form-group">
                                    <label class="form-label">Email</label>
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

                            {{-- Password --}}
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label class="form-label">Password</label>
                                    <div class="input-group">
                                        <input
                                            type="password"
                                            id="password"
                                            name="password"
                                            class="form-control
                                                @error('password') is-invalid @enderror">
                                        
                                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
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

                            {{-- Konfirmasi --}}
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label class="form-label">Confirm Password</label>
                                    <div class="input-group">
                                        <input
                                            type="password"
                                            id="password_confirmation"
                                            name="password_confirmation"
                                            class="form-control
                                                @error('password_confirmation') is-invalid @enderror
                                                @if($errors->has('password') && str_contains($errors->first('password'), 'confirmation')) is-invalid @endif">

                                        <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                                <i class="bi bi-eye"></i>
                                        </button>
                                    </div>

                                    @error('password_confirmation')
                                        <div class="invalid-feedback">
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

                            <div class="col-lg-12 d-flex justify-content-center">
                                <div class="form-check mb-3">
                                    <input
                                        type="checkbox"
                                        class="form-check-input"
                                        id="agree"
                                        required>

                                    <label class="form-check-label" for="agree">
                                        I agree to the Terms & Conditions.
                                    </label>
                                </div>
                            </div>

                            <div class="col-lg-12">
                                <button class="btn btn-primary w-100">
                                    DAFTAR
                                </button>
                            </div>
                            <p class="mt-3 text-center">
                                 Already have an Account <a href="{{ route('login') }}" class="text-underline">Sign In</a>
                              </p>
                        </div>
                    </form>
                        </div>
                     </div>    
                  </div>
               </div>           
               <div class="sign-bg sign-bg-right">
                  <svg width="280" height="230" viewBox="0 0 421 359" fill="none" xmlns="http://www.w3.org/2000/svg">
                     <g opacity="0.05">
                        <rect x="-15.0845" y="154.773" width="543" height="77.5714" rx="38.7857" transform="rotate(-45 -15.0845 154.773)" fill="#3A57E8"/>
                        <rect x="149.47" y="319.328" width="543" height="77.5714" rx="38.7857" transform="rotate(-45 149.47 319.328)" fill="#3A57E8"/>
                        <rect x="203.936" y="99.543" width="310.286" height="77.5714" rx="38.7857" transform="rotate(45 203.936 99.543)" fill="#3A57E8"/>
                        <rect x="204.316" y="-229.172" width="543" height="77.5714" rx="38.7857" transform="rotate(45 204.316 -229.172)" fill="#3A57E8"/>
                     </g>
                  </svg>
               </div>
            </div>   
         </div>
      </section>
      </div>
    
<!-- Library Bundle Script -->
<script src="{{ asset('assets/js/core/libs.min.js') }}"></script>

<!-- External Library Bundle Script -->
<script src="{{ asset('assets/js/core/external.min.js') }}"></script>

<!-- Widgetchart Script -->
<script src="{{ asset('assets/js/charts/widgetcharts.js') }}"></script>

<!-- mapchart Script -->
<script src="{{ asset('assets/js/charts/vectore-chart.js') }}"></script>
<script src="{{ asset('assets/js/charts/dashboard.js') }}" ></script>

<!-- fslightbox Script -->
<script src="{{ asset('assets/js/plugins/fslightbox.js') }}"></script>

<!-- Settings Script -->
<script src="{{ asset('assets/js/plugins/setting.js') }}"></script>

<!-- Slider-tab Script -->
<script src="{{ asset('assets/js/plugins/slider-tabs.js') }}"></script>

<!-- Form Wizard Script -->
<script src="{{ asset('assets/js/plugins/form-wizard.js') }}"></script>

<!-- AOS Animation Plugin-->

<!-- App Script -->
<script src="{{ asset('assets/js/hope-ui.js') }}" defer></script>
<script>
    function togglePassword(inputId, buttonId) {

    const input = document.getElementById(inputId);
    const icon = document.querySelector(`#${buttonId} i`);

    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }

}

document.getElementById('togglePassword')
    .addEventListener('click', function () {
        togglePassword('password', 'togglePassword');
    });

document.getElementById('toggleConfirmPassword')
    .addEventListener('click', function () {
        togglePassword('password_confirmation', 'toggleConfirmPassword');
    });
</script>
@if(session('success'))
<script>
document.addEventListener('DOMContentLoaded', function () {

    Swal.fire({
        icon: 'success',
        title: 'Berhasil',
        text: "{{ session('success') }}",
        confirmButtonColor: '#3a57e8'
    });

});
</script>
@endif

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

@if ($errors->any())
<script>
document.addEventListener('DOMContentLoaded', function () {

    Swal.fire({
        icon: 'error',
        title: 'Validasi Gagal',
        html: `{!! implode('<br>', $errors->all()) !!}`,
        confirmButtonColor: '#d33'
    });

});
</script>
@endif
  </body>
</html>