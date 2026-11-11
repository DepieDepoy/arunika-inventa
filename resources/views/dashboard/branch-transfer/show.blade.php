@extends('dashboard.layouts.wrapper')

@section('title', 'Mutasi ' . $transfer->transfer_no)

@section('content')
@php
    $chip = ['sent' => 'warn', 'received' => 'ok', 'cancelled' => 'mute'];
    $steps = [
        ['Dikirim', $transfer->sent_at, $transfer->sender?->name, true],
        $transfer->status === 'cancelled'
            ? ['Dibatalkan', $transfer->cancelled_at, $transfer->canceller?->name, true]
            : ['Diterima', $transfer->received_at, $transfer->receiver?->name, $transfer->status === 'received'],
    ];
@endphp
<style>
    .mt-chip { display: inline-flex; align-items: center; gap: 5px; padding: 4px 12px; border-radius: 99px; font-size: .8rem; font-weight: 700; }
    .mt-chip.warn { background: #fff4e0; color: #b46b00; }
    .mt-chip.ok { background: #e7f6ee; color: #1a8754; }
    .mt-chip.mute { background: #eef0f4; color: #6b7280; }
    .mt-route { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; }
    .mt-route .b { background: #f5f7fc; border-radius: 12px; padding: 10px 16px; min-width: 180px; }
    .mt-route .b small { color: #6b7280; display: block; }
    .mt-route .b strong { font-size: 1.05rem; }
    .mt-route > i { font-size: 1.6rem; color: #3a57e8; }
    .mt-steps { display: flex; gap: 24px; flex-wrap: wrap; }
    .mt-steps .s { display: flex; gap: 10px; align-items: flex-start; }
    .mt-steps .dot { width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: #e5e8f0; color: #9ca3af; flex-shrink: 0; }
    .mt-steps .s.done .dot { background: #3a57e8; color: #fff; }
</style>

<div class="content-wrapper">
<div class="container-xxl flex-grow-1 container-p-y">

<div class="card mb-4">
    <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <div>
            <h5 class="mb-1">{{ $transfer->transfer_no }} <span class="mt-chip {{ $chip[$transfer->status] ?? 'mute' }} ms-1">{{ $transfer->statusLabel() }}</span></h5>
            <small class="text-muted">{{ __(':p1 aset', ['p1' => $transfer->items->count()]) }}</small>
        </div>
        <a href="{{ route('branch-transfers.index') }}" class="btn btn-outline-secondary">{{ __('Kembali') }}</a>
    </div>
    <div class="card-body">
        <div class="mt-route mb-4">
            <div class="b"><small>{{ __('Dari') }}</small><strong>{{ $transfer->fromBranch?->branch_name }}</strong></div>
            <i class="bi bi-arrow-right-circle-fill"></i>
            <div class="b"><small>{{ __('Ke') }}</small><strong>{{ $transfer->toBranch?->branch_name }}</strong></div>
        </div>

        <div class="mt-steps mb-3">
            @foreach($steps as [$label, $at, $by, $done])
                <div class="s {{ $done ? 'done' : '' }}">
                    <div class="dot"><i class="bi {{ $done ? 'bi-check-lg' : 'bi-hourglass-split' }}"></i></div>
                    <div>
                        <div class="fw-bold">{{ $label }}</div>
                        <small class="text-muted">{{ $done && $at ? $at->translatedFormat('d M Y H:i') . ' · ' . ($by ?? '—') : 'Menunggu konfirmasi cabang tujuan' }}</small>
                    </div>
                </div>
            @endforeach
        </div>

        @if($transfer->notes)
            <div class="alert alert-light border mb-2"><strong>{{ __('Catatan pengirim:') }}</strong> {{ $transfer->notes }}</div>
        @endif
        @if($transfer->receive_notes)
            <div class="alert alert-light border mb-2"><strong>{{ __('Catatan penerima:') }}</strong> {{ $transfer->receive_notes }}</div>
        @endif
    </div>
</div>

<div class="row g-4">
    <div class="{{ $canReceive || $canCancel ? 'col-lg-8' : 'col-12' }}">
        <div class="card h-100">
            <div class="card-header"><h6 class="mb-0">{{ __('Daftar aset') }}</h6></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead><tr><th>{{ __('Kode') }}</th><th>{{ __('Nama aset') }}</th><th>{{ __('Lokasi asal') }}</th><th>{{ __('Posisi sekarang') }}</th></tr></thead>
                        <tbody>
                            @foreach($transfer->items as $item)
                                <tr>
                                    <td class="fw-bold">{{ $item->asset_code }}</td>
                                    <td>{{ $item->asset_name }}</td>
                                    <td class="text-muted">{{ $item->from_location ?: '—' }}</td>
                                    <td>
                                        @if(!$item->asset || $item->asset->trashed())
                                            <span class="text-danger small">{{ __('Aset sudah dihapus') }}</span>
                                        @else
                                            {{ $item->asset->branch?->branch_name }}
                                            <small class="d-block text-muted">{{ $item->asset->location }}</small>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @if($canReceive || $canCancel)
        <div class="col-lg-4">
            @if($canReceive)
                <div class="card mb-4">
                    <div class="card-header"><h6 class="mb-0"><i class="bi bi-box-arrow-in-down text-success me-1"></i> {{ __('Terima aset') }}</h6></div>
                    <div class="card-body">
                        <p class="small text-muted">{{ __('Pastikan semua aset sudah sampai dan sesuai daftar. Setelah diterima, aset tercatat milik :p1.', ['p1' => $transfer->toBranch?->branch_name]) }}</p>
                        <form method="POST" action="{{ route('branch-transfers.receive', $transfer->id) }}" id="formReceive">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">{{ __('Lokasi penempatan baru') }} <small class="text-muted">{{ __('(opsional)') }}</small></label>
                                <select name="location" class="form-select" data-location-picker data-branch="{{ $transfer->to_branch_id }}" data-empty-label="— Tentukan nanti —"></select>
                                <small class="text-muted">{{ __('Lokasi di :p1. Bisa juga diatur nanti lewat Pindah lokasi.', ['p1' => $transfer->toBranch?->branch_name]) }}</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ __('Catatan penerimaan') }}</label>
                                <textarea name="receive_notes" class="form-control" rows="2" maxlength="1000" placeholder="Contoh: diterima lengkap & baik"></textarea>
                            </div>
                            <button class="btn btn-success w-100"><i class="bi bi-check2-circle me-1"></i> {{ __('Terima :p1 aset', ['p1' => $transfer->items->count()]) }}</button>
                        </form>
                    </div>
                </div>
            @endif
            @if($canCancel)
                <div class="card">
                    <div class="card-body">
                        <p class="small text-muted mb-2">{{ __('Batal kirim? Aset tetap di :p1.', ['p1' => $transfer->fromBranch?->branch_name]) }}</p>
                        <form method="POST" action="{{ route('branch-transfers.cancel', $transfer->id) }}" id="formCancel">
                            @csrf
                            <button class="btn btn-outline-danger w-100"><i class="bi bi-x-circle me-1"></i> {{ __('Batalkan mutasi') }}</button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>

</div>
</div>
@endsection

@section('js')
@include('dashboard.components.location-picker')
<script>
$('#formCancel').on('submit', function (e) {
    e.preventDefault();
    const form = this;
    Swal.fire({ icon: 'warning', title: @json(__('Batalkan mutasi?')), text: @json(__('Cabang tujuan akan diberi tahu.')), showCancelButton: true, confirmButtonText: @json(__('Ya, batalkan')), cancelButtonText: @json(__('Tidak')) })
        .then(r => { if (r.isConfirmed) form.submit(); });
});
$('#formReceive').on('submit', function (e) {
    e.preventDefault();
    const form = this;
    Swal.fire({ icon: 'question', title: @json(__('Terima semua aset?')), text: @json(__('Aset akan dipindahkan ke cabang Anda.')), showCancelButton: true, confirmButtonText: @json(__('Ya, terima')), cancelButtonText: @json(__('Periksa lagi')) })
        .then(r => { if (r.isConfirmed) form.submit(); });
});
</script>
@endsection
