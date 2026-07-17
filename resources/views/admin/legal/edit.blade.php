@extends('admin.layout')

@section('title', $label)
@section('page-title', $label)

@section('content')
    <a href="{{ route('admin.legal.index') }}" class="text-sm text-maroon-500 hover:text-maroon-700 transition">← Back to Legal Pages</a>

    <div class="bg-white rounded-xl border border-gold-200/60 p-6 max-w-7xl mt-4">
        <form method="POST" action="{{ route('admin.legal.update', $type) }}">
            @csrf
            @method('PUT')
            @include('admin.legal._form')
        </form>
    </div>

    @if ($history->isNotEmpty())
        <div class="bg-white rounded-xl border border-gold-200/60 overflow-hidden max-w-7xl mt-6">
            <div class="px-5 py-4 border-b border-gold-100">
                <p class="font-display text-maroon-800">Version History</p>
            </div>
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-maroon-400 border-b border-gold-100">
                        <th class="px-5 py-2.5 font-medium">Version</th>
                        <th class="px-5 py-2.5 font-medium">Title</th>
                        <th class="px-5 py-2.5 font-medium">Updated By</th>
                        <th class="px-5 py-2.5 font-medium">Published</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($history as $v)
                        <tr class="border-b border-gold-50 last:border-0 {{ $v->id === $current?->id ? 'bg-gold-50/50' : '' }}">
                            <td class="px-5 py-3 text-maroon-800 font-semibold">
                                v{{ $v->version }}
                                @if ($v->id === $current?->id)
                                    <span class="ml-1.5 text-[10px] font-semibold uppercase tracking-wide text-pista-600 bg-pista-100 rounded-full px-2 py-0.5">Current</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-maroon-600">{{ $v->title }}</td>
                            <td class="px-5 py-3 text-maroon-500">{{ $v->admin->name ?? 'System' }}</td>
                            <td class="px-5 py-3 text-maroon-400">{{ $v->published_at->format('d M Y, h:i A') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
