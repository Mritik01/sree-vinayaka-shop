@props(['value' => '', 'placeholder' => 'Search…', 'target'])
<div class="relative" x-data="liveGridSearch('{{ $target }}')" x-init="q = '{{ addslashes($value) }}'">
    <input type="search" x-model="q" @input="onInput()" @keydown.enter.prevent="search()"
           placeholder="{{ $placeholder }}"
           class="w-48 sm:w-64 rounded-lg border border-gold-300/70 bg-white pl-9 pr-8 py-2 text-sm text-maroon-800 placeholder-maroon-400/60 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-maroon-300 text-sm pointer-events-none">🔎</span>
    <svg x-show="loading" x-cloak class="absolute right-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gold-500 animate-spin pointer-events-none" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
    </svg>
</div>
