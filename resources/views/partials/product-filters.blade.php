{{-- Shared filter controls for /products — included once in the desktop sidebar and once in the
     mobile bottom sheet. Both bind to the same productListing() Alpine scope, so state stays in
     sync automatically. Expects: $categories, $priceBuckets. --}}
<div class="space-y-6">
    <div>
        <p class="text-xs font-semibold uppercase tracking-widest text-gold-600 mb-3">{{ __('Category') }}</p>
        <div class="space-y-2">
            @foreach ($categories as $cat)
                <label class="flex items-center gap-3 cursor-pointer group/f">
                    <input type="checkbox" value="{{ $cat }}" x-model="filters.categories"
                           class="w-4 h-4 rounded border-gold-300 text-maroon-600 focus:ring-gold-400 transition">
                    <span class="text-sm text-maroon-700 group-hover/f:text-maroon-900 transition flex-1">{{ __($cat) }}</span>
                    <span class="text-xs text-maroon-300" x-text="categoryCount('{{ $cat }}')"></span>
                </label>
            @endforeach
        </div>
    </div>

    <div class="border-t border-gold-200/60 pt-5">
        <p class="text-xs font-semibold uppercase tracking-widest text-gold-600 mb-3">{{ __('Price') }}</p>
        <div class="space-y-2">
            @foreach ($priceBuckets as $i => $bucket)
                <label class="flex items-center gap-3 cursor-pointer group/f">
                    <input type="checkbox" value="{{ $i }}" x-model="filters.prices"
                           class="w-4 h-4 rounded border-gold-300 text-maroon-600 focus:ring-gold-400 transition">
                    <span class="text-sm text-maroon-700 group-hover/f:text-maroon-900 transition">{{ $bucket['label'] }}</span>
                </label>
            @endforeach
        </div>
    </div>

    <div class="border-t border-gold-200/60 pt-5" x-show="activeCount() > 0" x-cloak
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
        <button type="button" @click="clearAll()"
                class="w-full text-sm font-semibold text-maroon-600 border border-maroon-300/60 hover:bg-maroon-50 rounded-xl py-2.5 transition">
            ✕ {{ __('Clear all filters') }} (<span x-text="activeCount()"></span>)
        </button>
    </div>
</div>
