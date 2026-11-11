@extends('dashboard.layouts.wrapper')

@section('title', __('Buat Mutasi Aset'))

@section('content')
<style>
    .mt-pick { border: 1px solid #e5e8f0; border-radius: 12px; max-height: 380px; overflow-y: auto; }
    .mt-pick label { display: flex; gap: 10px; align-items: flex-start; padding: 10px 14px; border-bottom: 1px solid #f0f2f7; cursor: pointer; margin: 0; }
    .mt-pick label:hover { background: #f7f9ff; }
    .mt-pick label:last-child { border-bottom: 0; }
    .mt-pick .meta { font-size: .8rem; color: #6b7280; }
    .mt-sel { display: flex; flex-wrap: wrap; gap: 6px; }
    .mt-sel span { background: #eef1ff; color: #3a57e8; border-radius: 99px; padding: 4px 6px 4px 12px; font-size: .8rem; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; }
    .mt-sel button { border: 0; background: transparent; color: #3a57e8; line-height: 1; padding: 0 4px; }
    .mt-step { width: 26px; height: 26px; border-radius: 50%; background: #3a57e8; color: #fff; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; font-size: .8rem; margin-right: 6px; }
</style>

<div class="content-wrapper">
<div class="container-xxl flex-grow-1 container-p-y">
<form method="POST" action="{{ route('branch-transfers.store') }}" id="formTransfer">
@csrf
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0">{{ __('Buat Mutasi Aset') }}</h5>
            <small class="text-muted">{{ __('Aset tetap tercatat di cabang asal sampai cabang tujuan menekan "Terima".') }}</small>
        </div>
        <a href="{{ route('branch-transfers.index') }}" class="btn btn-outline-secondary">{{ __('Kembali') }}</a>
    </div>
    <div class="card-body">
        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <h6 class="mb-3"><span class="mt-step">1</span> {{ __('Rute pengiriman') }}</h6>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">{{ __('Dari cabang') }}</label>
                <select name="from_branch_id" id="from_branch_id" class="form-select" required {{ $restricted ? 'readonly' : '' }}>
                    @foreach($fromOptions as $b)
                        <option value="{{ $b->id }}" @selected(old('from_branch_id', $restricted) == $b->id)>{{ $b->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">{{ __('Ke cabang') }}</label>
                <select name="to_branch_id" id="to_branch_id" class="form-select" required>
                    <option value="">{{ __('— Pilih cabang tujuan —') }}</option>
                    @foreach($toOptions as $b)
                        <option value="{{ $b->id }}" @selected(old('to_branch_id') == $b->id)>{{ $b->label() }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <h6 class="mb-3 mt-2"><span class="mt-step">2</span> {{ __('Pilih aset yang dikirim') }}</h6>
        <div class="row g-3">
            <div class="col-lg-7">
                <div class="d-flex gap-2 mb-2">
                    <input type="search" id="assetSearch" class="form-control" placeholder="{{ __('Cari kode, nama, lokasi, atau serial number') }}">
                    <button type="button" class="btn btn-outline-primary text-nowrap" id="btnAll">{{ __('Pilih semua') }}</button>
                </div>
                <div class="mt-pick" id="assetList"><div class="p-4 text-center text-muted">{{ __('Memuat aset…') }}</div></div>
                <small class="text-muted">{{ __('Menampilkan maks. 50 aset; gunakan pencarian untuk aset lain. Aset yang sedang dalam mutasi lain tidak ditampilkan.') }}</small>
            </div>
            <div class="col-lg-5">
                <div class="border rounded-3 p-3 h-100">
                    <div class="d-flex justify-content-between mb-2">
                        <strong>{{ __('Aset dipilih') }}</strong>
                        <span class="badge bg-primary" id="selCount">0</span>
                    </div>
                    <div class="mt-sel" id="selected"><span class="text-muted bg-transparent p-0">{{ __('Belum ada aset dipilih.') }}</span></div>
                </div>
            </div>
        </div>

        <h6 class="mb-3 mt-4"><span class="mt-step">3</span> {{ __('Catatan') }}</h6>
        <textarea name="notes" class="form-control" rows="2" maxlength="1000" placeholder="Contoh: dikirim via ekspedisi JNE, resi 1234…">{{ old('notes') }}</textarea>
        <div id="hiddenIds"></div>
    </div>
    <div class="card-footer d-flex justify-content-end gap-2">
        <a href="{{ route('branch-transfers.index') }}" class="btn btn-label-secondary">{{ __('Batal') }}</a>
        <button type="submit" class="btn btn-primary"><i class="bi bi-truck me-1"></i> {{ __('Kirim Aset') }}</button>
    </div>
</div>
</form>
</div>
</div>
@endsection

@section('js')
<script>
(function () {
    const url = @json(route('branch-transfers.assets'));
    const selected = new Map();
    let rows = [];
    let timer = null;

    const esc = s => $('<div>').text(s ?? '').html();

    function renderSelected() {
        $('#selCount').text(selected.size);
        if (!selected.size) {
            $('#selected').html('<span class="text-muted bg-transparent p-0">Belum ada aset dipilih.</span>');
            return;
        }
        $('#selected').html([...selected.values()].map(a =>
            `<span>${esc(a.code)} · ${esc(a.name)} <button type="button" data-rm="${a.id}" title="Hapus">&times;</button></span>`).join(''));
    }

    function renderList() {
        if (!rows.length) {
            $('#assetList').html('<div class="p-4 text-center text-muted">Tidak ada aset yang bisa dikirim dari cabang ini.</div>');
            return;
        }
        $('#assetList').html(rows.map(a => `
            <label>
                <input type="checkbox" class="form-check-input mt-1" value="${a.id}" ${selected.has(a.id) ? 'checked' : ''}>
                <div>
                    <div class="fw-bold">${esc(a.code)} · ${esc(a.name)}</div>
                    <div class="meta">${a.location ? '<i class="bi bi-geo-alt"></i> ' + esc(a.location) : 'Lokasi belum diisi'}${a.holder ? ' · <i class="bi bi-person"></i> ' + esc(a.holder) : ''}</div>
                </div>
            </label>`).join(''));
    }

    function load() {
        $.get(url, { branch_id: $('#from_branch_id').val(), q: $('#assetSearch').val() }, res => {
            rows = res.data || [];
            renderList();
        });
    }

    $('#from_branch_id').on('change', function () { selected.clear(); renderSelected(); load(); });
    $('#assetSearch').on('input', function () { clearTimeout(timer); timer = setTimeout(load, 300); });

    $('#assetList').on('change', 'input[type=checkbox]', function () {
        const a = rows.find(r => r.id == this.value);
        this.checked ? selected.set(a.id, a) : selected.delete(a.id);
        renderSelected();
    });

    $('#selected').on('click', '[data-rm]', function () {
        selected.delete(Number($(this).data('rm')));
        renderSelected(); renderList();
    });

    $('#btnAll').on('click', function () { rows.forEach(a => selected.set(a.id, a)); renderSelected(); renderList(); });

    $('#formTransfer').on('submit', function (e) {
        if (!selected.size) {
            e.preventDefault();
            Swal.fire({ icon: 'warning', title: @json(__('Belum ada aset')), text: @json(__('Pilih minimal satu aset yang akan dikirim.')) });
            return;
        }
        if ($('#to_branch_id').val() === $('#from_branch_id').val()) {
            e.preventDefault();
            Swal.fire({ icon: 'warning', title: @json(__('Cabang sama')), text: @json(__('Cabang tujuan harus berbeda dengan cabang asal.')) });
            return;
        }
        $('#hiddenIds').html([...selected.keys()].map(id => `<input type="hidden" name="asset_ids[]" value="${id}">`).join(''));
    });

    @if($restricted)
        $('#from_branch_id').css('pointer-events', 'none').addClass('bg-light');
    @endif
    load();
})();
</script>
@endsection
