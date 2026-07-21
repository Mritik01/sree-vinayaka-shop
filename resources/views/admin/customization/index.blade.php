@extends('admin.layout')

@section('title', 'Application Customization')
@section('page-title', 'Application Customization')

@section('content')
    {{-- ── business logo ─────────────────────────────────────────────── --}}
    <div x-data="logoUploadCard(@js($settings->businessLogoUrl()), {{ $errors->has('logo') ? 'true' : 'false' }})"
         class="rounded-2xl border border-gold-200/60 bg-white p-5 mb-5">
        <div class="flex items-center gap-3">
            <span class="text-2xl">🖼️</span>
            <div>
                <p class="font-display text-maroon-800">Business Logo</p>
                <p class="text-sm text-maroon-500 mt-0.5">Used in the header, footer, login, order tracking, and invoices across the customer-facing site.</p>
            </div>
        </div>

        <div class="flex items-center gap-4 mt-4">
            <div class="w-20 h-20 shrink-0 rounded-xl overflow-hidden bg-cream border border-gold-200/60 grid place-items-center">
                <img src="{{ $settings->businessLogoUrl() }}" alt="Current business logo" class="w-full h-full object-contain p-1.5">
            </div>
            <div class="flex items-center gap-2">
                <button type="button" @click="openModal()" class="btn-gold text-sm px-5 py-2">⬆️ Upload New Logo</button>
                @if ($settings->business_logo_path)
                    <form method="POST" action="{{ route('admin.customization.logo.destroy') }}"
                          onsubmit="return confirm('Remove the custom logo? The default logo will be shown again.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-4 py-2 rounded-xl border border-red-300 text-red-600 hover:bg-red-50 text-sm font-semibold transition">🗑️ Remove</button>
                    </form>
                @endif
            </div>
        </div>

        {{-- upload modal — file picker branches into two paths client-side: raster (PNG/JPG/JPEG)
             mounts Cropper.js for a square crop, SVG skips straight to a plain preview since
             vector art can't be cropped/decoded by Cropper.js or GD — see logoUploadCard() and
             Admin\CustomizationController::updateLogo() (raster is always re-encoded PNG so
             transparency survives; SVG is validated as text and stored as-is). --}}
        <div x-show="open" x-cloak class="fixed inset-0 z-[90] flex items-center justify-center p-4">
            <div x-show="open" class="absolute inset-0 bg-maroon-900/60 backdrop-blur-sm"
                 x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 @click="closeModal()"></div>

            <div x-show="open" class="relative bg-cream rounded-2xl shadow-2xl w-full max-w-md overflow-hidden max-h-[90vh] flex flex-col"
                 x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95 translate-y-4" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
                <div class="relative px-6 py-5 bg-gradient-to-r from-gold-500 to-gold-600 overflow-hidden shrink-0">
                    <div class="absolute inset-0 opacity-25" style="background-image: radial-gradient(circle, white 1.5px, transparent 1.5px); background-size: 16px 16px;"></div>
                    <p class="relative font-display font-bold text-lg text-maroon-900">🖼️ Upload Business Logo</p>
                    <p class="relative text-maroon-900/70 text-xs mt-0.5">PNG, JPG, JPEG, or SVG — 512×512px recommended, max 2MB.</p>
                </div>

                <div class="p-6 overflow-y-auto space-y-4">
                    @error('logo')
                        <p class="text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2">{{ $message }}</p>
                    @enderror
                    <p x-show="fileError" x-cloak class="text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2" x-text="fileError"></p>

                    <form method="POST" action="{{ route('admin.customization.logo.update') }}" enctype="multipart/form-data"
                          @submit="beforeSubmit()">
                        @csrf
                        <input type="hidden" name="cropped_image" x-ref="croppedImageInput">

                        <div x-show="!rawImageSrc" x-cloak>
                            <label class="flex flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed border-gold-300 bg-white px-4 py-8 cursor-pointer hover:bg-gold-50 transition">
                                <span class="text-3xl">📁</span>
                                <span class="text-sm font-semibold text-maroon-700">Choose an image</span>
                                <span class="text-xs text-maroon-400">PNG, JPG, JPEG, or SVG — max 2MB</span>
                                {{-- name is only set for an actual SVG selection — a raster pick goes
                                     through the hidden cropped_image field instead (see
                                     beforeSubmit()), so an unnamed input here is correctly excluded
                                     from the submission rather than uploading the raw uncropped file too --}}
                                <input type="file" :name="isSvg ? 'svg_file' : ''" accept="image/png,image/jpeg,image/jpg,image/svg+xml"
                                       class="hidden" @change="onFileSelected($event)">
                            </label>
                        </div>

                        {{-- raster crop UI — hidden entirely for SVG, which has nothing to crop --}}
                        <div x-show="rawImageSrc && !isSvg" x-cloak class="space-y-3">
                            <div class="relative h-56 bg-maroon-900/5 rounded-xl overflow-hidden">
                                <img x-show="rawImageSrc" :src="rawImageSrc" x-ref="cropperImage" class="block max-w-full">
                            </div>
                            <div class="flex items-center justify-center gap-2">
                                <button type="button" @click="zoom(0.1)" class="w-8 h-8 rounded-lg border border-gold-300 text-maroon-700 hover:bg-gold-50 transition">+</button>
                                <button type="button" @click="zoom(-0.1)" class="w-8 h-8 rounded-lg border border-gold-300 text-maroon-700 hover:bg-gold-50 transition">−</button>
                                <span class="text-xs text-maroon-400 ml-2">Drag to reposition, buttons to zoom</span>
                            </div>
                        </div>

                        {{-- live preview — square, rounded, matching how the logo renders everywhere --}}
                        <div x-show="rawImageSrc || livePreview !== existingLogoUrl || isSvg" class="flex items-center justify-center gap-3 pt-1">
                            <div class="w-16 h-16 rounded-xl overflow-hidden bg-cream border border-gold-200/60 grid place-items-center">
                                <img :src="livePreview" class="w-full h-full object-contain p-1">
                            </div>
                            <p class="text-xs text-maroon-500">Preview</p>
                        </div>

                        <div class="flex items-center gap-3 mt-2">
                            <button type="submit" :disabled="!hasNewFile"
                                    class="flex-1 bg-pista-600 hover:bg-pista-700 text-white font-semibold rounded-xl py-2.5 transition text-sm disabled:opacity-40 disabled:cursor-not-allowed">
                                ✓ Save Logo
                            </button>
                            <button type="button" @click="closeModal()"
                                    class="px-5 bg-white border border-gold-300/70 hover:bg-gold-50 text-maroon-600 font-semibold rounded-xl py-2.5 transition text-sm">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- ── customer website theme ────────────────────────────────────── --}}
    <div x-data="themeSelector(@js($settings->customer_theme), @js($themes))"
         class="rounded-2xl border border-gold-200/60 bg-white p-5 mb-5">
        <div class="flex items-center gap-3">
            <span class="text-2xl">🎨</span>
            <div>
                <p class="font-display text-maroon-800">Customer Website Theme</p>
                <p class="text-sm text-maroon-500 mt-0.5">Changes the color palette across the customer-facing site only — the admin panel always keeps this current look.</p>
            </div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 mt-4">
            <template x-for="(theme, slug) in themes" :key="slug">
                <button type="button" @click="select(slug)"
                        class="relative text-left rounded-xl border-2 p-3 transition"
                        :class="selected === slug ? 'border-gold-500 ring-2 ring-gold-300/60' : 'border-gold-200/60 hover:border-gold-300'">
                    <span x-show="theme.recommended" x-cloak class="absolute -top-2 -right-2 text-[10px] font-bold uppercase tracking-wide px-1.5 py-0.5 rounded-full bg-gold-500 text-maroon-900 shadow-sm">⭐ Default</span>
                    <span x-show="selected === slug" x-cloak class="absolute -top-2 -left-2 w-5 h-5 rounded-full bg-pista-500 text-white text-xs grid place-items-center shadow-sm">✓</span>
                    <div class="flex h-8 rounded-lg overflow-hidden shadow-sm mb-2">
                        <span class="flex-1" :style="`background-color: ${theme.swatch.primary}`"></span>
                        <span class="flex-1" :style="`background-color: ${theme.swatch.secondary}`"></span>
                    </div>
                    <p class="text-xs font-semibold text-maroon-800" x-text="theme.label"></p>
                </button>
            </template>
        </div>

        {{-- self-contained live preview — CSS custom properties set only on this panel via
             previewStyle(), never touching the surrounding admin page's own styling --}}
        <div class="mt-5 rounded-xl border border-gold-200/60 overflow-hidden" :style="previewStyle()">
            <div class="px-4 py-3 flex items-center justify-between" style="background-color: rgb(var(--color-maroon-900))">
                <span class="font-display font-bold text-sm" style="color: rgb(var(--color-gold-400))">Shree Vinayak <span style="color: white">Family Shop</span></span>
                <span class="text-xs px-2 py-1 rounded-full" style="background-color: rgb(var(--color-gold-500)); color: rgb(var(--color-maroon-900))">Cart (2)</span>
            </div>
            <div class="p-4" style="background-color: rgb(var(--color-maroon-50))">
                <p class="text-sm font-semibold mb-2" style="color: rgb(var(--color-maroon-800))">Sample Product Card</p>
                <div class="flex items-center gap-2">
                    <span class="text-xs px-2 py-1 rounded-full font-semibold" style="background-color: rgb(var(--color-maroon-100)); color: rgb(var(--color-maroon-700))">Badge</span>
                    <a href="#" class="text-xs font-semibold underline" style="color: rgb(var(--color-maroon-600))" onclick="return false;">A link</a>
                </div>
                <button type="button" class="mt-3 text-xs font-semibold px-4 py-2 rounded-lg"
                        style="background: linear-gradient(to right, rgb(var(--color-gold-400)), rgb(var(--color-gold-600))); color: rgb(var(--color-maroon-900))">
                    Add to Cart
                </button>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.customization.theme') }}" class="mt-4 flex justify-end">
            @csrf
            @method('PATCH')
            <input type="hidden" name="customer_theme" :value="selected">
            <button type="submit" class="btn-gold text-sm px-6 py-2.5">💾 Save Changes</button>
        </form>
    </div>

    {{-- ── business contact number — moved here from Configuration; still saves through the same
         admin.settings.business-info route, unchanged (see AppServiceProvider's $businessPhone
         share) — this is a UI relocation only --}}
    <div class="rounded-2xl border border-gold-200/60 bg-white p-5 mb-5">
        <div class="flex items-center gap-3">
            <span class="text-2xl">📞</span>
            <div>
                <p class="font-display text-maroon-800">Business Contact</p>
                <p class="text-sm text-maroon-500 mt-0.5">Shown in the footer, WhatsApp links, order help text, invoices, and the legal pages.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.settings.business-info') }}" class="mt-4">
            @csrf
            @method('PATCH')
            <label class="block text-xs font-semibold text-maroon-500 uppercase tracking-wide mb-1.5">Business Mobile Number</label>
            <input type="text" name="business_mobile_number" maxlength="20" placeholder="10-digit mobile number"
                   value="{{ old('business_mobile_number', $settings->business_mobile_number) }}"
                   class="w-full max-w-xs rounded-lg border border-gold-300/70 px-3 py-2 text-sm text-maroon-800 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
            @error('business_mobile_number')
                <p class="text-xs text-red-600 mt-1.5">{{ $message }}</p>
            @enderror
            <p class="text-xs text-maroon-400 mt-1.5">Leave blank to hide all "call"/WhatsApp contact links site-wide.</p>
            <div class="flex justify-end mt-4">
                <button type="submit" class="btn-gold text-sm px-5 py-2">Save</button>
            </div>
        </form>
    </div>
@endsection
