<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Masuk · Vasetra Backoffice</title>
    <link rel="icon" href="{{ asset('assets/images/favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/fonts/plus-jakarta-sans/plus-jakarta-sans.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
    <style>
        * { box-sizing:border-box; }
        body { margin:0; min-height:100vh; display:grid; place-items:center; padding:24px; font-family:"Plus Jakarta Sans",system-ui,-apple-system,"Segoe UI",Roboto,sans-serif; color:#4b5a6e;
            background:#071a35; background-image:radial-gradient(50% 60% at 10% 0%,rgba(31,95,191,.55),transparent 70%),radial-gradient(40% 50% at 100% 100%,rgba(20,184,166,.4),transparent 70%); }
        .card { width:100%; max-width:410px; background:#fff; border-radius:22px; padding:34px 32px; box-shadow:0 40px 80px -30px rgba(0,0,0,.6); }
        .logo { height:34px; display:block; margin-bottom:6px; }
        .tag { display:inline-flex; gap:6px; align-items:center; font-size:.7rem; font-weight:800; letter-spacing:.1em; color:#1f5fbf; background:#e8f0fd; border-radius:999px; padding:4px 10px; margin:10px 0 18px; }
        h1 { font-size:1.35rem; color:#0f1b2d; margin:0 0 4px; letter-spacing:-.02em; }
        p.sub { margin:0 0 22px; font-size:.88rem; }
        label { display:block; font-weight:700; font-size:.82rem; color:#0f1b2d; margin-bottom:6px; }
        input[type=email], input[type=password] { width:100%; padding:12px 14px; border-radius:12px; border:1px solid rgba(15,27,45,.15); font:inherit; margin-bottom:14px; outline:none; }
        input:focus { border-color:#1f5fbf; box-shadow:0 0 0 3px rgba(31,95,191,.15); }
        .row { display:flex; justify-content:space-between; align-items:center; font-size:.84rem; margin-bottom:18px; }
        button { width:100%; border:0; border-radius:999px; padding:13px; font:inherit; font-weight:800; color:#fff; cursor:pointer;
            background:linear-gradient(125deg,#0b2447 0%,#163f86 38%,#1f5fbf 64%,#14b8a6 118%); }
        .err { background:#fee2e2; color:#b91c1c; border-radius:12px; padding:10px 14px; font-size:.85rem; margin-bottom:16px; }
        .ok { background:#ccfbf1; color:#0f766e; border-radius:12px; padding:10px 14px; font-size:.85rem; margin-bottom:16px; }
        .note { font-size:.75rem; color:#8592a6; margin-top:18px; text-align:center; }
    </style>
</head>
<body>
    <form class="card" method="POST" action="{{ route('backoffice.login.attempt') }}">
        @csrf
        <img src="{{ asset('assets/images/brand/vasetra-logo.png') }}" alt="Vasetra" class="logo">
        <span class="tag"><i class="bi bi-shield-lock"></i> BACKOFFICE</span>
        <h1>Masuk sebagai pegawai Vasetra</h1>
        <p class="sub">Untuk mengelola konten website, paket, dan harga.</p>

        @if(session('status'))<div class="ok">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="err"><i class="bi bi-exclamation-circle"></i> {{ $errors->first() }}</div>@endif

        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required autocomplete="current-password">

        <div class="row">
            <label style="display:flex;gap:8px;align-items:center;margin:0;font-weight:600"><input type="checkbox" name="remember" value="1"> Ingat saya</label>
        </div>

        <button type="submit">Masuk</button>

        <div class="note">Bukan pegawai Vasetra? Pelanggan masuk lewat <a href="{{ route('login') }}">halaman login aplikasi</a>.</div>
    </form>
</body>
</html>
