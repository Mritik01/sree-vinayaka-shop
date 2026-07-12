import './bootstrap';
import Alpine from 'alpinejs';
import confetti from 'canvas-confetti';

window.heroSlider = function (slideCount) {
    return {
        active: 0,
        slides: slideCount,
        autoplayMs: 5000,
        timer: null,
        paused: false,

        init() {
            this.startAutoplay();
        },
        startAutoplay() {
            this.timer = setInterval(() => {
                if (!this.paused) this.next();
            }, this.autoplayMs);
        },
        next() {
            this.active = (this.active + 1) % this.slides;
        },
        prev() {
            this.active = (this.active - 1 + this.slides) % this.slides;
        },
        goTo(i) {
            this.active = i;
        },
    };
};

window.promoPopup = function () {
    return {
        promoOpen: false,
        step: 'form',
        name: '',
        phone: '',
        otp: '',
        error: '',
        loading: false,
        resendCooldown: 0,
        resendTimer: null,
        devOtp: '',

        init() {
            if (!localStorage.getItem('mb_promo_dismissed')) {
                setTimeout(() => { this.promoOpen = true; }, 1400);
            }
        },
        dismiss() {
            this.promoOpen = false;
            localStorage.setItem('mb_promo_dismissed', '1');
            this.clearCooldown();
            this.devOtp = '';
        },
        clearCooldown() {
            if (this.resendTimer) {
                clearInterval(this.resendTimer);
                this.resendTimer = null;
            }
        },
        startCooldown(seconds) {
            this.clearCooldown();
            this.resendCooldown = seconds;
            this.resendTimer = setInterval(() => {
                this.resendCooldown--;
                if (this.resendCooldown <= 0) this.clearCooldown();
            }, 1000);
        },
        csrfToken() {
            return document.querySelector('meta[name=csrf-token]').content;
        },
        async postJson(url, payload) {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken(),
                },
                body: JSON.stringify(payload),
            });
            const data = await res.json().catch(() => ({}));
            return { ok: res.ok, status: res.status, data };
        },
        async sendOtp() {
            this.error = '';
            this.loading = true;
            try {
                const { ok, data } = await this.postJson('/promo/send-otp', {
                    name: this.name,
                    phone: this.phone,
                });
                if (ok && data.ok) {
                    this.step = 'otp';
                    this.otp = '';
                    this.devOtp = data.dev_otp || '';
                    this.startCooldown(data.resend_after || 45);
                } else {
                    this.error = data.message || 'Something went wrong, please try again.';
                    if (data.retry_after) this.startCooldown(data.retry_after);
                }
            } catch (e) {
                this.error = 'Network error, please check your connection and try again.';
            } finally {
                this.loading = false;
            }
        },
        async resendOtp() {
            if (this.resendCooldown > 0 || this.loading) return;
            await this.sendOtp();
        },
        async verifyOtp() {
            this.error = '';
            this.loading = true;
            try {
                const { ok, data } = await this.postJson('/promo/verify-otp', {
                    phone: this.phone,
                    otp: this.otp,
                });
                if (ok && data.ok) {
                    this.step = 'sent';
                    this.clearCooldown();
                    setTimeout(() => this.dismiss(), 1800);
                } else {
                    this.error = data.message || 'Incorrect OTP, please try again.';
                }
            } catch (e) {
                this.error = 'Network error, please check your connection and try again.';
            } finally {
                this.loading = false;
            }
        },
    };
};

window.authModal = function () {
    return {
        step: 'form',
        name: '',
        phone: '',
        otp: '',
        error: '',
        loading: false,
        resendCooldown: 0,
        resendTimer: null,
        devOtp: '',

        init() {
            this.$watch('authOpen', (isOpen) => {
                if (!isOpen) this.resetState();
            });
        },
        resetState() {
            this.step = 'form';
            this.otp = '';
            this.error = '';
            this.loading = false;
            this.devOtp = '';
            this.clearCooldown();
        },
        clearCooldown() {
            if (this.resendTimer) {
                clearInterval(this.resendTimer);
                this.resendTimer = null;
            }
            this.resendCooldown = 0;
        },
        startCooldown(seconds) {
            this.clearCooldown();
            this.resendCooldown = seconds;
            this.resendTimer = setInterval(() => {
                this.resendCooldown--;
                if (this.resendCooldown <= 0) this.clearCooldown();
            }, 1000);
        },
        csrfToken() {
            return document.querySelector('meta[name=csrf-token]').content;
        },
        async postJson(url, payload) {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken(),
                },
                body: JSON.stringify(payload),
            });
            const data = await res.json().catch(() => ({}));
            return { ok: res.ok, status: res.status, data };
        },
        async sendOtp() {
            this.error = '';
            this.loading = true;
            try {
                const { ok, data } = await this.postJson('/auth/send-otp', {
                    name: this.name,
                    phone: this.phone,
                });
                if (ok && data.ok) {
                    this.step = 'otp';
                    this.otp = '';
                    this.devOtp = data.dev_otp || '';
                    this.startCooldown(data.resend_after || 45);
                } else {
                    this.error = data.message || 'Something went wrong, please try again.';
                    if (data.retry_after) this.startCooldown(data.retry_after);
                }
            } catch (e) {
                this.error = 'Network error, please check your connection and try again.';
            } finally {
                this.loading = false;
            }
        },
        async resendOtp() {
            if (this.resendCooldown > 0 || this.loading) return;
            await this.sendOtp();
        },
        async verifyOtp() {
            this.error = '';
            this.loading = true;
            try {
                const { ok, data } = await this.postJson('/auth/verify-otp', {
                    phone: this.phone,
                    otp: this.otp,
                });
                if (ok && data.ok) {
                    window.location.reload();
                } else {
                    this.error = data.message || 'Incorrect OTP, please try again.';
                }
            } catch (e) {
                this.error = 'Network error, please check your connection and try again.';
            } finally {
                this.loading = false;
            }
        },
    };
};

// Shared by productSlider() and favoritesList() — persists a favorite toggle to
// the server (POST /favorites/{id}/toggle). Logged-out visitors get prompted to
// log in instead of silently no-op'ing, since favorites are now tied to accounts.
async function persistFavoriteToggle(id) {
    const csrf = document.querySelector('meta[name=csrf-token]').content;
    const res = await fetch(`/favorites/${id}/toggle`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrf,
        },
    });
    if (!res.ok) throw new Error('favorite toggle failed');
    return res.json();
}

