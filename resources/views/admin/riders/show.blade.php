@extends('admin.layout')

@section('title', $rider->name)
@section('page-title', $rider->name)

@section('content')
    @php
        $statusStyles = [
            'pending' => 'bg-gold-100 text-gold-600 border-gold-300/60',
            'confirmed' => 'bg-pista-100 text-pista-600 border-pista-400/40',
            'out_for_delivery' => 'bg-sky-50 text-sky-600 border-sky-200',
            'delivered' => 'bg-maroon-100 text-maroon-600 border-maroon-400/30',
            'cancelled' => 'bg-red-50 text-red-600 border-red-200',
        ];
        $statusLabels = [
            'pending' => __('Pending'),
            'confirmed' => __('Confirmed'),
            'out_for_delivery' => __('Out for Delivery'),
            'delivered' => __('Delivered'),
            'cancelled' => __('Cancelled'),
        ];
        $tabs = [
            '' => __('All'),
            'active' => __('Active'),
            'delivered' => __('Delivered'),
            'cancelled' => __('Cancelled'),
        ];
    @endphp

    <a href="{{ route('admin.riders.index') }}" class="text-sm text-maroon-500 hover:text-maroon-700 transition">← {{ __('Back to Riders') }}</a>

    {{-- rider header --}}
    <div class="bg-white rounded-2xl border border-gold-200/60 shadow-sm p-6 mt-4 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            @if ($rider->photo_path)
                <img src="{{ asset($rider->photo_path) }}" alt="{{ $rider->name }}" class="w-16 h-16 rounded-full object-cover border border-gold-200/60">
            @else
                <span class="w-16 h-16 rounded-full bg-gold-100 border border-gold-300/60 flex items-center justify-center font-display font-bold text-xl text-gold-600">
                    {{ mb_strtoupper(mb_substr($rider->name, 0, 1)) }}
                </span>
            @endif
            <div>
                <p class="font-display text-2xl text-maroon-800">{{ $rider->name }}</p>
                <p class="text-sm text-maroon-500 mt-0.5">{{ '@'.$rider->username }} @if ($rider->phone) · {{ $rider->phone }} @endif</p>
            </div>
        </div>
        <a href="{{ route('admin.riders.edit', $rider) }}" class="btn-gold">{{ __('Edit Rider') }}</a>
    </div>

    {{-- summary counts — active/delivered/cancelled/total, the "full picture" for this rider --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mt-5">
        <a href="{{ route('admin.riders.show', ['rider' => $rider, 'status' => 'active']) }}"
           class="relative overflow-hidden bg-white bg-gradient-to-br from-sky-400/15 to-sky-400/0 rounded-2xl border border-sky-200 p-5 transition {{ $statusFilter === 'active' ? 'ring-2 ring-sky-400' : 'hover:border-sky-300' }}">
            <span class="text-2xl">🛵</span>
            <p class="font-display text-2xl xl:text-3xl text-maroon-800 mt-2">{{ $rider->active_orders_count }}</p>
            <p class="text-maroon-400 text-xs mt-1">{{ __('Active Orders') }} <span class="text-maroon-300">· {{ __('confirmed + out for delivery') }}</span></p>
        </a>
        <a href="{{ route('admin.riders.show', ['rider' => $rider, 'status' => 'delivered']) }}"
           class="relative overflow-hidden bg-white bg-gradient-to-br from-pista-400/15 to-pista-400/0 rounded-2xl border border-pista-400/40 p-5 transition {{ $statusFilter === 'delivered' ? 'ring-2 ring-pista-400' : 'hover:border-pista-400/70' }}">
            <span class="text-2xl">🎁</span>
            <p class="font-display text-2xl xl:text-3xl text-maroon-800 mt-2">{{ $rider->delivered_orders_count }}</p>
            <p class="text-maroon-400 text-xs mt-1">{{ __('Delivered') }} <span class="text-maroon-300">· {{ __('past orders') }}</span></p>
        </a>
        <a href="{{ route('admin.riders.show', ['rider' => $rider, 'status' => 'cancelled']) }}"
           class="relative overflow-hidden bg-white bg-gradient-to-br from-red-400/10 to-red-400/0 rounded-2xl border border-red-200 p-5 transition {{ $statusFilter === 'cancelled' ? 'ring-2 ring-red-300' : 'hover:border-red-300' }}">
            <span class="text-2xl">✕</span>
            <p class="font-display text-2xl xl:text-3xl text-maroon-800 mt-2">{{ $rider->cancelled_orders_count }}</p>
            <p class="text-maroon-400 text-xs mt-1">{{ __('Cancelled') }}</p>
        </a>
        <a href="{{ route('admin.riders.show', $rider) }}"
           class="relative overflow-hidden bg-white bg-gradient-to-br from-gold-400/15 to-gold-400/0 rounded-2xl border border-gold-300/60 p-5 transition {{ $statusFilter === '' ? 'ring-2 ring-gold-400' : 'hover:border-gold-400' }}">
            <span class="text-2xl">🧾</span>
            <p class="font-display text-2xl xl:text-3xl text-maroon-800 mt-2">{{ $rider->total_orders_count }}</p>
            <p class="text-maroon-400 text-xs mt-1">{{ __('Total Orders') }} <span class="text-maroon-300">· {{ __('all time') }}</span></p>
        </a>
    </div>

    {{-- order history --}}
    <div class="bg-white rounded-2xl border border-gold-200/60 mt-6 overflow-hidden">
        <div class="px-5 py-4 border-b border-gold-100 flex items-center justify-between flex-wrap gap-3">
            <p class="font-display text-maroon-800">{{ __('Orders') }}</p>
            <div class="flex items-center gap-2 flex-wrap">
                @foreach ($tabs as $value => $label)
                    <a href="{{ route('admin.riders.show', array_filter(['rider' => $rider->id, 'status' => $value ?: null])) }}"
                       class="text-sm px-3.5 py-1.5 rounded-full border transition {{ $statusFilter === $value ? 'bg-maroon-700 text-cream border-maroon-700' : 'bg-white text-maroon-600 border-gold-200/60 hover:border-gold-400' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>

        @if ($orders->isEmpty())
            <p class="text-maroon-400 text-sm px-5 py-8 text-center">{{ __('No orders in this bucket yet.') }}</p>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-maroon-400 border-b border-gold-100">
                        <th class="px-5 py-2.5 font-medium">{{ __('Order') }}</th>
                        <th class="px-5 py-2.5 font-medium">{{ __('Customer') }}</th>
                        <th class="px-5 py-2.5 font-medium">{{ __('Total') }}</th>
                        <th class="px-5 py-2.5 font-medium">{{ __('Status') }}</th>
                        <th class="px-5 py-2.5 font-medium">{{ __('Date') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($orders as $order)
                        <tr class="border-b border-gold-50 last:border-0 hover:bg-cream/50 transition cursor-pointer"
                            onclick="window.location='{{ route('admin.orders.show', $order) }}'">
                            <td class="px-5 py-3 text-maroon-800 font-medium">{{ $order->orderNumber() }}</td>
                            <td class="px-5 py-3 text-maroon-600">
                                {{ $order->customer_name }}
                                <span class="block text-maroon-400 text-xs">{{ $order->customer_phone }}</span>
                            </td>
                            <td class="px-5 py-3 text-maroon-800 font-medium">₹{{ number_format($order->total) }}</td>
                            <td class="px-5 py-3">
                                <span class="inline-block text-xs font-semibold px-2.5 py-1 rounded-full border {{ $statusStyles[$order->status] ?? $statusStyles['pending'] }}">
                                    {{ $statusLabels[$order->status] ?? $order->status }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-maroon-400">{{ $order->created_at->format('d M, h:i A') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <x-admin.pagination :paginator="$orders" />
        @endif
    </div>
@endsection
