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

    {{-- customer directory --}}
    <div class="mt-6">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <div class="flex items-center gap-2 flex-wrap">
                @foreach ($sorts as $value => $label)
                    <a href="{{ route('admin.customers.index', array_merge(request()->query(), ['sort' => $value, 'page' => null])) }}"
                       class="text-sm px-3.5 py-1.5 rounded-full border transition {{ $sort === $value ? 'bg-maroon-700 text-cream border-maroon-700' : 'bg-white text-maroon-600 border-gold-200/60 hover:border-gold-400' }}">
                        {{ $label }}
                    </a>
                @endforeach
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

        <div id="customers-results" class="bg-white rounded-2xl border border-gold-200/60 overflow-hidden">
            @include('admin.customers._results')
        </div>
    </div>

    @include('admin.customers._notify-modal')
@endsection
