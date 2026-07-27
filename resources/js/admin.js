import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';
import Quill from 'quill';
import 'quill/dist/quill.snow.css';
import Cropper from 'cropperjs';
import 'cropperjs/dist/cropper.css';

const palette = {
    maroon: '#7a1622',
    maroonDark: '#3a0b12',
    gold: '#c8962e',
    goldLight: '#e9c873',
    pista: '#3d7a52',
    cream: '#fdf6e9',
    red: '#dc2626',
};

// formats a millisecond duration as a digital-clock string — "MM:SS", or "H:MM:SS" once
// it crosses an hour (ETAs cap at 240 min, so this only ever shows up to "3:59:59")
function formatClock(diffMs) {
    const totalSeconds = Math.max(0, Math.round(diffMs / 1000));
    const hours = Math.floor(totalSeconds / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = totalSeconds % 60;
    const mm = String(minutes).padStart(2, '0');
    const ss = String(seconds).padStart(2, '0');
    return hours > 0 ? `${hours}:${mm}:${ss}` : `${mm}:${ss}`;
}

// two-tone chime played through Web Audio — no sound file needed
function playOrderChime() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const now = ctx.currentTime;
        [[880, 0], [1174.66, 0.18]].forEach(([freq, delay]) => {
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.value = freq;
            gain.gain.setValueAtTime(0.0001, now + delay);
            gain.gain.exponentialRampToValueAtTime(0.35, now + delay + 0.02);
            gain.gain.exponentialRampToValueAtTime(0.0001, now + delay + 0.6);
            osc.connect(gain).connect(ctx.destination);
            osc.start(now + delay);
            osc.stop(now + delay + 0.65);
        });
    } catch (e) {
        // audio blocked before first user interaction — nothing else to do
    }
}

// softer, shorter two-note pop for an incoming support-chat message — distinct from the
// insistent new-order chime so the admin can tell them apart by ear
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
            gain.gain.exponentialRampToValueAtTime(0.2, now + delay + 0.02);
            gain.gain.exponentialRampToValueAtTime(0.0001, now + delay + 0.4);
            osc.connect(gain).connect(ctx.destination);
            osc.start(now + delay);
            osc.stop(now + delay + 0.45);
        });
    } catch (e) {
        // audio blocked before first user interaction — nothing else to do
    }
}

// bright three-note "cha-ching" for a rider marking COD cash as collected — distinct in shape
// from both the insistent new-order chime and the softer chat ping so an admin can tell them
// apart by ear without looking at the screen
function playPaymentChime() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const now = ctx.currentTime;
        [[988, 0], [1318.5, 0.1], [1568, 0.2]].forEach(([freq, delay]) => {
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'triangle';
            osc.frequency.value = freq;
            gain.gain.setValueAtTime(0.0001, now + delay);
            gain.gain.exponentialRampToValueAtTime(0.3, now + delay + 0.02);
            gain.gain.exponentialRampToValueAtTime(0.0001, now + delay + 0.5);
            osc.connect(gain).connect(ctx.destination);
            osc.start(now + delay);
            osc.stop(now + delay + 0.55);
        });
    } catch (e) {
        // audio blocked before first user interaction — nothing else to do
    }
}

// customizable sidebar order — drag-and-drop, persisted per-browser via localStorage. Dashboard
// is deliberately never part of this list at all (it's rendered as its own fixed row in the
// blade, above this component entirely) so there's no way to drag it out of first place.
window.adminNavMenu = function (initialItems) {
    return {
        items: initialItems || [],
        dragIndex: null,
        overIndex: null,

        init() {
            this.applySavedOrder();
        },
        applySavedOrder() {
            let saved;
            try {
                saved = JSON.parse(localStorage.getItem('mb_admin_nav_order') || 'null');
            } catch (e) {
                saved = null;
            }
            if (!Array.isArray(saved) || saved.length === 0) return;

            const byRoute = new Map(this.items.map((item) => [item.route, item]));
            const ordered = saved.map((route) => byRoute.get(route)).filter(Boolean);
            // anything new since the order was last saved (a nav item added after the admin
            // last customized their sidebar) lands at the end instead of vanishing
            const remaining = this.items.filter((item) => !saved.includes(item.route));
            this.items = [...ordered, ...remaining];
        },
        persistOrder() {
            localStorage.setItem('mb_admin_nav_order', JSON.stringify(this.items.map((item) => item.route)));
        },
        dragStart(index, event) {
            this.dragIndex = index;
            event.dataTransfer.effectAllowed = 'move';
        },
        dragOver(index, event) {
            event.preventDefault();
            this.overIndex = index;
        },
        dragLeave(index) {
            if (this.overIndex === index) this.overIndex = null;
        },
        drop(index) {
            if (this.dragIndex === null || this.dragIndex === index) {
                this.dragIndex = null;
                this.overIndex = null;
                return;
            }
            const moved = this.items.splice(this.dragIndex, 1)[0];
            this.items.splice(index, 0, moved);
            this.dragIndex = null;
            this.overIndex = null;
            this.persistOrder();
        },
        dragEnd() {
            this.dragIndex = null;
            this.overIndex = null;
        },
    };
};

window.adminNotifier = function (initialLatestId, isOrdersIndex) {
    return {
        lastSeen: initialLatestId,
        since: null,
        pendingCount: null,
        queue: [],
        activeOrder: null,
        acting: false,
        etaMinutes: 30,
        soundInterval: null,
        soundStopTimer: null,
        autoCancelWarnings: [],
        codPaymentToasts: [],
        // orders already toasted for "COD payment received" — without this, an order touched
        // again later for an unrelated reason (rider marks delivered, admin adds an item) would
        // still carry payment_status: 'paid' and would otherwise re-trigger the celebration toast
        // every time it reappears in the time-cursor diff below. Same idiom as poppedIds.
        notifiedPaidIds: new Set(),
        supportUnread: 0,
        supportToasts: [],
        // null until the first poll so a page load never replays a chime/toast for a message
        // that arrived while no admin page was open
        lastSupportId: null,
        // orders already offered for Accept/Reject — tracked separately from lastSeen so a
        // razorpay order (queued only once payment actually clears, which can be several poll
        // ticks after it first appears) is never queued for the popup twice
        poppedIds: new Set(),

        init() {
            this.poll();
            setInterval(() => this.poll(), 5000);
        },
        async poll() {
            try {
                const url = new URL('/admin/orders/poll', window.location.origin);
                if (this.since !== null) url.searchParams.set('since', this.since);
                const res = await fetch(url, { headers: { Accept: 'application/json' } });
                if (!res.ok) return;
                const data = await res.json();
                this.pendingCount = data.pending_count;
                this.since = data.server_now;

                if (data.support) {
                    this.supportUnread = data.support.unread;
                    if (this.lastSupportId === null) {
                        this.lastSupportId = data.support.latest_id;
                    } else if (data.support.latest_id > this.lastSupportId) {
                        this.lastSupportId = data.support.latest_id;
                        // the open thread page announces itself via this flag — no ping/toast
                        // for a conversation the admin is literally looking at
                        if (data.support.latest && data.support.latest.order_id !== window.__activeSupportOrderId) {
                            playChatPing();
                            const key = Date.now() + Math.random();
                            this.supportToasts.push({ ...data.support.latest, _key: key });
                            setTimeout(() => {
                                this.supportToasts = this.supportToasts.filter((t) => t._key !== key);
                            }, 12000);
                        }
                    }
                }

                if (data.auto_cancelled && data.auto_cancelled.length) {
                    data.auto_cancelled.forEach((order) => {
                        const id = Date.now() + Math.random();
                        this.autoCancelWarnings.push({ ...order, _key: id });
                        setTimeout(() => {
                            this.autoCancelWarnings = this.autoCancelWarnings.filter((w) => w._key !== id);
                        }, 12000);
                    });
                }

                if (data.orders && data.orders.length) {
                    // a rider ticking "Mark Payment Received" on a COD order is the only way it
                    // ever becomes payment_status: 'paid' (see Order::codPaymentReceived) — surface
                    // it live with its own chime + toast, distinct from the persistent bell entry
                    // the same event also lands in (see OrderPaymentReceived notification)
                    data.orders.forEach((o) => {
                        const isCodPaid = o.payment_method !== 'RAZORPAY' && o.payment_status === 'paid';
                        if (!isCodPaid) return;
                        if (this.notifiedPaidIds.has(o.id)) return;
                        this.notifiedPaidIds.add(o.id);

                        playPaymentChime();
                        const key = Date.now() + Math.random();
                        this.codPaymentToasts.push({ ...o, _key: key });
                        setTimeout(() => {
                            this.codPaymentToasts = this.codPaymentToasts.filter((t) => t._key !== key);
                        }, 12000);
                    });

                    // brand new (never seen before) vs. an update to an order already on screen —
                    // an existing order coming back through this same time-cursor poll (e.g. a rider
                    // marking it delivered) must never re-trigger the new-order popup/chime
                    const newIds = data.orders.filter((o) => o.id > this.lastSeen).map((o) => o.id);

                    // let any listening page (e.g. the orders list) react to changes in real time,
                    // whether that's a freshly-placed order or a rider-driven status change
                    window.dispatchEvent(new CustomEvent('admin-orders-changed', { detail: { orders: data.orders, newIds } }));

                    // a razorpay order row exists the instant "Pay Online" is clicked — well before
                    // the customer actually completes payment (or abandons/fails it). It must not
                    // prompt Accept/Reject until payment_status confirms paid, which can arrive
                    // several poll ticks after the order first appears (hence poppedIds, separate
                    // from the lastSeen watermark). COD orders are actionable immediately as before.
                    const actionable = (o) => o.status === 'pending' && (o.payment_method !== 'RAZORPAY' || o.payment_status === 'paid');
                    this.queue.push(...data.orders.filter((o) => actionable(o) && !this.poppedIds.has(o.id)));
                    data.orders.filter(actionable).forEach((o) => this.poppedIds.add(o.id));

                    this.lastSeen = Math.max(this.lastSeen, ...data.orders.map((o) => o.id));
                    if (!this.activeOrder) this.showNext();
                }
            } catch (e) {
                // offline / server restarting — try again next tick
            }
        },
        showNext() {
            if (this.queue.length === 0) {
                this.activeOrder = null;
                return;
            }
            this.activeOrder = this.queue.shift();
            this.acting = false;
            this.etaMinutes = 30;
            this.startSound();
        },
        startSound() {
            this.stopSound();
            playOrderChime();
            this.soundInterval = setInterval(() => playOrderChime(), 1200);
            this.soundStopTimer = setTimeout(() => this.stopSound(), 10000);
        },
        stopSound() {
            clearInterval(this.soundInterval);
            clearTimeout(this.soundStopTimer);
            this.soundInterval = null;
            this.soundStopTimer = null;
        },
        async respond(status) {
            if (!this.activeOrder || this.acting) return;
            this.acting = true;
            this.stopSound();
            try {
                const csrf = document.querySelector('meta[name=csrf-token]').content;
                const payload = { status };
                if (status === 'confirmed') payload.eta_minutes = this.etaMinutes;
                await fetch(`/admin/orders/${this.activeOrder.id}/status`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: JSON.stringify(payload),
                });
            } catch (e) {
                // network hiccup — the order stays pending and remains visible in the orders list
            }
            const shouldReload = isOrdersIndex && this.queue.length === 0;
            this.activeOrder = null;
            if (this.queue.length > 0) {
                setTimeout(() => this.showNext(), 400);
            } else if (shouldReload) {
                window.location.reload();
            }
        },
        dismiss() {
            this.stopSound();
            this.activeOrder = null;
            setTimeout(() => this.showNext(), 300);
        },
        openOrders() {
            window.location.href = '/admin/orders';
        },
    };
};

