@extends('admin.layout')

@section('title', 'Shadi/Function Leads')
@section('page-title', 'Shadi/Function Leads')

@section('content')
    <p class="text-maroon-500 text-sm -mt-1 mb-4">
        Everyone who submitted their name and OTP-verified phone number through the <span class="font-hindi">"शादी हो या फंक्शन?"</span> popup — {{ $totalCount }} total.
    </p>

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div class="flex items-center gap-3 flex-wrap">
            <x-admin.per-page-select :current="request('per_page', 10)" />
            <x-admin.search-box :value="$search" target="leads-results" placeholder="Search name or phone…" />
        </div>
    </div>

    <div id="leads-results" class="bg-white rounded-xl border border-gold-200/60 overflow-hidden">
        @include('admin.leads._results')
    </div>
@endsection
