@php $deviceIcons = ['mobile' => '📱', 'tablet' => '📟', 'desktop' => '💻']; @endphp
@if ($visits->isEmpty())
    <p class="text-maroon-400 text-sm px-5 py-8 text-center">
        {{ $search !== '' ? 'No visits match "'.$search.'".' : 'No visits recorded yet.' }}
    </p>
@else
    <table class="w-full text-sm">
        <thead>
            <tr class="text-left text-maroon-400 border-b border-gold-100">
                <th class="px-5 py-2.5 font-medium">Visitor</th>
                <th class="px-5 py-2.5 font-medium">Device</th>
                <th class="px-5 py-2.5 font-medium">Browser · OS</th>
                <th class="px-5 py-2.5 font-medium">Entry Page</th>
                <th class="px-5 py-2.5 font-medium">Pages Viewed</th>
                <th class="px-5 py-2.5 font-medium">First Seen</th>
                <th class="px-5 py-2.5 font-medium">Last Seen</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($visits as $i => $visit)
                @php $isOnline = $visit->last_seen->gt(now()->subMinutes(5)); @endphp
                <tr class="border-b border-gold-50 last:border-0 hover:bg-cream/50 transition animate-fade-up {{ $visit->user ? 'cursor-pointer' : '' }}"
                    style="animation-delay: {{ min($i, 12) * 40 }}ms"
                    @if ($visit->user) onclick="window.location='{{ route('admin.customers.show', $visit->user) }}'" @endif>
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-2">
                            @if ($isOnline)
                                <span class="relative flex w-2 h-2 shrink-0">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-pista-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-pista-500"></span>
                                </span>
                            @endif
                            @if ($visit->user)
                                <span class="text-maroon-800 font-medium">{{ $visit->user->name }}</span>
                                <span class="text-maroon-400 text-xs">{{ $visit->user->phone }}</span>
                            @else
                                <span class="text-maroon-500">👻 Guest</span>
                            @endif
                        </div>
                    </td>
                    <td class="px-5 py-3 text-maroon-600">
                        {{ $deviceIcons[$visit->device_type] ?? '❓' }} {{ ucfirst($visit->device_type ?? 'Unknown') }}
                    </td>
                    <td class="px-5 py-3 text-maroon-500">{{ $visit->browser ?? '—' }} · {{ $visit->platform ?? '—' }}</td>
                    <td class="px-5 py-3 text-maroon-500 max-w-[200px] truncate" title="{{ $visit->entry_path }}">{{ $visit->entry_path ?? '—' }}</td>
                    <td class="px-5 py-3 text-maroon-800 font-medium">{{ $visit->page_views }}</td>
                    <td class="px-5 py-3 text-maroon-400">{{ $visit->first_seen->format('d M Y, h:i A') }}</td>
                    <td class="px-5 py-3 text-maroon-400">{{ $visit->last_seen->diffForHumans() }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <x-admin.pagination :paginator="$visits" />
@endif
