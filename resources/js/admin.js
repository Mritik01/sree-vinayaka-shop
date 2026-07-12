import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

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
                    // brand new (never seen before) vs. an update to an order already on screen —
                    // an existing order coming back through this same time-cursor poll (e.g. a rider
                    // marking it delivered) must never re-trigger the new-order popup/chime
                    const newIds = data.orders.filter((o) => o.id > this.lastSeen).map((o) => o.id);

                    // let any listening page (e.g. the orders list) react to changes in real time,
                    // whether that's a freshly-placed order or a rider-driven status change
                    window.dispatchEvent(new CustomEvent('admin-orders-changed', { detail: { orders: data.orders, newIds } }));

                    // the accept/reject popup only makes sense for orders still awaiting a decision —
                    // one that arrived and was auto-cancelled within the same gap (e.g. admin was away
                    // for hours) shouldn't prompt Accept/Reject on an already-closed order
                    this.queue.push(...data.orders.filter((o) => o.status === 'pending' && newIds.includes(o.id)));
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
    delivered: 'bg-maroon-100 text-maroon-600 border-maroon-400/30',
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
window.ordersLivePage = function (initialOrders, statusFilter, currentPage, perPage, initialSearch) {
    return {
        orders: initialOrders || [],
        missedCount: 0,
        now: Date.now(),

        search: initialSearch || '',
        searching: !!initialSearch,
        totalMatches: null,
        searchTimer: null,

        init() {
            // ticks the digital-clock countdown shown next to confirmed / out-for-delivery orders
            setInterval(() => { this.now = Date.now(); }, 1000);
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
                    if (statusFilter !== '' && incoming.status !== statusFilter) {
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
            // the top of an unfiltered/pending view of page 1 — anywhere else (including an active
            // search, which a fresh order likely doesn't match), just flag that some arrived
            const applicable = !this.searching && currentPage === 1 && (statusFilter === '' || statusFilter === 'pending');
            if (!applicable) {
                this.missedCount += toInsert.length;
                return;
            }

            const rows = toInsert.map((o) => ({ ...o, _new: true }));
            this.orders = [...rows, ...this.orders].slice(0, perPage);
            setTimeout(() => {
                rows.forEach((r) => {
                    const row = this.orders.find((o) => o.id === r.id);
                    if (row) row._new = false;
                });
            }, 2500);
        },

        // instant keystroke search — debounced fetch of fresh rows as JSON, swapped straight
        // into the same reactive `orders` array the live new-order feature already drives
        onSearchInput() {
            clearTimeout(this.searchTimer);
            this.searchTimer = setTimeout(() => this.performSearch(), 300);
        },
        async performSearch() {
            clearTimeout(this.searchTimer);
            const url = new URL(window.location.pathname, window.location.origin);
            new URLSearchParams(window.location.search).forEach((value, key) => {
                if (key !== 'q' && key !== 'page') url.searchParams.set(key, value);
            });
            const trimmed = this.search.trim();
            if (trimmed) url.searchParams.set('q', trimmed);

            try {
                const res = await fetch(url.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' } });
                const data = await res.json();
                this.orders = (data.orders || []).map((o) => ({ ...o, _new: false }));
                this.totalMatches = data.total;
                this.searching = trimmed !== '';
                window.history.replaceState(null, '', url.toString());
            } catch (e) {
                // network hiccup — leave the last results in place, typing again will retry
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
            return cancelledReasons[this.order.cancelled_by] || '';
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

window.Alpine = Alpine;
Alpine.start();
