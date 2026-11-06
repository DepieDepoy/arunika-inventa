@extends('dashboard.layouts.wrapper')

@section('title', 'Profil Perusahaan')

@section('content')
@php $fallback = asset('assets/images/brand/vasetra-logo.png'); @endphp

<style>
    .cp-card { background:var(--bs-body-bg); border:1px solid rgba(15,27,45,.08); border-radius:16px; box-shadow:0 1px 2px rgba(15,27,45,.04), 0 8px 24px -16px rgba(15,27,45,.16); }
    .cp-h { padding:20px 22px 0; }
    .cp-h h5 { margin:0; font-weight:800; color:#0b2447; }
    .cp-h .hint { font-size:.8rem; color:#8592a6; margin-top:2px; }
    .cp-drop { border:2px dashed rgba(15,27,45,.15); border-radius:14px; padding:22px; display:flex; gap:20px; align-items:center; flex-wrap:wrap; transition:border-color .15s, background .15s; }
    .cp-drop.drag { border-color:#1f5fbf; background:#f2f7ff; }
    .cp-logo { width:220px; height:110px; border-radius:12px; background:#f6f8fb; display:grid; place-items:center; padding:14px; flex:none; }
    .cp-logo img { max-width:100%; max-height:100%; object-fit:contain; }
    .cp-badge { display:inline-flex; gap:6px; align-items:center; font-size:.72rem; font-weight:800; padding:3px 10px; border-radius:999px; }
    .cp-badge.own { background:#dcfce7; color:#166534; }
    .cp-badge.def { background:#eef2f7; color:#4b5a6e; }
    .cp-help { font-size:.78rem; color:#8592a6; }

    /* Pratinjau sampul laporan */
    .cp-cover { position:relative; aspect-ratio:16/9; border-radius:14px; overflow:hidden; background:#0b2447; color:#fff; padding:7% 7%; }
    .cp-cover:before { content:""; position:absolute; width:55%; aspect-ratio:1; border-radius:50%; right:-12%; top:-30%; background:#1f5fbf; opacity:.35; }
    .cp-cover:after { content:""; position:absolute; width:38%; aspect-ratio:1; border-radius:50%; right:-8%; bottom:-30%; background:#14b8a6; opacity:.25; }
    .cp-cover .chip { position:relative; z-index:1; background:#fff; border-radius:10px; width:34%; aspect-ratio:250/66; display:grid; place-items:center; padding:2.2% 3%; }
    .cp-cover .chip img { max-width:100%; max-height:100%; object-fit:contain; }
    .cp-cover .kick { position:relative; z-index:1; margin-top:9%; font-size:.62rem; font-weight:800; letter-spacing:.12em; color:#22d3ee; }
    .cp-cover .name { position:relative; z-index:1; font-size:1.15rem; font-weight:800; line-height:1.2; margin-top:4px; }
    .cp-cover .per { position:relative; z-index:1; font-size:.72rem; color:#d6e2f5; margin-top:6px; }
    .cp-cover .bar { position:relative; z-index:1; width:9%; height:3px; background:#14b8a6; margin-top:6%; }
</style>

<div class="content-wrapper">
<div class="container-xxl flex-grow-1 container-p-y">

    @if(session('success'))
        <div class="alert alert-success"><i class="bi bi-check2-circle me-1"></i> {{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-1"></i> {{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('company.update') }}" enctype="multipart/form-data" id="cpForm">
        @csrf
        <div class="row g-4">
            <div class="col-xl-7">
                <div class="cp-card h-100">
                    <div class="cp-h">
                        <h5>Profil Perusahaan</h5>
                        <div class="hint">Logo &amp; nama ini tampil di laporan PDF dan PowerPoint, serta di aplikasi mobile.</div>
                    </div>
                    <div class="p-4">
                        <label class="form-label fw-bold">Logo perusahaan</label>
                        <div class="cp-drop" id="cpDrop">
                            <div class="cp-logo"><img src="{{ $logoUrl ?? $fallback }}" alt="Logo" id="cpLogoImg"></div>
                            <div style="flex:1;min-width:220px">
                                <div class="mb-2" id="cpBadge">
                                    @if($logoUrl)
                                        <span class="cp-badge own"><i class="bi bi-check-circle"></i> Logo perusahaan aktif</span>
                                    @else
                                        <span class="cp-badge def"><i class="bi bi-info-circle"></i> Belum ada logo — laporan memakai logo Vasetra</span>
                                    @endif
                                </div>
                                <input type="file" name="logo" id="cpLogo" class="form-control" accept="image/png,image/jpeg,image/webp">
                                <div class="cp-help mt-2">PNG (latar transparan paling bagus), JPG, atau WEBP · maks 2 MB · disarankan logo mendatar, min. 400 piksel lebarnya. Bisa juga tarik &amp; lepas file ke kotak ini.</div>
                                @if($logoUrl)
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" name="remove_logo" value="1" id="cpRemove">
                                        <label class="form-check-label small" for="cpRemove">Hapus logo (kembali ke logo Vasetra)</label>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="mt-4">
                            <label class="form-label fw-bold" for="cpName">Nama perusahaan</label>
                            <input type="text" name="company_name" id="cpName" class="form-control" value="{{ old('company_name', $company->company_name) }}" maxlength="255" required>
                            <div class="cp-help mt-1">Kode perusahaan <b>{{ $company->company_code }}</b> tidak berubah.</div>
                        </div>

                        <div class="mt-3">
                            <label class="form-label fw-bold" for="cpAddress">Alamat <span class="text-muted fw-normal">(opsional)</span></label>
                            <textarea name="address" id="cpAddress" class="form-control" rows="3" maxlength="500" placeholder="Alamat kantor yang tampil di laporan cetak">{{ old('address', $company->address) }}</textarea>
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <button class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Simpan profil</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-5">
                <div class="cp-card h-100">
                    <div class="cp-h">
                        <h5>Pratinjau laporan</h5>
                        <div class="hint">Begini tampilan sampul laporan PowerPoint Anda.</div>
                    </div>
                    <div class="p-4">
                        <div class="cp-cover">
                            <div class="chip"><img src="{{ $logoUrl ?? $fallback }}" alt="" id="cpCoverImg"></div>
                            <div class="kick">LAPORAN BULANAN</div>
                            <div class="name" id="cpCoverName">{{ $company->company_name }}</div>
                            <div class="per">Bulan ini · {{ now()->startOfMonth()->locale('id')->translatedFormat('d M') }} – {{ now()->endOfMonth()->locale('id')->translatedFormat('d M Y') }}</div>
                            <div class="bar"></div>
                        </div>
                        @if(Route::has('reports.index') && auth()->user()->hasPermission('report.view'))
                            <a href="{{ route('reports.index') }}" class="btn btn-outline-primary w-100 mt-3"><i class="bi bi-graph-up me-1"></i> Buka Laporan</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
</div>
@endsection

@section('js')
<script>
(function () {
    var input = document.getElementById('cpLogo');
    var drop = document.getElementById('cpDrop');
    var imgs = [document.getElementById('cpLogoImg'), document.getElementById('cpCoverImg')];

    var preview = function (file) {
        if (!file) return;
        if (file.size > 2 * 1024 * 1024) {
            Swal.fire({ icon: 'warning', title: 'Logo terlalu besar', text: 'Maksimal 2 MB.' });
            input.value = '';
            return;
        }
        var url = URL.createObjectURL(file);
        imgs.forEach(function (i) { i.src = url; });
        document.getElementById('cpBadge').innerHTML = '<span class="cp-badge own"><i class="bi bi-eye"></i> Pratinjau — klik Simpan untuk memakai logo ini</span>';
        var rm = document.getElementById('cpRemove');
        if (rm) rm.checked = false;
    };

    input.addEventListener('change', function () { preview(input.files[0]); });

    ['dragenter', 'dragover'].forEach(function (ev) {
        drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.add('drag'); });
    });
    ['dragleave', 'drop'].forEach(function (ev) {
        drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.remove('drag'); });
    });
    drop.addEventListener('drop', function (e) {
        if (!e.dataTransfer.files.length) return;
        input.files = e.dataTransfer.files;
        preview(input.files[0]);
    });

    document.getElementById('cpName').addEventListener('input', function () {
        document.getElementById('cpCoverName').textContent = this.value || '—';
    });
})();
</script>
@endsection
