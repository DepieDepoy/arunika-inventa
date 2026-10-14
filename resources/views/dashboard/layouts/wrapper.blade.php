<!doctype html>
<html lang="id" dir="ltr">

<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1, shrink-to-fit=no"
    >

    <meta
        name="csrf-token"
        content="{{ csrf_token() }}"
    >

    <title>
        @yield('title', 'Vasetra')
    </title>

    <!-- Favicon -->
    <link
        rel="shortcut icon"
        href="{{ asset('assets/images/favicon.ico') }}"
    >

    <!-- Library / Plugin -->
    <link
        rel="stylesheet"
        href="{{ asset('assets/css/core/libs.min.css') }}"
    >

    <link
        rel="stylesheet"
        href="{{ asset('assets/vendor/aos/dist/aos.css') }}"
    >

    <!-- Hope UI -->
    <link
        rel="stylesheet"
        href="{{ asset('assets/css/hope-ui.min.css') }}?v=4.0.0"
    >

    <link
        rel="stylesheet"
        href="{{ asset('assets/css/custom.min.css') }}?v=4.0.0"
    >

    <!-- Theme -->
    <link
        rel="stylesheet"
        href="{{ asset('assets/css/dark.min.css') }}"
    >

    <link
        rel="stylesheet"
        href="{{ asset('assets/css/customizer.min.css') }}"
    >

    <link
        rel="stylesheet"
        href="{{ asset('assets/css/rtl.min.css') }}"
    >

    <!-- DataTables -->
    <link
        rel="stylesheet"
        href="{{ asset('assets/css/datatables.min.css') }}"
    >

    <link
        rel="stylesheet"
        href="{{ asset('assets/css/datatables-custom.css') }}"
    >

    <!-- Vasetra Custom -->
    <link
        rel="stylesheet"
        href="{{ asset('assets/css/arunika-admin.css') }}"
    >

    <!-- Icons -->
    <link
        rel="stylesheet"
        href="{{ asset('assets/css/vendor/boxicons.css') }}"
    >

    <link
        rel="stylesheet"
        href="{{ asset('assets/css/vendor/css/all.min.css') }}"
    >

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    @stack('styles')
</head>

<body>

    <!-- Loader -->
    <div id="loading">
        <div class="loader simple-loader">
            <div class="loader-body"></div>
        </div>
    </div>
    <!-- Loader END -->


    <!-- Sidebar -->
    @include('dashboard.layouts.menu')


    <!-- Main Content -->
    <main class="main-content">

        <!-- Navbar -->
        <div class="position-relative iq-banner">

            @include('dashboard.layouts.navbar')

            @include('dashboard.layouts.header')

        </div>
        <!-- Navbar END -->


        <!-- Page Content -->
        <div class="content-inner py-0">

            @yield('content')

        </div>
        <!-- Page Content END -->


        <!-- Footer -->
        @include('dashboard.layouts.footer')

    </main>


    <!-- SweetAlert -->
    @include('dashboard.components.sweetalert')

  @include('components.vasetra-ai')
    @stack('scripts')

</body>

</html>