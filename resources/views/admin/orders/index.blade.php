@extends('admin.layout')

@section('title', 'Orders')
@section('page-title', __('Orders'))

@section('content')
    @php
        $ordersForJs = collect($orders->items())->map(fn ($order) => [
            'id' => $order->id,
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'items_count' => $order->items->count(),
            'coupon_code' => $order->coupon->code ?? null,
            'total' => (int) $order->total,
            'status' => $order->status,
            'is_gift_order' => $order->is_gift_order,
            'created_at' => $order->created_at->format('d M, h:i A'),
            'eta_ends_at' => ($order->confirmed_at && $order->eta_minutes)
                ? $order->confirmed_at->addMinutes($order->eta_minutes)->valueOf()
                : null,
        ])->values();
    @endphp

    <div x-data='ordersLivePage(@json($ordersForJs), "{{ (string) $statusFilter }}", {{ $orders->currentPage() }}, {{ $orders->perPage() }}, @json($search))'
         @admin-orders-changed.window="handleOrdersChanged($event.detail)">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
            <div class="flex items-center gap-2 flex-wrap">
                @php $filters = ['' => __('All'), 'pending' => __('Pending'), 'confirmed' => __('Confirmed'), 'out_for_delivery' => __('Out for Delivery'), 'delivered' => __('Delivered'), 'cancelled' => __('Cancelled')]; @endphp
                @foreach ($filters as $value => $label)
                    <a href="{{ route('admin.orders.index', array_filter(array_merge(request()->query(), ['status' => $value, 'page' => null]))) }}"
                       class="text-sm px-3.5 py-1.5 rounded-full border transition {{ (string) $statusFilter === $value ? 'bg-maroon-700 text-cream border-maroon-700' : 'bg-white text-maroon-600 border-gold-200/60 hover:border-gold-400' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
            <div class="flex items-center gap-3 flex-wrap">
                <x-admin.per-page-select :current="request('per_page', 10)" />
                {{-- bound to ordersLivePage's own state (not the shared liveGridSearch component) so
                     results can be swapped straight into the same reactive `orders` array used for
                     the real-time new-order feature --}}
                <div class="relative">
                    <input type="search" x-model="search" @input="onSearchInput()" @keydown.enter.prevent="performSearch()"
                           placeholder="{{ __('Search order #, name, phone…') }}"
                           class="w-48 sm:w-64 rounded-lg border border-gold-300/70 bg-white pl-9 pr-3 py-2 text-sm text-maroon-800 placeholder-maroon-400/60 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-maroon-300 text-sm pointer-events-none">🔎</span>
                </div>
            </div>
        </div>

        <div x-show="missedCount > 0" x-cloak x-transition
             class="flex items-center justify-between gap-3 mb-4 rounded-lg bg-gold-100/60 border border-gold-300/60 text-maroon-700 text-sm px-4 py-3">
            <span>🔔 <span x-text="missedCount"></span> {{ __('new order(s) came in outside this view.') }}</span>
            <button type="button" @click="window.location.reload()" class="font-semibold text-gold-700 hover:text-gold-800 underline underline-offset-2 transition">{{ __('Refresh') }}</button>
        </div>

        <p x-show="searching" x-cloak class="text-xs text-maroon-400 mb-2">
            <span x-text="totalMatches"></span> {{ __('order(s) match') }} "<span x-text="search"></span>"
        </p>

        <div class="bg-white rounded-xl border border-gold-200/60 overflow-hidden">
            <p x-show="orders.length === 0" x-cloak class="text-maroon-400 text-sm px-5 py-8 text-center">
                <span x-show="searching" x-text='@json(__('No orders match')) + " “" + search + "”."'></span>
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
                                #<span x-text="order.id"></span>
                                <span x-show="order._new" x-cloak class="ml-1.5 text-[10px] font-bold uppercase tracking-wide px-1.5 py-0.5 rounded-full bg-gold-500 text-white animate-pulse">{{ __('New') }}</span>
                                <span x-show="order.is_gift_order" x-cloak class="ml-1.5 text-[10px] font-bold uppercase tracking-wide px-1.5 py-0.5 rounded-full bg-gradient-to-r from-pink-500 to-gold-500 text-white" title="Free gift claimed">🎁 {{ __('Gift') }}</span>
                            </td>
                            <td class="px-5 py-3 text-maroon-600">
                                <span x-text="order.customer_name"></span>
                                <span class="block text-maroon-400 text-xs" x-text="order.customer_phone"></span>
                            </td>
                            <td class="px-5 py-3 text-maroon-500" x-text="order.items_count"></td>
                            <td class="px-5 py-3 text-maroon-500" x-text="order.coupon_code || '—'"></td>
                            <td class="px-5 py-3 text-maroon-800 font-medium">₹<span x-text="order.total.toLocaleString('en-IN')"></span></td>
                            <td class="px-5 py-3">
                                <span class="inline-block text-xs font-semibold px-2.5 py-1 rounded-full border" :class="statusBadgeClasses(order.status)" x-text="statusLabel(order.status)"></span>
                                <span x-show="etaText(order)" x-cloak class="block font-mono text-[10px] tabular-nums mt-1" :class="isOverdue(order) ? 'text-red-500 font-semibold' : 'text-maroon-400'" x-text="(isOverdue(order) ? '⚠️ ' : '⏱️ ') + etaText(order)"></span>
                            </td>
                            <td class="px-5 py-3 text-maroon-400" x-text="order.created_at"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
            {{-- hidden while a client-side search is active, since it reflects the pre-search page load --}}
            <div x-show="!searching">
                <x-admin.pagination :paginator="$orders" />
            </div>
        </div>
    </div>
@endsection
