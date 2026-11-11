@extends('backoffice.layouts.app')

@section('title', 'Paket & Harga')

@section('content')

@include('backoffice.partials.style')

@php
    $gb = fn ($bytes) => $bytes ? rtrim(rtrim(number_format($bytes / 1024 ** 3, 2, '.', ''), '0'), '.') : '';
    $accents = ['FREE' => '#64748b', 'STARTER' => '#0ea5e9', 'PROFESSIONAL' => '#3a57e8', 'ENTERPRISE' => '#7c3aed'];
@endphp

<style>
    /* 2 kolom di layar lebar (4 paket = 2 x 2), 1 kolom di HP. */
    .pl-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 440px), 1fr)); gap: 18px; }
    .pl-card { position: relative; display: flex; flex-direction: column; overflow: hidden; }
    .pl-card::before { content: ''; position: absolute; inset: 0 0 auto 0; height: 4px; background: var(--pl-accent, #3a57e8); }
    .pl-card.is-off { opacity: .72; }
    .pl-card.is-rec { box-shadow: 0 0 0 2px var(--pl-accent, #3a57e8), 0 12px 28px -18px rgba(15, 27, 45, .35); }
    .pl-head { padding: 18px 18px 12px; display: flex; gap: 10px; align-items: flex-start; justify-content: space-between; }
    .pl-name { font-size: 1.05rem; font-weight: 800; border: 1px dashed transparent; padding: 4px 8px; margin-left: -8px; border-radius: 8px; width: 100%; background: transparent; }
    .pl-name:hover, .pl-name:focus { border-color: rgba(15, 27, 45, .2); background: #fff; outline: none; }
    .pl-meta { font-size: .74rem; color: #8592a6; display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
    .pl-sec { padding: 12px 18px; border-top: 1px solid rgba(15, 27, 45, .06); }
    .pl-sec-title { font-size: .68rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: #8592a6; margin-bottom: 8px; display: flex; align-items: center; gap: 6px; }
    .pl-sec .form-label { font-size: .74rem; font-weight: 700; color: #4b5a6e; margin-bottom: 3px; }
    .pl-sec .input-group-text { font-size: .76rem; font-weight: 700; background: #f5f7fb; color: #64748b; }
    .pl-quota .form-control { text-align: right; }
    .pl-quota .input-group-text i { color: var(--pl-accent, #3a57e8); }
    .pl-hint { font-size: .72rem; color: #8592a6; margin-top: 3px; min-height: 1em; }
    .pl-hint.good { color: #1a8754; font-weight: 600; }
    .pl-hint.bad { color: #c2410c; font-weight: 600; }
    .pl-trial { border-radius: 12px; background: #f5f7fb; padding: 12px; }
    .pl-foot { margin-top: auto; padding: 10px 18px; background: #fafbfd; border-top: 1px solid rgba(15, 27, 45, .06); font-size: .74rem; color: #8592a6; display: flex; justify-content: space-between; gap: 8px; flex-wrap: wrap; }
    .pl-side .cms-card { height: 100%; }
    .pl-legend { display: flex; gap: 14px; flex-wrap: wrap; font-size: .76rem; color: #64748b; }
    .pl-legend i { color: #3a57e8; }
</style>

<div class="container-xxl flex-grow-1 container-p-y">

    {{-- HEADER --}}
    <div class="cms-card p-3 p-md-4 mb-4 d-flex flex-wrap justify-content-between align-items-start gap-3">
        <div>
            <span class="cms-badge"><i class="bi bi-shield-lock"></i> Khusus pegawai Vasetra</span>
            <h4 class="mb-1 mt-2">Paket &amp; Harga</h4>
            <div class="text-muted small">
                Harga, kuota, dan masa coba yang tampil di landing page dan halaman Langganan pelanggan.
                <strong>Perubahan hanya berlaku untuk pembelian berikutnya</strong> — langganan yang sedang berjalan tidak berubah.
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('backoffice.cms.index') }}" class="btn btn-outline-primary"><i class="bi bi-window-sidebar me-1"></i> Konten Landing</a>
            <a href="{{ url('/#harga') }}" target="_blank" class="btn btn-primary"><i class="bi bi-box-arrow-up-right me-1"></i> Lihat harga di landing</a>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-1"></i> {{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('backoffice.plans.update') }}" id="planForm">
        @csrf

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h6 class="fw-bold mb-0"><i class="bi bi-boxes me-1 text-primary"></i> Daftar paket</h6>
            <div class="pl-legend">
                <span><i class="bi bi-infinity"></i> Kosongkan kuota = tanpa batas</span>
                <span><i class="bi bi-diagram-3"></i> Cabang termasuk Kantor Pusat</span>
                <span><i class="bi bi-eye-slash"></i> Paket nonaktif tidak tampil di landing &amp; Langganan</span>
            </div>
        </div>

        {{-- KARTU PAKET --}}
        <div class="pl-grid mb-4">
            @foreach($plans as $plan)
                @php
                    $isFree = strtoupper($plan->plan_code) === 'FREE';
                    $p = "plans[{$plan->id}]";
                    $o = fn ($field, $default) => old("plans.{$plan->id}.{$field}", $default);
                    $active = (int) $o('status', $plan->status) === 1 || $isFree;
                    $accent = $accents[strtoupper($plan->plan_code)] ?? '#3a57e8';
                    $isRec = $recommended === $plan->plan_code;
                @endphp
                <div class="cms-card pl-card {{ $active ? '' : 'is-off' }} {{ $isRec ? 'is-rec' : '' }}" style="--pl-accent: {{ $accent }}">

                    {{-- Judul paket --}}
                    <div class="pl-head">
                        <div class="flex-grow-1">
                            <input type="text" class="pl-name" name="{{ $p }}[plan_name]" value="{{ $o('plan_name', $plan->plan_name) }}" required aria-label="Nama paket">
                            <div class="pl-meta mt-1">
                                <code>{{ $plan->plan_code }}</code>
                                <span><i class="bi bi-people"></i> {{ (int) ($activeSubscriptions[$plan->id] ?? 0) }} langganan aktif</span>
                                @if($isRec)<span class="cms-chip cms-chip-blue"><i class="bi bi-star-fill"></i> Rekomendasi</span>@endif
                            </div>
                        </div>
                        <div class="text-end">
                            @if($isFree)
                                <input type="hidden" name="{{ $p }}[status]" value="1">
                                <span class="cms-chip cms-chip-blue">Selalu aktif</span>
                            @else
                                <input type="hidden" name="{{ $p }}[status]" value="0">
                                <div class="form-check form-switch m-0" title="Tampilkan paket">
                                    <input class="form-check-input pl-status" type="checkbox" name="{{ $p }}[status]" value="1" @checked($active)>
                                </div>
                                <small class="text-muted pl-status-label">{{ $active ? 'Aktif' : 'Nonaktif' }}</small>
                            @endif
                        </div>
                    </div>

                    {{-- Harga / masa coba --}}
                    <div class="pl-sec">
                        <div class="pl-sec-title"><i class="bi bi-cash-coin"></i> {{ $isFree ? 'Masa coba gratis' : 'Harga' }}</div>
                        @if($isFree)
                            <input type="hidden" name="{{ $p }}[price_monthly]" value="0">
                            <input type="hidden" name="{{ $p }}[price_yearly]" value="0">
                            <div class="pl-trial">
                                <div class="input-group input-group-sm">
                                    <input type="number" class="form-control" name="{{ $p }}[trial_days]" min="1" max="90" value="{{ $o('trial_days', $plan->trial_days) }}" required>
                                    <span class="input-group-text">hari</span>
                                </div>
                                <div class="pl-hint">Gratis untuk pendaftar baru, berlaku sejak registrasi.</div>
                            </div>
                        @else
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label">Per bulan</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control price-m" name="{{ $p }}[price_monthly]" min="0" step="1" value="{{ $o('price_monthly', (int) $plan->price_monthly) }}" required>
                                    </div>
                                    <div class="pl-hint price-m-hint"></div>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Per tahun</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control price-y" name="{{ $p }}[price_yearly]" min="0" step="1" value="{{ $o('price_yearly', (int) $plan->price_yearly) }}" required>
                                    </div>
                                    <div class="pl-hint yearly-hint"></div>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Kuota --}}
                    <div class="pl-sec pl-quota">
                        <div class="pl-sec-title"><i class="bi bi-sliders"></i> Kuota</div>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label">Maks. user</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="bi bi-people-fill"></i></span>
                                    <input type="number" class="form-control" name="{{ $p }}[max_users]" min="1" value="{{ $o('max_users', $plan->max_users) }}" placeholder="∞">
                                </div>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Maks. aset</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="bi bi-box-seam-fill"></i></span>
                                    <input type="number" class="form-control" name="{{ $p }}[max_assets]" min="1" value="{{ $o('max_assets', $plan->max_assets) }}" placeholder="∞">
                                </div>
                            </div>
                            <div class="col-12 col-sm-6">
                                <label class="form-label">Maks. cabang</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="bi bi-diagram-3-fill"></i></span>
                                    <input type="number" class="form-control" name="{{ $p }}[max_branches]" min="1" value="{{ $o('max_branches', $plan->max_branches) }}" placeholder="∞">
                                </div>
                            </div>
                            <div class="col-12 col-sm-6">
                                <label class="form-label">Storage</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="bi bi-hdd-fill"></i></span>
                                    <input type="number" class="form-control storage-mb" name="{{ $p }}[storage_mb]" min="100" step="1" value="{{ $o('storage_mb', (int) round($plan->max_storage_bytes / 1048576)) }}" required>
                                    <span class="input-group-text">MB</span>
                                </div>
                                <div class="pl-hint storage-hint">≈ {{ $gb($plan->max_storage_bytes) }} GB</div>
                            </div>
                        </div>
                    </div>

                    {{-- Deskripsi --}}
                    <div class="pl-sec">
                        <div class="pl-sec-title"><i class="bi bi-chat-left-text"></i> Deskripsi di landing</div>
                        <div class="input-group input-group-sm mb-2">
                            <span class="input-group-text">ID</span>
                            <input type="text" class="form-control" name="{{ $p }}[description]" value="{{ $o('description', $plan->description) }}" maxlength="255" placeholder="Deskripsi singkat (Indonesia)">
                        </div>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">EN</span>
                            <input type="text" class="form-control" name="{{ $p }}[description_en]" value="{{ $o('description_en', $plan->description_en) }}" maxlength="255" placeholder="Short description (English)">
                        </div>
                    </div>

                    <div class="pl-foot">
                        <span><i class="bi bi-clock-history me-1"></i> Diubah {{ $plan->updated_at?->translatedFormat('d M Y H:i') ?? '—' }}</span>
                        @if(!$isFree)<span class="per-month-equiv"></span>@endif
                    </div>
                </div>
            @endforeach
        </div>

        {{-- PENGATURAN LAIN --}}
        <div class="row g-4 mb-4 pl-side">
            <div class="col-lg-5">
                <div class="cms-card p-3 p-md-4">
                    <h6 class="fw-bold mb-1"><i class="bi bi-star me-1 text-warning"></i> Paket rekomendasi di landing</h6>
                    <div class="cms-help mb-3">Kartu paket ini diberi label "REKOMENDASI" dan disorot di landing page.</div>
                    <select name="recommended_plan" class="form-select">
                        <option value="">Otomatis (paket berbayar kedua)</option>
                        @foreach($plans->where('plan_code', '!=', 'FREE') as $plan)
                            <option value="{{ $plan->plan_code }}" @selected($recommended === $plan->plan_code)>{{ $plan->plan_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="cms-card p-3 p-md-4">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                        <h6 class="fw-bold mb-0"><i class="bi bi-hdd-stack me-1 text-primary"></i> Add-on storage</h6>
                        @if($storage)
                            <input type="hidden" name="storage[is_active]" value="0">
                            <div class="form-check form-switch m-0">
                                <input class="form-check-input" type="checkbox" name="storage[is_active]" value="1" id="storageActive" @checked($storage->is_active)>
                                <label class="form-check-label small fw-semibold" for="storageActive">Dijual</label>
                            </div>
                        @endif
                    </div>
                    <div class="cms-help mb-3">Pelanggan bisa menambah storage per unit di halaman Langganan.</div>
                    @if($storage)
                        <div class="row g-2">
                            <div class="col-sm-6 col-xl-3">
                                <label class="form-label small fw-bold mb-1">Per unit</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" class="form-control" name="storage[unit_mb]" min="64" value="{{ old('storage.unit_mb', (int) round($storage->unit_value / 1048576)) }}" required>
                                    <span class="input-group-text">MB</span>
                                </div>
                            </div>
                            <div class="col-sm-6 col-xl-3">
                                <label class="form-label small fw-bold mb-1">Harga / bulan</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" name="storage[price_monthly]" min="0" step="1" value="{{ old('storage.price_monthly', (int) $storage->price_monthly) }}" required>
                                </div>
                            </div>
                            <div class="col-sm-6 col-xl-3">
                                <label class="form-label small fw-bold mb-1">Harga / tahun</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" name="storage[price_yearly]" min="0" step="1" value="{{ old('storage.price_yearly', (int) $storage->price_yearly) }}" required>
                                </div>
                            </div>
                            <div class="col-sm-6 col-xl-3">
                                <label class="form-label small fw-bold mb-1">Maks. unit</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" class="form-control" name="storage[max_units]" min="1" value="{{ old('storage.max_units', $storage->max_units) }}" required>
                                    <span class="input-group-text">unit</span>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-muted small">Tarif add-on storage belum ada di database.</div>
                    @endif
                </div>
            </div>
        </div>

        <div class="cms-card">
            <div class="cms-sticky">
                <span class="small text-muted align-self-center"><i class="bi bi-info-circle me-1"></i> Setiap perubahan tercatat di audit trail.</span>
                <span id="planFormError" class="small text-danger fw-semibold align-self-center d-none" role="alert"></span>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Simpan paket &amp; harga</button>
            </div>
        </div>
    </form>
</div>

@endsection

@section('js')
<script>
(function () {
    const rp = n => 'Rp' + Math.round(n).toLocaleString('id-ID');

    document.querySelectorAll('.pl-card').forEach(function (card) {
        // Hemat tahunan dibanding bayar bulanan.
        const m = card.querySelector('.price-m'), y = card.querySelector('.price-y');
        const hint = card.querySelector('.yearly-hint'), equiv = card.querySelector('.per-month-equiv');
        if (m && y && hint) {
            const update = function () {
                const monthly = parseInt(m.value || 0, 10), yearly = parseInt(y.value || 0, 10);
                hint.className = 'pl-hint yearly-hint';
                if (!monthly || !yearly) { hint.textContent = ''; if (equiv) equiv.textContent = ''; return; }
                const saved = monthly * 12 - yearly;
                if (saved > 0) {
                    hint.classList.add('good');
                    hint.textContent = 'Hemat ' + rp(saved) + ' (' + (saved / monthly).toFixed(1).replace('.0', '') + ' bln)';
                } else {
                    hint.classList.add('bad');
                    hint.textContent = 'Tidak lebih hemat dari bulanan';
                }
                if (equiv) equiv.textContent = 'Setara ' + rp(yearly / 12) + '/bln bila tahunan';
            };
            m.addEventListener('input', update);
            y.addEventListener('input', update);
            update();
        }

        // Storage MB -> GB.
        const s = card.querySelector('.storage-mb'), sh = card.querySelector('.storage-hint');
        if (s && sh) {
            s.addEventListener('input', function () {
                const gb = (parseInt(s.value || 0, 10) / 1024);
                sh.textContent = gb ? '≈ ' + gb.toFixed(2).replace(/\.?0+$/, '') + ' GB' : '';
            });
        }

        // Status aktif / nonaktif.
        const st = card.querySelector('.pl-status'), sl = card.querySelector('.pl-status-label');
        if (st) {
            st.addEventListener('change', function () {
                card.classList.toggle('is-off', !st.checked);
                if (sl) sl.textContent = st.checked ? 'Aktif' : 'Nonaktif';
            });
        }
    });

    // Isian ditolak browser (wajib diisi, min, dsb.): tampilkan pesan yang jelas,
    // jangan hanya memindahkan kursor ke kotak input.
    const form = document.getElementById('planForm');
    const errBox = document.getElementById('planFormError');
    if (form && errBox) {
        let shown = false;
        form.addEventListener('submit', function () { shown = false; errBox.classList.add('d-none'); }, true);
        form.querySelector('button[type="submit"]').addEventListener('click', function () { shown = false; });
        form.addEventListener('invalid', function (e) {
            const el = e.target;
            el.classList.add('is-invalid');
            el.addEventListener('input', function () { el.classList.remove('is-invalid'); }, { once: true });
            if (shown) return;
            shown = true;
            const card = el.closest('.pl-card');
            const plan = card ? (card.querySelector('.pl-name')?.value || '') : 'Add-on storage';
            const label = el.closest('[class*="col"]')?.querySelector('.form-label')?.textContent.trim() || el.getAttribute('aria-label') || 'Isian';
            errBox.textContent = (plan ? plan + ' – ' : '') + label + ': ' + el.validationMessage;
            errBox.classList.remove('d-none');
            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, true);
    }
})();
</script>
@endsection
