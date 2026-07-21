@if ($customers->isEmpty())
    <p class="text-maroon-400 text-sm px-5 py-8 text-center">
        {{ $search !== '' ? 'No customers match "'.$search.'".' : 'No customers yet.' }}
    </p>
@else
    <table class="w-full text-sm">
        <thead>
            <tr class="text-left text-maroon-400 border-b border-gold-100">
                <th class="px-5 py-2.5 font-medium">Customer</th>
                <th class="px-5 py-2.5 font-medium">Phone</th>
                <th class="px-5 py-2.5 font-medium">Orders</th>
                <th class="px-5 py-2.5 font-medium">Total Spent</th>
                <th class="px-5 py-2.5 font-medium">Last Address</th>
                <th class="px-5 py-2.5 font-medium">Customer Since</th>
                <th class="px-5 py-2.5 font-medium text-right">Notify</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($customers as $i => $customer)
                <tr class="border-b border-gold-50 last:border-0 hover:bg-cream/50 transition cursor-pointer animate-fade-up"
                    style="animation-delay: {{ min($i, 12) * 40 }}ms"
                    onclick="window.location='{{ route('admin.customers.show', $customer) }}'">
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 shrink-0 rounded-full bg-maroon-700 text-cream flex items-center justify-center font-display font-bold text-sm">
                                {{ strtoupper(substr($customer->name, 0, 1)) }}
                            </div>
                            <div class="min-w-0">
                                <p class="text-maroon-800 font-medium truncate flex items-center gap-1.5">
                                    {{ $customer->name }}
                                    @if ($customer->is_blocked)
                                        <span class="text-[10px] font-bold uppercase tracking-wide px-1.5 py-0.5 rounded-full bg-red-50 text-red-600 border border-red-200 shrink-0">🚫 Blocked</span>
                                    @endif
                                </p>
                                <p class="text-xs space-x-1">
                                    @if ($mvp['today'] && $mvp['today']['user']->id === $customer->id)<span title="Today's MVP">🏆</span>@endif
                                    @if ($mvp['week'] && $mvp['week']['user']->id === $customer->id)<span title="This Week's MVP">👑</span>@endif
                                    @if ($mvp['allTime'] && $mvp['allTime']['user']->id === $customer->id)<span title="All-Time Legend">💎</span>@endif
                                    @if ($customer->cod_blocked_orders > 0)<span title="COD blocked for next {{ $customer->cod_blocked_orders }} order(s)">⚠️</span>@endif
                                </p>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-3 text-maroon-600">{{ $customer->phone }}</td>
                    <td class="px-5 py-3 text-maroon-500">{{ $customer->total_orders }}</td>
                    <td class="px-5 py-3 text-maroon-800 font-medium">₹{{ number_format($customer->total_spent) }}</td>
                    <td class="px-5 py-3 text-maroon-500 max-w-[220px] truncate" title="{{ $customer->latestOrder->delivery_address ?? '' }}">
                        {{ $customer->latestOrder->delivery_address ?? '—' }}
                    </td>
                    <td class="px-5 py-3 text-maroon-400">{{ $customer->created_at->format('d M Y') }}</td>
                    <td class="px-5 py-3 text-right">
                        <button type="button" title="Send notification to {{ $customer->name }}" aria-label="Send notification to {{ $customer->name }}"
                                onclick='event.stopPropagation(); window.dispatchEvent(new CustomEvent("open-notify-modal", { detail: { id: {{ $customer->id }}, name: @json($customer->name) } }))'
                                class="w-8 h-8 rounded-full inline-flex items-center justify-center text-maroon-400 hover:text-maroon-800 hover:bg-gold-100 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.7">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                            </svg>
                        </button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <x-admin.pagination :paginator="$customers" />
@endif
