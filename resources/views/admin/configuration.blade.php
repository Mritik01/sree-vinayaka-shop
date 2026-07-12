@extends('admin.layout')

@section('title', 'Configuration')
@section('page-title', 'Configuration')

@section('content')
    <div class="grid lg:grid-cols-2 gap-4 mb-5">
        {{-- accepting-orders toggle --}}
        <div x-data="settingToggle({{ $acceptingOrders ? 'true' : 'false' }}, '/admin/settings/accepting-orders')"
             class="rounded-2xl border p-5 flex items-center justify-between gap-4 transition-colors duration-300"
             :class="on ? 'bg-white border-gold-200/60' : 'bg-red-50 border-red-200'">
            <div>
                <p class="font-display text-maroon-800 flex items-center gap-2">
                    <span x-show="on" x-cloak>🟢 Accepting Orders</span>
                    <span x-show="!on" x-cloak>🔴 Orders Paused</span>
                </p>
                <p class="text-sm mt-1" :class="on ? 'text-maroon-500' : 'text-red-600'">
                    <span x-show="on" x-cloak>Customers can place new orders normally.</span>
                    <span x-show="!on" x-cloak>Customers will see a notice and can't check out until you turn this back on.</span>
                </p>
            </div>

            <label class="relative inline-flex items-center cursor-pointer shrink-0" :class="updating && 'opacity-60 pointer-events-none'">
                <input type="checkbox" class="sr-only peer" :checked="on" @change="toggle()">
                <div class="w-16 h-9 rounded-full transition-colors duration-300 bg-red-300 peer-checked:bg-pista-500"></div>
                <div class="absolute left-1 top-1 w-7 h-7 bg-white rounded-full shadow-md transition-transform duration-300 peer-checked:translate-x-7 flex items-center justify-center text-xs"
                     x-text="on ? '✓' : '✕'"></div>
            </label>
        </div>

        {{-- delivery-area toggle --}}
        <div x-data="settingToggle({{ $restrictDeliveryArea ? 'true' : 'false' }}, '/admin/settings/delivery-area')"
             class="rounded-2xl border p-5 flex items-center justify-between gap-4 transition-colors duration-300"
             :class="on ? 'bg-white border-gold-200/60' : 'bg-gold-50 border-gold-300/60'">
            <div>
                <p class="font-display text-maroon-800 flex items-center gap-2">
                    <span x-show="on" x-cloak>📍 Thuthibari Delivery Only</span>
                    <span x-show="!on" x-cloak>🌍 Delivering Anywhere</span>
                </p>
                <p class="text-sm mt-1 text-maroon-500">
                    <span x-show="on" x-cloak>Orders accepted only within {{ rtrim(rtrim(number_format($deliveryRadiusKm, 1), '0'), '.') }} km of Thuthibari — checked via the customer's live location.</span>
                    <span x-show="!on" x-cloak>No distance limit — orders are accepted from any location (testing mode).</span>
                </p>
            </div>

            <label class="relative inline-flex items-center cursor-pointer shrink-0" :class="updating && 'opacity-60 pointer-events-none'">
                <input type="checkbox" class="sr-only peer" :checked="on" @change="toggle()">
                <div class="w-16 h-9 rounded-full transition-colors duration-300 bg-gold-300 peer-checked:bg-pista-500"></div>
                <div class="absolute left-1 top-1 w-7 h-7 bg-white rounded-full shadow-md transition-transform duration-300 peer-checked:translate-x-7 flex items-center justify-center text-xs"
                     x-text="on ? '📍' : '🌍'"></div>
            </label>
        </div>
    </div>

    {{-- loyalty reward program --}}
    <div class="rounded-2xl border border-pink-200 bg-gradient-to-br from-pink-50 via-white to-gold-50 p-5">
        <div class="flex items-center justify-between gap-4 flex-wrap">
            <div class="flex items-center gap-3">
                <span class="text-2xl">🎁</span>
                <div>
                    <p class="font-display text-maroon-800">Loyalty Reward Program</p>
                    <p class="text-sm text-maroon-500 mt-0.5">Customers earn a free gift after completing a set number of delivered orders.</p>
                </div>
            </div>
            <div x-data="settingToggle({{ $rewardSettings->reward_enabled ? 'true' : 'false' }}, '{{ route('admin.settings.reward-enabled') }}')">
                <label class="relative inline-flex items-center cursor-pointer shrink-0" :class="updating && 'opacity-60 pointer-events-none'">
                    <input type="checkbox" class="sr-only peer" :checked="on" @change="toggle()">
                    <div class="w-16 h-9 rounded-full transition-colors duration-300 bg-maroon-200 peer-checked:bg-pink-500"></div>
                    <div class="absolute left-1 top-1 w-7 h-7 bg-white rounded-full shadow-md transition-transform duration-300 peer-checked:translate-x-7 flex items-center justify-center text-xs"
                         x-text="on ? '🎁' : '✕'"></div>
                </label>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.settings.rewards') }}" class="grid sm:grid-cols-3 gap-3 mt-4">
            @csrf
            @method('PATCH')
            <div>
                <label class="block text-xs font-semibold text-maroon-500 uppercase tracking-wide mb-1.5">Orders to unlock</label>
                <input type="number" name="reward_orders_required" min="1" max="100" required
                       value="{{ old('reward_orders_required', $rewardSettings->reward_orders_required) }}"
                       class="w-full rounded-lg border border-gold-300/70 px-3 py-2 text-sm text-maroon-800 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
            </div>
            <div>
                <label class="block text-xs font-semibold text-maroon-500 uppercase tracking-wide mb-1.5">Gift product</label>
                <select name="reward_gift_product_id" class="w-full rounded-lg border border-gold-300/70 px-3 py-2 text-sm text-maroon-800 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
                    <option value="">— Choose a product —</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}" @selected(old('reward_gift_product_id', $rewardSettings->reward_gift_product_id) == $product->id)>{{ $product->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-maroon-500 uppercase tracking-wide mb-1.5">Display name <span class="normal-case font-normal text-maroon-400">(optional)</span></label>
                <input type="text" name="reward_gift_label" maxlength="100" placeholder="Defaults to product name"
                       value="{{ old('reward_gift_label', $rewardSettings->reward_gift_label) }}"
                       class="w-full rounded-lg border border-gold-300/70 px-3 py-2 text-sm text-maroon-800 placeholder-maroon-300 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
            </div>
            <div class="sm:col-span-3 flex items-center justify-between gap-3">
                @if (!$rewardSettings->rewardConfigured())
                    <p class="text-xs text-gold-700">⚠️ Pick a gift product and make sure the toggle above is on for this to go live.</p>
                @else
                    <p class="text-xs text-maroon-400">Currently live: every {{ $rewardSettings->reward_orders_required }} delivered orders earns a free {{ $rewardSettings->reward_gift_label ?: $rewardSettings->rewardGiftProduct->name }}.</p>
                @endif
                <button type="submit" class="btn-gold text-sm px-5 py-2 shrink-0">Save</button>
            </div>
        </form>
    </div>

    {{-- order limits --}}
    <div class="rounded-2xl border border-sky-200 bg-gradient-to-br from-sky-50 via-white to-cream p-5 mt-5">
        <div class="flex items-center gap-3">
            <span class="text-2xl">🧾</span>
            <div>
                <p class="font-display text-maroon-800">Order Limits</p>
                <p class="text-sm text-maroon-500 mt-0.5">Set the minimum and maximum order value a customer can place at checkout. Leave a field blank for no limit.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.settings.order-limits') }}" class="grid sm:grid-cols-2 gap-3 mt-4">
            @csrf
            @method('PATCH')
            <div>
                <label class="block text-xs font-semibold text-maroon-500 uppercase tracking-wide mb-1.5">Minimum order (₹)</label>
                <input type="number" name="min_order_amount" min="0" placeholder="No minimum"
                       value="{{ old('min_order_amount', $rewardSettings->min_order_amount) }}"
                       class="w-full rounded-lg border border-gold-300/70 px-3 py-2 text-sm text-maroon-800 placeholder-maroon-300 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
                @error('min_order_amount')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-xs font-semibold text-maroon-500 uppercase tracking-wide mb-1.5">Maximum order (₹)</label>
                <input type="number" name="max_order_amount" min="0" placeholder="No maximum"
                       value="{{ old('max_order_amount', $rewardSettings->max_order_amount) }}"
                       class="w-full rounded-lg border border-gold-300/70 px-3 py-2 text-sm text-maroon-800 placeholder-maroon-300 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
                @error('max_order_amount')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div class="sm:col-span-2 flex items-center justify-between gap-3">
                @if ($rewardSettings->min_order_amount || $rewardSettings->max_order_amount)
                    <p class="text-xs text-maroon-400">
                        Currently: {{ $rewardSettings->min_order_amount ? '₹'.number_format($rewardSettings->min_order_amount).' minimum' : 'no minimum' }},
                        {{ $rewardSettings->max_order_amount ? '₹'.number_format($rewardSettings->max_order_amount).' maximum' : 'no maximum' }}.
                    </p>
                @else
                    <p class="text-xs text-maroon-400">No order limits set — customers can place an order of any value.</p>
                @endif
                <button type="submit" class="btn-gold text-sm px-5 py-2 shrink-0">Save</button>
            </div>
        </form>
    </div>
@endsection
