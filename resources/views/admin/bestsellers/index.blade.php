@extends('admin.layout')

@section('title', 'Bestsellers')
@section('page-title', 'Bestsellers')

@section('content')
    <p class="text-maroon-500 text-sm mb-5 max-w-2xl">
        Pick which products show up in <span class="font-semibold">Thuthibari's Favourites</span> on the homepage. Unchecked products stay in the catalog but won't appear in that section.
    </p>

    <form method="POST" action="{{ route('admin.bestsellers.update') }}" x-data="{ dirty: false, q: '' }" @change="dirty = true">
        @csrf

        <div class="flex flex-wrap items-center justify-between gap-3 mb-4 sticky top-0 z-10 bg-cream/95 backdrop-blur py-2 -mx-2 px-2">
            <div class="flex items-center gap-3 flex-wrap">
                <p class="text-sm text-maroon-500"><span class="font-semibold text-maroon-700">{{ $products->where('is_bestseller', true)->count() }}</span> of {{ $products->count() }} selected</p>
                {{-- live filter — client-side only, so unsaved checkbox changes are never lost --}}
                <div class="relative">
                    <input type="search" x-model="q" @change.stop @keydown.enter.prevent placeholder="Search products…"
                           class="w-48 sm:w-64 rounded-lg border border-gold-300/70 bg-white pl-9 pr-3 py-2 text-sm text-maroon-800 placeholder-maroon-400/60 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-maroon-300 text-sm pointer-events-none">🔎</span>
                </div>
            </div>
            <button type="submit" class="btn-gold" :class="!dirty && 'opacity-60'">Save Changes</button>
        </div>

        @if ($products->isEmpty())
            <div class="bg-white rounded-xl border border-gold-200/60 p-8 text-center text-maroon-400 text-sm">No products yet — add some from the Products page first.</div>
        @else
            @foreach ($products->groupBy('category') as $category => $group)
                <div class="mb-6" data-names="{{ strtolower($group->pluck('name')->join(' | ')) }}"
                     x-show="q.trim() === '' || $el.dataset.names.includes(q.trim().toLowerCase())">
                    <h3 class="font-display font-bold text-maroon-700 mb-2.5">{{ $category }}</h3>
                    <div class="bg-white rounded-xl border border-gold-200/60 divide-y divide-gold-50">
                        @foreach ($group as $product)
                            <label class="flex items-center gap-4 px-5 py-3 cursor-pointer hover:bg-cream/50 transition"
                                   data-name="{{ strtolower($product->name) }}"
                                   x-show="q.trim() === '' || $el.dataset.name.includes(q.trim().toLowerCase())">
                                <input type="checkbox" name="product_ids[]" value="{{ $product->id }}" @checked($product->is_bestseller)
                                       class="w-4.5 h-4.5 rounded border-gold-300 text-gold-600 focus:ring-gold-400">
                                <img src="{{ asset($product->image) }}" alt="{{ $product->name }}" class="w-10 h-10 rounded-lg object-cover border border-gold-200/60">
                                <div class="flex-1 min-w-0">
                                    <p class="text-maroon-800 font-medium truncate">{{ $product->name }}</p>
                                    <p class="text-maroon-400 text-xs">{{ $product->weight }}</p>
                                </div>
                                <span class="text-maroon-800 font-medium">₹{{ $product->price }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach
        @endif
    </form>
@endsection
