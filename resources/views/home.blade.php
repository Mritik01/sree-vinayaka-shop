@extends('layouts.app')

@section('title', 'Makhanbhog Sweets — No. 1 Sweet Shop in Thuthibari')

@section('content')
    {{-- reference-matching top: categories → hero banner → feature strip → best sellers --}}
    @include('partials.category-row')
    @include('partials.hero-slider')
    @include('partials.feature-strip')
    @include('partials.best-sellers')

    {{-- existing sections kept below the new top (per user decision) --}}
    @include('partials.festival-special')
    @include('partials.promo-banner')
    @include('partials.about-section')
    @include('partials.visit-us')
    @include('partials.newsletter')
@endsection
