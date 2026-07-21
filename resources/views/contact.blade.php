@extends('layouts.app')

@section('title', 'Contact Us — Shree Vinayak Family Shop')
@section('description', "Get in touch with Shree Vinayak Family Shop, Siswa Bazar's favourite grocery store — phone, WhatsApp, email, opening hours, and directions to our outlet.")

@php
    // same IST-hours logic as partials/visit-us.blade.php, kept in sync deliberately since both
    // display the same "Open Now"/"Closed Now" badge for the same physical outlet
    $outletHourIst = now('Asia/Kolkata')->hour;
    $outletIsOpen = $outletHourIst >= 8 && $outletHourIst < 21;
@endphp

@section('content')
    <section class="max-w-5xl mx-auto px-4 sm:px-6 py-10 sm:py-14">
        <a href="{{ url('/') }}" class="inline-flex items-center gap-1.5 text-sm text-maroon-500 hover:text-maroon-700 transition mb-6">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
            {{ __('Back to Home') }}
        </a>

        <p class="text-gold-600 font-semibold tracking-widest uppercase text-sm mb-2 text-center sm:text-left">{{ __('Get in Touch') }}</p>
        <h1 class="font-display text-3xl sm:text-4xl font-bold text-maroon-800 text-center sm:text-left">{{ __('Contact Us') }}</h1>
        <p class="text-maroon-500 mt-3 max-w-xl text-center sm:text-left mx-auto sm:mx-0">
            {{ __("Questions about an order, a bulk/gift order, or anything else? We'd love to hear from you.") }}
        </p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 md:gap-10 items-start mt-10">
            <div class="mithai-frame relative overflow-hidden h-64 sm:h-80">
                <img src="{{ asset('images/outlet/storefront.jpg') }}" alt="{{ __('Shree Vinayak Family Shop storefront in Siswa Bazar') }}" loading="lazy"
                     class="absolute inset-0 w-full h-full object-cover">
            </div>

            <div class="bg-white rounded-2xl border border-gold-200/60 shadow-sm p-6 sm:p-8">
                <div class="space-y-5 text-sm">
                    <div class="flex items-start gap-3">
                        <span class="text-xl shrink-0">📍</span>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-maroon-400">{{ __('Address') }}</p>
                            <p class="text-maroon-800 font-medium mt-0.5">{{ __('Roadways Bus Stand, Nichlaul Road, Siswa Bazar, Maharajganj, UP 273163') }}</p>
                            <a href="https://www.google.com/maps/place/Vinayak+family+shop/@27.1555715,83.7583867,116m/data=!3m1!1e3!4m15!1m8!3m7!1s0x399405f255a1917b:0x6a81af17a7f824bc!2sSiswa+Bazar,+Uttar+Pradesh+273163!3b1!8m2!3d27.1496918!4d83.7597505!16s%2Fg%2F11c1pcy3jv!3m5!1s0x399405c96c7bf3d1:0x2b1f6ee963c89b2d!8m2!3d27.155558!4d83.7584378!16s%2Fg%2F11fs1rllp9?entry=ttu&amp;g_ep=EgoyMDI2MDcxOS4wIKXMDSoASAFQAw%3D%3D"
                               target="_blank" rel="noopener" class="text-gold-600 hover:text-gold-700 font-semibold text-xs mt-1 inline-block">{{ __('Get Directions') }} →</a>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <span class="text-xl shrink-0">🕗</span>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-maroon-400">{{ __('Hours') }}</p>
                            <p class="text-maroon-800 font-medium mt-0.5">
                                {{ __('Open Daily') }} · 8:00 AM – 9:00 PM
                                @if ($outletIsOpen)
                                    <span class="ml-1.5 bg-pista-500 text-white text-[11px] font-semibold px-2.5 py-0.5 rounded-md align-middle">{{ __('Open Now') }}</span>
                                @else
                                    <span class="ml-1.5 bg-maroon-50 text-maroon-400 border border-maroon-200 text-[11px] font-semibold px-2.5 py-0.5 rounded-md align-middle">{{ __('Closed Now') }}</span>
                                @endif
                            </p>
                        </div>
                    </div>

                    @if ($businessPhone)
                        <div class="flex items-start gap-3">
                            <span class="text-xl shrink-0">📞</span>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-maroon-400">{{ __('Phone') }}</p>
                                <a href="{{ $businessPhone['tel'] }}" class="text-maroon-800 font-medium mt-0.5 hover:text-gold-600 transition inline-block">{{ $businessPhone['display'] }}</a>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <span class="text-xl shrink-0">💬</span>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-maroon-400">{{ __('WhatsApp') }}</p>
                                <a href="{{ $businessPhone['whatsapp'] }}" target="_blank" rel="noopener" class="text-maroon-800 font-medium mt-0.5 hover:text-gold-600 transition inline-block">{{ __('Chat with us on WhatsApp') }}</a>
                            </div>
                        </div>
                    @endif

                    <div class="flex items-start gap-3">
                        <span class="text-xl shrink-0">✉️</span>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-maroon-400">{{ __('Email') }}</p>
                            <a href="mailto:support@vinayakfamilyshop.com" class="text-maroon-800 font-medium mt-0.5 hover:text-gold-600 transition inline-block">support@vinayakfamilyshop.com</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
