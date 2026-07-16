@if ($leads->isEmpty())
    <p class="text-maroon-400 text-sm px-5 py-8 text-center">
        {{ $search !== '' ? 'No leads match "'.$search.'".' : 'No responses yet — they\'ll show up here once a visitor completes the popup.' }}
    </p>
@else
    <table class="w-full text-sm">
        <thead>
            <tr class="text-left text-maroon-400 border-b border-gold-100">
                <th class="px-5 py-2.5 font-medium">Name</th>
                <th class="px-5 py-2.5 font-medium">Phone</th>
                <th class="px-5 py-2.5 font-medium">Submitted</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($leads as $lead)
                <tr class="border-b border-gold-50 last:border-0 hover:bg-cream/50 transition">
                    <td class="px-5 py-3 text-maroon-800 font-semibold">{{ $lead->name }}</td>
                    <td class="px-5 py-3 text-maroon-600">
                        <a href="tel:{{ $lead->phone }}" class="hover:text-gold-600 transition">{{ $lead->phone }}</a>
                    </td>
                    <td class="px-5 py-3 text-maroon-400">{{ $lead->verified_at?->format('d M Y, h:i A') ?? '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <x-admin.pagination :paginator="$leads" />
@endif
