@if ($errors->any())
    <div class="mb-5 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3">
        <ul class="list-disc list-inside space-y-0.5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div>
    <label class="block text-sm font-medium text-maroon-700 mb-1.5">Name</label>
    <input type="text" name="name" value="{{ old('name', $tag->name ?? '') }}" required maxlength="60" placeholder="e.g. Snacks"
           class="w-full rounded-lg border border-gold-300/70 px-3.5 py-2.5 text-maroon-800 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
</div>

<div class="mt-7 flex items-center gap-3">
    <button type="submit" class="btn-gold">{{ isset($tag) ? 'Save Changes' : 'Create Tag' }}</button>
    <a href="{{ route('admin.product-tags.index') }}" class="text-maroon-500 hover:text-maroon-700 text-sm font-medium">Cancel</a>
</div>
