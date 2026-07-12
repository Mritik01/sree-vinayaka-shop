@extends('layouts.app')

@section('title', 'Makhanbhog Sweets — No. 1 Sweet Shop in Thuthibari')

@section('content')
    @include('partials.hero-slider')
    @include('partials.product-slider')
    @include('partials.tagline')
    @include('partials.shop-by-range')
    @include('partials.festival-special')
    @include('partials.promo-banner')
    @include('partials.about-section')
    @include('partials.visit-us')
    @include('partials.newsletter')
@endsection
