@if ($subscribers->isEmpty())
    <p class="text-maroon-400 text-sm px-5 py-8 text-center">
        {{ $search !== '' ? 'No subscribers match "'.$search.'".' : 'No signups yet — they\'ll show up here once a visitor joins through the homepage form.' }}
    </p>
@else
    <table class="w-full text-sm">
        <thead>
            <tr class="text-left text-maroon-400 border-b border-gold-100">
                <th class="px-5 py-2.5 font-medium">Email</th>
                <th class="px-5 py-2.5 font-medium">Subscribed</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($subscribers as $subscriber)
                <tr class="border-b border-gold-50 last:border-0 hover:bg-cream/50 transition">
                    <td class="px-5 py-3 text-maroon-800 font-semibold">
                        <a href="mailto:{{ $subscriber->email }}" class="hover:text-gold-600 transition">{{ $subscriber->email }}</a>
                    </td>
                    <td class="px-5 py-3 text-maroon-400">{{ $subscriber->created_at->format('d M Y, h:i A') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <x-admin.pagination :paginator="$subscribers" />
@endif
