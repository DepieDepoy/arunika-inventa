@extends('backoffice.layouts.app')

@section('title', 'Pelanggan')

@section('content')
@include('backoffice.partials.style')
@include('backoffice.customers._style')
@php $statusIcon = \App\Services\Backoffice\CustomerDirectory::STATUS_ICON; @endphp

<div class="container-xxl flex-grow-1 container-p-y">

    <div class="cms-card p-3 p-md-4 mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <span class="cms-badge"><i class="bi bi-buildings"></i> Pelanggan</span>
                <h4 class="mb-1 mt-2">Perusahaan yang memakai Vasetra</h4>
                <div class="text-muted small">{{ bx_num($total) }} perusahaan terdaftar. Klik baris untuk detail, perpanjang trial, atau tangguhkan akun.</div>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2 mt-3">
            <a href="{{ route('backoffice.customers.index', request()->except('status', 'page')) }}" class="bx-st {{ request('status') ? 'expired' : 'paid' }}" style="font-size:.78rem;padding:6px 12px">Semua · {{ bx_num($total) }}</a>
            @foreach($statuses as $k => $label)
                <a href="{{ route('backoffice.customers.index', array_merge(request()->except('page'), ['status' => $k])) }}" class="bx-st {{ $k }}" style="font-size:.78rem;padding:6px 12px;{{ request('status') === $k ? 'outline:2px solid currentColor' : '' }}">
                    <i class="bi {{ $statusIcon[$k] }}"></i> {{ $label }} · {{ bx_num($counts[$k] ?? 0) }}
                </a>
            @endforeach
        </div>

        <form method="GET" class="d-flex flex-wrap gap-2 mt-3">
            @if(request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
            <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari nama PT, kode, email / no. HP / nama user" style="max-width:360px">
            <select name="plan" class="form-select" style="width:auto" onchange="this.form.submit()">
                <option value="">Semua paket</option>
                @foreach($plans as $p)<option value="{{ $p }}" @selected(request('plan') === $p)>{{ $p }}</option>@endforeach
            </select>
            <select name="sort" class="form-select" style="width:auto" onchange="this.form.submit()">
                @foreach(['newest' => 'Terbaru daftar', 'ending' => 'Segera berakhir', 'assets' => 'Aset terbanyak', 'users' => 'User terbanyak', 'storage' => 'Storage terbesar', 'activity' => 'Aktivitas terakhir'] as $k => $label)
                    <option value="{{ $k }}" @selected(request('sort', 'newest') === $k)>Urut: {{ $label }}</option>
                @endforeach
            </select>
            <button class="btn btn-primary"><i class="bi bi-search"></i></button>
            @if(request()->hasAny(['q', 'plan', 'status', 'sort']))<a href="{{ route('backoffice.customers.index') }}" class="btn btn-outline-secondary">Reset</a>@endif
        </form>
    </div>

    <div class="cms-card">
        <div class="table-responsive">
            <table class="table align-middle mb-0 bx-table">
                <thead><tr><th>Perusahaan</th><th>Admin</th><th>Paket</th><th>Aset</th><th>User</th><th>Storage</th><th>Aktivitas</th><th></th></tr></thead>
                <tbody>
                    @forelse($companies as $c)
                        @php
                            $owner = $owners[$c->id] ?? null;
                            $aPct = $c->max_assets ? bx_pct($c->assets_count, $c->max_assets) : null;
                            $uPct = $c->max_users ? bx_pct($c->users_count, $c->max_users) : null;
                            $sPct = bx_pct($c->storage_used, $c->storage_limit);
                        @endphp
                        <tr style="cursor:pointer" onclick="location.href='{{ route('backoffice.customers.show', $c->id) }}'">
                            <td style="min-width:220px">
                                <div class="bx-co">
                                    <span class="av">{{ bx_initials($c->company_name) }}</span>
                                    <span style="min-width:0">
                                        <b>{{ $c->company_name }}</b>
                                        <small>{{ $c->company_code }} · daftar {{ $c->created_at?->locale('id')->translatedFormat('d M Y') }}</small>
                                    </span>
                                </div>
                            </td>
                            <td style="min-width:170px">{{ $owner?->name ?? '—' }}<small class="d-block text-muted">{{ $owner?->email }}</small></td>
                            <td class="text-nowrap">
                                <span class="bx-st {{ $c->customer_status }}"><i class="bi {{ $statusIcon[$c->customer_status] }}"></i> {{ $statuses[$c->customer_status] }}</span>
                                <small class="d-block text-muted mt-1">
                                    {{ $c->plan_label }}@if($c->ends_at) · @if($c->days_left < 0) berakhir @elseif($c->days_left === 0) berakhir hari ini @else sisa {{ $c->days_left }} hari @endif @endif
                                </small>
                            </td>
                            <td>
                                <div class="bx-meter">
                                    <div class="top"><span>{{ bx_num($c->assets_count) }}</span><span>{{ $c->max_assets ? '/ ' . bx_num($c->max_assets) : '∞' }}</span></div>
                                    <div class="track"><div class="fill {{ bx_level($aPct ?? 0) }}" style="width:{{ $aPct ?? 0 }}%"></div></div>
                                </div>
                            </td>
                            <td>
                                <div class="bx-meter" style="min-width:90px">
                                    <div class="top"><span>{{ bx_num($c->users_count) }}</span><span>{{ $c->max_users ? '/ ' . bx_num($c->max_users) : '∞' }}</span></div>
                                    <div class="track"><div class="fill {{ bx_level($uPct ?? 0) }}" style="width:{{ $uPct ?? 0 }}%"></div></div>
                                </div>
                            </td>
                            <td>
                                <div class="bx-meter" style="min-width:140px">
                                    <div class="top"><span>{{ bx_bytes($c->storage_used) }}</span><span>{{ $c->storage_limit ? '/ ' . bx_bytes($c->storage_limit) : '' }}</span></div>
                                    <div class="track"><div class="fill {{ bx_level($sPct) }}" style="width:{{ $sPct }}%"></div></div>
                                </div>
                            </td>
                            <td class="text-nowrap small">
                                @if($c->last_activity_at)
                                    {{ \Illuminate\Support\Carbon::parse($c->last_activity_at)->locale('id')->diffForHumans() }}
                                @else
                                    <span class="text-muted">Belum pernah login</span>
                                @endif
                            </td>
                            <td><i class="bi bi-chevron-right text-muted"></i></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="bx-empty">Tidak ada perusahaan yang cocok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($companies->hasPages())<div class="p-3">{{ $companies->links() }}</div>@endif
    </div>
</div>
@endsection
