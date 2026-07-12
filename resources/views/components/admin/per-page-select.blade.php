@props(['current' => 10, 'options' => [10, 25, 50, 100]])
<label class="flex items-center gap-2 text-sm text-maroon-500">
    {{ __('Show') }}
    <select onchange="const u = new window.URL(window.location.href); u.searchParams.set('per_page', this.value); u.searchParams.delete('page'); window.location = u.toString();"
            class="rounded-lg border border-gold-300/70 bg-white px-2.5 py-1.5 text-sm text-maroon-700 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
        @foreach ($options as $option)
            <option value="{{ $option }}" @selected((int) $current === $option)>{{ $option }}</option>
        @endforeach
    </select>
    {{ __('per page') }}
</label>
