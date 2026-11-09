@extends('dashboard.layouts.wrapper')

@section('title', $audit->audit_no . ' · Audit Fisik')

@section('content')
@php
    $chip = ['in_progress' => 'warn', 'completed' => 'ok', 'cancelled' => 'mute'];
    $tiles = [
        'all' => ['Total aset', $stats['total'], 'bi-box-seam', '#3a57e8'],
        'pending' => ['Belum dicek', $stats['pending'], 'bi-hourglass-split', '#6b7280'],
        'found' => ['Ditemukan', $stats['found'], 'bi-check-circle-fill', '#1a8754'],
        'misplaced' => ['Salah lokasi', $stats['misplaced'], 'bi-geo-alt-fill', '#d98a00'],
        'damaged' => ['Rusak', $stats['damaged'], 'bi-tools', '#c2410c'],
        'missing' => ['Tidak ditemukan', $stats['missing'], 'bi-x-circle-fill', '#c0392b'],
        'extra' => ['Di luar daftar', $stats['extra'], 'bi-plus-circle-fill', '#7c3aed'],
    ];
    $stateChip = [
        'pending' => ['Belum dicek', 'mute'],
        'found' => ['Ditemukan', 'ok'],
        'misplaced' => ['Salah lokasi', 'warn'],
        'missing' => ['Tidak ditemukan', 'bad'],
        'extra' => ['Di luar daftar', 'extra'],
    ];
