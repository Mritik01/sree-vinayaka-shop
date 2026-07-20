{{-- site-wide location permission reminder — positioned by the fixed wrapper in
     layouts/app.blade.php (floats above the page rather than pushing content down, to avoid
     Cumulative Layout Shift). It exists purely to get permission granted early so checkout's own
     silent check (checkoutPage().checkDeliveryArea) never has to interrupt the customer there —
     see window.locationPrompt() in app.js. --}}
<div x-data="locationPrompt()"
     x-show="supported && permissionState !== 'granted' && !dismissed" x-cloak
     x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
     class="w-full bg-gold-50 border-b border-gold-300/60 text-maroon-800 shadow-md">
    <div class="max-w-[1760px] mx-auto px-4 sm:px-8 lg:px-12 py-2.5 flex flex-wrap items-center justify-center gap-x-3 gap-y-1.5 text-sm font-medium text-center">
        <template x-if="permissionState !== 'denied'">
            <span>📍 {{ __('Enable location so we can confirm delivery availability and charges for your address.') }}</span>
        </template>
        <template x-if="permissionState === 'denied'">
            <span>📍 {{ __('Location is blocked for this site — enable it in your browser\'s site settings to check delivery availability.') }}</span>
        </template>

        <span x-show="confirmation" x-cloak class="text-pista-700 font-semibold" x-text="confirmation"></span>

        <button type="button" x-show="permissionState !== 'denied'" x-cloak
                @click="requestPermission()" :disabled="checking"
                class="px-3 py-1 rounded-full bg-gold-500 text-maroon-900 font-semibold hover:bg-gold-600 transition text-xs disabled:opacity-60">
            <span x-show="!checking">{{ __('Enable Location') }}</span>
            <span x-show="checking" x-cloak>{{ __('Checking…') }}</span>
        </button>

        <button type="button" @click="dismissed = true" aria-label="{{ __('Dismiss') }}"
                class="text-maroon-400 hover:text-maroon-700 transition text-sm leading-none ml-1">✕</button>
    </div>
</div>
