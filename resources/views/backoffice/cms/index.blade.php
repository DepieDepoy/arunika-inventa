@extends('backoffice.layouts.app')

@section('title', 'Konten Landing Page')

@section('content')

@include('backoffice.partials.style')

<div class="container-xxl flex-grow-1 container-p-y">

    <div class="cms-card p-3 p-md-4 mb-4 d-flex flex-wrap justify-content-between align-items-start gap-3">
        <div>
            <span class="cms-badge"><i class="bi bi-shield-lock"></i> Khusus pegawai Vasetra</span>
            <h4 class="mb-1 mt-2">Konten Landing Page</h4>
            <div class="text-muted small">
                Ubah teks, daftar, dan gambar di halaman depan Vasetra. Perubahan langsung tampil setelah disimpan.
                Gunakan <code>{hari_trial}</code> di teks untuk menampilkan masa coba ({{ $trialDays }} hari) secara otomatis.
                Setiap bagian punya versi <strong>Indonesia</strong> (<a href="{{ route('landing') }}" target="_blank">/</a>) dan <strong>English</strong> (<a href="{{ route('landing.en') }}" target="_blank">/en</a>) — versi English sudah punya teks bawaan sendiri.
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @if(auth('backoffice')->user()?->isSuperAdmin())
                <a href="{{ route('backoffice.plans.index') }}" class="btn btn-outline-primary"><i class="bi bi-tags me-1"></i> Paket &amp; Harga</a>
            @endif
            <a href="{{ url('/') }}" target="_blank" class="btn btn-primary"><i class="bi bi-box-arrow-up-right me-1"></i> Lihat landing page</a>
        </div>
    </div>

    <div class="row g-3">
        @foreach($sections as $s)
            <div class="col-md-6 col-xl-4">
                <a href="{{ route('backoffice.cms.edit', $s['key']) }}" class="cms-section">
                    <span class="cms-section-icon"><i class="bi {{ $s['icon'] }}"></i></span>
                    <span class="flex-grow-1" style="min-width:0">
                        <strong class="cms-section-title d-block">{{ $s['label'] }}</strong>
                        <span class="d-flex flex-wrap gap-1 mt-1">
                            @foreach($s['status'] as $code => $st)
                                <span class="cms-chip {{ $st['customized'] ? 'cms-chip-blue' : '' }}" title="{{ $st['customized'] ? 'Sudah diubah' : 'Masih teks bawaan' }}">
                                    {{ strtoupper($code) }} · {{ $st['customized'] ? 'Diubah' : 'Bawaan' }}
                                </span>
                            @endforeach
                            @if($s['en_outdated'])
                                <span class="cms-chip" style="background:#fef3c7;color:#92400e" title="Versi Indonesia diubah lebih baru dari versi English"><i class="bi bi-exclamation-triangle"></i> EN perlu diperbarui</span>
                            @endif
                        </span>
                        <span class="cms-section-desc">{{ $s['description'] }}</span>
                        @if($s['updated_at'])
                            <span class="cms-section-meta">Terakhir diubah {{ $s['updated_at']->diffForHumans() }}{{ $s['editor'] ? ' oleh ' . $s['editor'] : '' }}</span>
                        @endif
                    </span>
                    <i class="bi bi-chevron-right text-muted"></i>
                </a>
            </div>
        @endforeach
    </div>
</div>

@endsection