// grams -> "250g" / "1kg" — mirrors Product::portionLabel() on the PHP side; keep the two
// in sync if the format ever changes
window.portionLabel = function (grams) {
    if (!grams) return '';
    return grams >= 1000 ? (grams / 1000).toFixed(2).replace(/0+$/, '').replace(/\.$/, '') + 'kg' : grams + 'g';
};

// Global helper so any Add-to-Cart button (product cards, product page) can call it
// directly from a Blade `@click` without needing cart state threaded into every
// Alpine scope. Guests get the login-modal bridge, matching favorites/reviews.
// On success it broadcasts `cart-updated` so the navbar badge stays live everywhere.
// `portion` (grams) is only relevant for loose products — omit/null for piece products.
window.addProductToCart = async function (productId, quantity = 1, openDrawer = true, portion = null) {
    if (!window.__mbIsLoggedIn) {
        window.dispatchEvent(new CustomEvent('open-auth-modal'));
        return;
    }
    const csrf = document.querySelector('meta[name=csrf-token]').content;
    const body = portion ? { quantity, portion } : { quantity };
    const res = await fetch(`/cart/${productId}/add`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrf,
        },
        body: JSON.stringify(body),
    });
    const data = await res.json().catch(() => ({}));
    if (res.ok && data.ok) {
        window.dispatchEvent(new CustomEvent('cart-updated', { detail: { count: data.count, items: data.items } }));
        if (openDrawer) Alpine.store('cart').open = true;
    }
    return data;
};

// "Order Now" on a product card — jumps straight to checkout for just this one product,
// via ?buy_now=<id>&qty=<n>. Never touches the cart (nothing is added, nothing is read from
// it) — checkout.show() builds the order summary purely from the query string.
window.orderNow = function (productId, quantity = 1, portion = null) {
    if (!window.__mbIsLoggedIn) {
        window.dispatchEvent(new CustomEvent('open-auth-modal'));
        return;
    }
    const suffix = portion ? `&portion=${portion}` : '';
    window.location.href = `/checkout?buy_now=${productId}&qty=${quantity}${suffix}`;
};

