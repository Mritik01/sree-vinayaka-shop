@extends('layouts.app')

@section('title', 'Shree Vinayak Family Shop — No. 1 Grocery Store in Siswa Bazar')

@section('content')
    {{-- reference-matching top: featured categories → categories → hero banner → category-tabbed shop → feature strip --}}
    @include('partials.featured-categories')
    @include('partials.category-row')
    @include('partials.hero-slider')
    @include('partials.category-shop')
    @include('partials.feature-strip')

    {{-- existing sections kept below the new top (per user decision) --}}
    @include('partials.festival-special')
    @include('partials.promo-banner')
    @include('partials.about-section')
    @include('partials.visit-us')
    @include('partials.newsletter')
@endsection
