@extends('admin.layout')

@section('title', 'Transactions')
@section('page-title', __('Transactions'))

@section('content')
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-5">
        <div class="bg-white rounded-2xl border border-gold-200/60 shadow-sm p-4">
            <p class="text-xs text-maroon-400 uppercase tracking-wide font-semibold">{{ __('Total Transactions') }}</p>
            <p class="font-display font-bold text-2xl text-maroon-800 mt-1">{{ number_format($summary['total_count']) }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-gold-200/60 shadow-sm p-4">
            <p class="text-xs text-maroon-400 uppercase tracking-wide font-semibold">💵 {{ __('COD Collected') }}</p>
            <p class="font-display font-bold text-2xl text-gold-600 mt-1">₹{{ number_format($summary['cod_amount']) }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-gold-200/60 shadow-sm p-4">
            <p class="text-xs text-maroon-400 uppercase tracking-wide font-semibold">✅ {{ __('Paid Online') }}</p>
            <p class="font-display font-bold text-2xl text-pista-600 mt-1">₹{{ number_format($summary['online_paid_amount']) }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-gold-200/60 shadow-sm p-4">
            <p class="text-xs text-maroon-400 uppercase tracking-wide font-semibold">⚠️ {{ __('Failed Payments') }}</p>
            <p class="font-display font-bold text-2xl text-red-600 mt-1">{{ number_format($summary['failed_count']) }}</p>
        </div>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div class="flex items-center gap-2 flex-wrap">
            @php $methods = ['' => __('All Methods'), 'cod' => __('COD'), 'razorpay' => __('Online')]; @endphp
            @foreach ($methods as $value => $label)
                <a href="{{ route('admin.transactions.index', array_filter(array_merge(request()->query(), ['method' => $value, 'page' => null]))) }}"
                   class="text-sm px-3.5 py-1.5 rounded-full border transition {{ (string) $methodFilter === $value ? 'bg-maroon-700 text-cream border-maroon-700' : 'bg-white text-maroon-600 border-gold-200/60 hover:border-gold-400' }}">
                    {{ $label }}
                </a>
            @endforeach

            <span class="w-px h-5 bg-gold-200/70 mx-1"></span>

            @php $statuses = ['' => __('All Statuses'), 'paid' => __('Paid'), 'pending' => __('Pending'), 'failed' => __('Failed')]; @endphp
            @foreach ($statuses as $value => $label)
                <a href="{{ route('admin.transactions.index', array_filter(array_merge(request()->query(), ['payment_status' => $value, 'page' => null]))) }}"
                   class="text-sm px-3.5 py-1.5 rounded-full border transition {{ (string) $statusFilter === $value ? 'bg-maroon-700 text-cream border-maroon-700' : 'bg-white text-maroon-600 border-gold-200/60 hover:border-gold-400' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        <div class="flex items-center gap-3 flex-wrap">
            <x-admin.per-page-select :current="request('per_page', 10)" />
            <x-admin.search-box :value="$search" target="transactions-results" placeholder="Search order #, name, phone, razorpay id…" />
        </div>
    </div>

    <div id="transactions-results" class="bg-white rounded-xl border border-gold-200/60 overflow-hidden">
        @include('admin.transactions._results')
    </div>
@endsection
