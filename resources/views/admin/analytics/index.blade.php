@extends('admin.layout')

@section('title', 'Analytics')
@section('page-title', 'Analytics')

@section('content')
    @php
        $deviceIcons = ['mobile' => '📱', 'tablet' => '📟', 'desktop' => '💻'];
    @endphp

    {{-- the retention window is surfaced directly rather than left as a silent surprise — see
         PruneAnalyticsData, scheduled daily in Kernel.php --}}
    <p class="text-maroon-500 text-sm -mt-1 mb-4">
        Showing the last {{ $retentionDays }} days for registered customers — older activity is automatically deleted to keep this light.
    </p>

    {{-- top stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div class="relative overflow-hidden bg-white bg-gradient-to-br from-gold-400/15 to-gold-400/0 border-gold-300/60 rounded-2xl border p-5 animate-fade-up">
            <span class="text-2xl">👤</span>
            <p class="font-display text-2xl xl:text-3xl text-maroon-800 mt-2">
                <span x-data x-init="animateCounter($el, {{ $registeredCount }})">0</span>
            </p>
            <p class="text-maroon-400 text-xs mt-1">Registered Customer Sessions</p>
        </div>
        <div class="relative overflow-hidden bg-white bg-gradient-to-br from-pista-400/15 to-pista-400/0 border-pista-400/40 rounded-2xl border p-5 animate-fade-up" style="animation-delay: 80ms">
            <span class="text-2xl">🖱️</span>
            <p class="font-display text-2xl xl:text-3xl text-maroon-800 mt-2">
                <span x-data x-init="animateCounter($el, {{ $topClicks->sum() }})">0</span>
            </p>
            <p class="text-maroon-400 text-xs mt-1">Tracked Clicks ({{ $retentionDays }}-day)</p>
        </div>
        <div class="relative overflow-hidden bg-white bg-gradient-to-br from-maroon-400/10 to-maroon-400/0 border-maroon-400/30 rounded-2xl border p-5 animate-fade-up" style="animation-delay: 160ms">
            <span class="text-2xl">🌍</span>
            <p class="font-display text-2xl xl:text-3xl text-maroon-800 mt-2">
                <span x-data x-init="animateCounter($el, {{ $locationCounts->count() }})">0</span>
            </p>
            <p class="text-maroon-400 text-xs mt-1">Distinct Locations Seen</p>
        </div>
    </div>

    {{-- charts + top lists --}}
    <div x-data='analyticsCharts(@json($chartData))'
         class="grid lg:grid-cols-2 gap-5 mt-6">
        <div class="bg-white rounded-2xl border border-gold-200/60 p-5">
            <p class="font-display text-maroon-800 mb-4">Device Mix</p>
            <div class="h-56"><canvas x-ref="deviceChart"></canvas></div>
        </div>
        <div class="bg-white rounded-2xl border border-gold-200/60 p-5">
            <p class="font-display text-maroon-800 mb-4">Meaningful Clicks</p>
            @if ($topClicks->isEmpty())
                <p class="text-maroon-400 text-sm py-16 text-center">No click events yet.</p>
            @else
                <div class="h-56"><canvas x-ref="clicksChart"></canvas></div>
            @endif
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-5 mt-5">
        <div class="bg-white rounded-2xl border border-gold-200/60 overflow-hidden">
            <div class="px-5 py-4 border-b border-gold-100"><p class="font-display text-maroon-800">📄 Top Pages</p></div>
            @if ($topPages->isEmpty())
                <p class="text-maroon-400 text-sm px-5 py-6 text-center">No page views yet.</p>
            @else
                <ul class="divide-y divide-gold-50">
                    @foreach ($topPages as $label => $count)
                        <li class="px-5 py-2.5 flex items-center justify-between gap-3 text-sm">
                            <span class="text-maroon-700 truncate">{{ $label }}</span>
                            <span class="text-maroon-400 font-medium shrink-0">{{ $count }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="bg-white rounded-2xl border border-gold-200/60 overflow-hidden">
            <div class="px-5 py-4 border-b border-gold-100"><p class="font-display text-maroon-800">🍬 Top Products Viewed</p></div>
            @if ($topProducts->isEmpty())
                <p class="text-maroon-400 text-sm px-5 py-6 text-center">No product views yet.</p>
            @else
                <ul class="divide-y divide-gold-50">
                    @foreach ($topProducts as $name => $count)
                        <li class="px-5 py-2.5 flex items-center justify-between gap-3 text-sm">
                            <span class="text-maroon-700 truncate">{{ $name }}</span>
                            <span class="text-maroon-400 font-medium shrink-0">{{ $count }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="bg-white rounded-2xl border border-gold-200/60 overflow-hidden">
            <div class="px-5 py-4 border-b border-gold-100"><p class="font-display text-maroon-800">🌍 Top Locations</p></div>
            @if ($locationCounts->isEmpty())
                <p class="text-maroon-400 text-sm px-5 py-6 text-center">No location data yet.</p>
            @else
                <ul class="divide-y divide-gold-50">
                    @foreach ($locationCounts as $row)
                        <li class="px-5 py-2.5 flex items-center justify-between gap-3 text-sm">
                            <span class="text-maroon-700 truncate">{{ $row->city }}, {{ $row->country }}</span>
                            <span class="text-maroon-400 font-medium shrink-0">{{ $row->c }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    {{-- raw event stream --}}
    <div class="mt-6">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <p class="font-display text-maroon-800">Activity Stream</p>
            <div class="flex items-center gap-3 flex-wrap">
                <x-admin.per-page-select :current="request('per_page', 25)" />
                <x-admin.search-box :value="$search" target="analytics-stream" placeholder="Search event, page, IP, name…" />
            </div>
        </div>

        <div id="analytics-stream" class="bg-white rounded-2xl border border-gold-200/60 overflow-hidden">
            @include('admin.analytics._stream')
        </div>
    </div>
@endsection
