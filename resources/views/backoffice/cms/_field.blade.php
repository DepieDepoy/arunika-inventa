{{--
    Satu input CMS sesuai tipe skema.
    Variabel: $fname (nama input), $field (skema), $val (nilai), $fid (id unik)
--}}
@switch($field['type'])
    @case('text')
        <input type="text" class="form-control" name="{{ $fname }}" id="{{ $fid }}" value="{{ $val }}" maxlength="300">
        @break

    @case('textarea')
        <textarea class="form-control" name="{{ $fname }}" id="{{ $fid }}" rows="3" maxlength="3000">{{ $val }}</textarea>
        @break

    @case('lines')
        <textarea class="form-control" name="{{ $fname }}" id="{{ $fid }}" rows="{{ max(3, count((array) $val) + 1) }}">{{ implode("\n", (array) $val) }}</textarea>
        @break

    @case('icon')
        <div class="cms-icon-input">
            <span class="preview"><i class="bi {{ $val ?: 'bi-check2-circle' }}"></i></span>
            <input type="text" class="form-control cms-icon-field" name="{{ $fname }}" id="{{ $fid }}" value="{{ $val }}" list="cmsIconList" placeholder="bi-qr-code">
        </div>
        @break
@endswitch
