{{-- Satu baris daftar CMS. Variabel: $listName, $index, $subFields, $row --}}
<div class="cms-list-row">
    <div class="cms-list-head">
        <span class="num">#{{ is_int($index) ? $index + 1 : '' }}</span>
        <div class="cms-list-tools">
            <button type="button" data-row-action="up" title="Naik"><i class="bi bi-arrow-up"></i></button>
            <button type="button" data-row-action="down" title="Turun"><i class="bi bi-arrow-down"></i></button>
            <button type="button" data-row-action="remove" class="danger" title="Hapus"><i class="bi bi-trash"></i></button>
        </div>
    </div>
    <div class="row g-2">
        @foreach($subFields as $sub => $subField)
            <div class="{{ in_array($subField['type'], ['textarea', 'lines'], true) ? 'col-12' : ($subField['type'] === 'icon' ? 'col-md-4' : 'col-md-8') }} cms-field mb-2">
                <label class="small">{{ $subField['label'] ?? $sub }}</label>
                @include('backoffice.cms._field', [
                    'fname' => "content[$listName][$index][$sub]",
                    'field' => $subField,
                    'val' => $row[$sub] ?? null,
                    'fid' => "f_{$listName}_{$index}_{$sub}",
                ])
            </div>
        @endforeach
    </div>
</div>
