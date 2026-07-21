{{-- Shared markup for the public /terms and /privacy pages, the site-wide legal document modal
     (partials/legal-document-modal.blade.php), AND the admin live-preview pane
     (resources/views/admin/legal/_form.blade.php). All three callers must expose the same
     Alpine scope — title, content (HTML), updatedAt — so this partial never needs to know
     which one it's in (same pattern as partials/announcement-banner-content.blade.php). --}}
<article class="prose-legal">
    <h1 class="font-display font-bold text-2xl sm:text-3xl text-maroon-900" x-text="title"></h1>
    <p class="text-sm text-maroon-400 mt-2 mb-8">{{ __('Last Updated') }}: <span x-text="updatedAt"></span></p>
    <div class="legal-body text-maroon-700 leading-relaxed" x-html="content"></div>
</article>

<style>
    {{-- shared with the admin live-preview pane (see comment above), which never receives the
         customer theme override — using the CSS vars (not literal hex) means this still resolves
         to plain maroon/gold there while correctly following whatever theme the customer site
         has picked everywhere else. --}}
    .legal-body h2 { font-family: 'Baloo 2', sans-serif; font-weight: 700; font-size: 1.25rem; color: rgb(var(--color-maroon-500)); margin: 2rem 0 0.75rem; }
    .legal-body h3 { font-weight: 700; font-size: 1.05rem; color: rgb(var(--color-maroon-500)); margin: 1.5rem 0 0.5rem; }
    .legal-body h4 { font-weight: 700; font-size: 0.95rem; color: rgb(var(--color-maroon-500)); margin: 1.25rem 0 0.4rem; }
    .legal-body h2:first-child, .legal-body h3:first-child { margin-top: 0; }
    .legal-body p { margin: 0 0 0.9rem; }
    .legal-body ul, .legal-body ol { margin: 0 0 0.9rem; padding-left: 1.4rem; }
    .legal-body ul { list-style: disc; }
    .legal-body ol { list-style: decimal; }
    .legal-body li { margin-bottom: 0.35rem; }
    .legal-body blockquote { border-left: 3px solid rgb(var(--color-gold-500)); padding-left: 1rem; color: #6b7280; margin: 1rem 0; font-style: italic; }
    .legal-body a { color: rgb(var(--color-maroon-500)); text-decoration: underline; text-underline-offset: 2px; }
    .legal-body a:hover { color: rgb(var(--color-gold-500)); }
    .legal-body strong, .legal-body b { color: rgb(var(--color-maroon-800)); }
</style>
