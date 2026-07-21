@if ($errors->any())
    <div class="mb-5 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3">
        <ul class="list-disc list-inside space-y-0.5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="grid md:grid-cols-2 gap-5">
    <div>
        <label class="block text-sm font-medium text-maroon-700 mb-1.5">Eyebrow <span class="text-maroon-400 font-normal">(small gold line above the title, optional)</span></label>
        <input type="text" name="eyebrow" value="{{ old('eyebrow', $banner->eyebrow ?? '') }}" maxlength="100" placeholder="e.g. Loved By Siswa Bazar"
               class="w-full rounded-lg border border-gold-300/70 px-3.5 py-2.5 text-maroon-800 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
    </div>
    <div>
        <label class="block text-sm font-medium text-maroon-700 mb-1.5">Sort Order</label>
        <input type="number" name="sort_order" min="0" value="{{ old('sort_order', $banner->sort_order ?? 0) }}" required
               class="w-full rounded-lg border border-gold-300/70 px-3.5 py-2.5 text-maroon-800 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
        <p class="text-xs text-maroon-400 mt-1">Lower numbers show first in the rotation.</p>
    </div>
</div>

<div class="mt-5">
    <label class="block text-sm font-medium text-maroon-700 mb-1.5">Title <span class="text-maroon-400 font-normal">(optional — leave blank if your banner image already has text/branding baked in)</span></label>
    <input type="text" name="title" value="{{ old('title', $banner->title ?? '') }}" maxlength="150" placeholder="e.g. The Sweet Box Everyone's Taking Home"
           class="w-full rounded-lg border border-gold-300/70 px-3.5 py-2.5 text-maroon-800 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition"
           @input="overlayTitle = $event.target.value">
</div>

<div class="mt-5">
    <label class="block text-sm font-medium text-maroon-700 mb-1.5">Subtitle <span class="text-maroon-400 font-normal">(optional)</span></label>
    <input type="text" name="subtitle" value="{{ old('subtitle', $banner->subtitle ?? '') }}" maxlength="200" placeholder="e.g. Perfect for gifting, celebrating, or simply treating yourself."
           class="w-full rounded-lg border border-gold-300/70 px-3.5 py-2.5 text-maroon-800 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
</div>

<div class="grid md:grid-cols-2 gap-5 mt-5">
    <div>
        <label class="block text-sm font-medium text-maroon-700 mb-1.5">Button Text <span class="text-maroon-400 font-normal">(optional)</span></label>
        <input type="text" name="button_text" value="{{ old('button_text', $banner->button_text ?? '') }}" maxlength="60" placeholder="e.g. Order Now — Cash on Delivery"
               class="w-full rounded-lg border border-gold-300/70 px-3.5 py-2.5 text-maroon-800 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
    </div>
    <div>
        <label class="block text-sm font-medium text-maroon-700 mb-1.5">Button Link</label>
        <input type="text" name="button_url" value="{{ old('button_url', $banner->button_url ?? '#bestsellers') }}" maxlength="300" placeholder="#bestsellers, /products or https://…"
               class="w-full rounded-lg border border-gold-300/70 px-3.5 py-2.5 text-maroon-800 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
    </div>
</div>

<div class="mt-5 flex items-center gap-2.5">
    <input type="checkbox" name="is_active" id="is_active" value="1" @checked(old('is_active', $banner->is_active ?? true))
           class="w-4 h-4 rounded border-gold-300 text-gold-500 focus:ring-gold-400">
    <label for="is_active" class="text-sm text-maroon-700">Active — shown in the homepage rotation</label>
</div>

{{-- banner image: wide crop via Cropper.js, with live desktop + mobile frame previews so the
     admin sees exactly how the slide will look at both sizes before saving --}}
