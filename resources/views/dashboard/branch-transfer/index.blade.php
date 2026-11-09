@extends('dashboard.layouts.wrapper')

@section('title', 'Mutasi Aset')

@section('content')
@php
    $tabs = ['' => 'Semua'] + \App\Models\BranchTransfer::STATUS_LABELS;
    $total = $counts->sum();
    $chip = ['sent' => 'warn', 'received' => 'ok', 'cancelled' => 'mute'];
@endphp
<style>
    .mt-tabs { display: flex; flex-wrap: wrap; gap: 6px; }
    .mt-tabs a { padding: 6px 14px; border-radius: 99px; background: #f1f3f9; color: #4b5563; font-weight: 600; font-size: .85rem; text-decoration: none; }
    .mt-tabs a.on { background: #3a57e8; color: #fff; }
    .mt-tabs a .n { opacity: .75; margin-left: 4px; }
    .mt-chip { display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 99px; font-size: .75rem; font-weight: 700; white-space: nowrap; }
    .mt-chip.warn { background: #fff4e0; color: #b46b00; }
    .mt-chip.ok { background: #e7f6ee; color: #1a8754; }
    .mt-chip.mute { background: #eef0f4; color: #6b7280; }
    .mt-route { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; font-weight: 600; }
    .mt-route i { color: #3a57e8; }
</style>

<div class="content-wrapper">
<div class="container-xxl flex-grow-1 container-p-y">
<div class="card">
    <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <div>
            <h5 class="mb-0">Mutasi Aset Antar-Cabang</h5>
            <small class="text-muted">Kirim aset ke cabang lain. Aset pindah setelah cabang tujuan menekan "Terima".</small>
        </div>
        <div class="d-flex gap-2">
            @if(auth()->user()->hasPermission('branch.view'))
                <a href="{{ route('branches.index') }}" class="btn btn-outline-secondary"><i class="bi bi-diagram-3 me-1"></i> Cabang</a>
            @endif
            @if(auth()->user()->hasPermission('branch.transfer'))
                <a href="{{ route('branch-transfers.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Buat Mutasi</a>
            @endif
        </div>
    </div>
    <div class="card-body">
        <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
            <div class="mt-tabs">
                @foreach($tabs as $key => $label)
                    <a href="{{ route('branch-transfers.index', array_filter(['status' => $key, 'q' => request('q')])) }}" class="{{ (string) $status === (string) $key || (!$status && $key === '') ? 'on' : '' }}">
                        {{ $label }}<span class="n">{{ $key === '' ? $total : ($counts[$key] ?? 0) }}</span>
                    </a>
                @endforeach
            </div>
            <form method="GET" class="d-flex gap-2">
                @if($status)<input type="hidden" name="status" value="{{ $status }}">@endif
                <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="No. mutasi / kode aset">
                <button class="btn btn-outline-primary"><i class="bi bi-search"></i></button>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>No. Mutasi</th>
                        <th>Rute</th>
                        <th class="text-center">Aset</th>
                        <th>Dikirim</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transfers as $t)
                        <tr>
                            <td class="fw-bold">{{ $t->transfer_no }}</td>
                            <td>
                                <div class="mt-route">
                                    <span>{{ $t->fromBranch?->branch_name }}</span>
                                    <i class="bi bi-arrow-right"></i>
                                    <span>{{ $t->toBranch?->branch_name }}</span>
                                </div>
                            </td>
                            <td class="text-center fw-bold">{{ $t->items_count }}</td>
                            <td>
                                <div>{{ $t->sent_at?->translatedFormat('d M Y H:i') }}</div>
                                <small class="text-muted">oleh {{ $t->sender?->name ?? '—' }}</small>
                            </td>
                            <td>
                                <span class="mt-chip {{ $chip[$t->status] ?? 'mute' }}">{{ $t->statusLabel() }}</span>
                                @if($t->status === 'received')
                                    <small class="d-block text-muted mt-1">{{ $t->received_at?->translatedFormat('d M Y') }} · {{ $t->receiver?->name }}</small>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('branch-transfers.show', $t->id) }}" class="btn btn-sm {{ $t->status === 'sent' && ($restricted === null || $restricted === (int) $t->to_branch_id) ? 'btn-primary' : 'btn-outline-primary' }}">
                                    {{ $t->status === 'sent' && ($restricted === null || $restricted === (int) $t->to_branch_id) && auth()->user()->hasPermission('branch.receive') ? 'Periksa & Terima' : 'Detail' }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5"><i class="bi bi-truck fs-2 d-block mb-2"></i>Belum ada mutasi aset.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $transfers->links() }}
    </div>
</div>
</div>
</div>
@endsection
