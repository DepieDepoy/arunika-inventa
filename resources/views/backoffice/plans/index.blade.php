@extends('backoffice.layouts.app')

@section('title', 'Paket & Harga')

@section('content')

@include('backoffice.partials.style')

@php
    $gb = fn ($bytes) => $bytes ? rtrim(rtrim(number_format($bytes / 1024 ** 3, 2, '.', ''), '0'), '.') : '';
@endphp

<div class="container-xxl flex-grow-1 container-p-y">

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

    <form method="POST" action="{{ route('backoffice.plans.update') }}" class="cms-card">
        @csrf
        <div class="p-3 p-md-4">

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr class="small text-muted text-uppercase">
                            <th style="min-width:200px">Paket</th>
                            <th style="min-width:150px">Harga / bulan (Rp)</th>
                            <th style="min-width:160px">Harga / tahun (Rp)</th>
                            <th style="min-width:125px">Maks. user</th>
                            <th style="min-width:125px">Maks. aset</th>
                            <th style="min-width:130px">Storage (MB)</th>
                            <th>Aktif</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($plans as $plan)
                            @php $isFree = strtoupper($plan->plan_code) === 'FREE'; $p = "plans[{$plan->id}]"; @endphp
                            <tr>
                                <td>
                                    <input type="text" class="form-control form-control-sm fw-bold mb-1" name="{{ $p }}[plan_name]" value="{{ old("plans.{$plan->id}.plan_name", $plan->plan_name) }}" required>
                                    <input type="text" class="form-control form-control-sm" name="{{ $p }}[description]" value="{{ old("plans.{$plan->id}.description", $plan->description) }}" placeholder="Deskripsi singkat (Indonesia)">
                                    <input type="text" class="form-control form-control-sm mt-1" name="{{ $p }}[description_en]" value="{{ old("plans.{$plan->id}.description_en", $plan->description_en) }}" placeholder="Short description (English)">
                                    <div class="small text-muted mt-1">
                                        <code>{{ $plan->plan_code }}</code> · {{ (int) ($activeSubscriptions[$plan->id] ?? 0) }} langganan aktif
                                    </div>
                                </td>
                                @if($isFree)
                                    <td colspan="2">
                                        <input type="hidden" name="{{ $p }}[price_monthly]" value="0">
                                        <input type="hidden" name="{{ $p }}[price_yearly]" value="0">
                                        <label class="small fw-bold mb-1">Masa coba gratis (hari)</label>
                                        <input type="number" class="form-control form-control-sm" name="{{ $p }}[trial_days]" min="1" max="90" value="{{ old("plans.{$plan->id}.trial_days", $plan->trial_days) }}" required>
                                        <div class="small text-muted mt-1">Berlaku untuk pendaftar baru.</div>
                                    </td>
                                @else
                                    <td><input type="number" class="form-control form-control-sm price-m" name="{{ $p }}[price_monthly]" min="0" step="1000" value="{{ old("plans.{$plan->id}.price_monthly", (int) $plan->price_monthly) }}" required></td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm price-y" name="{{ $p }}[price_yearly]" min="0" step="1000" value="{{ old("plans.{$plan->id}.price_yearly", (int) $plan->price_yearly) }}" required>
                                        <div class="small text-muted mt-1 yearly-hint"></div>
                                    </td>
                                @endif
                                <td><input type="number" class="form-control form-control-sm" name="{{ $p }}[max_users]" min="1" value="{{ old("plans.{$plan->id}.max_users", $plan->max_users) }}" placeholder="Tanpa batas"></td>
                                <td><input type="number" class="form-control form-control-sm" name="{{ $p }}[max_assets]" min="1" value="{{ old("plans.{$plan->id}.max_assets", $plan->max_assets) }}" placeholder="Tanpa batas"></td>
                                <td>
                                    <input type="number" class="form-control form-control-sm" name="{{ $p }}[storage_mb]" min="100" step="1" value="{{ old("plans.{$plan->id}.storage_mb", (int) round($plan->max_storage_bytes / 1048576)) }}" required>
                                    <div class="small text-muted mt-1">≈ {{ $gb($plan->max_storage_bytes) }} GB</div>
                                </td>
                                <td>
                                    @if($isFree)
                                        <input type="hidden" name="{{ $p }}[status]" value="1">
                                        <span class="cms-chip cms-chip-blue">Selalu</span>
                                    @else
                                        <input type="hidden" name="{{ $p }}[status]" value="0">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="{{ $p }}[status]" value="1" @checked(old("plans.{$plan->id}.status", $plan->status) == 1)>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="cms-help mb-4">Kosongkan "Maks. user" / "Maks. aset" untuk tanpa batas. Paket nonaktif tidak tampil di landing maupun halaman Langganan.</div>

            <div class="row g-4">
                <div class="col-lg-5">
                    <h6 class="fw-bold mb-2"><i class="bi bi-star me-1 text-warning"></i> Paket rekomendasi di landing</h6>
                    <select name="recommended_plan" class="form-select">
                        <option value="">Otomatis (paket berbayar kedua)</option>
                        @foreach($plans->where('plan_code', '!=', 'FREE') as $plan)
                            <option value="{{ $plan->plan_code }}" @selected($recommended === $plan->plan_code)>{{ $plan->plan_name }}</option>
                        @endforeach
                    </select>
                    <div class="cms-help">Kartu paket ini diberi label "REKOMENDASI" dan disorot.</div>
                </div>

                <div class="col-lg-7">
                    <h6 class="fw-bold mb-2"><i class="bi bi-hdd-stack me-1 text-primary"></i> Add-on storage</h6>
                    @if($storage)
                        <div class="row g-2">
                            <div class="col-sm-3">
                                <label class="small fw-bold">Per unit (MB)</label>
                                <input type="number" class="form-control form-control-sm" name="storage[unit_mb]" min="64" value="{{ old('storage.unit_mb', (int) round($storage->unit_value / 1048576)) }}" required>
                            </div>
                            <div class="col-sm-3">
                                <label class="small fw-bold">Rp / bulan</label>
                                <input type="number" class="form-control form-control-sm" name="storage[price_monthly]" min="0" step="1000" value="{{ old('storage.price_monthly', (int) $storage->price_monthly) }}" required>
                            </div>
                            <div class="col-sm-3">
                                <label class="small fw-bold">Rp / tahun</label>
                                <input type="number" class="form-control form-control-sm" name="storage[price_yearly]" min="0" step="1000" value="{{ old('storage.price_yearly', (int) $storage->price_yearly) }}" required>
                            </div>
                            <div class="col-sm-3">
                                <label class="small fw-bold">Maks. unit</label>
                                <input type="number" class="form-control form-control-sm" name="storage[max_units]" min="1" value="{{ old('storage.max_units', $storage->max_units) }}" required>
                            </div>
                        </div>
                        <input type="hidden" name="storage[is_active]" value="0">
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" name="storage[is_active]" value="1" id="storageActive" @checked($storage->is_active)>
                            <label class="form-check-label small" for="storageActive">Add-on storage dijual</label>
                        </div>
                    @else
                        <div class="text-muted small">Tarif add-on storage belum ada di database.</div>
                    @endif
                </div>
            </div>
        </div>

        <div class="cms-sticky">
            <span class="small text-muted align-self-center"><i class="bi bi-info-circle me-1"></i> Setiap perubahan tercatat di audit trail.</span>
            <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Simpan paket &amp; harga</button>
        </div>
    </form>
</div>

@endsection

@section('js')
<script>
// Info hemat tahunan dibanding bayar bulanan.
document.querySelectorAll('tbody tr').forEach(function (tr) {
    var m = tr.querySelector('.price-m'), y = tr.querySelector('.price-y'), hint = tr.querySelector('.yearly-hint');
    if (!m || !y || !hint) return;
    var update = function () {
        var monthly = parseInt(m.value || 0, 10), yearly = parseInt(y.value || 0, 10);
        if (!monthly || !yearly) { hint.textContent = ''; return; }
        var saved = monthly * 12 - yearly;
        hint.textContent = saved > 0
            ? 'Hemat Rp' + saved.toLocaleString('id-ID') + ' (' + (saved / monthly).toFixed(1).replace('.0', '') + ' bln)'
            : 'Tidak lebih hemat dari bulanan';
    };
    m.addEventListener('input', update); y.addEventListener('input', update); update();
});
</script>
@endsection
