@extends('admin.layout')

@section('title', 'Product Tags')
@section('page-title', 'Product Tags')

@section('content')
    <p class="text-sm text-maroon-500 mb-4 max-w-2xl">Tags are the plain lookup labels you assign to products (in the product form) and map to Featured Categories (in Featured Categories) — a product can carry several, and a Featured Category shows every product carrying any of its mapped tags.</p>

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div class="flex items-center gap-3 flex-wrap">
            <x-admin.per-page-select :current="request('per_page', 10)" />
            <x-admin.search-box :value="$search" target="product-tags-results" placeholder="Search tag name…" />
        </div>
        <a href="{{ route('admin.product-tags.create') }}" class="btn-gold">+ Add Tag</a>
    </div>

    <div id="product-tags-results" class="bg-white rounded-xl border border-gold-200/60 overflow-hidden">
        @include('admin.product-tags._results')
    </div>
@endsection
