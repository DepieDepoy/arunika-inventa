@extends('backoffice.layouts.app')

@section('title', 'Ganti Password')

@section('content')
@include('backoffice.partials.style')
<div class="container-xxl flex-grow-1 container-p-y">
    <form method="POST" action="{{ route('backoffice.password.update') }}" class="cms-card p-4" style="max-width:520px">
        @csrf
        <h5 class="mb-1">Ganti password backoffice</h5>
        <p class="text-muted small mb-4">Minimal 10 karakter, berisi huruf dan angka.</p>

        @if($errors->any())<div class="alert alert-danger py-2 small">{{ $errors->first() }}</div>@endif

        <div class="mb-3">
            <label class="form-label fw-bold small">Password saat ini</label>
            <input type="password" name="current_password" class="form-control" required autocomplete="current-password">
        </div>
        <div class="mb-3">
            <label class="form-label fw-bold small">Password baru</label>
            <input type="password" name="password" class="form-control" required autocomplete="new-password">
        </div>
        <div class="mb-4">
            <label class="form-label fw-bold small">Ulangi password baru</label>
            <input type="password" name="password_confirmation" class="form-control" required autocomplete="new-password">
        </div>
        <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Simpan password</button>
    </form>
</div>
@endsection
