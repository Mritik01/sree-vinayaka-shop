@extends('admin.layout')

@section('title', $customer->name)
@section('page-title', 'Customer Profile')

@section('content')
    @php
        $avgOrder = $customer->total_orders > 0 ? (int) round($customer->total_spent / $customer->total_orders) : 0;
        $badges = [
            'today' => ['icon' => '🏆', 'label' => "Today's MVP"],
            'week' => ['icon' => '👑', 'label' => "This Week's MVP"],
            'allTime' => ['icon' => '💎', 'label' => 'All-Time Legend'],
        ];
        $deviceIcons = ['mobile' => '📱', 'tablet' => '📟', 'desktop' => '💻'];
        $eventMeta = [
            'page_view' => ['icon' => '👁️', 'text' => 'Viewed'],
            'product_view' => ['icon' => '🔍', 'text' => 'Viewed product'],
            'add_to_cart' => ['icon' => '🛒', 'text' => 'Added to cart'],
            'favorite_add' => ['icon' => '❤️', 'text' => 'Favorited'],
            'favorite_remove' => ['icon' => '💔', 'text' => 'Unfavorited'],
            'coupon_applied' => ['icon' => '🏷️', 'text' => 'Applied coupon'],
            'review_submitted' => ['icon' => '⭐', 'text' => 'Left a review'],
            'order_placed' => ['icon' => '✅', 'text' => 'Placed order'],
            'login' => ['icon' => '🔑', 'text' => 'Logged in'],
        ];
    @endphp

    <a href="{{ route('admin.customers.index') }}" class="text-sm text-maroon-500 hover:text-maroon-700 transition">← Back to Customers</a>

    <div class="grid lg:grid-cols-[1fr_320px] gap-6 items-start mt-4">
        <div class="space-y-5" x-data="{ tab: 'overview' }">
            {{-- profile header --}}
            <div class="bg-white rounded-2xl border border-gold-200/60 p-6 animate-fade-up">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 shrink-0 rounded-full bg-maroon-700 text-cream flex items-center justify-center font-display font-bold text-2xl">
                        {{ strtoupper(substr($customer->name, 0, 1)) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="font-display text-xl text-maroon-800 truncate">{{ $customer->name }}</p>
                        <p class="text-maroon-500 text-sm">📞 <a href="tel:+91{{ preg_replace('/\D/', '', $customer->phone) }}" class="hover:text-gold-600">{{ $customer->phone }}</a></p>
                        <p class="text-maroon-400 text-xs mt-0.5">Customer since {{ $customer->created_at->format('d M Y') }}</p>
                    </div>
                    <div class="shrink-0 flex items-center gap-2">
                        @if (Auth::guard('admin')->user()?->isSuperAdmin())
                            <form method="POST" action="{{ route('admin.customers.impersonate', $customer) }}"
                                  onsubmit="return confirm('Log in as {{ $customer->name }}? This starts a logged, time-limited session viewing the site as this customer.');">
                                @csrf
                                <button type="submit"
                                        class="text-sm px-4 py-2 rounded-full bg-gold-500 text-maroon-900 hover:bg-gold-600 transition font-medium inline-flex items-center gap-1.5">
                                    🕵️ Login as Customer
                                </button>
                            </form>
                        @endif
                        <button type="button"
                                onclick='window.dispatchEvent(new CustomEvent("open-notify-modal", { detail: { id: {{ $customer->id }}, name: @json($customer->name) } }))'
                                class="text-sm px-4 py-2 rounded-full bg-maroon-700 text-cream hover:bg-maroon-800 transition font-medium inline-flex items-center gap-1.5">
                            🔔 Send Notification
                        </button>
                    </div>
                </div>

                @if (array_filter($mvpBadges))
                    <div class="flex flex-wrap items-center gap-2 mt-4 pt-4 border-t border-gold-100">
                        @foreach ($mvpBadges as $key => $isMvp)
                            @if ($isMvp)
                                <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-full bg-gold-100 text-gold-700 border border-gold-300/60 animate-bulb-glow">
                                    {{ $badges[$key]['icon'] }} {{ $badges[$key]['label'] }}
                                </span>
                            @endif
                        @endforeach
                    </div>
                @endif

                @if ($customer->cod_blocked_orders > 0)
                    <div class="flex flex-wrap items-center justify-between gap-3 mt-4 pt-4 border-t border-gold-100">
                        <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-full bg-red-50 text-red-600 border border-red-200">
                            ⚠️ COD blocked for next {{ $customer->cod_blocked_orders }} order{{ $customer->cod_blocked_orders === 1 ? '' : 's' }} — didn't accept a previous COD order
                        </span>
                        <form method="POST" action="{{ route('admin.customers.clear-cod-restriction', $customer) }}" onsubmit="return confirm('Clear the COD restriction for {{ $customer->name }}?')">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="text-xs font-semibold text-maroon-600 hover:text-maroon-800 underline transition">
                                Clear restriction
                            </button>
                        </form>
                    </div>
                @endif
            </div>

            {{-- tabs --}}
            <div class="flex items-center gap-1.5 bg-white rounded-2xl border border-gold-200/60 p-1.5 animate-fade-up w-fit">
                <button @click="tab = 'overview'" type="button"
                        :class="tab === 'overview' ? 'bg-maroon-700 text-cream shadow-sm' : 'text-maroon-500 hover:bg-cream'"
                        class="px-4 py-2 rounded-xl text-sm font-medium transition">
                    📋 Overview
                </button>
                <button @click="tab = 'behaviour'; $nextTick(() => window.dispatchEvent(new CustomEvent('render-behaviour-charts')))" type="button"
                        :class="tab === 'behaviour' ? 'bg-maroon-700 text-cream shadow-sm' : 'text-maroon-500 hover:bg-cream'"
                        class="px-4 py-2 rounded-xl text-sm font-medium transition inline-flex items-center gap-1.5">
                    🧭 User Behaviour
                    @if ($behaviour['isOnline'])
                        <span class="relative flex w-2 h-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-pista-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-pista-500"></span>
                        </span>
                    @endif
                </button>
            </div>

            {{-- ===== OVERVIEW TAB ===== --}}
            <div x-show="tab === 'overview'" x-cloak x-transition.opacity.duration.200ms class="space-y-5">
                <div class="grid grid-cols-3 gap-4">
                    <div class="bg-white rounded-2xl border border-gold-200/60 p-5 animate-fade-up" style="animation-delay: 60ms">
                        <p class="font-display text-2xl text-maroon-800" x-data x-init="animateCounter($el, {{ $customer->total_orders }})">0</p>
                        <p class="text-maroon-400 text-xs mt-1">Total Orders</p>
                    </div>
                    <div class="bg-white rounded-2xl border border-gold-200/60 p-5 animate-fade-up" style="animation-delay: 120ms">
                        <p class="font-display text-2xl text-maroon-800" x-data x-init="animateCounter($el, {{ $customer->total_spent }}, { prefix: '₹' })">₹0</p>
                        <p class="text-maroon-400 text-xs mt-1">Total Spent</p>
                    </div>
                    <div class="bg-white rounded-2xl border border-gold-200/60 p-5 animate-fade-up" style="animation-delay: 180ms">
                        <p class="font-display text-2xl text-maroon-800" x-data x-init="animateCounter($el, {{ $avgOrder }}, { prefix: '₹' })">₹0</p>
                        <p class="text-maroon-400 text-xs mt-1">Avg. Order Value</p>
                    </div>
                </div>

                @if ($monthlyLabels->count() > 1)
                    <div x-data='customerSpendChart(@json($monthlyLabels), @json($monthlyValues))' class="bg-white rounded-2xl border border-gold-200/60 p-5 animate-fade-up">
                        <p class="font-display text-maroon-800 mb-4">Spend Over Time</p>
                        <div class="h-52"><canvas x-ref="spendChart"></canvas></div>
                    </div>
                @endif

                {{-- order history --}}
                <div class="bg-white rounded-2xl border border-gold-200/60 overflow-hidden animate-fade-up">
                    <div class="px-5 py-4 border-b border-gold-100">
                        <p class="font-display text-maroon-800">Order History</p>
                    </div>
                    @if ($orders->isEmpty())
                        <p class="text-maroon-400 text-sm px-5 py-8 text-center">No orders yet.</p>
                    @else
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-maroon-400 border-b border-gold-100">
                                    <th class="px-5 py-2.5 font-medium">Order</th>
                                    <th class="px-5 py-2.5 font-medium">Items</th>
                                    <th class="px-5 py-2.5 font-medium">Total</th>
                                    <th class="px-5 py-2.5 font-medium">Status</th>
                                    <th class="px-5 py-2.5 font-medium">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($orders as $order)
                                    <tr class="border-b border-gold-50 last:border-0 hover:bg-cream/50 transition cursor-pointer" onclick="window.location='{{ route('admin.orders.show', $order) }}'">
                                        <td class="px-5 py-3 text-maroon-800 font-medium">{{ $order->orderNumber() }}</td>
                                        <td class="px-5 py-3 text-maroon-500">{{ $order->items->count() }}</td>
                                        <td class="px-5 py-3 text-maroon-800 font-medium">₹{{ number_format($order->total) }}</td>
                                        <td class="px-5 py-3"><x-admin.status-badge :status="$order->status" /></td>
                                        <td class="px-5 py-3 text-maroon-400">{{ $order->created_at->format('d M Y, h:i A') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>

                {{-- legal consent — read-only audit trail, never editable by an admin --}}
                @php
                    $latestSignupConsent = $customer->consents->firstWhere('context', 'signup');
                    $latestConsentOverall = $customer->consents->first();
                @endphp
                <div class="bg-white rounded-2xl border border-gold-200/60 overflow-hidden animate-fade-up">
                    <div class="px-5 py-4 border-b border-gold-100">
                        <p class="font-display text-maroon-800">Legal Consent</p>
                    </div>
                    @if ($customer->consents->isEmpty())
                        <p class="text-maroon-400 text-sm px-5 py-8 text-center">No consent recorded — this account predates the Terms &amp; Conditions system.</p>
                    @else
                        <div class="grid sm:grid-cols-2 gap-4 px-5 py-4">
                            <div class="rounded-xl bg-cream/50 border border-gold-100 p-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-maroon-400">Terms &amp; Conditions</p>
                                @if ($latestConsentOverall?->terms_version)
                                    <p class="text-sm text-pista-600 font-semibold mt-1.5">✓ Accepted — v{{ $latestConsentOverall->terms_version }}</p>
                                @else
                                    <p class="text-sm text-red-600 font-semibold mt-1.5">Not accepted</p>
                                @endif
                            </div>
                            <div class="rounded-xl bg-cream/50 border border-gold-100 p-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-maroon-400">Privacy Policy</p>
                                @if ($latestConsentOverall?->privacy_version)
                                    <p class="text-sm text-pista-600 font-semibold mt-1.5">✓ Accepted — v{{ $latestConsentOverall->privacy_version }}</p>
                                @else
                                    <p class="text-sm text-red-600 font-semibold mt-1.5">Not accepted</p>
                                @endif
                            </div>
                        </div>

                        <div class="px-5 pb-2 -mt-1">
                            <p class="text-xs text-maroon-400">
                                Most recent acceptance: {{ $latestConsentOverall->accepted_at->format('d M Y, h:i A') }}
                                @if ($latestConsentOverall->ip_address) · IP {{ $latestConsentOverall->ip_address }} @endif
                            </p>
                            @if ($latestConsentOverall->user_agent)
                                <p class="text-xs text-maroon-400 truncate mt-0.5" title="{{ $latestConsentOverall->user_agent }}">Device: {{ $latestConsentOverall->user_agent }}</p>
                            @endif
                        </div>

                        <div class="overflow-x-auto mt-2">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="text-left text-maroon-400 border-y border-gold-100">
                                        <th class="px-5 py-2.5 font-medium">Event</th>
                                        <th class="px-5 py-2.5 font-medium">Terms</th>
                                        <th class="px-5 py-2.5 font-medium">Privacy</th>
                                        <th class="px-5 py-2.5 font-medium">Date</th>
                                        <th class="px-5 py-2.5 font-medium">IP Address</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($customer->consents as $consent)
                                        <tr class="border-b border-gold-50 last:border-0">
                                            <td class="px-5 py-2.5 text-maroon-800 font-medium capitalize">{{ $consent->context }}</td>
                                            <td class="px-5 py-2.5 text-maroon-500">{{ $consent->terms_version ? 'v'.$consent->terms_version : '—' }}</td>
                                            <td class="px-5 py-2.5 text-maroon-500">{{ $consent->privacy_version ? 'v'.$consent->privacy_version : '—' }}</td>
                                            <td class="px-5 py-2.5 text-maroon-400">{{ $consent->accepted_at->format('d M Y, h:i A') }}</td>
                                            <td class="px-5 py-2.5 text-maroon-400">{{ $consent->ip_address ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            {{-- ===== USER BEHAVIOUR TAB ===== --}}
            <div x-show="tab === 'behaviour'" x-cloak x-transition.opacity.duration.200ms
                 x-data='customerBehaviourCharts(@json($behaviourChart))'
                 @render-behaviour-charts.window.once="renderCharts()"
                 class="space-y-5">

                {{-- device / browser / status summary cards --}}
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div class="bg-white rounded-2xl border border-gold-200/60 p-5 animate-fade-up" style="animation-delay: 40ms">
                        <p class="text-3xl">{{ $deviceIcons[$behaviour['primaryDevice']] ?? '❓' }}</p>
                        <p class="font-display text-maroon-800 mt-2 capitalize">{{ $behaviour['primaryDevice'] ?? 'Unknown' }}</p>
                        <p class="text-maroon-400 text-xs mt-0.5">Primary Device</p>
                    </div>
                    <div class="bg-white rounded-2xl border border-gold-200/60 p-5 animate-fade-up" style="animation-delay: 90ms">
                        <p class="text-3xl">🧭</p>
                        <p class="font-display text-maroon-800 mt-2">{{ $behaviour['primaryBrowser'] ?? 'Unknown' }}</p>
                        <p class="text-maroon-400 text-xs mt-0.5">Primary Browser</p>
                    </div>
                    <div class="bg-white rounded-2xl border border-gold-200/60 p-5 animate-fade-up" style="animation-delay: 140ms">
                        <p class="text-3xl">⚙️</p>
                        <p class="font-display text-maroon-800 mt-2">{{ $behaviour['primaryPlatform'] ?? 'Unknown' }}</p>
                        <p class="text-maroon-400 text-xs mt-0.5">Operating System</p>
                    </div>
                    <div class="bg-white rounded-2xl border border-gold-200/60 p-5 animate-fade-up" style="animation-delay: 190ms">
                        @if ($behaviour['isOnline'])
                            <p class="text-3xl">🟢</p>
                            <p class="font-display text-pista-600 mt-2">Online Now</p>
                        @else
                            <p class="text-3xl">⚪</p>
                            <p class="font-display text-maroon-800 mt-2">Offline</p>
                        @endif
                        <p class="text-maroon-400 text-xs mt-0.5">
                            {{ $behaviour['lastSeen'] ? 'Last seen '.$behaviour['lastSeen']->diffForHumans() : 'Never visited' }}
                        </p>
                    </div>
                </div>

                @if (($behaviour['activities'] ?? collect())->isEmpty())
                    <div class="bg-white rounded-2xl border border-gold-200/60 p-10 text-center animate-fade-up">
                        <p class="text-4xl mb-3">🕵️</p>
                        <p class="font-display text-maroon-800">No browsing activity recorded yet</p>
                        <p class="text-maroon-400 text-sm mt-1">Once this customer browses the site, their activity will show up here in real time.</p>
                    </div>
                @else
                    <div class="grid sm:grid-cols-3 gap-4">
                        <div class="bg-white rounded-2xl border border-gold-200/60 p-5 animate-fade-up">
                            <p class="font-display text-2xl text-maroon-800" x-data x-init="animateCounter($el, {{ $behaviour['sessionsCount'] }})">0</p>
                            <p class="text-maroon-400 text-xs mt-1">Visits / Sessions</p>
                        </div>
                        <div class="bg-white rounded-2xl border border-gold-200/60 p-5 animate-fade-up" style="animation-delay: 60ms">
                            <p class="font-display text-2xl text-maroon-800" x-data x-init="animateCounter($el, {{ $behaviour['totalPageViews'] }})">0</p>
                            <p class="text-maroon-400 text-xs mt-1">Pages Viewed</p>
                        </div>
                        <div class="bg-white rounded-2xl border border-gold-200/60 p-5 animate-fade-up" style="animation-delay: 120ms">
                            <p class="font-display text-2xl text-maroon-800" x-data x-init="animateCounter($el, {{ $behaviour['firstSeen'] ? now()->diffInDays($behaviour['firstSeen']) : 0 }})">0</p>
                            <p class="text-maroon-400 text-xs mt-1">Days Since First Visit</p>
                        </div>
                    </div>

                    <div class="grid lg:grid-cols-2 gap-5">
                        <div class="bg-white rounded-2xl border border-gold-200/60 p-5 animate-fade-up">
                            <p class="font-display text-maroon-800 mb-4">Visits Over Time (14 days)</p>
                            <div class="h-52"><canvas x-ref="activityChart"></canvas></div>
                        </div>
                        <div class="bg-white rounded-2xl border border-gold-200/60 p-5 animate-fade-up">
                            <p class="font-display text-maroon-800 mb-4">Device Usage</p>
                            <div class="h-52"><canvas x-ref="deviceChart"></canvas></div>
                        </div>
                    </div>

                    <div class="grid lg:grid-cols-2 gap-5">
                        {{-- most viewed products --}}
                        <div class="bg-white rounded-2xl border border-gold-200/60 overflow-hidden animate-fade-up">
                            <div class="px-5 py-4 border-b border-gold-100">
                                <p class="font-display text-maroon-800">🔍 Most Viewed Products</p>
                                <p class="text-maroon-400 text-xs mt-0.5">From their last 15 recorded actions</p>
                            </div>
                            @if ($behaviour['mostViewedProducts']->isEmpty())
                                <p class="text-maroon-400 text-sm px-5 py-6 text-center">No product views yet.</p>
                            @else
                                <ul class="divide-y divide-gold-50">
                                    @foreach ($behaviour['mostViewedProducts'] as $row)
                                        <li class="flex items-center justify-between px-5 py-3 text-sm">
                                            <span class="text-maroon-700">{{ $row->product->name ?? 'Deleted product' }}</span>
                                            <span class="text-maroon-400 font-medium">{{ $row->views }}×</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>

                        {{-- most visited pages --}}
                        <div class="bg-white rounded-2xl border border-gold-200/60 overflow-hidden animate-fade-up">
                            <div class="px-5 py-4 border-b border-gold-100">
                                <p class="font-display text-maroon-800">🧭 Most Visited Pages</p>
                                <p class="text-maroon-400 text-xs mt-0.5">From their last 15 recorded actions</p>
                            </div>
                            @if ($behaviour['topPages']->isEmpty())
                                <p class="text-maroon-400 text-sm px-5 py-6 text-center">No page views yet.</p>
                            @else
                                <ul class="divide-y divide-gold-50">
                                    @foreach ($behaviour['topPages'] as $label => $count)
                                        <li class="flex items-center justify-between px-5 py-3 text-sm">
                                            <span class="text-maroon-700">{{ $label }}</span>
                                            <span class="text-maroon-400 font-medium">{{ $count }}×</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>

                    {{-- activity timeline --}}
                    <div class="bg-white rounded-2xl border border-gold-200/60 overflow-hidden animate-fade-up">
                        <div class="px-5 py-4 border-b border-gold-100">
                            <p class="font-display text-maroon-800">🕒 Recent Activity</p>
                            <p class="text-maroon-400 text-xs mt-0.5">Last 15 actions — older activity is trimmed automatically to keep things fast</p>
                        </div>
                        <ul class="divide-y divide-gold-50 max-h-[28rem] overflow-y-auto">
                            @foreach ($behaviour['activities'] as $activity)
                                @php $meta = $eventMeta[$activity->event] ?? ['icon' => '•', 'text' => $activity->event]; @endphp
                                <li class="flex items-start gap-3 px-5 py-3 text-sm hover:bg-cream/40 transition">
                                    <span class="text-lg leading-none mt-0.5">{{ $meta['icon'] }}</span>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-maroon-700">
                                            {{ $meta['text'] }}
                                            @if ($activity->label)
                                                <span class="font-medium text-maroon-800">{{ $activity->label }}</span>
                                            @endif
                                        </p>
                                        <p class="text-maroon-400 text-xs mt-0.5">
                                            {{ $activity->created_at->diffForHumans() }}
                                            @if ($activity->device_type)
                                                · {{ $deviceIcons[$activity->device_type] ?? '' }} {{ ucfirst($activity->device_type) }}
                                            @endif
                                            @if ($activity->browser) · {{ $activity->browser }} @endif
                                        </p>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>

        {{-- delivery addresses --}}
        <div class="bg-white rounded-2xl border border-gold-200/60 p-5 animate-fade-up" style="animation-delay: 100ms">
            <p class="font-display text-maroon-800 mb-3">📍 Delivery Addresses</p>
            @if ($addresses->isEmpty())
                <p class="text-maroon-400 text-sm">No address on file yet.</p>
            @else
                <ul class="space-y-3">
                    @foreach ($addresses as $address)
                        <li class="text-sm text-maroon-600 bg-cream/60 border border-gold-100 rounded-lg px-3.5 py-2.5">
                            {{ $address }}
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        {{-- assigned coupons — restricted to just this customer once at least one is attached --}}
        <div class="bg-white rounded-2xl border border-gold-200/60 p-5 animate-fade-up" style="animation-delay: 140ms">
            <p class="font-display text-maroon-800 mb-3">🏷️ Coupons</p>

            @if ($assignedCoupons->isEmpty())
                <p class="text-maroon-400 text-sm">No coupons assigned yet — this customer can still use any general (unrestricted) coupon.</p>
            @else
                <ul class="space-y-2.5 mb-4">
                    @foreach ($assignedCoupons as $coupon)
                        <li class="flex items-center justify-between gap-3 bg-cream/60 border border-gold-100 rounded-lg px-3.5 py-2.5">
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-maroon-800 tracking-wide">{{ $coupon->code }}</p>
                                <p class="text-xs text-maroon-500 truncate">{{ $coupon->description ?: ($coupon->discount_type === 'percent' ? $coupon->discount_value.'% off' : '₹'.$coupon->discount_value.' off') }}</p>
                            </div>
                            <form method="POST" action="{{ route('admin.customers.coupons.detach', [$customer, $coupon]) }}"
                                  onsubmit="return confirm('Remove {{ $coupon->code }} from {{ $customer->name }}? They will only be able to use it again if it is not restricted to anyone else, or it is re-assigned.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" aria-label="Remove {{ $coupon->code }}" class="shrink-0 text-maroon-300 hover:text-red-600 transition text-lg leading-none">✕</button>
                            </form>
                        </li>
                    @endforeach
                </ul>
                <p class="text-xs text-maroon-400 mb-4">Only {{ $customer->name }} can redeem these — everyone else will see "not available for your account."</p>
            @endif

            @if ($assignableCoupons->isNotEmpty())
                <form method="POST" action="{{ route('admin.customers.coupons.attach', $customer) }}" class="flex items-center gap-2 pt-3 border-t border-gold-100">
                    @csrf
                    <select name="coupon_id" required class="flex-1 min-w-0 rounded-lg border border-gold-300/70 bg-white px-2.5 py-2 text-sm text-maroon-700 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
                        <option value="">Assign a coupon…</option>
                        @foreach ($assignableCoupons as $coupon)
                            <option value="{{ $coupon->id }}">{{ $coupon->code }}{{ $coupon->description ? ' — '.$coupon->description : '' }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="shrink-0 text-sm font-semibold px-4 py-2 rounded-lg bg-maroon-700 text-cream hover:bg-maroon-800 transition">Attach</button>
                </form>
            @elseif ($assignedCoupons->isEmpty())
                <p class="text-xs text-maroon-400">No unassigned single-use coupons available — "once per user" coupons stay open to everyone and can't be assigned here. Create a single-use coupon from the Coupons page if you need one.</p>
            @endif

            @error('coupon_id')
                <p class="text-xs text-red-600 mt-2.5">{{ $message }}</p>
            @enderror
        </div>
    </div>

    @include('admin.customers._notify-modal')
@endsection
