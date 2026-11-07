@extends('errors.layout')

@section('code', 'Error 500')
@section('title', 'Terjadi kendala di sistem kami')
@section('message', 'Maaf, permintaan Anda belum bisa diproses. Kesalahan ini sudah tercatat dan akan segera kami perbaiki. Silakan coba lagi beberapa saat lagi.')
@section('icon')
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M10.3 3.9 2.6 17.2A2 2 0 0 0 4.3 20h15.4a2 2 0 0 0 1.7-2.8L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4"/><circle cx="12" cy="16.5" r=".9" fill="currentColor"/></svg>
@endsection
@section('hint', 'Jika masalah berlanjut, hubungi tim dukungan Vasetra dan sebutkan waktu kejadiannya.')
