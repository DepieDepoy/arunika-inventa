@extends('backoffice.layouts.app')

@section('title', 'Pegawai Backoffice')

@section('content')
@include('backoffice.partials.style')
@php $newPassword = session('new_password'); @endphp

<div class="container-xxl flex-grow-1 container-p-y">

    @if($newPassword)
        <div class="cms-card p-4 mb-4" style="border-color:#14b8a6">
            <h6 class="fw-bold mb-1"><i class="bi bi-key-fill text-success me-1"></i> Password untuk {{ $newPassword['name'] }}</h6>
            <p class="small text-muted mb-3">Password ini <strong>hanya ditampilkan sekali</strong>. Berikan ke pegawai lewat jalur pribadi, lalu minta ia menggantinya setelah login.</p>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <code class="fs-6 px-3 py-2 rounded" style="background:#f4f7fb" id="newPw">{{ $newPassword['password'] }}</code>
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="navigator.clipboard.writeText(document.getElementById('newPw').textContent).then(function(){ this.textContent='Tersalin'; }.bind(this))"><i class="bi bi-clipboard me-1"></i> Salin</button>
            </div>
            <div class="small text-muted mt-2">Email: {{ $newPassword['email'] }} · Login: <a href="{{ route('backoffice.login') }}" target="_blank">{{ route('backoffice.login') }}</a></div>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-4">
            <form method="POST" action="{{ route('backoffice.staff.store') }}" class="cms-card p-4">
                @csrf
                <h6 class="fw-bold mb-1">Tambah pegawai</h6>
                <p class="small text-muted mb-3">Password awal dibuat otomatis dan ditampilkan sekali setelah disimpan.</p>
                @if($errors->any())<div class="alert alert-danger py-2 small">{{ $errors->first() }}</div>@endif
                <div class="mb-3">
                    <label class="form-label small fw-bold">Nama</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" required maxlength="150">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Email</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Peran</label>
                    <select name="role" class="form-select">
                        <option value="editor" @selected(old('role', 'editor') === 'editor')>Editor — konten landing saja</option>
                        <option value="super_admin" @selected(old('role') === 'super_admin')>Super Admin — semua akses</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-person-plus me-1"></i> Tambah pegawai</button>
            </form>

            <div class="cms-card p-3 mt-3 small">
                <div class="fw-bold mb-2">Hak akses peran</div>
                <div class="mb-2"><span class="cms-chip cms-chip-blue">Super Admin</span> Konten landing, paket &amp; harga, pegawai, log aktivitas.</div>
                <div><span class="cms-chip">Editor</span> Konten landing saja.</div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="cms-card">
                <div class="p-3 p-md-4 pb-0">
                    <h6 class="fw-bold mb-1">Daftar pegawai ({{ $staff->count() }})</h6>
                    <p class="small text-muted mb-0">Pegawai yang resign cukup dinonaktifkan agar riwayat aktivitasnya tetap tersimpan.</p>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead><tr class="small text-muted text-uppercase"><th>Pegawai</th><th>Peran</th><th>Status</th><th>Login terakhir</th><th class="text-end">Aksi</th></tr></thead>
                        <tbody>
                            @foreach($staff as $s)
                                @php $isMe = $s->is($me); @endphp
                                <tr>
                                    <td>
                                        <div class="fw-bold">{{ $s->name }} @if($isMe)<span class="cms-chip ms-1">Anda</span>@endif</div>
                                        <div class="small text-muted">{{ $s->email }}</div>
                                    </td>
                                    <td><span class="cms-chip {{ $s->isSuperAdmin() ? 'cms-chip-blue' : '' }}">{{ $s->roleLabel() }}</span></td>
                                    <td>
                                        @if($s->is_active)<span class="cms-chip" style="background:#ccfbf1;color:#0f766e">Aktif</span>
                                        @else<span class="cms-chip" style="background:#fee2e2;color:#b91c1c">Nonaktif</span>@endif
                                    </td>
                                    <td class="small text-muted">{{ $s->last_login_at?->diffForHumans() ?? 'Belum pernah' }}</td>
                                    <td class="text-end text-nowrap">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#edit{{ $s->id }}"><i class="bi bi-pencil"></i></button>
                                        @unless($isMe)
                                            <form method="POST" action="{{ route('backoffice.staff.reset', $s) }}" class="d-inline js-confirm" data-confirm="Buat password baru untuk {{ $s->name }}? Password lama tidak berlaku lagi.">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-warning" title="Reset password"><i class="bi bi-key"></i></button>
                                            </form>
                                        @endunless
                                    </td>
                                </tr>
                                <tr class="collapse" id="edit{{ $s->id }}">
                                    <td colspan="5" style="background:#f8fafd">
                                        <form method="POST" action="{{ route('backoffice.staff.update', $s) }}" class="row g-2 align-items-end">
                                            @csrf
                                            <div class="col-md-4"><label class="small fw-bold">Nama</label><input type="text" name="name" class="form-control form-control-sm" value="{{ $s->name }}" required></div>
                                            <div class="col-md-3"><label class="small fw-bold">Peran</label>
                                                <select name="role" class="form-select form-select-sm" @disabled($isMe)>
                                                    @foreach($roles as $val => $label)<option value="{{ $val }}" @selected($s->role === $val)>{{ $label }}</option>@endforeach
                                                </select>
                                                @if($isMe)<input type="hidden" name="role" value="{{ $s->role }}">@endif
                                            </div>
                                            <div class="col-md-3"><label class="small fw-bold">Status</label>
                                                <select name="is_active" class="form-select form-select-sm" @disabled($isMe)>
                                                    <option value="1" @selected($s->is_active)>Aktif</option>
                                                    <option value="0" @selected(!$s->is_active)>Nonaktif</option>
                                                </select>
                                                @if($isMe)<input type="hidden" name="is_active" value="1">@endif
                                            </div>
                                            <div class="col-md-2"><button type="submit" class="btn btn-sm btn-primary w-100">Simpan</button></div>
                                        </form>
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
document.querySelectorAll('.js-confirm').forEach(function (form) {
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        Swal.fire({ icon: 'warning', title: 'Yakin?', text: form.dataset.confirm, showCancelButton: true, confirmButtonText: 'Ya, lanjutkan', cancelButtonText: 'Batal', reverseButtons: true })
            .then(function (r) { if (r.isConfirmed) form.submit(); });
    });
});
</script>
@endsection
