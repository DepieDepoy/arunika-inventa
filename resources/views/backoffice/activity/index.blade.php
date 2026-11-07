@extends('backoffice.layouts.app')

@section('title', 'Log Aktivitas')

@section('content')
@include('backoffice.partials.style')
@php
    $labels = [
        'login' => 'Login', 'logout' => 'Logout', 'login_failed' => 'Gagal login', 'password_changed' => 'Ganti password',
        'cms_updated' => 'Ubah konten', 'cms_reset' => 'Reset konten', 'pricing_updated' => 'Ubah harga',
        'admin_created' => 'Akun dibuat', 'admin_updated' => 'Akun diubah', 'admin_password_reset' => 'Reset password', 'admin_activated' => 'Akun diaktifkan', 'admin_deactivated' => 'Akun dinonaktifkan', 'analytics.settings' => 'Pengaturan analitik', 'customer.trial_extended' => 'Perpanjang trial', 'customer.suspended' => 'Tangguhkan pelanggan', 'customer.activated' => 'Aktifkan pelanggan', 'account.deletion_processed' => 'Proses hapus akun', 'account.deletion_rejected' => 'Tolak hapus akun', 'mobile.settings' => 'Pengaturan aplikasi', 'ai.settings' => 'Pengaturan Tera AI', 'push.settings' => 'Pengaturan notifikasi', 'security.settings' => 'Pengaturan keamanan', 'two_factor_enabled' => 'Aktifkan 2 langkah', 'two_factor_disabled' => 'Matikan 2 langkah', 'two_factor_failed' => 'Kode 2 langkah salah', 'two_factor_reset' => 'Reset 2 langkah', 'two_factor_codes' => 'Kode cadangan baru', 'two_factor_recovery_used' => 'Pakai kode cadangan',
    ];
@endphp
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="cms-card">
        <div class="p-3 p-md-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5 class="mb-1">Log aktivitas backoffice</h5>
                <div class="text-muted small">Siapa mengubah apa dan kapan — termasuk login & perubahan harga.</div>
            </div>
            <form method="GET" class="d-flex gap-2">
                <select name="event" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Semua aktivitas</option>
                    @foreach($events as $ev)<option value="{{ $ev }}" @selected(request('event') === $ev)>{{ $labels[$ev] ?? $ev }}</option>@endforeach
                </select>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr class="small text-muted text-uppercase"><th>Waktu</th><th>Pegawai</th><th>Aktivitas</th><th>Keterangan</th><th>IP</th></tr></thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td class="small text-nowrap">{{ $log->created_at?->translatedFormat('d M Y H:i') }}</td>
                            <td class="small">{{ $log->admin_name ?? '—' }}</td>
                            <td><span class="cms-chip {{ in_array($log->event, ['login_failed', 'admin_deactivated', 'cms_reset', 'customer.suspended', 'two_factor_failed', 'two_factor_reset', 'two_factor_disabled'], true) ? '' : 'cms-chip-blue' }}">{{ $labels[$log->event] ?? $log->event }}</span></td>
                            <td class="small">{{ $log->description }}</td>
                            <td class="small text-muted">{{ $log->ip_address ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-5">Belum ada aktivitas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())<div class="p-3">{{ $logs->links() }}</div>@endif
    </div>
</div>
@endsection
