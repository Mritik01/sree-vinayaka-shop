@extends('admin.layout')

@section('title', $order->orderNumber())
@section('page-title', $order->orderNumber())

@section('content')
    @php
        $steps = [
            1 => ['icon' => '🧾', 'label' => __('Placed')],
            2 => ['icon' => '📦', 'label' => __('Confirmed')],
            3 => ['icon' => '🛵', 'label' => __('Out for Delivery')],
            4 => ['icon' => '🎁', 'label' => __('Delivered')],
        ];
        $cancelledReasons = [
            'customer' => __('— by the customer (no COD restriction applied)'),
            'admin' => __('— by the shop'),
            'system' => __('— auto-cancelled after 90 min (no COD restriction applied)'),
        ];
        $removalReasons = \App\Models\OrderItem::REMOVAL_REASONS;
    @endphp

    {{-- real browser-history back (so arriving here from a customer's page returns there, not
         always to the Orders list) — falls back to the Orders list only when there's nowhere
         to go back to (e.g. this page was opened directly in a new tab) --}}
    <a href="{{ route('admin.orders.index') }}"
       onclick="if (window.history.length > 1) { event.preventDefault(); window.history.back(); }"
       class="text-sm text-maroon-500 hover:text-maroon-700 transition">← {{ __('Back') }}</a>

    <div class="space-y-5 mt-4" x-data='adminOrderShowPage(@json($orderForJs), {{ $order->id }}, @json($cancelledReasons))'>
        @if ($order->is_gift_order)
            <div class="rounded-xl bg-gradient-to-r from-pink-500 via-gold-500 to-pink-500 text-white px-5 py-3.5 flex items-center gap-2.5 shadow-md">
                <span class="text-xl">🎁</span>
                <p class="font-semibold text-sm">{{ __("This order claimed a loyalty free gift — it's included in the items below at ₹0.") }}</p>
            </div>
        @endif

        <div x-show="order.status === 'cancelled' && order.cancelled_by === 'system'" x-cloak class="rounded-xl bg-red-50 border-2 border-red-300 text-red-700 px-5 py-3.5 flex items-center gap-2.5 shadow-sm">
            <span class="text-xl">⏰</span>
            <p class="font-semibold text-sm">{{ __("This order was auto-cancelled — it wasn't delivered within 90 minutes of being placed. No COD restriction was applied to the customer.") }}</p>
        </div>

        @php
            $isPaidOnline = $order->payment_method === 'razorpay' && $order->payment_status === 'paid';
            $isUnpaidOnline = $order->payment_method === 'razorpay' && $order->payment_status !== 'paid';
            $isCodPaid = $order->codPaymentReceived();
        @endphp

        {{-- payment mode — the single most important thing to know before confirming or dispatching this order --}}
        <div class="rounded-2xl border-2 p-4 flex items-center justify-between gap-3 flex-wrap shadow-sm animate-fade-up
            {{ $isUnpaidOnline ? 'bg-red-50 border-red-300' : (($isPaidOnline || $isCodPaid) ? 'bg-pista-100 border-pista-400' : 'bg-gold-50 border-gold-400') }}">
            <div class="flex items-center gap-3 min-w-0">
                <span class="text-3xl shrink-0">{{ $isUnpaidOnline ? '⚠️' : (($isPaidOnline || $isCodPaid) ? '✅' : '💵') }}</span>
                <div class="min-w-0">
                    @if ($isUnpaidOnline)
                        <p class="font-display font-bold text-red-700">{{ __('Payment Not Confirmed') }}</p>
                        <p class="text-xs text-red-600 mt-0.5">{{ __('Razorpay has not confirmed this payment yet — do not dispatch until it clears.') }}</p>
                    @elseif ($isPaidOnline && $order->hasPostPaymentBalance())
                        <p class="font-display font-bold text-pista-600">{{ __('Partially Paid') }}</p>
                        <p class="text-xs text-pista-600/80 mt-0.5">{{ __('Rider will collect') }} ₹{{ number_format($order->balanceDueOnDelivery()) }} {{ __('for items added after payment.') }}</p>
                    @elseif ($isPaidOnline)
                        <p class="font-display font-bold text-pista-600">{{ __('Paid Online') }}</p>
                        <p class="text-xs text-pista-600/80 mt-0.5">{{ __('No cash to collect — already paid via Razorpay.') }}</p>
                    @elseif ($isCodPaid)
                        <p class="font-display font-bold text-pista-600">{{ __('Cash Collected') }}</p>
                        <p class="text-xs text-pista-600/80 mt-0.5">₹{{ number_format($order->amount_paid) }} {{ __('collected by the rider on delivery') }}{{ $order->paid_at ? ' · '.$order->paid_at->format('d M, h:i A') : '' }}.</p>
                    @else
                        <p class="font-display font-bold text-gold-600">{{ __('Cash on Delivery') }}</p>
                        <p class="text-xs text-gold-600/80 mt-0.5">{{ __('Rider must collect') }} ₹{{ number_format($order->total) }} {{ __('from the customer.') }}</p>
                    @endif
                </div>
            </div>

            @if ($order->razorpay_order_id)
                <div class="text-right shrink-0">
                    <p class="text-[10px] uppercase tracking-wide font-semibold text-maroon-400">{{ __('Transaction ID') }}</p>
                    <p class="font-mono font-bold text-sm text-maroon-800" title="{{ __('Our own reference — search by this instead of Razorpay\'s id') }}">{{ $order->transactionId() }}</p>
                    <p class="text-[11px] font-mono text-maroon-400 mt-0.5" title="{{ __('Razorpay reference, for support/refund lookups') }}">{{ $order->razorpay_payment_id ?: $order->razorpay_order_id }}</p>
                </div>
            @endif
        </div>

        @if ($order->payment_method === 'razorpay' && ($order->refund_status !== 'none' || $order->isRefundable()))
            <div class="bg-white rounded-2xl border border-gold-200/60 shadow-sm p-5 animate-fade-up">
                <p class="font-display text-maroon-800 mb-3">↩️ {{ __('Refund') }}</p>

                @if ($order->refund_status !== 'none')
                    <div class="rounded-xl bg-gold-50 border border-gold-300/60 px-4 py-3 mb-4">
                        <p class="text-sm text-maroon-700">
                            <span class="font-semibold">₹{{ number_format($order->refunded_amount) }}</span>
                            {{ $order->refund_status === 'full' ? __('fully refunded') : __('refunded so far') }}
                            @if ($order->refunded_at)
                                · {{ $order->refunded_at->format('d M, h:i A') }}
                            @endif
                        </p>
                        @if ($order->razorpay_refund_id)
                            <p class="text-xs text-maroon-400 font-mono mt-1">{{ $order->razorpay_refund_id }}</p>
                        @endif
                    </div>
                @endif

                @if ($order->unrefundedGapFromRemovedItems() > 0)
                    <div class="rounded-xl bg-red-50 border border-red-200 px-4 py-3 mb-4">
                        <p class="text-sm text-red-700">
                            {{ __('Already paid') }} <span class="font-semibold">₹{{ number_format($order->amount_paid) }}</span>
                            — {{ __('the order total is now') }} <span class="font-semibold">₹{{ number_format($order->total) }}</span>
                            {{ __('after removing unavailable item(s). Consider refunding the') }} ₹{{ number_format($order->unrefundedGapFromRemovedItems()) }} {{ __('difference below.') }}
                        </p>
                    </div>
                @endif

                @if ($order->isRefundable())
                    <form method="POST" action="{{ route('admin.orders.refund', $order) }}"
                          onsubmit="return confirm('Refund ₹' + document.getElementById('refund-amount-{{ $order->id }}').value + ' for order {{ $order->orderNumber() }} via Razorpay? This cannot be undone.');"
                          class="flex items-center gap-2 flex-wrap">
                        @csrf
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-maroon-400 text-sm">₹</span>
                            <input type="number" id="refund-amount-{{ $order->id }}" name="amount" min="1" max="{{ $order->refundableAmount() }}"
                                   value="{{ $order->refundableAmount() }}"
                                   class="w-32 rounded-lg border border-gold-300/70 pl-6 pr-3 py-2 text-sm text-maroon-800 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
                        </div>
                        <button type="submit" class="shrink-0 text-sm font-semibold px-4 py-2 rounded-lg bg-red-600 hover:bg-red-700 text-white transition">
                            {{ __('Initiate Refund') }}
                        </button>
                        <span class="text-xs text-maroon-400">{{ __('Max refundable') }}: ₹{{ number_format($order->refundableAmount()) }}</span>
                    </form>
                @endif
            </div>
        @endif

        {{-- header --}}
        <div class="bg-white rounded-2xl border border-gold-200/60 shadow-sm p-6 animate-fade-up">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="font-display text-2xl text-maroon-800">{{ $order->orderNumber() }}</p>
                    <p class="text-sm text-maroon-500 mt-1">
                        {{ __('Placed') }} {{ $order->created_at->format('d M Y, h:i A') }}
                    </p>
                    <button type="button" @click="window.dispatchEvent(new CustomEvent('open-support-widget', { detail: { orderId: {{ $order->id }}, summary: { customer_name: {{ Illuminate\Support\Js::from($order->customer_name) }}, order_number: {{ Illuminate\Support\Js::from($order->orderNumber()) }} } } }))"
                       class="inline-flex items-center gap-1.5 mt-3 text-xs font-semibold text-maroon-700 border border-gold-300/70 hover:border-gold-400 hover:bg-cream rounded-lg px-3 py-2 transition">
                        💬 {{ __('Support Chat') }}
                        @if (($supportUnread = $order->supportMessages()->where('sender', 'customer')->whereNull('read_at')->count()) > 0)
                            <span class="min-w-[18px] h-[18px] px-1 rounded-full bg-red-600 text-white text-[10px] font-bold grid place-items-center">{{ $supportUnread > 9 ? '9+' : $supportUnread }}</span>
                        @endif
                    </button>
                </div>
                <div class="text-right">
                    <span class="inline-block text-xs font-semibold px-2.5 py-1 rounded-full border" :class="statusBadgeClasses()" x-text="statusLabel()"></span>
                    <p class="font-display font-bold text-3xl text-maroon-800 mt-2">₹{{ number_format($order->total) }}</p>
                </div>
            </div>
        </div>

        {{-- progress stepper --}}
        <div x-show="order.status !== 'cancelled'" x-cloak class="bg-white rounded-2xl border border-gold-200/60 shadow-sm px-4 sm:px-6 py-6 animate-fade-up" style="animation-delay: 60ms">
            <div class="flex items-start">
                @foreach ($steps as $i => $step)
                    @if ($i > 1)
                        <div class="flex-1 h-1.5 rounded-full mt-[1.4rem] mx-1 sm:mx-2 bg-gold-100 overflow-hidden">
                            <div class="h-full rounded-full bg-gradient-to-r from-gold-400 to-gold-600 transition-all duration-1000"
                                 :style="'width: ' + (rank() >= {{ $i }} ? 100 : 0) + '%'"></div>
                        </div>
                    @endif
                    <div class="flex flex-col items-center w-16 sm:w-24 shrink-0">
                        <div class="w-11 h-11 rounded-full grid place-items-center text-lg border-2"
                             :class="{
                                 'bg-pista-500 border-pista-500': stepState({{ $i }}) === 'done',
                                 'bg-gold-100 border-gold-500 animate-track-breathe': stepState({{ $i }}) === 'active',
                                 'bg-white border-gold-200': stepState({{ $i }}) === 'todo',
                             }">
                            <template x-if="stepState({{ $i }}) === 'done'">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                </svg>
                            </template>
                            <template x-if="stepState({{ $i }}) !== 'done'">
                                <span :class="stepState({{ $i }}) === 'todo' ? 'grayscale opacity-40' : ''">{{ $step['icon'] }}</span>
                            </template>
                        </div>
                        <p class="text-[11px] sm:text-xs font-semibold mt-2 text-center leading-tight" :class="stepState({{ $i }}) === 'todo' ? 'text-maroon-400/60' : 'text-maroon-700'">{{ $step['label'] }}</p>
                        <p class="text-[10px] sm:text-[11px] text-maroon-400 mt-0.5 h-3.5" x-text="stepTime({{ $i }})"></p>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="grid lg:grid-cols-[1fr_360px] gap-5 items-start">
            <div class="space-y-5">
                {{-- items — the confirm checkmarks below are a purely internal packing
                     checklist (order_items.confirmed_at); never shown to the customer and never
                     touches the order's real status/timeline --}}
                <div class="bg-white rounded-2xl border border-gold-200/60 shadow-sm overflow-hidden animate-fade-up" style="animation-delay: 100ms">
                    <div class="px-5 py-4 border-b border-gold-100 flex items-center justify-between gap-3">
                        <p class="font-display text-maroon-800">
                            {{ __('Items') }}
                            <span class="text-xs font-normal text-maroon-400 ml-1" x-text="`(${confirmedItemsCount()}/${totalItemsCount()} ${confirmedItemsCount() === totalItemsCount() ? '✓' : 'confirmed'})`"></span>
                        </p>
                        <div class="flex items-center gap-2">
                            {{-- available until the order leaves the shop — same gate itemsLocked()
                                 already uses for the packing checklist below --}}
                            <button type="button" x-show="!itemsLocked()"
                                    @click="window.dispatchEvent(new CustomEvent('open-add-product-modal', { detail: { orderId: {{ $order->id }}, isPaidOnline: {{ $isPaidOnline ? 'true' : 'false' }} } }))"
                                    class="text-xs font-semibold px-3 py-1.5 rounded-lg border-2 border-gold-500 text-gold-700 bg-gold-50 hover:bg-gold-100 transition">
                                ➕ {{ __('Add Product') }}
                            </button>
                            <button type="button" @click="confirmAllItems()" :disabled="allItemsConfirmed() || itemsLocked()"
                                    class="text-xs font-semibold px-3 py-1.5 rounded-lg border transition disabled:opacity-40 disabled:cursor-default"
                                    :class="allItemsConfirmed() ? 'border-pista-400/60 text-pista-600 bg-pista-100' : 'border-pista-500 text-pista-600 hover:bg-pista-100'">
                                <span x-show="!allItemsConfirmed()">✓ {{ __('Confirm All') }}</span>
                                <span x-show="allItemsConfirmed()" x-cloak>✓ {{ __('All Confirmed') }}</span>
                            </button>
                        </div>
                    </div>
                    <div class="divide-y divide-gold-50">
                        @foreach ($order->items->whereNull('removed_at') as $item)
                            <div class="flex items-center gap-3.5 px-5 py-3.5 {{ $item->is_gift ? 'bg-gradient-to-r from-pink-50 to-gold-50' : '' }}">
                                <div class="w-12 h-12 shrink-0 rounded-lg overflow-hidden bg-cream border border-gold-100">
                                    @if ($item->product)
                                        <img src="{{ asset($item->product->image) }}" alt="{{ $item->product_name }}" class="w-full h-full object-cover">
                                    @else
                                        <div class="w-full h-full grid place-items-center text-lg">🍬</div>
                                    @endif
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-maroon-800 truncate">
                                        {{ $item->product_name }}
                                        @if ($item->is_gift)
                                            <span class="ml-1 text-[10px] font-bold uppercase tracking-wide px-1.5 py-0.5 rounded-full bg-gradient-to-r from-pink-500 to-gold-500 text-white">🎁 {{ __('Free Gift') }}</span>
                                        @endif
                                        @unless ($item->product)
                                            <span class="text-maroon-300 text-xs">({{ __('product removed') }})</span>
                                        @endunless
                                    </p>
                                    <p class="text-xs text-maroon-500 mt-0.5">
                                        ₹{{ $item->product_price }}
                                        @if ($item->portionLabel())
                                            ({{ $item->portionLabel() }})
                                        @endif
                                        × {{ $item->quantity }}
                                    </p>
                                    @if ($item->isAdminAdded())
                                        {{-- audit trail for a product an admin added to this already-placed order —
                                             see Admin\OrderController::addItems() --}}
                                        <p class="text-[10px] font-semibold text-gold-600 mt-1">
                                            ➕ {{ __('Added by') }} {{ $item->addedByAdmin->name ?? __('an admin') }} · {{ $item->added_at->format('d M, h:i A') }}
                                        </p>
                                    @endif
                                </div>
                                <p class="text-sm font-semibold text-maroon-800 shrink-0">{{ $item->is_gift ? __('FREE') : '₹'.number_format($item->line_total) }}</p>

                                <div class="flex items-center gap-2 shrink-0">
                                    {{-- packing-verification button — green outline "Confirm" until clicked,
                                         then a solid green checkmark --}}
                                    <button type="button" @click="toggleItemConfirmed({{ $item->id }})" :disabled="itemsLocked()"
                                            aria-label="{{ __('Toggle packed/confirmed') }}"
                                            class="shrink-0 rounded-lg transition font-semibold disabled:opacity-50 disabled:cursor-default"
                                            :class="isItemConfirmed({{ $item->id }})
                                                ? 'w-9 h-9 grid place-items-center bg-pista-500 text-white text-base shadow-sm hover:bg-pista-600'
                                                : 'text-xs px-3 py-2 border-2 border-pista-500 text-pista-600 hover:bg-pista-100'">
                                        <span x-show="isItemConfirmed({{ $item->id }})" x-cloak>✓</span>
                                        <span x-show="!isItemConfirmed({{ $item->id }})">{{ __('Confirm') }}</span>
                                    </button>

                                    {{-- unavailable-stock escape hatch — hidden the instant this item is
                                         packed (see the removal gate in Admin\OrderController::removeItem) --}}
                                    <button type="button" x-show="!isItemConfirmed({{ $item->id }})" x-cloak
                                            @click="window.dispatchEvent(new CustomEvent('open-remove-item-modal', {
                                                detail: {
                                                    orderId: {{ $order->id }},
                                                    itemId: {{ $item->id }},
                                                    itemName: {{ Illuminate\Support\Js::from($item->product_name) }},
                                                    itemImage: {{ Illuminate\Support\Js::from($item->product ? asset($item->product->image) : null) }},
                                                    itemMeta: {{ Illuminate\Support\Js::from(($item->portionLabel() ? $item->portionLabel().' · ' : '').'× '.$item->quantity) }},
                                                }
                                            }))"
                                            aria-label="{{ __('Remove item — unavailable') }}"
                                            class="shrink-0 w-9 h-9 rounded-lg grid place-items-center text-red-400 hover:text-red-600 hover:bg-red-50 border-2 border-transparent hover:border-red-200 transition">
                                        🗑️
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="px-5 py-4 border-t border-gold-100 space-y-1.5 text-sm ml-auto max-w-xs">
                        <div class="flex justify-between"><span class="text-maroon-500">{{ __('Subtotal') }}</span><span class="text-maroon-800">₹{{ number_format($order->subtotal) }}</span></div>
                        @if ($order->discount_amount > 0)
                            <div class="flex justify-between"><span class="text-pista-600">{{ __('Coupon discount') }} ({{ $order->coupon->code ?? __('removed') }})</span><span class="text-pista-600">−₹{{ number_format($order->discount_amount) }}</span></div>
                        @endif
                        @foreach ($order->fees as $fee)
                            <div class="flex justify-between"><span class="text-maroon-500">{{ $fee->label }}</span><span class="text-maroon-800">₹{{ number_format($fee->amount) }}</span></div>
                        @endforeach
                        <div class="flex justify-between font-semibold text-base pt-1.5 border-t border-gold-100"><span class="text-maroon-800">{{ __('Total') }}</span><span class="text-maroon-800">₹{{ number_format($order->total) }}</span></div>
                    </div>
                </div>

                {{-- audit trail for anything removed via the escape hatch above — never hidden,
                     so there's always a record of who removed what and why --}}
                @php $removedItems = $order->items->whereNotNull('removed_at'); @endphp
                @if ($removedItems->isNotEmpty())
                    <div class="bg-white rounded-2xl border border-red-200/70 shadow-sm overflow-hidden animate-fade-up" style="animation-delay: 120ms">
                        <div class="px-5 py-4 border-b border-red-100 bg-red-50/50">
                            <p class="font-display text-red-700">🗑️ {{ __('Removed Items') }} <span class="text-xs font-normal text-red-400 ml-1">({{ __('audit trail') }})</span></p>
                        </div>
                        <div class="divide-y divide-red-50">
                            @foreach ($removedItems as $item)
                                <div class="flex items-center gap-3.5 px-5 py-3.5 opacity-70">
                                    <div class="w-12 h-12 shrink-0 rounded-lg overflow-hidden bg-cream border border-gold-100 grayscale">
                                        @if ($item->product)
                                            <img src="{{ asset($item->product->image) }}" alt="{{ $item->product_name }}" class="w-full h-full object-cover">
                                        @else
                                            <div class="w-full h-full grid place-items-center text-lg">🍬</div>
                                        @endif
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-medium text-maroon-600 line-through truncate">{{ $item->product_name }} × {{ $item->quantity }}</p>
                                        <p class="text-xs text-red-500 mt-0.5">
                                            {{ __('Reason') }}: {{ $item->removalReasonLabel() }}@if ($item->removal_reason_note) — {{ $item->removal_reason_note }}@endif
                                        </p>
                                        <p class="text-[11px] text-maroon-400 mt-0.5">
                                            {{ __('Removed by') }} {{ $item->removedByAdmin->name ?? __('an admin') }} · {{ $item->removed_at->format('d M, h:i A') }}
                                        </p>
                                    </div>
                                    <span class="shrink-0 text-[11px] font-semibold uppercase tracking-wide px-2 py-1 rounded-full bg-red-100 text-red-600">{{ __('Removed') }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($order->customer_note)
                    <div class="bg-gold-50 rounded-2xl border border-gold-300/60 shadow-sm p-5 animate-fade-up" style="animation-delay: 140ms">
                        <p class="font-display text-maroon-800 mb-2">📝 {{ __('Customer Note') }}</p>
                        <p class="text-sm text-maroon-700 whitespace-pre-line">{{ $order->customer_note }}</p>

                        @if ($order->note_status === 'pending')
                            {{-- plain form POST + redirect, same pattern as the Remove Item /
                                 Assign Rider / Refund forms elsewhere on this page — two submit
                                 buttons with different `status` values, no JS needed --}}
                            <form method="POST" action="{{ route('admin.orders.note-decision', $order) }}" class="mt-3 space-y-2">
                                @csrf
                                @method('PATCH')
                                <textarea name="message" rows="2" maxlength="500" placeholder="{{ __('Optional message to the customer…') }}"
                                          class="w-full rounded-lg border border-gold-300/70 bg-white px-3 py-2 text-sm text-maroon-800 placeholder-maroon-300 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition resize-none"></textarea>
                                <div class="flex items-center gap-2">
                                    <button type="submit" name="status" value="accepted"
                                            class="text-xs font-semibold px-4 py-2 rounded-full bg-pista-600 hover:bg-pista-700 text-white transition">
                                        ✓ {{ __('Accept') }}
                                    </button>
                                    <button type="submit" name="status" value="denied"
                                            class="text-xs font-semibold px-4 py-2 rounded-full border border-red-300 text-red-700 hover:bg-red-50 transition">
                                        ✕ {{ __('Deny') }}
                                    </button>
                                </div>
                            </form>
                        @elseif ($order->note_status === 'accepted')
                            <div class="mt-3 flex items-start gap-2 bg-pista-50 border border-pista-300/60 rounded-xl px-3.5 py-2.5">
                                <span class="text-lg shrink-0">✅</span>
                                <div>
                                    <p class="text-sm font-semibold text-pista-700">{{ __('Accepted') }}</p>
                                    @if ($order->note_decision_message)
                                        <p class="text-xs text-maroon-600 mt-0.5">{{ $order->note_decision_message }}</p>
                                    @endif
                                </div>
                            </div>
                        @elseif ($order->note_status === 'denied')
                            <div class="mt-3 flex items-start gap-2 bg-red-50 border border-red-300/60 rounded-xl px-3.5 py-2.5">
                                <span class="text-lg shrink-0">✕</span>
                                <div>
                                    <p class="text-sm font-semibold text-red-700">{{ __('Denied') }}</p>
                                    @if ($order->note_decision_message)
                                        <p class="text-xs text-maroon-600 mt-0.5">{{ $order->note_decision_message }}</p>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                @endif

                @if ($order->status !== 'cancelled' || $order->rider || $order->delivery_photo_path)
                    <div class="bg-white rounded-2xl border border-gold-200/60 shadow-sm p-5 animate-fade-up" style="animation-delay: 180ms">
                        <p class="font-display text-maroon-800 mb-3">🛵 {{ __('Delivery Rider') }}</p>
                        @if ($order->status === 'cancelled')
                            @if ($order->rider)
                                <p class="text-sm text-maroon-700">{{ __('Rider') }}: <span class="font-medium">{{ $order->rider->name }}</span></p>
                            @endif
                        @else
                            <form method="POST" action="{{ route('admin.orders.rider', $order) }}" class="flex items-center gap-2">
                                @csrf
                                @method('PATCH')
                                <select name="rider_id" class="flex-1 rounded-lg border border-gold-300/70 px-3 py-2 text-sm text-maroon-800 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
                                    <option value="">{{ __('Unassigned') }}</option>
                                    @foreach ($riders as $rider)
                                        <option value="{{ $rider->id }}" {{ $order->rider_id === $rider->id ? 'selected' : '' }}>{{ $rider->name }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="shrink-0 text-sm font-semibold px-3.5 py-2 rounded-lg bg-maroon-700 hover:bg-maroon-800 text-cream transition">{{ __('Assign') }}</button>
                            </form>
                        @endif
                        <template x-if="order.delivery_photo_url">
                            <div>
                                <a :href="order.delivery_photo_url" target="_blank" rel="noopener" class="block mt-3 group">
                                    <img :src="order.delivery_photo_url" alt="Delivery proof for order {{ $order->orderNumber() }}"
                                         class="w-full max-h-80 object-cover rounded-xl border border-gold-200/60 group-hover:opacity-90 transition">
                                </a>
                                <p class="text-xs text-maroon-400 mt-2">{{ __('Uploaded') }} <span x-text="order.delivery_photo_uploaded_at"></span> — {{ __('auto-removed after 3 days.') }} <span class="text-maroon-300">({{ __('tap to view full size') }})</span></p>
                            </div>
                        </template>
                    </div>
                @endif
            </div>

            <div class="space-y-5">
                <div class="bg-white rounded-2xl border border-gold-200/60 shadow-sm p-5 animate-fade-up" style="animation-delay: 100ms">
                    <p class="font-display text-maroon-800 mb-3">{{ __('Customer') }}</p>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 shrink-0 rounded-full bg-maroon-700 text-cream flex items-center justify-center font-display font-bold">
                            {{ strtoupper(substr($order->customer_name, 0, 1)) }}
                        </div>
                        <div class="min-w-0">
                            @if ($order->user)
                                {{-- one-click lookup — jumps straight to this customer's admin profile
                                     (order history, block status, coupons, everything) --}}
                                <a href="{{ route('admin.customers.show', $order->user) }}" class="text-sm font-medium text-maroon-800 hover:text-gold-600 truncate block transition">
                                    {{ $order->customer_name }} <span class="text-xs font-normal text-gold-600">→</span>
                                </a>
                            @else
                                <p class="text-sm font-medium text-maroon-800 truncate">{{ $order->customer_name }}</p>
                            @endif
                            <a href="tel:+91{{ preg_replace('/\D/', '', $order->customer_phone) }}" class="text-sm text-maroon-500 hover:text-gold-600 transition">{{ $order->customer_phone }}</a>
                        </div>
                    </div>
                    @if ($order->user && $order->user->cod_blocked_orders > 0)
                        <p class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full bg-red-50 text-red-600 border border-red-200 mt-3">
                            ⚠️ {{ __('COD blocked for next :count orders', ['count' => $order->user->cod_blocked_orders]) }}
                        </p>
                    @endif
                    @if ($order->delivery_address)
                        <p class="text-xs text-maroon-400 mt-4 uppercase tracking-wide font-semibold">{{ __('Delivery Address') }}</p>
                        <p class="text-sm text-maroon-600 mt-1 whitespace-pre-line">{{ $order->delivery_address }}</p>
                    @endif
                    @if ($order->latitude !== null && $order->longitude !== null)
                        <p class="text-sm text-maroon-500 mt-2">
                            📍 @if ($order->distance_km !== null){{ number_format($order->distance_km, 1) }} {{ __('km from shop') }} · @endif<a href="https://www.google.com/maps?q={{ $order->latitude }},{{ $order->longitude }}" target="_blank" rel="noopener" class="text-gold-600 hover:text-gold-700 underline underline-offset-2">{{ __('View on map') }}</a>
                        </p>
                    @endif
                </div>

                <div class="bg-white rounded-2xl border border-gold-200/60 shadow-sm p-5 animate-fade-up" style="animation-delay: 140ms">
                    <div class="flex items-center justify-between mb-4">
                        <p class="font-display text-maroon-800">{{ __('Status') }}</p>
                        <span class="inline-block text-xs font-semibold px-2.5 py-1 rounded-full border" :class="statusBadgeClasses()" x-text="statusLabel()"></span>
                    </div>

                    @if ($order->eta_minutes && $etaEndsAt)
                        {{-- live digital-clock countdown, ticking every second — the same clock the customer sees on their tracking page --}}
                        <div x-show="['confirmed', 'out_for_delivery'].includes(order.status)" x-cloak x-data="etaCountdown({{ $etaEndsAt }})" class="mb-4 -mt-2">
                            <div class="flex items-center gap-3 rounded-xl px-4 py-3 transition-colors duration-300"
                                 :class="overdue ? 'bg-red-50 border border-red-200' : 'bg-maroon-900 border border-maroon-800'">
                                <span class="text-xl" x-text="overdue ? '⚠️' : '⏱️'"></span>
                                <div>
                                    <p class="text-[10px] font-semibold uppercase tracking-wide" :class="overdue ? 'text-red-500' : 'text-gold-300/70'" x-text='overdue ? @json(__('Running late by')) : @json(__('Arriving in'))'></p>
                                    <p class="font-mono text-2xl font-bold tabular-nums tracking-wider leading-tight" :class="overdue ? 'text-red-600' : 'text-gold-300'" x-text="clock"></p>
                                </div>
                            </div>
                            <p class="text-xs text-maroon-400 mt-2">{{ __('Promised') }} {{ $order->eta_minutes }} {{ __('min') }}{{ $order->confirmed_at ? ' ('.__('confirmed').' '.$order->confirmed_at->format('h:i A').')' : '' }}</p>
                        </div>
                        <p x-show="order.status === 'delivered'" x-cloak class="text-xs text-maroon-500 mb-4 -mt-2">⏱️ {{ __('Promised in') }} {{ $order->eta_minutes }} {{ __('min') }}{{ $order->confirmed_at ? ' ('.__('confirmed').' '.$order->confirmed_at->format('h:i A').')' : '' }}</p>
                    @endif

                    <div x-show="order.status === 'pending'" x-cloak>
                        <form method="POST" action="{{ route('admin.orders.status', $order) }}" x-data="{ eta: 30 }">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="confirmed">
                            <input type="hidden" name="eta_minutes" :value="eta">
                            <p class="text-xs text-maroon-400 uppercase tracking-wide font-semibold mb-2">{{ __('Delivery time') }}</p>
                            <div class="grid grid-cols-4 gap-2 mb-3">
                                @foreach ([20, 30, 45, 60] as $mins)
                                    <button type="button" @click="eta = {{ $mins }}"
                                            class="text-sm font-semibold rounded-lg py-2 border transition"
                                            :class="eta === {{ $mins }} ? 'bg-maroon-700 text-cream border-maroon-700' : 'bg-white text-maroon-600 border-gold-200/80 hover:border-gold-400'">
                                        {{ $mins }}{{ __('m') }}
                                    </button>
                                @endforeach
                            </div>
                            <button type="submit" :disabled="order.payment_method === 'RAZORPAY' && order.payment_status !== 'paid'"
                                    class="w-full bg-pista-500 hover:bg-pista-600 text-white font-semibold rounded-xl py-2.5 transition text-sm disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-pista-500">
                                ✓ {{ __('Confirm Order') }}
                            </button>
                            <p x-show="order.payment_method === 'RAZORPAY' && order.payment_status !== 'paid'" x-cloak class="text-xs text-maroon-400 mt-2">{{ __("Waiting for Razorpay to confirm payment before this can be confirmed.") }}</p>
                        </form>
                    </div>
                    <div x-show="order.status === 'confirmed'" x-cloak>
                        <form method="POST" action="{{ route('admin.orders.status', $order) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="out_for_delivery">
                            <button type="submit" :disabled="!order.rider_name"
                                    class="w-full bg-sky-500 hover:bg-sky-600 text-white font-semibold rounded-xl py-2.5 transition text-sm disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-sky-500">
                                🛵 {{ __('Out for Delivery') }}
                            </button>
                        </form>
                        <p x-show="!order.rider_name" x-cloak class="text-xs text-maroon-400 mt-2">{{ __('Assign a rider above before sending this out for delivery.') }}</p>
                    </div>
                    <div x-show="order.status === 'out_for_delivery'" x-cloak>
                        <form method="POST" action="{{ route('admin.orders.status', $order) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="delivered">
                            <button type="submit" class="w-full bg-maroon-700 hover:bg-maroon-800 text-cream font-semibold rounded-xl py-2.5 transition text-sm">✓ {{ __('Mark Delivered') }}</button>
                        </form>
                    </div>
                    <p x-show="order.status === 'delivered'" x-cloak class="text-sm text-maroon-500">{{ __('Delivered') }} <span x-text="order.delivered_at_full"></span> 🎉</p>
                    <p x-show="order.status === 'cancelled'" x-cloak class="text-sm text-red-600">
                        {{ __('Cancelled') }} <span x-text="order.cancelled_at_full"></span>
                        <span x-text="cancelledReasonText()"></span>
                    </p>

                    <div x-show="['pending', 'confirmed', 'out_for_delivery'].includes(order.status)" x-cloak>
                        <form method="POST" action="{{ route('admin.orders.status', $order) }}" class="mt-3"
                              onsubmit="if (!confirm('{{ __('Cancel order') }} {{ $order->orderNumber() }}? {{ $order->payment_method === 'cod' ? __('The customer will see it as cancelled by the shop, and their COD will be restricted for the next 2 orders.') : __('The customer will see it as cancelled by the shop.') }}')) return false;
                                        this.querySelector('[name=cancellation_reason]').value = prompt('{{ __('Reason for cancelling (optional):') }}') || '';
                                        return true;">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="cancelled">
                            <input type="hidden" name="cancellation_reason" value="">
                            <button type="submit" class="w-full bg-white border border-red-300 hover:bg-red-50 text-red-600 font-semibold rounded-xl py-2.5 transition text-sm">✕ {{ __('Cancel Order') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- shared "remove unavailable item" modal — one element serving every row's 🗑️ button
         above rather than one per row, opened via the open-remove-item-modal window event. The
         modal itself (item preview + explicit reason + Remove/Cancel) is the confirmation
         dialog — a plain form POST (@method('DELETE')), same full-page-redirect pattern as the
         Refund/Cancel Order forms elsewhere on this page. --}}
    <div x-data='removeItemModal(@json($removalReasons))' x-show="open" x-cloak class="fixed inset-0 z-[90] flex items-center justify-center p-4">
        <div x-show="open" class="absolute inset-0 bg-maroon-900/60 backdrop-blur-sm"
             x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             @click="open = false"></div>

        <div x-show="open" class="relative bg-cream rounded-2xl shadow-2xl w-full max-w-md overflow-hidden"
             x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95 translate-y-4" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
            <div class="relative px-6 py-5 bg-gradient-to-r from-red-500 to-red-700 overflow-hidden">
                <div class="absolute inset-0 opacity-25" style="background-image: radial-gradient(circle, white 1.5px, transparent 1.5px); background-size: 16px 16px;"></div>
                <p class="relative font-display font-bold text-lg text-white">🗑️ {{ __('Remove Item') }}</p>
                <p class="relative text-white/80 text-xs mt-0.5">{{ __("This can't be undone — the customer will be notified immediately.") }}</p>
            </div>

            <div class="p-6">
                <div class="flex items-center gap-3 mb-5 pb-4 border-b border-gold-200/60">
                    <div class="w-12 h-12 shrink-0 rounded-lg overflow-hidden bg-white border border-gold-100">
                        <template x-if="itemImage">
                            <img :src="itemImage" class="w-full h-full object-cover">
                        </template>
                        <template x-if="!itemImage">
                            <div class="w-full h-full grid place-items-center text-lg">🍬</div>
                        </template>
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-maroon-800 truncate" x-text="itemName"></p>
                        <p class="text-xs text-maroon-400" x-text="itemMeta"></p>
                    </div>
                </div>

                <form method="POST" :action="removeUrl()" class="space-y-4">
                    @csrf
                    @method('DELETE')
                    <div>
                        <label class="block text-xs font-semibold text-maroon-500 uppercase tracking-wide mb-1.5">{{ __('Reason') }}</label>
                        <select name="reason" x-model="reason" required
                                class="w-full rounded-lg border border-gold-300/70 bg-white px-3 py-2.5 text-sm text-maroon-800 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
                            <option value="" disabled>{{ __('Select a reason…') }}</option>
                            <template x-for="(label, value) in reasons" :key="value">
                                <option :value="value" x-text="label"></option>
                            </template>
                        </select>
                    </div>

                    <div x-show="reason === 'custom'" x-cloak>
                        <label class="block text-xs font-semibold text-maroon-500 uppercase tracking-wide mb-1.5">{{ __('Details') }}</label>
                        <textarea name="note" x-model="note" maxlength="255" rows="2" placeholder="{{ __('Describe the reason…') }}"
                                  class="w-full rounded-lg border border-gold-300/70 bg-white px-3 py-2.5 text-sm text-maroon-800 placeholder-maroon-300 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition resize-none"></textarea>
                    </div>

                    <div class="flex items-center gap-3">
                        <button type="submit" :disabled="!reason || (reason === 'custom' && !note.trim())"
                                class="flex-1 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-xl py-2.5 transition text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                            🗑️ {{ __('Remove Item') }}
                        </button>
                        <button type="button" @click="open = false"
                                class="px-5 bg-white border border-gold-300/70 hover:bg-gold-50 text-maroon-600 font-semibold rounded-xl py-2.5 transition text-sm">
                            {{ __('Cancel') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- "Add Product" modal — opened via the open-add-product-modal window event (the ➕ button
         above). Fetch/JSON, not a form POST (see addProductModal() in admin.js) — success reloads
         the page so the server-rendered item list/totals/audit chip all pick up the change. --}}
    <div x-data="addProductModal()" x-show="open" x-cloak class="fixed inset-0 z-[90] flex items-center justify-center p-4">
        <div x-show="open" class="absolute inset-0 bg-maroon-900/60 backdrop-blur-sm"
             x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             @click="close()"></div>

        <div x-show="open" class="relative bg-cream rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden max-h-[90vh] flex flex-col"
             x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95 translate-y-4" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">

            <div class="relative px-6 py-5 bg-gradient-to-r from-gold-500 to-gold-600 overflow-hidden shrink-0">
                <div class="absolute inset-0 opacity-25" style="background-image: radial-gradient(circle, white 1.5px, transparent 1.5px); background-size: 16px 16px;"></div>
                <p class="relative font-display font-bold text-lg text-maroon-900">➕ {{ __('Add Product to Order') }}</p>
                <p class="relative text-maroon-900/70 text-xs mt-0.5">{{ __('The customer will be notified and totals recalculated automatically.') }}</p>
            </div>

            <div class="p-6 overflow-y-auto">
                <p x-show="error" x-cloak class="text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2 mb-4" x-text="error"></p>

                {{-- ── review / confirm step ─────────────────────────────── --}}
                <template x-if="confirming">
                    <div>
                        <p class="text-sm font-semibold text-maroon-700 mb-3">{{ __('Confirm the following item(s) will be added:') }}</p>
                        <div class="space-y-2 mb-4">
                            <template x-for="line in lines" :key="line.key">
                                <div class="flex items-center gap-3 bg-white rounded-xl px-3.5 py-2.5 border border-gold-100">
                                    <div class="w-10 h-10 shrink-0 rounded-lg overflow-hidden bg-cream border border-gold-100">
                                        <img :src="line.image" class="w-full h-full object-cover">
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-medium text-maroon-800 truncate">
                                            <span x-text="line.name"></span>
                                            <template x-if="line.label"><span class="text-maroon-400" x-text="'(' + line.label + ')'"></span></template>
                                        </p>
                                        <p class="text-xs text-maroon-500" x-text="'× ' + line.quantity"></p>
                                    </div>
                                    <span class="text-sm font-semibold text-maroon-800 shrink-0" x-text="'₹' + lineTotal(line).toLocaleString('en-IN')"></span>
                                </div>
                            </template>
                        </div>
                        <div class="flex justify-between text-sm font-semibold text-maroon-800 border-t border-gold-200 pt-3 mb-4">
                            <span>{{ __('Added subtotal') }}</span>
                            <span x-text="'₹' + stagedTotal().toLocaleString('en-IN')"></span>
                        </div>
                        <p x-show="isPaidOnline" x-cloak class="text-xs text-pista-700 bg-pista-50 border border-pista-200 rounded-lg px-3 py-2 mb-4">
                            💳 {{ __('This order was paid online — the extra amount will be collected in cash on delivery.') }}
                        </p>
                        <div class="flex items-center gap-3">
                            <button type="button" @click="submit()" :disabled="submitting"
                                    class="flex-1 bg-pista-600 hover:bg-pista-700 text-white font-semibold rounded-xl py-2.5 transition text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                                <span x-show="!submitting">✓ {{ __('Confirm — Add to Order') }}</span>
                                <span x-show="submitting" x-cloak>{{ __('Adding…') }}</span>
                            </button>
                            <button type="button" @click="backToEdit()" :disabled="submitting"
                                    class="px-5 bg-white border border-gold-300/70 hover:bg-gold-50 text-maroon-600 font-semibold rounded-xl py-2.5 transition text-sm">
                                {{ __('Back') }}
                            </button>
                        </div>
                    </div>
                </template>

                {{-- ── configure a picked product's variant + quantity ───────── --}}
                <template x-if="!confirming && selectedProduct">
                    <div>
                        <div class="flex items-center gap-3 mb-4 pb-4 border-b border-gold-200/60">
                            <div class="w-12 h-12 shrink-0 rounded-lg overflow-hidden bg-white border border-gold-100">
                                <img :src="selectedProduct.image" class="w-full h-full object-cover">
                            </div>
                            <p class="text-sm font-semibold text-maroon-800" x-text="selectedProduct.name"></p>
                        </div>

                        <div x-show="selectedProduct.portions.length > 1" x-cloak class="mb-4">
                            <label class="block text-xs font-semibold text-maroon-500 uppercase tracking-wide mb-1.5">{{ __('Variant') }}</label>
                            <select x-model.number="selectedPortion"
                                    class="w-full rounded-lg border border-gold-300/70 bg-white px-3 py-2.5 text-sm text-maroon-800 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
                                <template x-for="variant in selectedProduct.portions" :key="variant.portion">
                                    <option :value="variant.portion" x-text="(variant.label || '{{ __('Standard') }}') + ' — ₹' + variant.price.toLocaleString('en-IN')"></option>
                                </template>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="block text-xs font-semibold text-maroon-500 uppercase tracking-wide mb-1.5">{{ __('Quantity') }}</label>
                            <div class="flex items-center gap-3">
                                <button type="button" @click="selectedQuantity = Math.max(1, selectedQuantity - 1)"
                                        class="w-9 h-9 rounded-lg border-2 border-gold-300 text-maroon-700 font-bold hover:bg-gold-50 transition">−</button>
                                <input type="number" x-model.number="selectedQuantity" min="1" max="99"
                                       class="w-16 text-center rounded-lg border border-gold-300/70 bg-white px-2 py-2 text-sm text-maroon-800 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
                                <button type="button" @click="selectedQuantity = Math.min(99, selectedQuantity + 1)"
                                        class="w-9 h-9 rounded-lg border-2 border-gold-300 text-maroon-700 font-bold hover:bg-gold-50 transition">+</button>
                            </div>
                        </div>

                        <div class="flex justify-between text-sm font-semibold text-maroon-800 bg-gold-50 rounded-lg px-3 py-2.5 mb-4">
                            <span>{{ __('Price') }} <span class="font-normal text-maroon-500" x-text="'(₹' + selectedUnitPrice().toLocaleString('en-IN') + ' × ' + selectedQuantity + ')'"></span></span>
                            <span x-text="'₹' + selectedLineTotal().toLocaleString('en-IN')"></span>
                        </div>

                        <div class="flex items-center gap-3">
                            <button type="button" @click="stageSelected()"
                                    class="flex-1 bg-gold-500 hover:bg-gold-600 text-maroon-900 font-semibold rounded-xl py-2.5 transition text-sm">
                                ➕ {{ __('Add to List') }}
                            </button>
                            <button type="button" @click="cancelPick()"
                                    class="px-5 bg-white border border-gold-300/70 hover:bg-gold-50 text-maroon-600 font-semibold rounded-xl py-2.5 transition text-sm">
                                {{ __('Back') }}
                            </button>
                        </div>
                    </div>
                </template>

                {{-- ── search + staged list ──────────────────────────────── --}}
                <template x-if="!confirming && !selectedProduct">
                    <div>
                        <label class="block text-xs font-semibold text-maroon-500 uppercase tracking-wide mb-1.5">{{ __('Search Products') }}</label>
                        <input type="search" x-model="query" @input.debounce.250ms="search()" placeholder="{{ __('Search by product name…') }}"
                               class="w-full rounded-lg border border-gold-300/70 bg-white px-3 py-2.5 text-sm text-maroon-800 placeholder-maroon-300 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">

                        <div x-show="searching" x-cloak class="text-xs text-maroon-400 mt-2">{{ __('Searching…') }}</div>

                        <div x-show="results.length > 0" x-cloak class="mt-2 space-y-1.5 max-h-48 overflow-y-auto">
                            <template x-for="product in results" :key="product.id">
                                <button type="button" @click="pickProduct(product)"
                                        class="w-full flex items-center gap-3 bg-white hover:bg-gold-50 rounded-xl px-3 py-2 border border-gold-100 transition text-left">
                                    <div class="w-9 h-9 shrink-0 rounded-lg overflow-hidden bg-cream border border-gold-100">
                                        <img :src="product.image" class="w-full h-full object-cover">
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-medium text-maroon-800 truncate" x-text="product.name"></p>
                                        <p class="text-xs text-maroon-400" x-text="product.category"></p>
                                    </div>
                                    <span class="text-xs font-semibold text-gold-700 shrink-0" x-text="'from ₹' + Math.min(...product.portions.map(p => p.price)).toLocaleString('en-IN')"></span>
                                </button>
                            </template>
                        </div>
                        <p x-show="!searching && query.trim().length >= 2 && results.length === 0" x-cloak class="text-xs text-maroon-400 mt-2">
                            {{ __('No products found.') }}
                        </p>

                        <template x-if="lines.length > 0">
                            <div class="mt-5 pt-4 border-t border-gold-200/60">
                                <p class="text-xs font-semibold text-maroon-500 uppercase tracking-wide mb-2">{{ __('Staged to Add') }}</p>
                                <div class="space-y-1.5">
                                    <template x-for="line in lines" :key="line.key">
                                        <div class="flex items-center gap-3 bg-white rounded-xl px-3 py-2 border border-gold-100">
                                            <div class="w-9 h-9 shrink-0 rounded-lg overflow-hidden bg-cream border border-gold-100">
                                                <img :src="line.image" class="w-full h-full object-cover">
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <p class="text-sm font-medium text-maroon-800 truncate">
                                                    <span x-text="line.name"></span>
                                                    <template x-if="line.label"><span class="text-maroon-400" x-text="'(' + line.label + ')'"></span></template>
                                                    <span class="text-maroon-400" x-text="'× ' + line.quantity"></span>
                                                </p>
                                            </div>
                                            <span class="text-sm font-semibold text-maroon-800 shrink-0" x-text="'₹' + lineTotal(line).toLocaleString('en-IN')"></span>
                                            <button type="button" @click="removeStaged(line.key)" aria-label="{{ __('Remove from list') }}"
                                                    class="shrink-0 text-red-400 hover:text-red-600 transition">✕</button>
                                        </div>
                                    </template>
                                </div>
                                <div class="flex justify-between text-sm font-semibold text-maroon-800 mt-3">
                                    <span>{{ __('Subtotal') }}</span>
                                    <span x-text="'₹' + stagedTotal().toLocaleString('en-IN')"></span>
                                </div>
                            </div>
                        </template>

                        <div class="flex items-center gap-3 mt-5">
                            <button type="button" @click="startConfirm()" :disabled="lines.length === 0"
                                    class="flex-1 bg-pista-600 hover:bg-pista-700 text-white font-semibold rounded-xl py-2.5 transition text-sm disabled:opacity-40 disabled:cursor-not-allowed">
                                {{ __('Review & Add') }} <span x-show="lines.length > 0" x-text="'(' + lines.length + ')'"></span>
                            </button>
                            <button type="button" @click="close()"
                                    class="px-5 bg-white border border-gold-300/70 hover:bg-gold-50 text-maroon-600 font-semibold rounded-xl py-2.5 transition text-sm">
                                {{ __('Cancel') }}
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
@endsection
