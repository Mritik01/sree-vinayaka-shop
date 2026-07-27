@extends('layouts.app')

@php
    // rich-text HTML meant for the popup reads oddly as a hero subtitle (stray links, formatting
    // baked in for a small card) — strip it down to plain text here, same idea as
    // LegalController's meta-description treatment
    $heroSubtitle = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($announcement->description ?? ''))));
    $eyebrow = $announcement->landing_page_mode === 'discounted' ? __('🔥 Today\'s Best Deals') : __('✨ Handpicked For You');
@endphp

@section('title', ($announcement->headline ?: __('Special Offer')).' — Shree Vinayak Family Shop')
@section('description', $heroSubtitle ?: __('Explore our specially curated offers.'))

@section('content')
    <section class="bg-ivory">
        {{-- hero — same visual language as the homepage hero banner (partials/hero-slider.blade.php):
             kenburns image zoom, maroon gradient scrim for legibility, staggered fade-up text —
             so this reads as a natural extension of the site rather than a bolted-on page, and
             automatically follows whichever customer theme is active since nothing here is a
             literal hex color. --}}
        <div class="max-w-[1760px] mx-auto px-4 sm:px-8 lg:px-12 pt-4 sm:pt-6">
            <nav class="text-sm text-maroon-500 flex items-center gap-2 mb-4">
                <a href="{{ url('/') }}" class="hover:text-gold-600 transition">{{ __('Home') }}</a>
                <span class="text-gold-400">✦</span>
                <span class="text-maroon-700 font-medium">{{ __('Special Offer') }}</span>
            </nav>

            <div class="relative rounded-2xl sm:rounded-3xl overflow-hidden bg-maroon-900 h-[280px] sm:h-[360px] lg:h-[440px] shadow-lg">
                @if ($announcement->image_path)
                    <img src="{{ asset($announcement->image_path) }}" alt="{{ $announcement->headline }}"
                         fetchpriority="high" class="absolute inset-0 w-full h-full object-cover animate-kenburns">
                @endif
                <div class="absolute inset-0 bg-gradient-to-r from-maroon-900/90 via-maroon-900/55 to-transparent"></div>

                <div class="relative z-10 h-full flex items-center px-6 sm:px-10 lg:px-14">
                    <div class="max-w-md lg:max-w-xl">
                        <p class="animate-fade-up [animation-delay:100ms] inline-block text-gold-300 font-semibold tracking-widest uppercase text-[10px] sm:text-xs mb-2 sm:mb-3 bg-white/10 backdrop-blur-sm rounded-full px-3 py-1">
                            {{ $eyebrow }}
                        </p>
                        <h1 class="animate-fade-up [animation-delay:250ms] font-display text-2xl sm:text-4xl lg:text-5xl font-bold text-cream leading-tight drop-shadow-md mb-2 sm:mb-4">
                            {{ $announcement->headline ?: __('Special Offer') }}
                        </h1>
                        @if ($heroSubtitle)
                            <p class="animate-fade-up [animation-delay:400ms] text-sm sm:text-lg text-gold-100/90 mb-1">
                                {{ $heroSubtitle }}
                            </p>
                        @endif
                        <p class="animate-fade-up [animation-delay:550ms] text-xs sm:text-sm text-cream/70 mt-3">
                            {{ trans_choice(':count product|:count products', $products->count(), ['count' => $products->count()]) }} {{ __('in this offer') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- product grid — reuses the exact same card (image, name, price + discount badge +
             struck-through original price, rating, add-to-cart, wishlist heart — all already
             theme-aware) and grid breakpoints as /products, so this feels like the same catalog,
             not a separate mini-app --}}
        <div class="max-w-[1760px] mx-auto px-4 sm:px-8 lg:px-12 py-10 sm:py-14"
             x-data="favoritesList({{ Auth::check() ? 'true' : 'false' }}, @json($favoritedIds))">
            @if ($products->isEmpty())
                <div class="text-center py-20 animate-fade-up">
                    <p class="text-5xl mb-4">🛍️</p>
                    <p class="text-maroon-700 font-display text-xl">{{ __('Nothing in this offer just yet') }}</p>
                    <p class="text-maroon-500 mt-2 max-w-sm mx-auto">
                        {{ __('Check back soon — or explore the full range in the meantime.') }}
                    </p>
                    <a href="{{ route('products.index') }}" class="btn-gold mt-8 inline-block">{{ __('Browse All Products') }}</a>
                </div>
            @else
                <div class="flex items-center justify-between gap-3 mb-6 sm:mb-8">
                    <h2 class="section-heading !text-left !text-2xl sm:!text-3xl">{{ __('Grab These Before They\'re Gone') }}</h2>
                    <a href="{{ route('products.index') }}" class="hidden sm:flex items-center gap-1.5 text-sm font-semibold text-maroon-700 hover:text-gold-600 transition shrink-0">
                        {{ __('View all products') }}
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                    </a>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 sm:gap-6">
                    @foreach ($products as $product)
                        <div class="animate-fade-up" style="animation-delay: {{ min($loop->index, 11) * 60 }}ms">
                            @include('partials.product-card-mini', ['product' => $product, 'fixedWidth' => false])
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endsection
