@extends('admin.layout')

@section('title', 'Products')
@section('page-title', 'Products')

@section('content')
    @php
        $stockTabs = ['' => 'All Products', 'in_stock' => 'In Stock', 'out_of_stock' => 'Out of Stock'];
    @endphp
    <div class="flex items-center gap-2 flex-wrap mb-3">
        @foreach ($stockTabs as $value => $label)
            <a href="{{ route('admin.products.index', array_merge(request()->query(), ['stock' => $value ?: null, 'page' => null])) }}"
               class="text-sm px-3.5 py-1.5 rounded-full border transition {{ $stockFilter === $value ? 'bg-maroon-700 text-cream border-maroon-700' : 'bg-white text-maroon-600 border-gold-200/60 hover:border-gold-400' }}">
                {{ $label }}{{ $value === 'out_of_stock' && $outOfStockCount > 0 ? " ({$outOfStockCount})" : '' }}
            </a>
        @endforeach
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div class="flex items-center gap-3 flex-wrap">
            <x-admin.per-page-select :current="request('per_page', 10)" />
            <x-admin.search-box :value="$search" target="products-results" placeholder="Search name, category, tag…" />
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.products.import') }}" class="inline-flex items-center gap-1.5 bg-pista-500 hover:bg-pista-600 text-white font-semibold rounded-lg px-3.5 py-1.5 text-sm transition">
                📊 {{ __('Bulk Upload') }}
            </a>
            <a href="{{ route('admin.products.create') }}" class="btn-gold">+ Add Product</a>
        </div>
    </div>

    <div id="products-results" class="bg-white rounded-xl border border-gold-200/60 overflow-hidden">
        @include('admin.products._results')
    </div>
@endsection
