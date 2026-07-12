<div x-data="{
        type: '{{ old('type', $product->type ?? 'piece') }}',
        portions: {{ json_encode(old('portions', $product->portions ?? [])) }},
        name: {{ Illuminate\Support\Js::from(old('name', $product->name ?? '')) }},
        category: {{ Illuminate\Support\Js::from(old('category', $product->category ?? $categories[0])) }},
        price: {{ Illuminate\Support\Js::from(old('price', $product->price ?? '')) }},
        weight: {{ Illuminate\Support\Js::from(old('weight', $product->weight ?? '')) }},
        tag: {{ Illuminate\Support\Js::from(old('tag', $product->tag ?? '')) }},
        color: {{ Illuminate\Support\Js::from(old('color', $product->color ?? '#c8962e')) }},
        description: {{ Illuminate\Support\Js::from(old('description', $product->description ?? '')) }},
        photoPreview: {{ Illuminate\Support\Js::from(isset($product) ? asset($product->image) : '') }},
        imagePosition: {{ Illuminate\Support\Js::from(old('image_position', $product->image_position ?? '50% 50%')) }},
        adjustingImage: false,
        dragging: false,
        dragStart: { x: 0, y: 0 },
        posAtDragStart: { x: 50, y: 50 },
        discountEnabled: {{ old('discount_enabled', isset($product) && $product->discount_type ? '1' : '0') ? 'true' : 'false' }},
        discountType: {{ Illuminate\Support\Js::from(old('discount_type', $product->discount_type ?? 'percentage')) }},
        discountValue: {{ Illuminate\Support\Js::from(old('discount_value', $product->discount_value ?? '')) }},

        onPhotoChange(e) {
            const file = e.target.files[0];
            if (!file) { this.photoPreview = {{ Illuminate\Support\Js::from(isset($product) ? asset($product->image) : '') }}; return; }
            const reader = new FileReader();
            reader.onload = (ev) => { this.photoPreview = ev.target.result; };
            reader.readAsDataURL(file);
        },
        defaultPortionGrams() {
            return this.portions.length ? Math.min(...this.portions) : 250;
        },
        portionLabel(g) {
            return g >= 1000 ? (g / 1000) + 'kg' : g + 'g';
        },
        discountedUnitPrice() {
            const p = parseInt(this.price) || 0;
            if (!this.discountEnabled || !this.discountValue) return p;
            if (this.discountType === 'percentage') {
                return Math.round(p * (1 - Math.min(this.discountValue, 100) / 100));
            }
            return Math.max(0, p - parseInt(this.discountValue));
        },
        previewOriginalPrice() {
            const p = parseInt(this.price) || 0;
            if (this.type !== 'loose') return p;
            return Math.round(p * (this.defaultPortionGrams() / 250));
        },
        previewPrice() {
            const p = this.discountedUnitPrice();
            if (this.type !== 'loose') return p;
            return Math.round(p * (this.defaultPortionGrams() / 250));
        },
        discountBadge() {
            if (!this.discountEnabled || !this.discountValue) return '';
            return this.discountType === 'percentage' ? `${this.discountValue}% OFF` : `₹${this.discountValue} OFF`;
        },
        onColorFor(hex) {
            hex = (hex || '#c8962e').replace('#', '');
            const r = parseInt(hex.substr(0, 2), 16) || 0, g = parseInt(hex.substr(2, 2), 16) || 0, b = parseInt(hex.substr(4, 2), 16) || 0;
            const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
            return luminance > 0.55 ? '#3a0b12' : '#fdf6e9';
        },

        // double-click the preview photo to drag it and pick which part stays visible
        // once the card crops it to a fixed aspect ratio
        posXY() {
            const parts = this.imagePosition.split(' ').map((s) => parseFloat(s));
            return { x: isNaN(parts[0]) ? 50 : parts[0], y: isNaN(parts[1]) ? 50 : parts[1] };
        },
        toggleAdjusting() {
            if (!this.photoPreview) return;
            this.adjustingImage = !this.adjustingImage;
        },
        startDrag(e) {
            if (!this.adjustingImage) return;
            this.dragging = true;
            this.dragStart = { x: e.clientX, y: e.clientY };
            this.posAtDragStart = this.posXY();
        },
        onDrag(e) {
            if (!this.dragging) return;
            const rect = this.$refs.previewImageBox.getBoundingClientRect();
            const dxPct = ((e.clientX - this.dragStart.x) / rect.width) * 100;
            const dyPct = ((e.clientY - this.dragStart.y) / rect.height) * 100;
            const x = Math.round(Math.min(100, Math.max(0, this.posAtDragStart.x - dxPct)));
            const y = Math.round(Math.min(100, Math.max(0, this.posAtDragStart.y - dyPct)));
            this.imagePosition = `${x}% ${y}%`;
        },
        stopDrag() {
            this.dragging = false;
        },
        resetImagePosition() {
            this.imagePosition = '50% 50%';
        },
     }"
     class="grid lg:grid-cols-[1fr_420px] gap-10 items-start">

    <div class="max-w-2xl">
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
                <input type="text" name="name" x-model="name" required
                       class="w-full rounded-lg border border-gold-300/70 px-3.5 py-2.5 text-maroon-800 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
            </div>
            <div>
                <label class="block text-sm font-medium text-maroon-700 mb-1.5">Category</label>
                <select name="category" x-model="category" required class="w-full rounded-lg border border-gold-300/70 px-3.5 py-2.5 text-maroon-800 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
                    @foreach ($categories as $category)
                        <option value="{{ $category }}">{{ $category }}</option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-maroon-700 mb-1.5">Product Type</label>
                <select name="type" x-model="type" required class="w-full md:w-64 rounded-lg border border-gold-300/70 px-3.5 py-2.5 text-maroon-800 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
                    <option value="piece">Piece (sold per unit)</option>
                    <option value="loose">Loose (sold by weight)</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-maroon-700 mb-1.5" x-text="type === 'loose' ? 'Price for 250g (₹)' : 'Price (₹)'"></label>
                <input type="number" name="price" min="1" x-model.number="price" required
                       class="w-full rounded-lg border border-gold-300/70 px-3.5 py-2.5 text-maroon-800 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
            </div>
            <div x-show="type === 'piece'">
                <label class="block text-sm font-medium text-maroon-700 mb-1.5">Weight / Size label</label>
                <input type="text" name="weight" placeholder="e.g. 250g, 1 Bowl" x-model="weight" :required="type === 'piece'"
                       class="w-full rounded-lg border border-gold-300/70 px-3.5 py-2.5 text-maroon-800 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
            </div>

            <div x-show="type === 'loose'" class="md:col-span-2">
                <label class="block text-sm font-medium text-maroon-700 mb-1.5">Available Portions</label>
                <div class="flex flex-wrap gap-4">
                    @foreach (\App\Models\Product::PORTION_OPTIONS as $grams)
                        <label class="flex items-center gap-2 text-sm text-maroon-700">
                            <input type="checkbox" name="portions[]" value="{{ $grams }}"
                                   :checked="portions.includes({{ $grams }})"
                                   @change="$event.target.checked ? portions.push({{ $grams }}) : portions = portions.filter(g => g !== {{ $grams }})"
                                   class="w-4 h-4 rounded border-gold-300/70 text-gold-600 focus:ring-gold-400">
                            {{ \App\Models\Product::portionLabel($grams) }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="md:col-span-2 rounded-lg border border-gold-200/70 bg-cream/40 p-4">
                <label class="flex items-center gap-2.5 text-sm font-medium text-maroon-700">
                    <input type="checkbox" name="discount_enabled" value="1" x-model="discountEnabled"
                           class="w-4 h-4 rounded border-gold-300/70 text-gold-600 focus:ring-gold-400">
                    Enable Discount
                </label>

                <div x-show="discountEnabled" x-cloak class="grid sm:grid-cols-2 gap-4 mt-3.5">
                    <div>
                        <label class="block text-sm font-medium text-maroon-700 mb-1.5">Discount Type</label>
                        <select name="discount_type" x-model="discountType" :required="discountEnabled"
                                class="w-full rounded-lg border border-gold-300/70 px-3.5 py-2.5 text-maroon-800 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
                            <option value="percentage">Percentage (%)</option>
                            <option value="flat">Flat Amount (₹)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-maroon-700 mb-1.5" x-text="discountType === 'percentage' ? 'Discount %' : 'Discount Amount (₹)'"></label>
                        <input type="number" name="discount_value" min="1" :max="discountType === 'percentage' ? 100 : null"
                               x-model.number="discountValue" :required="discountEnabled"
                               class="w-full rounded-lg border border-gold-300/70 px-3.5 py-2.5 text-maroon-800 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-maroon-700 mb-1.5">Tag <span class="text-maroon-300">(optional badge, e.g. Chilled)</span></label>
                <input type="text" name="tag" x-model="tag"
                       class="w-full rounded-lg border border-gold-300/70 px-3.5 py-2.5 text-maroon-800 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
            </div>
            <div>
                <label class="block text-sm font-medium text-maroon-700 mb-1.5">Accent Color</label>
                <div class="flex items-center gap-2">
                    <input type="color" name="color" x-model="color"
                           class="w-12 h-[42px] rounded-lg border border-gold-300/70 cursor-pointer">
                    <span class="text-maroon-400 text-sm">Used for the product's tag/price accent</span>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-maroon-700 mb-1.5">Sort Order</label>
                <input type="number" name="sort_order" min="0" value="{{ old('sort_order', $product->sort_order ?? 0) }}" required
                       class="w-full rounded-lg border border-gold-300/70 px-3.5 py-2.5 text-maroon-800 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
            </div>
            <div>
                <label class="block text-sm font-medium text-maroon-700 mb-1.5">
                    Photo @if(isset($product)) <span class="text-maroon-300">(leave blank to keep current)</span> @endif
                </label>
                <input type="file" name="image" accept="image/*" @change="onPhotoChange($event)"
                       class="w-full rounded-lg border border-gold-300/70 px-3.5 py-2 text-maroon-700 text-sm file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:bg-gold-100 file:text-gold-700 file:font-medium">
                <p class="text-xs text-maroon-400 mt-1.5">Double-click the photo in the preview to drag and reposition it.</p>
                <input type="hidden" name="image_position" :value="imagePosition">
            </div>
        </div>

        <div class="mt-5">
            <label class="block text-sm font-medium text-maroon-700 mb-1.5">Description</label>
            <textarea name="description" rows="3" x-model="description"
                      class="w-full rounded-lg border border-gold-300/70 px-3.5 py-2.5 text-maroon-800 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition"></textarea>
        </div>

        <div class="mt-7 flex items-center gap-3">
            <button type="submit" class="btn-gold">{{ isset($product) ? 'Save Changes' : 'Add Product' }}</button>
            <a href="{{ route('admin.products.index') }}" class="text-maroon-500 hover:text-maroon-700 text-sm font-medium">Cancel</a>
        </div>
    </div>

    {{-- live preview — mirrors partials/product-card.blade.php so the admin sees exactly
         what the customer will see, updating as the form is filled in --}}
    <div class="lg:sticky lg:top-6">
        <p class="text-xs font-semibold uppercase tracking-wide text-maroon-400 mb-2">👀 Live Preview</p>
        <div class="h-full flex flex-col bg-white rounded-2xl shadow-md overflow-hidden border border-gold-200/60">
            <div x-ref="previewImageBox" class="relative h-56 overflow-hidden select-none"
                 :class="adjustingImage ? 'ring-4 ring-inset ring-gold-400 cursor-move' : (photoPreview ? 'cursor-pointer' : '')"
                 :style="`background: linear-gradient(160deg, ${color}18, ${color}4d)`"
                 @dblclick="toggleAdjusting()"
                 @mousedown="startDrag($event)"
                 @mousemove.window="onDrag($event)"
                 @mouseup.window="stopDrag()">
                <template x-if="photoPreview">
                    <img :src="photoPreview" alt="" :style="`object-position: ${imagePosition}`"
                         class="absolute inset-0 w-full h-full object-cover pointer-events-none">
                </template>
                <template x-if="!photoPreview">
                    <div class="absolute inset-0 flex items-center justify-center text-6xl opacity-30">🍬</div>
                </template>
                <span x-show="discountBadge()" x-cloak x-text="discountBadge()"
                      class="absolute top-3 left-3 text-xs font-bold uppercase tracking-wide px-2.5 py-1 rounded-full bg-red-600 text-white shadow-sm"></span>
                <div class="absolute top-3 right-3 w-9 h-9 rounded-full bg-white/85 flex items-center justify-center shadow-sm">
                    <svg class="w-5 h-5 text-maroon-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                    </svg>
                </div>

                <div x-show="photoPreview && !adjustingImage" x-cloak
                     class="absolute inset-x-0 bottom-0 bg-maroon-900/60 text-cream text-[11px] text-center py-1.5 pointer-events-none">
                    Double-click to reposition
                </div>
                <div x-show="adjustingImage" x-cloak @mousedown.stop @dblclick.stop
                     class="absolute inset-x-0 bottom-0 bg-maroon-900/80 flex items-center justify-between px-3 py-1.5">
                    <span class="text-cream text-[11px]">Drag the photo, then double-click to finish</span>
                    <div class="flex items-center gap-2">
                        <button type="button" @click.stop="resetImagePosition()" class="text-[11px] font-semibold text-gold-300 hover:text-gold-200 transition">Reset</button>
                        <button type="button" @click.stop="adjustingImage = false" class="text-[11px] font-semibold text-cream bg-white/15 hover:bg-white/25 rounded-full px-2.5 py-1 transition">Done</button>
                    </div>
                </div>
            </div>

            <div class="p-5 flex flex-col flex-1">
                <p class="text-[11px] font-semibold tracking-widest uppercase text-gold-600" x-text="category"></p>
                <h3 class="font-display font-bold text-lg text-maroon-800 mt-0.5" x-text="name || 'Product Name'"></h3>

                <div class="flex flex-wrap gap-2 mt-2.5">
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-md border border-gold-300/60 text-maroon-600"
                          x-text="type === 'loose' ? (portions.length ? portionLabel(defaultPortionGrams()) : 'Select portions') : (weight || 'Weight')"></span>
                    <span x-show="tag" class="text-xs font-semibold px-2.5 py-1 rounded-md" :style="`background-color: ${color}; color: ${onColorFor(color)}`" x-text="tag"></span>
                </div>

                <p class="text-sm text-maroon-500/90 mt-3 leading-relaxed line-clamp-3" x-text="description || 'Product description will appear here.'"></p>

                <div class="flex items-center justify-between mt-4 pt-1 mt-auto">
                    <p class="flex items-baseline gap-1.5">
                        <span x-show="discountEnabled && discountValue" x-cloak class="text-sm text-maroon-300 line-through" x-text="'₹' + (previewOriginalPrice() || 0)"></span>
                        <span class="font-display font-bold text-lg" :style="`color: ${color}`">
                            <span x-show="type === 'loose'">From </span>₹<span x-text="previewPrice() || 0"></span>
                        </span>
                    </p>
                    <div class="flex items-center gap-2 pointer-events-none">
                        <div class="w-10 h-10 rounded-xl border-2 flex items-center justify-center shrink-0" :style="`border-color: ${color}; color: ${color}`">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.836l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 1.994-4.693 2.602-7.152.232-.94-.437-1.85-1.402-1.85H5.106M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                            </svg>
                        </div>
                        <div class="text-sm font-semibold px-4 py-2.5 rounded-xl shadow-sm" :style="`background-color: ${color}; color: ${onColorFor(color)}`">Order Now</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
