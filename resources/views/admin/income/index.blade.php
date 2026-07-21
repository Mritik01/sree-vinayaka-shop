@extends('admin.layout')

@section('title', 'Income')
@section('page-title', __('Income'))

@section('content')
    @php
        $statCards = [
            ['label' => __("Today's Income"), 'value' => '₹'.number_format($cards['today_income']), 'icon' => '🕐', 'accent' => 'from-gold-400/15 to-gold-400/0 border-gold-300/60'],
            ['label' => __("This Month's Income"), 'value' => '₹'.number_format($cards['month_income']), 'icon' => '📅', 'accent' => 'from-pista-400/15 to-pista-400/0 border-pista-400/40'],
            ['label' => __('Total Delivered Orders'), 'value' => number_format($cards['total_orders']), 'icon' => '📦', 'accent' => 'from-maroon-400/10 to-maroon-400/0 border-maroon-400/30'],
            ['label' => __('Fixed ₹15 Commission Earnings'), 'value' => '₹'.number_format($cards['fixed_commission_total']), 'icon' => '🎯', 'accent' => 'from-gold-400/15 to-gold-400/0 border-gold-300/60'],
            ['label' => __('Delivery Charge Earnings (50%)'), 'value' => '₹'.number_format($cards['delivery_income_total']), 'icon' => '🚚', 'accent' => 'from-pista-400/15 to-pista-400/0 border-pista-400/40'],
            ['label' => __('Average Income Per Order'), 'value' => '₹'.number_format($cards['avg_per_order']), 'icon' => '📊', 'accent' => 'from-maroon-400/10 to-maroon-400/0 border-maroon-400/30'],
        ];
    @endphp

    {{-- hero: total platform income, always-on, never scoped to a historical month --}}
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-maroon-800 via-maroon-700 to-maroon-800 p-6 sm:p-7 shadow-lg animate-fade-up">
        <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(circle, #e9c873 1.5px, transparent 1.5px); background-size: 18px 18px;"></div>
        <div class="relative flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-cream/70 text-xs font-semibold uppercase tracking-wide">💰 {{ __('Total Platform Income') }}</p>
                <p class="font-display font-extrabold text-4xl sm:text-5xl text-gold-400 mt-1.5">₹{{ number_format($cards['total_income']) }}</p>
                <p class="text-cream/60 text-xs mt-2">{{ __('Every rupee ever earned from ₹15 commission + 50% delivery charge share, across') }} {{ number_format($cards['total_orders']) }} {{ __('delivered orders.') }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2.5">
                <a href="{{ route('admin.income.history') }}" class="inline-flex items-center gap-1.5 bg-cream/10 hover:bg-cream/20 text-cream font-semibold rounded-xl px-4 py-2.5 text-sm transition">
                    📖 {{ __('Income History') }}
                </a>
                <a href="{{ route('admin.income.export.csv', request()->query()) }}" class="inline-flex items-center gap-1.5 bg-cream/10 hover:bg-cream/20 text-cream font-semibold rounded-xl px-4 py-2.5 text-sm transition">
                    ⬇️ {{ __('Export CSV') }}
                </a>
                <a href="{{ route('admin.income.export.pdf', request()->query()) }}" class="inline-flex items-center gap-1.5 bg-cream/10 hover:bg-cream/20 text-cream font-semibold rounded-xl px-4 py-2.5 text-sm transition">
                    📄 {{ __('Export PDF') }}
                </a>
                <form method="POST" action="{{ route('admin.income.reset-month') }}" onsubmit="return confirm('{{ __('Reset this month\'s tracking? Historical income is never deleted — every past order stays permanently available in Income History.') }}');">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1.5 bg-gold-500 hover:bg-gold-600 text-maroon-900 font-semibold rounded-xl px-4 py-2.5 text-sm transition">
                        ♻️ {{ __('Reset Current Month') }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    @if ($latestReset)
        <p class="text-xs text-maroon-400 mt-2.5 px-1">
            ℹ️ {{ __('This month was manually reset by') }} <strong class="text-maroon-600">{{ $latestReset->admin->name ?? __('an admin') }}</strong>
            {{ __('on') }} {{ $latestReset->reset_at->format('d M Y, h:i A') }} — {{ __('no data was lost.') }}
        </p>
    @endif

    @if ($isHistoricalView)
        <div class="mt-5 rounded-2xl bg-gold-50 border-2 border-gold-400 p-4 flex items-center justify-between gap-3 flex-wrap animate-fade-up">
            <div>
                <p class="font-display font-bold text-maroon-800">📅 {{ __('Viewing archived income for') }} {{ \Illuminate\Support\Carbon::createFromDate($viewingYear, $viewingMonth, 1)->translatedFormat('F Y') }}</p>
                <p class="text-xs text-maroon-500 mt-1">
                    {{ number_format($viewingTotals->orders ?? 0) }} {{ __('orders') }} ·
                    ₹{{ number_format($viewingTotals->fixed_total ?? 0) }} {{ __('fixed') }} ·
                    ₹{{ number_format($viewingTotals->delivery_total ?? 0) }} {{ __('delivery') }} ·
                    <strong>₹{{ number_format($viewingTotals->total ?? 0) }} {{ __('total') }}</strong>
                </p>
            </div>
            <a href="{{ route('admin.income.index') }}" class="text-sm font-semibold text-maroon-700 hover:text-maroon-900 underline underline-offset-2">← {{ __('Back to live dashboard') }}</a>
        </div>
    @endif

    <div class="grid grid-cols-2 lg:grid-cols-3 gap-4 mt-5">
        @foreach ($statCards as $card)
            <div class="relative overflow-hidden bg-white bg-gradient-to-br {{ $card['accent'] }} rounded-2xl border p-5 animate-fade-up">
                <span class="text-2xl">{{ $card['icon'] }}</span>
                <p class="font-display text-2xl xl:text-3xl text-maroon-800 mt-2 truncate">{{ $card['value'] }}</p>
                <p class="text-maroon-400 text-xs mt-1">{{ $card['label'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- analytics --}}
    <div x-data='incomeDashboardCharts(@json($chartData))'>
        <div class="grid lg:grid-cols-2 gap-5 mt-6">
            <div class="bg-white rounded-2xl border border-gold-200/60 p-5">
                <p class="font-display text-maroon-800 mb-4">{{ __('Daily Income — last 30 days') }}</p>
                <div class="h-56"><canvas x-ref="dailyChart"></canvas></div>
            </div>
            <div class="bg-white rounded-2xl border border-gold-200/60 p-5">
                <p class="font-display text-maroon-800 mb-4">{{ __('Delivered Orders Trend — last 30 days') }}</p>
                <div class="h-56"><canvas x-ref="ordersTrendChart"></canvas></div>
            </div>
        </div>

        <div class="grid lg:grid-cols-[1fr_320px] gap-5 mt-5">
            <div class="bg-white rounded-2xl border border-gold-200/60 p-5">
                <p class="font-display text-maroon-800 mb-4">{{ __('Monthly Income — last 12 months') }}</p>
                <div class="h-56"><canvas x-ref="monthlyChart"></canvas></div>
            </div>
            <div class="bg-white rounded-2xl border border-gold-200/60 p-5">
                <p class="font-display text-maroon-800 mb-4">{{ __('Fixed vs Delivery Charge Income') }}</p>
                <div class="h-56"><canvas x-ref="breakdownChart"></canvas></div>
            </div>
        </div>
    </div>

    {{-- monthly summary table --}}
    <div class="bg-white rounded-2xl border border-gold-200/60 mt-6 overflow-hidden">
        <div class="px-5 py-4 border-b border-gold-100 flex items-center justify-between">
            <p class="font-display text-maroon-800">{{ __('Monthly Summary') }} <span class="text-maroon-400 text-sm font-sans">({{ __('last 12 months') }})</span></p>
            <a href="{{ route('admin.income.history') }}" class="text-sm text-gold-600 hover:text-gold-700 font-medium">{{ __('Full history') }} →</a>
        </div>

        @if ($monthly->isEmpty())
            <p class="text-maroon-400 text-sm px-5 py-8 text-center">{{ __('No delivered orders have generated income yet.') }}</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-maroon-400 border-b border-gold-100">
                            <th class="px-5 py-2.5 font-medium">{{ __('Month') }}</th>
                            <th class="px-5 py-2.5 font-medium text-right">{{ __('Orders') }}</th>
                            <th class="px-5 py-2.5 font-medium text-right">{{ __('₹15 Income') }}</th>
                            <th class="px-5 py-2.5 font-medium text-right">{{ __('Delivery Income') }}</th>
                            <th class="px-5 py-2.5 font-medium text-right">{{ __('Total') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($monthly as $row)
                            <tr class="border-b border-gold-50 last:border-0 hover:bg-cream/50 transition cursor-pointer"
                                onclick="window.location='{{ route('admin.income.index', ['year' => $row->year, 'month' => $row->month]) }}'">
                                <td class="px-5 py-3 text-maroon-800 font-medium">{{ \Illuminate\Support\Carbon::createFromDate($row->year, $row->month, 1)->translatedFormat('F Y') }}</td>
                                <td class="px-5 py-3 text-maroon-600 text-right">{{ number_format($row->orders) }}</td>
                                <td class="px-5 py-3 text-maroon-600 text-right">₹{{ number_format($row->fixed_total) }}</td>
                                <td class="px-5 py-3 text-maroon-600 text-right">₹{{ number_format($row->delivery_total) }}</td>
                                <td class="px-5 py-3 text-maroon-800 font-semibold text-right">₹{{ number_format($row->total) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- income breakdown --}}
    <div class="grid lg:grid-cols-3 gap-5 mt-6">
        <div class="bg-white rounded-2xl border border-gold-200/60 p-5 animate-fade-up">
            <p class="font-display text-maroon-800 mb-3">🎯 {{ __('Fixed Commission') }}</p>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-maroon-500">{{ __('Total orders eligible') }}</dt><dd class="text-maroon-800 font-medium">{{ number_format($cards['total_orders']) }}</dd></div>
                <div class="flex justify-between"><dt class="text-maroon-500">{{ __('Earned per order') }}</dt><dd class="text-maroon-800 font-medium">₹15</dd></div>
                <div class="flex justify-between pt-2 border-t border-gold-100"><dt class="text-maroon-700 font-semibold">{{ __('Total fixed commission') }}</dt><dd class="text-maroon-800 font-bold">₹{{ number_format($cards['fixed_commission_total']) }}</dd></div>
            </dl>
        </div>
        <div class="bg-white rounded-2xl border border-gold-200/60 p-5 animate-fade-up">
            <p class="font-display text-maroon-800 mb-3">🚚 {{ __('Delivery Charge Income') }}</p>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-maroon-500">{{ __('Total delivery charges collected') }}</dt><dd class="text-maroon-800 font-medium">₹{{ number_format($cards['delivery_charge_collected_total']) }}</dd></div>
                <div class="flex justify-between"><dt class="text-maroon-500">{{ __('Platform share') }}</dt><dd class="text-maroon-800 font-medium">50%</dd></div>
                <div class="flex justify-between pt-2 border-t border-gold-100"><dt class="text-maroon-700 font-semibold">{{ __('Total delivery charge income') }}</dt><dd class="text-maroon-800 font-bold">₹{{ number_format($cards['delivery_income_total']) }}</dd></div>
            </dl>
        </div>
        <div class="rounded-2xl bg-gradient-to-br from-gold-500 to-gold-600 p-5 animate-fade-up shadow-md">
            <p class="font-display font-bold text-maroon-900 mb-3">🏆 {{ __('Grand Total') }}</p>
            <p class="text-xs text-maroon-800/70">{{ __('Fixed Commission') }} + {{ __('Delivery Charge Income') }}</p>
            <p class="font-display font-extrabold text-3xl text-maroon-900 mt-2">₹{{ number_format($cards['fixed_commission_total'] + $cards['delivery_income_total']) }}</p>
        </div>
    </div>

    {{-- order-wise income details --}}
    <div class="bg-white rounded-2xl border border-gold-200/60 mt-6 overflow-hidden">
        <div class="px-5 py-4 border-b border-gold-100">
            <div class="flex items-center justify-between gap-3 flex-wrap">
                <p class="font-display text-maroon-800">{{ __('Order-wise Income Details') }}</p>
                <div class="flex items-center gap-2">
                    <a href="{{ route('admin.income.export.csv', request()->query()) }}" class="text-xs font-semibold text-maroon-600 hover:text-maroon-800 border border-gold-300/70 hover:border-gold-400 rounded-lg px-3 py-1.5 transition">⬇️ {{ __('CSV') }}</a>
                    <a href="{{ route('admin.income.export.pdf', request()->query()) }}" class="text-xs font-semibold text-maroon-600 hover:text-maroon-800 border border-gold-300/70 hover:border-gold-400 rounded-lg px-3 py-1.5 transition">📄 {{ __('PDF') }}</a>
                </div>
            </div>

            <form method="GET" class="flex flex-wrap items-center gap-2.5 mt-3.5">
                @if ($isHistoricalView)
                    <input type="hidden" name="year" value="{{ $viewingYear }}">
                    <input type="hidden" name="month" value="{{ $viewingMonth }}">
                @endif
                <input type="text" name="q" value="{{ $search }}" placeholder="{{ __('Search order #, customer, rider…') }}"
                       class="rounded-lg border border-gold-200/80 px-3 py-2 text-sm text-maroon-800 focus:outline-none focus:ring-2 focus:ring-gold-400 w-full sm:w-64">
                <input type="date" name="from" value="{{ $from }}" class="rounded-lg border border-gold-200/80 px-3 py-2 text-sm text-maroon-800 focus:outline-none focus:ring-2 focus:ring-gold-400">
                <span class="text-maroon-400 text-sm">{{ __('to') }}</span>
                <input type="date" name="to" value="{{ $to }}" class="rounded-lg border border-gold-200/80 px-3 py-2 text-sm text-maroon-800 focus:outline-none focus:ring-2 focus:ring-gold-400">
                <button type="submit" class="bg-maroon-700 hover:bg-maroon-800 text-cream text-sm font-semibold rounded-lg px-4 py-2 transition">🔍 {{ __('Filter') }}</button>
                @if ($search !== '' || $from || $to)
                    <a href="{{ $isHistoricalView ? route('admin.income.index', ['year' => $viewingYear, 'month' => $viewingMonth]) : route('admin.income.index') }}" class="text-sm text-maroon-400 hover:text-maroon-600">{{ __('Clear') }}</a>
                @endif
            </form>
        </div>

        @if ($incomeOrders->isEmpty())
            <p class="text-maroon-400 text-sm px-5 py-8 text-center">{{ __('No income records match.') }}</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-maroon-400 border-b border-gold-100">
                            <th class="px-5 py-2.5 font-medium">{{ __('Order ID') }}</th>
                            <th class="px-5 py-2.5 font-medium">{{ __('Customer') }}</th>
                            <th class="px-5 py-2.5 font-medium">{{ __('Delivery Partner') }}</th>
                            <th class="px-5 py-2.5 font-medium text-right">{{ __('Order Amount') }}</th>
                            <th class="px-5 py-2.5 font-medium text-right">{{ __('Delivery Charge') }}</th>
                            <th class="px-5 py-2.5 font-medium text-right">{{ __('₹15 Income') }}</th>
                            <th class="px-5 py-2.5 font-medium text-right">{{ __('Delivery Income') }}</th>
                            <th class="px-5 py-2.5 font-medium text-right">{{ __('Total Income') }}</th>
                            <th class="px-5 py-2.5 font-medium">{{ __('Delivered') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($incomeOrders as $record)
                            <tr class="border-b border-gold-50 last:border-0 hover:bg-cream/50 transition">
                                <td class="px-5 py-3">
                                    @if ($record->order)
                                        <a href="{{ route('admin.orders.show', $record->order_id) }}" class="text-maroon-800 font-medium hover:text-gold-600">{{ $record->order->orderNumber() }}</a>
                                    @else
                                        <span class="text-maroon-400">#{{ $record->order_id }}</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-maroon-600">{{ $record->customer_name }}</td>
                                <td class="px-5 py-3 text-maroon-600">{{ $record->rider->name ?? '—' }}</td>
                                <td class="px-5 py-3 text-maroon-600 text-right">₹{{ number_format($record->order_amount) }}</td>
                                <td class="px-5 py-3 text-maroon-600 text-right">₹{{ number_format($record->delivery_charge) }}</td>
                                <td class="px-5 py-3 text-pista-600 text-right">₹{{ number_format($record->fixed_commission) }}</td>
                                <td class="px-5 py-3 text-pista-600 text-right">₹{{ number_format($record->delivery_charge_income) }}</td>
                                <td class="px-5 py-3 text-maroon-800 font-semibold text-right">₹{{ number_format($record->total_income) }}</td>
                                <td class="px-5 py-3 text-maroon-400">{{ $record->delivered_at->format('d M Y, h:i A') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-5 py-3.5 border-t border-gold-100">
                <x-admin.pagination :paginator="$incomeOrders" />
            </div>
        @endif
    </div>
@endsection
