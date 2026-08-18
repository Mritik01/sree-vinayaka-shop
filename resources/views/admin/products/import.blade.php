@extends('admin.layout')

@section('title', 'Bulk Upload Products')
@section('page-title', 'Bulk Upload Products')

@section('content')
    <div class="max-w-3xl space-y-5" x-data="productImportForm('{{ route('admin.products.import.store') }}', '{{ route('admin.products.import.template') }}')">
        <div class="bg-white rounded-xl border border-gold-200/60 p-6">
            <p class="font-display font-bold text-maroon-800 text-lg mb-1">1. Choose a category</p>
            <p class="text-sm text-maroon-500 mb-4">
                Every product in this upload — the template and the file you send back — goes into this one
                category. Uploading a different batch for another category? Come back and pick it again.
            </p>
            <select x-model="categoryId" class="w-full sm:w-72 rounded-lg border border-gold-300/70 bg-white px-3 py-2.5 text-sm text-maroon-800 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
                <option value="">Select a category…</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
            @if ($categories->isEmpty())
                <p class="text-sm text-red-600 font-medium mt-3">
                    No active categories yet — create one under <a href="{{ route('admin.categories.index') }}" class="underline">Admin → Categories</a> first.
                </p>
            @endif
        </div>

        <div class="bg-white rounded-xl border border-gold-200/60 p-6">
            <p class="font-display font-bold text-maroon-800 text-lg mb-1">2. Download the template</p>
            <p class="text-sm text-maroon-500 mb-4">
                An .xlsx file with 2 worked examples and a "Read Me" sheet explaining every column. Just 5
                columns — Name, Search Tags, Tag, Description, and the price for 250g (500g/1kg are worked out
                automatically). Every product starts with a placeholder photo; add the real one afterward from
                each product's edit page.
            </p>
            <a :href="templateUrl" class="btn-gold inline-block">⬇️ Download Template</a>
        </div>

        <div class="bg-white rounded-xl border border-gold-200/60 p-6">
            <p class="font-display font-bold text-maroon-800 text-lg mb-1">3. Upload your filled-in file</p>
            <p class="text-sm text-maroon-500 mb-4">
                .xlsx or .xls, up to 5MB. Each row becomes one product in the category chosen above.
            </p>

            {{-- step 1: pick a file --}}
            <div x-show="!uploading && !done" x-cloak>
                <label class="flex flex-col items-center justify-center gap-2 border-2 border-dashed border-gold-300/70 rounded-xl py-10 cursor-pointer hover:border-gold-500 hover:bg-gold-50/40 transition text-center px-4"
                       @dragover.prevent @dragleave.prevent @drop.prevent="onFile($event.dataTransfer.files[0])">
                    <span class="text-3xl">📊</span>
                    <span class="text-sm font-semibold text-maroon-700" x-text="fileName || 'Click to choose a file, or drag it here'"></span>
                    <input type="file" accept=".xlsx,.xls" class="hidden" @change="onFile($event.target.files[0])">
                </label>
                <p x-show="error" x-cloak x-text="error" class="text-sm text-red-600 font-medium mt-3"></p>
                <button type="button" @click="upload()" :disabled="!file || !categoryId"
                        class="btn-maroon mt-4 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-maroon-700">
                    Upload &amp; Import
                </button>
                <p x-show="!categoryId" x-cloak class="text-xs text-maroon-400 mt-2">Choose a category above first.</p>
            </div>

            {{-- step 2: real upload-transfer progress, then an indeterminate bar while the
                 server processes rows (synchronous — no queue worker to poll a job status from,
                 see ProductImportController) --}}
            <div x-show="uploading" x-cloak>
                <p class="text-sm font-semibold text-maroon-700 mb-2">
                    <span x-show="phase === 'uploading'">{{ __('Uploading…') }} <span x-text="progress"></span>%</span>
                    <span x-show="phase === 'processing'" x-cloak>{{ __('Processing your products… this can take a minute for larger files.') }}</span>
                </p>
                <div class="w-full h-2.5 rounded-full bg-gold-100 overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-gold-400 to-gold-600 transition-[width] duration-200 ease-out"
                         :class="phase === 'processing' ? 'animate-pulse' : ''"
                         :style="`width: ${phase === 'uploading' ? progress : 100}%`"></div>
                </div>
            </div>

            {{-- step 3: results --}}
            <div x-show="done" x-cloak>
                <div class="flex items-center gap-6 mb-4">
                    <div>
                        <p class="text-2xl font-bold text-green-600" x-text="created"></p>
                        <p class="text-xs text-maroon-400 uppercase tracking-wide">{{ __('Created') }}</p>
                    </div>
                    <div x-show="failed > 0" x-cloak>
                        <p class="text-2xl font-bold text-red-600" x-text="failed"></p>
                        <p class="text-xs text-maroon-400 uppercase tracking-wide">{{ __('Failed') }}</p>
                    </div>
                </div>

                <div class="max-h-80 overflow-y-auto divide-y divide-gold-100 border border-gold-200/60 rounded-lg">
                    <template x-for="r in results" :key="r.row">
                        <div class="flex items-start gap-2 px-3 py-2 text-sm">
                            <span x-text="r.status === 'created' ? '✅' : '❌'" class="shrink-0"></span>
                            <span class="text-maroon-400 shrink-0">{{ __('Row') }} <span x-text="r.row"></span>:</span>
                            <span class="text-maroon-800 font-medium" x-text="r.name"></span>
                            <span x-show="r.message" x-cloak class="text-red-500" x-text="'— ' + r.message"></span>
                        </div>
                    </template>
                </div>

                <div class="flex gap-2 mt-5">
                    <a href="{{ route('admin.products.index') }}" class="btn-gold">{{ __('View Products') }}</a>
                    <button type="button" @click="reset()" class="btn-maroon">{{ __('Upload Another File') }}</button>
                </div>
            </div>
        </div>
    </div>
@endsection
