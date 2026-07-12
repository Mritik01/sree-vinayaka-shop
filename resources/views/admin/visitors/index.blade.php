@extends('admin.layout')

@section('title', 'Visitors')
@section('page-title', 'Visitors')

@section('content')
    @php
        $deviceIcons = ['mobile' => '📱', 'tablet' => '📟', 'desktop' => '💻'];
        $statCards = [
            ['label' => 'Visitors Today', 'value' => $totalToday, 'icon' => '🌤️', 'accent' => 'from-gold-400/15 to-gold-400/0 border-gold-300/60'],
            ['label' => 'Visitors This Week', 'value' => $totalWeek, 'icon' => '📅', 'accent' => 'from-pista-400/15 to-pista-400/0 border-pista-400/40'],
            ['label' => 'Visitors All-Time', 'value' => $totalAllTime, 'icon' => '🌐', 'accent' => 'from-maroon-400/10 to-maroon-400/0 border-maroon-400/30'],
            ['label' => 'Registered vs Guest', 'value' => $registeredCount, 'icon' => '👤', 'accent' => 'from-gold-400/15 to-gold-400/0 border-gold-300/60', 'suffix' => ' / '.$guestCount.' guest'],
        ];
    @endphp

    <p class="text-maroon-500 text-sm -mt-1 mb-4">Every visit to the site is counted here — including people who never log in.</p>

    {{-- top stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach ($statCards as $i => $card)
            <div class="relative overflow-hidden bg-white bg-gradient-to-br {{ $card['accent'] }} rounded-2xl border p-5 animate-fade-up"
                 style="animation-delay: {{ $i * 80 }}ms">
                <span class="text-2xl">{{ $card['icon'] }}</span>
                <p class="font-display text-2xl xl:text-3xl text-maroon-800 mt-2">
                    <span x-data x-init="animateCounter($el, {{ $card['value'] }})">0</span>{{ $card['suffix'] ?? '' }}
                </p>
                <p class="text-maroon-400 text-xs mt-1">{{ $card['label'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- charts --}}
    <div x-data='visitorCharts(@json($chartData))' class="grid lg:grid-cols-3 gap-5 mt-6">
        <div class="bg-white rounded-2xl border border-gold-200/60 p-5 lg:col-span-2">
            <p class="font-display text-maroon-800 mb-4">Visits — last 14 days</p>
            <div class="h-56"><canvas x-ref="visitsChart"></canvas></div>
        </div>
        <div class="bg-white rounded-2xl border border-gold-200/60 p-5">
            <p class="font-display text-maroon-800 mb-4">Device Mix</p>
            <div class="h-56"><canvas x-ref="deviceChart"></canvas></div>
        </div>
    </div>

    {{-- recent visits --}}
    <div class="mt-6">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <p class="font-display text-maroon-800">Recent Visits</p>
            <div class="flex items-center gap-3 flex-wrap">
                <x-admin.per-page-select :current="request('per_page', 10)" />
                <x-admin.search-box :value="$search" target="visitors-results" placeholder="Search name, device, IP…" />
            </div>
        </div>

        <div id="visitors-results" class="bg-white rounded-2xl border border-gold-200/60 overflow-hidden">
            @include('admin.visitors._results')
        </div>
    </div>
@endsection
