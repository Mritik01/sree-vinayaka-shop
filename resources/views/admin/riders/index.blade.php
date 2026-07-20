@extends('admin.layout')

@section('title', 'Delivery Riders')
@section('page-title', 'Delivery Riders')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <p class="text-sm text-maroon-500">Riders log in separately at <a href="{{ route('rider.login') }}" target="_blank" class="text-gold-600 hover:text-gold-700 underline underline-offset-2">{{ route('rider.login') }}</a></p>
        <a href="{{ route('admin.riders.create') }}" class="btn-gold">+ Add Rider</a>
    </div>

    <div class="bg-white rounded-xl border border-gold-200/60 overflow-hidden">
        @if ($riders->isEmpty())
            <p class="text-maroon-400 text-sm px-5 py-8 text-center">No riders yet — add your first delivery rider.</p>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-maroon-400 border-b border-gold-100">
                        <th class="px-5 py-2.5 font-medium"></th>
                        <th class="px-5 py-2.5 font-medium">Name</th>
                        <th class="px-5 py-2.5 font-medium">Username</th>
                        <th class="px-5 py-2.5 font-medium">Phone</th>
                        <th class="px-5 py-2.5 font-medium text-center">Active</th>
                        <th class="px-5 py-2.5 font-medium text-center">Delivered</th>
                        <th class="px-5 py-2.5 font-medium text-center">Cancelled</th>
                        <th class="px-5 py-2.5 font-medium text-center">Total</th>
                        <th class="px-5 py-2.5 font-medium text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($riders as $rider)
                        <tr class="border-b border-gold-50 last:border-0 hover:bg-cream/50 transition cursor-pointer"
                            onclick="window.location='{{ route('admin.riders.show', $rider) }}'">
                            <td class="px-5 py-3">
                                @if ($rider->photo_path)
                                    <img src="{{ asset($rider->photo_path) }}" alt="{{ $rider->name }}" class="w-9 h-9 rounded-full object-cover border border-gold-200/60">
                                @else
                                    <span class="w-9 h-9 rounded-full bg-gold-100 border border-gold-300/60 flex items-center justify-center font-display font-bold text-xs text-gold-600">
                                        {{ mb_strtoupper(mb_substr($rider->name, 0, 1)) }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-maroon-800 font-semibold">
                                <a href="{{ route('admin.riders.show', $rider) }}" class="hover:text-gold-600 transition">{{ $rider->name }}</a>
                            </td>
                            <td class="px-5 py-3 text-maroon-600">{{ $rider->username }}</td>
                            <td class="px-5 py-3 text-maroon-500">{{ $rider->phone ?: '—' }}</td>
                            <td class="px-5 py-3 text-center">
                                @if ($rider->active_orders_count > 0)
                                    <span class="inline-block text-xs font-semibold px-2.5 py-1 rounded-full border bg-sky-50 text-sky-600 border-sky-200">{{ $rider->active_orders_count }}</span>
                                @else
                                    <span class="text-maroon-300">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-center text-maroon-700 font-medium">{{ $rider->delivered_orders_count }}</td>
                            <td class="px-5 py-3 text-center text-maroon-400">{{ $rider->cancelled_orders_count }}</td>
                            <td class="px-5 py-3 text-center text-maroon-800 font-semibold">{{ $rider->total_orders_count }}</td>
                            <td class="px-5 py-3 text-right space-x-3" onclick="event.stopPropagation()">
                                <a href="{{ route('admin.riders.show', $rider) }}" class="text-maroon-600 hover:text-maroon-800 font-medium">View</a>
                                <a href="{{ route('admin.riders.edit', $rider) }}" class="text-gold-600 hover:text-gold-700 font-medium">Edit</a>
                                <form action="{{ route('admin.riders.destroy', $rider) }}" method="POST" class="inline" onsubmit="return confirm('Remove rider {{ $rider->name }}?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-600 font-medium">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
