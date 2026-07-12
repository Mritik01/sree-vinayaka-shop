@extends('admin.layout')

@section('title', 'Coupons')
@section('page-title', 'Coupons')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div class="flex items-center gap-3 flex-wrap">
            <x-admin.per-page-select :current="request('per_page', 10)" />
            <x-admin.search-box :value="$search" target="coupons-results" placeholder="Search code or description…" />
        </div>
        <a href="{{ route('admin.coupons.create') }}" class="btn-gold">+ Add Coupon</a>
    </div>

    <div id="coupons-results" class="bg-white rounded-xl border border-gold-200/60 overflow-hidden">
        @include('admin.coupons._results')
    </div>
@endsection
