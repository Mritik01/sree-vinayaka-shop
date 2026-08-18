{{-- iOS-only counterpart to install-prompt-banner.blade.php. Apple never fires
     beforeinstallprompt on any iOS browser, so unlike the Chrome/Edge/Android banner there's no
     "Install App" button here that can trigger anything programmatically — this only shows plain
     instructions for the manual Share -> "Add to Home Screen" flow. See window.iosInstallPrompt()
     in app.js; the two banners are mutually exclusive in practice (one only ever fires on iOS,
     the other never does) but both are included so neither browser family is left without a nudge. --}}
<div x-data="iosInstallPrompt()"
     x-show="visible" x-cloak
     x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
     class="w-full bg-gold-50 border-b border-gold-300/60 text-maroon-800 shadow-md">
    <div class="max-w-[1760px] mx-auto px-4 sm:px-8 lg:px-12 py-2.5 flex flex-wrap items-center justify-center gap-x-3 gap-y-1.5 text-sm font-medium text-center">
        <span>📲 {{ __('Install this app: tap the Share button in Safari, then "Add to Home Screen".') }}</span>

        <button type="button" @click="dismiss()" aria-label="{{ __('Dismiss') }}"
                class="text-maroon-400 hover:text-maroon-700 transition text-sm leading-none ml-1">✕</button>
    </div>
</div>
