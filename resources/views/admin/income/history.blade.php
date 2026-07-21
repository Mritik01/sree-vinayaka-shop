@extends('admin.layout')

@section('title', 'Income History')
@section('page-title', __('Income History'))

@section('content')
    <div class="flex items-center justify-between gap-3 flex-wrap">
        <p class="text-sm text-maroon-500">{{ __('Every month ever recorded, permanently — nothing here can be deleted by a dashboard reset.') }}</p>
        <a href="{{ route('admin.income.index') }}" class="text-sm font-semibold text-gold-600 hover:text-gold-700">← {{ __('Back to Income Dashboard') }}</a>
    </div>

    {{-- year-wise summary --}}
    <div class="flex flex-wrap gap-3 mt-5">
        <a href="{{ route('admin.income.history') }}"
           class="rounded-xl border-2 px-4 py-2.5 text-sm font-semibold transition {{ !$yearFilter ? 'bg-maroon-800 text-cream border-maroon-800' : 'bg-white text-maroon-700 border-gold-200/80 hover:border-gold-400' }}">
            {{ __('All Years') }}
        </a>
        @foreach ($yearly as $year)
            <a href="{{ route('admin.income.history', ['year' => $year->year]) }}"
               class="rounded-xl border-2 px-4 py-2.5 text-sm font-semibold transition {{ $yearFilter === $year->year ? 'bg-maroon-800 text-cream border-maroon-800' : 'bg-white text-maroon-700 border-gold-200/80 hover:border-gold-400' }}">
                {{ $year->year }} <span class="opacity-70">· ₹{{ number_format($year->total) }}</span>
            </a>
        @endforeach
    </div>

    @if ($yearly->isEmpty())
        <p class="text-maroon-400 text-sm px-1 py-10 text-center">{{ __('No delivered orders have generated income yet.') }}</p>
    @else
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-5">
            @foreach ($yearly as $year)
                <div class="bg-white rounded-2xl border border-gold-200/60 p-5">
                    <p class="font-display text-lg text-maroon-800">{{ $year->year }}</p>
                    <p class="text-xs text-maroon-400 mt-1">{{ number_format($year->orders) }} {{ __('delivered orders') }}</p>
                    <div class="mt-3 pt-3 border-t border-gold-100 space-y-1 text-xs">
                        <div class="flex justify-between"><span class="text-maroon-500">{{ __('₹15 Income') }}</span><span class="text-maroon-700 font-medium">₹{{ number_format($year->fixed_total) }}</span></div>
                        <div class="flex justify-between"><span class="text-maroon-500">{{ __('Delivery Income') }}</span><span class="text-maroon-700 font-medium">₹{{ number_format($year->delivery_total) }}</span></div>
                        <div class="flex justify-between pt-1 border-t border-gold-100"><span class="text-maroon-700 font-semibold">{{ __('Total') }}</span><span class="text-maroon-800 font-bold">₹{{ number_format($year->total) }}</span></div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- month-wise, optionally filtered to one year --}}
        <div class="bg-white rounded-2xl border border-gold-200/60 mt-6 overflow-hidden">
            <div class="px-5 py-4 border-b border-gold-100">
                <p class="font-display text-maroon-800">{{ $yearFilter ? __('Months in') . ' ' . $yearFilter : __('All Months') }}</p>
            </div>

            @if ($monthly->isEmpty())
                <p class="text-maroon-400 text-sm px-5 py-8 text-center">{{ __('Nothing recorded for this year.') }}</p>
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
                                <th class="px-5 py-2.5 font-medium"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($monthly as $row)
                                <tr class="border-b border-gold-50 last:border-0 hover:bg-cream/50 transition">
                                    <td class="px-5 py-3 text-maroon-800 font-medium">{{ \Illuminate\Support\Carbon::createFromDate($row->year, $row->month, 1)->translatedFormat('F Y') }}</td>
                                    <td class="px-5 py-3 text-maroon-600 text-right">{{ number_format($row->orders) }}</td>
                                    <td class="px-5 py-3 text-maroon-600 text-right">₹{{ number_format($row->fixed_total) }}</td>
                                    <td class="px-5 py-3 text-maroon-600 text-right">₹{{ number_format($row->delivery_total) }}</td>
                                    <td class="px-5 py-3 text-maroon-800 font-semibold text-right">₹{{ number_format($row->total) }}</td>
                                    <td class="px-5 py-3 text-right">
                                        <a href="{{ route('admin.income.index', ['year' => $row->year, 'month' => $row->month]) }}" class="text-xs font-semibold text-gold-600 hover:text-gold-700">{{ __('View orders') }} →</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endif
@endsection
