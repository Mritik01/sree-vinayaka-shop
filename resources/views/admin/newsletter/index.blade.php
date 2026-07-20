@extends('admin.layout')

@section('title', 'Newsletter')
@section('page-title', 'Newsletter Subscribers')

@section('content')
    <p class="text-maroon-500 text-sm -mt-1 mb-4">
        Everyone who signed up through the "Join the Makhanbhog Parivaar" form on the homepage — {{ $totalCount }} total.
    </p>

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div class="flex items-center gap-3 flex-wrap">
            <x-admin.per-page-select :current="request('per_page', 10)" />
            <x-admin.search-box :value="$search" target="newsletter-results" placeholder="Search email…" />
        </div>
    </div>

    <div id="newsletter-results" class="bg-white rounded-xl border border-gold-200/60 overflow-hidden">
        @include('admin.newsletter._results')
    </div>
@endsection
