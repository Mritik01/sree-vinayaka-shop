import './bootstrap';
import Alpine from 'alpinejs';
import confetti from 'canvas-confetti';

// landing here after App\Http\Middleware\Authenticate::redirectTo() sent a guest to the
// homepage with ?login=1&redirect=... (there's no dedicated login page — auth is a modal on
// any page) — auto-open that modal, and remember where to send them after a successful login
// instead of just reloading the homepage they landed on. The redirect target is only ever
// trusted if it resolves to this same origin, so a crafted ?redirect=https://evil.example
// link can't be used to bounce a logged-in user off-site.
window.__mbLoginRedirect = null;
{
    const loginParams = new URLSearchParams(window.location.search);
    const redirectParam = loginParams.get('redirect');
    if (redirectParam) {
        try {
            const target = new URL(redirectParam, window.location.origin);
            if (target.origin === window.location.origin) {
                window.__mbLoginRedirect = target.pathname + target.search + target.hash;
            }
        } catch (e) {
            // malformed value — ignore, falls back to a plain reload after login
        }
    }
    if (loginParams.has('login')) {
        loginParams.delete('login');
        loginParams.delete('redirect');
        const cleanQuery = loginParams.toString();
        window.history.replaceState(null, '', window.location.pathname + (cleanQuery ? '?' + cleanQuery : '') + window.location.hash);
        document.addEventListener('alpine:initialized', () => window.dispatchEvent(new CustomEvent('open-auth-modal')));
    }
}

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

// Festival Special — auto-scrolls the product row on a timer (same setInterval shape as
// heroSlider above), advancing by one viewport-width "page" of cards at a time and looping
// back to the start with a smooth scroll once it reaches the end. Pauses on hover/touch so it
// doesn't fight a customer who's actively browsing.
window.festivalSpecialCarousel = function () {
    return {
        shown: false,
        paused: false,
        autoplayMs: 3200,
        timer: null,

        init() {
            const io = new IntersectionObserver((entries) => {
                if (entries[0].isIntersecting) {
                    this.shown = true;
                    this.startAutoplay();
                    io.disconnect();
                }
            }, { threshold: 0.1 });
            io.observe(this.$el);
        },
        startAutoplay() {
            this.timer = setInterval(() => {
                if (this.paused) return;
                const track = this.$refs.track;
                if (!track) return;
                const atEnd = track.scrollLeft + track.clientWidth >= track.scrollWidth - 4;
                track.scrollTo(atEnd
                    ? { left: 0, behavior: 'smooth' }
                    : { left: track.scrollLeft + track.clientWidth * 0.7, behavior: 'smooth' });
            }, this.autoplayMs);
        },
    };
};