window.productSlider = function (isLoggedIn, initialFavorites, bucketCounts) {
    return {
        isLoggedIn,
        favorites: initialFavorites || [],
        bucketCounts: bucketCounts || {},
        activeCategory: 'all',
        setWidths: {},
        loopTimer: null,

        init() {
            // Every category (plus "all") has its own pre-rendered 3x tripled
            // (clone-real-clone) track in the Blade template, toggled via x-show.
            // Only the active track is measured/scrolled at a time — measuring a
            // display:none track would report scrollWidth 0, so each track is
            // (re)initialized lazily, right after it becomes visible.
            this.$nextTick(() => this.initTrack('all'));
        },
        slug(cat) {
            return cat.toLowerCase().replace(/\s+/g, '-');
        },
        activeTrack() {
            return this.$refs['track_' + this.slug(this.activeCategory)];
        },
        initTrack(cat) {
            const track = this.$refs['track_' + this.slug(cat)];
            if (!track || track.children.length === 0) return;
            const setWidth = track.scrollWidth / 3;
            this.setWidths[cat] = setWidth;
            track.scrollLeft = setWidth;
            if (!track.dataset.loopBound) {
                track.dataset.loopBound = '1';
                track.addEventListener('scroll', () => this.handleLoopScroll(cat), { passive: true });
            }
        },
        setCategory(cat) {
            this.activeCategory = cat;
            this.$nextTick(() => {
                this.initTrack(cat);
                document.getElementById('bestsellers')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        },
        handleLoopScroll(cat) {
            clearTimeout(this.loopTimer);
            this.loopTimer = setTimeout(() => {
                const track = this.$refs['track_' + this.slug(cat)];
                const setWidth = this.setWidths[cat];
                if (!track || !setWidth) return;
                if (track.scrollLeft < setWidth) {
                    track.scrollLeft += setWidth;
                } else if (track.scrollLeft >= setWidth * 2) {
                    track.scrollLeft -= setWidth;
                }
            }, 120);
        },
        isFavorited(id) {
            return this.favorites.includes(id);
        },
        async toggleFavorite(id) {
            if (!this.isLoggedIn) {
                window.dispatchEvent(new CustomEvent('open-auth-modal'));
                return;
            }
            const wasFavorited = this.isFavorited(id);
            this.favorites = wasFavorited ? this.favorites.filter((f) => f !== id) : [...this.favorites, id];
            try {
                await persistFavoriteToggle(id);
            } catch (e) {
                this.favorites = wasFavorited ? [...this.favorites, id] : this.favorites.filter((f) => f !== id);
            }
        },
        scrollTrack(direction) {
            const track = this.activeTrack();
            if (!track) return;
            track.scrollBy({ left: direction * track.clientWidth * 0.8, behavior: 'smooth' });
        },
    };
};

// /products listing page — client-side filtering/sorting over server-rendered cards.
// Cards are hidden with x-show and reordered via the CSS `order` property, so no DOM
// is rebuilt and the Blade product-card partial stays the single source of card markup.
window.productListing = function (isLoggedIn, initialFavorites, products, priceBuckets) {
    return {
        isLoggedIn,
        favorites: initialFavorites || [],
        products: products || [],
        priceBuckets: priceBuckets || [],
        filters: { categories: [], prices: [], q: '' },
        sort: 'recommended',
        sheetOpen: false,

        matches(p) {
            if (this.filters.categories.length && !this.filters.categories.includes(p.category)) return false;
            if (this.filters.prices.length) {
                const inAny = this.filters.prices.some((i) => {
                    const b = this.priceBuckets[parseInt(i, 10)];
                    return b && p.price >= b.min && (b.max === null || p.price < b.max);
                });
                if (!inAny) return false;
            }
            const q = this.filters.q.trim().toLowerCase();
            if (q && !p.name.toLowerCase().includes(q)) return false;
            return true;
        },
        visible(id) {
            const p = this.products.find((x) => x.id === id);
            return p ? this.matches(p) : true;
        },
        sorted() {
            const arr = [...this.products];
            switch (this.sort) {
                case 'price_asc': arr.sort((a, b) => a.price - b.price); break;
                case 'price_desc': arr.sort((a, b) => b.price - a.price); break;
                case 'rating': arr.sort((a, b) => b.rating - a.rating || b.reviews - a.reviews); break;
                case 'name': arr.sort((a, b) => a.name.localeCompare(b.name)); break;
            }
            return arr;
        },
        orderOf(id) {
            return this.sorted().findIndex((p) => p.id === id);
        },
        visibleCount() {
            return this.products.filter((p) => this.matches(p)).length;
        },
        categoryCount(cat) {
            return this.products.filter((p) => p.category === cat).length;
        },
        activeCount() {
            return this.filters.categories.length + this.filters.prices.length + (this.filters.q.trim() ? 1 : 0);
        },
        clearAll() {
            this.filters = { categories: [], prices: [], q: '' };
        },
        isFavorited(id) {
            return this.favorites.includes(id);
        },
        async toggleFavorite(id) {
            if (!this.isLoggedIn) {
                window.dispatchEvent(new CustomEvent('open-auth-modal'));
                return;
            }
            const wasFavorited = this.isFavorited(id);
            this.favorites = wasFavorited ? this.favorites.filter((f) => f !== id) : [...this.favorites, id];
            try {
                await persistFavoriteToggle(id);
            } catch (e) {
                this.favorites = wasFavorited ? [...this.favorites, id] : this.favorites.filter((f) => f !== id);
            }
        },
    };
};

window.favoritesList = function (isLoggedIn, initialFavorites) {
    return {
        isLoggedIn,
        favorites: initialFavorites || [],

        isFavorited(id) {
            return this.favorites.includes(id);
        },
        async toggleFavorite(id) {
            if (!this.isLoggedIn) {
                window.dispatchEvent(new CustomEvent('open-auth-modal'));
                return;
            }
            const wasFavorited = this.isFavorited(id);
            this.favorites = wasFavorited ? this.favorites.filter((f) => f !== id) : [...this.favorites, id];
            try {
                await persistFavoriteToggle(id);
            } catch (e) {
                this.favorites = wasFavorited ? [...this.favorites, id] : this.favorites.filter((f) => f !== id);
            }
        },
    };
};

window.productPage = function (isLoggedIn, initialFavorited, productId, isLoose = false, basePrice = 0, portions = [], hasDiscount = false, originalBasePrice = 0) {
    return {
        isLoggedIn,
        favorited: initialFavorited,
        justFavorited: false,
        quantity: 1,
        showStickyBar: false,
        addingToCart: false,
        justAddedToCart: false,

        isLoose,
        basePrice,
        hasDiscount,
        originalBasePrice,
        portionOptions: (portions || []).map((g) => ({ grams: g, label: window.portionLabel(g) })),
        selectedPortion: isLoose ? Math.min(...(portions && portions.length ? portions : [250])) : null,

        unitPrice() {
            if (!this.isLoose) return this.basePrice;
            return Math.round(this.basePrice * (this.selectedPortion / 250));
        },
        originalUnitPrice() {
            if (!this.isLoose) return this.originalBasePrice;
            return Math.round(this.originalBasePrice * (this.selectedPortion / 250));
        },

        init() {
            const onScroll = () => {
                this.showStickyBar = window.scrollY > 480;
            };
            window.addEventListener('scroll', onScroll, { passive: true });
            onScroll();
        },
        increment() {
            if (this.quantity < 10) this.quantity++;
        },
        decrement() {
            if (this.quantity > 1) this.quantity--;
        },
        async toggleFavorite() {
            if (!this.isLoggedIn) {
                window.dispatchEvent(new CustomEvent('open-auth-modal'));
                return;
            }
            const wasFavorited = this.favorited;
            this.favorited = !wasFavorited;
            if (this.favorited) {
                this.justFavorited = true;
                setTimeout(() => { this.justFavorited = false; }, 500);
            }
            try {
                await persistFavoriteToggle(productId);
            } catch (e) {
                this.favorited = wasFavorited;
            }
        },
        async addToCart() {
            if (!this.isLoggedIn) {
                window.dispatchEvent(new CustomEvent('open-auth-modal'));
                return;
            }
            this.addingToCart = true;
            try {
                const data = await window.addProductToCart(productId, this.quantity, true, this.selectedPortion);
                if (data && data.ok) {
                    this.justAddedToCart = true;
                    setTimeout(() => { this.justAddedToCart = false; }, 2000);
                }
            } finally {
                this.addingToCart = false;
            }
        },
    };
};

window.reviewForm = function (productSlug, isLoggedIn, initialRating, initialComment) {
    return {
        isLoggedIn,
        rating: initialRating || 0,
        hoverRating: 0,
        justRatedIndex: null,
        comment: initialComment || '',
        hasReviewed: (initialRating || 0) > 0,
        submitting: false,
        justSubmitted: false,
        error: '',
        photoFiles: [],
        photoPreviews: [],

        showBurst: false,

        addPhotos(event) {
            if (!this.isLoggedIn) {
                window.dispatchEvent(new CustomEvent('open-auth-modal'));
                event.target.value = '';
                return;
            }
            for (const file of event.target.files) {
                if (this.photoFiles.length >= 3) {
                    this.error = 'You can attach up to 3 photos.';
                    break;
                }
                if (file.size > 2 * 1024 * 1024) {
                    this.error = 'Each photo must be under 2 MB.';
                    continue;
                }
                this.error = '';
                this.photoFiles.push(file);
                this.photoPreviews.push(URL.createObjectURL(file));
            }
            event.target.value = '';
        },
        removePhoto(index) {
            URL.revokeObjectURL(this.photoPreviews[index]);
            this.photoFiles.splice(index, 1);
            this.photoPreviews.splice(index, 1);
        },

        setRating(i) {
            if (!this.isLoggedIn) {
                window.dispatchEvent(new CustomEvent('open-auth-modal'));
                return;
            }
            this.rating = i;
            this.justRatedIndex = i;
            setTimeout(() => {
                if (this.justRatedIndex === i) this.justRatedIndex = null;
            }, 450);
            if (i === 5) {
                // remount the sparkle layer so the burst replays on every 5-star pick
                this.showBurst = false;
                this.$nextTick(() => { this.showBurst = true; });
                setTimeout(() => { this.showBurst = false; }, 1100);
            }
        },
        async submit() {
            if (!this.isLoggedIn) {
                window.dispatchEvent(new CustomEvent('open-auth-modal'));
                return;
            }
            if (this.rating < 1) {
                this.error = 'Please select a star rating.';
                return;
            }
            this.error = '';
            this.submitting = true;
            try {
                const csrf = document.querySelector('meta[name=csrf-token]').content;
                // multipart so photo files can ride along; the browser sets the boundary header
                const form = new FormData();
                form.append('rating', this.rating);
                form.append('comment', this.comment);
                this.photoFiles.forEach((file) => form.append('photos[]', file));
                const res = await fetch(`/product/${productSlug}/reviews`, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: form,
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok || !data.ok) {
                    throw new Error(data.message || 'Something went wrong, please try again.');
                }
                this.hasReviewed = true;
                this.justSubmitted = true;
                this.photoPreviews.forEach((src) => URL.revokeObjectURL(src));
                this.photoFiles = [];
                this.photoPreviews = [];
                setTimeout(() => { this.justSubmitted = false; }, 2500);
                window.dispatchEvent(new CustomEvent('review-submitted', { detail: data }));
            } catch (e) {
                this.error = e.message || 'Network error, please try again.';
            } finally {
                this.submitting = false;
            }
        },
    };
};

window.reviewsList = function (initialReviews, initialAverage, initialCount) {
    return {
        reviews: initialReviews || [],
        average: initialAverage || 0,
        count: initialCount || 0,
        barsShown: false,
        lightbox: null,

        init() {
            window.addEventListener('review-submitted', (e) => {
                const { review, average, count } = e.detail;
                this.reviews = this.reviews.filter((r) => r.user_id !== review.user_id);
                this.reviews.unshift(review);
                this.average = average;
                this.count = count;
            });
            // grow the distribution bars only once the section scrolls into view
            const observer = new IntersectionObserver((entries) => {
                if (entries.some((entry) => entry.isIntersecting)) {
                    this.barsShown = true;
                    observer.disconnect();
                }
            }, { threshold: 0.25 });
            observer.observe(this.$el);
        },
        countFor(star) {
            return this.reviews.filter((r) => r.rating === star).length;
        },
        percentFor(star) {
            return this.reviews.length ? Math.round((this.countFor(star) / this.reviews.length) * 100) : 0;
        },
    };
};

window.cartPage = function (initialItems) {
    return {
        items: initialItems || [],

        subtotal() {
            return this.items.reduce((sum, item) => sum + item.price * item.quantity, 0);
        },
        async increment(item) {
            if (item.quantity >= 10) return;
            item.quantity++;
            await this.syncQuantity(item);
        },
        async decrement(item) {
            if (item.quantity <= 1) return;
            item.quantity--;
            await this.syncQuantity(item);
        },
        async syncQuantity(item) {
            const csrf = document.querySelector('meta[name=csrf-token]').content;
            const res = await fetch(`/cart/${item.id}`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({ quantity: item.quantity }),
            });
            const data = await res.json().catch(() => ({}));
            if (data.ok) window.dispatchEvent(new CustomEvent('cart-updated', { detail: { count: data.count, items: data.items } }));
        },
        async remove(item) {
            const csrf = document.querySelector('meta[name=csrf-token]').content;
            const res = await fetch(`/cart/${item.id}`, {
                method: 'DELETE',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
            });
            const data = await res.json().catch(() => ({}));
            this.items = this.items.filter((i) => i.id !== item.id);
            if (data.ok) window.dispatchEvent(new CustomEvent('cart-updated', { detail: { count: data.count, items: data.items } }));
        },
        async changePortion(item, newPortion) {
            const csrf = document.querySelector('meta[name=csrf-token]').content;
            const res = await fetch(`/cart/${item.id}`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({ quantity: item.quantity, portion: newPortion }),
            });
            const data = await res.json().catch(() => ({}));
            if (data.ok) {
                const updated = data.items.find((i) => i.id === item.id);
                if (updated) {
                    item.portion = updated.portion;
                    item.price = updated.price;
                }
                window.dispatchEvent(new CustomEvent('cart-updated', { detail: { count: data.count, items: data.items } }));
            }
        },
    };
};

