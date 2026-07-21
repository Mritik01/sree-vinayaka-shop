@extends('admin.layout')

@section('title', 'Orders')
@section('page-title', __('Orders'))

@section('content')
    @php
        $ordersForJs = collect($orders->items())->map(fn ($order) => [
            'id' => $order->id,
            'order_number' => $order->orderNumber(),
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'items_count' => $order->items->whereNull('removed_at')->count(),
            'coupon_code' => $order->coupon->code ?? null,
            'total' => (int) $order->total,
            'status' => $order->status,
            'payment_method' => strtoupper($order->payment_method ?? 'COD'),
            'payment_status' => $order->payment_status,
            'is_gift_order' => $order->is_gift_order,
            'created_at' => $order->created_at->format('d M, h:i A'),
            'eta_ends_at' => ($order->confirmed_at && $order->eta_minutes)
                ? $order->confirmed_at->addMinutes($order->eta_minutes)->valueOf()
                : null,
        ])->values();

        $statusTabs = ['' => __('All'), 'pending' => __('Pending'), 'confirmed' => __('Confirmed'), 'out_for_delivery' => __('Out for Delivery'), 'delivered' => __('Delivered'), 'cancelled' => __('Cancelled')];
        $quickFilterChips = [
            'today' => __('Today'), 'yesterday' => __('Yesterday'), 'last_2_days' => __('Last 2 Days'), 'last_7_days' => __('Last 7 Days'),
            'last_week' => __('Last Week'), 'this_week' => __('This Week'), 'this_month' => __('This Month'), 'last_month' => __('Last Month'),
            'last_3_months' => __('Last 3 Months'), 'last_6_months' => __('Last 6 Months'), 'this_year' => __('This Year'), 'custom' => __('Custom Range'),
        ];

        // built as a plain variable (rather than an inline @json([...]) literal in the x-data
        // attribute below) — a long, deeply-nested array expression inside a single @json(...)
        // call was silently mis-parsed by Blade's directive-argument matcher, truncating it right
        // after the first nested function call's closing paren; a single `@json($jsConfig)` with
        // no nested expressions in the directive call itself sidesteps that entirely
        $jsConfig = [
            'orders' => $ordersForJs,
            'statusFilter' => (string) $statusFilter,
            'currentPage' => $orders->currentPage(),
            'perPage' => $orders->perPage(),
            'search' => $search,
            'stats' => $stats,
            'filters' => $filters,
            'exportBaseUrl' => route('admin.orders.export'),
        ];
    @endphp

    <div x-data='ordersLivePage(@json($jsConfig))'
         @admin-orders-changed.window="handleOrdersChanged($event.detail)">

        {{-- collapsed by default (remembered per-browser) so the order grid — the thing an admin
             actually needs the instant a new order comes in — sits right under the page title
             instead of below a full screen of cards/chips every time --}}
        <div class="flex items-center justify-between gap-3 mb-4 flex-wrap">
            <button type="button" @click="togglePanel()"
                    class="inline-flex items-center gap-2 text-sm font-semibold text-maroon-700 hover:text-maroon-900 bg-white border border-gold-200/60 hover:border-gold-400 rounded-xl px-4 py-2.5 transition">
                <span x-text="panelOpen ? '▲' : '▼'"></span>
                {{ __('Filters & Statistics') }}
                <span x-show="!panelOpen" x-cloak class="text-maroon-400 font-normal">
                    · <span x-text="(stats.total_orders || 0).toLocaleString('en-IN')"></span> {{ __('orders') }}
                    · ₹<span x-text="(stats.total_sales || 0).toLocaleString('en-IN')"></span>
                </span>
            </button>
            <span x-show="hasActiveFilters()" x-cloak class="text-xs font-semibold text-gold-700 bg-gold-100 rounded-full px-3 py-1.5">
                🔎 {{ __('Filters active') }}
            </span>
        </div>

        <div x-show="panelOpen" x-cloak
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2">

        {{-- summary statistics — recompute instantly (AJAX) whenever any filter below changes --}}
        @php
            $statCards = [
                ['key' => 'total_orders', 'label' => __('Total Orders'), 'icon' => '📦', 'currency' => false, 'accent' => 'from-gold-400/15 to-gold-400/0 border-gold-300/60'],
                ['key' => 'total_sales', 'label' => __('Total Sales'), 'icon' => '💰', 'currency' => true, 'accent' => 'from-pista-400/15 to-pista-400/0 border-pista-400/40'],
                ['key' => 'delivered_orders', 'label' => __('Total Delivered Orders'), 'icon' => '✅', 'currency' => false, 'accent' => 'from-maroon-400/10 to-maroon-400/0 border-maroon-400/30'],
                ['key' => 'pending_orders', 'label' => __('Pending Orders'), 'icon' => '⏳', 'currency' => false, 'accent' => 'from-gold-400/15 to-gold-400/0 border-gold-300/60'],
                ['key' => 'cancelled_orders', 'label' => __('Cancelled Orders'), 'icon' => '❌', 'currency' => false, 'accent' => 'from-red-400/10 to-red-400/0 border-red-300/40'],
                ['key' => 'cod_orders', 'label' => __('COD Orders'), 'icon' => '💵', 'currency' => false, 'accent' => 'from-gold-400/15 to-gold-400/0 border-gold-300/60'],
                ['key' => 'online_orders', 'label' => __('Online Payment Orders'), 'icon' => '💳', 'currency' => false, 'accent' => 'from-pista-400/15 to-pista-400/0 border-pista-400/40'],
                ['key' => 'avg_order_value', 'label' => __('Average Order Value'), 'icon' => '📊', 'currency' => true, 'accent' => 'from-maroon-400/10 to-maroon-400/0 border-maroon-400/30'],
            ];
        @endphp
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-5">
            @foreach ($statCards as $card)
                <div class="relative overflow-hidden bg-white bg-gradient-to-br {{ $card['accent'] }} rounded-2xl border p-4 animate-fade-up">
                    <span class="text-xl">{{ $card['icon'] }}</span>
                    <p class="font-display text-xl xl:text-2xl text-maroon-800 mt-1.5 truncate">
                        @if ($card['currency']) ₹@endif<span x-text="(stats.{{ $card['key'] }} || 0).toLocaleString('en-IN')"></span>
                    </p>
                    <p class="text-maroon-400 text-xs mt-1">{{ $card['label'] }}</p>
                </div>
            @endforeach
        </div>

        {{-- filter bar --}}
        <div class="bg-white rounded-2xl border border-gold-200/60 p-4 mb-5 space-y-3.5">
            <div class="flex items-center justify-between gap-3 flex-wrap">
                <p class="text-xs font-semibold text-maroon-400 uppercase tracking-wide">🗓️ {{ __('Quick Filters') }}</p>
                <div class="flex items-center gap-2">
                    <span x-show="loading" x-cloak class="inline-flex items-center gap-1.5 text-xs text-gold-600 font-medium">
                        <svg class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                        {{ __('Updating…') }}
                    </span>
                    <a :href="exportUrl()" class="inline-flex items-center gap-1.5 bg-pista-500 hover:bg-pista-600 text-white font-semibold rounded-lg px-3.5 py-1.5 text-sm transition">
                        📊 {{ __('Export to Excel') }}
                    </a>
                    <button type="button" x-show="hasActiveFilters()" x-cloak @click="clearFilters()" class="text-xs font-semibold text-maroon-400 hover:text-maroon-600 underline underline-offset-2">
                        {{ __('Clear all filters') }}
                    </button>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                @foreach ($quickFilterChips as $value => $label)
                    <button type="button" @click="setQuickFilter('{{ $value }}')"
                            class="text-sm px-3.5 py-1.5 rounded-full border transition"
                            :class="quickFilter === '{{ $value }}' ? 'bg-gold-500 text-maroon-900 border-gold-500 font-semibold' : 'bg-white text-maroon-600 border-gold-200/60 hover:border-gold-400'">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            <div x-show="quickFilter === 'custom'" x-cloak class="flex flex-wrap items-center gap-2.5 pt-1">
                <label class="text-xs text-maroon-500 font-medium">{{ __('From') }}</label>
                <input type="date" x-model="dateFrom" @change="onFilterInput(0)" class="rounded-lg border border-gold-300/70 px-3 py-1.5 text-sm text-maroon-800 focus:outline-none focus:ring-2 focus:ring-gold-400">
                <label class="text-xs text-maroon-500 font-medium">{{ __('To') }}</label>
                <input type="date" x-model="dateTo" @change="onFilterInput(0)" class="rounded-lg border border-gold-300/70 px-3 py-1.5 text-sm text-maroon-800 focus:outline-none focus:ring-2 focus:ring-gold-400">
            </div>

            <div class="h-px bg-gold-100"></div>

            <div class="flex items-center gap-2 flex-wrap">
                @foreach ($statusTabs as $value => $label)
                    <button type="button" @click="setStatus('{{ $value }}')"
                            class="text-sm px-3.5 py-1.5 rounded-full border transition"
                            :class="statusFilter === '{{ $value }}' ? 'bg-maroon-700 text-cream border-maroon-700' : 'bg-white text-maroon-600 border-gold-200/60 hover:border-gold-400'">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            <div class="flex flex-wrap items-center gap-2.5">
                <select x-model="paymentStatus" @change="onFilterInput(0)" class="rounded-lg border border-gold-300/70 px-3 py-2 text-sm text-maroon-700 focus:outline-none focus:ring-2 focus:ring-gold-400">
                    <option value="">{{ __('Payment Status: All') }}</option>
                    <option value="pending">{{ __('Pending') }}</option>
                    <option value="paid">{{ __('Paid') }}</option>
                    <option value="failed">{{ __('Failed') }}</option>
                </select>

                <select x-model="paymentMethod" @change="onFilterInput(0)" class="rounded-lg border border-gold-300/70 px-3 py-2 text-sm text-maroon-700 focus:outline-none focus:ring-2 focus:ring-gold-400">
                    <option value="">{{ __('Payment Method: All') }}</option>
                    <option value="cod">{{ __('Cash on Delivery') }}</option>
                    <option value="razorpay">{{ __('Online (Razorpay)') }}</option>
                </select>

                <select x-model="riderId" @change="onFilterInput(0)" class="rounded-lg border border-gold-300/70 px-3 py-2 text-sm text-maroon-700 focus:outline-none focus:ring-2 focus:ring-gold-400">
                    <option value="">{{ __('Delivery Partner: All') }}</option>
                    @foreach ($riders as $rider)
                        <option value="{{ $rider->id }}">{{ $rider->name }}</option>
                    @endforeach
                </select>

                <div class="flex items-center gap-1.5">
                    <input type="number" x-model="amountMin" @input="onFilterInput()" placeholder="{{ __('Min ₹') }}"
                           class="w-24 rounded-lg border border-gold-300/70 px-3 py-2 text-sm text-maroon-800 placeholder-maroon-400/60 focus:outline-none focus:ring-2 focus:ring-gold-400">
                    <span class="text-maroon-400 text-sm">–</span>
                    <input type="number" x-model="amountMax" @input="onFilterInput()" placeholder="{{ __('Max ₹') }}"
                           class="w-24 rounded-lg border border-gold-300/70 px-3 py-2 text-sm text-maroon-800 placeholder-maroon-400/60 focus:outline-none focus:ring-2 focus:ring-gold-400">
                </div>

                <div class="flex items-center gap-3 ml-auto flex-wrap">
                    <x-admin.per-page-select :current="request('per_page', 10)" />
                    <div class="relative">
                        <input type="search" x-model="search" @input="onFilterInput()" @keydown.enter.prevent="applyFilters()"
                               placeholder="{{ __('Search order #, name, phone…') }}"
                               class="w-48 sm:w-64 rounded-lg border border-gold-300/70 bg-white pl-9 pr-3 py-2 text-sm text-maroon-800 placeholder-maroon-400/60 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-maroon-300 text-sm pointer-events-none">🔎</span>
                    </div>
                </div>
            </div>
        </div>
        </div>

        <div x-show="missedCount > 0" x-cloak x-transition
             class="flex items-center justify-between gap-3 mb-4 rounded-lg bg-gold-100/60 border border-gold-300/60 text-maroon-700 text-sm px-4 py-3">
            <span>🔔 <span x-text="missedCount"></span> {{ __('new order(s) came in outside this view.') }}</span>
            <button type="button" @click="window.location.reload()" class="font-semibold text-gold-700 hover:text-gold-800 underline underline-offset-2 transition">{{ __('Refresh') }}</button>
        </div>

        <p x-show="searching" x-cloak class="text-xs text-maroon-400 mb-2">
            <span x-text="totalMatches"></span> {{ __('order(s) match the selected filters') }}
        </p>

        <div class="bg-white rounded-xl border border-gold-200/60 overflow-hidden relative">
            <div x-show="loading" x-cloak class="absolute inset-0 bg-white/60 z-10 flex items-start justify-center pt-10">
                <svg class="animate-spin w-6 h-6 text-gold-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
            </div>

            <p x-show="orders.length === 0" x-cloak class="text-maroon-400 text-sm px-5 py-8 text-center">
                <span x-show="searching">{{ __('No orders match the selected filters.') }}</span>
                <span x-show="!searching">{{ __('No orders found.') }}</span>
            </p>

            <table x-show="orders.length > 0" x-cloak class="w-full text-sm">
                <thead>
                    <tr class="text-left text-maroon-400 border-b border-gold-100">
                        <th class="px-5 py-2.5 font-medium">{{ __('Order') }}</th>
                        <th class="px-5 py-2.5 font-medium">{{ __('Customer') }}</th>
                        <th class="px-5 py-2.5 font-medium">{{ __('Items') }}</th>
                        <th class="px-5 py-2.5 font-medium">{{ __('Coupon') }}</th>
                        <th class="px-5 py-2.5 font-medium">{{ __('Total') }}</th>
                        <th class="px-5 py-2.5 font-medium">{{ __('Status') }}</th>
                        <th class="px-5 py-2.5 font-medium">{{ __('Date') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="order in orders" :key="order.id">
                        <tr class="border-b border-gold-50 last:border-0 hover:bg-cream/50 transition-colors duration-1000 cursor-pointer"
                            :class="order._new && 'bg-gold-50'"
                            @click="window.location = `/admin/orders/${order.id}`">
                            <td class="px-5 py-3 text-maroon-800 font-medium">
                                <span x-text="order.order_number"></span>
                                <span x-show="order._new" x-cloak class="ml-1.5 text-[10px] font-bold uppercase tracking-wide px-1.5 py-0.5 rounded-full bg-gold-500 text-white animate-pulse">{{ __('New') }}</span>
                                <span x-show="order.is_gift_order" x-cloak class="ml-1.5 text-[10px] font-bold uppercase tracking-wide px-1.5 py-0.5 rounded-full bg-gradient-to-r from-pink-500 to-gold-500 text-white" title="Free gift claimed">🎁 {{ __('Gift') }}</span>
                            </td>
                            <td class="px-5 py-3 text-maroon-600">
                                <span x-text="order.customer_name"></span>
                                <span class="block text-maroon-400 text-xs" x-text="order.customer_phone"></span>
                            </td>
                            <td class="px-5 py-3 text-maroon-500" x-text="order.items_count"></td>
                            <td class="px-5 py-3 text-maroon-500" x-text="order.coupon_code || '—'"></td>
                            <td class="px-5 py-3 text-maroon-800 font-medium">
                                ₹<span x-text="order.total.toLocaleString('en-IN')"></span>
                                <span class="block mt-1 text-[10px] font-bold px-1.5 py-0.5 rounded w-fit"
                                      :class="order.payment_method === 'COD' ? (order.payment_status === 'paid' ? 'bg-pista-100 text-pista-600' : 'bg-gold-100 text-gold-600') : (order.payment_status === 'paid' ? 'bg-pista-100 text-pista-600' : 'bg-red-100 text-red-700')"
                                      x-text="order.payment_method === 'COD' ? (order.payment_status === 'paid' ? '✓ PAID (COD)' : '💵 COD') : (order.payment_status === 'paid' ? '✓ PAID' : '⚠ UNPAID')"></span>
                            </td>
                            <td class="px-5 py-3">
                                <span class="inline-block text-xs font-semibold px-2.5 py-1 rounded-full border" :class="statusBadgeClasses(order.status)" x-text="statusLabel(order.status)"></span>
                                <span x-show="etaText(order)" x-cloak class="block font-mono text-[10px] tabular-nums mt-1" :class="isOverdue(order) ? 'text-red-500 font-semibold' : 'text-maroon-400'" x-text="(isOverdue(order) ? '⚠️ ' : '⏱️ ') + etaText(order)"></span>
                            </td>
                            <td class="px-5 py-3 text-maroon-400" x-text="order.created_at"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
            {{-- hidden while any filter is active, same as before this feature (client-side
                 filtered results reflect the exact matching set, not a separate paginated view) --}}
            <div x-show="!searching">
                <x-admin.pagination :paginator="$orders" />
            </div>
        </div>
    </div>
@endsection
