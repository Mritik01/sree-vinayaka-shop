import Alpine from 'alpinejs';

const statusBadgeStyles = {
    pending: 'bg-gold-100 text-gold-600 border-gold-300/60',
    confirmed: 'bg-pista-100 text-pista-600 border-pista-400/40',
    out_for_delivery: 'bg-sky-50 text-sky-600 border-sky-200',
    delivered: 'bg-maroon-100 text-maroon-600 border-maroon-400/30',
    cancelled: 'bg-red-50 text-red-600 border-red-200',
};

// server-translated (see partials/order-status-i18n.blade.php) so this respects the rider's
// own Hindi/English choice — falls back to English if the partial wasn't loaded for some reason
const statusLabels = window.ORDER_STATUS_LABELS || {
    pending: 'Pending',
    confirmed: 'Confirmed',
    out_for_delivery: 'Out for Delivery',
    delivered: 'Delivered',
    cancelled: 'Cancelled',
};

// short two-tone chime so a rider notices a freshly-assigned order even without looking at
// the screen — same approach as the admin new-order chime, just a lighter single pass
function playAssignChime() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const now = ctx.currentTime;
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.type = 'sine';
        osc.frequency.value = 988;
        gain.gain.setValueAtTime(0.0001, now);
        gain.gain.exponentialRampToValueAtTime(0.3, now + 0.02);
        gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.5);
        osc.connect(gain).connect(ctx.destination);
        osc.start(now);
        osc.stop(now + 0.55);
    } catch (e) {
        // audio blocked before first user interaction — nothing else to do
    }
}

// live deliveries list — polls for orders an admin has assigned to this rider so a freshly
// assigned order appears (and one that's reassigned away / delivered elsewhere disappears)
// without the rider ever needing to pull-to-refresh
window.riderOrdersPage = function (initialOrders) {
    return {
        orders: initialOrders || [],

        init() {
            setInterval(() => this.poll(), 5000);
        },
        statusBadgeClasses(status) {
            return statusBadgeStyles[status] || statusBadgeStyles.pending;
        },
        statusLabel(status) {
            return statusLabels[status] || status;
        },
        async poll() {
            try {
                const res = await fetch('/rider/orders/poll', { headers: { Accept: 'application/json' } });
                if (!res.ok) return;
                const data = await res.json().catch(() => ({}));
                if (!data.ok) return;

                const previousIds = this.orders.map((o) => o.id);
                const freshlyAssigned = data.orders.filter((o) => !previousIds.includes(o.id));
                if (freshlyAssigned.length) playAssignChime();

                this.orders = data.orders.map((o) => ({ ...o, _new: freshlyAssigned.some((n) => n.id === o.id) }));
                if (freshlyAssigned.length) {
                    setTimeout(() => {
                        this.orders = this.orders.map((o) => ({ ...o, _new: false }));
                    }, 2500);
                }
            } catch (e) {
                // offline / server hiccup — next tick will catch up
            }
        },
    };
};

window.Alpine = Alpine;
Alpine.start();
