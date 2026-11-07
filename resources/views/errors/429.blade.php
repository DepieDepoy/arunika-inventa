@extends('errors.layout')

@section('code', 'Error 429')
@section('title', 'Terlalu banyak permintaan')
@section('message', 'Demi keamanan, permintaan dari perangkat Anda kami batasi sementara. Tunggu sekitar satu menit, lalu coba lagi.')
@section('icon')
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 3 7v5c0 5 3.8 8.3 9 9 5.2-.7 9-4 9-9V7z"/><path d="M12 8v5"/><circle cx="12" cy="16.2" r=".9" fill="currentColor"/></svg>
@endsection
