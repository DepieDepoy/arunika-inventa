@extends('backoffice.layouts.app')

@section('title', 'Keamanan')

@section('content')
@include('backoffice.partials.style')
@php $TF = \App\Services\Backoffice\TwoFactor::class; @endphp

<div class="container-xxl flex-grow-1 container-p-y">
    <div class="cms-card p-3 p-md-4 mb-4">
        <span class="cms-badge"><i class="bi bi-shield-lock"></i> Pengaturan</span>
        <h4 class="mb-1 mt-2">Keamanan backoffice</h4>
        <div class="text-muted small">Verifikasi 2 langkah memakai aplikasi authenticator (Google / Microsoft Authenticator). Gratis, tanpa SMS, dan bekerja di localhost maupun server.</div>
    </div>

    @if(session('success'))<div class="alert alert-success"><i class="bi bi-check2-circle me-1"></i> {{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <div class="row g-4">
        <div class="col-xl-5">
            <form method="POST" action="{{ route('backoffice.settings.security.update') }}" class="cms-card p-3 p-md-4 h-100">
                @csrf
                <h6 class="fw-bold mb-3">Verifikasi 2 langkah</h6>
                @foreach($policies as $k => $label)
                    <label class="d-flex gap-2 p-3 rounded-3 mb-2" style="border:1px solid rgba(15,27,45,.12);cursor:pointer">
                        <input type="radio" name="two_factor" value="{{ $k }}" class="form-check-input mt-1" @checked($policy === $k)>
                        <span>
                            <b>{{ $label }}</b>
                            <span class="d-block small text-muted">
                                @switch($k)
                                    @case('off') Login hanya dengan password. Pengaturan pegawai tersimpan, dipakai lagi bila diaktifkan. @break
                                    @case('optional') Pegawai bisa mengaktifkan sendiri di menu Keamanan akun. @break
                                    @case('super') Disarankan. Super Admin wajib mengaktifkan sebelum bisa memakai backoffice. @break
                                    @case('all') Paling aman. Semua pegawai wajib mengaktifkan. @break
                                @endswitch
                            </span>
                        </span>
                    </label>
                @endforeach
                <div class="d-flex justify-content-end mt-3"><button class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Simpan</button></div>
            </form>
        </div>

        <div class="col-xl-7">
            <div class="cms-card h-100">
                <div class="p-3 p-md-4 pb-2"><h6 class="fw-bold mb-1">Status pegawai</h6><div class="small text-muted">Reset bila pegawai kehilangan / ganti HP. Ia akan diminta mengaktifkan ulang.</div></div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead><tr class="small text-muted text-uppercase"><th>Pegawai</th><th>Peran</th><th>2 langkah</th><th class="text-end">Aksi</th></tr></thead>
                        <tbody>
                            @foreach($staff as $s)
                                <tr class="{{ $s->is_active ? '' : 'text-muted' }}">
                                    <td><b class="text-dark">{{ $s->name }}</b><br><span class="small">{{ $s->email }}</span></td>
                                    <td class="small">{{ $s->roleLabel() }}</td>
                                    <td>
                                        @if($TF::enabled($s))
                                            <span class="cms-chip" style="background:#dcfce7;color:#166534">Aktif</span>
                                        @elseif($TF::requiredFor($s))
                                            <span class="cms-chip" style="background:#fee2e2;color:#991b1b">Wajib, belum aktif</span>
                                        @else
                                            <span class="cms-chip">Belum aktif</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if($TF::enabled($s) && $s->id !== auth('backoffice')->id())
                                            <form method="POST" action="{{ route('backoffice.staff.two-factor-reset', $s->id) }}" class="d-inline js-confirm" data-msg="Reset verifikasi 2 langkah {{ $s->name }}?">
                                                @csrf<button class="btn btn-sm btn-outline-secondary">Reset</button>
                                            </form>
                                        @elseif($s->id === auth('backoffice')->id())
                                            <a href="{{ route('backoffice.two-factor.show') }}" class="btn btn-sm btn-link">Akun saya</a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
document.querySelectorAll('.js-confirm').forEach(function (f) {
    f.addEventListener('submit', function (e) {
        if (f.dataset.ok) return;
        e.preventDefault();
        Swal.fire({ icon: 'warning', title: f.dataset.msg, showCancelButton: true, confirmButtonText: 'Ya, reset', cancelButtonText: 'Batal', reverseButtons: true })
            .then(function (r) { if (r.isConfirmed) { f.dataset.ok = 1; f.submit(); } });
    });
});
</script>
@endsection
