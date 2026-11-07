{{-- Layout halaman publik sederhana (legal, hapus akun). Variabel: $locale, $footer --}}
@php
    $isEn = ($locale ?? 'id') === 'en';
    $home = $isEn ? route('landing.en') : route('landing');
    $links = $isEn
        ? [[route('legal.privacy.en'), 'Privacy Policy'], [route('legal.terms.en'), 'Terms of Service'], [route('account-deletion.create.en'), 'Delete Account']]
        : [[route('legal.privacy'), 'Kebijakan Privasi'], [route('legal.terms'), 'Syarat Layanan'], [route('account-deletion.create'), 'Hapus Akun']];
@endphp
<!DOCTYPE html>
<html lang="{{ $isEn ? 'en' : 'id' }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title') · Vasetra</title>
<meta name="description" content="@yield('description')">
<meta name="theme-color" content="#0b2447">
@include('partials.favicon')
<link rel="stylesheet" href="{{ asset('assets/vendor/fonts/plus-jakarta-sans/plus-jakarta-sans.css') }}">
@yield('head')
<style>
    :root { --navy:#0b2447; --blue:#1f5fbf; --teal:#14b8a6; --ink:#0f1b2d; --muted:#5b6b82; --line:#e3e9f2; --bg:#f6f8fc; }
    * { box-sizing:border-box; }
    body { margin:0; font-family:"Plus Jakarta Sans", system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; color:var(--ink); background:var(--bg); line-height:1.7; }
    a { color:var(--blue); }
    .top { background:#fff; border-bottom:1px solid var(--line); position:sticky; top:0; z-index:5; }
    .top .wrap { display:flex; justify-content:space-between; align-items:center; height:68px; }
    .top img { height:34px; }
    .lang a { font-size:.8rem; font-weight:700; text-decoration:none; padding:5px 10px; border-radius:999px; color:var(--muted); }
    .lang a.on { background:var(--navy); color:#fff; }
    .wrap { width:min(880px, 100% - 32px); margin:0 auto; }
    .hero { background:linear-gradient(125deg,#0b2447 0%,#163f86 45%,#1f5fbf 78%,#14b8a6 130%); color:#fff; padding:56px 0 64px; }
    .hero h1 { margin:0 0 8px; font-size:clamp(1.8rem, 4vw, 2.5rem); line-height:1.2; }
    .hero p { margin:0; color:rgba(255,255,255,.8); max-width:680px; }
    .hero .meta { margin-top:14px; font-size:.82rem; color:#67e8f9; font-weight:700; }
    .card { background:#fff; border:1px solid var(--line); border-radius:18px; padding:clamp(22px, 4vw, 44px); margin:-36px auto 48px; box-shadow:0 18px 40px -28px rgba(15,27,45,.35); }
    .doc h2 { font-size:1.12rem; color:var(--navy); margin:30px 0 8px; }
    .doc h2:first-child { margin-top:0; }
    .doc p, .doc li { color:#2c3e57; }
    .doc ul { padding-left:22px; }
    .foot { border-top:1px solid var(--line); background:#fff; padding:22px 0; font-size:.84rem; color:var(--muted); }
    .foot .wrap { display:flex; flex-wrap:wrap; gap:10px 18px; justify-content:space-between; }
    .foot nav { display:flex; flex-wrap:wrap; gap:14px; }
    .foot a { color:var(--muted); text-decoration:none; }
    .foot a:hover { color:var(--blue); }
</style>
</head>
<body>
<header class="top">
    <div class="wrap">
        <a href="{{ $home }}"><img src="{{ asset('assets/images/brand/vasetra-logo.png') }}" alt="Vasetra"></a>
        <div class="lang">@yield('lang')</div>
    </div>
</header>

@yield('content')

<footer class="foot">
    <div class="wrap">
        <span>© {{ now()->year }} Vasetra{{ !empty($footer['company']) ? ' · ' . $footer['company'] : '' }}</span>
        <nav>
            <a href="{{ $home }}">{{ $isEn ? 'Home' : 'Beranda' }}</a>
            @foreach($links as [$url, $label])<a href="{{ $url }}">{{ $label }}</a>@endforeach
        </nav>
    </div>
</footer>
</body>
</html>
