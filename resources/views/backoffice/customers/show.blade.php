@extends('backoffice.layouts.app')

@section('title', $company->company_name)

@section('content')
@include('backoffice.partials.style')
@include('backoffice.customers._style')
@php $statusIcon = \App\Services\Backoffice\CustomerDirectory::STATUS_ICON; @endphp
@php
    $statuses = \App\Services\Backoffice\CustomerDirectory::STATUS;
    $aPct = $company->max_assets ? bx_pct($company->assets_count, $company->max_assets) : null;
    $uPct = $company->max_users ? bx_pct($company->users_count, $company->max_users) : null;
    $sPct = bx_pct($company->storage_used, $company->storage_limit);
    $suspended = (int) $company->status !== 1;
    $payLabel = ['success' => ['Lunas', 'paid'], 'pending' => ['Menunggu', 'trial'], 'failed' => ['Gagal', 'suspended'], 'expired' => ['Kedaluwarsa', 'expired'], 'cancelled' => ['Dibatalkan', 'expired']];
    $cycleLabel = ['trial' => 'Trial', 'monthly' => 'Bulanan', 'yearly' => 'Tahunan'];
@endphp

<style>
    .bx-info { display:grid; grid-template-columns:repeat(auto-fill, minmax(170px, 1fr)); gap:12px; padding:16px 20px 20px; }
    .bx-info div { background:#f6f8fb; border-radius:12px; padding:12px 14px; }
    .bx-info span { display:block; font-size:.72rem; font-weight:700; color:#8592a6; text-transform:uppercase; letter-spacing:.05em; }
    .bx-info b { display:block; color:#0b2447; margin-top:4px; font-size:.95rem; }
    .bx-use { padding:16px 20px 20px; display:grid; gap:16px; }
    .bx-use .bx-meter .top { font-size:.84rem; }
    .bx-use .bx-meter .track { height:9px; }
    .bx-act { padding:16px 20px 20px; display:grid; gap:14px; }
    .bx-act form { border:1px solid rgba(15,27,45,.08); border-radius:12px; padding:14px; }
    .bx-act h6 { font-size:.86rem; font-weight:800; color:#0b2447; margin-bottom:4px; }
    .bx-act p { font-size:.78rem; color:#8592a6; margin-bottom:10px; }
</style>

<div class="container-xxl flex-grow-1 container-p-y">

    @if(session('success'))<div class="alert alert-success"><i class="bi bi-check2-circle me-1"></i> {{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-1"></i> {{ $errors->first() }}</div>@endif

    <div class="cms-card p-3 p-md-4 mb-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div class="bx-co">
            <span class="av" style="width:52px;height:52px;font-size:1.05rem;border-radius:14px">{{ bx_initials($company->company_name) }}</span>
            <span>
                <a href="{{ route('backoffice.customers.index') }}" class="text-muted small"><i class="bi bi-arrow-left"></i> Pelanggan</a>
                <h4 class="mb-1 mt-1">{{ $company->company_name }}</h4>
                <span class="bx-st {{ $company->customer_status }}"><i class="bi {{ $statusIcon[$company->customer_status] }}"></i> {{ $statuses[$company->customer_status] }}</span>
                <small class="text-muted ms-1">{{ $company->company_code }} · bergabung {{ $company->created_at?->locale('id')->translatedFormat('d F Y') }}</small>
            </span>
        </div>
        <div class="text-md-end">
            <div class="small text-muted">Paket berjalan</div>
            <div class="fw-bold text-dark fs-5">{{ $company->plan_label }}</div>
            @if($company->ends_at)
                <div class="small {{ $company->days_left !== null && $company->days_left <= 7 ? 'text-danger fw-bold' : 'text-muted' }}">
                    s/d {{ $company->ends_at->locale('id')->translatedFormat('d M Y') }} ({{ $company->days_left < 0 ? 'berakhir' : 'sisa ' . $company->days_left . ' hari' }})
                </div>
            @endif
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-4">
            <div class="cms-card h-100">
                <div class="bx-h">
                    <div><h6>Pemakaian kuota</h6><div class="hint">Dibanding batas paket</div></div>
                    <form method="POST" action="{{ route('backoffice.customers.refresh-storage', $company->id) }}">@csrf<button class="btn btn-sm btn-link p-0" title="Hitung ulang storage"><i class="bi bi-arrow-clockwise"></i></button></form>
                </div>
                <div class="bx-use">
                    <div class="bx-meter">
                        <div class="top"><span><i class="bi bi-box-seam me-1"></i> Aset</span><span>{{ bx_num($company->assets_count) }} / {{ $company->max_assets ? bx_num($company->max_assets) : '∞' }}</span></div>
                        <div class="track"><div class="fill {{ bx_level($aPct ?? 0) }}" style="width:{{ $aPct ?? 0 }}%"></div></div>
                    </div>
                    <div class="bx-meter">
                        <div class="top"><span><i class="bi bi-people me-1"></i> User</span><span>{{ bx_num($company->users_count) }} / {{ $company->max_users ? bx_num($company->max_users) : '∞' }}</span></div>
                        <div class="track"><div class="fill {{ bx_level($uPct ?? 0) }}" style="width:{{ $uPct ?? 0 }}%"></div></div>
                    </div>
                    <div class="bx-meter">
                        <div class="top"><span><i class="bi bi-hdd me-1"></i> Storage</span><span>{{ bx_bytes($company->storage_used) }} / {{ bx_bytes($company->storage_limit) }}</span></div>
                        <div class="track"><div class="fill {{ bx_level($sPct) }}" style="width:{{ $sPct }}%"></div></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-8">
            <div class="cms-card h-100">
                <div class="bx-h"><div><h6>Aktivitas perusahaan</h6><div class="hint">Gambaran seberapa aktif pelanggan memakai Vasetra</div></div></div>
                <div class="bx-info">
                    <div><span>Nilai aset</span><b>{{ bx_rp($activity['assets_value']) }}</b></div>
                    <div><span>Request maintenance</span><b>{{ bx_num($activity['maintenance_requests']) }} <small class="text-muted fw-normal">({{ bx_num($activity['maintenance_open']) }} terbuka)</small></b></div>
                    <div><span>Jadwal / riwayat maintenance</span><b>{{ bx_num($activity['maintenances']) }}</b></div>
                    <div><span>Kategori aset</span><b>{{ bx_num($activity['categories']) }}</b></div>
                    <div><span>Aktivitas 30 hari</span><b>{{ bx_num($activity['audit_30d']) }} <small class="text-muted fw-normal">perubahan</small></b></div>
                    <div><span>Login terakhir</span><b>{{ $company->last_activity_at ? \Illuminate\Support\Carbon::parse($company->last_activity_at)->locale('id')->diffForHumans() : 'Belum pernah' }}</b></div>
                    <div><span>Nilai per bulan</span><b>{{ $company->mrr ? bx_rp($company->mrr) : '—' }}</b></div>
                    <div><span>Sumber pendaftaran</span><b>{{ $signup?->source ?? '—' }}@if($signup?->utm_campaign)<small class="d-block text-muted fw-normal">{{ $signup->utm_campaign }}</small>@endif</b></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-8">
            <div class="cms-card h-100">
                <div class="bx-h"><div><h6>User ({{ $users->count() }})</h6><div class="hint">Akun di perusahaan ini</div></div></div>
                <div class="table-responsive mt-2">
                    <table class="table align-middle mb-0 bx-table">
                        <thead><tr><th>Nama</th><th>Role</th><th>Kontak</th><th>Login terakhir</th><th>Status</th></tr></thead>
                        <tbody>
                            @foreach($users as $u)
                                <tr>
                                    <td class="fw-bold text-dark">{{ $u->name }}</td>
                                    <td>{{ $u->role?->role_name ?? '—' }}</td>
                                    <td>{{ $u->email }}<small class="d-block text-muted">{{ $u->phone }}</small></td>
                                    <td class="text-nowrap">{{ $u->last_login_at ? \Illuminate\Support\Carbon::parse($u->last_login_at)->locale('id')->diffForHumans() : '—' }}<small class="d-block text-muted">{{ $u->last_login_ip }}</small></td>
                                    <td>
                                        @if((int) $u->status !== 1)<span class="bx-st suspended">Nonaktif</span>
                                        @elseif(!$u->email_verified_at)<span class="bx-st trial">Belum verifikasi</span>
                                        @else<span class="bx-st paid">Aktif</span>@endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="cms-card h-100">
                <div class="bx-h"><div><h6>Tindakan</h6><div class="hint">Tercatat di log aktivitas backoffice</div></div></div>
                <div class="bx-act">
                    <form method="POST" action="{{ route('backoffice.customers.extend-trial', $company->id) }}">
                        @csrf
                        <h6><i class="bi bi-hourglass-split me-1"></i> Perpanjang trial</h6>
                        <p>Untuk calon pelanggan yang butuh waktu evaluasi lebih lama.</p>
                        <div class="d-flex gap-2 flex-wrap">
                            <select name="days" class="form-select form-select-sm" style="width:auto">
                                @foreach([3, 7, 14, 30] as $d)<option value="{{ $d }}">+{{ $d }} hari</option>@endforeach
                            </select>
                            <input type="text" name="note" class="form-control form-control-sm" placeholder="Catatan (opsional)" maxlength="200" style="flex:1;min-width:120px">
                            <button class="btn btn-sm btn-primary" @disabled($company->customer_status === 'paid')>Perpanjang</button>
                        </div>
                        @if($company->customer_status === 'paid')<p class="mt-2 mb-0">Sudah berbayar — trial tidak perlu diperpanjang.</p>@endif
                    </form>

                    <form method="POST" action="{{ route('backoffice.customers.tera', $company->id) }}">
                        @csrf
                        <h6><i class="bi bi-stars me-1"></i> Tera AI {!! ($company->ai_enabled ?? true) ? '<span class="bx-st paid">aktif</span>' : '<span class="bx-st suspended">nonaktif</span>' !!}</h6>
                        <p>Asisten chat di dashboard &amp; aplikasi perusahaan ini.</p>
                        <button class="btn btn-sm {{ ($company->ai_enabled ?? true) ? 'btn-outline-secondary' : 'btn-primary' }}">{{ ($company->ai_enabled ?? true) ? 'Nonaktifkan Tera' : 'Aktifkan Tera' }}</button>
                    </form>

                    <form method="POST" action="{{ route('backoffice.customers.status', $company->id) }}" id="statusForm">
                        @csrf
                        @if($suspended)
                            <h6><i class="bi bi-play-circle me-1"></i> Aktifkan kembali</h6>
                            <p>Semua user perusahaan ini bisa login lagi.</p>
                            <button class="btn btn-sm btn-success">Aktifkan perusahaan</button>
                        @else
                            <h6 class="text-danger"><i class="bi bi-slash-circle me-1"></i> Tangguhkan perusahaan</h6>
                            <p>Semua user langsung keluar dan tidak bisa login (web &amp; aplikasi mobile). Data tidak dihapus.</p>
                            <input type="text" name="reason" class="form-control form-control-sm mb-2" placeholder="Alasan (wajib), mis. penyalahgunaan / permintaan pelanggan" maxlength="200" required>
                            <button type="button" class="btn btn-sm btn-outline-danger" id="btnSuspend">Tangguhkan</button>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-6">
            <div class="cms-card h-100">
                <div class="bx-h"><div><h6>Riwayat langganan</h6><div class="hint">Paket, upgrade &amp; add-on</div></div></div>
                <div class="table-responsive mt-2">
                    <table class="table align-middle mb-0 bx-table">
                        <thead><tr><th>Paket</th><th>Periode</th><th>Nilai</th><th>Status</th></tr></thead>
                        <tbody>
                            @forelse($subscriptions as $sub)
                                <tr>
                                    <td class="fw-bold text-dark">{{ $sub->plan?->plan_name ?? '—' }}<small class="d-block text-muted fw-normal">{{ $cycleLabel[$sub->billing_cycle] ?? $sub->billing_cycle }}{{ $sub->kind && $sub->kind !== 'regular' ? ' · ' . $sub->kind : '' }}</small></td>
                                    <td class="text-nowrap small">{{ $sub->start_date?->format('d/m/Y') }} – {{ $sub->end_date?->format('d/m/Y') }}</td>
                                    <td class="text-nowrap">{{ bx_rp($sub->total_price) }}</td>
                                    <td><span class="bx-st {{ $sub->status === 'active' ? 'paid' : 'expired' }}">{{ ucfirst($sub->status) }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="bx-empty">Belum ada langganan.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="cms-card h-100">
                <div class="bx-h"><div><h6>Pembayaran</h6><div class="hint">Transaksi lewat DOKU</div></div></div>
                <div class="table-responsive mt-2">
                    <table class="table align-middle mb-0 bx-table">
                        <thead><tr><th>Invoice</th><th>Tanggal</th><th>Jumlah</th><th>Status</th></tr></thead>
                        <tbody>
                            @forelse($payments as $pay)
                                @php [$pl, $pc] = $payLabel[$pay->transaction_status] ?? [ucfirst((string) $pay->transaction_status), 'expired']; @endphp
                                <tr>
                                    <td class="fw-bold text-dark">{{ $pay->order_id }}<small class="d-block text-muted fw-normal">{{ $pay->plan_name }} · {{ $pay->purchase_type }}</small></td>
                                    <td class="text-nowrap small">{{ $pay->created_at?->format('d/m/Y H:i') }}</td>
                                    <td class="text-nowrap">{{ bx_rp($pay->gross_amount) }}</td>
                                    <td><span class="bx-st {{ $pc }}">{{ $pl }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="bx-empty">Belum ada transaksi pembayaran.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
(function () {
    var btn = document.getElementById('btnSuspend');
    if (!btn) return;
    btn.addEventListener('click', function () {
        var form = document.getElementById('statusForm');
        if (!form.reportValidity()) return;
        Swal.fire({
            icon: 'warning',
            title: @json('Tangguhkan ' . $company->company_name . '?'),
            text: 'Semua user perusahaan ini akan langsung keluar dan tidak bisa login sampai diaktifkan kembali.',
            showCancelButton: true,
            confirmButtonText: 'Ya, tangguhkan',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#dc2626',
            reverseButtons: true
        }).then(function (r) { if (r.isConfirmed) form.submit(); });
    });
})();
</script>
@endsection