const statusBadgeStyles = {
    pending: 'bg-gold-100 text-gold-600 border-gold-300/60',
    confirmed: 'bg-pista-100 text-pista-600 border-pista-400/40',
    out_for_delivery: 'bg-sky-50 text-sky-600 border-sky-200',
    delivered: 'bg-purple-100 text-purple-600 border-purple-300',
    cancelled: 'bg-red-50 text-red-600 border-red-200',
};

// server-translated (see partials/order-status-i18n.blade.php) so live-updated rows respect
// the admin's own Hindi/English choice instead of always showing English
const statusLabels = window.ORDER_STATUS_LABELS || {
    pending: 'Pending',
    confirmed: 'Confirmed',
    out_for_delivery: 'Out for Delivery',
    delivered: 'Delivered',
    cancelled: 'Cancelled',
};

// live-inserts freshly-placed orders and live-patches status changes on orders already on
// screen (e.g. a rider marking one delivered) — all without a page reload, driven by the
// 'admin-orders-changed' event adminNotifier() broadcasts on every poll tick
window.ordersLivePage = function (config) {
    const filters = config.filters || {};
    const hasIncomingFilters = !!(config.search || filters.quick_filter || filters.payment_status
        || filters.payment_method || filters.rider_id || filters.amount_min || filters.amount_max || config.statusFilter);

    return {
        orders: config.orders || [],
        missedCount: 0,
        now: Date.now(),
        loading: false,

        // collapsed by default so the order table sits right under the page title instead of
        // below a full screen of cards/chips — remembered per-browser, but a link that arrives
        // with a filter already applied (e.g. from Income History, or a bookmarked/shared URL)
        // opens the panel automatically so the admin isn't confused about why the table looks
        // filtered with no visible explanation
        panelOpen: localStorage.getItem('mb_admin_orders_panel_open') !== null
            ? localStorage.getItem('mb_admin_orders_panel_open') === '1'
            : hasIncomingFilters,

        // every filter dimension lives here as reactive state — one applyFilters() call builds
        // the URL from all of them at once, so any combination (quick date + payment status +
        // rider + amount range + free-text search, all together) is just "whatever's currently
        // set", never a special case to wire up per combination
        statusFilter: config.statusFilter || '',
        search: config.search || '',
        quickFilter: filters.quick_filter || '',
        dateFrom: filters.from || '',
        dateTo: filters.to || '',
        paymentStatus: filters.payment_status || '',
        paymentMethod: filters.payment_method || '',
        riderId: filters.rider_id || '',
        amountMin: filters.amount_min || '',
        amountMax: filters.amount_max || '',

        searching: hasIncomingFilters,
        totalMatches: null,
        currentPage: config.currentPage,
        perPage: config.perPage,
        filterTimer: null,

        stats: config.stats || {},

        init() {
            // ticks the digital-clock countdown shown next to confirmed / out-for-delivery orders
            setInterval(() => { this.now = Date.now(); }, 1000);
        },
        togglePanel() {
            this.panelOpen = !this.panelOpen;
            localStorage.setItem('mb_admin_orders_panel_open', this.panelOpen ? '1' : '0');
        },
        statusBadgeClasses(status) {
            return statusBadgeStyles[status] || statusBadgeStyles.pending;
        },
        statusLabel(status) {
            return statusLabels[status] || status;
        },
        // live digital-clock countdown text next to an order's status badge — counts down to the
        // promised time, then counts up as overdue once it passes
        etaText(order) {
            if (!order.eta_ends_at || !['confirmed', 'out_for_delivery'].includes(order.status)) return null;
            const clock = formatClock(Math.abs(order.eta_ends_at - this.now));
            return order.eta_ends_at > this.now ? clock : `+${clock}`;
        },
        isOverdue(order) {
            return !!order.eta_ends_at && order.eta_ends_at <= this.now;
        },
        handleOrdersChanged({ orders: changed, newIds }) {
            const toInsert = [];

            changed.forEach((incoming) => {
                const idx = this.orders.findIndex((o) => o.id === incoming.id);

                if (idx !== -1) {
                    // already on screen — patch it in place (status, eta, coupon, etc.), unless it no
                    // longer matches the active status filter, in which case it drops off this view
                    if (this.statusFilter !== '' && incoming.status !== this.statusFilter) {
                        this.orders.splice(idx, 1);
                    } else {
                        this.orders[idx] = { ...this.orders[idx], ...incoming, _new: this.orders[idx]._new };
                    }
                    return;
                }

                if (!newIds.includes(incoming.id)) return; // an update for a row not currently visible — ignore
                toInsert.push(incoming);
            });

            if (toInsert.length === 0) return;

            // brand-new orders are always 'pending' and sort newest-first, so they only belong at
            // the top of an unfiltered/pending view of page 1 — anywhere else (including any active
            // filter, which a fresh order likely doesn't match), just flag that some arrived
            const applicable = !this.searching && this.currentPage === 1 && (this.statusFilter === '' || this.statusFilter === 'pending');
            if (!applicable) {
                this.missedCount += toInsert.length;
                return;
            }

            const rows = toInsert.map((o) => ({ ...o, _new: true }));
            this.orders = [...rows, ...this.orders].slice(0, this.perPage);
            setTimeout(() => {
                rows.forEach((r) => {
                    const row = this.orders.find((o) => o.id === r.id);
                    if (row) row._new = false;
                });
            }, 2500);
        },

        setStatus(status) {
            this.statusFilter = this.statusFilter === status ? '' : status;
            this.applyFilters();
        },
        setQuickFilter(value) {
            this.quickFilter = this.quickFilter === value ? '' : value;
            if (this.quickFilter !== 'custom') {
                this.dateFrom = '';
                this.dateTo = '';
            }
            this.applyFilters();
        },
        onFilterInput(delay = 400) {
            clearTimeout(this.filterTimer);
            this.filterTimer = setTimeout(() => this.applyFilters(), delay);
        },
        clearFilters() {
            this.statusFilter = '';
            this.search = '';
            this.quickFilter = '';
            this.dateFrom = '';
            this.dateTo = '';
            this.paymentStatus = '';
            this.paymentMethod = '';
            this.riderId = '';
            this.amountMin = '';
            this.amountMax = '';
            this.applyFilters();
        },
        hasActiveFilters() {
            return !!(this.search || this.quickFilter || this.paymentStatus || this.paymentMethod
                || this.riderId || this.amountMin || this.amountMax || this.statusFilter);
        },

        // every filter maps to one query string — shared by the AJAX refresh below and the
        // Export-to-Excel link, so "export only the currently filtered data" just falls out of
        // both reading from the exact same place, nothing to keep in sync by hand
        buildParams() {
            const params = new URLSearchParams();
            if (this.statusFilter) params.set('status', this.statusFilter);
            if (this.search.trim()) params.set('q', this.search.trim());
            if (this.quickFilter) params.set('quick_filter', this.quickFilter);
            if (this.quickFilter === 'custom') {
                if (this.dateFrom) params.set('from', this.dateFrom);
                if (this.dateTo) params.set('to', this.dateTo);
            }
            if (this.paymentStatus) params.set('payment_status', this.paymentStatus);
            if (this.paymentMethod) params.set('payment_method', this.paymentMethod);
            if (this.riderId) params.set('rider_id', this.riderId);
            if (this.amountMin) params.set('amount_min', this.amountMin);
            if (this.amountMax) params.set('amount_max', this.amountMax);

            const currentPerPage = new URLSearchParams(window.location.search).get('per_page');
            if (currentPerPage) params.set('per_page', currentPerPage);

            return params;
        },
        exportUrl() {
            return `${config.exportBaseUrl}?${this.buildParams().toString()}`;
        },

        // instant filtering — any chip/select/date/amount/search change lands here, debounced
        // for free-text/number inputs (onFilterInput) or immediate for chips/selects. Always
        // resets to page 1 of the new filtered set; paging through a large filtered result still
        // goes through a normal (filter-preserving, see withQueryString) page reload — same as
        // this table's pagination already worked before this feature existed.
        async applyFilters() {
            this.loading = true;
            this.currentPage = 1;
            const params = this.buildParams();
            const url = new URL(window.location.pathname, window.location.origin);
            url.search = params.toString();

            try {
                const res = await fetch(url.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' } });
                const data = await res.json();
                this.orders = (data.orders || []).map((o) => ({ ...o, _new: false }));
                this.totalMatches = data.total;
                this.stats = data.stats || this.stats;
                this.searching = this.hasActiveFilters();
                window.history.replaceState(null, '', url.toString());
            } catch (e) {
                // network hiccup — leave the last results in place, changing a filter again will retry
            } finally {
                this.loading = false;
            }
        },
    };
};

// live search box used on every admin grid (Products/Coupons/Customers/Visitors) — debounces
// keystrokes, fetches the same index route as an AJAX partial, and swaps the results container's
// HTML in place so typing filters instantly without a page reload or pressing Enter
window.liveGridSearch = function (containerId) {
    return {
        q: '',
        loading: false,
        timer: null,
        onInput() {
            clearTimeout(this.timer);
            this.timer = setTimeout(() => this.search(), 300);
        },
        async search() {
            clearTimeout(this.timer);
            this.loading = true;
            const url = new URL(window.location.pathname, window.location.origin);
            new URLSearchParams(window.location.search).forEach((value, key) => {
                if (key !== 'q' && key !== 'page') url.searchParams.set(key, value);
            });
            const trimmed = this.q.trim();
            if (trimmed) url.searchParams.set('q', trimmed);

            try {
                const res = await fetch(url.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' } });
                const html = await res.text();
                const container = document.getElementById(containerId);
                if (container) container.innerHTML = html;
                window.history.replaceState(null, '', url.toString());
            } catch (e) {
                // network hiccup — leave the last results in place, typing again will retry
            } finally {
                this.loading = false;
            }
        },
    };
};

// live digital-clock countdown shown on an order's detail page while it's confirmed / out for
// delivery — ticks every second, counting down to the promised time and then counting up as
// "overdue by" once it passes
window.etaCountdown = function (etaEndsAtMs) {
    return {
        now: Date.now(),
        init() {
            setInterval(() => { this.now = Date.now(); }, 1000);
        },
        get overdue() {
            return this.now >= etaEndsAtMs;
        },
        get clock() {
            return formatClock(Math.abs(etaEndsAtMs - this.now));
        },
    };
};

const stepRank = { pending: 1, confirmed: 2, out_for_delivery: 3, delivered: 4 };

// order-detail page — polls so a rider-driven change (status, delivery photo) shows up live
// without the admin reloading; the item list / customer info / rider-assign form stay static
// server-rendered since only the admin's own actions can change those
window.adminOrderShowPage = function (initialOrder, orderId, cancelledReasons) {
    return {
        order: initialOrder,

        init() {
            setInterval(() => this.poll(), 5000);
        },
        rank() {
            return stepRank[this.order.status] || 0;
        },
        stepState(step) {
            if (this.order.status === 'cancelled') return step === 1 ? 'done' : 'todo';
            // an order still awaiting Razorpay confirmation isn't actually progressing toward
            // "Confirmed" — the real next step is the payment clearing, not the shop acting, so
            // don't highlight "Confirmed" as if it's already next in line
            if (this.order.status === 'pending' && this.order.payment_method === 'RAZORPAY' && this.order.payment_status !== 'paid') {
                return step === 1 ? 'done' : 'todo';
            }
            const r = this.rank();
            if (step <= r) return 'done';
            if (step === r + 1) return 'active';
            return 'todo';
        },
        stepTime(step) {
            return [null, this.order.placed_at, this.order.confirmed_at, this.order.out_for_delivery_at, this.order.delivered_at][step];
        },
        statusBadgeClasses() {
            return statusBadgeStyles[this.order.status] || statusBadgeStyles.pending;
        },
        statusLabel() {
            return statusLabels[this.order.status] || this.order.status;
        },
        cancelledReasonText() {
            const who = cancelledReasons[this.order.cancelled_by] || '';
            return this.order.cancellation_reason ? `${who} — "${this.order.cancellation_reason}"` : who;
        },

        // internal packing checklist — purely local UI state backed by order_items.confirmed_at;
        // never touches the order's actual status, never visible to the customer
        isItemConfirmed(itemId) {
            return !!this.order.confirmed_items?.[itemId];
        },
        confirmedItemsCount() {
            return Object.values(this.order.confirmed_items || {}).filter(Boolean).length;
        },
        totalItemsCount() {
            return Object.keys(this.order.confirmed_items || {}).length;
        },
        allItemsConfirmed() {
            return this.totalItemsCount() > 0 && this.confirmedItemsCount() === this.totalItemsCount();
        },
        itemsLocked() {
            return ['out_for_delivery', 'delivered', 'cancelled'].includes(this.order.status);
        },
        async toggleItemConfirmed(itemId) {
            if (this.itemsLocked()) return;
            const was = this.isItemConfirmed(itemId);
            this.order.confirmed_items = { ...this.order.confirmed_items, [itemId]: !was };
            try {
                const csrf = document.querySelector('meta[name=csrf-token]').content;
                const res = await fetch(`/admin/orders/${orderId}/items/${itemId}/confirm`, {
                    method: 'PATCH',
                    headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                });
                const data = await res.json().catch(() => ({}));
                if (!data.ok) throw new Error('failed');
                this.order.confirmed_items = { ...this.order.confirmed_items, [itemId]: data.confirmed };
            } catch (e) {
                this.order.confirmed_items = { ...this.order.confirmed_items, [itemId]: was };
            }
        },
        async confirmAllItems() {
            if (this.itemsLocked()) return;
            const snapshot = { ...this.order.confirmed_items };
            const allTrue = {};
            Object.keys(snapshot).forEach((id) => { allTrue[id] = true; });
            this.order.confirmed_items = allTrue;
            try {
                const csrf = document.querySelector('meta[name=csrf-token]').content;
                const res = await fetch(`/admin/orders/${orderId}/items/confirm-all`, {
                    method: 'POST',
                    headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                });
                const data = await res.json().catch(() => ({}));
                if (!data.ok) throw new Error('failed');
                this.order.confirmed_items = data.confirmed_items;
            } catch (e) {
                this.order.confirmed_items = snapshot;
            }
        },

        async poll() {
            // unlike the customer tracking page, keep polling even once delivered — a proof-of-delivery
            // photo can still land moments after the rider marks the order delivered; only a cancelled
            // order is truly terminal with nothing further to show
            if (this.order.status === 'cancelled') return;
            try {
                const res = await fetch(`/admin/orders/${orderId}/status`, { headers: { Accept: 'application/json' } });
                if (!res.ok) return;
                const data = await res.json().catch(() => ({}));
                if (data.ok) this.order = data.order;
            } catch (e) {
                // offline / server hiccup — next tick will catch up
            }
        },
    };
};

// admin notification center — mirrors window.notificationsBell() in app.js exactly (same JSON
// shape, same poll/mark-read/clear pattern), just pointed at the admin-guard endpoints so
// order-cancellation alerts (and any future admin notification) show up the same way
window.adminNotificationsBell = function () {
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
                const res = await fetch('/admin/notifications', { headers: { Accept: 'application/json' } });
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
                await fetch('/admin/notifications/mark-read', {
                    method: 'POST',
                    headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                });
                this.unread = 0;
            } catch (e) {}
        },
        async clear(id) {
            const csrf = document.querySelector('meta[name=csrf-token]').content;
            this.notifications = this.notifications.filter((n) => n.id !== id);
            try {
                await fetch(`/admin/notifications/${id}`, {
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
                await fetch('/admin/notifications/clear', {
                    method: 'DELETE',
                    headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                });
            } catch (e) {}
        },
    };
};

window.settingToggle = function (initial, endpoint) {
    return {
        on: initial,
        updating: false,
        async toggle() {
            if (this.updating) return;
            this.updating = true;
            const previous = this.on;
            this.on = !this.on; // optimistic
            try {
                const csrf = document.querySelector('meta[name=csrf-token]').content;
                const res = await fetch(endpoint, {
                    method: 'PATCH',
                    headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                });
                const data = await res.json().catch(() => ({}));
                if (data.ok) {
                    this.on = data.value;
                } else {
                    this.on = previous;
                }
            } catch (e) {
                this.on = previous;
            } finally {
                this.updating = false;
            }
        },
    };
};

// Payment Methods card (admin/configuration.blade.php) — two mutually-constrained switches
// (COD / Razorpay). Not built on settingToggle() above: a rejected toggle here needs a visible
// reason ("at least one must stay enabled"), which the generic component doesn't surface, and
// the constraint itself is enforced server-side (this just displays whatever it says).
window.paymentMethodsToggle = function (codEnabled, razorpayEnabled, codEndpoint, razorpayEndpoint) {
    return {
        cod: codEnabled,
        razorpay: razorpayEnabled,
        updating: false,
        error: null,

        async toggleCod() {
            await this.flip('cod', codEndpoint);
        },
        async toggleRazorpay() {
            await this.flip('razorpay', razorpayEndpoint);
        },
        async flip(key, endpoint) {
            if (this.updating) return;
            this.updating = true;
            this.error = null;
            const previous = this[key];
            this[key] = !this[key]; // optimistic
            try {
                const csrf = document.querySelector('meta[name=csrf-token]').content;
                const res = await fetch(endpoint, {
                    method: 'PATCH',
                    headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                });
                const data = await res.json().catch(() => ({}));
                if (data.ok) {
                    this[key] = data.value;
                } else {
                    this[key] = previous;
                    this.error = data.message || 'Something went wrong, please try again.';
                }
            } catch (e) {
                this[key] = previous;
                this.error = 'Network error, please check your connection and try again.';
            } finally {
                this.updating = false;
            }
        },
    };
};

// send-notification modal on the Customers pages — opened via the global
// `open-notify-modal` event so grid-row buttons keep working after the live-search
// AJAX partial swap replaces them (inline onclick survives innerHTML swaps)
window.notifyCustomerModal = function () {
    return {
        open: false,
        toAll: false,
        targetId: null,
        targetName: '',
        title: '',
        message: '',
        sending: false,
        sent: false,
        sentCount: 0,
        error: '',

        init() {
            window.addEventListener('open-notify-modal', (e) => {
                this.toAll = !!(e.detail && e.detail.all);
                this.targetId = e.detail?.id ?? null;
                this.targetName = e.detail?.name ?? '';
                this.title = '';
                this.message = '';
                this.error = '';
                this.sent = false;
                this.open = true;
            });
        },
        async send() {
            if (this.sending) return;
            this.error = '';
            if (!this.title.trim() || !this.message.trim()) {
                this.error = 'Please fill in both the title and the message.';
                return;
            }
            this.sending = true;
            try {
                const csrf = document.querySelector('meta[name=csrf-token]').content;
                const url = this.toAll ? '/admin/customers/notify-all' : `/admin/customers/${this.targetId}/notify`;
                const res = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify({ title: this.title.trim(), message: this.message.trim() }),
                });
                const data = await res.json().catch(() => ({}));
                if (res.ok && data.ok) {
                    this.sent = true;
                    this.sentCount = data.sent || 1;
                    setTimeout(() => { this.open = false; }, 1800);
                } else {
                    this.error = data.message || 'Could not send the notification.';
                }
            } catch (e) {
                this.error = 'Network error, please try again.';
            } finally {
                this.sending = false;
            }
        },
    };
};

