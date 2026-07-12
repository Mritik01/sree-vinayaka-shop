@if ($coupons->isEmpty())
    <p class="text-maroon-400 text-sm px-5 py-8 text-center">
        {{ $search !== '' ? 'No coupons match "'.$search.'".' : 'No coupons yet — add your first one.' }}
    </p>
@else
    <table class="w-full text-sm">
        <thead>
            <tr class="text-left text-maroon-400 border-b border-gold-100">
                <th class="px-5 py-2.5 font-medium">Code</th>
                <th class="px-5 py-2.5 font-medium">Discount</th>
                <th class="px-5 py-2.5 font-medium">Usage Type</th>
                <th class="px-5 py-2.5 font-medium">Assigned To</th>
                <th class="px-5 py-2.5 font-medium">Redeemed</th>
                <th class="px-5 py-2.5 font-medium">Expires</th>
                <th class="px-5 py-2.5 font-medium">Status</th>
                <th class="px-5 py-2.5 font-medium text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($coupons as $coupon)
                <tr class="border-b border-gold-50 last:border-0 hover:bg-cream/50 transition">
                    <td class="px-5 py-3 text-maroon-800 font-semibold tracking-wide">
                        <a href="{{ route('admin.coupons.show', $coupon) }}" class="hover:text-gold-600 transition">{{ $coupon->code }}</a>
                    </td>
                    <td class="px-5 py-3 text-maroon-600">
                        {{ $coupon->discount_type === 'percent' ? $coupon->discount_value . '%' : '₹' . $coupon->discount_value }}
                    </td>
                    <td class="px-5 py-3 text-maroon-500">{{ $coupon->usage_type === 'single_use' ? 'Single use (once, ever)' : 'Once per user' }}</td>
                    <td class="px-5 py-3">
                        @if ($coupon->isMasterCoupon())
                            <span class="inline-block text-xs font-semibold px-2.5 py-1 rounded-full border bg-gold-100 text-gold-700 border-gold-300/60">🚀 Master: {{ $coupon->assignedUsers->count() }}/{{ $coupon->auto_assign_limit }}</span>
                        @elseif ($coupon->assignedUsers->isEmpty())
                            <span class="inline-block text-xs font-medium px-2.5 py-1 rounded-full border bg-cream text-maroon-500 border-gold-200/60">All customers</span>
                        @else
                            <div class="flex flex-wrap gap-1.5 max-w-[220px]">
                                @foreach ($coupon->assignedUsers->take(2) as $assignee)
                                    <a href="{{ route('admin.customers.show', $assignee) }}"
                                       class="inline-block text-xs font-semibold px-2.5 py-1 rounded-full border bg-gold-100 text-gold-700 border-gold-300/60 hover:bg-gold-200 transition">
                                        {{ $assignee->name }}
                                    </a>
                                @endforeach
                                @if ($coupon->assignedUsers->count() > 2)
                                    <span class="inline-block text-xs font-medium px-2 py-1 text-maroon-400" title="{{ $coupon->assignedUsers->skip(2)->pluck('name')->join(', ') }}">
                                        +{{ $coupon->assignedUsers->count() - 2 }} more
                                    </span>
                                @endif
                            </div>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-maroon-500">{{ $coupon->redeemers_count }}</td>
                    <td class="px-5 py-3 {{ $coupon->isExpired() ? 'text-red-500' : 'text-maroon-500' }}">
                        {{ $coupon->expires_at->format('d M Y') }}
                    </td>
                    <td class="px-5 py-3">
                        @if ($coupon->isExpired())
                            <span class="inline-block text-xs font-semibold px-2.5 py-1 rounded-full border bg-red-50 text-red-600 border-red-200">Expired</span>
                        @elseif ($coupon->is_active)
                            <span class="inline-block text-xs font-semibold px-2.5 py-1 rounded-full border bg-pista-100 text-pista-600 border-pista-400/40">Active</span>
                        @else
                            <span class="inline-block text-xs font-semibold px-2.5 py-1 rounded-full border bg-gold-100 text-gold-600 border-gold-300/60">Inactive</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-right space-x-3">
                        <a href="{{ route('admin.coupons.show', $coupon) }}" class="text-maroon-500 hover:text-maroon-700 font-medium">View</a>
                        <a href="{{ route('admin.coupons.edit', $coupon) }}" class="text-gold-600 hover:text-gold-700 font-medium">Edit</a>
                        <form action="{{ route('admin.coupons.destroy', $coupon) }}" method="POST" class="inline" onsubmit="return confirm('Delete coupon {{ $coupon->code }}?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-500 hover:text-red-600 font-medium">Delete</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <x-admin.pagination :paginator="$coupons" />
@endif
