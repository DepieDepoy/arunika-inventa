@extends('backoffice.layouts.app')

@section('title', 'Permintaan Hapus Akun')

@section('content')
@include('backoffice.partials.style')
@php
    $M = \App\Models\AccountDeletionRequest::class;
    $chip = ['pending' => 'background:#fef3c7;color:#92400e', 'done' => 'background:#dcfce7;color:#166534', 'rejected' => 'background:#fee2e2;color:#991b1b'];
@endphp

<div class="container-xxl flex-grow-1 container-p-y">
    <div class="cms-card p-3 p-md-4 mb-4">
        <span class="cms-badge"><i class="bi bi-person-x"></i> Privasi &amp; UU PDP</span>
        <h4 class="mb-1 mt-2">Permintaan hapus akun</h4>
        <div class="text-muted small">
            Dari aplikasi mobile, dashboard web, dan formulir publik
            <a href="{{ route('account-deletion.create') }}" target="_blank">/hapus-akun</a>.
            Wajib diselesaikan maksimal <b>30 hari</b>. Akun biasa yang menghapus dirinya sendiri langsung selesai otomatis.
        </div>
        <div class="d-flex gap-2 flex-wrap mt-3">
            @foreach($M::STATUSES as $k => $label)
                <a href="{{ route('backoffice.account-deletions.index', ['status' => $k]) }}" class="cms-chip" style="font-size:.78rem;padding:6px 12px;{{ $chip[$k] }};{{ $status === $k ? 'outline:2px solid currentColor' : '' }}">{{ $label }} · {{ $counts[$k] ?? 0 }}</a>
            @endforeach
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success"><i class="bi bi-check2-circle me-1"></i> {{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <div class="cms-card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr class="small text-muted text-uppercase"><th>Diajukan</th><th>Akun</th><th>Cakupan</th><th>Sumber</th><th>Alasan</th><th>{{ $status === 'pending' ? 'Tindakan' : 'Hasil' }}</th></tr></thead>
                <tbody>
                    @forelse($requests as $req)
                        <tr>
                            <td class="small text-nowrap">{{ $req->created_at?->locale('id')->translatedFormat('d M Y H:i') }}<br><span class="text-muted">{{ $req->created_at?->locale('id')->diffForHumans() }}</span></td>
                            <td class="small">
                                <b class="text-dark">{{ $req->name ?? '—' }}</b><br>{{ $req->email }}
                                @if($req->company)<br><a href="{{ route('backoffice.customers.show', $req->company_id) }}">{{ $req->company->company_name }}</a>@endif
                                @if(!$req->user_id)<br><span class="cms-chip">Email tidak terdaftar</span>@endif
                            </td>
                            <td><span class="cms-chip {{ $req->scope === 'company' ? '' : 'cms-chip-blue' }}" style="{{ $req->scope === 'company' ? 'background:#fee2e2;color:#991b1b' : '' }}">{{ $M::SCOPES[$req->scope] ?? $req->scope }}</span></td>
                            <td class="small">{{ $M::SOURCES[$req->source] ?? $req->source }}</td>
                            <td class="small" style="max-width:260px">{{ $req->reason ?: '—' }}</td>
                            <td class="small" style="min-width:260px">
                                @if($req->status === 'pending')
                                    <form method="POST" action="{{ route('backoffice.account-deletions.process', $req->id) }}" class="d-flex gap-1 mb-1 js-confirm" data-msg="{{ $req->scope === 'company' ? 'Perusahaan akan DITANGGUHKAN dan data pribadi SELURUH user-nya dihapus. Lanjutkan?' : 'Data pribadi akun ini akan dihapus permanen. Lanjutkan?' }}">
                                        @csrf
                                        <input type="text" name="note" class="form-control form-control-sm" placeholder="Catatan (opsional)" maxlength="500">
                                        <button class="btn btn-sm btn-danger text-nowrap">Proses hapus</button>
                                    </form>
                                    <form method="POST" action="{{ route('backoffice.account-deletions.reject', $req->id) }}" class="d-flex gap-1">
                                        @csrf
                                        <input type="text" name="note" class="form-control form-control-sm" placeholder="Alasan penolakan" maxlength="500" required>
                                        <button class="btn btn-sm btn-outline-secondary">Tolak</button>
                                    </form>
                                @else
                                    <span class="cms-chip" style="{{ $chip[$req->status] }}">{{ $M::STATUSES[$req->status] }}</span>
                                    {{ $req->processed_at?->locale('id')->translatedFormat('d M Y') }}{{ $req->processor ? ' · ' . $req->processor->name : '' }}
                                    @if($req->note)<div class="text-muted mt-1">{{ $req->note }}</div>@endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5">Tidak ada permintaan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($requests->hasPages())<div class="p-3">{{ $requests->links() }}</div>@endif
    </div>
</div>
@endsection

@section('js')
<script>
document.querySelectorAll('.js-confirm').forEach(function (f) {
    f.addEventListener('submit', function (e) {
        if (f.dataset.ok) return;
        e.preventDefault();
        Swal.fire({ icon: 'warning', title: 'Proses hapus akun?', text: f.dataset.msg, showCancelButton: true, confirmButtonText: 'Ya, proses', cancelButtonText: 'Batal', confirmButtonColor: '#dc2626', reverseButtons: true })
            .then(function (r) { if (r.isConfirmed) { f.dataset.ok = 1; f.submit(); } });
    });
});
</script>
@endsection
