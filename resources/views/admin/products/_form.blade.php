<div x-data="{
        type: '{{ old('type', $product->type ?? 'piece') }}',
        unit: '{{ old('unit', $product->unit ?? 'weight') }}',
        portions: {{ json_encode(old('portions', $product->portions ?? [])) }},
        portionPrices: {{ json_encode(old('portion_prices', $product->portion_prices ?? (object) [])) }},
        // grams -> preview URL (existing override, or a freshly-picked file read via FileReader
        // below) — a portion with no entry here just shows the add-photo placeholder and falls
        // back to the product's main image (see Product::imageForPortion())
        portionImagePreviews: {{ Illuminate\Support\Js::from(collect($product->portion_images ?? [])->mapWithKeys(fn ($path, $grams) => [(string) $grams => asset($path)])->all()) }},
        portionImageRemoved: {},
        name: {{ Illuminate\Support\Js::from(old('name', $product->name ?? '')) }},
        category: {{ Illuminate\Support\Js::from(old('category', $product->category ?? $categories[0])) }},
        price: {{ Illuminate\Support\Js::from(old('price', $product->price ?? '')) }},
        weight: {{ Illuminate\Support\Js::from(old('weight', $product->weight ?? '')) }},
        tag: {{ Illuminate\Support\Js::from(old('tag', $product->tag ?? '')) }},
        searchTags: {{ json_encode(old('search_tags', $product->search_tags ?? [])) }},
        tagDraft: '',
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
        outOfStock: {{ old('is_out_of_stock', $product->is_out_of_stock ?? false) ? 'true' : 'false' }},

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
            const big = this.unit === 'volume' ? 'L' : 'kg';
            const small = this.unit === 'volume' ? 'ml' : 'g';
            return g >= 1000 ? (g / 1000) + big : g + small;
        },
        onPortionImageChange(e, grams) {
            const file = e.target.files[0];
            if (!file) return;
            this.portionImageRemoved[grams] = false;
            const reader = new FileReader();
            reader.onload = (ev) => { this.portionImagePreviews[grams] = ev.target.result; };
            reader.readAsDataURL(file);
        },
        removePortionImage(grams, fileInput) {
            delete this.portionImagePreviews[grams];
            this.portionImageRemoved[grams] = true;
            if (fileInput) fileInput.value = '';
        },
        discountedUnitPrice() {
            const p = parseInt(this.price) || 0;
            return this.applyDiscount(p);
        },
        applyDiscount(p) {
            if (!this.discountEnabled || !this.discountValue) return p;
            if (this.discountType === 'percentage') {
                return Math.round(p * (1 - Math.min(this.discountValue, 100) / 100));
            }
            return Math.max(0, p - parseInt(this.discountValue));
        },
        // the portion's own override price, if one was entered — null falls back to the
        // linear price*grams/250 formula, mirroring Product::portionOverridePrice() in PHP
        portionOverride(grams) {
            const v = this.portionPrices[grams];
            return v !== undefined && v !== null && v !== '' ? parseInt(v) : null;
        },
        previewOriginalPrice() {
            const grams = this.defaultPortionGrams();
            if (this.type !== 'loose') return parseInt(this.price) || 0;
            const override = this.portionOverride(grams);
            if (override !== null) return override;
            return Math.round((parseInt(this.price) || 0) * (grams / 250));
        },
        previewPrice() {
            const grams = this.defaultPortionGrams();
            if (this.type !== 'loose') return this.discountedUnitPrice();
            const override = this.portionOverride(grams);
            if (override !== null) return this.applyDiscount(override);
            return Math.round(this.discountedUnitPrice() * (grams / 250));
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

        // comma or Enter commits the current draft as one or more chips; case-insensitive
        // de-dup so 'Chini' typed twice (or pasted as part of a comma list) doesn't add twice
        addTag() {
            this.tagDraft.split(',').map((t) => t.trim()).filter(Boolean).forEach((t) => {
                if (!this.searchTags.some((existing) => existing.toLowerCase() === t.toLowerCase())) {
                    this.searchTags.push(t);
                }
            });
            this.tagDraft = '';
        },
        removeTag(i) {
            this.searchTags.splice(i, 1);
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

            @if ($allCategories->isNotEmpty())
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-maroon-700 mb-1.5">Categories</label>
                    <p class="text-xs text-maroon-400 mb-2">Shown in the mobile Categories panel — a product can belong to more than one.</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($allCategories as $cat)
                            <label class="flex items-center gap-2 text-sm text-maroon-700 border border-gold-300/60 rounded-lg px-3 py-2 cursor-pointer hover:border-gold-500 transition">
                                <input type="checkbox" name="categories[]" value="{{ $cat->id }}"
                                       @checked(in_array($cat->id, old('categories', isset($product) ? $product->categories->pluck('id')->all() : [])))
                                       class="w-4 h-4 rounded border-gold-300 text-gold-500 focus:ring-gold-400">
                                {{ $cat->name }}
                            </label>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($allTags->isNotEmpty())
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-maroon-700 mb-1.5">Product Tags</label>
                    <p class="text-xs text-maroon-400 mb-2">Drives the homepage's Featured Categories shortcut row — a product can carry more than one tag. Manage the tag list under Product Tags.</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($allTags as $tag)
                            <label class="flex items-center gap-2 text-sm text-maroon-700 border border-gold-300/60 rounded-lg px-3 py-2 cursor-pointer hover:border-gold-500 transition">
                                <input type="checkbox" name="tags[]" value="{{ $tag->id }}"
                                       @checked(in_array($tag->id, old('tags', isset($product) ? $product->tags->pluck('id')->all() : [])))
                                       class="w-4 h-4 rounded border-gold-300 text-gold-500 focus:ring-gold-400">
                                {{ $tag->name }}
                            </label>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-maroon-700 mb-1.5">Product Type</label>
                <select name="type" x-model="type" required class="w-full md:w-64 rounded-lg border border-gold-300/70 px-3.5 py-2.5 text-maroon-800 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
                    <option value="piece">Piece (sold per unit)</option>
                    <option value="loose">Loose (sold by weight)</option>
                </select>
            </div>

            <div x-show="type === 'loose'" x-cloak class="md:col-span-2">
                <label class="block text-sm font-medium text-maroon-700 mb-1.5">Unit</label>
                <select name="unit" x-model="unit" :required="type === 'loose'" class="w-full md:w-64 rounded-lg border border-gold-300/70 px-3.5 py-2.5 text-maroon-800 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
                    <option value="weight">Weight (g / kg) — bulk items like dal, rice</option>
                    <option value="volume">Volume (ml / L) — bottled liquids like handwash</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-maroon-700 mb-1.5" x-text="type === 'loose' ? ('Price for 250' + (unit === 'volume' ? 'ml' : 'g') + ' (₹)') : 'Price (₹)'"></label>
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
                <p class="text-xs text-maroon-400 mb-2.5">
                    Each portion's price is calculated from the base price above by default (2× the size = 2× the price, etc).
                    For a pre-packaged item where that doesn't hold — e.g. a 1kg pack that costs less per gram than a 250g one —
                    set that portion's own price to override the calculation just for it. A size can also have its own photo
                    (a 25ml bottle doesn't look like a 1L one) — leave it blank to just use the main product photo above.
                </p>
                @foreach ([['weight', \App\Models\Product::WEIGHT_PORTION_OPTIONS], ['volume', \App\Models\Product::VOLUME_PORTION_OPTIONS]] as [$u, $options])
                    {{-- weight and volume share the exact same 12 numbers, so every field name here
                         (portions[], portion_prices[N], portion_images[N]) exists TWICE in the DOM —
                         once per unit list. A plain x-show'd <div> still submits its hidden fields
                         (browsers don't exclude display:none inputs), and for a duplicate name the
                         LAST one in the DOM silently wins in PHP's request parsing — so an upload
                         picked in the weight list could get clobbered by the untouched, hidden
                         volume-list file input for that same size. <fieldset disabled> excludes
                         every field inside it from submission, which a plain :disabled on each
                         input can't do in one place. --}}
                    <fieldset x-show="unit === '{{ $u }}'" x-cloak :disabled="unit !== '{{ $u }}'" class="space-y-2.5 border-0 p-0 m-0">
                        @foreach ($options as $grams)
                            <div class="flex items-center gap-3 flex-wrap py-0.5">
                                <label class="flex items-center gap-2 text-sm text-maroon-700 w-20 shrink-0">
                                    <input type="checkbox" name="portions[]" value="{{ $grams }}"
                                           :checked="portions.includes({{ $grams }})"
                                           @change="$event.target.checked ? portions.push({{ $grams }}) : portions = portions.filter(g => g !== {{ $grams }})"
                                           class="w-4 h-4 rounded border-gold-300/70 text-gold-600 focus:ring-gold-400">
                                    {{ \App\Models\Product::portionLabel($grams, $u) }}
                                </label>
                                <div x-show="portions.includes({{ $grams }})" x-cloak class="flex items-center gap-2 flex-wrap">
                                    <span class="text-sm text-maroon-500">₹</span>
                                    <input type="number" name="portion_prices[{{ $grams }}]" min="1"
                                           x-model.number="portionPrices[{{ $grams }}]"
                                           :placeholder="'auto ₹' + Math.round((parseInt(price) || 0) * ({{ $grams }} / 250))"
                                           class="w-24 rounded-lg border border-gold-300/70 px-2.5 py-1.5 text-sm text-maroon-800 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
                                    <span class="text-xs text-maroon-400">price override</span>

                                    <label class="relative w-9 h-9 rounded-lg border border-dashed border-gold-300/70 overflow-hidden flex items-center justify-center cursor-pointer hover:border-gold-500 transition shrink-0"
                                           :class="portionImagePreviews[{{ $grams }}] && '!border-solid'">
                                        <template x-if="portionImagePreviews[{{ $grams }}]">
                                            <img :src="portionImagePreviews[{{ $grams }}]" class="absolute inset-0 w-full h-full object-cover">
                                        </template>
                                        <template x-if="!portionImagePreviews[{{ $grams }}]">
                                            <span class="text-gold-400 text-base leading-none">+</span>
                                        </template>
                                        <input type="file" name="portion_images[{{ $grams }}]" accept="image/*" class="hidden"
                                               x-ref="portionImage{{ $grams }}"
                                               @change="onPortionImageChange($event, {{ $grams }})">
                                    </label>
                                    <button type="button" x-show="portionImagePreviews[{{ $grams }}]" x-cloak
                                            @click="removePortionImage({{ $grams }}, $refs['portionImage{{ $grams }}'])"
                                            class="text-xs text-maroon-400 hover:text-red-600 transition">photo ×</button>
                                    <input type="hidden" name="remove_portion_image[{{ $grams }}]" :value="portionImageRemoved[{{ $grams }}] ? '1' : '0'">
                                </div>
                            </div>
                        @endforeach
                    </fieldset>
                @endforeach
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

            <div class="md:col-span-2 rounded-lg border p-4 transition"
                 :class="outOfStock ? 'border-red-300 bg-red-50' : 'border-gold-200/70 bg-cream/40'">
                <label class="flex items-center gap-2.5 text-sm font-medium" :class="outOfStock ? 'text-red-700' : 'text-maroon-700'">
                    <input type="checkbox" name="is_out_of_stock" value="1" x-model="outOfStock"
                           class="w-4 h-4 rounded border-gold-300/70 text-red-600 focus:ring-red-400">
                    🚫 Mark as Out of Stock
                </label>
                <p class="text-xs mt-1.5" :class="outOfStock ? 'text-red-600' : 'text-maroon-400'">
                    <span x-show="!outOfStock">Customers can still see and buy this product normally.</span>
                    <span x-show="outOfStock" x-cloak>Customers will see an "Out of Stock" badge everywhere this product appears, and won't be able to add it to their cart.</span>
                </p>
            </div>

            <div>
                <label class="block text-sm font-medium text-maroon-700 mb-1.5">Tag <span class="text-maroon-300">(optional badge, e.g. Chilled)</span></label>
                <input type="text" name="tag" x-model="tag"
                       class="w-full rounded-lg border border-gold-300/70 px-3.5 py-2.5 text-maroon-800 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-maroon-700 mb-1.5">
                    Search Tags <span class="text-maroon-300">(optional — synonyms/keywords so customers find this product)</span>
                </label>
                <div class="w-full rounded-lg border border-gold-300/70 px-2.5 py-2 flex flex-wrap gap-1.5 items-center bg-white focus-within:ring-2 focus-within:ring-gold-400 focus-within:border-gold-400 transition">
                    <template x-for="(t, i) in searchTags" :key="i + '-' + t">
                        <span class="inline-flex items-center gap-1.5 text-xs font-medium pl-2.5 pr-1.5 py-1 rounded-full bg-gold-100 text-gold-700 border border-gold-300/60">
                            <span x-text="t"></span>
                            <button type="button" @click="removeTag(i)" aria-label="Remove tag"
                                    class="w-4 h-4 rounded-full flex items-center justify-center hover:bg-gold-300/60 hover:text-gold-900 transition leading-none">✕</button>
                        </span>
                    </template>
                    <input type="text" x-model="tagDraft"
                           @keydown.enter.prevent="addTag()" @keydown.comma.prevent="addTag()"
                           @keydown.backspace="if (!tagDraft && searchTags.length) removeTag(searchTags.length - 1)"
                           @blur="addTag()"
                           placeholder="{{ __('Type a keyword, press Enter or comma…') }}"
                           class="flex-1 min-w-[160px] border-0 focus:ring-0 text-sm text-maroon-800 placeholder-maroon-400/50 py-1 px-1">
                </div>
                <template x-for="t in searchTags" :key="'hidden-'+t">
                    <input type="hidden" name="search_tags[]" :value="t">
                </template>
                <p class="text-xs text-maroon-400 mt-1.5">e.g. Sugar → Chini, Sakkar, Cheeni, Sweetener. Helps customers find this product when they search in Hindi, Hinglish, or a different spelling.</p>
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

    {{-- live preview — mirrors partials/product-card-mini.blade.php (the one canonical card
         style used everywhere on the storefront now) so the admin sees exactly what the
         customer will see, updating as the form is filled in. The heart button and category/
         tag/description fields shown on the OLD full card are intentionally absent here — the
         mini card never renders them, and a rating row is skipped entirely since a new product
         has no reviews yet. --}}
    <div class="lg:sticky lg:top-6">
        <p class="text-xs font-semibold uppercase tracking-wide text-maroon-400 mb-2">👀 Live Preview</p>
        <div class="w-full max-w-[220px] bg-white rounded-2xl border border-gold-200/60 shadow-sm p-3 flex flex-col">
            <div x-ref="previewImageBox" class="relative rounded-xl overflow-hidden aspect-square select-none bg-cream/60"
                 :class="adjustingImage ? 'ring-4 ring-inset ring-gold-400 cursor-move' : (photoPreview ? 'cursor-pointer' : '')"
                 @dblclick="toggleAdjusting()"
                 @mousedown="startDrag($event)"
                 @mousemove.window="onDrag($event)"
                 @mouseup.window="stopDrag()">
                <template x-if="photoPreview">
                    <img :src="photoPreview" alt="" :style="`object-position: ${imagePosition}`"
                         class="absolute inset-0 w-full h-full object-cover pointer-events-none transition"
                         :class="outOfStock ? 'grayscale opacity-60' : ''">
                </template>
                <template x-if="!photoPreview">
                    <div class="absolute inset-0 flex items-center justify-center text-5xl opacity-30">🍬</div>
                </template>
                <span x-show="discountBadge() && !outOfStock" x-cloak x-text="discountBadge()"
                      class="absolute top-1.5 left-1.5 bg-maroon-700 text-cream text-[10px] font-bold px-1.5 py-0.5 rounded-md shadow"></span>
                {{-- out-of-stock takes visual priority over the discount badge — a product that
                     can't be bought shouldn't still be advertising a % off --}}
                <div x-show="outOfStock" x-cloak class="absolute inset-0 z-[5] bg-maroon-950/35 flex items-center justify-center pointer-events-none">
                    <span class="bg-maroon-900 text-cream text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full shadow-lg border border-white/20">
                        Out of Stock
                    </span>
                </div>
                {{-- decorative only — there's no persisted product id yet for a real heart-toggle --}}
                <div class="absolute top-1.5 right-1.5 w-7 h-7 rounded-full bg-white/85 flex items-center justify-center shadow-sm">
                    <svg class="w-3.5 h-3.5 text-maroon-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                    </svg>
                </div>

                <div x-show="photoPreview && !adjustingImage" x-cloak
                     class="absolute inset-x-0 bottom-0 bg-maroon-900/60 text-cream text-[10px] text-center py-1 pointer-events-none">
                    Double-click to reposition
                </div>
                <div x-show="adjustingImage" x-cloak @mousedown.stop @dblclick.stop
                     class="absolute inset-x-0 bottom-0 bg-maroon-900/80 flex items-center justify-between px-2 py-1">
                    <span class="text-cream text-[10px]">Drag, then double-click</span>
                    <div class="flex items-center gap-2">
                        <button type="button" @click.stop="resetImagePosition()" class="text-[10px] font-semibold text-gold-300 hover:text-gold-200 transition">Reset</button>
                        <button type="button" @click.stop="adjustingImage = false" class="text-[10px] font-semibold text-cream bg-white/15 hover:bg-white/25 rounded-full px-2 py-0.5 transition">Done</button>
                    </div>
                </div>
            </div>

            <p class="mt-2.5 text-sm font-semibold text-maroon-800 leading-snug line-clamp-2" x-text="name || 'Product Name'"></p>

            <p class="text-xs text-maroon-400 mt-0.5"
               x-text="type === 'loose' ? (portions.length ? portionLabel(defaultPortionGrams()) : 'Select portions') : (weight || 'Weight')"></p>

            <div class="flex items-center justify-between gap-2 mt-2 pt-1 mt-auto">
                <p class="flex items-baseline gap-1 flex-wrap min-w-0" :class="outOfStock ? 'opacity-40' : ''">
                    <template x-if="discountEnabled && discountValue">
                        <span class="text-[11px] text-maroon-300 line-through">₹<span x-text="previewOriginalPrice() || 0"></span></span>
                    </template>
                    <span class="font-display font-bold text-base text-maroon-800">
                        <span x-show="type === 'loose'">From </span>₹<span x-text="previewPrice() || 0"></span>
                    </span>
                </p>
                <template x-if="!outOfStock">
                    <div class="w-8 h-8 rounded-full bg-maroon-800 text-cream flex items-center justify-center shrink-0 shadow pointer-events-none">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                    </div>
                </template>
                <template x-if="outOfStock">
                    <span class="text-[10px] font-bold uppercase tracking-wide text-red-600 bg-red-50 border border-red-200 rounded-full px-2.5 py-1.5 shrink-0 whitespace-nowrap">
                        Out of Stock
                    </span>
                </template>
            </div>
        </div>
        <p class="text-[11px] text-maroon-400 mt-2 text-center">No rating shown yet — a new product has no reviews.</p>
    </div>
</div>
