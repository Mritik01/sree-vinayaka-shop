{{-- live product search — used twice by the navbar (desktop center + mobile full-width row).
     headerSearch() lives in app.js: debounced fetch to /search-suggest, Enter → Shop All --}}
<div x-data="headerSearch()" @click.outside="open = false" class="relative w-full">
    <form @submit.prevent="goToResults()" role="search">
        <div class="relative">
            <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-maroon-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
            </svg>
            <input type="search" x-model="q" @input.debounce.250ms="suggest()" @focus="q.length >= 2 && (open = true)"
                   placeholder="{{ __('Search for sweets, snacks, namkeen...') }}" autocomplete="off"
                   class="w-full rounded-full bg-white border border-gold-300/60 text-maroon-900 placeholder-maroon-400/60 pl-10 pr-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition shadow-sm">
        </div>
    </form>

    <div x-show="open && results.length > 0" x-cloak
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 -translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="absolute left-0 right-0 top-full mt-2 rounded-2xl bg-white border border-gold-200/70 shadow-2xl overflow-hidden z-[70]">
        <template x-for="p in results" :key="p.url">
            <a :href="p.url" class="flex items-center gap-3 px-4 py-2.5 hover:bg-cream/70 transition">
                <img :src="p.image" :alt="p.name" class="w-10 h-10 rounded-lg object-cover border border-gold-100 shrink-0">
                <span class="min-w-0 flex-1">
                    <span class="block text-sm font-medium text-maroon-800 truncate" x-text="p.name"></span>
                    <span class="block text-xs text-maroon-400" x-text="p.weight"></span>
                </span>
                <span class="text-sm font-bold text-maroon-700 shrink-0" x-text="'₹' + p.price.toLocaleString('en-IN')"></span>
            </a>
        </template>
        <button type="button" @click="goToResults()"
                class="w-full text-center text-xs font-semibold text-gold-600 hover:text-gold-700 hover:bg-cream/70 py-2.5 border-t border-gold-100 transition">
            {{ __('See all results') }} →
        </button>
    </div>

    <div x-show="open && q.length >= 2 && results.length === 0 && !loading" x-cloak
         class="absolute left-0 right-0 top-full mt-2 rounded-2xl bg-white border border-gold-200/70 shadow-2xl z-[70] px-4 py-4 text-center text-sm text-maroon-400">
        {{ __('No sweets match that — try another name') }} 🍬
    </div>
</div>