@endphp
<style>
    .au-chip { display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 99px; font-size: .75rem; font-weight: 700; white-space: nowrap; }
    .au-chip.warn { background: #fff4e0; color: #b46b00; }
    .au-chip.ok { background: #e7f6ee; color: #1a8754; }
    .au-chip.mute { background: #eef0f4; color: #6b7280; }
    .au-chip.bad { background: #fdecee; color: #c0392b; }
    .au-chip.extra { background: #f3edff; color: #7c3aed; }
    .au-tiles { display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 10px; }
    .au-tile { display: block; border-radius: 12px; padding: 12px 14px; background: #f7f8fc; text-decoration: none; color: inherit; border: 2px solid transparent; }
    .au-tile.on { border-color: #3a57e8; background: #fff; }
    .au-tile .v { font-size: 1.45rem; font-weight: 800; line-height: 1.1; }
    .au-tile .l { font-size: .78rem; color: #6b7280; }
    .au-bar { height: 10px; border-radius: 10px; background: #e9edf5; overflow: hidden; }
    .au-bar span { display: block; height: 100%; background: linear-gradient(90deg, #3a57e8, #22c55e); transition: width .4s; }
    .au-scan { border: 0; border-radius: 16px; background: #0b2447; color: #fff; }
    .au-scan .form-control, .au-scan .form-select { border-radius: 10px; }
    .au-scan label { color: #c7d2fe; font-size: .8rem; font-weight: 600; }
    .au-code { font-size: 1.1rem; font-weight: 600; letter-spacing: .5px; }
    #reader { width: 100%; max-width: 420px; margin: 0 auto; border-radius: 12px; overflow: hidden; background: #000; }
    .au-feed { max-height: 260px; overflow-y: auto; }
    .au-feed .f { display: flex; gap: 10px; align-items: flex-start; padding: 8px 10px; border-radius: 10px; background: rgba(255,255,255,.08); margin-bottom: 6px; animation: auIn .35s ease; }
    .au-feed .f i { font-size: 1.2rem; }
    .au-feed .f small { color: #c7d2fe; }
    @keyframes auIn { from { opacity: 0; transform: translateY(-6px); } to { opacity: 1; transform: none; } }
    .au-flash { position: fixed; inset: 0; pointer-events: none; z-index: 9999; opacity: 0; transition: opacity .25s; }
    .au-flash.ok { background: rgba(34,197,94,.25); }
    .au-flash.bad { background: rgba(239,68,68,.25); }
    .au-flash.show { opacity: 1; }
    .au-row-actions .btn { padding: 2px 8px; font-size: .75rem; }
</style>

<div class="au-flash" id="auFlash"></div>

<div class="content-wrapper">
<div class="container-xxl flex-grow-1 container-p-y">

{{-- RINGKASAN --}}
<div class="card mb-4">
    <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-start">
        <div>
            <h5 class="mb-1">{{ $audit->title }} <span class="au-chip {{ $chip[$audit->status] ?? 'mute' }} ms-1">{{ $audit->statusLabel() }}</span></h5>
            <small class="text-muted">
                {{ $audit->audit_no }} · {{ $audit->branch?->branch_name ?? 'Semua cabang' }} · {{ $audit->location_filter ?: 'Semua lokasi' }}
                · dimulai {{ $audit->created_at->translatedFormat('d M Y H:i') }} oleh {{ $audit->starter?->name }}
                @if($audit->completed_at) · {{ $audit->statusLabel() }} {{ $audit->completed_at->translatedFormat('d M Y H:i') }} oleh {{ $audit->completer?->name }} @endif
            </small>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('asset-audits.index') }}" class="btn btn-outline-secondary">Kembali</a>
            @if(auth()->user()->hasPermission('asset_audit.view'))
                <a href="{{ route('asset-audits.export', $audit->id) }}" class="btn btn-success"><i class="fa-solid fa-file-excel"></i> Excel</a>
            @endif
            @if($canManage)
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalComplete"><i class="bi bi-flag-fill me-1"></i> Selesaikan Audit</button>
            @endif
        </div>
    </div>
    <div class="card-body">
        <div class="d-flex justify-content-between mb-1">
            <strong>Progres pengecekan</strong>
            <span><b id="stChecked">{{ $stats['checked'] }}</b> / <span id="stTotal">{{ $stats['total'] }}</span> (<span id="stPct">{{ $stats['percent'] }}</span>%)</span>
        </div>
        <div class="au-bar mb-3"><span id="stBar" style="width: {{ $stats['percent'] }}%"></span></div>
        <div class="au-tiles">
            @foreach($tiles as $key => [$label, $value, $icon, $color])
                <a href="{{ route('asset-audits.show', ['audit' => $audit->id, 'f' => $key]) }}" class="au-tile {{ $filter === $key ? 'on' : '' }}">
                    <div class="v" style="color: {{ $color }}" id="tile-{{ $key }}">{{ $value }}</div>
                    <div class="l"><i class="bi {{ $icon }}"></i> {{ $label }}</div>
                </a>
            @endforeach
        </div>
        @if($audit->notes)<div class="alert alert-light border mt-3 mb-0"><strong>Catatan:</strong> {{ $audit->notes }}</div>@endif
    </div>
</div>

<div class="row g-4">
    {{-- PANEL SCAN --}}
    @if($canScan)
    <div class="col-xl-4 order-xl-2">
        <div class="card au-scan">
            <div class="card-body">
                <h5 class="text-white mb-3"><i class="bi bi-qr-code-scan me-1"></i> Scan aset</h5>

                <div class="mb-2">
                    <label class="mb-1">Lokasi saya sekarang</label>
                    <input type="text" id="scanLocation" class="form-control" list="knownLocations" placeholder="Mis. Lantai 2 — Ruang Finance">
                    <datalist id="knownLocations">@foreach($locations as $loc)<option value="{{ $loc }}">@endforeach</datalist>
                    <small style="color:#93a4d8">Isi agar aset yang berada di lokasi lain otomatis ditandai "salah lokasi".</small>
                </div>
                <div class="mb-3">
                    <label class="mb-1">Kondisi aset</label>
                    <select id="scanCondition" class="form-select">
                        @foreach(\App\Models\AssetAuditItem::CONDITIONS as $k => $v)
                            <option value="{{ $k }}">{{ $v }}</option>
                        @endforeach
                    </select>
                </div>

                <button type="button" class="btn btn-light w-100 fw-bold mb-2" id="btnCamera"><i class="bi bi-camera-fill me-1"></i> Scan pakai kamera</button>
                <div id="reader" class="mb-2" hidden></div>

                <form id="formScan" class="d-flex gap-2 mb-3" autocomplete="off">
                    <input type="text" id="scanCode" class="form-control au-code" placeholder="Atau ketik / scanner USB" autofocus>
                    <button class="btn btn-primary"><i class="bi bi-arrow-return-left"></i></button>
                </form>

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <small style="color:#c7d2fe">Hasil scan terakhir</small>
                    <div class="form-check form-switch m-0">
                        <input class="form-check-input" type="checkbox" id="scanSound" checked>
                        <label class="form-check-label" for="scanSound" style="font-size:.75rem">Suara</label>
                    </div>
                </div>
                <div class="au-feed" id="scanFeed"><small style="color:#93a4d8">Belum ada scan.</small></div>
            </div>
        </div>
    </div>
    @endif

    {{-- DAFTAR ASET --}}
    <div class="{{ $canScan ? 'col-xl-8 order-xl-1' : 'col-12' }}">
        <div class="card">
            <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
                <h6 class="mb-0">Daftar aset · {{ $tiles[$filter][0] ?? 'Total aset' }}</h6>
                <form method="GET" class="d-flex gap-2">
                    <input type="hidden" name="f" value="{{ $filter }}">
                    <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Cari kode / nama / lokasi">
                    <button class="btn btn-sm btn-outline-primary"><i class="bi bi-search"></i></button>
                </form>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Aset</th>
                                <th>Lokasi tercatat</th>
                                <th>Hasil</th>
                                <th>Dicek</th>
                                @if($canScan)<th class="text-end">Tandai manual</th>@endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($items as $item)
                                @php [$lbl, $cls] = $stateChip[$item->state()]; @endphp
                                <tr id="item-{{ $item->id }}">
                                    <td>
                                        <div class="fw-bold">{{ $item->asset_code }}</div>
                                        <small class="text-muted">{{ $item->asset_name }}</small>
                                    </td>
                                    <td>
                                        {{ $item->expected_location ?: '—' }}
                                        @if($item->location_mismatch)
                                            <small class="d-block text-warning"><i class="bi bi-arrow-return-right"></i> ditemukan di {{ $item->found_location }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="au-chip {{ $cls }}" data-state>{{ $lbl }}</span>
                                        @if($item->conditionLabel() && $item->condition !== 'good')
                                            <small class="d-block text-danger mt-1"><i class="bi bi-tools"></i> {{ $item->conditionLabel() }}</small>
                                        @endif
                                        @if($item->notes)<small class="d-block text-muted">{{ $item->notes }}</small>@endif
                                    </td>
                                    <td class="text-muted small" data-checked>
                                        @if($item->checked_at){{ $item->checked_at->translatedFormat('d M H:i') }}<br>{{ $item->checker?->name }}@else — @endif
                                    </td>
                                    @if($canScan)
                                        <td class="text-end au-row-actions text-nowrap">
                                            @if($item->in_scope)
                                                <button class="btn btn-outline-success" data-mark="found" data-id="{{ $item->id }}" title="Ada"><i class="bi bi-check-lg"></i></button>
                                                <button class="btn btn-outline-danger" data-mark="missing" data-id="{{ $item->id }}" title="Tidak ada"><i class="bi bi-x-lg"></i></button>
                                            @endif
                                            @if($item->result)
                                                <button class="btn btn-outline-secondary" data-mark="reset" data-id="{{ $item->id }}" title="Batalkan"><i class="bi bi-arrow-counterclockwise"></i></button>
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">Tidak ada aset pada filter ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $items->links() }}
            </div>
        </div>
    </div>
</div>

</div>
</div>

@if($canManage)
<div class="modal fade" id="modalComplete" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('asset-audits.complete', $audit->id) }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Selesaikan audit?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p><b id="cmPending">{{ $stats['pending'] }}</b> aset yang belum dicek akan ditandai <b class="text-danger">Tidak ditemukan</b>. Audit yang sudah selesai tidak bisa di-scan lagi.</p>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="apply_locations" value="1" id="applyLoc" checked>
                        <label class="form-check-label" for="applyLoc">
                            Perbarui lokasi aset yang <b>salah lokasi</b> (<span id="cmMisplaced">{{ $stats['misplaced'] }}</span> aset) sesuai lokasi saat ditemukan. Perpindahan tercatat di riwayat lokasi aset.
                        </label>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-outline-danger" id="btnCancelAudit">Batalkan audit</button>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Kembali</button>
                        <button type="submit" class="btn btn-primary">Ya, selesaikan</button>
                    </div>
                </div>
            </div>
        </form>
        <form method="POST" action="{{ route('asset-audits.cancel', $audit->id) }}" id="formCancelAudit">@csrf</form>
    </div>
</div>
@endif
@endsection

@section('js')
@if($canScan)
<script src="{{ asset('assets/vendor/html5-qrcode/html5-qrcode.min.js') }}"></script>
@endif
<script>
(function () {
    const csrf = $('meta[name="csrf-token"]').attr('content');
    const scanUrl = @json(route('asset-audits.scan', $audit->id));
    const markUrl = @json(url('dashboard/asset-audits/' . $audit->id . '/items'));
    const storeKey = 'vasetra-audit-loc-{{ $audit->id }}';
    const esc = s => $('<div>').text(s ?? '').html();
    const stateMeta = {
        found: ['Ditemukan', 'ok', 'bi-check-circle-fill', '#22c55e'],
        misplaced: ['Salah lokasi', 'warn', 'bi-geo-alt-fill', '#f59e0b'],
        missing: ['Tidak ditemukan', 'bad', 'bi-x-circle-fill', '#ef4444'],
        extra: ['Di luar daftar', 'extra', 'bi-plus-circle-fill', '#a78bfa'],
        pending: ['Belum dicek', 'mute', 'bi-hourglass-split', '#9ca3af'],
    };

    function updateStats(s) {
        if (!s) return;
        $('#stChecked').text(s.checked); $('#stTotal').text(s.total); $('#stPct').text(s.percent);
        $('#stBar').css('width', s.percent + '%');
        $('#tile-all').text(s.total); $('#tile-pending').text(s.pending); $('#tile-found').text(s.found);
        $('#tile-misplaced').text(s.misplaced); $('#tile-damaged').text(s.damaged); $('#tile-missing').text(s.missing); $('#tile-extra').text(s.extra);
        $('#cmPending').text(s.pending); $('#cmMisplaced').text(s.misplaced);
    }

    function updateRow(item) {
        const row = $('#item-' + item.id);
        if (!row.length) return;
        const m = stateMeta[item.state] || stateMeta.pending;
        row.find('[data-state]').attr('class', 'au-chip ' + m[1]).text(m[0]);
        row.find('[data-checked]').text(item.checked_at || '—');
    }

    // ---------- umpan balik ----------
    let audioCtx = null;
    function beep(ok) {
        if (!$('#scanSound').is(':checked')) return;
        try {
            audioCtx = audioCtx || new (window.AudioContext || window.webkitAudioContext)();
            const o = audioCtx.createOscillator(), g = audioCtx.createGain();
            o.frequency.value = ok ? 1046 : 220; o.type = ok ? 'sine' : 'square';
            g.gain.value = .08; o.connect(g); g.connect(audioCtx.destination);
            o.start(); o.stop(audioCtx.currentTime + (ok ? .12 : .3));
        } catch (e) {}
        if (navigator.vibrate) navigator.vibrate(ok ? 60 : [80, 60, 80]);
    }
    function flash(ok) {
        const el = document.getElementById('auFlash');
        el.className = 'au-flash ' + (ok ? 'ok' : 'bad') + ' show';
        setTimeout(() => el.classList.remove('show'), 280);
    }
    function feed(icon, color, title, sub) {
        const box = $('#scanFeed');
        if (box.find('.f').length === 0) box.empty();
        box.prepend(`<div class="f"><i class="bi ${icon}" style="color:${color}"></i><div><div class="fw-bold">${title}</div><small>${sub}</small></div></div>`);
        box.find('.f').slice(15).remove();
    }

    // ---------- scan ----------
    let busy = false, lastCode = '', lastAt = 0;
    function submitCode(code) {
        code = (code || '').trim();
        if (!code || busy) return;
        const now = Date.now();
        if (code === lastCode && now - lastAt < 2500) return; // kamera membaca QR yang sama berulang
        lastCode = code; lastAt = now; busy = true;

        $.ajax({
            url: scanUrl, type: 'POST', headers: { 'Accept': 'application/json' },
            data: { _token: csrf, code: code, location: $('#scanLocation').val(), condition: $('#scanCondition').val() },
        }).done(res => {
            const it = res.item, m = stateMeta[it.state] || stateMeta.found;
            const good = it.state === 'found';
            beep(good || it.state !== 'missing'); flash(good);
            feed(m[2], m[3], esc(it.code) + ' · ' + esc(it.name), esc(res.message) + (it.condition && it.condition !== 'Baik' ? ' · ' + esc(it.condition) : ''));
            updateStats(res.stats); updateRow(it);
            $('#scanCondition').val('good');
        }).fail(xhr => {
            beep(false); flash(false);
            feed('bi-exclamation-triangle-fill', '#ef4444', 'Tidak dikenali', esc(xhr.responseJSON?.message || 'Gagal memproses scan.'));
        }).always(() => { busy = false; $('#scanCode').val('').trigger('focus'); });
    }

    $('#formScan').on('submit', function (e) { e.preventDefault(); submitCode($('#scanCode').val()); });

    // Lokasi diingat per audit di browser ini.
    try { $('#scanLocation').val(localStorage.getItem(storeKey) || ''); } catch (e) {}
    $('#scanLocation').on('change', function () { try { localStorage.setItem(storeKey, this.value); } catch (e) {} });

    // ---------- kamera ----------
    let scanner = null, running = false;
    $('#btnCamera').on('click', async function () {
        if (typeof Html5Qrcode === 'undefined') return;
        const btn = $(this);
        if (running) {
            await scanner.stop().catch(() => {});
            running = false; $('#reader').prop('hidden', true);
            btn.html('<i class="bi bi-camera-fill me-1"></i> Scan pakai kamera');
            return;
        }
        $('#reader').prop('hidden', false);
        scanner = scanner || new Html5Qrcode('reader');
        try {
            await scanner.start({ facingMode: 'environment' }, { fps: 10, qrbox: { width: 230, height: 230 } }, txt => submitCode(txt), () => {});
            running = true;
            btn.html('<i class="bi bi-stop-circle me-1"></i> Matikan kamera');
        } catch (err) {
            $('#reader').prop('hidden', true);
            Swal.fire({ icon: 'error', title: 'Kamera tidak bisa dibuka', text: 'Izinkan akses kamera di browser (situs harus dibuka lewat https), atau gunakan kolom ketik / scanner USB.' });
        }
    });

    // ---------- tandai manual ----------
    $(document).on('click', '[data-mark]', function () {
        const id = $(this).data('id'), action = $(this).data('mark');
        $.ajax({ url: markUrl + '/' + id, type: 'POST', headers: { 'Accept': 'application/json' }, data: { _token: csrf, action: action } })
            .done(res => {
                updateStats(res.stats);
                if (res.removed) { $('#item-' + id).fadeOut(); return; }
                updateRow(res.item);
            })
            .fail(xhr => Swal.fire({ icon: 'error', title: 'Gagal', text: xhr.responseJSON?.message || 'Terjadi kesalahan.' }));
    });

    // ---------- batalkan audit ----------
    $('#btnCancelAudit').on('click', function () {
        Swal.fire({ icon: 'warning', title: 'Batalkan audit ini?', text: 'Hasil scan tetap tersimpan tetapi audit ditandai dibatalkan.', showCancelButton: true, confirmButtonText: 'Ya, batalkan', cancelButtonText: 'Tidak' })
            .then(r => { if (r.isConfirmed) document.getElementById('formCancelAudit').submit(); });
    });
})();
</script>
@endsection
