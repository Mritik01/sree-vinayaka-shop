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
                    <span x-show="on" x-cloak>📍 Siswa Bazar Delivery Only</span>
                    <span x-show="!on" x-cloak>🌍 Delivering Anywhere</span>
                </p>
                <p class="text-sm mt-1 text-maroon-500">
                    <span x-show="on" x-cloak>Orders accepted only within {{ rtrim(rtrim(number_format($deliveryRadiusKm, 1), '0'), '.') }} km of Siswa Bazar — checked via the customer's live location.</span>
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
        {{-- shadi/function popup toggle --}}
        <div x-data="settingToggle({{ $promoPopupEnabled ? 'true' : 'false' }}, '{{ route('admin.settings.promo-popup') }}')"
             class="rounded-2xl border p-5 flex items-center justify-between gap-4 transition-colors duration-300"
             :class="on ? 'bg-white border-gold-200/60' : 'bg-gold-50 border-gold-300/60'">
            <div>
                <p class="font-display text-maroon-800 flex items-center gap-2">
                    <span class="font-hindi">शादी हो या फंक्शन?</span> Popup
                </p>
                <p class="text-sm mt-1 text-maroon-500">
                    <span x-show="on" x-cloak>Shown to first-time visitors — captures their name and OTP-verified phone number as a lead.</span>
                    <span x-show="!on" x-cloak>Turned off — visitors won't see this popup.</span>
                </p>
            </div>

            <label class="relative inline-flex items-center cursor-pointer shrink-0" :class="updating && 'opacity-60 pointer-events-none'">
                <input type="checkbox" class="sr-only peer" :checked="on" @change="toggle()">
                <div class="w-16 h-9 rounded-full transition-colors duration-300 bg-gold-300 peer-checked:bg-pista-500"></div>
                <div class="absolute left-1 top-1 w-7 h-7 bg-white rounded-full shadow-md transition-transform duration-300 peer-checked:translate-x-7 flex items-center justify-center text-xs"
                     x-text="on ? '✓' : '✕'"></div>
            </label>
        </div>

        {{-- circular category row (above the hero banner) show/hide toggle --}}
        <div x-data="settingToggle({{ $settings->show_category_row ? 'true' : 'false' }}, '{{ route('admin.settings.category-row') }}')"
             class="rounded-2xl border p-5 flex items-center justify-between gap-4 transition-colors duration-300"
             :class="on ? 'bg-white border-gold-200/60' : 'bg-gold-50 border-gold-300/60'">
            <div>
                <p class="font-display text-maroon-800 flex items-center gap-2">🗂️ Category Row</p>
                <p class="text-sm mt-1 text-maroon-500">
                    <span x-show="on" x-cloak>The circular category icons shown above the hero banner on the homepage.</span>
                    <span x-show="!on" x-cloak>Hidden — the homepage goes straight from the header to the hero banner.</span>
                </p>
            </div>

            <label class="relative inline-flex items-center cursor-pointer shrink-0" :class="updating && 'opacity-60 pointer-events-none'">
                <input type="checkbox" class="sr-only peer" :checked="on" @change="toggle()">
                <div class="w-16 h-9 rounded-full transition-colors duration-300 bg-gold-300 peer-checked:bg-pista-500"></div>
                <div class="absolute left-1 top-1 w-7 h-7 bg-white rounded-full shadow-md transition-transform duration-300 peer-checked:translate-x-7 flex items-center justify-center text-xs"
                     x-text="on ? '✓' : '✕'"></div>
            </label>
        </div>
    </div>

    {{-- payment methods — at least one must always stay enabled, enforced server-side --}}
    <div x-data="paymentMethodsToggle({{ $settings->cod_enabled ? 'true' : 'false' }}, {{ $settings->razorpay_enabled ? 'true' : 'false' }}, '{{ route('admin.settings.cod-enabled') }}', '{{ route('admin.settings.razorpay-enabled') }}')"
         class="rounded-2xl border border-gold-200/60 bg-white p-5 mb-5">
        <p class="font-display text-maroon-800 flex items-center gap-2">💳 Payment Methods</p>
        <p class="text-sm text-maroon-500 mt-0.5 mb-4">Choose which payment options customers see at checkout. At least one must stay enabled.</p>

        <div class="grid sm:grid-cols-2 gap-3">
            <div class="rounded-xl border p-4 flex items-center justify-between gap-3 transition-colors duration-300"
                 :class="cod ? 'bg-white border-gold-200/60' : 'bg-gold-50 border-gold-300/60'">
                <p class="text-sm font-semibold text-maroon-800">💵 Cash on Delivery</p>
                <label class="relative inline-flex items-center cursor-pointer shrink-0" :class="updating && 'opacity-60 pointer-events-none'">
                    <input type="checkbox" class="sr-only peer" :checked="cod" @change="toggleCod()">
                    <div class="w-11 h-6 rounded-full transition-colors duration-300 bg-gold-300 peer-checked:bg-pista-500"></div>
                    <div class="absolute left-1 top-1 w-4 h-4 bg-white rounded-full shadow-md transition-transform duration-300 peer-checked:translate-x-5"></div>
                </label>
            </div>
            <div class="rounded-xl border p-4 flex items-center justify-between gap-3 transition-colors duration-300"
                 :class="razorpay ? 'bg-white border-gold-200/60' : 'bg-gold-50 border-gold-300/60'">
                <p class="text-sm font-semibold text-maroon-800">💳 Pay Online (Razorpay)</p>
                <label class="relative inline-flex items-center cursor-pointer shrink-0" :class="updating && 'opacity-60 pointer-events-none'">
                    <input type="checkbox" class="sr-only peer" :checked="razorpay" @change="toggleRazorpay()">
                    <div class="w-11 h-6 rounded-full transition-colors duration-300 bg-gold-300 peer-checked:bg-pista-500"></div>
                    <div class="absolute left-1 top-1 w-4 h-4 bg-white rounded-full shadow-md transition-transform duration-300 peer-checked:translate-x-5"></div>
                </label>
            </div>
        </div>

        <p x-show="error" x-cloak x-text="error" class="text-xs text-red-600 font-medium mt-3"></p>
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
                    <div class="w-16 h-9 rounded-full transition-colors duration-300 bg-gray-300 peer-checked:bg-pink-500"></div>
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
                <p class="text-sm text-maroon-500 mt-0.5">Set the minimum and maximum order value a customer can place at checkout, and how many orders they can place per hour. Leave a value field blank for no limit.</p>
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
            <div class="sm:col-span-2">
                <label class="block text-xs font-semibold text-maroon-500 uppercase tracking-wide mb-1.5">Max orders per customer, per hour</label>
                <input type="number" name="max_orders_per_hour" min="1" max="100" required
                       value="{{ old('max_orders_per_hour', $rewardSettings->max_orders_per_hour) }}"
                       class="w-full sm:w-40 rounded-lg border border-gold-300/70 px-3 py-2 text-sm text-maroon-800 placeholder-maroon-300 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
                <p class="text-xs text-maroon-400 mt-1">Stops a customer from placing more than this many orders within a rolling 1-hour window — a simple guard against accidental double-orders or abuse.</p>
                @error('max_orders_per_hour')
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
                    <p class="text-xs text-maroon-400">No value limits set — customers can place an order of any value.</p>
                @endif
                <button type="submit" class="btn-gold text-sm px-5 py-2 shrink-0">Save</button>
            </div>
        </form>
    </div>

    {{-- delivery time estimate --}}
    <div class="rounded-2xl border border-gold-200 bg-gradient-to-br from-gold-50 via-white to-cream p-5 mt-5">
        <div class="flex items-center gap-3">
            <span class="text-2xl">⚡</span>
            <div>
                <p class="font-display text-maroon-800">Delivery Time Estimate</p>
                <p class="text-sm text-maroon-500 mt-0.5">Shown as the "⚡ N mins" badge in the site header. Leave blank or 0 to hide the badge.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.settings.delivery-time') }}" class="flex items-end gap-3 mt-4 flex-wrap">
            @csrf
            @method('PATCH')
            <div>
                <label class="block text-xs font-semibold text-maroon-500 uppercase tracking-wide mb-1.5">Estimated delivery time (minutes)</label>
                <input type="number" name="delivery_time_estimate_minutes" min="0" max="999" placeholder="Hidden"
                       value="{{ old('delivery_time_estimate_minutes', $settings->delivery_time_estimate_minutes) }}"
                       class="w-40 rounded-lg border border-gold-300/70 px-3 py-2 text-sm text-maroon-800 placeholder-maroon-300 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
                @error('delivery_time_estimate_minutes')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit" class="btn-gold text-sm px-5 py-2 shrink-0">Save</button>
        </form>
    </div>

    {{-- delivery fee --}}
    <div class="rounded-2xl border border-blue-200 bg-gradient-to-br from-blue-50 via-white to-cream p-5 mt-5">
        <div class="flex items-center gap-3">
            <span class="text-2xl">🚚</span>
            <div>
                <p class="font-display text-maroon-800">Delivery Fee</p>
                <p class="text-sm text-maroon-500 mt-0.5">Choose one strategy — either free delivery above a minimum order, or a flat fee on every order.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.settings.delivery-fee') }}" class="mt-4"
              x-data="{ strategy: {{ Illuminate\Support\Js::from(old('delivery_fee_strategy', $settings->delivery_fee_strategy)) }} }">
            @csrf
            @method('PATCH')

            <div class="grid sm:grid-cols-2 gap-2.5">
                <button type="button" @click="strategy = 'free_above_minimum'"
                        class="rounded-lg border px-4 py-3 text-sm font-medium text-left transition"
                        :class="strategy === 'free_above_minimum' ? 'border-blue-500 ring-2 ring-blue-200 bg-blue-50/60 text-maroon-800' : 'border-gold-200/60 text-maroon-500 hover:border-blue-300'">
                    Free Delivery Above Minimum Order
                </button>
                <button type="button" @click="strategy = 'fixed'"
                        class="rounded-lg border px-4 py-3 text-sm font-medium text-left transition"
                        :class="strategy === 'fixed' ? 'border-blue-500 ring-2 ring-blue-200 bg-blue-50/60 text-maroon-800' : 'border-gold-200/60 text-maroon-500 hover:border-blue-300'">
                    Fixed Delivery Fee
                </button>
            </div>
            <input type="hidden" name="delivery_fee_strategy" x-model="strategy">

            <div x-show="strategy === 'free_above_minimum'" x-cloak class="grid sm:grid-cols-2 gap-3 mt-3">
                <div>
                    <label class="block text-xs font-semibold text-maroon-500 uppercase tracking-wide mb-1.5">Minimum order for free delivery (₹)</label>
                    <input type="number" name="delivery_free_min_order" min="0" placeholder="e.g. 299"
                           value="{{ old('delivery_free_min_order', $settings->delivery_free_min_order) }}"
                           class="w-full rounded-lg border border-gold-300/70 px-3 py-2 text-sm text-maroon-800 placeholder-maroon-300 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-maroon-500 uppercase tracking-wide mb-1.5">Delivery fee below minimum (₹)</label>
                    <input type="number" name="delivery_fee_below_minimum" min="0" placeholder="e.g. 39"
                           value="{{ old('delivery_fee_below_minimum', $settings->delivery_fee_below_minimum) }}"
                           class="w-full rounded-lg border border-gold-300/70 px-3 py-2 text-sm text-maroon-800 placeholder-maroon-300 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
                </div>
            </div>

            <div x-show="strategy === 'fixed'" x-cloak class="mt-3 max-w-xs">
                <label class="block text-xs font-semibold text-maroon-500 uppercase tracking-wide mb-1.5">Delivery fee (₹)</label>
                <input type="number" name="delivery_fee_fixed" min="0" placeholder="e.g. 29"
                       value="{{ old('delivery_fee_fixed', $settings->delivery_fee_fixed) }}"
                       class="w-full rounded-lg border border-gold-300/70 px-3 py-2 text-sm text-maroon-800 placeholder-maroon-300 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
            </div>

            <div x-show="strategy === 'free_above_minimum'" x-cloak class="grid sm:grid-cols-2 gap-3 mt-3">
                <div>
                    <label class="block text-xs font-semibold text-maroon-500 uppercase tracking-wide mb-1.5">Success message</label>
                    <input type="text" name="delivery_success_message" maxlength="150"
                           value="{{ old('delivery_success_message', $settings->delivery_success_message) }}"
                           class="w-full rounded-lg border border-gold-300/70 px-3 py-2 text-sm text-maroon-800 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-maroon-500 uppercase tracking-wide mb-1.5">Success animation</label>
                    <select name="delivery_success_animation" class="w-full rounded-lg border border-gold-300/70 px-3 py-2 text-sm text-maroon-800 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
                        <option value="confetti_truck" @selected(old('delivery_success_animation', $settings->delivery_success_animation) === 'confetti_truck')>🎉 Confetti + Truck (premium)</option>
                        <option value="minimal" @selected(old('delivery_success_animation', $settings->delivery_success_animation) === 'minimal')>Minimal banner only</option>
                    </select>
                </div>
            </div>

            <div class="flex justify-end mt-4">
                <button type="submit" class="btn-gold text-sm px-5 py-2">Save</button>
            </div>
        </form>
    </div>

    {{-- rain fee --}}
    <div class="rounded-2xl border border-sky-200 bg-gradient-to-br from-sky-50 via-white to-cream p-5 mt-5">
        <div class="flex items-center justify-between gap-4 flex-wrap">
            <div class="flex items-center gap-3">
                <span class="text-2xl">🌧️</span>
                <div>
                    <p class="font-display text-maroon-800">Rain Fee</p>
                    <p class="text-sm text-maroon-500 mt-0.5">Add a temporary surcharge during bad weather, shown to customers as a banner and itemized at checkout.</p>
                </div>
            </div>
            <div x-data="settingToggle({{ $settings->rain_fee_enabled ? 'true' : 'false' }}, '{{ route('admin.settings.rain-fee-enabled') }}')">
                <label class="relative inline-flex items-center cursor-pointer shrink-0" :class="updating && 'opacity-60 pointer-events-none'">
                    <input type="checkbox" class="sr-only peer" :checked="on" @change="toggle()">
                    <div class="w-16 h-9 rounded-full transition-colors duration-300 bg-gray-300 peer-checked:bg-sky-500"></div>
                    <div class="absolute left-1 top-1 w-7 h-7 bg-white rounded-full shadow-md transition-transform duration-300 peer-checked:translate-x-7 flex items-center justify-center text-xs"
                         x-text="on ? '🌧️' : '✕'"></div>
                </label>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.settings.rain-fee') }}" class="grid sm:grid-cols-2 gap-3 mt-4">
            @csrf
            @method('PATCH')
            <div>
                <label class="block text-xs font-semibold text-maroon-500 uppercase tracking-wide mb-1.5">Rain fee (₹)</label>
                <input type="number" name="rain_fee_amount" min="0" placeholder="e.g. 20"
                       value="{{ old('rain_fee_amount', $settings->rain_fee_amount) }}"
                       class="w-full rounded-lg border border-gold-300/70 px-3 py-2 text-sm text-maroon-800 placeholder-maroon-300 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
            </div>
            <div>
                <label class="block text-xs font-semibold text-maroon-500 uppercase tracking-wide mb-1.5">Reason <span class="normal-case font-normal text-maroon-400">(optional)</span></label>
                <input type="text" name="rain_fee_reason" maxlength="100" placeholder="Heavy Rain"
                       value="{{ old('rain_fee_reason', $settings->rain_fee_reason) }}"
                       class="w-full rounded-lg border border-gold-300/70 px-3 py-2 text-sm text-maroon-800 placeholder-maroon-300 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
            </div>
            <div class="sm:col-span-2">
                <label class="block text-xs font-semibold text-maroon-500 uppercase tracking-wide mb-1.5">Customer message <span class="normal-case font-normal text-maroon-400">(optional — leave blank to auto-generate from the fee and reason above)</span></label>
                <textarea name="rain_fee_message" rows="2" maxlength="500" placeholder="{{ $settings->rainFeeMessage() }}"
                          class="w-full rounded-lg border border-gold-300/70 px-3 py-2 text-sm text-maroon-800 placeholder-maroon-300 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">{{ old('rain_fee_message', $settings->rain_fee_message) }}</textarea>
            </div>
            <div class="sm:col-span-2 flex justify-end">
                <button type="submit" class="btn-gold text-sm px-5 py-2">Save</button>
            </div>
        </form>
    </div>

    {{-- high demand mode --}}
    <div class="rounded-2xl border border-amber-200 bg-gradient-to-br from-amber-50 via-white to-cream p-5 mt-5"
         x-data="{ mode: {{ Illuminate\Support\Js::from(old('high_demand_mode', $settings->high_demand_mode)) }} }">
        <div class="flex items-center gap-3">
            <span class="text-2xl">⚡</span>
            <div>
                <p class="font-display text-maroon-800">High Demand Mode</p>
                <p class="text-sm text-maroon-500 mt-0.5">Choose how checkout behaves when order volume is too high to handle normally.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.settings.high-demand') }}" class="mt-4">
            @csrf
            @method('PATCH')

            <div class="grid sm:grid-cols-3 gap-2.5">
                <button type="button" @click="mode = 'normal'"
                        class="rounded-lg border px-3 py-3 text-sm font-medium text-center transition"
                        :class="mode === 'normal' ? 'border-pista-500 ring-2 ring-pista-200 bg-pista-50/60 text-maroon-800' : 'border-gold-200/60 text-maroon-500 hover:border-pista-300'">
                    ✅ Accept Normally
                </button>
                <button type="button" @click="mode = 'fee'"
                        class="rounded-lg border px-3 py-3 text-sm font-medium text-center transition"
                        :class="mode === 'fee' ? 'border-amber-500 ring-2 ring-amber-200 bg-amber-50/60 text-maroon-800' : 'border-gold-200/60 text-maroon-500 hover:border-amber-300'">
                    ⚡ Accept with Fee
                </button>
                <button type="button" @click="mode = 'stop'"
                        class="rounded-lg border px-3 py-3 text-sm font-medium text-center transition"
                        :class="mode === 'stop' ? 'border-red-500 ring-2 ring-red-200 bg-red-50/60 text-maroon-800' : 'border-gold-200/60 text-maroon-500 hover:border-red-300'">
                    🚫 Stop Accepting
                </button>
            </div>
            <input type="hidden" name="high_demand_mode" x-model="mode">

            <div x-show="mode === 'fee'" x-cloak class="grid sm:grid-cols-2 gap-3 mt-3">
                <div>
                    <label class="block text-xs font-semibold text-maroon-500 uppercase tracking-wide mb-1.5">High demand fee (₹)</label>
                    <input type="number" name="high_demand_fee_amount" min="0" placeholder="e.g. 30"
                           value="{{ old('high_demand_fee_amount', $settings->high_demand_fee_amount) }}"
                           class="w-full rounded-lg border border-gold-300/70 px-3 py-2 text-sm text-maroon-800 placeholder-maroon-300 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-maroon-500 uppercase tracking-wide mb-1.5">Custom message <span class="normal-case font-normal text-maroon-400">(optional)</span></label>
                    <input type="text" name="high_demand_message" maxlength="500" placeholder="{{ $settings->highDemandFeeMessage() }}"
                           value="{{ old('high_demand_message', $settings->high_demand_message) }}"
                           class="w-full rounded-lg border border-gold-300/70 px-3 py-2 text-sm text-maroon-800 placeholder-maroon-300 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
                </div>
            </div>

            <div x-show="mode === 'stop'" x-cloak class="mt-3">
                <label class="block text-xs font-semibold text-maroon-500 uppercase tracking-wide mb-1.5">Message shown to customers <span class="normal-case font-normal text-maroon-400">(optional)</span></label>
                <textarea name="high_demand_stop_message" rows="2" maxlength="500" placeholder="{{ $settings->highDemandStopMessage() }}"
                          class="w-full rounded-lg border border-gold-300/70 px-3 py-2 text-sm text-maroon-800 placeholder-maroon-300 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">{{ old('high_demand_stop_message', $settings->high_demand_stop_message) }}</textarea>
            </div>

            <div class="flex justify-end mt-4">
                <button type="submit" class="btn-gold text-sm px-5 py-2">Save</button>
            </div>
        </form>
    </div>
@endsection
