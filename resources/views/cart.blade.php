@extends('layouts.app')

@section('title', 'Your Cart — Makhanbhog Sweets')

@section('content')
@php
    $cartItemsForJs = $cartProducts->map(fn ($p) => $p->cartRow((int) $p->pivot->portion, (int) $p->pivot->quantity));
@endphp

<section class="relative py-14 bg-ivory min-h-[70vh]">
    <div class="max-w-6xl lg:max-w-none mx-auto px-4 sm:px-6 lg:px-10 xl:px-16">
        <nav class="text-sm text-maroon-500 flex items-center gap-2 mb-6">
            <a href="/" class="hover:text-gold-600 transition">{{ __('Home') }}</a>
            <span class="text-gold-400">✦</span>
            <span class="text-maroon-700 font-medium">{{ __('Cart') }}</span>
        </nav>

        <h1 class="font-display text-3xl sm:text-4xl text-maroon-800">{{ __('Your Cart') }}</h1>

        @if (session('status'))
            <div class="mt-4 rounded-lg bg-pista-100 border border-pista-400/40 text-pista-600 text-sm px-4 py-3">
                {{ session('status') }}
            </div>
        @endif

        <div x-data='cartPage(@json($cartItemsForJs))'>
            {{-- empty state --}}
            <div x-show="items.length === 0" x-cloak class="text-center py-20">
                <p class="text-5xl mb-4">🛍️</p>
                <p class="text-maroon-700 font-display text-xl">{{ __('Your cart is empty') }}</p>
                <p class="text-maroon-500 mt-2 max-w-sm mx-auto">{{ __("Add a few sweets from Thuthibari's Favourites and they'll show up here.") }}</p>
                <a href="/#bestsellers" class="btn-gold inline-block mt-8">{{ __('Explore Our Sweets') }}</a>
            </div>

            <div x-show="items.length > 0" class="grid lg:grid-cols-[1fr_380px] gap-8 mt-8 items-start">
                {{-- item list --}}
                <div class="bg-white rounded-2xl border border-gold-200/60 shadow-sm divide-y divide-gold-100">
                    <template x-for="item in items" :key="item.id">
                        <div class="flex items-center gap-4 p-4 sm:p-5"
                             x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0 -translate-x-2">
                            <a :href="`/product/${item.slug}`" class="w-20 h-20 sm:w-24 sm:h-24 rounded-xl overflow-hidden shrink-0 border border-gold-200/60">
                                <img :src="item.image" :alt="item.name" class="w-full h-full object-cover">
                            </a>

                            <div class="flex-1 min-w-0">
                                <a :href="`/product/${item.slug}`" class="font-display text-maroon-800 text-lg hover:text-gold-600 transition truncate block" x-text="item.name"></a>
                                <div class="flex flex-wrap items-center gap-1.5 mt-1.5">
                                    <template x-if="item.type === 'loose'">
                                        <select @change="changePortion(item, parseInt($event.target.value))"
                                            class="text-xs font-medium px-2 py-0.5 rounded-md border border-gold-300/60 text-maroon-600 bg-white focus:outline-none focus:ring-1 focus:ring-gold-400">
                                            <template x-for="g in item.portions" :key="g">
                                                <option :value="g" :selected="g === item.portion" x-text="portionLabel(g)"></option>
                                            </template>
                                        </select>
                                    </template>
                                    <template x-if="item.type !== 'loose'">
                                        <span class="text-xs font-medium px-2 py-0.5 rounded-md border border-gold-300/60 text-maroon-500" x-text="item.weight"></span>
                                    </template>
                                    <span class="text-xs font-medium px-2 py-0.5 rounded-md text-cream" :style="`background-color: ${item.color}`" x-text="item.tag"></span>
                                </div>
                                <p class="font-display font-semibold mt-2" :style="`color: ${item.color}`">₹<span x-text="item.price * item.quantity"></span></p>
                            </div>

                            <div class="flex flex-col items-end gap-3 shrink-0">
                                <button @click="remove(item)" aria-label="Remove item" class="text-maroon-300 hover:text-maroon-600 transition">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.7">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                    </svg>
                                </button>
                                <div class="flex items-center gap-2.5 bg-cream rounded-full border border-gold-300/60 px-2.5 py-1">
                                    <button @click="decrement(item)" aria-label="Decrease quantity" class="w-6 h-6 rounded-full hover:bg-gold-100 text-maroon-700 font-bold transition">−</button>
                                    <span class="w-5 text-center text-sm font-semibold text-maroon-800" x-text="item.quantity"></span>
                                    <button @click="increment(item)" aria-label="Increase quantity" class="w-6 h-6 rounded-full hover:bg-gold-100 text-maroon-700 font-bold transition">+</button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- order summary --}}
                <div class="bg-white rounded-2xl border border-gold-200/60 shadow-sm p-6 lg:sticky lg:top-28">
                    <p class="font-display text-lg text-maroon-800">{{ __('Order Summary') }}</p>

                    <div class="flex items-center justify-between mt-5 text-sm">
                        <span class="text-maroon-500">{{ __('Payment') }}</span>
                        <span class="text-maroon-700 font-medium">{{ __('Cash on Delivery') }}</span>
                    </div>

                    <div class="border-t border-gold-200/70 mt-4 pt-4 flex items-center justify-between">
                        <span class="text-maroon-800 font-medium">{{ __('Subtotal') }}</span>
                        <span class="font-display font-semibold text-2xl text-maroon-800">₹<span x-text="subtotal()"></span></span>
                    </div>

                    @include('partials.free-delivery-progress')

                    <div x-show="$store.shop.accepting && $store.shop.highDemandMode !== 'stop'" x-cloak>
                        <a href="/checkout" class="btn-gold w-full text-center mt-5 inline-flex items-center justify-center gap-2">
                            {{ __('Proceed to Checkout') }}
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                            </svg>
                        </a>
                        <p class="text-xs text-maroon-400 text-center mt-3">{{ __('Coupons and delivery address are entered on the next step.') }}</p>
                    </div>
                    <div x-show="!$store.shop.accepting" x-cloak class="mt-5 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3.5 text-center">
                        🚫 {{ __("We're not accepting online orders right now. Please check back soon — your cart will be waiting.") }}
                    </div>
                    <div x-show="$store.shop.accepting && $store.shop.highDemandMode === 'stop'" x-cloak class="mt-5 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3.5 text-center">
                        <span x-text="$store.shop.highDemandStopMessage"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
