@php $me = auth('backoffice')->user(); @endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Backoffice') · Vasetra Backoffice</title>
    <link rel="icon" href="{{ asset('assets/images/favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/core/libs.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/fonts/plus-jakarta-sans/plus-jakarta-sans.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/vasetra/hope-ui.min.css') }}?v=1.0.0">
    <link rel="stylesheet" href="{{ asset('assets/vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
    <style>
        :root { --bo-navy:#0b2447; --bo-grad:linear-gradient(125deg,#0b2447 0%,#163f86 38%,#1f5fbf 64%,#14b8a6 118%); }
        body { font-family:"Plus Jakarta Sans",system-ui,-apple-system,"Segoe UI",Roboto,sans-serif; background:#f4f7fb; color:#4b5a6e; }
        .bo-side { position:fixed; inset:0 auto 0 0; width:250px; background:var(--bo-navy); color:rgba(255,255,255,.75); display:flex; flex-direction:column; z-index:30; transition:transform .2s; }
        .bo-brand { padding:22px 22px 8px; }
        .bo-brand img { height:30px; filter:brightness(0) invert(1); }
        .bo-brand small { display:block; margin-top:8px; font-size:.68rem; letter-spacing:.14em; font-weight:800; color:#22d3ee; }
        .bo-nav { padding:14px 12px; flex:1; min-height:0; overflow-y:auto; scrollbar-width:thin; scrollbar-color:rgba(255,255,255,.2) transparent; }
        .bo-nav a { display:flex; align-items:center; gap:12px; padding:10px 14px; border-radius:11px; color:inherit; font-weight:600; font-size:.92rem; margin-bottom:3px; }
        .bo-nav a:hover { background:rgba(255,255,255,.07); color:#fff; }
        .bo-nav a.active { background:rgba(255,255,255,.14); color:#fff; }
        .bo-nav .grp { font-size:.66rem; letter-spacing:.12em; text-transform:uppercase; opacity:.5; margin:16px 14px 6px; font-weight:800; }
        .bo-me { flex-shrink:0; padding:16px 18px; border-top:1px solid rgba(255,255,255,.1); font-size:.84rem; }
        .bo-me b { color:#fff; display:block; }
        .bo-main { margin-left:250px; min-height:100vh; }
        .bo-top { display:flex; justify-content:space-between; align-items:center; padding:14px 28px; background:#fff; border-bottom:1px solid rgba(15,27,45,.08); position:sticky; top:0; z-index:20; }
        .bo-top .env { font-size:.72rem; font-weight:800; padding:4px 10px; border-radius:999px; background:#fef3c7; color:#92400e; }
        .bo-toggle { display:none; border:0; background:none; font-size:1.5rem; color:var(--bo-navy); }
        .container-p-y { padding-top:1.5rem !important; padding-bottom:1.5rem !important; }
        @media (max-width: 991.98px) {
            .bo-side { transform:translateX(-100%); }
            body.bo-open .bo-side { transform:none; }
            .bo-main { margin-left:0; }
            .bo-toggle { display:block; }
        }
    </style>
</head>
<body>

<aside class="bo-side">
    <div class="bo-brand">
        <img src="{{ asset('assets/images/brand/vasetra-logo.png') }}" alt="Vasetra">
        <small>BACKOFFICE</small>
    </div>
    <nav class="bo-nav">
        @if($me?->isSuperAdmin())
            <div class="grp">Bisnis</div>
            <a href="{{ route('backoffice.dashboard') }}" class="{{ request()->routeIs('backoffice.dashboard') ? 'active' : '' }}"><i class="bi bi-speedometer2"></i> Dashboard</a>
            <a href="{{ route('backoffice.customers.index') }}" class="{{ request()->routeIs('backoffice.customers.*') ? 'active' : '' }}"><i class="bi bi-buildings"></i> Pelanggan</a>
            <a href="{{ route('backoffice.transactions.index') }}" class="{{ request()->routeIs('backoffice.transactions.*') ? 'active' : '' }}"><i class="bi bi-receipt"></i> Transaksi</a>
            <a href="{{ route('backoffice.analytics.index') }}" class="{{ request()->routeIs('backoffice.analytics.*') ? 'active' : '' }}"><i class="bi bi-graph-up-arrow"></i> Analitik website</a>
            @php $pendingDeletion = \App\Models\AccountDeletionRequest::where('status', 'pending')->count(); @endphp
            <a href="{{ route('backoffice.account-deletions.index') }}" class="{{ request()->routeIs('backoffice.account-deletions.*') ? 'active' : '' }}">
                <i class="bi bi-person-x"></i> Hapus akun
                @if($pendingDeletion)<span class="ms-auto badge rounded-pill" style="background:#dc2626">{{ $pendingDeletion }}</span>@endif
            </a>
        @endif
        <div class="grp">Website</div>
        <a href="{{ route('backoffice.cms.index') }}" class="{{ request()->routeIs('backoffice.cms.*') ? 'active' : '' }}"><i class="bi bi-window-sidebar"></i> Konten Landing</a>
        @if($me?->isSuperAdmin())
            <a href="{{ route('backoffice.plans.index') }}" class="{{ request()->routeIs('backoffice.plans.*') ? 'active' : '' }}"><i class="bi bi-tags"></i> Paket &amp; Harga</a>
        @endif
        <a href="{{ url('/') }}" target="_blank"><i class="bi bi-box-arrow-up-right"></i> Lihat landing page</a>
        @if($me?->isSuperAdmin())
            <div class="grp">Tim Vasetra</div>
            <a href="{{ route('backoffice.staff.index') }}" class="{{ request()->routeIs('backoffice.staff.*') ? 'active' : '' }}"><i class="bi bi-people"></i> Pegawai</a>
            <div class="grp">Pengaturan</div>
            <a href="{{ route('backoffice.settings.mobile') }}" class="{{ request()->routeIs('backoffice.settings.mobile*') ? 'active' : '' }}"><i class="bi bi-phone"></i> Aplikasi mobile</a>
            <a href="{{ route('backoffice.settings.tera') }}" class="{{ request()->routeIs('backoffice.settings.tera*') ? 'active' : '' }}"><i class="bi bi-stars"></i> Tera AI</a>
            <a href="{{ route('backoffice.settings.security') }}" class="{{ request()->routeIs('backoffice.settings.security*') ? 'active' : '' }}"><i class="bi bi-shield-lock"></i> Keamanan</a>
            <a href="{{ route('backoffice.activity.index') }}" class="{{ request()->routeIs('backoffice.activity.*') ? 'active' : '' }}"><i class="bi bi-clock-history"></i> Log aktivitas</a>
        @endif
        <div class="grp">Akun</div>
        <a href="{{ route('backoffice.password.edit') }}" class="{{ request()->routeIs('backoffice.password.*') ? 'active' : '' }}"><i class="bi bi-key"></i> Ganti password</a>
        <a href="{{ route('backoffice.two-factor.show') }}" class="{{ request()->routeIs('backoffice.two-factor.*') ? 'active' : '' }}">
            <i class="bi bi-phone-vibrate"></i> Verifikasi 2 langkah
            @if($me && \App\Services\Backoffice\TwoFactor::inUse() && !\App\Services\Backoffice\TwoFactor::enabled($me))<span class="ms-auto badge rounded-pill" style="background:#f59e0b">!</span>@endif
        </a>
    </nav>
    <div class="bo-me">
        <b>{{ $me?->name }}</b>
        <span class="small d-block">{{ $me?->email }}</span>
        <span class="small" style="color:#22d3ee">{{ $me?->roleLabel() }}</span>
        <form method="POST" action="{{ route('backoffice.logout') }}" class="mt-2">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-light w-100"><i class="bi bi-box-arrow-right me-1"></i> Keluar</button>
        </form>
    </div>
</aside>

<div class="bo-main">
    <div class="bo-top">
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="bo-toggle" onclick="document.body.classList.toggle('bo-open')" aria-label="Menu"><i class="bi bi-list"></i></button>
            <strong class="text-dark">@yield('title', 'Backoffice')</strong>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="env d-none d-sm-inline"><i class="bi bi-shield-lock me-1"></i>Khusus pegawai Vasetra</span>
            <form method="POST" action="{{ route('backoffice.logout') }}" class="m-0">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-secondary" title="Keluar dari backoffice"><i class="bi bi-box-arrow-right"></i><span class="d-none d-md-inline ms-1">Keluar</span></button>
            </form>
        </div>
    </div>

    @yield('content')
</div>

<script src="{{ asset('assets/js/core/libs.min.js') }}"></script>
@include('dashboard.components.sweetalert')
@yield('js')
</body>
</html>
