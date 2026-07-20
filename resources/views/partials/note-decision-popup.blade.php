{{-- note-decision popup — opened via the open-note-decision window event, dispatched by
     orderTrackingPage().maybeShowNoteDecision() the moment its poll (or initial load) sees an
     admin decision on the customer's "Note for the Shop" it hasn't shown yet. Shown once per
     decision (localStorage-gated by the exact note_decided_at timestamp — see maybeShowNoteDecision
     in app.js); the persistent status chip in the note card (order-detail.blade.php) stays visible
     regardless, so the decision is never lost even after this popup is dismissed.
     Modeled directly on partials/rider-rating-popup.blade.php's glassmorphism shell — z-[150],
     same backdrop/card treatment, same enter/leave transitions — but single-step, no input. --}}
<div x-data="noteDecisionPopup()" x-show="open" x-cloak
     class="fixed inset-0 z-[150] flex items-center justify-center p-4"
     @keydown.escape.window="close()">
    <div x-show="open"
         class="absolute inset-0 bg-maroon-900/80 backdrop-blur-md"
         x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         @click="close()"></div>

    <div x-show="open"
         class="relative w-full max-w-sm bg-white/90 backdrop-blur-xl border border-white/40 shadow-2xl rounded-3xl overflow-hidden"
         x-transition:enter="transition ease-out duration-400" x-transition:enter-start="opacity-0 scale-90 translate-y-4" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">

        <button type="button" @click="close()" aria-label="{{ __('Close') }}"
                class="absolute top-4 right-4 z-10 w-8 h-8 rounded-full bg-white/70 hover:bg-white text-maroon-600 flex items-center justify-center shadow-sm transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </button>

        <div class="px-7 py-10 text-center">
            <template x-if="status === 'accepted'">
                <div>
                    <p class="text-5xl animate-track-pop">🎉</p>
                    <p class="font-display font-bold text-lg text-maroon-800 mt-3">{{ __('Your request was accepted!') }}</p>
                    <p class="text-sm text-maroon-600 mt-1.5 max-w-xs mx-auto leading-relaxed" x-text="message || @js(__('The shop will take care of it.'))"></p>
                </div>
            </template>
            <template x-if="status === 'denied'">
                <div>
                    <p class="text-5xl animate-droop">😔</p>
                    <p class="font-display font-bold text-lg text-maroon-800 mt-3">{{ __("We couldn't accommodate this one") }}</p>
                    <p class="text-sm text-maroon-600 mt-1.5 max-w-xs mx-auto leading-relaxed" x-text="message || @js(__('Sorry about that — thank you for understanding.'))"></p>
                </div>
            </template>
            <button type="button" @click="close()" class="btn-gold mt-6 px-8 py-2.5 text-sm">{{ __('Got it') }}</button>
        </div>
    </div>
</div>
