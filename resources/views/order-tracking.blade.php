@extends('layouts.app')

@section('title', $order->orderNumber() . ' — Shree Vinayak Family Shop')

@section('content')
<section class="relative py-8 sm:py-14 bg-ivory min-h-[80vh] overflow-hidden">
    {{-- ambient glow blobs, echoing the homepage feel --}}
    <div class="pointer-events-none absolute inset-0 overflow-hidden">
        <div class="absolute -top-10 left-[10%] w-64 h-64 rounded-full bg-gold-400/20 blur-3xl"></div>
        <div class="absolute bottom-10 -right-10 w-72 h-72 rounded-full bg-maroon-400/10 blur-3xl"></div>
    </div>

    <div class="relative max-w-lg lg:max-w-none mx-auto px-4 sm:px-6 lg:px-10 xl:px-16">
        <nav class="text-sm text-maroon-500 flex items-center gap-2 mb-5">
            <a href="/" class="hover:text-gold-600 transition">{{ __('Home') }}</a>
            <span class="text-gold-400">✦</span>
            <a href="/account" class="hover:text-gold-600 transition">{{ __('My Orders') }}</a>
            <span class="text-gold-400">✦</span>
            <span class="text-maroon-700 font-medium">{{ $order->orderNumber() }}</span>
        </nav>

        @include('partials.order-detail', ['order' => $order, 'orderForJs' => $orderForJs, 'justPlaced' => $justPlaced, 'autoOpenChat' => $autoOpenChat])
    </div>
</section>
@endsection
