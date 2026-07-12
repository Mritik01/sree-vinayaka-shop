@extends('rider.layout')

@section('title', __('Deliveries'))

@section('content')
    <div x-data='riderOrdersPage(@json($orders))'>
        <p class="font-display text-xl text-maroon-800 mb-4">{{ __('Active Deliveries') }} <span class="text-maroon-400 text-base font-sans" x-text="'(' + orders.length + ')'"></span></p>

        <div x-show="orders.length === 0" x-cloak class="bg-white rounded-2xl border border-gold-200/60 p-8 text-center">
            <p class="text-3xl mb-2">🎉</p>
            <p class="text-maroon-500 text-sm">{{ __('Nothing to deliver right now — check back soon.') }}</p>
        </div>

        <div x-show="orders.length > 0" x-cloak class="space-y-3">
            <template x-for="order in orders" :key="order.id">
                <a :href="'/rider/orders/' + order.id"
                   class="block bg-white rounded-xl border p-4 hover:border-gold-400 transition"
                   :class="order._new ? 'border-gold-400 bg-gold-50' : 'border-gold-200/60'">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-semibold text-maroon-800">
                                #<span x-text="order.id"></span> · <span x-text="order.customer_name"></span>
                            </p>
                            <p class="text-xs text-maroon-500 mt-0.5 truncate" x-text="order.delivery_address"></p>
                        </div>
                        <span class="inline-block text-xs font-semibold px-2.5 py-1 rounded-full border shrink-0" :class="statusBadgeClasses(order.status)" x-text="statusLabel(order.status)"></span>
                    </div>
                    <div class="flex items-center justify-between mt-3 text-sm">
                        <span class="text-maroon-500"><span x-text="order.items_count"></span> {{ __('item(s)') }} · <span x-text="order.payment_method"></span></span>
                        <span class="font-semibold text-maroon-800">₹<span x-text="order.total.toLocaleString('en-IN')"></span></span>
                    </div>
                </a>
            </template>
        </div>
    </div>
@endsection
