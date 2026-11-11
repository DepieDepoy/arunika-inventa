@extends('dashboard.layouts.wrapper')

@section('title', __('Cabang'))

@section('content')
@php
    $user = auth()->user();
    $canCreate = $user->hasPermission('branch.create') && !$restrictedBranchId;
    $canEdit = $user->hasPermission('branch.edit');
    $canDelete = $user->hasPermission('branch.delete') && !$restrictedBranchId;
    $pct = $limit['unlimited'] || $limit['max'] < 1 ? null : min(100, (int) round($limit['used'] / $limit['max'] * 100));
@endphp
<style>
    .swal2-container { z-index: 99999 !important; }
    .br-limit { border: 0; border-radius: 14px; background: linear-gradient(120deg, #fff 0%, #f5f8ff 100%); }
    .br-limit .bar { height: 8px; border-radius: 8px; background: #e9edf5; overflow: hidden; }
    .br-limit .bar > span { display: block; height: 100%; border-radius: 8px; background: #3a57e8; }
    .br-limit.full .bar > span { background: #e04f5f; }
    .br-chip { display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 99px; font-size: .75rem; font-weight: 600; }
    .br-chip.ho { background: #eef1ff; color: #3a57e8; }
    .br-chip.on { background: #e7f6ee; color: #1a8754; }
    .br-chip.off { background: #fdecee; color: #c0392b; }
    .br-stat { font-weight: 700; }
</style>

<div class="content-wrapper">
<div class="container-xxl flex-grow-1 container-p-y">

    {{-- KUOTA CABANG --}}
    <div class="card br-limit mb-4 {{ !$limit['unlimited'] && !$limit['can_add'] ? 'full' : '' }}">
        <div class="card-body d-flex flex-wrap align-items-center gap-3 justify-content-between">
            <div style="min-width:240px;flex:1">
                <div class="d-flex justify-content-between mb-1">
                    <span class="fw-bold"><i class="bi bi-diagram-3-fill text-primary me-1"></i> {{ __('Kuota cabang paket :p1', ['p1' => $limit['plan'] ?? '']) }}</span>
                    <span class="br-stat">{{ $limit['used'] }} / {{ $limit['unlimited'] ? '∞' : $limit['max'] }}</span>
                </div>
                <div class="bar"><span style="width: {{ $pct ?? 8 }}%"></span></div>
                <small class="text-muted d-block mt-1">
                    @if($limit['unlimited'])
                        Paket Anda tanpa batas cabang.
                    @elseif($limit['can_add'])
                        Sisa {{ max(0, $limit['max'] - $limit['used']) }} cabang lagi. Kantor Pusat ikut dihitung.
                    @else
                        Batas cabang paket sudah tercapai. Upgrade paket untuk menambah cabang.
                    @endif
                </small>
            </div>
            @if(!$limit['unlimited'] && !$limit['can_add'] && $user->hasPermission('subscription.view'))
                <a href="{{ route('subscription.index') }}" class="btn btn-warning"><i class="bi bi-rocket-takeoff me-1"></i> {{ __('Upgrade paket') }}</a>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
            <div>
                <h5 class="mb-0">{{ __('Cabang') }}</h5>
                <small class="text-muted">{{ __('Setiap user terdaftar di satu cabang. Administrator melihat semua cabang; user lain hanya melihat data cabangnya.') }}</small>
            </div>
            <div class="d-flex gap-2">
                @if($user->hasPermission('branch.transfer') || $user->hasPermission('branch.receive'))
                    <a href="{{ route('branch-transfers.index') }}" class="btn btn-outline-primary">
                        <i class="bi bi-truck me-1"></i> Mutasi Aset
                        @if($pendingTransfers > 0)<span class="badge bg-danger ms-1">{{ $pendingTransfers }}</span>@endif
                    </a>
                @endif
                @if($canCreate)
                    <button class="btn btn-primary" id="btnAddBranch"><i class="fa-solid fa-plus"></i> {{ __('Tambah Cabang') }}</button>
                @endif
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>{{ __('Cabang') }}</th>
                            <th>{{ __('Kode') }}</th>
                            <th>{{ __('Alamat') }}</th>
                            <th>{{ __('PIC / Telepon') }}</th>
                            <th class="text-center">{{ __('Aset') }}</th>
                            <th class="text-center">{{ __('User') }}</th>
                            <th>{{ __('Maintenance oleh') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th class="text-center" width="10%">{{ __('Aksi') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($branches as $b)
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark">{{ $b->branch_name }}</div>
                                    @if($b->is_head_office)<span class="br-chip ho"><i class="bi bi-star-fill"></i> {{ __('Kantor Pusat') }}</span>@endif
                                </td>
                                <td>{{ $b->branch_code }}</td>
                                <td class="text-muted" style="max-width:260px">{{ $b->address ?: '—' }}</td>
                                <td>
                                    <div>{{ $b->pic_name ?: '—' }}</div>
                                    <small class="text-muted">{{ $b->phone }}</small>
                                </td>
                                <td class="text-center br-stat">{{ number_format($b->assets_count, 0, ',', '.') }}</td>
                                <td class="text-center br-stat">{{ number_format($b->users_count, 0, ',', '.') }}</td>
                                <td>
                                    @php
                                        $serviceId = $serviceMap[$b->id] ?? null;
                                        $serviceName = optional($branches->firstWhere('id', $serviceId))->branch_name;
                                    @endphp
                                    @if($serviceId === $b->id)
                                        <span class="small"><i class="bi bi-tools text-success"></i> {{ __('Teknisi sendiri') }}</span>
                                    @else
                                        <span class="small"><i class="bi bi-arrow-left-right text-primary"></i> {{ __('Teknisi :p1', ['p1' => $serviceName ?? '—']) }}</span>
                                    @endif
                                    <small class="d-block text-muted">
                                        {{ $b->service_branch_id ? 'Diatur manual' : (in_array($b->id, $withTechnicians) ? 'Otomatis' : 'Otomatis · belum ada teknisi') }}
                                    </small>
                                </td>
                                <td>
                                    @if($b->status == 1)
                                        <span class="br-chip on"><i class="fa-solid fa-circle-check"></i> {{ __('Aktif') }}</span>
                                    @else
                                        <span class="br-chip off"><i class="fa-solid fa-circle-xmark"></i> {{ __('Nonaktif') }}</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        @if($canEdit && (!$restrictedBranchId || $restrictedBranchId == $b->id))
                                            <a href="javascript:void(0)" class="btn-action btn-edit" data-id="{{ $b->id }}" title="{{ __('Edit') }}"><i class="fa-solid fa-pen-to-square"></i></a>
                                        @endif
                                        @if($canDelete && !$b->is_head_office)
                                            <a href="javascript:void(0)" class="btn-action btn-delete" data-id="{{ $b->id }}" data-name="{{ $b->branch_name }}" title="{{ __('Hapus') }}"><i class="fa-solid fa-trash"></i></a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
</div>

{{-- MODAL TAMBAH / EDIT --}}
<div class="modal fade" id="modalBranch" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form id="formBranch">
            @csrf
            <input type="hidden" id="branch_id">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="branchModalTitle">{{ __('Tambah Cabang') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Nama cabang') }} <span class="text-danger">*</span></label>
                            <input type="text" name="branch_name" id="f_branch_name" class="form-control" maxlength="150" required placeholder="{{ __('Contoh: Cabang Surabaya') }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Status') }}</label>
                            <select name="status" id="f_status" class="form-select">
                                <option value="1">{{ __('Aktif') }}</option>
                                <option value="0">{{ __('Nonaktif') }}</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('PIC / Kepala cabang') }}</label>
                            <input type="text" name="pic_name" id="f_pic_name" class="form-control" maxlength="150">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Telepon') }}</label>
                            <input type="text" name="phone" id="f_phone" class="form-control" maxlength="30">
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">{{ __('Maintenance ditangani oleh') }}</label>
                            <select name="service_branch_id" id="f_service_branch_id" class="form-select">
                                <option value="">{{ __('Otomatis — teknisi cabang ini; bila belum ada teknisi, teknisi Kantor Pusat') }}</option>
                                @foreach($branches->where('status', 1) as $opt)
                                    <option value="{{ $opt->id }}">{{ __('Teknisi :p1', ['p1' => $opt->label()]) }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">{{ __('Request maintenance & notifikasi aset cabang ini dikirim ke teknisi cabang yang dipilih.') }}</small>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">{{ __('Alamat') }}</label>
                            <textarea name="address" id="f_address" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('Tutup') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Simpan') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('js')
<script>
(function () {
    const modalEl = document.getElementById('modalBranch');
    const csrf = $('meta[name="csrf-token"]').attr('content');
    const urls = {
        store: @json(route('branches.store')),
        base: @json(url('dashboard/branches')),
        upgrade: @json(route('subscription.index')),
    };
    const canUpgrade = @json($user->hasPermission('subscription.view'));

    function showErrors(xhr) {
        const res = xhr.responseJSON || {};
        if (res.limit_reached) {
            Swal.fire({
                icon: 'warning',
                title: @json(__('Batas cabang tercapai')),
                html: (res.errors.plan_limit || [])[0],
                showCancelButton: canUpgrade,
                confirmButtonText: canUpgrade ? 'Upgrade paket' : 'OK',
                cancelButtonText: @json(__('Nanti saja'))
            }).then(r => { if (r.isConfirmed && canUpgrade) window.location.href = urls.upgrade; });
            return;
        }
        let msg = res.message || '';
        if (res.errors) msg = Object.values(res.errors).map(v => v[0]).join('<br>');
        Swal.fire({ icon: 'error', title: @json(__('Gagal')), html: msg || 'Terjadi kesalahan.' });
    }

    $('#btnAddBranch').on('click', function () {
        $('#formBranch')[0].reset();
        $('#branch_id').val('');
        $('#f_status').prop('disabled', false);
        $('#f_service_branch_id option').prop('disabled', false);
        $('#branchModalTitle').text('Tambah Cabang');
        new bootstrap.Modal(modalEl).show();
    });

    $(document).on('click', '.btn-edit', function () {
        $.get(urls.base + '/' + $(this).data('id') + '/edit', function (b) {
            $('#branch_id').val(b.id);
            $('#f_branch_name').val(b.branch_name);
            $('#f_pic_name').val(b.pic_name);
            $('#f_phone').val(b.phone);
            $('#f_address').val(b.address);
            $('#f_status').val(String(b.status)).prop('disabled', !!b.is_head_office);
            $('#f_service_branch_id').val(b.service_branch_id ? String(b.service_branch_id) : '');
            $('#f_service_branch_id option').prop('disabled', false).filter('[value="' + b.id + '"]').prop('disabled', true);
            $('#branchModalTitle').text('Edit Cabang');
            new bootstrap.Modal(modalEl).show();
        }).fail(showErrors);
    });

    $('#formBranch').on('submit', function (e) {
        e.preventDefault();
        const id = $('#branch_id').val();
        const data = $(this).serializeArray();
        if ($('#f_status').prop('disabled')) data.push({ name: 'status', value: '1' });
        $.ajax({
            url: id ? urls.base + '/' + id : urls.store,
            type: 'POST',
            data: $.param(data),
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                bootstrap.Modal.getInstance(modalEl)?.hide();
                Swal.fire({ icon: 'success', title: @json(__('Berhasil')), text: res.message, timer: 1500, showConfirmButton: false })
                    .then(() => window.location.reload());
            },
            error: showErrors
        });
    });

    $(document).on('click', '.btn-delete', function () {
        const id = $(this).data('id');
        Swal.fire({
            icon: 'warning',
            title: @json(__('Hapus cabang?')),
            text: 'Cabang "' + $(this).data('name') + '" akan dihapus. Cabang harus sudah kosong (tanpa aset & user).',
            showCancelButton: true,
            confirmButtonText: @json(__('Ya, hapus')),
            cancelButtonText: @json(__('Batal'))
        }).then(r => {
            if (!r.isConfirmed) return;
            $.ajax({
                url: urls.base + '/' + id,
                type: 'DELETE',
                data: { _token: csrf },
                headers: { 'Accept': 'application/json' },
                success: res => Swal.fire({ icon: 'success', title: @json(__('Berhasil')), text: res.message }).then(() => window.location.reload()),
                error: showErrors
            });
        });
    });
})();
</script>
@endsection