window.checkoutPage = function (initialItems, initialCoupon, initialAddresses, defaultName, defaultPhone, initialReward, buyNowProductId, buyNowQuantity, initialAvailableCoupons, buyNowPortion) {
    return {
        items: initialItems || [],
        buyNowProductId: buyNowProductId || null,
        buyNowQuantity: buyNowQuantity || null,
        buyNowPortion: buyNowPortion || null,
        coupon: initialCoupon || null,
        availableCoupons: initialAvailableCoupons || [],
        appliedFromAvailable: null,
        couponCode: '',
        couponError: '',
        applyingCoupon: false,

        reward: initialReward || { configured: false, required: 0, progress: 0, available: 0, gift_label: '' },
        claimGift: false,

        addresses: initialAddresses || [],
        selectedAddressId: (initialAddresses || []).find((a) => a.is_default)?.id ?? (initialAddresses || [])[0]?.id ?? null,
        showNewAddressForm: (initialAddresses || []).length === 0,
        newAddressLabel: '',
        newAddressText: '',
        saveNewAddress: true,

        customerName: defaultName || '',
        customerPhone: defaultPhone || '',

        checkoutError: '',
        checkingOut: false,
        locationStatus: 'idle', // idle | checking | inside | outside | denied
        locationDistanceKm: null,
        cachedCoords: null,
        lastRestricted: null,

        get selectedAddress() {
            return this.addresses.find((a) => a.id === this.selectedAddressId) || null;
        },

        subtotal() {
            return this.items.reduce((sum, item) => sum + item.price * item.quantity, 0);
        },
        discount() {
            if (!this.coupon) return 0;
            const subtotal = this.subtotal();
            const raw = this.coupon.discount_type === 'percent'
                ? Math.round((subtotal * this.coupon.discount_value) / 100)
                : this.coupon.discount_value;
            return Math.min(raw, subtotal);
        },
        total() {
            return this.subtotal() - this.discount();
        },
        async applyCoupon(codeOverride = null) {
            const code = codeOverride || this.couponCode;
            if (!code.trim() || this.applyingCoupon) return;
            this.applyingCoupon = true;
            this.couponError = '';
            try {
                const csrf = document.querySelector('meta[name=csrf-token]').content;
                const body = { code };
                if (this.buyNowProductId) {
                    body.buy_now_product_id = this.buyNowProductId;
                    body.buy_now_quantity = this.buyNowQuantity;
                    body.buy_now_portion = this.buyNowPortion;
                }
                const res = await fetch('/coupon/apply', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: JSON.stringify(body),
                });
                const data = await res.json().catch(() => ({}));
                if (data.ok) {
                    this.coupon = data.coupon;
                    this.couponCode = '';
                    // stash the offer card so removing (not redeeming) the coupon can bring it back
                    this.appliedFromAvailable = this.availableCoupons.find((c) => c.code === data.coupon.code) || null;
                    this.availableCoupons = this.availableCoupons.filter((c) => c.code !== data.coupon.code);
                    this.fireConfetti();
                } else {
                    this.couponError = data.message || 'Could not apply this coupon.';
                }
            } catch (e) {
                this.couponError = 'Network error, please try again.';
            } finally {
                this.applyingCoupon = false;
            }
        },
        async removeCoupon() {
            const csrf = document.querySelector('meta[name=csrf-token]').content;
            await fetch('/coupon/remove', {
                method: 'DELETE',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
            });
            // removing just un-applies it — it's only actually spent once an order is placed
            // with it, so if it came from the offer list it belongs back there
            if (this.appliedFromAvailable) {
                this.availableCoupons.push(this.appliedFromAvailable);
                this.appliedFromAvailable = null;
            }
            this.coupon = null;
            this.couponError = '';
        },
        fireConfetti() {
            const colors = ['#d4a940', '#c8962e', '#8a1c2b', '#6b1420', '#fdf6e3'];
            confetti({ particleCount: 90, spread: 75, origin: { x: 0.5, y: 0.6 }, colors });
            setTimeout(() => confetti({ particleCount: 55, angle: 60, spread: 60, origin: { x: 0, y: 0.7 }, colors }), 150);
            setTimeout(() => confetti({ particleCount: 55, angle: 120, spread: 60, origin: { x: 1, y: 0.7 }, colors }), 300);
        },

        selectAddress(addr) {
            this.selectedAddressId = addr.id;
            this.showNewAddressForm = false;
            this.checkoutError = '';
        },
        openNewAddressForm() {
            this.selectedAddressId = null;
            this.showNewAddressForm = true;
        },
        async makeDefault(addr) {
            const csrf = document.querySelector('meta[name=csrf-token]').content;
            const res = await fetch(`/addresses/${addr.id}/default`, {
                method: 'PATCH',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
            });
            const data = await res.json().catch(() => ({}));
            if (data.ok) this.addresses.forEach((a) => { a.is_default = a.id === addr.id; });
        },
        async deleteAddress(addr) {
            const csrf = document.querySelector('meta[name=csrf-token]').content;
            const res = await fetch(`/addresses/${addr.id}`, {
                method: 'DELETE',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
            });
            const data = await res.json().catch(() => ({}));
            if (!data.ok) return;
            this.addresses = this.addresses.filter((a) => a.id !== addr.id);
            if (this.selectedAddressId === addr.id) {
                this.selectedAddressId = this.addresses[0]?.id ?? null;
                if (!this.addresses.length) this.showNewAddressForm = true;
            }
        },

        init() {
            this.lastRestricted = Alpine.store('shop').restricted;
            if (this.lastRestricted && this.showNewAddressForm) this.checkDeliveryArea(true);
            // reacts live if the admin flips the delivery-area toggle while this page is open
            Alpine.effect(() => {
                const restricted = Alpine.store('shop').restricted;
                if (restricted !== this.lastRestricted) {
                    this.lastRestricted = restricted;
                    if (restricted) {
                        if (this.showNewAddressForm) this.checkDeliveryArea(true);
                    } else {
                        this.locationStatus = 'idle';
                        this.locationDistanceKm = null;
                        this.checkoutError = '';
                    }
                }
            });
        },
        async checkDeliveryArea(auto = false) {
            if (!Alpine.store('shop').restricted || this.locationStatus === 'checking') return;
            if (auto) {
                // on auto-runs only proceed silently if permission was already granted,
                // so the page doesn't ambush visitors with a permission prompt
                try {
                    const perm = await navigator.permissions.query({ name: 'geolocation' });
                    if (perm.state !== 'granted') return;
                } catch (e) {
                    return;
                }
            }
            this.locationStatus = 'checking';
            try {
                const coords = await this.currentLocation();
                this.cachedCoords = coords;
                const res = await fetch(`/delivery-check?lat=${coords.latitude}&lng=${coords.longitude}`, {
                    headers: { Accept: 'application/json' },
                });
                const data = await res.json();
                if (data.ok) {
                    this.locationDistanceKm = Number(data.distance_km).toFixed(1);
                    this.locationStatus = data.within ? 'inside' : 'outside';
                } else {
                    this.locationStatus = 'idle';
                }
            } catch (e) {
                this.locationStatus = 'denied';
            }
        },
        // when delivery isn't radius-restricted, sharing location is purely a convenience for
        // the delivery rider — never blocks checkout, unlike checkDeliveryArea() above
        async captureLocationOptional() {
            if (this.locationStatus === 'checking') return;
            this.locationStatus = 'checking';
            try {
                this.cachedCoords = await this.currentLocation();
                this.locationStatus = 'captured';
            } catch (e) {
                this.locationStatus = 'denied';
            }
        },
        async currentLocation() {
            return new Promise((resolve, reject) => {
                if (!navigator.geolocation) {
                    reject(new Error('unsupported'));
                    return;
                }
                navigator.geolocation.getCurrentPosition(
                    (position) => resolve({ latitude: position.coords.latitude, longitude: position.coords.longitude }),
                    () => reject(new Error('denied')),
                    { enableHighAccuracy: true, timeout: 12000, maximumAge: 60000 }
                );
            });
        },
        async checkout() {
            if (this.checkingOut || this.items.length === 0) return;
            if (!this.customerName.trim()) {
                this.checkoutError = "Please enter the recipient's name.";
                return;
            }

            this.checkingOut = true;
            this.checkoutError = '';

            const payload = {
                customer_name: this.customerName.trim(),
                customer_phone: this.customerPhone.trim(),
                claim_gift: this.claimGift && this.reward.available > 0,
            };

            if (this.buyNowProductId) {
                payload.buy_now_product_id = this.buyNowProductId;
                payload.buy_now_quantity = this.buyNowQuantity;
                payload.buy_now_portion = this.buyNowPortion;
            }

            if (this.showNewAddressForm) {
                if (this.newAddressText.trim().length < 10) {
                    this.checkoutError = 'Please enter a complete delivery address.';
                    this.checkingOut = false;
                    return;
                }
                payload.delivery_address = this.newAddressText;
                payload.save_address = this.saveNewAddress;
                if (this.newAddressLabel.trim()) payload.address_label = this.newAddressLabel.trim();

                if (Alpine.store('shop').restricted) {
                    try {
                        const coords = this.cachedCoords || await this.currentLocation();
                        this.cachedCoords = coords;
                        Object.assign(payload, coords);
                    } catch (e) {
                        this.locationStatus = 'denied';
                        this.checkoutError = 'Please allow location access in your browser so we can confirm you are inside our delivery area.';
                        this.checkingOut = false;
                        return;
                    }
                } else if (this.cachedCoords) {
                    // optional share, e.g. via captureLocationOptional() — never required to check out
                    Object.assign(payload, this.cachedCoords);
                }
            } else {
                if (!this.selectedAddressId) {
                    this.checkoutError = 'Please choose a delivery address.';
                    this.checkingOut = false;
                    return;
                }
                payload.address_id = this.selectedAddressId;
            }

            try {
                const csrf = document.querySelector('meta[name=csrf-token]').content;
                const res = await fetch('/checkout', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: JSON.stringify(payload),
                });
                const data = await res.json().catch(() => ({}));
                if (data.ok) {
                    window.dispatchEvent(new CustomEvent('cart-updated', { detail: { count: 0 } }));
                    // hand off to the live tracking page — checkingOut stays true so the
                    // button can't be clicked again while the browser navigates away
                    window.location.assign(`/orders/${data.order_id}?placed=1`);
                    return;
                }
                this.checkoutError = data.message || (data.errors ? Object.values(data.errors)[0][0] : 'Could not place the order, please try again.');
                this.checkingOut = false;
            } catch (e) {
                this.checkoutError = 'Network error, please try again.';
                this.checkingOut = false;
            }
        },
    };
};

