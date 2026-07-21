{{-- collapsible "Product Details" panel built from the product's admin-entered description.
     Included twice from product-show.blade.php with different wrapper visibility classes: once
     just under the photo (desktop only), once in place of the trust-badges strip (mobile only)
     — never both at once, so each instance's independent x-data open/close state is fine. --}}
<div x-data="{ open: false }" class="border-t border-b border-gold-200/60">
    <button type="button" @click="open = !open" :aria-expanded="open"
            class="w-full flex items-center justify-between gap-3 py-4 text-left">
        <span class="font-display font-semibold text-maroon-800">{{ __('Product Details') }}</span>
        <svg class="w-5 h-5 text-maroon-500 transition-transform duration-200 shrink-0" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
        </svg>
    </button>
    <div x-show="open" x-cloak
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
         class="pb-5 text-sm text-maroon-600/90 leading-relaxed">
        {{ $description }}
    </div>
</div>
