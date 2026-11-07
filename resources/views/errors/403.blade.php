@extends('errors.layout')

@section('code', 'Error 403')
@section('title', 'Akses ditolak')
@section('message', 'Akun Anda tidak memiliki hak akses ke halaman ini. Jika Anda membutuhkannya, minta administrator perusahaan Anda untuk menambahkan izin pada role Anda.')
@section('icon')
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="11" rx="2.5"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/><circle cx="12" cy="15.5" r="1.2"/></svg>
@endsection
