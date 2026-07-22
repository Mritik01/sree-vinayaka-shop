{{-- Slide-out cart drawer: opens automatically on "Add to Cart" (see addProductToCart in
     app.js) and from the navbar cart icon. State lives in Alpine.store('cart') so it stays
     in sync no matter which page/component last touched the cart. --}}
<div x-show="$store.cart.open" x-cloak class="fixed inset-0 z-[110]">
    <div x-show="$store.cart.open" x-cloak class="absolute inset-0 bg-maroon-900/50 backdrop-blur-sm"
         x-transition:enter="transition-opacity duration-[400ms] ease-[cubic-bezier(0.32,0.72,0,1)]" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity duration-[300ms] ease-[cubic-bezier(0.32,0.72,0,1)]" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         @click="$store.cart.open = false"></div>

    <div x-show="$store.cart.open" x-cloak class="absolute inset-y-0 right-0 w-full max-w-sm sm:max-w-md bg-white shadow-2xl flex flex-col will-change-transform"
         x-transition:enter="transition-transform duration-[400ms] ease-[cubic-bezier(0.32,0.72,0,1)]" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
         x-transition:leave="transition-transform duration-[300ms] ease-[cubic-bezier(0.32,0.72,0,1)]" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gold-200/60 shrink-0">
            <p class="font-display text-lg text-maroon-800">Shopping Cart</p>
            <button type="button" @click="$store.cart.open = false" aria-label="Close cart" class="text-maroon-400 hover:text-maroon-700 transition text-xl leading-none">✕</button>
        </div>

        {{-- empty state --}}
        <div x-show="$store.cart.items.length === 0" x-cloak class="flex-1 flex flex-col items-center justify-center text-center px-6">
            <p class="text-5xl mb-4">🛍️</p>
            <p class="text-maroon-700 font-display text-lg">Your cart is empty</p>
            <p class="text-maroon-500 mt-2 text-sm max-w-xs">Add a few items from Siswa Bazar's Favourites and they'll show up here.</p>
            <a href="/#bestsellers" @click="$store.cart.open = false" class="btn-gold inline-block mt-6">Explore Our Products</a>
        </div>

        {{-- item list --}}
        <div x-show="$store.cart.items.length > 0" class="flex-1 overflow-y-auto divide-y divide-gold-100">
            <template x-for="item in $store.cart.items" :key="item.id">
                <div class="flex items-start gap-3 px-5 py-4"
                     x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0 -translate-x-2">
                    <a :href="`/product/${item.slug}`" class="w-16 h-16 rounded-lg overflow-hidden shrink-0 border border-gold-200/60">
                        <img :src="item.image" :alt="item.name" class="w-full h-full object-cover" :class="item.is_out_of_stock ? 'grayscale opacity-60' : ''">
                    </a>

                    <div class="flex-1 min-w-0">
                        <a :href="`/product/${item.slug}`" class="font-display text-maroon-800 text-sm font-semibold hover:text-gold-600 transition truncate block" x-text="item.name"></a>
                        <template x-if="item.is_out_of_stock">
                            <span class="inline-block mt-0.5 text-[9px] font-bold uppercase tracking-wide px-1.5 py-0.5 rounded-full bg-red-50 text-red-600 border border-red-200">Out of Stock</span>
                        </template>
                        <p class="text-xs text-maroon-400 mt-0.5">Size: <span x-text="item.type === 'loose' ? portionLabel(item.portion) : item.weight"></span></p>
                        <p class="font-display font-semibold text-sm mt-1" :style="`color: ${item.color}`">₹<span x-text="item.price"></span></p>

                        <div class="flex items-center gap-3 mt-2.5">
                            <div class="flex items-center gap-2 bg-cream rounded-full border border-gold-300/60 px-2 py-0.5">
                                <button @click="$store.cart.decrement(item)" aria-label="Decrease quantity" class="w-5 h-5 rounded-full hover:bg-gold-100 text-maroon-700 font-bold transition text-sm">−</button>
                                <span class="w-5 text-center text-xs font-semibold text-maroon-800" x-text="item.quantity"></span>
                                <button @click="$store.cart.increment(item)" :disabled="item.is_out_of_stock" aria-label="Increase quantity" class="w-5 h-5 rounded-full hover:bg-gold-100 text-maroon-700 font-bold transition text-sm disabled:opacity-30 disabled:hover:bg-transparent disabled:cursor-not-allowed">+</button>
                            </div>
                            <button @click="$store.cart.remove(item)" class="text-xs font-medium text-maroon-400 hover:text-red-600 underline underline-offset-2 transition">Remove</button>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        {{-- footer: subtotal + actions --}}
        <div x-show="$store.cart.items.length > 0" class="border-t border-gold-200/60 px-5 py-4 shrink-0 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-maroon-700 font-medium">Subtotal</span>
                <span class="font-display font-semibold text-xl text-maroon-800">₹<span x-text="$store.cart.subtotal()"></span></span>
            </div>

            <template x-if="$store.shop.accepting && !$store.cart.hasOutOfStockItems()">
                <a href="/checkout" @click="$store.cart.open = false" class="btn-gold w-full text-center inline-flex items-center justify-center gap-2">Check Out</a>
            </template>
            <template x-if="!$store.shop.accepting">
                <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 text-xs px-4 py-3 text-center">
                    🚫 We're not accepting online orders right now — your cart will be waiting.
                </div>
            </template>
            <template x-if="$store.shop.accepting && $store.cart.hasOutOfStockItems()">
                <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 text-xs px-4 py-3 text-center">
                    🚫 Remove out-of-stock items to proceed to checkout.
                </div>
            </template>

            <a href="{{ route('cart.index') }}" @click="$store.cart.open = false" class="block text-center text-sm text-maroon-500 hover:text-gold-600 underline underline-offset-2 transition">View Cart</a>
        </div>
    </div>
</div>
