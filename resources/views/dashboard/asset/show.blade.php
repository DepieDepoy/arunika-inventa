@extends('dashboard.layouts.wrapper')

@section('title', 'Detail Aset')

@section('content')

@php
    $rp = fn ($v) => 'Rp' . number_format((float) $v, 0, ',', '.');
    $date = fn ($v) => $v ? \Carbon\Carbon::parse($v)->translatedFormat('d M Y') : null;
    $holder = $asset->responsibleUser;
    $since = $asset->currentAssignment?->start_at;
    $mainPhoto = $asset->photos->first();
    $initials = fn ($name) => collect(explode(' ', trim((string) $name)))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
    $warrantyActive = $asset->warranty_end && $asset->warranty_end->copy()->endOfDay()->isFuture();
    $next = $asset->next_maintenance_date ? \Carbon\Carbon::parse($asset->next_maintenance_date) : null;
    $nextState = !$next ? null : ($next->isPast() && !$next->isToday() ? ['Terlambat', 'ad-chip-red'] : ($next->isToday() ? ['Hari ini', 'ad-chip-amber'] : ($next->diffInDays(now()) <= 7 ? ['Minggu ini', 'ad-chip-blue'] : ['Terjadwal', 'ad-chip-teal'])));
    $activeRequest = $asset->activeMaintenanceRequest;
    $encId = encryptId($asset->id);
    $statusLabels = ['scheduled' => 'Terjadwal', 'in_progress' => 'Dikerjakan', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan'];
    $typeLabels = ['preventive' => 'Preventif', 'corrective' => 'Perbaikan', 'inspection' => 'Inspeksi', 'calibration' => 'Kalibrasi'];
    $assignLabels = ['assign' => 'Diserahkan ke', 'transfer' => 'Dipindahkan ke', 'return' => 'Dikembalikan oleh'];
    $disposed = $asset->isDisposed();
    $canEdit = auth()->user()->hasPermission('asset.edit');
    $canMove = auth()->user()->hasPermission('asset.move');
    $canDispose = auth()->user()->hasPermission('asset.dispose');
    $warrantyChip = ['active' => 'ad-chip-teal', 'expiring' => 'ad-chip-amber', 'expired' => 'ad-chip-gray', 'none' => 'ad-chip-gray'][$warranty['state']];
    $warrantyShort = ['active' => 'aktif', 'expiring' => ($warranty['days_left'] === 0 ? 'habis hari ini' : $warranty['days_left'] . ' hari lagi'), 'expired' => 'habis', 'none' => ''][$warranty['state']];
    $initialTab = in_array(request('tab'), ['finance', 'maintenance', 'assignment', 'location', 'disposal', 'docs'], true) ? request('tab') : 'finance';
@endphp

<style>
    .ad-wrap { --ad-line: rgba(15,27,45,.08); --ad-muted: #8592a6; --ad-ink: #0f1b2d; }
    .ad-head { display:flex; justify-content:space-between; align-items:flex-start; gap:16px; flex-wrap:wrap; margin-bottom:1.25rem; }
    .ad-head h4 { margin:0; font-weight:800; letter-spacing:-.02em; }
    .ad-sub { color:var(--ad-muted); font-size:.88rem; margin-top:4px; display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
    .ad-actions { display:flex; gap:8px; flex-wrap:wrap; }
    .ad-actions .btn { border-radius:10px; font-weight:600; }
    .ad-card { background:var(--bs-body-bg); border:1px solid var(--ad-line); border-radius:16px; box-shadow:0 1px 2px rgba(15,27,45,.04), 0 8px 24px -16px rgba(15,27,45,.16); }
    .ad-photo { height:260px; border-radius:12px; overflow:hidden; background:linear-gradient(135deg,#dbeafe,#ccfbf1); display:grid; place-items:center; color:#1f5fbf; font-size:4rem; position:relative; }
    .ad-photo img { width:100%; height:100%; object-fit:cover; }
    .ad-photo .ad-count { position:absolute; right:12px; bottom:12px; background:rgba(11,36,71,.75); color:#fff; font-size:.75rem; font-weight:700; padding:4px 10px; border-radius:999px; }
    .ad-thumbs { display:flex; gap:8px; margin-top:10px; overflow-x:auto; }
    .ad-thumbs a { flex:none; width:64px; height:48px; border-radius:9px; overflow:hidden; border:2px solid transparent; }
    .ad-thumbs a.on { border-color:#1f5fbf; }
    .ad-thumbs img { width:100%; height:100%; object-fit:cover; }
    .ad-tiles { display:grid; grid-template-columns:repeat(3,1fr); gap:10px; }
    .ad-tile { background:var(--bs-tertiary-bg, #f4f7fb); border-radius:11px; padding:10px 12px; min-width:0; }
    .ad-tile small { display:block; font-size:.68rem; font-weight:700; color:var(--ad-muted); text-transform:uppercase; letter-spacing:.04em; }
    .ad-tile b { display:block; color:var(--ad-ink); font-size:.9rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    body.dark .ad-tile b, body.dark .ad-card b { color:#fff; }
    .ad-tile .muted { color:var(--ad-muted); font-weight:500; }
    .ad-chip { display:inline-flex; align-items:center; gap:5px; font-size:.72rem; font-weight:800; padding:3px 10px; border-radius:999px; }
    .ad-chip-teal { background:#ccfbf1; color:#0f766e; } .ad-chip-red { background:#fee2e2; color:#b91c1c; }
    .ad-chip-amber { background:#fef3c7; color:#92400e; } .ad-chip-blue { background:#dbeafe; color:#1d4ed8; }
    .ad-chip-gray { background:#eef2f7; color:#4b5a6e; }
    .ad-qr { text-align:center; padding:22px 18px 16px; }
    .ad-qr-box { width:168px; height:168px; margin:0 auto 12px; padding:10px; border-radius:14px; border:1px solid var(--ad-line); background:#fff; display:grid; place-items:center; }
    .ad-qr-box img, .ad-qr-box canvas { width:146px !important; height:146px !important; }
    .ad-qr-code { font-weight:800; letter-spacing:.04em; color:var(--ad-ink); font-size:1.05rem; }
    .ad-row { display:flex; align-items:center; gap:12px; padding:14px 18px; border-top:1px solid var(--ad-line); font-size:.86rem; }
    .ad-ava { width:38px; height:38px; border-radius:50%; flex:none; display:grid; place-items:center; color:#fff; font-weight:800; font-size:.8rem; background:linear-gradient(135deg,#1f5fbf,#14b8a6); }
    .ad-row .ic { width:38px; height:38px; border-radius:11px; flex:none; display:grid; place-items:center; background:#e8f0fd; color:#1f5fbf; font-size:1.05rem; }
    .ad-row small { color:var(--ad-muted); display:block; }
    .ad-progress { height:8px; border-radius:99px; background:#eef2f7; overflow:hidden; }
    .ad-progress span { display:block; height:100%; background:linear-gradient(90deg,#1f5fbf,#14b8a6); border-radius:99px; }
    .ad-tabs { display:flex; gap:6px; flex-wrap:wrap; border-bottom:1px solid var(--ad-line); padding:0 18px; }
    .ad-tabs button { border:0; background:none; font-weight:700; font-size:.88rem; color:var(--ad-muted); padding:14px 12px; border-bottom:2px solid transparent; margin-bottom:-1px; }
    .ad-tabs button.active { color:#1f5fbf; border-color:#1f5fbf; }
    .ad-pane { display:none; padding:20px; } .ad-pane.active { display:block; }
    .ad-tl { list-style:none; margin:0; padding:0; position:relative; }
    .ad-tl::before { content:""; position:absolute; left:15px; top:8px; bottom:8px; width:2px; background:var(--ad-line); }
    .ad-tl li { display:flex; gap:14px; padding:9px 0; position:relative; }
    .ad-tl .dot { width:32px; height:32px; border-radius:50%; flex:none; display:grid; place-items:center; font-size:.85rem; position:relative; z-index:1; border:3px solid var(--bs-body-bg); }
    .ad-tl b { color:var(--ad-ink); font-size:.9rem; }
    .ad-tl small { color:var(--ad-muted); display:block; font-size:.78rem; }
    .ad-doc { display:flex; align-items:center; gap:12px; padding:12px 14px; border:1px solid var(--ad-line); border-radius:12px; margin-bottom:8px; }
    .ad-doc i.bi { font-size:1.4rem; color:#1f5fbf; }
    .ad-doc .name { flex:1; min-width:0; font-weight:600; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .ad-empty { text-align:center; color:var(--ad-muted); padding:28px 10px; font-size:.9rem; }
    .ad-empty i { font-size:1.8rem; display:block; margin-bottom:6px; opacity:.6; }
    .ad-disposed { border:1px solid #fecaca; background:#fff5f5; border-radius:16px; padding:16px 18px; margin-bottom:1rem; }
    .ad-disposed-head { display:flex; gap:12px; align-items:center; }
    .ad-disposed-head .ic { width:40px; height:40px; border-radius:12px; flex:none; display:grid; place-items:center; background:#fee2e2; color:#b91c1c; font-size:1.15rem; }
    .ad-disposed-head b { color:#7f1d1d; display:block; }
    .ad-disposed-head small { color:#9f5050; }
    .ad-disposed-grid { display:grid; grid-template-columns:repeat(4, minmax(0,1fr)); gap:10px; margin-top:14px; }
    .ad-disposed-grid > div, .ad-disposed-foot > div { background:#fff; border-radius:11px; padding:9px 12px; min-width:0; }
    .ad-disposed-grid small, .ad-disposed-foot small { display:block; font-size:.68rem; font-weight:700; color:#9f5050; text-transform:uppercase; letter-spacing:.04em; }
    .ad-disposed-grid b { font-variant-numeric:tabular-nums; }
    .ad-disposed-foot { display:flex; gap:10px; align-items:center; margin-top:10px; flex-wrap:wrap; font-size:.88rem; }
    body.dark .ad-disposed { background:rgba(185,28,28,.12); border-color:rgba(248,113,113,.35); }
    body.dark .ad-disposed-grid > div, body.dark .ad-disposed-foot > div { background:rgba(0,0,0,.2); }
    body.dark .ad-disposed-head b { color:#fecaca; }
    @media (max-width: 767.98px) { .ad-disposed-grid { grid-template-columns:repeat(2, minmax(0,1fr)); } }
    .ad-alert { display:flex; gap:12px; align-items:center; background:#fff8e6; border:1px solid #fde68a; border-radius:12px; padding:12px 16px; margin-bottom:1rem; font-size:.88rem; color:#92400e; }
    @media (max-width: 991.98px) { .ad-tiles { grid-template-columns:repeat(2,1fr); } }
    @media (max-width: 575.98px) { .ad-tiles { grid-template-columns:1fr 1fr; } .ad-photo { height:200px; } }
</style>

<div class="container-xxl flex-grow-1 container-p-y ad-wrap">

    {{-- HEADER --}}
    <div class="ad-head ad-card p-3 p-md-4">
        <div>
            <a href="{{ route('assets.index') }}" class="text-muted small d-inline-flex align-items-center gap-1 mb-1">
                <i class="bi bi-arrow-left"></i> Data Aset
            </a>
            <h4>{{ $asset->asset_name }}</h4>
            <div class="ad-sub">
                <span class="fw-bold">{{ $asset->asset_code }}</span>
                <span>·</span>
                <span>{{ $asset->category->category_name ?? 'Tanpa kategori' }}@if($asset->subCategory) › {{ $asset->subCategory->sub_category_name }}@endif</span>
                @if($disposed)
                    <span class="ad-chip ad-chip-red"><i class="bi bi-archive"></i>Dihapuskan</span>
                @elseif($asset->status == 1)
                    <span class="ad-chip ad-chip-teal"><i class="bi bi-circle-fill" style="font-size:.45rem"></i>Aktif</span>
                @else
                    <span class="ad-chip ad-chip-gray"><i class="bi bi-circle-fill" style="font-size:.45rem"></i>Tidak aktif</span>
                @endif
                @if($asset->asset_condition)
                    <span class="ad-chip ad-chip-blue">{{ $asset->asset_condition === 'new' ? 'Baru' : 'Bekas' }}</span>
                @endif
            </div>
        </div>
        <div class="ad-actions">
            @if($disposed)
                @if($canDispose && $activeDisposal)
                    <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelDisposalModal">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Batalkan penghapusan
                    </button>
                @endif
            @else
                @if($canEdit)
                    <a href="{{ route('assets.assignment.index', $encId) }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left-right me-1"></i> {{ $holder ? 'Pindahkan / Kembalikan' : 'Serahkan' }}
                    </a>
                @endif
                @if($canMove)
                    <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#moveLocationModal">
                        <i class="bi bi-geo-alt me-1"></i> Pindah lokasi
                    </button>
                @endif
                @if($canEdit)
                    <a href="{{ route('assets.edit', $encId) }}" class="btn btn-outline-primary">
                        <i class="bi bi-pencil me-1"></i> Ubah
                    </a>
                @endif
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#printQrModal" onclick="generateAssetQr()">
                    <i class="bi bi-qr-code me-1"></i> Cetak QR
                </button>
                @if($canDispose)
                    <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#disposeModal" title="Rusak, hilang, dijual, dihibahkan, dikembalikan ke vendor">
                        <i class="bi bi-archive me-1"></i> Hapuskan
                    </button>
                @endif
            @endif
        </div>
    </div>

    @if($disposed && $activeDisposal)
        @php $gl = $activeDisposal->gain_loss !== null ? (float) $activeDisposal->gain_loss : null; @endphp
        <div class="ad-disposed">
            <div class="ad-disposed-head">
                <span class="ic"><i class="bi bi-archive"></i></span>
                <div>
                    <b>Aset ini telah dihapuskan &middot; {{ $activeDisposal->typeLabel() }}</b>
                    <small>
                        {{ $activeDisposal->disposal_date->translatedFormat('d M Y') }}
                        @if($activeDisposal->creator) &middot; dicatat oleh {{ $activeDisposal->creator->name }}@endif
                        @if($activeDisposal->holder) &middot; pemegang terakhir {{ $activeDisposal->holder->name }}@endif
                        @if($activeDisposal->previous_location) &middot; lokasi terakhir {{ $activeDisposal->previous_location }}@endif
                    </small>
                </div>
            </div>
            <div class="ad-disposed-grid">
                <div><small>Harga perolehan</small><b>{{ $activeDisposal->purchase_price !== null ? $rp($activeDisposal->purchase_price) : '—' }}</b></div>
                <div><small>Nilai buku saat dihapuskan</small><b>{{ $activeDisposal->book_value !== null ? $rp($activeDisposal->book_value) : '—' }}</b></div>
                <div><small>Hasil penjualan / ganti</small><b>{{ $rp($activeDisposal->proceeds) }}</b></div>
                <div><small>{{ $gl !== null && $gl > 0 ? 'Laba penghapusan' : 'Kerugian penghapusan' }}</small>
                    <b class="{{ $gl !== null && $gl < 0 ? 'text-danger' : ($gl > 0 ? 'text-success' : '') }}">{{ $gl === null ? '—' : ($gl < 0 ? '(' . $rp(abs($gl)) . ')' : $rp($gl)) }}</b>
                </div>
            </div>
            @if($activeDisposal->notes || $activeDisposal->document_path)
                <div class="ad-disposed-foot">
                    @if($activeDisposal->notes)<div class="flex-grow-1"><small>Catatan</small>{{ $activeDisposal->notes }}</div>@endif
                    @if($activeDisposal->document_path)
                        <a href="{{ asset('storage/' . $activeDisposal->document_path) }}" target="_blank" class="btn btn-sm btn-outline-danger"><i class="bi bi-file-earmark-text me-1"></i>Berita acara</a>
                    @endif
                </div>
            @endif
        </div>
    @endif

    @if($activeRequest)
        <div class="ad-alert">
            <i class="bi bi-tools fs-5"></i>
            <div>
                Ada request <strong>{{ strtolower(\App\Services\Maintenance\MaintenanceRequestNotifier::TYPE_LABELS[$activeRequest->request_type] ?? $activeRequest->request_type) }}</strong> yang
                {{ $activeRequest->status === 'pending' ? 'menunggu diambil teknisi' : 'sedang dikerjakan oleh ' . ($activeRequest->handler->name ?? 'teknisi') }}:
                “{{ \Illuminate\Support\Str::limit($activeRequest->description, 90) }}”
            </div>
        </div>
    @endif

    <div class="row g-4">

        {{-- KIRI: FOTO + INFO --}}
        <div class="col-lg-8">
            <div class="ad-card p-3 p-md-4 h-100">
                <div class="row g-4">
                    <div class="col-md-5">
                        <div class="ad-photo" id="adMainPhoto">
                            @if($mainPhoto)
                                <img src="{{ asset('storage/' . $mainPhoto->file_path) }}" alt="{{ $asset->asset_name }}">
                                @if($asset->photos->count() > 1)<span class="ad-count"><i class="bi bi-images me-1"></i>{{ $asset->photos->count() }} foto</span>@endif
                            @else
                                <i class="bi bi-box-seam"></i>
                            @endif
                        </div>
                        @if($asset->photos->count() > 1)
                            <div class="ad-thumbs">
                                @foreach($asset->photos as $photo)
                                    <a href="{{ asset('storage/' . $photo->file_path) }}" class="{{ $loop->first ? 'on' : '' }}" data-photo>
                                        <img src="{{ asset('storage/' . $photo->file_path) }}" alt="">
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    <div class="col-md-7">
                        <div class="ad-tiles">
                            <div class="ad-tile"><small>Vendor</small><b title="{{ $asset->vendor->vendor_name ?? '' }}">{{ $asset->vendor->vendor_name ?? '—' }}</b></div>
                            <div class="ad-tile"><small>Tanggal beli</small><b>{{ $date($asset->purchase_date) ?? '—' }}</b></div>
                            <div class="ad-tile"><small>Harga beli</small><b>{{ $asset->purchase_price ? $rp($asset->purchase_price) : '—' }}</b></div>
                            @if($disposed && $activeDisposal)
                                <div class="ad-tile" title="Nilai buku dibekukan pada tanggal penghapusan"><small>Nilai buku (saat dihapuskan)</small><b>{{ $activeDisposal->book_value !== null ? $rp($activeDisposal->book_value) : '—' }}</b></div>
                            @else
                                <div class="ad-tile"><small>Nilai buku</small><b>{{ $bookValue ? $rp($bookValue['value']) : '—' }}</b></div>
                            @endif
                            <div class="ad-tile"><small>Garansi s/d</small>
                                <b title="{{ $warranty['label'] }}">{{ $date($asset->warranty_end) ?? '—' }}
                                    @if($asset->warranty_end)<span class="ad-chip {{ $warrantyChip }} ms-1" style="font-size:.6rem">{{ $warrantyShort }}</span>@endif
                                </b>
                            </div>
                            <div class="ad-tile"><small>Lokasi</small><b title="{{ $asset->location }}">{{ $asset->location ?: '—' }}</b></div>
                            @if($asset->branch && \App\Support\BranchAccess::hasMultiple((int) $asset->company_id))
                                <div class="ad-tile"><small>Cabang</small><b title="{{ $asset->branch->branch_name }}">{{ $asset->branch->branch_name }}</b></div>
                            @endif
                            <div class="ad-tile"><small>Merek</small><b>{{ $asset->brand ?: '—' }}</b></div>
                            <div class="ad-tile"><small>Model</small><b title="{{ $asset->model }}">{{ $asset->model ?: '—' }}</b></div>
                            <div class="ad-tile"><small>Nomor seri</small><b title="{{ $asset->serial_number }}">{{ $asset->serial_number ?: '—' }}</b></div>
                        </div>
                        @if($asset->description)
                            <div class="ad-tile mt-2" style="white-space:normal">
                                <small>Deskripsi</small><div class="small" style="color:inherit">{{ $asset->description }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- KANAN: QR + PEMEGANG + RIWAYAT --}}
        <div class="col-lg-4">
            <div class="ad-card h-100">
                <div class="ad-qr">
                    <div class="ad-qr-box" id="assetQrPreview"></div>
                    <div class="ad-qr-code">{{ $asset->asset_code }}</div>
                    <small class="text-muted">Scan untuk lihat detail &amp; lapor kerusakan</small>
                </div>
                <div class="ad-row">
                    @if($holder)
                        <span class="ad-ava">{{ $initials($holder->name) }}</span>
                        <div>
                            <b>{{ $holder->name }}</b>
                            <small>Pemegang{{ $since ? ' sejak ' . $since->translatedFormat('d M Y') : '' }}</small>
                        </div>
                    @else
                        <span class="ic"><i class="bi bi-person-dash"></i></span>
                        <div><b>Belum diserahkan</b><small>Aset belum dipegang siapa pun</small></div>
                    @endif
                </div>
                <div class="ad-row">
                    <span class="ic"><i class="bi bi-clock-history"></i></span>
                    <div>
                        <small>Riwayat</small>
                        <b>{{ $asset->assignments_count }} penugasan · {{ $maintenanceStats['count'] }} maintenance · {{ $rp($maintenanceStats['cost']) }}</b>
                    </div>
                </div>
                <div class="ad-row">
                    <span class="ic"><i class="bi bi-calendar2-check"></i></span>
                    <div>
                        <small>Maintenance berikutnya</small>
                        @if($next)
                            <b>{{ $next->translatedFormat('d M Y') }}</b>
                            <span class="ad-chip {{ $nextState[1] }} ms-1">{{ $nextState[0] }}</span>
                        @else
                            <b class="muted">Tidak dijadwalkan</b>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- TAB DETAIL --}}
        <div class="col-12">
            <div class="ad-card">
                <div class="ad-tabs" role="tablist">
                    <button type="button" data-ad-tab="finance"><i class="bi bi-cash-stack me-1"></i>Pembelian &amp; Penyusutan</button>
                    <button type="button" data-ad-tab="maintenance"><i class="bi bi-tools me-1"></i>Maintenance</button>
                    <button type="button" data-ad-tab="assignment"><i class="bi bi-arrow-left-right me-1"></i>Penugasan</button>
                    <button type="button" data-ad-tab="location"><i class="bi bi-geo-alt me-1"></i>Lokasi ({{ $locationHistories->count() }})</button>
                    @if($disposals->isNotEmpty())
                        <button type="button" data-ad-tab="disposal"><i class="bi bi-archive me-1"></i>Penghapusan</button>
                    @endif
                    <button type="button" data-ad-tab="docs"><i class="bi bi-paperclip me-1"></i>Dokumen ({{ $asset->documents->count() }})</button>
                </div>

                {{-- Pembelian & penyusutan --}}
                <div class="ad-pane" data-ad-pane="finance">
                    <div class="ad-tiles mb-3">
                        <div class="ad-tile"><small>No. invoice / pembelian</small><b>{{ $asset->purchase_invoice ?: '—' }}</b></div>
                        <div class="ad-tile"><small>Metode penyusutan</small><b>{{ $asset->depreciation_method === 'straight_line' ? 'Garis lurus' : '—' }}</b></div>
                        <div class="ad-tile"><small>Umur manfaat</small><b>{{ $asset->useful_life ? $asset->useful_life . ' tahun' : '—' }}</b></div>
                        <div class="ad-tile"><small>Nilai residu</small><b>{{ $asset->residual_value ? $rp($asset->residual_value) : '—' }}</b></div>
                        <div class="ad-tile"><small>Mulai penyusutan</small><b>{{ $date($asset->depreciation_start_date) ?? '—' }}</b></div>
                        <div class="ad-tile"><small>Periode garansi</small><b>@if($asset->warranty_start || $asset->warranty_end){{ $date($asset->warranty_start) ?? '?' }} – {{ $date($asset->warranty_end) ?? '?' }}@else—@endif</b></div>
                    </div>
                    @if($bookValue)
                        <div class="d-flex justify-content-between small mb-1">
                            <span>Sudah disusutkan <strong>{{ $rp($bookValue['depreciated']) }}</strong></span>
                            <span class="text-muted">{{ number_format($bookValue['percent'], 1, ',', '.') }}% dari umur manfaat</span>
                        </div>
                        <div class="ad-progress"><span style="width:{{ min(100, $bookValue['percent']) }}%"></span></div>
                    @else
                        <div class="text-muted small"><i class="bi bi-info-circle me-1"></i>Isi harga beli, umur manfaat, dan metode penyusutan untuk melihat nilai buku.</div>
                    @endif
                    @if($asset->warranty_note)
                        <div class="ad-tile mt-3" style="white-space:normal"><small>Catatan garansi</small><div class="small">{{ $asset->warranty_note }}</div></div>
                    @endif
                </div>

                {{-- Maintenance --}}
                <div class="ad-pane" data-ad-pane="maintenance">
                    @if($asset->maintenance_required)
                        <div class="ad-tiles mb-3">
                            <div class="ad-tile"><small>Jenis</small><b>{{ $typeLabels[$asset->maintenance_type] ?? ($asset->maintenance_type ?: '—') }}</b></div>
                            <div class="ad-tile"><small>Interval</small><b>{{ $asset->maintenance_interval ? 'Tiap ' . $asset->maintenance_interval . ' ' . ($asset->maintenance_interval_unit ?? '') : '—' }}</b></div>
                            <div class="ad-tile"><small>Terakhir</small><b>{{ $date($asset->last_maintenance_date) ?? '—' }}</b></div>
                        </div>
                    @endif
                    @if($maintenances->isEmpty())
                        <div class="ad-empty"><i class="bi bi-tools"></i>Belum ada riwayat maintenance.</div>
                    @else
                        <ul class="ad-tl">
                            @foreach($maintenances as $m)
                                <li>
                                    <span class="dot {{ $m->status === 'completed' ? 'ad-chip-teal' : 'ad-chip-blue' }}"><i class="bi {{ $m->status === 'completed' ? 'bi-check-lg' : 'bi-tools' }}"></i></span>
                                    <div>
                                        <b>{{ $typeLabels[$m->maintenance_type] ?? ucfirst((string) $m->maintenance_type) }} — {{ $m->action_taken ?: ($m->problem_description ?: 'Maintenance') }}</b>
                                        <small>
                                            {{ $date($m->maintenance_date) }} · {{ $statusLabels[$m->status] ?? $m->status }}
                                            @if($m->technician_name) · {{ $m->technician_name }}@endif
                                            @if($m->vendor) · {{ $m->vendor->vendor_name }}@endif
                                            @if($m->cost > 0) · {{ $rp($m->cost) }}@endif
                                        </small>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                {{-- Penugasan --}}
                <div class="ad-pane" data-ad-pane="assignment">
                    @if($assignments->isEmpty())
                        <div class="ad-empty"><i class="bi bi-person-badge"></i>Belum ada riwayat penugasan.</div>
                    @else
                        <ul class="ad-tl">
                            @foreach($assignments as $a)
                                <li>
                                    <span class="dot {{ $a->assignment_type === 'return' ? 'ad-chip-gray' : 'ad-chip-blue' }}"><i class="bi {{ $a->assignment_type === 'return' ? 'bi-box-arrow-in-left' : 'bi-person-check' }}"></i></span>
                                    <div>
                                        <b>{{ $assignLabels[$a->assignment_type] ?? 'Diserahkan ke' }} {{ $a->user->name ?? '—' }}</b>
                                        <small>
                                            {{ $a->start_at?->translatedFormat('d M Y') }}{{ $a->end_at ? ' – ' . $a->end_at->translatedFormat('d M Y') : ($a->assignment_type !== 'return' ? ' – sekarang' : '') }}
                                            @if($a->reason) · {{ $a->reason }}@endif
                                        </small>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                        @if(auth()->user()->hasPermission('asset.edit') && $asset->assignments_count > $assignments->count())
                            <a href="{{ route('assets.assignment.index', $encId) }}" class="small fw-semibold">Lihat semua riwayat penugasan →</a>
                        @endif
                    @endif
                </div>

                {{-- Lokasi --}}
                <div class="ad-pane" data-ad-pane="location">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                        <div>
                            <small class="text-muted d-block">Lokasi saat ini</small>
                            <b class="fs-6"><i class="bi bi-geo-alt-fill text-primary me-1"></i>{{ $asset->location ?: 'Belum diisi' }}</b>
                            @if($asset->branch && \App\Support\BranchAccess::hasMultiple((int) $asset->company_id))
                                <small class="d-block text-muted mt-1"><i class="bi bi-diagram-3 me-1"></i>{{ $asset->branch->branch_name }}
                                    @if(auth()->user()->hasPermission('branch.transfer') && !$disposed) · <a href="{{ route('branch-transfers.create') }}">Pindah cabang &rarr;</a>@endif
                                </small>
                            @endif
                        </div>
                        @if($canMove && !$disposed)
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#moveLocationModal">
                                <i class="bi bi-arrow-right-circle me-1"></i>Pindah lokasi
                            </button>
                        @endif
                    </div>
                    @if($locationHistories->isEmpty())
                        <div class="ad-empty"><i class="bi bi-geo-alt"></i>Belum ada perpindahan lokasi.@if($asset->location) Aset berada di lokasi awal sejak dicatat.@endif</div>
                    @else
                        <ul class="ad-tl">
                            @foreach($locationHistories as $h)
                                <li>
                                    <span class="dot {{ $loop->first ? 'ad-chip-blue' : 'ad-chip-gray' }}"><i class="bi bi-geo-alt"></i></span>
                                    <div>
                                        <b>{{ $h->from_location ?: 'Belum diisi' }} <i class="bi bi-arrow-right mx-1"></i> {{ $h->to_location }}</b>
                                        <small>
                                            {{ $h->moved_at->translatedFormat('d M Y') }}
                                            @if($h->creator) &middot; oleh {{ $h->creator->name }}@endif
                                            @if($h->notes) &middot; {{ $h->notes }}@endif
                                        </small>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                {{-- Penghapusan (termasuk yang dibatalkan) --}}
                @if($disposals->isNotEmpty())
                    <div class="ad-pane" data-ad-pane="disposal">
                        <ul class="ad-tl">
                            @foreach($disposals as $d)
                                <li>
                                    <span class="dot {{ $d->isCancelled() ? 'ad-chip-gray' : 'ad-chip-red' }}"><i class="bi {{ $d->isCancelled() ? 'bi-arrow-counterclockwise' : 'bi-archive' }}"></i></span>
                                    <div>
                                        <b>{{ $d->typeLabel() }}{{ $d->isCancelled() ? ' (dibatalkan)' : '' }}</b>
                                        <small>
                                            {{ $d->disposal_date->translatedFormat('d M Y') }}
                                            &middot; nilai buku {{ $d->book_value !== null ? $rp($d->book_value) : '—' }}
                                            @if((float) $d->proceeds > 0) &middot; hasil {{ $rp($d->proceeds) }}@endif
                                            @if($d->creator) &middot; oleh {{ $d->creator->name }}@endif
                                        </small>
                                        @if($d->isCancelled())
                                            <small>Dibatalkan {{ $d->cancelled_at->translatedFormat('d M Y H:i') }}{{ $d->canceller ? ' oleh ' . $d->canceller->name : '' }}: {{ $d->cancel_reason }}</small>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Dokumen --}}
                <div class="ad-pane" data-ad-pane="docs">
                    @forelse($asset->documents as $document)
                        @php $ext = strtolower(pathinfo($document->file_path, PATHINFO_EXTENSION)); @endphp
                        <div class="ad-doc">
                            <i class="bi {{ $ext === 'pdf' ? 'bi-file-earmark-pdf' : 'bi-file-earmark-image' }}"></i>
                            <div class="name" title="{{ $document->original_name ?? basename($document->file_path) }}">
                                {{ $document->original_name ?? basename($document->file_path) }}
                                <small class="text-muted d-block">{{ strtoupper($ext) }}</small>
                            </div>
                            <a href="{{ asset('storage/' . $document->file_path) }}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye me-1"></i>Lihat</a>
                        </div>
                    @empty
                        <div class="ad-empty"><i class="bi bi-paperclip"></i>Belum ada invoice atau dokumen.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

@include('dashboard.asset.partials.lifecycle-modals')

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Tab detail (?tab=location dst. membuka tab tertentu)
    function openTab(key) {
        var btn = document.querySelector('[data-ad-tab="' + key + '"]') || document.querySelector('[data-ad-tab]');
        key = btn.getAttribute('data-ad-tab');
        document.querySelectorAll('[data-ad-tab]').forEach(function (b) { b.classList.toggle('active', b === btn); });
        document.querySelectorAll('[data-ad-pane]').forEach(function (p) { p.classList.toggle('active', p.getAttribute('data-ad-pane') === key); });
    }
    document.querySelectorAll('[data-ad-tab]').forEach(function (btn) {
        btn.addEventListener('click', function () { openTab(btn.getAttribute('data-ad-tab')); });
    });
    openTab(@json($disposed && $initialTab === 'finance' && $disposals->isNotEmpty() ? 'disposal' : $initialTab));

    // Galeri foto
    var main = document.querySelector('#adMainPhoto img');
    document.querySelectorAll('[data-photo]').forEach(function (a) {
        a.addEventListener('click', function (e) {
            if (!main) return;
            e.preventDefault();
            main.src = a.getAttribute('href');
            document.querySelectorAll('[data-photo]').forEach(function (x) { x.classList.toggle('on', x === a); });
        });
    });

    // Pratinjau QR (isi sama dengan label cetak)
    var data = document.getElementById('assetQrData');
    var box = document.getElementById('assetQrPreview');
    if (data && box && typeof QRCode !== 'undefined' && data.dataset.qrToken) {
        new QRCode(box, {
            text: data.dataset.qrBaseUrl + '/' + data.dataset.qrToken,
            width: 146, height: 146,
            colorDark: '#0b2447', colorLight: '#ffffff',
            correctLevel: QRCode.CorrectLevel.M
        });
    } else if (box) {
        box.innerHTML = '<span class="text-muted small">QR belum tersedia</span>';
    }
});
</script>

<!-- =========================================================
     QR DATA
     
     Data asset disimpan di HTML data-* attribute.
     Tidak ada syntax Blade di dalam JavaScript.
========================================================= -->

<div
    id="assetQrData"
    data-id="{{ $asset->id }}"
    data-asset-code="{{ e($asset->asset_code) }}"
    data-asset-name="{{ e($asset->asset_name) }}"
    data-qr-token="{{ e($asset->qr_token) }}"
    data-qr-base-url="{{ e(url('/assets/qr')) }}"
    data-company-name="{{ e($asset->company->company_name ?? 'COMPANY') }}"
></div>

<!-- =========================================================
     PRINT QR MODAL
========================================================= -->

<div
    class="modal fade"
    id="printQrModal"
    tabindex="-1"
    aria-labelledby="printQrModalLabel"
    aria-hidden="true"
>


<div class="modal-dialog modal-lg modal-dialog-centered">

    <div class="modal-content border-0 shadow-lg">

        <!-- HEADER -->

        <div class="modal-header">

            <div>

                <h5
                    class="modal-title fw-bold"
                    id="printQrModalLabel"
                >

                    <i class="fa-solid fa-qrcode text-primary me-2"></i>

                    Print QR Asset

                </h5>

                <small class="text-muted">
                    Generate dan cetak QR Code asset
                </small>

            </div>


            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="modal"
                aria-label="Close"
            ></button>

        </div>


        <!-- BODY -->

        <div class="modal-body">

            <div class="row g-4">

                <!-- QR PREVIEW -->

                <div class="col-md-5">

                    <div
                        id="qrPrintArea"
                        class="border rounded-4 bg-white p-4 text-center"
                    >

                        <div class="mb-3">

                            <small
                                class="text-muted"
                                id="qrCompanyName"
                            ></small>

                        </div>


                        <div
                            id="assetQrCode"
                            class="d-flex justify-content-center align-items-center"
                            style="min-height: 220px;"
                        >

                            <div class="text-muted">

                                <i class="fa-solid fa-qrcode fa-2x mb-2"></i>

                                <br>

                                QR siap dibuat

                            </div>

                        </div>


                        <div class="mt-3">

                            <div
                                class="fw-bold fs-5"
                                id="qrAssetCode"
                            ></div>


                            <div
                                class="text-muted"
                                id="qrAssetName"
                            ></div>

                        </div>

                    </div>

                </div>


                <!-- SETTINGS -->

                <div class="col-md-7">

                    <!-- SIZE -->

                    <div class="mb-4">

                        <label class="form-label fw-semibold">
                            QR Size
                        </label>


                        <div class="row g-2">

                            <div class="col-3">

                                <input
                                    type="radio"
                                    class="btn-check"
                                    name="qrSize"
                                    id="qrSizeSmall"
                                    value="40"
                                    checked
                                >

                                <label
                                    class="btn btn-outline-primary w-100"
                                    for="qrSizeSmall"
                                >

                                    Small

                                    <small class="d-block">
                                        1 × 1 cm
                                    </small>

                                </label>

                            </div>


                            <div class="col-3">

                                <input
                                    type="radio"
                                    class="btn-check"
                                    name="qrSize"
                                    id="qrSizeMedium"
                                    value="50"
                                >

                                <label
                                    class="btn btn-outline-primary w-100"
                                    for="qrSizeMedium"
                                >

                                    Medium

                                    <small class="d-block">
                                        1.3 × 1.3 cm
                                    </small>

                                </label>

                            </div>


                            <div class="col-3">

                                <input
                                    type="radio"
                                    class="btn-check"
                                    name="qrSize"
                                    id="qrSizeLarge"
                                    value="60"
                                >

                                <label
                                    class="btn btn-outline-primary w-100"
                                    for="qrSizeLarge"
                                >

                                    Large

                                    <small class="d-block">
                                        1.6 × 1.6 cm
                                    </small>

                                </label>

                            </div>


                            <div class="col-3">

                                <input
                                    type="radio"
                                    class="btn-check"
                                    name="qrSize"
                                    id="qrSizeXL"
                                    value="80"
                                >

                                <label
                                    class="btn btn-outline-primary w-100"
                                    for="qrSizeXL"
                                >

                                    XL

                                    <small class="d-block">
                                        2.1 × 2.1 cm
                                    </small>

                                </label>

                            </div>

                        </div>

                    </div>


                    <!-- INFO -->

                    <div class="alert alert-light border mb-4">

                        <div class="d-flex">

                            <div class="me-3">

                                <i
                                    class="fa-solid fa-circle-info text-primary"
                                ></i>

                            </div>

                            <div>

                                <small>

                                    QR Code akan berisi link unik
                                    berdasarkan QR Token asset dan dapat
                                    digunakan untuk proses scanning
                                    inventory.

                                </small>

                            </div>

                        </div>

                    </div>


                    <!-- ACTION -->

                    <div class="d-grid gap-2">

                        <button
                            type="button"
                            class="btn btn-primary"
                            onclick="printAssetQr()"
                        >

                            <i class="fa-solid fa-print me-2"></i>

                            Print QR

                        </button>


                        <button
                            type="button"
                            class="btn btn-outline-secondary"
                            onclick="downloadAssetQr()"
                        >

                            <i class="fa-solid fa-download me-2"></i>

                            Download QR

                        </button>

                    </div>


                    <!-- SHARE -->

                    <div class="mt-4">

                        <label class="form-label fw-semibold">
                            Share
                        </label>


                        <div class="d-flex gap-2">

                            <button
                                type="button"
                                class="btn btn-outline-success flex-fill"
                                onclick="shareAssetWhatsApp()"
                            >

                                <i class="fa-brands fa-whatsapp me-1"></i>

                                WhatsApp

                            </button>


                            <button
                                type="button"
                                class="btn btn-outline-primary flex-fill"
                                onclick="shareAssetEmail()"
                            >

                                <i class="fa-solid fa-envelope me-1"></i>

                                Email

                            </button>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- FOOTER -->

        <div class="modal-footer bg-light">

            <small class="text-muted me-auto">

                Asset:
                <strong id="qrFooterAssetCode"></strong>

            </small>


            <button
                type="button"
                class="btn btn-secondary"
                data-bs-dismiss="modal"
            >

                Close

            </button>

        </div>

    </div>

</div>


</div>

<!-- =========================================================
     QR CODE LIBRARY
========================================================= -->

<script src="{{ asset('assets/vendor/qrcodejs/qrcode.min.js') }}"></script>

<script>

    /*
    |--------------------------------------------------------------------------
    | ASSET QR DATA
    |--------------------------------------------------------------------------
    |
    | Semua data dibaca dari HTML data-* attribute.
    | Tidak ada Blade syntax di dalam JavaScript.
    |
    */

    const qrDataElement =
        document.getElementById('assetQrData');


    /*
    |--------------------------------------------------------------------------
    | VALIDATE DATA ELEMENT
    |--------------------------------------------------------------------------
    */

    if (!qrDataElement) {

        console.error(
            'Element assetQrData tidak ditemukan.'
        );

    }


    /*
    |--------------------------------------------------------------------------
    | ASSET DATA
    |--------------------------------------------------------------------------
    */

    const assetId =
        qrDataElement
            ? qrDataElement.dataset.id
            : '';

    const assetCode =
        qrDataElement
            ? qrDataElement.dataset.assetCode
            : '';

    const assetName =
        qrDataElement
            ? qrDataElement.dataset.assetName
            : '';

    const qrToken =
        qrDataElement
            ? qrDataElement.dataset.qrToken
            : '';

    const qrBaseUrl =
        qrDataElement
            ? qrDataElement.dataset.qrBaseUrl
            : '';

    const companyName =
        qrDataElement
            ? qrDataElement.dataset.companyName
            : 'COMPANY';


    /*
    |--------------------------------------------------------------------------
    | QR URL
    |--------------------------------------------------------------------------
    |
    | PENTING:
    |
    | QR menggunakan qr_token UUID.
    | BUKAN database ID.
    |
    | Contoh:
    |
    | http://192.168.43.120:8001/assets/qr/
    | 0c7d29e2-8f17-4ec7-b762-8019e09f225b
    |
    */

    const assetQrUrl =
        qrBaseUrl && qrToken
            ? qrBaseUrl + '/' + qrToken
            : '';


    /*
    |--------------------------------------------------------------------------
    | SET BASIC DISPLAY DATA
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        'DOMContentLoaded',
        function () {

            const companyElement =
                document.getElementById('qrCompanyName');

            const codeElement =
                document.getElementById('qrAssetCode');

            const nameElement =
                document.getElementById('qrAssetName');

            const footerCodeElement =
                document.getElementById('qrFooterAssetCode');


            if (companyElement) {

                companyElement.textContent =
                    companyName;

            }


            if (codeElement) {

                codeElement.textContent =
                    assetCode;

            }


            if (nameElement) {

                nameElement.textContent =
                    assetName;

            }


            if (footerCodeElement) {

                footerCodeElement.textContent =
                    assetCode;

            }

        }
    );


    /*
    |--------------------------------------------------------------------------
    | GENERATE QR
    |--------------------------------------------------------------------------
    */

    function generateAssetQr() {

        const qrContainer =
            document.getElementById(
                'assetQrCode'
            );


        if (!qrContainer) {

            console.error(
                'QR container tidak ditemukan.'
            );

            return;

        }


        /*
        |----------------------------------------------------------------------
        | CHECK QR LIBRARY
        |----------------------------------------------------------------------
        */

        if (
            typeof QRCode === 'undefined'
        ) {

            qrContainer.innerHTML = `

                <div class="text-danger text-center">

                    <i
                        class="fa-solid fa-triangle-exclamation fa-2x mb-2"
                    ></i>

                    <br>

                    QR Code library gagal dimuat.

                </div>

            `;

            return;

        }


        /*
        |----------------------------------------------------------------------
        | CHECK TOKEN
        |----------------------------------------------------------------------
        */

        if (!qrToken) {

            qrContainer.innerHTML = `

                <div class="text-danger text-center">

                    <i
                        class="fa-solid fa-triangle-exclamation fa-2x mb-2"
                    ></i>

                    <br>

                    QR Token asset tidak tersedia.

                </div>

            `;

            return;

        }


        /*
        |----------------------------------------------------------------------
        | CHECK QR URL
        |----------------------------------------------------------------------
        */

        if (!assetQrUrl) {

            qrContainer.innerHTML = `

                <div class="text-danger text-center">

                    <i
                        class="fa-solid fa-triangle-exclamation fa-2x mb-2"
                    ></i>

                    <br>

                    QR URL tidak dapat dibuat.

                </div>

            `;

            return;

        }


        /*
        |----------------------------------------------------------------------
        | GET SIZE
        |----------------------------------------------------------------------
        */

        const sizeElement =
            document.querySelector(
                'input[name="qrSize"]:checked'
            );


        const size =
            sizeElement
                ? parseInt(
                    sizeElement.value,
                    10
                )
                : 40;


        /*
        |----------------------------------------------------------------------
        | CLEAR OLD QR
        |----------------------------------------------------------------------
        */

        qrContainer.innerHTML = '';


        /*
        |----------------------------------------------------------------------
        | GENERATE
        |----------------------------------------------------------------------
        */

        try {

            new QRCode(
                qrContainer,
                {

                    text: assetQrUrl,

                    width: size,

                    height: size,

                    correctLevel:
                        QRCode.CorrectLevel.H

                }
            );

        } catch (error) {

            console.error(
                'Gagal generate QR:',
                error
            );


            qrContainer.innerHTML = `

                <div class="text-danger text-center">

                    <i
                        class="fa-solid fa-triangle-exclamation fa-2x mb-2"
                    ></i>

                    <br>

                    Gagal membuat QR Code.

                </div>

            `;

        }

    }


    /*
    |--------------------------------------------------------------------------
    | QR SIZE CHANGE
    |--------------------------------------------------------------------------
    */

    document
        .querySelectorAll(
            'input[name="qrSize"]'
        )
        .forEach(
            function (radio) {

                radio.addEventListener(
                    'change',
                    function () {

                        generateAssetQr();

                    }
                );

            }
        );


    /*
    |--------------------------------------------------------------------------
    | PRINT QR
    |--------------------------------------------------------------------------
    */

    function printAssetQr() {

        const printArea =
            document.getElementById(
                'qrPrintArea'
            );


        if (!printArea) {

            alert(
                'Area QR tidak ditemukan.'
            );

            return;

        }


        const qrContainer =
            document.getElementById(
                'assetQrCode'
            );


        if (!qrContainer) {

            alert(
                'QR Code tidak ditemukan.'
            );

            return;

        }


        /*
        |----------------------------------------------------------------------
        | CHECK QR
        |----------------------------------------------------------------------
        */

        const qrCanvas =
            qrContainer.querySelector(
                'canvas'
            );

        const qrImage =
            qrContainer.querySelector(
                'img'
            );


        if (!qrCanvas && !qrImage) {

            generateAssetQr();


            setTimeout(
                function () {

                    printAssetQr();

                },
                300
            );


            return;

        }


        /*
        |----------------------------------------------------------------------
        | OPEN WINDOW
        |----------------------------------------------------------------------
        */

        const printWindow =
            window.open(
                '',
                '_blank',
                'width=900,height=900'
            );


        if (!printWindow) {

            alert(
                'Popup diblokir browser. Silakan izinkan popup untuk mencetak QR.'
            );

            return;

        }


        /*
        |----------------------------------------------------------------------
        | GET SIZE
        |----------------------------------------------------------------------
        */

        const selectedSizeElement =
            document.querySelector(
                'input[name="qrSize"]:checked'
            );


        const qrSize =
            selectedSizeElement
                ? parseInt(
                    selectedSizeElement.value,
                    10
                )
                : 40;


        /*
        |----------------------------------------------------------------------
        | FONT SIZE
        |----------------------------------------------------------------------
        */

        let companyFontSize = 6;
        let assetCodeFontSize = 8;
        let assetNameFontSize = 6;


        if (qrSize === 40) {

            companyFontSize = 6;
            assetCodeFontSize = 8;
            assetNameFontSize = 6;

        } else if (qrSize === 50) {

            companyFontSize = 7;
            assetCodeFontSize = 10;
            assetNameFontSize = 7;

        } else if (qrSize === 60) {

            companyFontSize = 9;
            assetCodeFontSize = 12;
            assetNameFontSize = 8;

        } else {

            companyFontSize = 11;
            assetCodeFontSize = 15;
            assetNameFontSize = 10;

        }


        /*
        |----------------------------------------------------------------------
        | QR HTML
        |----------------------------------------------------------------------
        */

        const qrHtml =
            qrContainer.innerHTML;


        /*
        |----------------------------------------------------------------------
        | PRINT DOCUMENT
        |----------------------------------------------------------------------
        */

        printWindow.document.write(`

            <!DOCTYPE html>

            <html>

            <head>

                <meta charset="UTF-8">

                <title>
                    QR Asset - ${escapeHtml(assetCode)}
                </title>

                <style>

                    @page {

                        size: A4;

                        margin: 15mm;

                    }


                    html,
                    body {

                        margin: 0;

                        padding: 0;

                        background: #ffffff;

                    }


                    body {

                        font-family:
                            Arial,
                            Helvetica,
                            sans-serif;

                        display: flex;

                        justify-content: center;

                        align-items: flex-start;

                        padding-top: 20mm;

                    }


                    .qr-print {

                        text-align: center;

                    }


                    .company {

                        font-size:
                            ${companyFontSize}px;

                        margin-bottom: 8px;

                    }


                    .qr-code {

                        display: flex;

                        justify-content: center;

                        align-items: center;

                    }


                    .qr-code img {

                        display: block;

                        margin: auto;

                    }


                    .qr-code canvas {

                        display: block;

                        margin: auto;

                    }


                    .asset-code {

                        font-size:
                            ${assetCodeFontSize}px;

                        font-weight: bold;

                        margin-top: 8px;

                    }


                    .asset-name {

                        font-size:
                            ${assetNameFontSize}px;

                        margin-top: 4px;

                        max-width: 200px;

                        word-break: break-word;

                    }

                </style>


        </head>


        <body>

            <div class="qr-print">

                <div class="company">
                    ${escapeHtml(companyName)}
                </div>

                <div class="qr-code">
                    ${qrHtml}
                </div>

                <div class="asset-code">
                    ${escapeHtml(assetCode)}
                </div>

                <div class="asset-name">
                    ${escapeHtml(assetName)}
                </div>

            </div>

        </body>

        </html>

    `);


    printWindow.document.close();

    printWindow.focus();


    /*
    |----------------------------------------------------------------------
    | PRINT AFTER LOAD
    |----------------------------------------------------------------------
    */

    setTimeout(
        function () {

            printWindow.print();

        },
        500
    );

}


/*
|--------------------------------------------------------------------------
| DOWNLOAD QR
|--------------------------------------------------------------------------
*/

function downloadAssetQr() {

    const qrContainer =
        document.getElementById(
            'assetQrCode'
        );


    if (!qrContainer) {

        alert(
            'QR Code tidak ditemukan.'
        );

        return;

    }


    const canvas =
        qrContainer.querySelector(
            'canvas'
        );


    if (canvas) {

        const link =
            document.createElement('a');


        link.download =
            'QR-' + assetCode + '.png';


        link.href =
            canvas.toDataURL(
                'image/png'
            );


        link.click();


        return;

    }


    /*
    |----------------------------------------------------------------------
    | FALLBACK IMAGE
    |----------------------------------------------------------------------
    */

    const image =
        qrContainer.querySelector(
            'img'
        );


    if (image && image.src) {

        const link =
            document.createElement('a');


        link.download =
            'QR-' + assetCode + '.png';


        link.href =
            image.src;


        link.target =
            '_blank';


        link.click();


        return;

    }


    alert(
        'QR Code belum selesai dibuat.'
    );

}


/*
|--------------------------------------------------------------------------
| WHATSAPP
|--------------------------------------------------------------------------
*/

function shareAssetWhatsApp() {

    if (!assetQrUrl) {

        alert(
            'QR URL tidak tersedia.'
        );

        return;

    }


    const message =
        `Asset Information\n\n` +
        `Asset Code: ${assetCode}\n` +
        `Asset Name: ${assetName}\n\n` +
        `QR / Asset Link:\n${assetQrUrl}`;


    const whatsappUrl =
        'https://wa.me/?text=' +
        encodeURIComponent(
            message
        );


    window.open(
        whatsappUrl,
        '_blank'
    );

}


/*
|--------------------------------------------------------------------------
| EMAIL
|--------------------------------------------------------------------------
*/

function shareAssetEmail() {

    if (!assetQrUrl) {

        alert(
            'QR URL tidak tersedia.'
        );

        return;

    }


    const subject =
        `Asset QR - ${assetCode}`;


    const body =
        `Asset Information\n\n` +
        `Asset Code: ${assetCode}\n` +
        `Asset Name: ${assetName}\n\n` +
        `Asset Link:\n${assetQrUrl}`;


    window.location.href =
        'mailto:?subject=' +
        encodeURIComponent(
            subject
        ) +
        '&body=' +
        encodeURIComponent(
            body
        );

}


/*
|--------------------------------------------------------------------------
| ESCAPE HTML
|--------------------------------------------------------------------------
|
| Digunakan untuk data asset ketika dimasukkan ke print window.
|
*/

function escapeHtml(value) {

    if (value === null || value === undefined) {

        return '';

    }


    return String(value)
        .replace(
            /&/g,
            '&amp;'
        )
        .replace(
            /</g,
            '&lt;'
        )
        .replace(
            />/g,
            '&gt;'
        )
        .replace(
            /"/g,
            '&quot;'
        )
        .replace(
            /'/g,
            '&#039;'
        );

}


</script>

@endsection
