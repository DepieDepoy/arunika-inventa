@extends('dashboard.layouts.wrapper')

@section('title', 'Lokasi')

@section('content')
@php
    $user = auth()->user();
    $multi = $branches->count() > 1;
@endphp
<style>
    .swal2-container { z-index: 99999 !important; }
    .lc-chip { display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 99px; font-size: .75rem; font-weight: 600; }
    .lc-chip.on { background: #e7f6ee; color: #1a8754; }
    .lc-chip.off { background: #fdecee; color: #c0392b; }
    .lc-help { border: 0; border-radius: 14px; background: linear-gradient(120deg, #f5f8ff 0%, #fff 100%); }
</style>

<div class="content-wrapper">
<div class="container-xxl flex-grow-1 container-p-y">

    <div class="card lc-help mb-4">
        <div class="card-body d-flex gap-3 align-items-start">
            <i class="bi bi-geo-alt-fill text-primary fs-3"></i>
            <div>
                <b>Cabang vs Lokasi</b>
                <div class="text-muted small">
                    <b>Cabang</b> = kantor / kota (Kantor Pusat, Surabaya). <b>Lokasi</b> = tempat di dalam cabang
                    (Lantai 1, Gudang, Ruang Finance). Lokasi dipakai saat mencatat aset, pindah lokasi, dan audit fisik
                    — aset yang ditemukan di lokasi lain otomatis ditandai <i>salah lokasi</i>.
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
            <div>
                <h5 class="mb-0">Lokasi</h5>
                <small class="text-muted">Ruangan / area per cabang</small>
            </div>
            <div class="d-flex gap-2">
                @if($multi && !$restricted)
                    <form method="GET">
                        <select name="branch_id" class="form-select" onchange="this.form.submit()">
                            <option value="">Semua cabang</option>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}" @selected($branchId == $b->id)>{{ $b->label() }}</option>
                            @endforeach
                        </select>
                    </form>
                @endif
                @if($user->hasPermission('location.create'))
                    <button class="btn btn-primary" id="btnAdd"><i class="fa-solid fa-plus"></i> Tambah Lokasi</button>
                @endif
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Lokasi</th>
                            @if($multi)<th>Cabang</th>@endif
                            <th>Keterangan</th>
                            <th class="text-center">Aset</th>
                            <th>Status</th>
                            <th class="text-center" width="10%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($locations as $l)
                            <tr>
                                <td class="fw-bold">{{ $l->name }}</td>
                                @if($multi)<td>{{ $l->branch?->branch_name }}</td>@endif
                                <td class="text-muted">{{ $l->description ?: '—' }}</td>
                                <td class="text-center fw-bold">{{ $l->assets_count }}</td>
                                <td>
                                    @if($l->status == 1)
                                        <span class="lc-chip on">Aktif</span>
                                    @else
                                        <span class="lc-chip off">Nonaktif</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        @if($user->hasPermission('location.edit'))
                                            <a href="javascript:void(0)" class="btn-action btn-edit" title="Edit"
                                               data-id="{{ $l->id }}" data-name="{{ $l->name }}" data-description="{{ $l->description }}"
                                               data-status="{{ $l->status }}" data-branch="{{ $l->branch_id }}"><i class="fa-solid fa-pen-to-square"></i></a>
                                        @endif
                                        @if($user->hasPermission('location.delete'))
                                            <a href="javascript:void(0)" class="btn-action btn-delete" title="Hapus" data-id="{{ $l->id }}" data-name="{{ $l->name }}"><i class="fa-solid fa-trash"></i></a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-5"><i class="bi bi-geo-alt fs-2 d-block mb-2"></i>Belum ada lokasi. Tambahkan ruangan / area, mis. "Lantai 1", "Gudang".</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div>

<div class="modal fade" id="modalLocation" tabindex="-1">
    <div class="modal-dialog">
        <form id="formLocation">
            @csrf
            <input type="hidden" id="l_id">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="lTitle">Tambah Lokasi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Cabang</label>
                        <select name="branch_id" id="l_branch" class="form-select" @disabled($restricted)>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}" @selected(($branchId ?? $restricted) == $b->id)>{{ $b->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama lokasi <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="l_name" class="form-control" maxlength="150" required placeholder="Contoh: Lantai 2 - Ruang Finance">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Keterangan</label>
                        <input type="text" name="description" id="l_description" class="form-control" maxlength="255">
                    </div>
                    <div class="mb-1">
                        <label class="form-label">Status</label>
                        <select name="status" id="l_status" class="form-select">
                            <option value="1">Aktif</option>
                            <option value="0">Nonaktif</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('js')
<script>
(function () {
    const modalEl = document.getElementById('modalLocation');
    const base = @json(url('dashboard/locations'));
    const csrf = $('meta[name="csrf-token"]').attr('content');
    const fail = xhr => Swal.fire({ icon: 'error', title: 'Gagal', text: xhr.responseJSON?.message || 'Terjadi kesalahan.' });

    $('#btnAdd').on('click', function () {
        $('#formLocation')[0].reset();
        $('#l_id').val('');
        $('#l_branch').prop('disabled', {{ $restricted ? 'true' : 'false' }});
        $('#lTitle').text('Tambah Lokasi');
        new bootstrap.Modal(modalEl).show();
    });

    $(document).on('click', '.btn-edit', function () {
        const d = $(this).data();
        $('#l_id').val(d.id);
        $('#l_name').val(d.name);
        $('#l_description').val(d.description);
        $('#l_status').val(String(d.status));
        $('#l_branch').val(String(d.branch)).prop('disabled', true);
        $('#lTitle').text('Edit Lokasi');
        new bootstrap.Modal(modalEl).show();
    });

    $('#formLocation').on('submit', function (e) {
        e.preventDefault();
        const id = $('#l_id').val();
        const data = $(this).serializeArray();
        if (!id) data.push({ name: 'branch_id', value: $('#l_branch').val() });
        $.ajax({ url: id ? base + '/' + id : base, type: 'POST', data: $.param(data), headers: { 'Accept': 'application/json' } })
            .done(res => Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message, timer: 1300, showConfirmButton: false }).then(() => location.reload()))
            .fail(fail);
    });

    $(document).on('click', '.btn-delete', function () {
        const id = $(this).data('id');
        Swal.fire({ icon: 'warning', title: 'Hapus lokasi?', text: '"' + $(this).data('name') + '" hanya bisa dihapus bila tidak dipakai aset.', showCancelButton: true, confirmButtonText: 'Ya, hapus', cancelButtonText: 'Batal' })
            .then(r => {
                if (!r.isConfirmed) return;
                $.ajax({ url: base + '/' + id, type: 'DELETE', data: { _token: csrf }, headers: { 'Accept': 'application/json' } })
                    .done(res => Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message }).then(() => location.reload()))
                    .fail(fail);
            });
    });
})();
</script>
@endsection
