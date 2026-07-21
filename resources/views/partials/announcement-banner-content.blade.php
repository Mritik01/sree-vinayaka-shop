{{-- Shared markup for both the live site-wide popup (partials/announcement-banner.blade.php)
     and the admin live-preview pane (admin/announcement/edit.blade.php). Both callers must
     expose the same Alpine scope: headline, description, buttonText, buttonUrl, image, bg,
     text, showClose, dismiss() — so this partial never needs to know which one it's in. --}}
<div class="relative" :style="`background:${bg}; color:${text};`">
    <button type="button" x-show="showClose" x-cloak @click="dismiss()" aria-label="Close"
            class="absolute top-3 right-3 z-10 w-8 h-8 rounded-full bg-black/20 hover:bg-black/30 flex items-center justify-center transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
        </svg>
    </button>

    <template x-if="image">
        <div class="h-60 sm:h-72 overflow-hidden">
            <img :src="image" class="w-full h-full object-cover" alt="">
        </div>
    </template>

    <div class="p-5 sm:p-6 text-center">
        <p class="font-display font-bold text-xl sm:text-2xl leading-snug" x-text="headline || 'Your headline here'"></p>
        <div class="mt-1.5 text-sm sm:text-[15px] opacity-90 leading-relaxed [&_a]:underline" x-html="description || ''"></div>

        <template x-if="buttonText">
            <a :href="buttonUrl || '#'" @click="dismiss()"
               class="inline-block mt-4 px-6 py-2.5 rounded-full font-semibold text-sm transition shadow-md bg-white/95 hover:bg-white"
               :style="`color:${bg};`" x-text="buttonText"></a>
        </template>
    </div>
</div>
