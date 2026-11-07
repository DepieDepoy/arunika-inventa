@extends('errors.layout')

@section('code', 'Error 419')
@section('title', 'Sesi Anda telah berakhir')
@section('message', 'Halaman terlalu lama dibiarkan terbuka sehingga sesi keamanannya habis. Muat ulang halaman, lalu ulangi tindakan Anda.')
@section('icon')
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="13" r="8"/><path d="M12 9v4l2.5 2"/><path d="M9 2h6"/></svg>
@endsection
@section('actions')
<button type="button" class="btn btn-primary" onclick="history.length > 1 ? history.back() : location.reload()">Kembali &amp; muat ulang</button>
<a class="btn btn-ghost" href="{{ url('/login') }}">Masuk lagi</a>
@endsection
