@if ($errors->any())
    <div class="mb-5 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3">
        <ul class="list-disc list-inside space-y-0.5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div x-data="announcementForm({
        enabled: {{ old('is_enabled', $announcement->is_enabled) ? 'true' : 'false' }},
        headline: {{ Illuminate\Support\Js::from(old('headline', $announcement->headline ?? '')) }},
        description: {{ Illuminate\Support\Js::from(old('description', $announcement->description ?? '')) }},
        buttonText: {{ Illuminate\Support\Js::from(old('button_text', $announcement->button_text ?? '')) }},
        buttonUrl: {{ Illuminate\Support\Js::from(old('button_url', $announcement->button_url ?? '')) }},
        image: {{ Illuminate\Support\Js::from($announcement->image_path ? asset($announcement->image_path) : null) }},
        theme: {{ Illuminate\Support\Js::from(old('theme', $announcement->theme ?? 'maroon')) }},
        backgroundColor: {{ Illuminate\Support\Js::from(old('background_color', $announcement->background_color ?? '#7a1622')) }},
        textColor: {{ Illuminate\Support\Js::from(old('text_color', $announcement->text_color ?? '#fdf6e9')) }},
        showClose: {{ old('show_close_button', $announcement->show_close_button) ? 'true' : 'false' }},
     })" x-init="initEditor()"
     class="grid lg:grid-cols-[1fr_460px] gap-10 items-start">

    <div class="max-w-2xl">
        <div class="rounded-lg border border-gold-200/70 bg-cream/40 p-4 flex items-center justify-between">
            <div>
                <p class="text-sm font-semibold text-maroon-800">Show announcement on the site</p>
                <p class="text-xs text-maroon-400 mt-0.5">When off, nothing appears for visitors regardless of the settings below.</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" name="is_enabled" value="1" x-model="enabled" class="sr-only peer">
                {{-- off-state was bg-gold-200 — too close to this card's own bg-cream/40 to read as
                     a toggle at all; a visible border plus a darker off-track fixes that in both states --}}
                <div class="w-11 h-6 bg-maroon-200 border border-maroon-300/70 rounded-full peer peer-checked:bg-pista-500 peer-checked:border-pista-500 transition-colors"></div>
                <div class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full shadow-md ring-1 ring-black/10 transition-transform peer-checked:translate-x-5"></div>
            </label>
        </div>

        <div class="mt-5">
            <label class="block text-sm font-medium text-maroon-700 mb-1.5">Headline</label>
            <input type="text" name="headline" x-model="headline" maxlength="120" placeholder="e.g. Diwali Special — Flat 20% Off!"
                   class="w-full rounded-lg border border-gold-300/70 px-3.5 py-2.5 text-maroon-800 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
        </div>

        <div class="mt-5">
            <label class="block text-sm font-medium text-maroon-700 mb-1.5">Description</label>
            <div class="rounded-lg border border-gold-300/70 overflow-hidden bg-white">
                <div x-ref="editor" style="min-height: 140px;"></div>
            </div>
            <input type="hidden" name="description" x-model="description">
        </div>

        <div class="grid sm:grid-cols-2 gap-5 mt-5">
            <div>
                <label class="block text-sm font-medium text-maroon-700 mb-1.5">Button Text <span class="text-maroon-300">(optional)</span></label>
                <input type="text" name="button_text" x-model="buttonText" maxlength="40" placeholder="e.g. Shop Now"
                       class="w-full rounded-lg border border-gold-300/70 px-3.5 py-2.5 text-maroon-800 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
            </div>
            <div>
                <label class="block text-sm font-medium text-maroon-700 mb-1.5">Button Link</label>
                <input type="text" name="button_url" x-model="buttonUrl" placeholder="/products or https://…"
                       class="w-full rounded-lg border border-gold-300/70 px-3.5 py-2.5 text-maroon-800 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
            </div>
        </div>

        <div class="mt-5">
            <label class="block text-sm font-medium text-maroon-700 mb-1.5">
                Banner Image <span class="text-maroon-300">(optional)</span>
            </label>
            <input x-ref="imageInput" type="file" name="image" accept="image/*" @change="onImageSelected($event)"
                   class="w-full rounded-lg border border-gold-300/70 px-3.5 py-2 text-maroon-700 text-sm file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:bg-gold-100 file:text-gold-700 file:font-medium">
            <div x-show="image" x-cloak class="mt-2">
                <button type="button" @click="removeImage()" class="text-xs font-semibold text-red-600 hover:text-red-700">Remove image</button>
            </div>
            <input type="hidden" name="remove_image" :value="removeImageFlag ? '1' : '0'">
        </div>

        <div class="mt-6 rounded-lg border border-gold-200/70 bg-cream/40 p-4">
            <p class="text-sm font-semibold text-maroon-800 mb-3">Appearance</p>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-maroon-700 mb-1.5">Theme</label>
                    <select name="theme" x-model="theme" class="w-full rounded-lg border border-gold-300/70 px-3.5 py-2.5 text-maroon-800 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
                        <option value="maroon">Maroon (brand default)</option>
                        <option value="gold">Gold</option>
                        <option value="pista">Pista Green</option>
                        <option value="dark">Dark</option>
                        <option value="custom">Custom colors</option>
                    </select>
                </div>
                <div x-show="theme === 'custom'" x-cloak class="flex items-end gap-3">
                    <div>
                        <label class="block text-sm font-medium text-maroon-700 mb-1.5">Background</label>
                        <input type="color" name="background_color" x-model="backgroundColor" class="w-12 h-[42px] rounded-lg border border-gold-300/70 cursor-pointer">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-maroon-700 mb-1.5">Text</label>
                        <input type="color" name="text_color" x-model="textColor" class="w-12 h-[42px] rounded-lg border border-gold-300/70 cursor-pointer">
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-5 rounded-lg border border-gold-200/70 bg-cream/40 p-4">
            <p class="text-sm font-semibold text-maroon-800 mb-3">Behavior</p>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-maroon-700 mb-1.5">Display Frequency</label>
                    <select name="display_frequency" class="w-full rounded-lg border border-gold-300/70 px-3.5 py-2.5 text-maroon-800 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
                        <option value="every_visit" @selected(old('display_frequency', $announcement->display_frequency) === 'every_visit')>Every visit</option>
                        <option value="once_per_session" @selected(old('display_frequency', $announcement->display_frequency) === 'once_per_session')>Once per session</option>
                        <option value="once_per_day" @selected(old('display_frequency', $announcement->display_frequency) === 'once_per_day')>Once per day</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-maroon-700 mb-1.5">Auto-close after <span class="text-maroon-300">(seconds, optional)</span></label>
                    <input type="number" name="auto_close_seconds" min="3" max="120"
                           value="{{ old('auto_close_seconds', $announcement->auto_close_seconds ?? '') }}" placeholder="Never"
                           class="w-full rounded-lg border border-gold-300/70 px-3.5 py-2.5 text-maroon-800 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
                </div>
            </div>
            <label class="flex items-center gap-2.5 text-sm font-medium text-maroon-700 mt-4">
                <input type="checkbox" name="show_close_button" value="1" x-model="showClose" class="w-4 h-4 rounded border-gold-300/70 text-gold-600 focus:ring-gold-400">
                Show close (✕) button
            </label>
        </div>

        <div class="mt-7 flex items-center gap-3">
            <button type="submit" class="btn-gold">Save Announcement</button>
        </div>
    </div>

    {{-- live preview — mirrors the exact markup shown to visitors --}}
    <div class="lg:sticky lg:top-6">
        <p class="text-xs font-semibold uppercase tracking-wide text-maroon-400 mb-2">👀 Live Preview</p>
        <div class="rounded-2xl bg-maroon-950/90 p-8 flex items-center justify-center min-h-[420px]">
            <div class="relative w-full max-w-md rounded-3xl shadow-2xl overflow-hidden" @click.prevent>
                @include('partials.announcement-banner-content')
            </div>
        </div>
        <p class="text-xs text-maroon-400 mt-2">This is exactly how it will appear, centered over a dimmed page, when a visitor opens the site.</p>
    </div>
</div>
