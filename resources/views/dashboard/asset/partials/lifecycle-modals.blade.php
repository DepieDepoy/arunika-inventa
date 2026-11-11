{{--
    Modal siklus hidup aset di halaman detail:
    - Pindah lokasi      (asset.move)
    - Hapuskan aset      (asset.dispose)
    - Batalkan penghapusan (asset.dispose)
    Variabel dari show.blade.php: $asset, $encId, $disposed, $canMove, $canDispose, $activeDisposal, $knownLocations, $bookValue, $rp.
--}}
<style>
    .lc-types { display:grid; grid-template-columns:repeat(3, minmax(0,1fr)); gap:8px; }
    .lc-type { position:relative; }
    .lc-type input { position:absolute; opacity:0; inset:0; }
    .lc-type label { display:flex; flex-direction:column; gap:2px; height:100%; padding:10px 12px; border:1.5px solid rgba(15,27,45,.12); border-radius:12px; cursor:pointer; font-size:.84rem; font-weight:700; color:#0f1b2d; }
    .lc-type label i { font-size:1.1rem; color:#1f5fbf; }
    .lc-type label small { font-weight:500; color:#8592a6; font-size:.72rem; }
    .lc-type input:checked + label { border-color:#dc2626; background:#fff5f5; }
    .lc-type input:checked + label i { color:#dc2626; }
    .lc-type input:focus-visible + label { outline:2px solid #1f5fbf; outline-offset:2px; }
    body.dark .lc-type label { color:#fff; border-color:rgba(255,255,255,.15); }
    body.dark .lc-type input:checked + label { background:rgba(220,38,38,.15); }
    .lc-value { display:grid; grid-template-columns:repeat(3, minmax(0,1fr)); gap:8px; background:var(--bs-tertiary-bg, #f4f7fb); border-radius:12px; padding:12px; }
    .lc-value small { display:block; font-size:.68rem; font-weight:700; color:#8592a6; text-transform:uppercase; letter-spacing:.04em; }
    .lc-value b { font-variant-numeric:tabular-nums; font-size:.95rem; }
    .lc-effects { font-size:.82rem; color:#5b6b80; margin:0; padding-left:1.1rem; }
    @media (max-width: 575.98px) { .lc-types { grid-template-columns:1fr 1fr; } .lc-value { grid-template-columns:1fr; } }
</style>

{{-- ================= PINDAH LOKASI ================= --}}
@if($canMove && !$disposed)
<div class="modal fade" id="moveLocationModal" tabindex="-1" aria-labelledby="moveLocationTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="POST" action="{{ route('assets.location.move', $encId) }}">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title" id="moveLocationTitle"><i class="bi bi-geo-alt me-2 text-primary"></i>{{ __('Pindah lokasi aset') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Tutup') }}"></button>
            </div>
            <div class="modal-body">
                <div class="small text-muted mb-1">{{ __('Lokasi saat ini') }}</div>
                <div class="fw-bold mb-3">{{ $asset->location ?: 'Belum diisi' }}</div>

                <label class="form-label" for="lcLocation">{{ __('Lokasi baru') }} <span class="text-danger">*</span></label>
                {{-- Master lokasi cabang aset (lihat menu Master Data > Lokasi) --}}
                <select name="location" id="lcLocation" class="form-select" required data-location-picker
                        data-branch="{{ $asset->branch_id }}" data-value="{{ old('location') }}"></select>
                <div class="form-text">{{ __('Lokasi di :p1. Pilih', ['p1' => $asset->branch?->branch_name ?? 'cabang aset']) }} <b>{{ __('+ Tambah lokasi baru') }}</b> {{ __('bila belum ada. Untuk pindah ke cabang lain gunakan Mutasi Aset.') }}</div>

                <div class="row g-3 mt-1">
                    <div class="col-sm-5">
                        <label class="form-label" for="lcMovedAt">{{ __('Tanggal pindah') }} <span class="text-danger">*</span></label>
                        <input type="date" name="moved_at" id="lcMovedAt" class="form-control" required max="{{ today()->toDateString() }}" value="{{ old('moved_at', today()->toDateString()) }}">
                    </div>
                    <div class="col-sm-7">
                        <label class="form-label" for="lcMoveNotes">{{ __('Catatan') }}</label>
                        <input type="text" name="notes" id="lcMoveNotes" class="form-control" maxlength="1000" placeholder="{{ __('Mis. relokasi tim, renovasi ruangan') }}" value="{{ old('notes') }}">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Batal') }}</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i>{{ __('Simpan perpindahan') }}</button>
            </div>
        </form>
    </div>
</div>
@endif

{{-- ================= HAPUSKAN ASET ================= --}}
@if($canDispose && !$disposed)
@php
    $typeIcons = ['scrapped' => 'bi-hammer', 'lost' => 'bi-question-octagon', 'sold' => 'bi-cash-coin', 'donated' => 'bi-gift', 'returned_vendor' => 'bi-truck', 'other' => 'bi-three-dots'];
    $typeHints = ['scrapped' => 'Rusak berat, tidak bisa diperbaiki', 'lost' => 'Tidak ditemukan / dicuri', 'sold' => 'Dijual ke pihak lain', 'donated' => 'Diberikan ke pihak lain', 'returned_vendor' => 'Retur / klaim ke vendor', 'other' => 'Jelaskan di catatan'];
@endphp
<div class="modal fade" id="disposeModal" tabindex="-1" aria-labelledby="disposeTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <form class="modal-content" method="POST" action="{{ route('assets.dispose', $encId) }}" enctype="multipart/form-data" id="disposeForm">
            @csrf
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="disposeTitle"><i class="bi bi-archive me-2 text-danger"></i>{{ __('Hapuskan aset') }}</h5>
                    <small class="text-muted">{{ $asset->asset_code }} &middot; {{ $asset->asset_name }}</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Tutup') }}"></button>
            </div>
            <div class="modal-body">
                <label class="form-label">{{ __('Alasan penghapusan') }} <span class="text-danger">*</span></label>
                <div class="lc-types mb-3" role="radiogroup">
                    @foreach(\App\Models\AssetDisposal::TYPES as $key => $label)
                        <div class="lc-type">
                            <input type="radio" name="disposal_type" id="lcType_{{ $key }}" value="{{ $key }}" required
                                   data-proceeds="{{ in_array($key, \App\Models\AssetDisposal::WITH_PROCEEDS, true) ? 1 : 0 }}" @checked(old('disposal_type') === $key)>
                            <label for="lcType_{{ $key }}"><i class="bi {{ $typeIcons[$key] }}"></i>{{ $label }}<small>{{ $typeHints[$key] }}</small></label>
                        </div>
                    @endforeach
                </div>

                <div class="row g-3">
                    <div class="col-sm-6">
                        <label class="form-label" for="lcDisposalDate">{{ __('Tanggal penghapusan') }} <span class="text-danger">*</span></label>
                        <input type="date" name="disposal_date" id="lcDisposalDate" class="form-control" required
                               max="{{ today()->toDateString() }}" min="{{ $asset->purchase_date?->toDateString() }}"
                               value="{{ old('disposal_date', today()->toDateString()) }}">
                    </div>
                    <div class="col-sm-6" id="lcProceedsWrap">
                        <label class="form-label" for="lcProceeds">{{ __('Hasil penjualan / nilai ganti') }}</label>
                        <div class="input-group">
                            <span class="input-group-text">{{ __('Rp') }}</span>
                            <input type="number" name="proceeds" id="lcProceeds" class="form-control" min="0" step="1" value="{{ old('proceeds', 0) }}">
                        </div>
                        <div class="form-text">{{ __('Isi 0 bila tidak ada uang yang diterima.') }}</div>
                    </div>
                </div>

                <div class="mt-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="form-label mb-0">{{ __('Perhitungan nilai') }}</span>
                        <small class="text-muted" id="lcValueHint">{{ __('metode garis lurus, per tanggal penghapusan') }}</small>
                    </div>
                    <div class="lc-value" aria-live="polite">
                        <div><small>{{ __('Nilai buku') }}</small><b id="lcBook">{{ $bookValue ? $rp($bookValue['value']) : ($asset->purchase_price ? $rp($asset->purchase_price) : '—') }}</b></div>
                        <div><small>{{ __('Hasil') }}</small><b id="lcGot">{{ __('Rp0') }}</b></div>
                        <div><small id="lcGlLabel">{{ __('Kerugian penghapusan') }}</small><b id="lcGl">—</b></div>
                    </div>
                    @if(!$bookValue)
                        <div class="form-text"><i class="bi bi-info-circle me-1"></i>{{ __('Aset belum memiliki data penyusutan, sehingga nilai buku memakai harga beli.') }}</div>
                    @endif
                </div>

                <div class="row g-3 mt-1">
                    <div class="col-md-7">
                        <label class="form-label" for="lcNotes">{{ __('Catatan') }} <span class="text-muted small" id="lcNotesReq"></span></label>
                        <textarea name="notes" id="lcNotes" class="form-control" rows="3" maxlength="2000" placeholder="Kronologi, nomor berita acara, pembeli/penerima, dll.">{{ old('notes') }}</textarea>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label" for="lcDocument">{{ __('Berita acara / bukti') }}</label>
                        <input type="file" name="document" id="lcDocument" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                        <div class="form-text">{{ __('PDF/JPG/PNG, maks 10 MB. Dihitung ke kuota storage.') }}</div>
                    </div>
                </div>

                <div class="alert alert-warning mt-3 mb-2 py-2">
                    <div class="fw-semibold mb-1"><i class="bi bi-exclamation-triangle me-1"></i>{{ __('Setelah dihapuskan:') }}</div>
                    <ul class="lc-effects">
                        <li>{{ __('Status aset menjadi') }} <b>{{ __('Dihapuskan') }}</b>{{ __('; tidak lagi dihitung di total aset, nilai aset, dan Aset Saya.') }}</li>
                        @if($asset->responsibleUser)<li>{{ __('Aset otomatis dikembalikan dari') }} <b>{{ $asset->responsibleUser->name }}</b>.</li>@endif
                        <li>{{ __('Jadwal maintenance & request yang masih terbuka dibatalkan.') }}</li>
                        <li>{{ __('Data dan seluruh riwayat') }} <b>{{ __('tetap tersimpan') }}</b>{{ __('; penghapusan bisa dibatalkan bila salah input.') }}</li>
                    </ul>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="confirm" value="1" id="lcConfirm" required>
                    <label class="form-check-label" for="lcConfirm">{{ __('Saya sudah memeriksa data di atas dan aset ini memang dihapuskan.') }}</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Batal') }}</button>
                <button type="submit" class="btn btn-danger"><i class="bi bi-archive me-1"></i>{{ __('Hapuskan aset') }}</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('disposeForm');
    if (!form) return;

    var dateEl = document.getElementById('lcDisposalDate');
    var proceedsEl = document.getElementById('lcProceeds');
    var proceedsWrap = document.getElementById('lcProceedsWrap');
    var notesReq = document.getElementById('lcNotesReq');
    var previewUrl = @json(route('assets.dispose.preview', $encId));
    var rp = function (v) { return 'Rp' + Math.round(Math.abs(v)).toLocaleString('id-ID'); };
    var timer = null;

    function selectedType() {
        var r = form.querySelector('input[name="disposal_type"]:checked');
        return r ? r : null;
    }

    function syncType() {
        var r = selectedType();
        var withProceeds = r ? r.dataset.proceeds === '1' : true;
        proceedsWrap.style.opacity = withProceeds ? '1' : '.55';
        if (!withProceeds) proceedsEl.value = proceedsEl.value || 0;
        notesReq.textContent = r && r.value === 'other' ? '(wajib)' : '';
        document.getElementById('lcNotes').required = !!(r && r.value === 'other');
        refresh();
    }

    function refresh() {
        clearTimeout(timer);
        timer = setTimeout(function () {
            if (!dateEl.value) return;
            var url = previewUrl + '?disposal_date=' + encodeURIComponent(dateEl.value) + '&proceeds=' + encodeURIComponent(proceedsEl.value || 0);
            fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.ok ? r.json() : null; })
                .then(function (res) {
                    if (!res || !res.data) return;
                    var d = res.data;
                    document.getElementById('lcBook').textContent = d.book_value === null ? '—' : rp(d.book_value);
                    document.getElementById('lcGot').textContent = rp(d.proceeds || 0);
                    var gl = d.gain_loss;
                    var glEl = document.getElementById('lcGl');
                    document.getElementById('lcGlLabel').textContent = gl !== null && gl > 0 ? 'Laba penghapusan' : 'Kerugian penghapusan';
                    glEl.textContent = gl === null ? '—' : (gl < 0 ? '(' + rp(gl) + ')' : rp(gl));
                    glEl.className = gl === null ? '' : (gl < 0 ? 'text-danger' : (gl > 0 ? 'text-success' : ''));
                })
                .catch(function () {});
        }, 250);
    }

    form.querySelectorAll('input[name="disposal_type"]').forEach(function (r) { r.addEventListener('change', syncType); });
    dateEl.addEventListener('change', refresh);
    proceedsEl.addEventListener('input', refresh);
    document.getElementById('disposeModal').addEventListener('shown.bs.modal', syncType);

    form.addEventListener('submit', function () {
        var btn = form.querySelector('button[type="submit"]');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Memproses...';
    });
});
</script>
@endif

{{-- ================= BATALKAN PENGHAPUSAN ================= --}}
@if($canDispose && $disposed && $activeDisposal)
<div class="modal fade" id="cancelDisposalModal" tabindex="-1" aria-labelledby="cancelDisposalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="POST" action="{{ route('assets.disposals.cancel', $activeDisposal->id) }}">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title" id="cancelDisposalTitle"><i class="bi bi-arrow-counterclockwise me-2"></i>{{ __('Batalkan penghapusan') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Tutup') }}"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted">{{ __('Gunakan bila penghapusan salah input. Aset kembali berstatus') }} <b>{{ $activeDisposal->previous_status == 1 ? 'Aktif' : 'Tidak aktif' }}</b>{{ __('. Pemegang dan jadwal maintenance') }} <b>{{ __('tidak') }}</b> {{ __('dipulihkan otomatis; serahkan ulang dan atur jadwalnya bila perlu. Catatan penghapusan tetap tersimpan sebagai riwayat.') }}</p>
                <label class="form-label" for="lcCancelReason">{{ __('Alasan pembatalan') }} <span class="text-danger">*</span></label>
                <textarea name="cancel_reason" id="lcCancelReason" class="form-control" rows="3" minlength="5" maxlength="500" required placeholder="Mis. salah pilih aset, aset ternyata masih bisa diperbaiki"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Tutup') }}</button>
                <button type="submit" class="btn btn-danger">{{ __('Batalkan penghapusan') }}</button>
            </div>
        </form>
    </div>
</div>
@endif

@php
    // ?move=1 / ?dispose=1 (dari daftar aset atau halaman Penghapusan) membuka modal langsung.
    $autoModal = match (true) {
        request()->boolean('move') && $canMove && !$disposed => 'moveLocationModal',
        request()->boolean('dispose') && $canDispose && !$disposed => 'disposeModal',
        default => null,
    };
@endphp
@if($autoModal)
<script>
document.addEventListener('DOMContentLoaded', function () {
    var el = document.getElementById(@json($autoModal));
    if (el && window.bootstrap) bootstrap.Modal.getOrCreateInstance(el).show();
});
</script>
@endif
