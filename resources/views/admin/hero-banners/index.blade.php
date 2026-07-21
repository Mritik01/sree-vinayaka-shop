@extends('admin.layout')

@section('title', 'Hero Banners')
@section('page-title', 'Hero Banners')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <p class="text-sm text-maroon-500">These slides rotate in the big banner at the top of the homepage. Lower sort numbers show first.</p>
        <a href="{{ route('admin.hero-banners.create') }}" class="btn-gold">+ Add Banner</a>
    </div>

    <div class="bg-white rounded-xl border border-gold-200/60 overflow-hidden">
        @if ($banners->isEmpty())
            <p class="text-maroon-400 text-sm px-5 py-8 text-center">No banners yet — add your first one. The homepage hero stays hidden until at least one banner is active.</p>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-maroon-400 border-b border-gold-100">
                        <th class="px-5 py-2.5 font-medium">Banner</th>
                        <th class="px-5 py-2.5 font-medium">Title</th>
                        <th class="px-5 py-2.5 font-medium">Button</th>
                        <th class="px-5 py-2.5 font-medium">Sort</th>
                        <th class="px-5 py-2.5 font-medium">Active</th>
                        <th class="px-5 py-2.5 font-medium text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($banners as $banner)
                        <tr class="border-b border-gold-50 last:border-0 hover:bg-cream/50 transition cursor-pointer"
                            ondblclick="window.location.href='{{ route('admin.hero-banners.edit', $banner) }}'">
                            <td class="px-5 py-3">
                                <img src="{{ asset($banner->image_path) }}" alt="{{ $banner->title ?: 'Hero banner' }}" class="w-32 h-14 rounded-lg object-cover border border-gold-200/60">
                            </td>
                            <td class="px-5 py-3 text-maroon-800 font-medium max-w-[220px]">
                                <p class="truncate {{ $banner->title ? '' : 'text-maroon-300 italic font-normal' }}">{{ $banner->title ?: 'Image only — no title' }}</p>
                                @if ($banner->eyebrow)
                                    <p class="text-xs text-maroon-400 truncate">{{ $banner->eyebrow }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-maroon-500 max-w-[160px] truncate">{{ $banner->button_text ?: '—' }}</td>
                            <td class="px-5 py-3 text-maroon-500">{{ $banner->sort_order }}</td>
                            <td class="px-5 py-3" ondblclick="event.stopPropagation()">
                                <div x-data="settingToggle({{ $banner->is_active ? 'true' : 'false' }}, '{{ route('admin.hero-banners.toggle', $banner) }}')">
                                    <label class="relative inline-flex items-center cursor-pointer shrink-0" :class="updating && 'opacity-60 pointer-events-none'">
                                        <input type="checkbox" class="sr-only peer" :checked="on" @change="toggle()">
                                        <div class="w-11 h-6 rounded-full transition-colors duration-300 bg-gray-300 peer-checked:bg-pista-500"></div>
                                        <div class="absolute left-1 top-1 w-4 h-4 bg-white rounded-full shadow-md transition-transform duration-300 peer-checked:translate-x-5"></div>
                                    </label>
                                </div>
                            </td>
                            <td class="px-5 py-3 text-right space-x-3" ondblclick="event.stopPropagation()">
                                <a href="{{ route('admin.hero-banners.edit', $banner) }}" class="text-gold-600 hover:text-gold-700 font-medium">Edit</a>
                                <form action="{{ route('admin.hero-banners.destroy', $banner) }}" method="POST" class="inline" onsubmit="return confirm('Delete this banner?');">
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