<div class="mt-6 border-t border-gold-200/60 pt-5">
    <p class="text-sm font-medium text-maroon-700 mb-1">Banner Image</p>
    <p class="text-xs text-maroon-400 mb-2">Recommended size: 1920 × 823px (21:9, wide banner shape) — anything smaller than 1680 × 720px may look soft once cropped. JPG or PNG.</p>

    <div>
        <template x-if="rawImageSrc">
            <div class="w-full max-w-2xl bg-maroon-900/5 rounded-xl overflow-hidden border border-gold-300/60">
                <img x-ref="cropperImage" :src="rawImageSrc" class="block max-w-full" style="display: block; max-width: 100%;">
            </div>
        </template>
        <template x-if="!rawImageSrc">
            <div class="w-full max-w-2xl aspect-[21/9] rounded-xl border-2 border-dashed border-gold-300/70 overflow-hidden flex items-center justify-center text-center text-sm text-maroon-400 px-4">
                <img x-show="existingImageUrl" :src="existingImageUrl" class="w-full h-full object-cover">
                <span x-show="!existingImageUrl">No image yet — choose one below</span>
            </div>
        </template>

        <div class="flex items-center gap-2 mt-3" x-show="rawImageSrc" x-cloak>
            <button type="button" @click="zoom(0.1)" aria-label="Zoom in" class="w-8 h-8 rounded-full border border-gold-300/70 text-maroon-700 hover:bg-cream transition font-bold">+</button>
            <button type="button" @click="zoom(-0.1)" aria-label="Zoom out" class="w-8 h-8 rounded-full border border-gold-300/70 text-maroon-700 hover:bg-cream transition font-bold">−</button>
            <span class="text-xs text-maroon-400">Drag to reposition, use +/− to zoom — the crop is locked to the banner shape</span>
        </div>
    </div>

    <div class="mt-5 grid sm:grid-cols-[2fr,1fr] gap-5 max-w-2xl" x-show="livePreview" x-cloak>
        <div>
            <p class="text-xs font-semibold text-maroon-500 uppercase tracking-wide mb-2">Desktop Preview</p>
            <div class="relative rounded-xl overflow-hidden border border-gold-300/60 aspect-[21/9] bg-maroon-900">
                <img :src="livePreview" class="absolute inset-0 w-full h-full object-cover">
                <template x-if="overlayTitle">
                    <div>
                        <div class="absolute inset-0 bg-gradient-to-r from-maroon-900/85 via-maroon-900/45 to-transparent"></div>
                        <div class="absolute inset-0 flex items-center px-4">
                            <p class="text-cream font-bold text-sm leading-snug drop-shadow max-w-[60%]" x-text="overlayTitle"></p>
                        </div>
                    </div>
                </template>
            </div>
        </div>
        <div>
            <p class="text-xs font-semibold text-maroon-500 uppercase tracking-wide mb-2">Mobile Preview</p>
            <div class="relative rounded-xl overflow-hidden border border-gold-300/60 aspect-[4/3] bg-maroon-900 max-w-[180px]">
                <img :src="livePreview" class="absolute inset-0 w-full h-full object-cover">
                <template x-if="overlayTitle">
                    <div>
                        <div class="absolute inset-0 bg-gradient-to-r from-maroon-900/85 via-maroon-900/45 to-transparent"></div>
                        <div class="absolute inset-0 flex items-center px-3">
                            <p class="text-cream font-bold text-[11px] leading-snug drop-shadow max-w-[75%]" x-text="overlayTitle"></p>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <label class="inline-block mt-4 text-sm font-semibold text-maroon-700 border border-gold-300/70 hover:border-gold-500 hover:bg-cream rounded-lg px-4 py-2 cursor-pointer transition">
        🖼️ Choose Image
        <input type="file" accept="image/*" class="hidden" @change="onFileSelected($event)">
    </label>

    <input type="hidden" name="cropped_image" x-ref="croppedImageInput">
</div>

<div class="mt-7 flex items-center gap-3">
    <button type="submit" class="btn-gold">{{ isset($banner) ? 'Save Changes' : 'Create Banner' }}</button>
    <a href="{{ route('admin.hero-banners.index') }}" class="text-maroon-500 hover:text-maroon-700 text-sm font-medium">Cancel</a>
</div>
