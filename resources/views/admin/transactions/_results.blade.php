@if ($transactions->isEmpty())
    <p class="text-maroon-400 text-sm px-5 py-8 text-center">
        {{ $search !== '' ? 'No transactions match "'.$search.'".' : 'No transactions yet.' }}
    </p>
@else
    <table class="w-full text-sm">
        <thead>
            <tr class="text-left text-maroon-400 border-b border-gold-100">
                <th class="px-5 py-2.5 font-medium">Order</th>
                <th class="px-5 py-2.5 font-medium">Placed</th>
                <th class="px-5 py-2.5 font-medium">Customer</th>
                <th class="px-5 py-2.5 font-medium">Amount</th>
                <th class="px-5 py-2.5 font-medium">Payment</th>
                <th class="px-5 py-2.5 font-medium">Transaction ID</th>
                <th class="px-5 py-2.5 font-medium">Paid At</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($transactions as $order)
                @php
                    $isCod = $order->payment_method !== 'razorpay';
                    $statusBadge = $isCod
                        ? ['bg-gold-100 text-gold-600 border-gold-300/60', __('Cash on Delivery')]
                        : match ($order->payment_status) {
                            'paid' => ['bg-pista-100 text-pista-600 border-pista-400/40', __('Paid')],
                            'failed' => ['bg-red-50 text-red-600 border-red-200', __('Failed')],
                            default => ['bg-gold-100 text-gold-600 border-gold-300/60', __('Awaiting Payment')],
                        };
                @endphp
                <tr class="border-b border-gold-50 last:border-0 hover:bg-cream/50 transition">
                    <td class="px-5 py-3 text-maroon-800 font-semibold">
                        <a href="{{ route('admin.orders.show', $order) }}" class="hover:text-gold-600 transition">{{ $order->orderNumber() }}</a>
                        @if ($order->is_gift_order)
                            <span class="ml-1" title="Free gift claimed">🎁</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-maroon-500">{{ $order->created_at->format('d M, h:i A') }}</td>
                    <td class="px-5 py-3 text-maroon-600">
                        {{ $order->customer_name }}
                        <span class="block text-maroon-400 text-xs">{{ $order->customer_phone }}</span>
                    </td>
                    <td class="px-5 py-3 text-maroon-800 font-medium">₹{{ number_format($order->total) }}</td>
                    <td class="px-5 py-3">
                        <span class="inline-block text-xs font-semibold px-2.5 py-1 rounded-full border {{ $statusBadge[0] }}">{{ $statusBadge[1] }}</span>
                        @if ($order->refund_status !== 'none')
                            <span class="block text-[10px] text-maroon-400 mt-1">↩️ ₹{{ number_format($order->refunded_amount) }} {{ $order->refund_status === 'full' ? __('refunded') : __('refunded (partial)') }}</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-xs font-mono">
                        @if ($order->razorpay_order_id)
                            <span class="block font-semibold text-maroon-800" title="Our own reference for this payment — search by this instead of Razorpay's id">{{ $order->transactionId() }}</span>
                            <span class="block text-maroon-400 mt-0.5" title="Razorpay reference (for support/refund lookups)">{{ $order->razorpay_payment_id ?: $order->razorpay_order_id }}</span>
                        @else
                            <span class="text-maroon-300">—</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-maroon-500">
                        {{ $order->paid_at?->format('d M, h:i A') ?? '—' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <x-admin.pagination :paginator="$transactions" />
@endif
