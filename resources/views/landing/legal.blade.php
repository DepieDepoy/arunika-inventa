@extends('landing.layouts.page')

@section('title', $doc['title'] ?? '')
@section('description', \Illuminate\Support\Str::limit($doc['intro'] ?? '', 155))

@section('head')
    <link rel="canonical" href="{{ url()->current() }}">
    <link rel="alternate" hreflang="id" href="{{ route($page === 'privacy' ? 'legal.privacy' : 'legal.terms') }}">
    <link rel="alternate" hreflang="en" href="{{ route($page === 'privacy' ? 'legal.privacy.en' : 'legal.terms.en') }}">
@endsection

@section('lang')
    <a href="{{ route($page === 'privacy' ? 'legal.privacy' : 'legal.terms') }}" class="{{ $locale === 'id' ? 'on' : '' }}" hreflang="id">ID</a>
    <a href="{{ route($page === 'privacy' ? 'legal.privacy.en' : 'legal.terms.en') }}" class="{{ $locale === 'en' ? 'on' : '' }}" hreflang="en">EN</a>
@endsection

@section('content')
<section class="hero">
    <div class="wrap">
        <h1>{{ $doc['title'] ?? '' }}</h1>
        @if(!empty($doc['intro']))<p>{{ $doc['intro'] }}</p>@endif
        @if(!empty($doc['updated']))<div class="meta">{{ $locale === 'en' ? 'Effective' : 'Berlaku sejak' }} {{ $doc['updated'] }}</div>@endif
    </div>
</section>
<div class="wrap">
    <article class="card doc">{!! $html !!}</article>
</div>
@endsection
