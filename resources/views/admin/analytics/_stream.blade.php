@php
    $eventIcons = [
        'page_view' => '📄', 'product_view' => '👁️', 'add_to_cart' => '🛒', 'session_end' => '🚪',
        'favorite_add' => '❤️', 'favorite_remove' => '💔', 'review_submitted' => '⭐', 'coupon_applied' => '🏷️',
        'login' => '🔑', 'gift_claimed' => '🎁', 'order_placed' => '🧾', 'payment_captured' => '💳', 'order_cancelled' => '❌',
    ];
@endphp
@if ($activities->isEmpty())
    <p class="text-maroon-400 text-sm px-5 py-8 text-center">
        {{ $search !== '' ? 'No activity matches "'.$search.'".' : 'No activity recorded yet.' }}
    </p>
@else
    <table class="w-full text-sm">
        <thead>
            <tr class="text-left text-maroon-400 border-b border-gold-100">
                <th class="px-5 py-2.5 font-medium">Who</th>
                <th class="px-5 py-2.5 font-medium">Event</th>
                <th class="px-5 py-2.5 font-medium">Detail</th>
                <th class="px-5 py-2.5 font-medium">Device / IP</th>
                <th class="px-5 py-2.5 font-medium">Location</th>
                <th class="px-5 py-2.5 font-medium">When</th>
            </tr>
        </thead>
        <tbody>
            {{-- AnalyticsController::index() filters to whereNotNull('user_id'), so $activity->user
                 is always present here — guest/anonymous activity is deliberately excluded from
                 this page (see VisitorController's Visitors page for guest-inclusive traffic data) --}}
            @foreach ($activities as $i => $activity)
                <tr class="border-b border-gold-50 last:border-0 hover:bg-cream/50 transition animate-fade-up cursor-pointer"
                    style="animation-delay: {{ min($i, 12) * 30 }}ms"
                    onclick="window.location='{{ route('admin.customers.show', $activity->user) }}'">
                    <td class="px-5 py-3">
                        <span class="text-maroon-800 font-medium">{{ $activity->user->name }}</span>
                        <span class="text-maroon-400 text-xs block">{{ $activity->user->phone }}</span>
                    </td>
                    <td class="px-5 py-3 text-maroon-700 whitespace-nowrap">
                        {{ $eventIcons[$activity->event] ?? (str_starts_with($activity->event, 'click:') ? '🖱️' : '•') }}
                        {{ Str::of(str_replace(['click:', '_'], ['', ' '], $activity->event))->title() }}
                    </td>
                    <td class="px-5 py-3 text-maroon-500 max-w-[220px] truncate" title="{{ $activity->label ?? $activity->path }}">
                        {{ $activity->label ?? $activity->path ?? '—' }}
                    </td>
                    <td class="px-5 py-3 text-maroon-500 whitespace-nowrap">
                        {{ ucfirst($activity->device_type ?? 'Unknown') }} · {{ $activity->ip_address ?? '—' }}
                    </td>
                    <td class="px-5 py-3 text-maroon-500 whitespace-nowrap">
                        {{ $activity->city ? $activity->city.', '.$activity->country : ($activity->country ?? '—') }}
                    </td>
                    <td class="px-5 py-3 text-maroon-400 whitespace-nowrap">{{ $activity->created_at->diffForHumans() }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <x-admin.pagination :paginator="$activities" />
@endif
