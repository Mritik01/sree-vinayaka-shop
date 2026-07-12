@extends('admin.layout')

@section('title', 'Products')
@section('page-title', 'Products')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div class="flex items-center gap-3 flex-wrap">
            <x-admin.per-page-select :current="request('per_page', 10)" />
            <x-admin.search-box :value="$search" target="products-results" placeholder="Search name, category, tag…" />
        </div>
        <a href="{{ route('admin.products.create') }}" class="btn-gold">+ Add Product</a>
    </div>

    <div id="products-results" class="bg-white rounded-xl border border-gold-200/60 overflow-hidden">
        @include('admin.products._results')
    </div>
@endsection
