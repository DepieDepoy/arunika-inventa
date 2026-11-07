<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Verifikasi 2 langkah · Vasetra Backoffice</title>
    <link rel="icon" href="{{ asset('assets/images/favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/fonts/plus-jakarta-sans/plus-jakarta-sans.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
    <style>
        * { box-sizing:border-box; }
        body { margin:0; min-height:100vh; display:grid; place-items:center; padding:24px; font-family:"Plus Jakarta Sans",system-ui,-apple-system,"Segoe UI",Roboto,sans-serif; color:#4b5a6e;
            background:#071a35; background-image:radial-gradient(50% 60% at 10% 0%,rgba(31,95,191,.55),transparent 70%),radial-gradient(40% 50% at 100% 100%,rgba(20,184,166,.4),transparent 70%); }
        .card { width:100%; max-width:410px; background:#fff; border-radius:22px; padding:34px 32px; box-shadow:0 40px 80px -30px rgba(0,0,0,.6); }
        .logo { height:34px; display:block; margin-bottom:6px; }
        .ic { width:54px; height:54px; border-radius:16px; display:grid; place-items:center; font-size:1.6rem; color:#fff; margin:14px 0 14px; background:linear-gradient(125deg,#0b2447 0%,#1f5fbf 64%,#14b8a6 118%); }
        h1 { font-size:1.3rem; color:#0f1b2d; margin:0 0 4px; }
        p.sub { margin:0 0 20px; font-size:.88rem; }
        .code { width:100%; padding:14px; border-radius:14px; border:1px solid rgba(15,27,45,.15); font:inherit; font-size:1.7rem; letter-spacing:.5em; text-align:center; font-weight:800; color:#0f1b2d; outline:none; }
        .code:focus, .rc:focus { border-color:#1f5fbf; box-shadow:0 0 0 3px rgba(31,95,191,.15); }
        .rc { width:100%; padding:12px 14px; border-radius:12px; border:1px solid rgba(15,27,45,.15); font:inherit; text-transform:uppercase; letter-spacing:.12em; outline:none; }
        .row { display:flex; gap:8px; align-items:center; font-size:.84rem; margin:14px 0 18px; font-weight:600; }
        button { width:100%; border:0; border-radius:999px; padding:13px; font:inherit; font-weight:800; color:#fff; cursor:pointer; background:linear-gradient(125deg,#0b2447 0%,#163f86 38%,#1f5fbf 64%,#14b8a6 118%); }
        .err { background:#fee2e2; color:#b91c1c; border-radius:12px; padding:10px 14px; font-size:.85rem; margin-bottom:16px; }
        .link { background:none; border:0; color:#1f5fbf; font:inherit; font-size:.84rem; font-weight:700; padding:0; width:auto; cursor:pointer; }
        .note { font-size:.75rem; color:#8592a6; margin-top:18px; text-align:center; }
        [hidden] { display:none !important; }
    </style>
</head>
<body>
    <form class="card" method="POST" action="{{ route('backoffice.two-factor.verify') }}" autocomplete="off">
        @csrf
        <img src="{{ asset('assets/images/brand/vasetra-logo.png') }}" alt="Vasetra" class="logo">
        <div class="ic"><i class="bi bi-shield-lock"></i></div>
        <h1>Verifikasi 2 langkah</h1>
        <p class="sub">Buka aplikasi authenticator di HP Anda, lalu masukkan kode 6 digit untuk <b>{{ $email }}</b>.</p>

        @if($errors->any())<div class="err"><i class="bi bi-exclamation-circle"></i> {{ $errors->first() }}</div>@endif

        <div id="byCode">
            <input class="code" type="text" name="code" inputmode="numeric" pattern="[0-9 ]*" maxlength="7" placeholder="••••••" autofocus autocomplete="one-time-code">
        </div>
        <div id="byRecovery" hidden>
            <label style="display:block;font-weight:700;font-size:.82rem;color:#0f1b2d;margin-bottom:6px">Kode cadangan</label>
            <input class="rc" type="text" name="recovery_code" maxlength="11" placeholder="XXXXX-XXXXX">
        </div>

        <label class="row"><input type="checkbox" name="trust_device" value="1"> Percayai perangkat ini selama 30 hari</label>

        <button type="submit">Verifikasi &amp; masuk</button>

        <div class="note">
            <button type="button" class="link" id="toggle">HP tidak ada? Pakai kode cadangan</button><br><br>
            <a href="{{ route('backoffice.login') }}">Kembali ke login</a>
        </div>
    </form>
<script>
    (function () {
        var t = document.getElementById('toggle'), a = document.getElementById('byCode'), b = document.getElementById('byRecovery');
        t.addEventListener('click', function () {
            var rec = a.hidden;
            a.hidden = !rec; b.hidden = rec;
            a.querySelector('input').value = ''; b.querySelector('input').value = '';
            t.textContent = rec ? 'HP tidak ada? Pakai kode cadangan' : 'Pakai kode dari aplikasi authenticator';
            (rec ? a : b).querySelector('input').focus();
        });
        // Kirim otomatis setelah 6 digit.
        var c = a.querySelector('input');
        c.addEventListener('input', function () { if (c.value.replace(/\D/g, '').length === 6) c.form.submit(); });
    })();
</script>
</body>
</html>