// live order-tracking page — polls the order's status so admin-side updates
// (confirmed / out for delivery / delivered) animate in without a reload
window.orderTrackingPage = function (initialOrder, justPlaced) {
    return {
        order: initialOrder,
        now: Date.now(),
        noteDraft: initialOrder.customer_note || '',
        savingNote: false,
        noteSaved: false,
        noteError: '',
        confirmingCancel: false,
        cancelling: false,
        cancelError: '',

        init() {
            if (justPlaced) {
                setTimeout(() => this.celebrate(), 350);
                // strip ?placed=1 so refreshing the page doesn't re-fire the celebration
                window.history.replaceState(null, '', `/orders/${this.order.id}`);
            }
            // stored on the element (not just closed over) so an external caller can clear them —
            // this component is also mounted/torn down dynamically (the account page's inline
            // "My Orders" detail view swaps orders without a page reload); see accountPage()'s
            // viewOrder()/backToOrders(), which clear these before tearing the element down, so
            // switching orders never piles up background polling
            this.$el._pollTimer = setInterval(() => this.poll(), 5000);
            this.$el._clockTimer = setInterval(() => { this.now = Date.now(); }, 15000);
        },

        rank() {
            return { pending: 1, confirmed: 2, out_for_delivery: 3, delivered: 4 }[this.order.status] || 0;
        },
        // timeline steps are 1-4 (Placed, Confirmed, On the way, Delivered)
        stepState(step) {
            if (this.order.status === 'cancelled') return step === 1 ? 'done' : 'todo';
            if (step <= this.rank()) return 'done';
            if (step === this.rank() + 1) return 'active';
            return 'todo';
        },
        stepTime(step) {
            return [null, this.order.placed_at, this.order.confirmed_at, this.order.out_for_delivery_at, this.order.delivered_at][step];
        },
        etaText() {
            if (!this.order.eta_ends_at) return null;
            const mins = Math.ceil((this.order.eta_ends_at - this.now) / 60000);
            return mins > 1 ? `in ~${mins} min` : 'any moment now';
        },

        async poll() {
            if (['delivered', 'cancelled'].includes(this.order.status)) return;
            try {
                const res = await fetch(`/orders/${this.order.id}/status`, { headers: { Accept: 'application/json' } });
                if (!res.ok) return;
                const data = await res.json().catch(() => ({}));
                if (!data.ok) return;
                const previous = this.order.status;
                this.order = data.order;
                this.now = Date.now();
                if (data.order.status !== previous) {
                    if (data.order.status === 'delivered') this.celebrate();
                    else if (data.order.status !== 'cancelled') this.cheer();
                }
            } catch (e) {
                // offline / server hiccup — next tick will catch up
            }
        },

        celebrate() {
            const colors = ['#d4a940', '#c8962e', '#8a1c2b', '#6b1420', '#fdf6e3'];
            confetti({ particleCount: 110, spread: 80, origin: { x: 0.5, y: 0.5 }, colors });
            setTimeout(() => confetti({ particleCount: 60, angle: 60, spread: 60, origin: { x: 0, y: 0.65 }, colors }), 180);
            setTimeout(() => confetti({ particleCount: 60, angle: 120, spread: 60, origin: { x: 1, y: 0.65 }, colors }), 330);
        },
        cheer() {
            confetti({ particleCount: 45, spread: 65, origin: { x: 0.5, y: 0.45 }, colors: ['#d4a940', '#3d7a52', '#fdf6e3'] });
        },

        async saveNote() {
            if (this.savingNote) return;
            this.savingNote = true;
            this.noteError = '';
            this.noteSaved = false;
            try {
                const csrf = document.querySelector('meta[name=csrf-token]').content;
                const res = await fetch(`/orders/${this.order.id}/note`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: JSON.stringify({ note: this.noteDraft }),
                });
                const data = await res.json().catch(() => ({}));
                if (data.ok) {
                    this.order.customer_note = data.note;
                    this.noteSaved = true;
                    setTimeout(() => { this.noteSaved = false; }, 2500);
                } else {
                    this.noteError = data.message || 'Could not save the note, please try again.';
                }
            } catch (e) {
                this.noteError = 'Network error, please try again.';
            } finally {
                this.savingNote = false;
            }
        },

        async cancelOrder() {
            if (this.cancelling) return;
            this.cancelling = true;
            this.cancelError = '';
            try {
                const csrf = document.querySelector('meta[name=csrf-token]').content;
                const res = await fetch(`/orders/${this.order.id}/cancel`, {
                    method: 'PATCH',
                    headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                });
                const data = await res.json().catch(() => ({}));
                if (data.ok) {
                    this.order = data.order;
                    this.confirmingCancel = false;
                } else {
                    this.cancelError = data.message || 'Could not cancel the order.';
                }
            } catch (e) {
                this.cancelError = 'Network error, please try again.';
            } finally {
                this.cancelling = false;
            }
        },
    };
};

