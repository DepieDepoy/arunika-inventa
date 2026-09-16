<!doctype html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
      <title>Manajemen Aset - By Arunika</title>
      <!-- Favicon -->
      <link rel="shortcut icon" href="{{ asset('assets/images/favicon.ico') }}">
      <!-- Library / Plugin Css Build -->
      <link rel="stylesheet" href="{{ asset('assets/css/core/libs.min.css') }}">
      <!-- Aos Animation Css -->
      <link rel="stylesheet" href="{{ asset('assets/vendor/aos/dist/aos.css') }}">
      <!-- Hope Ui Design System Css -->
      <link rel="stylesheet" href="{{ asset('assets/css/hope-ui.min.css') }}?v=4.0.0">
      <!-- Custom Css -->
      <link rel="stylesheet" href="{{ asset('assets/css/custom.min.css') }}?v=4.0.0">
      <!-- Dark Css -->
      <link rel="stylesheet" href="{{ asset('assets/css/dark.min.css') }}">
      <!-- Customizer Css -->
      <link rel="stylesheet" href="{{ asset('assets/css/customizer.min.css') }}">
      <!-- RTL Css -->
      <link rel="stylesheet" href="{{ asset('assets/css/rtl.min.css') }}">
      <link rel="stylesheet" href="{{ asset('assets/css/datatables.min.css') }}">
      <link rel="stylesheet" href="{{ asset('assets/css/datatables-custom.css') }}">
      <link rel="stylesheet" href="{{ asset('assets/css/arunika-admin.css') }}">
      <link rel="stylesheet" href="{{ asset('assets/css/vendor/boxicons.css') }}">
      <link rel="stylesheet" href="{{ asset('assets/css/vendor/css/all.min.css') }}">
      <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <body class="  ">
    <!-- loader Start -->
    <div id="loading">
      <div class="loader simple-loader">
          <div class="loader-body">
          </div>
      </div>    
    </div>
    <!-- loader END -->
     @include('dashboard.layouts.menu')
    <main class="main-content">
      <div class="position-relative iq-banner">
        <!--Nav Start-->
         @include('dashboard.layouts.navbar')
         @include('dashboard.layouts.header')
        <!-- Nav Header Component Start -->
         @yield('content')
      <!-- Footer Section Start -->
       @include('dashboard.layouts.footer')
      <!-- Footer Section End -->    
    
  @include('dashboard.components.sweetalert')
  </body>
</html>