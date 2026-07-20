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
        <label class="block text-sm font-medium text-maroon-700 mb-1.5">Name</label>
        <input type="text" name="name" value="{{ old('name', $category->name ?? '') }}" required maxlength="60" placeholder="e.g. Snacks"
               class="w-full rounded-lg border border-gold-300/70 px-3.5 py-2.5 text-maroon-800 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
    </div>
    <div>
        <label class="block text-sm font-medium text-maroon-700 mb-1.5">Sort Order</label>
        <input type="number" name="sort_order" min="0" value="{{ old('sort_order', $category->sort_order ?? 0) }}" required
               class="w-full rounded-lg border border-gold-300/70 px-3.5 py-2.5 text-maroon-800 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
        <p class="text-xs text-maroon-400 mt-1">Lower numbers show first in the row.</p>
    </div>
</div>

<div class="mt-5 flex items-center gap-2.5">
    <input type="checkbox" name="is_active" id="is_active" value="1" @checked(old('is_active', $category->is_active ?? true))
           class="w-4 h-4 rounded border-gold-300 text-gold-500 focus:ring-gold-400">
    <label for="is_active" class="text-sm text-maroon-700">Active — shown in the homepage row</label>
</div>

{{-- product tags — same "checkbox list assigns a many-to-many taxonomy" pattern already used
     on the product form's Categories block. A product shows under this Featured Category if it
     carries ANY of the tags checked here. --}}
<div class="mt-6 border-t border-gold-200/60 pt-5">
    <label class="block text-sm font-medium text-maroon-700 mb-1.5">Product Tags</label>
    @if ($allTags->isEmpty())
        <p class="text-xs text-maroon-400">No tags exist yet — <a href="{{ route('admin.product-tags.create') }}" class="text-gold-600 underline">create one first</a>, then come back to map it here.</p>
    @else
        <p class="text-xs text-maroon-400 mb-2">Products carrying any of these tags appear under this Featured Category. <strong>Remember to also check the matching tag(s) on the products themselves</strong> (Products → edit a product → Product Tags) — a tag with no products checked keeps this tile hidden from the homepage.</p>
        <div class="flex flex-wrap gap-2">
            @foreach ($allTags as $tag)
                <label class="flex items-center gap-2 text-sm text-maroon-700 border border-gold-300/60 rounded-lg px-3 py-2 cursor-pointer hover:border-gold-500 transition">
                    <input type="checkbox" name="tags[]" value="{{ $tag->id }}"
                           @checked(in_array($tag->id, old('tags', isset($category) ? $category->tags->pluck('id')->all() : [])))
                           class="w-4 h-4 rounded border-gold-300 text-gold-500 focus:ring-gold-400">
                    {{ $tag->name }}
                </label>
            @endforeach
        </div>
    @endif
</div>

{{-- icon image: square crop via Cropper.js, PNG output (not re-encoded to JPEG) so the icon
     art's transparent background survives, matching the reference's flat-icon look --}}
<div class="mt-6 border-t border-gold-200/60 pt-5">
    <p class="text-sm font-medium text-maroon-700 mb-2">Icon Image <span class="text-maroon-400 font-normal">(PNG, ideally with a transparent background)</span></p>

    <div class="flex items-start gap-6 flex-wrap">
        <div>
            <template x-if="rawImageSrc">
                <div class="w-56 h-56 bg-maroon-900/5 rounded-xl overflow-hidden border border-gold-300/60">
                    <img x-ref="cropperImage" :src="rawImageSrc" class="block max-w-full" style="display: block; max-width: 100%;">
                </div>
            </template>
            <template x-if="!rawImageSrc">
                <div class="w-56 h-56 rounded-xl border-2 border-dashed border-gold-300/70 overflow-hidden flex items-center justify-center text-center text-sm text-maroon-400 px-4 bg-[repeating-conic-gradient(#f3ede0_0%_25%,white_0%_50%)] bg-[length:16px_16px]">
                    <img x-show="existingImageUrl" :src="existingImageUrl" class="w-full h-full object-contain">
                    <span x-show="!existingImageUrl">No icon yet — choose one below</span>
                </div>
            </template>

            <div class="flex items-center gap-2 mt-3" x-show="rawImageSrc" x-cloak>
                <button type="button" @click="zoom(0.1)" aria-label="Zoom in" class="w-8 h-8 rounded-full border border-gold-300/70 text-maroon-700 hover:bg-cream transition font-bold">+</button>
                <button type="button" @click="zoom(-0.1)" aria-label="Zoom out" class="w-8 h-8 rounded-full border border-gold-300/70 text-maroon-700 hover:bg-cream transition font-bold">−</button>
                <span class="text-xs text-maroon-400">Drag to reposition, use +/− to zoom</span>
            </div>
        </div>

        <div class="text-center shrink-0">
            <p class="text-xs font-semibold text-maroon-500 uppercase tracking-wide mb-2">Live Preview</p>
            <div class="w-24 h-24 rounded-xl bg-[repeating-conic-gradient(#f3ede0_0%_25%,white_0%_50%)] bg-[length:12px_12px] border-2 border-gold-400 shadow-sm flex items-center justify-center overflow-hidden">
                <img x-show="livePreview" :src="livePreview" class="w-full h-full object-contain">
                <span x-show="!livePreview" class="text-2xl">🧩</span>
            </div>
        </div>
    </div>

    <label class="inline-block mt-4 text-sm font-semibold text-maroon-700 border border-gold-300/70 hover:border-gold-500 hover:bg-cream rounded-lg px-4 py-2 cursor-pointer transition">
        🧩 Choose Icon
        <input type="file" accept="image/*" class="hidden" @change="onFileSelected($event)">
    </label>

    <input type="hidden" name="cropped_image" x-ref="croppedImageInput">
</div>

<div class="mt-7 flex items-center gap-3">
    <button type="submit" class="btn-gold">{{ isset($category) ? 'Save Changes' : 'Create Featured Category' }}</button>
    <a href="{{ route('admin.featured-categories.index') }}" class="text-maroon-500 hover:text-maroon-700 text-sm font-medium">Cancel</a>
</div>