// account page — sidebar tabs (Profile / Addresses / Rewards) plus a full address-book
// CRUD reusing the same /addresses endpoints the checkout page's address picker uses
window.accountPage = function (initialAddresses, initialReward, initialTab, initialFavorites) {
    return {
        tab: initialTab || 'profile',
        addresses: initialAddresses || [],
        reward: initialReward || { configured: false, required: 0, progress: 0, available: 0, gift_label: '' },

        showAddForm: false,
        editingId: null,
        saving: false,
        formError: '',
        form: { label: '', address_line: '' },

        // My Orders — viewing a single order renders inline (fetched as HTML and mounted into
        // $refs.orderDetailPanel) instead of navigating to a separate page
        viewingOrderId: null,
        loadingOrder: false,
        orderLoadError: '',

        // My Favorites — reused by partials/product-card.blade.php's heart button, same as
        // favoritesList()/productSlider() elsewhere on the site
        favorites: initialFavorites || [],

        init() {
            // stat cards count up from 0 on page load
            this.$nextTick(() => {
                this.$el.querySelectorAll('[data-countup]').forEach((el) => this.countUp(el, parseInt(el.dataset.countup, 10) || 0));
            });
        },

        isFavorited(id) {
            return this.favorites.includes(id);
        },
        async toggleFavorite(id) {
            const wasFavorited = this.isFavorited(id);
            this.favorites = wasFavorited ? this.favorites.filter((f) => f !== id) : [...this.favorites, id];
            try {
                await persistFavoriteToggle(id);
            } catch (e) {
                this.favorites = wasFavorited ? [...this.favorites, id] : this.favorites.filter((f) => f !== id);
            }
        },

        // the detail view is only hidden (x-show), not removed from the DOM, so the previously
        // injected order's poll/clock timers (see orderTrackingPage()) would otherwise keep
        // running in the background forever — clear them before tearing the tree down
        teardownOrderPanel(panel) {
            const root = panel?.firstElementChild;
            if (!root) return;
            clearInterval(root._pollTimer);
            clearInterval(root._clockTimer);
            Alpine.destroyTree(root);
            panel.innerHTML = '';
        },
        async viewOrder(id) {
            this.viewingOrderId = id;
            this.orderLoadError = '';
            this.loadingOrder = true;
            try {
                const res = await fetch(`/orders/${id}/partial`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                if (!res.ok) throw new Error('request failed');
                const html = await res.text();
                const panel = this.$refs.orderDetailPanel;
                this.teardownOrderPanel(panel);
                panel.innerHTML = html;
                Alpine.initTree(panel);
            } catch (e) {
                this.orderLoadError = "Couldn't load this order — please try again.";
            } finally {
                this.loadingOrder = false;
            }
        },
        backToOrders() {
            this.teardownOrderPanel(this.$refs.orderDetailPanel);
            this.viewingOrderId = null;
        },
        countUp(el, end) {
            const duration = 1000;
            const start = performance.now();
            const frame = (now) => {
                const progress = Math.min((now - start) / duration, 1);
                const eased = 1 - Math.pow(1 - progress, 3);
                el.textContent = Math.round(end * eased).toLocaleString('en-IN');
                if (progress < 1) requestAnimationFrame(frame);
            };
            requestAnimationFrame(frame);
        },

        resetForm() {
            this.editingId = null;
            this.formError = '';
            this.form = { label: '', address_line: '' };
        },
        startEdit(addr) {
            this.editingId = addr.id;
            this.form = { label: addr.label || '', address_line: addr.address_line };
            this.showAddForm = true;
        },
        async addAddress() {
            if (this.form.address_line.trim().length < 10) {
                this.formError = 'Please enter a complete address.';
                return;
            }
            this.saving = true;
            this.formError = '';
            try {
                const csrf = document.querySelector('meta[name=csrf-token]').content;
                const res = await fetch('/addresses', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify({ label: this.form.label.trim() || null, address_line: this.form.address_line.trim() }),
                });
                const data = await res.json().catch(() => ({}));
                if (data.ok) {
                    if (data.address.is_default) this.addresses.forEach((a) => { a.is_default = false; });
                    this.addresses.push(data.address);
                    this.showAddForm = false;
                    this.resetForm();
                } else {
                    this.formError = data.message || 'Could not save this address.';
                }
            } catch (e) {
                this.formError = 'Network error, please try again.';
            } finally {
                this.saving = false;
            }
        },
        async updateAddress() {
            if (this.form.address_line.trim().length < 10) {
                this.formError = 'Please enter a complete address.';
                return;
            }
            this.saving = true;
            this.formError = '';
            try {
                const csrf = document.querySelector('meta[name=csrf-token]').content;
                const res = await fetch(`/addresses/${this.editingId}`, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify({ label: this.form.label.trim() || null, address_line: this.form.address_line.trim() }),
                });
                const data = await res.json().catch(() => ({}));
                if (data.ok) {
                    const idx = this.addresses.findIndex((a) => a.id === this.editingId);
                    if (idx !== -1) this.addresses[idx] = data.address;
                    this.showAddForm = false;
                    this.resetForm();
                } else {
                    this.formError = data.message || 'Could not save this address.';
                }
            } catch (e) {
                this.formError = 'Network error, please try again.';
            } finally {
                this.saving = false;
            }
        },
        async makeDefault(addr) {
            const csrf = document.querySelector('meta[name=csrf-token]').content;
            const res = await fetch(`/addresses/${addr.id}/default`, {
                method: 'PATCH',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
            });
            const data = await res.json().catch(() => ({}));
            if (data.ok) this.addresses.forEach((a) => { a.is_default = a.id === addr.id; });
        },
        async deleteAddress(addr) {
            if (!confirm('Delete this address?')) return;
            const csrf = document.querySelector('meta[name=csrf-token]').content;
            const res = await fetch(`/addresses/${addr.id}`, {
                method: 'DELETE',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
            });
            const data = await res.json().catch(() => ({}));
            if (data.ok) this.addresses = this.addresses.filter((a) => a.id !== addr.id);
        },
    };
};

