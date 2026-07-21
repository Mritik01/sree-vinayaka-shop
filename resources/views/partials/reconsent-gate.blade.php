{{-- Only rendered when $needsReconsent is true (see layouts/app.blade.php) — a logged-in
     customer's last accepted Terms/Privacy version has fallen behind what's currently
     published. Deliberately non-dismissible (no close button, no backdrop click-to-close,
     no Escape) — "before continuing" per the spec, not just a reminder. --}}
<div x-data="reconsentGate()" class="fixed inset-0 z-[140] flex items-center justify-center p-4 bg-maroon-950/80 backdrop-blur-sm">
    <div class="relative w-full max-w-md bg-white rounded-3xl shadow-2xl p-6 sm:p-8 text-center"
         x-show="!accepted" x-transition:enter="transition duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
        <div class="mx-auto w-14 h-14 rounded-full bg-gradient-to-br from-gold-400 to-gold-600 flex items-center justify-center text-2xl shadow-md">📜</div>
        <h2 class="font-display font-bold text-xl text-maroon-800 mt-4">{{ __("We've updated our policies") }}</h2>
        <p class="text-maroon-500 text-sm mt-2">{{ __('Please review and accept the latest Terms & Conditions and Privacy Policy to continue using Shree Vinayak Family Shop.') }}</p>

        <div class="mt-5 text-left">
            <label class="flex items-start gap-2.5 cursor-pointer">
                <input type="checkbox" x-model="agreeTerms" required
                       class="mt-0.5 w-4 h-4 shrink-0 rounded border-gold-300 text-gold-600 focus:ring-gold-400 focus:ring-offset-0">
                <span class="text-xs text-maroon-500 leading-snug">
                    {{ __('I have read and agree to the') }}
                    <button type="button" @click.prevent="window.dispatchEvent(new CustomEvent('open-legal-modal', { detail: { type: 'terms' } }))"
                            class="text-maroon-700 font-medium underline hover:text-gold-600">{{ __('Terms & Conditions') }}</button>
                    {{ __('and') }}
                    <button type="button" @click.prevent="window.dispatchEvent(new CustomEvent('open-legal-modal', { detail: { type: 'privacy' } }))"
                            class="text-maroon-700 font-medium underline hover:text-gold-600">{{ __('Privacy Policy') }}</button>.
                </span>
            </label>
            <p x-show="error" x-cloak x-text="error" class="text-xs text-maroon-600 font-medium mt-2 text-center"></p>
        </div>

        <button type="button" @click="accept()" :disabled="loading || !agreeTerms"
                class="btn-gold w-full text-center mt-5 disabled:opacity-60">
            <span x-show="!loading">{{ __('Agree & Continue') }}</span>
            <span x-show="loading" x-cloak>{{ __('Just a moment...') }}</span>
        </button>
    </div>
</div>
