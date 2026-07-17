@if ($errors->any())
    <div class="mb-5 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3">
        <ul class="list-disc list-inside space-y-0.5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div x-data="legalDocumentForm({
        title: {{ Illuminate\Support\Js::from(old('title', $current->title ?? $label)) }},
        content: {{ Illuminate\Support\Js::from(old('content', $current->content ?? '')) }},
     })" x-init="initEditor()"
     class="grid lg:grid-cols-[1fr_460px] gap-10 items-start">

    <div class="max-w-2xl">
        <div>
            <label class="block text-sm font-medium text-maroon-700 mb-1.5">Page Title</label>
            <input type="text" name="title" x-model="title" maxlength="150" required
                   class="w-full rounded-lg border border-gold-300/70 px-3.5 py-2.5 text-maroon-800 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
        </div>

        <div class="mt-5">
            <label class="block text-sm font-medium text-maroon-700 mb-1.5">Content</label>
            <div class="rounded-lg border border-gold-300/70 overflow-hidden bg-white">
                <div x-ref="editor" style="min-height: 420px;"></div>
            </div>
            <input type="hidden" name="content" x-model="content">
            <p class="text-xs text-maroon-400 mt-1.5">Use headings to separate sections (Introduction, Payments, Refunds, etc.) — they're pulled straight into the styled page you see in the preview.</p>
        </div>

        <div class="mt-7 flex items-center gap-3">
            <button type="submit" class="btn-gold">Publish New Version</button>
            <p class="text-xs text-maroon-400">Saving creates a new version — existing customers will be asked to re-accept it.</p>
        </div>
    </div>

    {{-- live preview — the exact same partial the public page and the signup modal render --}}
    <div class="lg:sticky lg:top-6">
        <p class="text-xs font-semibold uppercase tracking-wide text-maroon-400 mb-2">👀 Live Preview</p>
        <div class="rounded-2xl bg-cream border border-gold-200/60 p-6 max-h-[70vh] overflow-y-auto">
            @include('partials.legal-document-content')
        </div>
        <p class="text-xs text-maroon-400 mt-2">This is exactly how it will render on the public page and in the signup modal, with today's date as "Last Updated."</p>
    </div>
</div>
