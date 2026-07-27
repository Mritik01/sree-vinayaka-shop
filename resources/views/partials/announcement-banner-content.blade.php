{{-- Shared markup for both the live site-wide popup (partials/announcement-banner.blade.php)
     and the admin live-preview pane (admin/announcement/edit.blade.php). Both callers must
     expose the same Alpine scope: headline, description, buttonText, buttonUrl, image, bg,
     text, showClose, dismiss() — so this partial never needs to know which one it's in.

     Image-dominant layout: the photo owns the top of the card (its own aspect-ratio box, not a
     cropped fixed height) with the close button floating on top of it, then a compact
     headline/description/CTA block below in the admin-configurable bg/text colors. --}}
<div class="relative flex flex-col">
    <button type="button" x-show="showClose" x-cloak @click="dismiss()" aria-label="{{ __('Close') }}"
            class="absolute top-3 right-3 z-10 w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-white/90 hover:bg-white shadow-md flex items-center justify-center text-maroon-700 transition">
        <svg class="w-4 h-4 sm:w-[18px] sm:h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
        </svg>
    </button>

    <template x-if="image">
        <div class="w-full aspect-[4/3] sm:aspect-[16/10] overflow-hidden shrink-0 bg-cream">
            <img :src="image" class="w-full h-full object-cover" alt="">
        </div>
    </template>

    <div class="relative px-5 sm:px-6 py-5 sm:py-6 text-center flex flex-col items-center gap-1.5" :style="`background:${bg}; color:${text};`">
        <p class="font-display font-bold text-xl sm:text-2xl leading-snug" x-text="headline || 'Your headline here'"></p>
        <div class="text-sm sm:text-[15px] opacity-90 leading-relaxed [&_a]:underline" x-html="description || ''"></div>

        <template x-if="buttonText">
            <a :href="buttonUrl || '#'" @click="dismiss()"
               class="inline-block mt-2.5 px-7 py-2.5 rounded-full font-semibold text-sm transition-all shadow-md bg-white/95 hover:bg-white hover:scale-[1.03] active:scale-[0.98]"
               :style="`color:${bg};`" x-text="buttonText"></a>
        </template>
    </div>
</div>