// Block Customer modal — opened via the global `open-block-modal` event from the customer list
// or the customer detail page. Selecting a reason auto-fills a clear default customer-facing
// message (from User::DEFAULT_BLOCK_MESSAGES, passed in from the Blade partial); the admin can
// freely edit it before confirming, and sees exactly what the customer will see in the preview.
window.blockCustomerModal = function (reasons, defaultMessages) {
    return {
        open: false,
        targetId: null,
        targetName: '',
        reasons,
        reason: '',
        message: '',
        notes: '',
        sending: false,
        blocked: false,
        error: '',

        init() {
            window.addEventListener('open-block-modal', (e) => {
                this.targetId = e.detail?.id ?? null;
                this.targetName = e.detail?.name ?? '';
                this.reason = '';
                this.message = '';
                this.notes = '';
                this.error = '';
                this.blocked = false;
                this.open = true;
            });
        },
        selectReason(key) {
            this.reason = key;
            // only overwrite the textarea if it's still blank or still holds another reason's
            // default — an admin who's already started typing their own message never gets it
            // silently replaced by clicking around between reason cards
            const isUntouched = this.message.trim() === '' || Object.values(defaultMessages).includes(this.message);
            if (isUntouched) this.message = defaultMessages[key] || '';
        },
        async submit() {
            if (this.sending || !this.reason) return;
            this.error = '';
            this.sending = true;
            try {
                const csrf = document.querySelector('meta[name=csrf-token]').content;
                const res = await fetch(`/admin/customers/${this.targetId}/block`, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify({ reason: this.reason, message: this.message.trim(), notes: this.notes.trim() }),
                });
                if (res.ok) {
                    this.blocked = true;
                    setTimeout(() => window.location.reload(), 1400);
                } else {
                    const data = await res.json().catch(() => ({}));
                    this.error = data.message || Object.values(data.errors || {}).flat()[0] || 'Could not block this customer.';
                }
            } catch (e) {
                this.error = 'Network error, please try again.';
            } finally {
                this.sending = false;
            }
        },
    };
};

