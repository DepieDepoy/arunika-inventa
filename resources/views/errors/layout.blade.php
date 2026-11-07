{{--
    Kerangka halaman error Vasetra. Sengaja tanpa CSS/JS eksternal, database,
    atau session agar tetap tampil walau aplikasi sedang bermasalah.
--}}
@php
    $home = url('/dashboard/home');
    try {
        $loggedIn = auth()->check();
    } catch (\Throwable $e) {
        $loggedIn = false;
    }
    $primaryUrl = $loggedIn ? $home : url('/');
    $primaryLabel = $loggedIn ? 'Kembali ke dashboard' : 'Ke beranda';
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('title') - Vasetra</title>
    @include('partials.favicon')
    <style>
        :root { --navy:#0b2447; --blue:#1f5fbf; --teal:#14b8a6; --ink:#0f1b2d; --muted:#5b6b80; --line:#e3e9f2; --bg:#f4f7fb; }
        * { box-sizing:border-box; }
        body { margin:0; min-height:100vh; display:flex; flex-direction:column; font-family:"Plus Jakarta Sans",system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif; background:var(--bg); color:var(--ink); }
        main { flex:1; display:flex; align-items:center; justify-content:center; padding:32px 20px; }
        .card { width:100%; max-width:520px; background:#fff; border:1px solid var(--line); border-radius:20px; padding:40px 36px 34px; text-align:center; box-shadow:0 18px 50px -24px rgba(11,36,71,.25); }
        .logo { height:30px; margin-bottom:28px; }
        .badge { width:72px; height:72px; margin:0 auto 18px; border-radius:20px; display:flex; align-items:center; justify-content:center; background:linear-gradient(135deg, rgba(31,95,191,.12), rgba(20,184,166,.16)); color:var(--blue); }
        .badge svg { width:34px; height:34px; }
        .code { font-size:.78rem; font-weight:800; letter-spacing:.16em; color:var(--teal); text-transform:uppercase; margin:0 0 6px; }
        h1 { font-size:1.45rem; line-height:1.3; margin:0 0 10px; color:var(--navy); }
        p.msg { margin:0 auto 26px; max-width:400px; color:var(--muted); line-height:1.6; font-size:.95rem; }
        .actions { display:flex; gap:10px; justify-content:center; flex-wrap:wrap; }
        .btn { display:inline-flex; align-items:center; justify-content:center; gap:8px; padding:11px 20px; border-radius:12px; font-weight:700; font-size:.92rem; text-decoration:none; border:1px solid transparent; cursor:pointer; font-family:inherit; }
        .btn-primary { background:var(--navy); color:#fff; }
        .btn-primary:hover { background:var(--blue); }
        .btn-ghost { background:#fff; color:var(--navy); border-color:var(--line); }
        .btn-ghost:hover { border-color:var(--blue); color:var(--blue); }
        .hint { margin-top:22px; font-size:.8rem; color:#8592a6; }
        footer { text-align:center; padding:18px; font-size:.78rem; color:#8592a6; }
        @media (max-width:480px) { .card { padding:32px 22px 26px; } h1 { font-size:1.25rem; } .btn { flex:1 1 100%; } }
    </style>
</head>
<body>
<main>
    <div class="card">
        <img class="logo" src="{{ asset('assets/images/brand/vasetra-logo.png') }}" alt="Vasetra">
        <div class="badge" aria-hidden="true">@yield('icon')</div>
        <p class="code">@yield('code')</p>
        <h1>@yield('title')</h1>
        <p class="msg">@yield('message')</p>
        <div class="actions">
            @hasSection('actions')
                @yield('actions')
            @else
                <a class="btn btn-primary" href="{{ $primaryUrl }}">{{ $primaryLabel }}</a>
                <button type="button" class="btn btn-ghost" onclick="history.length > 1 ? history.back() : location.href='{{ $primaryUrl }}'">Halaman sebelumnya</button>
            @endif
        </div>
        @hasSection('hint')<div class="hint">@yield('hint')</div>@endif
    </div>
</main>
<footer>&copy; {{ date('Y') }} Vasetra &middot; PT Arunika Solusi Inovasi</footer>
</body>
</html>
