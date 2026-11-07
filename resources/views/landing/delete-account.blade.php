@extends('landing.layouts.page')

@php $en = $locale === 'en'; @endphp

@section('title', $en ? 'Delete Account' : 'Hapus Akun')
@section('description', $en ? 'Request deletion of your Vasetra account and personal data.' : 'Ajukan penghapusan akun dan data pribadi Vasetra Anda.')

@section('lang')
    <a href="{{ route('account-deletion.create') }}" class="{{ $en ? '' : 'on' }}" hreflang="id">ID</a>
    <a href="{{ route('account-deletion.create.en') }}" class="{{ $en ? 'on' : '' }}" hreflang="en">EN</a>
@endsection

@section('head')
<style>
    .grid { display:grid; grid-template-columns:1fr 1fr; gap:28px; }
    @media (max-width: 760px) { .grid { grid-template-columns:1fr; } }
    .box { background:#f6f8fc; border:1px solid var(--line); border-radius:14px; padding:18px 20px; }
    .box h3 { margin:0 0 6px; font-size:1rem; color:var(--navy); }
    .box ol, .box ul { margin:6px 0 0; padding-left:20px; color:#2c3e57; font-size:.92rem; }
    label { display:block; font-weight:700; font-size:.88rem; margin:14px 0 6px; }
    input[type=email], textarea { width:100%; border:1px solid #cfd8e6; border-radius:10px; padding:11px 13px; font:inherit; font-size:.95rem; }
    input:focus, textarea:focus { outline:2px solid rgba(31,95,191,.35); border-color:var(--blue); }
    .check { display:flex; gap:10px; align-items:flex-start; margin-top:14px; font-size:.88rem; color:#2c3e57; font-weight:400; }
    .check input { margin-top:5px; }
    .btn { margin-top:18px; border:0; border-radius:11px; padding:12px 22px; font:inherit; font-weight:800; color:#fff; background:#dc2626; cursor:pointer; }
    .err { color:#b91c1c; font-size:.85rem; margin-top:6px; }
    .ok { background:#ecfdf5; border:1px solid #a7f3d0; color:#065f46; border-radius:14px; padding:18px 20px; }
    .hp { position:absolute; left:-9999px; }
</style>
@endsection

@section('content')
<section class="hero">
    <div class="wrap">
        <h1>{{ $en ? 'Delete your Vasetra account' : 'Hapus akun Vasetra' }}</h1>
        <p>{{ $en
            ? 'You can delete your account and personal data at any time. Requests are completed within 30 days.'
            : 'Anda dapat menghapus akun dan data pribadi kapan saja. Permintaan diselesaikan paling lambat 30 hari.' }}</p>
    </div>
</section>

<div class="wrap">
    <div class="card">
        <div class="grid">
            <div>
                <div class="box">
                    <h3>{{ $en ? 'Fastest way: from the app' : 'Cara tercepat: dari aplikasi' }}</h3>
                    <ol>
                        @if($en)
                            <li>Sign in to Vasetra (web or mobile app).</li>
                            <li>Open <b>Profile</b> → <b>Delete account</b>.</li>
                            <li>Enter your password to confirm. Your account is deleted immediately.</li>
                        @else
                            <li>Masuk ke Vasetra (website atau aplikasi mobile).</li>
                            <li>Buka <b>Profil</b> → <b>Hapus akun</b>.</li>
                            <li>Masukkan password untuk konfirmasi. Akun langsung terhapus.</li>
                        @endif
                    </ol>
                </div>
                <div class="box" style="margin-top:14px">
                    <h3>{{ $en ? 'What gets deleted' : 'Data yang dihapus' }}</h3>
                    <ul>
                        @if($en)
                            <li>Your name, email, phone number, employee ID, and photo.</li>
                            <li>Your sign-in sessions, app tokens, and notification subscriptions.</li>
                            <li>If you are your company's <b>last administrator</b>, the whole company account is closed and its users' personal data is deleted.</li>
                            <li>Records we must keep by law (such as payment transactions) are kept as required.</li>
                        @else
                            <li>Nama, email, nomor HP, NIK/ID karyawan, dan foto Anda.</li>
                            <li>Sesi login, token aplikasi, dan langganan notifikasi.</li>
                            <li>Bila Anda <b>administrator terakhir</b> perusahaan, akun perusahaan ditutup dan data pribadi seluruh user-nya dihapus.</li>
                            <li>Catatan yang wajib disimpan menurut hukum (mis. transaksi pembayaran) tetap disimpan sesuai ketentuan.</li>
                        @endif
                    </ul>
                </div>
            </div>

            <div>
                <h3 style="margin:0 0 4px;color:var(--navy)">{{ $en ? "Can't sign in? Send a request" : 'Tidak bisa login? Kirim permintaan' }}</h3>
                <p style="margin:0;color:var(--muted);font-size:.9rem">{{ $en ? 'We will verify the request via your email.' : 'Kami akan memverifikasi permintaan melalui email Anda.' }}</p>

                @if(session('submitted'))
                    <div class="ok" style="margin-top:16px">
                        <b>{{ $en ? 'Request received.' : 'Permintaan diterima.' }}</b><br>
                        {{ $en
                            ? 'If the email is registered with Vasetra, we will process the deletion within 30 days and contact you at that address.'
                            : 'Bila email tersebut terdaftar di Vasetra, penghapusan akan kami proses paling lambat 30 hari dan kami akan menghubungi Anda melalui email itu.' }}
                    </div>
                @else
                    <form method="POST" action="{{ route('account-deletion.store') }}">
                        @csrf
                        <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">
                        <label for="email">{{ $en ? 'Account email' : 'Email akun' }}</label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" required maxlength="255" autocomplete="email">
                        @error('email')<div class="err">{{ $message }}</div>@enderror

                        <label for="reason">{{ $en ? 'Reason (optional)' : 'Alasan (opsional)' }}</label>
                        <textarea name="reason" id="reason" rows="3" maxlength="1000">{{ old('reason') }}</textarea>

                        <label class="check">
                            <input type="checkbox" name="confirm" value="1" required>
                            <span>{{ $en ? 'I understand my account and personal data will be permanently deleted.' : 'Saya mengerti akun dan data pribadi saya akan dihapus permanen.' }}</span>
                        </label>
                        @error('confirm')<div class="err">{{ $message }}</div>@enderror

                        <button class="btn" type="submit">{{ $en ? 'Send deletion request' : 'Kirim permintaan hapus akun' }}</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
