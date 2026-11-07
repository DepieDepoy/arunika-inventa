@extends('errors.layout')

@section('code', 'Error 404')
@section('title', 'Halaman tidak ditemukan')
@section('message', 'Halaman atau data yang Anda cari tidak ada, sudah dihapus, atau alamatnya salah ketik.')
@section('icon')
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/><path d="M9 9l4 4M13 9l-4 4"/></svg>
@endsection
