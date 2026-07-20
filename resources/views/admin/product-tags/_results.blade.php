@if ($tags->isEmpty())
    <p class="text-maroon-400 text-sm px-5 py-8 text-center">
        {{ $search !== '' ? 'No tags match "'.$search.'".' : 'No tags yet — add your first one.' }}
    </p>
@else
    <table class="w-full text-sm">
        <thead>
            <tr class="text-left text-maroon-400 border-b border-gold-100">
                <th class="px-5 py-2.5 font-medium">Name</th>
                <th class="px-5 py-2.5 font-medium">Products</th>
                <th class="px-5 py-2.5 font-medium text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($tags as $tag)
                <tr class="border-b border-gold-50 last:border-0 hover:bg-cream/50 transition cursor-pointer"
                    ondblclick="window.location.href='{{ route('admin.product-tags.edit', $tag) }}'">
                    <td class="px-5 py-3 text-maroon-800 font-medium">🔖 {{ $tag->name }}</td>
                    <td class="px-5 py-3 text-maroon-500">{{ $tag->products_count }}</td>
                    <td class="px-5 py-3 text-right space-x-3" ondblclick="event.stopPropagation()">
                        <a href="{{ route('admin.product-tags.edit', $tag) }}" class="text-gold-600 hover:text-gold-700 font-medium">Edit</a>
                        <form action="{{ route('admin.product-tags.destroy', $tag) }}" method="POST" class="inline" onsubmit="return confirm('Delete the tag {{ $tag->name }}? It will be removed from any products and Featured Categories using it.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-500 hover:text-red-600 font-medium">Delete</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <x-admin.pagination :paginator="$tags" />
@endif
