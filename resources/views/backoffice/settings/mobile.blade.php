@extends('backoffice.layouts.app')

@section('title', 'Aplikasi Mobile')

@section('content')
@include('backoffice.partials.style')

<div class="container-xxl flex-grow-1 container-p-y">
    <div class="cms-card p-3 p-md-4 mb-4">
        <span class="cms-badge"><i class="bi bi-phone"></i> Pengaturan</span>
        <h4 class="mb-1 mt-2">Aplikasi mobile</h4>
        <div class="text-muted small">Aplikasi membaca pengaturan ini lewat <code>GET /api/v1/app-info</code> dan header <code>X-App-Version</code>. Versi di bawah minimal akan diminta update.</div>
    </div>

    @if(session('success'))<div class="alert alert-success"><i class="bi bi-check2-circle me-1"></i> {{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <div class="row g-4">
        <div class="col-xl-8">
            <form method="POST" action="{{ route('backoffice.settings.mobile.update') }}" class="cms-card p-3 p-md-4">
                @csrf
                <div class="row g-4">
                    @foreach(['android' => ['Android', 'bi-android2', 'https://play.google.com/store/apps/details?id=...'], 'ios' => ['iOS', 'bi-apple', 'https://apps.apple.com/app/...']] as $p => [$label, $icon, $ph])
                        <div class="col-md-6">
                            <h6 class="fw-bold"><i class="bi {{ $icon }} me-1"></i> {{ $label }}</h6>
                            <label class="form-label small fw-bold mt-2">Versi minimal (wajib update di bawah ini)</label>
                            <input name="{{ $p }}_min" class="form-control" value="{{ old($p . '_min', $s[$p . '_min']) }}" placeholder="1.0.0">
                            <label class="form-label small fw-bold mt-2">Versi terbaru</label>
                            <input name="{{ $p }}_latest" class="form-control" value="{{ old($p . '_latest', $s[$p . '_latest']) }}" placeholder="1.0.0">
                            <label class="form-label small fw-bold mt-2">Link toko</label>
                            <input name="{{ $p }}_store_url" class="form-control" value="{{ old($p . '_store_url', $s[$p . '_store_url']) }}" placeholder="{{ $ph }}">
                        </div>
                    @endforeach

                    <div class="col-12">
                        <label class="form-label small fw-bold">Pesan wajib update</label>
                        <input name="update_message" class="form-control" maxlength="300" value="{{ old('update_message', $s['update_message']) }}">
                    </div>

                    <div class="col-12">
                        <div class="p-3 rounded-3" style="background:#fff7ed;border:1px solid #fed7aa">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" name="maintenance" value="1" id="mt" @checked(old('maintenance', $s['maintenance']))>
                                <label class="form-check-label fw-bold" for="mt">Mode pemeliharaan aplikasi</label>
                            </div>
                            <div class="small text-muted mb-2">Saat aktif, semua request dari aplikasi mobile dijawab "sedang pemeliharaan" (website tetap jalan).</div>
                            <input name="maintenance_message" class="form-control" maxlength="300" value="{{ old('maintenance_message', $s['maintenance_message']) }}">
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-end mt-4">
                    <button class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Simpan</button>
                </div>
            </form>
        </div>

        <div class="col-xl-4">
            <div class="cms-card p-3 p-md-4 mb-4">
                <h6 class="fw-bold mb-1"><i class="bi bi-bell me-1"></i> Notifikasi push</h6>
                <div class="small text-muted mb-3">Matikan bila tidak ingin pelanggan menerima pop-up. Notifikasi di lonceng tetap tersimpan.</div>
                <form method="POST" action="{{ route('backoffice.settings.push.update') }}" class="mb-3">
                    @csrf
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input" type="checkbox" role="switch" name="web_enabled" value="1" id="pushWeb" @checked($push['web_on']) onchange="this.form.submit()">
                            <label class="form-check-label small fw-bold" for="pushWeb">Browser (Chrome / Edge)</label>
                        </div>
                        <span class="cms-chip {{ $push['web'] ? 'cms-chip-blue' : '' }}">{{ $push['web'] ? 'Siap' : 'Belum diatur' }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input" type="checkbox" role="switch" name="mobile_enabled" value="1" id="pushMobile" @checked($push['mobile_on']) onchange="this.form.submit()">
                            <label class="form-check-label small fw-bold" for="pushMobile">Aplikasi Android / iOS</label>
                        </div>
                        <span class="cms-chip {{ $push['fcm'] ? 'cms-chip-blue' : '' }}" style="{{ $push['fcm'] ? '' : 'background:#fef3c7;color:#92400e' }}">{{ $push['fcm'] ? 'Siap' : 'Belum diatur' }}</span>
                    </div>
                </form>
                <div class="small text-muted">
                    Browser terdaftar: <b>{{ number_format($push['browsers']) }}</b><br>
                    Perangkat: <b>{{ number_format($push['devices']['android'] ?? 0) }}</b> Android · <b>{{ number_format($push['devices']['ios'] ?? 0) }}</b> iOS
                </div>
                @unless($push['fcm'])
                    <div class="alert alert-warning small mt-3 mb-0">
                        Push ke HP butuh file service account Firebase. Unduh dari Firebase Console › Project settings › Service accounts, simpan di server (di luar folder public), lalu isi <code>FIREBASE_CREDENTIALS</code> di <code>.env</code> dengan path file tersebut.
                    </div>
                @endunless
            </div>
            <div class="cms-card p-3 p-md-4">
                <h6 class="fw-bold mb-2"><i class="bi bi-link-45deg me-1"></i> Link untuk Play Store / App Store</h6>
                <div class="small">
                    Kebijakan Privasi:<br><a href="{{ route('legal.privacy') }}" target="_blank">{{ route('legal.privacy') }}</a><br><br>
                    Hapus akun &amp; data:<br><a href="{{ route('account-deletion.create') }}" target="_blank">{{ route('account-deletion.create') }}</a><br><br>
                    Syarat Layanan:<br><a href="{{ route('legal.terms') }}" target="_blank">{{ route('legal.terms') }}</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
