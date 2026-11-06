{{-- Judul bagian landing dari CMS. Variabel: $h (kicker/title/highlight/subtitle), $underline (opsional), $dark (opsional) --}}
<div class="head reveal">
  @if(!empty($h['kicker']))<span class="kicker">{{ $h['kicker'] }}</span>@endif
  <h2>{{ $h['title'] ?? '' }}@if(!empty($h['highlight']))<br><span class="grad-text{{ !empty($underline) ? ' underline' : '' }}">{{ $h['highlight'] }}</span>@endif</h2>
  @if(!empty($h['subtitle']))<p @if(!empty($dark)) style="color:rgba(255,255,255,.7)" @endif>{{ $h['subtitle'] }}</p>@endif
</div>
