{{-- Site-wide Terms/Privacy popup — opened from the signup checkbox (and anywhere else that
     dispatches `open-legal-modal`) via window.legalDocumentModal(). Content is embedded once
     in the layout (window.__mbLegalDocs, see layouts/app.blade.php) so this opens instantly,
     no fetch/loading state. Renders partials/legal-document-content.blade.php — the exact
     same markup the admin live-preview and the real /terms, /privacy pages use. --}}
<div x-data="legalDocumentModal()" x-show="open" x-cloak
     @keydown.escape.window="open && close()"
     class="fixed inset-0 z-[145] flex items-center justify-center p-0 sm:p-4">
    <div class="absolute inset-0 bg-maroon-900/50 backdrop-blur-sm"
         x-transition:enter="transition-opacity duration-300 ease-[cubic-bezier(0.32,0.72,0,1)]" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity duration-200 ease-[cubic-bezier(0.32,0.72,0,1)]" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         @click="close()"></div>

    <div class="relative w-full sm:max-w-2xl h-full sm:h-auto sm:max-h-[85vh] bg-white sm:rounded-3xl shadow-2xl flex flex-col overflow-hidden"
         x-transition:enter="transition duration-300 ease-[cubic-bezier(0.32,0.72,0,1)]" x-transition:enter-start="opacity-0 sm:scale-95 sm:translate-y-4" x-transition:enter-end="opacity-100 sm:scale-100 sm:translate-y-0"
         x-transition:leave="transition duration-200 ease-[cubic-bezier(0.32,0.72,0,1)]" x-transition:leave-start="opacity-100 sm:scale-100" x-transition:leave-end="opacity-0 sm:scale-95">

        {{-- sticky title bar --}}
        <div class="sticky top-0 z-10 shrink-0 bg-white border-b border-gold-200/60 px-5 sm:px-7 py-4 flex items-center justify-between gap-3">
            <p class="font-display font-bold text-lg text-maroon-800 truncate" x-text="title"></p>
            <button type="button" @click="close()" aria-label="{{ __('Close') }}"
                    class="shrink-0 w-9 h-9 grid place-items-center rounded-full text-maroon-400 hover:text-maroon-700 hover:bg-cream transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- scrollable content --}}
        <div class="flex-1 overflow-y-auto overscroll-contain px-5 sm:px-7 py-6">
            <div x-show="content" class="[&_h1]:hidden">
                @include('partials.legal-document-content')
            </div>
            <p x-show="!content" x-cloak class="text-sm text-maroon-400 text-center py-10">{{ __('This page is not available right now.') }}</p>
        </div>

        <div class="shrink-0 border-t border-gold-100 px-5 sm:px-7 py-3.5 flex justify-end">
            <button type="button" @click="close()" class="btn-gold text-sm px-6 py-2">{{ __('Close') }}</button>
        </div>
    </div>
</div>