// Announcement Banner — site-wide popup driven by whatever the admin configured
// (partials/announcement-banner.blade.php). Shares its inner markup and Alpine property
// names with the admin live-preview pane so both render identically.
window.announcementBanner = function (data) {
    return {
        open: false,
        headline: data.headline,
        description: data.description,
        buttonText: data.buttonText,
        buttonUrl: data.buttonUrl,
        image: data.image,
        showClose: data.showClose,
        bg: '',
        text: '',
        timer: null,

        init() {
            const presets = {
                maroon: ['#7a1622', '#fdf6e9'],
                gold: ['#c8962e', '#3a0b12'],
                pista: ['#3d7a52', '#fdf6e9'],
                dark: ['#241f1f', '#fdf6e9'],
            };
            if (data.theme === 'custom') {
                this.bg = data.backgroundColor || '#7a1622';
                this.text = data.textColor || '#fdf6e9';
            } else {
                const [bg, text] = presets[data.theme] || presets.maroon;
                this.bg = bg;
                this.text = text;
            }

            if (!this.shouldShow()) return;
            // small stagger so it doesn't visually collide with the promo popup's own 1400ms delay
            setTimeout(() => {
                this.open = true;
                this.markShown();
                if (data.autoCloseSeconds) {
                    this.timer = setTimeout(() => this.dismiss(), data.autoCloseSeconds * 1000);
                }
            }, 700);
        },
        shouldShow() {
            if (data.frequency === 'once_per_session') return !sessionStorage.getItem('mb_announcement_shown');
            if (data.frequency === 'once_per_day') {
                const last = parseInt(localStorage.getItem('mb_announcement_shown_at') || '0', 10);
                return Date.now() - last > 24 * 60 * 60 * 1000;
            }
            return true; // every_visit
        },
        markShown() {
            if (data.frequency === 'once_per_session') sessionStorage.setItem('mb_announcement_shown', '1');
            if (data.frequency === 'once_per_day') localStorage.setItem('mb_announcement_shown_at', String(Date.now()));
        },
        dismiss() {
            clearTimeout(this.timer);
            this.open = false;
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
        // phone → otp → name (new accounts only) → success
        step: 'phone',
        name: '',
        phone: '',
        otp: '',
        error: '',
        loading: false,
        resendCooldown: 0,
        resendTimer: null,
        devOtp: '',
        isNewUser: false,
        welcomeName: '',
        agreeTerms: false,
        nameSubmitted: false,

        init() {
            this.$watch('authOpen', (isOpen) => {
                if (!isOpen) this.resetState();
            });
        },
        resetState() {
            this.step = 'phone';
            this.name = '';
            this.otp = '';
            this.error = '';
            this.loading = false;
            this.devOtp = '';
            this.isNewUser = false;
            this.welcomeName = '';
            this.agreeTerms = false;
            this.nameSubmitted = false;
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
                const { ok, data } = await this.postJson('/auth/send-otp', { phone: this.phone });
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
        // OTP confirmed — an existing account logs straight in; a brand-new number needs
        // just one more step (name) before we can create the account
        async verifyOtp() {
            this.error = '';
            this.loading = true;
            try {
                const { ok, data } = await this.postJson('/auth/verify-otp', {
                    phone: this.phone,
                    otp: this.otp,
                });
                if (ok && data.ok) {
                    if (data.new_user) {
                        this.isNewUser = true;
                        this.step = 'name';
                    } else {
                        this.welcomeName = data.name || '';
                        this.celebrate(false);
                    }
                } else {
                    this.error = data.message || 'Incorrect OTP, please try again.';
                }
            } catch (e) {
                this.error = 'Network error, please check your connection and try again.';
            } finally {
                this.loading = false;
            }
        },
        async completeSignup() {
            this.nameSubmitted = true;
            if (!this.agreeTerms) return;
            this.error = '';
            this.loading = true;
            try {
                const { ok, data } = await this.postJson('/auth/complete-signup', {
                    name: this.name,
                    phone: this.phone,
                    agree_terms: this.agreeTerms,
                });
                if (ok && data.ok) {
                    this.celebrate(true);
                } else {
                    this.error = data.message || 'Something went wrong, please try again.';
                }
            } catch (e) {
                this.error = 'Network error, please check your connection and try again.';
            } finally {
                this.loading = false;
            }
        },
        // brand-new accounts get a bigger celebratory beat (confetti + a longer pause to enjoy
        // it); a returning login stays snappy — they've done this before, don't make them wait
        celebrate(isNew) {
            this.step = 'success';
            if (isNew) this.fireConfetti();
            // if they were bounced here from an auth-required page (see window.__mbLoginRedirect
            // above), continue on to it instead of just reloading whatever page the modal was on
            setTimeout(() => {
                if (window.__mbLoginRedirect) {
                    window.location.href = window.__mbLoginRedirect;
                } else {
                    window.location.reload();
                }
            }, isNew ? 1700 : 1000);
        },
        fireConfetti() {
            const colors = ['#d4a940', '#c8962e', '#8a1c2b', '#6b1420', '#fdf6e3'];
            confetti({ particleCount: 90, spread: 75, origin: { x: 0.5, y: 0.45 }, colors });
            setTimeout(() => confetti({ particleCount: 55, angle: 60, spread: 60, origin: { x: 0.15, y: 0.6 }, colors }), 150);
            setTimeout(() => confetti({ particleCount: 55, angle: 120, spread: 60, origin: { x: 0.85, y: 0.6 }, colors }), 300);
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

// fired once, the moment a cart/checkout subtotal crosses the free-delivery threshold (see
// cartPage()/checkoutPage() below) — shared so both pages celebrate identically. The
// 'minimal' admin-chosen style skips confetti and just lets the CSS banner speak for itself.
function celebrateFreeDelivery() {
    window.dispatchEvent(new CustomEvent('free-delivery-unlocked'));
    if (Alpine.store('shop').deliverySuccessAnimation === 'minimal') return;
    const colors = ['#7a1622', '#e9c873', '#3d7a52'];
    confetti({ particleCount: 90, spread: 75, origin: { x: 0.5, y: 0.5 }, colors });
    setTimeout(() => confetti({ particleCount: 50, angle: 60, spread: 60, origin: { x: 0.1, y: 0.65 }, colors }), 150);
    setTimeout(() => confetti({ particleCount: 50, angle: 120, spread: 60, origin: { x: 0.9, y: 0.65 }, colors }), 300);
}

// header live search (partials/header-search.blade.php) — debounced suggestions from
// /search-suggest, Enter/see-all lands on the Shop All page with the query pre-filled
window.headerSearch = function () {
    return {
        q: '',
        results: [],
        open: false,
        loading: false,

        async suggest() {
            const query = this.q.trim();
            if (query.length < 2) {
                this.results = [];
                this.open = false;
                return;
            }
            this.loading = true;
            try {
                const res = await fetch(`/search-suggest?q=${encodeURIComponent(query)}`, { headers: { Accept: 'application/json' } });
                const data = res.ok ? await res.json() : { products: [] };
                // ignore stale responses that come back after the user kept typing
                if (this.q.trim() === query) {
                    this.results = data.products || [];
                    this.open = true;
                }
            } catch (e) {
                this.results = [];
            } finally {
                this.loading = false;
            }
        },
        goToResults() {
            const query = this.q.trim();
            if (!query) return;
            window.location.href = `/products?q=${encodeURIComponent(query)}`;
        },
    };
};

// "Deliver to" address switcher in the header (partials/deliver-to.blade.php) — switching
// reuses the existing addresses.set-default endpoint, so checkout picks the same address up
window.deliverTo = function (addresses, isLoggedIn, labels = {}) {
    const current = (addresses || []).find((a) => a.is_default) || (addresses || [])[0] || null;

    return {
        addresses: addresses || [],
        isLoggedIn,
        open: false,
        currentId: current ? current.id : null,

        get currentLine() {
            const a = this.addresses.find((x) => x.id === this.currentId);
            if (a) return a.label ? `${a.label} — ${a.line}` : a.line;
            return this.isLoggedIn ? (labels.empty || 'Add address') : (labels.guest || 'Thuthibari & nearby');
        },
        handleEmpty() {
            if (!this.isLoggedIn) {
                window.dispatchEvent(new CustomEvent('open-auth-modal'));
                return;
            }
            window.location.href = '/account?tab=addresses';
        },
        async choose(address) {
            this.open = false;
            if (address.id === this.currentId) return;
            const previous = this.currentId;
            this.currentId = address.id;
            try {
                const csrf = document.querySelector('meta[name=csrf-token]').content;
                const res = await fetch(`/addresses/${address.id}/default`, {
                    method: 'PATCH',
                    headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                });
                if (!res.ok) this.currentId = previous;
            } catch (e) {
                this.currentId = previous;
            }
        },
    };
};

// shared "scroll arrows only when the row actually overflows" behavior for any horizontally
// scrollable row that exposes its scroller via x-ref="track" — used by categoryRow() below and
// categoryShop()'s tab strip. `step` is how many px each arrow click scrolls.
function scrollArrows(step = 280) {
    return {
        canLeft: false,
        canRight: false,
        measured: false,

        init() {
            this.$nextTick(() => { this.update(); this.measured = true; });
            window.addEventListener('resize', () => this.update());
        },
        update() {
            const el = this.$refs.track;
            if (!el) return;
            this.canLeft = el.scrollLeft > 4;
            this.canRight = el.scrollLeft + el.clientWidth < el.scrollWidth - 4;
        },
        scrollBy(direction) {
            this.$refs.track?.scrollBy({ left: direction * step, behavior: 'smooth' });
        },
        // true once we've measured and know the row's content is narrower than its container
        // (nothing to scroll either direction) — lets a handful of items center instead of
        // sitting stranded on the left with a big empty gap on wide desktop screens. Stays
        // false until that first measurement, so a row that's actually going to overflow never
        // flashes centered first.
        fits() {
            return this.measured && !this.canLeft && !this.canRight;
        },
    };
}

// circular category row on the homepage (partials/category-row.blade.php)
window.categoryRow = function () {
    return scrollArrows(280);
};

// admin-curated "Featured Categories" shortcut row (partials/featured-categories.blade.php)
window.featuredCategoryRow = function () {
    return scrollArrows(240);
};

// category-tabbed shop section (partials/category-shop.blade.php) — a "Top Picks" tab plus one
// per category, each with a pre-rendered product grid swapped via activeTab (no fetch; every
// grid is already in the DOM, see the home route's $categoryTabs). The tab strip reuses the same
// overflow-arrow behavior as categoryRow() above.
window.categoryShop = function (isLoggedIn, initialFavorites) {
    return {
        ...scrollArrows(220),
        activeTab: 'top-picks',
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
// Adding to cart never opens the drawer by default — that's a deliberate choice so
// browsing/adding several items in a row isn't interrupted; the drawer only opens when
// the user explicitly taps the navbar cart icon or the mobile floating cart button.
window.addProductToCart = async function (productId, quantity = 1, openDrawer = false, portion = null) {
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

// Lets product-card buttons render as a "− N +" stepper once a product is in the cart,
// instead of a static Add-to-Cart button with no feedback. Reads straight from the shared
// cart store (kept live by `cart-updated`), so it's correct on every card everywhere —
// homepage carousel, /products grid, favorites — without each needing its own cart state.
window.cartQty = function (productId) {
    const item = Alpine.store('cart').items.find((i) => i.id === productId);
    return item ? item.quantity : 0;
};

// The live cart line for a product, if any — lets the weight/quantity picker below
// initialize its selection from what's actually in the cart instead of always defaulting.
window.cartItem = function (productId) {
    return Alpine.store('cart').items.find((i) => i.id === productId) || null;
};

window.stepCartQty = async function (productId, delta) {
    const store = Alpine.store('cart');
    const item = store.items.find((i) => i.id === productId);
    if (!item) return;
    if (delta > 0) {
        await store.increment(item);
    } else if (item.quantity <= 1) {
        await store.remove(item);
    } else {
        await store.decrement(item);
    }
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
window.productListing = function (isLoggedIn, initialFavorites, products, priceBuckets, activeCategoryName, activeTagIds) {
    return {
        isLoggedIn,
        favorites: initialFavorites || [],
        products: products || [],
        priceBuckets: priceBuckets || [],
        // arriving via ?category=slug (the mobile category panel / a shared link) seeds the same
        // `categories` array the sidebar checkboxes use, rather than a separate id-based filter
        // dimension — two parallel AND'd category filters used to zero out the results the moment
        // a shopper touched any other category checkbox, since a product can't match both at once.
        // initialCategoryName remembers which entry that was, purely so the "Filtered by: X ✕"
        // chip knows what to remove when dismissed.
        // tagIds is the Featured Categories equivalent (?featured_category=slug) — a SET of tag
        // ids since one Featured Category can map to several tags; a product matches if it
        // carries ANY of them (OR, not AND).
        // q pre-seeds from ?q= so the header search's Enter/see-all lands here already filtered
        initialCategoryName: activeCategoryName || null,
        filters: { categories: activeCategoryName ? [activeCategoryName] : [], prices: [], q: new URLSearchParams(window.location.search).get('q') || '', tagIds: activeTagIds || [] },
        sort: 'recommended',
        // the mobile bottom nav's "Categories" tab used to link here with ?open_filters=1 to
        // auto-open this sheet; it now opens the image-tile category panel instead (see
        // partials/category-panel.blade.php), but a manually-typed ?open_filters=1 still works
        sheetOpen: new URLSearchParams(window.location.search).has('open_filters'),

        matches(p) {
            if (this.filters.tagIds.length && !(p.tag_ids || []).some((id) => this.filters.tagIds.includes(id))) return false;
            if (this.filters.categories.length && !this.filters.categories.includes(p.category)) return false;
            if (this.filters.prices.length) {
                const inAny = this.filters.prices.some((i) => {
                    const b = this.priceBuckets[parseInt(i, 10)];
                    return b && p.price >= b.min && (b.max === null || p.price < b.max);
                });
                if (!inAny) return false;
            }
            // collapse repeated/extra whitespace so "  chini  " and "chini" match the same
            const q = this.filters.q.trim().toLowerCase().replace(/\s+/g, ' ');
            if (q) {
                const inName = p.name.toLowerCase().includes(q);
                const inCategory = p.category.toLowerCase().includes(q);
                const inTags = (p.search_tags || '').includes(q);
                if (!inName && !inCategory && !inTags) return false;
            }
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
            this.filters = { categories: [], prices: [], q: '', tagIds: [] };
            this.stripParamFromUrl('category');
            this.stripParamFromUrl('featured_category');
        },
        // the dedicated "Filtered by: X ✕" chip — leaves the other checkbox filters (prices/
        // search/other categories) untouched, only unchecks the one seeded via ?category=slug
        clearCategoryFilter() {
            this.filters.categories = this.filters.categories.filter((c) => c !== this.initialCategoryName);
            this.stripParamFromUrl('category');
        },
        // same idea as clearCategoryFilter() above, for the Featured Categories (?featured_
        // category=slug) chip
        clearTagFilter() {
            this.filters.tagIds = [];
            this.stripParamFromUrl('featured_category');
        },
        stripParamFromUrl(param) {
            const url = new URL(window.location.href);
            if (!url.searchParams.has(param)) return;
            url.searchParams.delete(param);
            window.history.replaceState(null, '', url.pathname + (url.search ? url.search : ''));
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
            // tells the floating "View Cart" button (layouts/app.blade.php) to lift up and
            // clear this page's own sticky Add-to-Cart bar once it scrolls into view
            const onScroll = () => {
                const next = window.scrollY > 480;
                if (next !== this.showStickyBar) {
                    this.showStickyBar = next;
                    window.dispatchEvent(new CustomEvent('sticky-bar-toggled', { detail: { visible: next } }));
                }
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
                const data = await window.addProductToCart(productId, this.quantity, false, this.selectedPortion);
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
        crossedFreeDeliveryThreshold: false,

        subtotal() {
            return this.items.reduce((sum, item) => sum + item.price * item.quantity, 0);
        },
        estimatedFees() {
            return Alpine.store('shop').totalFees(this.subtotal());
        },
        init() {
            // if a customer arrives already above the threshold (e.g. items added on the product
            // page), don't fire the unlock celebration on load — only when they actually cross it
            // while watching, which is the moment worth celebrating
            this.crossedFreeDeliveryThreshold = Alpine.store('shop').amountToFreeDelivery(this.subtotal()) === 0;
            this.$watch('items', () => {
                const remaining = Alpine.store('shop').amountToFreeDelivery(this.subtotal());
                if (remaining === 0 && !this.crossedFreeDeliveryThreshold) {
                    this.crossedFreeDeliveryThreshold = true;
                    celebrateFreeDelivery();
                } else if (remaining > 0) {
                    this.crossedFreeDeliveryThreshold = false;
                }
            });
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
        // defaults to COD unless the admin has disabled it, in which case Razorpay (checked
        // again, self-correcting, right before submission in checkout() below)
        paymentMethod: Alpine.store('shop').codEnabled ? 'cod' : 'razorpay',

        checkoutError: '',
        checkingOut: false,
        locationStatus: 'idle', // idle | checking | inside | outside | denied
        locationDistanceKm: null,
        cachedCoords: null,
        lastRestricted: null,
        crossedFreeDeliveryThreshold: false,

        get selectedAddress() {
            return this.addresses.find((a) => a.id === this.selectedAddressId) || null;
        },

        init() {
            this.crossedFreeDeliveryThreshold = Alpine.store('shop').amountToFreeDelivery(this.subtotal()) === 0;
            this.$watch('items', () => {
                const remaining = Alpine.store('shop').amountToFreeDelivery(this.subtotal());
                if (remaining === 0 && !this.crossedFreeDeliveryThreshold) {
                    this.crossedFreeDeliveryThreshold = true;
                    celebrateFreeDelivery();
                } else if (remaining > 0) {
                    this.crossedFreeDeliveryThreshold = false;
                }
            });
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
        deliveryFee() {
            return Alpine.store('shop').deliveryFee(this.subtotal());
        },
        rainFee() {
            return Alpine.store('shop').rainFee();
        },
        highDemandFee() {
            return Alpine.store('shop').highDemandFee();
        },
        total() {
            return this.subtotal() - this.discount() + Alpine.store('shop').totalFees(this.subtotal());
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
                } else if (res.status === 419) {
                    // Laravel's own "CSRF token mismatch" (session expired) — never show that
                    // raw framework text to a customer, give them something they can act on
                    this.couponError = 'Your session has expired. Please refresh the page and try again.';
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
            if (Alpine.store('shop').highDemandMode === 'stop') return;
            // self-correct if the admin disabled the customer's currently-selected method while
            // they were on this page (live via pollShopStatus) — the server re-checks this too
            if (this.paymentMethod === 'cod' && !Alpine.store('shop').codEnabled) this.paymentMethod = 'razorpay';
            if (this.paymentMethod === 'razorpay' && !Alpine.store('shop').razorpayEnabled) this.paymentMethod = 'cod';
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
                payment_method: this.paymentMethod,
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
                    if (data.payment_method === 'razorpay') {
                        this.openRazorpay(data);
                        return;
                    }
                    // hand off to the live tracking page — checkingOut stays true so the
                    // button can't be clicked again while the browser navigates away
                    window.location.assign(`/orders/${data.order_id}?placed=1`);
                    return;
                }
                if (res.status === 419) {
                    // session expired mid-checkout — nothing was charged yet at this point
                    // (order/payment only happen after this call succeeds), so a refresh is safe
                    this.checkoutError = 'Your session has expired. Please refresh the page and try again.';
                } else {
                    this.checkoutError = data.message || (data.errors ? Object.values(data.errors)[0][0] : 'Could not place the order, please try again.');
                }
                this.checkingOut = false;
            } catch (e) {
                this.checkoutError = 'Network error, please try again.';
                this.checkingOut = false;
            }
        },
        // order already exists server-side at this point (payment_status: pending) — this just
        // opens Razorpay's widget and, on success, verifies the signature before redirecting
        openRazorpay(data) {
            const csrf = document.querySelector('meta[name=csrf-token]').content;

            const rzp = new window.Razorpay({
                key: data.razorpay.key,
                amount: data.razorpay.amount,
                currency: data.razorpay.currency,
                name: data.razorpay.name,
                description: data.razorpay.description,
                order_id: data.razorpay.order_id,
                prefill: data.razorpay.prefill,
                theme: { color: '#c8962e' },
                handler: async (response) => {
                    try {
                        const verifyRes = await fetch('/checkout/razorpay/verify', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                Accept: 'application/json',
                                'X-CSRF-TOKEN': csrf,
                            },
                            body: JSON.stringify({
                                order_id: data.order_id,
                                razorpay_order_id: response.razorpay_order_id,
                                razorpay_payment_id: response.razorpay_payment_id,
                                razorpay_signature: response.razorpay_signature,
                            }),
                        });
                        const verifyData = await verifyRes.json().catch(() => ({}));
                        if (verifyData.ok) {
                            window.location.assign(`/orders/${data.order_id}?placed=1`);
                            return;
                        }
                        if (verifyRes.status === 419) {
                            // session expired at the worst possible moment — the payment itself may
                            // already be captured by Razorpay, so this must not read as "try again"
                            this.checkoutError = `Your session expired while confirming this payment. If the amount was deducted, it will be verified automatically — please check your Orders page shortly, or contact support with reference ${response.razorpay_payment_id}.`;
                        } else {
                            this.checkoutError = verifyData.message || 'Payment verification failed. If the amount was deducted, please contact support.';
                        }
                        this.checkingOut = false;
                    } catch (e) {
                        this.checkoutError = 'Network error while verifying payment. If the amount was deducted, please contact support.';
                        this.checkingOut = false;
                    }
                },
                modal: {
                    ondismiss: () => {
                        this.checkingOut = false;
                        this.checkoutError = 'Payment was not completed.';
                    },
                },
            });

            rzp.on('payment.failed', () => {
                this.checkoutError = 'Payment failed, please try again.';
                this.checkingOut = false;
            });

            rzp.open();
        },
    };
};

// site-wide reminder to grant location permission, so by the time a customer reaches checkout
// its own silent checkDeliveryArea(true) (above) already has permission and never has to ask —
// this component's only job is getting permission granted; it doesn't gate anything itself.
// Reused from checkoutPage(): same getCurrentPosition options and the same /delivery-check
// endpoint, just triggered from every page instead of only the checkout page.
window.locationPrompt = function () {
    return {
        supported: !!navigator.geolocation,
        permissionState: 'unknown', // 'granted' | 'denied' | 'prompt' | 'unknown'
        checking: false,
        dismissed: false, // this page view only — not persisted, so it naturally reappears on the next page load if still not granted
        confirmation: '', // brief "you're within/outside our delivery area" note after a successful check

        async init() {
            if (!this.supported) return;
            if (!navigator.permissions?.query) {
                // no Permissions API (older Safari) — can't pre-detect state, just let the
                // banner show with a working button; the real state only becomes known once
                // the customer actually clicks it
                this.permissionState = 'prompt';
                return;
            }
            try {
                const status = await navigator.permissions.query({ name: 'geolocation' });
                this.permissionState = status.state;
                // reacts live if the customer flips the permission via the browser's own site
                // settings UI while this tab stays open, without needing a page reload
                status.onchange = () => { this.permissionState = status.state; };
            } catch (e) {
                this.permissionState = 'prompt';
            }
        },
        async requestPermission() {
            if (this.checking) return;
            this.checking = true;
            this.confirmation = '';
            try {
                const coords = await new Promise((resolve, reject) => {
                    navigator.geolocation.getCurrentPosition(
                        (position) => resolve({ latitude: position.coords.latitude, longitude: position.coords.longitude }),
                        () => reject(new Error('denied')),
                        { enableHighAccuracy: true, timeout: 12000, maximumAge: 60000 }
                    );
                });
                this.permissionState = 'granted';
                try {
                    const res = await fetch(`/delivery-check?lat=${coords.latitude}&lng=${coords.longitude}`, {
                        headers: { Accept: 'application/json' },
                    });
                    const data = await res.json();
                    if (data.ok) {
                        this.confirmation = data.within
                            ? "✅ You're within our delivery area."
                            : "This location looks outside our delivery area — you can still choose a different address at checkout.";
                    }
                } catch (e) {
                    // permission itself succeeded, which is this component's actual job — a
                    // failed courtesy delivery-area check afterward isn't worth surfacing an error for
                }
            } catch (e) {
                this.permissionState = 'denied';
            } finally {
                this.checking = false;
            }
        },
    };
};

// PWA "Install App" banner — captures the browser's beforeinstallprompt event (Chrome/Edge/
// Android; iOS Safari never fires it, so this component simply never shows there — no custom
// "Add to Home Screen" instructions, that's out of scope) and offers it once per browser session
// via a small in-flow banner, same pattern as locationPrompt() above. sessionStorage (not
// localStorage) is deliberate here — it clears when the tab/browser session actually ends, so a
// genuinely new session is offered the install again if the site still isn't installed, but
// navigating between pages within the same session doesn't re-show it every time.
window.installPrompt = function () {
    return {
        deferredEvent: null,
        visible: false,

        init() {
            const seenKey = 'mb_install_prompt_seen';
            window.addEventListener('beforeinstallprompt', (e) => {
                e.preventDefault();
                this.deferredEvent = e;
                if (!sessionStorage.getItem(seenKey)) this.visible = true;
            });
            // installed via the browser's own native affordance instead of this banner —
            // hide it immediately rather than leaving a stale "Install App" button up
            window.addEventListener('appinstalled', () => {
                this.visible = false;
                sessionStorage.setItem(seenKey, '1');
            });
        },
        async install() {
            if (!this.deferredEvent) return;
            this.deferredEvent.prompt();
            await this.deferredEvent.userChoice;
            // don't ask again this session either way — accept or dismiss, the browser's own
            // prompt already gave the customer the choice once
            this.deferredEvent = null;
            this.visible = false;
            sessionStorage.setItem('mb_install_prompt_seen', '1');
        },
        dismiss() {
            this.visible = false;
            sessionStorage.setItem('mb_install_prompt_seen', '1');
        },
    };
};

// live order-tracking page — polls the order's status so admin-side updates
// (confirmed / out for delivery / delivered) animate in without a reload
window.orderTrackingPage = function (initialOrder, justPlaced, etaSoonTemplate, etaNowText) {
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
        cancelReason: '',
        // true for a few seconds right after a genuine rider assignment/reassignment — drives the
        // "Your Delivery Partner" card's one-time entrance flourish (glow pulse, bounce) without a
        // separate popup component; the card itself stays visible via x-show="order.rider_name"
        // whether or not this flag is set
        justAssignedRider: false,
        // same idea, for the "We're Sorry" apology card — true for ~8s right after poll() sees a
        // NEW item removal (not on every tick the card happens to already be visible for)
        justRemovedItems: false,
        // same idea again, for the "🎉 Your order has been updated!" card — true for ~8s right
        // after poll() sees an admin-added item it hasn't shown yet
        justAddedItems: false,
        // "We're Sorry" card: starts fully open with a 10s countdown, then auto-collapses — see
        // startSorryCollapse()/collapseSorry()/reopenSorry() below. sorryCountingDown gates the
        // progress bar specifically — true only during that initial timed sequence, never after
        // a manual reopen (reopenSorry() doesn't restart the countdown)
        sorryExpanded: false,
        sorryCountingDown: false,
        sorryProgressReady: false,
        // "Your order has been updated!" card — identical collapse mechanic, mirrored 1:1 (see
        // startUpdatedCollapse()/collapseUpdated()/reopenUpdated() below)
        updatedExpanded: false,
        updatedCountingDown: false,
        updatedProgressReady: false,

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

            // once the "We're Sorry" card has actually collapsed (timeout or manual), that's
            // persisted per removed-item-id — so a page refresh doesn't re-run the whole 10s
            // expand-then-collapse cycle for something the customer already saw and dismissed.
            // A genuinely NEW removal (an id not yet in that dismissed set) still opens it fresh.
            const removedIds = this.order.items.filter((i) => i.removed).map((i) => i.id);
            if (removedIds.length > 0) {
                const dismissed = this.dismissedRemovalIds();
                if (removedIds.some((id) => !dismissed.has(id))) {
                    this.startSorryCollapse();
                } else {
                    this.sorryExpanded = false;
                }
            }
            // identical logic for the "Your order has been updated!" card and its added-item ids
            const addedIds = this.order.items.filter((i) => i.added).map((i) => i.id);
            if (addedIds.length > 0) {
                const dismissed = this.dismissedAddedIds();
                if (addedIds.some((id) => !dismissed.has(id))) {
                    this.startUpdatedCollapse();
                } else {
                    this.updatedExpanded = false;
                }
            }

            // covers "rider marked this delivered while the customer wasn't on this page at
            // all" — poll() below early-returns the instant status is already 'delivered', so
            // it never gets a chance to fire its own transition-detected prompt in that case.
            // $nextTick matters here: riderRatingPopup() is a separate, nested x-data component
            // further down this same tree — on a fresh page load Alpine hasn't initialized it
            // (and registered its open-rider-rating listener) yet at the point this init() runs,
            // so dispatching synchronously would fire into a void. $nextTick defers until after
            // Alpine finishes initializing the whole tree, poll()'s own call below doesn't need
            // this (everything's long since initialized after the first 5s), but it's harmless
            // there too.
            this.$nextTick(() => this.maybePromptRating(this.order));
            // covers "admin decided on the note while the customer wasn't on this page" — same
            // $nextTick reasoning as maybePromptRating() above (noteDecisionPopup() is a separate,
            // nested x-data component not yet initialized at this point in a fresh page load)
            this.$nextTick(() => this.maybeShowNoteDecision(this.order));
            // covers "admin added item(s) while the customer wasn't on this page" — same
            // $nextTick reasoning (orderUpdatedPopup() is a separate, nested x-data component)
            this.$nextTick(() => this.maybeShowOrderUpdated(this.order));

            // popup lives in a separate x-data component — it can't reach into this.order
            // directly, so it tells us via event instead of us waiting for the next 5s poll
            window.addEventListener('rider-rating-submitted', (e) => {
                if (e.detail.orderId === this.order.id) this.order.needs_rating = false;
            });

            // same reasoning, for productRatingPopup() — a product can appear on more than one
            // order-item (different portions), so flip every matching one, then recompute the
            // order-level flag the hero button's label switches on
            window.addEventListener('product-rating-submitted', (e) => {
                if (e.detail.orderId !== this.order.id) return;
                for (const item of this.order.items) {
                    if (item.product_id === e.detail.productId) {
                        item.already_rated = true;
                        item.existing_rating = e.detail.rating;
                    }
                }
                const ids = [...new Set(this.order.items.filter((i) => i.product_id).map((i) => i.product_id))];
                this.order.all_products_rated = ids.length > 0
                    && ids.every((id) => this.order.items.find((i) => i.product_id === id).already_rated);
            });
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
            return mins > 1 ? etaSoonTemplate.replace('␟', mins) : etaNowText;
        },
        // hides the Cancel Order section the instant the window closes, rather than waiting up
        // to 5s for the next status poll to bring back an updated can_cancel from the server
        // (which remains the actual source of truth — see OrderTrackingController::cancel())
        cancelVisible() {
            return this.order.can_cancel
                && (!this.order.cancel_window_ends_at || this.now < this.order.cancel_window_ends_at);
        },
        cancelCountdownText() {
            if (!this.order.cancel_window_ends_at) return null;
            const mins = Math.ceil((this.order.cancel_window_ends_at - this.now) / 60000);
            return mins > 1 ? `You can cancel for ${mins} more minutes` : 'Less than a minute left to cancel';
        },
        // dedupes by product_id — a product bought twice in one order (different portions) is
        // only rated once, since ratings are product-scoped, not per order-item
        ratableProducts() {
            const seen = new Set();
            const list = [];
            for (const item of this.order.items) {
                if (!item.product_id || seen.has(item.product_id)) continue;
                seen.add(item.product_id);
                list.push({
                    productId: item.product_id,
                    slug: item.product_slug,
                    name: item.name,
                    imageUrl: item.image_url,
                    portionLabel: item.portion_label,
                    rating: item.existing_rating || 0,
                    comment: item.existing_comment || '',
                });
            }
            return list;
        },
        openProductRatingPopup() {
            window.dispatchEvent(new CustomEvent('open-product-rating', {
                detail: { orderId: this.order.id, products: this.ratableProducts() },
            }));
        },

        async poll() {
            if (['delivered', 'cancelled'].includes(this.order.status)) return;
            try {
                const res = await fetch(`/orders/${this.order.id}/status`, { headers: { Accept: 'application/json' } });
                if (!res.ok) return;
                const data = await res.json().catch(() => ({}));
                if (!data.ok) return;
                const previousStatus = this.order.status;
                const previousRiderId = this.order.rider_id;
                const previousRemovedIds = new Set(this.order.items.filter((i) => i.removed).map((i) => i.id));
                const previousAddedIds = new Set(this.order.items.filter((i) => i.added).map((i) => i.id));
                this.order = data.order;
                this.now = Date.now();
                if (data.order.status !== previousStatus) {
                    if (data.order.status === 'delivered') {
                        this.celebrate();
                        this.maybePromptRating(data.order);
                    } else if (data.order.status !== 'cancelled') this.cheer();
                }
                // a genuine assignment or reassignment — never fires on first load (nothing to
                // diff against yet) or when unassigning (new id is falsy)
                if (data.order.rider_id && data.order.rider_id !== previousRiderId) {
                    this.justAssignedRider = true;
                    this.celebrateRiderAssigned();
                    setTimeout(() => { this.justAssignedRider = false; }, 6000);
                }
                // a genuine NEW removal since the last poll — previousRemovedIds already reflects
                // whatever was true at the last tick (including page load), so this only fires
                // the moment something changes, not on every tick the card happens to be visible
                const newlyRemoved = data.order.items.some((i) => i.removed && !previousRemovedIds.has(i.id));
                if (newlyRemoved) {
                    this.justRemovedItems = true;
                    setTimeout(() => { this.justRemovedItems = false; }, 8000);
                    // nudges the notification bell to refresh immediately instead of waiting out
                    // its own independent 15s cycle — see notificationsBell() below
                    window.dispatchEvent(new CustomEvent('notifications-refresh'));
                    // re-opens the "We're Sorry" card (even if the customer had already collapsed
                    // an earlier removal) and restarts its 10s countdown for the fresh one
                    this.startSorryCollapse();
                }
                // a genuine NEW admin-added item since the last poll — same shape as the removal
                // diff above, mirrored for the celebratory "order updated" card
                const newlyAdded = data.order.items.some((i) => i.added && !previousAddedIds.has(i.id));
                if (newlyAdded) {
                    this.justAddedItems = true;
                    setTimeout(() => { this.justAddedItems = false; }, 8000);
                    window.dispatchEvent(new CustomEvent('notifications-refresh'));
                    // re-opens the "Your order has been updated!" card and restarts its 10s
                    // countdown for the fresh add, same as startSorryCollapse() above
                    this.startUpdatedCollapse();
                }
                // self-contained one-shot check (localStorage-gated by note_decided_at), no
                // "previous value" snapshot needed here unlike the justX flags above
                this.maybeShowNoteDecision(data.order);
                // same self-contained one-shot pattern, gated by items_added_at
                this.maybeShowOrderUpdated(data.order);
            } catch (e) {
                // offline / server hiccup — next tick will catch up
            }
        },

        // opens the rider-rating popup once per order — the first time the customer lands on (or
        // polls into) a delivered, unrated order. Persisted via localStorage, same pattern as the
        // "We're Sorry" card's dismissal tracking, so it never re-interrupts a later visit; the
        // small "Rate your delivery" pill in the delivered hero is the only prompt after that.
        maybePromptRating(order) {
            if (!order.needs_rating) return;
            const key = `mb_rating_prompted_${order.id}`;
            if (localStorage.getItem(key)) return;
            localStorage.setItem(key, '1');
            window.dispatchEvent(new CustomEvent('open-rider-rating', {
                detail: { orderId: order.id, riderName: order.rider_name, riderPhoto: order.rider_photo_url },
            }));
        },

        // fires the note-decision popup exactly once per decision. note_decided_at (an ISO
        // timestamp, not a boolean) is the fingerprint — storing the exact value already shown
        // means a note that gets edited and re-decided later correctly triggers the popup again,
        // since a fresh decision always gets a fresh timestamp
        maybeShowNoteDecision(order) {
            if (!order.note_decided_at || order.note_status === 'pending') return;
            const key = `mb_note_decision_seen_${order.id}`;
            if (localStorage.getItem(key) === order.note_decided_at) return;
            localStorage.setItem(key, order.note_decided_at);
            window.dispatchEvent(new CustomEvent('open-note-decision', {
                detail: { status: order.note_status, message: order.note_decision_message },
            }));
        },

        // fires the order-updated popup exactly once per add-event. items_added_at (the latest
        // admin-added item's timestamp) is the fingerprint, same trick as note_decided_at above —
        // a second, later batch of added items gets a fresh timestamp and correctly re-fires.
        maybeShowOrderUpdated(order) {
            if (!order.items_added_at) return;
            const key = `mb_order_updated_seen_${order.id}`;
            if (localStorage.getItem(key) === order.items_added_at) return;
            localStorage.setItem(key, order.items_added_at);
            window.dispatchEvent(new CustomEvent('open-order-updated', {
                detail: { items: order.items.filter((i) => i.added) },
            }));
        },

        // opens the "We're Sorry" card fully and (re)starts its 10s auto-collapse countdown —
        // called on init() when items were already removed before this page load, and again from
        // poll() every time a fresh removal is detected
        startSorryCollapse() {
            clearTimeout(this.$el._sorryTimer);
            this.sorryExpanded = true;
            this.sorryCountingDown = true;
            // the progress bar is a single CSS width transition (100% -> 0%) rather than a JS
            // tick loop: reset to the un-transitioned 100% state first, then flip it a frame
            // later so the browser actually animates the change instead of snapping straight to 0
            this.sorryProgressReady = false;
            requestAnimationFrame(() => requestAnimationFrame(() => { this.sorryProgressReady = true; }));
            this.$el._sorryTimer = setTimeout(() => { this.collapseSorry(); }, 10000);
        },
        // collapsing (whether the 10s timeout or the manual header tap) is the moment these
        // removed items get marked "seen" in localStorage — that's what makes a later page
        // refresh open straight into the collapsed state instead of re-running the whole
        // expand-then-collapse cycle for something the customer already dismissed
        collapseSorry() {
            clearTimeout(this.$el._sorryTimer);
            this.sorryExpanded = false;
            this.sorryCountingDown = false;
            this.markRemovalsDismissed(this.order.items.filter((i) => i.removed).map((i) => i.id));
        },
        reopenSorry() {
            // manual reopen doesn't restart the countdown — it stays open until the customer
            // collapses it again themselves, or a new removal arrives
            clearTimeout(this.$el._sorryTimer);
            this.sorryExpanded = true;
            this.sorryCountingDown = false;
        },
        sorryStorageKey() {
            return `mb_sorry_dismissed_${this.order.id}`;
        },
        dismissedRemovalIds() {
            try {
                return new Set(JSON.parse(localStorage.getItem(this.sorryStorageKey()) || '[]'));
            } catch (e) {
                return new Set();
            }
        },
        markRemovalsDismissed(ids) {
            const dismissed = this.dismissedRemovalIds();
            ids.forEach((id) => dismissed.add(id));
            localStorage.setItem(this.sorryStorageKey(), JSON.stringify([...dismissed]));
        },

        // "Your order has been updated!" card — identical collapse mechanic to the "We're Sorry"
        // card above (startSorryCollapse()/collapseSorry()/reopenSorry()/dismissedRemovalIds()/
        // markRemovalsDismissed()), mirrored 1:1 for admin-added items instead of removed ones
        startUpdatedCollapse() {
            clearTimeout(this.$el._updatedTimer);
            this.updatedExpanded = true;
            this.updatedCountingDown = true;
            this.updatedProgressReady = false;
            requestAnimationFrame(() => requestAnimationFrame(() => { this.updatedProgressReady = true; }));
            this.$el._updatedTimer = setTimeout(() => { this.collapseUpdated(); }, 10000);
        },
        collapseUpdated() {
            clearTimeout(this.$el._updatedTimer);
            this.updatedExpanded = false;
            this.updatedCountingDown = false;
            this.markAddedDismissed(this.order.items.filter((i) => i.added).map((i) => i.id));
        },
        reopenUpdated() {
            clearTimeout(this.$el._updatedTimer);
            this.updatedExpanded = true;
            this.updatedCountingDown = false;
        },
        updatedStorageKey() {
            return `mb_updated_dismissed_${this.order.id}`;
        },
        dismissedAddedIds() {
            try {
                return new Set(JSON.parse(localStorage.getItem(this.updatedStorageKey()) || '[]'));
            } catch (e) {
                return new Set();
            }
        },
        markAddedDismissed(ids) {
            const dismissed = this.dismissedAddedIds();
            ids.forEach((id) => dismissed.add(id));
            localStorage.setItem(this.updatedStorageKey(), JSON.stringify([...dismissed]));
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
        celebrateRiderAssigned() {
            confetti({ particleCount: 50, spread: 70, origin: { x: 0.5, y: 0.4 }, colors: ['#d4a940', '#c8962e', '#3d7a52', '#fdf6e3'] });
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
                    this.order.note_status = data.note_status;
                    this.order.note_decision_message = data.note_decision_message;
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
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: JSON.stringify({ reason: this.cancelReason.trim() || null }),
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

// rider rating popup (partials/rider-rating-popup.blade.php) — opened via the open-rider-rating
// window event, dispatched by orderTrackingPage() the moment it sees a delivered, unrated order
// (see maybePromptRating() above) or by the "Rate your delivery" reminder pill in the delivered
// hero. Submission reuses the exact same star-picker interaction as the product review form
// (reviewForm() above) — hover-scale, a quick select pulse, and a sparkle burst on 5★.
window.riderRatingPopup = function () {
    return {
        open: false,
        step: 'rate', // 'rate' | 'result'
        orderId: null,
        riderName: '',
        riderPhoto: null,
        rating: 0,
        hoverRating: 0,
        justRatedIndex: null,
        showBurst: false,
        comment: '',
        submitting: false,
        error: '',

        init() {
            window.addEventListener('open-rider-rating', (e) => {
                this.orderId = e.detail.orderId;
                this.riderName = e.detail.riderName || '';
                this.riderPhoto = e.detail.riderPhoto || null;
                this.step = 'rate';
                this.rating = 0;
                this.hoverRating = 0;
                this.comment = '';
                this.error = '';
                this.open = true;
            });
        },
        setRating(i) {
            this.rating = i;
            this.justRatedIndex = i;
            setTimeout(() => {
                if (this.justRatedIndex === i) this.justRatedIndex = null;
            }, 450);
            if (i === 5) {
                this.showBurst = false;
                this.$nextTick(() => { this.showBurst = true; });
                setTimeout(() => { this.showBurst = false; }, 1100);
            }
        },
        async submit() {
            if (!this.rating || this.submitting) return;
            this.submitting = true;
            this.error = '';
            try {
                const csrf = document.querySelector('meta[name=csrf-token]').content;
                const res = await fetch(`/orders/${this.orderId}/rider-rating`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify({ rating: this.rating, comment: this.comment.trim() || null }),
                });
                const data = await res.json().catch(() => ({}));
                if (data.ok) {
                    this.step = 'result';
                    if (this.rating >= 3) this.celebrateRating();
                    // droop animation is pure CSS (animate-droop on the emoji, see app.css) — no
                    // JS trigger needed for the 1-2★ path
                    // lets orderTrackingPage() hide the "Rate your delivery" pill right away instead
                    // of waiting for its next 5s poll (which also carries the real needs_rating flag)
                    window.dispatchEvent(new CustomEvent('rider-rating-submitted', { detail: { orderId: this.orderId } }));
                } else {
                    this.error = data.message || 'Could not submit your rating — please try again.';
                }
            } catch (e) {
                this.error = 'Network error, please try again.';
            } finally {
                this.submitting = false;
            }
        },
        // respects the same admin-chosen animation toggle every other delivery celebration in
        // this app already does — 'minimal' skips confetti, the thank-you message still shows
        celebrateRating() {
            if (Alpine.store('shop').deliverySuccessAnimation === 'minimal') return;
            confetti({ particleCount: 90, spread: 75, origin: { x: 0.5, y: 0.45 }, colors: ['#d4a940', '#c8962e', '#3d7a52', '#fdf6e3'] });
        },
        skip() {
            this.open = false;
        },
        close() {
            this.open = false;
        },
    };
};

// note-decision popup — opened via open-note-decision, dispatched by orderTrackingPage()'s
// maybeShowNoteDecision() the moment an admin's accept/deny on the customer's "Note for the Shop"
// is detected (live via poll, or on next page load if the customer wasn't watching). Single-step,
// no input — just the reveal. Confetti-gated the same way celebrateRating()/celebrateFreeDelivery()
// already are (unlike this same file's celebrate()/cheer(), which don't check the toggle).
window.noteDecisionPopup = function () {
    return {
        open: false,
        status: null, // 'accepted' | 'denied'
        message: '',

        init() {
            window.addEventListener('open-note-decision', (e) => {
                this.status = e.detail.status;
                this.message = e.detail.message || '';
                this.open = true;
                if (this.status === 'accepted' && Alpine.store('shop').deliverySuccessAnimation !== 'minimal') {
                    confetti({ particleCount: 70, spread: 70, origin: { x: 0.5, y: 0.45 }, colors: ['#d4a940', '#c8962e', '#3d7a52', '#fdf6e3'] });
                }
                // droop animation is pure CSS (animate-droop on the emoji) — no JS trigger needed
                // for the denied path, same as riderRatingPopup's 1-2★ result
            });
        },
        close() {
            this.open = false;
        },
    };
};

// order-updated popup — opened via open-order-updated, dispatched by orderTrackingPage()'s
// maybeShowOrderUpdated() the moment an admin adds product(s) to the customer's order (see
// Admin\OrderController::addItems()) is detected (live via poll, or on next page load). Lists
// every added item, same confetti gate as noteDecisionPopup() above.
window.orderUpdatedPopup = function () {
    return {
        open: false,
        items: [],

        init() {
            window.addEventListener('open-order-updated', (e) => {
                this.items = e.detail.items || [];
                this.open = true;
                if (Alpine.store('shop').deliverySuccessAnimation !== 'minimal') {
                    confetti({ particleCount: 70, spread: 70, origin: { x: 0.5, y: 0.45 }, colors: ['#d4a940', '#c8962e', '#3d7a52', '#fdf6e3'] });
                }
            });
        },
        close() {
            this.open = false;
        },
    };
};

// product rating popup — opened via open-product-rating, dispatched by orderTrackingPage()'s
// openProductRatingPopup() when the customer taps "⭐ Rate Products"/"View Your Reviews". Unlike
// riderRatingPopup, this never auto-opens (click-triggered only) and rates a LIST of products,
// each independently, reusing the existing product-page review endpoint unchanged — ratings are
// product-scoped (not order-scoped), so "already rated" just means "rated this product before."
window.productRatingPopup = function () {
    return {
        open: false,
        orderId: null,
        products: [], // [{ productId, slug, name, imageUrl, portionLabel, rating, hoverRating,
                       //    justRatedIndex, showBurst, comment, savedComment, savingComment,
                       //    justSaved, error }]
        celebrated: false,

        init() {
            window.addEventListener('open-product-rating', (e) => {
                this.orderId = e.detail.orderId;
                this.products = e.detail.products.map((p) => ({
                    productId: p.productId,
                    slug: p.slug,
                    name: p.name,
                    imageUrl: p.imageUrl,
                    portionLabel: p.portionLabel,
                    rating: p.rating || 0,
                    hoverRating: 0,
                    justRatedIndex: null,
                    showBurst: false,
                    comment: p.comment || '',
                    savedComment: p.comment || '',
                    savingComment: false,
                    justSaved: false,
                    error: '',
                }));
                this.celebrated = false;
                this.open = true;
            });
        },
        ratedCount() {
            return this.products.filter((p) => p.rating > 0).length;
        },
        allRated() {
            return this.products.length > 0 && this.products.every((p) => p.rating > 0);
        },
        setRating(product, i) {
            product.rating = i;
            product.justRatedIndex = i;
            setTimeout(() => {
                if (product.justRatedIndex === i) product.justRatedIndex = null;
            }, 450);
            if (i === 5) {
                product.showBurst = false;
                this.$nextTick(() => { product.showBurst = true; });
                setTimeout(() => { product.showBurst = false; }, 1100);
            }
            this.submitProduct(product);
        },
        saveComment(product) {
            if (!product.rating || product.comment === product.savedComment || product.savingComment) return;
            this.submitProduct(product);
        },
        async submitProduct(product) {
            product.error = '';
            product.savingComment = true;
            try {
                const csrf = document.querySelector('meta[name=csrf-token]').content;
                const form = new FormData();
                form.append('rating', product.rating);
                form.append('comment', product.comment || '');
                // the EXISTING /product/{slug}/reviews endpoint, unmodified — same
                // updateOrCreate(user_id, product_id) upsert the product page's own review form uses
                const res = await fetch(`/product/${product.slug}/reviews`, {
                    method: 'POST',
                    headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: form,
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok || !data.ok) throw new Error(data.message || 'Something went wrong, please try again.');
                product.savedComment = product.comment;
                product.justSaved = true;
                setTimeout(() => { product.justSaved = false; }, 1800);
                // orderTrackingPage() can't reach into this popup's state — tell it via event
                // instead of waiting for the next 5s poll, same pattern as rider-rating-submitted
                window.dispatchEvent(new CustomEvent('product-rating-submitted', {
                    detail: { orderId: this.orderId, productId: product.productId, rating: product.rating },
                }));
                if (this.allRated() && !this.celebrated) {
                    this.celebrated = true;
                    if (Alpine.store('shop').deliverySuccessAnimation !== 'minimal') {
                        confetti({ particleCount: 90, spread: 75, origin: { x: 0.5, y: 0.45 }, colors: ['#d4a940', '#c8962e', '#3d7a52', '#fdf6e3'] });
                    }
                }
            } catch (e) {
                product.error = e.message || 'Network error, please try again.';
            } finally {
                product.savingComment = false;
            }
        },
        close() {
            this.open = false;
        },
    };
};

// soft two-note pop for an incoming support reply — quieter and shorter than the admin
// panel's order chime, since the customer may be in a public place
function playChatPing() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const now = ctx.currentTime;
        [[740, 0], [988, 0.12]].forEach(([freq, delay]) => {
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.value = freq;
            gain.gain.setValueAtTime(0.0001, now + delay);
            gain.gain.exponentialRampToValueAtTime(0.12, now + delay + 0.02);
            gain.gain.exponentialRampToValueAtTime(0.0001, now + delay + 0.35);
            osc.connect(gain).connect(ctx.destination);
            osc.start(now + delay);
            osc.stop(now + delay + 0.4);
        });
    } catch (e) {
        // audio blocked before first user interaction — nothing else to do
    }
}

// per-order support chat — launcher FAB + chat window on the order detail view. Polling-based
// (like every other live feature here): 3s while the window is open, every ~4th tick while
// closed just to keep the unread badge honest.
//
// Window has three visible forms, all under the one `panelOpen` flag:
//   - closed (panelOpen=false): only the launcher FAB shows.
//   - minimized (panelOpen=true, minimized=true): a slim "resume" bar — the order page behind
//     it is fully visible/usable again, which is the whole point of minimize vs. just closing.
//   - viewing (panelOpen=true, minimized=false): the actual thread, `expanded` toggling between
//     the default compact size and a larger one (desktop only — mobile is always full-screen here).
// `viewingThread` (panelOpen && !minimized) gates anything that should only happen while the
// customer is actually looking at the thread: unread reset, the "read" receipt query, autoscroll.
window.supportChat = function (orderId, autoOpen = false) {
    return {
        panelOpen: false,
        minimized: false,
        expanded: false,
        customWidth: null,
        customHeight: null,
        resizeStart: null,
        messages: [],
        draft: '',
        sending: false,
        sendError: '',
        unread: 0,
        loaded: false,
        lastId: 0,
        tick: 0,
        pendingImage: null,
        pendingImagePreview: null,

        get viewingThread() {
            return this.panelOpen && !this.minimized;
        },

        init() {
            this.fetchMessages();
            this.$el._chatTimer = setInterval(() => {
                this.tick += 1;
                if (this.viewingThread || this.tick % 4 === 0) this.fetchMessages();
            }, 3000);
            // arrived from the notification bell's "admin replied" link — jump straight in
            if (autoOpen) {
                this.openChat();
                // strip ?chat=1 so refreshing/reopening the page doesn't reopen it every time
                window.history.replaceState(null, '', `/orders/${orderId}`);
            }
        },
        // fired by Alpine both on normal page nav and by the account page's
        // teardownOrderPanel() (Alpine.destroyTree) when switching orders inline
        destroy() {
            clearInterval(this.$el._chatTimer);
            this.unlockScroll();
            this.clearPendingImage();
        },

        openChat() {
            this.panelOpen = true;
            this.minimized = false;
            this.expanded = false;
            this.customWidth = null;
            this.customHeight = null;
            this.unread = 0;
            this.lockScroll();
            this.fetchMessages();
            this.$nextTick(() => {
                this.scrollToBottom(true);
                // only auto-focus where there's no on-screen keyboard to shove the layout around
                if (window.innerWidth >= 640) this.$refs.chatInput?.focus();
            });
        },
        closeChat() {
            this.panelOpen = false;
            this.minimized = false;
            this.unlockScroll();
        },
        // collapses to the slim "resume" bar — the order page behind becomes fully usable again,
        // which is the point: chatting shouldn't block checking/managing the order
        minimizeChat() {
            this.minimized = true;
            this.unlockScroll();
        },
        restoreChat() {
            this.minimized = false;
            this.unread = 0;
            this.lockScroll();
            this.$nextTick(() => this.scrollToBottom(true));
        },
        toggleExpand() {
            if (window.innerWidth < 640) return; // mobile is already full-screen; nothing to expand
            this.expanded = !this.expanded;
            this.customWidth = null;
            this.customHeight = null;
        },
        // the window is a full-screen sheet on phones — the page behind it must not scroll
        lockScroll() {
            if (window.innerWidth < 640 && this.viewingThread) document.documentElement.style.overflow = 'hidden';
        },
        unlockScroll() {
            document.documentElement.style.overflow = '';
        },

        // desktop-only manual resize, dragged from the panel's top-left corner grip. Clamped so
        // it can never grow to cover the whole page or shrink below a usable size.
        startResize(event) {
            if (window.innerWidth < 640) return;
            event.preventDefault();
            const rect = this.$refs.panel.getBoundingClientRect();
            this.resizeStart = { x: event.clientX, y: event.clientY, width: rect.width, height: rect.height };
            document.body.style.userSelect = 'none';
            const onMove = (e) => this.onResizeMove(e);
            const onUp = () => {
                this.resizeStart = null;
                document.body.style.userSelect = '';
                window.removeEventListener('mousemove', onMove);
                window.removeEventListener('mouseup', onUp);
            };
            window.addEventListener('mousemove', onMove);
            window.addEventListener('mouseup', onUp);
        },
        onResizeMove(event) {
            if (!this.resizeStart) return;
            const dx = this.resizeStart.x - event.clientX; // grip is top-left: dragging left/up grows the panel
            const dy = this.resizeStart.y - event.clientY;
            const maxW = Math.min(640, window.innerWidth - 32);
            const maxH = window.innerHeight - 48;
            this.customWidth = Math.min(maxW, Math.max(320, this.resizeStart.width + dx));
            this.customHeight = Math.min(maxH, Math.max(400, this.resizeStart.height + dy));
        },
        // inline size wins over the compact/expanded preset classes once the customer has
        // dragged the grip; left empty on mobile so the sm: full-screen classes stay in control
        sizeStyle() {
            if (window.innerWidth < 640) return '';
            const width = this.customWidth ?? (this.expanded ? 448 : 384);
            const height = this.customHeight ?? Math.min(window.innerHeight - 48, this.expanded ? 720 : 544);
            return `width: ${width}px; height: ${height}px;`;
        },

        async fetchMessages() {
            try {
                const url = new URL(`/orders/${orderId}/support`, window.location.origin);
                url.searchParams.set('after', this.lastId);
                // only an on-screen chat counts as "read" — the background badge poll must not
                if (this.viewingThread && !document.hidden) url.searchParams.set('read', 1);
                const res = await fetch(url, { headers: { Accept: 'application/json' } });
                if (!res.ok) return;
                const data = await res.json().catch(() => ({}));
                if (!data.ok) return;
                const incoming = (data.messages || []).filter((m) => m.id > this.lastId);
                if (incoming.length) {
                    this.appendMessages(incoming);
                    if (this.panelOpen && incoming.some((m) => m.sender === 'admin')) {
                        playChatPing();
                        if (this.viewingThread) this.$nextTick(() => this.scrollToBottom());
                    }
                }
                this.unread = this.viewingThread ? 0 : data.unread;
                this.loaded = true;
            } catch (e) {
                // offline / server hiccup — next tick will catch up
            }
        },
        appendMessages(list) {
            list.forEach((m) => {
                const prev = this.messages[this.messages.length - 1];
                m.showDay = !prev || prev.day !== m.day;
                this.messages.push(m);
                this.lastId = Math.max(this.lastId, m.id);
            });
        },

        // triggered by the paperclip/camera button's hidden <input type=file> — plain
        // accept="image/*" (no `capture` attribute) so mobile browsers offer BOTH "Take Photo"
        // and "Choose from Library" rather than forcing the camera
        onImageSelected(event) {
            const file = event.target.files[0];
            event.target.value = '';
            if (!file) return;
            if (!file.type.startsWith('image/')) {
                this.sendError = 'Please choose an image file.';
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                this.sendError = 'That image is too large — please pick one under 5MB.';
                return;
            }
            this.sendError = '';
            this.clearPendingImage();
            this.pendingImage = file;
            this.pendingImagePreview = URL.createObjectURL(file);
        },
        clearPendingImage() {
            if (this.pendingImagePreview) URL.revokeObjectURL(this.pendingImagePreview);
            this.pendingImage = null;
            this.pendingImagePreview = null;
        },

        async send(preset = null) {
            const text = (preset ?? this.draft).trim();
            const image = preset === null ? this.pendingImage : null;
            if ((!text && !image) || this.sending) return;
            this.sending = true;
            this.sendError = '';
            try {
                const csrf = document.querySelector('meta[name=csrf-token]').content;
                const body = new FormData();
                body.append('message', text);
                if (image) body.append('image', image);
                const res = await fetch(`/orders/${orderId}/support`, {
                    method: 'POST',
                    headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                    body,
                });
                if (res.status === 419) {
                    this.sendError = 'Your session expired — please refresh the page and try again.';
                    return;
                }
                const data = await res.json().catch(() => ({}));
                if (data.ok) {
                    this.appendMessages([data.message]);
                    this.draft = '';
                    this.clearPendingImage();
                    this.$nextTick(() => this.scrollToBottom(true));
                } else {
                    this.sendError = data.message || "Couldn't send — please try again.";
                }
            } catch (e) {
                this.sendError = 'Network error — please try again.';
            } finally {
                this.sending = false;
            }
        },

        // stick to the bottom on new activity, but never yank the view while the customer is
        // scrolled up re-reading older messages (unless it's their own message going out)
        scrollToBottom(force = false) {
            const el = this.$refs.chatThread;
            if (!el) return;
            const nearBottom = el.scrollHeight - el.scrollTop - el.clientHeight < 140;
            if (force || nearBottom) el.scrollTop = el.scrollHeight;
        },
    };
};

// account page — sidebar tabs (Profile / Addresses / Rewards) plus a full address-book
// CRUD reusing the same /addresses endpoints the checkout page's address picker uses
window.accountPage = function (initialAddresses, initialReward, initialTab, initialFavorites, initialOrderId) {
    return {
        tab: initialTab || 'profile',
        addresses: initialAddresses || [],
        reward: initialReward || { configured: false, required: 0, progress: 0, available: 0, gift_label: '' },

        showAddForm: false,
        editingId: null,
        saving: false,
        formError: '',
        form: { label: '', address_line: '' },

        // profile name lives in Alpine.store('user') (see app.js bottom), not local state — that
        // way editing it here also updates the navbar dropdown instantly, with no page reload
        editingName: false,
        nameDraft: '',
        savingName: false,
        nameError: '',

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

            // reopens whatever order the customer was looking at — see syncOrderUrl(), which
            // is what put ?tab=orders&order=ID in the address bar in the first place
            if (initialOrderId) this.viewOrder(initialOrderId);
        },
        // every tab button calls this (instead of assigning `tab` directly) so navigating away
        // from an open order also clears it from the address bar — otherwise a refresh from,
        // say, the Profile tab would bounce back into the order the customer already left
        setTab(t) {
            this.tab = t;
            if (this.viewingOrderId !== null) this.backToOrders();
            else this.syncOrderUrl(null);
        },

        startEditingName() {
            this.nameDraft = this.$store.user.name;
            this.nameError = '';
            this.editingName = true;
        },
        async saveName() {
            const trimmed = this.nameDraft.trim();
            if (!trimmed) {
                this.nameError = "Name can't be empty.";
                return;
            }
            this.savingName = true;
            this.nameError = '';
            try {
                const csrf = document.querySelector('meta[name=csrf-token]').content;
                const res = await fetch('/account/name', {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify({ name: trimmed }),
                });
                const data = await res.json().catch(() => ({}));
                if (data.ok) {
                    this.$store.user.name = data.name;
                    this.editingName = false;
                } else {
                    this.nameError = data.message || 'Could not save your name — please try again.';
                }
            } catch (e) {
                this.nameError = 'Network error, please try again.';
            } finally {
                this.savingName = false;
            }
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
            clearTimeout(root._sorryTimer);
            Alpine.destroyTree(root);
            panel.innerHTML = '';
        },
        async viewOrder(id) {
            this.tab = 'orders';
            this.viewingOrderId = id;
            this.orderLoadError = '';
            this.loadingOrder = true;
            this.syncOrderUrl(id);
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
            this.syncOrderUrl(null);
        },
        // keeps the address bar (and therefore a refresh) in sync with the active tab and
        // (if on Orders) which order is open — same copy-other-params-then-set-one pattern as
        // admin.js's performSearch()
        syncOrderUrl(id) {
            const url = new URL(window.location.pathname, window.location.origin);
            new URLSearchParams(window.location.search).forEach((value, key) => {
                if (key !== 'order' && key !== 'tab') url.searchParams.set(key, value);
            });
            url.searchParams.set('tab', this.tab);
            if (id) url.searchParams.set('order', id);
            window.history.replaceState(null, '', url.toString());
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

// site-wide Terms/Privacy popup (partials/legal-document-modal.blade.php) — content comes
// straight from window.__mbLegalDocs (embedded once in layouts/app.blade.php), so opening
// is instant with no fetch. Triggered from anywhere via the `open-legal-modal` window event.
window.legalDocumentModal = function () {
    return {
        open: false,
        type: null,
        title: '',
        content: '',
        updatedAt: '',

        init() {
            window.addEventListener('open-legal-modal', (e) => {
                const type = e.detail?.type;
                const doc = window.__mbLegalDocs?.[type];
                this.type = type;
                this.title = doc?.title || '';
                this.content = doc?.content || '';
                this.updatedAt = doc?.updatedAt || '';
                this.open = true;
            });
        },
        close() {
            this.open = false;
        },
    };
};

// forced re-consent gate (partials/reconsent-gate.blade.php) — only ever rendered by the
// server when $needsReconsent is true, so there's no client-side check to bypass; accepting
// just records the event and reloads so the layout re-evaluates and stops rendering the gate
window.reconsentGate = function () {
    return {
        agreeTerms: false,
        loading: false,
        error: '',
        accepted: false,

        async accept() {
            if (!this.agreeTerms || this.loading) return;
            this.loading = true;
            this.error = '';
            try {
                const csrf = document.querySelector('meta[name=csrf-token]').content;
                const res = await fetch('/consent/accept', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: JSON.stringify({ agree_terms: this.agreeTerms }),
                });
                const data = await res.json().catch(() => ({}));
                if (res.ok && data.ok) {
                    this.accepted = true;
                    window.location.reload();
                } else {
                    this.error = data.message || 'Something went wrong, please try again.';
                    this.loading = false;
                }
            } catch (e) {
                this.error = 'Network error, please check your connection and try again.';
                this.loading = false;
            }
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
            // a generic hook other components can nudge instead of waiting out this bell's own
            // independent 15s cycle — currently only orderTrackingPage() uses it, right after
            // detecting a fresh item removal on the order being viewed
            window.addEventListener('notifications-refresh', () => this.refresh());
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
            if (this.open) {
                this.$nextTick(() => {
                    this.positionPanel();
                    requestAnimationFrame(() => this.positionPanel());
                });
            }
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

        // the bell isn't the last icon in the header (avatar + hamburger follow it), so a
        // CSS-only anchor either overflows off-screen on narrow phones (anchored tight to the
        // bell) or lands with an awkward gap past the bell (anchored to the header's far edge).
        // Measuring the real position here gets the best of both: right-edge-aligned with the
        // bell whenever there's room, nudged just enough to stay on-screen when there isn't.
        //
        // Called once in $nextTick right after opening, then again on the following animation
        // frame — mobile browsers can report a slightly stale button position on that very first
        // call right after page load (web font swap, dynamic toolbar settling, etc.), so the
        // second pass self-corrects before the enter transition finishes fading in.
        positionPanel() {
            const button = this.$refs.bellButton;
            const panel = this.$refs.panel;
            if (!button || !panel) return;
            const buttonRect = button.getBoundingClientRect();
            const margin = 12;
            // clientWidth ignores any transient scrollbar/zoom quirks that innerWidth can report
            const vw = Math.min(window.innerWidth, document.documentElement.clientWidth) || window.innerWidth;
            const panelWidth = panel.offsetWidth || 320;

            // sanity guard: a bad/zero measurement (button not yet laid out) shouldn't produce a
            // wildly off-screen panel — fall back to flush-right-of-viewport instead
            if (!buttonRect.right || buttonRect.right < 0) {
                panel.style.right = `${margin}px`;
                panel.style.top = `${(button.getBoundingClientRect().bottom || 60) + 12}px`;
                return;
            }

            const rightEdge = Math.min(Math.max(buttonRect.right, margin + panelWidth), vw - margin);
            panel.style.right = `${vw - rightEdge}px`;
            panel.style.top = `${buttonRect.bottom + 12}px`;
        },
    };
};

// single source of truth for the logged-in customer's display name, so editing it on the account
// page (accountPage().saveName()) updates the navbar dropdown too, without a page reload
Alpine.store('user', {
    name: window.__mbUserName || '',
});

Alpine.store('shop', {
    accepting: (window.__mbShopStatus || {}).accepting !== false,
    restricted: (window.__mbShopStatus || {}).restricted === true,
    radiusKm: (window.__mbShopStatus || {}).radiusKm || 7,
    toast: null,

    // operational fees — mirrors ShopSetting::activeFees()/deliveryFeeFor() (the server is
    // always the authority at checkout; these just drive an instant client-side preview,
    // refreshed live by pollShopStatus() below so an admin change needs no reload anywhere
    deliveryFeeStrategy: (window.__mbShopStatus || {}).deliveryFeeStrategy || 'fixed',
    deliveryFreeMinOrder: (window.__mbShopStatus || {}).deliveryFreeMinOrder || 0,
    deliveryFeeBelowMinimum: (window.__mbShopStatus || {}).deliveryFeeBelowMinimum || 0,
    deliveryFeeFixed: (window.__mbShopStatus || {}).deliveryFeeFixed || 0,
    deliverySuccessMessage: (window.__mbShopStatus || {}).deliverySuccessMessage || 'Free Delivery Unlocked! 🚚',
    deliverySuccessAnimation: (window.__mbShopStatus || {}).deliverySuccessAnimation || 'confetti_truck',
    rainFeeEnabled: (window.__mbShopStatus || {}).rainFeeEnabled === true,
    rainFeeAmount: (window.__mbShopStatus || {}).rainFeeAmount || 0,
    rainFeeMessage: (window.__mbShopStatus || {}).rainFeeMessage || null,
    highDemandMode: (window.__mbShopStatus || {}).highDemandMode || 'normal',
    highDemandFeeAmount: (window.__mbShopStatus || {}).highDemandFeeAmount || 0,
    highDemandFeeMessage: (window.__mbShopStatus || {}).highDemandFeeMessage || null,
    highDemandStopMessage: (window.__mbShopStatus || {}).highDemandStopMessage || null,
    deliveryTimeMinutes: (window.__mbShopStatus || {}).deliveryTimeMinutes || 0,
    codEnabled: (window.__mbShopStatus || {}).codEnabled !== false,
    razorpayEnabled: (window.__mbShopStatus || {}).razorpayEnabled !== false,

    deliveryFee(subtotal) {
        if (this.deliveryFeeStrategy === 'free_above_minimum') {
            return subtotal >= this.deliveryFreeMinOrder ? 0 : this.deliveryFeeBelowMinimum;
        }
        return this.deliveryFeeFixed;
    },
    amountToFreeDelivery(subtotal) {
        if (this.deliveryFeeStrategy !== 'free_above_minimum') return 0;
        return Math.max(0, this.deliveryFreeMinOrder - subtotal);
    },
    rainFee() {
        return this.rainFeeEnabled ? this.rainFeeAmount : 0;
    },
    highDemandFee() {
        return this.highDemandMode === 'fee' ? this.highDemandFeeAmount : 0;
    },
    totalFees(subtotal) {
        return this.deliveryFee(subtotal) + this.rainFee() + this.highDemandFee();
    },
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
            store.deliveryFeeStrategy = data.delivery_fee_strategy || store.deliveryFeeStrategy;
            store.deliveryFreeMinOrder = data.delivery_free_min_order || 0;
            store.deliveryFeeBelowMinimum = data.delivery_fee_below_minimum || 0;
            store.deliveryFeeFixed = data.delivery_fee_fixed || 0;
            store.deliverySuccessMessage = data.delivery_success_message || store.deliverySuccessMessage;
            store.deliverySuccessAnimation = data.delivery_success_animation || store.deliverySuccessAnimation;
            store.rainFeeEnabled = data.rain_fee_enabled === true;
            store.rainFeeAmount = data.rain_fee_amount || 0;
            store.rainFeeMessage = data.rain_fee_message || null;
            store.highDemandMode = data.high_demand_mode || 'normal';
            store.highDemandFeeAmount = data.high_demand_fee_amount || 0;
            store.highDemandFeeMessage = data.high_demand_fee_message || null;
            store.highDemandStopMessage = data.high_demand_stop_message || null;
            store.deliveryTimeMinutes = data.delivery_time_estimate_minutes || 0;
            store.codEnabled = data.cod_enabled !== false;
            store.razorpayEnabled = data.razorpay_enabled !== false;
        })
        .catch(() => {});
}
setInterval(pollShopStatus, 5000);

// PWA installability — bare passthrough service worker (see public/sw.js), registered only from
// this customer-facing bundle, never admin.js/rider.js. Pairs with window.installPrompt() below.
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js'));
}

window.Alpine = Alpine;
Alpine.start();
