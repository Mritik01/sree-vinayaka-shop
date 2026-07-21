@extends('rider.layout')

@section('title', __('Deliveries'))

@section('content')
    {{-- your ratings — a lifetime summary, not live-polled like the deliveries queue below, so
         it's plain server-rendered Blade; the 5-1 star distribution bars port the exact
         percentage-bar pattern used for product reviews (product-show.blade.php's reviewsList) --}}
    <div class="bg-white rounded-2xl border border-gold-200/60 p-5 mb-5">
        <p class="font-display text-lg text-maroon-800 mb-4">{{ __('Your Ratings') }}</p>

        @if ($ratingCount === 0)
            <p class="text-sm text-maroon-400">{{ __("No ratings yet — they'll show up here after your first delivery is rated.") }}</p>
        @else
            <div class="flex items-center gap-5 flex-wrap">
                <div class="shrink-0 text-center">
                    <p class="font-display font-bold text-4xl text-maroon-800">{{ number_format($ratingAverage, 1) }}</p>
                    <div class="flex items-center gap-0.5 mt-1 justify-center">
                        @for ($i = 1; $i <= 5; $i++)
                            <svg class="w-3.5 h-3.5 {{ $i <= round($ratingAverage) ? 'text-gold-500' : 'text-gold-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 1.5l2.6 5.6 6.1.6-4.6 4.2 1.3 6-5.4-3-5.4 3 1.3-6L1.3 7.7l6.1-.6z"/>
                            </svg>
                        @endfor
                    </div>
                    <p class="text-xs text-maroon-400 mt-1">{{ trans_choice('{1}:count rating|[2,*]:count ratings', $ratingCount, ['count' => $ratingCount]) }}</p>
                </div>

                <div class="flex-1 min-w-[180px] space-y-1.5">
                    @foreach ($ratingDistribution as $star => $count)
                        @php $percent = $ratingCount > 0 ? round($count / $ratingCount * 100) : 0; @endphp
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 text-xs text-maroon-500 text-right shrink-0">{{ $star }}</span>
                            <svg class="w-3 h-3 text-gold-400 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 1.5l2.6 5.6 6.1.6-4.6 4.2 1.3 6-5.4-3-5.4 3 1.3-6L1.3 7.7l6.1-.6z"/>
                            </svg>
                            <div class="flex-1 h-1.5 rounded-full bg-gold-100 overflow-hidden">
                                <div class="h-full rounded-full bg-gradient-to-r from-gold-400 to-gold-600" style="width: {{ $percent }}%"></div>
                            </div>
                            <span class="w-6 text-[11px] text-maroon-400 text-right shrink-0 tabular-nums">{{ $count }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            @if ($latestRatings->isNotEmpty())
                <div class="mt-5 pt-4 border-t border-gold-100 space-y-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-maroon-400">{{ __('Latest Reviews') }}</p>
                    @foreach ($latestRatings as $rating)
                        <div class="text-sm">
                            <div class="flex items-center gap-2">
                                <div class="flex items-center gap-0.5">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <svg class="w-3 h-3 {{ $i <= $rating->rating ? 'text-gold-500' : 'text-gold-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 1.5l2.6 5.6 6.1.6-4.6 4.2 1.3 6-5.4-3-5.4 3 1.3-6L1.3 7.7l6.1-.6z"/>
                                        </svg>
                                    @endfor
                                </div>
                                <span class="font-medium text-maroon-700">{{ $rating->user ? explode(' ', trim($rating->user->name))[0] : __('A customer') }}</span>
                                <span class="text-maroon-300 text-xs">· {{ $rating->created_at->diffForHumans() }}</span>
                            </div>
                            <p class="text-maroon-600 mt-1">{{ $rating->comment }}</p>
                        </div>
                    @endforeach
                </div>
            @endif
        @endif
    </div>

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
                                <span x-text="order.order_number"></span> · <span x-text="order.customer_name"></span>
                            </p>
                            <p class="text-xs text-maroon-500 mt-0.5 truncate" x-text="order.delivery_address"></p>
                        </div>
                        <span class="inline-block text-xs font-semibold px-2.5 py-1 rounded-full border shrink-0" :class="statusBadgeClasses(order.status)" x-text="statusLabel(order.status)"></span>
                    </div>
                    <div class="flex items-center justify-between mt-3 text-sm">
                        <span class="text-maroon-500 flex items-center gap-1.5">
                            <span x-text="order.items_count"></span> {{ __('item(s)') }}
                            {{-- a "paid" online order can still have a balance_due > 0 if the admin
                                 added items after payment cleared — see Order::balanceDueOnDelivery() --}}
                            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded"
                                  :class="order.payment_method === 'COD' ? (order.payment_status === 'paid' ? 'bg-pista-100 text-pista-600' : 'bg-gold-100 text-gold-600') : (order.payment_status !== 'paid' ? 'bg-red-100 text-red-700' : (order.balance_due > 0 ? 'bg-gold-100 text-gold-600' : 'bg-pista-100 text-pista-600'))"
                                  x-text="order.payment_method === 'COD' ? (order.payment_status === 'paid' ? '✓ PAID' : '💵 COD') : (order.payment_status !== 'paid' ? '⚠ UNPAID' : (order.balance_due > 0 ? '💵 ₹' + order.balance_due.toLocaleString('en-IN') + ' DUE' : '✓ PAID'))"></span>
                        </span>
                        <span class="font-semibold text-maroon-800">₹<span x-text="order.total.toLocaleString('en-IN')"></span></span>
                    </div>
                </a>
            </template>
        </div>
    </div>
@endsection
