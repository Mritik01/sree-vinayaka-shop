@if ($products->isEmpty())
    <p class="text-maroon-400 text-sm px-5 py-8 text-center">
        {{ $search !== '' ? 'No products match "'.$search.'".' : 'No products yet — add your first one.' }}
    </p>
@else
    <table class="w-full text-sm">
        <thead>
            <tr class="text-left text-maroon-400 border-b border-gold-100">
                <th class="px-5 py-2.5 font-medium"></th>
                <th class="px-5 py-2.5 font-medium">Name</th>
                <th class="px-5 py-2.5 font-medium">Category</th>
                <th class="px-5 py-2.5 font-medium">Price</th>
                <th class="px-5 py-2.5 font-medium">Weight</th>
                <th class="px-5 py-2.5 font-medium">Sort</th>
                <th class="px-5 py-2.5 font-medium text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($products as $product)
                <tr class="border-b border-gold-50 last:border-0 hover:bg-cream/50 transition cursor-pointer"
                    ondblclick="window.location.href='{{ route('admin.products.edit', $product) }}'">
                    <td class="px-5 py-3">
                        <img src="{{ asset($product->image) }}" alt="{{ $product->name }}" style="object-position: {{ $product->image_position ?? '50% 50%' }};" class="w-11 h-11 rounded-lg object-cover border border-gold-200/60">
                    </td>
                    <td class="px-5 py-3 text-maroon-800 font-medium">{{ $product->name }}</td>
                    <td class="px-5 py-3 text-maroon-500">{{ $product->category }}</td>
                    <td class="px-5 py-3 text-maroon-800">
                        ₹{{ $product->price }}{{ $product->isLoose() ? '/250g' : '' }}
                        @if ($product->isLoose())
                            <span class="ml-1.5 text-[10px] font-semibold uppercase tracking-wide px-1.5 py-0.5 rounded-full bg-gold-100 text-gold-700 border border-gold-300/60">Loose</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-maroon-500">{{ $product->weight }}</td>
                    <td class="px-5 py-3 text-maroon-500">{{ $product->sort_order }}</td>
                    <td class="px-5 py-3 text-right space-x-3" ondblclick="event.stopPropagation()">
                        <a href="{{ route('admin.products.edit', $product) }}" class="text-gold-600 hover:text-gold-700 font-medium">Edit</a>
                        <form action="{{ route('admin.products.destroy', $product) }}" method="POST" class="inline" onsubmit="return confirm('Delete {{ $product->name }}? This cannot be undone.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-500 hover:text-red-600 font-medium">Delete</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <x-admin.pagination :paginator="$products" />
@endif
