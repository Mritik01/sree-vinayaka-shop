@extends('admin.layout')

@section('title', $coupon->code)
@section('page-title', 'Coupon Details')

@section('content')
    <a href="{{ route('admin.coupons.index') }}" class="text-sm text-maroon-500 hover:text-maroon-700 transition">← Back to Coupons</a>

    <div class="grid lg:grid-cols-[1fr_1fr] gap-6 items-start mt-4">
        {{-- header card --}}
        <div class="lg:col-span-2 bg-white rounded-2xl border border-gold-200/60 p-6 animate-fade-up">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="font-display text-2xl text-maroon-800 tracking-wide">{{ $coupon->code }}</p>
                    <p class="text-maroon-500 text-sm mt-1">{{ $coupon->description ?: 'No description' }}</p>
                </div>
                <div class="flex items-center gap-2">
                    @if ($coupon->isExpired())
                        <span class="inline-block text-xs font-semibold px-2.5 py-1 rounded-full border bg-red-50 text-red-600 border-red-200">Expired</span>
                    @elseif ($coupon->is_active)
                        <span class="inline-block text-xs font-semibold px-2.5 py-1 rounded-full border bg-pista-100 text-pista-600 border-pista-400/40">Active</span>
                    @else
                        <span class="inline-block text-xs font-semibold px-2.5 py-1 rounded-full border bg-gold-100 text-gold-600 border-gold-300/60">Inactive</span>
                    @endif
                    <a href="{{ route('admin.coupons.edit', $coupon) }}" class="text-sm font-semibold px-4 py-2 rounded-lg bg-maroon-700 text-cream hover:bg-maroon-800 transition">Edit</a>
                </div>
            </div>

            <div class="grid sm:grid-cols-4 gap-4 mt-5 pt-5 border-t border-gold-100">
                <div>
                    <p class="text-xs text-maroon-400 uppercase tracking-wide">Discount</p>
                    <p class="text-maroon-800 font-semibold mt-0.5">{{ $coupon->discount_type === 'percent' ? $coupon->discount_value.'%' : '₹'.$coupon->discount_value }}</p>
                </div>
                <div>
                    <p class="text-xs text-maroon-400 uppercase tracking-wide">Usage Type</p>
                    <p class="text-maroon-800 font-semibold mt-0.5">{{ $coupon->usage_type === 'single_use' ? 'Single use (once, ever)' : 'Once per user' }}</p>
                </div>
                <div>
                    <p class="text-xs text-maroon-400 uppercase tracking-wide">Expires</p>
                    <p class="text-maroon-800 font-semibold mt-0.5">{{ $coupon->expires_at->format('d M Y') }}</p>
                </div>
                <div>
                    <p class="text-xs text-maroon-400 uppercase tracking-wide">Eligibility</p>
                    <p class="text-maroon-800 font-semibold mt-0.5">
                        @if ($coupon->isMasterCoupon())
                            🚀 Master ({{ $coupon->assignedUsers->count() }}/{{ $coupon->auto_assign_limit }} claimed)
                        @else
                            {{ $coupon->isRestricted() ? 'Restricted' : 'All customers' }}
                        @endif
                    </p>
                </div>
            </div>
        </div>

        {{-- redeemed --}}
        <div class="bg-white rounded-2xl border border-gold-200/60 p-5 animate-fade-up" style="animation-delay: 60ms">
            <p class="font-display text-maroon-800 mb-3">✅ Redeemed <span class="text-maroon-400 font-sans text-sm">({{ $redeemed->count() }})</span></p>

            @if ($redeemed->isEmpty())
                <p class="text-maroon-400 text-sm">No one has redeemed this coupon yet.</p>
            @else
                <ul class="space-y-2.5 max-h-[26rem] overflow-y-auto pr-1">
                    @foreach ($redeemed as $user)
                        <li class="flex items-center justify-between gap-3 bg-cream/60 border border-gold-100 rounded-lg px-3.5 py-2.5">
                            <a href="{{ route('admin.customers.show', $user) }}" class="min-w-0">
                                <p class="text-sm font-bold text-maroon-800 truncate hover:text-gold-600 transition">{{ $user->name }}</p>
                                <p class="text-xs text-maroon-500">{{ $user->phone }}</p>
                            </a>
                            <div class="text-right shrink-0">
                                <p class="text-sm font-semibold text-pista-600">-₹{{ $user->pivot->discount_amount }}</p>
                                <p class="text-xs text-maroon-400">{{ \Illuminate\Support\Carbon::parse($user->pivot->redeemed_at)->format('d M Y, h:i A') }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        {{-- not yet redeemed --}}
        <div class="bg-white rounded-2xl border border-gold-200/60 p-5 animate-fade-up" style="animation-delay: 100ms">
            <p class="font-display text-maroon-800 mb-3">🕐 Not Redeemed Yet <span class="text-maroon-400 font-sans text-sm">({{ $notRedeemed->count() }})</span></p>
            <p class="text-xs text-maroon-400 mb-3">
                {{ $coupon->isRestricted() ? 'Customers this coupon is assigned to, who haven\'t used it yet.' : 'Every customer is eligible — these haven\'t redeemed it yet.' }}
            </p>

            @if ($notRedeemed->isEmpty())
                <p class="text-maroon-400 text-sm">{{ $coupon->isRestricted() ? 'Every assigned customer has already redeemed this.' : 'Every customer has already redeemed this.' }}</p>
            @else
                <ul class="space-y-2.5 max-h-[26rem] overflow-y-auto pr-1">
                    @foreach ($notRedeemed as $user)
                        <li class="flex items-center justify-between gap-3 bg-cream/60 border border-gold-100 rounded-lg px-3.5 py-2.5">
                            <a href="{{ route('admin.customers.show', $user) }}" class="min-w-0">
                                <p class="text-sm font-bold text-maroon-800 truncate hover:text-gold-600 transition">{{ $user->name }}</p>
                                <p class="text-xs text-maroon-500">{{ $user->phone }}</p>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
@endsection
