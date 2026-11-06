@extends('backoffice.layouts.app')

@section('title', 'Edit: ' . $section['label'])

@section('content')

@include('backoffice.partials.style')

@php
    $anchors = ['hero' => '', 'compare' => '#beda', 'features' => '#fitur', 'tour' => '#tur', 'lifecycle' => '#alur', 'request' => '#alur', 'roles' => '#solusi', 'solutions' => '#solusi', 'mobile' => '#harga', 'pricing' => '#harga', 'faq' => '#faq', 'cta' => '#faq'];
    $isBase = $locale === \App\Services\Landing\LandingContent::DEFAULT_LOCALE;
    $landingUrl = ($isBase ? route('landing') : route('landing.en')) . ($anchors[$key] ?? '');
    $flags = ['id' => '🇮🇩', 'en' => '🇬🇧'];
@endphp

<style>
    .cms-lang { display:flex; gap:6px; border-bottom:1px solid rgba(15,27,45,.08); padding:0 20px; }
    .cms-lang a { padding:14px 14px 12px; font-weight:700; font-size:.9rem; color:#8592a6; border-bottom:2px solid transparent; margin-bottom:-1px; display:flex; gap:8px; align-items:center; }
    .cms-lang a.on { color:#1f5fbf; border-color:#1f5fbf; }
    .cms-lang .dot { width:7px; height:7px; border-radius:50%; background:#cbd5e1; }
    .cms-lang .dot.ok { background:#14b8a6; }
</style>

<div class="container-xxl flex-grow-1 container-p-y">

    <div class="cms-card p-3 p-md-4 mb-4 d-flex flex-wrap justify-content-between align-items-start gap-3">
        <div>
            <a href="{{ route('backoffice.cms.index') }}" class="text-muted small"><i class="bi bi-arrow-left"></i> Konten Landing</a>
            <h4 class="mb-1 mt-1"><i class="bi {{ $section['icon'] }} me-1 text-primary"></i>{{ $section['label'] }}</h4>
            <div class="text-muted small">{{ $section['description'] }}</div>
        </div>
        <div class="d-flex gap-2 flex-wrap align-items-center">
            @if($customized)
                <span class="cms-chip cms-chip-blue">Sudah diubah</span>
            @else
                <span class="cms-chip">Masih teks bawaan</span>
            @endif
            <a href="{{ $landingUrl }}" target="_blank" class="btn btn-outline-primary btn-sm"><i class="bi bi-box-arrow-up-right me-1"></i> Lihat di landing ({{ strtoupper($locale) }})</a>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle me-1"></i> {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('backoffice.cms.update', $key) }}" enctype="multipart/form-data" class="cms-card" id="cmsForm">
        @csrf
        <input type="hidden" name="locale" value="{{ $locale }}">

        <div class="cms-lang">
            @foreach($locales as $code => $label)
                @php $done = $code === $locale ? $customized : ($customizedOther[$code] ?? false); @endphp
                <a href="{{ route('backoffice.cms.edit', ['key' => $key, 'lang' => $code]) }}" class="{{ $code === $locale ? 'on' : '' }}" data-lang-tab>
                    <span>{{ $flags[$code] ?? '' }}</span> {{ $label }}
                    <span class="dot {{ $done ? 'ok' : '' }}" title="{{ $done ? 'Sudah diubah' : 'Masih teks bawaan' }}"></span>
                </a>
            @endforeach
        </div>

        <div class="p-3 p-md-4">
            @if(!$isBase)
                <div class="alert alert-info small py-2">
                    <i class="bi bi-translate me-1"></i>
                    Anda mengedit versi <strong>English</strong> (tampil di <a href="{{ route('landing.en') }}" target="_blank">/en</a>).
                    Gambar dipakai bersama kedua bahasa dan diubah di tab Bahasa Indonesia.
                </div>
            @endif

            @foreach($section['fields'] as $name => $field)
                @continue($field['type'] === 'image' && !$isBase && empty($value[$name]))
                <div class="cms-field">
                    <label for="f_{{ $name }}">{{ $field['label'] ?? $name }}</label>

                    @if($field['type'] === 'image' && !$isBase)
                        <div class="cms-image">
                            <div class="cms-image-preview"><img src="{{ \App\Services\Landing\LandingContent::imageUrl($value[$name]) }}" alt=""></div>
                            <div class="cms-help">Sama dengan versi Indonesia.</div>
                        </div>
                    @elseif($field['type'] === 'image')
                        @php $url = \App\Services\Landing\LandingContent::imageUrl($value[$name] ?? null); @endphp
                        <div class="cms-image">
                            <div class="cms-image-preview" data-preview-for="f_{{ $name }}">
                                @if($url)<img src="{{ $url }}" alt="">@else<span>Belum ada gambar<br>(memakai ilustrasi bawaan)</span>@endif
                            </div>
                            <div>
                                <input type="file" class="form-control cms-image-input" name="images[{{ $name }}]" id="f_{{ $name }}" accept="image/jpeg,image/png,image/webp,image/gif">
                                @if($url)
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" name="remove_images[{{ $name }}]" value="1" id="rm_{{ $name }}">
                                        <label class="form-check-label small" for="rm_{{ $name }}">Hapus gambar (kembali ke ilustrasi bawaan)</label>
                                    </div>
                                @endif
                            </div>
                        </div>
                        @error("images.$name")<div class="text-danger small mt-1">{{ $message }}</div>@enderror

                    @elseif($field['type'] === 'list')
                        @php $rows = array_values((array) ($value[$name] ?? [])); @endphp
                        <div class="cms-list" data-list="{{ $name }}" data-max="{{ $field['max'] ?? 20 }}">
                            @foreach($rows as $i => $row)
                                @include('backoffice.cms._list_row', ['listName' => $name, 'index' => $i, 'subFields' => $field['fields'], 'row' => $row])
                            @endforeach
                        </div>
                        <template data-template-for="{{ $name }}">
                            @include('backoffice.cms._list_row', ['listName' => $name, 'index' => '__INDEX__', 'subFields' => $field['fields'], 'row' => []])
                        </template>
                        <button type="button" class="btn btn-outline-primary btn-sm" data-add-row="{{ $name }}">
                            <i class="bi bi-plus-lg me-1"></i> Tambah {{ strtolower($field['label']) }}
                        </button>
                        <span class="cms-help ms-2">Maks. {{ $field['max'] ?? 20 }}. Urutan bisa diatur dengan tombol panah.</span>

                    @else
                        @include('backoffice.cms._field', ['fname' => "content[$name]", 'field' => $field, 'val' => $value[$name] ?? null, 'fid' => "f_$name"])
                    @endif

                    @if(!empty($field['help']))<div class="cms-help">{{ $field['help'] }}</div>@endif
                    @if($field['type'] === 'lines' && str_contains($field['label'] ?? '', 'ikon'))
                        <div class="cms-help">Contoh: <code>bi-qr-code | Label QR di setiap perangkat</code>. Daftar ikon: <a href="https://icons.getbootstrap.com" target="_blank" rel="noopener">icons.getbootstrap.com</a></div>
                    @endif
                </div>
            @endforeach

            <datalist id="cmsIconList">
                @foreach($icons as $icon)<option value="{{ $icon }}">@endforeach
            </datalist>
        </div>

        <div class="cms-sticky">
            <div>
                @if($customized)
                    <button type="button" class="btn btn-outline-danger btn-sm" id="btnReset"><i class="bi bi-arrow-counterclockwise me-1"></i> Kembalikan ke bawaan</button>
                @endif
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('backoffice.cms.index') }}" class="btn btn-outline-secondary">Batal</a>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Simpan &amp; terbitkan ({{ strtoupper($locale) }})</button>
            </div>
        </div>
    </form>

    <form method="POST" action="{{ route('backoffice.cms.reset', $key) }}" id="resetForm" class="d-none">@csrf<input type="hidden" name="locale" value="{{ $locale }}"></form>
</div>

@endsection

@section('js')
<script>
(function () {
    // Tambah / hapus / urutkan baris daftar.
    var counter = Date.now();

    var refresh = function (list) {
        var rows = list.querySelectorAll(':scope > .cms-list-row');
        rows.forEach(function (row, i) { var n = row.querySelector('.num'); if (n) n.textContent = '#' + (i + 1); });
        var add = document.querySelector('[data-add-row="' + list.dataset.list + '"]');
        if (add) add.disabled = rows.length >= parseInt(list.dataset.max, 10);
    };

    document.querySelectorAll('.cms-list').forEach(refresh);

    document.addEventListener('click', function (e) {
        var addBtn = e.target.closest('[data-add-row]');
        if (addBtn) {
            var name = addBtn.dataset.addRow;
            var list = document.querySelector('.cms-list[data-list="' + name + '"]');
            var tpl = document.querySelector('template[data-template-for="' + name + '"]');
            var html = tpl.innerHTML.replace(/__INDEX__/g, 'n' + (counter++));
            list.insertAdjacentHTML('beforeend', html);
            refresh(list);
            var last = list.lastElementChild.querySelector('input,textarea');
            if (last) last.focus();
            return;
        }

        var tool = e.target.closest('[data-row-action]');
        if (!tool) return;
        var row = tool.closest('.cms-list-row'), list = row.parentNode, action = tool.dataset.rowAction;

        if (action === 'up' && row.previousElementSibling) list.insertBefore(row, row.previousElementSibling);
        if (action === 'down' && row.nextElementSibling) list.insertBefore(row.nextElementSibling, row);
        if (action === 'remove') row.remove();
        refresh(list);
    });

    // Pratinjau ikon.
    document.addEventListener('input', function (e) {
        if (!e.target.classList.contains('cms-icon-field')) return;
        var i = e.target.parentNode.querySelector('.preview i');
        if (i) i.className = 'bi ' + (e.target.value.trim() || 'bi-check2-circle');
    });

    // Pratinjau gambar sebelum diunggah.
    document.querySelectorAll('.cms-image-input').forEach(function (input) {
        input.addEventListener('change', function () {
            var box = document.querySelector('[data-preview-for="' + input.id + '"]');
            if (!box || !input.files[0]) return;
            if (input.files[0].size > 2 * 1024 * 1024) {
                Swal.fire({ icon: 'warning', title: 'Gambar terlalu besar', text: 'Maksimal 2 MB.' });
                input.value = '';
                return;
            }
            box.innerHTML = '<img src="' + URL.createObjectURL(input.files[0]) + '" alt="">';
        });
    });

    // Kembalikan ke bawaan.
    var reset = document.getElementById('btnReset');
    if (reset) {
        reset.addEventListener('click', function () {
            Swal.fire({
                icon: 'warning',
                title: @json('Kembalikan versi ' . $locales[$locale] . ' ke teks bawaan?'),
                text: @json($isBase ? 'Semua perubahan & gambar di bagian ini akan dihapus. Versi English tidak terpengaruh.' : 'Perubahan teks versi English di bagian ini akan dihapus. Versi Indonesia & gambar tidak terpengaruh.'),
                showCancelButton: true,
                confirmButtonText: 'Ya, kembalikan',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then(function (r) { if (r.isConfirmed) document.getElementById('resetForm').submit(); });
        });
    }
})();
</script>
@endsection
