@extends('backoffice.layouts.app')

@section('title', 'Keamanan Akun')

@section('content')
@include('backoffice.partials.style')
<style>
    .tf-steps { counter-reset:s; list-style:none; padding:0; margin:0; }
    .tf-steps li { counter-increment:s; display:flex; gap:12px; margin-bottom:14px; font-size:.9rem; }
    .tf-steps li:before { content:counter(s); width:26px; height:26px; border-radius:50%; background:#e8f0fd; color:#1f5fbf; font-weight:800; display:grid; place-items:center; flex:none; font-size:.8rem; }
    #tfQr { width:196px; height:196px; padding:8px; background:#fff; border:1px solid rgba(15,27,45,.1); border-radius:14px; }
    .tf-key { font-family:ui-monospace,Consolas,monospace; font-size:.9rem; letter-spacing:.08em; background:#f4f7fb; border-radius:10px; padding:8px 12px; word-break:break-all; }
    .tf-codes { display:grid; grid-template-columns:repeat(2, 1fr); gap:8px; font-family:ui-monospace,Consolas,monospace; font-size:1rem; font-weight:700; color:#0f1b2d; }
    .tf-codes span { background:#f4f7fb; border-radius:8px; padding:8px; text-align:center; letter-spacing:.06em; }
    .tf-code-input { max-width:200px; font-size:1.4rem; letter-spacing:.4em; text-align:center; font-weight:800; }
</style>

<div class="container-xxl flex-grow-1 container-p-y">
    @if(session('warning'))<div class="alert alert-warning"><i class="bi bi-shield-exclamation me-1"></i> {{ session('warning') }}</div>@endif
    @if(session('success'))<div class="alert alert-success"><i class="bi bi-check2-circle me-1"></i> {{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <div class="cms-card p-3 p-md-4 mb-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <span class="cms-badge"><i class="bi bi-shield-lock"></i> Keamanan akun</span>
            <h4 class="mb-1 mt-2">Verifikasi 2 langkah</h4>
            <div class="text-muted small">Setelah password, login meminta kode 6 digit dari aplikasi authenticator di HP Anda. Bekerja tanpa internet & tanpa SMS.</div>
        </div>
        @if($enabled)
            <span class="cms-chip" style="background:#dcfce7;color:#166534;font-size:.8rem;padding:6px 12px"><i class="bi bi-check-circle"></i> Aktif sejak {{ $admin->two_factor_confirmed_at?->locale('id')->translatedFormat('d M Y') }}</span>
        @else
            <span class="cms-chip" style="background:#fef3c7;color:#92400e;font-size:.8rem;padding:6px 12px">{{ $required ? 'Wajib diaktifkan' : 'Belum aktif' }}</span>
        @endif
    </div>

    @if($policy === 'off')
        <div class="alert alert-info small"><i class="bi bi-info-circle me-1"></i> Verifikasi 2 langkah sedang dinonaktifkan oleh Super Admin. Pengaturan Anda tersimpan dan berlaku lagi bila diaktifkan.</div>
    @endif

    @if($newCodes)
        <div class="cms-card p-3 p-md-4 mb-4" style="border-color:#f59e0b">
            <h6 class="fw-bold"><i class="bi bi-key me-1 text-warning"></i> Kode cadangan — simpan sekarang</h6>
            <p class="small text-muted">Pakai salah satu kode ini bila HP hilang. Setiap kode hanya bisa dipakai sekali. <b>Kode tidak akan ditampilkan lagi.</b></p>
            <div class="tf-codes mb-3" style="max-width:420px">@foreach($newCodes as $c)<span>{{ $c }}</span>@endforeach</div>
            <button type="button" class="btn btn-outline-primary btn-sm" id="tfCopy"><i class="bi bi-clipboard me-1"></i> Salin semua</button>
            <textarea id="tfCodesText" class="d-none">{{ implode("\n", $newCodes) }}</textarea>
        </div>
    @endif

    @if(!$enabled)
        <div class="row g-4">
            <div class="col-lg-7">
                <div class="cms-card p-3 p-md-4 h-100">
                    <ol class="tf-steps">
                        <li><span>Pasang <b>Google Authenticator</b> atau <b>Microsoft Authenticator</b> di HP (gratis, dari Play Store / App Store).</span></li>
                        <li><span>Di aplikasi, pilih <b>Tambah akun → Pindai kode QR</b>, lalu arahkan ke kode di samping.</span></li>
                        <li><span>Masukkan kode 6 digit yang muncul di aplikasi untuk mengonfirmasi.</span></li>
                    </ol>
                    <form method="POST" action="{{ route('backoffice.two-factor.enable') }}" class="d-flex gap-2 flex-wrap align-items-center mt-2">
                        @csrf
                        <input type="text" name="code" class="form-control tf-code-input" inputmode="numeric" maxlength="7" placeholder="000000" autocomplete="one-time-code" required>
                        <button class="btn btn-primary"><i class="bi bi-shield-check me-1"></i> Aktifkan</button>
                    </form>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="cms-card p-3 p-md-4 h-100 text-center">
                    <div id="tfQr" class="mx-auto mb-3"></div>
                    <div class="small text-muted mb-1">Tidak bisa scan? Masukkan kunci ini secara manual:</div>
                    <div class="tf-key">{{ trim(chunk_split($secret, 4, ' ')) }}</div>
                    <div class="small text-muted mt-2">Nama akun: {{ \App\Services\Backoffice\TwoFactor::ISSUER }} · {{ $admin->email }}</div>
                </div>
            </div>
        </div>
    @else
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="cms-card p-3 p-md-4 h-100">
                    <h6 class="fw-bold"><i class="bi bi-key me-1"></i> Kode cadangan</h6>
                    <p class="small text-muted">Tersisa <b>{{ $codesLeft }}</b> dari 8 kode. Buat ulang bila hampir habis atau mungkin sudah dilihat orang lain.</p>
                    <form method="POST" action="{{ route('backoffice.two-factor.codes') }}" class="d-flex gap-2 flex-wrap">
                        @csrf
                        <input type="password" name="password" class="form-control" style="max-width:220px" placeholder="Password Anda" required autocomplete="current-password">
                        <button class="btn btn-outline-primary">Buat kode baru</button>
                    </form>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="cms-card p-3 p-md-4 h-100">
                    <h6 class="fw-bold"><i class="bi bi-shield-x me-1"></i> Nonaktifkan</h6>
                    @if($required)
                        <p class="small text-muted mb-0">Verifikasi 2 langkah <b>wajib</b> untuk akun Anda sesuai kebijakan keamanan, jadi tidak bisa dimatikan. Bila ganti HP, minta Super Admin lain mereset lalu aktifkan ulang.</p>
                    @else
                        <p class="small text-muted">Login hanya akan memakai password (kurang aman).</p>
                        <form method="POST" action="{{ route('backoffice.two-factor.disable') }}" class="d-flex gap-2 flex-wrap">
                            @csrf
                            <input type="password" name="password" class="form-control" style="max-width:220px" placeholder="Password Anda" required autocomplete="current-password">
                            <button class="btn btn-outline-danger">Nonaktifkan</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

@section('js')
<script src="{{ asset('assets/vendor/qrcodejs/qrcode.min.js') }}"></script>
<script>
(function () {
    var box = document.getElementById('tfQr');
    if (box && window.QRCode) {
        new QRCode(box, { text: @json($otpauth), width: 180, height: 180, correctLevel: QRCode.CorrectLevel.M });
    }
    var copy = document.getElementById('tfCopy');
    if (copy) copy.addEventListener('click', function () {
        navigator.clipboard.writeText(document.getElementById('tfCodesText').value).then(function () { copy.innerHTML = '<i class="bi bi-check2 me-1"></i> Tersalin'; });
    });
})();
</script>
@endsection
