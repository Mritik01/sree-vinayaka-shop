@extends('admin.layout')

@section('title', 'Customers')
@section('page-title', 'Customers')

@section('content')
    @php
        $statCards = [
            ['label' => 'Total Customers', 'value' => $totalCustomers, 'icon' => '👥', 'accent' => 'from-gold-400/15 to-gold-400/0 border-gold-300/60'],
            ['label' => 'Revenue from Customers', 'value' => $totalRevenue, 'icon' => '💰', 'accent' => 'from-pista-400/15 to-pista-400/0 border-pista-400/40', 'prefix' => '₹'],
            ['label' => 'New This Week', 'value' => $newThisWeek, 'icon' => '✨', 'accent' => 'from-maroon-400/10 to-maroon-400/0 border-maroon-400/30'],
        ];

        $mvpCards = [
            ['key' => 'today', 'label' => "Today's MVP", 'icon' => '🏆', 'sub' => 'Highest spend today'],
            ['key' => 'week', 'label' => "This Week's MVP", 'icon' => '👑', 'sub' => 'Highest spend this week'],
            ['key' => 'allTime', 'label' => 'All-Time Legend', 'icon' => '💎', 'sub' => 'Highest spend ever'],
        ];

        $sorts = ['spent' => 'Highest Spend', 'orders' => 'Most Orders', 'recent' => 'Recently Active', 'newest' => 'Newest Customer', 'name' => 'Name'];
    @endphp

    {{-- collapsed by default (remembered per-browser) — same fix as the Orders page: the customer
         grid an admin actually needs sits right under the page title instead of a full screen of
         cards/podium/charts every single time --}}
    <div x-data="{ panelOpen: localStorage.getItem('mb_admin_customers_panel_open') !== null ? localStorage.getItem('mb_admin_customers_panel_open') === '1' : false,
                   togglePanel() { this.panelOpen = !this.panelOpen; localStorage.setItem('mb_admin_customers_panel_open', this.panelOpen ? '1' : '0'); } }">
        <div class="flex items-center justify-between gap-3 mb-4 flex-wrap">
            <button type="button" @click="togglePanel()"
                    class="inline-flex items-center gap-2 text-sm font-semibold text-maroon-700 hover:text-maroon-900 bg-white border border-gold-200/60 hover:border-gold-400 rounded-xl px-4 py-2.5 transition">
                <span x-text="panelOpen ? '▲' : '▼'"></span>
                📊 Statistics &amp; Insights
                <span x-show="!panelOpen" x-cloak class="text-maroon-400 font-normal">
                    · {{ number_format($totalCustomers) }} customers · ₹{{ number_format($totalRevenue) }} revenue
                    @if ($mvp['allTime']) · 💎 {{ $mvp['allTime']['user']->name }} is the All-Time Legend @endif
                </span>
            </button>
        </div>

        {{-- x-if (not x-show) is deliberate: the charts below init Chart.js from the canvas's
             actual pixel size at mount time, and a canvas measured while display:none (as x-show
             would leave it before first expand) mounts as a broken 0×0 chart forever, even after
             becoming visible. x-if only ever stamps this into the DOM once panelOpen is truly
             true, so Chart.js always sees a real, laid-out canvas. --}}
        <template x-if="panelOpen">
        <div x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2">

    {{-- top stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        @foreach ($statCards as $i => $card)
            <div class="relative overflow-hidden bg-white bg-gradient-to-br {{ $card['accent'] }} rounded-2xl border p-5 animate-fade-up"
                 style="animation-delay: {{ $i * 80 }}ms">
                <span class="text-2xl">{{ $card['icon'] }}</span>
                <p class="font-display text-2xl xl:text-3xl text-maroon-800 mt-2"
                   x-data x-init="animateCounter($el, {{ $card['value'] }}, { prefix: '{{ $card['prefix'] ?? '' }}' })">0</p>
                <p class="text-maroon-400 text-xs mt-1">{{ $card['label'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- MVP podium --}}
    <div class="grid md:grid-cols-3 gap-5 mt-6">
        @foreach ($mvpCards as $i => $card)
            @php $entry = $mvp[$card['key']]; @endphp
            <a href="{{ $entry ? route('admin.customers.show', $entry['user']) : '#' }}"
               class="block bg-white rounded-2xl border border-gold-200/60 overflow-hidden shadow-sm transition duration-300 animate-fade-up {{ $entry ? 'hover:shadow-xl hover:-translate-y-1' : 'pointer-events-none' }}"
               style="animation-delay: {{ 240 + $i * 100 }}ms">
                <div class="relative bg-gradient-to-r from-gold-400 to-gold-600 px-5 py-5 text-center overflow-hidden">
                    <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(circle, white 1.5px, transparent 1.5px); background-size: 16px 16px;"></div>
                    <p class="relative text-4xl inline-block {{ $entry ? 'animate-bulb-glow' : '' }}">{{ $card['icon'] }}</p>
                    <p class="relative font-display font-bold text-maroon-900 mt-1">{{ $card['label'] }}</p>
                    <p class="relative text-maroon-800/70 text-xs mt-0.5">{{ $card['sub'] }}</p>
                </div>

                <div class="p-5">
                    @if ($entry)
                        <div class="flex items-center gap-3">
                            <div class="w-11 h-11 shrink-0 rounded-full bg-maroon-700 text-cream flex items-center justify-center font-display font-bold">
                                {{ strtoupper(substr($entry['user']->name, 0, 1)) }}
                            </div>
                            <div class="min-w-0">
                                <p class="font-display text-maroon-800 truncate">{{ $entry['user']->name }}</p>
                                <p class="text-maroon-400 text-xs">{{ $entry['user']->phone }}</p>
                            </div>
                        </div>
                        <div class="flex items-center justify-between mt-4 pt-4 border-t border-gold-100">
                            <div>
                                <p class="text-2xl font-display font-bold text-maroon-800"
                                   x-data x-init="animateCounter($el, {{ $entry['spent'] }}, { prefix: '₹' })">₹0</p>
                                <p class="text-maroon-400 text-xs">total spent</p>
                            </div>
                            <div class="text-right">
                                <p class="text-2xl font-display font-bold text-maroon-800">{{ $entry['orders_count'] }}</p>
                                <p class="text-maroon-400 text-xs">order{{ $entry['orders_count'] === 1 ? '' : 's' }}</p>
                            </div>
                        </div>
                    @else
                        <p class="text-maroon-400 text-sm text-center py-3">No orders yet — check back soon!</p>
                    @endif
                </div>
            </a>
        @endforeach
    </div>

    {{-- charts --}}
    <div x-data='customersCharts(@json($chartData))' class="grid lg:grid-cols-2 gap-5 mt-6">
        <div class="bg-white rounded-2xl border border-gold-200/60 p-5">
            <p class="font-display text-maroon-800 mb-4">Top Spenders</p>
            <div class="h-56"><canvas x-ref="topSpendersChart"></canvas></div>
        </div>
        <div class="bg-white rounded-2xl border border-gold-200/60 p-5">
            <p class="font-display text-maroon-800 mb-4">New Customers — last 14 days</p>
            <div class="h-56"><canvas x-ref="newCustomersChart"></canvas></div>
        </div>
    </div>

        </div>
        </template>
    </div>

    {{-- customer directory --}}
    <div class="mt-6">
        @php
            $statusTabs = ['' => 'All Customers', 'active' => 'Active Customers', 'blocked' => 'Blocked Customers'];
        @endphp
        {{-- status tabs — the primary filter, in the same bold maroon-tab style used site-wide,
             kept visually separate from the lighter sort chips below (via the border-t divider)
             so the two rows read as "which customers" vs "what order", not one pile of pills --}}
        <div class="flex items-center gap-2 flex-wrap">
            @foreach ($statusTabs as $value => $label)
                <a href="{{ route('admin.customers.index', array_merge(request()->query(), ['status' => $value ?: null, 'page' => null])) }}"
                   class="text-sm px-3.5 py-1.5 rounded-full border transition {{ $statusFilter === $value ? 'bg-maroon-700 text-cream border-maroon-700' : 'bg-white text-maroon-600 border-gold-200/60 hover:border-gold-400' }}">
                    {{ $label }}{{ $value === 'blocked' && $blockedCount > 0 ? " ({$blockedCount})" : '' }}
                </a>
            @endforeach
        </div>

        <div class="flex flex-wrap items-center justify-between gap-x-6 gap-y-3 mt-4 pt-4 border-t border-gold-100">
            <div class="flex items-center gap-2 flex-wrap">
                <span class="text-xs font-semibold uppercase tracking-wide text-maroon-400 mr-0.5">Sort by</span>
                @foreach ($sorts as $value => $label)
                    <a href="{{ route('admin.customers.index', array_merge(request()->query(), ['sort' => $value, 'page' => null])) }}"
                       class="text-xs px-3 py-1 rounded-full border transition {{ $sort === $value ? 'bg-gold-500 text-maroon-900 border-gold-500 font-semibold' : 'bg-white text-maroon-500 border-gold-200/60 hover:border-gold-400' }}">
                        {{ $label }}
                    </a>
                @endforeach
                @if ($sort !== 'spent' || $statusFilter !== '' || $search !== '')
                    {{-- resets sort/status/search all at once — a plain link back to the bare
                         index route, since none of those three have a dedicated "off" state on
                         their own chips (sort especially — some sort is always active) --}}
                    <a href="{{ route('admin.customers.index') }}" class="text-xs text-maroon-400 hover:text-maroon-600 underline underline-offset-2 transition ml-1">
                        ✕ Clear Filters
                    </a>
                @endif
            </div>
            <div class="flex items-center gap-3 flex-wrap">
                <button type="button"
                        onclick='window.dispatchEvent(new CustomEvent("open-notify-modal", { detail: { all: true } }))'
                        class="text-sm px-4 py-1.5 rounded-full bg-maroon-700 text-cream hover:bg-maroon-800 transition font-medium inline-flex items-center gap-1.5">
                    📣 Notify All
                </button>
                <x-admin.per-page-select :current="request('per_page', 10)" />
                <x-admin.search-box :value="$search" target="customers-results" placeholder="Search name or phone…" />
            </div>
        </div>

        <div id="customers-results" class="bg-white rounded-2xl border border-gold-200/60 overflow-hidden mt-4">
            @include('admin.customers._results')
        </div>
    </div>

    @include('admin.customers._notify-modal')
@endsection
