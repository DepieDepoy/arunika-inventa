@extends('dashboard.layouts.wrapper')

@section('title', 'Audit Fisik Aset')

@section('content')
@php
    $chip = ['in_progress' => 'warn', 'completed' => 'ok', 'cancelled' => 'mute'];
    $canCreate = auth()->user()->hasPermission('asset_audit.create');
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
</style>

<div class="content-wrapper">
<div class="container-xxl flex-grow-1 container-p-y">

    <div class="card au-intro mb-4">
        <div class="card-body">
            <h5 class="mb-1"><i class="bi bi-clipboard2-check text-primary me-1"></i> Audit Fisik Aset</h5>
            <p class="text-muted mb-3">Pastikan aset yang tercatat di sistem benar-benar ada di tempatnya — cocok untuk tutup buku, audit tahunan, atau serah terima gedung.</p>
            <div class="row g-3">
                <div class="col-md-4"><div class="st"><span class="n">1</span><div><b>Buat sesi audit</b><div class="au-mini">Pilih cabang &amp; lokasi; daftar aset terisi otomatis.</div></div></div></div>
                <div class="col-md-4"><div class="st"><span class="n">2</span><div><b>Keliling &amp; scan QR</b><div class="au-mini">Pakai kamera HP, scanner USB, atau ketik kode aset.</div></div></div></div>
                <div class="col-md-4"><div class="st"><span class="n">3</span><div><b>Selesaikan &amp; unduh laporan</b><div class="au-mini">Ditemukan, tidak ditemukan, salah lokasi, rusak.</div></div></div></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
            <div>
                <h5 class="mb-0">Sesi audit</h5>
                <small class="text-muted">Riwayat audit fisik aset perusahaan.</small>
            </div>
            @if($canCreate)
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAudit"><i class="fa-solid fa-plus"></i> Mulai Audit Baru</button>
            @endif
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Audit</th>
                            <th>Cakupan</th>
                            <th>Progres</th>
                            <th>Hasil</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($audits as $a)
                            @php $s = $a->stats; @endphp
                            <tr>
                                <td>
                                    <div class="fw-bold">{{ $a->title }}</div>
                                    <small class="text-muted">{{ $a->audit_no }} · {{ $a->created_at->translatedFormat('d M Y') }} · {{ $a->starter?->name }}</small>
                                </td>
                                <td>
                                    <div>{{ $a->branch?->branch_name ?? 'Semua cabang' }}</div>
                                    <small class="text-muted">{{ $a->location_filter ?: 'Semua lokasi' }}</small>
                                </td>
                                <td>
                                    <div class="au-bar mb-1"><span style="width: {{ $s['percent'] }}%"></span></div>
                                    <span class="au-mini"><b>{{ $s['checked'] }}</b> / {{ $s['total'] }} dicek ({{ $s['percent'] }}%)</span>
                                </td>
                                <td class="au-mini">
                                    <span class="text-success"><b>{{ $s['found'] }}</b> ada</span> ·
                                    <span class="text-danger"><b>{{ $s['missing'] }}</b> hilang</span> ·
                                    <span class="text-warning"><b>{{ $s['misplaced'] }}</b> salah lokasi</span>
                                </td>
                                <td><span class="au-chip {{ $chip[$a->status] ?? 'mute' }}">{{ $a->statusLabel() }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('asset-audits.show', $a->id) }}" class="btn btn-sm {{ $a->isOpen() ? 'btn-primary' : 'btn-outline-primary' }}">
                                        {{ $a->isOpen() ? 'Lanjutkan scan' : 'Lihat laporan' }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-5"><i class="bi bi-qr-code-scan fs-2 d-block mb-2"></i>Belum ada audit. Klik "Mulai Audit Baru".</td></tr>
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
    <div class="modal-dialog">
        <form method="POST" action="{{ route('asset-audits.store') }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Mulai Audit Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
                    <div class="mb-3">
                        <label class="form-label">Judul audit <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" maxlength="150" required value="{{ old('title', 'Audit fisik ' . now()->translatedFormat('F Y')) }}">
                    </div>
                    @if($multiBranch || $restricted)
                        <div class="mb-3">
                            <label class="form-label">Cabang</label>
                            <select name="branch_id" class="form-select" {{ $restricted ? 'disabled' : '' }}>
                                @unless($restricted)<option value="">Semua cabang</option>@endunless
                                @foreach($branches as $b)
                                    <option value="{{ $b->id }}" @selected(old('branch_id', $restricted) == $b->id)>{{ $b->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label">Lokasi <small class="text-muted">(opsional)</small></label>
                        <select name="location_filter" class="form-select">
                            <option value="">Semua lokasi</option>
                            @foreach($locations as $loc)
                                <option value="{{ $loc }}" @selected(old('location_filter') === $loc)>{{ $loc }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Pilih lokasi bila audit dilakukan per ruangan / gedung.</small>
                    </div>
                    <div class="mb-1">
                        <label class="form-label">Catatan</label>
                        <textarea name="notes" class="form-control" rows="2" maxlength="1000" placeholder="Contoh: audit akhir tahun untuk laporan keuangan">{{ old('notes') }}</textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-play-fill"></i> Mulai Audit</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endif
@endsection

@section('js')
@if($errors->any())
<script>new bootstrap.Modal(document.getElementById('modalAudit')).show();</script>
@endif
@endsection
