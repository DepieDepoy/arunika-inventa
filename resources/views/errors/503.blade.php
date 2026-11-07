@extends('errors.layout')

@section('code', 'Sedang pembaruan')
@section('title', 'Vasetra sedang diperbarui')
@section('message', 'Kami sedang memasang pembaruan agar layanan semakin baik. Proses ini biasanya hanya beberapa menit. Data Anda aman.')
@section('icon')
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-2.6-6.4"/><path d="M21 4v5h-5"/></svg>
@endsection
@section('actions')
<button type="button" class="btn btn-primary" onclick="location.reload()">Muat ulang</button>
@endsection
@section('hint', 'Halaman ini akan kembali normal setelah pembaruan selesai.')
