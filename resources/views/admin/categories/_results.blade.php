@if ($categories->isEmpty())
    <p class="text-maroon-400 text-sm px-5 py-8 text-center">
        {{ $search !== '' ? 'No categories match "'.$search.'".' : 'No categories yet — add your first one.' }}
    </p>
@else
    <table class="w-full text-sm">
        <thead>
            <tr class="text-left text-maroon-400 border-b border-gold-100">
                <th class="px-5 py-2.5 font-medium"></th>
                <th class="px-5 py-2.5 font-medium">Name</th>
                <th class="px-5 py-2.5 font-medium">Products</th>
                <th class="px-5 py-2.5 font-medium">Sort</th>
                <th class="px-5 py-2.5 font-medium">Active</th>
                <th class="px-5 py-2.5 font-medium text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($categories as $category)
                <tr class="border-b border-gold-50 last:border-0 hover:bg-cream/50 transition cursor-pointer"
                    ondblclick="window.location.href='{{ route('admin.categories.edit', $category) }}'">
                    <td class="px-5 py-3">
                        @if ($category->image_path)
                            <img src="{{ asset($category->image_path) }}" alt="{{ $category->name }}" class="w-11 h-11 rounded-full object-cover border border-gold-200/60">
                        @else
                            <span class="w-11 h-11 rounded-full bg-gold-100 border border-gold-300/60 flex items-center justify-center font-display font-bold text-gold-600">
                                {{ mb_strtoupper(mb_substr($category->name, 0, 1)) }}
                            </span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-maroon-800 font-medium">{{ $category->name }}</td>
                    <td class="px-5 py-3 text-maroon-500">{{ $category->products_count }}</td>
                    <td class="px-5 py-3 text-maroon-500">{{ $category->sort_order }}</td>
                    <td class="px-5 py-3" ondblclick="event.stopPropagation()">
                        <div x-data="settingToggle({{ $category->is_active ? 'true' : 'false' }}, '{{ route('admin.categories.toggle', $category) }}')">
                            <label class="relative inline-flex items-center cursor-pointer shrink-0" :class="updating && 'opacity-60 pointer-events-none'">
                                <input type="checkbox" class="sr-only peer" :checked="on" @change="toggle()">
                                <div class="w-11 h-6 rounded-full transition-colors duration-300 bg-gray-300 peer-checked:bg-pista-500"></div>
                                <div class="absolute left-1 top-1 w-4 h-4 bg-white rounded-full shadow-md transition-transform duration-300 peer-checked:translate-x-5"></div>
                            </label>
                        </div>
                    </td>
                    <td class="px-5 py-3 text-right space-x-3" ondblclick="event.stopPropagation()">
                        <a href="{{ route('admin.categories.edit', $category) }}" class="text-gold-600 hover:text-gold-700 font-medium">Edit</a>
                        <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" class="inline" onsubmit="return confirm('Delete {{ $category->name }}? Products in it are not deleted, just untagged.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-500 hover:text-red-600 font-medium">Delete</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <x-admin.pagination :paginator="$categories" />
@endif
