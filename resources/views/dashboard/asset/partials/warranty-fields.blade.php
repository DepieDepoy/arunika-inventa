{{-- Isian garansi, dipakai form tambah & edit aset. $asset = null saat tambah. --}}
@php
    $wStart = old('warranty_start', $asset?->warranty_start?->format('Y-m-d'));
    $wEnd = old('warranty_end', $asset?->warranty_end?->format('Y-m-d'));
    $wNote = old('warranty_note', $asset?->warranty_note);
@endphp

<hr class="my-3">

<div class="d-flex align-items-center gap-2 mb-2">
    <i class="bi bi-shield-check text-primary"></i>
    <h6 class="mb-0">{{ __('Warranty') }}</h6>
    <small class="text-muted">{{ __('optional · a reminder is sent 30 and 7 days before it ends') }}</small>
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label" for="warranty_start">{{ __('Warranty Start') }}</label>
        <input type="date" name="warranty_start" id="warranty_start" class="form-control" value="{{ $wStart }}">
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label" for="warranty_end">{{ __('Warranty End') }}</label>
        <input type="date" name="warranty_end" id="warranty_end" class="form-control" value="{{ $wEnd }}">
        <div class="d-flex flex-wrap gap-1 mt-1" data-warranty-quick>
            <small class="text-muted me-1">{{ __('Quick:') }}</small>
            @foreach([1, 2, 3] as $y)
                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" data-years="{{ $y }}">{{ $y }} yr</button>
            @endforeach
        </div>
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label" for="warranty_note">{{ __('Warranty Note') }}</label>
        <input type="text" name="warranty_note" id="warranty_note" class="form-control" maxlength="1000"
               placeholder="{{ __('e.g. Official service center, card no. 12345') }}" value="{{ $wNote }}">
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var start = document.getElementById('warranty_start');
    var end = document.getElementById('warranty_end');
    var purchase = document.querySelector('[name="purchase_date"]');
    if (!start || !end) return;

    // Tombol cepat: akhir garansi = mulai garansi (atau tanggal beli / hari ini) + N tahun - 1 hari.
    document.querySelectorAll('[data-warranty-quick] [data-years]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var base = start.value || (purchase && purchase.value) || new Date().toISOString().slice(0, 10);
            if (!start.value) start.value = base;
            var d = new Date(base + 'T00:00:00');
            d.setFullYear(d.getFullYear() + parseInt(btn.dataset.years, 10));
            d.setDate(d.getDate() - 1);
            var pad = function (n) { return String(n).padStart(2, '0'); };
            end.value = d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
        });
    });

    // Mulai garansi kosong: ikuti tanggal beli.
    if (purchase) {
        purchase.addEventListener('change', function () {
            if (!start.value) start.value = purchase.value;
        });
    }

    start.addEventListener('change', function () { end.min = start.value || ''; });
    end.min = start.value || '';
});
</script>
