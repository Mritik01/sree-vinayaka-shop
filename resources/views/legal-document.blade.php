@extends('layouts.app')

{{-- @section('name', $value) already runs $value through e() internally (see Laravel's
     ManagesLayouts::startSection()) — do NOT escape here or in the layout's @yield/yieldContent,
     or entities double-encode (confirmed: & became &amp;amp; before this was caught). --}}
@section('title', $title.' — Makhanbhog Sweets')
@section('description', $metaDescription)

@section('content')
    <section class="max-w-3xl mx-auto px-4 sm:px-6 py-10 sm:py-14">
        <a href="{{ url('/') }}" class="inline-flex items-center gap-1.5 text-sm text-maroon-500 hover:text-maroon-700 transition mb-6">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
            {{ __('Back to Home') }}
        </a>

        <div class="bg-white rounded-2xl border border-gold-200/60 shadow-sm p-6 sm:p-10"
             x-data="{ title: @js($title), content: @js($content), updatedAt: @js($updatedAt) }">
            @include('partials.legal-document-content')
        </div>

        <p class="text-xs text-maroon-400 text-center mt-6">
            {{ __('Questions about this policy? Reach us at') }}
            <a href="tel:+918920937331" class="text-maroon-600 hover:text-gold-600 underline">+91 89209 37331</a>
            {{ __('or') }}
            <a href="https://wa.me/918920937331" target="_blank" rel="noopener" class="text-maroon-600 hover:text-gold-600 underline">WhatsApp</a>.
        </p>
    </section>
@endsection