// shared "remove unavailable item" modal on the admin order detail page — one instance serving
// every item row's 🗑️ button, opened via the open-remove-item-modal window event with the
// clicked item's details in e.detail. Submission is a plain form POST (see the Blade template),
// not fetch — this just tracks which item/order the form's action should point at and drives
// the reason picker's conditional "custom reason" textarea.
window.removeItemModal = function (reasons) {
    return {
        open: false,
        orderId: null,
        itemId: null,
        itemName: '',
        itemImage: null,
        itemMeta: '',
        reason: '',
        note: '',
        reasons: reasons || {},

        init() {
            window.addEventListener('open-remove-item-modal', (e) => {
                this.orderId = e.detail.orderId;
                this.itemId = e.detail.itemId;
                this.itemName = e.detail.itemName;
                this.itemImage = e.detail.itemImage;
                this.itemMeta = e.detail.itemMeta;
                this.reason = '';
                this.note = '';
                this.open = true;
            });
        },
        removeUrl() {
            return this.orderId && this.itemId ? `/admin/orders/${this.orderId}/items/${this.itemId}` : '#';
        },
    };
};

// "Add Product" modal on the admin order detail page — lets an admin add product(s) to an
// order the customer already placed (see Admin\OrderController::addItems()). Opened via the
// open-add-product-modal window event dispatched by the Items card's ➕ button (show.blade.php).
// Three-part flow in one modal: search (fetch /admin/products/search, debounced via
// @input.debounce in the template) → configure+stage a pick (portion/qty, "Add to list") →
// review/confirm (the confirmation dialog the spec asked for) → submit. Prices shown here are
// only a preview — the server always re-derives them via Product::priceForPortion(), never
// trusts this payload. Success reloads the page so the server-rendered item list, totals, and
// audit chip all refresh in one shot (this admin page is otherwise plain Blade + redirects, same
// convention as removeItemModal above — no partial-refresh machinery to hook into here).
window.addProductModal = function () {
    return {
        open: false,
        orderId: null,
        isPaidOnline: false,
        query: '',
        results: [],
        searching: false,
        selectedProduct: null, // the product currently being configured (portion/qty), or null
        selectedPortion: null,
        selectedQuantity: 1,
        lines: [], // staged picks: {key, product_id, name, image, portion, label, quantity, unitPrice}
        confirming: false,
        submitting: false,
        error: '',

        init() {
            window.addEventListener('open-add-product-modal', (e) => {
                this.orderId = e.detail.orderId;
                this.isPaidOnline = !!e.detail.isPaidOnline;
                this.query = '';
                this.results = [];
                this.selectedProduct = null;
                this.lines = [];
                this.confirming = false;
                this.submitting = false;
                this.error = '';
                this.open = true;
            });
        },
        close() {
            this.open = false;
        },
        async search() {
            const q = this.query.trim();
            if (q.length < 2) {
                this.results = [];
                return;
            }
            this.searching = true;
            try {
                const res = await fetch(`/admin/products/search?q=${encodeURIComponent(q)}`, { headers: { Accept: 'application/json' } });
                const data = res.ok ? await res.json() : { products: [] };
                if (this.query.trim() === q) this.results = data.products || [];
            } catch (e) {
                this.results = [];
            } finally {
                this.searching = false;
            }
        },
        pickProduct(product) {
            this.selectedProduct = product;
            this.selectedPortion = product.portions[0]?.portion ?? 0;
            this.selectedQuantity = 1;
        },
        cancelPick() {
            this.selectedProduct = null;
        },
        selectedVariant() {
            if (!this.selectedProduct) return null;
            return this.selectedProduct.portions.find((p) => p.portion === this.selectedPortion) || this.selectedProduct.portions[0];
        },
        selectedUnitPrice() {
            return this.selectedVariant()?.price ?? 0;
        },
        selectedLineTotal() {
            return this.selectedUnitPrice() * this.selectedQuantity;
        },
        stageSelected() {
            if (!this.selectedProduct) return;
            const variant = this.selectedVariant();
            this.lines.push({
                key: `${this.selectedProduct.id}-${this.selectedPortion}-${Date.now()}`,
                product_id: this.selectedProduct.id,
                name: this.selectedProduct.name,
                image: this.selectedProduct.image,
                portion: this.selectedPortion,
                label: variant?.label ?? null,
                quantity: this.selectedQuantity,
                unitPrice: this.selectedUnitPrice(),
            });
            this.selectedProduct = null;
            this.query = '';
            this.results = [];
        },
        removeStaged(key) {
            this.lines = this.lines.filter((l) => l.key !== key);
        },
        lineTotal(line) {
            return line.unitPrice * line.quantity;
        },
        stagedTotal() {
            return this.lines.reduce((sum, l) => sum + this.lineTotal(l), 0);
        },
        startConfirm() {
            if (this.lines.length === 0) return;
            this.confirming = true;
        },
        backToEdit() {
            this.confirming = false;
        },
        async submit() {
            if (this.submitting || this.lines.length === 0) return;
            this.submitting = true;
            this.error = '';
            try {
                const csrf = document.querySelector('meta[name=csrf-token]').content;
                const res = await fetch(`/admin/orders/${this.orderId}/items`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify({
                        items: this.lines.map((l) => ({ product_id: l.product_id, portion: l.portion, quantity: l.quantity })),
                    }),
                });
                const data = await res.json().catch(() => ({}));
                if (res.ok && data.ok) {
                    window.location.reload();
                } else {
                    this.error = data.message || 'Could not add the product(s) to this order.';
                    this.confirming = false;
                }
            } catch (e) {
                this.error = 'Network error, please try again.';
                this.confirming = false;
            } finally {
                this.submitting = false;
            }
        },
    };
};

// animates a number counting up from 0 — used on stat cards / MVP cards for a bit of life
window.animateCounter = function (el, end, { duration = 900, prefix = '' } = {}) {
    const startTime = performance.now();
    function frame(now) {
        const progress = Math.min((now - startTime) / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3);
        const value = Math.round(end * eased);
        el.textContent = prefix + value.toLocaleString('en-IN');
        if (progress < 1) requestAnimationFrame(frame);
    }
    requestAnimationFrame(frame);
};

window.visitorCharts = function (data) {
    return {
        init() {
            const gridColor = 'rgba(122, 22, 34, 0.08)';
            const tickColor = '#7a1622';

            new Chart(this.$refs.visitsChart, {
                type: 'bar',
                data: {
                    labels: data.visits.labels,
                    datasets: [{
                        label: 'Visits',
                        data: data.visits.values,
                        backgroundColor: palette.gold,
                        borderRadius: 6,
                        maxBarThickness: 26,
                    }],
                },
                options: {
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, ticks: { precision: 0, color: tickColor }, grid: { color: gridColor } },
                        x: { ticks: { color: tickColor }, grid: { display: false } },
                    },
                },
            });

            new Chart(this.$refs.deviceChart, {
                type: 'doughnut',
                data: {
                    labels: data.devices.labels.length ? data.devices.labels : ['No data'],
                    datasets: [{
                        data: data.devices.values.length ? data.devices.values : [1],
                        backgroundColor: [palette.gold, palette.pista, palette.maroon, palette.maroonDark],
                        borderColor: palette.cream,
                        borderWidth: 3,
                    }],
                },
                options: {
                    maintainAspectRatio: false,
                    cutout: '62%',
                    plugins: { legend: { position: 'bottom', labels: { color: tickColor, boxWidth: 12, padding: 14 } } },
                },
            });
        },
    };
};

window.customersCharts = function (data) {
    return {
        init() {
            const gridColor = 'rgba(122, 22, 34, 0.08)';
            const tickColor = '#7a1622';

            new Chart(this.$refs.topSpendersChart, {
                type: 'bar',
                data: {
                    labels: data.topSpenders.labels,
                    datasets: [{
                        label: 'Total spent (₹)',
                        data: data.topSpenders.values,
                        backgroundColor: palette.gold,
                        borderRadius: 6,
                        maxBarThickness: 22,
                    }],
                },
                options: {
                    maintainAspectRatio: false,
                    indexAxis: 'y',
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { beginAtZero: true, ticks: { color: tickColor }, grid: { color: gridColor } },
                        y: { ticks: { color: tickColor }, grid: { display: false } },
                    },
                },
            });

            new Chart(this.$refs.newCustomersChart, {
                type: 'bar',
                data: {
                    labels: data.newCustomers.labels,
                    datasets: [{
                        label: 'New customers',
                        data: data.newCustomers.values,
                        backgroundColor: palette.pista,
                        borderRadius: 6,
                        maxBarThickness: 26,
                    }],
                },
                options: {
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, ticks: { precision: 0, color: tickColor }, grid: { color: gridColor } },
                        x: { ticks: { color: tickColor }, grid: { display: false } },
                    },
                },
            });
        },
    };
};

window.customerSpendChart = function (labels, values) {
    return {
        init() {
            new Chart(this.$refs.spendChart, {
                type: 'line',
                data: {
                    labels,
                    datasets: [{
                        label: 'Spend (₹)',
                        data: values,
                        borderColor: palette.maroon,
                        backgroundColor: 'rgba(122, 22, 34, 0.08)',
                        fill: true,
                        tension: 0.35,
                        pointBackgroundColor: palette.gold,
                    }],
                },
                options: {
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, ticks: { color: '#7a1622' }, grid: { color: 'rgba(122, 22, 34, 0.08)' } },
                        x: { ticks: { color: '#7a1622' }, grid: { display: false } },
                    },
                },
            });
        },
    };
};