// navbar notifications bell — polls the user's notifications so an admin message
// shows up live while browsing; opening the dropdown marks everything read
window.notificationsBell = function () {
    return {
        open: false,
        unread: 0,
        notifications: [],
        timer: null,

        init() {
            this.refresh();
            this.timer = setInterval(() => this.refresh(), 15000);
        },
        async refresh() {
            try {
                const res = await fetch('/notifications', { headers: { Accept: 'application/json' } });
                if (!res.ok) return;
                const data = await res.json();
                this.unread = data.unread;
                this.notifications = data.notifications;
            } catch (e) {
                // network hiccup — keep whatever we had, next poll will recover
            }
        },
        toggle() {
            this.open = !this.open;
            if (this.open && this.unread > 0) this.markAllRead();
        },
        async markAllRead() {
            const csrf = document.querySelector('meta[name=csrf-token]').content;
            try {
                await fetch('/notifications/mark-read', {
                    method: 'POST',
                    headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                });
                this.unread = 0;
                // keep the "new" highlight visible while the dropdown is open;
                // items flip to read state on the next refresh
            } catch (e) {}
        },
        async clear(id) {
            const csrf = document.querySelector('meta[name=csrf-token]').content;
            this.notifications = this.notifications.filter((n) => n.id !== id);
            try {
                await fetch(`/notifications/${id}`, {
                    method: 'DELETE',
                    headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                });
            } catch (e) {}
        },
        async clearAll() {
            const csrf = document.querySelector('meta[name=csrf-token]').content;
            this.notifications = [];
            this.unread = 0;
            try {
                await fetch('/notifications/clear', {
                    method: 'DELETE',
                    headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                });
            } catch (e) {}
        },
    };
};

