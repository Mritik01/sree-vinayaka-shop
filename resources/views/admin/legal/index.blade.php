@extends('admin.layout')

@section('title', 'Legal Pages')
@section('page-title', 'Legal Pages')

@section('content')
    <p class="text-maroon-500 text-sm -mt-1 mb-5">
        Manage the Terms &amp; Conditions and Privacy Policy shown on the public site. Every save creates a new version — customers who already agreed to an older version will be prompted to accept the update the next time they visit.
    </p>

    <div class="grid sm:grid-cols-2 gap-5 max-w-3xl">
        @foreach ($documents as $doc)
            <a href="{{ route('admin.legal.edit', $doc['type']) }}"
               class="block bg-white rounded-2xl border border-gold-200/60 p-6 hover:border-gold-400 hover:shadow-md transition">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">{{ $doc['type'] === 'terms' ? '📜' : '🔒' }}</span>
                    <p class="font-display text-lg text-maroon-800">{{ $doc['label'] }}</p>
                </div>
                @if ($doc['current'])
                    <p class="text-sm text-maroon-500 mt-3">Version <span class="font-semibold text-maroon-700">{{ $doc['current']->version }}</span></p>
                    <p class="text-xs text-maroon-400 mt-1">Last updated {{ $doc['current']->published_at->format('d M Y, h:i A') }}</p>
                @else
                    <p class="text-sm text-gold-600 font-medium mt-3">Not yet published</p>
                @endif
                <span class="inline-flex items-center gap-1 text-sm font-semibold text-maroon-700 mt-4">
                    Edit <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17.25 8.25 21 12m0 0-3.75 3.75M21 12H3"/></svg>
                </span>
            </a>
        @endforeach
    </div>
@endsection
