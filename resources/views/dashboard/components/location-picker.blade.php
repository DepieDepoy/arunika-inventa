{{--
    Pemilih lokasi (master lokasi per cabang) — dipakai form aset, pindah lokasi,
    terima mutasi, dan audit. Sertakan SEKALI per halaman:
        @include('dashboard.components.location-picker')
    Lalu pada <select data-location-picker data-branch="ID_CABANG" data-value="Nama lokasi">:
        - opsi diisi otomatis dari master lokasi cabang;
        - opsi "+ Tambah lokasi baru..." membuat lokasi baru (bila punya izin);
        - value = NAMA lokasi (server mencocokkan ke master), data-id = ID lokasi.
    Ganti cabang: el.dataset.branch = id; VasetraLocation.load(el);
--}}
@once
<script>
window.VasetraLocation = (function () {
    const urlOptions = @json(route('locations.options'));
    const urlStore = @json(route('locations.store'));
    const canCreate = @json(auth()->user()->hasPermission('location.create') || auth()->user()->hasPermission('asset.create') || auth()->user()->hasPermission('asset.move'));
    const cache = {};
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    function fetchBranch(branchId) {
        const key = branchId || 'all';
        if (!cache[key]) {
            cache[key] = fetch(urlOptions + (branchId ? '?branch_id=' + encodeURIComponent(branchId) : ''), { headers: { 'Accept': 'application/json' } })
                .then(r => r.json()).then(r => r.data || []);
        }
        return cache[key];
    }

    function render(el, items) {
        const current = el.dataset.value ?? el.value ?? '';
        const empty = el.dataset.emptyLabel ?? '— Pilih lokasi —';
        let html = '<option value="">' + esc(empty) + '</option>';
        let found = false;
        items.forEach(l => {
            const sel = current && current.toLowerCase() === String(l.name).toLowerCase();
            found = found || sel;
            html += '<option value="' + esc(l.name) + '" data-id="' + l.id + '"' + (sel ? ' selected' : '') + '>' + esc(l.name) + '</option>';
        });
        // Nilai lama yang belum ada di master tetap ditampilkan agar tidak hilang.
        if (current && !found) {
            html += '<option value="' + esc(current) + '" selected>' + esc(current) + '</option>';
        }
        if (canCreate && el.dataset.allowCreate !== '0') {
            html += '<option value="__new__">+ Tambah lokasi baru…</option>';
        }
        el.innerHTML = html;
        el.dataset.previous = el.value;
    }

    function load(el) {
        el.innerHTML = '<option value="">Memuat lokasi…</option>';
        return fetchBranch(el.dataset.branch || '').then(items => render(el, items));
    }

    function askNew(el) {
        const branchId = el.dataset.branch || '';
        Swal.fire({
            title: @json(__('Lokasi baru')),
            input: 'text',
            inputLabel: @json(__('Nama ruangan / area (mis. Lantai 2 - Ruang Finance)')),
            inputAttributes: { maxlength: 150 },
            showCancelButton: true,
            confirmButtonText: @json(__('Simpan')),
            cancelButtonText: @json(__('Batal')),
            inputValidator: v => !String(v || '').trim() ? 'Nama lokasi wajib diisi' : null,
        }).then(r => {
            if (!r.isConfirmed) { el.value = el.dataset.previous || ''; return; }
            $.ajax({
                url: urlStore, type: 'POST', headers: { 'Accept': 'application/json' },
                data: { _token: $('meta[name="csrf-token"]').attr('content'), name: r.value.trim(), branch_id: branchId },
            }).done(res => {
                delete cache[branchId || 'all'];
                el.dataset.value = res.data.name;
                load(el).then(() => el.dispatchEvent(new Event('change', { bubbles: true })));
            }).fail(xhr => {
                el.value = el.dataset.previous || '';
                Swal.fire({ icon: 'error', title: @json(__('Gagal')), text: xhr.responseJSON?.message || 'Lokasi gagal ditambahkan.' });
            });
        });
    }

    function init(root) {
        (root || document).querySelectorAll('select[data-location-picker]').forEach(el => {
            if (el.dataset.lpReady) return;
            el.dataset.lpReady = '1';
            el.addEventListener('change', function () {
                if (this.value === '__new__') { askNew(this); return; }
                this.dataset.previous = this.value;
                this.dataset.value = this.value;
            });
            load(el);
        });
    }

    document.addEventListener('DOMContentLoaded', () => init());

    return { init, load, selectedId: el => el.selectedOptions[0]?.dataset.id || '' };
})();
</script>
@endonce
