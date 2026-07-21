{{-- PWA "Install App" reminder — positioned by the fixed wrapper in layouts/app.blade.php
     (floats above the page rather than pushing content down, to avoid Cumulative Layout Shift;
     this is a top overlay, so it's independent of the mobile bottom nav / floating View Cart pill
     down at the bottom of the screen). Only ever appears when the browser actually fires
     beforeinstallprompt (Chrome/Edge/Android) — see window.installPrompt() in app.js. Offered once
     per browser session via sessionStorage, not indefinitely dismissed. --}}
<div x-data="installPrompt()"
     x-show="visible" x-cloak
     x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
     class="w-full bg-gold-50 border-b border-gold-300/60 text-maroon-800 shadow-md">
    <div class="max-w-[1760px] mx-auto px-4 sm:px-8 lg:px-12 py-2.5 flex flex-wrap items-center justify-center gap-x-3 gap-y-1.5 text-sm font-medium text-center">
        <span>📲 {{ __('Install Shree Vinayak Family Shop for faster ordering next time.') }}</span>

        <button type="button" @click="install()"
                class="px-3 py-1 rounded-full bg-gold-500 text-maroon-900 font-semibold hover:bg-gold-600 transition text-xs">
            {{ __('Install App') }}
        </button>

        <button type="button" @click="dismiss()" aria-label="{{ __('Dismiss') }}"
                class="text-maroon-400 hover:text-maroon-700 transition text-sm leading-none ml-1">✕</button>
    </div>
</div>