Alpine.store('shop', {
    accepting: (window.__mbShopStatus || {}).accepting !== false,
    restricted: (window.__mbShopStatus || {}).restricted === true,
    radiusKm: (window.__mbShopStatus || {}).radiusKm || 7,
    toast: null,
});

// backs the slide-out cart drawer (partials/cart-drawer.blade.php), mounted once in the
// shared layout. Kept in sync from any page via the global `cart-updated` event below,
// so it reflects whatever the navbar, the /cart page, or an Add-to-Cart click last did.
Alpine.store('cart', {
    items: window.__mbCartItems || [],
    count: (window.__mbCartItems || []).reduce((sum, item) => sum + item.quantity, 0),
    open: false,
    subtotal() {
        return this.items.reduce((sum, item) => sum + item.price * item.quantity, 0);
    },
    async increment(item) {
        if (item.quantity >= 10) return;
        item.quantity++;
        await this.sync(item);
    },
    async decrement(item) {
        if (item.quantity <= 1) return;
        item.quantity--;
        await this.sync(item);
    },
    async sync(item) {
        const csrf = document.querySelector('meta[name=csrf-token]').content;
        const res = await fetch(`/cart/${item.id}`, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify({ quantity: item.quantity }),
        });
        const data = await res.json().catch(() => ({}));
        if (data.ok) window.dispatchEvent(new CustomEvent('cart-updated', { detail: { count: data.count, items: data.items } }));
    },
    async remove(item) {
        const csrf = document.querySelector('meta[name=csrf-token]').content;
        const res = await fetch(`/cart/${item.id}`, {
            method: 'DELETE',
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
        });
        const data = await res.json().catch(() => ({}));
        this.items = this.items.filter((i) => i.id !== item.id);
        if (data.ok) window.dispatchEvent(new CustomEvent('cart-updated', { detail: { count: data.count, items: data.items } }));
    },
});
window.addEventListener('cart-updated', (e) => {
    const store = Alpine.store('cart');
    if (typeof e.detail.count === 'number') store.count = e.detail.count;
    if (Array.isArray(e.detail.items)) store.items = e.detail.items;
});

// polls the public shop-status endpoint so the accepting-orders banner/cart
// controls update live for anyone already browsing when the admin flips a toggle
let shopStatusToastTimer = null;
function showShopToast(store, message) {
    store.toast = message;
    clearTimeout(shopStatusToastTimer);
    shopStatusToastTimer = setTimeout(() => { store.toast = null; }, 6000);
}
function pollShopStatus() {
    fetch('/shop-status', { headers: { Accept: 'application/json' } })
        .then((res) => (res.ok ? res.json() : null))
        .then((data) => {
            if (!data) return;
            const store = Alpine.store('shop');
            store.radiusKm = data.delivery_radius_km || store.radiusKm;
            if (data.accepting_orders !== store.accepting) {
                store.accepting = data.accepting_orders;
                showShopToast(store, data.accepting_orders
                    ? '✅ Good news — we are accepting online orders again!'
                    : "⚠️ We've paused new online orders for now.");
            }
            if (data.restrict_delivery_area !== store.restricted) {
                store.restricted = data.restrict_delivery_area;
                showShopToast(store, data.restrict_delivery_area
                    ? `📍 Heads up — we now deliver only within ${store.radiusKm} km of Thuthibari.`
                    : '🚚 Delivery area restriction lifted — we accept orders from anywhere again.');
            }
        })
        .catch(() => {});
}
setInterval(pollShopStatus, 5000);

window.Alpine = Alpine;
Alpine.start();