window.customerBehaviourCharts = function (data) {
    return {
        rendered: false,
        renderCharts() {
            if (this.rendered) return; // canvases can't be re-initialized once a Chart is bound to them
            this.rendered = true;
            const gridColor = 'rgba(122, 22, 34, 0.08)';
            const tickColor = '#7a1622';

            new Chart(this.$refs.activityChart, {
                type: 'line',
                data: {
                    labels: data.activity.labels,
                    datasets: [{
                        label: 'Activity events',
                        data: data.activity.values,
                        borderColor: palette.maroon,
                        backgroundColor: 'rgba(122, 22, 34, 0.08)',
                        fill: true,
                        tension: 0.35,
                        pointBackgroundColor: palette.gold,
                    }],
                },
                options: {
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, ticks: { precision: 0, color: tickColor }, grid: { color: gridColor } },
                        x: { ticks: { color: tickColor }, grid: { display: false } },
                    },
                },
            });

            new Chart(this.$refs.deviceChart, {
                type: 'doughnut',
                data: {
                    labels: data.devices.labels.length ? data.devices.labels : ['No data'],
                    datasets: [{
                        data: data.devices.values.length ? data.devices.values : [1],
                        backgroundColor: [palette.gold, palette.pista, palette.maroon, palette.maroonDark],
                        borderColor: palette.cream,
                        borderWidth: 3,
                    }],
                },
                options: {
                    maintainAspectRatio: false,
                    cutout: '62%',
                    plugins: { legend: { position: 'bottom', labels: { color: tickColor, boxWidth: 12, padding: 14 } } },
                },
            });
        },
    };
};

window.adminDashboardCharts = function (data) {
    return {
        init() {
            const gridColor = 'rgba(122, 22, 34, 0.08)';
            const tickColor = '#7a1622';

            new Chart(this.$refs.ordersChart, {
                type: 'bar',
                data: {
                    labels: data.days.labels,
                    datasets: [{
                        label: 'Orders',
                        data: data.days.orders,
                        backgroundColor: palette.gold,
                        borderRadius: 6,
                        maxBarThickness: 26,
                    }],
                },
                options: {
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, ticks: { precision: 0, color: tickColor }, grid: { color: gridColor } },
                        x: { ticks: { color: tickColor }, grid: { display: false } },
                    },
                },
            });

            new Chart(this.$refs.revenueChart, {
                type: 'line',
                data: {
                    labels: data.days.labels,
                    datasets: [{
                        label: 'Revenue (₹)',
                        data: data.days.revenue,
                        borderColor: palette.maroon,
                        backgroundColor: 'rgba(122, 22, 34, 0.08)',
                        fill: true,
                        tension: 0.35,
                        pointBackgroundColor: palette.gold,
                    }],
                },
                options: {
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, ticks: { color: tickColor }, grid: { color: gridColor } },
                        x: { ticks: { color: tickColor }, grid: { display: false } },
                    },
                },
            });

            new Chart(this.$refs.statusChart, {
                type: 'doughnut',
                data: {
                    labels: ['Pending', 'Confirmed', 'Out for Delivery', 'Delivered', 'Cancelled'],
                    datasets: [{
                        data: [data.status.pending, data.status.confirmed, data.status.out_for_delivery, data.status.delivered, data.status.cancelled],
                        backgroundColor: [palette.gold, palette.pista, '#0284c7', palette.maroon, palette.red],
                        borderColor: palette.cream,
                        borderWidth: 3,
                    }],
                },
                options: {
                    maintainAspectRatio: false,
                    cutout: '62%',
                    plugins: { legend: { position: 'bottom', labels: { color: tickColor, boxWidth: 12, padding: 14 } } },
                },
            });

            new Chart(this.$refs.productsChart, {
                type: 'bar',
                data: {
                    labels: data.topProducts.labels,
                    datasets: [{
                        label: 'Units sold',
                        data: data.topProducts.quantities,
                        backgroundColor: palette.maroon,
                        borderRadius: 6,
                        maxBarThickness: 22,
                    }],
                },
                options: {
                    maintainAspectRatio: false,
                    indexAxis: 'y',
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { beginAtZero: true, ticks: { precision: 0, color: tickColor }, grid: { color: gridColor } },
                        y: { ticks: { color: tickColor }, grid: { display: false } },
                    },
                },
            });
        },
    };
};

// Super Admin's Income dashboard — same palette/gridColor/tickColor idiom as adminDashboardCharts
// above, so this reads as part of the same design language rather than a bolted-on new page
window.incomeDashboardCharts = function (data) {
    return {
        init() {
            const gridColor = 'rgba(122, 22, 34, 0.08)';
            const tickColor = '#7a1622';

            new Chart(this.$refs.dailyChart, {
                type: 'line',
                data: {
                    labels: data.daily.labels,
                    datasets: [{
                        label: 'Income (₹)',
                        data: data.daily.income,
                        borderColor: palette.gold,
                        backgroundColor: 'rgba(200, 150, 46, 0.12)',
                        fill: true,
                        tension: 0.35,
                        pointBackgroundColor: palette.maroon,
                    }],
                },
                options: {
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, ticks: { color: tickColor }, grid: { color: gridColor } },
                        x: { ticks: { color: tickColor, maxRotation: 0, autoSkip: true, maxTicksLimit: 8 }, grid: { display: false } },
                    },
                },
            });

            new Chart(this.$refs.ordersTrendChart, {
                type: 'bar',
                data: {
                    labels: data.daily.labels,
                    datasets: [{
                        label: 'Delivered orders',
                        data: data.daily.orders,
                        backgroundColor: palette.pista,
                        borderRadius: 6,
                        maxBarThickness: 18,
                    }],
                },
                options: {
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, ticks: { precision: 0, color: tickColor }, grid: { color: gridColor } },
                        x: { ticks: { color: tickColor, maxRotation: 0, autoSkip: true, maxTicksLimit: 8 }, grid: { display: false } },
                    },
                },
            });

            new Chart(this.$refs.monthlyChart, {
                type: 'bar',
                data: {
                    labels: data.monthly.labels,
                    datasets: [
                        { label: '₹15 Commission', data: data.monthly.fixed, backgroundColor: palette.gold, stack: 'income', borderRadius: 4, maxBarThickness: 34 },
                        { label: 'Delivery Charge Income', data: data.monthly.delivery, backgroundColor: palette.maroon, stack: 'income', borderRadius: 4, maxBarThickness: 34 },
                    ],
                },
                options: {
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom', labels: { color: tickColor, boxWidth: 12, padding: 14 } } },
                    scales: {
                        y: { beginAtZero: true, stacked: true, ticks: { color: tickColor }, grid: { color: gridColor } },
                        x: { stacked: true, ticks: { color: tickColor }, grid: { display: false } },
                    },
                },
            });

            new Chart(this.$refs.breakdownChart, {
                type: 'doughnut',
                data: {
                    labels: ['₹15 Commission', 'Delivery Charge Income'],
                    datasets: [{
                        data: [data.breakdown.fixed, data.breakdown.delivery],
                        backgroundColor: [palette.gold, palette.maroon],
                        borderColor: palette.cream,
                        borderWidth: 3,
                    }],
                },
                options: {
                    maintainAspectRatio: false,
                    cutout: '62%',
                    plugins: { legend: { position: 'bottom', labels: { color: tickColor, boxWidth: 12, padding: 14 } } },
                },
            });
        },
    };
};

// support inbox — server-rendered rows swapped for fresh JSON every few seconds so unread
// badges / previews / ordering stay live while the admin sits on the page
window.adminSupportInbox = function (initialConversations) {
    return {
        conversations: initialConversations || [],
        search: '',
        page: 1,
        perPage: 15,

        init() {
            this.$el._inboxTimer = setInterval(() => this.refresh(), 5000);
        },
        destroy() {
            clearInterval(this.$el._inboxTimer);
        },
        async refresh() {
            try {
                const res = await fetch('/admin/support', { headers: { Accept: 'application/json' } });
                if (!res.ok) return;
                const data = await res.json().catch(() => ({}));
                if (data.ok) this.conversations = data.conversations;
            } catch (e) {
                // offline / server restarting — try again next tick
            }
        },
        // client-side only — the whole inbox is already loaded/polled in one shot, so a second
        // round-trip just to filter it would be pure overhead. Matches name, order #, or phone.
        filteredConversations() {
            const q = this.search.trim().toLowerCase();
            if (!q) return this.conversations;
            return this.conversations.filter((c) =>
                (c.customer_name || '').toLowerCase().includes(q)
                || (c.order_number || '').toLowerCase().includes(q)
                || (c.customer_phone || '').includes(q)
            );
        },
        // also client-side, over the (already-searched) in-memory list — clamps `page` in case
        // a delete, a fresh search, or the next 5s poll leaves it pointing past the new last page
        totalPages() {
            return Math.max(1, Math.ceil(this.filteredConversations().length / this.perPage));
        },
        pagedConversations() {
            if (this.page > this.totalPages()) this.page = this.totalPages();
            const start = (this.page - 1) * this.perPage;
            return this.filteredConversations().slice(start, start + this.perPage);
        },
        pageFirstItem() {
            return this.filteredConversations().length === 0 ? 0 : (this.page - 1) * this.perPage + 1;
        },
        pageLastItem() {
            return Math.min(this.page * this.perPage, this.filteredConversations().length);
        },
        goToPage(p) {
            this.page = Math.min(Math.max(1, p), this.totalPages());
        },
        // same "current page ± 2" window as the server-rendered <x-admin.pagination> component,
        // so this client-side list looks and behaves the same as every paginated table elsewhere
        pageRange() {
            const last = this.totalPages();
            const start = Math.max(1, this.page - 2);
            const end = Math.min(last, this.page + 2);
            const range = [];
            for (let i = start; i <= end; i++) range.push(i);
            return range;
        },
        async deleteConversation(orderId, customerName) {
            if (!confirm(`Delete the entire conversation with ${customerName}? This cannot be undone.`)) return;
            try {
                const csrf = document.querySelector('meta[name=csrf-token]').content;
                const res = await fetch(`/admin/support/${orderId}`, {
                    method: 'DELETE',
                    headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                });
                const data = await res.json().catch(() => ({}));
                if (data.ok) this.conversations = this.conversations.filter((c) => c.order_id !== orderId);
            } catch (e) {
                alert('Could not delete — check your connection and try again.');
            }
        },
    };
};

