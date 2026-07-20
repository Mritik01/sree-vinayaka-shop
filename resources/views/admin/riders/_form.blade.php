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
        <input type="text" name="name" value="{{ old('name', $rider->name ?? '') }}" required
               class="w-full rounded-lg border border-gold-300/70 px-3.5 py-2.5 text-maroon-800 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
    </div>
    <div>
        <label class="block text-sm font-medium text-maroon-700 mb-1.5">Phone <span class="text-maroon-400 font-normal">(optional)</span></label>
        <input type="text" name="phone" value="{{ old('phone', $rider->phone ?? '') }}"
               class="w-full rounded-lg border border-gold-300/70 px-3.5 py-2.5 text-maroon-800 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
    </div>
    <div>
        <label class="block text-sm font-medium text-maroon-700 mb-1.5">Username</label>
        <input type="text" name="username" value="{{ old('username', $rider->username ?? '') }}" required
               class="w-full rounded-lg border border-gold-300/70 px-3.5 py-2.5 text-maroon-800 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
    </div>
    <div>
        <label class="block text-sm font-medium text-maroon-700 mb-1.5">
            Password @if(isset($rider)) <span class="text-maroon-400 font-normal">(leave blank to keep current)</span> @endif
        </label>
        <input type="password" name="password" {{ isset($rider) ? '' : 'required' }}
               class="w-full rounded-lg border border-gold-300/70 px-3.5 py-2.5 text-maroon-800 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
    </div>
</div>

{{-- profile photo: crop/zoom/reposition via Cropper.js, live circular preview alongside —
     same pattern as the Category photo uploader. Shown to the customer on the order-tracking
     page once this rider is assigned to their order; optional, falls back to an initials circle
     when absent. --}}
<div class="mt-6 border-t border-gold-200/60 pt-5">
    <p class="text-sm font-medium text-maroon-700 mb-2">Profile Photo <span class="text-maroon-400 font-normal">(optional)</span></p>

    <div class="flex items-start gap-6 flex-wrap">
        <div>
            <template x-if="rawImageSrc">
                <div class="w-64 h-64 bg-maroon-900/5 rounded-xl overflow-hidden border border-gold-300/60">
                    <img x-ref="cropperImage" :src="rawImageSrc" class="block max-w-full" style="display: block; max-width: 100%;">
                </div>
            </template>
            <template x-if="!rawImageSrc">
                <div class="w-64 h-64 rounded-xl border-2 border-dashed border-gold-300/70 overflow-hidden flex items-center justify-center text-center text-sm text-maroon-400 px-4">
                    <img x-show="existingImageUrl" :src="existingImageUrl" class="w-full h-full object-cover">
                    <span x-show="!existingImageUrl">No photo yet — choose one below</span>
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
            <img x-show="livePreview" :src="livePreview" class="w-24 h-24 rounded-full object-cover border-2 border-gold-400 shadow-sm">
            <span x-show="!livePreview" class="w-24 h-24 rounded-full bg-gold-100 border-2 border-gold-300 flex items-center justify-center text-2xl">🛵</span>
        </div>
    </div>

    <label class="inline-block mt-4 text-sm font-semibold text-maroon-700 border border-gold-300/70 hover:border-gold-500 hover:bg-cream rounded-lg px-4 py-2 cursor-pointer transition">
        📷 Choose Photo
        <input type="file" accept="image/*" class="hidden" @change="onFileSelected($event)">
    </label>

    <input type="hidden" name="cropped_image" x-ref="croppedImageInput">
</div>

<div class="mt-7 flex items-center gap-3">
    <button type="submit" class="btn-gold">{{ isset($rider) ? 'Save Changes' : 'Add Rider' }}</button>
    <a href="{{ route('admin.riders.index') }}" class="text-maroon-500 hover:text-maroon-700 text-sm font-medium">Cancel</a>
</div>
