@extends('admin.layout')

@section('title', 'Impersonation Log')
@section('page-title', 'Impersonation Log')

@section('content')
    <p class="text-maroon-500 text-sm -mt-1 mb-4">
        Audit trail of every "Login as Customer" session — who accessed which account, when, and from where.
    </p>

    <div class="bg-white rounded-xl border border-gold-200/60 overflow-hidden">
        @if ($sessions->isEmpty())
            <p class="text-maroon-400 text-sm px-5 py-8 text-center">No impersonation sessions yet.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-maroon-400 border-b border-gold-100">
                            <th class="px-5 py-2.5 font-medium">Admin</th>
                            <th class="px-5 py-2.5 font-medium">Customer</th>
                            <th class="px-5 py-2.5 font-medium">Started</th>
                            <th class="px-5 py-2.5 font-medium">Ended</th>
                            <th class="px-5 py-2.5 font-medium">Reason</th>
                            <th class="px-5 py-2.5 font-medium">IP Address</th>
                            <th class="px-5 py-2.5 font-medium">Device</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sessions as $session)
                            <tr class="border-b border-gold-50 last:border-0 hover:bg-cream/50 transition">
                                <td class="px-5 py-3 text-maroon-800 font-semibold">{{ $session->admin?->name ?? '—' }}</td>
                                <td class="px-5 py-3 text-maroon-600">
                                    @if ($session->user)
                                        <a href="{{ route('admin.customers.show', $session->user) }}" class="hover:text-gold-600 transition">{{ $session->user->name }}</a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-maroon-400">{{ $session->created_at->format('d M Y, h:i A') }}</td>
                                <td class="px-5 py-3 text-maroon-400">
                                    {{ $session->ended_at?->format('d M Y, h:i A') ?? 'Active' }}
                                </td>
                                <td class="px-5 py-3 text-maroon-400">
                                    @if (!$session->ended_at)
                                        <span class="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded-full bg-pista-100 text-pista-700">● Active</span>
                                    @else
                                        <span class="capitalize">{{ $session->end_reason ?? '—' }}</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-maroon-400">{{ $session->ip_address ?? '—' }}</td>
                                <td class="px-5 py-3 text-maroon-400 max-w-[220px] truncate" title="{{ $session->user_agent }}">{{ $session->user_agent ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-5 py-3">
                <x-admin.pagination :paginator="$sessions" />
            </div>
        @endif
    </div>
@endsection
