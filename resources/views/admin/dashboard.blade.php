@extends('admin.layout')

@section('title', 'Dashboard')
@section('page-title', __('Dashboard'))

@section('content')
    @php
        $cards = [
            ['label' => __('Total Orders'), 'value' => $stats['total_orders'], 'icon' => '🧾', 'accent' => 'from-gold-400/15 to-gold-400/0 border-gold-300/60'],
            ['label' => __('Pending Orders'), 'value' => $stats['pending_orders'], 'icon' => '⏳', 'accent' => 'from-maroon-400/10 to-maroon-400/0 border-maroon-400/30'],
            ['label' => __('Revenue'), 'value' => '₹' . number_format($stats['revenue']), 'icon' => '💰', 'accent' => 'from-pista-400/15 to-pista-400/0 border-pista-400/40', 'note' => __('confirmed + delivered')],
            ['label' => __('Products'), 'value' => $stats['total_products'], 'icon' => '🍬', 'accent' => 'from-gold-400/15 to-gold-400/0 border-gold-300/60'],
            ['label' => __('Active Coupons'), 'value' => $stats['active_coupons'], 'icon' => '🏷️', 'accent' => 'from-maroon-400/10 to-maroon-400/0 border-maroon-400/30'],
            ['label' => __('Customers'), 'value' => $stats['customers'], 'icon' => '👥', 'accent' => 'from-pista-400/15 to-pista-400/0 border-pista-400/40'],
        ];
    @endphp

    <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
        @foreach ($cards as $card)
            <div class="relative overflow-hidden bg-white bg-gradient-to-br {{ $card['accent'] }} rounded-2xl border p-5">
                <span class="text-2xl">{{ $card['icon'] }}</span>
                <p class="font-display text-2xl xl:text-3xl text-maroon-800 mt-2 truncate">{{ $card['value'] }}</p>
                <p class="text-maroon-400 text-xs mt-1">{{ $card['label'] }}@isset($card['note']) <span class="text-maroon-300">· {{ $card['note'] }}</span>@endisset</p>
            </div>
        @endforeach
    </div>

    <div x-data='adminDashboardCharts(@json($chartData))'>
        <div class="grid lg:grid-cols-2 gap-5 mt-6">
            <div class="bg-white rounded-2xl border border-gold-200/60 p-5">
                <p class="font-display text-maroon-800 mb-4">{{ __('Orders — last 14 days') }}</p>
                <div class="h-56"><canvas x-ref="ordersChart"></canvas></div>
            </div>
            <div class="bg-white rounded-2xl border border-gold-200/60 p-5">
                <p class="font-display text-maroon-800 mb-4">{{ __('Revenue — last 14 days') }}</p>
                <div class="h-56"><canvas x-ref="revenueChart"></canvas></div>
            </div>
        </div>

        <div class="grid lg:grid-cols-[320px_1fr] gap-5 mt-5">
            <div class="bg-white rounded-2xl border border-gold-200/60 p-5">
                <p class="font-display text-maroon-800 mb-4">{{ __('Orders by status') }}</p>
                <div class="h-56"><canvas x-ref="statusChart"></canvas></div>
            </div>
            <div class="bg-white rounded-2xl border border-gold-200/60 p-5">
                <p class="font-display text-maroon-800 mb-4">{{ __('Top products by units sold') }}</p>
                <div class="h-56"><canvas x-ref="productsChart"></canvas></div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-gold-200/60 mt-6 overflow-hidden">
        <div class="px-5 py-4 border-b border-gold-100 flex items-center justify-between">
            <p class="font-display text-maroon-800">{{ __('Recent Orders') }}</p>
            <a href="{{ route('admin.orders.index') }}" class="text-sm text-gold-600 hover:text-gold-700 font-medium">{{ __('View all') }} →</a>
        </div>

        @if ($recentOrders->isEmpty())
            <p class="text-maroon-400 text-sm px-5 py-8 text-center">{{ __('No orders yet.') }}</p>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-maroon-400 border-b border-gold-100">
                        <th class="px-5 py-2.5 font-medium">{{ __('Order') }}</th>
                        <th class="px-5 py-2.5 font-medium">{{ __('Customer') }}</th>
                        <th class="px-5 py-2.5 font-medium">{{ __('Items') }}</th>
                        <th class="px-5 py-2.5 font-medium">{{ __('Total') }}</th>
                        <th class="px-5 py-2.5 font-medium">{{ __('Status') }}</th>
                        <th class="px-5 py-2.5 font-medium">{{ __('Date') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($recentOrders as $order)
                        <tr class="border-b border-gold-50 last:border-0 hover:bg-cream/50 transition">
                            <td class="px-5 py-3">
                                <a href="{{ route('admin.orders.show', $order) }}" class="text-maroon-800 font-medium hover:text-gold-600">{{ $order->orderNumber() }}</a>
                            </td>
                            <td class="px-5 py-3 text-maroon-600">{{ $order->customer_name }}</td>
                            <td class="px-5 py-3 text-maroon-500">{{ $order->items->count() }}</td>
                            <td class="px-5 py-3 text-maroon-800 font-medium">₹{{ number_format($order->total) }}</td>
                            <td class="px-5 py-3">
                                <x-admin.status-badge :status="$order->status" />
                            </td>
                            <td class="px-5 py-3 text-maroon-400">{{ $order->created_at->format('d M, h:i A') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