// one order's support thread — 3s id-cursor poll; keeping it open marks the customer's
// messages read server-side, which is also what clears the sidebar/inbox unread badges
window.adminSupportThread = function (orderId, initialMessages) {
    return {
        messages: [],
        draft: '',
        sending: false,
        sendError: '',
        lastId: 0,
        pendingImage: null,
        pendingImagePreview: null,

        init() {
            this.appendMessages(initialMessages || []);
            // tells adminNotifier's global poll not to ping/toast for THIS conversation
            window.__activeSupportOrderId = orderId;
            this.$nextTick(() => this.scrollToBottom(true));
            this.$el._threadTimer = setInterval(() => this.poll(), 3000);
        },
        destroy() {
            clearInterval(this.$el._threadTimer);
            if (window.__activeSupportOrderId === orderId) window.__activeSupportOrderId = null;
            this.clearPendingImage();
        },

        async poll() {
            try {
                const url = new URL(`/admin/support/${orderId}/messages`, window.location.origin);
                url.searchParams.set('after', this.lastId);
                const res = await fetch(url, { headers: { Accept: 'application/json' } });
                if (!res.ok) return;
                const data = await res.json().catch(() => ({}));
                if (!data.ok) return;
                const incoming = (data.messages || []).filter((m) => m.id > this.lastId);
                if (incoming.length) {
                    this.appendMessages(incoming);
                    if (incoming.some((m) => m.sender === 'customer')) {
                        playChatPing();
                        this.$nextTick(() => this.scrollToBottom());
                    }
                }
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

        // plain accept="image/*" (no `capture`) so a phone/tablet admin gets both "Take Photo"
        // and "Choose from Library" instead of being forced straight into the camera
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

        async send() {
            const text = this.draft.trim();
            if ((!text && !this.pendingImage) || this.sending) return;
            this.sending = true;
            this.sendError = '';
            try {
                const csrf = document.querySelector('meta[name=csrf-token]').content;
                const body = new FormData();
                body.append('message', text);
                if (this.pendingImage) body.append('image', this.pendingImage);
                const res = await fetch(`/admin/support/${orderId}/messages`, {
                    method: 'POST',
                    headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                    body,
                });
                const data = await res.json().catch(() => ({}));
                if (data.ok) {
                    this.appendMessages([data.message]);
                    this.draft = '';
                    this.clearPendingImage();
                    this.$nextTick(() => this.scrollToBottom(true));
                    this.$refs.threadInput?.focus();
                } else {
                    this.sendError = data.message || "Couldn't send — please try again.";
                }
            } catch (e) {
                this.sendError = 'Network error — please try again.';
            } finally {
                this.sending = false;
            }
        },

        // stick to the bottom on new activity, but never yank the view while the admin is
        // scrolled up re-reading history (unless it's their own reply going out)
        scrollToBottom(force = false) {
            const el = this.$refs.thread;
            if (!el) return;
            const nearBottom = el.scrollHeight - el.scrollTop - el.clientHeight < 140;
            if (force || nearBottom) el.scrollTop = el.scrollHeight;
        },
    };
};

// bumped from 'mb_admin_chat_widget' — a bad customWidth/customHeight saved under the old key
// (from before the panel had a hard CSS max-height) could size the panel off the top of the
// screen every time it reopened; changing the key orphans that old value for everyone at once
const ADMIN_CHAT_STORAGE_KEY = 'mb_admin_chat_widget_v2';

// Global floating support-chat widget — lives once in admin/layout.blade.php (nested inside the
// same adminNotifier() scope so its launcher badge/toast can share that scope's `supportUnread`
// without a second poll). Two views share one panel: 'inbox' (the conversation list) and
// 'thread' (one order's messages) — switching between them never leaves the current admin page,
// which is the whole point: an admin can browse Orders/Dashboard/etc. with this open over it.
//
// State (open/minimized/expanded/view/activeOrderId/custom size) is mirrored to localStorage
// because this is a classic server-rendered app — every admin-panel navigation is a full page
// load, and without this the widget would silently reset on every click.
window.adminSupportWidget = function () {
    return {
        open: false,
        minimized: false,
        expanded: false,
        view: 'inbox', // 'inbox' | 'thread'
        activeOrderId: null,
        activeOrder: null, // conversation summary for the thread header (customer_name, order_number, ...)
        conversations: [],
        messages: [],
        draft: '',
        sending: false,
        sendError: '',
        lastId: 0,
        pendingImage: null,
        pendingImagePreview: null,
        customWidth: null,
        customHeight: null,
        resizeStart: null,
        // read (but otherwise unused) inside sizeStyle() purely so Alpine tracks it as a
        // dependency — window.innerWidth/innerHeight aren't reactive on their own, so without
        // this a live browser-window resize wouldn't re-run sizeStyle() until some other
        // reactive prop (expanded, customWidth, ...) happened to change again
        viewportTick: 0,

        init() {
            this.restoreState();
            window.addEventListener('resize', () => { this.viewportTick++; });
            if (this.open && this.view === 'thread' && this.activeOrderId) {
                window.__activeSupportOrderId = this.minimized ? null : this.activeOrderId;
                this.loadThread(this.activeOrderId);
                // the persisted summary redraws the header instantly; this quietly confirms/
                // refreshes it (status, unread, etc. may have moved on since the last page)
                this.refreshActiveOrderSummary();
            } else if (this.open) {
                this.refreshInbox();
            }
            this.$el._inboxTimer = setInterval(() => {
                if (this.open && !this.minimized && this.view === 'inbox') this.refreshInbox();
            }, 5000);
            this.$el._threadTimer = setInterval(() => {
                if (this.open && !this.minimized && this.view === 'thread' && this.activeOrderId) this.pollThread();
            }, 3000);
            // opened from elsewhere on the page: the header chat icon (no detail — inbox), a
            // conversation row, or a toast (detail.orderId + detail.summary — straight to thread)
            window.addEventListener('open-support-widget', (e) => {
                const detail = e.detail || {};
                if (detail.orderId) this.openThread(detail.orderId, detail.summary || null);
                else this.openInbox();
            });
        },
        destroy() {
            clearInterval(this.$el._inboxTimer);
            clearInterval(this.$el._threadTimer);
            if (window.__activeSupportOrderId === this.activeOrderId) window.__activeSupportOrderId = null;
            this.clearPendingImage();
        },

        persistState() {
            try {
                localStorage.setItem(ADMIN_CHAT_STORAGE_KEY, JSON.stringify({
                    open: this.open,
                    minimized: this.minimized,
                    expanded: this.expanded,
                    view: this.view,
                    activeOrderId: this.activeOrderId,
                    // small summary object, not the messages — enough to redraw the thread
                    // header (name/avatar/Call button) immediately after a page navigation,
                    // before the fresh fetch below confirms/updates it
                    activeOrder: this.activeOrder,
                    customWidth: this.customWidth,
                    customHeight: this.customHeight,
                }));
            } catch (e) {
                // private browsing / storage disabled — widget still works, just won't survive nav
            }
        },
        restoreState() {
            try {
                const raw = localStorage.getItem(ADMIN_CHAT_STORAGE_KEY);
                if (!raw) return;
                const s = JSON.parse(raw);
                this.open = !!s.open;
                this.minimized = !!s.minimized;
                this.expanded = !!s.expanded;
                this.view = s.view === 'thread' ? 'thread' : 'inbox';
                this.activeOrderId = s.activeOrderId || null;
                this.activeOrder = s.activeOrder || null;
                this.customWidth = s.customWidth ?? null;
                this.customHeight = s.customHeight ?? null;
                if (this.view === 'thread' && !this.activeOrderId) this.view = 'inbox';
            } catch (e) {
                // corrupt/old shape — just start fresh
            }
        },

        openInbox() {
            this.open = true;
            this.minimized = false;
            this.view = 'inbox';
            if (window.__activeSupportOrderId === this.activeOrderId) window.__activeSupportOrderId = null;
            this.refreshInbox();
            this.persistState();
        },
        openThread(orderId, summary = null) {
            this.open = true;
            this.minimized = false;
            this.view = 'thread';
            this.activeOrderId = orderId;
            this.activeOrder = summary;
            this.messages = [];
            this.lastId = 0;
            window.__activeSupportOrderId = orderId;
            this.loadThread(orderId);
            this.persistState();
        },
        backToInbox() {
            if (window.__activeSupportOrderId === this.activeOrderId) window.__activeSupportOrderId = null;
            this.view = 'inbox';
            this.activeOrderId = null;
            this.activeOrder = null;
            this.refreshInbox();
            this.persistState();
        },
        // collapses to the slim "resume" bar — whatever admin page is behind becomes fully
        // usable again, which is the point of minimize vs. just closing the conversation
        minimizeChat() {
            this.minimized = true;
            if (window.__activeSupportOrderId === this.activeOrderId) window.__activeSupportOrderId = null;
            this.persistState();
        },
        restoreChat() {
            this.minimized = false;
            if (this.view === 'thread' && this.activeOrderId) {
                window.__activeSupportOrderId = this.activeOrderId;
                this.loadThread(this.activeOrderId);
            } else {
                this.refreshInbox();
            }
            this.persistState();
        },
        closeChat() {
            this.open = false;
            this.minimized = false;
            if (window.__activeSupportOrderId === this.activeOrderId) window.__activeSupportOrderId = null;
            this.persistState();
        },
        toggleExpand() {
            if (window.innerWidth < 640) return; // mobile is already full-screen
            this.expanded = !this.expanded;
            this.customWidth = null;
            this.customHeight = null;
            this.persistState();
        },

        // desktop-only manual resize, dragged from the panel's top-left corner grip
        startResize(event) {
            if (window.innerWidth < 640) return;
            event.preventDefault();
            const rect = this.$refs.widgetPanel.getBoundingClientRect();
            this.resizeStart = { x: event.clientX, y: event.clientY, width: rect.width, height: rect.height };
            document.body.style.userSelect = 'none';
            const onMove = (e) => this.onResizeMove(e);
            const onUp = () => {
                this.resizeStart = null;
                document.body.style.userSelect = '';
                this.persistState();
                window.removeEventListener('mousemove', onMove);
                window.removeEventListener('mouseup', onUp);
            };
            window.addEventListener('mousemove', onMove);
            window.addEventListener('mouseup', onUp);
        },
        onResizeMove(event) {
            if (!this.resizeStart) return;
            const dx = this.resizeStart.x - event.clientX;
            const dy = this.resizeStart.y - event.clientY;
            const maxW = Math.min(640, window.innerWidth - 32);
            const maxH = window.innerHeight - 48;
            this.customWidth = Math.min(maxW, Math.max(320, this.resizeStart.width + dx));
            this.customHeight = Math.min(maxH, Math.max(400, this.resizeStart.height + dy));
        },
        sizeStyle() {
            this.viewportTick; // touch so Alpine re-runs this on window resize — see init()
            if (window.innerWidth < 640) return '';
            // re-clamped against the CURRENT viewport on every render — not just at drag-time —
            // so a size persisted from a bigger screen/zoom (or a since-resized window) can never
            // push the panel's own header/controls off-screen and unreachable (bottom-24/right-6
            // anchoring means an oversized panel grows upward past the top of the screen)
            const maxW = Math.min(640, window.innerWidth - 32);
            const maxH = window.innerHeight - 48;
            const width = Math.max(320, Math.min(this.customWidth ?? (this.expanded ? 448 : 384), maxW));
            const height = Math.max(400, Math.min(this.customHeight ?? (this.expanded ? 720 : 544), maxH));
            return `width: ${width}px; height: ${height}px;`;
        },

        async refreshInbox() {
            try {
                const res = await fetch('/admin/support', { headers: { Accept: 'application/json' } });
                if (!res.ok) return;
                const data = await res.json().catch(() => ({}));
                if (data.ok) this.conversations = data.conversations;
            } catch (e) {
                // offline / server restarting — next tick will catch up
            }
        },
        // there's no single-conversation endpoint, so this reuses the inbox list just to pick
        // out the one row matching the persisted thread — only called once, right after a page
        // navigation restores a thread that was open, to refresh a possibly-stale summary
        async refreshActiveOrderSummary() {
            if (!this.activeOrderId) return;
            try {
                const res = await fetch('/admin/support', { headers: { Accept: 'application/json' } });
                if (!res.ok) return;
                const data = await res.json().catch(() => ({}));
                if (!data.ok) return;
                const match = (data.conversations || []).find((c) => c.order_id === this.activeOrderId);
                if (match) {
                    this.activeOrder = match;
                    this.persistState();
                }
            } catch (e) {
                // offline / server hiccup — the persisted summary stays as the fallback
            }
        },
        async loadThread(orderId) {
            try {
                const url = new URL(`/admin/support/${orderId}/messages`, window.location.origin);
                url.searchParams.set('after', 0);
                const res = await fetch(url, { headers: { Accept: 'application/json' } });
                if (!res.ok) return;
                const data = await res.json().catch(() => ({}));
                if (!data.ok) return;
                this.appendMessages(data.messages || []);
                this.$nextTick(() => this.scrollToBottom(true));
            } catch (e) {
                // offline / server hiccup — next tick will catch up
            }
        },
        async pollThread() {
            if (!this.activeOrderId) return;
            try {
                const url = new URL(`/admin/support/${this.activeOrderId}/messages`, window.location.origin);
                url.searchParams.set('after', this.lastId);
                const res = await fetch(url, { headers: { Accept: 'application/json' } });
                if (!res.ok) return;
                const data = await res.json().catch(() => ({}));
                if (!data.ok) return;
                const incoming = (data.messages || []).filter((m) => m.id > this.lastId);
                if (incoming.length) {
                    this.appendMessages(incoming);
                    if (incoming.some((m) => m.sender === 'customer')) {
                        playChatPing();
                        this.$nextTick(() => this.scrollToBottom());
                    }
                }
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

        async send() {
            const text = this.draft.trim();
            if ((!text && !this.pendingImage) || this.sending || !this.activeOrderId) return;
            this.sending = true;
            this.sendError = '';
            try {
                const csrf = document.querySelector('meta[name=csrf-token]').content;
                const body = new FormData();
                body.append('message', text);
                if (this.pendingImage) body.append('image', this.pendingImage);
                const res = await fetch(`/admin/support/${this.activeOrderId}/messages`, {
                    method: 'POST',
                    headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                    body,
                });
                const data = await res.json().catch(() => ({}));
                if (data.ok) {
                    this.appendMessages([data.message]);
                    this.draft = '';
                    this.clearPendingImage();
                    this.$nextTick(() => this.scrollToBottom(true));
                    this.$refs.widgetInput?.focus();
                } else {
                    this.sendError = data.message || "Couldn't send — please try again.";
                }
            } catch (e) {
                this.sendError = 'Network error — please try again.';
            } finally {
                this.sending = false;
            }
        },

        scrollToBottom(force = false) {
            const el = this.$refs.widgetThread;
            if (!el) return;
            const nearBottom = el.scrollHeight - el.scrollTop - el.clientHeight < 140;
            if (force || nearBottom) el.scrollTop = el.scrollHeight;
        },
    };
};

// Announcement Banner admin form — drives the Quill rich-text editor and the live preview
// pane, which shares the exact same Blade partial (and Alpine property names) rendered on
// the live site so what the admin sees here is pixel-for-pixel what visitors will see.
window.announcementForm = function (initial) {
    // kept outside the returned (reactive) object — Alpine would otherwise try to deep-proxy
    // the Quill instance itself, which it isn't built to handle
    let quill = null;

    return {
        enabled: initial.enabled,
        headline: initial.headline || '',
        description: initial.description || '',
        buttonText: initial.buttonText || '',
        buttonUrl: initial.buttonUrl || '',
        image: initial.image || null,
        theme: initial.theme || 'maroon',
        backgroundColor: initial.backgroundColor || '#7a1622',
        textColor: initial.textColor || '#fdf6e9',
        showClose: initial.showClose,
        removeImageFlag: false,

        // Landing Page (Promo page /offer — see PromoLandingController, AnnouncementSetting)
        landingPageMode: initial.landingPageMode || 'none',
        discountedCount: initial.discountedCount || 0,
        previewUrl: initial.previewUrl || '/offer',
        // {id, name, image}[] — order here IS the display order (see dragStart/drop below);
        // submitted as hidden product_ids[] inputs in this same order, see the Blade template
        selectedProducts: initial.selectedProducts || [],
        productQuery: '',
        productResults: [],
        searchingProducts: false,
        productDragIndex: null,
        productOverIndex: null,

        async searchProducts() {
            const q = this.productQuery.trim();
            if (q.length < 2) {
                this.productResults = [];
                return;
            }
            this.searchingProducts = true;
            try {
                const res = await fetch(`/admin/products/search?q=${encodeURIComponent(q)}`, { headers: { Accept: 'application/json' } });
                const data = res.ok ? await res.json() : { products: [] };
                if (this.productQuery.trim() === q) this.productResults = data.products || [];
            } catch (e) {
                this.productResults = [];
            } finally {
                this.searchingProducts = false;
            }
        },
        addProduct(product) {
            if (!this.selectedProducts.some((p) => p.id === product.id)) {
                this.selectedProducts.push({ id: product.id, name: product.name, image: product.image });
            }
            this.productQuery = '';
            this.productResults = [];
        },
        removeProduct(id) {
            this.selectedProducts = this.selectedProducts.filter((p) => p.id !== id);
        },
        // same drag-and-drop idiom as window.adminNavMenu (sidebar reordering) — kept as its own
        // copy rather than a shared helper since the two operate on differently-shaped arrays
        // and this one has no localStorage persistence step
        productDragStart(index, event) {
            this.productDragIndex = index;
            event.dataTransfer.effectAllowed = 'move';
        },
        productDragOver(index, event) {
            event.preventDefault();
            this.productOverIndex = index;
        },
        productDragLeave(index) {
            if (this.productOverIndex === index) this.productOverIndex = null;
        },
        productDrop(index) {
            if (this.productDragIndex === null || this.productDragIndex === index) {
                this.productDragIndex = null;
                this.productOverIndex = null;
                return;
            }
            const moved = this.selectedProducts.splice(this.productDragIndex, 1)[0];
            this.selectedProducts.splice(index, 0, moved);
            this.productDragIndex = null;
            this.productOverIndex = null;
        },
        productDragEnd() {
            this.productDragIndex = null;
            this.productOverIndex = null;
        },

        get bg() {
            const presets = { maroon: '#7a1622', gold: '#c8962e', pista: '#3d7a52', dark: '#241f1f' };
            return this.theme === 'custom' ? (this.backgroundColor || '#7a1622') : presets[this.theme];
        },
        get text() {
            const presets = { maroon: '#fdf6e9', gold: '#3a0b12', pista: '#fdf6e9', dark: '#fdf6e9' };
            return this.theme === 'custom' ? (this.textColor || '#fdf6e9') : presets[this.theme];
        },
        // the shared preview partial calls this on the close button / CTA — nothing to actually
        // close while editing, the preview pane always stays visible
        dismiss() {},

        initEditor() {
            quill = new Quill(this.$refs.editor, {
                theme: 'snow',
                placeholder: 'Write your announcement description…',
                modules: {
                    toolbar: [['bold', 'italic', 'underline'], [{ color: [] }], [{ align: [] }], ['link'], ['clean']],
                },
            });
            if (this.description) quill.root.innerHTML = this.description;
            quill.on('text-change', () => { this.description = quill.root.innerHTML; });
        },
        onImageSelected(e) {
            const file = e.target.files[0];
            if (!file) return;
            this.image = URL.createObjectURL(file);
            this.removeImageFlag = false;
        },
        removeImage() {
            this.image = null;
            this.removeImageFlag = true;
            if (this.$refs.imageInput) this.$refs.imageInput.value = '';
        },
    };
};

// Terms & Conditions / Privacy Policy editor (admin/legal/_form.blade.php) — same Quill +
// shared-partial live-preview pattern as announcementForm() above, with a richer toolbar
// since these are real documents (headings, lists, quotes), not a one-paragraph banner.
window.legalDocumentForm = function (initial) {
    let quill = null;

    return {
        title: initial.title || '',
        content: initial.content || '',
        // preview-only — the real "Last Updated" only exists once this version is actually
        // published (see LegalDocumentVersion::published_at); "today" is the honest preview
        updatedAt: new Date().toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }),

        initEditor() {
            quill = new Quill(this.$refs.editor, {
                theme: 'snow',
                placeholder: 'Write the policy content…',
                modules: {
                    toolbar: [
                        [{ header: [2, 3, false] }],
                        ['bold', 'italic', 'underline'],
                        [{ list: 'ordered' }, { list: 'bullet' }],
                        ['blockquote', 'link'],
                        ['clean'],
                    ],
                },
            });
            if (this.content) quill.root.innerHTML = this.content;
            quill.on('text-change', () => { this.content = quill.root.innerHTML; });
        },
    };
};

// category image editor (admin/categories/_form.blade.php) — crop/zoom/reposition a photo into
// a perfect square before upload, previewed round to match the final circular tile. The actual
// crop is always a plain square canvas; only the on-screen viewport/preview are masked round.
window.categoryImageCropper = function (existingImageUrl) {
    // kept outside the returned (reactive) object, same reasoning as announcementForm() above —
    // Alpine can't usefully proxy a Cropper instance
    let cropper = null;

    return {
        existingImageUrl: existingImageUrl || null,
        rawImageSrc: null,
        livePreview: existingImageUrl || null,
        hasNewImage: false,

        onFileSelected(e) {
            const file = e.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = (ev) => {
                this.rawImageSrc = ev.target.result;
                this.hasNewImage = true;
                this.$nextTick(() => this.mountCropper());
            };
            reader.readAsDataURL(file);
        },
        mountCropper() {
            if (cropper) {
                cropper.destroy();
                cropper = null;
            }
            cropper = new Cropper(this.$refs.cropperImage, {
                aspectRatio: 1,
                viewMode: 1,
                dragMode: 'move',
                autoCropArea: 1,
                background: false,
                crop: () => this.refreshPreview(),
                ready: () => this.refreshPreview(),
            });
        },
        zoom(delta) {
            cropper?.zoom(delta);
        },
        refreshPreview() {
            if (!cropper) return;
            this.livePreview = cropper.getCroppedCanvas({ width: 300, height: 300 }).toDataURL('image/jpeg', 0.9);
        },
        // called from the form's @submit (doesn't preventDefault — the native multipart POST
        // still happens, this just fills in the hidden field first) — an edit where the admin
        // didn't touch the photo leaves this empty, so the controller keeps the existing image
        beforeSubmit() {
            if (!cropper || !this.hasNewImage) return;
            this.$refs.croppedImageInput.value = cropper.getCroppedCanvas({ width: 600, height: 600 }).toDataURL('image/jpeg', 0.9);
        },
        destroy() {
            cropper?.destroy();
            cropper = null;
        },
    };
};

// rider profile photo editor (admin/riders/_form.blade.php) — identical square-crop Cropper
// flow to categoryImageCropper() above; kept as its own function (not a shared helper) since
// that's the established convention in this file (each admin photo uploader gets its own
// window.xCropper, even when the body is a near-exact copy — see hero banners below too)
window.riderPhotoCropper = function (existingImageUrl) {
    let cropper = null;

    return {
        existingImageUrl: existingImageUrl || null,
        rawImageSrc: null,
        livePreview: existingImageUrl || null,
        hasNewImage: false,

        onFileSelected(e) {
            const file = e.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = (ev) => {
                this.rawImageSrc = ev.target.result;
                this.hasNewImage = true;
                this.$nextTick(() => this.mountCropper());
            };
            reader.readAsDataURL(file);
        },
        mountCropper() {
            if (cropper) {
                cropper.destroy();
                cropper = null;
            }
            cropper = new Cropper(this.$refs.cropperImage, {
                aspectRatio: 1,
                viewMode: 1,
                dragMode: 'move',
                autoCropArea: 1,
                background: false,
                crop: () => this.refreshPreview(),
                ready: () => this.refreshPreview(),
            });
        },
        zoom(delta) {
            cropper?.zoom(delta);
        },
        refreshPreview() {
            if (!cropper) return;
            this.livePreview = cropper.getCroppedCanvas({ width: 300, height: 300 }).toDataURL('image/jpeg', 0.9);
        },
        beforeSubmit() {
            if (!cropper || !this.hasNewImage) return;
            this.$refs.croppedImageInput.value = cropper.getCroppedCanvas({ width: 600, height: 600 }).toDataURL('image/jpeg', 0.9);
        },
        destroy() {
            cropper?.destroy();
            cropper = null;
        },
    };
};

// hero banner image editor (admin/hero-banners/_form.blade.php) — same Cropper flow as the
// category one above but locked to the wide 21:9 banner shape, with live desktop + mobile
// frame previews (including the title overlay) so the admin sees the real slide before saving
window.heroBannerCropper = function (existingImageUrl, initialTitle) {
    let cropper = null;

    return {
        existingImageUrl: existingImageUrl || null,
        rawImageSrc: null,
        livePreview: existingImageUrl || null,
        hasNewImage: false,
        overlayTitle: initialTitle || '',

        onFileSelected(e) {
            const file = e.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = (ev) => {
                this.rawImageSrc = ev.target.result;
                this.hasNewImage = true;
                this.$nextTick(() => this.mountCropper());
            };
            reader.readAsDataURL(file);
        },
        mountCropper() {
            if (cropper) {
                cropper.destroy();
                cropper = null;
            }
            cropper = new Cropper(this.$refs.cropperImage, {
                aspectRatio: 21 / 9,
                viewMode: 1,
                dragMode: 'move',
                autoCropArea: 1,
                background: false,
                crop: () => this.refreshPreview(),
                ready: () => this.refreshPreview(),
            });
        },
        zoom(delta) {
            cropper?.zoom(delta);
        },
        refreshPreview() {
            if (!cropper) return;
            this.livePreview = cropper.getCroppedCanvas({ width: 700, height: 300 }).toDataURL('image/jpeg', 0.85);
        },
        beforeSubmit() {
            if (!cropper || !this.hasNewImage) return;
            this.$refs.croppedImageInput.value = cropper.getCroppedCanvas({ width: 1680, height: 720 }).toDataURL('image/jpeg', 0.85);
        },
        destroy() {
            cropper?.destroy();
            cropper = null;
        },
    };
};

// featured-category icon editor (admin/featured-categories/_form.blade.php) — same Cropper flow
// as categoryImageCropper() above (square 1:1), but deliberately kept as PNG end-to-end rather
// than re-encoded to JPEG, so a transparent-background icon stays transparent. `fillColor:
// 'transparent'` is explicit because Cropper's canvas would otherwise paint white behind any
// transparent pixels on export.
window.featuredCategoryImageCropper = function (existingImageUrl) {
    let cropper = null;

    return {
        existingImageUrl: existingImageUrl || null,
        rawImageSrc: null,
        livePreview: existingImageUrl || null,
        hasNewImage: false,

        onFileSelected(e) {
            const file = e.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = (ev) => {
                this.rawImageSrc = ev.target.result;
                this.hasNewImage = true;
                this.$nextTick(() => this.mountCropper());
            };
            reader.readAsDataURL(file);
        },
        mountCropper() {
            if (cropper) {
                cropper.destroy();
                cropper = null;
            }
            cropper = new Cropper(this.$refs.cropperImage, {
                aspectRatio: 1,
                viewMode: 1,
                dragMode: 'move',
                autoCropArea: 1,
                background: false,
                crop: () => this.refreshPreview(),
                ready: () => this.refreshPreview(),
            });
        },
        zoom(delta) {
            cropper?.zoom(delta);
        },
        refreshPreview() {
            if (!cropper) return;
            this.livePreview = cropper.getCroppedCanvas({ width: 300, height: 300, fillColor: 'transparent' }).toDataURL('image/png');
        },
        beforeSubmit() {
            if (!cropper || !this.hasNewImage) return;
            this.$refs.croppedImageInput.value = cropper.getCroppedCanvas({ width: 512, height: 512, fillColor: 'transparent' }).toDataURL('image/png');
        },
        destroy() {
            cropper?.destroy();
            cropper = null;
        },
    };
};

// Business Logo uploader (admin/customization/index.blade.php) — the one uploader in this app
// that has to handle two genuinely different file types: raster (PNG/JPG/JPEG) goes through the
// same Cropper.js square-crop-to-PNG flow as featuredCategoryImageCropper() above (transparency
// preserved the same way); SVG can't be cropped or decoded by Cropper/GD at all (it's vector XML,
// not a raster format either understands), so it skips the cropper entirely and is submitted as a
// plain file input — the browser's own <img>-preview (a blob URL) is enough since there's no
// crop/zoom to do on scalable vector art. Submission is a plain multipart form POST (matches
// CustomizationController::updateLogo(), which responds with back()->with()/withErrors(), the
// same convention every other admin image upload in this app already uses — not fetch/JSON).
window.logoUploadCard = function (existingLogoUrl, hasError) {
    let cropper = null;

    return {
        open: hasError, // reopen automatically if the previous submission had a logo error
        existingLogoUrl,
        rawImageSrc: null,
        livePreview: existingLogoUrl,
        isSvg: false,
        hasNewFile: false,
        fileError: '',
        confirmingDelete: false,

        openModal() {
            this.open = true;
        },
        closeModal() {
            this.open = false;
            this.resetSelection();
        },
        resetSelection() {
            this.rawImageSrc = null;
            this.livePreview = this.existingLogoUrl;
            this.isSvg = false;
            this.hasNewFile = false;
            this.fileError = '';
            cropper?.destroy();
            cropper = null;
        },
        onFileSelected(e) {
            const file = e.target.files[0];
            if (!file) return;
            this.fileError = '';

            if (file.size > 2 * 1024 * 1024) {
                this.fileError = 'That file is larger than 2MB — please choose a smaller one.';
                e.target.value = '';
                return;
            }

            this.hasNewFile = true;
            this.isSvg = file.type === 'image/svg+xml' || file.name.toLowerCase().endsWith('.svg');

            if (this.isSvg) {
                cropper?.destroy();
                cropper = null;
                this.rawImageSrc = null;
                this.livePreview = URL.createObjectURL(file);
                return;
            }

            // checked here, on the ORIGINAL file, because Cropper.js's exported canvas is always
            // a fixed 512×512 regardless of source size — checking after cropping would always
            // see 512×512 and could never actually reject anything
            const reader = new FileReader();
            reader.onload = (ev) => {
                const probe = new Image();
                probe.onload = () => {
                    if (probe.naturalWidth < 200 || probe.naturalHeight < 200) {
                        this.fileError = 'Please upload an image at least 200×200px.';
                        this.hasNewFile = false;
                        e.target.value = '';
                        return;
                    }
                    this.rawImageSrc = ev.target.result;
                    this.$nextTick(() => this.mountCropper());
                };
                probe.src = ev.target.result;
            };
            reader.readAsDataURL(file);
        },
        mountCropper() {
            if (cropper) {
                cropper.destroy();
                cropper = null;
            }
            cropper = new Cropper(this.$refs.cropperImage, {
                aspectRatio: 1,
                viewMode: 1,
                dragMode: 'move',
                autoCropArea: 1,
                background: false,
                crop: () => this.refreshPreview(),
                ready: () => this.refreshPreview(),
            });
        },
        zoom(delta) {
            cropper?.zoom(delta);
        },
        refreshPreview() {
            if (!cropper) return;
            this.livePreview = cropper.getCroppedCanvas({ width: 300, height: 300, fillColor: 'transparent' }).toDataURL('image/png');
        },
        // called from the form's @submit — for a raster crop, fills the hidden field the
        // controller reads (svg_file, if chosen, is already attached via the native file input
        // itself and needs no JS help)
        beforeSubmit() {
            if (this.isSvg || !cropper || !this.hasNewFile) return;
            this.$refs.croppedImageInput.value = cropper.getCroppedCanvas({ width: 512, height: 512, fillColor: 'transparent' }).toDataURL('image/png');
        },
    };
};

// Customer website theme picker (admin/customization/index.blade.php) — 10 config-defined
// presets (see config/customer_themes.php). Selecting a card only updates a self-contained
// preview panel's own inline CSS custom properties (never the surrounding admin page — the whole
// point of this feature is that admin's own look never changes); nothing applies site-wide until
// the plain form beneath is actually submitted to admin.customization.theme.
window.themeSelector = function (initialTheme, themes) {
    return {
        selected: initialTheme,
        themes,

        select(slug) {
            this.selected = slug;
        },
        activeVars() {
            return this.themes[this.selected]?.vars || {};
        },
        // used as an inline :style binding on the preview panel — CSS custom properties set
        // directly on that one element, scoped to it and its descendants only
        previewStyle() {
            return Object.entries(this.activeVars())
                .map(([name, value]) => `--color-${name}: ${value}`)
                .join('; ');
        },
    };
};

window.Alpine = Alpine;
Alpine.start();
