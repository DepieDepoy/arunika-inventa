@extends('backoffice.layouts.app')

@section('title', 'Tera AI')

@section('content')
@include('backoffice.partials.style')

<div class="container-xxl flex-grow-1 container-p-y">
    <div class="cms-card p-3 p-md-4 mb-4">
        <span class="cms-badge"><i class="bi bi-stars"></i> Pengaturan</span>
        <h4 class="mb-1 mt-2">Tera AI</h4>
        <div class="text-muted small">Asisten chat di dashboard &amp; aplikasi pelanggan. Jawaban selalu diambil dari database Vasetra sesuai hak akses tiap user.</div>
    </div>

    @if(session('success'))<div class="alert alert-success"><i class="bi bi-check2-circle me-1"></i> {{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <div class="row g-4">
        <div class="col-xl-7">
            <form method="POST" action="{{ route('backoffice.settings.tera.update') }}" class="cms-card p-3 p-md-4">
                @csrf
                <div class="form-check form-switch mb-1">
                    <input class="form-check-input" type="checkbox" role="switch" name="enabled" value="1" id="teraOn" @checked(old('enabled', $s['enabled']))>
                    <label class="form-check-label fw-bold" for="teraOn">Tera AI aktif untuk semua pelanggan</label>
                </div>
                <div class="small text-muted mb-4">Bila dimatikan, tombol Tera hilang dari dashboard pelanggan dan API menjawab "dinonaktifkan".</div>

                <label class="form-label fw-bold">Mode</label>
                @foreach($modes as $k => $label)
                    <label class="d-flex gap-2 p-3 rounded-3 mb-2" style="border:1px solid rgba(15,27,45,.12);cursor:pointer">
                        <input type="radio" name="mode" value="{{ $k }}" class="form-check-input mt-1" @checked(old('mode', $s['mode']) === $k)>
                        <span>
                            <b>{{ $label }}</b>
                            @if($k === 'basic')
                                <span class="d-block small text-muted">Gratis selamanya &amp; data tidak keluar dari server. Memahami singkatan ("brp", "yg", "mtc", "rsk", "lt"), salah ketik ringan, nama kategori / lokasi / karyawan, lalu menjawab langsung dari database.</span>
                            @else
                                <span class="d-block small text-muted">Dipakai hanya bila mode Dasar tidak paham. Yang dikirim ke Google <b>hanya teks pertanyaan</b> (tanpa data aset/karyawan), jawaban tetap dari database. Free tier Gemini punya batas pemakaian dan Google dapat memakai teks pertanyaan untuk meningkatkan layanannya.</span>
                            @endif
                        </span>
                    </label>
                @endforeach

                <div class="row g-3 mt-1" id="geminiBox">
                    <div class="col-md-7">
                        <label class="form-label small fw-bold">API key Gemini {!! $hasKey ? '<span class="cms-chip cms-chip-blue ms-1">tersimpan</span>' : '' !!}</label>
                        <input type="password" name="gemini_key" class="form-control" autocomplete="new-password" placeholder="{{ $hasKey ? '•••••••• (kosongkan bila tidak diganti)' : 'Dari aistudio.google.com' }}">
                        @if($hasKey)
                            <div class="form-check mt-1"><input class="form-check-input" type="checkbox" name="clear_key" value="1" id="clr"><label for="clr" class="form-check-label small">Hapus API key</label></div>
                        @endif
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small fw-bold">Model</label>
                        <input name="gemini_model" class="form-control" value="{{ old('gemini_model', $s['gemini_model']) }}" maxlength="60">
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-4"><button class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Simpan</button></div>
            </form>
        </div>

        <div class="col-xl-5">
            <div class="cms-card p-3 p-md-4 mb-4">
                <h6 class="fw-bold mb-2"><i class="bi bi-chat-dots me-1"></i> Uji pemahaman kalimat</h6>
                <div class="small text-muted mb-2">Lihat bagaimana Tera merapikan kalimat sebelum mencari jawaban.</div>
                <div class="d-flex gap-2">
                    <input id="tq" class="form-control" placeholder="brp aset rsk di gdg lt 2?" maxlength="300">
                    <button class="btn btn-outline-primary" id="tbtn" type="button">Uji</button>
                </div>
                <pre id="tout" class="small mt-3 mb-0 p-2 rounded" style="background:#f4f7fb;white-space:pre-wrap;display:none"></pre>
            </div>
            <div class="cms-card p-3 p-md-4">
                <h6 class="fw-bold mb-2"><i class="bi bi-building-slash me-1"></i> Dinonaktifkan per perusahaan</h6>
                @forelse($disabledCompanies as $c)
                    <div class="d-flex justify-content-between align-items-center small py-1">
                        <a href="{{ route('backoffice.customers.show', $c->id) }}">{{ $c->company_name }}</a>
                        <form method="POST" action="{{ route('backoffice.customers.tera', $c->id) }}">@csrf<button class="btn btn-sm btn-link p-0">Aktifkan</button></form>
                    </div>
                @empty
                    <div class="small text-muted">Tidak ada. Untuk mematikan Tera di satu perusahaan, buka <a href="{{ route('backoffice.customers.index') }}">Pelanggan</a> › detail perusahaan.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
(function () {
    var btn = document.getElementById('tbtn'), out = document.getElementById('tout');
    btn.addEventListener('click', function () {
        var q = document.getElementById('tq').value.trim();
        if (!q) return;
        btn.disabled = true;
        fetch(@json(route('backoffice.settings.tera.test')), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify({ question: q })
        }).then(function (r) { return r.json(); }).then(function (j) {
            out.style.display = 'block';
            out.textContent = 'Dipahami sebagai:\n' + j.normalized + (j.gemini ? '\n\nGemini:\n' + JSON.stringify(j.gemini, null, 2) : '');
        }).finally(function () { btn.disabled = false; });
    });
})();
</script>
@endsection
