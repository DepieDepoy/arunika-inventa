{{-- Bendera bahasa (SVG, karena emoji bendera tidak tampil di Windows). Pakai: <x-flag code="id" /> atau <x-flag code="en" /> --}}
@props(['code' => 'id', 'width' => 22])
@php
    $h = round($width * 2 / 3);
    $style = 'flex-shrink:0;border-radius:2px;box-shadow:0 0 0 1px rgba(0,0,0,.18);vertical-align:middle';
@endphp
@if($code === 'en')
<svg {{ $attributes->merge(['class' => 'lang-flag']) }} width="{{ $width }}" height="{{ $h }}" viewBox="0 0 60 40" style="{{ $style }}" role="img" aria-label="English">
    <path d="M0 0h60v40H0z" fill="#012169"/>
    <path d="M0 0l60 40M60 0L0 40" stroke="#fff" stroke-width="8"/>
    <path d="M0 0l60 40M60 0L0 40" stroke="#C8102E" stroke-width="3"/>
    <path d="M30 0v40M0 20h60" stroke="#fff" stroke-width="12"/>
    <path d="M30 0v40M0 20h60" stroke="#C8102E" stroke-width="7"/>
</svg>
@else
<svg {{ $attributes->merge(['class' => 'lang-flag']) }} width="{{ $width }}" height="{{ $h }}" viewBox="0 0 60 40" style="{{ $style }}" role="img" aria-label="Bahasa Indonesia">
    <path d="M0 0h60v20H0z" fill="#E70011"/>
    <path d="M0 20h60v20H0z" fill="#fff"/>
</svg>
@endif
