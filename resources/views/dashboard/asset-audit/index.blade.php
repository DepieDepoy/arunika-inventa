@extends('dashboard.layouts.wrapper')

@section('title', __('Audit Fisik Aset'))

@section('content')
@php
    $chip = ['in_progress' => 'warn', 'completed' => 'ok', 'cancelled' => 'mute'];
    $canCreate = auth()->user()->hasPermission('asset_audit.create');
    $defaultBranch = old('branch_id', $restricted ?? ($multiBranch ? '' : optional($branches->first())->id));
@endphp
<style>
    .au-chip { display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 99px; font-size: .75rem; font-weight: 700; white-space: nowrap; }
    .au-chip.warn { background: #fff4e0; color: #b46b00; }
    .au-chip.ok { background: #e7f6ee; color: #1a8754; }
    .au-chip.mute { background: #eef0f4; color: #6b7280; }
    .au-bar { height: 7px; border-radius: 7px; background: #e9edf5; overflow: hidden; min-width: 120px; }
    .au-bar span { display: block; height: 100%; background: #3a57e8; }
    .au-mini { font-size: .78rem; color: #6b7280; }
    .au-mini b { color: #111827; }
    .au-intro { border: 0; border-radius: 14px; background: linear-gradient(120deg, #f5f8ff 0%, #ffffff 100%); }
    .au-intro .st { display: flex; gap: 10px; align-items: flex-start; }
    .au-intro .st .n { width: 28px; height: 28px; border-radius: 50%; background: #3a57e8; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0; }
    .au-team { border: 1px solid #e5e8f0; border-radius: 12px; max-height: 260px; overflow-y: auto; }
    .au-team .r { display: flex; gap: 10px; align-items: center; padding: 8px 12px; border-bottom: 1px solid #f0f2f7; flex-wrap: wrap; }
    .au-team .r:last-child { border-bottom: 0; }
    .au-team .r label { flex: 1; min-width: 160px; margin: 0; cursor: pointer; }
    .au-team .r select { max-width: 220px; }
</style>

<div class="content-wrapper">
<div class="container-xxl flex-grow-1 container-p-y">

    <div class="card au-intro mb-4">
        <div class="card-body">
            <h5 class="mb-1"><i class="bi bi-clipboard2-check text-primary me-1"></i> {{ __('Audit Fisik Aset') }}</h5>
            <p class="text-muted mb-3">{{ __('Pastikan aset yang tercatat di sistem benar-benar ada di tempatnya — untuk tutup buku, audit tahunan, atau serah terima gedung.') }}</p>
            <div class="row g-3">
                <div class="col-md-4"><div class="st"><span class="n">1</span><div><b>{{ __('Buat sesi & pilih petugas') }}</b><div class="au-mini">{{ __('Pilih cabang, lokasi, lalu petugas + area masing-masing. Petugas mendapat notifikasi.') }}</div></div></div></div>
                <div class="col-md-4"><div class="st"><span class="n">2</span><div><b>{{ __('Petugas scan dengan akunnya') }}</b><div class="au-mini">{{ __('Pilih lokasi tempat berdiri, scan QR. Bisa beberapa petugas bersamaan.') }}</div></div></div></div>
                <div class="col-md-4"><div class="st"><span class="n">3</span><div><b>{{ __('Selesaikan & unduh laporan') }}</b><div class="au-mini">{{ __('Ditemukan, salah lokasi, beda cabang, tidak ditemukan.') }}</div></div></div></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
            <div>
                <h5 class="mb-0">{{ __('Sesi audit') }}</h5>
                <small class="text-muted">{{ __('Riwayat audit fisik aset.') }}</small>
            </div>
            @if($canCreate)
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAudit"><i class="fa-solid fa-plus"></i> {{ __('Mulai Audit Baru') }}</button>
            @endif
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>{{ __('Audit') }}</th>
                            <th>{{ __('Cakupan') }}</th>
                            <th>{{ __('Progres') }}</th>
                            <th>{{ __('Hasil') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($audits as $a)
                            @php $s = $a->stats; @endphp
                            <tr>
                                <td>
                                    <div class="fw-bold">{{ $a->title }}</div>
                                    <small class="text-muted">{{ $a->audit_no }} · {{ $a->created_at->translatedFormat('d M Y') }} · {{ $a->starter?->name }}
                                        @if($a->assignees_count) · <i class="bi bi-people"></i> petugas @endif</small>
                                </td>
                                <td>
                                    <div>{{ $a->branch?->branch_name ?? 'Semua cabang' }}</div>
                                    <small class="text-muted">{{ $a->location_filter ?: 'Semua lokasi' }}</small>
                                </td>
                                <td>
                                    <div class="au-bar mb-1"><span style="width: {{ $s['percent'] }}%"></span></div>
                                    <span class="au-mini"><b>{{ $s['checked'] }}</b> {{ __('/ :p1 dicek (:p2%)', ['p1' => $s['total'], 'p2' => $s['percent']]) }}</span>
                                </td>
                                <td class="au-mini">
                                    <span class="text-success"><b>{{ $s['found'] }}</b> {{ __('ada') }}</span> ·
                                    <span class="text-danger"><b>{{ $s['missing'] }}</b> {{ __('hilang') }}</span> ·
                                    <span class="text-warning"><b>{{ $s['misplaced'] }}</b> {{ __('salah lokasi') }}</span>
                                    @if($s['other_branch'])· <span style="color:#0e7490"><b>{{ $s['other_branch'] }}</b> {{ __('beda cabang') }}</span>@endif
                                </td>
                                <td><span class="au-chip {{ $chip[$a->status] ?? 'mute' }}">{{ $a->statusLabel() }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('asset-audits.show', $a->id) }}" class="btn btn-sm {{ $a->isOpen() ? 'btn-primary' : 'btn-outline-primary' }}">
                                        {{ $a->isOpen() ? 'Buka & scan' : 'Lihat laporan' }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-5"><i class="bi bi-qr-code-scan fs-2 d-block mb-2"></i>Belum ada audit.@if($canCreate) Klik "Mulai Audit Baru".@endif</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $audits->links() }}
        </div>
    </div>
</div>
</div>

@if($canCreate)
<div class="modal fade" id="modalAudit" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="{{ route('asset-audits.store') }}" id="formAudit">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Mulai Audit Baru') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
                    <div class="mb-3">
                        <label class="form-label">{{ __('Judul audit') }} <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" maxlength="150" required value="{{ old('title', 'Audit fisik ' . now()->translatedFormat('F Y')) }}">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Cabang') }}</label>
                            <select name="branch_id" id="auBranch" class="form-select" {{ $restricted ? 'disabled' : '' }}>
                                @if(!$restricted && $multiBranch)<option value="">{{ __('Semua cabang') }}</option>@endif
                                @foreach($branches as $b)
                                    <option value="{{ $b->id }}" @selected((string) $defaultBranch === (string) $b->id)>{{ $b->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Lokasi') }}</label>
                            <select name="location_id" id="auLocation" class="form-select"></select>
                            <small class="text-muted">{{ __('Pecah audit per lokasi agar lebih ringan. Kelola lokasi di Master Data › Lokasi.') }}</small>
                        </div>
                    </div>

                    <label class="form-label">{{ __('Petugas audit') }} <small class="text-muted">{{ __('(opsional)') }}</small></label>
                    @if($candidates->isEmpty())
                        <div class="alert alert-light border small mb-3">{{ __('Belum ada user dengan izin scan audit. Beri role') }} <b>{{ __('Auditor') }}</b> {{ __('(atau centang izin "Scan Asset Audit") di menu Users / Role.') }}</div>
                    @else
                        <div class="au-team mb-1" id="auTeam">
                            @foreach($candidates as $i => $c)
                                <div class="r" data-branch="{{ $c->branch_id }}" data-head="{{ $c->branch?->is_head_office ? 1 : 0 }}">
                                    <input class="form-check-input" type="checkbox" id="cand{{ $c->id }}" name="assignees[{{ $i }}][user_id]" value="{{ $c->id }}">
                                    <label for="cand{{ $c->id }}">
                                        <b>{{ $c->name }}</b>
                                        <small class="text-muted d-block">{{ $c->role?->role_name }} · {{ $c->branch?->branch_name }}</small>
                                    </label>
                                    <select class="form-select form-select-sm au-area" name="assignees[{{ $i }}][location_id]" disabled>
                                        <option value="">{{ __('Semua lokasi') }}</option>
                                    </select>
                                </div>
                            @endforeach
                        </div>
                        <small class="text-muted d-block mb-3">{{ __('Bila petugas dipilih, hanya petugas (dan pengelola audit) yang bisa scan. Pilih area agar tiap petugas punya daftar "Tugas saya" sendiri.') }}</small>
                    @endif

                    <div class="mb-1">
                        <label class="form-label">{{ __('Catatan') }}</label>
                        <textarea name="notes" class="form-control" rows="2" maxlength="1000" placeholder="Contoh: audit akhir tahun untuk laporan keuangan">{{ old('notes') }}</textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('Batal') }}</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-play-fill"></i> {{ __('Mulai Audit') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endif
@endsection

@section('js')
@if($canCreate)
<script>
(function () {
    const locations = @json($locations);
    const esc = s => $('<div>').text(s ?? '').html();
    const branchSel = document.getElementById('auBranch');
    const locSel = document.getElementById('auLocation');

    function options(branchId, emptyLabel) {
        let html = '<option value="">' + emptyLabel + '</option>';
        (locations[branchId] || []).forEach(l => html += '<option value="' + l.id + '">' + esc(l.name) + '</option>');
        return html;
    }

    function refresh() {
        const b = branchSel.value;
        locSel.innerHTML = b ? options(b, 'Semua lokasi') : '<option value="">Semua lokasi (pilih cabang untuk memilih lokasi)</option>';
        locSel.disabled = !b;
        // Petugas: tampilkan user cabang terpilih + Kantor Pusat (bisa ditugaskan ke cabang).
        document.querySelectorAll('#auTeam .r').forEach(r => {
            const show = !b || r.dataset.branch === b || r.dataset.head === '1';
            r.hidden = !show;
            if (!show) { r.querySelector('input').checked = false; }
            const area = r.querySelector('.au-area');
            area.innerHTML = b ? options(b, 'Semua lokasi') : '<option value="">Semua lokasi</option>';
            area.disabled = !r.querySelector('input').checked || !b;
        });
    }

    branchSel.addEventListener('change', refresh);
    locSel.addEventListener('change', function () {
        // Audit satu lokasi: area petugas otomatis lokasi tersebut.
        document.querySelectorAll('#auTeam .au-area').forEach(a => a.value = locSel.value);
    });
    document.querySelectorAll('#auTeam input[type=checkbox]').forEach(cb => cb.addEventListener('change', function () {
        const area = this.closest('.r').querySelector('.au-area');
        area.disabled = !this.checked || !branchSel.value;
    }));
    document.getElementById('formAudit').addEventListener('submit', function () {
        // Kirim cabang walau select dikunci (user cabang).
        if (branchSel.disabled) {
            const h = document.createElement('input');
            h.type = 'hidden'; h.name = 'branch_id'; h.value = branchSel.value; this.appendChild(h);
        }
        // Baris petugas yang tidak dicentang tidak dikirim.
        document.querySelectorAll('#auTeam .r').forEach(r => {
            if (!r.querySelector('input').checked) r.querySelectorAll('input,select').forEach(el => el.disabled = true);
        });
    });
    refresh();
})();
</script>
@endif
@if($errors->any())
<script>new bootstrap.Modal(document.getElementById('modalAudit')).show();</script>
@endif
@endsection
